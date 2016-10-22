<?php
	/**
	 * This function cleans up old sessions in the database.
	 */
	function Clean_Sessions()
	{
		$query = "
			DELETE FROM session WHERE date_modified < CURDATE()	";
		
		$db = ECash::getMasterDb();
		$db->exec($query);
	}

	function Main()
	{
		global $server;
		global $_BATCH_XEQ_MODE;
	
		require_once(LIB_DIR.'NightlyEvents/Handler.class.php');
		require_once(LIB_DIR.'cronscheduler.class.php');
		require_once(LIB_DIR.'AgentAffiliation.php');
		require_once(LIB_DIR."common_functions.php");
		require_once(LIB_DIR."temporary_ach_actions.php"); 
		require_once(LIB_DIR."non_ach_actions.php"); 
		require_once(COMMON_LIB_DIR."pay_date_calc.3.php");
		require_once(CUSTOMER_LIB."failure_dfa.php");
		require_once(SERVER_CODE_DIR . "loan_data.class.php");
		require_once(SERVER_CODE_DIR . "follow_up.class.php");
		require_once(SERVER_CODE_DIR . "comment.class.php");
		require_once(SERVER_MODULE_DIR."collections/quick_checks.class.php");
		require_once(SERVER_MODULE_DIR."fraud/fraud.class.php");
		require_once(LIB_DIR . "business_rules.class.php");
		require_once(SQL_LIB_DIR.'scheduling.func.php');
		require_once(SQL_LIB_DIR.'react.func.php');
	
		$today = date("Y-m-d");
		$co = $server->company;
			
		ECash::getLog()->Write(__FILE__.": Executing post-batch processing. [Mode: {$_BATCH_XEQ_MODE}] [Company: {$co}]");
		$nightly_pid = Set_Process_Status(null, $server->company_id, 'nightly','started', $today);
	
		/**
		 * Clean up old sessions
		 */
		Clean_Sessions();

		$manager = new CronScheduler($server);

		/**
		 * This will grab the appropriate event handler
		 * via a simple Factory method.  This gives us the
		 * ability to have a default set of events to run
		 * as well as the ability to extend or override them.
		 */
		$handler = ECash_NightlyEvents_Handler::getInstance();
		$handler->registerEvents($manager);
		
		/**
		 * Now that the events are registered in the Cron Scheduler,
		 * we'll run through them in order, one by one.
		 */
		$manager->Main($server->log);
	
		if(CronScheduler::$has_failure === true ) {
			ECash::getLog()->Write(__FILE__.": Post-batch processing had errors. [Mode: {$_BATCH_XEQ_MODE}] [Company: {$co}]");
			Set_Process_Status(null, $server->company_id, 'nightly','failed', $today, $nightly_pid);
		} else {
			ECash::getLog()->Write(__FILE__.": Post-batch processing completed. [Mode: {$_BATCH_XEQ_MODE}] [Company: {$co}]");
			Set_Process_Status(null, $server->company_id, 'nightly','completed', $today, $nightly_pid);
		}
	}

