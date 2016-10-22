<?php

	// 	$manager->Define_Task('Resolve_Flash_Report', 'resolve_flash_report', $resolve_timer, 'resolve_flash', array($server, $today));


	class ECash_NightlyEvent_ResolveFlashReport extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'resolve_flash_report';
		protected $timer_name = 'Resolve_Flash_Report';
		protected $process_log_name = 'resolve_flash';
		protected $use_transaction = FALSE;

		/**
		 * Taken from the function Resolve_Flash_Report()
		 * originally located in ecash3.0/cronjobs/resolve_flash_report.php
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites
			parent::run();
			$this->Resolve_Flash_Report($this->start_date);
		}

		private function Resolve_Flash_Report($run_date)
		{
			global $_BATCH_XEQ_MODE;
			$this->log->Write("Executing resolve flash report data for run date {$run_date}. [Mode: {$_BATCH_XEQ_MODE}]");
			$db = ECash::getMasterDb();
			
			// Make sure this cron can be re-run safely without creating lots of duplicate records by
			// first deleting all records that already exist for yesterday's date.
			$sql = "
				delete from resolve_flash_report
				where date = '{$run_date}'
				and company_id = {$this->company_id}
			";

			$rows_deleted += $this->db->exec($sql);
			$this->log->Write("Deleted $rows_deleted rows to avoid inserting duplicates. [Mode: {$_BATCH_XEQ_MODE}]");

			// Warning, if you use today's date, you will not be as accurate as waiting
			// until past midnight and running for "yesterday".  This is because the
			// day may not yet be over, and more changes may yet happen.
			$retroactive = FALSE;
			
			//We do not run the Flash Report retroactively, and even if we did, this code would blow up because it tries to generate
			//a temporary table. This code could probably stand to be rewritten, but it was promised to go out in a release tomorrow
			//But don't worry, we'll refactor it when things slow down, and we get more people, I promise.  Also, your dog isn't here
			//any more because he went away to the farm where he can play with other dogs.  Don't cry.[W!-12-03-2008][#21831]
//			if(date("Y-m-d") != $run_date)
//			{
//				// This will cause a modification of a later query to use this retroactive data
//				$retroactive = TRUE;
//
//				// This query will fix retroactive entry problems
//				$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
//					INSERT INTO status_history
//					(
//						date_created,
//						company_id,
//						application_id,
//						status_history_id,
//						status_history_related_id,
//						agent_id,
//						application_status_id
//					)
//					SELECT
//						a.date_created,
//						a.company_id,
//						a.application_id,
//						0,
//						NULL,
//						a.agent_id,
//						a.application_status_id
//					FROM application AS a
//						LEFT JOIN status_history AS sh ON (a.application_id = sh.application_id)
//					WHERE sh.application_id IS NULL
//				";
//				$rows_inserted += $this->db->exec($sql);
//				$this->log->Write("Inserted false status_history entries for applications showing no entries. [rows_inserted: {$rows_inserted}] [Mode: {$_BATCH_XEQ_MODE}]");
//
//				// This has been split off to reduce server load and lock times
//				$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
//					CREATE TEMPORARY TABLE
//						resolve_flash_report__latest_status_history
//							( `application_id` INT UNSIGNED NOT NULL
//							, `application_status_id` INT UNSIGNED NOT NULL
//							, PRIMARY KEY (`application_id`)
//							, KEY `application_status_id` (`application_status_id`)
//							)
//					SELECT application_id, application_status_id
//					FROM status_history
//					WHERE date_created <= '$run_date 23:59:59'
//						AND company_id = {$this->company_id}
//					GROUP BY application_id
//					ORDER BY status_history_id DESC
//				";
//				$rows_inserted += $this->db->exec($sql);
//				$this->log->Write("Created temporary table of status_history entries. [rows_inserted: {$rows_inserted}] [Mode: {$_BATCH_XEQ_MODE}]");
//			}

			// This is the actual report
			$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT
					NOW()                   AS `date_created` ,
					'$run_date'             AS `date`         ,
					c.company_id            AS `company_id`   ,
					c.name_short            AS `company_name` ,
					a.income_frequency      AS `model`        ,
					lt.name_short           AS `loan_type`    ,
					aps.name                AS `status`       ,
					COUNT(a.application_id) AS `count`
				FROM application AS a
				JOIN company AS c ON (a.company_id = c.company_id)
				JOIN loan_type AS lt ON (a.loan_type_id = lt.loan_type_id)
				" . ( $retroactive ? "
					JOIN resolve_flash_report__latest_status_history AS sh ON ( 1 = 1
							AND a.application_id = sh.application_id
							)
					JOIN application_status AS aps ON ( 1 = 1
							AND sh.application_status_id = aps.application_status_id
							)
				" : "
					JOIN application_status AS aps ON ( 1 = 1
							AND a.application_status_id = aps.application_status_id
							)
				" ) . "
				WHERE a.company_id = {$this->company_id}
				GROUP BY
					`company_id`,
					`company_name`,
					`model`,
					`loan_type`,
					`status`
				ORDER BY
					`company_id`,
					`company_name`,
					`model`,
					`loan_type`,
					`status`
			";
			$result = $this->db->query($sql);
			$rows_inserted = 0;
			$rows_selected = 0;
			$insert = NULL;

			while($row = $result->fetch(PDO::FETCH_ASSOC))
			{
				
				if (!$insert)
				{
					$query = "
						INSERT INTO resolve_flash_report
						(".join(', ', array_keys($row)).")
						VALUES (?".str_repeat(', ?', count($row) - 1) .")
					";
					$insert = $this->db->prepare($query);
				}

				$rows_selected ++;
				
				$insert->execute(array_values($row));
				$rows_inserted += $insert->rowCount();
			}

			$this->log->Write("Finished executing ".($retroactive ? "retroactive " : "")."resolve flash report data. [rows_inserted: {$rows_inserted}/{$rows_selected}] [Mode: {$_BATCH_XEQ_MODE}]");

		}
	}


?>