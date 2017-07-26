<?php
  include('system/includes/application_top.php');  
include(DIR_FS_CATALOG .'modules/qp/QuickpayApi.php');
//include(DIR_FS_CATALOG.DIR_WS_CLASSES.'QuickpayApi.php');

$oid = sprintf('%04d', $_GET["oid"]);
	
		$qp = new QuickpayApi;
$apikey =	MODULE_PAYMENT_QUICKPAY_ADVANCED_ADMIN_APIKEY;
//test
$apikey = "f2d562c815d9c0e1dea9fb2573d4053041315f902ca04a15600186d023462a0a";

		$qp->setOptions( $apikey); //former version config values...
		if(MODULE_PAYMENT_QUICKPAY_ADVANCED_SUBSCRIPTION != "Normal"){
    	$qp->mode = 'subscriptions?order_id=';
		}else{
		$qp->mode = 'payments?order_id=';
		}
    // Commit the status request, checking valid transaction id
     $str = $qp->status($oid);
	 $str["operations"][0] = array_reverse($str["operations"][0]);
 
 $qp_status = $str[0]["operations"][0]["qp_status_code"];
 $qp_type = strtolower($str[0]["type"]);
 $qp_operations_type = $str[0]["operations"][0]["type"];
 $qp_capture = $str[0]["autocapture"];
 $qp_vars = $str[0]["variables"];
 $qp_id = $str[0]["id"];
 $qp_order_id = $_GET["oid"];
 $qp_aq_status_code = $str[0]["aq_status_code"];
 $qp_aq_status_msg = $str[0]["aq_status_msg"];
  $qp_cardtype = $str[0]["metadata"]["brand"];
  $qp_cardhash_nr = $str[0]["metadata"]["hash"];
  $qp_status_msg = $str[0]["operations"][0]["qp_status_msg"]."\n"."Cardhash: ".$qp_cardhash_nr."\n";
  $qp_cardnumber = "xxxx-xxxxxx-".$str[0]["metadata"]["last4"];
  $qp_amount = $str[0]["operations"][0]["amount"];
  $qp_currency = $str[0]["currency"];
  $qp_pending = ($str[0]["pending"] == "true" ? " - pending ": "");
  $qp_expire = $str[0]["metadata"]["exp_month"]."-".$str[0]["metadata"]["exp_year"];
  $qp_cardhash = $str[0]["operations"][0]["type"].(strstr($str[0]["description"],'Subscription') ? " Subscription" : "");


 if (!$str[0]["id"]) {
	 // Request is NOT authenticated or transaction does not exist

    $sql_data_array = array('cc_transactionid' => bg_db_input(MODULE_PAYMENT_QUICKPAY_ADVANCED_ERROR_TRANSACTION_DECLINED));
    bg_db_perform(TABLE_ORDERS, $sql_data_array, 'update', "orders_id = '" . $qp_order_id . "'");

    exit();
   
 }

$qp_approved = false;
/*
20000	Approved
40000	Rejected By Acquirer
40001	Request Data Error
50000	Gateway Error
50300	Communications Error (with Acquirer)
*/

switch ($qp_status) {
    case '20000':
        // approved
        $qp_approved = true;
        break;
    case '40000':
	case '40001':
        // Error in request data.
        // write status message into order to retrieve it as error message on checkout_payment

        $sql_data_array = array('cc_transactionid' => bg_db_input($qp_status_msg),
            'last_modified' => 'now()',
            'orders_status_id' => MODULE_PAYMENT_QUICKPAY_ADVANCED_REJECTED_ORDER_STATUS_ID);

        // reject order by updating status
        bg_db_perform(TABLE_ORDERS, $sql_data_array, 'update', "orders_id = '" . $qp_order_id . "'");


      $sql_data_array = array('orders_id' => $qp_order_id,
            'orders_status_id' => MODULE_PAYMENT_QUICKPAY_ADVANCED_REJECTED_ORDER_STATUS_ID,
            'date_added' => 'now()',
            'customer_notified' => '0');
			/*,
            'comments' => 'QuickPay Payment rejected [message: '.$qp_operations_type.'-'. $qp_status_msg . ' - '.$qp_aq_status_msg.']');*/

        bg_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array,'update', "orders_id = '" . $qp_order_id . "'");

        break;

    default:

        $sql_data_array = array('cc_transactionid' => $qp_status,
            'last_modified' => 'now()');

        //  updating status
        bg_db_perform(TABLE_ORDERS, $sql_data_array, 'update', "orders_id = '" . $qp_order_id . "'");


   /*     $sql_data_array = array('orders_id' => $qp_order_id,
            'orders_status_id' => MODULE_PAYMENT_QUICKPAY_ADVANCED_REJECTED_ORDER_STATUS_ID,
            'date_added' => 'now()',
            'customer_notified' => '0',
            'comments' => 'QuickPay Payment rejected [message: '.$qp_operations_type.'-'. $qp_status_msg . ' - '.$qp_aq_status_msg.']');

        tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array); */

        break;
}

if ($qp_approved) {
 

	   
    $sql = "select orders_status, currency, currency_value from " . TABLE_ORDERS . " where orders_id = '" . $qp_order_id . "'";
    $order_query = bg_db_query($sql);

    if (bg_db_num_rows($order_query) > 0) {
        $order = bg_db_fetch_array($order_query);
/*
    $comment_status = "Transaction: ".$str["id"] . $qp_pending.' (' . $qp_cardtype . ' ' . $currencies->format($qp_amount / 100, false, $qp_currency) . ') '. $qp_status_msg;
*/
            
			// set order status as configured in the module
            $order_status_id = (MODULE_PAYMENT_QUICKPAY_ADVANCED_ORDER_STATUS_ID > 0 ? (int) MODULE_PAYMENT_QUICKPAY_ADVANCED_ORDER_STATUS_ID : (int) DEFAULT_ORDERS_STATUS_ID);

            $sql_data_array = array('cc_transactionid' => $str[0]["id"],
               'cc_type' => $qp_cardtype,
			   'cc_number' => $qp_cardnumber,
			    'cc_expires' => ($qp_expire ? $qp_expire : 'N/A'),
			    'cc_cardhash' => $qp_cardhash,
				'orders_status' => $order_status_id,
                'last_modified' => 'now()');
	
            // approve order by updating status
            bg_db_perform(TABLE_ORDERS, $sql_data_array, 'update', "orders_id = '" . $qp_order_id . "'");


            // write/update into order history
	/*		

    $sql = "select * from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id = '" . $qp_order_id . "'";
    $order_query = tep_db_query($sql);
            $sql_data_array = array('orders_id' => $qp_order_id,
                'orders_status_id' => $order_status_id,
                'date_added' => 'now()',
                'customer_notified' => '0',
                'comments' => 'QuickPay Payment '.$qp_operations_type.' successfull [ ' . $comment_status . ']');
   
  if ($qp_operations_type == "authorize" ) {

             tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
}
*/

	   }
			
 }

 

  require(SYSTEM_FOLDER . DIR_WS_INCLUDES . 'application_bottom.php');
?>

<!--{
  "id": 7,
  "order_id": "Order7",
  "accepted": true,
  "test_mode": true,
  "branding_id": null,
  "variables": {},
  "acquirer": "nets",
  "operations": [
    {
      "id": 1,
      "type": "authorize",
      "amount": 123,
      "pending": false,
      "qp_status_code": "20000",
      "qp_status_msg": "Approved",
      "aq_status_code": "000",
      "aq_status_msg": "Approved",
      "data": {},
      "created_at": "2015-03-05T10:06:18+00:00"
    }
  ],
  "metadata": {
    "type": "card",
    "brand": "quickpay-test-card",
    "last4": "0008",
    "exp_month": 8,
    "exp_year": 2019,
    "country": "DK",
    "is_3d_secure": false,
    "customer_ip": "195.41.47.54",
    "customer_country": "DK"
  },
  "created_at": "2015-03-05T10:06:18Z",
  "balance": 0,
  "currency": "DKK"
}-->