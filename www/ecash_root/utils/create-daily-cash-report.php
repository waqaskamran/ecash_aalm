#!/usr/bin/php
<?php
/*  Usage example:  php -f ecash_data_utility.php  */

/*
 * Before using this utility, verify all of the defines below.  Only a few precautions 
 * are made to verify data is not modified on the wrong servers.  Be sure to have a full 
 * set of reference data loaded on the local DB before attempting to run the 'fund' command.
 */

chdir(dirname(__FILE__));
require_once("../www/config.php");

require_once(SQL_LIB_DIR."get_mysqli.func.php");
require_once(LIB_DIR."common_functions.php");
require_once('../utils/mini-server.class.php');
require_once(SERVER_MODULE_DIR."reporting/daily_cash_report.class.php");

$server = new Server(get_log('test'), get_mysqli(), 3);

$dcr = new Daily_Cash_Report_Query($server);

$dcr->Create_Daily_Cash_Report();
?>
