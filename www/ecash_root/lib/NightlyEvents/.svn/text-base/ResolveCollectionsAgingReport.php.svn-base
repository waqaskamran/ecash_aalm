<?php

	//$manager->Define_Task('Resolve_Open_Advances_Report', 'resolve_open_advances_report', null, null, array($server, $today), false); //no transaction
	require_once(SERVER_CODE_DIR . "collections_aging_report.class.php");
	class ECash_NightlyEvent_ResolveCollectionsAgingReport extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = null;
		protected $timer_name = NULL;
		protected $process_log_name = NULL;
		protected $use_transaction = FALSE;
		
		public function __construct()
		{
			$this->classname = __CLASS__;
			
			parent::__construct();
		}
		
		/**
		 * Taken from the function Resolve_Collections_Aging_Report()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();
			
			$report_object = new Collections_Aging_Report_Query($this->server);
			$report_data   = $report_object->Fetch_Current_Data(str_replace("-","",$this->today), $this->company_id, 'cli', true);
			// Open Advances Report Query now automatically saves the report...so this call is not needed.
			//	$oa_report_object->Save_Report_Data($oa_report_data, $today);

		}
	}


?>
