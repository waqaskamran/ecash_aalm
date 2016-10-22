<?php
/**
 * Puts inactive paid applications (with a balance, excluding
 * irrecoverable amounts) into collections contact status.  Also puts
 * them into collections_general or collections_fatal queues depending
 * on whether or not they have fatal failures.  Calls
 * complete_schedule to rebuild the schedule if neccessary.  Fixes
 * [#32527]
 * 
 * @author Justin Foell <justin.foell@sellingsource.com>
 */

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once SQL_LIB_DIR . 'scheduling.func.php';

$factory = ECash::getFactory();

$query = "
SELECT
	a.application_id
FROM application AS a
JOIN transaction_register AS tr ON tr.application_id = a.application_id
JOIN event_amount AS ea ON ea.transaction_register_id = tr.transaction_register_id AND ea.application_id = a.application_id
JOIN event_amount_type AS eat ON eat.event_amount_type_id = ea.event_amount_type_id
WHERE tr.transaction_status <> 'failed'
AND eat.name_short <> 'irrecoverable'
AND a.application_status_id = ?
GROUP BY a.application_id
HAVING SUM(ea.amount) > 0
";

$asf = $factory->getReferenceList('ApplicationStatusFlat');
$paid_status = $asf->toId('paid::customer::*root');
$db = $factory->getDB();
$st = $db->queryPrepared($query, array($paid_status));
$apps = $st->fetchAll(PDO::FETCH_OBJ);

$status_id = $asf->toId('queued::contact::collections::customer::*root');
$queue_manager = $factory->getQueueManager();
$general_queue = $queue_manager->getQueue("collections_general");
$fatal_queue = $queue_manager->getQueue("collections_fatal");

foreach($apps as $app_row)
{
	$app = ECash::getApplicationByID($app_row->application_id);
	$app->application_status_id = $status_id;
	$app->save();

	/**
	 * queue setup stolen from
	 * common_functions::performQueueOperationsForStatusChange
	 * check for correctness
	 */
	
	$queue_item = new ECash_Queues_BasicQueueItem($app_row->application_id);
	//this may be pointless as I think all of the apps specified have
	//fatal failures
	$has_fatal = Has_Fatal_Failures($app_row->application_id);
	$queue_item->Priority = (!$has_fatal ? 200 : 100);

	$queue_manager->getQueueGroup('automated')->remove($queue_item);
	if($has_fatal)		
		$fatal_queue->insert($queue_item);
	else
		$general_queue->insert($queue_item);

	//this may also be pointless as I think all of the apps specified have
	//fatal failures (and won't be completed)
	Complete_Schedule($app_row->application_id);
}




?>