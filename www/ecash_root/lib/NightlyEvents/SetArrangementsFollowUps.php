<?php

	//	$manager->Define_Task('Set_Arrangements_Follow_Ups', 'set_arr_followup', $sarfu_timer, 'set_arr_followup', array($server, $today));

	class ECash_NightlyEvent_SetArrangementsFollowUps extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'set_arr_followup';
		protected $timer_name = 'Set_Arrangements_FollowUps';
		protected $process_log_name = 'set_arr_followup';
		protected $use_transaction = FALSE;

		public function __construct()
		{
			$this->classname = __CLASS__;

			parent::__construct();
		}

		/**
		 * A wrapper for the function Set_Arrangements_Follow_Ups()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();

			$this->Set_Arrangements_Follow_Ups($this->start_date);
		}

		private function Set_Arrangements_Follow_Ups($run_date)
		{
			require_once(SERVER_CODE_DIR . "follow_up.class.php");

			$holidays = Fetch_Holiday_List();
			$pdc = new Pay_Date_Calc_3($holidays);

			$biz_rules = new ECash_BusinessRulesCache($this->db);

			$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'offline_processing');
			$rule_set_id  = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
			$rules        = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

			$period = ($rules['arrangements_followup_period']) ? $rules['arrangements_followup_period'] : 4;  // This will be THREE days on the next morning

			$period_day  = $pdc->Get_Business_Days_Forward($run_date, $period);
			$period_date = date('Ymd', strtotime($period_day));
			$tomorrow    = date('Ymd', strtotime("+1 Day", strtotime($run_date)));

			// We're looking for arrangements that are due on the period_date
			// that aren't adjustments and have an assoicated agent
			$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT
					es.application_id, (
						SELECT agent_id
						FROM agent_affiliation
						WHERE
							application_id = es.application_id AND
							affiliation_area = 'collections' AND
							affiliation_type = 'owner' AND
							affiliation_status = 'active'
						ORDER BY
							date_expiration_actual DESC
						LIMIT 1
					)
				FROM
					event_schedule es
					JOIN event_transaction USING (event_type_id)
					JOIN transaction_type tt USING (transaction_type_id)
				WHERE
					es.date_effective = '{$period_day}' AND
					( es.context = 'arrangement' OR es.context = 'partial' ) AND
					es.event_status = 'scheduled' AND
					tt.clearing_type <> 'adjustment' AND
					es.company_id = {$this->company_id}
				GROUP BY
					es.application_id
			";

			$result = $this->db->query($sql);

			while ($row = $result->fetch(PDO::FETCH_OBJ))
			{
				if(!empty($row->agent_id)) {
					// Set Follow-Up time by updating the status
					$this->log->Write("Updating Arrangement follow up time for App: {$row->application_id}, Agent: {$row->agent_id}");

					Follow_Up::createCollectionsFollowUp($row['application_id'], date('Y-m-d H:i:s'), $row['agent_id'], $this->company_id, "This account has an arrangement due in two days.", NULL, 'arrangement_due');
				}
			}
		}

	}

?>
