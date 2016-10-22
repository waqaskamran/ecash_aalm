<?php
/**
 * This script is intended to run in RC environments where we want to make certain
 * the batch is sent daily.  Run this script at the end of the day but before the
 * nightly events.  If the batch has been sent, the script will do nothing,
 * otherwise it will send out the batch to the loopback server.
 */

require_once(LIB_DIR."batch_maintenance.class.php");

function Main()
{
	global $server;
	$log = $server->log;
	$company_id = $server->company_id;
	$db = ECash::getMasterDb();
	$today = date('Y-m-d');
	
	if(! defined('EXECUTION_MODE') || EXECUTION_MODE === 'LIVE')
	{
		echo "Error: Trying to run Automatic Batch Send in a NON-RC Environment!\n";
		$log->Write("Error: Trying to run Automatic Batch Send in a NON-RC Environment!");
		return;
	}
	
	$batch_close_state = Check_Process_State($db, $company_id, 'ach_batchclose', $today);
	$batch_send_state  = Check_Process_State($db, $company_id, 'ach_send', $today);
	
	if(($batch_send_state == 'completed') || ($batch_send_state == 'started'))
	{
		echo "Batch is/has already been sent.  Not auotmatically sending the batch.\n";
		$log->Write("Batch is/has already been sent.  Not auotmatically sending the batch.");
		return;
	}

	$bm = new Batch_Maintenance($server);

	if(($batch_close_state != 'completed') && ($batch_close_state != 'started'))
	{
	
		$closeout_msg = $bm->Close_Out(); // Closeout the batch for end of day
		if($closeout_msg->message != "")
		{
			$log->Write($closeout_msg->message);
		}
	}
	
	$send_batch_msg = $bm->Send_Batch();
	if($send_batch_msg->message != "")
	{
		$log->Write($send_batch_msg->message);
	}
	
}

?>