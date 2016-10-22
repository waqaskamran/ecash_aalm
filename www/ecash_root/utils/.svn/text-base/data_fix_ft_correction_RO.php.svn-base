<?php
/**
php data_fix_ft_correction_RO.php

TO DO: active with ach flag has no payments
*/
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once "../www/config.php";
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(CUSTOMER_LIB."failure_dfa.php");
require_once(SERVER_CODE_DIR . 'comment.class.php');

$db = ECash::getMasterDb();
$company_id = 1;
$agent_id = 1;
$server = ECash::getServer();
$server->company_id = $company_id;

echo "Started...\n";
		
$handle = fopen("2015-11-11_RolloverUpdateTemplate_ClearlakeHoldings.csv", "r"); //read from
$report_csv = fopen('/tmp/ft_corrections_RO.csv', 'w+'); //write to

fputcsv($report_csv, array(
'RecordID',
'ApplicationNumber',
'NewStatus',
'NewLoanNumber',
'NewLoanDate',
'NewDueDate',
'NewRolloverNumber',
'NewRolloverReference',
'NewBalance'
));


$app_array = array();
while ($data = fgetcsv($handle))
{
	$record_id = $data[0];
	//$application_id = intval(substr($data[5],0,9));
	$application_id = $data[1];

	if (in_array($application_id, $app_array))
	{
		continue;
	}
	else
	{
		$app_array[] = $application_id;
	}

	$sql = "
	SELECT ap.application_id,
	IF(duedatemodCountTbl.cnt > 0, CONCAT(ap.application_id,'-',duedatemodCountTbl.cnt), ap.application_id) AS NewLoanNumber,

	(CASE
	WHEN transCountTbl.cnt > 0 AND duedatemodCountTbl.cnt > 0 THEN transCountTbl.cnt + duedatemodCountTbl.cnt
	WHEN transCountTbl.cnt > 0 THEN transCountTbl.cnt
	WHEN duedatemodCountTbl.cnt > 0 THEN duedatemodCountTbl.cnt
	ELSE ''
	END) AS NewRolloverNumber,

	IF(duedatemodCountTbl.cnt > 1, CONCAT(ap.application_id,'-',duedatemodCountTbl.cnt - 1), ap.application_id) AS NewRolloverReference,

	(CASE 
	WHEN ap.application_status_id IN (132,134,190,192,111,112,122) THEN 'CO' -- coll contact,rework,cccs,2nd Tier,service hold
	WHEN ap.application_status_id IN (130,131) THEN 'BK' -- bankruptcy
	WHEN ap.application_status_id IN (18,19,194) THEN 'VO' -- denied,withdrawn,canceled
	WHEN ap.application_status_id IN (109,113,162,158) THEN 'PM' -- paid,recovered,int.rec,settled 
	ELSE 'RO'
	END) AS NewLoanStatus,

	(CASE
	-- CO,BK,VO,PM
	WHEN ap.application_status_id IN (132,134,190,192,111,112,130,131,18,19,194,109,113,162,158) THEN DATE_FORMAT(ap.date_application_status_set,'%m/%d/%Y')
	ELSE
	(
	SELECT DATE_FORMAT(tr.date_modified,'%m/%d/%Y')
	FROM transaction_register AS tr
	JOIN transaction_type AS tt USING (company_id,transaction_type_id)
	WHERE tr.application_id = ap.application_id
	AND tr.company_id = ap.company_id
	AND tt.company_id = ap.company_id
	AND tt.clearing_type IN ('ach','card','external')
	AND tr.amount < 0
	AND tr.transaction_status = 'complete'
	ORDER BY tr.transaction_register_id DESC
	LIMIT 1
	)
	END) AS NewLoanDate,

	(
	SELECT DATE_FORMAT(pt.date_effective,'%m/%d/%Y')
	FROM payment_timing AS pt
	WHERE pt.application_id = ap.application_id
	AND pt.event_status = 'scheduled'
	ORDER BY pt.date_effective ASC
	LIMIT 1
	) AS NewDueDate,

	(SELECT SUM(ea1.amount)
	FROM transaction_register AS tr1
	JOIN event_amount ea1 USING (application_id, event_schedule_id, transaction_register_id)
	JOIN event_amount_type eat1 USING (event_amount_type_id)
	WHERE ea1.application_id = ap.application_id
	AND eat1.name_short <> 'irrecoverable'
	AND tr1.transaction_status = 'complete') AS NewBalance,

	aps.name AS Application_Status

	FROM application AS ap
	JOIN application_status AS aps USING (application_status_id)

	LEFT JOIN 
	(
		SELECT application_id,count(DISTINCT date_effective_after) as cnt
		FROM application_date_effective
		GROUP BY application_id
	) AS duedatemodCountTbl ON duedatemodCountTbl.application_id = ap.application_id

	LEFT JOIN 
	(
		SELECT application_id,count(DISTINCT date_effective) AS cnt
		FROM transaction_register AS tr
		JOIN transaction_type tt USING(transaction_type_id)
		WHERE tt.clearing_type IN ('ach','card','external')
		AND tr.amount < 0
		AND transaction_status = 'complete'
		GROUP BY application_id
	) AS transCountTbl ON transCountTbl.application_id = ap.application_id

	WHERE
	ap.application_id = {$application_id}
	GROUP BY ap.application_id
	";
	$result = $db->query($sql);
	$row = $result->fetch(PDO::FETCH_OBJ);

	$new_loan_status = $row->NewLoanStatus;
	$new_loan_number = $row->NewLoanNumber;
	$new_loan_date = $row->NewLoanDate;
	$new_due_date = $row->NewDueDate;
	$new_rollover_number = $row->NewRolloverNumber;
	$new_rollover_reference = $row->NewRolloverReference;
	$new_balance = $row->NewBalance;
	$application_status = $row->Application_Status;

	$today = "11/13/2015";

	if ($new_balance < 0) $new_balance = 0.00;

	if ($new_loan_status == "RO"
		&& !isset($new_due_date)
	)
	{
		$new_due_date = $today;
	}

	if ($new_loan_status == "RO"
		&& $new_rollover_number == 0
	)
	{
		$new_loan_status = "CO";
		$new_loan_date = $today;
		$new_due_date = NULL;
	}

	$special_handling = array(903923436,903923649,903975145,904107712,904117397,904183152,904242246,904535241,904598309,904729380);
	if (in_array($application_id, $special_handling))
	{
		$new_loan_status = "PM";
	}
	
	//write
	fputcsv($report_csv,
	array(
	$record_id,
	$application_id,
	$new_loan_status,
	$new_loan_number,
	$new_loan_date,
	$new_due_date,
	$new_rollover_number,
	$new_rollover_reference,
	$new_balance
	//$application_status
	));

	echo $record_id, ", ",
	$application_id, ", ",
	$new_loan_status, ", ",
	$new_loan_number, ", ",
	$new_loan_date, ", ",
	$new_due_date, ", ",
	$new_rollover_number, ", ",
	$new_rollover_reference, ", ",
	$new_balance, ", ",
	$application_status, "\n";
}

fclose($handle);
fclose($report_csv);

?>
