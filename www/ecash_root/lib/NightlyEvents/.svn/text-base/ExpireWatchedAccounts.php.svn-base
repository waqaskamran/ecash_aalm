<?php

	//	$manager->Define_Task('Expire_Watched_Accounts', 'expire_watched_accounts', $watched_acct_timer, 'expire_watched', array($server));

	class ECash_NightlyEvent_ExpireWatchedAccounts extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'expire_watched_accounts';
		protected $timer_name = 'Expire_Watched_Accounts';
		protected $process_log_name = 'expire_watched';
		protected $use_transaction = FALSE;

		public function __construct()
		{
			$this->classname = __CLASS__;

			parent::__construct();
		}

		/**
		 * A wrapper for the function Expire_Watched_Accounts()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();

			$this->Expire_Watched_Accounts();
		}

		private function Expire_Watched_Accounts()
		{
			$business_rules = new ECash_Business_Rules($this->db);

			$mssql_query = "CALL sp_commercial_has_watch_flag;";

			$app_service_result = ECash::getAppSvcDB()->query($mssql_query);

			$mssql_results = array();
			while ($row = $app_service_result->fetch(DB_IStatement_1::FETCH_OBJ))
			{
				$mssql_results[] = $row;
			}

			$columns = array(
				'application_id' => 'int',
				'company_id' => 'int'
			);

			ECash_DB_Util::generateTempTableFromArray($this->db, 'temp_has_watch_flag', $mssql_results, $columns, 'application_id');
			
			$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT
						t.application_id,
		                t.company_id,
						SUM(tr.amount) AS balance,
						(
							SELECT COUNT(*)
							FROM event_schedule es
							WHERE es.application_id = t.application_id
								AND es.date_effective > CURRENT_TIMESTAMP
						) AS scheduled_arrangements,
						watch_affiliation.agent_id,
						watch_affiliation.date_expiration
					FROM
						temp_has_watch_flag AS t
						JOIN transaction_register AS tr ON (tr.application_id = t.application_id)
						JOIN transaction_type AS tt ON (tt.transaction_type_id = tr.transaction_type_id)
						JOIN
						(
							SELECT af.agent_id, af.application_id, af.date_expiration
							FROM agent_affiliation AS af
							WHERE af.affiliation_area = 'watch'
								AND af.date_expiration < CURRENT_TIMESTAMP
								AND	af.company_id = {$this->company_id}
							GROUP BY application_id
							ORDER BY af.date_expiration desc
						) AS watch_affiliation ON (watch_affiliation.application_id = t.application_id)
					WHERE
						t.company_id = {$this->company_id}
						AND tr.transaction_status = 'complete'
						AND	tt.name_short NOT LIKE 'refund_3rd_party%'
						GROUP BY application_id
						HAVING balance >= 0
						ORDER BY date_expiration ASC
			";

			$result = $this->db->query($sql);
			$qm = ECash::getFactory()->getQueueManager();
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
		 		// If their balance is 0, set the status to inactive/paid
				if($row->balance == 0)
				{
					Update_Status(null, $row->application_id, array('paid', 'customer', '*root'));
					Set_Watch_Status_Flag($row->application_id, 'no');
					$this->log->Write("Watch: Application {$row->application_id}: Setting to Inactive/Paid.");
				}
				else
				{	// If there are still scheduled arrangements, reset
					// the expiration time on the agent affiliation to 30
					// days from now
					if($row->scheduled_arrangements > 0)
					{
						// Gotta grab the business rules for this applicant
						$rsid = $business_rules->Get_Rule_Set_Id_For_Application($row->application_id);
						$rules = $business_rules->Get_Rule_Set_Tree($rsid);

						$this->log->Write("Watch: Application {$row->application_id}: Still have pending arrangements.  Renewing affiliation.");

						$application = ECash::getApplicationById($row->application_id);
						$affiliations = $application->getAffiliations();
					
						$normalizer= new Date_Normalizer_1(new Date_BankHolidays_1());
						$date_expiration = $normalizer->advanceBusinessDays(time(), $rules['watch_period'] + 1);
						$agent = ECash::getAgentById($row->agent_id);
						$affiliations->add($agent, 'watch', 'owner', $date_expiration);
					
					}

					// If there are no arrangements, but there's still a balance
					// set the status to collections.

					// To handlle a situation, when a watch flag is set, the balance > 0, the last transaction is failed,
					// a reattempt (or other recovering) should not be scheduled.
					// In order to ensure this, the watch flag should not be removed - mantis:7961
					// The app is sent to Collection by ach_returns_dfa, State_23

					// RE: Mantis 7961 - The Watch flag was unset because the watch period expired.  This is correct.
					// When the affiliation expires, if there was a failure while the account had the Watch
					// flag, then rescheduling is held until the Watch flag expires. - BR
					else
					{
						Set_Watch_Status_Flag($row->application_id, 'no');
						$this->log->Write("Watch: Application {$row->application_id} expired and still has a balance.  Sending to Collections Queue.");
						$sort = "2 - " . date('Y-m-d');
					//	move_to_automated_queue('Collections General', $row->application_id, $sort, NULL, NULL);
						
						$qi = $qm->getQueue('collections_general')->getNewQueueItem($row->application_id);
						$qm->moveToQueue($qi, 'collections_general') ;
					}
				}
			}
		}
	}

?>