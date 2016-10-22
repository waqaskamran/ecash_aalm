<?php
/**
php data_fix_ft_correction.php

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
		
$handle = fopen("2015_Q2_LoanReview_ClearlakeHoldings.csv", "r"); //read from

$report_csv = fopen('/tmp/ft_corrections.csv', 'w+'); //write to
fputcsv($report_csv, array(
'Record ID',
'Loan Status',
'Loan Status as of Date',
'Current Balance',
));

while ($data = fgetcsv($handle))
{
	$record_id = $data[0];
	$application_id = intval(substr($data[5],0,9));

	$sql = "
	SELECT ap.application_id,

	(CASE 
	WHEN ap.application_status_id IN (132,134,190,192,111,112,122) THEN 'CO' -- coll contact,rework,cccs,2nd Tier,service hold
	WHEN ap.application_status_id IN (130,131) THEN 'BK' -- bankruptcy
	WHEN ap.application_status_id IN (18,19,194) THEN 'VO' -- denied,withdrawn,canceled
	WHEN ap.application_status_id IN (109,113,162,158) THEN 'PM' -- paid,recovered,int.rec,settled 
	ELSE 'RO'
	END) AS Loan_Status,

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
	END) AS Loan_Status_as_of_Date,

	(SELECT SUM(ea1.amount)
	FROM transaction_register AS tr1
	JOIN event_amount ea1 USING (application_id, event_schedule_id, transaction_register_id)
	JOIN event_amount_type eat1 USING (event_amount_type_id)
	WHERE ea1.application_id = ap.application_id
	AND eat1.name_short <> 'irrecoverable'
	AND tr1.transaction_status = 'complete') AS Current_Balance
	FROM application AS ap
	WHERE
	ap.application_id = {$application_id}
	GROUP BY ap.application_id
	";
	$result = $db->query($sql);
	$row = $result->fetch(PDO::FETCH_OBJ);
	$loan_status = $row->Loan_Status;
	$loan_status_date = $row->Loan_Status_as_of_Date;
	$balance = $row->Current_Balance;

	//write
	fputcsv($report_csv,
	array(
	$record_id,
	$loan_status,
	$loan_status_date,
	$balance
	));

	echo $record_id, ", ", $application_id, ", ", $loan_status, ", ", $loan_status_date, ", ", $balance, "\n";
}

fclose($handle);
fclose($report_csv);

?>
