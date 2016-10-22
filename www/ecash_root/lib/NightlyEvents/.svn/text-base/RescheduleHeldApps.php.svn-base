<?php

	//	$manager->Define_Task('Reschedule_Held_Apps', 'reschedule_held_apps', $reschedule_held_timer, 'reschedule_held_apps', array($server), false);

	class ECash_NightlyEvent_RescheduleHeldApps extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'reschedule_held_apps';
		protected $timer_name = 'Reschedule_Held_Applications';
		protected $process_log_name = 'reschedule_held_apps';
		protected $use_transaction = FALSE;

		public function __construct()
		{
			$this->classname = __CLASS__;

			parent::__construct();
		}

		/**
		 * A wrapper for the function Reschedule_Held_Apps()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();

			$this->Reschedule_Held_Apps($this->server);
		}

		// Finds applications that have had their rescheduling postponed
		// and are no longer in a status that restricts them from rescheduling
		private function Reschedule_Held_Apps (Server $server)
		{
			require_once(CUSTOMER_LIB."/failure_dfa.php");
			require_once(LIB_DIR . "status_utility.class.php");

			$reschedule_list = array();
			
			$application_ids = $this->fetchApplicationDataByStatusNotIn($this->getDisallowedStatuses());
			$application_ids = implode(',', $application_ids);
						
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT DISTINCT
					st.application_id
				FROM
					standby st
				WHERE
					st.process_type = 'hold_reschedule'
					AND st.company_id = $this->company_id
					AND st.application_id IN ($application_ids)
			";

			$result = $this->db->query($query);
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$this->log->Write("Found {$row->application_id} for postponed rescheduling");
				$reschedule_list[] = $row->application_id;
			}

			// If the list is empty, skip the next step
			if(count($reschedule_list) == 0)
			{
				return true;
			}

			foreach($reschedule_list as $application_id)
			{
				try
				{
					$fdfa = new FailureDFA($application_id);

					$fdfap = new stdClass();
					$fdfap->application_id = $application_id;
					$fdfap->server = $server;
					$fdfa->run($fdfap);
					Remove_Standby($application_id, 'hold_reschedule');
					unset($fdfap);
				}
				catch (Exception $e)
				{
					$this->log->Write("Unable to reschedule app {$application_id}: {$e->getMessage()}");
					throw $e;
				}
			}
		}
		
		private function getDisallowedStatuses()
		{
			return array(
				'hold::arrangements::collections::customer::*root',
				'unverified::bankruptcy::collections::customer::*root',
				'verified::bankruptcy::collections::customer::*root',
				'amortization::bankruptcy::collections::customer::*root',
				'skip_trace::collections::customer::*root',
				'cccs::collections::customer::*root'
			);
		}
		
		private function fetchApplicationDataByStatusNotIn(array $statuses)
		{
			//$statuses = "''" . implode("'',''", $statuses) . "''";
			$statuses = "'" . implode("','", $statuses) . "'";
			
			$query = "SELECT a.application_id application_id
					FROM application a
					INNER JOIN application_status aps ON a.application_status_id = aps.application_status_id
					WHERE aps.application_status_name NOT IN ($statuses)
						AND is_watched = 1;";
			
			$result = $this->app_svc_db->query($query);
			
			$application_ids = array();
			while ($row = $result->fetch())
			{
				$application_ids[] = $row['application_id'];
			}
			
			return $application_ids;
		}
	}

?>
