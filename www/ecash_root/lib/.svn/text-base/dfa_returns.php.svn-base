<?php

require_once('dfa.php');
require_once(SQL_LIB_DIR . "agent_affiliation.func.php");
require_once(SQL_LIB_DIR . "application.func.php");
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(SQL_LIB_DIR."comment.func.php");
require_once(SQL_LIB_DIR . "fetch_ach_return_code_map.func.php");
require_once(SQL_LIB_DIR . "fetch_card_return_code_map.func.php");
require_once(SQL_LIB_DIR . "app_flags.class.php");
require_once (LIB_DIR . "Document/Document.class.php");


class ReturnsDFABase extends DFA
{

	function __construct()
	{
		parent::__construct();
	}

	
	/**
	 * Checks to see if there are any items in the application's failset
	 *
	 * @param obj $parameters
	 * @return boolean
	 */
	function has_failures ($parameters) 
	{
		if(count($parameters->status->fail_set) > 0)
		{
			return 1;
		}

		return 0;
	}
	
	/**
	 * Checks to see if the most recent failure is an ACH failure.
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function is_ach ($parameters)
	{
		$map = $parameters->event_transaction_map;
		$f = $parameters->most_recent_failure;
		
		switch($map[$f->type]->clearing_type)
		{
			case 'ach':
				return 1;
				break;
			default:
				return 0;
				break;
		}
	}
	
	/**
	 * Checks to see if the most recent failure is an 'arrange next payment'
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function is_arrange_next ($parameters)
	{
		$fail_set = $parameters->status->fail_set;
		$map = $parameters->event_transaction_map;
		$f = $parameters->most_recent_failure;
		
		if($f->context == 'arrange_next')
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Checks to see if the most recent return is a fatal failure
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function is_fatal ($parameters)
	{		
		$code_map = Fetch_ACH_Return_Code_Map();
		foreach ($parameters->status->fail_set as $f) 
		{
			foreach ($code_map as $options)
			{
				if ($options['return_code'] == $f->return_code)
				{
					if ($options['is_fatal'] == 'yes')
					{
						return 1;
					}
				}
			}
		}
		
		$code_map = Fetch_Card_Return_Code_Map();
		foreach ($parameters->status->fail_set as $f) 
		{
			foreach ($code_map as $options)
			{
				if ($options['return_code'] == $f->return_code)
				{
					if ($options['is_fatal'])
					{
						return 1;
					}
				}
			}
		}
		
		return 0;
	}
	
	
	/**
	 * Checks to see if an application has reached the return limit
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function is_at_return_limit($parameters) 
	{
		$r = $parameters->rules;
		$s = $parameters->status;

		return (($s->max_reattempt_count >= $r['max_svc_charge_failures'])?1:0);
	}
	
	/**
	 * Checks to see if an application is in a holding status (a status which should be left alone)
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function is_in_holding_status($parameters) 
	{
		$application_id = $parameters->application_id;
		if(In_Holding_Status($application_id)) 
		{
			return 1;
		}
		return 0;
	}
	
	/**
	 * Checks to see if there are credits in the fail set.  We usually don't take such a harsh approach when there's
	 * a problem with the customer taking money.
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function has_credits($parameters) 
	{
		foreach ($parameters->status->fail_set as $e) 
		{
			$total = 0;
			foreach($e->amounts as $amount)
			{
				$total = bcadd($total,$amount->amount,2);
			}
			if ($total > 0.0) return 1;
		}
		return 0;
	}

	/**
	 * Checks to see if a specific event type exists in the failset
	 *
	 * @param unknown_type $parameters
	 * @param unknown_type $comparison_type
	 * @param unknown_type $checklist
	 * @return unknown
	 */
	function has_type($parameters, $comparison_type, $checklist='failures') 
	{
		if ($checklist == 'failures') 
		{
			$list = $parameters->status->fail_set;
		}
		else 
		{
			$list = $parameters->schedule;
		}
		
		foreach ($list as $e) 
		{
			if ($e->type == $comparison_type) return 1;
		}
		return 0;
	}
	
	/**
	 * Checks to see if the requested transaction is a reattempt, but verifying that it has an origin_id and a context of reattempt
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function is_reattempt($parameters) 
	{
		foreach ($parameters->status->fail_set as $e) 
		{
			//Reattempts have an origin_id (because they originated from another transaction
			//Reattempts also have a context of reattempt!
			if ($e->origin_id != null && $e->context == 'reattempt') 
			{
				return 1;
			}
		}
		return 0;
	}

	/**
	 * Checks to see if there's a disbursement in the failset
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function is_disbursement($parameters) 
	{ 
		return $this->has_type($parameters, 'loan_disbursement'); 
	}
	
	/**
	 * Checks to see if there's a quickcheck in the failset.
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function is_quickcheck($parameters) 
	{ 
		return $this->has_type($parameters, 'quickcheck','schedule'); 
	}
	
	/**
	 * Checks to see if there's a fullpull in the failset
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function is_fullpull($parameters) 
	{ 
		return $this->has_type($parameters, 'full_balance'); 
	}
	
	
	function is_watched($parameters) 
	{ 
		return (($parameters->is_watched == 'yes')?1:0); 
	}

	/**
	 * Checks to see if there's a fatal ACH return in the current failset
	 *
	 * Yes, it duplicates functionality, but I haven't had time to refactor all the DFAs and strip it all out.
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function has_fatal_ach($parameters) 
	{
		$ach_code_map = Fetch_ACH_Return_Code_Map();
		foreach ($parameters->status->fail_set as $f) 
		{
			foreach ($ach_code_map as $options)
			{
				if ($options['return_code'] == $f->return_code)
				{
					if ($options['is_fatal'] == 'yes')
					{
						return 1;
					}
				}
			}
		}
		
		$card_code_map = Fetch_Card_Return_Code_Map();
		foreach ($parameters->status->fail_set as $f) 
		{
			foreach ($card_code_map as $options)
			{
				if ($options['return_code'] == $f->return_code)
				{
					if ($options['is_fatal'])
					{
						return 1;
					}
				}
			}
		}
		
		return 0;
	}

	/**
	 * Checks to see if there's a fatal ACH return in the current failset
	 *
	 * Yes, it duplicates functionality, but I haven't had time to refactor all the DFAs and strip it all out.
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function has_fatal_card($parameters) 
	{
		$code_map = Fetch_Card_Return_Code_Map();
		foreach ($parameters->status->fail_set as $f) 
		{
			foreach ($code_map as $options)
			{
				if ($options['return_code'] == $f->return_code)
				{
					if ($options['is_fatal'])
					{
						return 1;
					}
				}
			}
		}
		
		return 0;
	}

	/**
	 * Checks to see if an application has a fatal ACH return on the current bank account, and whether or not the 
	 * fatal flag has already been set.
	 * Return true if it sets the flag, false in all other cases.
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function fatal_flag($parameters)
	{
		$fatal = false;
		$app = 	ECash::getApplicationByID($parameters->application_id);

		$flags = $app->getFlags();
		//check and see if it's on the same bank account, if so, add the has_fatal. If not, who cares?
		if($this->has_fatal_ach_current_bank_account($parameters) && (!$flags->get('has_fatal_ach_failure')))
		{
			$fatal = true;
			$this->_set_flag($parameters->application_id, 'has_fatal_ach_failure');
		}
		return $fatal;
	}
	
	/**
	 * Checks to see if there was a fatal return on the current bank account.
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function has_fatal_ach_current_bank_account ($parameters) 
	{
		
		if (empty($parameters->status->fail_set)) return 0;
		$code_map = Fetch_ACH_Return_Code_Map();
		foreach ($parameters->status->fail_set as $f) 
		{
            foreach ($code_map as $options)
            {
                if ($options['return_code'] == $f->return_code)
                {
                    if ($options['is_fatal'] == 'yes') 
                    {
						$this->log->Write($this->log_prefix . ' ' . print_r($f, true));
						if ($f->bank_account === $f->current_bank_account && $f->bank_aba === $f->current_bank_aba )
	                        return 1;
                    }
                }
            }
		}
		
		return 0;
	}
	

	/**
	 * Sets the specified flag on an application.
	 *
	 * @param unknown_type $application_id
	 * @param unknown_type $flag
	 * @return unknown
	 */
	protected function _set_flag($application_id, $flag)
	{
		$app = 	ECash::getApplicationByID($application_id);

		$flags = $app->getFlags();
		
		// only set it if its not set already
		$flags->set($flag);
		
		return 0;
	}
	
	/**
	 * This method will use the business rules to determine the appropriate
	 * re-attempt date for the customer's first set of returns.
	 *
	 * @param Object $parameters
	 * @return array Array of dates: array('event' => 'Y-m-d', 'effective' => 'Y-m-d')
	 */
	function getFirstReturnDate($parameters)
	{
		$rules = $parameters->rules;
		$reattempt_date = $rules['failed_pmnt_next_attempt_date']['1'];
		return $this->getReattemptDate($reattempt_date, $parameters);
	}
	
	/**
	 * This method will use the business rules to determine the appropriate
	 * re-attempt date for all returns after the customer's first.
	 *
	 * @param Object $parameters
	 * @return array Array of dates: array('event' => 'Y-m-d', 'effective' => 'Y-m-d')
	 */
	function getAdditionalReturnDate($parameters)
	{
		$rules = $parameters->rules;
		$reattempt_date = $rules['failed_pmnt_next_attempt_date']['2'];
		return $this->getReattemptDate($reattempt_date, $parameters);		
	}
	
	/**
	 * This method uses the failed_pmnt_next_attempt_date->full_pull rule to determine when to schedule a full pull
	 * on the account.
	 * It also uses the full_pulls->days_delinquent to determine the number of days to delay the scheduled date of the
	 * full pull by.
	 *
	 * @param Object $parameters
	 * @return array Array of dates: array('event' => 'Y-m-d', 'effective' => 'Y-m-d')
	 */
	function getFullPullDate($parameters)
	{
		$rules = $parameters->rules;
		$delay = (is_array($rules['full_pulls'])) ? $rules['full_pulls']['days_delinquent'] : $rules['full_pulls'];
		$reattempt_date = $rules['failed_pmnt_next_attempt_date']['full_pull'];
		return $this->getReattemptDate($reattempt_date, $parameters,$delay);		
	}
	
		
	/**
	 * Returns the appropriate reattempt date based on the business rule
	 * value in 'failed_pmt_next_attempt_date', and the delay passed in.
	 * 
	 * If the rule value doesn't match an appropriate case in the switch
	 * statement, the fallback is the customer's next pay day.
	 *
	 * 
	 * @param string $reattempt_date
	 * @param obj $parameters
	 * @param int $delay
	 * @return array Array of dates: array('event' => 'Y-m-d', 'effective' => 'Y-m-d')
	 */
	function getReattemptDate($reattempt_date, $parameters, $delay = 0)
	{
		$application = ECash::getApplicationByID($parameters->application_id);
		//Using the centralized function in scheduling.func, because this logic is used in a couple places.
		$date_pair = getReattemptDate($reattempt_date,$application,$delay);
				
		$this->Log("Scheduling reattempt for {$date_pair['event']} - {$date_pair['effective']} based on the rule '{$reattempt_date}' with a delay of {$delay}");
		return $date_pair;
		
	}
}

?>
