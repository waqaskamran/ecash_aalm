<?php
/**
php data_fix_sort_documents.php
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

$name_order_array = array();
$order = 1;

$sql = "
SELECT name_short, document_list_id, doc_send_order
FROM document_list
WHERE company_id=1
AND active_status='active'
AND system_id=3
ORDER BY name_short ASC;
";
$result = $db->query($sql);
while ($row = $result->fetch(PDO::FETCH_OBJ))
{
	echo $row->name_short, ' ', $row->document_list_id, ' ', $row->doc_send_order, "\n";
	$name_order_array[$row->document_list_id] = $order;
	++ $order;
}

asort($name_order_array);

foreach($name_order_array as $document_list_id => $order)
{
	echo $document_list_id, ' ', $order, "\n";

	$sql = "
	UPDATE document_list
	SET doc_send_order = {$order}
	WHERE document_list_id = {$document_list_id}
	";
	$db->query($sql);
}

//var_dump($name_order_array);
?>

