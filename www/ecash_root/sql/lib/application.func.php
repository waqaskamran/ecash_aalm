<?php

/*
* Application related Functions -- Moved from Application Query
*
* 06.24.2006 - Folded in Application History functions too. (MarcC)
*/

require_once(SQL_LIB_DIR."app_flags.class.php");
require_once( 'crypt.3.php' );
require_once(ECASH_COMMON_DIR . "/ecash_api/ecash_api.2.php");

/**
* @TODO: This all should come from ECash_Application or the Reference List ApplicationStatusFlat 
*/
function Fetch_Application_Status($application_id)
{
	$application = ECash::getApplicationById($application_id);
	if(! $application->exists())
	{
		throw new ECash_Application_NotFoundException("Unable to locate application '{$application_id}'");
	}

	$status = $application->getStatus();

	$response = array();
	$response['status'] = $status->level0;
	$response['level0'] = $status->level0;
	$response['level1'] = $status->level1;
	$response['level2'] = $status->level2;
	$response['level3'] = $status->level3;
	$response['level4'] = $status->level4;
	$response['level5'] = $status->level5;
	$response['is_watched'] = $application->is_watched;
	$response['is_react'] = $application->is_react;
	$response['olp_process'] = $application->olp_process;
	$response['application_status_id'] = $application->getStatusId();
	$response['status_chain'] = $status->toName();

	return $response;
}

// This function sets the Watch Status Flag in the application table /only/
function Set_Watch_Status_Flag ($application_id, $state='no')
{
	//This is sloppy, but I need to know if it is in a followup status to set priority correctly.
	$followup_statuses = array(
		'follow_up::contact::collections::customer::*root',
		'follow_up::verification::applicant::*root',
		'follow_up::underwriting::applicant::*root'
		);
	$application = ECash::getApplicationById($application_id);
	$current_status = $application->getStatus()->getApplicationStatus();
	$agent_id = ECash::getAgent()->getAgentId();

	if(strtolower($state) != 'yes') { $state = 'no'; }

	if("yes" == $state)
	{
		$qm = ECash::getFactory()->getQueueManager();
		$queue = $qm->getQueue('watch');
		$qi = $queue->getNewQueueItem($application_id);
		if (in_array($current_status, $followup_statuses))
		{
			$qi->Priority = 102;
		}

		$qm->moveToQueue($qi, 'watch');
		$application->setWatchStatus();
	}
	else
	{
		$qm = ECash::getFactory()->getQueueManager();
		$qm->removeFromAllQueues(new ECash_Queues_BasicQueueItem($application_id));
		$application->clearWatchStatus();

		//hack to put in queue for status it currently is
		$engine = ECash::getEngine();
		$engine->executeEvent('APPLICATION_STATUS');
	}
}

/**
 * Gets the previous application_status_id of the current user
 * If there is no previou status, return FALSE.
 *
 * @TODO: This can probably be refactored out.
 */
function Get_Previous_Status($application_id)
{
	$application = ECash::getApplicationById($application_id);
	if(! $application->exists())
	{
		throw new ECash_Application_NotFoundException("Unable to locate application '{$application_id}'");
	}

	$current = $application->getStatus();
	$previous = $application->getPreviousStatus();

	if(! empty($previous->application_status_id) && $previous->application_status_id != $current->application_status_id)
	{
		return $previous->application_status_id;
	}

	return FALSE;
}

function Date_Format_MDY_To_YMD( $date_in )
{
	$d = isset($date_in) ? trim($date_in) : '';
	if ( strlen($d) == 10 )
	{
		$d = substr($d, 6, 4) . '-' . substr($d, 0, 2) . '-' . substr($d, 3, 2);
	}
	return $d;
}

function Format_Model_Data($data)
{
	// Semi ghetto
	foreach( array("day_of_week", "day_of_month_1", "day_of_month_2", "week_1", "week_2", "last_paydate") AS $value )
	{
		if( !isset($data[$value]) || trim($data[$value]) == "" )
		$data[$value] = "NULL";
	}

	return $data;
}

/**
 * Checks the account to see if it is in a 'holding' status
 *
 * This should be replaced with ECash_Application::isInHoldingStatus()
 *
 * @param integer $application_id
 * @return boolean
 */
function In_Holding_Status($application_id)
{
	require_once(SQL_LIB_DIR . "fetch_status_map.func.php");

	$status_map = Fetch_Status_Map();
	$disallowed_statuses = array();

	$disallowed_statuses[] = Search_Status_Map('hold::arrangements::collections::customer::*root', $status_map);
	$disallowed_statuses[] = Search_Status_Map('unverified::bankruptcy::collections::customer::*root', $status_map);
	$disallowed_statuses[] = Search_Status_Map('verified::bankruptcy::collections::customer::*root', $status_map);
	$disallowed_statuses[] = Search_Status_Map('amortization::bankruptcy::collections::customer::*root', $status_map);
	$disallowed_statuses[] = Search_Status_Map('skip_trace::collections::customer::*root', $status_map);

	$db = ECash::getMasterDb();

	$query = "
		SELECT application_status_id, is_watched
		FROM   application
		WHERE  application_id = '$application_id' ";

	$st = $db->query($query);
	$row = $st->fetch(PDO::FETCH_OBJ);

	if ((in_array($row->application_status_id, $disallowed_statuses)) || $row->is_watched == 'yes') 
	{
		return true;
	} 
	else 
	{
		return false;
	}
}

/**
 * Returns all flags for a given application.
 *
 * @param int $application_id
 * @return array
 */
function Fetch_Flags($application_id) 
{
	settype($application_id, 'int');
	$db = ECash::getMasterDb();
	$query = "
		SELECT DISTINCT ft.*
		  FROM
		  	application_flag af
		  	JOIN flag_type ft USING (flag_type_id)
		  WHERE
		  	af.application_id = {$application_id} AND
		  	ft.active_status = 'active' ";

	$st = $db->query($query);

	$flags = array();

	while ($row = $st->fetch(PDO::FETCH_OBJ))
	{
		$flags[$row->name_short] = $row;
	}

	return $flags;
}

/**
 * Determines if a flag exists for a given application
 *
 * @param int $application_id
 * @return bool
 */
function Application_Flag_Exists($application_id, $flag) 
{
	static $app_flags = array();
	if (!isset($app_flags[$application_id])) 
	{
		$app_flags[$application_id] = Fetch_Flags($application_id);
	}
	if (key_exists($flag, $app_flags[$application_id])) 
	{
		return true;
	} 
	else 
	{
		return false;
	}
}

/**
 * Adds a new flag to the application. If the flag already exists it is
 * removed and readded.
 *
 * @param int $application_id
 * @param char $flag
 * @return bool
 */
function Application_Add_Flag($application_id, $flag) 
{
	$application = ECash::getApplicationById($application_id);
	if(! $application->exists())
	{
		throw new ECash_Application_NotFoundException("Unable to locate application '{$application_id}'");
	}

	$flag_type = ECash::getFactory()->getModel('FlagType');
	if(! $flag_type->loadBy(array('name_short' => $flag)))
	{
		ECash::getLog()->Write("Flag type $flag does not exist.");
		return FALSE;
	}

	return $application->getFlags()->set($flag);
}

function Application_Remove_Flag($application_id, $flag) 
{
	ECash::getLog()->Write("[Application: $application_id] Removing $flag flag");
	$application = ECash::getApplicationById($application_id);
	return $application->getFlags()->clear($flag);
}

//mantis:1472
function Get_Bank_Phone($aba)
{
	$db = ECash::getMasterDb();

	$query = "
					SELECT
						Institution_Name_Full,
						ACH_Contact_Area_Code,
						ACH_Contact_Phone_Number,
						ACH_Contact_Extension
					FROM
						aba_list
					WHERE
						Routing_Number_MICR_Format = {$db->quote($aba)} LIMIT 1";

	$values = array();
	$result = $db->query($query);
	return $result->fetchAll(PDO::FETCH_OBJ);
}

/**
 * Update the APR for an application - Currently only used by Agean
 *
 * @param string $payment_date
 * @param string $fund_date
 * @param int $fund_amount
 * @param int $application_id
 * @param int $loan_type_id
 * @param string $company
 * @return string $apr or false on failure
 */
function Update_APR($payment_date, $fund_date, $fund_amount, $application_id, $loan_type_id, $company)
{
	$application = ECash::getApplicationById($application_id);
	$rate_calc = $application->getRateCalculator();
	$apr = $rate_calc->getAPR(strtotime($fund_date), strtotime($payment_date));

	$application->apr = $apr;
	$application->save();

	return $apr;
}

/**
 * Find the last collection status, then the first occurance of that status in
 * the status history.
 *
 * @param <type> $application_id
 * @return <FALSE | array>
 */
function Get_Last_Collections_Status_Changed_Info($application_id)
{
	$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');

	$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
	$history_list = $app_client->getApplicationStatusHistory($application_id)->item;
	$reversed_history_list = array_reverse($history_list);

	/**
	 * Iterate through the status history from the end to find
	 * the last collections status they were in.
	 */
	foreach($reversed_history_list as $rec)
	{
		$status_id = $asf->toId($rec->applicationStatus);
		$status = $asf[$status_id];

		if($status->level1 == 'arrangements')
			continue;

		if(
			(
				$status->level1 == 'collections'
				&&
				(in_array($status->level0, array('new','contact','collections_rework','cccs')))
			)
			||
			($status->level2 == 'collections' && $status->level1 == 'contact')
			||
			($status->level0 == 'pending' && $status->level1 == 'external_collections')
		)
		{
			break;
		}
	}

	/**
	 * If we haven't found a collection status,
	 * return FALSE.
	 */
	if(empty($status_id))
		return FALSE;

	/**
	 * Now go through the status history from the start
	 * to find the first time the customer was in that status
	 */
	foreach($history_list as $rec)
	{
		$first_status_id = $asf->toId($rec->applicationStatus);
		if($first_status_id == $status_id)
		{
			return array('application_status_id' => $status_id, 'date_created' => $rec->dateCreated);
		}
	}
}

function Get_Previous_Due_Date($application_id)
{
	$db = ECash::getMasterDb();

	$query = "
		SELECT
			MAX(tr.date_effective) AS last_due_date
		FROM
			transaction_register tr
		WHERE
			tr.application_id = {$application_id}
		AND
			tr.transaction_status IN ('complete', 'failed')
		AND
			tr.date_effective <= CURRENT_DATE()
		LIMIT 
			1
	";
			
	$result = $db->querySingleRow($query, NULL, PDO::FETCH_ASSOC);

	return $result['last_due_date'];
}
