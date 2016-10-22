<?php
require_once(SQL_LIB_DIR . "scheduling.func.php");

/**
 * Takes an array of objects with event schedule and transaction data and puts
 * the relevant info into the _SESSION['Transaction_Data'] session varaible.
 * This will be cross-checked against in any write methods to the Transaction_Register
 * to make sure the modification times for any of these transactions hasn't changed since
 * the agent last viewed it.
 *
 * @param string  $application_id the applicant's application_id
 * @param array   $schedule Array of MySQL_Row objects with transaction schedule data
 */
function Set_Transaction_Lock_Info($application_id, $schedule)
{
	// Remove any previous info
	unset($_SESSION['LOCK_LAYER']['Transaction_Data']);

	if (count($schedule) > 0)
	{
		$_SESSION['LOCK_LAYER']['Transaction_Data'][$application_id]['HAS_TRANSACTIONS'] = 'yes';
		foreach($schedule as $row)
		{
			
			$_SESSION['LOCK_LAYER']['Transaction_Data'][$application_id][$row->event_schedule_id][$row->type_id]['modification_time'] = $row->date_modified;
		}
	}
	else
	{
		$_SESSION['LOCK_LAYER']['Transaction_Data'][$application_id]['HAS_TRANSACTIONS'] = 'no';
	}
	//$this->log->Write("[Agent:{$_SESSION['Server_state']['agent_id']}] Set Transaction_Data Lock Layer for Application ID: {$application_id}");
}
	
/**
 * Compares the Session LOCK_LAYER Transaction Schedule with what's currently in the database
 *
 * @param string  $application_id the applicant's application_id
 * @return bool   false if there aren't any changes, true if there are
 */
function Check_For_Transaction_Modifications (DB_Database_1 $db, $log, $application_id)
{
	$current_schedule = Fetch_Schedule($application_id);

	// If we even have this applicant in the LOCK_LAYER...
	if($_SESSION['LOCK_LAYER']['Transaction_Data'][$application_id] != NULL)
	{
		// If they have transactions, check them
		if($_SESSION['LOCK_LAYER']['Transaction_Data'][$application_id]['HAS_TRANSACTIONS'] == 'yes')
		{
			foreach($current_schedule as $event)
			{
				if($_SESSION['LOCK_LAYER']['Transaction_Data'][$application_id][$event->event_schedule_id] == NULL)
				{
					$log->Write('TransModCheck: Transaction Register IDs dont match up', LOG_WARNING);
					return true; // Event doesn't exist in previous schedule
				}
				else
				{
					if ($event->date_modified != $_SESSION['LOCK_LAYER']['Transaction_Data'][$application_id][$event->event_schedule_id][$event->type_id]['modification_time'])
					{			

						$log->Write(var_export("Session Date: " . $_SESSION['LOCK_LAYER']['Transaction_Data'][$application_id][$event->event_schedule_id][$event->type_id]['modification_time'], true));
						$log->Write(var_export("Event Date: " . $event->date_modified, true));
					   	$log->Write('TransModCheck: Dates do not match up.', LOG_WARNING);
					   	return true; // Dates do not match
					}
				}
			}
			// Everything matched up...
			return false;
		}
		else
		{
			// If they didn't have transactions before, check to see if they do now
			if((is_array($current_schedule)) && (count($current_schedule) > 0))
			{
				$log->Write('TransModCheck: Didnt have transaction data before, but now we do.', LOG_WARNING);
				return true;
			}
			else
			{
				return false;
			}
		}
	}
	else
	{		
		$log->Write('TransModCheck: Applicant # ' . $application_id . ' isnt in lock layer', LOG_NOTICE);
		return false; // Applicant isn't in the lock layer
	}
}
	
/**
 * Checks the version in the session that's set when the application info is read with what's
 * currently available in the App Service.
 *
 * @param string  $application_id the applicant's application_id
 * @return bool   false if there aren't any changes, true if there are
 */
function Check_For_Application_Modifications ($application_id)
{
	$as = ECash::getFactory()->getWebServiceFactory()->getWebService('application');

	/**
	 * The logic between these checks is a little backwards.
	 * 
	 * versionCheck returns TRUE if there are no changes, FALSE if there are.
	 * 
	 */
	return ($as->versionCheck($application_id) == TRUE) ? FALSE : TRUE; 
}

