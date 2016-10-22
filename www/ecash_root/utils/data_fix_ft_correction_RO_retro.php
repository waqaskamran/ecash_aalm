<?php
/**
php data_fix_ft_correction_RO_retro.php

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
'CurrentLoanNumber',
'CurrentApplicationNumber',
'CurrentLoanDate',
'CurrentDueDate',
'NewStatus',
'CurrentBalance',
'UpdatedBalance',
'CurrentRolloverNumber',
'CurrentRolloverReference',

//'CurrentNumberOfDueDateChanges',

'NewLoanNumber',
'NewLoanDate',
'NewDueDate',
'NewRolloverNumber',
'NewRolloverReference',
'NewBalance',

//'NewNumberOfDueDateChanges',
));


$app_array = array();
while ($data = fgetcsv($handle))
{
	///////////////////////////////////////INITILIZE
	$RecordID = NULL;
	$CurrentLoanNumber = NULL;
	$CurrentApplicationNumber = NULL;
	$CurrentLoanDate = NULL;
	$CurrentDueDate = NULL;
	$NewStatus = NULL;
	$CurrentBalance = NULL;
	$UpdatedBalance = NULL;
	$CurrentRolloverNumber = NULL;
	$CurrentRolloverReference = NULL;
	
	$CurrentNumberOfDueDateChanges = NULL;

	$NewLoanNumber = NULL;
	$NewLoanDate = NULL;
	$NewDueDate = NULL;
	$NewRolloverNumber = NULL;
	$NewRolloverReference = NULL;
	$NewBalance = NULL;
	
	$NewNumberOfDueDateChanges = NULL;

	$offset = NULL;
	$current_CO = FALSE;
	$new_CO = FALSE;
	////////
	
	$RecordID = $data[0];

	//$application_id = intval(substr($data[5],0,9));
	$application_id = $data[1];

	$CurrentApplicationNumber = $application_id;

	$CurrentRolloverNumber = $data[8];
	if (empty($CurrentRolloverNumber)) $CurrentRolloverNumber = 0;

	//if (!in_array($application_id, array(903754996,905012971,905125803))) continue;

	if (in_array($application_id, $app_array))
	{
		continue;
	}
	else
	{
		$app_array[] = $application_id;
	}
	
	//////////////////////////////////////POPULATION
	///////////////////////CURRENT
	//CurrentLoanDate
	if ($CurrentRolloverNumber > 0)
	{
		$offset = $CurrentRolloverNumber - 1;
		$current_loan_date = getLoanDate($db, $application_id, $offset, TRUE);
		if (empty($current_loan_date["LoanDate"]))
		{
			$current_loan_date = getLoanDate($db, $application_id, $offset, FALSE);
			$current_CO = TRUE;
		}
		$CurrentLoanDate = $current_loan_date["LoanDate"];
		$CurrentLoanDate_F = $current_loan_date["LoanDate_F"];
	}
	else
	{
		$current_loan_date = getLoanDateByDateFunded($db, $application_id);
		$CurrentLoanDate = $current_loan_date["LoanDate"];
		$CurrentLoanDate_F = $current_loan_date["LoanDate_F"];
	}

	//CurrentDueDate
	$CurrentDueDate = getDueDate($db, $application_id, $CurrentLoanDate);

	//CurrentLoanNumber
	$CurrentNumberOfDueDateChanges = getNumberOfDueDateChanges($db, $application_id, $CurrentLoanDate);

	if ($CurrentNumberOfDueDateChanges > 0)
		$CurrentLoanNumber = $application_id . "-" . $CurrentNumberOfDueDateChanges;
	else
		$CurrentLoanNumber = $application_id;

	//CurrentBalance
	$CurrentBalance = getBalance($db, $application_id, $CurrentLoanDate);
	if ($CurrentBalance < 0) $CurrentBalance = 0.00;

	//CurrentRolloverReference
	if ($CurrentNumberOfDueDateChanges > 1)
		$CurrentRolloverReference = $application_id . "-" . $CurrentNumberOfDueDateChanges - 1;
	else
		$CurrentRolloverReference = $application_id;

	///////////////////NEW
	//NewRolloverNumber
	$NewRolloverNumber = $CurrentRolloverNumber + 1;
	
	//NewLoanDate
	$offset = $NewRolloverNumber - 1;
	$new_loan_date = getLoanDate($db, $application_id, $offset, TRUE);
	if (empty($new_loan_date["LoanDate"]))
	{
		$new_loan_date = getLoanDate($db, $application_id, $offset, FALSE);
		$new_CO = TRUE;
		
		$NewRolloverNumber = $CurrentRolloverNumber;

		if (empty($new_loan_date["LoanDate"]))
		{
			$NewLoanDate = $CurrentLoanDate;
			$NewLoanDate_F = $CurrentLoanDate_F;
		}
		else
		{
			$NewLoanDate = $new_loan_date["LoanDate"];
			$NewLoanDate_F = $new_loan_date["LoanDate_F"];
		}
	}
	else
	{
		$NewLoanDate = $new_loan_date["LoanDate"];
		$NewLoanDate_F = $new_loan_date["LoanDate_F"];
	}

	//NewDueDate
	$NewDueDate = getDueDate($db, $application_id, $NewLoanDate);

	//NewLoanNumber
	$NewNumberOfDueDateChanges = getNumberOfDueDateChanges($db, $application_id, $NewLoanDate);
	if ($NewNumberOfDueDateChanges > 0)
		$NewLoanNumber = $application_id . "-" . $NewNumberOfDueDateChanges;
	else
		$NewLoanNumber = $application_id;

	//NewRolloverReference
	if ($NewNumberOfDueDateChanges > 1)
		$NewRolloverReference = $application_id . "-" . $NewNumberOfDueDateChanges - 1;
	else
		$NewRolloverReference = $application_id;

	//NewBalance
	$NewBalance = getBalance($db, $application_id, $NewLoanDate);
	if ($NewBalance < 0) $NewBalance = 0.00;

	//NewStatus
	if ($NewBalance > 0)
		$NewStatus = "RO";
	else
		$NewStatus = "PM";

	if ($new_CO)
		$NewStatus = "CO";
	//////////////COMPARISON:
	$UpdatedBalance = $CurrentBalance - $NewBalance;
	
	//////////////CORRECTIONS
	$CurrentRolloverNumber = $CurrentRolloverNumber + $CurrentNumberOfDueDateChanges;
	$NewRolloverNumber = $NewRolloverNumber + $NewNumberOfDueDateChanges;

	/*	
	$special_handling = array(903923436,903923649,903975145,904107712,904117397,904183152,904242246,904535241,904598309,904729380);
	if (in_array($application_id, $special_handling))
	{
		$NewStatus = "PM";
	}
	*/

	//write
	fputcsv($report_csv,
	array(
	$RecordID,
	$CurrentLoanNumber,
	$CurrentApplicationNumber,
	$CurrentLoanDate_F,
	$CurrentDueDate,
	$NewStatus,
	$CurrentBalance,
	$UpdatedBalance,
	$CurrentRolloverNumber,
	$CurrentRolloverReference,
	
	//$CurrentNumberOfDueDateChanges,

	$NewLoanNumber,
	$NewLoanDate_F,
	$NewDueDate,
	$NewRolloverNumber,
	$NewRolloverReference,
	$NewBalance,
	
	//$NewNumberOfDueDateChanges,
	));

	echo "RecordID: ", $RecordID, ", ",
	"CurrentLoanNumber: ", $CurrentLoanNumber, ", ",
	"CurrentApplicationNumber: ", $CurrentApplicationNumber, ", ",
	"CurrentLoanDate: ", $CurrentLoanDate_F, ", ",
	"CurrentDueDate: ", $CurrentDueDate, ", ",
	"NewStatus: ", $NewStatus, ", ",
	"CurrentBalance: ", $CurrentBalance, ", ",
	"UpdatedBalance: ", $UpdatedBalance, ", ",
	"CurrentRolloverNumber: ", $CurrentRolloverNumber, ", ",
	"CurrentRolloverReference: ", $CurrentRolloverReference, ", ",
	
	"CurrentNumberOfDueDateChanges: ", $CurrentNumberOfDueDateChanges, ", ",
	
	"NewLoanNumber: ", $NewLoanNumber, ", ",
	"NewLoanDate: ", $NewLoanDate_F, ", ",
	"NewDueDate: ", $NewDueDate, ", ",
	"NewRolloverNumber: ", $NewRolloverNumber, ", ",
	"NewRolloverReference: ", $NewRolloverReference, ", ",
	"NewBalance: ", $NewBalance, ", ",
	
	"NewNumberOfDueDateChanges: ", $NewNumberOfDueDateChanges, ", ",
	"\n";
}

fclose($handle);
fclose($report_csv);

/////////////////////////////////////////////////////FUNCTIONS
function getLoanDate($db, $application_id, $offset, $complete = FALSE)
{
	if ($complete)
		$status_sql = " AND transaction_status = 'complete' ";
	else
		$status_sql = "";

	$sql = "
		SELECT DATE(tr.date_modified) AS LoanDate,
		DATE_FORMAT(tr.date_modified,'%m/%d/%Y') AS LoanDate_F
		FROM transaction_register AS tr
		JOIN transaction_type tt USING(company_id,transaction_type_id)
		WHERE tt.clearing_type IN ('ach','card','external')
		AND tr.amount < 0
		{$status_sql}
		AND tr.application_id = {$application_id}
		GROUP BY tr.date_effective
		ORDER BY tr.date_effective
		LIMIT 1 OFFSET {$offset}
	";
	$result = $db->query($sql);
	$row = $result->fetch(PDO::FETCH_OBJ);
	$LoanDate = $row->LoanDate;
	$LoanDate_F = $row->LoanDate_F;
	
	$return_array = array("LoanDate" => $LoanDate, "LoanDate_F" => $LoanDate_F);
	return $return_array;
}

function getLoanDateByDateFunded($db, $application_id)
{
	$sql = "
		SELECT ap.date_fund_actual AS LoanDate,
		DATE_FORMAT(ap.date_fund_actual,'%m/%d/%Y') AS LoanDate_F
		FROM application AS ap
		WHERE ap.application_id = {$application_id}
	";
	$result = $db->query($sql);
	$row = $result->fetch(PDO::FETCH_OBJ);
	$LoanDate = $row->LoanDate;
	$LoanDate_F = $row->LoanDate_F;
	
	$return_array = array("LoanDate" => $LoanDate, "LoanDate_F" => $LoanDate_F);
	return $return_array;
}

function getDueDate($db, $application_id, $LoanDate)
{
	$sql = "
		SELECT DATE_FORMAT(es.date_effective,'%m/%d/%Y') AS DueDate
		FROM event_schedule AS es
		JOIN event_type et USING(event_type_id)
		JOIN event_transaction etr USING(event_type_id)
		JOIN transaction_type tt USING(transaction_type_id)
		WHERE
		tt.clearing_type IN ('ach','card','external')
		AND es.amount_non_principal < 0
		AND es.date_effective > '{$LoanDate}'
		AND es.application_id = {$application_id}
		GROUP BY es.date_effective
		ORDER BY es.date_effective ASC
		LIMIT 1
	";
	$result = $db->query($sql);
	$row = $result->fetch(PDO::FETCH_OBJ);
	$DueDate = $row->DueDate;
	return $DueDate;
}

function getNumberOfDueDateChanges($db, $application_id, $LoanDate)
{
	$sql = "
		SELECT count(DISTINCT date_effective_after) AS application_date_effective_count
		FROM application_date_effective
		WHERE application_id = {$application_id}
		AND date_created < '{$LoanDate}'
	";
	$result = $db->query($sql);
	$row = $result->fetch(PDO::FETCH_OBJ);
	$application_date_effective_count = intval($row->application_date_effective_count);
	return $application_date_effective_count;
}

function getBalance($db, $application_id, $LoanDate)
{
	$sql = "
	SELECT SUM(ea.amount) AS Balance
	FROM transaction_register AS tr
	JOIN event_amount AS ea USING (application_id, event_schedule_id, transaction_register_id)
	JOIN event_amount_type eat USING (event_amount_type_id)
	WHERE ea.application_id = {$application_id}
	AND eat.name_short <> 'irrecoverable'
	AND tr.transaction_status = 'complete'
	AND DATE(tr.date_created) <= DATE_ADD('{$LoanDate}', INTERVAL 3 DAY)
	GROUP BY ea.application_id
	";
	$result = $db->query($sql);
	$row = $result->fetch(PDO::FETCH_OBJ);
	$Balance = $row->Balance;
	return $Balance;
}

?>
