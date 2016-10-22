<?php
/**
 * Status Update (CL Verify) request for Clarity
 */
class ECash_Clarity_Requests_Status_Update_CL_Verify extends Clarity_UW_Request
{
	/**
	 * Construct a basic Clarity request
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
			'STREET1'   => 'street',
			'CITY'      => 'city',
			'STATE'     => 'state',
			'ZIP'       => 'zip_code',
			'PHONEHOME' => 'phone_home',
			'PHONEWORK' => 'phone_work',
			'EMAIL'     => 'email',
			'DOBYEAR'   => 'dob_y',
			'DOBMONTH'  => 'dob_m',
			'DOBDAY'    => 'dob_d',
			'SSN'       => 'ssn',
			'BANKNAME'  => 'bank_name',
			'BANKACCTNUMBER' => 'bank_account',
			'BANKABA'   => 'bank_aba',
			'PAYPERIOD' => 'income_frequency',
			'IPADDRESS' => 'ip_address',
			'WORKNAME'  => 'employer_name',
			'SOURCE'    => 'client_url_root',
			'PROMOID'   => 'promo_id',
			'DRIVERLICENSENUMBER' => 'legal_id_number',
			'DRIVERLICENSESTATE'  => 'legal_id_state',
			'STATUS'=> 'status'
				);

		parent::__construct($license, $password, $call_name);
	}
}
