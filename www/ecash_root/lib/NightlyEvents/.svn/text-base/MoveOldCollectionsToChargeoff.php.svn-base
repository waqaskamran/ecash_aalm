<?php

	/**
	 * This script will move accounts to the 'Chargeoff' status where:
	 * - 120 days or more have passed since entering collections [#48876] (used to be first failure)
	 * - 14 days or more have passed since their last failure
	 * - They have no scheduled transactions
	 * - They have no pending transactions [#38952]
	 * - They are in a Collections status.
	 *
	 * Spec: Business requirements: 
	 * 1.) A charge-off for CRA reporting purposes will be defined as a loan 
	 * in a "collections" status that has aged 120 days since entering collections [#48876]
	 */
	class ECash_NightlyEvent_MoveOldCollectionsToChargeoff extends ECash_Nightly_AbstractAppServiceEvent
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'move_old_collections_to_charge';
		protected $timer_name = 'Move_Old_Collections_To_Charge';
		protected $process_log_name = 'move_old_collections_to_charge';
		protected $use_transaction = FALSE;		

		public function __construct()
		{
			$this->classname = __CLASS__;

			parent::__construct();
		}

		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();

			$this->Move_Old_Collections_To_Chargeoff($this->server, $this->start_date);
		}

		/**
		 * @param Server $server
		 * @param string $run_date
		 */
		function Move_Old_Collections_To_Chargeoff(Server $server, $run_date)
		{
			$db = ECash::getMasterDb();
			$log = $server->log;

			$collections_statuses = array();
			$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
			$new_status = $asf->toId('chargeoff::collections::customer::*root');

			if (empty($new_status)) throw new Exception ("Unable to find status for chargeoff!");

			$company_id = $server->company_id;
			
			$this->createApplicationIdTempTable($this->getCollectionStatuses(), date('Y-m-d', strtotime('-120 days', strtotime($run_date))));

			// We're looking for accounts in our application status set (collections tree) that have had more than 120 days since first entering collections [#48876]
			// and have no scheduled entries at all
			$sql = "SELECT application_id
				FROM " . $this->getTempTableName() . "
				JOIN (SELECT application_id, MAX(date_effective) AS last_failure 
					  FROM transaction_register 
					  WHERE transaction_status =  'failed' and company_id = {$company_id}
					  GROUP BY application_id						 
					  HAVING DATE_ADD(last_failure, INTERVAL 14 DAY) < NOW() ) lf USING (application_id)
				LEFT OUTER JOIN (SELECT application_id, COUNT(*) AS scheduled_count
								 FROM event_schedule 
								 WHERE event_status = 'scheduled'
								 GROUP BY application_id) es USING (application_id)
				LEFT OUTER JOIN (SELECT application_id, COUNT(*) AS pending_count
								 FROM transaction_register
								 WHERE transaction_status = 'pending'
								 GROUP BY application_id) tr USING (application_id)				 
				WHERE scheduled_count IS NULL
				  AND pending_count IS NULL
								";

			$result = $db->Query($sql);

			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$log->Write("[App: {$row->application_id}] Moving account to Chargeoff");

				Update_Status(null, $row->application_id, array('chargeoff','collections','customer','*root'));

				// Per Impact, ChargeOff accounts do not need to be worked, so remove them from any automated queues.
				$qm = ECash::getFactory()->getQueueManager();
				$qm->removeFromAllQueues(new ECash_Queues_BasicQueueItem($row->application_id));
			}
		}

		protected function createApplicationIdTempTable($status_list, $first_date)
		{
			$insert_list = "'".implode("','",$status_list)."'";
			$mssql_db = ECash::getAppSvcDB();
			$query = 'CALL sp_fetch_application_ids_by_first_time_in_status ("'.$insert_list.'","'.$first_date'");';
			$results = $mssql_db->query($query);

			$app_ids = array();
			while ($row = $results->fetch())
			{
				$app_ids[] = $row;
			}
			
			ECash_DB_Util::generateTempTableFromArray($this->db, $this->getTempTableName(), $app_ids,
				$this->getTempTableSpec(), $this->getApplicationIdColumn());
		}
		
		/**
		 * (non-PHPdoc)
		 * @see lib/ECash_Nightly_AbstractAppServiceEvent#getTempTableName()
		 */
		protected function getTempTableName()
		{
			return 'temp_moveOldCollectionsToChargeoff_application';
		}
		
		/**
		 * Returns an array of collection statuses.
		 * 
		 * @return array
		 */
		private function getCollectionStatuses()
		{
			return array(
				'indef_dequeue::collections::customer::*root',
				'new::collections::customer::*root',
				'arrangements_failed::arrangements::collections::customer::*root',
				'current::arrangements::collections::customer::*root',
				'hold::arrangements::collections::customer::*root',
				'amortization::bankruptcy::collections::customer::*root',
				'dequeued::contact::collections::customer::*root',
				'follow_up::contact::collections::customer::*root',
				'queued::contact::collections::customer::*root',
				'arrangements::quickcheck::collections::customer::*root',
				'ready::quickcheck::collections::customer::*root',
				'return::quickcheck::collections::customer::*root',
				'sent::quickcheck::collections::customer::*root'
			);
		}
	}

?>
