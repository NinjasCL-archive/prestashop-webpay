<?php

if (!defined('_PS_VERSION_'))
    exit;

/**
* This Class Holds the Admin
* Part of the Webpay Module
*/
class WebpayKcc extends PaymentModule {

	public function __construct() {
		
		// Basic Settings

		$this->name = 'webpaykcc';
		$this->tab = 'payments_gateway';

		$this->version = '1.0.0';
		$this->author = 'Camilo Castro <camilo@cervezapps.cl>';

		$this->need_instance = 1;

		$this->ps_versions_compliancy = array(
								'min' => '1.6', 
								'max' => __PS_VERSION__);

		// This is for the views
		// This module does not use twitter bootstrap
		// helpers. But someday in the future will.
		// meanwhile is turned off.
		// $this->bootstrap = true;
		// $this->bootstrap = false;

		// Call the Parent Constructor
		parent::__construct();

		// Now some messages
		$this->displayName = $this->l('Webpay KCC Payment');

		$this->description = $this->l("Payment Gateway using Chile's Transbank Webpay KCC");

		$this->confirmUninstall = $this->l("Payments with Webpay KCC will not be possible. Are you sure to uninstall?");


		// Call internal setup methods
		$this->setModuleSettings();

		$this->checkModuleRequirements();

	}

	// Install Methods
	// Set params before Module Installation
	public function install() {

		// wait for parent installation
		// and register to hooks
		if(!parent::install()||
			!$this->registerHook('payment')||
			!$this->registerHook('paymentReturn'))
			return false;

		// All is good
		return true;
	}

	// Uninstall function
	// clean all the data
	// and remove the module
	public function uninstall() {
		
		// Clean all data
		// after the parent 
		// uninstall
		if(!parent::uninstall())
			return false;

		
		return true;
	}

	// This function is called when
	// the user reach the payment
	// selection screen
	// we will show the option
	// to pay using webpay

	public function hookPayment($params) {
		
		// Only show if the module
		// is active
		if(!$this->active)
			return;

		// The smarty template engine
		// will be used to render
		// the html
		global $smarty;

		// Get the active shop id if in multistore shop
		$activeShopID = (int) Context::getContext()->shop()->id;

		// Look for webpay logo
		// inside the current folder
		$logo = Tools::getShopDomainSsl(true, true) 
				. __PS_BASE_URI__ 
				. "modules/{$this->name}/logo-small.png";

		// Assign the variables
		// for use inside the template
		$smarty->assign(array(
			'logo' => $logo
		));

		// Render the template
		$html = $this->display(__FILE__, 'views/templates/hook/payment.tpl');

		return $html;
	}

	// This is the Confirmation Page
	// Show all the details before 
	// commit the payment and call the bank
	public function hookPayment($params) {

		// Only show if the module
		// is active
		if(!$this->active)
			return;

		// The smarty template engine
		// will be used to render
		// the html
		global $smarty;

		// Get the active shop id if in multistore shop
		$activeShopID = (int) Context::getContext()->shop()->id;


		// Get all the cart data
		// Do formatting
		// Check that everything is OK

		// Assign the variables
		// for use inside the template
		$smarty->assign(array(
		
		));

		// Render the template
		$html = $this->display(__FILE__, 'confirmation.tpl');

		return $html;
	}
}