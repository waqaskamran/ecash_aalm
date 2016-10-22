<?php
/**
php data_fix_bus_rule_delete.php
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

$sql = "
select rule_component_id
from rule_component
where name_short='email_reminder_interval'
";
$result = $db->query($sql);
$row = $result->fetch(PDO::FETCH_OBJ);
$rule_component_id = intval($row->rule_component_id);
echo "rule_component_id: ", $rule_component_id, "\n";

$query =
"
delete from rule_set_component_parm_value
where rule_component_id = {$rule_component_id}
";
$db->query($query);

$query =
"
delete from rule_component_parm
where rule_component_id = {$rule_component_id}
";
$db->query($query);

$query =
"
delete from rule_set_component
where rule_component_id = {$rule_component_id}
";
$db->query($query);

$query =
"
delete from rule_component
where rule_component_id = {$rule_component_id}
";
$db->query($query);

?>
