<?php

declare (ticks = 1000);

require_once LIB_DIR . "/PBX/PBX.class.php";

function signal_daemon($company, $command, $signal)
{
	if (is_numeric($command)) 
	{
		$pid = $command;

	} 
	else 
	{
		$pid = `ps xa | grep php | grep -E 'run_daemon {$command} [0-9]+' | grep {$company} | awk '{ print $1 }'`;
		$pid = trim($pid);
		
	}
	
	if ($pid) 
	{
		$kres = posix_kill($pid, $signal);
			 
		eCash_PBX::Log()->write(($kres) ? __FILE__ . ": " . __METHOD__ . "  triggered. Signal {$signal} successful." : __FILE__ . " triggered. Signal {$signal} failed.");
			
	} 
	else 
	{
		eCash_PBX::Log()->write( __FILE__ . ": " . __METHOD__ . "  triggered. Process ID for socket daemon not found.");			
	}		
	
	return $kres;
	
}

function restart_daemon($company, $command)
{
	$c = trim(`ps xa | grep php | grep -E 'run_daemon {$command} [0-9]+' | grep {$company} | awk '{ print $5,$6,$7,$8,$9,$10,$11,$12 }'`);

	if($c) 
	{
		eCash_PBX::Log()->write( __FILE__ . ": " . __METHOD__ . "  triggered. Daemon process found.");			
		
		signal_daemon($company, $command, SIGTERM);
		
		usleep(250000);
		
		$pid = `ps xa | grep php | grep -E 'run_daemon {$command} [0-9]+' | grep {$company} | awk '{ print $1 }'`;
		
		if ($pid) 
		{
			eCash_PBX::Log()->write( __FILE__ . ": " . __METHOD__ . "  triggered. Daemon still running! Killing all processes.");			
			
			if (kill_daemon($company, $command)) return;
			
		}
		
		eCash_PBX::Log()->write( __FILE__ . ": " . __METHOD__ . "  triggered. Restarting daemon.");			

		passthru($c . "  > /dev/null & ");

		return;
		
	}
	
	eCash_PBX::Log()->write( __FILE__ . ": " . __METHOD__ . "  triggered. Daemon process not found.");			
	
}

function kill_daemon($company, $command)
{
	do {
		eCash_PBX::Log()->write( __FILE__ . ": " . __METHOD__ . "  triggered.");			
		
		$pid_list = preg_split("/\s/",trim(`ps xa | grep php | grep {$command} | grep -v asterisk_db_age_check | grep {$company} | awk '{ print $1 }'`), PREG_SPLIT_NO_EMPTY);
	
		if (is_array($pid_list) && count($pid_list)) 
		{
			foreach ($pid_list as $pid) 
			{
				eCash_PBX::Log()->write( __FILE__ . ": " . __METHOD__ . "  triggered. Killing pid {$pid}. DIE! DIE! DIE!");
				posix_kill($pid, SIGKILL);
				usleep(50000);
			}
		}
		
		usleep(250000);
		
	} while (count($pid_list) && ++$i < 10);
	
	if (count($pid_list)) 
	{
		eCash_PBX::Log()->write( __FILE__ . ": " . __METHOD__ . "  triggered. Unable to kill some daemon processes: " . implode(",", $pid_list));		
		return false;
	}
	
	return true;
	
}

function check5min($company, $command)
{
	global $server;
	
	$db = ECash::getMasterDb();
	$query = "
		select if(timediff(now(), date_created) > '00:05:00', 1,0) as t from pbx_history where company_id = {$server->company_id} AND pbx_event NOT IN ('Originate', 'CDR Import') order by date_created desc limit 1
	";
	
	$int = $db->querySingleValue($query);
		
	if ($int) 
	{
		eCash_PBX::Log()->write(__FILE__ . ": " . __METHOD__ . "  triggered. Last event age too old. Restarting socket daemon.");
		
		signal_daemon($company, $command, SIGHUP);		
		
	} 
	else 
	{

		eCash_PBX::Log()->write(__FILE__ . ": " . __METHOD__ . "  triggered. Last event age ok.");
		
	}
	
	
	
}

function check15min ($company, $command)
{
	global $server;
	
	$db = ECash::getMasterDb();
	$query = "
		select if(timediff(now(), date_created) > '00:15:00', 1,0) as t from pbx_history where company_id = {$server->company_id} AND pbx_event NOT IN ('Originate', 'CDR Import') order by date_created desc limit 1
	";
	
	$int = $db->querySingleValue($query);
		
	if ($int) 
	{
		eCash_PBX::Log()->write(__FILE__ . ": " . __METHOD__ . "  triggered. Last event age too old. Force restarting socket daemon.");
		
		restart_daemon($company, $command);		
		
		return true;
		
	} 

	eCash_PBX::Log()->write(__FILE__ . ": " . __METHOD__ . " triggered. Last event age ok.");
	return false;
	
}

function Main($args)
{

	array_shift($args);
	
	$company = array_shift($args);
	$log = array_shift($args);
	$this_command = array_shift($args);
	$command = array_shift($args);

	if (!check15min($company, $command)) 
	{
		check5min($company, $command);
	}	
	
}
