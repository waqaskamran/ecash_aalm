<?php
require_once(LIB_DIR . "landmark_ach.class.php");
require_once(LIB_DIR . "Mail.class.php");

function main()
{
	global $server;
	
	$company_id = $server->company_id;
	$company_short = $server->company;

	try 
	{
	
		$lm = new Landmark_ACH($company_id, $company_short);
		$lm->runBatch();
	}
	catch (Exception $e)
	{
		echo "An error has occurred processing the batch!";
		echo $e->getMessage() . "\n";
		echo $e->getTraceAsString() . "\n";

		$hostname = exec('hostname -f');
	    require_once(LIB_DIR . '/Mail.class.php');
	
	    $recipients = (EXECUTION_MODE !== 'LOCAL') ? ECash::getConfig()->ECASH_NOTIFICATION_ERROR_RECIPIENTS : '';
		$body = "An error has occurred processing the Landmark ACH Batch!\r\n<br>" . 
				"Execution Mode:  " . EXECUTION_MODE . "\r\n<br>\r\n<br>" .
				"Company:  " . $this->company . "\r\n<br>" .
				"Process Server: {$hostname}\r\n<br>" .
				"Exception: {$e->getMessage()} \r\n<br>" .
				"Trace:\r\n<br> {$e->getTraceAsString()} \r\n<br>";
		
		if(count($recipients) > 0) eCash_Mail::sendExceptionMessage($recipients, $body);
		
	}
	
}
