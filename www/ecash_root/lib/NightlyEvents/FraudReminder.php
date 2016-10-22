<?php

	require_once(BASE_DIR . "/cronjobs/fraud_reminder.php");

	//  $manager->Define_Task('Fraud_Reminder', 'fraud_reminder', $fraud_timer, 'fraud_reminder', $parameters = array($server));

	class ECash_NightlyEvent_FraudReminder extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'fraud_reminder';
		protected $timer_name = 'Fraud_Reminder';
		protected $process_log_name = 'fraud_reminder';
		protected $use_transaction = FALSE;
		
		public function __construct()
		{
			$this->classname = __CLASS__;
			
			parent::__construct();
		}
		
		/**
		 * A wrapper for the Fraud_Reminder class which is located 
		 * in ecash3.0/cronjobs/fraud_reminder.php.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();
			
			Fraud_Reminder::Call($this->server);
		}

	}

?>