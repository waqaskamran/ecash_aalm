<?php

	//  $manager->Define_Task('Resolve_DDA_History_Report', 'resolve_dda_history_report', $resolve_dda, 'resolve_dda_history', array($server, $ach, $co, $today));

	class ECash_NightlyEvent_ResolveDDAHistoryReport extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'resolve_dda_history_report';
		protected $timer_name = 'Resolve_DDA_History';
		protected $process_log_name = 'resolve_dda_history';
		protected $use_transaction = FALSE;

		/**
		 * Taken from the function Resolve_DDA_History_Report()
		 * originally located in ecash3.0/cronjobs/resolve_dda_history_report.php
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites
			parent::run();

			$this->Resolve_DDA_History_Report($this->start_date);
		}

		private function Resolve_DDA_History_Report($run_date)
		{
			global $_BATCH_XEQ_MODE;
			
			//[#37444] Only run this report once per enterprise
			$ach = new ACH_Utils($this->server);
			if(($company = $ach->Check_Enterprise_Has_Run($this->process_log_name, $run_date)) !== FALSE)
			{
				$this->log->Write("Skipping run of Resolve DDA History report. Already run by: {$company} [Mode: {$_BATCH_XEQ_MODE}]");
				return;
			}

			$this->log->Write("Executing resolve DDA History report data for run date {$run_date}. [Mode: {$_BATCH_XEQ_MODE}]");

			$sub_sql = "
		      	INSERT INTO reports_dda_history
      			(dda_history__id, date, agent_id, application_id, event_schedule_id, description)
		      	VALUES (?, ?, ?, ?, ?, ?)
		      ";
			$insert = $this->db->prepare($sub_sql);

			$query = "
	        -- eCash3.0 ".__FILE__.":".__LINE__.":".__METHOD__."()
	        SELECT  `id`            AS      `id`
	            ,   `date`          AS      `date`
	            ,   `class`         AS      `class`
	            ,   `serialized`    AS      `serialized`
	        FROM    `dda_history`
	        WHERE   `date`          BETWEEN '$run_date 00:00:00'
	                                AND     '$run_date 23:59:59'
	        ";
			$result = $this->db->query($query);
			$rows_inserted = 0;
			$rows_selected = 0;

			while($row = $result->fetch(PDO::FETCH_ASSOC))
			{
				$rows_selected++;
				$more = unserialize($row['serialized']);

				$insert->execute(array(
									 $row['id'],
									 $row['date'],
									 (int)$more['agent_id'],
									 (int)$more['application_id'],
									 (int)$more['event_schedule_id'],
									 (string)$more['action'],
									 ));

				$rows_inserted += $insert->rowCount();
			}

			$this->log->Write("Finished executing resolve DDA History report data. [rows_inserted: {$rows_inserted}/{$rows_selected}] [Mode: {$_BATCH_XEQ_MODE}]");
		}
	}


?>