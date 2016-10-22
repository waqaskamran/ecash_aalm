<?php
/**
php data_fix_ach_pending.php
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

$not_reprocessed = array();

echo "Started...\n";

$sql = "
SELECT 
tr.application_id,
tr.event_schedule_id,
tr.transaction_register_id,
tr.date_effective,
st.name,
ach.ach_id,
ach.ach_status,
ab.ach_file_outbound

FROM application AS ap
JOIN application_status AS st ON (st.application_status_id = ap.application_status_id)
JOIN transaction_register AS tr ON (tr.application_id=ap.application_id)
JOIN transaction_type AS tt ON (tt.transaction_type_id = tr.transaction_type_id
AND tt.company_id=1
)
JOIN ach AS ach ON (ach.ach_id = tr.ach_id)
JOIN ach_batch AS ab ON (ab.ach_batch_id=ach.ach_batch_id)
WHERE tr.transaction_status='pending'
AND tt.clearing_type='ach'
AND ach.ach_status <> 'batched'
AND tr.date_effective < DATE_SUB(CURDATE(), INTERVAL 3 DAY)
-- and tr.ach_id=647798
ORDER BY tr.date_effective
";

$result = $db->query($sql);
while ($row = $result->fetch(PDO::FETCH_OBJ))
{
	$application_id = $row->application_id;
	$event_schedule_id = $row->event_schedule_id;
	$transaction_register_id = $row->transaction_register_id;
	$ach_id = $row->ach_id;

	$batch_array = array();
	$batch_array = explode("\n", $row->ach_file_outbound);
	$ach_array_from_batch = array();
	foreach ($batch_array as $batch_record)
	{
        	$batch_record_array = explode(",", $batch_record);
		//if ($ach_id == $batch_record_array[19]) var_dump($batch_record_array);
	        $ach_array_from_batch[] = $batch_record_array[19];
	}

	//var_dump($ach_array_from_batch);
	
	//var_dump($row->ach_status);

	try
	{
		if ($row->ach_status == "returned")
		{
			echo $application_id, " ", $ach_id, ": Returned", "\n";

			Record_Event_Failure($application_id, $event_schedule_id);
					
			$fdfap = new stdClass();
			$fdfap->application_id = $application_id;
			$fdfap->server = $server;
			$fdfa = new FailureDFA($application_id);
			$fdfa->run($fdfap);

			$comment = new Comment();
			$comment->Add_Comment($company_id, $application_id, $agent_id,
			"Per Jared, modified old ach pending transaction_register_id" . $transaction_register_id, "standard");
		}
		else if (in_array($ach_id, $ach_array_from_batch))
		{
			echo $application_id, " ", $ach_id, ": Found", "\n";

			$sql1 = "
			UPDATE transaction_register
			SET transaction_status = 'complete'
			WHERE transaction_register_id = {$transaction_register_id}
			AND application_id = {$application_id}
			";
			$db->query($sql1);

			$comment = new Comment();
			$comment->Add_Comment($company_id, $application_id, $agent_id,
			"Per Jared, modified old ach pending transaction_register_id" . $transaction_register_id, "standard");

			Complete_Schedule($application_id);
			Check_Inactive($application_id);
		}
		else
		{
			echo $application_id, " ", $ach_id, ": Not Found", "\n";

			$sql2 = "
			DELETE FROM transaction_history
			WHERE transaction_register_id = {$transaction_register_id}
			AND application_id = {$application_id}
			";
			$db->query($sql2);

			$sql2 = "
			DELETE FROM transaction_ledger
			WHERE transaction_register_id = {$transaction_register_id}
			AND application_id = {$application_id}
			";
			$db->query($sql2);

			$sql2 = "
			DELETE FROM transaction_register
			WHERE transaction_register_id = {$transaction_register_id}
			AND application_id = {$application_id}
			";
			$db->query($sql2);

			$sql2 = "
			DELETE FROM amount_schedule
			WHERE event_schedule_id = {$event_schedule_id}
			AND application_id = {$application_id}
			";
			$db->query($sql2);

			$sql2 = "
			DELETE FROM event_schedule
			WHERE event_schedule_id = {$event_schedule_id}
			AND application_id = {$application_id}
			";
			$db->query($sql2);

			Complete_Schedule($application_id);
		}
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

