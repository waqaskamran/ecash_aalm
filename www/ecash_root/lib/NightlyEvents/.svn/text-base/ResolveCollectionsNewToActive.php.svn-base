<?php

	/**
	 * This process will locate Collections New applications whose last transaction 
	 * has completed and has scheduled payments.
	 */
	//class ECash_NightlyEvent_ResolveCollectionsNewToActive extends ECash_Nightly_AbstractAppServiceEvent
	class ECash_NightlyEvent_ResolveCollectionsNewToActive extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'resolve_collections_new_to_act';
		protected $timer_name = 'Resolve_Collections_New_To_Active';
		protected $process_log_name = 'resolve_collections_new';
		protected $use_transaction = FALSE;

		public function __construct()
		{
			$this->classname = __CLASS__;

			parent::__construct();
		}

		/**
		 * A wrapper for the function Resolve_Collections_New_To_Active()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			parent::run();

			$this->Resolve_Collections_New_To_Active($this->start_date, $this->end_date);
		}

		/* This function is similar to Resolve_Past_Due_To_Active but it works with apps in the
		 * Collections New status.  What we want to do is check to see if their account is in good
		 * standing and that they haven't used any arrangements to get there.
		 *
		 */
		private function Resolve_Collections_New_To_Active($start_date, $end_date)
		{
			//$this->createApplicationIdTempTable(array('new::collections::customer::*root'));

			// First, grab all people under Past Due
			$select_query = "
			SELECT
				ap.application_id AS application_id
			FROM
				application AS ap
			JOIN
				application_status_flat AS st USING (application_status_id)
			WHERE
				st.level0 IN ('new','indef_dequeue')
			AND
				st.level1 = 'collections'
			AND
				EXISTS (
					SELECT 'X'
					FROM event_schedule es
					JOIN event_type AS et USING (event_type_id)
					WHERE es.application_id = ap.application_id 
					AND   es.event_status = 'scheduled'
					AND   et.name_short IN ('payment_service_chg', 'repayment_principal')
				)
			";
			
			$st = $this->db->query($select_query);

			while ($app = $st->fetch(PDO::FETCH_OBJ))
			{
				// For each one, find their last transactions and see if they
				// fit the criteria: any non-zero debits are completed.
				$query = "
					SELECT transaction_status, amount, date_effective,
				       	transaction_register_id, transaction_type_id
					FROM transaction_register
					WHERE application_id = {$app->application_id}
					AND date_effective = (SELECT MAX(date_effective)
					      		FROM transaction_register
						      	WHERE application_id = {$app->application_id})
					AND amount < 0.00
				";
				$st2 = $this->db->query($query);

				$reset_to_active = false;

				while ($row = $st2->fetch(PDO::FETCH_OBJ))
				{
					if ($row->transaction_status != 'complete')
					{
						$reset_to_active = false;
						break;
					}
					else
					{
						$reset_to_active = true;
					}
				}

				if (!$reset_to_active) continue;

				try
				{
					$this->log->Write("Set application {$app->application_id} from Collections New to Active.");
					Update_Status(NULL, $app->application_id, 'active::servicing::customer::*root');
				}
				catch (Exception $e)
				{
					$this->log->Write("Setting application {$app->application_id} Collections New -> Active failed.");
					throw $e;
				}
			}

			return true;
		}
		
		/**
		 * (non-PHPdoc)
		 * @see lib/ECash_Nightly_AbstractAppServiceEvent#getTempTableName()
		 */
		protected function getTempTableName()
		{
			return 'temp_resolveCollectionsNewToActive_application';
		}
	}

?>
