<?php
require_once(LIB_DIR.'Nightly_Event.class.php');
require_once(LIB_DIR.'NightlyEvents/ResolveCurrentLeadStatusReport.php');

		/**
		 * This call runs the Current Lead Status Report at 2AM. The report works exactly
		 * like the normal Current Lead Status Report, but HMS wanted a snapshot taken of 
		 * the information at 2AM. [kb][GF #21830]
		 */

		function Main()
		{
			global $server;
			//We're using yesterday as the date we're generating for. 
			$start_date = date('Y-m-d',strtotime('-1 day'));
			$report = new ECash_NightlyEvent_ResolveCurrentLeadStatusReport($server,$server->company_id);
			
			$report->setServer($server);
			$report->setCompanyId($server->company_id);
			$report->setLog($server->log);
			$report->setStartDate($start_date);
			$report->run();
		}
?>