<?php
/**
php data_fix_ach_providers_aba.php
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

$ach_provider_id = 1;

$new_values_array = array(

);

foreach ($new_values_array as $value)
{
//echo $value, "\n";

$query =
"
SELECT ach_provider_bank_aba_id
FROM ach_provider_bank_aba
WHERE active_status = 'active'
AND ach_provider_id = {$ach_provider_id}
AND bank_aba = '{$value}'
";
$result = $db->Query($query);
$row = $result->fetch(PDO::FETCH_OBJ);
$ach_provider_bank_aba_id = intval($row->ach_provider_bank_aba_id);

if (empty($ach_provider_bank_aba_id))
{
//insert value and suppression_list_revision_values
echo $value, " does NOT exist", "\n";

$query_insert_value =
"
INSERT IGNORE INTO
ach_provider_bank_aba
SET
date_modified = NOW(),
date_created = NOW(),
ach_provider_id = {$ach_provider_id},
bank_aba = '{$value}',
active_status = 'active',
agent_id={$agent_id}
";
$db->Query($query_insert_value);
}
}

?>
