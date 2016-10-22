<?php

/*                 MAIN processing code                */

function Main()
{	
	global $server;
	$ach = new ACH($server);
	global $_BATCH_XEQ_MODE;

	ECash::getLog()->Write(__FILE__.": Executing ACH returns and corrections processing. [Mode: {$_BATCH_XEQ_MODE}] [Company: {$server->company}]");
	
	// Change this date to override and force
	// the script to run on another day
	$today = date("Y-m-d");
	
	// A few timer symbols...
	$ar_timer = "({$today}) Process_ACH_Returns";
	$ac_timer = "({$today}) Process_ACH_Corrections";
	$ra_timer = "({$today}) Reschedule Apps";

	$has_returns     = false;
	$has_corrections = false;
	
	// The processes states are logged in the database in the process_log table
	// using business_day for the run date.
	
	// First we want to check for the returns and corrections files
	$has_returns = Check_ACH_File('returns', $today);
	$has_corrections = Check_ACH_File('corrections', $today);
	// If the returns and corrections have been received, 
	// then we'll go ahead and start processing them
	if($has_returns && $has_corrections)
	{
		// Returns
		$server->timer->Timer_Start($ar_timer);
		$state = $ach->Check_Process_State("ach_returns", $today);
		if(($state != 'completed') && ($state != 'started'))
		{
			$ach->Set_Process_Status("ach_returns", 'started', $today);
			$ach->Process_ACH_Returns($today, $today);
		}
		$server->timer->Timer_Stop($ar_timer);
		

		// Corrections
		$server->timer->Timer_Start($ac_timer);
		$state = $ach->Check_Process_State("ach_corrections",$today);
		if(($state != 'completed') && ($state != 'started'))
		{
			$ach->Set_Process_Status("ach_corrections", 'started', $today);
			$ach->Process_ACH_Corrections($today, $today);
		}
		$server->timer->Timer_Stop($ac_timer);
	
		// If returns & corrections have processed alright,
		// run the rescheduling.	
		$ret_state = $ach->Check_Process_State("ach_returns", $today);
		$cor_state = $ach->Check_Process_State("ach_corrections", $today);
		
		if($ret_state == 'completed' && $cor_state == 'completed')
		{
			// Finally we'll let the rescheduling of the apps happen
			$server->timer->Timer_Start($ra_timer);
			$state = $ach->Check_Process_State("ach_reschedule", $today);
			if(($state != 'completed') && ($state != 'started'))
			{
				$ach->Set_Process_Status("ach_reschedule", 'started', $today);
				$ach->Reschedule_Apps();
			}
			$server->timer->Timer_Stop($ra_timer);
		}
	}
	
}

// Simple function to check to see if a report has run properly and
// sets the appropriate states then returns a boolean value based 
// on whether or not the report is available in the databse for
// further processing.
function Check_ACH_File($type, $date)
{
	global $ach;
	$response = false;
	
	$state = $ach->Check_Process_State("ach_fetch_{$type}",$date);
	if($state != 'completed' && $state != 'started') 
	{
		$ach->Set_Process_Status("ach_fetch_{$type}", 'started', $date);

		if($ach->Fetch_ACH_File($type, $date))
		{
			$response = true;
			$ach->Set_Process_Status("ach_fetch_{$type}", 'completed', $date);
		}
		else
		{
			$ach->Set_Process_Status("ach_fetch_{$type}", 'failed', $date);
		}
	} else 
	{
		$response = true;
	}
	return $response;
}

?>
