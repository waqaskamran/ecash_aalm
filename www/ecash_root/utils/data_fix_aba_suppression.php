<?php
/**
php data_fix_aba_suppression.php
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

$list_name = "MLS - ABA Suppression List";

$query =
"
SELECT list_id
FROM suppression_lists
WHERE company_id=1
AND active=1
AND name = '{$list_name}'
";
$result = $db->Query($query);
$row = $result->fetch(PDO::FETCH_OBJ);
$list_id = intval($row->list_id);
var_dump($list_id);

$query_insert =
"
INSERT INTO
suppression_list_revisions
SET
list_id = {$list_id},
date_created = NOW(),
date_modified = NOW(),
status = 'INACTIVE' -- Change to ACTIVE in the end
";
$db->Query($query_insert);

$query =
"
SELECT revision_id
FROM suppression_list_revisions
WHERE status='INACTIVE'
AND list_id = {$list_id}
ORDER BY revision_id DESC
LIMIT 1
";
$result = $db->Query($query);
$row = $result->fetch(PDO::FETCH_OBJ);
$new_revision_id = intval($row->revision_id);
var_dump($new_revision_id);

$query =
"
SELECT revision_id
FROM suppression_list_revisions
WHERE status='ACTIVE'
AND list_id = {$list_id}
ORDER BY revision_id DESC
LIMIT 1
";
$result = $db->Query($query);
$row = $result->fetch(PDO::FETCH_OBJ);
$current_active_revision_id = intval($row->revision_id);
var_dump($current_active_revision_id);

$new_values_array = array(
"011001234"
,"011103093"
,"011900254"
,"021031207"
,"021200025"
,"021200339"
,"021409169"
,"022300173"
,"031101169"
,"041000153"
,"041200775"
,"042205038"
,"051000101"
,"053000219"
,"053101121"
,"053112592"
,"053200983"
,"056073573"
,"061000227"
,"061092387"
,"061120000"
,"062000080"
,"062202985"
,"062203984"
,"063106750"
,"063107513"
,"063115194"
,"064003768"
,"071900948"
,"073972181"
,"074001019"
,"083900680"
,"084003997"
,"101106942"
,"102000021"
,"103100195"
,"103112675"
,"111000753"
,"111324196"
,"111900659"
,"111900785"
,"111906271"
,"113000861"
,"113008465"
,"113024588"
,"113122655"
,"114924742"
,"122000247"
,"124071889"
,"124085024"
,"124302529"
,"124303065"
,"124303120"
,"211070175"
,"253171621"
,"253175737"
,"253177049"
,"253184537"
,"253279031"
,"263182817"
,"264171241"
,"265377484"
,"273970116"
,"291070001"
,"291471024"
,"301081508"
,"311989331"
,"312081089"
,"313083646"
);

foreach ($new_values_array as $value)
{
//echo $value, "\n";

$query =
"
SELECT value_id
FROM suppression_list_values
WHERE value = '{$value}'
";
$result = $db->Query($query);
$row = $result->fetch(PDO::FETCH_OBJ);
$value_id = intval($row->value_id);

if (empty($value_id))
{
//insert value and suppression_list_revision_values
echo $value, " does NOT exist", "\n";

$query_insert_value =
"
INSERT INTO
suppression_list_values
SET
value = '{$value}',
date_created = NOW()
";
$db->Query($query_insert_value);

$query_find_new_value_id =
"
SELECT value_id
FROM suppression_list_values
WHERE value = '{$value}'
";
$result = $db->Query($query_find_new_value_id);
$row = $result->fetch(PDO::FETCH_OBJ);
$value_id = intval($row->value_id);

$query_insert_srv =
"
INSERT IGNORE INTO
suppression_list_revision_values
SET
list_id = {$list_id},
revision_id = {$new_revision_id},
value_id = {$value_id}
";
$db->Query($query_insert_srv);
}
else
{
//insert only suppression_list_revision_values
echo $value, " exists, value_id: ", $value_id, "\n";
$query_insert_srv =
"
INSERT IGNORE INTO
suppression_list_revision_values
SET
list_id = {$list_id},
revision_id = {$new_revision_id},
value_id = {$value_id}
";
$db->Query($query_insert_srv);
}

}

// UPDATE status
$query =
"
UPDATE suppression_list_revisions
SET status='INACTIVE'
WHERE revision_id = {$current_active_revision_id}
";
$db->Query($query);

$query =
"
UPDATE suppression_list_revisions
SET status='ACTIVE'
WHERE revision_id = {$new_revision_id}
";
$db->Query($query);

?>
