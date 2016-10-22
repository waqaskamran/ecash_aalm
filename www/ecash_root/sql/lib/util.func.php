<?php
require_once(LIB_DIR . "business_rules.class.php");
require_once(dirname(realpath(__FILE__))."/../../www/config.php");
require_once(COMMON_LIB_DIR."/applog.1.php");

function Check_Inactive($application_id) 
{
	require_once(LIB_DIR.'AgentAffiliation.php');
	require_once(SQL_LIB_DIR . "scheduling.func.php");

	// Exclude these terminating statuses
	$balance_info = Fetch_Balance_Information($application_id);
	$log = get_log();
	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Checking Inactive eligibility, Total Balance: {$balance_info->total_balance}");
	
	// JUST for QC Sent
	// If Posted <= 0 then Update Status to Inactive Paid
	// Otherwise leave in QC Sent
	//$as = $_SESSION['current_app']; // App Status can be grabbed from here	
	$as = Fetch_Application_Status($application_id);	//Grab the application status!
	
	$application = ECash::getApplicationByID($application_id);
	$business_rules = new ECash_Business_Rules(ECash::getMasterDb());
	$rules = $business_rules->Get_Rule_Set_Tree($application->rule_set_id);

	// Check for terminating statuses
	if (($as['level0'] == 'verified'  && $as['level1'] == 'deceased' && $as['level2'] == 'collections' && $as['level3'] == 'customer' && $as['level4'] == '*root') ||
		($as['level0'] == 'settled'   && $as['level1'] == 'customer' && $as['level2'] == '*root') ||
		($as['level0'] == 'write_off' && $as['level1'] == 'customer' && $as['level2'] == '*root'))
	{
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Application is already in a terminating status, skipping setting to inactive.");
		return FALSE;
	}
		
	//If its funding failed, we don't care how much it owes, it can't be inactive. [Intacash #13063]
	if ($balance_info->total_balance <= 0 && $balance_info->total_pending <= 0 && $as['level0'] != 'funding_failed')
	{
		// Only used for AALM collections stuff
        $info = Get_Last_Collections_Status_Changed_Info($application_id);

        $date          = $info['date_created'];
        $status        = Status_Utility::Get_Status_Chain_By_ID($info['application_status_id']);

		if ($as['level1'] == 'external_collections' && !Has_Settled_Transaction($application_id))
		{
			Update_Status(NULL, $application_id, array("recovered","external_collections","*root"));
		} 
		// If they have a discount, and a special business rule. they settled.
		else if ((Application_Has_Discount($application_id) && ((isset($rules['settlement_offer']['has_arranged_settlements'])) && $rules['settlement_offer']['has_arranged_settlements'] == 'yes'))
			|| Has_Settled_Transaction($application_id))
		{
			Update_Status(NULL, $application_id, array("settled","customer","*root"));
		}
		// AALM #13633, if they're in, or previously were in the collections_rework process, if they pay off their balance without a discount
		// the status is changed to Inactive Internal Recovered
		else if (($info != FALSE) && $status == 'collections_rework::collections::customer::*root')
		{
			Update_Status(NULL, $application_id, array("internal_recovered", "external_collections", "*root"));
		}
		else 
		{
			Update_Status(NULL, $application_id, array("paid","customer","*root"));
		}

		$application = ECash::getApplicationById($application_id);
		$affiliations = $application->getAffiliations();
		$affiliations->expireAll();
		Remove_Unregistered_Events_From_Schedule($application_id);
		$queue_manager = ECash::getFactory()->getQueueManager();
		$queue_item = new ECash_Queues_BasicQueueItem($application_id);
		$queue_manager->getQueueGroup('automated')->remove($queue_item);
		
		// delete from my queue if not follow up
		$agent_queue_reason_model = ECash::getFactory()->getReferenceModel('AgentQueueReason');
		$agent_queue_reason_model->loadBy(array('name_short'=>'follow up',));
		$agent_queue_reason_id = $agent_queue_reason_model->agent_queue_reason_id;
		
		if ($agent_queue_reason_id)
		{
			$agent_queue_model = ECash::getFactory()->getModel('AgentQueueEntry');
			$loaded = $agent_queue_model->loadBy(array('related_id'=>$application_id,
								   'agent_queue_reason_id'=>$agent_queue_reason_id,));
			if (!$loaded)
			{
				$queue_name = 'Agent';
				$qm = ECash::getFactory()->getQueueManager();
				$queue = $qm->getQueue($queue_name);
				$queue_item = new ECash_Queues_BasicQueueItem($application_id);
				$queue->remove($queue_item);
			}
		}

		return TRUE;
	}

	return FALSE;
}

/*
* Has_Settled_Transaction
* Determines if an application has one of the chosen settled transactions are are completed
*
*@param string appplication_id
*@return bool 
*/
function Has_Settled_Transaction($application_id)
{
	$settled_transactions = ECash::getConfig()->SETTLED_TRANSACTIONS;
	if(empty($settled_transactions))
		$settled_transactions = array();
	$schedule = Fetch_Schedule($application_id);
	foreach($schedule as $transaction)
	{
		if(in_array($transaction->type, $settled_transactions) && $transaction->status == 'complete')
		{
			return true;
		}
	}
	return false;
}
function Get_Collections_Agents($company_id)
{
	static $ids;
	
	if(! empty($ids)) 
	{
		return $ids;
	}
	
	$ids = array();
	
	$query = "
		SELECT agent_id, name_first, name_last 
		FROM agent a
		JOIN agent_access_group AS aag USING (agent_id)
		JOIN access_group_control_option AS agco USING (access_group_id)
		JOIN control_option AS co USING (control_option_id)
		WHERE co.name_short = 'populate_collections_agent'
		AND company_id = {$company_id}
		ORDER BY name_first ASC, name_last ASC
	";
	
	$db = ECash::getMasterDb();
	$st = $db->query($query);
	
	while (($row = $st->fetch(PDO::FETCH_OBJ)) !== FALSE)
	{
		$ids[$row->agent_id] = ucfirst($row->name_first) . " " . ucfirst($row->name_last);
	}
	
	return $ids;
}


function Get_All_Agents($company_id)
{
	static $ids;
	
	if(! empty($ids)) 
	{
		return $ids;
	}

	$db = ECash::getMasterDb();
	$ids = array();
	
	$query = "
		SELECT DISTINCT agent_id, name_first, name_last 
		FROM agent a
		JOIN agent_access_group AS aag USING (agent_id)
		WHERE company_id = {$company_id} AND
		a.system_id = 3 AND
		a.active_status = 'active'
		ORDER BY name_first ASC, name_last ASC
	";
		
	$st = $db->query($query);
	while (($row = $st->fetch(PDO::FETCH_OBJ)) !== FALSE)
	{
		$ids[$row->agent_id] = ucfirst($row->name_first) . " " . ucfirst($row->name_last);
	}
	
	return $ids;
}

function Load_Reverse_Map($company_id = 0)
{
	static $reverse_map;
	
	if (empty($reverse_map)) 
	{
		$db = ECash::getMasterDb();

		$query = "
		SELECT et.event_type_id, tt.name_short, tt.company_id
		FROM event_transaction et, transaction_type tt 
		WHERE et.transaction_type_id = tt.transaction_type_id
		";

		$reverse_map = array();
		$st = $db->query($query);
		
		while (($row = $st->fetch(PDO::FETCH_OBJ)) !== FALSE)
		{
			$reverse_map[$row->company_id][$row->name_short] = $row->event_type_id;
		}
	}
	
	return $reverse_map[$company_id];
}


function Load_Fatal_Codes()
{
	static $fatal_codes;
	
	if(empty($fatal_codes))
	{
		$db = ECash::getMasterDb();
	
		$query = "
		SELECT ach_return_code_id, name_short, name
		FROM ach_return_code
		WHERE is_fatal = 'yes'";

		$fatal_codes = array();
		$st = $db->query($query);
		
		while (($row = $st->fetch(PDO::FETCH_OBJ)) !== FALSE)
		{
			$fatal_codes[$row->ach_return_code_id] = $row;
		}
	}

	return $fatal_codes;
}


function Load_ACH_Event_Types($company_id = NULL)
{
	static $ach_event_types;
	
	if(isset($company_id))
	{
		$company_limit = "AND tt.company_id = {$company_id}";
	}
	else
	{
		$company_id = 0;
		$company_limit = "";
	}
	
	if(empty($ach_event_types[$company_id]))
	{	
		$ach_event_types = array();

		$query = "
				SELECT DISTINCT 
					et.event_type_id 
				FROM 
					event_transaction et, 
					transaction_type tt 
				WHERE
						et.transaction_type_id	= tt.transaction_type_id 
					AND tt.clearing_type		= 'ach' 
					{$company_limit}
		";

		$db = ECash::getMasterDb();
		$st = $db->query($query);
		
		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$ach_event_types[$company_id][] = $row->event_type_id;
		}
	}
	
	return $ach_event_types[$company_id];
}


function Has_Fatal_ACH_Codes($application_id)
{
	$fatal_codes = Load_Fatal_Codes();
	$keys = array_keys($fatal_codes);
	$fcsql = "(".implode(",", $keys).")";

	$query = "
		SELECT count(*) as 'count'
		FROM ach
		WHERE application_id = {$application_id}
		AND ach_return_code_id IN {$fcsql}";
		
	return (ECash::getMasterDb()->querySingleValue($query) > 0);
}


function Fetch_Holiday_List(DB_Database_1 $db = NULL)
{
	static $holiday_list;
	
	if ($db == NULL)
	{
		$db = ECash::getMasterDb();
	}

	if(empty($holiday_list))
	{
		$query = "
			SELECT  holiday
			FROM    holiday
			WHERE   active_status = 'active'";
		
		$holiday_list = $db->querySingleColumn($query);
	}
	
	return $holiday_list;
}

//asm 80
function fetchAchProviders()
{
        static $ach_provider_list;

        if(empty($ach_provider_list))
        {
                $query = "
                        SELECT
                                date_created,
                                ach_provider_id,
                                name_short,
                                name,
                                active_status
                        FROM
                                ach_provider
                        -- WHERE active_status = 'active'
                        ORDER BY ach_provider_id
                ";

                $db = ECash::getMasterDb();
                $st = $db->query($query);
                $ach_provider_list = $st->fetchAll(PDO::FETCH_OBJ);
        }

        return $ach_provider_list;
}

function Application_Exists($application_id)
{
	$query = "
		SELECT count(*) as 'count'
		FROM application
		WHERE application_id = {$application_id}
	";
	
	return (ECash::getMasterDb()->querySingleValue($query) > 0);
}


function Fetch_Full_Holiday_List()
{
	static $holiday_list;

	if(empty($holiday_list))
	{
		$query = "
			SELECT  holiday, name
			FROM    holiday
			WHERE   active_status = 'active'
			ORDER BY holiday";
	
		$db = ECash::getMasterDb();
		$st = $db->query($query);
		$holiday_list = $st->fetchAll(PDO::FETCH_OBJ);
	}
	return $holiday_list;
}


function get_log($subdir = 'main') 
{

	if (!isset($GLOBALS['get_log()'])) 
	{
		$GLOBALS['get_log()'] = array();
	}

	if (!isset($GLOBALS['get_log()'][$subdir])) 
	{
		$GLOBALS['get_log()'][$subdir] = 
			new Applog(APPLOG_SUBDIRECTORY.'/'.$subdir, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT);
	}

	return ($GLOBALS['get_log()'][$subdir]);
}


?>
