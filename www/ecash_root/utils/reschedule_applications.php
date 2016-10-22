<?php
/**
 * This script should be used to run applications through rescheduling code.
 * 
 * Application IDs should be passed on newlines through the command line. An 
 * empty line will halt the application.
 */
chdir(dirname(__FILE__));
require_once "../www/config.php";
require_once './mini-server.class.php';
require_once SQL_LIB_DIR.'util.func.php';

Main();

function Main()
{
	$args = GetArgs();
	if (empty($args['company_id']))
	{
		DisplayHelp();
		exit(1);
	}
	
	$server = SetupServer($args['company_id']);
	
	require_once CUSTOMER_LIB . 'failure_dfa.php';
	while (($application_id = ReadApplicationId()) !== false)
	{
		try
		{
			RescheduleApplication($application_id, $server);
		}
		catch (Exception $e)
		{
			echo "Error [{$application_id}]: {$e->getMessage()}\n";
		}
	}
}

function GetArgs()
{
	$args = getopt('c:');
	return array(
		'company_id' => $args['c']
	);
}

function DisplayHelp()
{
	echo 'Usage: php '.basename(__FILE__).' -c <company_id>'."\n";
	echo "------------------------------------------------------\n";
	echo "After running the command enter each app id you would \n";
	echo "like to reschedule on its own line. An empty line will\n";
	echo "halt the program.\n\n";
}

function ReadApplicationId()
{
	$application_id = trim(fgets(STDIN));
	
	if (empty($application_id))
	{
		return false;
	}
	else
	{
		return $application_id;
	}
}

function SetupServer($company_id)
{
	$server = new Server(get_log('repair'), MySQLi_1e::Get_Instance(), $company_id);
	Set_Company_Constants($server->company);
}

function RescheduleApplication($application_id, $server)
{
	$fdfap = new stdClass();
	$fdfap->application_id = $application_id;
	$fdfap->server = $server;
	$fdfa = new FailureDFA($application_id);
	$fdfa->run($fdfap);
}
