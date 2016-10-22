<?php
/**
php compare_aalm-ldb_loan_action_data.php
*/
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(COMMON_LIB_DIR."mysqli.1.php");

echo "Database Comparison Test:  loan_action\n";

$factory = ECash::getFactory();
$status_list = $factory->getReferenceList('ApplicationStatusFlat');

$unset_ary = array();

$time_window = 600;

$key_ignore_list = array(
    "application_status" => true
);

$db_ldb = ECash::getMasterDb();
$db_chasm = ECash::getAppSvcDB();
$counter = 0;

$query_chasm_ap = "
SELECT
    ap.authoritative_id AS application_id,
    ast.application_status_name AS application_status,
    la.name_short AS action,
    lah.date_created AS date_created,
    lah.modifying_agent_id AS agent_id,
    las.section AS section
FROM loan_action_history AS lah
    JOIN application_status AS ast ON (lah.application_status_id = ast.application_status_id)
    JOIN application AS ap ON (lah.application_id = ap.application_id)
    JOIN loan_action AS la ON (lah.loan_action_id = la.loan_action_id)
    LEFT JOIN loan_action_section AS las ON (lah.loan_action_section_id = las.loan_action_section_id)
    ORDER BY ap.authoritative_id, lah.date_created
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
        $lookup_ary_ap[$rows_chasm_ap[$i]->application_id] = $i;
    }

    $i++;
    $finish = $finish_test;
}

$rows_chasm = $rows_chasm_ap;


$query_ldb = "
SELECT
    lah.application_id AS application_id,
    lah.application_status_id AS application_status_id,
    la.name_short AS action,
    lah.date_created AS date_created,
    lah.agent_id AS agent_id,
    las.name_short AS section
FROM loan_action_history AS lah
    JOIN loan_actions AS la ON (lah.loan_action_id = la.loan_action_id)
    LEFT JOIN loan_action_section AS las ON (lah.loan_action_section_id = las.loan_action_section_id)
    ORDER BY lah.application_id, lah.date_created
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
        echo "\n  missing chasm bureau_inquiry field [".$key."] in ldb.";
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
    $chasm_lookup_array[$chasm_row->application_id] = $i;
    $chasm_lookup_array[$chasm_row->application_id."-". strtolower($chasm_row->action)] = $i;
    $chasm_lookup_array[$chasm_row->application_id."-". strtolower($chasm_row->action)."-".($time_window*(floor(strtotime($chasm_row->date_created)/$time_window)))] = $i++;
    foreach (array_keys($chasm_keys) as $key){
        if (!is_null($chasm_row->$key)) $null_test_array[$key] = true;
        if (!empty($chasm_row->$key)) $empty_test_array[$key] = true;
        if (($chasm_row->$key != 0)) $zero_test_array[$key] = true;
    }
}
$not_one_fail = true;
foreach (array_keys($chasm_keys) as $key){
    if (!$null_test_array[$key]) {
        echo "\n  all of loan_action fields [".$key."] are null in chasm.";
        $not_one_fail = true;
    }
    if (!$empty_test_array[$key]) {
        echo "\n  all of loan_action fields [".$key."] are empty in chasm.";
        $not_one_fail = true;
    }
}
if ($not_one_fail) echo "\n   none found.";

echo "\n\nChasm fields match to LDB by loan_action test:";
$i = 0;
$not_one_fail = true;
$chasm_missing_array = array();
foreach ($rows_ldb as $ldb_row){
    $ldb_row->application_status_name = $status_list->toName($ldb_row->application_status_id);
    $ldb_lookup_array[$ldb_row->application_id] = $i;
    $ldb_lookup_array[$ldb_row->application_id."-".strtolower($ldb_row->action)] = $i;
    $ldb_lookup_array[$ldb_row->application_id."-".strtolower($ldb_row->action)."-".($time_window*(floor(strtotime($ldb_row->date_created)/$time_window)))] = $i++;
//echo $ldb_row->application_id ."\n";
    if (isset($chasm_lookup_array[$ldb_row->application_id])) $chasm_row_A = $rows_chasm[$chasm_lookup_array[$ldb_row->application_id]];
    else $chasm_row_A = false;
    if (isset($chasm_lookup_array[$ldb_row->application_id."-".strtolower($ldb_row->action)])) $chasm_row_B = $rows_chasm[$chasm_lookup_array[$ldb_row->application_id."-".strtolower($ldb_row->action)]];
    else $chasm_row_B = false;
    if (isset($chasm_lookup_array[$ldb_row->application_id."-".strtolower($ldb_row->action)."-".($time_window*(floor(strtotime($ldb_row->date_created)/$time_window)))])) $chasm_row = $rows_chasm[$chasm_lookup_array[$ldb_row->application_id."-".strtolower($ldb_row->action)."-".($time_window*(floor(strtotime($ldb_row->date_created)/$time_window)))]];
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
                        $chasm_val = strlen($chasm_row->$key) > 40 ? substr($chasm_row->$key,0,30)."...more..." : $chasm_row->$key;
                        $ldb_val = strlen($ldb_row->$key) > 40 ? substr($ldb_row->$key,0,30)."...more..." : $ldb_row->$key;
                        echo "\n  field ".$key." is ".$chasm_val." in chasm and ".$ldb_val." in ldb";
                        $not_one_fail = false;
                    }
                }
            }
        }
    } else {
        if (!$chasm_row_A) $chasm_missing_array[] = "\n  ap: ".$ldb_row->application_id. '  application_id missing.';
        else if (!$chasm_row_B) $chasm_missing_array[] = "\n  ap: ".$ldb_row->application_id.'  action:'.strtolower($ldb_row->action).' type missing.';
        else $chasm_missing_array[] = "\n  ap: ".$ldb_row->application_id.'  action:'.strtolower($ldb_row->action). '  date:'.$ldb_row->date_created.' missing';
    }
}
if ($not_one_fail) echo "\n   none found.";

echo "\n\nMissing loan_action test:";
echo "\n ldb loan_action ids missing from chasm";
foreach ($chasm_missing_array as $id_string){
    echo $id_string;
}
if (count($chasm_missing_array) == 0) echo "\n   none found.";

$i = 0;
echo "\n chasm loan_action ids missing from ldb";
foreach ($rows_chasm as $chasm_row){
    if (!isset($ldb_lookup_array[$chasm_row->application_id."-".strtolower($chasm_row->action)."-".($time_window*(floor(strtotime($chasm_row->date_created)/$time_window)))])) {
        $i++;
        if (!isset($ldb_lookup_array[$chasm_row->application_id."-".strtolower($chasm_row->action)])) {
            if (!isset($ldb_lookup_array[$chasm_row->application_id])) {
                echo ("\n  ap: ".$chasm_row->application_id.'  application missing.');
            }
            else echo ("\n  ap: ".$chasm_row->application_id.'  action:'.strtolower($chasm_row->action). ' type missing.');
        }
        else echo ("\n  ap: ".$chasm_row->application_id.'  action:'.strtolower($chasm_row->action). '  date:'.$chasm_row->date_created.' missing');
    }
}
if ($i == 0) echo "\n   none found.";
echo "\n\nTesting Complete\n";
?>
