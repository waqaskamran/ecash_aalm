<?php
/**
 * Searches for appliations with two ach fee assesments on the same
 * day.  Reports any duplicate payments.
 * 
 * For [#51314] when reschedule.php & new_returns_and_corrections.php
 * cronjobs happen to pick up the same app at the same time.
 * 
 * @author Justin Foell <justin.foell@sellingsource.com>
 */

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once SQL_LIB_DIR . 'scheduling.func.php';

$query = "
select tr.application_id, tr.transaction_status, es.date_event, et.name_short, count(*)
from transaction_register tr
join event_schedule es on (es.event_schedule_id = tr.event_schedule_id)
join event_type et on (et.event_type_id = es.event_type_id)
where et.name_short = 'assess_fee_ach_fail' 
and tr.transaction_status = 'complete'
group by tr.application_id, tr.transaction_status, es.date_event, et.name_short
having count(*) > 1
order by date_event desc
";

$factory = ECash::getFactory();

$db = $factory->getDB();
$delete = "
delete es, ea, tr, tl
from event_schedule es
join event_amount ea on (ea.event_schedule_id = es.event_schedule_id)
join transaction_register tr on (tr.event_schedule_id = es.event_schedule_id)
join transaction_ledger tl on (tl.transaction_register_id = tr.transaction_register_id)
where es.event_schedule_id = ?";

$delete_stmt = $db->prepare($delete);

$st = $db->query($query);
$rows = $st->fetchAll(PDO::FETCH_OBJ);

$fp = fopen('/tmp/gforge_51314.csv', 'w');
fputcsv($fp, array('application_id', 'status', 'has_duplicate_payments_as_well'));

foreach($rows as $row)
{
	$application = ECash::getApplicationById($row->application_id);
	$status = $application->getStatus();
	$status_name = $status->level0_name;

	if($status->level0 == 'paid' || $status->level0 == 'settled' || $status->toName() == 'sent::external_collections::*root')
	{
		//echo "Skipping adjustments on {$row->application_id} in status {$status_name}\n";
		//continue;
	}
	
	$schedule = Fetch_Schedule($row->application_id);
	$assessments = array();
	$payments = array();
	$has_dupe_pmnts = FALSE;
	foreach($schedule as $e)
	{
		if(strtotime($e->date_event) >= strtotime($row->date_event))
		{
			if($e->type == 'assess_fee_ach_fail' && $e->date_event == $row->date_event)
			{
				if(count($assessments))
				{					
					//echo "Deleting event_schedule_id {$e->event_schedule_id} for {$row->application_id} in status {$status_name}\n";
					//$delete_stmt->execute(array($e->event_schedule_id));					
				}
				else
				{
					$assessments[] = $e;
				}
			}

			if($e->type == 'payment_fee_ach_fail')
			{
				if(!empty($payments) && $payments[0]->date_event == $e->date_event)
				{
					//echo "Duplicate payments found for {$row->application_id}\n";
					$has_dupe_pmnts = TRUE;
					//no need to keep looking
					break;
				}
				else
				{
					$payments[] = $e;
				}
			}
		}
	}
	fputcsv($fp, array($row->application_id, $status_name, $has_dupe_pmnts ? 'yes' : 'no'));
}

fclose($fp);

?>