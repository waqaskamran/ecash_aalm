<?php

/**
 * Perf data request for DataX
 */
class ECash_DataX_Requests_Perf extends TSS_DataX_Request
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
				'NAMEFIRST' => 'name_first',
				'NAMELAST'  => 'name_last',
				'NAMEMIDDLE'=> 'name_middle',
				'STREET1'   => 'street',
				'CITY'      => 'city',
				'STATE'     => 'state',
				'ZIP'       => 'zip',
				'PHONEHOME' => 'phone_home',
				'PHONECELL' => 'phone_cell',
				'PHONEWORK' => 'phone_work',
				'EMAIL'     => 'customer_email',
				'DOBYEAR'   => 'dob_year',
				'DOBMONTH'  => 'dob_month',
				'DOBDAY'    => 'dob_day',
				'SSN'       => 'ssn',
				'STREET2'   => 'unit',
				'BANKNAME'  => 'bank_name',
				'BANKACCTNUMBER' => 'bank_account',
				'BANKABA'   => 'bank_aba',
				'PAYPERIOD' => 'payperiod',
				'IPADDRESS' => 'ip_address',
				'WORKNAME'  => 'employer_name',
				'SOURCE'    => 'origin_url',
				'PROMOID'   => 'promo_id',
				'INCOMETYPE' => 'income_source',
				'MONTHLYINCOME' => 'income_monthly',
				'LEADCOST'  => 'lead_cost',
				'DIRECTDEPOSIT' => 'income_direct_deposit',
				'AMOUNTREQUESTED' => 'fund_amount',
				'DRIVERLICENSENUMBER' => 'legal_id_number',
				'DRIVERLICENSESTATE'  => 'legal_id_state',
				'APPLICATIONTYPE' => 'loan_type_model'
				);
		parent::__construct($license, $password, $call_name);
	}
}
