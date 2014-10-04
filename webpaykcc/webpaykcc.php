<?php

if (!defined('_PS_VERSION_'))
    exit;

// Constants
define('KCC_PATH', 'WEBPAY_KCC_PATH');
define('KCC_URL', 'WEBPAY_KCC_URL');
define('KCC_LOG', 'WEBPAY_KCC_LOGPATH');

/**
* This Class Holds the Admin
* Part of the Webpay Module
*/
class WebpayKcc extends PaymentModule {

	// Holds errors that could happen
	// in configuration page
	protected $_errors = array();

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

		// Drop the paymentmethod table
        Db::getInstance()->execute("DROP TABLE if exists {$this->dbPmInfo}");

        // Drop the paymentmethod raw data table
        Db::getInstance()->execute("DROP TABLE if exists {$this->dbRawData}");

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


		// Get the active shop id if in multistore shop
		$activeShopID = (int) Context::getContext()->shop()->id;

		// Look for webpay logo
		// inside the current folder
		$logo = Tools::getShopDomainSsl(true, true) 
				. __PS_BASE_URI__ 
				. "modules/{$this->name}/logo.png";

		// The smarty template engine
		// will be used to render
		// the html
		//
		// Assign the variables
		// for use inside the template

		$this->context->smarty->assign(array(
			'logo' => $logo
		));

		// Render the template
		$html = $this->display(__FILE__, 'views/templates/hook/payment.tpl');

		return $html;
	}

	// This is the Confirmation Page
	// Show all the details before 
	// commit the payment and call the bank
	public function hookPaymentReturn($params) {

		// Only show if the module
		// is active
		if(!$this->active)
			return;


		// Get the active shop id if in multistore shop
		$activeShopID = (int) Context::getContext()->shop()->id;


		// Get all the cart data
		// Do formatting
		// Check that everything is OK

		// The smarty template engine
		// will be used to render
		// the html
		//
		// Assign the variables
		// for use inside the template
		$this->context->smarty->assign(array(
		
		));

		// Render the template
		$html = $this->display(__FILE__, 'confirmation.tpl');

		return $html;
	}

	// This function renders the configuration
	// page for the module inside admin
	// also gets the configuration values and updates them

	public function getContent() {

		// Get the active shop id if in multistore shop
		$activeShopID = (int) Context::getContext()->shop()->id;

		// Check if the update flag is present
		// and process the input
		if(isset($_POST['webpaykcc_updateSettings'])) {

			// Update the values in database
			// according to what the form sends
			Configuration::updateValue(KCC_PATH, Tools::getValue('kccPath'));
			Configuration::updateValue(KCC_URL, Tools::getValue('kccURL'));
			Configuration::updateValue(KCC_LOG, Tools::getValue('kccLogPath'));

			// Update the internal vars
			$this->setModuleSettings();

			// Check if the values are right
			$this->checkModuleRequirements();

		// If there is no update flag
		// Ensure that we use the saved values 
		} else {
			$this->setModuleSettings();
		}

		// The smarty template engine
		// will be used to render
		// the html
		//
		// Assign the variables
		// for use inside the template

		// Image Header 
		// For the Webpay Logo
		$img_header = Tools::getShopDomainSsl(true, true)
					. __PS_BASE_URI__
					. "modules/{$this->name}/logo.png";

		$this->context->smarty->assign(array(
			'errors' => $this->_errors,
			'data_kccPath' => $this->kccPath,
			'data_kccURL' => $this->kccURL,
			'data_kccLogPath' => $this->kccLogPath,
			'version' => $this->version,
			'img_header' => $img_header
		));

		// Render the template
		$html = $this->display(__FILE__, "views/templates/admin/config.tpl");

		return $html;
	}

	// This private method
	// fills the error property
	// if there is a configuration
	// related error
	private function checkModuleRequirements() {
		$this->_errors = array();

		if($this->kccPath == '') {
			$this->_errors['kccPath'] = $this->l('KCC Path is not Set');
		}

		if($this->kccURL == '') {
			$this->_errors['kccURL'] = $this->l('KCC URL is not Set');
		}

		if($this->kccLogPath == '') {
			$this->_errors['kccLogPath'] = $this->l('KCC Log Path is not Set');
		}


	}

	// This private method
	// sets the default settings
	// if needed
	private function setModuleSettings() {
		$this->kccPath = Configuration::get(KCC_PATH);
		$this->kccURL = Configuration::get(KCC_URL);
		$this->kccLogPath = Configuration::get(KCC_LOG);
	}
}