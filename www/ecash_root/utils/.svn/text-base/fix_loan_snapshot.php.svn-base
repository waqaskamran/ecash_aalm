<?php
include_once "../www/config.php";
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$mysqli->autocommit(FALSE);

//EDIT THIS QUERY TO SELECT THE ROWS TO REPAIR
$query = "
SELECT application_id FROM loan_snapshot WHERE date_created BETWEEN 20061218120000 AND 20061218180000
";
$result = $mysqli->query($query);

$num_rows = $result->num_rows;
$count = 0;
echo "Number of Rows: $num_rows";
while ($row = $result->fetch_object()) {
	Main($mysqli, $row->application_id);
	$count++;
	echo '%', round($count / $num_rows * 100, 3), " Completed\n";
}

function update_loan_snapshot($mysqli, $application_id, $balance_object) {
	$query = <<<END_SQL
UPDATE loan_snapshot
  SET
  	date_modified = NOW(),
    balance_complete_principal = %f,
    balance_complete_service_charge = %f,
    balance_complete_fee = %f,
    balance_complete_irrecoverable = %f,
    balance_complete = %f,
    sum_pending_principal = %f,
    sum_pending_service_charge = %f,
    sum_pending_fee = %f,
    sum_pending_irrecoverable = %f,
    sum_pending = %f,
    balance_pending_principal = %f,
    balance_pending_service_charge = %f,
    balance_pending_fee = %f,
    balance_pending_irrecoverable = %f,
    balance_pending = %f
  WHERE application_id = %d
END_SQL;
	
	$result = $mysqli->Query(sprintf($query, 
			$balance_object->balance_complete_principal,
			$balance_object->balance_complete_service_charge,
			$balance_object->balance_complete_fee,
			$balance_object->balance_complete_irrecoverable,
			$balance_object->balance_complete,
			
			$balance_object->sum_pending_principal,
			$balance_object->sum_pending_service_charge,
			$balance_object->sum_pending_fee,
			$balance_object->sum_pending_irrecoverable,
			$balance_object->sum_pending,
			
			$balance_object->balance_pending_principal,
			$balance_object->balance_pending_service_charge,
			$balance_object->balance_pending_fee,
			$balance_object->balance_pending_irrecoverable,
			$balance_object->balance_pending,
			$application_id));
	
	if (!$result) { throw new Exception($mysqli->error); }
}

function fetch_balance($mysqli, $application_id) {
	$query = <<<END_SQL
SELECT
    NOW() date_modified,
    NOW() date_created,
    ea.company_id company_id,
    ea.application_id application_id,
    SUM( IF( eat.name_short = 'principal' AND tr.transaction_status = 'complete', ea.amount, 0)) balance_complete_principal,
    SUM( IF( eat.name_short = 'service_charge' AND tr.transaction_status = 'complete', ea.amount, 0)) balance_complete_service_charge,
    SUM( IF( eat.name_short = 'fee' AND tr.transaction_status = 'complete', ea.amount, 0)) balance_complete_fee,
    SUM( IF( eat.name_short = 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) balance_complete_irrecoverable,
    SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) balance_complete,
    SUM( IF( eat.name_short = 'principal' AND tr.transaction_status = 'pending', ea.amount, 0)) sum_pending_principal,
    SUM( IF( eat.name_short = 'service_charge' AND tr.transaction_status = 'pending', ea.amount, 0)) sum_pending_service_charge,
    SUM( IF( eat.name_short = 'fee' AND tr.transaction_status = 'pending', ea.amount, 0)) sum_pending_fee,
    SUM( IF( eat.name_short = 'irrecoverable' AND tr.transaction_status = 'pending', ea.amount, 0)) sum_pending_irrecoverable,
    SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'pending', ea.amount, 0)) sum_pending,
    SUM( IF( eat.name_short = 'principal' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) balance_pending_principal,
    SUM( IF( eat.name_short = 'service_charge' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) balance_pending_service_charge,
    SUM( IF( eat.name_short = 'fee' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) balance_pending_fee,
    SUM( IF( eat.name_short = 'irrecoverable' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) balance_pending_irrecoverable,
    SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) balance_pending
  FROM
    event_amount ea
    JOIN event_amount_type eat USING (event_amount_type_id)
    JOIN transaction_register tr USING (transaction_register_id)
    WHERE application_id = %d
  GROUP BY ea.application_id
END_SQL;

	$result = $mysqli->Query(sprintf($query, $application_id));
	
	if (!$result) { throw new Exception($mysqli->error); }
	
	return $result->fetch_object();
}

function Main($mysqli, $application_id) {
	try {
		$data = fetch_balance($mysqli, $application_id);
		update_loan_snapshot($mysqli, $application_id, $data);
		$mysqli->commit();
	} catch (Exception $e) {
		echo "ERROR: " . $e->getMessage();
		$mysqli->Rollback();
	}
}


?>