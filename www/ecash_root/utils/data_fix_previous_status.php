<?php
/**
php data_fix_previous_status.php
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

echo "Started...\n";
/*
                $sql = "
                        SELECT application_id, modifying_agent_id
                        FROM application
                        WHERE application_status_id = 190
                        AND modifying_agent_id = 1
                        AND date_application_status_set > '2015-08-05 10:35:21'
                ";
                $result = $db->query($sql);
                while ($row = $result->fetch(PDO::FETCH_OBJ))
                {
                        $application_id = $row->application_id;
                        echo $application_id, ", ", $row->modifying_agent_id, "\n";

                        $app =  ECash::getApplicationByID($application_id);
                        $prev_stat = $app->getPreviousStatus();

                        Update_Status(null, $application_id, $prev_stat->application_status_id);

                        $qm = ECash::getFactory()->getQueueManager();
                        $queue_item = new ECash_Queues_BasicQueueItem($application_id);
                        $qm->removeFromAllQueues($queue_item);
                }
*/

?>