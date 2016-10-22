<?php

// Make sure we've been initialized before running anything more.
if (!defined('IS_CRONJOB') || IS_CRONJOB !== TRUE)
{
	echo 'We must be in a cron job to run correctly. Aborting...';
	exit -1;
}

// Object setup

// Yeah, this is kinda cheeseball, but I need $server to get the company list
$server->Set_Company($company);

// Again, this is cheeseball, but it's still getting set.
$log	= new Applog(APPLOG_SUBDIRECTORY . '/' . $logname, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, strtoupper($server->company));
$server->Set_Log($log);

/**
 * Try for filename with .php appended to the name as default.
 * If that doesn't exist, just try the filename passed in.
 */
$fullpath = BASE_DIR . "/cronjobs/{$cmd}.php";
if (!file_exists($fullpath))
{
	$fullpath = BASE_DIR . "/cronjobs/{$cmd}";
	if (!file_exists($fullpath))
	{	
		die("\nCould not find file {$fullpath} to execute.\n");
	}

}

require_once($fullpath);
$start_time = time();
echo "---- OUTPUT LOG START ".date('Y-m-d H:i:s', $start_time)." -----------------------------------\n";

try 
{
	$ret_val = Main($argv);
}
catch (Exception $e)
{
	echo "Exception line ". $e->getLine() ." in file ". $e->getFile() .":\n";
	echo $e->getMessage() . "\n\n";
	echo $e->getTraceAsString();
	$ret_val = -1;
}

$end_time = time();
$duration = $end_time - $start_time;
echo "---- OUTPUT LOG END   ".date('Y-m-d H:i:s', $end_time).", Duration: {$duration} seconds --------------\n";
//==========================================================================================

return $ret_val;

?>
