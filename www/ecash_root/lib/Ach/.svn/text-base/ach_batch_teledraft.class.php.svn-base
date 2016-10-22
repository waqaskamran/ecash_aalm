<?php

/**
 * Processor specific ACH_Batch class extension
 * 
 * 	Allows for processor specific adjustments to batch format.
 *
 */
class ACH_Batch_Teledraft extends ACH_Batch
{
	protected static $achstruct =  array(
					      0 =>
					      array(	
					      	'transaction_type'			=> array( 1,  2, 'A', '20'),
							'account_type'				=> array( 2,  1, 'A', 'C'),
							'entry_type'				=> array( 4, 1, 'A', 'D'),
							'check_number'				=> array(14, 20, 'A'),
							'routing_number'			=> array(14, 9, 'A'),
							'account_number'			=> array(24,  17, 'A'),
							'amount'					=> array(30,  18, 'A', '0'),
							'transaction_date'			=> array(30,  8, 'A'),
							'customer_name'				=> array(34,  100, 'A'),
							'customer_address1'			=> array(35,  50, 'A'),
							'customer_address2'			=> array(38,  50, 'A'),
							'customer_city'				=> array(40,  50, 'A'),
							'customer_state'			=> array(41, 2, 'A'),
							'customer_zip'				=> array(64, 5, 'A'),
							'customer_phone'			=> array(87,  10, 'A'),
							'license_state'				=> array(64, 2, 'A'),
							'license_number'			=> array(64, 35, 'A'),
							'customer_ssn'				=> array(64, 9, 'A'),
							'merchant_id'				=> array(64, 20, 'A'),
							'merchant_trans_id'			=> array(64, 100, 'A'),
							'validation_type_code'		=> array(64, 5, 'A')
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
			
			$output['transaction_type']				= 20; // 10 - Individual, 20 - Business
			$output['account_type']					= ($record['bank_account_type'] == 'checking') ? 'C' : 'S'; 
			$output['entry_type']					= ($record['ach_type'] == 'debit') ? 'D' : 'C';
			$output['routing_number']				= substr($record['bank_aba'], 0, 9);
			$output['account_number']				= $record['bank_account'];
			$output['amount']						= $record['amount'];
			$output['transaction_date']				= date("Ymd");
			$output['customer_name']				= $record['name_first'] . " " . $record['name_last'];
			$output['customer_address1']			= $record['address_1'];
			$output['customer_address2']			= $record['address_2'];
			$output['customer_city']				= $record['city'];
			$output['customer_state']				= $record['state'];
			$output['customer_zip']					= $record['zip_code'];
			$output['customer_phone']				= $record['phone_home'];
			$output['merchant_id']					= isset(ECash::getConfig()->MERCHANT_ID) ? ECash::getConfig()->MERCHANT_ID : '';
			$output['merchant_trans_id']			= $record['ach_id'];
			$output['validation_type_code']			= "NONE";
			
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
	
	protected function Set_Field_Content ($rectype, $fieldname, $value=NULL)
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
	
	protected function Capture_Trace_Number ($value_ary)
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
		
		// If we're using SFTP, we need to specify the whole path including a filename
			if(in_array($transport_type, array('SFTP', 'SFTP_AGEAN', 'FTP', 'FTPS'))) {
				// This needs to be modified based on the company
				$filename = "$transport_url/{$client_id}_{$this->batch_type}_" . date('Ymd') . ".csv"; 
			} else {
				$filename = $transport_url;
			}
			
			return $filename;
	}

}
?>