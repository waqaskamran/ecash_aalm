<?php
/**
php compare_aalm-ldb_bureau_inquiry_data.php
*/
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(COMMON_LIB_DIR."mysqli.1.php");

echo "Database Comparison Test:  bureau_inquiry\n";

$unset_ary = array();

$time_window = 300;

$key_ignore_list = array(
    "bureau_inquiry_id"=>true,
    "external_id"=>true
);

$db_ldb = ECash::getMasterDb();
$db_chasm = ECash::getAppSvcDB();
for ($k = 0; $k<100; $k++){

    $j = $k>9 ? $k."": "0".$k;
    echo "\n testing applications ending in: ".$j."\n";
    
    $query_chasm_ap = "
    SELECT
        bi.application_id AS application_id,
        bi.bureau_inquiry_id AS bureau_inquiry_id,
        bi.bureau_id AS bureau_id,
        bi.date_created AS date_created,
        bi.date_modified AS date_modified,
        bi.company_id AS company_id,
        bi.external_id AS external_id,
        bi.inquiry_type AS inquiry_type,
        bi.outcome AS outcome,
        bi.payrate AS payrate,
        bi.reason AS reason,
        bi.score AS score,
        bi.timer AS timer,
        bi.trace_info AS trace_info,
        de.decision_name AS decision,
        ec.error_condition_name AS error_condition,
        rp.package AS received_package,
        sp.package AS sent_package
    FROM bureau_inquiry AS bi
        JOIN decision de ON (bi.decision_id = de.decision_id)
        LEFT JOIN error_condition ec ON (ec.error_condition_id = bi.error_condition_id)
        JOIN receive_package rp ON (bi.receive_package_id = rp.receive_package_id)
        JOIN sent_package sp ON (bi.sent_package_id = sp.sent_package_id)
        WHERE bi.application_id LIKE '%".$j."'
        ORDER BY bi.application_id
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
        bi.application_id AS application_id,
        bi.bureau_inquiry_id AS bureau_inquiry_id,
        bi.bureau_id AS bureau_id,
        bi.date_created AS date_created,
        bi.date_modified AS date_modified,
        bi.company_id AS company_id,
        bi.inquiry_type AS inquiry_type,
        bi.outcome AS outcome,
        bi.payrate AS payrate,
        bi.reason AS reason,
        bi.score AS score,
        bi.timer AS timer,
        bi.trace_info AS trace_info,
        bi.decision AS decision,
        bi.error_condition AS error_condition,
        CONVERT(bi.received_package, CHAR(32767)) AS received_package,
        CONVERT(bi.sent_package, CHAR(32767)) AS sent_package
    FROM bureau_inquiry AS bi
        WHERE bi.application_id LIKE '%".$j."'
        ORDER BY bi.application_id
        ";
    $result_ldb = $db_ldb->query($query_ldb);
    $rows_ldb = $result_ldb->fetchAll(PDO::FETCH_OBJ);
    
    $chasm_row = $rows_chasm[1];
    $chasm_keys = get_object_vars($chasm_row);

    $ldb_row = $rows_ldb[1];
    $ldb_keys = get_object_vars($ldb_row);
    if ($k == 0){
        echo "\n\nChasm Keys:\n";
        print_r(array_keys($chasm_keys));
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
        if ($not_one_fail) "\n   none found.";
        
        echo "\n\nChasm null/empty field test:";
        $null_test_array = $initial_test_array;
        $zero_test_array = $initial_test_array;
        $empty_test_array = $initial_test_array;
        $i = 0;
        foreach ($rows_chasm as $chasm_row){
            $chasm_lookup_array[$chasm_row->application_id] = $i;
            $chasm_lookup_array[$chasm_row->application_id."-". strtolower($chasm_row->inquiry_type)] = $i;
            $chasm_lookup_array[$chasm_row->application_id."-". strtolower($chasm_row->inquiry_type)."-".($time_window*(floor(strtotime($chasm_row->date_created)/$time_window)))] = $i++;
            foreach (array_keys($chasm_keys) as $key){
                if (!is_null($chasm_row->$key)) $null_test_array[$key] = true;
                if (!empty($chasm_row->$key)) $empty_test_array[$key] = true;
                if (($chasm_row->$key != 0)) $zero_test_array[$key] = true;
            }
        }
        $not_one_fail = true;
        foreach (array_keys($chasm_keys) as $key){
            if (!$null_test_array[$key]) {
                echo "\n  all of bureau_inquiry fields [".$key."] are null in chasm.";
                $not_one_fail = true;
            }
            if (!$empty_test_array[$key]) {
                echo "\n  all of bureau_inquiry fields [".$key."] are empty in chasm.";
                $not_one_fail = true;
            }
        }
        if ($not_one_fail) "\n   none found.";
    }

    echo "\n\nChasm fields match to LDB by bureau_inquiry test:";
    $i = 0;
    $not_one_fail = true;
    $chasm_missing_array = array();
    foreach ($rows_ldb as $ldb_row){
        $ldb_lookup_array[$ldb_row->application_id] = $i;
        $ldb_lookup_array[$ldb_row->application_id."-".strtolower($ldb_row->inquiry_type)] = $i;
        $ldb_lookup_array[$ldb_row->application_id."-".strtolower($ldb_row->inquiry_type)."-".($time_window*(floor(strtotime($ldb_row->date_created)/$time_window)))] = $i++;
    //echo $ldb_row->application_id ."\n";
        if (isset($chasm_lookup_array[$ldb_row->application_id])) $chasm_row_A = $rows_chasm[$chasm_lookup_array[$ldb_row->application_id]];
        else $chasm_row_A = false;
        if (isset($chasm_lookup_array[$ldb_row->application_id."-".strtolower($ldb_row->inquiry_type)])) $chasm_row_B = $rows_chasm[$chasm_lookup_array[$ldb_row->application_id."-".strtolower($ldb_row->inquiry_type)]];
        else $chasm_row_B = false;
        if (isset($chasm_lookup_array[$ldb_row->application_id."-".strtolower($ldb_row->inquiry_type)."-".($time_window*(floor(strtotime($ldb_row->date_created)/$time_window)))])) $chasm_row = $rows_chasm[$chasm_lookup_array[$ldb_row->application_id."-".strtolower($ldb_row->inquiry_type)."-".($time_window*(floor(strtotime($ldb_row->date_created)/$time_window)))]];
        else $chasm_row = false;
        if ($chasm_row) {
    //
    // need to convert ldb receive and sent packages to text strings
            if (ord(substr($ldb_row->received_package, 5, 1)) == '156')
            {
                    $ldb_row->received_package = gzuncompress(substr($ldb_row->received_package, 4));
            }
            if (ord(substr($ldb_row->sent_package, 5, 1)) == '156')
            {
                    $ldb_row->sent_package = gzuncompress(substr($ldb_row->sent_package, 4));
            }
    
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
            if (!$chasm_row_A) $chasm_missing_array[] = "\n  ap: ".$ldb_row->application_id. '  application missing.';
            else if (!$chasm_row_B) $chasm_missing_array[] = "\n  ap: ".$ldb_row->application_id.'  type:'.strtolower($ldb_row->inquiry_type).' type missing.';
            else $chasm_missing_array[] = "\n  ap: ".$ldb_row->application_id.'  type:'.strtolower($ldb_row->inquiry_type). '  bureau_inquiry:'.$ldb_row->bureau_inquiry_id;
        }
    }
    if ($not_one_fail) "\n   none found.";
    
    echo "\n\nMissing bureau_inquiry test:";
    echo "\n ldb bureau_inquiry ids missing from chasm";
    foreach ($chasm_missing_array as $id_string){
        echo $id_string;
    }
    if (count($chasm_missing_array) == 0) "\n   none found.";
    
    $i = 0;
    echo "\n chasm bureau_inquiry ids missing from ldb";
    foreach ($rows_chasm as $chasm_row){
        if (!isset($ldb_lookup_array[$chasm_row->application_id."-".strtolower($ldb_row->inquiry_type)."-".($time_window*(floor(strtotime($chasm_row->date_created)/$time_window)))])) {
            $i++;
            if (!isset($ldb_lookup_array[$chasm_row->application_id."-".strtolower($ldb_row->inquiry_type)])) {
                if (!isset($ldb_lookup_array[$chasm_row->application_id])) {
                    echo ("\n  ap: ".$chasm_row->application_id.'  application missing.');
                }
                else echo ("\n  ap: ".$chasm_row->application_id.'  type:'.strtolower($chasm_row->inquiry_type). ' type missing.');
            }
            else echo ("\n  ap: ".$chasm_row->application_id.'  type:'.strtolower($chasm_row->inquiry_type). '  bureau_inquiry:'.$chasm_row->bureau_inquiry_id);
        }
    }
    if ($i == 0) "\n   none found.";
}
echo "\n\nTesting Complete\n";
?>
