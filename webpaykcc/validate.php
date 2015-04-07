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

class WebpayKccCallback {

    public function init() {

        define('_PS_ADMIN_DIR_', getcwd());

        // Load Presta Configuration
        Configuration::loadConfiguration();
        Context::getContext()->link = new Link();

        $this->confirm();
    }

    public function confirm() {
    
        // Global vars
        $kccPath = Configuration::get(KCC_PATH);

        $kccLogPath = Configuration::get(KCC_LOG);

        // Order States
        $order_state_completed = (int) Configuration::get('PS_OS_PAYMENT');
        
        $order_state_failed    = (int) Configuration::get('PS_OS_ERROR');

        $order_state_waiting_payment = (int) Configuration::get(KCC_WAITING_PAYMENT_STATE);

        // TBK Vars
        $tbk_session_id = (isset($_POST['TBK_ID_SESION']) ? trim($_POST['TBK_ID_SESION']) : null);

        $tbk_order_id = (isset($_POST['TBK_ORDEN_COMPRA']) ? trim($_POST['TBK_ORDEN_COMPRA']) : null);

        $tbk_response = (isset($_POST['TBK_RESPUESTA']) ? trim($_POST['TBK_RESPUESTA']) : null);

        $tbk_total_amount = (isset($_POST['TBK_MONTO']) ? trim($_POST['TBK_MONTO']) : null);

        // Default Values

        $result = KCC_REJECTED_RESULT;

        $order = null;

        $cart = null;

        $isDone = false;

        // Log helper closure
        $logger = function($message) {

          $today = date('Y-m-d');

          $now = date('Y-m-d H:i:s');

          $name = "validation.$today.log";

          $path = _PS_MODULE_DIR_ . 'webpaykcc/logs/';

          $logPath = Configuration::get(KCC_LOG);

          if($logPath){
            $path = $logPath;
          }

          $logFile = $path . $name;

          $log = fopen($logFile, 'a');

          $text = "$now : $message\n";

          fwrite($log, $text);

          fclose($log);
        };

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

        // Start Validation Process
        $logger("Start Validation");
        $logger("#################");

        // Log Params Received
        
        if (count($_POST) > 0) {

            $logger("Params Received");

            foreach($_POST as $key => $value) {
              $logger("$key => $value");
            }

        } else {
            $logger("Params Not Found");
        }

        // First we must check the tbk_response.
        if(isset($tbk_response)) {
            
            if($tbk_response == KCC_OK_RESPONSE) {
                
                $logger("Response is OK");

                // Now the response is OK, we must check the order
                if (isset($tbk_order_id)) {

                    // Get cart data

                    try {

                        $order = new Order(Order::getOrderByCartId($tbk_order_id));

                        $cart = Cart::getCartByOrderId($order->id);

                    } catch(Exception $e) {

                      $logger($e->getMessage());

                    }

                    // Both order and cart must exist
                    if(isset($order->id) && isset($cart->id)) {
                        
                        $logger("Order Exists");

                        // Now we check the current state of the order and cart
                        if($order->current_state == $order_state_waiting_payment) {

                            // The amounts must be equal

                            $total_order_amount = $getOrderTotalAmount($cart);

                            // Needed 00 at the end
                            $total_order_amount_formatted = $total_order_amount . '00';


                            if ($total_order_amount_formatted == $tbk_total_amount) {
                                
                                $logger("Amounts are Equal");

                                // Now check the session log file
                                if (isset($tbk_session_id)) {

                                    //  The log file was generated in front controller
                                    $tbk_log_path = getKccLog($kccLogPath, $session_id);

                                    if (file_exists($tbk_log_path)) {

                                        // Open the log file
                                        $tbk_log = fopen($tbk_log_path, 'r');

                                        // Put everything inside in a string
                                        $tbk_log_string = fgets($tbk_log);

                                        fclose($tbk_log);

                                        // $tbk_details is an array
                                        // separated by semicolon
                                        $tbk_details = explode(';', $tbk_log_string);

                                        // Details should exist
                                        if (isset($tbk_details) && 
                                            isset($tbk_details[0]) &&
                                            isset($tbk_details[1])) {

                                            $logger("Session File Exists");
                                        
                                            $tbk_session_total_amount = $tbk_details[0];

                                            $tbk_session_order_id = $tbk_details[1];

                                            // Session values and POST values must be equal
                                            if ($tbk_session_total_amount == $tbk_total_amount &&
                                                $tbk_session_order_id == $tbk_order_id) {

                                                $logger("Session Values are Correct");

                                                // Check KCC Path
                                                if(!(is_null($kccPath) || $kccPath == '')) {

                                                    // The cache file is needed for validation
                                                    $tbk_cache_path = $tbk_log_path . '.cache';

                                                    $tbk_cache = fopen($tbk_cache_path, 'w+');

                                                    // Write all the vars to cache
                                                    foreach ($_POST as $tbk_key => $tbk_value) {
                                                        fwrite($tbk_cache, "$tbk_key=$tbk_value&");
                                                    }

                                                    fclose($tbk_cache);
                                                    
                                                    $logger("Cache file created");

                                                    // Execute the CGI Check Script
                                                    $logger("Start CGI Verification Process");

                                                    if(KCC_USE_EXEC) {

                                                        $logger("Verify Using Exec");

                                                        // Store the result in $tbk_result
                                                        // executing the script with the log cache file
                                                        // as param

                                                        $command = $kccPath . KCC_CGI_CHECK . ' ' . $tbk_cache_path;

                                                        exec($command , $tbk_result);

                                                    } else {
                                                        // Use perl
                                                        // TODO: Implement Perl Someday
                                                        $logger("Verify Using Perl");
                                                    }

                                                    // Check the result
                                                    $logger("Checking the CGI Result");

                                                    if (isset($tbk_result[0]) && $tbk_result[0] == KCC_VERIFICATION_OK) {

                                                        // Verification OK
                                                        // Change the order status
                                                        $logger("Transbank Verification Complete");

                                                        $current_state = $order->current_state;

                                                        $order->setCurrentState($order_state_completed);

                                                        $logger("Order State Was Changed From ($current_state) to ({$order->current_state})");

                                                        // Last Check
                                                        if($order->current_state == $order_state_completed) {

                                                            $result = KCC_ACCEPTED_RESULT;

                                                            $logger("Order state is Completed");

                                                            $isDone = true;

                                                        } else {
                                                            
                                                            $result = KCC_REJECTED_RESULT;

                                                            $logger("Order State is not Completed.");
                                                        }

                                                    } else {
                                                        $logger("Failed CGI Verification " . print_r($tbk_result, true));
                                                    }


                                                } else {
                                                    $logger("KCC Path not Found");
                                                }

                                            } else {
                                                $logger("Session and Post Vars are different");
                                                $logger("Session Total : $tbk_session_total_amount");
                                                $logger("TBK Total: $tbk_total_amount");
                                                $logger("Session Order: $tbk_session_order_id");
                                                $logger("TBK Order Id: $tbk_order_id");
                                            }

                                        } else {
                                            $logger("$tbk_log_path does not contains valid data");
                                        }

                                    } else {
                                        $logger("$tbk_log_path does not exist");
                                    }

                                } else {
                                    $logger("TBK_ID_SESION not set");
                                }

                            } else {
                                $logger("Amounts are different ".
                                        "$total_order_amount_formatted != $tbk_total_amount");
                            }


                        } else {
                            $logger("Order State is not Waiting Payment ($order_state_waiting_payment)");
                            $logger("Current Order State is ({$order->current_state})");
                        }

                    } else {
                        $logger("Order not found in DB");
                    }

                } else {
                    $logger("TBK_ORDEN_COMPRA Not Set");
                }

            } else if($tbk_response >= -8 && 
                      $tbk_response <= -1) {

                $result = KCC_ACCEPTED_RESULT;
                $logger("Accepted Result, but TBK_RESPUESTA != OK (0)");
            
            } else {
                $logger("TBK_RESPUESTA has invalid value");
            }

        } else {
            $logger("TBK_RESPUESTA not set");
        }

        if (!$isDone && isset($order->id)) {
            $order->setCurrentState($order_state_failed);
            $logger("Order State was set to Failed");
        }

        // End Validation Process
        $logger("Final Result: $result");
        
        $logger("End Validation");
        $logger("#################");

        echo $result;
    }
}

$notify = new WebpayKccCallback();
$notify->init();