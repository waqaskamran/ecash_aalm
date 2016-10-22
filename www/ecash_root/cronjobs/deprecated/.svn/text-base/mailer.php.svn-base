<?php

/*                 MAIN processing code               */

function Main()
{
	global $server;
	$ach = new ACH($server);
	global $co;
	global $_BATCH_XEQ_MODE;
	$log = $server->log;
	$db = ECash::getMasterDb();
	$company_id = $server->company_id;

	require_once(LIB_DIR."common_functions.php");
	require_once(SQL_LIB_DIR. "util.func.php");
	
	$today = date("Y-m-d");	

	// First make sure we haven't run today already
	$run_state = Check_Process_State($db, $company_id, "nsf_mailer", $today);
	if ($run_state == 'completed') 
	{
		echo "Ran already today.\n";
		return true;
	}
	// Make sure we only continue if the rescheduling is done.
	$reschedule_state = Check_Process_State($db, $company_id, "ach_reschedule", $today);

	// A few timer symbols...
	$insufficient_funds_timer = "({$today}) Insufficient_Funds_Mailer";
	
	$server->timer->Timer_Start($insufficient_funds_timer);

	try 
	{
			$pid = Set_Process_Status($db, $company_id, 'nsf_mailer', 'started', $today);
			if(Generate_ACH_Mailer_Entries($today) != false)
			{
				$status = Upload_ACH_Mailer_File($server, $today);

				if($status === true) 
				{
					Set_Process_Status($db, $company_id, 'nsf_mailer', 'completed', $today, $pid);
				} 
				else 
				{
					Set_Process_Status($db, $company_id, 'nsf_mailer', 'failed', $today, $pid);
					echo "Unable to FTP File.\n";
				}
			}
			else
			{
				echo "Generate_ACH_Mailer_Entries returned false.\n";
				Set_Process_Status($db, $company_id, 'nsf_mailer', 'failed', $today, $pid);
			}
		} 
		catch (Exception $e) 
		{
			Set_Process_Status($db, $company_id, 'nsf_mailer', 'failed', $today, $pid);
			throw $e;
		}
		$server->timer->Timer_Stop($insufficient_funds_timer);

	return true;
}

function Upload_ACH_Mailer_File($server, $day)
{
	$log = $server->log;
	$mailer_host = ECash::getConfig()->INS_FUNDS_MAILER_HOST; 
	$mailer_user = ECash::getConfig()->INS_FUNDS_MAILER_USER; 
	$mailer_pass = ECash::getConfig()->INS_FUNDS_MAILER_PASS; 
	
	
	// hardcoding directories like this... Yea, I know.
	$filename = $server->company . "M" . substr($day, 5, 2) . substr($day, 8, 2) . substr($day, 0, 4) . ".csv";
	$local_file = ECash::getConfig()->NSF_MAILER_DIR . "/{$filename}";

	if(file_exists($local_file))
	{	
		$log->Write("Attempting to upload {$local_file}");
		
		// and now upload it
		if( ! $ftp = ftp_connect($mailer_host) )
		{
			$log->Write("Could not connect to ftp host [$mailer_host] in " . __FILE__ . " on line " . __LINE__ . ".");
			return false;
		}

		if( ! $login = ftp_login($ftp, $mailer_user, $mailer_pass) )
		{
			$log->Write("Login failure to ftp host [$mailer_host] using [$mailer_user:********] in " . __FILE__ . " on line " . __LINE__ . ".");
			return false;
		}

		if( ! $upload = ftp_put($ftp, $filename, $local_file, FTP_ASCII) )
		{
			$log->Write("Could not write contents of {$local_file} to remote host in " . __FILE__ . " on line " . __LINE__ . ".");
			return false;
		}

		ftp_close($ftp);
		$log->Write( "ACH Returns mailer file successfully uploaded to $mailer_host", LOG_INFO );
		return true;
	}
	else
	{
		$log->Write("ACH Return mailer file \"{$local_file}\" could not be opened!");
		throw new Exception("Could not open {$local_file} for upload.");
	}
}

function Generate_ACH_Mailer_Entries($business_day) 
{
	$log = get_log();
	$db = ECash::getMasterDb();
	$company_id = ECash::getCompany()->company_id;
	$company    = ECash::getCompany();

	$filename = $company . "M" . substr($business_day, 5, 2);
	$filename .= substr($business_day, 8, 2) . substr($business_day, 0, 4) . ".csv";
	$nsf_mailer_dir = ECash::getConfig()->NSF_MAILER_DIR;
	$fullpath = $nsf_mailer_dir . $filename;

	if(file_exists($fullpath)) 
	{
		$log->Write("ACH Mailer file already exists for {$business_day}.");
		return true;
	}
	
	$log->Write("Creating ACH Mailer for business day {$business_day}");

	$return_accts = array();
	$mailer_accts = array();

	$report_query = "
		SELECT distinct ar.ach_report_id as 'report_id'
		FROM ach_report ar INNER JOIN ach USING (ach_report_id) 
		WHERE DATE(ar.date_request) = '{$business_day}'
        AND ar.company_id = {$company_id}";
		
	$report_id = $db->querySingleValue($report_query);

	if ($report_id == null)
	{
		$log->Write("Could not find report id for day {$business_day}.");
		return false;
	}
	else
	{
		$log->Write("Using report id {$report_id}");
	}

	$main_query = "
		SELECT application_id, sum(count) AS 'sum' 
		FROM (
			SELECT application_id, count(distinct ach1.ach_report_id) AS 'count'
			FROM  ach ach1 
			INNER JOIN (	SELECT DISTINCT application_id 
				    		FROM ach ach3, ach_return_code arc 
		    				WHERE ach3.ach_report_id = {$report_id}
		    				AND arc.ach_return_code_id = ach3.ach_return_code_id 
		    				AND arc.is_fatal = 'no'
		                    AND NOT EXISTS (SELECT 'x' 
                                    		FROM ach ach4, ach_return_code arc2
                                    		WHERE ach4.ach_report_id = {$report_id}
                                    		AND ach4.application_id = ach3.application_id 
                                    		AND arc2.ach_return_code_id = ach4.ach_return_code_id
		                    				AND arc2.is_fatal = 'yes')) ach2
			USING(application_id)
	        WHERE ach1.ach_report_id <= {$report_id}
			GROUP BY ach1.application_id, ach1.ach_report_id) a 
			GROUP BY application_id having sum < 3";

	$log->Write("Main query:\n{$main_query}");
	
	$st = $db->query($main_query);
	
	while ($row = $st->fetch(PDO::FETCH_OBJ))
	{
		$return_accts[$row->application_id] = intval($row->sum);
	}
		
	$log->Write("Found ".count($return_accts)." accounts to search.");
		
	// We have the counts referenced against ecash only. Need to get cashline and
	// transitional returns if they exist.
	foreach ($return_accts as $application_id => $count) 
	{
		$log->Write("Account {$application_id} has {$count} returns.");
			
		// Look for a cashline id
		$acid = $db->querySingleValue("SELECT archive_cashline_id FROM application WHERE application_id = {$application_id}");

		if ($acid == null) $log->Write("Account {$application_id} has no Cashline ID.");
		if (($acid != null) &&($count < 3)) 
		{
			$log->Write("Searching Cashline data for pre-conversion returns, Cashline ID {$acid}");

			/* Look for any cashline returns before conversion */
			$supp_query = "
				SELECT count(*) as 'count'
				FROM cl_transaction clt, cl_customer clc
				WHERE clc.customer_id = clt.customer_id
				AND clc.company_id = {$company_id}
				AND clc.cashline_id = {$acid}
				AND clt.transaction_type = 'ach return'
				AND clt.transaction_date >
								(SELECT MAX(transaction_date)
								 FROM cl_transaction clt2
								 WHERE transaction_type = 'advance'
								 AND clt2.customer_id = clc.customer_id)";
			
			$log->Write("Supplemental Cashline query:\n{$supp_query}");
			$adds = $db->querySingleValue($supp_query);
			$log->Write("Discovered {$adds} cashline returns");
			$count += intval($adds);
		}			
			
		// If it's still under 2, we're still good
		if ($count < 3) 
		{
				
			$log->Write("Checking pending returns for {$application_id}.");

			$supp_query = "
				SELECT count(*) as 'count'
				FROM cl_conversion_ach_return
				WHERE application_id = {$application_id}
				AND company_id = {$company_id}
				AND return_date <= {$business_day}";
			
			$log->Write("Supplemental pending query:\n{$supp_query}");
			$add = $db->querySingleValue($supp_query);
			$log->Write("Discovered {$add} pending period returns."); 
			$count += intval($add);
		}
			
		if ($count == 2) 
		{
			$log->Write("Adding {$application_id} to mailer list.");
			$mailer_accts[] = $application_id;				
		}
	}
		
		
	// NOW, we need to check for the people who had returns for that day
	// that DIDNT show up in ecash.
	$pending_query = "
				SELECT DISTINCT application_id, count(*) as 'count' 
				FROM cl_conversion_ach_return
				WHERE return_date = '{$business_day}'
				GROUP BY application_id";
	
	$log->Write("Pending Main Query:\n{$pending_query}");
	$result = $db->query($pending_query);
	
	$pending_accts = array();
	
	while ($row = $result->fetch(PDO::FETCH_OBJ))
	{
		$pending_accts[$row->application_id] = intval($row->count);
	}

	$log->Write("Discovered ".count($pending_accts)." accounts from pending main query.");
	foreach ($pending_accts as $application_id => $count) 
	{
		$log->Write("[Pending] Account {$application_id} has {$count} returns.");
		$acid = $db->querySingleValue("SELECT archive_cashline_id FROM application WHERE application_id = {$application_id}");
		
		if (($acid != null) &&($count < 3))
		{
			
			$log->Write("[Pending] Searching Cashline data for pre-conversion returns, Cashline ID {$acid}");
				
			/* Look for any cashline returns before conversion */
			$supp_query = "
				SELECT count(*) as 'count'
				FROM cl_transaction clt, cl_customer clc
				WHERE clc.customer_id = clt.customer_id
				AND clc.company_id = {$company_id}
				AND clc.cashline_id = {$acid}
				AND clt.transaction_type = 'ach return'
				AND clt.transaction_date >
					(SELECT MAX(transaction_date)
					 FROM cl_transaction clt2
					 WHERE transaction_type = 'advance'
					 AND clt2.customer_id = clc.customer_id)";
			$log->Write("[Pending] Supplemental Cashline query:\n{$supp_query}");
			$adds = $db->querySingleValue($supp_query);
			$log->Write("[Pending] Discovered {$adds} cashline returns");
			$count += intval($adds);
		}

		if ($count == 2) 
		{
			$log->Write("[Pending] Adding {$application_id} to mailer list.");
			$mailer_accts[] = $application_id;				
		}

	}

	// We now should have all the people for the day.
	if (count($mailer_accts) > 0) 
	{
		$output = "";
		foreach ($mailer_accts as $acct) 
		{
			$log->Write("Adding {$acct} data to mailer file.");
			$info_query = "
				SELECT name_last, name_first,street, unit, city, county, state, zip_code
				FROM application
				WHERE application_id = {$acct}";
			
			$row = $db->querySingleRow($info_query, NULL, PDO::FETCH_ASSOC);
			
			$last    = str_replace("\t","",$row['name_last']);
			$first   = str_replace("\t","",$row['name_first']);
			$address = str_replace("\t","",$row['street']);
			$unit    = str_replace("\t","",(!empty($row['unit'])?$row['unit']:""));
			$city    = str_replace("\t","",$row['city']);
			$county  = str_replace("\t","",$row['county']);
			$state   = str_replace("\t","",$row['state']);
			$zip     = str_replace("\t","",$row['zip_code']);
				
			$output .= "{$first}\t{$last}\t{$address}\t{$unit}\t{$city}\t{$state}\t{$zip}\n";
		}

		// Now make our temp file
		
		// In case the directory isn't there...
		if (!is_dir($nsf_mailer_dir)) 
		{
			if (!mkdir($nsf_mailer_dir, 0777, true)) 
			{
				$log->Write("ERROR: Unable to create directory ".$nsf_mailer_dir);
				return false;
			}
		}

		if (($fout = fopen($fullpath, "w")) == null) 
		{
			$log->Write("ERROR: Unable to open file {$fullpath}.");
			return false;
		}
			
		fwrite($fout, $output);
		fflush($fout);
		fclose($fout);
		$log->Write("ACH Mailer file {$fullpath} written with ".count($mailer_accts)." entries.");
	} 
	else 
	{
		$log->Write("No accounts for eligible for the {$business_day} ACH Mailer.");
	} 

	return true;
}
