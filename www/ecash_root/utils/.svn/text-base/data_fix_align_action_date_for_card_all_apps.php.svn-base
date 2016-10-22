<?php
/**
php data_fix_align_action_date_for_card_all_apps.php
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

$company_id = 1;
$agent_id = 1;
$server = ECash::getServer();
$server->company_id = $company_id;

$not_reprocessed = array();

//echo "Started...\n";

$db = ECash::getMasterDb();
$clearing_type = 'card';

$holidays = Fetch_Holiday_List();
$pdc = new Pay_Date_Calc_3($holidays);

$sql = "
SELECT DISTINCT
es.application_id,
es.event_schedule_id,
et.name AS event_name,
es.date_event,
es.date_effective,
ea.amount
FROM event_schedule AS es
JOIN event_amount AS ea USING (event_schedule_id)
JOIN event_type AS et ON (et.event_type_id=es.event_type_id
AND et.company_id={$company_id}
AND et.active_status='active')
JOIN event_transaction AS evtr ON (evtr.event_type_id=et.event_type_id
AND evtr.company_id={$company_id}
AND evtr.active_status='active')
JOIN transaction_type AS tt ON (tt.transaction_type_id=evtr.transaction_type_id
AND tt.company_id={$company_id}
AND tt.active_status='active')
WHERE es.event_status='scheduled'
AND tt.clearing_type='{$clearing_type}'
AND ea.amount < 0
AND es.date_event < es.date_effective
-- AND es.application_id = 901698634
ORDER BY es.date_event
";
$result = $db->query($sql);
while ($row = $result->fetch(PDO::FETCH_OBJ))
{
try
{
$application_id = $row->application_id;
$previous_business_day = $pdc->Get_Last_Business_Day($row->date_effective);
echo $application_id, ", ", $row->event_schedule_id, ", ", $row->event_name, ", ", $row->date_event, ", ", $row->date_effective, ", ", $previous_business_day, "\n";
/*
$query = "
UPDATE event_schedule
SET date_event = date_effective
WHERE application_id={$application_id}
AND event_schedule_id = {$row->event_schedule_id}
";
$db->query($query);

$query = "
UPDATE event_schedule AS es
JOIN event_type AS et ON (et.event_type_id=es.event_type_id
AND et.company_id={$company_id}
AND et.active_status='active')
SET es.date_event = '{$row->date_effective}',
es.date_effective = '{$row->date_effective}'
WHERE es.application_id={$application_id}
AND et.name_short='assess_service_chg'
AND es.event_status='scheduled'
AND es.date_effective='{$row->date_event}'
";
$db->query($query);
*/
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
