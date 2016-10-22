<?php
/**
php merge_aalm-ldb_personal_reference_data.php
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

$ldb_lookup_array = array();
$i=0;
foreach ($rows_ldb as $ldb_row){
    $ldb_lookup_array[$ldb_row->application_id."-".strtolower($ldb_row->name_full)] = $i++;
}

echo "\n migrating chasm personal_references missing from ldb";
foreach ($rows_chasm as $chasm_row){
    if (!isset($ldb_lookup_array[$chasm_row->application_id."-".strtolower($chasm_row->name_full)])) {
        echo "\n  Inserting ap: ".$chasm_row->application_id. '  name:'.$chasm_row->name_full;
	$query = "INSERT INTO personal_reference (
                date_created,
                date_modified,
                company_id,
                application_id,
                name_full,
                phone_home,
                relationship,
                reference_verified,
                contact_pref,
                agent_id
	    ) VALUES (
                '".$chasm_row->date_created."',
                '".$chasm_row->date_modified."',
                ".$chasm_row->company_id.",
                ".$chasm_row->application_id.",
                '".$chasm_row->name_full."',
                '".$chasm_row->phone_home."',
                '".$chasm_row->relationship."',
                '".$chasm_row->reference_verified."',
                '".$chasm_row->contact_pref."',
                ".$chasm_row->agent_id."
            );";
	$result = $db_ldb->query($query);
        
    }
}
echo "\n\Migration Complete\n";
?>
