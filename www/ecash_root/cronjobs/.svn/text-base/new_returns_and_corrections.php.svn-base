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
	$ach = ACH::Get_ACH_Handler($server, 'return');

	$cron = new eCash_Cronjobs_ReturnsCorrections($server, ECash::getMasterDb(), $server->log, $ach);

	/**
	 * Check for an existing PID file.  If it exists and it's stale then it'll find any processes
	 * still marked as 'started', mark then as failed, and then resume.  If it doesn't exist it'll
	 * just start like normally.  If it exist and isn't stale it'll return false.
	 */
	if($cron->checkProcesses())
	{
		$cron->runFetchPhase();
		$cron->runProcessPhase();
		$cron->runReschedulePhase();
	}
	else
	{
		echo "Returns already processing!\n";
	}
}

class eCash_Cronjobs_ReturnsCorrections
{
	const PROCESS_FETCH_RETURNS = 'ach_fetch_returns';
	const PROCESS_FETCH_CORRECTIONS = 'ach_fetch_corrections';
	const PROCESS_RETURNS = 'ach_returns';
	const PROCESS_CORRECTIONS = 'ach_corrections';
	const PROCESS_RESCHEDULE = 'ach_reschedule';

	const PHASE_FETCH = 'fetch';
	const PHASE_PROCESS = 'process';
	const PHASE_RESCHEDULE = 'reschedule';
	
	const LOCK_FILE_FORMAT = '/tmp/ecash_returns_and_corrections_%s.lock';
	
	const LOCK_EMPTY = 0;
	const LOCK_STALE = 1;
	const LOCK_VALID = 2;
	
	private $db;
	private $run_date;
	private $ecash_3_company_ids;
	private $ach;
	private $company_processes;
	private $log;
	private $server;

	private $fetch_returns_ids;
	private $fetch_corrections_ids;
	private $process_returns_ids;
	private $process_corrections_ids;
	private $reschedule_ids;
	private $process_failures;
	
	private $combined_returns;
	
	public function __construct($server, DB_Database_1 $db, $log, $ach)
	{
		$this->db = $db;
		$this->log = $log;
		$this->ach = $ach;
		$this->server = $server;

		$this->run_date = date('Y-m-d');
		$this->loadApplicableCompanyIDs();
		
		$this->combined_returns = $ach->useCombined();
	}

	// For eCash Commercial, all companies are eCash 3 companies.
	// ... that and we don't use the company_property table.
	private function loadApplicableCompanyIDs()
	{
		$query = "
			SELECT c.company_id, c.name_short 
			FROM 
				company c
            LEFT JOIN external_batch_company ebc ON ebc.company_id = c.company_id
            LEFT JOIN external_batch_report ebr ON ebr.external_batch_report_id = ebc.external_batch_report_id
            LEFT JOIN external_batch_type ebt ON ebt.external_batch_type_id = ebr.external_batch_type_id AND ebt.name_short LIKE '%ach%'
            
			WHERE 
				c.company_id < 100
			AND
				c.active_status = 'active'
            AND ebt.external_batch_type_id IS NULL
			ORDER BY
				c.company_id DESC;
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
				$this->fetch_returns_ids = $this->canProcessRun(self::PROCESS_FETCH_RETURNS);
				// Hack to handle combined returns & corrections
				if(! $this->combined_returns)
				{
					$this->fetch_corrections_ids = $this->canProcessRun(self::PROCESS_FETCH_CORRECTIONS);
				}
				else
				{
					$this->fetch_corrections_ids = TRUE;
				}
				
				if ($this->fetch_returns_ids &&	$this->fetch_corrections_ids)
				{
					return true;
				}
				else
				{
					return false;
				}
				break;

			case self::PHASE_PROCESS:
				$this->process_returns_ids = $this->canProcessRun(self::PROCESS_RETURNS);
				// Hack to handle combined returns & corrections
				if(! $this->combined_returns)
				{
					$this->process_corrections_ids = $this->canProcessRun(self::PROCESS_CORRECTIONS);
				}
				else
				{
					$this->process_corrections_ids = TRUE;
				}
				if ($this->process_returns_ids && $this->process_corrections_ids)
				{
					return true;
				}
				else
				{
					return false;
				}
				break;

			case self::PHASE_RESCHEDULE:
				$this->reschedule_ids = $this->canProcessRun(self::PROCESS_RESCHEDULE);
				if ($this->reschedule_ids)
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
			$company_ids = $this->startProcessForAllCompanies(self::PROCESS_FETCH_RETURNS, $this->fetch_returns_ids);
			foreach ($company_ids as $company_id)
			{
				$this->fetchReturnsFile($company_id);
			}

			if(! $this->combined_returns)
			{
				$company_ids = $this->startProcessForAllCompanies(self::PROCESS_FETCH_CORRECTIONS, $this->fetch_corrections_ids);
				foreach ($company_ids as $company_id)
				{
					 $this->fetchCorrectionsFile($company_id);
				}
			}
		}
	}

	public function runProcessPhase()
	{
		if ($this->phaseCanRun(self::PHASE_PROCESS))
		{
			$company_ids = $this->startProcessForAllCompanies(self::PROCESS_RETURNS, $this->process_returns_ids);
			foreach ($company_ids as $company_id)
			{
				$this->processReturns($company_id);
			}

			if(! $this->combined_returns)
			{
				$company_ids = $this->startProcessForAllCompanies(self::PROCESS_CORRECTIONS, $this->process_corrections_ids);
				foreach ($company_ids as $company_id)
				{
					$this->processCorrections($company_id);
				}
			}
		}
	}

	public function runReschedulePhase()
	{
		if ($this->phaseCanRun(self::PHASE_RESCHEDULE))
		{
			$company_ids = $this->startProcessForAllCompanies(self::PROCESS_RESCHEDULE, $this->reschedule_ids);

			while (count($company_ids))
			{
				$tmp = $company_ids;
				foreach ($tmp as $i => $company_id)
				{
					if (!$this->rescheduleApplications($company_id))
					{
						unset($tmp[$i]);
					}
				}
				$company_ids = array_values($tmp);
			}
		}
	}

	private function fetchReturnsFile($company_id)
	{
		try
		{
			$this->switchCompanies($company_id);
			$this->log->Write("Running {$company_id}:".__METHOD__);
			
			if (!$this->ach->Fetch_ACH_File('returns', $this->run_date))
			{
				$this->failProcessForCompany(self::PROCESS_FETCH_RETURNS, $company_id);
			}
			else
			{
				$this->completeProcessForCompany(self::PROCESS_FETCH_RETURNS, $company_id);
			}
		}
		catch (Exception $e)
		{
			$this->log->Write("There was an error fetching returns: {$e->getMessage()} - TRACE\n{$e->getTraceAsString()}");
			$this->failProcessForCompany(self::PROCESS_FETCH_RETURNS, $company_id);
		}
	}

	private function fetchCorrectionsFile($company_id)
	{
		try
		{
			$this->switchCompanies($company_id);
			$this->log->Write("Running {$company_id}:".__METHOD__);
			if (!$this->ach->Fetch_ACH_File('corrections', $this->run_date))
			{
				$this->failProcessForCompany(self::PROCESS_FETCH_CORRECTIONS, $company_id);
			}
			else
			{
				$this->completeProcessForCompany(self::PROCESS_FETCH_CORRECTIONS, $company_id);
			}
		}
		catch (Exception $e)
		{
			$this->log->Write("There was an error fetching corrections: {$e->getMessage()} - TRACE\n{$e->getTraceAsString()}");
			$this->failProcessForCompany(self::PROCESS_FETCH_CORRECTIONS, $company_id);
		}
	}

	private function processReturns($company_id)
	{
		try
		{
			$this->switchCompanies($company_id);
			$this->log->Write("Running {$company_id}:".__METHOD__);
			$this->ach->Process_ACH_Returns($this->run_date, $this->run_date);
			
			$this->completeProcessForCompany(self::PROCESS_RETURNS, $company_id);
		}
		catch (Exception $e)
		{
			$this->log->Write("There was an error processing returns: {$e->getMessage()} - TRACE\n{$e->getTraceAsString()}");
			$this->failProcessForCompany(self::PROCESS_RETURNS, $company_id);
		}
	}

	private function processCorrections($company_id)
	{
		try
		{
			$this->switchCompanies($company_id);
			$this->log->Write("Running {$company_id}:".__METHOD__);
			$this->ach->Process_ACH_Corrections($this->run_date, $this->run_date);
			
			$this->completeProcessForCompany(self::PROCESS_CORRECTIONS, $company_id);
		}
		catch (Exception $e)
		{
			$this->log->Write("There was an error processing corrections: {$e->getMessage()} - TRACE\n{$e->getTraceAsString()}");
			$this->failProcessForCompany(self::PROCESS_CORRECTIONS, $company_id);
		}
	}

	private function rescheduleApplications($company_id)
	{
		try
		{
			$this->switchCompanies($company_id);
			$this->log->Write("Running {$company_id}:".__METHOD__);
			
			if ($this->ach->Reschedule_Apps(100))
			{
				return true;
			}
			else
			{
				$this->completeProcessForCompany(self::PROCESS_RESCHEDULE, $company_id);
				return false;
			}
		}
		catch (Exception $e)
		{
			$this->log->Write("There was an error rescheduling applications: {$e->getMessage()} - TRACE\n{$e->getTraceAsString()}");
			$this->failProcessForCompany(self::PROCESS_RESCHEDULE, $company_id);
			return false;
		}
	}

	private function switchCompanies($company_id)
	{
		$this->server->Set_Company($this->ecash_3_company_ids[$company_id]);
		$this->ach = ACH::Get_ACH_Handler($this->server, 'return');
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
		
		/**
		 * This method isn't the safest as it will ONLY work
		 * on a Linux system, but it's reliable.
		 */
		$lockpid = file_get_contents($file);
		if(file_exists("/proc/$lockpid/"))
		{
			return self::LOCK_VALID;
		}
		else
		{
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
