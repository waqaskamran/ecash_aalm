<?php
/*  Usage example:  php -f ecash_engine.php ufc LOCAL batch_maintenance  */

require_once(LIB_DIR."batch_maintenance.class.php");

function Main()
{
	global $server;
	$log = $server->log;

	$bm = new Batch_Maintenance($server);

	/*
	$closeout_msg = $bm->Close_Out(); // Closeout the batch for end of day
	if($closeout_msg->message != "")
	{
		$log->Write($closeout_msg->message);
	}
	*/
	
	$send_batch_msg = $bm->Send_Batch(); // Send the batch off.
	if($send_batch_msg->message != "")
	{
		$log->Write($send_batch_msg->message);
	}
	
}

?>