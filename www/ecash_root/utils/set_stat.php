<?php
if($argc > 3) { $status = $argv[3]; $application_id = $argv[2]; $company = strtolower($argv[1]);}
else Usage($argv);

require_once("../www/config.php");
define ('CUSTOMER_LIB', BASE_DIR . "customer_lib/{$company}/");
//require_once("Server.class.php");
require_once(COMMON_LIB_DIR."mysqli.1.php");
require_once(SQL_LIB_DIR . "get_mysqli.func.php");
require_once(SQL_LIB_DIR . "fetch_ach_return_code_map.func.php");

require_once(LIB_DIR . 'common_functions.php');
require_once 'config.4.php';

$log = new Applog('repair', 5000000, 20);
$mysqli = new MySQLi_1(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$server = new Server($log, get_mysqli(), 3);
$_SESSION['company_id'] = 3;
$_SESSION['company'] = $company;
$_SESSION['agent_id'] = 1;
$res = Update_Status($server, $application_id, $status);

var_dump($res);

function Usage($argv)
{
        echo "Usage: {$argv[0]} [ic|clk] [application_id] [status]\n";
        exit;
}