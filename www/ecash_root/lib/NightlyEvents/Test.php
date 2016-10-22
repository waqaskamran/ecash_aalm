<?php

	class ECash_NightlyEvent_Test extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		//private $business_rule_name = 'test_rule';
		protected $business_rule_name = 'resolve_past_due_to_active';
		protected $timer_name = 'test_timer';
		protected $process_log_name = 'test_proces_log_name';
		protected $use_transaction = FALSE;
		
		public function __construct()
		{
			$this->classname = __CLASS__;
			
			parent::__construct();
		}
		
		/**
		 * Taken from the function Resolve_Flash_Report()
		 * originally located in ecash3.0/cronjobs/resolve_flash_report.php
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();

			$this->log->Write("Test event running!");

		}
	}


?>