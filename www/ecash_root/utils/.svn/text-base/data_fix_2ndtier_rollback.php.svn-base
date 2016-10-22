<?php
/**
php data_fix_2ndtier_rollback.php
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

//SET ID !!!
$ext_collections_batch_id=;

$sql = "
SELECT ec.application_id,a.application_status_id 
FROM application AS a
JOIN ext_collections AS ec USING (application_id)
WHERE ec.ext_collections_batch_id={$ext_collections_batch_id}
AND a.application_status_id = 112
";
$result = $db->query($sql);
while ($row = $result->fetch(PDO::FETCH_OBJ))
{
		$application_id = $row->application_id;
		echo "application_id: ", $application_id, "\n";
			
			try
			{
				Update_Status(NULL, $application_id, "pending::external_collections::*root");
				
				$comment = new Comment();
				$comment->Add_Comment($company_id, $application_id, $agent_id, "rolled back ext_collections_batch_id " . $ext_collections_batch_id, "standard");
			}
			catch(Exception $e)
			{
				echo $e, "\n";
				$not_reprocessed[] = $application_id;
				continue;
			}
			
}


var_dump($not_reprocessed);

$sql = "
DELETE
FROM ext_collections
WHERE ext_collections_batch_id={$ext_collections_batch_id}
";
$db->query($sql);

$sql = "
DELETE
FROM ext_collections_batch
WHERE ext_collections_batch_id={$ext_collections_batch_id}
";
$db->query($sql);

?>

