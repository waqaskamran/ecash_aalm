<?php
/**
php data_fix_bus_rule_company_level_simple.php
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
/*
$sql = "
select rs.rule_set_id
from rule_set rs
join loan_type lt on (rs.loan_type_id=lt.loan_type_id)
where lt.company_id = {$company_id}
and lt.name='Company Level'
";
$result = $db->query($sql);
$row = $result->fetch(PDO::FETCH_OBJ);
$rule_set_id = intval($row->rule_set_id);
echo "rule_set_id: ", $rule_set_id, "\n";

$sql =
"
insert ignore into rule_component
(
date_modified,
date_created,
active_status,
name,
name_short,
grandfathering_enabled
)
values
(
now(),
now(),
'active',
'Email Reminder Interval',
'email_reminder_interval',
'no'
)
";
$db->query($sql);

$sql = "
select rule_component_id
from rule_component
where name_short='email_reminder_interval'
";
$result = $db->query($sql);
$row = $result->fetch(PDO::FETCH_OBJ);
$rule_component_id = intval($row->rule_component_id);
echo "rule_component_id: ", $rule_component_id, "\n";

$sql = "
select sequence_no
from rule_set_component
where rule_set_id = {$rule_set_id}
order by sequence_no desc
limit 1
";
$result = $db->query($sql);
$row = $result->fetch(PDO::FETCH_OBJ);
$sequence_no = intval($row->sequence_no);
$sequence_no = $sequence_no + 1;
echo "sequence_no: ", $sequence_no, "\n";

$sql =
"
insert ignore into rule_set_component
SET
date_modified = NOW(),
date_created = NOW(),
active_status = 'active',
rule_set_id = {$rule_set_id},
rule_component_id = {$rule_component_id},
sequence_no = {$sequence_no}
";
$db->query($sql);

/////////////////////////////////////////////////rule_component_parm
$rule_component_parm_array = array(
"email_reminder_interval_days" => "Email Reminder Interval",
);
$sequence_no = 1;
foreach ($rule_component_parm_array as $parm_name => $display_name)
{
$sql =
"
insert into rule_component_parm
SET
date_modified = NOW(),
date_created = NOW(),
active_status = 'active',
rule_component_id = {$rule_component_id},
parm_name = '{$parm_name}',
sequence_no = {$sequence_no},
display_name = '{$display_name}',
description = 'This rule determines Email Reminder Interval',
parm_type = 'integer',
user_configurable = 'yes',
input_type = 'select',
presentation_type = 'scalar',
value_label = 'Business Days',
value_min = 0,
value_max = 30,
value_increment = 1,
length_min=1,
length_max=2
";
$db->query($sql);

$sql =
"
select rule_component_parm_id
from rule_component_parm
where rule_component_id = {$rule_component_id}
and parm_name= '{$parm_name}'
";
$result = $db->query($sql);
$row = $result->fetch(PDO::FETCH_OBJ);
$rule_component_parm_id = intval($row->rule_component_parm_id);
echo "rule_component_parm_id: ", $rule_component_parm_id, "\n";

$sql =
"
insert into rule_set_component_parm_value
SET
date_modified = NOW(),
date_created = NOW(),
agent_id = 1,
rule_set_id = {$rule_set_id},
rule_component_id = {$rule_component_id},
rule_component_parm_id = {$rule_component_parm_id},
parm_value = 2
";
$db->query($sql);

$sequence_no++;
}
*/
?>
