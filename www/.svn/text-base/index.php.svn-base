<?php
define('CUSTOMER_WWW_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('CUSTOMER_DIR', CUSTOMER_WWW_DIR . ".." . DIRECTORY_SEPARATOR);
define('CUSTOMER_CODE_DIR', CUSTOMER_DIR . "code" . DIRECTORY_SEPARATOR);

/**
 * Use the Environment Variable get the ECASH_WWW_DIR
 */
define('ECASH_WWW_DIR', getenv('ECASH_WWW_DIR'));

/**
 * Uses the Environment Variables from the .htaccess file to determine
 * what the execution mode is and who the customer is so the appropriate
 * configuration file can be loaded.
 */
$customer = getenv('ECASH_CUSTOMER');
$exec_mode = getenv('ECASH_EXEC_MODE');
	
if(  defined('CUSTOMER_DIR') &&
   ! empty($customer) &&
   ! empty($exec_mode) &&
   file_exists(CUSTOMER_CODE_DIR . "{$customer}/Config/{$exec_mode}.php"))
{
	require_once(CUSTOMER_CODE_DIR . "{$customer}/Config/{$exec_mode}.php");
}
else
{
	/**
	  * This should now instead die a horrible death.
	  *
	  * @TODO replace with red screen of death or similar
	  */
	die("No config found in '" . CUSTOMER_CODE_DIR . "{$customer}/Config/{$exec_mode}.php'");
}

/**
 * Loads the config file from the main eCash module
 * and include the index.php file.
 */
require_once ECASH_WWW_DIR . 'config.php';
include ECASH_WWW_DIR . 'index.php';

?>
