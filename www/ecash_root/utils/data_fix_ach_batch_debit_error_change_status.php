<?php
/**
php data_fix_ach_batch_debit_error_change_status.php
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

echo "Started...\n";

//////////////////////////////////////////////////////////////2013-08-01 ACH BATCH
$apps_array = array();

$count = 0;
$query = "
SELECT
ap.application_id,
st.name AS status,
(
-- SELECT st1.name
SELECT sh1.application_status_id
FROM status_history AS sh1
JOIN application_status AS st1 ON (st1.application_status_id = sh1.application_status_id)
WHERE sh1.application_id = ap.application_id
AND sh1.date_created < '2014-07-31'
ORDER BY sh1.status_history_id DESC
LIMIT 1
) AS prev_status,
ap.date_application_status_set,
tr.transaction_register_id,
tt.name AS transaction_type,
tr.transaction_status,
tr.date_modified,
ach.ach_status,
IF (tr.transaction_status = 'failed',
(SELECT th1.date_created
FROM transaction_history AS th1
WHERE th1.application_id = ap.application_id
AND th1.transaction_register_id = tr.transaction_register_id
AND th1.status_after = 'failed'
ORDER BY th1.transaction_history_id DESC LIMIT 1)
, NULL
) AS Return_Date
FROM application AS ap
JOIN application_status AS st ON (st.application_status_id = ap.application_status_id)
JOIN ach AS ach ON (ach.application_id = ap.application_id)
JOIN ach_batch AS ab ON (ab.ach_batch_id=ach.ach_batch_id)
JOIN transaction_register AS tr ON (tr.ach_id=ach.ach_id)
JOIN transaction_type AS tt ON (tt.transaction_type_id = tr.transaction_type_id
AND tt.company_id=1
)
WHERE
DATE(ab.date_created) = '2013-07-31'
AND tr.transaction_status = 'failed'
AND ach.ach_type='debit'
AND st.name = 'Second Tier (Pending)'
-- AND ap.application_id IN (900897022)
GROUP BY tr.transaction_register_id
-- HAVING (Return_Date > '2014-07-31' AND prev_status LIKE 'Inactive%')
HAVING (Return_Date > '2014-07-31' AND prev_status IN (109,113,158,162))
ORDER BY st.`name`,ap.application_id
";
$result = $db->query($query);
while ($row = $result->fetch(PDO::FETCH_OBJ))
{
	$application_id = intval($row->application_id);
	$transaction_register_id = intval($row->transaction_register_id);

	$status_chain = Status_Utility::Get_Status_Chain_By_ID($row->prev_status);

	echo "App ID: ", $application_id,
	", Status: ", $row->status,
	", Prev Status: ", $row->prev_status,
	", Prev Status Chain: ", $status_chain,
	", Transaction register id: ", $transaction_register_id,
	", Transaction Type: ", $row->transaction_type,
	"\n";
	
	$sql = "
		UPDATE transaction_register
		SET transaction_status = 'complete'
		WHERE application_id = {$application_id}
		AND transaction_register_id = {$transaction_register_id}
	";
	$db->query($sql);

	if (!in_array($application_id, $apps_array))
	{
		$apps_array[] = $application_id;
	}

	$comment = new Comment();
	$comment->Add_Comment($company_id, $application_id, $agent_id,
	"Assembla 77, restored transaction status of " . $row->transaction_type . " (tr id " . $transaction_register_id . ")",
	"standard");

	Update_Status(NULL,$application_id,$status_chain);
	
	$count++;
}

echo "Total: ", $count, "\n";

foreach ($apps_array as $application_id)
{
	$comment = new Comment();
	$comment->Add_Comment($company_id, $application_id, $agent_id,
	"Assembla 77, restored 2014-07-31 application status",
	"standard");

	$app =  ECash::getApplicationByID($application_id);
	$flags = $app->getFlags();

	$flag = "has_fatal_ach_failure";
	$flags->clear($flag);

	$flag = "had_fatal_ach_failure";
	$flags->clear($flag);
}

?>

