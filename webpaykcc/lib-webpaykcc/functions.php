<?php

include_once dirname(__FILE__) . '/constants.php';

// Webpay Related Functions and Helpers

// Get the log path for a given
// path and session id
function getKCCLog($path, $session_id) {
	
	$logPath = $path 
			   . KCC_LOG_PREFIX
		  	   . $session_id
			   . '.log';

	return $logPath;
}

// Get a Random UID
// for Session Name
// we use micro time to ensure
// uniqueness
function getSessionUID(){
	// Random UID
	return date("Y.m.d.h.i.s") 
			. "_" 
			. md5(microtime() 
				. microtime());
}