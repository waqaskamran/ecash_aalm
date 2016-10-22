<?php

	require_once( SERVER_CODE_DIR . "accounts_recievable_report_query.class.php" );

	/**
	 * Accounts Recievable Report - Originally written for AALM, but shared by all.
	 *
	 */
	class ECash_NightlyEvent_ResolveARReport extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = null;
		protected $timer_name = 'ResolveARReport';
		protected $process_log_name = 'resolve_ar_report';
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

			$payments_due_object = new AR_Report_Query($this->server);
			$payments_due_data   = $payments_due_object->Fetch_Payments_Due_Data(str_replace("-","",$this->today),  $this->company_id, 'cli', true);

		}
	}

?>
