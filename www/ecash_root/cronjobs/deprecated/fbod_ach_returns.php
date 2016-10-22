<?php
/**
 * @package cronjobs
 * @package Landmark_ACH
 */

function Main()
{
	global $server;

	$cron = new eCash_Cronjobs_LandmarkReturns($server);

	$cron->runFetchPhase();
	$cron->runProcessPhase();
}


/**
 * This class is based on eCash's Returns and Corrections
 * cron job used for normal ACH operations, but rewritten
 * for Landmark's ACH process.
 * 
 * Since there is no corrections file, that phase has been 
 * eliminated.  Also, the Rescheduling process isn't required
 * since we're handling the status updates in the process
 * phase.
 */
class eCash_Cronjobs_LandmarkReturns
{
	const PROCESS_FETCH_RETURNS = 'landmark_fetch_returns';
	const PROCESS_RETURNS = 'landmarl_process_returns';

	const PHASE_FETCH = 'fetch';
	const PHASE_PROCESS = 'process';

	private $mysqli;
	private $run_date;
	private $company;
	private $company_id;
	private $company_processes;
	private $log;
	private $server;
	private $landmark;

	public function __construct($server)
	{
		$this->server 		= $server;
		$this->mysqli 		= $server->MySQLi();
		$this->log 			= $server->log;
		$this->company_id 	= $server->company_id;
		$this->company    	= $server->company;
		
		$this->run_date = date('Y-m-d');

		$this->landmark = new Landmark_ACH($this->company_id, $this->company, $this->run_date);

	}

	private function checkProcessState($process)
	{
		return Check_Process_State($this->mysqli, $this->company_id, $process, $this->run_date);
	}

	private function checkAggregateProcessState($process, $expected_state)
	{
		if ($this->checkProcessState($process) != $expected_state)
		{
			return false;
		}

		return true;
	}

	private function canProcessRun($process)
	{
		$state = $this->checkProcessState($process);
		return ($state == 'completed' || $state == 'started') ? false : true;
	}

	private function phaseCanRun($phase)
	{
		switch ($phase)
		{
			case self::PHASE_FETCH:
				if (
					$this->canProcessRun(self::PROCESS_FETCH_RETURNS))
				{
					return true;
				}
				else
				{
					return false;
				}
				break;

			case self::PHASE_PROCESS:
				if (
					$this->checkAggregateProcessState(self::PROCESS_FETCH_RETURNS, 'completed') &&
					$this->canProcessRun(self::PROCESS_RETURNS))
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

	private function startProcess($process)
	{
		if ($this->canProcessRun($process, $this->company_id))
		{
			$this->company_processes[$process][$this->company_id] = 
				Set_Process_Status($this->mysqli, $this->company_id, $process, 'started', $this->run_date);
		}
	}

	private function failProcessForCompany($process)
	{
		Set_Process_Status($this->mysqli, $this->company_id, $process, 'failed', $this->run_date, $this->company_processes[$process][$this->company_id]);
	}

	private function completeProcessForCompany($process)
	{
		echo "Completing Process for $process\n";
		Set_Process_Status($this->mysqli, $this->company_id, $process, 'completed', $this->run_date, $this->company_processes[$process][$this->company_id]);
	}

	public function runFetchPhase()
	{
		if ($this->phaseCanRun(self::PHASE_FETCH))
		{
			$this->startProcess(self::PROCESS_FETCH_RETURNS);
			$this->fetchReturnsFile();
		}
	}

	public function runProcessPhase()
	{
		if ($this->phaseCanRun(self::PHASE_PROCESS))
		{
			$this->startProcess(self::PROCESS_RETURNS);
			$this->processReturns();
		}
	}

	private function fetchReturnsFile()
	{
		try
		{
			if (! $this->landmark->fetchReturns())
			{
				$this->failProcessForCompany(self::PROCESS_FETCH_RETURNS);
			}
			else
			{
				$this->completeProcessForCompany(self::PROCESS_FETCH_RETURNS);
			}
		}
		catch (Exception $e)
		{
			$this->log->Write("There was an error fetching returns: {$e->getMessage()} - TRACE\n{$e->getTraceAsString()}");
			$this->failProcessForCompany(self::PROCESS_FETCH_RETURNS, $this->company_id);
		}
	}

	private function processReturns()
	{
		try
		{
			$this->landmark->processReturns();
			
			$this->completeProcessForCompany(self::PROCESS_RETURNS);
		}
		catch (Exception $e)
		{
			$this->log->Write("There was an error processing returns: {$e->getMessage()} - TRACE\n{$e->getTraceAsString()}");
			$this->failProcessForCompany(self::PROCESS_RETURNS);
		}
	}
}

?>
