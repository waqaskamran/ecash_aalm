<?php 

Class ECash_Renewal_CSO extends ECash_Renewal_Renewal implements ECash_Renewal_RenewalInterface
{

	
	public function getRolloverEligibility($application_id)
	{
		$application =  ECash::getApplicationByID($application_id);
		$schedule = Fetch_Schedule($application_id);
		$status = Analyze_Schedule($schedule,true);
		$rules = $application->getBusinessRules();
		$eligibility = array();
		$eligibility['eligible'] = TRUE;
		$eligibility['reason'] = 'eligible';
		$eligibility['cs_reason'] = $eligibility['reason'];
		$eligibility['application_id'] = $application_id;
		
		$olp_template = $rules['renewals']['olp_template'];
		$eligibility['olp_template'] = $olp_template ? $olp_template : 'cso_default';
		//Verify that the application doesn't already have a pending rollover.
		foreach ($schedule as $e) 
		{
			if ($e->type == 'cso_assess_fee_broker' && $e->status == 'scheduled')
			{
				$eligibility['eligible'] = FALSE;
				$eligibility['reason'] = "Currently has a refinance pending";
			}
		}
		
		//Verify there are no pending transactions
		if ($status->num_pending_items >= 1)
		{
			$eligibility['eligible'] = FALSE;
			$eligibility['reason'] = 'Has transaction Pending';
		}

		//Verify that this request is being made before the cutoff date, currently MCC wants this date to be their action date
		//before the batch is closed.
		//However, this is going to be determined by a business rule, so they can make it whatever they want. [W!-01-07-2009][#22523]
		$next_action_date = $this->fixDate($status->next_action_date);
		$today = strtotime(date('Y-m-d'));
		$cutoff_limit = $rules['renewals']['renewal_cutoff'];
		$cutoff_date = strtotime($next_action_date ."-{$cutoff_limit} day");

		//As requested in GForge #22523, DMP
		if($cutoff_date < $today || ($cutoff_date == $today && Has_Batch_Closed($application->company_id)))
		{
			$eligibility['eligible'] = FALSE;
			$eligibility['reason'] = 'This refinancing request was not submitted before the cut-off time';
		}
		
		//Verify that the application is in an active status.
		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');

		if($application->application_status_id != $asf->toId('active::servicing::customer::*root'))
		{
			$eligibility['eligible'] = FALSE;
			$eligibility['reason'] = 'Application is not in Active Status';
		}
		//Return eligibility and a reason
		$eligibility['cs_reason'] = $eligibility['reason'];
		return $eligibility;
	}
	
	public function getRequestEligibility($application_id)
	{
		return $this->getRolloverEligibility($application_id);
	}
	
	public function getRolloverExecutionEligibility($application_id)
	{
		return $this->getRolloverEligibility($application_id);
	}
	
	public function requestRollover($application_id, $paydown_amount = 0)
	{
		//This is the same thing as creating one for CSO
		return $this->createRollover($application_id, $paydown_amount);
	}

	/**
	 * createRollover
	 * Runs the whole Rollover process on an application.
	 * Checks eligibility, does lender verification, and finally executes the rollover.
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
		
		//Verify Eligibility
		$eligibility = $this->getRolloverExecutionEligibility($application_id);
		$results = array_merge($results,$eligibility);
		if($eligibility['eligible'] == FALSE)
		{
			$results['success'] = $eligibility['eligible'];
			//Insert comment stating that a rollover was attempted and denied.  Include reason.
			$comments->add('Refinance request was denied - ' .$eligibility['reason'],ECash::getAgent()->getAgentId());

			return $results;
		}
		//Applicant is eligible, now check for lender verification
		$verification = $this->LenderVerification($application_id);
		if(!$verification['success'])
		{
			$results['success'] = $verification['success'];
			$results['reason'] = $verification['comment'];
			$results['cs_reason'] = $results['reason'];
			//Insert comment stating that a rollover was attempted and denied.  Include reason.
			$comments->add('Refinance request was denied - ' .$verification['comment'],ECash::getAgent()->getAgentId());
			return $results;
		}
		
		
		//Lender verification is complete, all that's left is executing the rollover.
		
		$rollover = $this->executeRollover($application_id, $paydown_amount);
		
		//Populate results and return results.
		
		$results = array_merge($rollover,$results);
		$results['cs_reason'] = $results['reason'];
		$comments->add('Loan was successfully refinanced',ECash::getAgent()->getAgentId());
		
		return $results;
		
	}
	
	protected function LenderVerification($application_id)
	{
		$app = ECash::getFactory()->getModel('Application');
		$app->loadBy(array('application_id' => $application_id));
		//Get verification_type business rule
		$business_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());
		$rule_set_id = $business_rules->Get_Rule_Set_Id_For_Application($application_id);
		$rule_set = $business_rules->Get_Rule_Set_Tree($rule_set_id);
		
		/**
		 * If the funding_verification rule is set, use it and return
		 * the result.  If it isn't, just reutrn a successful value.
		 */
		$type = $rule_set['funding_verification'];
		
		//Instantiate verification class
		$class_file = strtolower($type) . ".class.php";
 
		require_once(LIB_DIR . $class_file);
		$verifier = new $type($app);
		
		//Check to see if lender verification is necessary
		$verification_required = $verifier->verificationRequired();
		$lender_verification = array();
		if($verification_required)
		{
			//Call verification with necessary parameters
			$lender_verification = $verifier->runVerification();
			$lender_verification['success'] = $verifier->verified();
			$lender_verification['reason'] = $lender_verification['comment'];
		}
		else 
		{
			$lender_verification['success'] = TRUE;
			$lender_verification['reason'] = 'Lender Verification not required';
		}
		return $lender_verification;
	}
	
	protected function executeRollover($application_id, $paydown_amount = 0)
	{
		
		//Assess  new interest charges
		//Assess new Broker fee
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
	
	//	// Do not run if the account has arrangments and a balance
	//	if ($status->has_arrangements && $status->posted_and_pending_total > 0)
	//	{
	//		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Account has arrangements and a balance, not renewing schedule.");
	//		return;
	//	}
	//
	//	// No accounts with fatal failures or that aren't an acceptable status
	//	if (($status->num_fatal_failures > 0) || ! in_array($status_chain, $acceptable_statuses))
	//	{
	//		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Is in an invalid status or has fatal errors, not renewing schedule.");
	//		return;
	//	}
	
		
		
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
				//This determines the date of the rollover
				case 'cso_assess_fee_broker':
					$rollover_date = strtotime($e->date_effective);
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
			if($e->type === 'cso_assess_fee_broker' && $e->status === 'complete')
			{
				$term++;
			}
	
		}
		//return a number
		return $term;
		
	}
	
	/**
	 * getCSOFeeDescription
	 *
	 * @param string $fee_name - The name of the business rule component that dictates the fee.
	 * @param string $application_id - The application_id  (or the loan_type's name_short) to get the fee description for.
	 * @param integer $company_id - the company_id to get the loan_type to get the fee description for.
	 * @return string - A plain text description of the applicable fee.
	 */
	public function getCSOFeeDescription($fee_name,$application_id, $company_id = null, $rules = NULL)
	{
		if (!is_array($rules))
	{
		$business_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());
		if (is_numeric($application_id)) 
		{
			$rule_set_id = $business_rules->Get_Rule_Set_Id_For_Application($application_id);
		}
		else 
		{
			$loan_type = $application_id; //Yes, really
			$search = array();
			if($company_id)
			{
				$search['company_id'] = $company_id;
			}
			$search['name_short'] = $loan_type;
			$loan_type = ECash::getFactory()->getModel('LoanType');
			$loan_type->loadBy($search);
			$loan_type_id = $loan_type->loan_type_id;
			$rule_set_id = $business_rules->Get_Current_Rule_Set_Id($loan_type_id);

			
		}
		
		$rules = $business_rules->Get_Rule_Set_Tree($rule_set_id);
		}
	
		
		
		// If the fee rules don't exist, return NULL
		if(! isset($rules[$fee_name]))
		{
			return 'This rule does not exist';
		}
		
		$rule = $rules[$fee_name];
		
		$amount_type     = $rule['amount_type'];
		$fixed_amount    = $rule['fixed_amount'];
		$percentage_type = $rule['percent_type'];
		$percentage      = $rule['percent_amount'];
		


		switch($amount_type)
		{
			case 'amt':
				$description = money_format('%.2n',$fixed_amount);
			break;
			
			case 'pct of prin':
			case 'pct of fund':
			case 'amt or pct of due >':
			case 'amt or pct of due <':
			case 'amt or pct of pymnt >':
			case 'amt or pct of prin >':
			case 'amt or pct of pymnt <':
			case 'amt or pct of prin <':
			$description = str_replace('amt',money_format('%.2n',$fixed_amount),$amount_type);
			$description = str_replace('pct',$percentage.'%',$description);
			$description = str_replace('prin','principal due',$description);
			$description = str_replace('pymnt', 'payment',$description);
			$description = str_replace('due', 'total balance due',$description);
			$description = str_replace('<',', whichever is lesser',$description);
			$description = str_replace('>', ', whichever is higher',$description);
			break;
			default:
				$description = 'no amount specified';
				break;
		}
		
		return $description;
	}
	
	
	
	public function getCSOFeeAmount($fee_name,$application_id,$start_date = null, $end_date = null, $payment_amount = null, $principal_override = null, $balance_override = null, $application = NULL)
	{
		if (!is_object($application))
	{
		$application =  ECash::getApplicationByID($application_id);
		$rate_calc = $application->getRateCalculator();
		$business_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());
		$rule_set_id = $business_rules->Get_Rule_Set_Id_For_Application($application_id);
		$rules = $business_rules->Get_Rule_Set_Tree($rule_set_id);
		$fund_amount = $application->fund_actual;
		}
		else
		{
			$rules = $application->business_rules;
			$fund_amount = $application->fund_amount;
		}
		$balance_info = Fetch_Balance_Information($application_id);
		
		if ($balance_info->principal_balance <= 0) 
		{
			$principal_balance = $fund_amount;
		}
		else 
		{
			$principal_balance = $balance_info->principal_balance;
		}

		$principal_balance = ($principal_override != NULL) ? $principal_override : $principal_balance;
		$total_balance     = ($balance_override   != NULL) ? $balance_override   : $balance_info->total_balance;
		
		$rule = $rules[$fee_name];
		$amount_type     = $rule['amount_type'];
		$fixed_amount    = $rule['fixed_amount'];
		$percentage_type = $rule['percent_type'];
		$percentage      = $rule['percent_amount'];

		if(stristr($amount_type,'pymnt'))
		{
			$amount = $payment_amount;
		}
		elseif (stristr($amount_type,'fund')) 
		{
			$amount = $fund_amount;
		}
		elseif (stristr($amount_type,'due'))
		{
			$amount = $total_balance;
		}
		else 
		{
			$amount = $principal_balance;
		}
		
		
		/**
		 * Determine the percentage amount based on APR or Fixed
		 */
		if($percentage_type === 'apr')
		{
			// Get the daily rate assuming the average 365 days in a year
			$daily_rate = $percentage / 365;
			
			// Get the term of the loan (Fund Date till the Effective Due Date)
			$term = Date_Util_1::dateDiff($start_date, $end_date);
			$percentage_amount = ((($term * $daily_rate) / 100) * $amount);
		}
		else
		{
			$percentage_amount = (($amount * $percentage) / 100);
		}

		switch($amount_type)
		{
			case 'amt':
				$fee_amount = $fixed_amount;
				break;
			
			case 'pct of prin':
			case 'pct of fund':
				$fee_amount = $percentage_amount;				
				break;
			
			case 'amt or pct of due >':
			case 'amt or pct of pymnt >':
			case 'amt or pct of prin >':
				$fee_amount = ($fixed_amount > $percentage_amount ? $fixed_amount : $percentage_amount );
				break;

			case 'amt or pct of due <':
			case 'amt or pct of pymnt <':
			case 'amt or pct of prin <':
				$fee_amount = ($fixed_amount < $percentage_amount ? $fixed_amount : $percentage_amount );
				break;
			default:
				$fee_amount = 0;
				break;
		}
		//Let's format and round it appropriately, since we are going to be displaying it in docs, potentially.
		$fee_amount = $rate_calc->round($fee_amount);
		//$fee_amount = Interest_Calculator::roundInterest($fee_amount,$rules['interest_rounding']['type'],$rules['interest_rounding']['digit']);
		return $fee_amount;
	}
	
	
	/**
	 * defaultLoan
	 * Defaults a Loan. 
	 * This clears out the scheduled events, sets the application's status to default and then to collections,
	 * assesses the CSO late fee, and assesses interest if applicable.
	 *
	 * @param int $application_id - The ID of the application you're defaulting
	 * @param float $failure_amount - the failure amount, used if the CSO late fee uses the failure amount when calculating the CSO fee.
	 * @param float $principal_balance - Principal balance override, this is used in case the principal balance needs to be overridden when calculating the CSO fee
	 */
	public function defaultLoan($application_id, $failure_amount = null, $principal_balance = null)
	{
		
		$log = get_log('scheduling');
		$log->Write("Defaulting Loan for {$application_id}");
		$business_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());
		$rule_set_id = $business_rules->Get_Rule_Set_Id_For_Application($application_id);
		$rules = $business_rules->Get_Rule_Set_Tree($rule_set_id);
		$balance = Fetch_Balance_Information($application_id);
		$schedule = Fetch_Schedule($application_id);
		$status   = Analyze_Schedule($schedule, TRUE);
		
		$date_event = date("Y-m-d");

		$db = ECash::getMasterDb();

		
		try 
		{
			$db->beginTransaction();

			Remove_Unregistered_Events_From_Schedule($application_id);

			$db->commit();
		} 
		catch (Exception $e) 
		{
			$log->Write(__METHOD__.": Unable to place account in collections.");
			$db->rollBack();
			throw $e;
		}
		
		Remove_Standby($application_id);		

	
	
		
		//We are no longer assessing a CSO Late fee at the time of default
		
		//Update to Default status
		Update_Status(null, $application_id, array('default','collections','customer','*root'), NULL, NULL, FALSE);
		
		//If this is in the middle of a rollover assess interest
		if($this->getRolloverTerm($application_id) > 1)
		{
			require_once(ECASH_COMMON_DIR . "/ecash_api/interest_calculator.class.php");

			$last_sc = $status->last_service_charge_date;
			$pdc = $this->pdc;

			$application = ECash::getApplicationById($application_id);
			$rate_calc = $application->getRateCalculator();
			$amount = $rate_calc->calculateCharge($balance->principal_balance, $last_sc, $date_event);
			//$amount = Interest_Calculator::calculateDailyInterest($rules, $balance->principal_balance, $last_sc, $date_event);
	
			$first_date_display = date('m/d/Y', strtotime($last_sc));
			$last_date_display = date('m/d/Y', strtotime($return_date));
			$comment = "$amount Interest accrued from {$first_date_display} to {$last_date_display} - Defaulted Loan";
			$log->Write($comment);
			
			// Create the SC assessment
			$amounts = array();
			$amounts[] = Event_Amount::MakeEventAmount('service_charge', $amount);
			$e = Schedule_Event::MakeEvent($date_event,$date_event,
						  $amounts, 'assess_service_chg', $comment);
			Post_Event($application_id,$e);
			$log->Write("Defaulted loan");
		}
		Update_Status(null, $application_id, array('queued','contact','collections','customer','*root'), NULL, NULL, FALSE);
		
		//When this loan defaults, it will be inserted with a default/low priority, as this only occurs after it has lapsed from
		//being in past due status.  This means that they do not have a fatal failure
	}
	
	
	public function getDefaultingFailure($application_id)
	{
		$schedule = Fetch_Schedule($application_id);
		
		$default_set = array();
		
		//determine last loan due date.
		$loan_due_date = null;
		
		foreach ($schedule as $e) 
		{
			if ($e->type == 'cso_assess_fee_broker' && $e->status == 'complete')
			{
				$loan_due_date = $e->date_effective;
			}
		}
		
		foreach ($schedule as $e) 
		{
			if(($e->context == 'generated'  || $e->is_fatal )&& $e->status == 'failed' || (strtotime($e->date_registered) > strtotime($loan_due_date)))
			{
				if (empty($default_set['return_date']))
				{
					$default_set['return_date'] = $e->return_date;
					$default_set['default_date'] = $e->date_effective;
					$default_set['default_amount'] = 0;
				}
				if ($default_set['return_date'] == $e->return_date) 
				{
					$default_set['default_amount'] = bcadd($e->principal_amount, $default_set['default_amount'],2);
					$default_set['default_amount'] = bcadd($e->fee_amount, $default_set['default_amount'],2);	
				}
			}
		}
		//This amount should be a positive number, since payments are registered as negative, we're gonna fix that.
		//If it's already a positive number, how the Hell did we default on a failed credit?!
		$default_set['default_amount'] = $default_set['default_amount'] < 0 ? -$default_set['default_amount'] : 0;
		return $default_set;
	}
	
/*	static public function getCSOFeeEvent($aplication_id, $action_date, $due_date, $rule_name, $fee_name, $fee_description)
	{
		if(! empty($fee_amount))
		{
			$fee_amount = number_format($fee_amount, 2, '.', '');
			$amounts = array();
			$amounts[] = Event_Amount::MakeEventAmount('fee', $fee_amount);
			
			return Schedule_Event::MakeEvent($action_date, $due_date, $amounts, $fee_name, $fee_description);
		}
		else
		{
			return NULL;
		}
	}
*/
	public function hasDefaulted($application_id)
	{
		$application = ECash::getApplicationByID($application_id);
		$sh          = $application->getStatusHistory();

		foreach ($sh as $history_item)
		{
			$status = $history_item->getStatus();
			
			if ($status->level0 == 'default')
				return TRUE;
		}

		return FALSE;
	}

	
}

?>
