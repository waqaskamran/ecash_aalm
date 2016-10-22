#!/usr/bin/php
<?php
/*
 * This script will register an event to the transaction register.  This is only safe for 
 * NON-ACH items in a Live environment.  ACH or QC items will not send the transaction out
 * or create the ach or ecld table entries, etc.  It should be safe for testing purposes
 * in RC environments though.
 *
 * This utility uses the current configuration based on the ../www/config.php file.
 */

	require_once dirname(realpath(__FILE__)) . '/../www/config.php';
    require_once SQL_LIB_DIR . 'scheduling.func.php';
	
	// Mandatory: Event_ID
	if($argc > 1) 
	{ 
		$event_id = $argv[1]; 
	
		$log = new Applog('repair', 5000000, 20);
	}
	else
	{
		Usage($argv);
	}
	
	// Optional Date
	if($argc > 2) 
	{
		$date_current = validate_date($argv[2]);
	}
	else
	{
        $date_current = date("Y-m-d");
	}

	try 
	{
		$db = ECash::getMasterDb();
		
		$query = "SELECT  application_id
                    FROM    event_schedule
                    WHERE   event_schedule_id = '{$event_id}'
                    ";

		$application_id = $db->querySingleValue($query);
		echo "Found application id {$application_id}\n";
                
 		echo "Registering Event ID: $event_id to Transaction Register for $date_current\n";
		$tids = Record_Scheduled_Event_To_Register_Pending($date_current, $application_id, $event_id);
	}
	catch (Exception $e) {
		echo $e->getMessage();
		echo $e->getTrace();
		die();
	}
	echo "Returned the following transaction ID's: ";
	foreach($tids as $tid) { echo "$tid "; }
	echo "\n";
	
	function Usage($argv)
	{
		echo "Usage: {$argv[0]} [event_schedule_id] [date]\n";
		exit;
	}
	
	/**
	 * Very simple date validation.. Returns a date in 'Y-m-d' format
	 * if valid, else dies.
	 *
	 * @param string $date
	 * @return string
	 */
	function validate_date($date)
	{
		if($unixtime = strtotime($date))
		{
			return date('Y-m-d', $unixtime);
		}
		else
		{
			die("Unable to use this date!  $date\n");
		}
		
	}