<?php
/**
php data_fix_complete_schedule.php
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

//Create array:
//$app_array = array(901426941);

$not_reprocessed = array();

echo "Started...\n";
$i = 0;
$sql = "
SELECT DISTINCT
es.application_id
FROM event_schedule AS es
JOIN event_type AS etp ON (etp.company_id=es.company_id AND etp.event_type_id=es.event_type_id)
JOIN application AS ap ON (ap.application_id = es.application_id)
JOIN application_status AS st ON (st.application_status_id = ap.application_status_id)
JOIN event_amount AS ea ON (ea.event_schedule_id = es.event_schedule_id)
JOIN event_transaction AS et ON (et.company_id = es.company_id
AND et.event_type_id = es.event_type_id)
JOIN transaction_type AS tt ON (tt.company_id = et.company_id
AND tt.transaction_type_id = et.transaction_type_id)
WHERE
ea.amount < 0
AND
es.event_status = 'scheduled'
AND
tt.clearing_type IN ('ach')
AND
es.date_effective BETWEEN '2015-07-08' AND '2015-07-22'
AND
es.company_id = 1
and ap.application_status_id != 121
-- GROUP BY application_id
ORDER BY es.date_effective ASC;
";
$result = $db->query($sql);
while ($row = $result->fetch(PDO::FETCH_OBJ))
{
	$application_id = $row->application_id;
	$i++;
	try
	{
		echo $application_id, ", ", $i, "\n";
		//$event_schedule_id = intval($row->event_schedule_id);
		/*
		//do not remove to restore reattempts
		$query = "
			DELETE FROM event_amount
			WHERE event_schedule_id = {$event_schedule_id}
			AND application_id = {$application_id}
		";
		$db->query($query);

		$query = "
		 	DELETE FROM event_schedule
			WHERE event_schedule_id = {$event_schedule_id}
			AND application_id = {$application_id}
		 ";
		$db->query($query);
		*/

		//Complete_Schedule($application_id);

		//Remove_Unregistered_Events_From_Schedule($application_id);
		/*	
		Update_Status(NULL, $application_id, 'collections_rework::collections::customer::*root');

		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue('collections_rework')->getNewQueueItem($application_id);
		$qm->moveToQueue($queue_item, 'collections_rework');
		*/			
		//$comment = new Comment();
		//$comment->Add_Comment($company_id, $application_id, $agent_id, "Assembla 24, removed Full Balance Pull", "standard");
	}
	catch(Exception $e)
	{
		echo $e, "\n";
		$not_reprocessed[] = $application_id;
		continue;
	}
}

echo "not processed: \n";
var_dump($not_reprocessed);
?>

