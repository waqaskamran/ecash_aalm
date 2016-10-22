<?php
/**
 * Status Update request for DataX
 */
class ECash_DataX_Requests_Status_Update extends TSS_DataX_Request
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
		parent::__construct($license, $password, $call_name);
		
		self::$data_map = array(
				'SSN' => 'ssn',
				'TRANSACTIONCODE'  => 'transaction_code',
				'STATUS'=> 'status'
				);
	}
}
