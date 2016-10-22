<?php
/**
php compare_aalm-ldb_eventlog_data.php
*/
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(COMMON_LIB_DIR."mysqli.1.php");

echo "Database Comparison Test:  do_not_loan_audit\n";

$unset_ary = array();

$time_window = 600;

$key_ignore_list = array(
    "do_not_loan_flag_id" => true
);

$db_ldb = ECash::getMasterDb();
$db_chasm = ECash::getAppSvcDB();
$counter = 0;

$query_chasm_ap = "
SELECT
    dnl.do_not_loan_audit_id AS do_not_loan_audit_id,
    ssn.ssn AS ssn,
    dnl.company_id AS company_id,
    dnl.table_name AS table_name,
    dnl.old_value AS old_value,
    dnl.new_value AS new_value,
    dnl.modifying_agent_id AS modifying_agent_id,
    dnl.date_created AS date_created
FROM do_not_loan_audit AS dnl
    JOIN ssn AS ssn ON (dnl.ssn_id = ssn.ssn_id)
    ORDER BY ssn.ssn
";

//echo $query_chasm_ap, "\n";
$result_chasm_ap = $db_chasm->query($query_chasm_ap);
$rows_chasm_ap = $result_chasm_ap->fetchAll(DB_IStatement_1::FETCH_OBJ);

// construct complete application records
$finish = false;
$i = 0;

$lookup_ary_ap = array();

while (! $finish){
    $finish_test = true;

    if (count($rows_chasm_ap) > $i){
        $finish_test = false;
        $lookup_ary_ap[$rows_chasm_ap[$i]->ssn] = $i;
    }

    $i++;
    $finish = $finish_test;
}

$rows_chasm = $rows_chasm_ap;


$query_ldb = "
SELECT
    dnl.audit_log_id AS do_not_loan_audit_id,
    dnl.ssn AS ssn,
    dnl.company_id AS company_id,
    dnl.table_name AS table_name,
    dnl.column_name AS column_name,
    dnl.value_before AS old_value,
    dnl.value_after AS new_value,
    dnl.agent_id AS modifying_agent_id,
    dnl.date_created AS date_created
FROM do_not_loan_audit AS dnl
    ORDER BY dnl.ssn
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

echo "\n\nChasm keyed to LDB test:";
$chasm_lookup_array = array();
$ldb_lookup_array = array();
$initial_test_array = array();
$key_test_array = array();
$not_one_fail = true;
foreach (array_keys($chasm_keys) as $key){
    $initial_test_array[$key] = false;
    $key_test_array[$key] = true;
    if (!array_key_exists($key,$ldb_keys)) {
        echo "\n  missing chasm do_not_loan_audit field [".$key."] in ldb.";
        $key_test_array[$key] = false;
        $not_one_fail = false;
    }
}
if ($not_one_fail) echo "\n   none found.";

echo "\n\nChasm null/zero/empty field test:";
$null_test_array = $initial_test_array;
$zero_test_array = $initial_test_array;
$empty_test_array = $initial_test_array;
$i = 0;
foreach ($rows_chasm as $chasm_row){
    $chasm_lookup_array[$chasm_row->ssn] = $i;
    foreach (array_keys($chasm_keys) as $key){
        if (!is_null($chasm_row->$key)) $null_test_array[$key] = true;
        if (!empty($chasm_row->$key)) $empty_test_array[$key] = true;
        if (($chasm_row->$key != 0)) $zero_test_array[$key] = true;
    }
}
$not_one_fail = true;
foreach (array_keys($chasm_keys) as $key){
    if (!$null_test_array[$key]) {
        echo "\n  all of do_not_loan_audit fields [".$key."] are null in chasm.";
        $not_one_fail = true;
    }
    if (!$empty_test_array[$key]) {
        echo "\n  all of do_not_loan_audit fields [".$key."] are empty in chasm.";
        $not_one_fail = true;
    }
}
if ($not_one_fail) echo "\n   none found.";

echo "\n\nChasm fields match to LDB by do_not_loan_audit test:";
$i = 0;
$not_one_fail = true;
$chasm_missing_array = array();
foreach ($rows_ldb as $ldb_row){
    $ldb_lookup_array[$ldb_row->ssn] = $i;
    if (isset($chasm_lookup_array[$ldb_row->ssn])) $chasm_row_A = $rows_chasm[$chasm_lookup_array[$ldb_row->ssn]];
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
                        echo "\n ssn id: ".$ldb_row->ssn;
                        $this_test = false;
                    }
                    if ($fail) {
                        $chasm_val = strlen($chasm_row->$key) > 40 ? substr($chasm_row->$key,0,30)."...more..." : $chasm_row->$key;
                        $ldb_val = strlen($ldb_row->$key) > 40 ? substr($ldb_row->$key,0,30)."...more..." : $ldb_row->$key;
                        echo "\n  field ".$key." is ".$chasm_val." in chasm and ".$ldb_val." in ldb";
                        $not_one_fail = false;
                    }
                }
            }
        }
    } else {
        $chasm_missing_array[] = "\n  ap: ".$ldb_row->ssn. '  ssn missing.';
    }
}
if ($not_one_fail) echo "\n   none found.";

echo "\n\nMissing do_not_loan_audit test:";
echo "\n ldb do_not_loan_audit ids missing from chasm";
foreach ($chasm_missing_array as $id_string){
    echo $id_string;
}
if (count($chasm_missing_array) == 0) echo "\n   none found.";

$i = 0;
echo "\n chasm do_not_loan_audit ids missing from ldb";
foreach ($rows_chasm as $chasm_row){
    if (!isset($ldb_lookup_array[$chasm_row->ssn])) {
        echo ("\n  ap: ".$chasm_row->ssn.'  application missing.');
    }
}
if ($i == 0) echo "\n   none found.";
echo "\n\nTesting Complete\n";

//print_r($rows_chasm);
//echo "=========================================================================\n";
//print_r($rows_ldb);
//echo "=========================================================================\n";
//print_r($chasm_lookup_array);
//echo "=========================================================================\n";
//print_r($ldb_lookup_array);

?>
