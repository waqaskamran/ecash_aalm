<?php

require_once('dfa.php');
require_once('dfa_returns.php');
require_once(SQL_LIB_DIR . "agent_affiliation.func.php");
require_once(SQL_LIB_DIR . "application.func.php");
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(SQL_LIB_DIR."comment.func.php");
require_once(SQL_LIB_DIR . "fetch_ach_return_code_map.func.php");
require_once(SQL_LIB_DIR . "app_flags.class.php");
require_once (LIB_DIR . "Document/Document.class.php");

/**
 * History:
 *          - Removed the optionally_queue_application garbage. [BR]
 * 
 * [#16437] - Agean wanted Fees to be re-attempted if the return set only contained
 *            fees per #11299, but then changed their mind and wants that only to apply
 * 	          if the loan isn't a CA Payday Loan. [BR]
 *
 */

class ReschedulingDFABase extends ReturnsDFABase
{

	function __construct($server)
	{
		parent::__construct();
	}
	
	/**
	 * This method will use the business rules to determine the appropriate
	 * re-attempt date for the customer's first set of returns.
	 *
	 * @param Object $parameters
	 * @return string date in format of 'Y-m-d'
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
	 * @return string date in format of 'Y-m-d'
	 */
	function getAdditionalReturnDate($parameters)
	{
		$rules = $parameters->rules;
		$reattempt_date = $rules['failed_pmnt_next_attempt_date']['2'];
		return $this->getReattemptDate($reattempt_date, $parameters);		
	}
	
	/**
	 * Returns the appropriate reattempt date based on the business rule
	 * value in 'failed_pmt_next_attempt_date'.
	 * 
	 * If the rule value doesn't match an appropriate case in the switch
	 * statement, the fallback is the customer's next pay day.
	 *
	 * @param string $reattempt_date
	 * @return array Array of dates: array('event' => 'Y-m-d', 'effective' => 'Y-m-d')
	 */
	function getReattemptDate($reattempt_date, $parameters)
	{
		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);
		$today = date('Y-m-d');

		$date_pair = array();
		$date_pair['event'] = '';
		$date_pair['effective'] = '';
		
		switch($reattempt_date)
		{
			// Re-Attempt the next business day
			case 'immediate':
				if(Has_Batch_Closed())
				{
					$date_pair['event'] = $pdc->Get_Next_Business_Day($today);
				}
				else
				{
					$date_pair['event'] = $today;
				}
				$date_pair['effective'] = $pdc->Get_Next_Business_Day($date_pair['event']);
				break;

			// Since HMS & Impact get their returns at the end of the day this is almost always going to
			// be the same as 'immediate'.
			case '1 day':
				$date_pair['event'] = $pdc->Get_Next_Business_Day($today);
				$date_pair['effective'] = $pdc->Get_Next_Business_Day($date_pair['event']);
				break;

			case '2 days':
			case '3 days':
			case '4 days':
			case '5 days':
			case '6 days':
			case '7 days':
			case '8 days':
			case '9 days':
			case '10 days':
				$days = trim(substr($reattempt_date, 0, 2));
				$date_pair['event'] = $pdc->Get_Business_Days_Forward($today, $days);
				$date_pair['effective'] = $pdc->Get_Next_Business_Day($date_pair['event']);
				break;
				
			case 'next pay day or 15 days':

	       		$next_paydate = Get_Next_Payday(date("Y-m-d"), $parameters->info, $parameters->rules);
				$fortnight_n_change = $pdc->Get_Calendar_Days_Forward(date('Y-m-d'), 15);		
        		
				if(strtotime($fortnight_n_change) < strtotime($next_paydate['effective']))
				{
					$date_pair['effective'] = $pdc->Get_Closest_Business_Day_Forward($fortnight_n_change);
					$date_pair['event'] = $pdc->Get_Business_Days_Backward($fortnight_n_change,1);
				}
				else 
				{
					$date_pair = $next_paydate;
				}

			break;
	
			case 'next friday':
				if (Has_Batch_Closed()) 
				{
					$date = strtotime(date('Y-m-d') . ' +2 day');
				}
				else 
				{
					$date = strtotime(date('Y-m-d') . ' +1 day');
				}
				while ($date_pair['effective'] == '')
				{	
					if(date('w',$date) == 5)
					{
						if($pdc->Is_Holiday($date))
						{
							$date_pair['effective'] = $pdc->Get_Business_Days_Backward($date,1);
						}
						else 
						{
							$date_pair['effective'] = date('Y-m-d',$date);
						}
						$date_pair['event'] = $pdc->Get_Business_Days_Backward($date_pair['effective'],1);
					}
					$date = strtotime(date('Y-m-d',$date) . ' +1 day');
				}
				break;
				
			case '15th or 30th':
		
				if (Has_Batch_Closed()) 
				{
					$date = strtotime(date('Y-m-d') .  ' +2 day');
				}
				else 
				{
					$date = strtotime(date('Y-m-d') . ' +1 day');
				}
				while ($date_pair['effective'] == '')
				{			
					
					if(date('j',$date) == 15 || date('j',$date) == 30)
					{

						if ($pdc->isBusinessDay($date)) 
						{
							$date_pair['effective'] = date('Y-m-d',$date);
						}
						else 
						{
							$date_pair['effective'] = $pdc->Get_Business_Days_Backward($date,1);
						}
						$date_pair['event'] = $pdc->Get_Business_Days_Backward($date_pair['effective'],1);
					}
					$date = strtotime(date('Y-m-d',($date)) . ' +1 day');
				}
				break;
				
			case 'none':
				$date_pair = null;
				break;
			// Re-Attempt on the customer's next pay day (Default)
			case 'next pay day':
			default: 
				$date_pair = Get_Next_Payday(date("Y-m-d"), $parameters->info, $parameters->rules);
				break;
		}
		$this->Log("Scheduling reattempt for {$date_pair['event']} - {$date_pair['effective']} based on the rule '{$reattempt_date}'");
		return $date_pair;
		
	}
}

?>
