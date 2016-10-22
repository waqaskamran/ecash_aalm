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
require_once(SQL_LIB_DIR."scheduling.func.php");
require_once(LIB_DIR."common_functions.php");

$mysqli = get_mysqli();
Main($mysqli);

function Main($mysqli) {
	echo "Searching for accounts";
	$query = <<<END_SQL
SELECT
    a.application_id,
    a.company_id,
    SUM(IF (tr.transaction_status = 'complete' AND eat.name_short ='service_charge', -ea.amount, 0)) as service_charge,
    SUM(IF (tr.transaction_status = 'complete' AND eat.name_short = 'fee', -ea.amount, 0)) as fee,
    SUM(IF (tr.transaction_status = 'complete' AND eat.name_short IN ('service_charge', 'fee'), ea.amount, 0)) as principal,
    SUM(IF (tr.transaction_status = 'complete' AND eat.name_short <> 'irrecoverable', ea.amount, 0)) as total_balance,
    SUM(IF (tr.transaction_status = 'complete' AND eat.name_short NOT IN ('principal', 'irrecoverable'), ea.amount, 0)) as current_non_principal_balance
  FROM
    application a
    JOIN event_amount ea USING (application_id)
    JOIN transaction_register tr USING (transaction_register_id)
    JOIN event_amount_type eat USING (event_amount_type_id)
  WHERE
    application_status_id IN (109, 113)
  GROUP BY a.application_id
  HAVING current_non_principal_balance != 0
END_SQL;

	$result = $mysqli->Query($query);
	
	while ($row = $result->Fetch_Object_Row()) {
		echo "Repairing application {$row->application_id}\n";
		ProcessRow($mysqli, $row);
	}
	
}

function ProcessRow($mysqli, $row) {
	$_SESSION['company_id'] = $row->company_id;
	$_SESSION['agent_id'] = '603';
	try {
		$mysqli->Start_Transaction();
		$event_schedule_id = AddInternalAdjustment($mysqli, $row->application_id, $row->company_id, $row->principal, $row->service_charge, $row->fee);
		PostInternalAdjustments($row->application_id, $event_schedule_id);
		$mysqli->Commit();
	} catch (Exception $e) {
		$mysqli->Rollback();
		echo "Error: ", $e->getMessage(), "\n";
	}
}

function PostInternalAdjustments($application_id, $event_schedule_id) {
	$trids = Record_Current_Scheduled_Events_To_Register("2037-01-01", $application_id, $event_schedule_id, 'all', 'immediate posting');
	foreach ($trids as $trid)
	{
		Post_Transaction($application_id, $trid);
	}

	return $evid;
}

function AddInternalAdjustment($mysqli, $application_id, $company_id, $principal, $service_charge, $fee) {
	$event_schedule_query = <<<END_SQL
INSERT event_schedule
	(
		date_modified,
		date_created,
		company_id,
		application_id,
		event_type_id,
		configuration_trace_data,
		event_status,
		date_event,
		date_effective,
		context
	) VALUES (
		NOW(),
		NOW(),
		{$company_id},
		{$application_id},
		10,
		'Datafix for mantis #4659',
		'scheduled',
		CURRENT_DATE(),
		CURRENT_DATE(),
		'manual'
	)
END_SQL;
	$mysqli->query($event_schedule_query);
	$event_schedule_id = $mysqli->Insert_Id();
	
	$origin_query = <<<END_SQL
UPDATE event_schedule
  SET
  	origin_id = {$event_schedule_id}
  WHERE
  	event_schedule_id = {$event_schedule_id}
END_SQL;

	$mysqli->query($event_schedule_query);
	
	$amount_rows = array();
	
	if ($principal != 0) {
		$amount_rows[] = "(
			{$event_schedule_id},
			0,
			1,
			{$principal},
			{$application_id},
			0,
			{$company_id},
			NOW(),
			NOW()
		)";
	}
	
	if ($service_charge != 0) {
		$amount_rows[] = "(
			{$event_schedule_id},
			0,
			2,
			{$service_charge},
			{$application_id},
			0,
			{$company_id},
			NOW(),
			NOW()
		)";
	}
	
	if ($fee != 0) {
		$amount_rows[] = "(
			{$event_schedule_id},
			0,
			3,
			{$fee},
			{$application_id},
			0,
			{$company_id},
			NOW(),
			NOW()
		)";
	}
	
	$event_amounts_insert = <<<END_SQL
INSERT event_amount
	(
		event_schedule_id, 
		transaction_register_id,
		event_amount_type_id, 
		amount, 
		application_id, 
		num_reattempt, 
		company_id, 
		date_modified, 
		date_created
	) VALUES 
END_SQL;
	$event_amounts_insert .= implode(', ', $amount_rows);
	$mysqli->query($event_amounts_insert);
	
	return $event_schedule_id;
}



class Event_Split_Container {
	public $event_schedule_id = '';
	public $split_amount;
	
	public function __construct($id, $amount) {
		$this->event_schedule_id = $id;
		$this->split_amount = $amount;
	}
}

?>
