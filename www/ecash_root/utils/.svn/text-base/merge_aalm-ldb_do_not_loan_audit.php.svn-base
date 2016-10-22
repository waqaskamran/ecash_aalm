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
        $query = "INSERT INTO do_not_loan_audit (
                date_created,
                company_id,
                ssn,
                table_name,
                column_name,
                value_before,
                value_after,
                agent_id
            ) VALUES (
                '".$chasm_row->date_created."',
                ".$chasm_row->company_id.",
                '".$chasm_row->ssn."',
                'do_not_loan_flag',
                '',
                '".$chasm_row->old_value."',
                '".$chasm_row->new_value."',
                ".$chasm_row->modifying_agent_id."
            );";
        $result = $db_ldb->query($query);
    }
}
if ($i == 0) echo "\n   none found.";
echo "\n\nMigration Complete\n";
?>
