<?php

/**
 * Processor specific ACH_Batch class extension
 * 
 * This is a copy of the ACH_Batch_Teledraft class with a few customizations for Regal.
 * - Transaction Type is set to 10 (Individual) instead of 20 (Business).
 * - Entry Type is 'TEL' for debits, 'credit' for credits.
 *
 */
class ACH_Batch_Regal extends ACH_Batch
{
	protected static $achstruct =  array(
					      0 =>
					      array(	
					      	'transaction_type'			=> array( 1,  2, 'A', '10'),
							'account_type'				=> array( 2,  1, 'A', 'C'),
							'entry_type'				=> array( 4, 6, 'A', 'debit'),
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
		/**
		 * Used for the batch totals
		 */
		$total_amount			= 0;
		$total_credit_count     = 0;
		$total_credit_amount	= 0;
		$total_debit_count		= 0;
		$total_debit_amount		= 0;

		foreach ($ach_transaction_ary AS $record)
		{
			$output['transaction_type']				= 10; // 10 - Individual, 20 - Business
			$output['account_type']					= ($record['bank_account_type'] == 'checking') ? 'C' : 'S'; 
			$output['entry_type']					= ($record['ach_type'] == 'debit') ? 'debit' : 'credit';
			$output['routing_number']				= substr($record['bank_aba'], 0, 9);
			$output['account_number']				= substr($record['bank_account'], 0, 17);
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
			$output['validation_type_code']			= ($record['ach_type'] == 'debit') ? 'WEB' : 'PPD';

			$this->file .= $this->Build_Record(0, $output);

			/**
			 * For our batch totals...
			 */
			if($record['ach_type'] === 'credit')
			{
				$total_credit_count++;
				$total_credit_amount += $output['amount'];
			}
			else
			{
				$total_debit_count++;
				$total_debit_amount += $output['amount'];
			}			

			$total_amount	+= $output['amount'];

		}

		$this->ach_utils->Set_Total_Amount($total_amount);
		$this->credit_count  = $total_credit_count;
		$this->credit_amount = $total_credit_amount;
		$this->debit_count   = $total_debit_count;
		$this->debit_amount  = $total_debit_amount;

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

	public function Get_Remote_Filename($batch_id = NULL) //asm 80
	{
		$transport_type   = ECash::getConfig()->ACH_TRANSPORT_TYPE;
		$transport_url    = ECash::getConfig()->ACH_BATCH_URL;
		$client_id = ECash::getConfig()->CLIENT_ID;

		if ($batch_id !== NULL)
		{
			$batch_model = ECash::getFactory()->getModel('AchBatch');
			$batch_model->loadBy(array('ach_batch_id' => $batch_id,));
			$ach_provider_id = $batch_model->ach_provider_id;
			$replace = array(" ",":");
			$batch_date_created = str_replace($replace, '-', $batch_model->date_created);

			$pr_model = ECash::getFactory()->getModel('AchProvider');
			$pr_model->loadBy(array('ach_provider_id' => $ach_provider_id,));

			$ach_provider_name = $pr_model->name_short;
		}
		else
		{
			$ach_provider_name = "";
		}
		
		// If we're using SFTP, we need to specify the whole path including a filename
		if(in_array($transport_type, array('SFTP', 'SFTP_AGEAN', 'FTP', 'FTPS'))) {
			// This needs to be modified based on the company
			//$filename = "$transport_url/{$client_id}_{$this->batch_type}_" . date('Ymd') . "_" . $ach_provider_name . ".csv";
			$filename = "$transport_url/{$client_id}_{$this->batch_type}_" . $batch_date_created . "_" . $ach_provider_name . ".csv";
		}
		else
		{
			$filename = $transport_url;
		}
		
		return $filename;
	}
	
	public function Get_Filename_Download($batch_id = NULL) //asm 80
	{
		$filename = $this->Get_Remote_Filename($batch_id);
		
		return $filename;
	}

}
?>
