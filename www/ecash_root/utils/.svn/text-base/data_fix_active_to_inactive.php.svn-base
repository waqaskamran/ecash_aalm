<?php
/**
php data_fix_active_to_inactive.php
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
'active::servicing::customer::*root'
,'current::arrangements::collections::customer::*root'
-- ,'past_due::servicing::customer::*root'
-- ,'new::collections::customer::*root'
-- ,'queued::contact::collections::customer::*root'
-- ,'dequeued::contact::collections::customer::*root'
-- ,'collections_rework::collections::customer::*root'
-- 'pending::external_collections::*root'
-- ,'sent::external_collections::*root'
-- 'paid::customer::*root'
-- ,'recovered::external_collections::*root'
-- ,'internal_recovered::external_collections::*root'
-- ,'settled::customer::*root'
)
-- AND date_application_status_set > '2013-07-27'
-- AND ap.authoritative_id = 700002258
ORDER BY st.application_status_name,ap.date_application_status_set
";

//echo $query_chasm, "\n";
$result_chasm = $db_chasm->query($query_chasm);
while ($row_chasm = $result_chasm->fetch(DB_IStatement_1::FETCH_OBJ))
{
$date_created = $row_chasm->date_created;
$date_modified = $row_chasm->date_modified;
$application_id = $row_chasm->application_id;
$date_application_status_set = $row_chasm->date_application_status_set;
$application_status_name = $row_chasm->application_status_name;

$query_ldb_check = "
SELECT application_id
FROM application
WHERE application_id={$application_id}
";
$result_ldb_check = $db_ldb->query($query_ldb_check);
$row_ldb_check = $result_ldb_check->fetch(PDO::FETCH_OBJ);
if (empty($row_ldb_check->application_id)) continue;

/*
$schedule = Fetch_Schedule($application_id);
$status = Analyze_Schedule($schedule,false);
//if ($status->posted_and_pending_total >= 0) continue;
if ($status->posted_total >= 0) continue;
*/

$query_ldb_check = "
SELECT a.application_id,
(SELECT SUM(ea1.amount)
FROM transaction_register AS tr1
JOIN event_amount ea1 USING (application_id, event_schedule_id, transaction_register_id)
JOIN event_amount_type eat1 USING (event_amount_type_id)
WHERE ea1.application_id = a.application_id
AND eat1.name_short <> 'irrecoverable'
-- AND tr1.transaction_status <> 'failed') AS balance
AND tr1.transaction_status = 'complete') AS balance
FROM application AS a
WHERE
a.application_id={$application_id}
GROUP BY a.application_id
HAVING balance <= 0
";
//$result_ldb_check = $db_ldb->query($query_ldb_check);
//$row_ldb_check = $result_ldb_check->fetch(PDO::FETCH_OBJ);
//if (empty($row_ldb_check->application_id)) continue;

$result_ldb_check = $db_ldb->query($query_ldb_check);
while ($row_ldb_check = $result_ldb_check->fetch(PDO::FETCH_OBJ))
{
++ $counter;
$balance = $row_ldb_check->balance;

echo 
//$date_created, ", ",
//$date_modified, ", ",
$application_id, ", ",
$application_status_name, ", ",
//$date_application_status_set, ", ",
$balance, ", ",
"\n";

Update_Status(NULL, $application_id, "paid::customer::*root");
}

}

echo "Total: ", $counter, "\n";
?>
