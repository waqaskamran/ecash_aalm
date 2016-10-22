<?php
/**
php compare_aalm-ldb_campaign_data.php
*/
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(COMMON_LIB_DIR."mysqli.1.php");

echo "Database Comparison Test:  campaign\n";

$unset_ary = array();

$time_window = 300;

$key_ignore_list = array(
    "password"=>true
);

$db_ldb = ECash::getMasterDb();
$db_chasm = ECash::getAppSvcDB();
$counter = 0;

$query_chasm_ap = "
SELECT
    ap.authoritative_id AS application_id,
    ci.campaign_info_id AS campaign_info_id,
    ci.date_created AS date_created,
    ci.date_modified AS date_modified,
    ci.campaign_name AS campaign_name,
    ci.promo_id AS promo_id,
    ci.promo_sub_code AS promo_sub_code,
    si.site_name AS site_name,
    si.license_key AS license_key,
    ci.reservation_id AS reservation_id,
    ci.friendly_name AS friendly_name
FROM application AS ap
    JOIN campaign_info ci ON (ap.application_id = ci.application_id)
    JOIN site si ON (ci.site_id = si.site_id)
    ORDER BY ap.authoritative_id
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
SELECT ci.application_id, 
    ci.campaign_info_id AS campaign_info_id,
    ci.date_created AS date_created,
    ci.date_modified AS date_modified,
    ci.campaign_name AS campaign_name,
    ci.promo_id AS promo_id,
    ci.promo_sub_code AS promo_sub_code,
    si.name AS site_name,
    si.license_key AS license_key,
    ci.reservation_id AS reservation_id,
    '' AS friendly_name
FROM campaign_info ci 
    JOIN site si USING (site_id)
    ORDER BY ci.application_id
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
        echo "\n  missing chasm campaign field [".$key."] in ldb.";
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
    $chasm_lookup_array[$chasm_row->application_id] = $i++;
    foreach (array_keys($chasm_keys) as $key){
        if (!is_null($chasm_row->$key)) $null_test_array[$key] = true;
        if (!empty($chasm_row->$key)) $empty_test_array[$key] = true;
        if (($chasm_row->$key != 0)) $zero_test_array[$key] = true;
    }
}
$not_one_fail = true;
foreach (array_keys($chasm_keys) as $key){
    if (!$null_test_array[$key]) {
        echo "\n  all of campaign fields [".$key."] are null in chasm.";
        $not_one_fail = true;
    }
    if (!$empty_test_array[$key]) {
        echo "\n  all of campaign fields [".$key."] are empty in chasm.";
        $not_one_fail = true;
    }
}
if ($not_one_fail) echo "\n   none found.";

echo "\n\nChasm fields match to LDB by campaign test:";
$i = 0;
$not_one_fail = true;
$chasm_missing_array = array();
foreach ($rows_ldb as $ldb_row){
    $ldb_lookup_array[$ldb_row->application_id] = $i++;
//echo $ldb_row->application_id ."\n";
    if (isset($chasm_lookup_array[$ldb_row->application_id])) $chasm_row = $rows_chasm[$chasm_lookup_array[$ldb_row->application_id]];
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
    } else $chasm_missing_array[] = "\n  ap: ".$ldb_row->application_id. '  campaign_info:'.$ldb_row->campaign_info_id;
}
if ($not_one_fail) echo "\n   none found.";

echo "\n\nMissing campaign test:";
echo "\n ldb campaign ids missing from chasm";
foreach ($chasm_missing_array as $id_string){
    echo $id_string;
}
if (count($chasm_missing_array) == 0) echo "\n   none found.";

$i = 0;
echo "\n chasm campaign ids missing from ldb";
foreach ($rows_chasm as $chasm_row){
    if (!isset($ldb_lookup_array[$chasm_row->application_id])) {
        $i++;
        echo "\n  ap: ".$chasm_row->application_id. '  campaign_info:'.$chasm_row->campaign_info_id;
    }
}
if ($i == 0) echo "\n   none found.";
echo "\n\nTesting Complete\n";
?>
