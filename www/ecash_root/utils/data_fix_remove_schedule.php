<?php
/**
php data_fix_remove_schedule.php
*/
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once "../www/config.php";
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(CUSTOMER_LIB."failure_dfa.php");
require_once(SERVER_CODE_DIR . 'comment.class.php');

$db = ECash::getMasterDb();
$company_id = 1;
$agent_id = 1;
$server = ECash::getServer();
$server->company_id = $company_id;

//Create array:
//$app_array = array(901426941);

$not_reprocessed = array();

echo "Started...\n";
$sql = "
SELECT DISTINCT ap.application_id, st.`name`
FROM application AS ap

JOIN application_status AS st ON (st.application_status_id = ap.application_status_id)

JOIN transaction_register AS tr ON (tr.application_id = ap.application_id
AND tr.transaction_status='failed'
)

JOIN ach AS ach ON (ach.ach_id = tr.ach_id
AND ach_status = 'returned'
)

JOIN ach_return_code AS arc ON (arc.ach_return_code_id = ach.ach_return_code_id
AND arc.is_fatal='yes'
)

JOIN transaction_type AS tt ON (tt.transaction_type_id = tr.transaction_type_id
AND tt.clearing_type='ach'
AND tt.company_id=1
)

JOIN transaction_history AS th ON (th.transaction_register_id = tr.transaction_register_id
AND th.status_after = 'failed'
)

LEFT JOIN application_audit AS aa ON (aa.application_id=ap.application_id
AND aa.column_name IN ('bank_account','bank_aba')
AND aa.date_created > th.date_created
)

JOIN event_schedule AS es ON (es.application_id=ap.application_id
AND es.event_status='scheduled'
)

JOIN event_type AS et ON (et.event_type_id = es.event_type_id
AND et.company_id=1
AND et.name_short IN ('payment_service_chg','repayment_principal','assess_service_chg','full_balance')
-- AND et.name_short IN ('payment_service_chg')
)

WHERE ap.application_status_id NOT IN (20,123,137,138)
AND aa.audit_log_id IS NULL
ORDER BY st.`name`
";
$result = $db->query($sql);
while ($row = $result->fetch(PDO::FETCH_OBJ))
{
			$application_id = $row->application_id;
			try
			{
				echo $application_id, " ", $row->name, "\n";
				Remove_Unregistered_Events_From_Schedule($application_id);
					
				$app = 	ECash::getApplicationByID($application_id);
				$flags = $app->getFlags();
				if(!$flags->get('has_fatal_ach_failure'))
				{
					$flags->set('has_fatal_ach_failure');
				}
			}
			catch(Exception $e)
			{
				echo $e, "\n";
				$not_reprocessed[] = $application_id;
				continue;
			}
}

echo "not processed: \n";
var_dump($not_reprocessed);
?>

