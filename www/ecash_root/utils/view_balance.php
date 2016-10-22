#!/usr/bin/php
<?php
/*
 * This script will view a customer's balance info via Fetch_Balance_Info
 * which currently gets the customer balance via loan snapshot.
 *
 * This utility uses the current configuration based on the BASE_DIR/www/config.php file.
 */


require_once("../www/config.php");
define ('CUSTOMER_LIB', BASE_DIR . "customer_lib/clk/");
require_once("mini-server.class.php");
require_once(COMMON_LIB_DIR."mysqli.1.php");
require_once(LIB_DIR . 'common_functions.php');
require_once(SQL_LIB_DIR . 'scheduling.func.php');
require_once(COMMON_LIB_DIR . 'pay_date_calc.3.php');

if($argc > 1) { $application_id = $argv[1]; }
else Usage($argv);

$balance_info = Fetch_Balance_Information($application_id);
var_dump($balance_info);

exit;

function Usage($argv)
{
        echo "Usage: {$argv[0]} [application_id]\n\n";

        exit;
}