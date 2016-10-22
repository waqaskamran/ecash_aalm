<?php

class CommandLineException extends Exception
{
	
}


declare (ticks = 1);

// Data fix Tracking flag.
if (!defined('DEFAULT_SOURCE_TYPE')) define('DEFAULT_SOURCE_TYPE', 'ecashdaemon');
define('IS_CRONJOB', TRUE);

set_time_limit(0);
ob_implicit_flush(true);
set_magic_quotes_runtime(0);

// Include the config file here to load our execution context
require_once dirname(__FILE__) . "/../www/config.php";
require_once COMMON_LIB_DIR . "/applog.1.php";

$_BATCH_XEQ_MODE = strtoupper(EXECUTION_MODE);

require_once SERVER_CODE_DIR . "/skeletal_server.class.php";
try 
{

	echo "\n-------------- OUTPUT LOG START (" . date("Y-m-d H:i:s") . ") --------------\n";
	
	$server = new Server();

	if ($argc < 4) 
	{
		throw new CommandLineException();
	}

	// Yeah, this is kinda cheeseball
	$server->Set_Company($argv[1]);
	$logname = $argv[2];
	$cmd = $argv[3];

	// Object setup
	$log	= new Applog(APPLOG_SUBDIRECTORY.'/'.$logname, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, strtoupper($server->company));
	$server->Set_Log($log);

	// Main
	//==========================================================================================

	$fullpath = dirname(__FILE__) . "/{$cmd}.php";

	file_exists($fullpath) or die ("\nCould not find file {$fullpath} to execute.\n");

	require_once($fullpath);

	$ret_val = Main($argv);
		
} 
catch (CommandLineException $e) 
{

	if(!function_exists('print_usage')) 
	{
		function print_usage($companies = null)
		{
			echo  basename(__FILE__) . " <company> <log> <execution_command>\n";
			echo "<log>              is the name of the log directory to use\n";

			if(is_array($companies))
			{
				echo "<company>          can be: " . implode (" ", $companies) . "\n";
			} 
			else 
			{
				echo "<company>          short name of company\n";
			}
		}
	}
	
	$companies = $server->Fetch_Company_List();
	print_usage ( in_array($argv[1], $companies) ? NULL : $companies );
	$ret_val = -1;
	
} 
catch (Exception $e) 
{
	echo "Uncaught Exception line " . $e->getLine() . " in file " . $e->getFile() . ":\n";
	echo $e->getMessage() . "\n\n";
	echo $e->getTraceAsString();
	$ret_val = -1;
}
	
echo "\n-------------- OUTPUT LOG STOP (" . date("Y-m-d H:i:s") . ") --------------\n";
//==========================================================================================

return($ret_val);
