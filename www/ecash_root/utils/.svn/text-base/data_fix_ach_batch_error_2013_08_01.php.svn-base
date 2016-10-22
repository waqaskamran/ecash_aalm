<?php
/**
php data_fix_ach_batch_error_2013_08_01.php
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

$query = "
SELECT
ap.application_id,
st.`name`,
ap.date_application_status_set,
tr.transaction_register_id,
tt.`name`,
tr.transaction_status,
tr.date_modified,
ach.ach_status,
ach.ach_id,
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
DATE(ab.date_created) = '2013-08-01'
-- ab.ach_batch_id=1419
-- AND tr.transaction_status = 'failed'
-- AND ach.ach_status <> 'batched' -- returned and processed
-- AND tr.date_modified > '2014-07-31'
GROUP BY tr.transaction_register_id
-- HAVING Return_Date > '2014-07-31'
ORDER BY ap.date_application_status_set,ap.application_id
";
$result = $db->query($query);
while ($row = $result->fetch(PDO::FETCH_OBJ))
{
	//echo "", "\n";
	$batch_ach_ids_array[] = $row->ach_id;
	$batch_tr_ids_array[] = $row->transaction_register_id;
}

//////////////////////////////////////////////////////////////2014-07-31 ACH BATCH
$batch_14_ach_ids_array = array();
$batch_14_tr_ids_array = array();

$query = "
SELECT
ap.application_id,
st.`name`,
ap.date_application_status_set,
tr.transaction_register_id,
tt.`name`,
tr.transaction_status,
tr.date_modified,
ach.ach_status,
ach.ach_id,
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
DATE(ab.date_created) = '2014-07-31'
-- AND tr.transaction_status = 'failed'
-- AND ach.ach_status <> 'batched' -- returned and processed
-- AND tr.date_modified > '2014-07-31'
GROUP BY tr.transaction_register_id
-- HAVING Return_Date > '2014-07-31'
ORDER BY ap.date_application_status_set,ap.application_id
";
$result = $db->query($query);
while ($row = $result->fetch(PDO::FETCH_OBJ))
{
	$batch_14_ach_ids_array[] = $row->ach_id;
	$batch_14_tr_ids_array[] = $row->transaction_register_id;
}

//////////////////////////////////////////////////////////////RETURNS > 2014-07-31
$returns_ach_ids_array = array();
$returns_tr_ids_array = array();

$sql = "
SELECT ach_report_id,remote_response
FROM ach_report
WHERE
date_request > '2014-07-31'
-- report_status='received'
-- AND ach_report_id IN (1781)
ORDER BY ach_report_id ASC
-- LIMIT 1
";
$result = $db->query($sql);
while ($row = $result->fetch(PDO::FETCH_OBJ))
{
	$app_es_array = array();
	$es_array = array();

	$ach_report_id = $row->ach_report_id;
	$return_array = array();
	$return_array = explode("\n", $row->remote_response);
	//var_dump($return_array);
	if (count($return_array) > 0)
	{
		//echo count($return_array), " records in the report", "\n";
		foreach ($return_array as $return_record)
		{
			$record_array = array();
			$record_array = explode(",", $return_record);
			$ach_id = str_replace('"', '', $record_array[3]);
			$returns_ach_ids_array[] = $ach_id;
			$ach_status = strtolower(str_replace('"', '', $record_array[14]));
			$returned_code = str_replace('"', '', $record_array[15]);
			
			$ach_id = intval($ach_id);
			$sql1 = "
				SELECT transaction_register_id
				FROM transaction_register
				WHERE ach_id = {$ach_id}
			";
			$result1 = $db->query($sql1);
			while ($row1 = $result1->fetch(PDO::FETCH_OBJ))
			{
				$returns_tr_ids_array[] = $row1->transaction_register_id;
			}
		}
	}
}

$count_batch_ach_ids_array = count($batch_ach_ids_array);
$count_batch_tr_ids_array = count($batch_tr_ids_array);

$count_batch_14_ach_ids_array = count($batch_14_ach_ids_array);
$count_batch_14_tr_ids_array = count($batch_14_tr_ids_array);

$count_returns_ach_ids_array = count($returns_ach_ids_array);
$count_returns_tr_ids_array = count($returns_tr_ids_array);

echo "count_batch_ach_ids_array: ", $count_batch_ach_ids_array, "\n";
echo "count_batch_tr_ids_array: ", $count_batch_tr_ids_array, "\n";

echo "count_batch_14_ach_ids_array: ", $count_batch_14_ach_ids_array, "\n";
echo "count_batch_14_tr_ids_array: ", $count_batch_14_tr_ids_array, "\n";

echo "count_returns_ach_ids_array: ", $count_returns_ach_ids_array, "\n";
echo "count_returns_tr_ids_array: ", $count_returns_tr_ids_array, "\n";

$intersect = array_intersect($batch_ach_ids_array, $returns_ach_ids_array);
var_dump($intersect);

//var_dump($batch_ach_ids_array);
//var_dump($batch_14_ach_ids_array);
//var_dump($returns_ach_ids_array);

// test
//$a = array(1,2,3,4);
//$b = array(4,5,6,7,8);
//var_dump(array_intersect($a,$b));
$intersect_14 = array_intersect($batch_ach_ids_array, $batch_14_ach_ids_array);
var_dump($intersect_14);

//$intersect14_returns = array_intersect($batch_14_ach_ids_array, $returns_ach_ids_array);
//var_dump($intersect14_returns);

$intersect = array_intersect($batch_tr_ids_array, $returns_tr_ids_array);
var_dump($intersect);

$intersect_14 = array_intersect($batch_tr_ids_array, $batch_14_tr_ids_array);
var_dump($intersect_14);

?>

