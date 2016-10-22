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

echo "Database Merge Date:  application\n";

$db_ldb = ECash::getMasterDb();
$db_chasm = ECash::getAppSvcDB();

for ($i = 0; $i<100; $i++){

    $j = $i>9 ? $i."": "0".$i;
    echo "\n updating applications ending in: ".$j."\n";
    
    $query_chasm = "
        SELECT
            ap.authoritative_id AS application_id,
            ap.external_id AS external_id,
            appl.applicant_account_id AS applicant_account_id
        FROM application AS ap
            JOIN applicant AS appl ON (appl.application_id = ap.application_id)
        WHERE ap.authoritative_id LIKE '%".$j."' AND appl.applicant_account_id > 0
        ORDER BY ap.authoritative_id
    ";

    $result_chasm = $db_chasm->query($query_chasm);
    $rows_chasm = $result_chasm->fetchAll(DB_IStatement_1::FETCH_OBJ);
    
    $query_ldb = "
        SELECT *
        FROM application
        WHERE application_id LIKE '%".$j."'
        ORDER BY application_id
    ";
    
    $result_ldb = $db_ldb->query($query_ldb);
    $rows_ldb = $result_ldb->fetchAll(PDO::FETCH_OBJ);
    
    $chasm_lookup_array = array();
    
    $k = 0;
    foreach ($rows_chasm as $chasm_row){
        $chasm_lookup_array[$chasm_row->application_id] = $k++;
    }
    
    foreach ($rows_ldb as $ldb_row){
        if (isset($chasm_lookup_array[$ldb_row->application_id])) $chasm_row = $rows_chasm[$chasm_lookup_array[$ldb_row->application_id]];
        else $chasm_row = false;
        if ($chasm_row) {
	    if (!($chasm_row->external_id >0)) $chasm_row->external_id = 0;
	    echo $ldb_row->application_id." updating... ";
            $query = "UPDATE application SET 
                            external_id = ".$chasm_row->external_id.",
                            applicant_account_id = ".$chasm_row->applicant_account_id."
                    WHERE application_id ='".$ldb_row->application_id."';";
//echo $query."\n";
	    $result = $db_ldb->query($query);
            echo "done.\n";
        }
    }

}

echo "\n\nUpdate Complete\n";
?>
