#!/usr/bin/php
<?php
/**
 * This script should be used to rebuild the serialized monthly aggregates 
 * for an entry in the resolve daily cash report table. You can call it with 
 * either two or three parameters. 
 * 
 * rebuild-daily-cash-report.php <company_id> <start_date> <end_date<
 * 
 * if you don't specify an end date, it will default to the same value as start 
 * date.
 */

chdir(dirname(__FILE__));
require_once("../www/config.php");

if (isset($argv[1]))
{
	$company_id = $argv[1];
}
else
{
	usage();
}

if (isset($argv[2]))
{
	$start_date = $argv[2];
}
else
{
	usage();
}





function usage()
{
	echo "
/**
 * This script should be used to rebuild the serialized monthly aggregates 
 * for an entry in the resolve daily cash report table. You can call it with 
 * either two parameters. 
 * 
 * rebuild-daily-cash-report.php <company_id> <start_date>
 */\n";
	die;
}

require_once(SQL_LIB_DIR."get_mysqli.func.php");
require_once(LIB_DIR."common_functions.php");
require_once('../utils/mini-server.class.php');
require_once(SERVER_MODULE_DIR."reporting/daily_cash_report.class.php");

$server = new Server(get_log('repair'), get_mysqli(), $company_id);

Daily_Cash_Report_Query::Update_Daily_Totals($server, date('Y-m-d', strtotime($start_date)));
?>
