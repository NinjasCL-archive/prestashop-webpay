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

// This class handles the Success or Failure
// pages called from webpay
class WebpayKccValidateModuleFrontController 
extends ModuleFrontController {

	// This var holds the error message
	protected $error_message = null;

	// webpay logo path
	protected $logo = null;

	// Constructor
	public function __construct() {

	  		parent::__construct();

	  		// Hide columns
	  		$this->display_column_left = false;
        	$this->display_column_right = false;
	}


	public function initContent() {

       	parent::initContent();

		// Look for webpay logo
		// inside the current folder
		$base_url = Tools::getShopDomainSsl(true, true);

		$name = $this->module->name;

		$this->logo = $base_url
				. "/modules/{$name}/logo.png";

       	$this->handleGET();
    }

    // This function handles the redirect from
    // transbank, and call the renders of success or failure
    private function handleGET() {
            
        // show an error page if error is present
        // or POST does not exist (was not called from transbank)
        if ($_GET['return'] == 'error' || count($_POST) <= 0) {
        	$this->handleError();
        }

        // Return page must be show only
        // if return is `ok` and transbank response is equal to zero
        if ($_GET['return'] == 'ok') {
            $this->handleOK();
        }

    }

    // This makes all the checks needed
    // for considering a success page

    private function handleOK() {

    	// Get Webpay Post Data

		// Check if the Post Data exists

		$session_id = (isset($_POST['TBK_ID_SESION']) ? $_POST['TBK_ID_SESION'] : null);

		$cart_id = (isset($_POST['TBK_ORDEN_COMPRA']) ? $_POST['TBK_ORDEN_COMPRA'] : null);

		$response = (isset($_POST['TBK_RESPUESTA']) ? $_POST['TBK_RESPUESTA'] : null);
		
		$tbk_total_amount = (isset($_POST['TBK_MONTO']) ? $_POST['TBK_MONTO'] : null);

		// log files
		$tbk_log_path = null;
		$tbk_cache_path = null;

		// Paths from Configuration
		$kccPath = Configuration::get(KCC_PATH);
		$kccLogPath = Configuration::get(KCC_LOG);
		$kccTocPage = Configuration::get(KCC_TOC_PAGE_URL);

		$cart = null;
		$order = null;
		$customer = null;
		$webpaykcc = new WebpayKcc();

		// Error vars
		$error = false;
		$error_message = null;

		// Set the log paths
		// and cart and order vars

		if(!is_null($cart_id) && !is_null($session_id)) {

			//  The log file was generated in front controller
			$tbk_log_path = getKccLog($kccLogPath, $session_id);

			// The cache file is needed for validation
			// was generated in validate.php

			$tbk_cache_path = $tbk_log_path . '.cache';

			// Get cart data
			// $cart_id is set in /controllers/front/payment.php
			// as the current cart id
			// this is called by transbank with those vars

			try {

				$order = new Order(Order::getOrderByCartId($cart_id));

  				$cart = Cart::getCartByOrderId($order->id);

  			} catch(Exception $e) {
  				$error = true;
  				$error_message = $e->getMessage();
  			}

		} else {
			$error = true;
			$error_message = 'Session and Cart params not found';
		}

		// Start Checks for Success
		if(!$error) {

			// Check if log files are present
			if(file_exists($tbk_log_path) && file_exists($tbk_cache_path)) {
				
				// Check if order and cart exists
				if(isset($order->id) && isset($cart->id)) {

					// Check for customer
					$customer = $order->getCustomer();

					if(isset($customer->id)) {


						// Check Log Data

						$tbk_cache = fopen($tbk_cache_path, 'r');
						
						$tbk_cache_string = fgets($tbk_cache);
						
						fclose($tbk_cache);


						$tbk_data = explode('&', $tbk_cache_string);

						// there must be at least 12 params
						// response is the 2nd param
						if(is_array($tbk_data) && isset($tbk_data[2]) && count($tbk_data) >= 12) {

							// Check Response
							$tbk_response = explode('=', $tbk_data[2]);

							if(isset($tbk_response[1]) && $tbk_response[1] == KCC_OK_RESPONSE) {

								// Everything seems OK
								// should render the Success Page
								$error = false;
								$error_message = null;

							} else {
								$error = true;
								$error_message = 'Response is not OK';
							}

						} else {
							$error = true;
							$error_message = 'Cache data is invalid';
						}

					} else {
						$error = true;
						$error_message = 'Customer not found';
					}

				} else {
					$error = true;
					$error_message = 'Order or Cart Objects not Found';
				}

			} else {
				$error = true;
				$error_message = 'Log files not found';
			}

		}


		// Render the template
		if(!$error && is_null($error_message)) {

			// Init params var
			$params = array();

			// Get the active shop id if in multistore shop
			$activeShopID = (int) Context::getContext()->shop->id;

			// Parse Cache

			// $tbk_data and tbk_response are set in checks above

			$tbk_cart_id = explode('=', $tbk_data[0]);
			
			$tbk_transaction_type = explode('=', $tbk_data[1]);
			
			$tbk_amount = explode('=', $tbk_data[3]);

			$tbk_auth_code = explode('=', $tbk_data[4]);

			$tbk_card_last_digit = explode('=', $tbk_data[5]);
			
			$tbk_accounting_date = explode('=', $tbk_data[6]);

			$tbk_transaction_date = explode('=', $tbk_data[7]);

			$tbk_transaction_time = explode('=', $tbk_data[8]);

			$tbk_transaction_id = explode('=', $tbk_data[10]);

			$tbk_payment_type = explode('=', $tbk_data[11]);

			$tbk_installment_quantity = explode('=', $tbk_data[12]);

			$tbk_mac = explode('=', $tbk_data[13]);


			// Do some formatting for the Accounting Year

			$tbk_accounting_year = date('Y');

			if (substr($tbk_accounting_date[1], 0, 2) == '12' && date('d') == '01') {
				
				$tbk_accounting_year = date('Y') - 1;

			} else if (substr($tbk_accounting_date[1], 0, 2) == '01' && date('d') == '12') {
				
				$tbk_accounting_year = date('Y') + 1;

			}


			// Do some formatting for the Transaction Year

			$tbk_transaction_year = date('Y');

			if (substr($tbk_transaction_date[1], 0, 2) == '12' && date('d') == '01') {

				$tbk_transaction_year = date('Y') - 1;

			} else if (substr($tbk_transaction_date[1], 0, 2) == '01' && date('d') == '12') {
				
				$tbk_transaction_year = date('Y') + 1;

			}


			// Start Adding info to Params
			
			
			// Format transaction date
			$params['tbk_transaction_date'] = substr($tbk_transaction_date[1], 2, 2) . '-' 
									. substr($tbk_transaction_date[1], 0, 2) . '-' 
									. $tbk_transaction_year;

			// Format transaction time
			$params['tbk_transaction_time'] = substr($tbk_transaction_time[1], 0, 2) . ':' 
									. substr($tbk_transaction_time[1], 2, 2) . ':' 
									. substr($tbk_transaction_time[1], 4, 2);


			// Do some formatting for the payment type
			if ($tbk_payment_type[1] == 'VD') {

				$params['tbk_payment_type'] = $this->module->l('Redcompra');

			} else {

				$params['tbk_payment_type'] = $this->module->l("Crédito");

			}

			// Do some formatting for the Installment Type
			if ($tbk_payment_type[1] == 'VN') {
				
				$params['tbk_installment_type'] = $this->module->l('Sin cuotas');

			} else if ($tbk_payment_type[1] == 'VC') {

				$params['tbk_installment_type'] = $this->module->l('Cuotas normales');

			} else if ($tbk_payment_type[1] == 'SI') {

				$params['tbk_installment_type'] = $this->module->l('Sin interés');

			} else if ($tbk_payment_type[1] == 'S2') {

				$params['tbk_installment_type'] = $this->module->l('Dos cuotas sin interés');

			} else if ($tbk_payment_type[1] == 'CI') {

				$params['tbk_installment_type'] = $this->module->l('Cuotas comercio');

			} else if ($tbk_payment_type[1] == 'VD') {

				$params['tbk_installment_type'] = $this->module->l('Débito');

			}


			// Check for Quantity of Installments
			if ($tbk_installment_quantity[1] == 0) {

				$params['tbk_installment_quantity'] = '00';

			} else {

				$params['tbk_installment_quantity'] = $tbk_installment_quantity[1];

			}



			// Add more info to params

			// General Info
			$base_url = Tools::getShopDomainSsl(true, true);

			$params['toc_page'] = $kccTocPage;

			$params['order_history'] = $base_url . '/index.php?controller=history';

			$params['shop_name'] = Context::getContext()->shop->name;
			
			$params['shop_url'] = $base_url;
			
			$params['customer_name'] = $customer->firstname . ' ' . $customer->lastname;



			// Transbank Info

			$params['tbk_accounting_year'] = $tbk_accounting_year;

			$params['tbk_transaction_year'] = $tbk_transaction_year;
			
			$params['tbk_mac'] = $tbk_mac[1];
			
			$params['tbk_cart_id'] = $tbk_cart_id[1];

			// TODO: Should check tbk_transaction_type value
			// For now this will work
			$params['tbk_transaction_type'] = $this->module->l('Venta');

			$params['tbk_amount'] = ($tbk_amount[1] / 100);
			
			$params['tbk_auth_code'] = $tbk_auth_code[1];
			
			$params['tbk_card_last_digit'] = '************' . $tbk_card_last_digit[1];			
			
			$params['tbk_transaction_id'] = $tbk_transaction_id[1];

			$params['string'] = print_r($params, true);

			$params['logo'] = $this->logo;

			// Now we pass the data
			// to smarty and render
			// the template

			$this->context->smarty->assign($params);

			$this->setTemplate('success.tpl');

		} else {

			// for generating pages
			$base_url = Tools::getShopDomainSsl(true, true) 
						. __PS_BASE_URI__;

 			// Base URL for success
			// or failure pages
			$module_url = "index.php?fc=module&module="
						. "{$webpaykcc->name}&controller="
						. "validate"
						. "&cartId=" 
						. $cart_id;

			
			$failure_page = $base_url . $module_url . "&return=error";

			// set the error message
			
			$this->error_message = $error_message;

			// Redirect to failure

			// $this->handleError();
			Tools::redirect($failure_page);
		}

    }

    // If something went wrong
    // show this page
    private function handleError() {

    	$cart_id = (isset($_GET['cartId']) ? $_GET['cartId'] : 0);

		$cart_id = (isset($_POST['TBK_ORDEN_COMPRA']) ? $_POST['TBK_ORDEN_COMPRA'] : $cart_id);
 
		if($cart_id == '' || is_null($cart_id))
			$cart_id = $this->module->l('No disponible');

		$error_message = (isset($this->error_message) ? $this->error_message : null);

		// Register Error in Log if present
		if(!is_null($error_message)) {
			
			$path = _PS_MODULE_DIR_ . 'webpaykcc/logs/';

			$kccLogPath = Configuration::get(KCC_LOG);

			if($kccLogPath){
				$path = $kccLogPath;
			}


			$error_log_path = $path . 'callback.errors.log';

			$error_log = fopen($error_log_path, 'a');

			$text = date('Y-m-d H:i:s') . "  Error: $error_message\n";

			$text .= "#########################################\n";

			fwrite($error_log, $text);
							
			fclose($error_log);

		}

    	// Fill the params
        $this->context->smarty->assign(array(
        	'cart_id' => $cart_id,
        	'logo' => $this->logo
		));

		$this->setTemplate('failure.tpl');	

    }
}