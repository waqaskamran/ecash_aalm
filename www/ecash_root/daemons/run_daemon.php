<?php

require LIB_DIR . "/Daemon.class.php";

function print_usage($companies = null)
{
	echo  $_SERVER["_"] . " " . dirname(__FILE__) . "/script_engine.php <company> <log> run_daemon <daemon_command> <number_to_spawn>\n";
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

function Main($args)
{	

	array_shift($args);
	
	$company = array_shift($args);
	$log = array_shift($args);
	$this_command = array_shift($args);
	$command = array_shift($args);
	$spawn_count = array_shift($args);

	if(!is_numeric($spawn_count)) 
	{
		throw new CommandLineException();
	}
	
	if ($command . ".php" == basename(__FILE__)) return;
	
	$path = `which php`;
	if (!$path && isset($_SERVER["_"])) 
	{
		$path = $_SERVER["_"];
	} 
	elseif (!$path && isset($_SERVER["SUDO_COMMAND"])) 
	{
		preg_match ("/^([^\s]+)/", $_SERVER["SUDO_COMMAND"], $matches);
		$path = $matches[1];
	} 
	elseif(!$path) 
	{
		$path = "php";
	}
	
	eCash_Daemon::Execute(trim($path) . " " . dirname(__FILE__) . "/script_engine.php {$company} {$log} {$command} " . implode(" ", $args), $spawn_count);
	
}
