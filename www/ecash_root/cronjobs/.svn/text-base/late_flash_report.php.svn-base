<?php
require_once(LIB_DIR.'Nightly_Event.class.php');
require_once(LIB_DIR.'NightlyEvents/ResolveFlashReport.php');

		/**
		 * This calls the process that creates the daily flash report snapshot. Which resides in the nightly.
		 * This cron exists because HMS [#21831] wants a report that displays exactly the same data as the flash report, but displays
		 * it in a different manner and they don't want it called the flash report, they want it called 'status snapshot'.  They
		 * also want this report populated with data generated at 2AM as opposed to 10PM which is when the nightly runs.
		 * This necessitates the following cronjob:  [W!-12-03-2008][#21831]
		 */
		function Main()
		{
			
			global $server;
			//We're using yesterday as the date we're generating for. 
			$start_date = date('Y-m-d',strtotime('-1 day'));
			$flash_report = new ECash_NightlyEvent_ResolveFlashReport($server,$server->company_id);
			
			$flash_report->setServer($server);
			$flash_report->setCompanyId($server->company_id);
			$flash_report->setLog($server->log);
			$flash_report->setStartDate($start_date);
			$flash_report->run();
		}


?>