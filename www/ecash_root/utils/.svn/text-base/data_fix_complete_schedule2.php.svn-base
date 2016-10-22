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
$sql = "
SELECT DISTINCT
tr.application_id,
tr.event_schedule_id,
tr.transaction_register_id,
tr.date_effective,
tr.amount,
st.name AS status
FROM application AS ap
JOIN application_status AS st ON (st.application_status_id = ap.application_status_id)
JOIN transaction_register AS tr ON (tr.application_id = ap.application_id)
WHERE ap.application_status_id IN (20)
AND tr.transaction_type_id=4
AND tr.transaction_status='complete'
AND tr.date_effective > '2015-07-06'
-- and tr.application_id = 905353577
";
$result = $db->query($sql);
while ($row = $result->fetch(PDO::FETCH_OBJ))
{
	$application_id = intval($row->application_id);
	try
	{
		echo $application_id, "\n";
		/*
		$event_schedule_id = intval($row->event_schedule_id);
		$transaction_register_id = intval($row->transaction_register_id);

$query = "
DELETE FROM transaction_history
WHERE transaction_register_id = {$transaction_register_id}
AND application_id = {$application_id}
";
$db->query($query);

$query = "
DELETE FROM transaction_ledger
WHERE transaction_register_id = {$transaction_register_id}
AND application_id = {$application_id}
";
$db->query($query);

$query = "
DELETE FROM transaction_register
WHERE transaction_register_id = {$transaction_register_id}
AND application_id = {$application_id}
";
$db->query($query);
		
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
		

		Complete_Schedule($application_id);
		*/
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

