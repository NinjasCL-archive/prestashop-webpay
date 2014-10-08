<?php

// Constants Used in Different
// Parts of the Module
define('KCC_PATH', 'WEBPAY_KCC_PATH');
define('KCC_URL', 'WEBPAY_KCC_URL');
define('KCC_LOG', 'WEBPAY_KCC_LOGPATH');

// Transaction Type
// Maybe Change it to something more 
// configurable in the future
define('KCC_TRANSACTION_TYPE', 'TR_NORMAL');

// Use Exec
// Eventually we could use
// perl for not using php's exec
// but for know this is just a dummy check
define('KCC_USE_EXEC', true);

// Useful Constants
// This constant is used in log
// generation
define('KCC_LOG_PREFIX', 'TBK_');

// CGI File
define('KCC_CGI_NAME', 'tbk_bp_pago.cgi');

// Check CGI File
define('KCC_CGI_CHECK', 'tbk_check_mac.cgi');

// Results
define('KCC_ACCEPTED_RESULT', 'ACEPTADO');
define('KCC_REJECTED_RESULT', 'RECHAZADO');

// Webpay Responses
define('KCC_OK_RESPONSE', 0);

define('KCC_VERIFICATION_OK', 'CORRECTO');
