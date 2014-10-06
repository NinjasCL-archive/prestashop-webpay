<?php

include_once dirname(__FILE__) . '/constants.php';

// Webpay Related Functions and Helpers

// Get the log path for a given
// path and session id
function getKCCLog($path, $session_id) {
	
	$logPath = $path 
			   . KCC_LOG_PREFIX
		  	   . $session_id
			   . 'log';

	return $logPath;
}