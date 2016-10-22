<?php
/**
php data_fix_info.php <application_id> <info_id from switch statement>, for example, data_fix_info.php 987654321 1 (to Fetch_Balance_Information)
*/
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');
require_once(SQL_LIB_DIR.'scheduling.func.php');
require_once CUSTOMER_LIB . 'failure_dfa.php';

$db = ECash::getMasterDb();
$company_id = 1;
$agent_id = 1;
$server = ECash::getServer();
$server->company_id = $company_id;

if ($_SERVER['argc'] != 3)
{
echo "Usage: php data_fix_info.php <application_id> <info_id from switch statement>, for example, data_fix_info.php 987654321 1 (to Fetch_Balance_Information).\n";
exit(1);
}

$application_id = $_SERVER['argv'][1];
$action = $_SERVER['argv'][2];

echo $application_id, " ", $action, "\n";

switch($action)
{
        case 1:
                $bi = Fetch_Balance_Information($application_id);
                var_dump($bi);
                echo "\nInfo: Fetch_Balance_Information.", "\n";
                break;

	case 2:
		$schedule = Fetch_Schedule($application_id);
		//var_dump($schedule);
		$status = Analyze_Schedule($schedule,false);
		echo "Completed_SC_Credits:", $status->Completed_SC_Credits, "\n";
		echo "Completed_SC_Debits:", $status->Completed_SC_Debits, "\n";
		echo "Failed_Princ_non_reatts:", $status->Failed_Princ_non_reatts, "\n";
		echo "Completed_Reatts:", $status->Completed_Reatts, "\n";
		echo "past_due_balance:", $status->past_due_balance, "\n";
		//var_dump(count($status->fail_set));
		//var_dump(count($status->max_reattempt_count));
		break;

	case 3:
		$db = ECash::getSlaveDb();
		$application =  ECash::getApplicationByID($application_id);
		$sh = new ECash_Application_StatusHistory($db, $application);
		$shl = $sh->getStatusHistoryList();
		foreach ($shl as $sh)
		{
			echo "Date: ", $sh->date_created, ", App Status Id: " , $sh->application_status_id, ", Agent: ", $sh->agent_id, "\n";
		}

		$shl = $application->getStatusHistory();
		foreach ($shl as $sh)
		{
			echo $sh->applicationStatus, "\n";	
		}

		//var_dump($sh);
		break;

	case 4:
		$td = Get_Transactional_Data($application_id);
		//var_dump($td->last_payment_date);
		var_dump($td->info);
		echo "\nInfo: Get_Transactional_Data.", "\n";
		break;

	case 5:
		$app = ECash::getApplicationById($application_id);
		var_dump($app->day_of_month_1);
		var_dump($app->day_of_month_2);
		var_dump($app->income_frequency);
		break;

	case 6:
		$info = Get_Last_Collections_Status_Changed_Info($application_id);
		var_dump($info);
		$date = $info['date_created'];
		$status = explode('::', Status_Utility::Get_Status_Chain_By_ID($info['application_status_id']));
		var_dump($date);
		var_dump($status);
		break;

	case 7:
		$qm = ECash::getFactory()->getQueueManager();
		//$col_queue = $qm->getQueue('collections_new');
		$col_queue = $qm->getQueue('collections_rework');
		$col_table = $col_queue->getQueueEntryTableName();
		var_dump($col_table);
		break;

	case 8:
		$holidays  = Fetch_Holiday_List();
		$pdc       = new Pay_Date_Calc_3($holidays);
		$biz_rules = new ECash_BusinessRulesCache($db);

		$loan_type_id = $biz_rules->Get_Loan_Type_For_Company('mls', 'company_level');
		var_dump($loan_type_id);
		$rule_set_id = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
		var_dump($rule_set_id);
		$rules = $biz_rules->Get_Rule_Set_Tree($rule_set_id);
		//$days = $rules['collections_processes']['days_until_general'];
		$days = $rules['collections_processes']['days_until_rework'];
		//$days = $rules['collections_processes']['days_until_second_tier'];
		var_dump($days);

		//$app = ECash::getApplicationById($application_id);
		//$business_rules = $app->getBusinessRules();
		//$days = $business_rules['collections']['days_until_general'];
		//var_dump($days);
		break;

	case 9:
		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);
		$days = 30;
		$end_stamp = $pdc->Get_Business_Days_Backward(date('Y-m-d'), $days);

		echo "Get_Business_Days_Backward, ", $days, " days: ", $end_stamp, "\n";
		break;

	case 10:
		$document_list_model = ECash::getFactory()->getModel('DocumentList');
		$document_list_model->loadBy(array('name_short' => 'Loan Document', 'company_id' => $company_id));
		$document_list_id = $document_list_model->document_list_id;

		$document_model = ECash::getFactory()->getModel('Document');
		$document_array = $document_model->loadAllBy(array('application_id' => $application_id,
		'document_list_id' => $document_list_id,
		'document_method' => 'olp',
		'document_event_type' => 'sent',));
		if ($document_array->count() > 0)
			$loan_doc_found = TRUE;
		if ($loan_doc_found) echo "OLP Loan Doc found ", "\n";
		else echo "OLP Loan Doc NOT found ", "\n";
		break;

        default:
		$bundling_enabled = Company_Rules::Get_Config('ach_bundling');
		var_dump($bundling_enabled);
                echo "No valid action is specified.", "\n";
                break;
}

?>
