<?php
/*
 *  This is the new return file processor for QuickCheck Return files
 *  from RDM Corp. that come in the new XML file format.  The script 
 *  will attempt to find the last successful run date and then search
 *  for any files for those days up to the current day.  If there are 
 *  multiple files available for a given day, it will still process them.
 *
 *
 * @author Brian Ronald <brian.ronald@sellingsource.com>
 * @requires - to be run from the ecash_engine
 * @todo - Nothing that I'm aware of at this point.
 */

function Main()
{
	require_once(SERVER_MODULE_DIR."collections/quick_checks.class.php");

	global $server;
	global $_BATCH_XEQ_MODE;
	$qc = new Quick_Checks($server);
	$db = ECash::getMasterDb();
	$log = $server->log;
	
	$today = date("Y-m-d");	
	$qc_returns_timer  = "({$today}) Process_Quickcheck_Returns";

	// Start with today
	$run_dates = array();
	
	// Get the last successful run and if it's more
	// than a day since today then fill in the days
	// up to the current day
	$last_run = Get_Last_Run('qc_process_returns');
	$last_run = strtotime($last_run);

	while(strtotime("+1 day", $last_run) <= strtotime(date("Y-m-d")))
	{
		$last_run = strtotime("+1 day", $last_run); // Advance the day
		$run_dates[] = date('Ymd', $last_run); // Add to date list
	}

	$server->timer->Timer_Start($qc_returns_timer);
	foreach($run_dates as $run_date) 
	{
		try 
		{
			$pid = Set_Process_Status($db, $server->company_id, 'qc_process_returns', 'started', date('Y-m-d', strtotime($run_date)));
			$return_files = Fetch_Return_File($run_date);
			if($return_files != false) 
			{
				foreach ($return_files as $file) 
				{
					$log->Write("QC: Processing $file");
					$qc->Process_Return_File($file);
				}

				Set_Process_Status($db, $server->company_id, 'qc_process_returns', 'completed', date('Y-m-d', strtotime($run_date)), $pid);
			} 
			else 
			{
				// No files found for this day, mark it as a failure.
				Set_Process_Status($db, $server->company_id, 'qc_process_returns', 'failed', date('Y-m-d', strtotime($run_date)), $pid);
			}
		}
		catch (Exception $e) 
		{
			$log->Write("QC: Exception!  {$e->getMessage()}");
			Set_Process_Status($db, $server->company_id, 'qc_process_returns', 'failed', date('Y-m-d', strtotime($run_date)), $pid);
		}
	}
	$server->timer->Timer_Stop($qc_returns_timer);
}

// Find return files in the QC_RETURN_FILE_DIR for a given day
// Returns an array of filenames.
function Fetch_Return_File($date)
{
	global $server;
	$log = $server->log;
	$files = array(); // Array of files that match our regexp.
	
	if(! $date) { $date = date("Ymd"); }

	$owner_code = ECash::getConfig()->QC_OWNER_CODE;
	$owner_code = ltrim(substr($owner_code,3), '0');
	
	// The filename pattern to match with
	$file_pattern = "/FinalChargebackReturnsFile_$owner_code-$date(\d{6})_FastCashFTPSServer.xml/i";
		
	if((file_exists(QC_RETURN_FILE_DIR) && $dh = opendir(QC_RETURN_FILE_DIR)))
	{
		while(false !== ($file = readdir($dh)))
		{
			if(preg_match($file_pattern, $file))
			{
				$log->Write("QC: Found $file");
				$files[] = QC_RETURN_FILE_DIR . "$file";
			}
		}
		closedir($dh);
	}
	else
	{
		$log->Write("QC: Could not open " .QC_RETURN_FILE_DIR);
		echo "Could not open ".QC_RETURN_FILE_DIR."\n";
		return false;
	}
	
	return $files;
}

// Try to get the last successful process_run date
// Returns a date in 'm/d/Y' format.
function Get_Last_Run($process_type)
{
	global $server;
	$db = ECash::getMasterDb();

	$query = "
		SELECT 
			max(business_day) as last_run_date
		FROM 
			process_log
		WHERE
				step	 = '{$process_type}'
			AND	state	 = 'completed'
			AND company_id	 = {$server->company_id} ";

	$result = $db->query($query);
	$row = $result->fetch(PDO::FETCH_OBJ);

	$last_date = $row->last_run_date;
	
	if($last_date === null)
		return(date('m/d/Y', strtotime("-1 day")));
	
	return($last_date);
}
