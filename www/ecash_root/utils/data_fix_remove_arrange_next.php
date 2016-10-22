<?php
/**
php data_fix_remove_schedule.php
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
//$app_array = array();

$not_reprocessed = array();

echo "Started...\n";
$sql = "
SELECT DISTINCT ap.application_id, es.event_schedule_id, st.name
FROM application AS ap
JOIN application_status AS st ON (st.application_status_id=ap.application_status_id)
JOIN event_schedule AS es ON (es.application_id=ap.application_id)
WHERE es.context IN ('arrange_next')
AND es.event_status = 'scheduled'
AND ap.application_status_id IN (20,123,137,138)
ORDER BY st.name
";
$result = $db->query($sql);
while ($row = $result->fetch(PDO::FETCH_OBJ))
{
			$application_id = $row->application_id;
			$event_schedule_id = $row->event_schedule_id;
			try
			{
				echo $application_id, " ", $event_schedule_id, " ", $row->name, "\n";
				/*								
				$sql1 = "
						DELETE FROM event_amount
						WHERE event_schedule_id = {$event_schedule_id}
						AND application_id = {$application_id}
				";
				$db->query($sql1);
				
				$sql2 = "
						DELETE FROM event_schedule
						WHERE event_schedule_id = {$event_schedule_id}
						AND application_id = {$application_id}
				";
				$db->query($sql2);
				
				Complete_Schedule($application_id);
				
				$comment = new Comment();
				$comment->Add_Comment($company_id, $application_id, $agent_id,
						      "Per Jared: remove scheduled arrange_next.", "standard"
				);
				*/
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

