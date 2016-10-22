<?php

function Main($target_dates)
{
    global $server;
	$ach = ACH::Get_ACH_Handler($server, 'return');

    $ecash_engine = array_shift($target_dates);
    $company_code = array_shift($target_dates);
    $log_subsection = array_shift($target_dates);
    $self = array_shift($target_dates);

    $reschedule_apps = FALSE;

    if(!$target_dates)
    {
		die("Invalid usage, please provide a list of dates as parameters\n");
    }

    foreach($target_dates as $target_date)
    {
        ECash::getLog()->Write(__FILE__.":".'$Revision$'.":".__LINE__.":".__METHOD__."(): Attempting $target_date");

        if($ach->Fetch_ACH_File("returns", $target_date))
        {
			print("Received Return file for $target_date\n");
			$ach->Process_ACH_Returns($target_date, $target_date);
			$reschedule_apps = FALSE;
		}

		if($ach->Fetch_ACH_File("corrections", $target_date))
		{
			print("Received Corrections file for $target_date\n");
			$ach->Process_ACH_Corrections($target_date, $target_date);
        }
    }

    if($reschedule_apps)
    {
        ECash::getLog()->Write(__FILE__.":".'$Revision$'.":".__LINE__.":".__METHOD__."(): Rescheduling all apps that need it");
        print("Rescheduling apps...\n");

      //$ach->Reschedule_Apps();
    }
}

?>