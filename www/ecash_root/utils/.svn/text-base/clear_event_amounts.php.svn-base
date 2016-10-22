<?php
/**
 * Removes all event amounts and corrects the event schedule table.
 */
$application_id = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : null;

if ($application_id == null) {
	echo "Usage: php clear_event_amounts.php <application_id|all>\n";
}
include_once "../www/config.php";
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$mysqli->autocommit(FALSE);

Main($mysqli, $application_id);


function Update_Event_Schedule($mysqli, $application_id) {
	
	$application_where = '';
	
	if ($application_id != 'all') {
		settype($application_id, 'int');
		$application_where = 'AND event_schedule.application_id = '.$application_id;
	}
	
	$query = <<<END_SQL
UPDATE event_schedule
  SET
	amount_principal = event_schedule.amount_principal * 2,
	amount_non_principal = event_schedule.amount_non_principal * 2
  WHERE
  	EXISTS
  	  (
  	  	SELECT 1 FROM event_amount WHERE event_amount.event_schedule_id = event_schedule.event_schedule_id
  	  )
  	{$application_where}
;
END_SQL;
	$mysqli->query($query);
}

function Delete_Event_Amounts($mysqli, $application_id) {
	
	$application_where = '';
	
	if ($application_id != 'all') {
		settype($application_id, 'int');
		$application_where = 'WHERE application_id = '.$application_id;
	}
	
	$query = <<<END_SQL
DELETE FROM event_amount {$application_where};
END_SQL;
	
	$mysqli->query($query);
	
	$query = <<<END_SQL
DELETE FROM loan_snapshot {$application_where};
END_SQL;
	
	$mysqli->query($query);
}

function Main($mysqli, $application_id) {
	Update_Event_Schedule($mysqli, $application_id);
	$mysqli->commit();
	Delete_Event_Amounts($mysqli, $application_id);
	$mysqli->commit();

	if ($application_id == 'all') {
		echo "All application event amounts have been deleted.\n";
	} else {
		echo "Application event amounts for application #{$application_id} have been deleted.\n";
	}
}

?>
