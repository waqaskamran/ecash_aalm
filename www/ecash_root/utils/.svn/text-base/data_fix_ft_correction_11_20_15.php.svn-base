<?php
/**
php data_fix_ft_correction_11_20_15.php
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
		
$handle = fopen("2015-11-20_LoanReview_ClearlakeHoldings_DuplicatedAccounts.csv", "r"); //read from
$report_csv = fopen('/tmp/ft_corrections_11_20_15_v2.csv', 'w+'); //write to


fputcsv($report_csv, array(
'Record ID',
'Loan Number',
'Rollover Number',
'Rollover Reference',
'Origination Date',
'Due Date',
'Loan Amount',
'Current Balance',
'Current Loan Status',
'Status As Of Date',
'Payment Amount',
'Payment Date',
'Returned Item Code',
'Returned Item Date'
));


$app_array = array();
while ($data = fgetcsv($handle))
{
	$RecordID = $data[0];

	$application_id = intval(substr($data[1],0,9));

	$LoanNumber = $data[1];

	$RolloverNumber = intval($data[2]);
	$RolloverReference = $data[3];
	$OriginationDate = $data[4];
	$OriginationDate_F = date("Y-m-d", strtotime($OriginationDate));
	$DueDate = $data[5];
	$LoanAmount = intval($data[6]);

	//if (!in_array($application_id, array(900958589))) continue;

	//Transaction By Offsett or Date
	/*
	if ($RolloverNumber > 0)
	{
		$transaction = getLastTransactionByOffset($db, $application_id, $RolloverNumber - 1);
	}
	else
	{
	*/
		$transaction = getLastTransactionByDate($db, $application_id, $OriginationDate_F);
	//}
	$transaction_status = $transaction["transaction_status"];
	$PaymentAmount = $transaction["PaymentAmount"];
	$PaymentDate = $transaction["PaymentDate"];
	$PaymentDate_F = date("Y-m-d", strtotime($PaymentDate));
	$ReturnedItemCode = $transaction["ReturnedItemCode"];
	$ReturnedItemDate = $transaction["ReturnedItemDate"];
	
	$StatusAsOfDate = $PaymentDate;

	//Balance By Date
	//$balance_by_date = getBalanceByDate($db, $application_id, $OriginationDate_F);
	$balance_by_date = getBalanceByDate($db, $application_id, $PaymentDate_F);

	//$application_status_id_by_date = getStatusByDate($db, $application_id, $OriginationDate_F);
	$application_status_id_by_date = getStatusByDate($db, $application_id, $PaymentDate_F);

	//CurrentBalance
	if ($transaction_status == "complete")
	{
		$CurrentBalance = 0;
		$CurrentLoanStatus = "Paid as Agreed";
		/*
		$prev_transaction_status_failed = FALSE;
		if ($RolloverNumber > 1)
		{
			$prev_trans = getLastTransactionByOffset($db, $application_id, $RolloverNumber - 2);
			$prev_transaction_status = $prev_trans["transaction_status"];
			if ($prev_transaction_status == "failed")
			{
				$prev_transaction_status_failed = TRUE;
			}
		}
		
		if ($prev_transaction_status_failed)
		{
			$CurrentLoanStatus = "Paid Default";
		}
		else
		{
			$CurrentLoanStatus = "Paid as Agreed";
		}

		$CurrentBalance = 0;

		if ($RolloverNumber == 1)
		{
			$StatusAsOfDate = "New Loan";
		}
		else if ($RolloverNumber > 0 || $PaymentAmount > 0)
		{
			$StatusAsOfDate = "New Rollover/Cycle";
		}
		else
		{
			$StatusAsOfDate = "New Loan";
		}
		*/
	}
	else if ($transaction_status == "failed")
	{	
		if (in_array($application_status_id_by_date, array(112)))
		{
			$CurrentLoanStatus = "Charged-off";
			$CurrentBalance = 0;
		}
		/*
		else if (in_array($application_status_id_by_date, array(131)))
		{
			$CurrentLoanStatus = "Returned Item";
			$StatusAsOfDate = "Bankruptcy";
			$CurrentBalance = 0;
			$PaymentAmount = NULL;
			$PaymentDate = NULL;
			$ReturnedItemCode = NULL;
			$ReturnedItemDate = NULL;
		}
		*/
		else
		{
			$CurrentLoanStatus = "Charged-off";
			$CurrentBalance = $balance_by_date;
		}
	}
	else;

	//Adjusments
	if ($RolloverNumber == 0) $RolloverNumber = NULL;

	if (in_array($application_status_id_by_date, array(131)))
	{
		$CurrentLoanStatus = "Bankruptcy";
		$CurrentBalance = 0;
		$PaymentAmount = NULL;
		$PaymentDate = NULL;
		$ReturnedItemCode = NULL;
		$ReturnedItemDate = NULL;
	}

	//write
	fputcsv($report_csv,
	array(
	$RecordID,
	$LoanNumber,
	$RolloverNumber,
	$RolloverReference,
	$OriginationDate,
	$DueDate,
	$LoanAmount,
	$CurrentBalance,
	$CurrentLoanStatus,
	$StatusAsOfDate,
	$PaymentAmount,
	$PaymentDate,
	$ReturnedItemCode,
	$ReturnedItemDate
	));

	echo "RecordID: ", $RecordID, ", ",
	"application_id: ", $application_id, ", ",
	"LoanNumber: ", $LoanNumber, ", ",
	"RolloverNumber: ", $RolloverNumber, ", ",
	"RolloverReference: ", $RolloverReference, ", ",
	"OriginationDate: ", $OriginationDate, ", ",
	//"OriginationDate_F: ", $OriginationDate_F, ", ",
	"DueDate: ", $DueDate, ", ",
	"LoanAmount: ", $LoanAmount, ", ",
	//"transaction_status_by_date: ", $transaction_status_by_date, ", ",
	"transaction_status: ", $transaction_status, ", ",
	"PaymentAmount: ", $PaymentAmount, ", ",
	"PaymentDate: ", $PaymentDate, ", ",
	"PaymentDate_F: ", $PaymentDate_F, ", ",
	"ReturnedItemCode: ", $ReturnedItemCode, ", ",
	"ReturnedItemDate: ", $ReturnedItemDate, ", ",
	"application_status_id_by_date: ", $application_status_id_by_date, ", ",
	"balance_by_date: ", $balance_by_date, ", ",
	"StatusAsOfDate: ", $StatusAsOfDate, ", ",
	"CurrentLoanStatus: ", $CurrentLoanStatus, ", ",
	"CurrentBalance: ", $CurrentBalance, ", ",
	"\n";
}

fclose($handle);
fclose($report_csv);

////////////////////////////////////////////////////////////////////////////////////FUNCTIONS
function getStatusByDate($db, $application_id, $date)
{
	$sql = "
		SELECT application_status_id
		FROM status_history
		WHERE application_id = {$application_id}
		AND DATE(date_created) <= '{$date}'
		ORDER BY status_history_id DESC
		LIMIT 1
	";
	$result = $db->query($sql);
	$row = $result->fetch(PDO::FETCH_OBJ);
	$application_status_id = $row->application_status_id;
	return $application_status_id;
}

function getStatus($db, $application_id)
{
	$sql = "
		SELECT application_status_id
		FROM application
		WHERE application_id = {$application_id}
	";
	$result = $db->query($sql);
	$row = $result->fetch(PDO::FETCH_OBJ);
	$application_status_id = $row->application_status_id;
	return $application_status_id;
}

function getLastTransactionByDate($db, $application_id, $date)
{
	$sql = "
		SELECT tr.transaction_status,
		SUM(ABS(tr.amount)) AS PaymentAmount,
		DATE_FORMAT(tr.date_effective,'%m/%d/%Y') AS PaymentDate,
		-- DATE_FORMAT(tr.date_modified,'%m/%d/%Y') AS PaymentDate,
		arc.name_short AS ReturnedItemCode,
		-- IF(tr.transaction_status = 'failed', DATE_FORMAT(arc.date_modified,'%m/%d/%Y'), NULL) AS ReturnedItemDate
		IF(tr.transaction_status = 'failed', DATE_FORMAT(tr.date_modified,'%m/%d/%Y'), NULL) AS ReturnedItemDate
		FROM transaction_register AS tr
		JOIN transaction_type tt USING(company_id,transaction_type_id)
		LEFT JOIN ach ON (ach.ach_id=tr.ach_id)
		LEFT JOIN ach_return_code AS arc ON (arc.ach_return_code_id=ach.ach_return_code_id)
		-- LEFT JOIN card_process AS cp ON (cp.card_process_id=tr.card_process_id)
		-- LEFT JOIN card_process_response AS cpr ON (cpr.reason_code=cp.reason_code)
		WHERE tt.clearing_type IN ('ach','card','external')
		AND tr.amount < 0
		AND tr.application_id = {$application_id}
		-- <= date
		-- AND DATE(tr.date_modified) > '{$date}'
		AND DATE(tr.date_modified) > DATE_ADD('{$date}', INTERVAL 5 DAY)
		GROUP BY tr.date_effective
		ORDER BY tr.date_effective ASC  -- DESC
		LIMIT 1
	";
	$result = $db->query($sql);
	$row = $result->fetch(PDO::FETCH_OBJ);
	$transaction_status = $row->transaction_status;
	$PaymentAmount = $row->PaymentAmount;
	$PaymentDate = $row->PaymentDate;
	$ReturnedItemCode = $row->ReturnedItemCode;
	$ReturnedItemDate = $row->ReturnedItemDate;
	$transaction = array("transaction_status" => $transaction_status,
				"PaymentAmount" => $PaymentAmount,
				"PaymentDate" => $PaymentDate,
				"ReturnedItemCode" => $ReturnedItemCode,
				"ReturnedItemDate" => $ReturnedItemDate
			);

	return $transaction;
}

function getLastTransactionByOffset($db, $application_id, $offset)
{
	$sql = "
		SELECT tr.transaction_status,
			SUM(ABS(tr.amount)) AS PaymentAmount,
			DATE_FORMAT(tr.date_effective,'%m/%d/%Y') AS PaymentDate,
			arc.name_short AS ReturnedItemCode,
			-- IF(tr.transaction_status = 'failed', DATE_FORMAT(arc.date_modified,'%m/%d/%Y'), NULL) AS ReturnedItemDate
			IF(tr.transaction_status = 'failed', DATE_FORMAT(tr.date_modified,'%m/%d/%Y'), NULL) AS ReturnedItemDate
		FROM transaction_register AS tr
		JOIN transaction_type tt USING(company_id,transaction_type_id)
		LEFT JOIN ach ON (ach.ach_id=tr.ach_id)
		LEFT JOIN ach_return_code AS arc ON (arc.ach_return_code_id=ach.ach_return_code_id)
		-- LEFT JOIN card_process AS cp ON (cp.card_process_id=tr.card_process_id)
		-- LEFT JOIN card_process_response AS cpr ON (cpr.reason_code=cp.reason_code)
		WHERE tt.clearing_type IN ('ach','card','external')
		AND tr.amount < 0
		AND tr.application_id = {$application_id}
		GROUP BY tr.date_effective
		ORDER BY tr.date_effective
		LIMIT 1 OFFSET {$offset}
	";
	$result = $db->query($sql);
	$row = $result->fetch(PDO::FETCH_OBJ);
	$transaction_status = $row->transaction_status;
	$PaymentAmount = $row->PaymentAmount;
	$PaymentDate = $row->PaymentDate;
	$ReturnedItemCode = $row->ReturnedItemCode;
	$ReturnedItemDate = $row->ReturnedItemDate;
	$transaction = array("transaction_status" => $transaction_status,
				"PaymentAmount" => $PaymentAmount,
				"PaymentDate" => $PaymentDate,
				"ReturnedItemCode" => $ReturnedItemCode,
				"ReturnedItemDate" => $ReturnedItemDate
			);
	return $transaction;
}

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

function getBalanceByDate($db, $application_id, $LoanDate)
{
	$sql = "
	SELECT SUM(ea.amount) AS Balance
	FROM transaction_register AS tr
	JOIN event_amount AS ea USING (application_id, event_schedule_id, transaction_register_id)
	JOIN event_amount_type eat USING (event_amount_type_id)
	WHERE ea.application_id = {$application_id}
	AND eat.name_short <> 'irrecoverable'
	AND tr.transaction_status = 'complete'
	AND DATE(tr.date_modified) <= DATE_ADD('{$LoanDate}', INTERVAL 5 DAY)
	-- AND DATE(tr.date_modified) <= '{$LoanDate}'
	GROUP BY ea.application_id
	";
	$result = $db->query($sql);
	$row = $result->fetch(PDO::FETCH_OBJ);
	$balance = $row->Balance;
	return $balance;
}

function getBalance($db, $application_id)
{
	$sql = "
	SELECT SUM(ea.amount) AS Balance
	FROM transaction_register AS tr
	JOIN event_amount AS ea USING (application_id, event_schedule_id, transaction_register_id)
	JOIN event_amount_type eat USING (event_amount_type_id)
	WHERE ea.application_id = {$application_id}
	AND eat.name_short <> 'irrecoverable'
	AND tr.transaction_status = 'complete'
	GROUP BY ea.application_id
	";
	$result = $db->query($sql);
	$row = $result->fetch(PDO::FETCH_OBJ);
	$balance = $row->Balance;
	return $balance;
}

?>
