<?php

	require_once(LIB_DIR . 'Nightly_Event.class.php');
	require_once(LIB_DIR . 'AbstractAppServiceEvent.php');
	require_once(LIB_DIR . 'NightlyEvents/ResolveFlashReport.php');
	require_once(LIB_DIR . 'NightlyEvents/ResolveDDAHistoryReport.php');
	require_once(LIB_DIR . 'NightlyEvents/ResolvePaymentsDueReport.php');
	require_once(LIB_DIR . 'NightlyEvents/ResolveOpenAdvancesReport.php');
	require_once(LIB_DIR . 'NightlyEvents/TransactionsUpdate.php');
	require_once(LIB_DIR . 'NightlyEvents/ResolvePastDueToActive.php');
	require_once(LIB_DIR . 'NightlyEvents/ResolveCollectionsNewToActive.php');
	require_once(LIB_DIR . 'NightlyEvents/BankruptcyMoveToCollections.php');
	require_once(LIB_DIR . 'NightlyEvents/SetCompletedAccountsToInactive.php');
	require_once(LIB_DIR . 'NightlyEvents/ExpireWatchedAccounts.php');
	require_once(LIB_DIR . 'NightlyEvents/RescheduleHeldApps.php');
	require_once(LIB_DIR . 'NightlyEvents/CompleteAgentAffiliationExpirationActions.php');
	require_once(LIB_DIR . 'NightlyEvents/SetArrangementsFollowUps.php');
	require_once(LIB_DIR . 'NightlyEvents/HandleAmortizationFollowUps.php');
	require_once(LIB_DIR . 'NightlyEvents/FraudReminder.php');
	require_once(LIB_DIR . 'NightlyEvents/AddUpcomingCollectionsDebitsToQueue.php');
	require_once(LIB_DIR . 'NightlyEvents/SendZeroBalanceLetter.php');
	require_once(LIB_DIR . 'NightlyEvents/CLVerifyUpdates.php');
	
	/**
	 * This class is used to define all of the eCash nightly events that need to be run.
	 * This specific class includes the default events that almost every customer
	 * will need to run.  It includes and should be obstantiated using the getInstance()
	 * factory method so that a customer specific class can extend and override the 
	 * defaults.
	 *
	 * @author Brian Ronald <brian.ronald@sellingsource.com>
	 * 
	 */
	class ECash_NightlyEvents_Handler
	{
		/**
		 * Basic factory method which will return a customer specific
		 * or the default ECash_Nightly_Event_Handler object
		 *
		 * @return ECash_Nightly_Event_Handler
		 */
		public static function getInstance()
		{
			if(file_exists(CUSTOMER_LIB . "/nightly_events.php"))
			{
				require_once(CUSTOMER_LIB . "/nightly_events.php");

		        $enterprise_prefix = ECash::getConfig()->ENTERPRISE_PREFIX;
				$classname = strtoupper($enterprise_prefix) . "_ECash_NightlyEvents_Handler";
				return new $classname;
			}
			else 
			{
				return new self();
			}
		}
		
		/**
		 * These are the eCash 3.x Default tasks which will be passed to the 
		 * CronScheduler object which is provided by Nightly.
		 * 
		 * Please note that these tasks must be added in the order that they are to be run.
		 *
		 * @param CronScheduler $manager
		 */
		public function registerEvents(CronScheduler $manager)
		{
			/**
			 * Transactions based tasks
			 */
			$manager->Add_Task(new ECash_NightlyEvent_TransactionsUpdate());

			/**
			 * Reporting tasks
			 */
			$manager->Add_Task(new ECash_NightlyEvent_ResolveFlashReport());
			$manager->Add_Task(new ECash_NightlyEvent_ResolveDDAHistoryReport());
			$manager->Add_Task(new ECash_NightlyEvent_ResolvePaymentsDueReport());
			$manager->Add_Task(new ECash_NightlyEvent_ResolveOpenAdvancesReport());

			/**
			 * Everything else
			 */
			$manager->Add_Task(new ECash_NightlyEvent_ResolvePastDueToActive());
			$manager->Add_Task(new ECash_NightlyEvent_ResolveCollectionsNewToActive());
			$manager->Add_Task(new ECash_NightlyEvent_BankruptcyMoveToCollections());
			$manager->Add_Task(new ECash_NightlyEvent_ExpireWatchedAccounts());
			$manager->Add_Task(new ECash_NightlyEvent_RescheduleHeldApps());
		//	$manager->Add_Task(new ECash_NightlyEvent_CompleteAgentAffiliationExpirationActions());
			$manager->Add_Task(new ECash_NightlyEvent_SetArrangementsFollowUps());
			$manager->Add_Task(new ECash_NightlyEvent_HandleAmortizationFollowUps());
			$manager->Add_Task(new ECash_NightlyEvent_FraudReminder());
			$manager->Add_Task(new ECash_NightlyEvent_AddUpcomingCollectionsDebitsToQueue());
			$manager->Add_Task(new ECash_NightlyEvent_SetCompletedAccountsToInactive());
			$manager->Add_Task(new ECash_NightlyEvent_SendZeroBalanceLetter());
		}
		
	}
	
	
	
	
?>
