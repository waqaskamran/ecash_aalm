#!/usr/bin/php
<?php
/*  Usage example:  php -f ecash_data_utility.php  */

/*
 * Before using this utility, verify all of the defines below.  Only a few precautions 
 * are made to verify data is not modified on the wrong servers.  Be sure to have a full 
 * set of reference data loaded on the local DB before attempting to run the 'fund' command.
 */

require_once("../www/config.php");

require_once(COMMON_LIB_DIR."mysqli.1.php");
require_once(BASE_DIR . "server/code/transaction_query.class.php");
require_once(LIB_DIR . 'common_functions.php');
require_once(COMMON_LIB_DIR . 'pay_date_calc.3.php');
require_once(SQL_LIB_DIR . "scheduling.func.php");


$log = new Applog(APPLOG_SUBDIRECTORY.'/repairs', APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT);
$mysqli = new MySQLi_1(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

$tq = new Transaction_Query($mysqli, $log, 3);
$pdc = new Pay_Date_Calc_3(Fetch_Holiday_List());

$search_query = "
SELECT DISTINCT application_id
FROM event_schedule 
WHERE event_status = 'scheduled'
";

$result = $mysqli->Query($search_query);
$appids = array();
while ($row = $result->Fetch_Object_Row()) {
	$appids[] = $row->application_id;
}

$log->Write("Found ".count($appids)." accounts with scheduled events.");
$violators = 0;
foreach($appids as $acct) {
	$log->Write("Analyzing account {$acct}");
	
	// Get our pivot date
	$pivot_query = "
SELECT MIN(date_event) as 'pivot'
FROM event_schedule
WHERE application_id = {$acct} 
AND event_status = 'scheduled'
AND (amount_principal < 0 or amount_non_principal < 0)
";

	$result = $mysqli->Query($pivot_query);
	$pivot = $result->Fetch_Object_Row()->pivot;
	if ($pivot == null) {
		$log->Write("Account {$acct} has no pivot date. Skipping.");
		continue;
	}

	$log->Write("Account {$acct}: using pivot date {$pivot}");

	// Find the nearest next scheduled date, and the nearest previous one (scheduled or not)
	
	$next_query = "
SELECT date_event 
FROM event_schedule 
WHERE application_id = {$acct} 
AND date_event > '{$pivot}' 
AND ((amount_principal < 0) or (amount_non_principal < 0))
ORDER BY date_event asc limit 1
";

	$result = $mysqli->Query($next_query);       
	if ($result->Row_Count() == 0) {
		$log->Write("Account {$acct}: did not locate next date.");
	} else {
		$next_date = $result->Fetch_Object_Row()->date_event;
		$log->Write("Account {$acct}: using next date {$next_date}. Checking bounds.");
		$next_threshold = $pdc->Get_Business_Days_Forward($pivot, 7);
		if (strtotime($next_threshold) > strtotime($next_date)) {
			$violators += 1;
			$log->Write("Account {$acct} is in violation of next date threshold. {$next_threshold}");
			continue;
		} else {
			$log->Write("Account {$acct} has a valid next date.");
		}
	}

	$prev_query = "
SELECT date_event 
FROM event_schedule 
WHERE application_id = {$acct} 
AND date_event < '{$pivot}'
AND ((amount_principal < 0) or (amount_non_principal < 0))
ORDER BY date_event desc limit 1
";
	$result = $mysqli->Query($prev_query);
	if ($result->Row_Count() == 0) {
		$log->Write("Account {$acct}: did not locate previous date.");
	} else {
		$prev_date = $result->Fetch_Object_Row()->date_event;
		$log->Write("Account {$acct}: using previous date {$prev_date}. Checking bounds.");
		$prev_threshold = $pdc->Get_Business_Days_Backward($pivot, 7);
		if (strtotime($prev_threshold) < strtotime($prev_date)) {
			$violators += 1;
			$log->Write("Account {$acct} is in violation of previous date threshold {$prev_threshold}.");
		} else {
			$log->Write("Account {$acct} has a valid previous date.");
		}		       
	}
}

$log->Write("{$violators} threshold violators were found.");

?>
