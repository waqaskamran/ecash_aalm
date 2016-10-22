#!/usr/bin/php
<?php
/*
 * This script will post a transaction as complete and insert the
 * item into the transaction_ledger.
 *
 * This utility uses the current configuration based on the ../www/config.php file.
 */

	require_once(realpath("../www/config.php"));
	require_once("mini-server.class.php");
	require_once(LIB_DIR . 'common_functions.php');
	require_once(COMMON_LIB_DIR . 'pay_date_calc.3.php');
	require_once(SQL_LIB_DIR . 'scheduling.func.php');
	require_once("acl.3.php");

	if($argc > 3)
	{ 
		$company_id 			 = $argv[1];
		$application_id 		 = $argv[2];
		$transaction_register_id = $argv[3];

		$log = new Applog('ecash3.0', 5000000, 20);
		$mysqli = MySQLi_1e::Get_Instance();
		$server = new Server($log, $mysqli, $company_id);

	}
	else
	{
		Usage($argv);
	}

	echo "Posting Transaction $transaction_register_id for $application_id\n";
	Post_Transaction($application_id, $transaction_register_id);

	function Usage($argv)
	{
		echo "Usage: {$argv[0]} [company_id] [application_id] [transaction_register_id]\n";
		exit;
	}
