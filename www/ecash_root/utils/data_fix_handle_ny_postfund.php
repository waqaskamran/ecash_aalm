<?php
/**
php data_fix_handle_ny_postfund.php
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
ap.application_id, 
aps.level0_name,
ap.application_status_id
FROM application AS ap
JOIN application_status_flat AS aps USING (application_status_id)
WHERE state='ny'
AND 
(
aps.level1 NOT IN ('prospect','applicant')
AND aps.level2 NOT IN ('prospect','applicant')
)
AND ap.application_status_id NOT IN (109,113,162,158,111,112,131,121,124,190)
";
$result = $db->query($sql);
while ($row = $result->fetch(PDO::FETCH_OBJ))
{
		$application_id = $row->application_id;
		echo "application_id: ", $application_id, "\n";

			try
			{
				Remove_Unregistered_Events_From_Schedule($application_id);

				Update_Status(NULL, $application_id, 'collections_rework::collections::customer::*root');

				$qm = ECash::getFactory()->getQueueManager();
				$queue_item = $qm->getQueue('collections_rework')->getNewQueueItem($application_id);
				$qm->moveToQueue($queue_item, 'collections_rework');

				$app = 	ECash::getApplicationByID($application_id);
				$flags = $app->getFlags();
				if(!$flags->get('has_fatal_ach_failure'))
				{
					$flags->set('has_fatal_ach_failure');
				}

				$comment = new Comment();
				$comment->Add_Comment($company_id, $application_id, $agent_id,
						      "Assembla 36, Handled NY postfund customers", "standard"
				);
			}
			catch(Exception $e)
			{
				echo $e, "\n";
				$not_reprocessed[] = $application_id;
				continue;
			}
}

var_dump($not_reprocessed);

?>

