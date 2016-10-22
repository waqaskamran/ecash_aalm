<?php

	//	$manager->Define_Task('Complete_Agent_Affiliation_Expiration_Actions', 'cmp_aff_exp_actions', $caaea_timer, 'cmp_aff_exp_actions', array($server));

	class ECash_NightlyEvent_CompleteAgentAffiliationExpirationActions extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'cmp_aff_exp_actions';
		protected $timer_name = 'Complete_Agent_Affiliation_Expiration_Actions';
		protected $process_log_name = 'cmp_aff_exp_actions';
		protected $use_transaction = FALSE;
		
		public function __construct()
		{
			$this->classname = __CLASS__;
			
			parent::__construct();
		}
		
		/**
		 * A wrapper for the function Complete_Agent_Affiliation_Expiration_Actions()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();
			
			$this->Complete_Agent_Affiliation_Expiration_Actions($this->server);
		}

		private function Complete_Agent_Affiliation_Expiration_Actions(Server $server) 
		{
			eCash_AgentAffiliation_Legacy::expireOldAgentAffiliations($this->company_id);
			$applications = eCash_AgentAffiliation_Legacy::getExpiredAffiliationsWithStandbys($this->company_id);
			
			// Process the standby
			foreach ($applications as $application_id => $standby) {
				switch ($standby) {
					case 'arrangement_failed':
						
						$schedule = Fetch_Schedule($application_id);
						 //We only want to schedule a full pull if the application has no scheduled events [AGEAN LIVE #12530]
                         if (!Has_A_Scheduled_Event($application_id))
                         {
                             //Schedule_Full_Pull($application_id);
                         }
						$e = Grab_Most_Recent_Failure($application_id, $schedule);
						//ECash_Documents_AutoEmail::Send($application_id, 'ARRANGEMENTS_MISSED', $e->transaction_register_id);
						Remove_Standby($application_id, $standby);
						break;
					
					case '3_day_return_queue':
						Remove_Standby($application_id, $standby);
						Set_Standby($application_id, $this->company_id, 'qc_ready');
						Update_Status(null, $application_id, "return::quickcheck::collections::customer::*root", NULL, FALSE);
				
						$holidays = Fetch_Holiday_List();
						$pdc = new Pay_Date_Calc_3($holidays);
				
						require_once(SQL_LIB_DIR."util.func.php");
				
						move_to_automated_queue
							(   "Collections Returned QC"
							,   $application_id
							,   date("Y-m-d H:i:s") // Sort String
							,   NULL // Time available : Now
							,   strtotime($pdc->Get_Business_Days_Forward(date("Y-m-d H:i:s"),3))+((60*60*24)-1) // Time unavailable, returns as Y-m-d string, add one-second shy of new day
							)   ;
						
						break;
					
					case '5_day_return_queue':
						Remove_Standby($application_id, $standby);
						Set_Standby($application_id, $this->company_id, 'qc_return');
						Update_Status(null, $application_id, "return::quickcheck::collections::customer::*root", NULL, FALSE);
				
						$holidays = Fetch_Holiday_List();
						$pdc = new Pay_Date_Calc_3($holidays);
				
						require_once(SQL_LIB_DIR."util.func.php");
				
						move_to_automated_queue
							(   "Collections Returned QC"
							,   $application_id
							,   date("Y-m-d H:i:s") // Sort String
							,   NULL // Time available : Now
							,   strtotime($pdc->Get_Business_Days_Forward(date("Y-m-d H:i:s"),5))+((60*60*24)-1) // Time unavailable, returns as Y-m-d string, add one-second shy of new day
							)   ;
						break;
					case 'followup_expired':
						Remove_Standby($application_id, $standby);
						Return_To_Previous_Status($application_id);
						break;
				}
			}

			// Cleanup standbys
			$pdc = new Pay_Date_Calc_3(Fetch_Holiday_List());
			$date = $pdc->Get_Business_Days_Backward(date('Y-m-d'), 1);
//			Remove_Expired_Standbys('arrangement_failed',$date.' 00:00:00');
//			Remove_Expired_Standbys('3_day_return_queue',$date.' 00:00:00');
//			Remove_Expired_Standbys('5_day_return_queue',$date.' 00:00:00');
//			Remove_Expired_Standbys('followup_expired',$date.' 00:00:00');
		}

	}

?>