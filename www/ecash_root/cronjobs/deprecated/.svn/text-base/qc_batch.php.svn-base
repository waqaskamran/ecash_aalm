<?php
/*
 *  Quick Check Batch Processing Script
 *  This script will process the quick check batch
 *  and send it.  
 *  It has a variety of safety measures to ensure that
 *  the process is not run twice, that errors are recovered
 *  from, and that a batch is not sent twice in the same day.
 *
 */

require_once(LIB_DIR."common_functions.php");
require_once(BASE_DIR . "/server/module/collections/quick_checks.class.php");

function Main()
{	
	global $server;
	$log    = $server->log;
	$company_id = $server->company_id;

	$db = ECash::getMasterDb();

	$today = date('Ymd');
	$qc = new Quick_Checks($server);
	
	// Check to see if a process is running already
	$pid = Get_Pid();
	if(Check_Process($pid))
	{
		$log->Write("QC: Attempt to run two QC Processes at the same time.  Exiting.");
		exit;
	}
	else
	{
		// Write out our own PID file
		Write_Pid();
	}
	// See if the batch has been run yet.
	$status = Check_Process_State($db, $company_id, 'qc_build_deposit', $today);
	if(($status === false) || ($status != 'completed'))
	{
		Update_Progress('qc','Processing Quick Checks',1);
		$qc->Process_Quick_Checks();
	}

	// See if the deposit has been sent yet
	$status = Check_Process_State($db, $company_id, 'qc_send_deposit', $today);
	if(($status === false) || ($status != 'completed'))
	{
		// If we've got a ecld file for today, then we'll go
		// ahead and try to send/resend the file.
		if($ecld_file_id = $qc->Get_Ecld_File_Id($today))
		{
			Update_Progress('qc','Sending Quick Checks',99);
			if(($status == 'started') || ($status == 'failed'))
			{
				// The send must have failed
				$log->Write("QC: Re-attempting to send {$ecld_file_id}");
				Update_Progress('qc',"Re-attempting to send {$ecld_file_id}");
				$qc->Send_Deposit_File($ecld_file_id);
			}
			else
			{
				$qc->Send_Deposit_File($ecld_file_id);
			}
		}
	}
	Remove_Pid();
	
}

function Write_Pid()
{
	$pid = posix_getpid();
	if($fp = fopen('/tmp/qc_batch.pid','w'))
	{
		fputs($fp,$pid);
		fclose($fp);
		return true;
	}

	return false;
}

function Remove_Pid()
{
	unlink('/tmp/qc_batch.pid');
}

function Get_Pid()
{
	if(file_exists('/tmp/qc_batch.pid'))
	{
		$pid = file_get_contents('/tmp/qc_batch.pid');
		return $pid;
	}
	return false;
}
	
function Check_Process($pid)
{
	if(file_exists('/proc/$pid/cmdline'))
	{
		return true;
	}

	return false;
}


?>