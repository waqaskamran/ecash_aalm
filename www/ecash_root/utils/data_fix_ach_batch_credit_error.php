<?php
/**
php data_fix_ach_batch_credit_error.php
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
$batch_ach_ids_array = array();
$batch_tr_ids_array = array();
$count = 0;
$query = "
SELECT
ap.application_id,
st.name AS status,
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
-- AND ach.ach_type='debit'
AND ach.ach_type='credit'
-- AND ap.application_id IN (901558694) -- CHANGE !!!
GROUP BY tr.transaction_register_id
HAVING Return_Date > '2014-07-31'
ORDER BY st.`name`,ap.application_id
";
$result = $db->query($query);
while ($row = $result->fetch(PDO::FETCH_OBJ))
{
	$application_id = intval($row->application_id);
	$transaction_register_id = intval($row->transaction_register_id);

	echo "App ID: ", $application_id,
	", Status: ", $row->status,
	", Transaction register id: ", $transaction_register_id,
	", Transaction Type: ", $row->transaction_type,
	"\n";
	//$batch_ach_ids_array[] = $row->ach_id;
	//$batch_tr_ids_array[] = $row->transaction_register_id;
	$sql = "
		UPDATE transaction_register
		SET transaction_status = 'complete'
		WHERE application_id = {$application_id}
		AND transaction_register_id = {$transaction_register_id}
	";
	$db->query($sql);

	$comment = new Comment();
	$comment->Add_Comment($company_id, $application_id, $agent_id,
	"Assembla 77, restored transaction status of " . $row->transaction_type . " (tr id " . $transaction_register_id . ")",
	"standard");
	
	$count++;
}
// Assembla 77, restored 2014-07-31 application status
echo "Total: ", $count, "\n";
?>

