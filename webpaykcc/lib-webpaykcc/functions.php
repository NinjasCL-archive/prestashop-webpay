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
