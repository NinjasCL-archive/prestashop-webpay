<?php
/*
  Plugin Name: Prestashop Webpay KCC Plugin
  Description: A Prestashop Payment Module for Chilean Transbank's WebPay KCC.
  Author: Camilo A. Castro Cabrera
  Version: 1.0
  Author URI: www.cervezapps.cl
  Plugin URI: https://github.com/clsource/prestashop-webpay
  
  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License or any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

include(dirname(__FILE__).'/../../config/config.inc.php');

include(dirname(__FILE__).'/../../init.php');

include_once (_PS_MODULE_DIR_ . 'webpaykcc/lib-webpaykcc/webpay.php');

include_once (_PS_MODULE_DIR_ . 'webpaykcc/webpaykcc.php');

// This class handles
// the transbank callback
// updates the cart
// and tell transbank if
// everything is ok

class WebpayKccCallback {

	public function init() {
		define('_PS_ADMIN_DIR_', getcwd());

        // Load Presta Configuration
        Configuration::loadConfiguration();
        Context::getContext()->link = new Link();

		$this->confirm();
	}

	public function confirm() {
		
		// Get Webpay Post Data

		// Check if the Post Data exists

		$session_id = (isset($_POST['TBK_ID_SESION']) ? $_POST['TBK_ID_SESION'] : null);

		$order_id = (isset($_POST['TBK_ORDEN_COMPRA']) ? $_POST['TBK_ORDEN_COMPRA'] : null);

		$response = (isset($_POST['TBK_RESPUESTA']) ? $_POST['TBK_RESPUESTA'] : null);
		
		$tbk_total_amount = (isset($_POST['TBK_MONTO']) ? $_POST['TBK_MONTO'] : null);


		// Get the log files
		$tbk_log_path = null;
		$tbk_cache_path = null;

		$kccPath = Configuration::get(KCC_PATH);
		$kccLogPath = Configuration::get(KCC_LOG);

		
		// Default Values
		$result = KCC_REJECTED_RESULT;

		$error_message = "";

		$cart = null;


		// Log helper closure
		$logger = function($message) {
			
			$kccLogPath = Configuration::get(KCC_LOG);

			$today = date('Y-m-d');

			$now = date('Y-m-d H:i:s');

			$name = "validation.$today.log";

			$path = _PS_MODULE_DIR_ . 'webpaykcc/logs/';

			if($kccLogPath){
				$path = $kccLogPath;
			}

			$logFile = $path . $name;

			$log = fopen($logFile, 'a');

			$text = "$now $message\n";

			fwrite($log, $text);
			fclose($log);
		};

		$logger("Start Validation");

		$message = "Params\n";

		foreach($_POST as $key => $value) {
			$message .= "$key => $value \n";
		}

		$logger($message);


		// Set the log paths
		// and cart and order vars

		if(!is_null($order_id) && !is_null($session_id)) {

			//  The log file was generated in front controller
			$tbk_log_path = getKccLog($kccLogPath, $session_id);

			// The cache file is needed for validation
			$tbk_cache_path = $tbk_log_path . '.cache';

			// Get cart data
			// $order_id is set in /controllers/front/payment.php
			// as the current cart id
			try {

				$order = new Order(Order::getOrderByCartId($order_id));

  				$cart = Cart::getCartByOrderId($order->id);

  			} catch(Exception $e) {
  				$error_message .= $e->getMessage();
  				$logger($error_message);
  			}
		}


		// Check for params
		if (is_null($session_id) ||
			is_null($order_id) ||
			is_null($response)  ||
			is_null($tbk_total_amount)
			) {

			$error_message .= "Params Not Found\n";

			$logger($error_message);

		}

		// Helper closure
		// for the total amount
		$getOrderTotalAmount = function($cart) {
			
			$order_total = 0;

			if($cart) {
    			$order_total = Tools::ps_round(floatval(
    						   $cart->getOrderTotal(true, Cart::BOTH)), 0);
    		}

    		return $order_total;
		};

		// Start Verification Process

		// Response must be OK
		if(!is_null($response) && $response == KCC_OK_RESPONSE) {

			$logger("Response OK");

			// Cart and Order must exist
			if(isset($order->id) && isset($cart->id)) {

				$logger("Cart and Order Exists");
				
				// Now we must check the log file
				if(isset($session_id) && file_exists($tbk_log_path)) {

					$logger("Log path File Exists");

					// Open the log file
					$tbk_log = fopen($tbk_log_path, 'r');

					// Put everything inside in a string
					$tbk_log_string = fgets($tbk_log);

					fclose($tbk_log);

					// $tbk_details is an array
					// separated by semicolon
					$tbk_details = explode(';', $tbk_log_string);

					// Detail count must be > 0
					if (isset($tbk_details) && count($tbk_details) >= 1) {

						$logger("TBK Details exists");

						// Check kcc path
						if(!(is_null($kccPath) || $kccPath == '')) {

							// We must check with the cgi
							// so we need to create a cache
							// with all the $_POST params

							$tbk_total = $tbk_details[0];

							$tbk_order_id = $tbk_details[1];

							$tbk_cache = fopen($tbk_cache_path, 'w+');

							// Write all the vars to cache
							foreach ($_POST as $tbk_key => $tbk_value) {

								fwrite($tbk_cache, "$tbk_key=$tbk_value&");
							}

							fclose($tbk_cache);
							
							// Execute the CGI Check Script
							$logger("Start CGI Verification Process");

							if(KCC_USE_EXEC) {
								
								$logger("Using Exec");

								// Store the result in $tbk_result
								// executing the script with the log cache file
								// as param

								$command = $kccPath . KCC_CGI_CHECK . ' ' . $tbk_cache_path;

								exec($command , $tbk_result);


							} else {
								// Use perl
								// TODO: Implement Perl Someday
								$logger("Using Perl");
							}

							// Check the result
							if (isset($tbk_result[0]) && $tbk_result[0] == KCC_VERIFICATION_OK) {
								
								$logger("CGI Verification OK");

								// Check Order

								if(isset($order->id) && 
								   trim($order_id) == trim($tbk_order_id) &&
								   trim($cart->id) == trim($tbk_order_id)) {
									
									$logger("Orders are Equal");

									// Check Amount
									
									$order_amount = $getOrderTotalAmount($cart);

									// Needed 00 at the end
									$tbk_order_amount = $order_amount . '00';

									if(isset($tbk_total) && 
									   isset($tbk_total_amount) &&
									   $tbk_total == $tbk_order_amount &&
									   $tbk_total == $tbk_total_amount &&
									   $tbk_total_amount == $tbk_order_amount) {

									   	$logger("Amount are Equal");
									    $logger("ACCEPT RESULT");

										// Everything is OK
										$result = KCC_ACCEPTED_RESULT;
										$error_message = null;

									} else {
										$error_message .= "Wrong Total $tbk_total != $tbk_order_amount";
										$logger($error_message);
									}

								} else {
									$error_message .= "Wrong Order Id $tbk_order_id != $order_id\n";
									$error_message .= "order_id $order_id tbk_order_id $tbk_order_id cart->id $order->id\n" . print_r($order, true);
									$logger($error_message);
								}

							} else {
								$error_message .= "Verification failure " . print_r($tbk_result, true);
								$logger($error_message);
							}
							

						} else {
							$error_message .= "Problem with KCC Path";
							$logger($error_message);
						}

					} else {
						$error_message .= "Log file is empty for path $tbk_log_path";
						$logger($error_message);
					}

				} else {
					$error_message .= "Log file does not exists for path $tbk_log_path";
					$logger($error_message);
				}

			} else {
				$error_message .= "Order does not exist for Cart id $order_id\n";
				$logger($error_message);
			}

		} else if(isset($response)){

			// Response Wasn't OK
			$error_message .= "Response Not OK, Response : $response";

			$logger($error_message);

			// Result must be Accepted
			// if there is a response 
			// but is not OK

			$result = KCC_ACCEPTED_RESULT;
		} 



		// Update Cart Status
		// if Cart Exists

		if(isset($cart) && is_object($cart)) {

			$logger("Cart Object Exists");

			// Get order data
			$order_status_completed = (int) Configuration::get('PS_OS_PAYMENT');
	    	
	    	$order_status_failed    = (int) Configuration::get('PS_OS_ERROR');

	    	$order_status = $order_status_failed;

	    	$order_total = $getOrderTotalAmount($cart);

	    	$order_waiting_payment = (int) Configuration::get(KCC_WAITING_PAYMENT_STATE);

			if(isset($response) && 
			   $response == KCC_OK_RESPONSE && 
			   $result == KCC_ACCEPTED_RESULT &&
			   is_null($error_message)) {

				// Set Order as Paid

				$order_status = $order_status_completed;

				$logger("Order State Should be Completed");

			}


			try {

				// Change Order State
				if(isset($order) && is_object($order)) {
					
					// Only change the state if is waiting payment
					if($order->current_state == $order_waiting_payment) {
						
						$order->setCurrentState($order_status);

					} else {
						
						$result = KCC_REJECTED_RESULT;
						$error_message .= "\n Order State is not Waiting Payment";

						$logger($error_message);

					}

				} else {

					$result = KCC_REJECTED_RESULT;
					$error_message .= "\nFailed to change order state";

					$logger($error_message);
				}

		   } catch (Exception $e) {

		   	 $result = KCC_REJECTED_RESULT;
		   	 $error_message .= $e->getMessage();

		   	 $logger($error_message);
		   }
		   
		   // Last check in order to ensure that 
		   // the order really changed it's state to completed
		   // this is made outside all the ifs in order to
		   // really check the state at the end.
		   
		   if($order->current_state == $order_status_completed) {

		   		$logger("Order state is Completed");

		   } else {
		   	
		   		$result = KCC_REJECTED_RESULT;
			
				$error_message .= "\n Order State is not Completed (State Number: $order_status_completed)."
			                  ."\n Current State Number: $order->current_state";

				$logger($error_message);
		   }

		} else {
			$error_message .= "Cart Object Not Found\n";
			
			$result = KCC_REJECTED_RESULT;

			$logger($error_message);
		}

		// Register Error in Log if present
		if(!is_null($error_message) || $result == KCC_REJECTED_RESULT) {
			
			$path = _PS_MODULE_DIR_ . 'webpaykcc/logs/';

			if($kccLogPath){
				$path = $kccLogPath;
			}


			$error_log_path = $path . 'validation.errors.log';

			$error_log = fopen($error_log_path, 'a');

			$text = date('Y-m-d H:i:s') . "  Error: $error_message\n";

			// Log params
			foreach ($_POST as $key => $value) {
				$text .= "$key => $value \n";
			}

			$text .= "#########################################\n";

			fwrite($error_log, $text);
							
			fclose($error_log);
			
			// If we got and error in any point
			// set the order state to failed
			if(isset($order) && is_object($order)) {

				$order->setCurrentState($order_status_failed);
			}

		}

		// Send transbank the result
		if($result == KCC_ACCEPTED_RESULT) {
			
			$logger("SUCCESS");

		} else {

			$logger("FAILURE");

		}

		$logger("End Validation");
		$logger("#################");

		echo $result;
	}
}

$notify = new WebpayKccCallback();
$notify->init();