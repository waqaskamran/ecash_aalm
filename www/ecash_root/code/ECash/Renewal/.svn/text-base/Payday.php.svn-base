<?php 

Class ECash_Renewal_Payday extends ECash_Renewal_Renewal implements ECash_Renewal_RenewalInterface 
{
	const PENDING_RENEWAL = 'Currently has a Renewal pending';
	const RENEWAL_LIMIT = 'Customer has reached number of allowable renewals';
	const NOT_ACTIVE = 'Application is not in Active Status';
	const TOO_LATE = 'Request being made too close to due date';
	const PENDING_TRANSACTION = 'Currently has transaction Pending';
	
	const CS_PENDING_RENEWAL = 'We are unable to process your request at this time because there is a current renewal transaction pending with the associated loan account.  
	You must wait until the transaction has posted to your account before requesting an additional renewal.';
	
	const CS_RENEWAL_LIMIT = 'We are unable to process your request at this time because you have exceeded the maximum allowable renewals.';
	
	const CS_NOT_ACTIVE = 'We are unable to process your request at this time because your loan is not in an active status.  If you have questions or concerns, please contact a Customer Service Representative at 888.820.6667';
	
	const CS_TOO_LATE = 'We are unable to process your request at this time because it is too close to your scheduled due date.
	Renewal requests must be received at least two business days prior to your scheduled due date.';
	
	const CS_PENDING_TRANSACTION = 'We are unable to process your request at this time because there is a current transaction pending with the associated loan account.';
	
	const CS_RENEWAL_REQUEST_SUCCESS = 'Your request for renewal has been submitted.  You will receive an email momentarily with an attached renewal request form that must be completed and returned.';
	
	const CS_RENEWAL_CREATION_SUCCESS = 'Your loan has been renewed.';
	
	public function getRolloverEligibility($application_id)
	{
		$application =  ECash::getApplicationByID($application_id);
		$schedule = Fetch_Schedule($application_id);
		$status = Analyze_Schedule($schedule,true);
		$business_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());
		$rule_set_id = $business_rules->Get_Rule_Set_Id_For_Application($application_id);
		$rules = $business_rules->Get_Rule_Set_Tree($rule_set_id);
		$eligibility = array();
		$eligibility['eligible'] = TRUE;
		$eligibility['reason'] = 'eligible';
		$eligibility['cs_reason'] = 'eligible';
		$eligibility['application_id'] = $application_id;

		
		$olp_template = $rules['renewals']['olp_template'];
		$eligibility['olp_template'] = $olp_template;
		
		//Verify that the application doesn't already have a pending rollover.
		$scheduled_sc_count = 0;
		foreach ($schedule as $e) 
		{
			if ($e->type == 'payment_service_chg' && $e->status == 'scheduled')
			{
				$scheduled_sc_count++;
			}
		}

		if ($scheduled_sc_count > 1)
		{
			$eligibility['eligible'] = FALSE;
			$eligibility['reason'] = self::PENDING_RENEWAL;
			$eligibility['cs_reason'] = self::CS_PENDING_RENEWAL;
		}
		//Verify the customer has not reached the renewal limit
		
		$principal_percent = $rules['principal_payment']['min_renew_prin_pmt_prcnt'];
		$rollover_limit = $rules['service_charge']['max_renew_svc_charge_only_pmts'];
		if($principal_percent == 100 && $this->getRolloverTerm($application_id) >= $rollover_limit)
		{
			$eligibility['eligible'] = FALSE;
			$eligibility['reason'] = self::RENEWAL_LIMIT;
			$eligibility['cs_reason'] = self::CS_RENEWAL_LIMIT;
		}
		
		//Verify there are no pending transactions
		if ($status->num_pending_items >= 1)
		{
			$eligibility['eligible'] = FALSE;
			$eligibility['reason'] = self::PENDING_TRANSACTION;
			$eligibility['cs_reason'] = self::CS_PENDING_TRANSACTION;
		}

		
		//Verify that the application is in an active status.
		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');

		if($application->application_status_id != $asf->toId('active::servicing::customer::*root'))
		{
			$eligibility['eligible'] = FALSE;
			$eligibility['reason'] = self::NOT_ACTIVE;
			$eligibility['cs_reason'] = self::CS_NOT_ACTIVE;
		}
		//Return eligibility and a reason
		
		return $eligibility;
	}
	
	public function getRequestEligibility($application_id)
	{
		$schedule = Fetch_Schedule($application_id);
		$status = Analyze_Schedule($schedule,true);
		$ineligible_date = $this->pdc->Get_Business_Days_Backward($this->fixDate($status->next_due_date),3);
		$eligibility = $this->getRolloverEligibility($application_id);
		if($eligibility['eligible'])
		{
			if(strtotime($ineligible_date) < strtotime(date('Y-m-d')))
			{
				$eligibility['eligible'] = FALSE;
				$eligibility['reason'] = self::TOO_LATE;
				$eligibility['cs_reason'] = self::CS_TOO_LATE;
			}
		}
		return $eligibility;
	}
	
	public function getRolloverExecutionEligibility($application_id)
	{
		$schedule = Fetch_Schedule($application_id);
		$status = Analyze_Schedule($schedule,true);
		$ineligible_date = $this->pdc->Get_Business_Days_Backward($this->fixDate($status->next_due_date),2);
		$eligibility = $this->getRolloverEligibility($application_id);
		
		if($eligibility['eligible'])
		{
			if(strtotime($ineligible_date) < strtotime(date('Y-m-d')))
			{
				$eligibility['eligible'] = FALSE;
				$eligibility['reason'] = self::TOO_LATE;
				$eligibility['cs_reason'] = self::CS_TOO_LATE;
			}
		}
		
		return $eligibility;
	}
	
	public function requestRollover($application_id, $paydown_amount = 0)
	{
		$application =  ECash::getApplicationByID($application_id);
		$results = array();
		$results['success'] = FALSE;
		$eligibility = $this->getRequestEligibility($application_id);
		$results = array_merge($eligibility,$results);
		if($eligibility['eligible'])
		{
			//Email the customer
			ECash_Documents_AutoEmail::Queue_For_Send($application_id, 'RENEWAL_REQUEST');
			//@todo Insert into the appropriate queues
			//1. fetch date_effective of next payment
			//2. get date for close of business 2 days before date from step 1
			//3. insert into renewal queue with expiration of date from step 2
			$schedule = Fetch_Schedule($application_id);
			$status = Analyze_Schedule($schedule,true);
			//@todo transform poorly formatted date m-d-y to y-m-d
			$due_date = $this->fixDate($status->next_due_date);
			$expiration = $this->pdc->Get_Business_Days_Backward($due_date, 1);
			$qm = ECash::getFactory()->getQueueManager();
			$queue = $qm->getQueue('renewal');
			$queue_item = $queue->getNewQueueItem($application_id);
			$queue_item->DateAvailable = strtotime($this->pdc->Get_Business_Days_Forward(date('Y-m-d'),1));
			$queue_item->DateExpire = strtotime($expiration);
			$qm->moveToQueue($queue_item, 'renewal');		
			//Do NOT modify the schedule!!!!!!!!
			
			//Return Approval message
			$results['success'] = TRUE;
			$results['reason'] = 'renewal request submitted';
			$results['cs_reason'] = self::CS_RENEWAL_REQUEST_SUCCESS;
		}
		else 
		{
			//Insert Comment
			$comments = $application->getComments();
			$comments->add('Renewal request was denied - ' .$eligibility['reason'],ECash::getAgent()->getAgentId());
		}
		
		return $results;
		
	}
	

	/**
	 * createRollover
	 * Runs the whole Rollover process on an application.
	 * Checks eligibility and executes the rollover.
	 *
	 * @param int $application_id
	 * @param float $paydown_amount
	 * @return array
	 */
	public function createRollover($application_id, $paydown_amount = 0)
	{
		
		$application =  ECash::getApplicationByID($application_id);
		$comments = $application->getComments();
		
		$results = array();
		$results['success'] = TRUE;
		$results['reason'] = 'Success';
		$results['cs_reason'] = self::CS_RENEWAL_CREATION_SUCCESS;
		
		//Verify Eligibility
		$eligibility = $this->getRolloverExecutionEligibility($application_id);
		$results = array_merge($results,$eligibility);
		if($eligibility['eligible'] == FALSE)
		{
			//Insert comment stating that a rollover was attempted and denied.  Include reason.
			$comments->add('Renewal request was denied - ' .$eligibility['reason'],ECash::getAgent()->getAgentId());
			$results['success'] = FALSE;
			
			
			//EMAIL THE CUSTOMER TO TELL THEM THEY DO NOT QUALIFY!
			switch ($eligibility['reason'])
			{
				case self::NOT_ACTIVE :
					$document = 'RENEWAL_DECLINED_NOTACTIVE';
					break;
				case self::PENDING_RENEWAL :
					$document = 'RENEWAL_DECLINED_PENDINGRENEWAL';
					break;
				case self::PENDING_TRANSACTION :
					$document = 'RENEWAL_DECLINED_PENDINGTRANSACTION';
					break;
				case self::RENEWAL_LIMIT :
					$document = 'RENEWAL_DECLINED_RENEWALLIMIT';
					break;
				case self::TOO_LATE :
					$document = 'RENEWAL_DECLINED_LATE';
				default:
					break;
			}
			ECash_Documents_AutoEmail::Queue_For_Send($application_id, $document);

			return $results;
		}
		
		$rollover = $this->executeRollover($application_id, $paydown_amount);
		
		//Populate results and return results.
		
		$results = array_merge($rollover,$results);
		$comments->add('Loan Renewal was requested',ECash::getAgent()->getAgentId());
		
		//remove from renewal queue
		$qm = ECash::getFactory()->getQueueManager();
		$queue = $qm->getQueue('renewal');
		$queue->remove(new ECash_Queues_BasicQueueItem($application_id));

		//Email customer to notify of successful renewal
		ECash_Documents_AutoEmail::Queue_For_Send($application_id, 'RENEWAL_SUCCESS');
		return $results;
		
	}
	
	protected function executeRollover($application_id, $paydown_amount = 0)
	{
		
		//Assess  new interest charges
		//Assess principal paydown if necessary
		//remove scheduled payout. create scheduled payout for the next pay period.
		//We're going to use the DFAs and do this in the shameful old way for now.
		require_once(CUSTOMER_LIB. "renew_schedule_dfa.php");
		$application =  ECash::getApplicationByID($application_id);
		
		$db = ECash::getMasterDb();
		
		$log = get_log("scheduling");
		
		$log->Write("[Agent:".ECash::getAgent()->getAgentId()."][AppID:{$application_id}] Renewing schedule");
	
		$tr_data = Get_Transactional_Data($application_id);
	
		$schedule = Fetch_Schedule($application_id);
		$status   = Analyze_Schedule($schedule, TRUE);
	
		$sent_disbursement = false;
		$num_scs_assessed = 0;
		$special_payments = array();
		$disbursement_types = array('loan_disbursement', 'converted_principal_bal', 'converted_service_chg_bal','moneygram_disbursement', 'check_disbursement');

		// Ignore these events because we'll be recreating them in the DFA
		$ignored_events = array('assess_service_chg',
				'payment_service_chg',
				'repayment_principal',
				'payout',
				'assess_fee_ach_fail',
				'payment_fee_ach_fail',
				'cso_assess_fee_app',
				'cso_assess_fee_broker',
				'cso_pay_fee_broker',
				'cso_pay_fee_app',);
		foreach($schedule as $e)
		{
			if($e->type === 'assess_service_chg' && $e->status === 'complete')
			{
				$num_scs_assessed++;
			}
	
			// Grab any special payments, arrangements
			if(($e->status  === 'scheduled') && (!in_array($e->type, $ignored_events) || $e->is_shifted)) 
			{
				$special_payments[] = $e;
			}
	
			// This probably should either be done sooner or cut out completely.
			if (in_array($e->type, $disbursement_types) && $e->status != 'scheduled') 
			{
				$sent_disbursement = true;
			}
		}
		$ecash_api = eCash_API_2::Get_eCash_API(null, $db, $application_id);
	
		// Remove all unregistered events. Don't you fret,  We'll regenerate them!
		Remove_Unregistered_Events_From_Schedule($application_id);
		$schedule = Fetch_Schedule($application_id);
		$status   = Analyze_Schedule($schedule, TRUE);
		$tr_data = Get_Transactional_Data($application_id);
		
		
		$parameters = new stdClass();
		$parameters->application_id = $application_id;
		$parameters->fund_amount = $tr_data->info->fund_actual;
		$parameters->paydown_amount = $paydown_amount;
		$parameters->fund_date = $tr_data->info->date_fund_stored;
		$parameters->tr_data = $tr_data;
		$parameters->info = $tr_data->info;
		$parameters->rules = Prepare_Rules($tr_data->rules, $tr_data->info);
		$parameters->schedule = $schedule;
		$parameters->status = $status;
		$parameters->balance_info = Fetch_Balance_Information($application_id);
		$parameters->special_payments = $special_payments;
		$parameters->num_scs_assessed = $num_scs_assessed;
	
		$parameters->log = $log;
		$parameters->pdc = $this->pdc;
	
		if (isset($current_schedule[$status->stopping_location]->date_event))
		{
			$parameters->next_action_date = $current_schedule[$status->stopping_location]->date_event;
			$parameters->next_due_date = $parameters->pdc->Get_Next_Business_Day($parameters->next_action_date);
		}
		else
		{
			$parameters->next_action_date = false;
			$parameters->next_due_date = false;
		}
	
		$dfa = new RenewScheduleDFA();
		$dfa->SetLog($log);
	
		$log->Write("Running Renew Schedule DFA for $application_id");
		
		$new_events = $dfa->run($parameters);
	    foreach ($new_events as $e)
	    {
	        Record_Event($application_id, $e);
	    }
		
		
		
		
		
		//return array with results
		$results = array();
		//Let's determine when the rollover occurs and when the loan is due
		foreach($new_events as $e)
		{
			switch($e->type)
			{
				//Yes, this will get every principal payment, but it'll keep overwriting with the last one, and that's
				//all we care about
				case 'repayment_principal':
					$results['loan_due_date'] = $e->date_effective;
					break;
			}
		}
		//Now let's determine the next loan date
		foreach ($new_events as $e)
		{
			if (strtotime($e->date_event) == $rollover_date && $e->type == 'payment_service_chg')
			{
				$results['next_due_date'] = $e->date_effective;
			}
		}
		
		foreach($new_events as $e)
		{
			foreach($e->amounts as $a)
			{
				if($a->amount < 0 )
				{
					if (strtotime($e->date_effective) == strtotime($results['next_due_date']))
					{
						$results['rollover_amount_due'] = bcadd($results['rollover_amount_due'],abs($a->amount),2);
					}
					if (strtotime($e->date_effective) == strtotime($results['loan_due_date']))
					{
						$results['loan_amount_due'] = bcadd($results['loan_amount_due'],abs($a->amount),2);
					}
				}
			}
			

		}
		
		return $results;
	}

	/**
	 * getRolloverTerm
	 * A quick and easy way to see what rollover term the application is in.
	 *People are going to want this eventually. 
	 *
	 * @param unknown_type $application_id
	 * @return unknown
	 */
	public function getRolloverTerm($application_id)
	{
		
		$term = 0;
		
		//get schedule
		$schedule = Fetch_Schedule($application_id);
		//analyze schedule to determine how many rollovers this application has
		//Count the number of CSO Broker fees assessed (Each time a broker fee is assessed denotes a new loan/rollover term)
		foreach($schedule as $e)
		{
			if($e->type === 'payment_service_chg' && $e->status === 'complete')
			{
				$term++;
			}
		}
		//return a number
		return $term;
		
	}
	
}

?>
