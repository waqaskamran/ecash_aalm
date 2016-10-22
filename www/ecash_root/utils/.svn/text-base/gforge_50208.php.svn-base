<?php
/**
 * Searches for applications which were not in 'Collections Rework'
 * for the requisite 14 days before moving to their current status of
 * 'Second Tier (Pending)'.  Fixes [#50208]
 * 
 * @author Justin Foell <justin.foell@sellingsource.com>
 */
require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once SQL_LIB_DIR . 'scheduling.func.php';
$factory = ECash::getFactory();
$asl = $factory->getReferenceList('ApplicationStatusFlat');
$second_tier_id = $asl->toId('pending::external_collections::*root');
if(!$second_tier_id)
{
	die("Status ID for 'Second Tier (Pending)' not found\n");
}

$rework_id = $asl->toId('collections_rework::collections::customer::*root');
if(!$rework_id)
{
	die("Status ID for 'Collections Rework' not found\n");
}

$query = "
select a.application_id
from application a
join status_history cur on (cur.application_id=a.application_id)
left join status_history sh1 on (sh1.application_id=cur.application_id and sh1.status_history_id>cur.status_history_id)
join status_history prev on (prev.application_id=cur.application_id and prev.status_history_id<cur.status_history_id)
left join status_history sh2 on (sh2.application_id=prev.application_id and sh2.status_history_id<cur.status_history_id and sh2.status_history_id>prev.status_history_id)
where 
sh1.status_history_id is null
and sh2.status_history_id is null
and cur.application_status_id={$second_tier_id}
and prev.application_status_id={$rework_id}
 and date_add(prev.date_created, interval 14 day) > cur.date_created";

$db = $factory->getDB();
$st = $db->queryPrepared($query, array($status_id));
$apps = $st->fetchAll(PDO::FETCH_OBJ);
$qm = $factory->getQueueManager();
$rework_queue = $qm->getQueue('collections_rework');

foreach($apps as $app_row)
{
	//print_r($app_row); continue;
	Update_Status(null, $app_row->application_id, array('collections_rework','collections','customer','*root'), NULL, NULL, FALSE);
	$queue_item = $rework_queue->getNewQueueItem($app_row->application_id);
	$qm->moveToQueue($queue_item, 'collections_rework');
}
