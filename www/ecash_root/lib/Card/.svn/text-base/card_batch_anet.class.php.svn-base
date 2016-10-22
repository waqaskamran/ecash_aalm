<?php

/**
 * Processor specific Card_Batch class extension
 * 
 *
 */
class Card_Batch_Anet extends Card_Batch
{
	protected static $cardstruct =  array(
					      0 =>
					      array(	
					      	'transaction_type'			=> array( 1,  2, 'A', '10'),
							'account_type'			=> array( 2,  1, 'A', 'C'),
							'entry_type'			=> array( 4, 6, 'A', 'debit'),
							'check_number'			=> array(14, 20, 'A'),
							'routing_number'		=> array(14, 9, 'A'),
							'account_number'		=> array(24,  17, 'A'),
							'amount'			=> array(30,  18, 'A', '0'),
							'transaction_date'		=> array(30,  8, 'A'),
							'customer_name'			=> array(34,  100, 'A'),
							'customer_address1'		=> array(35,  50, 'A'),
							'customer_address2'		=> array(38,  50, 'A'),
							'customer_city'			=> array(40,  50, 'A'),
							'customer_state'		=> array(41, 2, 'A'),
							'customer_zip'			=> array(64, 5, 'A'),
							'customer_phone'		=> array(87,  10, 'A'),
							'license_state'			=> array(64, 2, 'A'),
							'license_number'		=> array(64, 35, 'A'),
							'customer_ssn'			=> array(64, 9, 'A'),
							'merchant_id'			=> array(64, 20, 'A'),
							'merchant_trans_id'		=> array(64, 100, 'A'),
							'validation_type_code'		=> array(64, 5, 'A')
							)
					      );

	protected $log;
	protected $company_abbrev;
	protected $company_id;

	/**
	 * Used to determine whether or not the Card Processor will contain
	 * both the credits and debits in one file or to send two separate
	 * files.
	 */
	protected $COMBINED_BATCH = FALSE;

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}


}
?>
