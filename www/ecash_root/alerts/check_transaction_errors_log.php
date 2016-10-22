<?php

/**
 * If there is a current transaction_errors_log, send it.
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */

try 
{
	require_once(dirname(__FILE__)."/../www/config.php");

	// If passed a recipient list, use it instead, otherwise
	// use the NOTIFICATION_ERROR_RECIPIENTS, which will
	// probably be a larger list than we may want to use.
	if($argc > 1) $notify_list = $argv[1];
	else 
	{
		if(ECash::getConfig()->NOTIFICATION_ERROR_RECIPIENTS === NULL)
			throw new Exception("No Notification List defined!");
		$notify_list = ECash::getConfig()->NOTIFICATION_ERROR_RECIPIENTS;
	}
	
	$log_file = APPLOG_SUBDIRECTORY . '/alert_errors/current';
	if (file_exists($log_file)) 
	{
		$contents = file_get_contents($log_file);
		if (!$contents) exit(0);
		$body = 'Errors in transactions were found. To clear this alert, the accounts below must be reviewed and the transaction_errors applog cleared.'."\n\n--------------------------------------------------------------------\n".$contents;
		Email_Report($notify_list, "Transaction Errors Found\n\n" . $body);
		exit(1);
	}

}
catch (Exception $e) 
{
	echo "{$argv[0]}: Error occurred: {$e->getMessage()}\nTrace:\n{$e->getTraceAsString()}\n";
	exit(3);
}

exit(0);


function Email_Report($recipients, $body) {
	require_once(LIB_DIR . '/Mail.class.php');
	return eCash_Mail::sendExceptionMessage($recipients, $body);
}

?>
