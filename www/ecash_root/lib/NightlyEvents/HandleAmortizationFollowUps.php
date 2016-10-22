<?php

	//  $manager->Define_Task('Handle_Amortization_Follow_Ups', 'amortization_follow_up', $amort_timer, 'amortization_follow_up', array($server));

	class ECash_NightlyEvent_HandleAmortizationFollowUps extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'amortization_follow_up';
		protected $timer_name = 'Handle_Amortization_FollowUps';
		protected $process_log_name = 'amortization_follow_up';
		protected $use_transaction = FALSE;

		public function __construct()
		{
			$this->classname = __CLASS__;

			parent::__construct();
		}

		/**
		 * A wrapper for the function Handle_Amortization_Follow_Ups()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();

			$this->Handle_Amortization_Follow_Ups();
		}

		/**
		 * Look for 30 and 90 day Amortization Follow Ups and put them into the Action Queue
		 *
		 */
		private function Handle_Amortization_Follow_Ups()
		{
			$agent_id = Fetch_Current_Agent();
			$biz_rules = new ECash_Business_Rules($this->db);

			$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'offline_processing');
			$rule_set_id = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
			$rules = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

			$types = array('amortization_start','amortization_payment');
			$applications = Follow_Up::Get_Follow_Ups_By_Type($types);

			$amortization_start_period_list = array();
			$amortization_payment_list = array();

			foreach ($applications as $a)
			{
				$this->log->Write("[App:{$a->application_id}] Amortization Follow Up has Expired, moving to Action Queue");
				Follow_Up::Update_Follow_Up_Status($a->application_id, $row->follow_up_id);

				if($a->type = 'amortization_start')
				{
					$period = $rules['amortization_start_period'];
					$comment = "{$period} day payment follow-up expired, please contact customer to make arrangements";
					$amortization_start_period_list[] = $a->application_id;
				}
				else if ($a->type = 'amortization_payment')
				{
					$period = $rules['amortization_payment_period'];
					$comment = "No payments made for {$period} days, please contact customer to make arrangements";
					$amortization_payment_list[] = $a->application_id;
				}

				$comments = ECash::getApplicationById($a->application_id)->getComments();
				$comments->add($comment, $agent_id);
				

				// Remove from any queues the application may already be in and add to the Action Queue
				//remove_from_automated_queues($a->application_id);
				//move_to_automated_queue("Action Queue",	$a->application_id, "", time(), NULL);
				$qm = ECash::getFactory()->getQueueManager();
				$qm->removeFromAllQueues(new ECash_Queues_BasicQueueItem($a->application_id));
				
				if($qm->hasQueue('action_queue'))
				{
					$qi = $qm->getQueue('action_queue')->getNewQueueItem($a->application_id);
					$qm->moveToQueue($qi, 'action_queue') ;	
				}
			}

			if(count($amortization_start_period_list) > 0)
			{
				if(strpos($rules['amort_start_expr_list'], ',')) {
					$recipient_list = explode(',', $rules['amort_start_expr_list']);
				} else {
					$recipient_list = array();
					$recipient_list[] = $rules['amort_start_expr_list'];
				}

				$tokens = array();
				$tokens['from_email'] = 'rebel75cell@gmail.com, brian.gillingham@gmail.com, randy.klepetko@sbcglobal.net';
				$tokens['period'] = $rules['amortization_start_period'];
				$tokens['report_results'] = implode("\n", $amortization_start_period_list);
				$this->Email_Amortization_Report($tokens, $recipient_list);
			}

			if(count($amortization_payment_list) > 0)
			{
				if(strpos($rules['amort_pay_expr_list'], ',')) {
					$recipient_list = explode(',', $rules['amort_pay_expr_list']);
				} else {
					$recipient_list = array();
					$recipient_list[] = $rules['amort_pay_expr_list'];
				}

				$tokens = array();
				$tokens['from_email'] = 'rebel75cell@gmail.com, brian.gillingham@gmail.com, randy.klepetko@sbcglobal.net';
				$tokens['period'] = $rules['amortization_payment_period'];
				$tokens['report_results'] = implode("\n", $amortization_payment_list);
				$this->Email_Amortization_Report($tokens, $recipient_list);
			}

		}

		private function Email_Amortization_Report($tokens, $recipient_list)
		{
			require_once(LIB_DIR . '/Mail.class.php');
			if(!empty($recipient_list))
			eCash_Mail::sendMessage('ECASH_AMORTIZATION_REPORT', $recipient_list, $tokens);
		}
	}

?>