<?php

	//	$manager->Define_Task('Set_Completed_Accounts_To_Inactive', 'completed_accounts_to_inactive', $scati_timer, 'set_inactive', array($server));

	class ECash_NightlyEvent_SetCompletedAccountsToInactive extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'completed_accounts_to_inactive';
		protected $timer_name = 'Set_Completed_Accounts_To_Inactive';
		protected $process_log_name = 'set_inactive';
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

			$this->Set_Completed_Accounts_To_Inactive($this->server);
		}

		protected function Set_Completed_Accounts_To_Inactive(Server $server)
		{

			$agent_queue_reason_model = ECash::getFactory()->getReferenceModel('AgentQueueReason');
			$agent_queue_reason_model->loadBy(array('name_short'=>'follow up',));
			$agent_queue_reason_id = $agent_queue_reason_model->agent_queue_reason_id;

			$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');

			$statuses = array();

			$statuses[] = 'new::collections::customer::*root';
			$statuses[] = 'contact::collections::customer::*root';
			$statuses[] = 'queued::contact::collections::customer::*root';
			$statuses[] = 'dequeued::contact::collections::customer::*root';
			$statuses[] = 'follow_up::contact::collections::customer::*root';
			$statuses[] = 'collections_rework::collections::customer::*root';
			$statuses[] = 'unverified::bankruptcy::collections::customer::*root';
			$statuses[] = 'verified::bankruptcy::collections::customer::*root';
			$statuses[] = 'current::arrangements::collections::customer::*root';
			$statuses[] = 'arrangements_failed::arrangements::collections::customer::*root';
			$statuses[] = 'hold::arrangements::collections::customer::*root';
			$statuses[] = 'arrangements::collections::customer::*root';
			$statuses[] = 'cccs::collections::customer::*root';

			$statuses[] = 'pending::external_collections::*root'; //[#34346]
			$statuses[] = 'active::servicing::customer::*root';
			$statuses[] = 'past_due::servicing::customer::*root';
			$statuses[] = 'indef_dequeue::collections::customer::*root';
			$statuses[] = 'sent::quickcheck::collections::customer::*root';
			$statuses[] = 'sent::external_collections::*root';
			$statuses[] = 'approved::servicing::customer::*root';
			
			$supplemental_query = "
				SELECT
					application_id,
					(
						SELECT count(tr.transaction_register_id)
						FROM transaction_register tr, transaction_type tt
						WHERE tr.application_id = app.application_id
							AND tr.transaction_type_id = tt.transaction_type_Id
							AND tt.name_short LIKE 'cancel%'
							AND tr.transaction_status = 'complete'
					) AS cancel_complete,
					(
						SELECT count(tr.transaction_register_id)
						FROM transaction_register tr, transaction_type tt
						WHERE tr.application_id = app.application_id
							AND tr.transaction_type_id = tt.transaction_type_Id
							AND tt.name_short = 'payment_service_chg'
							AND tr.transaction_status = 'complete'
					) AS paid_service_charges,
					(
						SELECT count(tr.transaction_register_id)
						FROM transaction_register tr
						WHERE tr.application_id = app.application_id
							
					) AS num_transactions
				FROM {$this->getTempTableName()} app
				WHERE application_id = ?
			";
			$info = $this->db->prepare($supplemental_query);
            
            $supplemental_query2 = "
                SELECT
                    application_id,
                    (
                        SELECT count(tr.transaction_register_id)
                        FROM transaction_register tr, transaction_type tt
                        WHERE tr.application_id = app.application_id
                            AND tr.transaction_type_id = tt.transaction_type_Id
                            AND tt.name_short LIKE 'full_balance%'
                            AND tr.transaction_status = 'complete'
                    ) AS full_pull,
                    (
                        SELECT count(tr.transaction_register_id)
                        FROM transaction_register tr
                        WHERE tr.application_id = app.application_id
                            
                    ) AS num_transactions
                FROM {$this->getTempTableName()} app
                WHERE application_id = ?
            ";
			$info2 = $this->db->prepare($supplemental_query2);
			
			// Pull applications from the application service first
			$this->createApplicationTempTable($this->fetchApplicationDataByStatus($statuses));

			// Select every application which...
			// Has only completed transactions (No new or pending transactions, and no scheduled events)
			// Has a balance of zero (see tr2, group by, having)
			// Has a valid status (see above query injected below)
			$main_query = "
				SELECT
					a1.application_id,
					a1.application_status,
					a1.is_react
				FROM
					{$this->getTempTableName()} AS a1
					LEFT JOIN transaction_register AS tr1
						ON (tr1.application_id = a1.application_id
							AND tr1.transaction_status IN ('new','pending'))
					LEFT JOIN event_schedule AS es1
						ON (es1.application_id = a1.application_id
							AND es1.event_status = 'scheduled')
					LEFT JOIN transaction_register AS tr2
						ON (tr2.application_id = a1.application_id
							AND tr2.transaction_status IN ('complete'))
				WHERE
					tr1.transaction_register_id IS NULL
					AND es1.event_schedule_id IS NULL
	            AND (tr2.transaction_type_id NOT IN (
	                SELECT transaction_type_id
	                FROM transaction_type
	                WHERE name_short like '%refund_3rd_party%'
				) OR tr2.transaction_type_id IS NULL)
				GROUP BY a1.application_id
				HAVING SUM(IFNULL(tr2.amount,0)) <= 0 
			";
			
			$results = $this->db->query($main_query);

			while ($row = $results->fetch(PDO::FETCH_OBJ))
			{
                $info2->execute(array($row->application_id));
                $row3 = $info2->fetch(PDO::FETCH_OBJ);
                if (($row3->full_pull > 0))
                {
					$this->log->Write("Application {$row->application_id}: Setting to Recovered (full pull).");
					$new_stat = array('recovered','external_collections','*root');
                }
				else if ($row->application_status == 'sent::external_collections::*root')
				{
					$this->log->Write("Application {$row->application_id}: Setting to Recovered.");
					$new_stat = array('recovered','external_collections','*root');
				}
				//[#34346] added pending::external_collections::*root -> Inactive (Settled)
				else if ($row->application_status == 'pending::external_collections::*root')
				{
					$this->log->Write("Application {$row->application_id}: Setting to Inactive (Settled).");
					$new_stat = array('settled','customer','*root');
				}
				else
				{
					$info->execute(array($row->application_id));
					$row2 = $info->fetch(PDO::FETCH_OBJ);

					if (($row2->cancel_complete > 0) && ($row->is_react == 0))
					{
						if($row2->paid_service_charges > 0)
						{
							$this->log->Write("Application {$row->application_id}: Setting to Paid Inactive (Cancelled)");
							$new_stat = array('paid', 'customer', '*root');
							//Send the Zero balance letter
						//	eCash_Document_AutoEmail::Queue_For_Send($server, $row->application_id, 'ZERO_BALANCE_LETTER');
						}
						else
						{
							$this->log->Write("Application {$row->application_id}: Setting to Withdrawn (Cancelled)");
							$new_stat = array('withdrawn', 'applicant', '*root');
						}
					}
					elseif($row2->num_transactions == 0)
					{
						$this->log->Write("Application {$row->application_id}: Setting to Withdrawn (Cancelled)");
						$new_stat = array('withdrawn', 'applicant', '*root');
					}
					else
					{
						$this->log->Write("Application {$row->application_id}: Setting to Inactive.");
						$new_stat = array('paid','customer','*root');
				//		eCash_Document_AutoEmail::Queue_For_Send($server, $row->application_id, 'ZERO_BALANCE_LETTER');
					}
				}

				try
				{
					Remove_Unregistered_Events_From_Schedule($row->application_id);
					Update_Status(NULL, $row->application_id, $new_stat);

					$application = ECash::getApplicationById($row->application_id);
					$affiliations = $application->getAffiliations();
					$affiliations->expire('collections', 'owner');
					
					// delete from my queue if not follow up
					$application_id = $row->application_id;
					if ($agent_queue_reason_id)
					{
						$agent_queue_model = ECash::getFactory()->getModel('AgentQueueEntry');
						$loaded = $agent_queue_model->loadBy(array('related_id'=>$application_id,
											   'agent_queue_reason_id'=>$agent_queue_reason_id,));
						if (!$loaded)
						{
							$queue_name = 'Agent';
							$qm = ECash::getFactory()->getQueueManager();
							$queue = $qm->getQueue($queue_name);
							$queue_item = new ECash_Queues_BasicQueueItem($application_id);
							$queue->remove($queue_item);
						}
					}
				}
				catch (Exception $e)
				{
					$this->log->Write("Movement of app {$row->application_id} to Inactive/Recovered failed.");
					//throw $e;
				}
			}
		}
		
		/**
		 * Returns application data by the specified status.
		 * 
		 * @param array $statuses
		 * @return array
		 */
		private function fetchApplicationDataByStatus(array $statuses)
		{
			$statuses = "'" . implode("','", $statuses) . "'";
			
			$query = "SELECT
					a.application_id application_id,
					aps.application_status_name application_status,
					a.is_react
				FROM
					application a
					INNER JOIN application_status aps
						ON a.application_status_id = aps.application_status_id
				WHERE aps.application_status_name IN ($statuses);
			";
			
			$result = $this->app_svc_db->query($query);
			return $result->fetchAll();
		}
		
		/**
		 * Creates an application temporary table based on the specified data.
		 * 
		 * @param array $data
		 */
		private function createApplicationTempTable(array $data)
		{
			$spec = array(
				'application_id' => 'INT UNSIGNED',
				'application_status' => 'VARCHAR(255)',
				'is_react' => 'TINYINT'
			);
			
			ECash_DB_Util::generateTempTableFromArray($this->db, $this->getTempTableName(), $data, $spec, 'application_id');
		}
		
		/**
		 * Returns the name of the temp table.
		 * 
		 * @return string
		 */
		private function getTempTableName()
		{
			return 'temp_setCompletedAccountsToInactive_application';
		}
	}


?>
