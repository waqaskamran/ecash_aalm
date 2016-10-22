<?php
/**
 * @package scheduling
 */

/**
 * This class will create a schedule of date_events and date_effectives for a 
 * customer. This should be used over the paydate calculator (in fact it wraps 
 * the paydate calculator) so that skip periods and other rules can be 
 * properly followed.
 * 
 * @author Mike Lively <Mike.Lively@sellingsource.com>
 */
class ECash_DueDateCalculator
{
	/**
	 * Pay model info.
	 *
	 * @var stdClass
	 */
	protected $info;
	
	/**
	 * The earliest possible date_effective to return.
	 *
	 * @var string
	 */
	protected $first_date;
	
	/**
	 * The ruleset tree for this application.
	 *
	 * @var array
	 */
	protected $rules;
	
	/**
	 * The number of service charges on the account. [deprecated]
	 *
	 * @var int
	 * @deprecated 
	 */
	protected $num_service_charges;
	
	/**
	 * Creates a new calculator for this application. 
	 * 
	 * The $info parameter can be populated with a call to 
	 * Get_Transactional_Data()->info. The $first_date parameter is an 
	 * arbitrary date that is the starting point for all returned dates. The 
	 * $rules parameter can be populated with a call to Get_Rule_Set_Tree or 
	 * Get_Transactional_Data()->rules. The $num_service_charges parameter is 
	 * deprecated and should no longer be used. 
	 *
	 * @param stdClass $info
	 * @param string $first_date
	 * @param array $rules
	 * @param int $num_service_charges
	 */
	public function __construct($info, $first_date, $rules, $num_service_charges = null)
	{
		if (! isset($rules['period_skip']))
		{
			$this->rules = Prepare_Rules($rules, $info);
		}
		else 
		{
			$this->rules = $rules;
		}
		
		$this->info = $info;
		$this->first_date = $first_date;
		$this->rules = $rules;
		$this->num_service_charges = $num_service_charges;
	}
	
	/**
	 * Returns an array containing two indexed arrays with keys of 'events' 
	 * and 'effectives'.
	 * 
	 * The $total_dates parameter is the maximum number of dates to return. 
	 * $start_date is an optional parameter specifying which date the list 
	 * should be built from. In almost every case this should be set to null 
	 * to guarantee that eCash will build the date list with the appropriate 
	 * skip periods.
	 *
	 * Revision History:
	 *	tonyc - 11/01/2007 - added date_first_payment check [mantis:12533]
	 *	alexanderl - 12/04/2007 - added last_payment_date check to the previous one to use the fund_date 
	 *				  as the start_date if the schedule completes having only Fund Loan [mantis:13157]
	 *
	 * @param int $total_dates
	 * @param string $start_date
	 * @return array
	 */
	public function getDateList($total_dates = 1, $start_date = null)
	{
		$pdc = $this->getPaydateCalc();
		
		$total_dates_adjustment = 8;

		$fund_date = $this->info->date_fund_stored;
		$this->log("date_first_payment: {$this->info->date_first_payment}");
		$this->log("fund_date: {$fund_date}");

		if ($this->rules['period_skip'])
		{
			$total_dates_adjustment = 36;
			//$fund_date = $this->info->date_fund_stored;

			if (!empty($this->info->date_first_payment)) // if there's a date_first_payment, then use it [#8195]
			{
				$start_date = $pdc->Get_Calendar_Days_Backward($this->info->date_first_payment, 1); // mantis:13257
				$this->log("Using date_first_payment as start date: {$this->info->date_first_payment}");
			}
			elseif (!empty($fund_date) && (strtotime($fund_date) > strtotime("-36 weeks")))
			{
				$start_date = $fund_date;
				$this->log("Using skip start date of: {$start_date}");
			}
			else 
			{
				$start_date = $this->getCalculatedStartDate();
				$this->log("Using calculated start_date of: {$start_date}");
			}
		}
		elseif (empty($start_date))
		{
			/*
			if (!empty($this->info->date_first_payment))
			{
				$start_date = $pdc->Get_Calendar_Days_Backward($this->info->date_first_payment, 1); // mantis:13257
				$this->log("Using date_first_payment as start date, passed start_date empty: {$this->info->date_first_payment}");
			}
			else
			{
			*/
				$start_date = $this->getCalculatedStartDate();
				$this->log("Using calculated start date of: {$start_date}");
			//}
		}
		else 
		{
			$this->log("Using given start date of: {$start_date}");
		}
		
		$pay_dates = $pdc->Calculate_Pay_Dates($this->info->paydate_model, $this->info->model, $this->info->direct_deposit, ($total_dates * 2) + 36, $start_date);
		
		$this->log("First date returned by pdc: {$pay_dates[0]}");
		$this->removeDatesBeforeFundingGracePeriod($pay_dates);
		$this->log("First date after grace period: {$pay_dates[0]}");
		
		if ($this->rules['period_skip'])
		{
			$this->applySkipPeriod($pay_dates);
		}
		
		$this->removeDatesUntilLastPayment($pay_dates);
		$this->chooseNextPaymentDate($pay_dates);
		
		$this->log("First date after last payment: {$pay_dates[0]}");
		$this->removeDatesBeforeFirstDate($pay_dates);
		$this->log("First date returned: {$pay_dates[0]}");
		
		$pay_dates = array_slice($pay_dates, 0, $total_dates);
		
		$event_effective_dates = $this->convertDateListToEventsAndEffectives($pay_dates);
		//return array_slice($event_effective_dates, 0, $total_dates);
		return $event_effective_dates;
	}
	
	/**
	 * Returns a paydate calculator to use in determining pay days.
	 *
	 * @return Pay_Date_Calc_3
	 */
	protected function getPaydateCalc()
	{
		static $instance;
		
		if (empty($instance))
		{
			$instance = new Pay_Date_Calc_3(Fetch_Holiday_List());
		}
		
		return $instance;
	}
	
	/**
	 * Returns the date an application was funded. If such a date does not 
	 * exist the current date is returned.
	 *
	 * @return string
	 */
	protected function getDateFunded()
	{
		if (! empty($this->info->date_fund_stored)){
			$date_funded = $this->info->date_fund_stored;
		} else {
			$date_funded = date('Y-m-d');
		}
		return $date_funded;
	}
	
	/**
	 * Calculates a start date based on the applications last payment date.
	 * 
	 * If a payment date does not exist the assessment date is used. If the 
	 * assessment date does not exist 2 months prior to the current date is 
	 * used.
	 *
	 * @return string
	 */
	protected function getCalculatedStartDate()
	{
		$start_timestamp = strtotime('-2 months');
		
		if (!empty($this->info->last_payment_date))
		{
			$time = strtotime($this->info->last_payment_date);
			$start_timestamp = min($start_timestamp, $time);
		}
		elseif (!empty($this->info->last_assessment_date))
		{
			$time = strtotime($this->info->last_assessment_date);
			$start_timestamp = min($start_timestamp, $time);
		}
		
		$start_date = date('Y-m-d', $start_timestamp);
		return $start_date;
	}
	
	/**
	 * Strips out dates in $date_list that are before the funding grace 
	 * period.
	 * 
	 * Please note the $date_list WILL be modified.
	 * 
	 * <b>Revision History:</b>
	 * <ul>
	 *     <li><b>2007-12-12 - mlively</b><br>
	 *         Changed grace period to not count transactions on the Nth day.
	 *     </li>
	 * </ul>
	 *
	 * @param array $date_list
	 */
	protected function removeDatesBeforeFundingGracePeriod(&$date_list)
	{
		$pdc = $this->getPaydateCalc();
		$date_funded = $this->getDateFunded();
        	$grace_days = $this->rules['grace_period'];
        
        	// Include the reaction due date for the grace period for react apps
        	if ($this->info->is_react && (strtotime($date_funded)>time())){
            		$react_due_time = strtotime($this->rules['react_grace_date']);
            		$react_due_offset = $react_due_time - time();
            		$react_due_offset = ceil($react_due_offset / (24 * 60 * 60));
            
            		if ($react_due_offset > $grace_days) $grace_days = $react_due_offset;
        	}
		$grace_period = $pdc->Get_Calendar_Days_Forward($date_funded, $grace_days + 1);
		
		$grace_period_timestamp = strtotime($grace_period);
		
		$this->removeDatesBeforeDate($date_list, $grace_period_timestamp);
	}
	
	/**
	 * Strips out dates in $date_list that fall before the last payment.
	 * 
	 * This function will reinsert the date before the last payment just in 
	 * case the last payment date was shifted. Please note the $date_list WILL 
	 * be modified.
	 *
	 * @param array $date_list
	 */
	protected function removeDatesUntilLastPayment(&$date_list)
	{
		if (!empty($this->info->last_payment_date))
		{
			$last_payment_timestamp = strtotime($this->info->last_payment_date);
			$this->log("In removeDatesUntilLastPayment, last_payment_date: {$this->info->last_payment_date}");
			
			$this->removeDatesToFirstBeforeDate($date_list, $last_payment_timestamp);
		}
		elseif (!empty($this->info->date_first_payment))
		{
			$first_payment_timestamp = strtotime($this->info->date_first_payment);
			$this->log("In removeDatesUntilLastPayment, date_first_payment: {$this->info->date_first_payment}");
			$this->log("In removeDatesUntilLastPayment, date_list0: {$date_list[0]}");
			
			$this->removeDatesBeforeDate($date_list, $first_payment_timestamp);
		}
	}
	
	/**
	 * Strips out dates in $date_list that fall before the first date (set in 
	 * the constructor)
	 *
	 * Please note the $date_list WILL be modified.
	 * 
	 * @param array $date_list
	 * @see $first_date
	 */
	protected function removeDatesBeforeFirstDate(&$date_list)
	{
		$first_timestamp = strtotime($this->first_date);
		$this->removeDatesBeforeDate($date_list, $first_timestamp);
	}
	
	/**
	 * Strips out dates in $date_list that fall before $cutoff_timestamp.
	 *
	 * Please note the $date_list WILL be modified.
	 * 
	 * @param Array $date_list
	 * @param int $cutoff_timestamp
	 */
	protected function removeDatesBeforeDate(&$date_list, $cutoff_timestamp)
	{
		while (strtotime($date_list[0]) < $cutoff_timestamp && count($date_list) > 0)
		{
			array_shift($date_list);
		}
	}
	
	/**
	 * Strips out all dates in $date_list that fall before $cutoff_timestamp 
	 * with the exception of the date immediately preceding $cutoff_timestamp. 
	 *
	 * Please note the $date_list WILL be modified.
	 * 
	 * @param Array $date_list
	 * @param int $cutoff_timestamp
	 */
	protected function removeDatesToFirstBeforeDate(&$date_list, $cutoff_timestamp)
	{
		while (strtotime($date_list[0]) <= $cutoff_timestamp && count($date_list) > 0)
		{
			$last_date = array_shift($date_list);
		}
		
		if (isset($last_date))
		{
			array_unshift($date_list, $last_date);
		}
	}
	
	/**
	 * Compares the first two dates in $date_list to the last payment date. If 
	 * the first date is closest then that date will be removed. If the second 
	 * paydate is closer then both the first and second dates will be removed. 
	 * 
	 * The logic behind this is that we want this calculator to return the 
	 * first due date AFTER the last due date. This method will assist in 
	 * ensuring that if a due-date was moved we will be able to properly 
	 * associate that due-date with a paydate allowing us to accurately 
	 * account for skip periods (weekly paydates.)
	 * 
	 * Please note the $date_list WILL be modified.
	 * 
	 * @param Array $date_list
	 */
	/* As noted in an IRC conversation with lively, which might be helpful in the future:
	 * by the time you reach that function the first date should be the date immediately prior to their last payment date
     */
	protected function chooseNextPaymentDate(&$date_list)
	{
		if (count($date_list) >= 2 && !empty($this->info->last_payment_date))
		{
			$last_payment_timestamp = strtotime($this->info->last_payment_date);
			$first_timestamp_diff = abs($last_payment_timestamp - strtotime($date_list[0]));
			$second_timestamp_diff = abs($last_payment_timestamp - strtotime($date_list[1]));
			
			if ($first_timestamp_diff < $second_timestamp_diff)
			{
				array_shift($date_list);
			}
			else
			{
				array_shift($date_list);
				array_shift($date_list);
			}
		}
	}
	
	/**
	 * Returns an array based off of $date_list containing two indexed arrays 
	 * with keys of 'events' and 'effectives'.
	 * 
	 * The 'effectives' array should correspond with the paydates in 
	 * $date_list and the 'events' array should contain the business days 
	 * prior to each paydate.
	 * 
	 * <b>Revision History:</b>
	 * <ul>
	 *     <li><b>Mike Lively - 2007-08-28</b><br>
	 *         Added an adjustment to the index that will essentially cause 
	 *         the first due date to be returned first on accounts with no 
	 *         previous payments. The variable doing this is $index_adjustment
	 *     </li>
	 * 
	 * @param array $date_list
	 * @return array
	 */
	protected function convertDateListToEventsAndEffectives($date_list)
	{
		$pdc = $this->getPaydateCalc();
		$event_effectives = array('effective' => array(), 'event' => array());
		
		$index_adjustment = empty($this->info->last_payment_date) ? 1 : 0;
		
		foreach ($date_list as $index => $date_effective)
		{
			$event_effectives['effective'][] = $date_effective;
			if ($this->info->is_card_payment) $event_effectives['event'][] = $date_effective;
			else $event_effectives['event'][] = $pdc->Get_Last_Business_Day($date_effective);
		}
		
		return $event_effectives;
	}
	
	protected function applySkipPeriod(&$date_list)
	{
		$new_array = array();
		foreach ($date_list as $index => $date)
		{
			if (!($index % 2))
			{
				$new_array[] = $date;
			}
		}
		$date_list = $new_array;
	}
	
	/**
	 * A shortcut to assist in logging data into the scheduling log.
	 * 
	 * This method will prefix all log entries with this class' name.
	 *
	 * @param unknown_type $msg
	 */
	protected function log($msg)
	{
		get_log('scheduling')->Write(__CLASS__.": {$msg}");
	}
}

?>
