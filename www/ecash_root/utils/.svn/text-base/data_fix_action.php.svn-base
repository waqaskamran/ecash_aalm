<?php
/**
php data_fix_action.php <application_id> <action_id from switch statement>, for example, data_fix_action.php 987654320 1 (to Complete_Schedule)
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
require_once(LIB_DIR.'AgentAffiliation.php');

$db = ECash::getMasterDb();
$company_id = 1;
$agent_id = 1;
$server = ECash::getServer();
$server->company_id = $company_id;

if ($_SERVER['argc'] != 3)
{
echo "Usage: php data_fix_action.php <application_id> <action_id from switch statement>, for example, data_fix_action.php 987654321 1 (to Complete_Schedule).\n";
exit(1);
}

$application_id = $_SERVER['argv'][1];
$action = $_SERVER['argv'][2];

echo $application_id, " ", $action, "\n";

switch($action)
{
        case 1:
                Complete_Schedule($application_id);
                echo "Action: Complete_Schedule.", "\n";
                break;
        case 2:
                Remove_Unregistered_Events_From_Schedule($application_id);
                echo "Action: Remove_Unregistered_Events_From_Schedule.", "\n";
                break;

	case 3:
		Remove_Unregistered_Events_From_Schedule($application_id);

		$app = 	ECash::getApplicationByID($application_id);
		$flags = $app->getFlags();
		if(!$flags->get('has_fatal_ach_failure'))
		{
			$flags->set('has_fatal_ach_failure');
		}

		echo "Action: Remove_Unregistered_Events_From_Schedule, Set Fatal ACH Flag.", "\n";
		break;
	
	case 4:
		Remove_Unregistered_Events_From_Schedule($application_id);

		Update_Status(NULL, $application_id, 'collections_rework::collections::customer::*root');

		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue('collections_rework')->getNewQueueItem($application_id);
		$qm->moveToQueue($queue_item, 'collections_rework');

		$app =  ECash::getApplicationByID($application_id);
		$flags = $app->getFlags();
		if(!$flags->get('has_fatal_ach_failure'))
		{
			$flags->set('has_fatal_ach_failure');
		}
		
		echo "Action: Put to Collections Rework.", "\n";
		break;

	case 5:
		Update_Status(NULL, $application_id, 'queued::verification::applicant::*root');
		$queue_name = 'verification';
		//$queue_name = 'underwriting';
		//$queue_name = 'verification_react';
		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue($queue_name)->getNewQueueItem($application_id);
		$qm->moveToQueue($queue_item, $queue_name);
		$comment = new Comment();
		$comment->Add_Comment($company_id, $application_id, $agent_id, "Contains VERIFY loan action. Routed to Verification queue for manual funding", "standard");
		echo "To Verification Queue.", "\n";
		break;
	
	case 6;
                $qm = ECash::getFactory()->getQueueManager();
                $queue_item = new ECash_Queues_BasicQueueItem($application_id);
                $qm->removeFromAllQueues($queue_item);
                echo "Action: Dequeued application.", "\n";
                break;

	case 7;
		Remove_Unregistered_Events_From_Schedule($application_id);
		Update_Status(NULL, $application_id, 'cccs::collections::customer::*root');
		$app =  ECash::getApplicationByID($application_id);
		$flags = $app->getFlags();
		if(!$flags->get('has_fatal_ach_failure'))
		{
			$flags->set('has_fatal_ach_failure');
		}
		echo "Action: Status to CCCS, Removed Schedule, Fatal Flag.", "\n";
		break;

	case 8;
		//Remove_Unregistered_Events_From_Schedule($application_id);
		//Update_Status(NULL, $application_id, 'cccs::collections::customer::*root');
		$app =  ECash::getApplicationByID($application_id);
		$flags = $app->getFlags();
		if(!$flags->get('cust_no_ach'))
		{
			$flags->set('cust_no_ach');
		}
		echo "Action: Status to CCCS, Removed Schedule, ACH Flag.", "\n";
		break;

	case 9:
		Update_Status(null, $application_id, array('active::servicing::customer::*root'));
		Complete_Schedule($application_id);
		$comment = new Comment();
		$comment->Add_Comment($company_id, $application_id, $agent_id, "Refi failed. Restored Active", "standard");
		echo "Action: Refi to Active.", "\n";
		break;

	case 10:
		$status_id = 192; // CCCS
		$status_chain = Status_Utility::Get_Status_Chain_By_ID($status_id);
		Update_Status(NULL,$application_id,$status_chain);

		$queue_name = 'cccs';
		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue($queue_name)->getNewQueueItem($application_id);
		$qm->moveToQueue($queue_item, $queue_name);

		$comment = new Comment();
		$comment->Add_Comment($company_id, $application_id, $agent_id,
		"Assembla 77, restored 2014-07-31 application status",
		"standard");

		$app =  ECash::getApplicationByID($application_id);
		$flags = $app->getFlags();
		$flag = "has_fatal_ach_failure";
		$flags->clear($flag);
		$flag = "had_fatal_ach_failure";
		$flags->clear($flag);

		echo "Action: Updated status to ", $status_chain, "\n";
		break;

	case 11:
		$app =  ECash::getApplicationByID($application_id);

		$status_id = 190; // Collections Rework
		$status_chain = Status_Utility::Get_Status_Chain_By_ID($status_id);
		Update_Status(NULL,$application_id,$status_chain);

		$queue_name = 'collections_rework';
		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue($queue_name)->getNewQueueItem($application_id);
		$qm->moveToQueue($queue_item, $queue_name);
		
		//$app->getComments()->add('Assembla 77, restored 2014-07-31 application status', ECash::getAgent()->getAgentId());
		$app->getComments()->add('Assembla 77, previous application status', ECash::getAgent()->getAgentId());
		echo "Action: Updated status to ", $status_chain, "\n";
		break;

	case 12:
		$app =  ECash::getApplicationByID($application_id);
		
		$flags = $app->getFlags();
		$flag = "has_fatal_ach_failure";
		$flags->clear($flag);
		$flag = "had_fatal_ach_failure";
		$flags->clear($flag);
		
		$status_id = 134; // Collections Contact
		$status_chain = Status_Utility::Get_Status_Chain_By_ID($status_id);
		Update_Status(NULL,$application_id,$status_chain);

		$queue_name = 'collections_general';
		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue($queue_name)->getNewQueueItem($application_id);
		$qm->moveToQueue($queue_item, $queue_name);

		$app->getComments()->add('Assembla 77, restored 2014-07-31 application status', ECash::getAgent()->getAgentId());
		echo "Action: Updated status to ", $status_chain, "\n";
		break;

	case 13:
		$app =  ECash::getApplicationByID($application_id);

		$status_id = 137; // Collections New
		$status_chain = Status_Utility::Get_Status_Chain_By_ID($status_id);
		Update_Status(NULL,$application_id,$status_chain);

		Complete_Schedule($application_id);

		$queue_name = 'collections_new';
		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue($queue_name)->getNewQueueItem($application_id);
		$qm->moveToQueue($queue_item, $queue_name);

		/*
		$flags = $app->getFlags();
		$flag = "has_fatal_ach_failure";
		$flags->clear($flag);
		$flag = "had_fatal_ach_failure";
		$flags->clear($flag);
		*/

		$app->getComments()->add('Assembla 77, restored 2014-07-31 application status', ECash::getAgent()->getAgentId());
		echo "Action: Updated status to ", $status_chain, "\n";
		break;

	case 14:
		$app =  ECash::getApplicationByID($application_id);

		$status_id = 123; // Past Due
		$status_chain = Status_Utility::Get_Status_Chain_By_ID($status_id);
		Update_Status(NULL,$application_id,$status_chain);

		Complete_Schedule($application_id);

		$queue_name = 'collections_new';
		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue($queue_name)->getNewQueueItem($application_id);
		$qm->moveToQueue($queue_item, $queue_name);

		/*
		$flags = $app->getFlags();
		$flag = "has_fatal_ach_failure";
		$flags->clear($flag);
		$flag = "had_fatal_ach_failure";
		$flags->clear($flag);
		*/

		$app->getComments()->add('Assembla 77, restored 2014-07-31 application status', ECash::getAgent()->getAgentId());
		echo "Action: Updated status to ", $status_chain, "\n";
		break;

	case 15:
		$app =  ECash::getApplicationByID($application_id);

		$status_id = 20; // Active
		$status_chain = Status_Utility::Get_Status_Chain_By_ID($status_id);
		Update_Status(NULL,$application_id,$status_chain);

		Complete_Schedule($application_id);

		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = new ECash_Queues_BasicQueueItem($application_id);
		$qm->removeFromAllQueues($queue_item);
		
		$flags = $app->getFlags();
		$flag = "has_fatal_ach_failure";
		$flags->clear($flag);
		$flag = "had_fatal_ach_failure";
		$flags->clear($flag);
		

		$app->getComments()->add('Assembla 77, restored 2014-07-31 application status', ECash::getAgent()->getAgentId());
		echo "Action: Updated status to ", $status_chain, "\n";
		break;

	case 16:
		$app =  ECash::getApplicationByID($application_id);

		$status_id = 194; // Canceled
		$status_chain = Status_Utility::Get_Status_Chain_By_ID($status_id);
		Update_Status(NULL,$application_id,$status_chain);

		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = new ECash_Queues_BasicQueueItem($application_id);
		$qm->removeFromAllQueues($queue_item);

		$flags = $app->getFlags();
		$flag = "has_fatal_ach_failure";
		$flags->clear($flag);
		$flag = "had_fatal_ach_failure";
		$flags->clear($flag);

		$app->getComments()->add('Assembla 77, restored 2014-07-31 application status', ECash::getAgent()->getAgentId());
		echo "Action: Updated status to ", $status_chain, "\n";
		break;

	case 17:
		$app =  ECash::getApplicationByID($application_id);

		$status_id = 160; // Deceased Verified
		$status_chain = Status_Utility::Get_Status_Chain_By_ID($status_id);
		Update_Status(NULL,$application_id,$status_chain);

		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = new ECash_Queues_BasicQueueItem($application_id);
		$qm->removeFromAllQueues($queue_item);

		/*
		$flags = $app->getFlags();
		$flag = "has_fatal_ach_failure";
		$flags->clear($flag);
		$flag = "had_fatal_ach_failure";
		$flags->clear($flag);
		*/

		$app->getComments()->add('Assembla 77, restored 2014-07-31 application status', ECash::getAgent()->getAgentId());
		echo "Action: Updated status to ", $status_chain, "\n";
		break;

	case 18:
		$app =  ECash::getApplicationByID($application_id);

		$status_id = 125; // Made Arrangement
		$status_chain = Status_Utility::Get_Status_Chain_By_ID($status_id);
		Update_Status(NULL,$application_id,$status_chain);

		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = new ECash_Queues_BasicQueueItem($application_id);
		$qm->removeFromAllQueues($queue_item);

		/*
		$flags = $app->getFlags();
		$flag = "has_fatal_ach_failure";
		$flags->clear($flag);
		$flag = "had_fatal_ach_failure";
		$flags->clear($flag);
		*/

		$app->getComments()->add('Assembla 77, restored 2014-07-31 application status', ECash::getAgent()->getAgentId());
		echo "Action: Updated status to ", $status_chain, "\n";
		break;

	case 19:
		Set_Standby($application_id, $company_id, 'reschedule');
		echo "Action: Set_Standby reschedule ", "\n";
		break;
	
	case 20:
		Remove_Standby($application_id, 'reschedule');
		echo "Action: Remove_Standby reschedule ", "\n";
		break;

	case 21:
		$app =  ECash::getApplicationByID($application_id);

		Remove_Unregistered_Events_From_Schedule($application_id);

		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = new ECash_Queues_BasicQueueItem($application_id);
		$qm->removeFromAllQueues($queue_item);

		$status_id = 134; // Collections Contact
		$status_chain = Status_Utility::Get_Status_Chain_By_ID($status_id);
		Update_Status(NULL,$application_id,$status_chain);

		$queue_name = 'collections_general';
		$qm = ECash::getFactory()->getQueueManager();
		$queue_item = $qm->getQueue($queue_name)->getNewQueueItem($application_id);
		$qm->moveToQueue($queue_item, $queue_name);

		echo "Action: Updated status to ", $status_chain, "\n";
		break;

	case 22:
		require_once(SQL_LIB_DIR . "do_not_loan.class.php");
		$app =  ECash::getApplicationByID($application_id);
		$ssn = $app->ssn;
		$dnl = ECash::getCustomerBySSN($ssn)->getDoNotLoan();

		//var_dump(ECash::getAgent()->AgentId);
		//var_dump(ECash::getCompany()->company_id);
		//var_dump($dnl->getByCompany(ECash::getCompany()->company_id));
		
		if (!($dnl->getByCompany($dnl->getByCompany(ECash::getCompany()->company_id))))
		{
			$agent_id = ECash::getAgent()->AgentId;
			$do_not_loan_exp = "Hostile ACH return";
			$do_not_loan_category = "other";
			$dnl->set($agent_id, $do_not_loan_exp, $do_not_loan_category);
		}
		
		break;

	case 23:
		//$app = ECash::getApplicationByID($application_id);
		//$affiliations = $application->getAffiliations();
		//$affiliations->expireAll();
		//eCash_AgentAffiliation_Legacy::expireApplicationAffiliations($application_id); //$area = null, $type = null, $agent_id = null
		eCash_AgentAffiliation_Legacy::expireAllApplicationAffiliations($application_id); // $agent_id = null

		$queue_name = 'Agent';
		$qm = ECash::getFactory()->getQueueManager();

		//Remove from queue specified
		$queue = $qm->getQueue($queue_name);
		$queue_item = new ECash_Queues_BasicQueueItem($application_id);
		$queue->remove($queue_item);
		break;

	case 24:
		Check_Inactive($application_id);
		echo "Action: Check_Inactive.", "\n";
		break;

        default:
                echo "No valid action is specified.", "\n";
                break;
}

?>
