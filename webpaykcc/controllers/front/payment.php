<?php
/**
* Copyright (c) 2014, Camilo Castro <camilo@cervezapps.cl>
* All rights reserved.
*
* Redistribution and use in source and binary forms,
* with or without modification, are permitted provided that 
* the following conditions are met:
*
* 1. Redistributions of source code must retain the above 
* copyright notice, this list of conditions and the following 
* disclaimer.
*
* 2. Redistributions in binary form must reproduce the above 
* copyright notice, this list of conditions and the following 
* disclaimer in the documentation and/or other materials 
* provided with the distribution.
*
* 3. Neither the name of the copyright holder nor the names of 
* its contributors may be used to endorse or promote products
*  derived from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND
* CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
* INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF 
* MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. 
* IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE 
* FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR 
* CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF 
* SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR 
* BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF 
* LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT 
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE 
* USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
* SUCH DAMAGE.
*/

if (!defined('_PS_VERSION_'))
    exit;

// Include Webpay Lib
include_once (_PS_MODULE_DIR_ . 'webpaykcc/lib-webpaykcc/webpay.php');

// This class holds the controller
// for the frontend
class WebpayKccPaymentModuleFrontController 
	  extends ModuleFrontController {

	  	// Constructor
	  	public function __construct() {
	  		parent::__construct();

	  		// Hide Left column
	  		$this->display_column_left = false;
	  	}


	  	// This method renders the view
	  	// when the user will confirm
	  	// his purchase

	  	public function initContent() {
	  		
	  		// Instance a new variable
	  		// and call the parent init method
	  		$webpaykcc = new WebpayKcc();
	  		
	  		parent::initContent();

	  		// Get cart data
	  		$cart = $this->context->cart;
	  		

	  		// Create a new Order for the Cart
	  		// This order will be procesed
	  		// by webpaykcc/validate.php
	  		// and set the status for the payment
        	$webpayKcc = new WebpayKcc();

			/*
				http://doc.prestashop.com/display/PS16/Creating+a+payment+module
				Validating the payment
				In order to register the payment validation, you must use the 
				validateOrder() method from the PaymentModule class, using the 
				following parameters:
				(integer) id_cart: the ID of the cart to validate.
				(integer) id_order_state: the ID of the order status (Awaiting payment,
				 Payment accepted, Payment error, etc.).
				(float) amount_paid: the amount that the client actually paid.
				(string) payment_method: the name of the payment method.

				function validateOrder($id_cart, $id_order_state, $amountPaid, 
				$paymentMethod = 'Unknown', $message = NULL, $extraVars = array(), 
				$currency_special = NULL)
			*/

        	$webpayKcc->validateOrder(
        		(int) self::$cart->id, 
        		(int)Configuration::get(KCC_WAITING_PAYMENT_STATE), 
        		(float) self::$cart->getOrderTotal(), 
        		$webpayKcc->displayName, 
        		NULL, 
        		array(), 
        		NULL, 
        		false, 
        		self::$cart->secure_key
        		);

	  		$cart_id = self::$cart->id;

	  		// Get customer data
	  		$customer = $this->context->customer;

	  		// Get KCC Vars
	  		$kccPath = Configuration::get(KCC_PATH);
	  		
	  		$kccURL = Configuration::get(KCC_URL);

	  		$kccLogPath = Configuration::get(KCC_LOG);

	  		// Round total amount
	  		// of user cart
			$total_amount = Tools::ps_round(
							floatval(
								$cart->getOrderTotal(
									true, 
									Cart::BOTH)
								), 0);

			// Base URL
			// for generating pages
			$base_url = Tools::getShopDomainSsl(true, true) 
						. __PS_BASE_URI__;

 			// Base URL for success
			// or failure pages
			$module_url = "index.php?fc=module&module="
						. "{$webpaykcc->name}&controller="
						. "validate&cartId=" 
						. $cart_id;


			// Transbank will
			// call this page when
			// the transaction is successful
			$success_page = $base_url . $module_url . "&return=ok";

			// Transbank will
			// call this page when
			// the transaction is not right
			$failure_page = $base_url . $module_url . "&return=error";

			// This page will
			// be called when the transaction
			// is being made.
			// this page will update the cart
			// data, register the logs and
			// tell transbank if all is correct.
			// then transbank will call success
			// or failure depending on the
			// result of this page
			$callback_page = $base_url 	
						. "modules/{$webpaykcc->name}"
						. "/validate.php";

			// Session Id will be used 
			// to generate the logs
			$session_id = getSessionUID();

			// Now create the log file
			// Something like
			// TBK_2014.10.05_224902_9293012312.log
			// Inside the Log Folder
			$tbk_log = fopen(getKCCLog($kccLogPath, $session_id), 'w+');

			// We must format the amount
			// to include 00 at the end
			// this is needed for transbank
			$tbk_total_amount = $total_amount . '00';

			// This line is needed
			// in order to verify
			// the amount later in
			// the callback page

			$verification_line = "$tbk_total_amount;$cart_id";

			// Now we create the file

			fwrite($tbk_log, $verification_line);

			fclose($tbk_log);

			// Action URL
			// will be called in the form
			// for sending the POST vars
			// and begin transaction
			$cgi_URL = $kccURL . KCC_CGI_NAME;

			// Look for webpay logo
			// inside the current folder
			$logo = $base_url
					. "modules/{$webpaykcc->name}/logo.png";

			// Now we pass the data
			// to smarty and render
			// the template

			$this->context->smarty->assign(array(
				'action' => $cgi_URL,
				'transaction_type' => KCC_TRANSACTION_TYPE,
				'success_page' => $success_page,
				'failure_page' => $failure_page,
				'callback_page' => $callback_page,
				'total_amount' => $total_amount,
				'tbk_total_amount' => $tbk_total_amount,
				'order_id' => $cart_id,
				'session_id' => $session_id,
				'logo' => $logo
			));

			$this->setTemplate('confirmation.tpl');	

	  	}

}