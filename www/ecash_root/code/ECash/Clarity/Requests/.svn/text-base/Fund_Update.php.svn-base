<?php

/**
 * Fund Update data request for DataX
 */
class ECash_Clarity_Requests_Fund_Update extends Clarity_UW_Request
{
	/**
	 * Construct a basic datax request
	 *
	 * @param string $license
	 * @param string $password
	 * @param string $call_name
	 */
	public function __construct($license, $password, $call_name)
	{
		self::$data_map = array(
				'FUNDAMOUNT'=> 'fund_amount',
				'FUNDFEE'   => 'fund_fee',
				'FUNDDATE'  => 'fund_date',
				'DUEDATE'   => 'due_date',
				'NAMEFIRST' => 'name_first',
				'NAMELAST'  => 'name_last',
				'NAMEMIDDLE'=> 'name_middle',
				'STREET1'   => 'street',
				'CITY'      => 'city',
				'STATE'     => 'state',
				'ZIP'       => 'zip_code',
				'PHONEHOME' => 'phone_home',
				'PHONECELL' => 'phone_cell',
				'PHONEWORK' => 'phone_work',
				'EMAIL'     => 'email',
				'DOBYEAR'   => 'dob_y',
				'DOBMONTH'  => 'dob_m',
				'DOBDAY'    => 'dob_d',
				'SSN'       => 'ssn',
				'STREET2'   => 'unit',
				'BANKNAME'  => 'bank_name',
				'BANKACCTNUMBER' => 'bank_account',
				'BANKABA'   => 'bank_aba',
				'PAYPERIOD' => 'income_frequency',
				'IPADDRESS' => 'ip_address',
				'WORKNAME'  => 'employer_name',
				'SOURCE'    => 'client_url_root',
				'PROMOID'   => 'promo_id',
				'INCOMETYPE' => 'income_source',
				'MONTHLYINCOME' => 'income_monthly',
				'LEADCOST'  => 'lead_cost',
				'DIRECTDEPOSIT' => 'income_direct_deposit',
				'AMOUNTREQUESTED' => 'qualified_loan_amount',
				'DRIVERLICENSENUMBER' => 'legal_id_number',
				'DRIVERLICENSESTATE'  => 'legal_id_state',
				'APPLICATIONTYPE' => 'application_type'
				);
		parent::__construct($license, $password, $call_name);
	}
}
