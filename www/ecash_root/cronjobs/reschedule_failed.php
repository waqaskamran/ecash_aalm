<?php
/**
 * Script to run ACH rescheduling of failed transactions.
 */

function Main($argv)
{
	global $server;

	$rescheduler = new ECash_Rescheduler_Failed($server);
	$rescheduler->run();
}

class ECash_Rescheduler_Failed
{
	private $db;
	private $log;
	private $server;

	public function __construct($server)
	{
		$this->db = ECash::getMasterDb();
		$this->log = ECash::getLog('scheduling');
		$this->server = $server;
	}

	public function run()
	{
		$standby_list = $this->getStandbyList();
		foreach ($standby_list as $company_id => $company_array)
		{
			$reschedule_list = array_slice($company_array, 0, 100);
			$this->rescheduleApps($reschedule_list, $company_id);
		}	
	}

	/**
	 * Returns a multi-dimensional array applications under an 
	 * index of the company_id they're under that have the 'reschedule_failed'
	 * standby entry in the database.
	 *
	 * @return array
	 */
	private function getStandbyList()
	{
		$applications = array();
		$company_id = $this->server->company_id;
		$query = "
			SELECT DISTINCT application_id, company_id
			FROM standby
			WHERE process_type = 'reschedule_failed'
				AND company_id = {$company_id}
			ORDER BY date_created ASC ";

		$result = $this->db->Query($query);

		while ($row = $result->fetch(PDO::FETCH_OBJ)) 
		{
			$applications[$row->company_id][] = $row->application_id;
		}

		return $applications;
	}
	
	/**
	 * Fetches a list of applications in the standby table and then launches the
	 * Failure DFA.
	 *
	 * @param array $application_list
	 * @param int $company_id
	 * @return BOOL
	 */
	private function rescheduleApps($application_list, $company_id)
	{
		require_once(CUSTOMER_LIB."failure_dfa.php");

		$server = $this->server;
		$company_short = strtoupper($server->company);

		while (count($application_list) > 0)
		{
			$application_id = array_pop($application_list);
			try
			{
				$this->log->Write("[{$company_short}] Rescheduling previously failed Application ID {$application_id}");

				$fdfap = new stdClass();
				$fdfap->application_id = $application_id;
				$fdfap->server = $server;

				$fdfa = new FailureDFA($application_id);
				$fdfa->run($fdfap);

				Remove_Standby($application_id, 'reschedule_failed');
			}
			catch (Exception $e)
			{
				$this->log->Write("[{$company_short}] Unable to reschedule Application ID {$application_id}: {$e->getMessage()}");
				Remove_Standby($application_id, 'reschedule_failed');
				Set_Standby($application_id, $company_id, 'reschedule_failed_failed');
			}
		}

		return TRUE;
	}
}

?>
