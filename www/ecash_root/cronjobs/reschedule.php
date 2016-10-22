<?php
/**
 * Quick and Dirty script to run ACH rescheduling against
 * what's currently in the standby table.
 */
function Main($argv)
{
	global $server;

	/**
	 * This is the maximum number of applications to reschedule in this run.
	 * If we pass a number on the command line, use it, else use the default
	 * of 100 applications.
	 */
	$reschedule_limit = (isset($argv[4]) && is_numeric($argv[4])) ? $argv[4] : 100;
	$lock_filename = '/tmp/reschduling_lock_' . getenv('ECASH_CUSTOMER');
	
	$rescheduler = new ECash_Rescheduler($server, ECash::getMasterDb(), $lock_filename, $reschedule_limit);
	$rescheduler->run();
	
}

class ECash_Rescheduler
{
	private $server;
	private $db;
	private $process_lock;
	
	/**
	 * The maximum number of applications to reschedule per company
	 * for one iteration.
	 *
	 * @var int
	 */
	private $reschedule_limit;
	
	public function __construct($server, $db, $lock_filename, $reschedule_limit = NULL)
	{
		$this->server = $server;
		$this->db = $db;
		$this->log = ECash::getLog('scheduling');
		$this->process_lock = new ECash_ProcessLock();
		$this->process_lock->setlockFilename($lock_filename);
		$this->reschedule_limit = $reschedule_limit;
	}
	
	public function run()
	{
		/**
		 * Check the lock status, exit if we have a valid lock,
		 * if there's a stale lock, do any cleanup necessary,
		 * and continue, and if we weren't locked, continue.
		 */
		$lock_status = NULL;
		if ($this->process_lock->isLocked($lock_status))
		{
			switch ($lock_status)
			{
				case ECash_ProcessLock::LOCK_STALE:
					// Do any cleanup here...
					break;

				case ECash_ProcessLock::LOCK_VALID:
				default:
					//Can't continue, there's a valid lock.
					return false;
					break;
			}
		}
		
		/**
		 * I hate these while(TRUE) loops, but this is supposed to run as a daemon.
		 * 
		 * The code should pull all standby entries for all companies, iterating
		 * through each, yet pulling only processing up to the max of the reschedule_limit
		 * so we can keep an even flow of applications going into the queues for each
		 * company.  Through each iteration, we'll pull in a new standby list since it may
		 * have grown since the last round.
		 */
		while(TRUE)
		{
			$start_time = time();
			$standby_list = $this->getStandbyList();
			foreach ($standby_list as $company_id => $company_array)
			{
				$reschedule_list = array_slice($company_array, 0, $this->reschedule_limit);
				$this->rescheduleApps($reschedule_list, $company_id);
			}
			
			/**
			 * If there was work to do the first run through, we want to just keep
			 * working, otherwise sleep and check again in a minute.
			 */
			if($start_time == time())
			{
				//echo "Nothing to do... I feel sleepy....\n";
				sleep(60);
			}
		}
	}

	/**
	 * Returns a multi-dimensional array applications under an 
	 * index of the company_id they're under that have the 'reschedule'
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
			WHERE process_type = 'reschedule'
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

		// Switch company here!
		$server = $this->server;
		$company_short = strtoupper($server->company);

		while (count($application_list) > 0)
		{
			$application_id = array_pop($application_list);
			try
			{
				$this->log->Write("[{$company_short}] Rescheduling Application ID {$application_id}");

				// Reschedule here
				$fdfap = new stdClass();
				$fdfap->application_id = $application_id;
				$fdfap->server = $server;

				$fdfa = new FailureDFA($application_id);
				$fdfa->run($fdfap);

				Remove_Standby($application_id, 'reschedule');
			}
			catch (Exception $e)
			{
				$this->log->Write("[{$company_short}] Unable to reschedule Application ID {$application_id}: {$e->getMessage()}");
				Remove_Standby($application_id, 'reschedule');
				Set_Standby($application_id, $company_id, 'reschedule_failed');
			}
		}

		return TRUE;
	}

}

?>
