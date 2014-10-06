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
	  		$cartId self::$cart->id;

	  		// Get customer data
	  		$customer = $this->context->customer;

	  		// Get KCC Vars
	  		$kccPath = Configuration::get(KCC_PATH);
	  		
	  		$kccURL = Configuration::get(KCC_URL);

	  		$kccLogPath = Configuration::get(KCC_LOG);

	  		// Round total amount
	  		// of user cart
			$total_amount = Tools:ps_round(
							floatval(
								$cart->getOrderTotal(
									true, 
									Cart::BOTH)
								) 0);

			// Base URL
			// for generating pages
			$base_url = Tools::getShopDomainSsl(true, true) 
						. __PS_BASE_URI__;

 			// Base URL for success
			// or failure pages
			$module_url = . "index.php?fc=module&module="
						. "{$webpaykcc->name}&controller="
						. "validate&cartId=" 
						. $cartId;


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


			// Now we pass the data
			// to smarty and render
			// the template

			$this->context->smarty->assign(array(

			));

			$this->setTemplate('confirmation.tpl');	

	  	}

}