<?php
/**
 * @package cronjobs
 * 
 * Changelog:
 * 
 * 2008-08-21: As part of GForge # 17337, I've included a system to use PID files to track 
 *             if the process is currently running and have reworked the script so that if
 *             a process dies and leaves a stale lock it will resume gracefully.  The code 
 *             for the PID/Lock Files was stolen from Condor's send_mail_for_account.php. [BR]
 */

function Main()
{
	global $server;
	$second_tier  = Second_Tier::Get_Second_Tier_Handler($server, 'incoming');
	$cron = new eCash_Cronjobs_SecondTierIncomingUpdate($server, ECash::getMasterDb(), $server->log, $second_tier);

	/**
	 * Check for an existing PID file.  If it exists and it's stale then it'll find any processes
	 * still marked as 'started', mark then as failed, and then resume.  If it doesn't exist it'll
	 * just start like normally.  If it exist and isn't stale it'll return false.
	 */
	if($cron->checkProcesses())
	{
		$cron->runFetchPhase();
		$cron->runProcessPhase();
	}
	else
	{
		echo "Second Tier Incoming already processing!\n";
	}
}

class eCash_Cronjobs_SecondTierIncomingUpdate
{
	const PROCESS_FETCH_INCOMING_UPDATE = 'second_tier_fetch_update';
	const PROCESS_UPDATES = 'second_tier_updates';
	
	const PHASE_FETCH = 'fetch';
	const PHASE_PROCESS = 'process';
	
	const LOCK_FILE_FORMAT = '/tmp/ecash_second_tier_incoming_%s.lock';
	
	const LOCK_EMPTY = 0;
	const LOCK_STALE = 1;
	const LOCK_VALID = 2;
	
	private $db;
	private $run_date;
	private $ecash_3_company_ids;
	private $second_tier;
	private $company_processes;
	private $log;
	private $server;

	private $fetch_returns_ids;
	private $fetch_corrections_ids;
	private $process_returns_ids;
	private $process_corrections_ids;
	private $reschedule_ids;
	private $process_failures;
	public function __construct($server, DB_Database_1 $db, $log, $second_tier)
	{
		$this->db = $db;
		$this->log = $log;
		$this->second_tier = $second_tier;
		$this->server = $server;

		$this->run_date = date('Y-m-d');
		$this->loadApplicableCompanyIDs();
	}

	// For eCash Commercial, all companies are eCash 3 companies.
	// ... that and we don't use the company_property table.
	private function loadApplicableCompanyIDs()
	{
		$query = "
			SELECT company_id, name_short 
			FROM 
				company
			WHERE 
				company_id < 100
			AND
				active_status = 'active'
			ORDER BY
				company_id DESC
		";

		$result = $this->db->query($query);

		$company_ids = array();

		while ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$company_ids[$row['company_id']] = $row['name_short'];
		}
		$this->ecash_3_company_ids = $company_ids;
	}

	private function checkProcessState($process, $company_id)
	{
		return Check_Process_State($this->db, $company_id, $process, $this->run_date);
	}

	private function checkAggregateProcessState($process, $expected_state)
	{
		foreach (array_keys($this->ecash_3_company_ids) as $company_id)
		{
			if ($this->checkProcessState($process, $company_id) != $expected_state)
			{
				return false;
			}
		}

		return true;
	}

	private function canProcessRun($process, $company_id = null)
	{
		if (empty($company_id))
		{
			$runnable_ids = array();
			foreach ($this->ecash_3_company_ids as $company_id => $company_short)
			{
				$state = $this->checkProcessState($process, $company_id);
				$has_failures = $this->hasFailures($company_id);

				if (($state != 'completed') && !$has_failures)
				{
					$runnable_ids[$company_id] = $company_short;
				}
			}
			// If there are company_ids for which this process can run return an array of them
			return (!empty($runnable_ids)) ? $runnable_ids : FALSE;
		}
		else
		{
			$state = $this->checkProcessState($process, $company_id);
			return ($state == 'completed') ? false : true;
		}
	}

	private function phaseCanRun($phase)
	{
		switch ($phase)
		{
			case self::PHASE_FETCH:
				$this->fetch_returns_ids = $this->canProcessRun(self::PROCESS_FETCH_INCOMING_UPDATE);
				if ($this->fetch_returns_ids)
				{
					return true;
				}
				else
				{
					return false;
				}
				break;

			case self::PHASE_PROCESS:
				$this->process_returns_ids = $this->canProcessRun(self::PROCESS_UPDATES );
				if ($this->process_returns_ids)
				{
					return true;
				}
				else
				{
					return false;
				}
				break;
		}
	}

	private function startProcessForAllCompanies($process, $companies=NULL)
	{
		$companies = ($companies) ? $companies : $this->ecash_3_company_ids;
		$companies_running = array();
		
		$this->failStaleProcesses($process, $companies);
		
		foreach (array_keys($companies) as $company_id)
		{
			if ($this->canProcessRun($process, $company_id))
			{
				$this->company_processes[$process][$company_id] = 
					Set_Process_Status($this->db, $company_id, $process, 'started', $this->run_date);
				$companies_running[] = $company_id;
			}
		}

		return $companies_running;
	}

	private function failProcessForCompany($process, $company_id)
	{
		$result = Set_Process_Status($this->db, $company_id, $process, 'failed', $this->run_date, $this->company_processes[$process][$company_id]);
		$this->process_failures[$company_id][] = $process;
	}

	private function completeProcessForCompany($process, $company_id)
	{
		Set_Process_Status($this->db, $company_id, $process, 'completed', $this->run_date, $this->company_processes[$process][$company_id]);
	}

	public function runFetchPhase()
	{
		if ($this->phaseCanRun(self::PHASE_FETCH))
		{
			$company_ids = $this->startProcessForAllCompanies(self::PROCESS_FETCH_INCOMING_UPDATE, $this->fetch_returns_ids);
			foreach ($company_ids as $company_id)
			{
				$this->fetchUpdatesFile($company_id);
			}

		}
	}

	public function runProcessPhase()
	{
		if ($this->phaseCanRun(self::PHASE_PROCESS))
		{
			$company_ids = $this->startProcessForAllCompanies(self::PROCESS_UPDATES , $this->process_returns_ids);
			foreach ($company_ids as $company_id)
			{
				$this->ProcessUpdates($company_id);
			}
		}
	}

	private function fetchUpdatesFile($company_id)
	{
		try
		{
			$this->switchCompanies($company_id);
			$this->log->Write("Running {$company_id}:".__METHOD__);
			
			if (!$this->second_tier->Fetch_Second_Tier_File('updates', $this->run_date))
			{
				$this->failProcessForCompany(self::PROCESS_FETCH_INCOMING_UPDATE , $company_id);
			}
			else
			{
				$this->completeProcessForCompany(self::PROCESS_FETCH_INCOMING_UPDATE, $company_id);
			}
		}
		catch (Exception $e)
		{
			$this->log->Write("There was an error fetching returns: {$e->getMessage()} - TRACE\n{$e->getTraceAsString()}");
			$this->failProcessForCompany(self::PROCESS_FETCH_INCOMING_UPDATE , $company_id);
		}
	}
	

	private function ProcessUpdates($company_id)
	{
		try
		{
			$this->switchCompanies($company_id);
			$this->log->Write("Running {$company_id}:".__METHOD__);
			$this->second_tier->Process_Second_Tier_Incoming_Update($this->run_date, $this->run_date);
			
			$this->completeProcessForCompany(self::PROCESS_UPDATES , $company_id);
		}
		catch (Exception $e)
		{
			$this->log->Write("There was an error processing returns: {$e->getMessage()} - TRACE\n{$e->getTraceAsString()}");
			$this->failProcessForCompany(self::PROCESS_UPDATES, $company_id);
		}
	}

	private function switchCompanies($company_id)
	{
		$this->server->Set_Company($this->ecash_3_company_ids[$company_id]);
		$this->second_tier = Second_Tier::Get_Second_Tier_Handler($this->server, 'incoming');
	}
	
	private function hasFailures($company_id)
	{
		if(!empty($this->process_failures[$company_id]))
		{
			return TRUE;
		}
		else 
		{
			return FALSE;
		}
	}
	
	/**
	 * Checks the current lock states and process statuses
	 * 
	 * - If the lock file doesn't exist, we create one
	 * - If the lock file exists but is stale, check the 
	 *   current processes and fail them, remove the stale
	 *   lock file and create a new one.
	 *
	 * @return boolean
	 */
	public function checkProcesses()
	{
		$locked_status = $this->isLocked();

		switch($locked_status)
		{
			case self::LOCK_EMPTY;
				$this->Lock();
				return TRUE;
				break;

			case self::LOCK_STALE;
				$this->log->Write("ERROR: Returns Processing found a stale lock!");
				$this->failStaleProcesses();
				$this->Unlock();
				$this->Lock();
				return TRUE;
				break;

			case self::LOCK_VALID;
				return FALSE;
				break;
		}
	}

	/**
	 * Checks the process state for all processes for all companies
	 * and marks any that are marked as 'started' as 'failed'.
	 * 
	 * This should only be called when we know these processes
	 * aren't currently running due to some sort of failure and
	 * we need to mark them as failed to resume processing.
	 */
	private function failStaleProcesses($process = NULL, $companies = NULL)
	{
		if(! empty($process))
		{
			$processes = array($process);
		}
		else
		{
			$processes = array( self::PROCESS_FETCH_RETURNS,
								self::PROCESS_FETCH_CORRECTIONS,
								self::PROCESS_RETURNS,
								self::PROCESS_CORRECTIONS,
								self::PROCESS_RESCHEDULE );
		}

		if(empty($companies)) $companies = $this->ecash_3_company_ids;

		foreach($companies as $company_id => $company_name)
		{
			foreach($processes as $process)
			{
				$state = $this->checkProcessState($process, $company_id);
				if($state == 'started')
				{
					// Not using $this->failProcessForCompany() because it will track the failure and 
					// we don't want that, we want it to continue.
					Set_Process_Status($this->db, $company_id, $process, 'failed', $this->run_date);
					
				}
			}
		}
	}

	/**
	 * Locks things down
	 */
	private function Lock()
	{
		$file = $this->Get_Lock_File();
		if(!file_exists($file))
		{
			//If we lock things, make sure we unlock if
			//the script exits.
			register_shutdown_function(array($this,'Unlock'));
			file_put_contents($file, getmypid());
		}
		else
		{
			// Check if the process is still alive
			$lockpid = file_get_contents($file);
			
			$running = posix_kill($lockpid, 0);
			
			if (posix_get_last_error() == 1)
				$running = TRUE;
		
			if ($running == FALSE)
			{
				// Stale lockfile
				register_shutdown_function(array($this,'Unlock'));
				file_put_contents($file, getmypid());
			}
		}
	}

	/**
	 * Are things locked?
	 *
	 * @return  self::LOCK_EMPTY = 0;
	 *			self::LOCK_STALE = 1;
	 *			self::LOCK_VALID = 2;
	 */
	private function isLocked()
	{
		$file = $this->Get_Lock_File();
		
		// If the file does not exist, return FALSE
		if (file_exists($file) == FALSE)
		return self::LOCK_EMPTY;
		
		$lockpid = file_get_contents($file);
		
		$running = posix_kill($lockpid, 0);
		
		if (posix_get_last_error() == 1)
		{
			// Process is still running
			return self::LOCK_VALID;
		}
		else
		{
			// Process is not running, lock is stale
			return self::LOCK_STALE;
		}
	}
	
	/**
	 * Removes the lock file
	 */
	public function Unlock()
	{
		$file = $this->Get_Lock_File();
		if(file_exists($file))
		{
			unlink($file);
		}
	}

	/**
	 * Returns the name of the lock file
	 *
	 * @return string
	 */
	private function Get_Lock_File()
	{
		return sprintf(self::LOCK_FILE_FORMAT, getenv('ECASH_CUSTOMER'));
	}

}

?>
