<?php
/**
php data_fix_dequeue_inactive_recovered.php
*/
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(COMMON_LIB_DIR."mysqli.1.php");


$db_ldb = ECash::getMasterDb();
$db_chasm = ECash::getAppSvcDB();

$company_id = 1;
$agent_id = 1;
$server = ECash::getServer();
$server->company_id = $company_id;

$counter = 0;

$query_chasm = "
SELECT
CONVERT(VARCHAR(19), CONVERT(DATETIME,ap.date_created), 121) AS date_created,
CONVERT(VARCHAR(19), CONVERT(DATETIME,ap.date_modified), 121) AS date_modified,
ap.authoritative_id AS application_id,
st.application_status_name,
CONVERT(VARCHAR(19), CONVERT(DATETIME,ap.date_application_status_set), 121) AS date_application_status_set,
CONVERT(VARCHAR(10), CONVERT(DATETIME,ap.date_fund_actual), 121) AS date_fund_actual
FROM application AS ap
JOIN application_status AS st ON (st.application_status_id=ap.application_status_id)
WHERE  st.application_status_name IN (
'recovered::external_collections::*root'
-- ,'internal_recovered::external_collections::*root'
)
-- AND date_application_status_set > '2013-07-27'
-- AND ap.authoritative_id = 700002258
ORDER BY st.application_status_name,ap.date_application_status_set
";

//echo $query_chasm, "\n";
$result_chasm = $db_chasm->query($query_chasm);
while ($row_chasm = $result_chasm->fetch(DB_IStatement_1::FETCH_OBJ))
{
$application_id = $row_chasm->application_id;

$query_ldb_check = "
SELECT application_id
FROM application
WHERE application_id={$application_id}
";
$result_ldb_check = $db_ldb->query($query_ldb_check);
$row_ldb_check = $result_ldb_check->fetch(PDO::FETCH_OBJ);
if (empty($row_ldb_check->application_id)) continue;

$query_ldb_check = "
SELECT tq.related_id, nq.name_short
FROM n_time_sensitive_queue_entry AS tq
JOIN n_queue AS nq ON (nq.queue_id=tq.queue_id)
WHERE tq.related_id={$application_id}
AND nq.name_short NOT IN ('customer_service')
";
$result_ldb_check = $db_ldb->query($query_ldb_check);
$row_ldb_check = $result_ldb_check->fetch(PDO::FETCH_OBJ);
if (empty($row_ldb_check->related_id)) continue;

++ $counter;

echo 
$application_id, ", ",
$row_ldb_check->name_short, ", ",
//$row_chasm->application_status_name,
"\n";

//$qm = ECash::getFactory()->getQueueManager();
//$queue_item = new ECash_Queues_BasicQueueItem($application_id);
//$qm->removeFromAllQueues($queue_item);
}

echo "Total: ", $counter, "\n";
?>
