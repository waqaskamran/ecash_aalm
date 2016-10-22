<?php
/**
 * This cronjob was written to process the Teledraft ACH processor's Initial Results file
 * 
 * @package cronjobs
 */

function Main()
{
	global $server;
	$ach = ACH::Get_ACH_Handler($server, 'return');

	$cron = new eCash_Cronjobs_Teledraft_Results($server, ECash::getMasterDb(), $server->log, $ach);

	$cron->runFetchPhase();
	$cron->runProcessPhase();
	$cron->runReschedulePhase();
}

class eCash_Cronjobs_Teledraft_Results
{
	const PROCESS_FETCH_RESULTS = 'ach_fetch_results';
	const PROCESS_RESULTS = 'ach_results';
	const PROCESS_RESCHEDULE = 'ach_results_reschedule';

	const PHASE_FETCH = 'fetch';
	const PHASE_PROCESS = 'process';
	const PHASE_RESCHEDULE = 'reschedule';

	private $db;
	private $run_date;
	private $ecash_3_company_ids;
	private $ach;
	private $company_processes;
	private $log;
	private $server;

	public function __construct($server, DB_Database_1 $db, $log, $ach)
	{
		$this->db = $db;
		$this->log = $log;
		$this->ach = $ach;
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
			$is_incomplete = false;

			foreach (array_keys($this->ecash_3_company_ids) as $company_id)
			{
				$state = $this->checkProcessState($process, $company_id);

				if ($state == 'started')
				{
					return false;
				}
				elseif ($state != 'completed')
				{
					$is_incomplete = true;
				}
			}

			return $is_incomplete;
		}
		else
		{
			$state = $this->checkProcessState($process, $company_id);
			return ($state == 'completed' || $state == 'started') ? false : true;
		}
	}

	private function phaseCanRun($phase)
	{
		switch ($phase)
		{
			case self::PHASE_FETCH:
				if ($this->canProcessRun(self::PROCESS_FETCH_RESULTS))
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
					$this->checkAggregateProcessState(self::PROCESS_FETCH_RESULTS, 'completed') &&
					$this->canProcessRun(self::PROCESS_RESULTS))
				{
					return true;
				}
				else
				{
					return false;
				}
				break;

			case self::PHASE_RESCHEDULE:
				if (
					$this->checkAggregateProcessState(self::PROCESS_RESULTS, 'completed') &&
					$this->canProcessRun(self::PROCESS_RESCHEDULE))
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

	private function startProcessForAllCompanies($process)
	{
		$companies_running = array();
		foreach (array_keys($this->ecash_3_company_ids) as $company_id)
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
		Set_Process_Status($this->db, $company_id, $process, 'failed', $this->run_date, $this->company_processes[$process][$company_id]);
	}

	private function completeProcessForCompany($process, $company_id)
	{
		Set_Process_Status($this->db, $company_id, $process, 'completed', $this->run_date, $this->company_processes[$process][$company_id]);
	}

	public function runFetchPhase()
	{
		if ($this->phaseCanRun(self::PHASE_FETCH))
		{
			$company_ids = $this->startProcessForAllCompanies(self::PROCESS_FETCH_RESULTS);
			foreach ($company_ids as $company_id)
			{
				$this->fetchResultsFile($company_id);
			}
		}
	}

	public function runProcessPhase()
	{
		if ($this->phaseCanRun(self::PHASE_PROCESS))
		{
			$company_ids = $this->startProcessForAllCompanies(self::PROCESS_RESULTS);
			foreach ($company_ids as $company_id)
			{
				$this->processResults($company_id);
			}
		}
	}

	public function runReschedulePhase()
	{
		if ($this->phaseCanRun(self::PHASE_RESCHEDULE))
		{
			$company_ids = $this->startProcessForAllCompanies(self::PROCESS_RESCHEDULE);
			
			$companies = $this->ecash_3_company_ids;

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

	private function fetchResultsFile($company_id)
	{
		try
		{
			$this->switchCompanies($company_id);
			$this->log->Write("Running {$company_id}:".__METHOD__);
			
			if (!$this->ach->Fetch_ACH_File('results', $this->run_date))
			{
				$this->failProcessForCompany(self::PROCESS_FETCH_RESULTS, $company_id);
			}
			else
			{
				$this->completeProcessForCompany(self::PROCESS_FETCH_RESULTS, $company_id);
			}
		}
		catch (Exception $e)
		{
			$this->log->Write("There was an error fetching results: {$e->getMessage()} - TRACE\n{$e->getTraceAsString()}");
			$this->failProcessForCompany(self::PROCESS_FETCH_RESULTS, $company_id);
		}
	}

	private function processResults($company_id)
	{
		try
		{
			$this->switchCompanies($company_id);
			$this->log->Write("Running {$company_id}:".__METHOD__);
			$this->ach->Process_ACH_Results($this->run_date, $this->run_date);
			
			$this->completeProcessForCompany(self::PROCESS_RESULTS, $company_id);
		}
		catch (Exception $e)
		{
			$this->log->Write("There was an error processing returns: {$e->getMessage()} - TRACE\n{$e->getTraceAsString()}");
			$this->failProcessForCompany(self::PROCESS_RESULTS, $company_id);
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
}

?>
