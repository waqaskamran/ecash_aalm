<?php
/**
 * Searches for active appliations with fee balance and adds a fee
 * payment to the next paydate to remedy this.  Fixes [#32201]
 * 
 * @author Justin Foell <justin.foell@sellingsource.com>
 */

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once SQL_LIB_DIR . 'scheduling.func.php';

$factory = ECash::getFactory();
$asl = $factory->getReferenceList('ApplicationStatusFlat');
$status_id = $asl->toId('active::servicing::customer::*root');
if(!$status_id)
{
	die("Status ID not found\n");
}

$query = "
SELECT
    ea.application_id,
    SUM( IF( tr.transaction_status = 'complete' OR transaction_status = 'pending', ea.amount, 0)) pending_balance,
    SUM( IF( tr.transaction_status != 'failed' OR tr.transaction_register_id IS NULL, ea.amount, 0)) scheduled_balance,
    SUM( IF( (tr.transaction_status != 'failed' OR tr.transaction_register_id IS NULL) AND eat.name_short = 'fee', ea.amount, 0)) scheduled_fee_balance,
	next_payment.date_event as next_date_event,
	next_payment.date_effective as next_date_effective
FROM event_amount AS ea
JOIN event_amount_type AS eat ON eat.event_amount_type_id = ea.event_amount_type_id
JOIN application AS a ON ea.application_id = a.application_id
LEFT JOIN transaction_register AS tr ON ea.transaction_register_id = tr.transaction_register_id
left join (
	select 
		es.application_id,
		min(es.date_event) as date_event,
		min(es.date_effective) as date_effective
	from event_schedule es
	join event_transaction et on (et.event_type_id = es.event_type_id)
	join transaction_type tt on (et.transaction_type_id = tt.transaction_type_id)
	where event_status = 'scheduled'
	and es.date_event > now()
	and es.date_effective > now()
	and tt.clearing_type = 'ach'
	group by application_id
) as next_payment on (next_payment.application_id = a.application_id)
WHERE a.application_status_id = ?
GROUP BY ea.application_id
HAVING scheduled_balance <> 0 AND scheduled_fee_balance > 0
ORDER BY application_id
";

$db = $factory->getDB();
$st = $db->queryPrepared($query, array($status_id));
$apps = $st->fetchAll(PDO::FETCH_OBJ);

foreach($apps as $app_row)
{
	$amounts = array();
	$amounts[] = Event_Amount::MakeEventAmount('fee', -intval($app_row->scheduled_fee_balance));
	$e = Schedule_Event::MakeEvent($app_row->next_date_event, $app_row->next_date_effective, $amounts, 'payment_fee_ach_fail', 'ACH fee payment');
	//echo 'Would record event: ', print_r($e, TRUE), PHP_EOL;
	Record_Event($app_row->application_id, $e);
	
}

?>