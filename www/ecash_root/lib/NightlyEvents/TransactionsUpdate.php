<?php

	//	$manager->Define_Task('Nightly_Transactions_Update', 'nightly_transactions_update', $nightly_transcation_timer, 'nightly_trans', array($server, $ach, $temp_ach, $non_ach, $pdc, $today), false);

	class ECash_NightlyEvent_TransactionsUpdate extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'nightly_transactions_update';
		protected $timer_name = 'Nightly_Transactions_Update';
		protected $process_log_name = 'nightly_trans';
		protected $use_transaction = FALSE;

		public function __construct()
		{
			$this->classname = __CLASS__;

			parent::__construct();
		}

		/**
		 * Taken from the function Nightly_Transactions_Update()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();

			$ach	  = new ACH_Utils($this->server);
			$non_ach  = new Non_ACH_Actions($this->server);
			
			$holidays = Fetch_Holiday_List();
			$pdc = new Pay_Date_Calc_3($holidays);
			$tomorrow = $pdc->Get_Business_Days_Forward($this->today, 1);

			// Propagating "current" scheduled events to the trans register should be the first thing we do
			// The second function here gets all non-ACH items, and moves the "new" ones straight to "pending"

			/**
			 * Accrued charges and adjustments go to the register at the end of the day.
			 * Per Impact's GF #10239 we register external (manual payments, arragements) the
			 * night beforehand.
			 */
			Record_Current_Scheduled_Events_To_Register($this->today, NULL, NULL, 'accrued charge');
			Record_Current_Scheduled_Events_To_Register($this->today, NULL, NULL, 'adjustment');
			Record_Current_Scheduled_Events_To_Register($tomorrow,    NULL, NULL, 'external');

			$this->Update_Non_ACH_Register_Items();

			$ach->ACH_Deem_Successful($this->today);

			//$this->Clear_Completed_Quickchecks($this->today);

			$non_ach->Non_ACH_Deem_Successful($this->today);

			$non_ach->Reschedule_Non_ACH_Failures();

		}

		/**
		 * Changes all "new" register items that have a non-ACH and non-Card clearing type to "pending",
		 * because it's done by ACH code for the ACH-cleared items.
		 */
		private function Update_Non_ACH_Register_Items()
		{
			$map = $this->Get_Transaction_Status_Map($this->company_id);
			$agent_id = Fetch_Current_Agent();

			$typelist = array();
			foreach ($map as $type) {
				if (($type->clearing_type != 'ach') && ($type->clearing_type != 'card')) {
					$typelist[] = $type->transaction_type_id;
				}
			}

			$upd_query = "
				UPDATE transaction_register
				SET transaction_status = 'pending',
					modifying_agent_id = '{$agent_id}'
				WHERE transaction_status = 'new'
					AND transaction_type_id IN (".implode(",",$typelist).")
			";
			$rows = $this->db->exec($upd_query);

			$this->log->Write("Updated {$rows} non-ACH rows from 'new' to 'pending'.");
		}

		/*
		 * Created to independently clear Quickchecks, since they work on calendar days, and not
		 * business days.
		 */
		private function Clear_Completed_Quickchecks($date)
		{
			// If we're not using QuickChecks, disable QC Related activities
			if(ECash::getConfig()->USE_QUICKCHECKS === FALSE) return TRUE;

			$query = "
				SELECT transaction_type_id,
					pending_period,
					end_status
				FROM transaction_type
				WHERE name_short = 'quickcheck'
			";
			$row = $this->db->querySingleRow($query);
			if (!$row) throw new Exception('Missing quickcheck transaction type');
			
			$ttid = $row['transaction_type_id'];
			$pp = $row->pending_period;
			$es = $row->end_status;

			$clear_date = date("Y-m-d", strtotime("-{$pp} days", strtotime($date)));

			$query2 = "
				SELECT
					tr.application_id,
					tr.transaction_register_id,
					es.date_event,
					es.date_effective,
					tr.date_effective
				FROM transaction_register tr
					JOIN event_schedule AS es USING (event_schedule_id)
				WHERE tr.transaction_status = 'pending'
					AND es.date_event <= '{$clear_date}'
					AND tr.transaction_type_id = {$ttid}
			";
			
			$r2 = $this->db->query($query2);

			while ($row2 = $r2->fetch(PDO::FETCH_OBJ))
			{
				$this->log->Write("Clearing Quickcheck related to application id {$row2->application_id}");
				Post_Transaction($row2->application_id, $row2->transaction_register_id);

				// Now if they still have a balance, we want
				// to put them back into Quickcheck Ready so
				// that another QC will be created and sent.
				// UNLESS they have already had two quick checks,
				// then the account will be sent to 2nd tier.

				$schedule = Fetch_Schedule($row2->application_id);
				$status = Analyze_Schedule($schedule);

				$r3 = Get_Current_Balance($row2->application_id);
				if($r3 > 0)
				{
					if ($status->num_qc < 2) {
						Update_Status(null, $row2->application_id,
								   array('ready','quickcheck','collections','customer','*root'));
					} else {
						Update_Status(null, $row2->application_id,
										array('pending','external_collections','*root'));
					}
				}
			}
		}

		private function Get_Transaction_Status_Map($company_id)
		{
			$query = "
				SELECT
		      name_short,
		      transaction_type_id,
		      clearing_type,
		      pending_period,
		      end_status
				FROM transaction_type
				WHERE
					company_id = {$company_id}
			";
			$st = $this->db->query($query);

			$map = array();
			while ($row = $st->fetch(PDO::FETCH_OBJ))
			{
				$map[$row->name_short] = $row;
			}

			return $map;
		}
	}


?>
