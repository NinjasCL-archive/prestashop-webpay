<?php

// This file is the callback
// that Webpay uses for validation

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
include_once 'webpaykcc.php';

class WebpayKCC_Postback {

}

$notify = new WebpayKCC_Postback();
$notify->init();