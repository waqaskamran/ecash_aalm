<?php

require_once(ECASH_COMMON_DIR . 'ecash_api/interest_calculator.class.php');
require_once(COMMON_LIB_DIR . 'pay_date_calc.3.php');
require_once(SERVER_CODE_DIR . 'module_interface.iface.php');
require_once(SQL_LIB_DIR . 'util.func.php');
require_once(SQL_LIB_DIR . 'scheduling.func.php');

class API_Calculator implements Module_Interface
{
	public function __construct(Server $server, $request, $module_name) 
	{
		$this->request = $request;
		$this->server = $server;
		$this->name = $module_name;
        $this->permissions = array(
            array('loan_servicing'),
            array('funding'),
            array('collections'),
            array('fraud'),
        );
	
	}
	
	public function get_permissions()
	{
		return $this->permissions; 
	}
	/*
	* OFFICIAL CALCULATOR API DOCUMENTATION @ http://wiki.ecash_commercial.ept->tss/index.php/API_Calculator 
	 
To get here, your module must have been 'Calculator'.

parameters should be encoded as name-value pairs in the 'data' parameter

When action is 'paydate' function may be:

Calculate_Pay_Dates
	takes parameters
		model_name - string - pay date model name
		model_data - array - the data for  the model
		direct_deposit - boolean - optional default true
		num_dates - integer - optional default 4
		start_date - date string - optional default "now"
	returns array of paydates
Shift_Dates
	takes parameters
		date_array - array - array of dates to shift
		direction - string - optonal default "forward"
	returns - array of dates
Get_Billing_Dates
	takes no parameters
	returns array of datestrings
Get_Closest_Business_Day_Forward
	takes parameters
		date - date string - date to look forward from
	returns datestring - date
Get_Next_Business_Day
	takes parameters
		date - date string - date to look forward from
	returns datestring - date
Get_Last_Business_Day
	takes parameters
		date - date string - date to look backwards from
	returns datestring - date
Get_Business_Days_Forward
	takes parameters
		date - date string - date to look forward from
		count - integer - how many days to go forward
	returns datestring - date
Get_Calendar_Days_Forward
	takes parameters
		date - date string - date to look forward from
		count - integer - how many days to go forward
	returns datestring - date
Get_Business_Days_Backward
	takes parameters
		date - date string - date to look forward from
		count - integer - how many days to go back
	returns datestring - date
Get_Calendar_Days_Backward($date, $count)
	takes parameters
		date - date string - date to look forward from
		count - integer - how many days to go back
	returns datestring - date
Is_Weekend
	takes parameters
		timestamp - epoch number
	returns boolean - flag
Is_Holiday
	takes parameters
		timestamp - epoch number
	returns boolean - flag
get_Holiday_List
	returns array of strings
isBusinessDay
	takes parameters
		timestamp - epoch number
	returns boolean - flag
dateDiff($date_a, $date_b)
	takes parameters
		date_a - date string
		date_b - date string
	returns integer - days


When action is 'interest' function may be:

	calculateDailyInterest
	($rules, $amount, $first_date, $last_date)

	getInterestPaidPrincipalAndDate
	($input->schedule, $input->include_scheduled);

	scheduleCalculateInterest
	((Array)$input->rules, $input->schedule, $input->end_date, $input->include_scheduled);

	dateDiff
	($input->date_a, $input->date_b);

																		~
	*/
	public function Main() 
	{
		$input = $this->request->params[0];

		switch ($input->action)
		{
			case 'paydate':
				$calc = new Pay_Date_Calc_3(Fetch_Holiday_List());
				
				switch ($input->function)
				{
				    case 'Calculate_Pay_Dates':
					    $data = $calc->Calculate_Pay_Dates($input->model_name, $input->model_data, $input->direct_deposit, $input->num_dates, $input->start_date);
						break;
					case 'Shift_Dates':
						$data = $calc->Shift_Dates($input->date_array, $input->direction);
						break;
					case 'Get_Billing_Dates':
						$data = $calc->Get_Billing_Dates();
						break;
					case 'Get_Closest_Business_Day_Forward':
						$data = $calc->Get_Closest_Business_Day_Forward($input->date);
						break;
					case 'Get_Next_Business_Day':
						$data = $calc->Get_Next_Business_Day($input->date);
						break;
					case 'Get_Last_Business_Day':
						$data = $calc->Get_Last_Business_Day($input->date);
						break;
					case 'Get_Business_Days_Forward':
						$data = $calc->Get_Business_Days_Forward($input->date, $input->count);
						break;
					case 'Get_Calendar_Days_Forward':
						$data = $calc->Get_Calendar_Days_Forward($input->date, $input->count);
						break;
					case 'Get_Business_Days_Backward':
						$data = $calc->Get_Business_Days_Backward($input->date, $input->count);
						break;
					case 'Get_Calendar_Days_Backward':
						$data = $calc->Get_Calendar_Days_Backward($input->date, $input->count);
						break;
					case 'Is_Weekend':
						$data = $calc->Is_Weekend($input->timestamp);
						break;
					case 'Is_Holiday':
						$data = $calc->Is_Holiday($input->timestamp);
						break;
					case 'isBusinessDay':
						$data = $calc->isBusinessDay($input->timestamp);
						break;
					default:
						throw new Exception("Unknown paydate function {$input->function}");
				}
				break;
			case 'interest':
				//No Object Required, Static Implimentation

				$calc = new Pay_Date_Calc_3(Fetch_Holiday_List());
				
				//Get business rules if possible, rather than rely on having them all passed in and having
				//to change them EVERY time a business rule is introduced that effects interest
				$application_id = $input->application_id;
				$application = ECash::getApplicationById($application_id);
				$business_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());
				$rule_set_id = $business_rules->Get_Rule_Set_Id_For_Application($application_id);
				$rules = ($rule_set_id)?$business_rules->Get_Rule_Set_Tree($rule_set_id):(Array)$input->rules;
				$schedule = Fetch_Schedule($application_id);
				$status = Analyze_Schedule($schedule, TRUE);
				$ecash_api = eCash_API_2::Get_eCash_API($this->server->company, ECash::getMasterDb(), $application_id);
				$renewal_class =  ECash::getFactory()->getRenewalClassByApplicationID($application_id);
				switch ($input->function)
				{
    				case 'calculateDailyInterest':
						$first_date = date('Y/m/d', strtotime($input->first_date));
						$last_date  = date('Y/m/d', strtotime($input->last_date));

						$rate_calc = $application->getRateCalculator();
						$data = $rate_calc->calculateCharge($input->amount, strtotime($first_date), strtotime($last_date));
    					
						// See if their loan type is a CSO loan
						if ($rules['loan_type_model'] == 'CSO')
						{
							// if so, check to see if they defaulted
							if ($renewal_class->hasDefaulted($application_id) == TRUE)
							{
								// if so, set $data to 0.
								$data = 0.00;
							}
						}
    					
    				//	get_log()->write("Ajax call for Daily Interest\n" . print_r($input,true) . print_r($data,true));
						break;
    				case 'getInterestPaidPrincipalAndDate':
						$data = Interest_Calculator::getInterestPaidPrincipalAndDate($input->schedule, $input->include_scheduled, $rules);
						break;
  					case 'scheduleCalculateInterest':
						$end_date   = date('Y/m/d', strtotime($input->end_date));
						$data = Interest_Calculator::scheduleCalculateInterest($rules, $input->schedule, $end_date, $input->include_scheduled);
						break;
					case 'dateDiff':
						$date_a = strtotime($input->date_a);
						$date_b = strtotime($input->date_b);
						$data = Date_Util_1::dateDiff($date_a, $date_b);
						break;
					default:
						throw new Exception("Unknown paydate function {$input->function}");
				}
				break;
			default:
				throw new Exception("Unknown action {$input->action}");
		}
		return $data;
	}
}

?>
