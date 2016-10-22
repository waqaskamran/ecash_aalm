<?php
/**
php nerge_aalm-ldb_eventlog_data.php
*/
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(COMMON_LIB_DIR."mysqli.1.php");

echo "Database Comparison Test:  do_not_loan_flag\n";

$unset_ary = array();

$time_window = 300;

$key_ignore_list = array(
    "do_not_loan_flag_id" => true
);

$db_ldb = ECash::getMasterDb();
$db_chasm = ECash::getAppSvcDB();
$counter = 0;

$query_chasm_ap = "
SELECT
    dnl.do_not_loan_flag_id AS do_not_loan_flag_id,
    ssn.ssn AS ssn,
    dnl.company_id AS company_id,
    dnlc.name AS category,
    dnl.other_reason AS other_reason,
    dnl.explanation AS explanation,
    dnl.active_status AS active_status,
    dnl.modifying_agent_id AS modifying_agent_id,
    dnl.date_created AS date_created,
    dnl.date_modified AS date_modified
FROM do_not_loan_flag AS dnl
    JOIN ssn AS ssn ON (dnl.ssn_id = ssn.ssn_id)
    JOIN do_not_loan_flag_category AS dnlc ON (dnl.category_id = dnlc.category_id)
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
    dnl.dnl_flag_id AS do_not_loan_flag_id,
    dnl.ssn AS ssn,
    dnl.company_id AS company_id,
    dnlc.name AS category,
    dnl.other_reason AS other_reason,
    dnl.explanation AS explanation,
    dnl.active_status AS active_status,
    dnl.agent_id AS modifying_agent_id,
    dnl.date_created AS date_created,
    dnl.date_modified AS date_modified
FROM do_not_loan_flag AS dnl
    JOIN do_not_loan_flag_category AS dnlc ON (dnl.category_id = dnlc.category_id)
    ORDER BY dnl.ssn
    ";
$result_ldb = $db_ldb->query($query_ldb);
$rows_ldb = $result_ldb->fetchAll(PDO::FETCH_OBJ);

$ldb_lookup_array = array();

foreach ($rows_ldb as $ldb_row){
    $ldb_lookup_array[$ldb_row->ssn.'-'.($time_window*(floor(strtotime($ldb_row->date_created)/$time_window)))] = $i;
}

$i = 0;
echo "\n migrating chasm do_not_loan_flag ids missing from ldb";
foreach ($rows_chasm as $chasm_row){
    if (!isset($ldb_lookup_array[$chasm_row->ssn.'-'.($time_window*(floor(strtotime($chasm_row->date_created)/$time_window)))])) {
        $i++;
        echo ("\n  Inserting: ".$chasm_row->ssn.'  at:'.date(strtotime($chasm_row->date_created)));
        $query = "INSERT INTO do_not_loan_flag (
                ssn,
                company_id,
                category_id,
                other_reason,
                explanation,
                active_status,
                agent_id,
                date_created,
                date_modified
            ) VALUES (
                '".$chasm_row->ssn."',
                ".$chasm_row->company_id.",
                (SELECT category_id FROM do_not_loan_flag_category WHERE ucase(name) = ucase('".$chasm_row->category."') LIMIT 1),
                '".$chasm_row->other_reason."',
                '".$chasm_row->explanation."',
                IF(".$chasm_row->active_status."=1,'active','inactive'),
                ".$chasm_row->modifying_agent_id.",
                '".$chasm_row->date_created."',
                '".$chasm_row->date_modified."'
            );";
echo $query."\n";
        $result = $db_ldb->query($query);
    }
}
if ($i == 0) echo "\n   none found.";
echo "\n\nMigration Complete\n";
?>
