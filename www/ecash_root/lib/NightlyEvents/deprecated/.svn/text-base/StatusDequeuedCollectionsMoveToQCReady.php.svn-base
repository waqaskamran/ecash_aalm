<?php

	//	$manager->Define_Task('Status_Dequeued_Collections_Move_To_QC_Ready', 'deq_coll_to_qc_ready', $status_dequeued_timer, 'collections_qc', array($server));

/**
 * @deprecated
 */
	class ECash_NightlyEvent_StatusDequeuedCollectionsMoveToQCReady extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'deq_coll_to_qc_ready';
		protected $timer_name = 'Status_Dequeued_Collections_Move_To_QC_Ready';
		protected $process_log_name = 'collections_qc';
		protected $use_transaction = FALSE;

		public function __construct()
		{
			$this->classname = __CLASS__;

			parent::__construct();
		}
		
		/**
		 * A wrapper for the function Status_Dequeued_Collections_Move_To_QC_Ready()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();

			$this->Status_Dequeued_Collections_Move_To_QC_Ready($this->server);
		}

		/**
		 * Moves apps with collections/dequeued and fatal ACH code to QC Ready
		 * Looks for apps in the standby table to move.
		 */
		private function Status_Dequeued_Collections_Move_To_QC_Ready()
		{
			// If we're not using QuickChecks, disable QC Related activities
			if (ECash::getConfig()->USE_QUICKCHECKS === FALSE)
			{
				return TRUE;
			}

			$status_list = "
				('dequeued::contact::collections::customer::*root'),
				('follow_up::contact::collections::customer::*root'),
				('queued::contact::collections::customer::*root'),
				('return::quickcheck::collections::customer::*root')
			";

			/* set up the application service */
			$mssql_query = "
				DECLARE @STATUS_LIST table_type_varchar256;
				INSERT INTO @STATUS_LIST VALUES {$status_list};
				CALL sp_commercial_nightly_status_dequeued_to_qcready (@STATUS_LIST, 'IN');
			";

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

			ECash_DB_Util::generateTempTableFromArray($this->db, 'temp_move_in_qaready', $mssql_results, $columns, 'application_id');

			$sql = "
				SELECT
					st.application_id
				FROM
					standby AS st
					JOIN temp_move_qaready AS t ON (t.application_id = st.application_id)
				WHERE
					st.process_type = 'qc_ready'
					AND DATE_ADD(st.date_created, INTERVAL 1 day) < CURRENT_TIMESTAMP
					AND t.company_id = {$this->company_id}
			";

			$st = $this->db->query($sql);

			while($row = $st->fetch(PDO::FETCH_OBJ))
			{
				try
				{
					$this->log->Write("Application {$row->application_id}: Collections Dequeued -> QC Ready");
					Update_Status(NULL, $row->application_id, array( 'ready', 'quickcheck','collections','customer', '*root' ));
					Remove_Standby($row->application_id, 'qc_ready');

				}
				catch (Exception $e)
				{
					$this->log->Write("Movement of Collections Dequeued app {$row->application_id} to QC Ready failed.");
					throw $e;
				}
			}

			/* set up the application service */
			$mssql_query = "
				DECLARE @STATUS_LIST table_type_varchar256;
				INSERT INTO @STATUS_LIST VALUES {$status_list};
				CALL sp_commercial_nightly_status_dequeued_to_qcready (@STATUS_LIST, 'NOT IN');
			";

			$app_service_result = ECash::getAppSvcDB()->query($mssql_query);

			$mssql_results = array();
			while ($row = $app_service_result->Fetch_Array_Row(MYSQL_ASSOC))
			{
				$mssql_results[] = $row;
			}

			$columns = array(
				'application_id' => 'int',
				'company_id' => 'int'
			);

			ECash_DB_Util::generateTempTableFromArray($this->db, 'temp_move_notin_qaready', $mssql_results, $columns, 'application_id');

			// Now we remove anyone who's not in queued/dequeued/followup Collections
			// b/c they've been taken care of and we don't want to bloat the table.
			$sql = "
				SELECT
					st.application_id
				FROM
					standby AS st
					JOIN temp_move_notin_qaready AS t ON (t.application_id = st.application_id)
				WHERE
					st.process_type = 'qc_ready'
					AND t.company_id = {$this->company_id}
			";
			$st = $this->db->Query($sql);

			 while ($row = $st->fetch(PDO::FETCH_OBJ))
			{
				$this->log->Write("Removing standby entry for ({$row->application_id}, 'qc_ready')");
				Remove_Standby($row->application_id, 'qc_ready');
			}
		}
	}

?>