#!/usr/bin/php
<?php
/**
 * This script is to be used for data fixups where accounts will be
 * manually modified and an External Collections delta will need to
 * be created for the account.
 * 
 * Be very careful with this because no safeguards are being used
 * to make certain the account is even in 2nd Tier Sent.
 * 
 *
 * This utility uses the current configuration based on the ../www/config.php file.
 *
 * @author Brian Ronald <brian.ronald@sellingsource.com>
 */

require_once("../www/config.php");
require_once("mini-server.class.php");
require_once(COMMON_LIB_DIR."mysqli.1.php");
require_once(LIB_DIR . 'common_functions.php');
require_once(SERVER_CODE_DIR . "external_collections_query.class.php");
require_once(SERVER_CODE_DIR . "external_collections.class.php");
require_once("acl.3.php");

$log = new Applog('repair', 5000000, 20);
$mysqli = new MySQLi_1(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$server = new Server($log, $mysqli, 3);
$ec = new External_Collections($server);

if($argc > 2) { 
	$application_id = $argv[1]; 
	$adjustment_amount = $argv[2];
} else Usage($argv);

echo "Creating External Collections Delta for App ID $application_id for the amount of $adjustment_amount\n";
try {
	$ec->Create_EC_Delta_From($application_id , $adjustment_amount);
}
catch (Exception $e) {
	echo $e->getMessage();
	echo $e->getTrace();
	die();
}

function Usage($argv)
{
		echo "Create an External Collections Delta :\n\n";
		echo "This creates an adjustment for the external collections company\n";
		echo "to notify them of an increase or decrease in the customer's balance.\n";
		echo "Use a positive number to specify the account has an increase in balance\n";
		echo "or a negative number to specify the account has a decrease in balance.\n\n";
        echo "Usage: {$argv[0]} [application_id] [adjustment amount]\n";
        exit;
}