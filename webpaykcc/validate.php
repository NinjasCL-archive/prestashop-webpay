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
		$this->confirm();
	}

	public function confirm() {
		
		// Get Webpay Post Data
		$session_id = $_POST['TBK_ID_SESION'];
		$order_id = $_POST['TBK_ORDEN_COMPRA'];
		$response = $_POST['TBK_RESPUESTA'];
		$tbk_total_amount = $_POST['TBK_MONTO'];

		// Default Result
		$result = KCC_REJECTED_RESULT;

		// Get the log files
		$tbk_log_path = null;
		$tbk_cache_path = null;

		$kccPath = Configuration::get(KCC_PATH);
		$kccLogPath = Configuration::get(KCC_LOG);

		if(!is_null($order_id) && !is_null($session_id)) {

			//  The log file was generated in front controller
			$tbk_log_path = getKccLog($kccLogPath, $session_id);

			// The cache file is needed for validation
			$tbk_cache_path = $tbk_log . '.cache';

			// Get cart data
			$order = new Order(Order::getOrderByCartId($order_id));

  			$cart = Cart::getCartByOrderId($order->id);
		}


		// Start Verification Process
		$error_message = "Unknown Error for Response $reponse";

		// Response must be OK
		if(!is_null($response) && $response == KCC_OK_RESPONSE){

			// Cart and Order must exist
			if(isset($order->id) && isset($cart->id)) {
				
				// Now we must check the log file
				if(isset($session_id) && file_exists($tbk_log_path)) {

					// Open the log file
					$tbk_log = fopen($tbk_log_path, 'r');

					// put everything inside in a string
					$tbk_log_string = fgets($tbk_log);

					fclose($tbk_log);

					// $tbk_details is an array
					// separated by semicolon
					$tbk_details = explode(';', $tbk_log_string);

					// detail count must be > 0
					if (isset($tbk_details) && count($tbk_details) >= 1) {

						// check kcc path
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

							exec($kccPath . ' ' . $tbk_cache_file, $tbk_result);

						} else {
							$error_message = "Problem with KCC Path";
						}

					} else {
						$error_message = "Log file is empty for path $tbk_log_path";
					}

				} else {
					$error_message = "Log file does not exists for path $tbk_log_path";
				}

			} else {
				$error_message = "Cart does not exist for id $order_id";
			}

		} else {
			// Response Wasn't OK
			$error_message = "Response Not OK, Response : $response";
		}

		// Register Error in Log if present
		if($response != KCC_OK_RESPONSE) {
			// TODO: Register Error
		}

		echo $result;
	}
}

$notify = new WebpayKccCallback();
$notify->init();
