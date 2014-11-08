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

if (!defined('_PS_VERSION_'))
    exit;


// Include Webpay Lib
include_once (_PS_MODULE_DIR_ . 'webpaykcc/lib-webpaykcc/webpay.php');
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
		$this->tab = 'payments_gateways';

		$this->version = '1.0.0';
		$this->author = 'Camilo Castro <camilo@cervezapps.cl>';

		$this->need_instance = 1;

		$this->ps_versions_compliancy = array(
								'min' => '1.6', 
								'max' => _PS_VERSION_);

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

		// Create Order States
		$this->addOrderStates();

		// All is good
		return true;
	}

	// Uninstall function
	// clean all the data
	// and remove the module
	public function uninstall() {

        // Drop table Closure
        $drop_table = function($table_name) {
            $query = "DROP TABLE IF EXISTS {$table_name}";

            if(!is_null($table_name))
                if($table_name != "")
                    Db::getInstance()->execute($query);

        };

		// Clean all data
		// after the parent 
		// uninstall
		if(!Configuration::deleteByName(KCC_PATH)
				|| !Configuration::deleteByName(KCC_URL)
				|| !Configuration::deleteByName(KCC_LOG)
				|| !Configuration::deleteByName(KCC_WAITING_PAYMENT_STATE)
				|| !Configuration::deleteByName(KCC_TOC_PAGE_URL)
				|| !parent::uninstall()
			)

				return false;

        // Drop the payment method table
        $drop_table($this->dbPmInfo);

        // Drop the payment method raw data table
        $drop_table($this->dbRawData);

		return true;
	}

	// This function creates the states
	// for the order. Needed for
	// order creation and updates.

	private function addOrderStates() {

		// Create a new Order state if not already done

        if (!(Configuration::get(KCC_WAITING_PAYMENT_STATE) > 0)) {

            // Create a new state
            // and set the state
            // as Open 
            
            $orderState = new OrderState(null, Configuration::get('PS_LANG_DEFAULT'));
            
            $orderState->name = $this->l("Awaiting Webpay KCC Payment");

            $orderState->invoice = false;
           
            $orderState->send_email = true;
            $orderState->module_name = $this->name;
            $orderState->color = "RoyalBlue";
           
            $orderState->unremovable = true;
            $orderState->hidden = false;
           
            $orderState->logable = false;
            $orderState->delivery = false;
           
            $orderState->shipped = false;
            $orderState->paid = false;
           
            $orderState->deleted = false;
           
            $orderState->template = "order_changed";
            $orderState->add();
            
            // The the value
            // in the configuration database
            Configuration::updateValue(KCC_WAITING_PAYMENT_STATE, $orderState->id);
            
            // Create an icon
            if (file_exists(dirname(dirname(dirname(__file__))) . '/img/os/10.gif'))
                copy(dirname(dirname(dirname(__file__))) 
                . '/img/os/10.gif', dirname(dirname(dirname(__file__)))
                . '/img/os/'.$orderState->id.'.gif');
        }

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
		$activeShopID = (int) Context::getContext()->shop->id;

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
		$activeShopID = (int) Context::getContext()->shop->id;


		// Get all the cart data
		// Do formatting
		// Check that everything is OK
		// This is done in /controllers/front/payment.php
		//
		// We will add a status flag
		// inside the url so we can
		// know where are we in the
		// payment process.
		//
		// This is made in this hook
		// so always we should have
		// a status value set.
		//
		// The getValue function works like this
		// 
		// Tools::getValue($key, $defaultValue = false)
		// Get a value from $_POST / $_GET. 
		// If unavailable, take a default value.
		
		$status = Tools::getValue('status', 'OPEN');

		// The smarty template engine
		// will be used to render
		// the html
		//
		// Assign the variables
		// for use inside the template


		$this->context->smarty->assign(array(
			'status' => $status
		));

		// Render the template
		$html = $this->display(__FILE__, 'confirmation.tpl');

		return $html;
	}

	// This function renders the configuration
	// page for the module inside admin
	// also gets the configuration values and updates them

	public function getContent() {

        // Get active Shop ID for multistore shops
        $activeShopID = (int) Context::getContext()->shop->id;

		// Check if the update flag is present
		// and process the input
		if(isset($_POST['webpaykcc_updateSettings'])) {

			// Update the values in database
			// according to what the form sends
			Configuration::updateValue(KCC_PATH, Tools::getValue('kccPath'));
			Configuration::updateValue(KCC_URL, Tools::getValue('kccURL'));
			Configuration::updateValue(KCC_LOG, Tools::getValue('kccLogPath'));
			Configuration::updateValue(KCC_TOC_PAGE_URL, Tools::getValue('kccTocPage'));

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

		// For sending the form
		$post_url =  Tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']);



		// These will be the placeholders inside the paths

		$base_url = Tools::getShopDomainSsl(true, true)
					. __PS_BASE_URI__;

		$base_path = realpath(dirname(__FILE__)) . '/';
		

		$cgi_path = $base_path . 'cgi-bin/';

		$log_path = $cgi_path . 'log/';

		$cgi_url = $base_url . "cgi-bin/";

		$toc_url = $base_url . "content/<page>";

		$validation_url = $base_url . "modules/" . $this->name . "/validate.php";

		$this->context->smarty->assign(array(
			'errors' => $this->_errors,
			'data_kccPath' => $this->kccPath,
			'data_kccURL' => $this->kccURL,
			
			'data_kccLogPath' => $this->kccLogPath,
			'data_kccTocPage' => $this->kccTocPage,
			
			'version' => $this->version,
			'img_header' => $img_header,
			
			'post_url' => $post_url,
			'base_path' => $base_path,
			
			'cgi_path' => $cgi_path,
			
			'log_path' => $log_path,
			'base_url' => $base_url,
			
			'cgi_url' => $cgi_url,
			'toc_url' => $toc_url,
			'validation_url' => $validation_url
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

		if($this->kccTocPage == '') {
			$this->_errors['kccTocPage'] = $this->l('Terms and Conditions Page URL not Set');
		}


	}

	// This private method
	// sets the default settings
	// if needed
	private function setModuleSettings() {
		$this->kccPath = Configuration::get(KCC_PATH);
		$this->kccURL = Configuration::get(KCC_URL);
		$this->kccLogPath = Configuration::get(KCC_LOG);
		$this->kccTocPage = Configuration::get(KCC_TOC_PAGE_URL);
	}
}
