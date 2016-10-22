<?php
/**
php data_fix_active_with_final_balance.php
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
SELECT
ap.application_id,
SUM(ea.amount) AS balance
FROM application AS ap
JOIN application_status AS st USING (application_status_id)
JOIN event_schedule AS es ON (es.company_id = ap.company_id
AND es.application_id = ap.application_id)
JOIN event_amount AS ea ON (ea.company_id = ap.company_id
AND ea.application_id = ap.application_id
AND ea.event_schedule_id = es.event_schedule_id)
JOIN event_amount_type AS eat ON (eat.event_amount_type_id = ea.event_amount_type_id
AND eat.name_short <> 'irrecoverable')
LEFT JOIN transaction_register AS tr ON (tr.company_id = ap.company_id
AND tr.application_id = ap.application_id
AND tr.event_schedule_id = es.event_schedule_id
AND tr.transaction_status = 'failed')
WHERE ap.company_id = {$company_id}
AND ap.application_status_id IN (20,123,137,138)
AND tr.transaction_register_id IS NULL
GROUP BY application_id
HAVING balance > 0
";
$result = $db->query($sql);
while ($row = $result->fetch(PDO::FETCH_OBJ))
{
$application_id = $row->application_id;
try
{
echo $application_id, " ", $row->name, "\n";

Complete_Schedule($application_id);

$comment = new Comment();
$comment->Add_Comment($company_id, $application_id, $agent_id,
"Completed Schedule", "standard");
	
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

