<?php
/**
 * Basic commercial Qualify.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class ECash_VendorAPI_Qualify extends VendorAPI_Qualify
{
	const PAY_DATES_NEEDED = 52;

	/**
	 * @var Pay_Date_Calc_3
	 */
	protected $pay_date_calc;

	/**
	 * @var eCash_API_2
	 */
	protected $ecash_api;

	/**
	 * Instance of ECash_Qualify.
	 *
	 * @var ECash_IQualify
	 */
	protected $qualify;

	/**
	 * @var LoanAmountCalculator
	 */
	protected $loan_amount_calc;

	/**
	 * @var ECash_Transactions_IRateCalculator
	 */
	protected $rate_calculator;

	/**
	 * @var string
	 */
	protected $property_short;

	/**
	 * @var array
	 */
	protected $business_rules;

	/**
	 * @var string
	 */
	protected $loan_type_name;

	/**
	 * @var string
	 */
	protected $loan_type_short;

	/**
	 * @var VendorAPI_IDriver
	 */
	protected $driver;

	/**
	 *
	 * @var ECash_Factory
	 */
	protected $factory;

	/**
	 *
	 * @var Integer
	 */
	protected $loan_type_id;
	
	/**
	 * Object containing name / nameshort. 
	 * of the loan type we're using.
	 * @var Object
	 */
	protected $loan_type;

	/**
	 * Constructor
	 *
	 * @param ECash_Qualify $qualify
	 * @param Pay_Date_Calc_3 $pay_date_calc
	 * @param eCash_API_2 $ecash_api
	 * @param LoanAmountCalculator $loan_amount_calc
	 * @param string $property_short - Abbreviated company name
	 * @param string $loan_type_name - The full name of the loan type
	 * @param ECash_BusinessRules $business_rules
	 * @param ECash_Factory $factory
	 */
	public function __construct(
		ECash_IQualify $qualify,
		Pay_Date_Calc_3 $pay_date_calc,
		eCash_API_2 $ecash_api,
		LoanAmountCalculator $loan_amount_calc,
		$property_short = NULL,
		$loan_type_name = NULL,
		$business_rules = NULL,
		$factory = NULL
	)
	{
		$this->qualify = $qualify;
		$this->pay_date_calc = $pay_date_calc;
		$this->ecash_api = $ecash_api;
		$this->loan_amount_calc = $loan_amount_calc;
		$this->property_short = $property_short;
		$this->loan_type_name = $loan_type_name;
		$this->business_rules = $business_rules;
		$this->factory = $factory;
	}

	/**
	 * Sets the business rules to use
	 *
	 * @param array $rules
	 * @return void
	 */
	public function setBusinessRules(array $rules)
	{
		$this->business_rules = $rules;
		$this->qualify->setBusinessRules($rules);
	}

	/**
	 * Sets the loan type name to use
	 *
	 * @param string $loan_type_name
	 * @return void
	 */
	public function setLoanTypeName($loan_type_name, $name_short)
	{
		$this->loan_type_name = $loan_type_name;
		$this->loan_type_short = $name_short;
	}

	/**
	 * Defined by VendorAPI_IQualify
	 *
	 * @param array $data
	 * @param int $loan_amount
	 * @return VendorAPI_QualifyInfo
	 */
	public function qualifyApplication(array $data, $loan_amount = NULL)
	{
		if (isset($data['loan_type_id']) && is_numeric($data['loan_type_id']))
		{
			$this->setLoanTypeId($data['loan_type_id']);
		}
		$this->mapVehicleData($data);
		// Need timestamps for qualify class
		$pay_dates = array_map('strtotime', $this->getPaydates($data));
		$this->max_fund_amount = (int)$this->calculateMaxLoanAmount($data, $this->isReact($data));

		// If this is an eCash react desired amount is set and less less than max amount, use ita
                if ($data['olp_process'] == 'ecashapp_react'
		    && !empty($data['loan_amount_desired'])
		    && is_numeric($data['loan_amount_desired']))
		{
			$this->fund_amount = $data['loan_amount_desired'];
			$this->max_fund_amount = $this->fund_amount;
		} elseif ($loan_amount !== NULL)
		{
			if ($data['olp_process'] == 'ecashapp_react') {
				$this->fund_amount = $loan_amount;
			} else {
				$this->fund_amount = min($loan_amount, $this->max_fund_amount);
			}
		}
		else
		{
			$this->fund_amount = $this->max_fund_amount;
		}

		$this->fund_date = strtotime($this->ecash_api->Get_Date_Fund_Estimated());

		$this->payoff_date = $this->qualify->calculateDueDate(
			$pay_dates,
			$data['income_direct_deposit'],
			$this->fund_date,
	            $this->isReact($data)
		);

		$this->finance_charge = $this->calculateFinanceCharge(
			$this->fund_amount,
			$this->fund_date,
			$this->payoff_date
		);

		$this->total_payments = $this->fund_amount + $this->finance_charge;

		$rate_calc = $this->getRateCalculator();
		$this->apr = $rate_calc->getAPR(
			$this->fund_date,
			$this->payoff_date
		);
		return new VendorAPI_QualifyInfo(
			$this->max_fund_amount,
			$this->fund_amount,
			$this->apr,
			$this->fund_date,
			$this->payoff_date,
			$this->finance_charge,
			$this->total_payments
		);
	}

	/**
	 * Calculate paydates and return them as an array
	 * @param Array $data
	 * @return Array
	 */
	public function getPaydates($data)
	{
		$paydate_info = new stdClass();
		$paydate_info->paydate_model = $data['paydate_model'];
		$paydate_info->income_frequency = $data['income_frequency'];
		$paydate_info->income_direct_deposit = $data['income_direct_deposit'];
		$paydate_info->last_paydate = $data['last_paydate'];
		$paydate_info->day_string_one = $data['day_of_week'];
		$paydate_info->day_int_one = $data['day_of_month_1'];
		$paydate_info->day_int_two = $data['day_of_month_2'];
		$paydate_info->week_one = $data['week_1'];
		$paydate_info->week_two = $data['week_2'];

		return $this->pay_date_calc->Calculate_Pay_Dates(
			$paydate_info->paydate_model,
			$paydate_info,
			$data['income_direct_deposit'],
			self::PAY_DATES_NEEDED
		);
	}

	/**
	 * (non-PHPdoc)
	 * @see code/VendorAPI/VendorAPI_IQualify#calculateFinanceInfo()
	 */
	public function calculateFinanceInfo($amount, $fund, $due_date)
	{
		$this->fund_amount = $amount;
		$this->fund_date = $fund;
		$this->payoff_date = $due_date;

		$this->finance_charge = $this->calculateFinanceCharge(
			$this->fund_amount,
			$this->fund_date,
			$this->payoff_date
		);

		$this->total_payments = $this->fund_amount + $this->finance_charge;
		$rate_calc = $this->getRateCalculator();
		$this->apr = $rate_calc->getAPR(
			$this->fund_date,
			$this->payoff_date
		);

		return new VendorAPI_QualifyInfo(
			$amount,
			$amount,
			$this->apr,
			$fund,
			$due_date,
			$this->finance_charge,
			$this->total_payments
		);
	}

	/**
	 * Calculates the maximum loan amount
	 *
	 * @param int $monthly_income
	 * @param bool $react
	 */
	protected function calculateMaxLoanAmount(array $data, $is_react)
	{
		$info = new stdClass();
		$info->business_rules = $this->business_rules;
		$info->income_monthly = $data['income_monthly'];
		$info->is_react = ($is_react ? 'yes' : 'no');
		$info->application_status = $data['application_status'];
		$info->loan_type_name = $this->getLoanTypeName();
		$info->num_paid_applications = (int)$data['num_paid_applications'];
		$info->prev_max_qualify = (int)$data['prev_max_qualify'];
		$info->react_app_id = isset($data['react_app_id']) ? $data['react_application_id'] : NULL;
		$info->application_list = isset($data['application_list']) ? $data['application_list'] : array();
		$info->payperiod = $data['income_frequency'];
		if(isset($data['idv_increase_eligible']) && $data['idv_increase_eligible'])
		{
			$info->idv_increase_eligible = $data['idv_increase_eligible'];
		}
		else
		{
			$info->idv_increase_eligible = FALSE;
		}
		$vehicle_data = $this->mapVehicleData($data);
		foreach ($vehicle_data as $k => $v)
		{
			$info->$k = $v;
		}
		return (int)$this->loan_amount_calc->calculateMaxLoanAmount($info);
	}

	/**
	 * Makes a call to the static calculateDailyInterest in the Interest_Calculator and returns the result.
	 *
	 * @param int $fund_amount
	 * @param int $start_date
	 * @param int $end_date
	 * @return int
	 */
	protected function calculateFinanceCharge($fund_amount, $start_date, $end_date)
	{
		$rate_calc = $this->getRateCalculator();
		return $rate_calc->calculateCharge(
			$fund_amount,
			$start_date,
			$end_date
			);
	}

	/**
	 * Sets the rate calculator on the qualify object
	 * 
	 * @param ECash_Transactions_IRateCalculator $rate_calculator
	 */
	public function setRateCalculator(ECash_Transactions_IRateCalculator $rate_calculator)
	{
		$this->rate_calculator = $rate_calculator;
	}

	/**
	 * Returns the rate calculator used to determine the service charge or interest rate
	 * as well as the APR
	 * 
	 * @return ECash_Transactions_IRateCalculator
	 */
    protected function getRateCalculator()
    {
		if(! $this->rate_calculator)
		{
	        throw new Exception("No rate calculator has been set on " . __CLASS__);
		}

		return $this->rate_calculator;
    }

	/**
	 * Returns the minimum fund amount
	 *
	 * @param bool $is_react
	 * @return int
	 */
	protected function getMinFundAmount($is_react)
	{
		$rule = $is_react ? 'min_react' : 'min_non_react';
		return (
			is_numeric($this->business_rules['minimum_loan_amount'][$rule])
			? $this->business_rules['minimum_loan_amount'][$rule]
			: 150
		);
	}

	protected function getFundIncrement()
	{
		return (
			is_numeric($this->business_rules['loan_amount_increment'])
			? $this->business_rules['loan_amount_increment']
			: 50
		);
	}

	/**
	 * Defined by VendorAPI_IQualify
	 *
	 * @param int $fund_amount
	 * @param bool $is_react
	 * @return array
	 */
	public function getAmountIncrements($fund_amount, $is_react)
	{
		$amount = $this->getMinFundAmount($is_react);
		$end   = $fund_amount;
		$inc   = $this->getFundIncrement();
		$return = array();
		for ($end = $end + ($inc -1);$amount < $end; $amount += $inc)
		{
			$return[] = min($amount, $fund_amount);
		}
		return $return;
	}

	public function getLoanTypeName()
	{
		if (empty($this->loan_type_name))
		{
			$this->loan_type_name = $this->findLoanType()->name;
		}
		return $this->loan_type_name;
	}
	
	public function getLoanTypeNameShort()
	{
		if (empty($this->loan_type_short))
		{
			$this->loan_type_short = $this->findLoanType()->name_short;
		}	
		return $this->loan_type_short;
	}

	protected function getFactory()
	{
		if (!$this->factory instanceof ECash_Factory)
		{
			throw new RuntimeException('No ecash factory to find models.');
		}
		return $this->factory;
	}
	
	protected function findLoanType()
	{
		if (isset($this->loan_type))
		{
			return $this->loan_type;
		}
		if (is_numeric($this->loan_type_id))
		{

			$loan_type = $this->getFactory()->getModel('LoanType');
			if ($loan_type->loadBy(array('loan_type_id' => $this->loan_type_id)))
			{
				$this->loan_type = $loan_type;
				return $loan_type;
			}
			else
			{
				throw new RuntimeException('Failed to load a loan type with id ('.$this->loan_type_id.').');
			}
		}
		else
		{
			throw new RuntimeException('No loan type id to find a loan type name.');
		}
	}


	/**
	 * Set loan type id
	 *
	 * @param Integer $id
	 */
	public function setLoanTypeId($id)
	{
		$this->loan_type_id = $id;
		unset($this->loan_type);
		unset($this->loan_type_short);
		unset($this->loan_type_name);
	}
	

	/**
	 * Map all the data to what the actual
	 * calculators look for
	 * @param Array $data
	 * @return Array
	 */
	public function mapVehicleData($data)
	{
		$return = array();
		$return['vehicle_vin'] = empty($data['vin']) ? NULL : $data['vin'];
		$return['vehicle_make'] = empty($data['make']) ? NULL : $data['make'];
		$return['vehicle_year'] = empty($data['year']) ? NULL : $data['year'];
		$return['vehicle_type'] = empty($data['type']) ? NULL : $data['type'];
		$return['vehicle_model'] = empty($data['model']) ? NULL : $data['model'];
		$return['vehicle_style'] = empty($data['style']) ? NULL : $data['style'];
		$return['vehicle_series'] = empty($data['series']) ? NULL : $data['series'];
		$return['vehicle_mileage'] = empty($data['mileage']) ? NULL : $data['mileage'];
		$return['vehicle_license_plate'] = empty($data['license_plate']) ? NULL : $data['license_plate'];
		$return['vehicle_color'] = empty($data['color']) ? NULL : $data['color'];
		$return['vehicle_value'] = empty($data['value']) ? NULL : $data['value'];
		$return['vehicle_title_state'] = empty($data['title_state']) ? NULL : $data['title_state'];
		return $return;	
	}
}
