<?php

/*
  quickpay_advanced.php,
  Copyright 2016 © by BGwebshop
 */


include(DIR_FS_CATALOG .'modules/qp/QuickpayApi.php');

class quickpay_advanced {

    var $code, $title, $description, $enabled, $creditcardgroup, $num_groups;

// class constructor
    function quickpay_advanced() {
        global $order, $cardlock;

        $this->code = 'quickpay_advanced';
        $this->title = MODULE_PAYMENT_QUICKPAY_ADVANCED_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_QUICKPAY_ADVANCED_TEXT_PUBLIC_TITLE;
        $this->description = MODULE_PAYMENT_QUICKPAY_ADVANCED_TEXT_DESCRIPTION;
        $this->sort_order = MODULE_PAYMENT_QUICKPAY_ADVANCED_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_QUICKPAY_ADVANCED_STATUS == 'True') ? true : false);
        $this->creditcardgroup = array();
        $this->email_footer = ($cardlock == "ibill" || $cardlock == "viabill" ? DENUNCIATION : '');

        // CUSTOMIZE THIS SETTING FOR THE NUMBER OF PAYMENT GROUPS NEEDED
        $this->num_groups = 5;

        if ((int) MODULE_PAYMENT_QUICKPAY_ADVANCED_PREPARE_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_QUICKPAY_ADVANCED_PREPARE_ORDER_STATUS_ID;
        }

        if (is_object($order))
            $this->update_status;


        // Store online payment options in local variable
        for ($i = 1; $i <= $this->num_groups; $i++) {
            if (defined('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i) && constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i) != '') {
                if (!isset($this->creditcardgroup[constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i . '_FEE')])) {
                    $this->creditcardgroup[constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i . '_FEE')] = array();
                }
                $payment_options = preg_split('[\,\;]', constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i));
                foreach ($payment_options as $option) {
                    $msg .= $option;
                    $this->creditcardgroup[constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i . '_FEE')][] = $option;
                }
            }
        }
    }

// class methods

    function update_status() {
        global $order, $quickpay_fee, $_POST, $qp_adv_card;

        if (($this->enabled == true) && ((int) MODULE_PAYMENT_QUICKPAY_ADVANCED_ZONE > 0) && isset($order->billing['country']['id'])) {
            $check_flag = false;
            $check_query = bg_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_QUICKPAY_ADVANCED_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
            while ($check = bg_db_fetch_array($check_query)) {
                if ($check['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check['zone_id'] == $order->billing['zone_id']) {
                    $check_flag = true;
                    break;
                }
            }

            if ($check_flag == false) {
                $this->enabled = false;
            }
        }

        if (!bg_session_is_registered('qp_adv_card'))
            bg_session_register('qp_adv_card');
        if (isset($_POST['qp_adv_card']))
            $qp_adv_card = $_POST['qp_adv_card'];


        if (!bg_session_is_registered('quickpay_fee')) {
            bg_session_register('quickpay_fee');
        }
    }

    function javascript_validation() {
        // @todo: make JS confirmation work

        $js = '  if (payment_value == "' . $this->code . '") {' . "\n" .
                '     var qp_card_value = null;' . "\n" .
                '      if (document.checkout_payment.qp_adv_card.length) {' . "\n" .
                '        for (var i=0; i<document.checkout_payment.qp_adv_card.length; i++) {' . "\n" .
                '          if (document.checkout_payment.qp_adv_card[i].checked) {' . "\n" .
                '            qp_card_value = document.checkout_payment.qp_adv_card[i].value;' . "\n" .
                '          }' . "\n" .
                '        }' . "\n" .
                '      } else if (document.checkout_payment.qp_adv_card.checked) {' . "\n" .
                '        qp_card_value = document.checkout_payment.qp_adv_card.value;' . "\n" .
                '      } else if (document.checkout_payment.qp_adv_card.value) {' . "\n" .
                '        qp_card_value = document.checkout_payment.qp_adv_card.value;' . "\n" .
                '        document.checkout_payment.qp_adv_card.checked=true;' . "\n" .
                '      }' . "\n" .
                '    if (qp_card_value == null) {' . "\n" .
                '      error_message = error_message + "' . MODULE_PAYMENT_QUICKPAY_ADVANCED_TEXT_SELECT_CARD . '";' . "\n" .
                '      error = 1;' . "\n" .
                '    }' . "\n" .
                ' if (document.checkout_payment.cardlock.value == null) {' . "\n" .
                '      error_message = error_message + "' . MODULE_PAYMENT_QUICKPAY_ADVANCED_TEXT_SELECT_CARD . '";' . "\n" .
                '      error = 1;' . "\n" .
                '    }' . "\n" .
                '  }' . "\n";
        return $js;
    }

    function selection() {
        global $order, $currencies, $qp_adv_card, $cardlock;
        $qty_groups = 1;
        $fees = array();
        for ($i = 1; $i <= $this->num_groups; $i++) {
            if (constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i) == '') {
                continue;
            }
            if (constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i . '_FEE') == '') {
                continue;
            } else {
                $fees[$i] = constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i . '_FEE');
            }
            $qty_groups++;
        }

        if ($qty_groups > 1) {
            $selection = array('id' => $this->code,
                'module' => $this->title . bg_draw_hidden_field('cardlock', $cardlock));
            $selection['module'] .= bg_draw_hidden_field('qp_adv_card', (isset($fees[1])) ? $fees[1] : '0');
        } else {
            $selection = array('id' => $this->code,
                               'module' => $this->title);
        }
        
        if(ENABLE_EXPRESS_CHECKOUT=='true')
          {
        $js_function = '
        <script language="javascript"><!-- 
          function setQuickPay_advanced() {
          	var radioLength = document.checkout.payment.length;
          	for(var i = 0; i < radioLength; i++) {
          		document.checkout.payment[i].checked = false;
          		if(document.checkout.payment[i].value == "quickpay_advanced") {
          			document.checkout.payment[i].checked = true;
          		}
          	}
          }
          function selectQuickPayAdvRowEffect(object, buttonSelect) {
            if (!selected) {
              if (document.getElementById) {
                selected = document.getElementById("defaultSelected");
              } else {
                selected = document.all["defaultSelected"];
              }
            }

            if (selected) selected.className = "moduleRow";
            object.className = "moduleRowSelected";
            selected = object;
          
          // one button is not an array
            if (document.checkout.qp_adv_card[0]) {
              document.checkout.qp_adv_card[buttonSelect].checked=false;
            } else {
              document.checkout.qp_adv_card.checked=true;
            }
            setQuickPay_advanced();
          }
        //--></script>
        ';
          }else
          {
            $js_function = '
        <script language="javascript"><!-- 
          function setQuickPay_advanced() {
          	var radioLength = document.checkout_payment.payment.length;
          	for(var i = 0; i < radioLength; i++) {
          		document.checkout_payment.payment[i].checked = false;
          		if(document.checkout_payment.payment[i].value == "quickpay_advanced") {
          			document.checkout_payment.payment[i].checked = true;
          		}
          	}
          }
          function selectQuickPayAdvRowEffect(object, buttonSelect) {
            if (!selected) {
              if (document.getElementById) {
                selected = document.getElementById("defaultSelected");
              } else {
                selected = document.all["defaultSelected"];
              }
            }
          
            if (selected) selected.className = "moduleRow";
            object.className = "moduleRowSelected";
            selected = object;
          
          // one button is not an array
            if (document.checkout_payment.qp_adv_card[0]) {
              document.checkout_payment.qp_adv_card[buttonSelect].checked=false;
            } else {
              document.checkout_payment.qp_adv_card.checked=true;
            }
            setQuickPay_advanced();
          }
        //--></script>
        ';
          }
        
        $selection['module'] .= $js_function;
        $selection['fields'] = array();
        $msg = '';
        $optscount = 0;
        for ($i = 1; $i <= $this->num_groups; $i++) {
            $options_text = '';
            if (defined('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i) && constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i) != '') {
                $payment_options = preg_split('[\,\;]', constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i));
                foreach ($payment_options as $option) {

                    if ($option == "creditcard") {
                        $optscount++;
                        //You can extend the following cards-array and upload corresponding titled images to images/icons
                        $cards = array('dankort', 'visa', 'american-express', 'jcb', 'mastercard');
                        foreach ($cards as $optionc) {
                            $iconc = "";
                            $iconc = (file_exists(DIR_WS_ICONS . $optionc . ".png") ? DIR_WS_ICONS . $optionc . ".png" : $iconc);
                            $iconc = (file_exists(DIR_WS_ICONS . $optionc . ".jpg") ? DIR_WS_ICONS . $optionc . ".jpg" : $iconc);
                            $iconc = (file_exists(DIR_WS_ICONS . $optionc . ".gif") ? DIR_WS_ICONS . $optionc . ".gif" : $iconc);
                            //define payment icon width
                            $w = 35;
                            $h = 22;
                            $space = 5;

                            $msg .= bg_image($iconc, $optionc, $w, $h, 'style="position:relative;border:0px;float:left;margin:' . $space . 'px;" ');
                        }
                        $options_text = $msg;


                        $cost = $this->calculate_order_fee($order->info['total'], $fees[$i]);


                        if ($qty_groups == 1) {

                            $selection = array('id' => $this->code,
                                'module' => '<table width="100%" border="0">
                                                            <tr class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectQuickPayAdvRowEffect(this, ' . ($optscount + 0) . ',\'' . $option . '\')">
                                                            <td class="main">' . $options_text . ($cost != 0 ? ' (+' . $currencies->format($cost, true, $order->info['currency'], $order->info['currency_value']) . ')' : '') . '</td>
                                                            </tr></table>' . bg_draw_hidden_field('cardlock', $option) . bg_draw_hidden_field('qp_adv_card', (isset($fees[1])) ? $fees[1] : '0'));
                        } else {
                           if(ENABLE_EXPRESS_CHECKOUT=='true')
                           {
                            $selection['fields'][] = array('title' => '<table width="100%" border="0">
                                                                           <tr class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectQuickPayAdvRowEffect(this, ' . ($optscount + 0) . ',\'' . $option . '\')">
                                                                           <td class="main" >' . $options_text . '</td></tr></table>',
                                                               'field' => ($cost != 0 ? ' (+' . $currencies->format($cost, true, $order->info['currency'], $order->info['currency_value']) . ') ' : '') . bg_draw_radio_field('qp_adv_card', $fees[$i], ($option == $cardlock ? true : false), ' onClick="setQuickPay_advanced(); document.checkout.cardlock.value = \'' . $option . '\';" '));
                           } else {
                                $selection['fields'][] = array('title' => '<table width="100%" border="0">
                                                                           <tr class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectQuickPayAdvRowEffect(this, ' . ($optscount + 0) . ',\'' . $option . '\')">
                                                                           <td class="main" >' . $options_text . '</td></tr></table>',
                                                               'field' => ($cost != 0 ? ' (+' . $currencies->format($cost, true, $order->info['currency'], $order->info['currency_value']) . ') ' : '') . bg_draw_radio_field('qp_adv_card', $fees[$i], ($option == $cardlock ? true : false), ' onClick="setQuickPay_advanced(); document.checkout_payment.cardlock.value = \'' . $option . '\';" '));
                           }
                        }//end qty=1
                    }

                    if ($option != "creditcard") {
                        //upload images to images/icons corresponding to your chosen cardlock groups in your payment module settings
                        //OPTIONAL image if different from cardlogo, add _payment to filename

                        $selectedopts = explode(",", $option);
                        $icon = "";
                        foreach ($selectedopts as $option) {
                            $optscount++;

                            $icon = (file_exists(DIR_WS_ICONS . $option . ".png") ? DIR_WS_ICONS . $option . ".png" : $icon);
                            $icon = (file_exists(DIR_WS_ICONS . $option . ".jpg") ? DIR_WS_ICONS . $option . ".jpg" : $icon);
                            $icon = (file_exists(DIR_WS_ICONS . $option . ".gif") ? DIR_WS_ICONS . $option . ".gif" : $icon);
                            $icon = (file_exists(DIR_WS_ICONS . $option . "_payment.png") ? DIR_WS_ICONS . $option . "_payment.png" : $icon);
                            $icon = (file_exists(DIR_WS_ICONS . $option . "_payment.jpg") ? DIR_WS_ICONS . $option . "_payment.jpg" : $icon);
                            $icon = (file_exists(DIR_WS_ICONS . $option . "_payment.gif") ? DIR_WS_ICONS . $option . "_payment.gif" : $icon);
                            $space = 5;
                            //define payment icon width
                            if (strstr($icon, "_payment")) {
                                $w = 120;
                                $h = 27;
                            } else {
                                $w = 35;
                                $h = 22;
                            }

                            $cost = $this->calculate_order_fee($order->info['total'], $fees[$i]);
                            $options_text = '<table><tr><td width="100px">' . bg_image($icon, $this->get_payment_options_name($option), $w, $h, ' style="position:relative;border:0px;float:left;margin:' . $space . 'px;" ') . '</td><td class="main">' . $this->get_payment_options_name($option) . ($cost != 0 ? ' <font size="2" >(+' . $currencies->format($cost, true, $order->info['currency'], $order->info['currency_value']) . ')</font>' : '') . '</td></tr></table>';


                            if ($qty_groups == 1) {
                              
                                $selection = array('id' => $this->code,
                                    'module' => '<table width="100%" border="0">
                                                                <tr class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectQuickPayAdvRowEffect(this, ' . ($optscount + 0) . ',\'' . $option . '\')">
                                                                <td class="main">' . $options_text . ' </td>
                                                                </tr></table>' . bg_draw_hidden_field('cardlock', $option) . bg_draw_hidden_field('qp_adv_card', (isset($fees[1])) ? $fees[1] : '0'));
                            } else {
                               if(ENABLE_EXPRESS_CHECKOUT=='true')
                                {
                              
                                $selection['fields'][] = array('title' => '<table width="100%" border="0">
                                                                          <tr class="moduleRowAdv" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectQuickPayAdvRowEffect(this, ' . ($optscount + 0) . ',\'' . $option . '\')">
                                                                          <td class="main" >' . $options_text . '</td></tr></table>',
                                                               'field' => bg_draw_radio_field('qp_adv_card', $fees[$i], ($option == $cardlock ? true : false), ' onClick="setQuickPay_advanced();document.checkout.cardlock.value = \'' . $option . '\';" '));
                                } else {
                                $selection['fields'][] = array('title' => '<table width="100%" border="0">
                                                                          <tr class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectQuickPayAdvRowEffect(this, ' . ($optscount + 0) . ',\'' . $option . '\')">
                                                                          <td class="main" >' . $options_text . '</td></tr></table>',
                                                               'field' => bg_draw_radio_field('qp_adv_card', $fees[$i], ($option == $cardlock ? true : false), ' onClick="setQuickPay_advanced();document.checkout_payment.cardlock.value = \'' . $option . '\';" '));    
                                }
                            }//end qty
                       }
                    }
                }
            }
        }

        return $selection;
    }

    function pre_confirmation_check() {
        global $cartID, $cart;

        if (empty($cart->cartID)) {
            $cartID = $cart->cartID = $cart->generate_cart_id();
        }

        if (!bg_session_is_registered('cartID')) {
            bg_session_register('cartID');
        }
        $this->get_order_fee();
    }

    function confirmation() {
        global $_POST;

        if ($_SESSION['quickpay_fee'] == 0.0) {
            $confirmation = array('title' => MODULE_PAYMENT_QUICKPAY_ADVANCED_TEXT_DESCRIPTION . ': ' . MODULE_PAYMENT_QUICKPAY_ADVANCED_CREDITCARD_TEXT);
        } else {
            $options_text = '';
            $options_text_array = array();
            foreach (($this->creditcardgroup[$_POST['qp_adv_card']]) as $option) {
                $tmp_option = $this->get_payment_options_name($option);
                if (sizeof($options_text_array) > 0 && !in_array($tmp_option, $options_text_array)) {
                    $options_text_array[] = $tmp_option;
                    $options_text .= $tmp_option . '&nbsp;';
                } elseif (sizeof($options_text_array) <= 0) {
                    $options_text_array[] = $tmp_option;
                    $options_text .= $tmp_option . '&nbsp;';
                }
            }
            $options_text = substr($options_text, 0, -1);
            $confirmation = array('title' => MODULE_PAYMENT_QUICKPAY_ADVANCED_TEXT_DESCRIPTION . ': ' . $options_text);
        }
        return $confirmation;
    }

    function process_button() {
        global $_POST;
        $process_button_string = bg_draw_hidden_field('qp_adv_card', $_POST['qp_adv_card']);
        $process_button_string .= bg_draw_hidden_field('cardlock', $_POST['cardlock']);
		
		return $process_button_string;
    }

    function before_process() {
        global $_POST, $order, $currencies, $language, $order_id, $order_total_modules;


		
		if ($order->info['total'] <= 0) {
            $_SESSION['credit_covers'] = 1;
            return false;
        }
  
      
        $process_parameters = array();
        $flag_qp_order_id = false;

      	
      	    if($_GET['qp_oid'] > 0){
      		
          		$order_number_query = bg_db_query("SELECT * FROM " . TABLE_ORDERS . " WHERE orders_id = '".bg_db_input((int)$_GET['qp_oid'])."' ");
          		if(bg_db_num_rows($order_number_query) > 0 ){
          			$flag_qp_order_id = true;
          			bg_session_register('order_id');
          			$_SESSION['order_id'] = (int)$_GET['qp_oid'];	
          			$order_id = (int)$_GET['qp_oid'];
          		
                        }
      	   }
  
  //KL added .avoid the following functions from doing anything if called  as continue url from Quickpay gateway. Functions are only needed to initiate the payment process.
if (!$_GET['qp_oid']) {


        // We need order_id to pass to the gateway
        // But we do not have the order_id at this moment. 
        // So instead we create the order_id now and bypass the usual checkout_process.php function
       
        if (!bg_session_is_registered('order_id') || $_SESSION['order_id'] <= 0 || $_SESSION['order_id'] == '') {
            bg_session_register('order_id');
            $order_id = $this->create_order();
            $_SESSION['order_id'] = $order_id;
            $_SESSION['qp_customer_ip'] = $_SERVER['REMOTE_ADDR'];
        } else {

            // Authenticate order_id with QP using API
            $order_id = $_SESSION['order_id'];
		
	   
	   /*  ////KL section Commented out: 
	   The following sequence will not work here: You will not get any order payment status from Quickpay  if the payment link is not created . Payment link is created /updated below and user sent to payment window.
	   
		 Authorization check functions and status assignment functions are implemented in the callback script, custom_qp_callback.php.
		 
		 If any errors should occur, the cancelurl will be used by the gateway
		 
		 		
          $order_status_approved_id = (MODULE_PAYMENT_QUICKPAY_ADVANCED_ORDER_STATUS_ID > 0 ? (int) MODULE_PAYMENT_QUICKPAY_ADVANCED_ORDER_STATUS_ID : (int) DEFAULT_ORDERS_STATUS_ID);

            $mode = (MODULE_PAYMENT_QUICKPAY_ADVANCED_SUBSCRIPTION == "Normal" ? "" : "1");
      
	
		 
		    $checkorderid = $this->get_quickpay_order_status($order_id, $mode);

            if($checkorderid["oid"] != $order_id){
              $fp = fopen('/home/bgtest/public_html/responsive/quickpay.txt', 'a+');  
              fwrite($fp, 'QP API Authenticatication Failed-> Redirected to chackout payment.');
              fclose($fp);
              bg_redirect(bg_href_link(SYSTEM_FOLDER . MODULE_CHECKOUT . FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code, 'SSL'));

            } else {

            // adopt order status of order object to "real" status
            $order->info['order_status'] = $order_status_approved_id;
            //for debugging with FireBug / FirePHP
            global $firephp;
            if (isset($firephp)) {
                $firephp->log($order_id, 'order_id');
            }
            // everything is fine... continue
            return;
                    }
*/	
            }
			//testing account purpose...
		if(!defined('MODULE_PAYMENT_QUICKPAY_ADVANCED_ORDERPREFIX')){define('MODULE_PAYMENT_QUICKPAY_ADVANCED_ORDERPREFIX','BGSYS');
		
		//Use the prefix consistently
        $qp_order_id = MODULE_PAYMENT_QUICKPAY_ADVANCED_ORDERPREFIX.sprintf('%04d', $order_id);

        // Calculate the total order amount for the order (the same way as in checkout_process.php)
//        require(DIR_WS_CLASSES . 'order_total.php');//present in checkout_process
//        $order_total_modules = new order_total;
//        $order_total_modules->process();
        // Here we want to call the Quickpay payment window.
        $qp_language = "da";
        switch ($language) {
            case "english": $qp_language = "en";
                break;
            case "swedish": $qp_language = "se";
                break;
            case "norwegian": $qp_language = "no";
                break;
            case "german": $qp_language = "de";
                break;
            case "french": $qp_language = "fr";
                break;
        }

        $creditcards_lock_array = $this->creditcardgroup[$_POST['qp_adv_card']];
        $cardtypelock = (is_array($creditcards_lock_array)) ? implode(",", $creditcards_lock_array) : '';

        if ($order->info['currency'] != 'JPY') {
            $qp_order_amount = 100 * $currencies->calculate($order->info['total'], true, $order->info['currency'], $order->info['currency_value'], '.', '');
        } else {
            $qp_order_amount = $currencies->calculate($order->info['total'], true, $order->info['currency'], $order->info['currency_value'], '.', '');
        }
        $currency_code = $order->info['currency'];
		
	
      $merchant_id = MODULE_PAYMENT_QUICKPAY_GATE_SHOPID;
	  


        $qp_continueurl = bg_href_link(SYSTEM_FOLDER . MODULE_CHECKOUT . FILENAME_CHECKOUT_PROCESS, bg_session_name() . '=' . bg_session_id().'&qp_oid='.$order_id, 'SSL');
	   
        $qp_cancelurl = bg_href_link(SYSTEM_FOLDER . MODULE_CHECKOUT . FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code, 'SSL');
        
        $qp_callbackurl = bg_href_link('custom_qp_callback10.php','oid='.$order_id, 'SSL'); 
      
        $qp_merchant_id = MODULE_PAYMENT_QUICKPAY_ADVANCED_MERCHANTID;
        $qp_aggreement_id = MODULE_PAYMENT_QUICKPAY_ADVANCED_AGGREEMENTID;
        $qp_branding_id = "";

        //todo...
        $qp_subscription = (MODULE_PAYMENT_QUICKPAY_ADVANCED_SUBSCRIPTION == "Normal" ? "" : "1");
        $qp_description = "Merchant " . $qp_merchant_id;

// Calculate the total order amount for the order (the same way as in checkout_process.php)
      
        $qp_autocapture = "0";
        $qp_cardtypelock = $_POST['cardlock'];
       
        $qp_autofee='0';
        $qp_version = "v10";
//KL changed. You are using the admin API key for the API user (former version of the plugin)
        $qp_apikey = MODULE_PAYMENT_QUICKPAY_ADVANCED_ADMIN_APIKEY;

        $qp_product_id = "P03";
        $qp_category = MODULE_PAYMENT_QUICKPAY_ADVANCED_PAII_CAT;
        $qp_reference_title = $qp_order_id;
        if($order->info['tax']!=''){
        $qp_vat_amount = $order->info['tax'];
        } else {
        $qp_vat_amount = '';    
        }
        //replace &amp; in simple & 
        $qp_continueurl = str_replace('&amp;', '&', $qp_continueurl);
        $qp_cancelurl = str_replace('&amp;', '&', $qp_cancelurl);
        $qp_callbackurl = str_replace('&amp;', '&', $qp_callbackurl);

        if (ENABLE_SSL == 'true') {
            $HTTP_SERVER_LINK = HTTPS_SERVER;
        } else {
            $HTTP_SERVER_LINK = HTTP_SERVER;
        }

        if (defined('BG_DOCTYPE_LOCAL') && BG_DOCTYPE_LOCAL != '') {
            echo '<!doctype html public "' . BG_DOCTYPE_LOCAL . '">';
        } elseif (defined('BG_DOCTYPE') && BG_DOCTYPE != '') {
            echo '<!doctype html public "' . BG_DOCTYPE . '">';
        } else {
            echo '<!DOCTYPE html>';
        }
//        echo '<html ' . HTML_PARAMS . '>
//              <head>
//                <title>' . STORE_NAME . '</title>
//                <meta http-equiv="Content-Type" content="text/html; charset=' . CHARSET . '">
//                <style type="text/css">
//                  body {background-color:#FFFFFF;}
//                  body, td, div {font-family: verdana, arial, sans-serif;}
//                </style>
//              </head>
//              <body onload="return document.quickpay_advanced_payment_info.submit();">';
//
//     
//        $params = array(
//            'agreement_id' => $qp_aggreement_id,
//            'amount' => $qp_order_amount,
//            'autocapture' => $qp_autocapture,
//            'autofee' => $qp_autofee,
//            'callbackurl' => $qp_callbackurl,
//            'cancelurl' => $qp_cancelurl,
//            'continueurl' => $qp_continueurl,
//            'currency' => $currency_code,
//            'description' => $qp_description,
//            'language' => $qp_language,
//            'merchant_id' => $qp_merchant_id,
//            'order_id' => $qp_order_id,
//            'payment_methods' => $qp_cardtypelock,
//            'vat_amount' => $qp_vat_amount,
//            'version' => 'v10'
//        );
//
//         $params["checksum"] = $this->sign($params, $qp_apikey);
//
//
//        echo bg_draw_form('quickpay_advanced_payment_info', 'https://payment.quickpay.net', 'post'/* , 'target="_blank"' */);
//        echo bg_draw_hidden_field('version', $params['version']) . "\n" .
//        bg_draw_hidden_field('merchant_id', $params['merchant_id']) . "\n" .
//        bg_draw_hidden_field('agreement_id', $params['agreement_id']) . "\n" .
//        bg_draw_hidden_field('order_id', $params['order_id']) . "\n" .        
//        bg_draw_hidden_field('amount', $params['amount']) . "\n" .
//        bg_draw_hidden_field('currency', $params['currency']) . "\n" .
//        bg_draw_hidden_field('callbackurl', $params['callbackurl']) . "\n" . 
//        bg_draw_hidden_field('cancelurl', $params['cancelurl']) . "\n" .        
//        bg_draw_hidden_field('continueurl', $params['continueurl']) . "\n" .
//        bg_draw_hidden_field('autofee', $params['autofee']) . "\n" .
//        bg_draw_hidden_field('payment_methods', $params['payment_methods']) . "\n" .   
//        bg_draw_hidden_field('description', $params['description']) . "\n" .
//        bg_draw_hidden_field('language', $params['language']) . "\n" .
//        bg_draw_hidden_field('vat_amount', $params['vat_amount']) . "\n" .
//        bg_draw_hidden_field('autocapture', $params['autocapture']) . "\n" .        
//        bg_draw_hidden_field('checksum', $params["checksum"]);
//
//        echo '<input type="image" src="' . bg_output_string($HTTP_SERVER_LINK . DIR_WS_HTTP_CATALOG . DIR_WS_LANGUAGES . $language . '/images/buttons/' . 'button_continue.gif') . '" border="0" alt="' . bg_output_string(IMAGE_BUTTON_CONTINUE) . '" >' . '</form>' . "\n";
//        echo MODULE_PAYMENT_QUICKPAY_TEXT_WAIT . "\n";
//        echo '</body></html>';
//        exit();
  
  
  //test account data KL:
  
    $qp_apikey = "f2d562c815d9c0e1dea9fb2573d4053041315f902ca04a15600186d023462a0a";
	$qp_aggreement_id = "762"; // The WINDOW user agreement id.
	$qp_merchant_id ="313";
	      
        		$process_parameters = array(
					'agreement_id'                 => $qp_aggreement_id,
					'amount'                       => $qp_order_amount,
					'autocapture'                  => $qp_autocapture,
					'autofee'                      => $qp_autofee,
					//'branding_id'                  => $qp_branding_id,
					'callbackurl'                  => $qp_callbackurl,
					'cancelurl'                    => $qp_cancelurl,
					'continueurl'                  => $qp_continueurl,
					'currency'                     => $currency_code,
					'description'                  => $qp_description,
					//'google_analytics_client_id'   => $qp_google_analytics_client_id,
					//'google_analytics_tracking_id' => $analytics_tracking_id,
					'language'                     => $qp_language,
					'merchant_id'                  => $qp_merchant_id,
					'order_id'                     => $qp_order_id,
					'payment_methods'              => $qp_cardtypelock,
					//'product_id'                   => $qp_product_id,
					//'category'                     => $qp_category,
					//'reference_title'              => $qp_reference_title,
					'vat_amount'                 => $qp_vat_amount,
                                        'subscription'               => $qp_subscription,
					'version'                      => 'v10'
						);
        

                        
              // if($_POST['callquickpay'] == "go") {
	    $apiorder= new QuickpayApi();
	$apiorder->setOptions($qp_apikey);
	//set status request mode
	$mode = (MODULE_PAYMENT_QUICKPAY_ADVANCED_SUBSCRIPTION == "Normal" ? "" : "1");
	  	//been here before?
	    $exists = $this->get_quickpay_order_status($qp_order_id, $mode);
	//print_r($exists);
    $qid = $exists["qid"];
    
	//set to create/update mode
	$apiorder->mode = (MODULE_PAYMENT_QUICKPAY_ADVANCED_SUBSCRIPTION == "Normal" ? "payments/" : "subscriptions/");
	  if($exists["qid"] == null){
             
      //create new quickpay order	
      $storder = $apiorder->createorder($qp_order_id, $currency_code, $process_parameters);
      $qid = $storder["id"];
 
	 
	  }else{
       $qid = $exists["qid"];
       }
        echo $qid;
        echo "<pre>";
      //  print_r($process_parameters);
        echo "</pre>";
        
	$storder = $apiorder->link($qid, $process_parameters);	
      //  echo $storder['url'];
//exit;
        
	echo "<script> window.location.replace('".$storder['url']."')</script>";         
			  }       
    exit();                    
}
    }

    function after_process() {
        bg_session_unregister('cardlock');
        bg_session_unregister('order_id');
        bg_session_unregister('quickpay_fee');
        bg_session_unregister('qp_adv_card');
        bg_session_unregister('cart_QuickPay_ID');
    }

    function get_error() {

        global $cart_QuickPay_ID;
        $order_id = substr($cart_QuickPay_ID, strpos($cart_QuickPay_ID, '-') + 1);

        $transaction_query = bg_db_query("select cc_transactionid from " . TABLE_ORDERS . " where orders_id = '" . $order_id . "'");
        $transaction = bg_db_fetch_array($transaction_query);
        $errorcode = $transaction['cc_transactionid'];

        // Remove transactionid for declined payment
        $sql_data_array = array('cc_transactionid' => NULL,
            'orders_status' => MODULE_PAYMENT_QUICKPAY_ADVANCED_REJECTED_ORDER_STATUS_ID);



        $error_desc = '';
        switch (urldecode($errorcode)) {
            case 1: $error_desc = MODULE_PAYMENT_QUICKPAY_ADVANCED_ERROR_TRANSACTION_DECLINED;
                break;
            case 40001: $error_desc = MODULE_PAYMENT_QUICKPAY_ADVANCED_ERROR_COMMUNICATION_FAILURE;
                break;
            case 40000: $error_desc = MODULE_PAYMENT_QUICKPAY_ADVANCED_ERROR_CARD_EXPIRED;
                break;

            case 50000: $error_desc = MODULE_PAYMENT_QUICKPAY_ADVANCED_ERROR_TRANSACTION_EXPIRED;
                break; // NO TRANSLATION!!!
            case 50300: $error_desc = MODULE_PAYMENT_QUICKPAY_ADVANCED_ERROR_COMMUNICATION_FAILURE;
                break;

            default:if ($errorcode == '' || $errorcode == 0) {
                    $error_desc = MODULE_PAYMENT_QUICKPAY_ADVANCED_ERROR_CANCELLED;
                } else {
                    $error_desc = nl2br(urldecode($errorcode)); //ERROR_CARDNO_NOT_VALID;
                }
        }
        bg_db_perform(TABLE_ORDERS, $sql_data_array, 'update', "orders_id = '" . $order_id . "'");
        $sql_data_array = array('orders_id' => $order_id,
            'orders_status_id' => MODULE_PAYMENT_QUICKPAY_ADVANCED_REJECTED_ORDER_STATUS_ID,
            'date_added' => 'now()',
            'customer_notified' => '0',
            'comments' => $error_desc);

        bg_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

        $this->after_process();
        $this->pre_confirmation_check();

        $error = array('title' => MODULE_PAYMENT_QUICKPAY_ADVANCED_TEXT_ERROR,
            'error' => $error_desc);
        return $error;
    }

    function create_order() {
        global $customer_id;

        // Create an entry in the orders-table in order to get an order-id we can use
        // This entry only works as a reservation.
        // If no other data is added, the order will not be displayed in OsCommerce
        $sql_data_array = array('customers_id' => $customer_id,
            'date_purchased' => 'now()',
            'qp_advanced_flag' => 1,
            'orders_status' => 0);
        bg_db_perform(TABLE_ORDERS, $sql_data_array);
        return bg_db_insert_id();
    }

    function output_error() {
        return false;
    }

    function check() {
        if (!isset($this->_check)) {
            $check_query = bg_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_QUICKPAY_ADVANCED_STATUS'");
            $this->_check = bg_db_num_rows($check_query);
        }
        return $this->_check;
    }

    function install() {


        $qp_paymentzone = (defined('MODULE_PAYMENT_QUICKPAY_ADVANCED_ZONE')) ? MODULE_PAYMENT_QUICKPAY_ADVANCED_ZONE : '0'; // take eventually old value
        $qp_shopid = (defined('MODULE_PAYMENT_QUICKPAY_ADVANCED_SHOPID')) ? MODULE_PAYMENT_QUICKPAY_ADVANCED_SHOPID : ''; // take eventually old value
        $qp_md5word = (defined('MODULE_PAYMENT_QUICKPAY_ADVANCED_MD5WORD')) ? MODULE_PAYMENT_QUICKPAY_ADVANCED_MD5WORD : ''; // take eventually old value

        bg_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) values ('MODULE_PAYMENT_QUICKPAY_ADVANCED_STATUS', 'False', '6', '3', 'bg_cfg_select_option(array(\'True\', \'False\'), ', now())");
        bg_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, use_function, set_function, date_added) values ('MODULE_PAYMENT_QUICKPAY_ADVANCED_ZONE', '" . $qp_paymentzone . "', '6', '2', 'bg_get_zone_class_title', 'bg_cfg_pull_down_zone_classes(', now())");
        bg_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_PAYMENT_QUICKPAY_ADVANCED_MERCHANTID', '" . $qp_shopid . "', '6', '6', now())");
        bg_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_PAYMENT_QUICKPAY_ADVANCED_AGGREEMENTID', '" . $qp_shopid . "', '6', '6', now())");
        bg_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_PAYMENT_QUICKPAY_ADVANCED_APIKEY', '" . $qp_md5word . "', '6', '0', now())");
        bg_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_PAYMENT_QUICKPAY_ADVANCED_PRIVATEKEY', '', '6', '0', now())");
        bg_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_PAYMENT_QUICKPAY_ADVANCED_ADMIN_APIKEY', '', '6', '0', now())");
        bg_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_PAYMENT_QUICKPAY_ADVANCED_SORT_ORDER', '0', '6', '0', now())");
        // new settings
        bg_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) values ('MODULE_PAYMENT_QUICKPAY_ADVANCED_PAII_CAT','', '6', '0','bg_cfg_pull_down_paii_list(', now())");
        bg_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, use_function, date_added) values ('MODULE_PAYMENT_QUICKPAY_ADVANCED_PREPARE_ORDER_STATUS_ID', '" . $status_id . "', '6', '0', 'bg_cfg_pull_down_order_statuses(', 'bg_get_order_status_name', now())");
        bg_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, use_function, date_added) values ('MODULE_PAYMENT_QUICKPAY_ADVANCED_ORDER_STATUS_ID', '0', '6', '0', 'bg_cfg_pull_down_order_statuses(', 'bg_get_order_status_name', now())");
        bg_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, use_function, date_added) values ('MODULE_PAYMENT_QUICKPAY_ADVANCED_REJECTED_ORDER_STATUS_ID', '" . $status_rejected_id . "', '6', '0', 'bg_cfg_pull_down_order_statuses(', 'bg_get_order_status_name', now())");
        //update protocol 6 settings
        bg_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) values ('MODULE_PAYMENT_QUICKPAY_ADVANCED_SUBSCRIPTION', 'Normal', '6', '0', 'bg_cfg_select_option(array(\'Normal\', \'Subscription\'), ',now())");

        for ($i = 1; $i <= $this->num_groups; $i++) {
            if ($i == 1) {
                $defaultlock = 'viabill';
                $qp_groupfee = '0:0';
            } else if ($i == 2) {
                $defaultlock = 'creditcard';
                $qp_groupfee = '0:0';
            } else {
                $defaultlock = '';
                $qp_groupfee = '0:0';
            }

            $qp_group = (defined('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i)) ? constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i) : $defaultlock;
            $qp_groupfee = (defined('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i . '_FEE')) ? constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i . '_FEE') : $qp_groupfee;
            
            $qp_group = (defined('MODULE_PAYMENT_QUICKPAY_GROUP' . $i)) ? constant('MODULE_PAYMENT_QUICKPAY_GROUP' . $i) : $defaultlock;
            $qp_groupfee = (defined('MODULE_PAYMENT_QUICKPAY_GROUP' . $i . '_FEE')) ? constant('MODULE_PAYMENT_QUICKPAY_GROUP' . $i . '_FEE') : $qp_groupfee;

            bg_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP" . $i . "', '" . $qp_group . "', '6', '6', now())");
            bg_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP" . $i . "_FEE', '" . $qp_groupfee . "', '6', '6', now())");
        }

        $languages = bg_get_languages();
        for ($k = 0, $n = sizeof($languages); $k < $n; $k++) {
            $language_id = $languages[$k]['id'];

            bg_db_query("insert into " . TABLE_CONFIGURATION_DESCRIPTION . " (configuration_title, configuration_key, configuration_description, language_id) values ('Enable quickpay_advanced', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_STATUS', 'Do you want to accept Quickpay payments?', '" . $language_id . "');");
            bg_db_query("insert into " . TABLE_CONFIGURATION_DESCRIPTION . " (configuration_title, configuration_key, configuration_description, language_id) values ('Payment Zone', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_ZONE', 'If a zone is selected, only enable this payment method for that zone.', '" . $language_id . "');");
            bg_db_query("insert into " . TABLE_CONFIGURATION_DESCRIPTION . " (configuration_title, configuration_key, configuration_description, language_id) values ('Quickpay Merchant Id', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_MERCHANTID', 'Enter Merchant id', '" . $language_id . "');");
            bg_db_query("insert into " . TABLE_CONFIGURATION_DESCRIPTION . " (configuration_title, configuration_key, configuration_description, language_id) values ('Quickpay Aggreement Id', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_AGGREEMENTID', 'Enter Merchant Agreement id', '" . $language_id . "');");
            bg_db_query("insert into " . TABLE_CONFIGURATION_DESCRIPTION . " (configuration_title, configuration_key, configuration_description, language_id) values ('Set Payment window key', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_APIKEY', 'Add the gateway Payment Window key here.', '" . $language_id . "');");
            bg_db_query("insert into " . TABLE_CONFIGURATION_DESCRIPTION . " (configuration_title, configuration_key, configuration_description, language_id) values ('Merchant Private key', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_PRIVATEKEY', 'Add the Merchant Private key here.', '" . $language_id . "');");
            bg_db_query("insert into " . TABLE_CONFIGURATION_DESCRIPTION . " (configuration_title, configuration_key, configuration_description, language_id) values ('API users key', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_ADMIN_APIKEY', 'Add the API users key key here.', '" . $language_id . "');");
            bg_db_query("insert into " . TABLE_CONFIGURATION_DESCRIPTION . " (configuration_title, configuration_key, configuration_description, language_id) values ('Sort order of display.', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_SORT_ORDER', 'Sort order of display. Lowest is displayed first.', '" . $language_id . "');");
            bg_db_query("insert into " . TABLE_CONFIGURATION_DESCRIPTION . " (configuration_title, configuration_key, configuration_description, language_id) values ('Paii shop category', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_PAII_CAT', 'Shop category must be set, if using Paii cardlock (paii), ', '" . $language_id . "');");
            bg_db_query("insert into " . TABLE_CONFIGURATION_DESCRIPTION . " (configuration_title, configuration_key, configuration_description, language_id) values ('Set Preparing Order Status', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_PREPARE_ORDER_STATUS_ID', 'Set the status of prepared orders made with this payment module to this value', '" . $language_id . "');");
            bg_db_query("insert into " . TABLE_CONFIGURATION_DESCRIPTION . " (configuration_title, configuration_key, configuration_description, language_id) values ('Set Quickpay Acknowledged Order Status', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_ORDER_STATUS_ID', 'Set the status of orders made with this payment module to this value', '" . $language_id . "');");
            bg_db_query("insert into " . TABLE_CONFIGURATION_DESCRIPTION . " (configuration_title, configuration_key, configuration_description, language_id) values ('Set Quickpay Rejected Order Status', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_REJECTED_ORDER_STATUS_ID', 'Set the status of rejected orders made with this payment module to this value', '" . $language_id . "');");
            bg_db_query("insert into " . TABLE_CONFIGURATION_DESCRIPTION . " (configuration_title, configuration_key, configuration_description, language_id) values ('Subscription payment', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_SUBSCRIPTION', 'Set Subscription payment as default (normal is single payment).', '" . $language_id . "');");
            for ($i = 1; $i <= $this->num_groups; $i++) {
                bg_db_query("insert into " . TABLE_CONFIGURATION_DESCRIPTION . " (configuration_title, configuration_key, configuration_description, language_id) values ('Group " . $i . " Payment Options', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP" . $i . "', 'Comma seperated Quickpay payment options that are included in Group " . $i . ", maximum 255 chars (<a href=\"http://quickpay.dk/features/cardtypelock/\" target=\"_blank\"><u>available options</u>)</a><br>Example: <b>creditcard OR ibill OR dankort,danske-dk</b>', '" . $language_id . "');");
                bg_db_query("insert into " . TABLE_CONFIGURATION_DESCRIPTION . " (configuration_title, configuration_key, configuration_description, language_id) values ('Group " . $i . " Payments fee', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP" . $i . "_FEE', 'Fee for Group " . $i . " payments (fixed fee:percentage fee)<br>Example: <b>1.45:0.10</b>', '" . $language_id . "');");
            }
        }

        /* 	bg_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Deadline', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_DEADLINE', '900', 'Sets timeout in seconds for payment window, Defaults to 15 min.(= 900 seconds).', '6', '0', now())");
          bg_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Payment window', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_MOBILE', 'Normal', 'Show normal or mobile version payment window', '6', '0', 'bg_cfg_select_option(array(\'Normal\', \'Mobile\'), ',now())");
         */
    }

    function remove() {
        bg_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
        $keys = array('MODULE_PAYMENT_QUICKPAY_ADVANCED_STATUS', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_ZONE', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_AGGREEMENTID', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_MERCHANTID', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_APIKEY', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_PREPARE_ORDER_STATUS_ID', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_ORDER_STATUS_ID', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_REJECTED_ORDER_STATUS_ID', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_SORT_ORDER', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_PRIVATEKEY', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_ADMIN_APIKEY', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_SUBSCRIPTION', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_PAII_CAT');

        for ($i = 1; $i <= $this->num_groups; $i++) {
            $keys[] = 'MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i;
            $keys[] = 'MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i . '_FEE';
        }

        return $keys;
    }

//------------- Internal help functions-------------------------
// $order_total parameter must be total amount for current order including tax
// format of $fee parameter: "[fixed fee]:[percentage fee]"
    function calculate_order_fee($order_total, $fee) {
        list($fixed_fee, $percent_fee) = explode(':', $fee);
        return ((float) $fixed_fee + (float) $order_total * ($percent_fee / 100));
    }

    function get_order_fee() {
        global $_POST, $order, $currencies, $quickpay_fee;
        $quickpay_fee = 0.0;
        if (isset($_POST['qp_adv_card']) && strpos($_POST['qp_adv_card'], ":")) {
            $quickpay_fee = $this->calculate_order_fee($order->info['total'], $_POST['qp_adv_card']);
        }
    }

    function get_payment_options_name($payment_option) {
        switch ($payment_option) {
            case '3d-jcb': return MODULE_PAYMENT_QUICKPAY_ADVANCED_JCB_3D_TEXT;
            case '3d-maestro': return MODULE_PAYMENT_QUICKPAY_ADVANCED_MAESTRO_3D_TEXT;
            case '3d-maestro-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_MAESTRO_DK_3D_TEXT;
            case '3d-mastercard': return MODULE_PAYMENT_QUICKPAY_ADVANCED_MASTERCARD_3D_TEXT;
            case '3d-mastercard-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_MASTERCARD_DK_3D_TEXT;
            case '3d-visa': return MODULE_PAYMENT_QUICKPAY_ADVANCED_VISA_3D_TEXT;
            case '3d-visa-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_VISA_DK_3D_TEXT;
            case '3d-visa-electron': return MODULE_PAYMENT_QUICKPAY_ADVANCED_VISA_ELECTRON_3D_TEXT;
            case '3d-visa-electron-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_VISA_ELECTRON_DK_3D_TEXT;
            case '3d-visa-debet': return MODULE_PAYMENT_QUICKPAY_ADVANCED_VISA_DEBET_3D_TEXT;
            case '3d-visa-debet-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_VISA_DEBET_DK_3D_TEXT;
            case '3d-creditcard': return MODULE_PAYMENT_QUICKPAY_ADVANCED_CREDITCARD_3D_TEXT;
            case 'american-express': return MODULE_PAYMENT_QUICKPAY_ADVANCED_AMERICAN_EXPRESS_TEXT;
            case 'american-express-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_AMERICAN_EXPRESS_DK_TEXT;
            case 'dankort': return MODULE_PAYMENT_QUICKPAY_ADVANCED_DANKORT_TEXT;
            case 'danske-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_DANSKE_DK_TEXT;
            case 'diners': return MODULE_PAYMENT_QUICKPAY_ADVANCED_DINERS_TEXT;
            case 'diners-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_DINERS_DK_TEXT;
            case 'edankort': return MODULE_PAYMENT_QUICKPAY_ADVANCED_EDANKORT_TEXT;
            case 'jcb': return MODULE_PAYMENT_QUICKPAY_ADVANCED_JCB_TEXT;
            case 'mastercard': return MODULE_PAYMENT_QUICKPAY_ADVANCED_MASTERCARD_TEXT;
            case 'mastercard-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_MASTERCARD_DK_TEXT;
            case 'mastercard-debet': return MODULE_PAYMENT_QUICKPAY_ADVANCED_MASTERCARD_DEBET_TEXT;
            case 'mastercard-debet-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_MASTERCARD_DEBET_DK_TEXT;
            case 'nordea-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_NORDEA_DK_TEXT;
            case 'visa': return MODULE_PAYMENT_QUICKPAY_ADVANCED_VISA_TEXT;
            case 'visa-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_VISA_DK_TEXT;
            case 'visa-electron': return MODULE_PAYMENT_QUICKPAY_ADVANCED_VISA_ELECTRON_TEXT;
            case 'visa-electron-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_VISA_ELECTRON_DK_TEXT;
            case 'creditcard': return MODULE_PAYMENT_QUICKPAY_ADVANCED_CREDITCARD_TEXT;
            case 'ibill': return MODULE_PAYMENT_QUICKPAY_ADVANCED_IBILL_DESCRIPTION;
            case 'viabill': return MODULE_PAYMENT_QUICKPAY_ADVANCED_IBILL_DESCRIPTION;
            case 'fbg1886': return MODULE_PAYMENT_QUICKPAY_ADVANCED_FBG1886_TEXT;
            case 'paypal': return MODULE_PAYMENT_QUICKPAY_ADVANCED_PAYPAL_TEXT;
            case 'sofort': return MODULE_PAYMENT_QUICKPAY_ADVANCED_SOFORT_TEXT;
            case 'paii': return MODULE_PAYMENT_QUICKPAY_ADVANCED_PAII_TEXT;
            case 'mobilepay': return MODULE_PAYMENT_QUICKPAY_ADVANCED_MOBILEPAY_TEXT;
        }
        return '';
    }

    public function sign($params, $api_key) {
    $flattened_params = flatten_params($params);
    ksort($flattened_params);
    $base = implode(" ", $flattened_params);

        return hash_hmac("sha256", $base, $api_key);
    }

private function get_quickpay_order_status($order_id,$mode="") {
	$api= new QuickpayApi();
	
	
	$qp_apikey = MODULE_PAYMENT_QUICKPAY_ADVANCED_ADMIN_APIKEY;
	
	//uncomment this line . Used for test account
	 $qp_apikey = "f2d562c815d9c0e1dea9fb2573d4053041315f902ca04a15600186d023462a0a";
	//
	$api->setOptions($qp_apikey);
  try {
	$api->mode = ($mode=="" ? "payments?order_id=" : "subscriptions?order_id=");
	
    // Commit the status request, checking valid transaction id
    $st = $api->status(MODULE_PAYMENT_QUICKPAY_ADVANCED_ORDERPREFIX.sprintf('%04d', $order_id));
		$eval = array();
	if($st[0]["id"]){
    $eval["oid"] = str_replace(MODULE_PAYMENT_QUICKPAY_ADVANCED_ORDERPREFIX,"", $st[0]["order_id"]);
	$eval["qid"] = $st[0]["id"];
	}else{
	$eval["oid"] = null;
	$eval["qid"] = null;	
	}
  
  } catch (Exception $e) {
   $eval = 'QuickPay Status: ';
		  	// An error occured with the status request
          $eval .= 'Problem: ' . $this->json_message_front($e->getMessage()) ;
		 //  tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code, 'SSL'));
  }
    return $eval;
  } 
private function json_message_front($input){
	
	$dec = json_decode($input,true);
	
	$message= $dec["message"];
	return $message;
	
	
}
}



$paiioptions = array(
			''	   => '',
    'SC00' => 'Ringetoner, baggrundsbilleder m.v.',
    'SC01' => 'Videoklip og	tv',
    'SC02' => 'Erotik og voksenindhold',
    'SC03' => 'Musik, sange og albums',
    'SC04' => 'Lydb&oslash;ger	og podcasts',
    'SC05' => 'Mobil spil',
    'SC06' => 'Chat	og dating',
    'SC07' => 'Afstemning og konkurrencer',
    'SC08' => 'Mobil betaling',
    'SC09' => 'Nyheder og information',
    'SC10' => 'Donationer',
    'SC11' => 'Telemetri og service sms',
    'SC12' => 'Diverse',
    'SC13' => 'Kiosker & sm&aring; k&oslash;bm&aelig;nd',
    'SC14' => 'Dagligvare, F&oslash;devarer & non-food',
    'SC15' => 'Vin & tobak',
    'SC16' => 'Apoteker	og medikamenter',
    'SC17' => 'T&oslash;j, sko og accessories',
    'SC18' => 'Hus, Have, Bolig og indretning',
    'SC19' => 'B&oslash;ger, papirvare	og kontorartikler',
    'SC20' => 'Elektronik, Computer & software',
    'SC21' => '&Oslash;vrige forbrugsgoder',
    'SC22' => 'Hotel, ophold, restaurant, cafe & v&aelig;rtshuse, Kantiner og catering',
    'SC24' => 'Kommunikation og konnektivitet, ikke via telefonregning',
    'SC25' => 'Kollektiv trafik',
    'SC26' => 'Individuel trafik (Taxik&oslash;rsel)',
    'SC27' => 'Rejse (lufttrafik, rejser, rejser med ophold)',
    'SC28' => 'Kommunikation og konnektivitet, via telefonregning',
    'SC29' => 'Serviceydelser',
    'SC30' => 'Forlystelser og underholdning, ikke digital',
    'SC31' => 'Lotteri- og anden spillevirksomhed',
    'SC32' => 'Interesse- og hobby (Motion, Sport, udendÃ¸rsaktivitet, foreninger, organisation)',
			'SC33' => 'Personlig pleje (Fris&oslash;r, sk&oslash;nhed, sol og helse)',
    'SC34' => 'Erotik og voksenprodukter(fysiske produkter)',
);
$options = '';
$paiique = bg_db_query("select configuration_value  from " . TABLE_CONFIGURATION . " WHERE configuration_key  =  'MODULE_PAYMENT_QUICKPAY_ADVANCED_PAII_CAT' ");
$paiicat_values = bg_db_fetch_array($paiique);
$selectedcat = $paiicat_values['configuration_value'];

$option_array = array();
foreach ($paiioptions as $arrid => $val) {
    $option_array[] = array('id' => $arrid,
        'text' => $val);
    $selected = '';
    if ($selectedcat == $arrid) {
        $selected = ' selected="selected"';
    }
    $options .= '<option value="' . $arrid . '" ' . $selected . ' >' . $val . '</option>';
}

function bg_cfg_pull_down_paii_list($option_array) {
    global $options;
    return "<select name='configuration[MODULE_PAYMENT_QUICKPAY_ADVANCED_PAII_CAT]' />
	" . $options . "	
	</select>";
}

function sign_callback($base, $private_key) {
    return hash_hmac("sha256", $base, $private_key);
}

function flatten_params($obj, $result = array(), $path = array()) {
    if (is_array($obj)) {
        foreach ($obj as $k => $v) {
            $result = array_merge($result, flatten_params($v, $result, array_merge($path, array($k))));
        }
    } else {
        $result[implode("", array_map(function($p) { return "[{$p}]"; }, $path))] = $obj;
    }

    return $result;
}

?>