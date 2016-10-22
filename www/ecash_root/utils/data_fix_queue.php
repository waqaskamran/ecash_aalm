<?php
/**
php data_fix_handle_ny_postfund.php
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

$not_reprocessed = array();

echo "Started...\n";

$application_id = 902216397;
//$queue_name = 'underwriting_react';
$queue_name = 'verification';
echo "application_id: ", $application_id, " queue: ", $queue_name, "\n";

$qm = ECash::getFactory()->getQueueManager();

//Insert into queue specified
$queue_item = $qm->getQueue($queue_name)->getNewQueueItem($application_id);
$qm->moveToQueue($queue_item, $queue_name);

//Remove from queue specified
//$queue = $qm->getQueue($queue_name);
//$queue_item = new ECash_Queues_BasicQueueItem($application_id);
//$queue->remove($queue_item);

//Remove from all queues
//$queue_item = new ECash_Queues_BasicQueueItem($application_id);
//$qm->removeFromAllQueues($queue_item);

?>

