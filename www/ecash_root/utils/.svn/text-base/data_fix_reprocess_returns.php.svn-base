<?php
/**
php data_fix_reprocess_returns.php

DELETE DFA in utils dir

CHECK FORMAT
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

$app_es_array = array();
$es_array = array();

echo "Started...\n";

$sql = "
SELECT DISTINCT ach.ach_id
FROM ach AS ach
JOIN ach_return_code AS arc USING (ach_return_code_id)
JOIN transaction_register AS tr USING (ach_id)
JOIN transaction_history AS th USING (transaction_register_id)
WHERE 
arc.name_short = 'R99'
AND tr.transaction_status = 'failed'
AND th.status_after = 'failed'
AND th.date_created > '2013-09-23 00:00:00'
-- AND ach.ach_id=3623
ORDER BY ach.ach_id ASC
";
$result = $db->query($sql);
while ($row = $result->fetch(PDO::FETCH_OBJ))
{
	$ach_id = $row->ach_id;
	
	$sql1 = "
		SELECT application_id,event_schedule_id,transaction_status
		FROM transaction_register
		WHERE ach_id = {$ach_id}
	";
	$result1 = $db->query($sql1);
	$row1 = $result1->fetch(PDO::FETCH_OBJ);
	if (!empty($row1))
	{
		$transaction_status = $row1->transaction_status;
		$application_id = $row1->application_id;
		$event_schedule_id = $row1->event_schedule_id;
		echo "ach_id: ", $ach_id, ", application_id: ", $application_id, ", event_schedule_id:", $event_schedule_id,  "\n";

		if (!in_array($event_schedule_id, $es_array))
		{
			$es_array[] = $event_schedule_id;
			$app_es_array[$event_schedule_id] = $application_id;
		}
								
	}
}
	//var_dump($app_es_array);

	if (count($app_es_array) > 0)
	{
		ksort($app_es_array);
		foreach ($app_es_array as $event_schedule_id => $application_id)
		{
			echo $event_schedule_id, ", ", $application_id, "\n";
			try
			{
				Record_Event_Failure($application_id, $event_schedule_id);
			
				$fdfap = new stdClass();
				$fdfap->application_id = $application_id;
				$fdfap->server = $server;
				$fdfa = new FailureDFA($application_id);
				$fdfa->run($fdfap);

				$comment = new Comment();
				$comment->Add_Comment($company_id, $application_id, $agent_id,
						      "Assembla 36, Re-processed return R99 of event id " . $event_schedule_id, "standard"
				);
			}
			catch(Exception $e)
			{
				echo $e, "\n";
				continue;
			}
		}
	}
?>

