<?php

	//	$manager->Define_Task('Set_QC_Returns_To_Second_Tier', 'set_qc_to_2nd_tier', $qc_to_2nd_tier_timer, 'qc_2nd_tier', array($server));

	class ECash_NightlyEvent_SetQCReturnsToSecondTier extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'set_qc_to_2nd_tier';
		protected $timer_name = 'Set_QC_Returns_To_Second_Tier';
		protected $process_log_name = 'qc_2nd_tier';
		protected $use_transaction = FALSE;

		public function __construct()
		{
			$this->classname = __CLASS__;

			parent::__construct();
		}

		/**
		 * A wrapper for the function Set_Completed_Accounts_To_Inactive()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();

			$this->Set_QC_Returns_To_Second_Tier();
		}

		// This moves Apps that have fatal returns or more than one non-fatal
		// returns that haven't had arrangements made after being viewed by
		// the collections agents to External Collections
		private function Set_QC_Returns_To_Second_Tier()
		{
			// If we're not using QuickChecks, disable QC Related activities
			if(ECash::getConfig()->USE_QUICKCHECKS === FALSE) return TRUE;

			// All the statuses to include in the update
			$query = "
				SELECT asf.application_status_id
				FROM application_status_flat asf
				WHERE
					(
						asf.level0 = 'return'
						AND asf.level1 = 'quickcheck' AND asf.level2 = 'collections'
						AND asf.level3 = 'customer' AND asf.level4 = '*root'
					) OR (
						asf.level0 = 'follow_up'
						AND asf.level1 = 'contact' AND asf.level2 = 'collections'
						AND asf.level3 = 'customer' AND asf.level4 = '*root'
					)
			";
			$statuses = $this->db->querySingleColumn($query);

			// Find apps that have not had arrangements made through Collections,
			// move them to Second Tier status, then remove their standby table entry.
			//mantis:7357 - filter company
			$query = "
				SELECT a.application_id, a.application_status_id, st.process_type
				FROM standby st, application a
				WHERE a.application_id = st.application_id
					AND a.company_id = {$this->company_id}
					AND st.process_type = 'qc_return'
					AND	a.application_status_id in ( " .implode(",", $statuses) . ")
					AND DATE_SUB(st.date_created, INTERVAL -7 DAY) <= CURDATE()
			";
			
			$st = $this->db->query($query);

			while ($row = $st->fetch(PDO::FETCH_OBJ))
			{
				try
				{
					$this->log->Write("Application {$row->application_id}: Moved to External Collections");
					Update_Status(NULL, $row->application_id, array( 'pending', 'external_collections', '*root' ));
					Remove_Standby($row->application_id, 'qc_return');
				}
				catch (Exception $e)
				{
					$this->log->Write("Movement to External Collections for app {$row->application_id} failed.");
					throw $e;
				}
			}

      // Now we remove anyone who's not in queued/dequeued/followup Collections
      // b/c they've been taken care of and we don't want to bloat the table.
			//mantis:7357 - filter company
			$sql = "
				SELECT st.application_id
				FROM standby st, application app, application_status_flat asf
				WHERE st.process_type = 'qc_return'
					AND app.application_id = st.application_id
					AND app.company_id = {$this->company_id}
					AND app.application_status_id = asf.application_status_id
					AND asf.application_status_id NOT IN ( " .implode(",", $statuses) . ")
			";
			$st = $this->db->query($sql);

      while ($row = $st->fetch(PDO::FETCH_OBJ))
      {
        $this->log->Write("Removing standby entry for ({$row->application_id}, 'qc_return')");
        Remove_Standby($row->application_id, 'qc_return');
      }
		}

	}


?>