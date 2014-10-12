<?php

// This class handles the Success or Failure
// pages called from webpay
class WebpayKccValidateModuleFrontController 
extends ModuleFrontController {

	public function initContent() {

        $this->display_column_left = false;
        $this->display_column_right = false;

       	parent::initContent();

       	$this->handleGET();
    }

    private function handleGET() {

        $cartId = $_GET['cartId'];

        $order = new Order(Order::getOrderByCartId($cartId));

        $customer = $order->getCustomer();

        $modID = Module::getInstanceByName($order->module);

        $base_url = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ 
                            . 'index.php?controller=order-confirmation&id_cart=' 
                            . $cartId . '&id_module=' 
                            . (int)$modID->id 
                            . '&id_order=' 
                            . $order->id 
                            . '&key=' 
                            . $customer->secure_key;

        $error = false;
            
        // Return page must be show only
        // if return is `ok` and transbank response is equal to zero
        if ($_GET['return'] == 'ok') {
            $redirect_url = $base_url . '&status=OPEN';
        }

        // if not show a error page
        if ($_GET['return'] == 'error' || $error) {
            $redirect_url = $base_url . '&status=ERR';        

        }

        Tools::redirect($redirect_url);
    }
}