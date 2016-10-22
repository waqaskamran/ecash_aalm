<?php
/**
php data_fix_reprocess_reschedule_failed.php
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
SELECT application_id 
FROM standby
WHERE process_type='reschedule_failed_failed'
order by date_created
";
$result = $db->query($sql);
while ($row = $result->fetch(PDO::FETCH_OBJ))
{
		$application_id = $row->application_id;
		echo "application_id: ", $application_id, "\n";

			try
			{
				$fdfap = new stdClass();
				$fdfap->application_id = $application_id;
				$fdfap->server = $server;
				$fdfa = new FailureDFA($application_id);
				$fdfa->run($fdfap);

				$comment = new Comment();
				$comment->Add_Comment($company_id, $application_id, $agent_id,
						      "Assembla 36, re-processed failed return processing", "standard"
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

