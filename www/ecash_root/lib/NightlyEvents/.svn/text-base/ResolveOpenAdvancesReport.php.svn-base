<?php

	//$manager->Define_Task('Resolve_Open_Advances_Report', 'resolve_open_advances_report', null, null, array($server, $today), false); //no transaction

	class ECash_NightlyEvent_ResolveOpenAdvancesReport extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'resolve_open_advances_report';
		protected $timer_name = NULL;
		protected $process_log_name = NULL;
		protected $use_transaction = FALSE;
		
		public function __construct()
		{
			$this->classname = __CLASS__;
			
			parent::__construct();
		}
		
		/**
		 * Taken from the function Resolve_Open_Advances_Report()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();
			
			$oa_report_object = new Open_Advances_Report_Query($this->server);
			$oa_report_data   = $oa_report_object->Fetch_Open_Advances_Data(str_replace("-","",$this->today), 'all', $this->company_id, 'cli', true);
			// Open Advances Report Query now automatically saves the report...so this call is not needed.
			//	$oa_report_object->Save_Report_Data($oa_report_data, $today);

		}
	}


?>