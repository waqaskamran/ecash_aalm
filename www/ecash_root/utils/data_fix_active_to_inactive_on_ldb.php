<?php
/**
php data_fix_active_to_inactive_on_ldb.php
*/

putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(COMMON_LIB_DIR."mysqli.1.php");

$db_ldb = ECash::getMasterDb();
$db_chasm = ECash::getAppSvcDB();

echo "Started...\n";
$sql = "
SELECT a.application_id,
(SELECT SUM(ea1.amount)
FROM transaction_register AS tr1
JOIN event_amount ea1 USING (application_id, event_schedule_id, transaction_register_id)
JOIN event_amount_type eat1 USING (event_amount_type_id)
WHERE ea1.application_id = a.application_id
AND eat1.name_short <> 'irrecoverable'
-- AND tr1.transaction_status <> 'failed') AS balance
AND tr1.transaction_status = 'complete') AS balance
FROM application AS a
WHERE
a.application_status_id IN (20,125)
GROUP BY a.application_id
HAVING balance <= 0
";
$result = $db_ldb->query($sql);
while ($row = $result->fetch(PDO::FETCH_OBJ))
{
        $application_id = $row->application_id;
        
        $query_chasm = "
        SELECT
                ap.authoritative_id AS chasm_application_id,
                st.application_status_name
        FROM application AS ap
        JOIN application_status AS st ON (st.application_status_id=ap.application_status_id)
        WHERE
                ap.authoritative_id = {$application_id}
        AND
                st.application_status_name IN ('paid::customer::*root','recovered::external_collections::*root','internal_recovered::external_collections::*root','settled::customer::*root')
        ";
        $result_chasm = $db_chasm->query($query_chasm);
        $row_chasm = $result_chasm->fetch(DB_IStatement_1::FETCH_OBJ);
        if ($row_chasm->chasm_application_id > 0)
        {
                $status = $row_chasm->application_status_name;
                echo $application_id, " changed to ", $status, "\n";
		//echo $application_id, "\n";

		if ($status == 'paid::customer::*root')
			$application_status_id = 109;
		else if ($status == 'recovered::external_collections::*root')
			$application_status_id = 113;
		else if ($status == 'internal_recovered::external_collections::*root')
			$application_status_id = 162;
		else if ($status == 'settled::customer::*root')
			$application_status_id = 158;
		else
			$application_status_id = 109;
        }

	$query_ldb = "
		UPDATE application
		SET application_status_id = {$application_status_id}
		WHERE application_id = {$application_id}
	";
	$db_ldb->query($query_ldb);
}

?>
