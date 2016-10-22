<?php
/**
 * Status Update request for Clarity
 */
class ECash_Clarity_Requests_Status_Update extends Clarity_UW_Request
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
		parent::__construct($license, $password, $call_name);
		
		self::$data_map = array(
				'SSN' => 'ssn',
				'TRANSACTIONCODE'  => 'transaction_code',
				'STATUS'=> 'status'
				);
	}
}
