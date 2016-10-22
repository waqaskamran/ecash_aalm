<?php
/**
php compare_aalm-ldb_personal_reference_data.php
*/
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(COMMON_LIB_DIR."mysqli.1.php");

echo "Database Comparison Test:  personal_reference\n";

$unset_ary = array();

$time_window = 300;

$key_ignore_list = array(
    "personal_reference_id"=>true
);

$db_ldb = ECash::getMasterDb();
$db_chasm = ECash::getAppSvcDB();
$counter = 0;

$query_chasm = "
SELECT
    ap.authoritative_id AS application_id,
    pr.personal_reference_id AS personal_reference_id,
    pr.date_created AS date_created,
    pr.date_modified AS date_modified,
    pr.company_id AS company_id,
    pr.name_full AS name_full,
    pr.phone_home AS phone_home,
    pr.relationship AS relationship,
    lv.value AS reference_verified,
    lc.value AS contact_pref,
    pr.modifying_agent_id AS agent_id
FROM application AS ap
    JOIN personal_reference AS pr ON (ap.application_id = pr.application_id)
    JOIN lookup_verified AS lv ON (pr.verified = lv.id)
    JOIN lookup_ok_to_contact AS lc ON (pr.ok_to_contact = lc.id)
    ORDER BY ap.authoritative_id,pr.name_full
";

//echo $query_chasm_ap, "\n";
$result_chasm = $db_chasm->query($query_chasm);
$rows_chasm = $result_chasm->fetchAll(DB_IStatement_1::FETCH_OBJ);

$query_ldb = "
SELECT
    pr.application_id AS application_id,
    pr.personal_reference_id AS personal_reference_id,
    pr.date_created AS date_created,
    pr.date_modified AS date_modified,
    pr.company_id AS company_id,
    pr.name_full AS name_full,
    pr.phone_home AS phone_home,
    pr.relationship AS relationship,
    pr.reference_verified AS reference_verified,
    pr.contact_pref AS contact_pref,
    pr.agent_id AS agent_id
FROM personal_reference AS pr 
    ORDER BY pr.application_id,pr.name_full
";
$result_ldb = $db_ldb->query($query_ldb);
$rows_ldb = $result_ldb->fetchAll(PDO::FETCH_OBJ);

$chasm_row = $rows_chasm[1];
$chasm_keys = get_object_vars($chasm_row);
echo "\n\nChasm Keys:\n";
print_r(array_keys($chasm_keys));

$ldb_row = $rows_ldb[1];
$ldb_keys = get_object_vars($ldb_row);
echo "\n\nLDB Keys:\n";
print_r(array_keys($ldb_keys));

echo "\n\nChasm keys found in LDB test:";
$chasm_lookup_array = array();
$ldb_lookup_array = array();
$initial_test_array = array();
$key_test_array = array();
$not_one_fail = true;
foreach (array_keys($chasm_keys) as $key){
    $initial_test_array[$key] = false;
    $key_test_array[$key] = true;
    if (!array_key_exists($key,$ldb_keys)) {
        echo "\n  missing chasm personal_reference field [".$key."] in ldb.";
        $key_test_array[$key] = false;
        $not_one_fail = false;
    }
}
if ($not_one_fail) echo "\n   none found.";

echo "\n\nChasm null/empty field test:";
$null_test_array = $initial_test_array;
$zero_test_array = $initial_test_array;
$empty_test_array = $initial_test_array;
$i = 0;
foreach ($rows_chasm as $chasm_row){
    $chasm_lookup_array[$chasm_row->application_id."-".strtolower($chasm_row->name_full)] = $i++;
    foreach (array_keys($chasm_keys) as $key){
        if (!is_null($chasm_row->$key)) $null_test_array[$key] = true;
        if (!empty($chasm_row->$key)) $empty_test_array[$key] = true;
        if (($chasm_row->$key != 0)) $zero_test_array[$key] = true;
    }
}
$not_one_fail = true;
foreach (array_keys($chasm_keys) as $key){
    if (!$null_test_array[$key]) {
        echo "\n  all of personal_reference fields [".$key."] are null in chasm.";
        $not_one_fail = true;
    }
    if (!$empty_test_array[$key]) {
        echo "\n  all of personal_reference fields [".$key."] are empty in chasm.";
        $not_one_fail = true;
    }
}
if ($not_one_fail) echo "\n   none found.";

echo "\n\nChasm fields match to LDB by personal_reference test:";
$i = 0;
$not_one_fail = true;
$chasm_missing_array = array();
foreach ($rows_ldb as $ldb_row){
    $ldb_lookup_array[$ldb_row->application_id."-".strtolower($ldb_row->name_full)] = $i++;
//echo $ldb_row->application_id ."\n";
    if (isset($chasm_lookup_array[$ldb_row->application_id."-".strtolower($ldb_row->name_full)])) $chasm_row = $rows_chasm[$chasm_lookup_array[$ldb_row->application_id."-".strtolower($ldb_row->name_full)]];
    else $chasm_row = false;
    if ($chasm_row) {
        $this_test = true;
        foreach (array_keys($chasm_keys) as $key){
            // is the test valid
//echo $key.": ".$chasm_row->$key ." = ". $ldb_row->$key ."\n";
            if (!isset($key_ignore_list[$key]) && $key_test_array[$key] && $null_test_array[$key] && $empty_test_array[$key]) {
                if (strcasecmp(trim($chasm_row->$key),trim($ldb_row->$key)) != 0){ 
//                   !(
//                        (($chasm_row->$key == 0) || empty($chasm_row->$key) || is_null($chasm_row->$key))&&
//                        (($ldb_row->$key == 0) || empty($ldb_row->$key) || is_null($ldb_row->$key))
//                    ) &&
		    $fail = true;
                    if (strtotime($chasm_row->$key)) {
		    	if (abs(strtotime($chasm_row->$key) - strtotime($ldb_row->$key)) < $time_window){
			    $fail = false;
			}
		    }
                    if ($fail && $this_test) {
                        echo "\n application id: ".$ldb_row->application_id;
                        $this_test = false;
                    }
                    if ($fail) {
                        echo "\n  field ".$key." is ".$chasm_row->$key." in chasm and ".$ldb_row->$key." in ldb";
                        $not_one_fail = false;
                    }
                }
            }
        }
    } else $chasm_missing_array[] = "\n  ap: ".$ldb_row->application_id. '  pr_id:'.$ldb_row->personal_reference_id;
}
if ($not_one_fail) echo "\n   none found.";

echo "\n\nMissing personal_reference test:";
echo "\n ldb application ids missing from chasm:";
foreach ($chasm_missing_array as $id_string){
    echo "   ".$id_string;
}
if (count($chasm_missing_array) == 0) echo "\n   none found.";

$i = 0;
echo "\n chasm personal_reference ids missing from ldb";
foreach ($rows_chasm as $chasm_row){
    if (!isset($ldb_lookup_array[$chasm_row->application_id."-".strtolower($chasm_row->name_full)])) {
        $i++;
        echo "\n  ap: ".$chasm_row->application_id. '  pr_id:'.$chasm_row->personal_reference_id;
    }
}
if ($i == 0) echo "\n   none found.";
echo "\n\nTesting Complete\n";
?>
