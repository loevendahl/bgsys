<?php
/*
  $Id: checkout_process.php,v 1.33 2010-09-16 10:17:28 deepthy Exp $
  Copyright © 2007 Lux BGsys.dk
*/



  include('system/includes/application_top.php');
  
  if (isset($_GET['bgsid'])) { // callback from quickpay
    bg_session_id($_GET['bgsid']);
    bg_session_start();
  }
  
// if the customer is not logged on, redirect them to the login page
  if (!bg_session_is_registered('customer_id')) {
    $navigation->set_snapshot(array('mode' => 'SSL', 'page' => SYSTEM_FOLDER . MODULE_CHECKOUT . FILENAME_CHECKOUT_PAYMENT));
    bg_redirect(bg_href_link(FILENAME_LOGIN, '', 'SSL'));
  }

  if (!bg_session_is_registered('sendto')) {
    bg_redirect(bg_href_link(SYSTEM_FOLDER . MODULE_CHECKOUT . FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
  }
    
  if ((bg_not_null(MODULE_PAYMENT_INSTALLED)) && (!bg_session_is_registered('payment')) && ($_SESSION['payment']=='') && (!$customer_shopping_points_spending)) {
    
    bg_redirect(bg_href_link(SYSTEM_FOLDER . MODULE_CHECKOUT . FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
 }
if($_SESSION['payment']=='')
{
 bg_redirect(bg_href_link(SYSTEM_FOLDER . MODULE_CHECKOUT . FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));   
}
// avoid hack attempts during the checkout procedure by checking the internal cartID
  if (isset($cart->cartID) && bg_session_is_registered('cartID')) {
    if ($cart->cartID != $cartID) {
      bg_redirect(bg_href_link(SYSTEM_FOLDER . MODULE_CHECKOUT . FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
    }
  }

  language_variables(FILENAME_CHECKOUT_PROCESS, $language, $languages_id);
  include(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_PROCESS);
  
  
//shipping date ADI 
//require(DIR_WS_OBJECTS . 'shipsched.php');
//$shipsched=new ShippingSchedule;
//if(!$shipsched->DateStillValid($_SESSION['shipdate'])){
//	unset($_SESSION['shipdate']);
//	bg_redirect(bg_href_link(SYSTEM_FOLDER . MODULE_CHECKOUT . FILENAME_CHECKOUT_SHIPPING, 'error=expired_arrival_date', 'SSL')); //ERR MSG ON NEXT PAGE
//}
  

// load selected payment module
  require(DIR_WS_CLASSES . 'payment.php');
  if ($_SESSION['credit_covers']) $payment=''; //ICW added for CREDIT CLASS
  $payment_modules = new payment($payment);

// load the selected shipping module
  require(DIR_WS_CLASSES . 'shipping.php');
  $shipping_modules = new shipping($shipping);

  require(DIR_WS_OBJECTS . 'order.php');
  $order = new order;
  
//  
//// load the before_process function from the payment modules
//  $payment_modules->before_process();
//
//  require(DIR_WS_CLASSES . 'order_total.php');
//  $order_total_modules = new order_total;
//
//  $order_totals = $order_total_modules->process();
  
  
  //---PayPal WPP Modification START ---//
  //Fixes Bug#1629
  require(DIR_WS_CLASSES . 'order_total.php');
  $order_total_modules = new order_total;

  $order_totals = $order_total_modules->process();
  $points_system = bg_calc_shopping_pvalue($customer_shopping_points_spending);
  
if($_SESSION['qp_card_value']!='' && $_SESSION['payment'] == 'quickpay_gate')
	{
	$qp_des = substr($_SESSION['qp_card_value'],-1);
	$qp_des_first = MODULE_PAYMENT_QUICKPAY_GROUP . $qp_des . _DESCRIPTION;
	$qp_des_sql = bg_db_query("select configuration_value from ".TABLE_CONFIGURATION." where configuration_key='".$qp_des_first."'");
	$qp_des_result = bg_db_fetch_array($qp_des_sql);
	$quickpay_description = $qp_des_result['configuration_value'];
	}
else
	{
	$quickpay_description = '';
	}
  // load the before_process function from the payment modules
  $payment_modules->before_process();
  //---PayPal WPP Modification END ---//
  $postdanmark_servicePointId = 0;
  if($_SESSION['shipping']['id'] == 'gls_gls')
	{
//          if(array_key_exists("0", $_SESSION['gls_address']))
//           {
//           $_SESSION['gls_address'] = $_SESSION['gls_address'][0];
//           }
     
        $order->delivery['company']=$_SESSION['gls_address']['CompanyName'];
	$order->delivery['street_address'] =$_SESSION['gls_address']['Streetname'];
	$order->delivery['suburb']=$_SESSION['gls_address']['Streetname2'];
	$order->delivery['city']=$_SESSION['gls_address']['CityName'];
	$order->delivery['postcode']=$_SESSION['gls_address']['ZipCode'];
	
	$delivery_glsaddress=$order->delivery['firstname'] . ' ' . $order->delivery['lastname']."\n".$order->delivery['company']."\n".$order->delivery['street_address']."\n".$order->delivery['suburb']."\n".$order->delivery['postcode']."\n".$order->delivery['city']."\n".$order->delivery['country']['title']; 
	$export_flag=1;
	$gls_shopid = $_SESSION['gls_address']['Number'];
	} 
	 else if($_SESSION['shipping']['id'] == 'glsbusiness_glsbusiness')
	{
//          if(array_key_exists("0", $_SESSION['gls_address']))
//           {
//           $_SESSION['gls_address'] = $_SESSION['gls_address'][0];
//           }
     
    $order->delivery['company']=$_SESSION['gls_business_address']['CompanyName'];
	$order->delivery['street_address'] =$_SESSION['gls_business_address']['Streetname'];
	$order->delivery['suburb']=$_SESSION['gls_business_address']['Streetname2'];
	$order->delivery['city']=$_SESSION['gls_business_address']['CityName'];
	$order->delivery['postcode']=$_SESSION['gls_business_address']['ZipCode'];
	
	$delivery_glsaddress=$order->delivery['firstname'] . ' ' . $order->delivery['lastname']."\n".$order->delivery['company']."\n".$order->delivery['street_address']."\n".$order->delivery['suburb']."\n".$order->delivery['postcode']."\n".$order->delivery['city']."\n".$order->delivery['country']['title']; 
	$export_flag=1;
	$gls_shopid = $_SESSION['gls_business_address']['Number'];
	}
	
		 else if($_SESSION['shipping']['id'] == 'glsprivate_glsprivate')
	{
//          if(array_key_exists("0", $_SESSION['gls_address']))
//           {
//           $_SESSION['gls_address'] = $_SESSION['gls_address'][0];
//           }
     
    $order->delivery['company']=$_SESSION['gls_private_address']['CompanyName'];
	$order->delivery['street_address'] =$_SESSION['gls_private_address']['Streetname'];
	$order->delivery['suburb']=$_SESSION['gls_private_address']['Streetname2'];
	$order->delivery['city']=$_SESSION['gls_private_address']['CityName'];
	$order->delivery['postcode']=$_SESSION['gls_private_address']['ZipCode'];
	
	$delivery_glsaddress=$order->delivery['firstname'] . ' ' . $order->delivery['lastname']."\n".$order->delivery['company']."\n".$order->delivery['street_address']."\n".$order->delivery['suburb']."\n".$order->delivery['postcode']."\n".$order->delivery['city']."\n".$order->delivery['country']['title']; 
	$export_flag=1;
	$gls_shopid = $_SESSION['gls_private_address']['Number'];
	}

	else if ($_SESSION['shipping']['id']== 'postdanmark_postdanmark') {
             $address =  explode(";", $_SESSION['pdk_address']);
             $del_address = implode("<br/>",$address);
             if($address[0] != '---'){
             $postdanmark_servicePointId=$address[0];
             }
             if($address[1] != '---'){
             $order->delivery['company']=$address[1];
             }
             if($address[2] != '---'){
             $order->delivery['street_address'] =$address[2];
             }
             if($address[4] != '---'){
             $order->delivery['city']=$address[4];
             }
             if($address[3] != '---'){
             $order->delivery['postcode']=$address[3];
             }
           $del_address =  $order->delivery['company']."\n".$order->delivery['firstname'] . ' ' . $order->delivery['lastname'] ."\n".$order->delivery['street_address']."\n".$order->delivery['postcode'].' '.$order->delivery['city']."\n".$order->delivery['country']['title'];
           $delivery_glsaddress= str_replace('---', '', $del_address);
           
         } elseif($_SESSION['shipping']['id']== 'swipbox_swipbox') {
            
             $address =  explode(";", $_SESSION['sb_address']);
             $del_address = implode("<br/>",$address);
             if($address[0] != '---'){
             $order->delivery['company']=$address[0];
         }
             if($address[1] != '---'){
             $order->delivery['street_address'] =$address[1];
             }
             if($address[3] != '---'){
             $order->delivery['city']=$address[3];
             }
             if($address[2] != '---'){
             $order->delivery['postcode']=$address[2];
             }
           $del_address =  $cust_name.$order->delivery['firstname'] . ' ' . $order->delivery['lastname'] .'<br/>'.$order->delivery['street_address'].'<br/>'.$order->delivery['postcode'].' '.$order->delivery['city'];
           $delivery_glsaddress= str_replace('---', '', $del_address);
         }
        else {
	
	$delivery_glsaddress = bg_address_label($customer_id, $sendto, 0, '', "\n");
	$export_flag=0;
	}

  $sql_data_array = array('customers_id' => $customer_id,
                          'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                          'customers_company' => $order->customer['company'],
                          'customers_street_address' => $order->customer['street_address'],
                          'customers_suburb' => $order->customer['suburb'],
                          'customers_city' => $order->customer['city'],
                          'customers_postcode' => $order->customer['postcode'], 
                          'customers_state' => $order->customer['state'], 
                          'customers_country' => $order->customer['country']['title'], 
                          'customers_telephone' => $order->customer['telephone'], 
                          'customers_email_address' => $order->customer['email_address'],
                          'customers_address_format_id' => $order->customer['format_id'], 
                          'delivery_name' => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'], 
                          'delivery_company' => $order->delivery['company'],
                          'delivery_street_address' => trim($order->delivery['street_address']), 
                          'delivery_suburb' => $order->delivery['suburb'], 
                          'delivery_city' => $order->delivery['city'], 
                          'delivery_postcode' => $order->delivery['postcode'], 
                          'delivery_state' => $order->delivery['state'], 
                          'delivery_country' => $order->delivery['country']['title'], 
                          'delivery_address_format_id' => $order->delivery['format_id'], 
                          'billing_name' => $order->billing['firstname'] . ' ' . $order->billing['lastname'], 
                          'billing_company' => $order->billing['company'],
                          'billing_street_address' => $order->billing['street_address'], 
                          'billing_suburb' => $order->billing['suburb'], 
                          'billing_city' => $order->billing['city'], 
                          'billing_postcode' => $order->billing['postcode'], 
                          'billing_state' => $order->billing['state'], 
                          'billing_country' => $order->billing['country']['title'], 
                          'billing_address_format_id' => $order->billing['format_id'], 
                          'payment_method' => $order->info['payment_method'], 
                          'payment_info' => $GLOBALS['payment_info'], // Print Order
                          'cc_type' => $order->info['cc_type'], 
                          'cc_owner' => $order->info['cc_owner'], 
                          'cc_number' => $order->info['cc_number'], 
                          'cc_expires' => $order->info['cc_expires'], 
                          'date_purchased' => 'now()', 
                          'orders_status' => $order->info['order_status'],
                          'account_name' => $order->info['account_name'],
                          'account_number' => $order->info['account_number'],
                          'po_number' => $order->info['po_number'],
                          'ean_number' => $order->info['ean_number'],
                          'vat_number' => $order->info['vat_number'],
                          'currency' => $order->info['currency'], 
                          'currency_value' => $order->info['currency_value'],
                          'payment_method' => $order->info['payment_method'],
                          'shipdate' => $_SESSION['shipdate'],
                          'shiptime' => $_SESSION['shiptime'],
                          'ship_comment' => $_SESSION['ship_comment'],
			  'gls_export_flag' => $export_flag,
			  'customers_ip' => $customer_ip,
			  'quickpay_gate_description' => $quickpay_description,
			  'languages_id' => (int)$languages_id,
                          'postdanmark_servicePointId' => $postdanmark_servicePointId,
                          'shipping_method' => $_SESSION['shipping']['id'],
                          'gls_shopid' => $gls_shopid);
				  
				  
	// Code Added by Anisha	  
	if(SELECT_SHOP == 'Multi')
        {
	    $sql_data_array['orders_stores_id'] =  STORES_ID; 
        }
	// End of Code
    $_SESSION['gls_address']='';
    $_SESSION['gls_code']='';	
	
	 $_SESSION['gls_business_address']='';
    $_SESSION['gls_business_code']='';	
	
	 $_SESSION['gls_private_address']='';
    $_SESSION['gls_private_code']='';		
 

  // QuickPay changed start
  //KL changed
 // if (strncmp($payment, 'quickpay', 8) == 0 && !$_SESSION['credit_covers']) {
	   if (($_GET['qp_oid'] && $payment == 'quickpay_advanced') && !$_SESSION['credit_covers']) {
  	// Update transaction_id from db
     $order_id = $_GET['qp_oid'];
	 if($_SESSION['payment']!='quickpay_advanced') { 
       
	$transaction_query = bg_db_query("SELECT cc_transactionid FROM " . TABLE_ORDERS . " WHERE orders_id = '" . bg_db_input($order_id) . "'");
	$transaction = bg_db_fetch_array($transaction_query);
	  	
	   if($transaction['cc_transactionid'] != '2' && $transaction['cc_transactionid'] != '1' && $transaction['cc_transactionid']>999) {
		//use customer ip not QP ip 
		$sql_data_array['customers_ip'] = $_SESSION['qp_customer_ip'];
		
		bg_db_perform(TABLE_ORDERS, $sql_data_array, 'update', 'orders_id = ' . $order_id);
	  	$insert_id = $order_id; 
	        $order->info['cc_transactionid'] = $transaction['cc_transactionid'];
	   } 	      
       } else {
            $sql_data_array['customers_ip'] = $_SESSION['qp_customer_ip'];
            bg_db_perform(TABLE_ORDERS, $sql_data_array, 'update', 'orders_id = ' . $order_id);
            $insert_id = $order_id; 
           
       }
	   }
  else {
    // else do as usual
    bg_db_perform(TABLE_ORDERS, $sql_data_array);
    $insert_id = bg_db_insert_id();
  }
// QuickPay changed end  

 
  for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
  	
  	//adi update
  	if(BGSYS_MODULES_C5 == 'true'){
	  	if($order_totals[$i]['ship_code']){
	  		$ot_code_query = bg_db_query("SELECT orders_total_code FROM orders_total_codes  WHERE orders_total_module='".$order_totals[$i]['code']."' AND orders_total_submodule='".$order_totals[$i]['ship_code']."'");
	  	}else{
	  		$ot_code_query = bg_db_query("SELECT orders_total_code FROM orders_total_codes  WHERE orders_total_module='".$order_totals[$i]['code']."'");
	  	}
	  	$ot_code = bg_db_fetch_array($ot_code_query);
  	}
  	
    $sql_data_array = array('orders_id' => $insert_id,
                            'title' => $order_totals[$i]['title'],
                            'text' => $order_totals[$i]['text'],
                            'value' => $order_totals[$i]['value'],
                            'class' => $order_totals[$i]['code'],
                            'sort_order' => $order_totals[$i]['sort_order']);
                            
    if(BGSYS_MODULES_C5 == 'true'){                       
    	$sql_data_array['orders_total_code'] = $ot_code['orders_total_code'];//adi update                            
    }
                            
    bg_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
  }
#### Points/Rewards Module V2.1beta balance customer points BOF ####
  if ((USE_POINTS_SYSTEM == 'true') && (USE_REDEEM_SYSTEM == 'true')) {
// customer pending points added 
      if ($order->info['total'] > 0) {
	      $points_toadd = get_points_toadd($order);
	      $points_comment = 'TEXT_DEFAULT_COMMENT';
	      $points_type = 'SP';
	      if ((get_redemption_awards($customer_shopping_points_spending) == true) && ($points_toadd >0)) {
		      bg_add_pending_points($customer_id, $insert_id, $points_toadd, $points_comment, $points_type);
	      }
      }
// customer referral points added 
      if ((bg_session_is_registered('customer_referral')) && (bg_not_null(USE_REFERRAL_SYSTEM))) {
	      $referral_twice_query = bg_db_query("select unique_id from " . TABLE_CUSTOMERS_POINTS_PENDING . " where orders_id = '". (int)$insert_id ."' and points_type = 'RF' limit 1");
	      if (!bg_db_num_rows($referral_twice_query)) {
		      $points_toadd = USE_REFERRAL_SYSTEM;
		      $points_comment = 'TEXT_DEFAULT_REFERRAL';
		      $points_type = 'RF';
		      bg_add_pending_points($customer_referral, $insert_id, $points_toadd, $points_comment, $points_type);
	      }
      }
// customer shoppping points account balanced 
      if ($customer_shopping_points_spending) {
	      bg_redeemed_points($customer_id, $insert_id, $customer_shopping_points_spending);
      }
  }
#### Points/Rewards Module V2.1beta balance customer points EOF ####*/
  $customer_notification = (SEND_EMAILS == 'true') ? '1' : '0';
  $sql_data_array = array('orders_id' => $insert_id,
                          'orders_status_id' => $order->info['order_status'],
                          'date_added' => 'now()',
                          'customer_notified' => $customer_notification,
                          'comments' => $order->info['comments']);
  bg_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

// initialized for the email confirmation
  $products_ordered = '';
  $subtotal = 0;
  $total_tax = 0;

  for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
// Stock Update - Joao Correia
//++++ QT Pro: Begin Changed code
    $products_stock_attributes=null;
    if (STOCK_LIMITED == 'true') {
        $products_attributes = $order->products[$i]['attributes'];
//      if (DOWNLOAD_ENABLED == 'true') {
//++++ QT Pro: End Changed Code
      $stock_query_raw = "SELECT products_quantity, products_bundle, pad.products_attributes_filename 
                          FROM " . TABLE_PRODUCTS . " p
                          LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                           ON p.products_id=pa.products_id
                          LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                           ON pa.products_attributes_id=pad.products_attributes_id
                          WHERE p.products_id = '" . bg_get_prid($order->products[$i]['id']) . "'";
// Will work with only one option for downloadable products
// otherwise, we have to build the query dynamically with a loop
//++++ QT Pro: Begin Changed code
//      $products_attributes = $order->products[$i]['attributes'];
//++++ QT Pro: End Changed Code
      if (is_array($products_attributes)) {
        $stock_query_raw .= " AND pa.options_id = '" . $products_attributes[0]['option_id'] . "' AND pa.options_values_id = '" . $products_attributes[0]['value_id'] . "'";
      }
      $stock_query = bg_db_query($stock_query_raw);
      if (bg_db_num_rows($stock_query) > 0) {
        $stock_values = bg_db_fetch_array($stock_query);
//++++ QT Pro: Begin Changed code
        $actual_stock_bought = $order->products[$i]['qty'];
        $download_selected = false;
        if ((DOWNLOAD_ENABLED == 'true') && isset($stock_values['products_attributes_filename']) && bg_not_null($stock_values['products_attributes_filename'])) {
          $download_selected = true;
          $products_stock_attributes='$$DOWNLOAD$$';
        }
//      If not downloadable and attributes present, adjust attribute stock
        if (!$download_selected && is_array($products_attributes)) {
          $all_nonstocked = true;
          $products_stock_attributes_array = array();
          foreach ($products_attributes as $attribute) {

//**si** 14-11-05 fix missing att list
if ($attribute['track_stock'] == 1) {
              $products_stock_attributes_array[] = $attribute['option_id'] . "-" . $attribute['value_id']; }
//$products_stock_attributes_array[] = $attribute['option_id'] . "-" . $attribute['value_id'];
if ($attribute['track_stock'] == 1) {
//**si** 14-11-05 end

              $all_nonstocked = false;
            }
          } 
          if ($all_nonstocked) {
            $actual_stock_bought = $order->products[$i]['qty'];

//**si** 14-11-05 fix missing att list
asort($products_stock_attributes_array, SORT_NUMERIC);
$products_stock_attributes = implode(",", $products_stock_attributes_array);
//**si** 14-11-05 end

          }  else {
            asort($products_stock_attributes_array, SORT_NUMERIC);
            $products_stock_attributes = implode(",", $products_stock_attributes_array);
            $attributes_stock_query = bg_db_query("select products_stock_quantity from " . TABLE_PRODUCTS_STOCK . " where products_stock_attributes = '$products_stock_attributes' AND products_id = '" . bg_get_prid($order->products[$i]['id']) . "'");
            if (bg_db_num_rows($attributes_stock_query) > 0) {
              $attributes_stock_values = bg_db_fetch_array($attributes_stock_query);
              $attributes_stock_left = $attributes_stock_values['products_stock_quantity'] - $order->products[$i]['qty'];
              bg_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity = '" . $attributes_stock_left . "' where products_stock_attributes = '$products_stock_attributes' AND products_id = '" . bg_get_prid($order->products[$i]['id']) . "'");
              $actual_stock_bought = ($attributes_stock_left < 1) ? $attributes_stock_values['products_stock_quantity'] : $order->products[$i]['qty'];
            } else {
              $attributes_stock_left = 0 - $order->products[$i]['qty'];
              bg_db_query("insert into " . TABLE_PRODUCTS_STOCK . " (products_id, products_stock_attributes, products_stock_quantity) values ('" . bg_get_prid($order->products[$i]['id']) . "', '" . $products_stock_attributes . "', '" . $attributes_stock_left . "')");
              $actual_stock_bought = 0;
            }
          }
        }
//        $stock_query = bg_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . bg_get_prid($order->products[$i]['id']) . "'");
//      }
//      if (bg_db_num_rows($stock_query) > 0) {
//        $stock_values = bg_db_fetch_array($stock_query);
// do not decrement quantities if products_attributes_filename exists
        if (!$download_selected) {
          $stock_left = $stock_values['products_quantity'] - $actual_stock_bought;
          bg_db_query("UPDATE " . TABLE_PRODUCTS . " 
                        SET products_quantity = products_quantity - '" . $actual_stock_bought . "' 
                        WHERE products_id = '" . bg_get_prid($order->products[$i]['id']) . "'");
//++++ QT Pro: End Changed Code
          if ( ($stock_left < 1) && (DISABLE_OUT_OF_STOCK_PRODUCT == 'true') ) {
            bg_db_query("update " . TABLE_PRODUCTS . " set products_status = '0' where products_id = '" . bg_get_prid($order->products[$i]['id']) . "'");
          }
        }
      }
//++++ QT Pro: Begin Changed code
//bundled
        if ($stock_values['products_bundle'] == 'yes') {
        // order item is a bundle and must be separated
          $report_text .= "Bundle found in order : " . bg_get_prid($order->products[$i]['id']) . "<br>\n";
          $bundle_query = bg_db_query("select pb.bundle_subproducts_id, pb.bundle_subproducts_qty, p.products_model, p.products_quantity, p.products_bundle 
          from " . TABLE_PRODUCTS_BUNDLE . " pb 
          LEFT JOIN " . TABLE_PRODUCTS . " p 
          ON p.products_id=pb.bundle_subproducts_id 
          where pb.bundle_master_products_id = '" . bg_get_prid($order->products[$i]['id']) . "'");
		  	  

          while ($bundle_data = bg_db_fetch_array($bundle_query)) {
            if ($bundle_data['products_bundle'] == "yes") {
              $report_text .= "<br>level 2 bundle found in order : " . $bundle_data['products_model'] . "<br>";
              $bundle_query_nested = bg_db_query("select pb.bundle_subproducts_id, pb.bundle_subproducts_qty, p.products_model, p.products_quantity, p.products_bundle 
              from " . TABLE_PRODUCTS_BUNDLE . " pb 
              LEFT JOIN " . TABLE_PRODUCTS . " p 
              ON p.products_id=pb.bundle_subproducts_id 
              where pb.bundle_master_products_id = '" . $bundle_data['bundle_subproducts_id'] . "'");
			  while ($bundle_data_nested = bg_db_fetch_array($bundle_query_nested)) {
                $stock_left = $bundle_data_nested['products_quantity'] - $bundle_data_nested['bundle_subproducts_qty'] * $order->products[$i]['qty'];
                $report_text .= "updating level 2 item " . $bundle_data_nested['products_model'] . " : was " . $bundle_data_nested['products_quantity'] . " and number ordered is " . ($bundle_data_nested['bundle_subproducts_qty'] * $order->products[$i]['qty']) . " <br>\n";
                bg_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . $stock_left . "' where products_id = '" . $bundle_data_nested['bundle_subproducts_id'] . "'");
                
				$bundle_common_id= $bundle_data_nested['bundle_subproducts_id'];
			   
			   }
			  $stock_left = $bundle_data['products_quantity'] - $bundle_data['bundle_subproducts_qty'] * $order->products[$i]['qty'];
              $report_text .= "updating level 2 item " . $bundle_data['products_model'] . " : was " . $bundle_data['products_quantity'] . " and number ordered is " . ($bundle_data['subproduct_qty'] * $order->products[$i]['qty']) . " <br>\n";
              bg_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . $stock_left . "' where products_id = '" . $bundle_data['bundle_subproducts_id'] . "'");
			  
            } else {
              
			  if($bundle_common_id == $bundle_data['bundle_subproducts_id']) {
				// query to fetch products quantity from products table
				$prod_qty_comm_prod = bg_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . $bundle_common_id . "'");
				$common_product_qty = bg_db_fetch_array($prod_qty_comm_prod);
				$qty_common_product= $common_product_qty['products_quantity'];	 
				
				$stock_left = $qty_common_product - $bundle_data['bundle_subproducts_qty'] * $order->products[$i]['qty'];
			  } else {
				$stock_left = $bundle_data['products_quantity'] - $bundle_data['bundle_subproducts_qty'] * $order->products[$i]['qty'];  
			  }
			               
			  $report_text .= "updating level 1 item " . $bundle_data['products_model'] . " : was " . $bundle_data['products_quantity'] . " and number ordered is " . ($bundle_data['subproduct_qty'] * $order->products[$i]['qty']) . " <br>\n";
              bg_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . $stock_left . "' where products_id = '" . $bundle_data['bundle_subproducts_id'] . "'");
             
			}
          }
        } else {
          // order item is normal and should be treated as such
          $report_text .= "Normal product found in order : " . bg_get_prid($order->products[$i]['id']) . "\n";
          // do not decrement quantities if products_attributes_filename exists
          if ((DOWNLOAD_ENABLED != 'true') || (!$stock_values['products_attributes_filename'])) {
            $stock_left = $stock_values['products_quantity'] - $order->products[$i]['qty'];
          } else {
            $stock_left = $stock_values['products_quantity'];
          }
          bg_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . $stock_left . "' where products_id = '" . bg_get_prid($order->products[$i]['id']) . "'");
          if ( ($stock_left < 1) && (DISABLE_OUT_OF_STOCK_PRODUCT == 'true') ) {
            bg_db_query("update " . TABLE_PRODUCTS . " set products_status = '0' where products_id = '" . bg_get_prid($order->products[$i]['id']) . "'");
          }
        } 
//end bundled
    }


//**si** 14-11-05 fix missing att list
else {
	if ( is_array($order->products[$i]['attributes']) ) {
	  $products_stock_attributes_array = array();
	  foreach ($order->products[$i]['attributes'] as $attribute) {
	      $products_stock_attributes_array[] = $attribute['option_id'] . "-" . $attribute['value_id'];
		}
		asort($products_stock_attributes_array, SORT_NUMERIC);
		$products_stock_attributes = implode(",", $products_stock_attributes_array);
	}
}
//**si** 14-11-05 end


//++++ QT Pro: End Changed Code
// Update products_ordered (for bestsellers list)
    bg_db_query("update " . TABLE_PRODUCTS . " set products_ordered = products_ordered + " . sprintf('%d', $order->products[$i]['qty']) . " where products_id = '" . bg_get_prid($order->products[$i]['id']) . "'");

//++++ QT Pro: Begin Changed code
    if (!isset($products_stock_attributes)) $products_stock_attributes=null;
    $sql_data_array = array('orders_id' => $insert_id,
                            'products_id' => bg_get_prid($order->products[$i]['id']),
                            'products_model' => $order->products[$i]['model'],
                            'products_name' => $order->products[$i]['name'],
                            'products_price' => $order->products[$i]['price'],
                            'products_cost' => $order->products[$i]['cost'],
                            'final_price' => $order->products[$i]['final_price'],
                            'products_tax' => $order->products[$i]['tax'],
                            'products_quantity' => $order->products[$i]['qty'],
                            'products_stock_attributes' => $products_stock_attributes);
							
		// Code Added by Anisha	  
		if(SELECT_SHOP == 'Multi')
        {
	    $sql_data_array['products_distributors_id'] =  $order->products[$i]['distrib_id']; 
        }
		// End of Code				

//++++ QT Pro: End Changed Code
    bg_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);
    $order_products_id = bg_db_insert_id();
    $order_total_modules->update_credit_account($i);
//------insert customer choosen option to order--------
    $attributes_exist = '0';
    $products_ordered_attributes = '';
    if (isset($order->products[$i]['attributes'])) {
      $attributes_exist = '1';
      for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
        if (DOWNLOAD_ENABLED == 'true') {
          $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pa.option_values_partnumber, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                               from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                               left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                on pa.products_attributes_id=pad.products_attributes_id
                               where pa.products_id = '" . $order->products[$i]['id'] . "'
                                and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                                and pa.options_id = popt.products_options_id
                                and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                                and pa.options_values_id = poval.products_options_values_id
                                and popt.language_id = '" . $languages_id . "'
                                and poval.language_id = '" . $languages_id . "'";
          $attributes = bg_db_query($attributes_query); 
        } else {
          $attributes = bg_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pa.option_values_partnumber from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
        }
        $attributes_values = bg_db_fetch_array($attributes);

        $sql_data_array = array('orders_id' => $insert_id,
                                'orders_products_id' => $order_products_id,
                                'products_options' => $attributes_values['products_options_name'],
                                'products_options_values' => $order->products[$i]['attributes'][$j]['value'],
                                'options_values_price' => $attributes_values['options_values_price'],
                                'price_prefix' => $attributes_values['price_prefix'],
                                'option_values_partnumber' => $attributes_values['option_values_partnumber']);
        bg_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

        if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && bg_not_null($attributes_values['products_attributes_filename'])) {
          $sql_data_array = array('orders_id' => $insert_id,
                                  'orders_products_id' => $order_products_id,
                                  'orders_products_filename' => $attributes_values['products_attributes_filename'],
                                  'download_maxdays' => $attributes_values['products_attributes_maxdays'],
                                  'download_count' => $attributes_values['products_attributes_maxcount']);
          bg_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
        }
		
		if ((EMAIL_USE_HTML == 'true') && (SHOW_PRODUCT_IMAGE_IN_ORDER_MAIL == 'true') && (bg_not_null($order->products[$i]['image']))) {
        $products_ordered_attributes .= "\n\t&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $attributes_values['products_options_name'] . ' ' . bg_decode_specialchars($order->products[$i]['attributes'][$j]['value']);
		} else {
        $products_ordered_attributes .= "\n\t" . $attributes_values['products_options_name'] . ' ' . bg_decode_specialchars($order->products[$i]['attributes'][$j]['value']);
		}
      }
    }
//------insert customer choosen option eof ----
    $total_weight += ($order->products[$i]['qty'] * $order->products[$i]['weight']);
    $total_tax += bg_calculate_tax($total_products_price, $products_tax) * $order->products[$i]['qty'];
    $total_cost += $total_products_price;
// Custom Product Creator Begin
    if (CUSTOM_PRODUCT_CREATOR_ON == 'true'){
       if ($order->products[$i]['model'] == "Custom"){    
          $products_ordered_attributes = $order->products[$i]['description'] . '<br>' . $products_ordered_attributes;
       }
    }
// Custom Product Creator End

//for displaying the bundled products details to checkout mails
		$products_ordered_bundle='';
		$bundle_query_check = bg_db_query("SELECT products_bundle FROM " . TABLE_PRODUCTS . " WHERE products_id = '" . $order->products[$i]['id'] . "'");
		$bundle_data = bg_db_fetch_array($bundle_query_check);
		
		  if($bundle_data['products_bundle'] == 'yes') 
		  { 
	        
				  $subproduct_details = bg_db_query("select bundle_subproducts_id, bundle_subproducts_qty from products_bundle where bundle_master_products_id = '" . $order->products[$i]['id'] . "'");
					while($sub_product_row = bg_db_fetch_array($subproduct_details))
					{
						 
						$sub_attribute_query = "select products_name from products_description where products_id = '" .$sub_product_row['bundle_subproducts_id']. "' and language_id = '" . (int)$languages_id . "'";
						$sub_attribute_details = bg_db_query($sub_attribute_query);
						$sub_attribute_row = bg_db_fetch_array($sub_attribute_details);
						if ((EMAIL_USE_HTML == 'true') && (SHOW_PRODUCT_IMAGE_IN_ORDER_MAIL == 'true') && (bg_not_null($order->products[$i]['image']))) {
						$products_ordered_bundle.= "\n\t&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <i> - ".$sub_product_row['bundle_subproducts_qty'].' x ' . $sub_attribute_row['products_name'] ."</i>";
						} else {
						$products_ordered_bundle.= "\n\t <i> - ".$sub_product_row['bundle_subproducts_qty'].' x ' . $sub_attribute_row['products_name'] ."</i>";
						}
		
					}
		  
		 
		  }
	if ((EMAIL_USE_HTML == 'true') && (SHOW_PRODUCT_IMAGE_IN_ORDER_MAIL == 'true')) {
	 if(bg_not_null($order->products[$i]['image'])) {
		  
	 $order_product_image_src = DIR_IMAGE_SERVER. DIR_WS_IMAGES .$order->products[$i]['image'];
	
	 $order_product_image = '<img src="'.$order_product_image_src.'" alt="'.$order->products[$i]['name'].'" width="70" height="70">';	 
		  
	 }  else {
		  $order_product_image = '';
	 }
	 } else {
		  $order_product_image = '';
	 }
    $products_ordered .= $order_product_image. $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = ' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty'],$order->products[$i]['id']) . $products_ordered_attributes . $products_ordered_bundle . "\n";
  }
 $order_total_modules->apply_credit();

 //Include the mail sending file
require_once(DIR_WS_MODULES."mails/checkout_process_mail.php");

// Include OSC-AFFILIATE 
  require(SYSTEM_FOLDER . MODULE_AFFILIATE . 'affiliate_checkout_process.php');

// load the after_process function from the payment modules
  $payment_modules->after_process();
if(BGSYS_MODULES_SALDI=='true')
{
  include_once(SYSTEM_FOLDER . 'modules/saldi/saldi_update.php');
//  include_once('soapklient/saldi_update.php');
  saldi_update($insert_id);
} 

  $cart->reset(true);

// unregister session variables used during checkout
  bg_session_unregister('payment_processed');
  bg_session_unregister('sendto');
  bg_session_unregister('billto');
  bg_session_unregister('shipping');
  if($_SESSION['payment']!='quickpay_advanced') {
  bg_session_unregister('payment');
  }
  bg_session_unregister('comments');
  bg_session_unregister('insert_order_id');
  $order_total_modules->clear_posts();//ICW ADDED FOR CREDIT CLASS SYSTEM
  #### Points/Rewards Module V2.1beta balance customer points EOF ####*/
  if (bg_session_is_registered('customer_shopping_points')) bg_session_unregister('customer_shopping_points');
  if (bg_session_is_registered('customer_shopping_points_spending')) bg_session_unregister('customer_shopping_points_spending');
  if (bg_session_is_registered('customer_referral')) bg_session_unregister('customer_referral');
#### Points/Rewards Module V2.1beta balance customer points EOF ####*/
  
  if(SHIPPING_DATE_ESTIMATOR == 'True'){//if feature enabled
	  bg_session_unregister('shipdate');//shipping date ADI
	  bg_session_unregister('shiptime');
	  bg_session_unregister('shiptime1');
	  bg_session_unregister('shiptime2');
	  bg_session_unregister('ship_comment');
  }	  
  bg_session_unregister('quotes_array_cartID');
  
     
  if (strncmp($payment, 'quickpay', 8) != 0 || $_SESSION['credit_covers'] || $_SESSION['payment']=='quickpay_advanced') {
	// Print Order
  	if(bg_session_is_registered('credit_covers')) bg_session_unregister('credit_covers');
    bg_redirect(bg_href_link(SYSTEM_FOLDER . MODULE_CHECKOUT . FILENAME_CHECKOUT_SUCCESS, 'order_id='. $insert_id, 'SSL'));
      
  }

  require(SYSTEM_FOLDER . DIR_WS_INCLUDES . 'application_bottom.php');
?>