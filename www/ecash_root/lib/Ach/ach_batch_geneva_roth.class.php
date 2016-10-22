<?php

/**
 * Processor specific ACH_Batch class extension
 *
 * U.S. Bank
 * 
 * 
 */
class ACH_Batch_Geneva_Roth extends ACH_Batch
{
	// attempt at documentation [benb]
	// the $achstruct[$rectype][$fieldname] must be an array
	// of at least 3 elements
	// The first element must be an integer (minimum length)
	// The first element must be greater than 1 character
	// and the first element must be less than 94 characters (why?)
	// the second element must be an integer (maximum length)
	// the first element + the second element - 1 must be less than 94 (min size + max size - 1 < 94)
	
	// Attributes:
	// Minimum Length
	// Maximum Length
	// (A = ASCII | N = Number)
	// Default Value
	protected static $achstruct =  array(
					      0 =>
					      array(	
							'customer_name'				=> array( 1, 20, 'A'),       // Customer Name
							'transaction_id'            => array(10, 10, 'N'),       // Transaction ID (ACH ID)
							'routing_number'            => array(14,  9, 'A'),       // Routing Number
							'account_number'            => array(14, 20, 'A'),       // Account #
							'amount'                    => array(30, 18, 'A', '0'),  // Amount
							'transaction_code'          => array( 2,  2, 'N', '27')  // Transaction Code (22 - Credit, 27 - Debit)
							)
					      );
					      
	
	protected $log;
	protected $company_abbrev;
	protected $company_id;

	/**
	 * Used to determine whether or not the ACH batch file will contain
	 * both the credits and debits in one file or to send two separate
	 * files.
	 */
	protected $COMBINED_BATCH = TRUE;

					  
	public function __construct(Server $server)
	{
		parent::__construct($server);
	}
	
	/**
	 * Build the ACH file from an array of transactions. This is in NACHA format.
	 *
	 * @param Array $ach_transaction_ary
	 */
	protected function Build_ACH_File($ach_transaction_ary)
	{
		// Entry Detail Records (customer)
		$trace_seqno	= 0;
		$total_amount	= 0;
		$total_hash		= 0;
		$entry_addenda_count	= 0;

		foreach ($ach_transaction_ary AS $record)
		{
			// substr($this->ach_company_name, 0, 23);
			$output['customer_name']         = substr($record['name_first'] . " " . $record['name_last'], 0, 20);
			$output['transaction_id']        = $record['ach_id'];
			$output['routing_number']        = substr($record['bank_aba'], 0, 9);
			$output['account_number']        = $record['bank_account'];
			$output['amount']                = $record['amount'];
			$output['transaction_code']      = ($record['ach_type'] == 'debit') ? 27 : 22; 
			
			$trace_seqno++;
			
			$this->file .= $this->Build_Record(0, $output);
			//$this->customer_trace_numbers[$output['individual_identification_no']] = $this->Capture_Trace_Number($output);

			$total_amount	+= $output['amount'];
			$entry_addenda_count++;
		}
		$this->ach_utils->Set_Total_Amount($total_amount/100);
	}
	
	protected function Build_Record ($rectype, $value_ary)
	{
		$record = "";
		$record_values = array();
		foreach (self::$achstruct[$rectype] as $fieldname => $attributes)
		{
			
			if (isset($value_ary[$fieldname]))
			{
				$record_values[] = $this->Set_Field_Content($rectype, $fieldname, $value_ary[$fieldname]);
			}
			else
			{
				$record_values[] = $this->Set_Field_Content($rectype, $fieldname);
			}
		}
		
		$record = implode(",", $record_values);
		$record .= $this->RS;
		
		$this->rowcount++;
		
		return $record;
	}
	
	private function Set_Field_Content ($rectype, $fieldname, $value=NULL)
	{
		$result = "";
		
		if (!isset(self::$achstruct[$rectype]))
		{
			throw new General_Exception("ACH internal failure -- record type '$rectype' undefined.");
		}
		
		if (!isset(self::$achstruct[$rectype][$fieldname]))
		{
			throw new General_Exception("ACH internal failure -- field '$fieldname' undefined for record type '$rectype'.");
		}
				
		if ( isset($value) && strlen($value) > self::$achstruct[$rectype][$fieldname][1] )
		{
			throw new General_Exception("ACH internal failure -- value '$value' is too long to fit in field '$fieldname' of record type '$rectype'.");
		}
		
		if ( (!isset($value) || strlen($value) == 0) && isset(self::$achstruct[$rectype][$fieldname][3]) )
		{
			$value = self::$achstruct[$rectype][$fieldname][3];
		}
		
//		if (self::$achstruct[$rectype][$fieldname][2] == 'A')
//		{
//			$result = str_pad($value, self::$achstruct[$rectype][$fieldname][1], ' ', STR_PAD_RIGHT);
//		}
//		elseif (self::$achstruct[$rectype][$fieldname][2] == 'N')
//		{
//			$result = str_pad($value, self::$achstruct[$rectype][$fieldname][1], '0', STR_PAD_LEFT );
//		}
		
		return str_replace(',', '', $value);
	}
	
	private function Capture_Trace_Number ($value_ary)
	{
		$trace_number = "";
		
		foreach (self::$achstruct[0] as $fieldname => $attributes)
		{
			if ( in_array($fieldname, array('trace_number_prefix','trace_number_suffix')) )
			{
				if (isset($value_ary[$fieldname]))
				{
					$trace_number .= $this->Set_Field_Content(0, $fieldname, $value_ary[$fieldname]);
				}
				else
				{
					$trace_number .= $this->Set_Field_Content(0, $fieldname);
				}
			}
		}
				
		return $trace_number;
	}
	
	public function Get_Remote_Filename()
	{
		$transport_type   = ECash::getConfig()->ACH_TRANSPORT_TYPE;
		$transport_url    = ECash::getConfig()->ACH_BATCH_URL;
		$client_id = ECash::getConfig()->CLIENT_ID;
		
		if (empty($transport_url))
			throw new Exception('No transport url (ACH_BATCH_URL) in config files');

		$filename = $transport_url;

		return $filename;
	}

}
?>
