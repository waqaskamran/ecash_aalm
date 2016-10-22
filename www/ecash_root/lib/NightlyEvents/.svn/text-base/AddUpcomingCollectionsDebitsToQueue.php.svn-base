<?php

	//	$manager->Define_Task('Add_Upcoming_Collections_Debits_To_Queue', 'add_collections_debits_to_queu', $aucdq_timer, 'add_collections_debits_to_queue', array($server, $today));

	class ECash_NightlyEvent_AddUpcomingCollectionsDebitsToQueue extends ECash_Nightly_AbstractAppServiceEvent
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'add_collections_debits_to_queu';
		protected $timer_name = 'Add_Upcoming_Collections_Debits_To_Queue';
		protected $process_log_name = 'add_collections_debits_to_queue';
		protected $use_transaction = FALSE;

		public function __construct()
		{
			$this->classname = __CLASS__;

			parent::__construct();
		}

		/**
		 * A wrapper for the function Add_Upcoming_Collections_Debits_To_Queue()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();

			$this->Add_Upcoming_Collections_Debits_To_Queue($this->start_date);
		}

		/**
		 * Add collections contact statuses w/ a scheduled debit in 2 days to the Collections General queue
		 *
		 * @param string $run_date Format: Y-m-d
		 */
		private function Add_Upcoming_Collections_Debits_To_Queue($run_date)
		{
			$collecions_contact_ids = array();
			$collecions_contact_ids[] = Status_Utility::Get_Status_ID_By_Chain('queued::contact::collections::customer::*root');
			$collecions_contact_ids[] = Status_Utility::Get_Status_ID_By_Chain('dequeued::contact::collections::customer::*root');

			$holidays = Fetch_Holiday_List();
			$pdc = new Pay_Date_Calc_3($holidays);

			$biz_rules = new ECash_BusinessRulesCache($this->db);

			$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'offline_processing');
			$rule_set_id = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
			$rules = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

			$period = ($rules['collections_debits_period']) ? $rules['collections_debits_period'] : 4;

			$period_day = $pdc->Get_Business_Days_Forward($run_date, $period);
			$period_date = date('Ymd', strtotime($period_day));
			$tomorrow = date('Ymd', strtotime("+1 Day", strtotime($run_date)));
			
			$this->createApplicationIdTempTable($this->getCollectionsStatuses());

			// We're looking for arrangements that are due on the period_date
			// that aren't adjustments and have an assoicated agent
			$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT DISTINCT
						a.application_id,
						tt.clearing_type
				FROM    event_schedule AS es
				JOIN    event_type AS et USING (event_type_id)
				JOIN    (SELECT event_type_id, transaction_type_id FROM event_transaction GROUP BY event_type_id) AS evt USING (event_type_id)
				JOIN    transaction_type AS tt ON (tt.transaction_type_id = evt.transaction_type_id)
		 		JOIN    " . $this->getTempTableName() . " AS a USING (application_id)
		 		LEFT JOIN agent_affiliation aa ON (
		 			aa.application_id = a.application_id AND
		 			aa.affiliation_status = 'active' AND
		 			(
		 				aa.date_expiration_actual IS NULL OR
		 				aa.date_expiration_actual > NOW()
		 			)
		 		)
				WHERE   es.event_status = 'scheduled'
				AND     es.date_effective = '{$period_date}'
				AND		es.company_id = '{$this->company_id}'
				AND		aa.agent_affiliation_id IS NULL
				HAVING clearing_type <> 'adjustment'
				 ";

			$result = $this->db->query($sql);

			$applications = array();
			while ($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$this->log->Write("[App: {$row->application_id}] Adding Application with collections status and 2 day future debit scheduled to Collections General queue");
				//move_to_automated_queue("Collections General", $row->application_id, "", time() ,NULL);
				$qm = ECash::getFactory()->getQueueManager();				
				if($qm->hasQueue('collections_general'))
				{
					$qi = $qm->getQueue('collections_general')->getNewQueueItem($row->application_id);
					$qm->moveToQueue($qi, 'collections_general') ;	
				}
			}
		}
		
		/**
		 * (non-PHPdoc)
		 * @see lib/ECash_Nightly_AbstractAppServiceEvent#getTempTableName()
		 */
		protected function getTempTableName()
		{
			return 'temp_addUpcomingCollectionsDebitsToQueue_application';
		}
		
		/**
		 * Returns an array of collection statuses.
		 * 
		 * @return array
		 */
		private function getCollectionsStatuses()
		{
			return array('queued::contact::collections::customer::*root', 'dequeued::contact::collections::customer::*root');
		}

	}

?>