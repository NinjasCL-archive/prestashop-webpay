<?php

// Protection from direct access
if(!defined('_PS_VERSION_'))
	exit;

// Main Class that Handles
// Payments through Transbank's Webpay KCC 6.x version
class WebpayKCC extends PaymentModule {

	// Holds configuration errors
	protected $_errors = array();

	// Constructor
	public function __construct() {

		$this->name = 'webpaykcc';
		$this->tab = 'payments_gateways';
		
		$this->version = '1.0.0b';
		$this->author = 'Camilo Castro <camilo@cervezapps.cl>';
		
		$this->need_instance = 1;
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
		// $this->bootstrap = false;

		// Parent Constructor
		parent::__construct();

		$this->displayName = $this->l('Webpay KCC');
		
		$this->description = $this->l('Payment Gateway for Webpay KCC');
		
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

		// Set Module settings
		$this->setModuleSettings();

		// Check Requirements
		$this->checkModuleRequirements();

	}

	// Install Function
	// Set params before Module
	// Installation
	public function install() {
		if(!parent::install() ||
			!$this->registerHook('payment') ||
			!$this->registerHook('paymentReturn')
			)
			return false;

		return true;
	}

	// Uninstall function
	// clean all the data 
	// before removing the module
	public function uninstall() {
		if(!parent::uninstall())
			return false;

		// Do clean up
		return true;
	}

	// Show after Payment Selection Screen
	public function hookPaymentReturn($params) {
		if(!this->active)
			return;

		global $smarty;

		$smarty->assign(array(
			'status' => Tools::getValue('status', 'OPEN');
		));

		return $this->display(__FILE__, 'confirmation.tpl');
	}

	// Show in Payment Selection Screen
	public function hookPayment($params) {
		if(!$this->active)
			return;

		global $smarty;

		// Get active Shop Id for Multistore shops
		$activeShopID = (int) Context::getContext()->shop->id;

		$smarty->assign(array(
			'logo' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . "modules/{$this->name}/logo-small.png"
		));

		return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
	}

	// Configuration Page
	public function getContent() {

		// Get active Shop Id for Multistore shops
		$activeShopID = (int) Context::getContext()->shop->id;

		// Receive Update Settings
		if(isset($_POST['webpaykcc_updateSettings'])) {
			// Update Values
			
			// Configuration::updateValue('NAME', Tools::getValue('VAR'));

		} else {
			// Set default values
			$this->setModuleSettings();
		}

		// Set template vars
		$this->context->smarty->assign(array(
			'errors' => $this->_errors,
			'post_url' => Tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']),

			'version' => $this->version,
			'img_header' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . "modules/{$this->name}/logo.png"
		));

		// render template
		return $this->display($this->name, "views/templates/admin/config.tpl");
	}

	// Check for Missing Vars
	private function checkModuleRequirements() {
		$this->_errors = array();

		// Check vars
	}

	// Set the Module Default Values
	private function setModuleSettings() {

	}
}