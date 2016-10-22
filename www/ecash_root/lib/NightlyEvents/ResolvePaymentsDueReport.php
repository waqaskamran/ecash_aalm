<?php

	//	$manager->Define_Task('Resolve_Payments_Due_Report', 'resolve_payments_due_report', null, null, array($server, $today), false); //no transaction

	class ECash_NightlyEvent_ResolvePaymentsDueReport extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'resolve_payments_due_report';
		protected $timer_name = NULL;
		protected $process_log_name = NULL;
		protected $use_transaction = FALSE;
		
		public function __construct()
		{
			$this->classname = __CLASS__;
			
			parent::__construct();
		}
		
		/**
		 * Taken from the function Resolve_Payments_Due_Report()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();
			
			$payments_due_object = new Payments_Due_Report_Query($this->server);
			$payments_due_data   = $payments_due_object->Fetch_Payments_Due_Data(str_replace("-","",$this->today), 'all', $this->company_id, 'cli', true);
			
			$nonach_payments_due_object = new Nonach_Payments_Due_Report_Query($this->server);
			$payments_due_data = $nonach_payments_due_object->Fetch_Payments_Due_Data(str_replace("-","",$this->today), 'all', $this->company_id, 'cli', true);

		}
	}


?>