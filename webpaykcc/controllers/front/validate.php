<?php

// This class handles the Success or Failure
// pages called from webpay
class WebpayKccValidateModuleFrontController 
extends ModuleFrontController {

	public function initContent()
    {
        $this->display_column_left = false;
        $this->display_column_right = false;

       	parent::initContent();

       	$this->handleGET();
    }

    private function handleGET()
    {
        $cartId = $_GET['cartId'];
        $order = new Order(Order::getOrderByCartId($cartId));
        $customer = $order->getCustomer();
        $modID = Module::getInstanceByName($order->module);

        if ($_GET['return'] == 'error') {
            

        } else if ($_GET['return'] == 'ok') {
           
        }
    }
}}