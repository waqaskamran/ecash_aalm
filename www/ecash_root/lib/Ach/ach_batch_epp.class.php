<?php

/**
 * Processor specific ACH_Batch class extension
 *
 * This is a copy of the ACH_Batch_Teledraft class with a few customizations for EPP.
 * - Transaction Type is set to 10 (Individual) instead of 20 (Business).
 * - Entry Type is 'TEL' for debits, 'credit' for credits.
 *
 */
class ACH_Batch_EPP extends ACH_Batch
{
	protected static $achstruct =  array(
					      0 =>
					      array(
							'customer_name'				=> array(34,  22, 'A'),
							'routing_number'			=> array(14,   9, 'A'),
							'account_number'			=> array(24,  17, 'A'),
							'amount'					=> array(30,   9, 'A', '0'),
							'service_fee'				=> array(30,   9, 'A', ''),
							'account_type'				=> array( 2,   1, 'A', 'C'),
							'check_number'				=> array(14,   4, 'A'),
							'reference' 				=> array(64,  15, 'A'),
							'transaction_type'			=> array( 1,   6, 'A', 'Debit'),
							'transaction_date'			=> array(30,  10, 'A'),
							'account_id'				=> array(14,   4, 'A'),
							'ach_id'				=> array(14,   15, 'A'),
							'app_id'				=> array(14,   15, 'A'),
							'addendum'				=> array(14,   80, 'A', ''),
							'check_status_id'				=> array(14,   1, 'A', '2'),
							'submit_count'				=> array(14,   1, 'A', '0'),
							'address1'				=> array(14,   30, 'A', ''),
							'address2'				=> array(14,   30, 'A', ''),
							'city'				=> array(14,   30, 'A', ''),
							'state'				=> array(14,   2, 'A', ''),
							'zip'				=> array(14,   10, 'A', ''),
							'homephone'				=> array(14,   12, 'A', ''),
							'workphone'				=> array(14,   12, 'A', ''),
							'transgid'				=> array(14,   50, 'A', ''),
							'image_storage_id'				=> array(14,   9, 'A', ''),
							'source_date'				=> array(14,   22, 'A', ''),	
							'submit_date'				=> array(14,  22, 'A', ''),						
							)
					      );

	protected $log;
	protected $company_abbrev;
	protected $company_id;

	/**
	 * These constants define the positions in the $achstruct array
	 * record types. Position 0 is currently unknown, but also unused.
	 */
	const BATCH_FIELD_SIZE    = 1;
	const BATCH_FIELD_TYPE    = 2;
	const BATCH_FIELD_DEFAULT = 3;

	/**
	 * Used to determine whether or not the ACH batch file will contain
	 * both the credits and debits in one file or to send two separate
	 * files.
	 */
	protected $COMBINED_BATCH = FALSE;

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Build the ACH file from an array of transactions. This is in a custom quoted CSV format.
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

			$output['customer_name']				= $this->truncate($record['name_last'] . " " . $record['name_first'],
																	  self::$achstruct[0]['customer_name']);
			$output['routing_number']				= $this->truncate($record['bank_aba'],
																	  self::$achstruct[0]['routing_number']);
			$output['account_number']				= $this->truncate($record['bank_account'],
																	  self::$achstruct[0]['account_number']);
			$output['amount']						= $record['amount'];

			// Service Charge would go in this position, but is not something we're using, therefore should use the default of 0.

			$output['account_type']					= ($record['bank_account_type'] == 'checking') ? 'C' : 'S';

			$output['check_number']					= $this->truncate($record['ach_id'],
																	  self::$achstruct[0]['check_number']);

			$output['account_id']					= $this->truncate('1676',
																	  self::$achstruct[0]['account_id']);

			$output['ach_id']					= $this->truncate($record['ach_id'],
																	  self::$achstruct[0]['ach_id']);

			$output['app_id']					= $this->truncate($record['application_id'],
																	  self::$achstruct[0]['app_id']);
			/**
			 * EPP only allows us to use a string length of 4 for the check number
			 * so we're going to embed the whole value in the reference, along with the Application ID.
			 * We need to limit it to 15 characters though, so the App ID may get cut off
			 */
			$output['reference']					= $this->truncate($record['ach_id'] . ' ' . $record['application_id'],
																	  self::$achstruct[0]['reference']);

			$output['transaction_type']				= ($record['ach_type'] == 'debit') ? 'Debit' : 'Credit';
			$output['submit_count']				= ($record['ach_type'] == 'debit') ? '2' : '1';

			/**
			 * For our batch totals...
			 */
			if($output['transaction_type'] === 'Credit')
			{
				$total_credit_count++;
				$total_credit_amount += $output['amount'];
			}
			else
			{
				$total_debit_count++;
				$total_debit_amount += $output['amount'];
			}

			$output['transaction_date']				= date("m/d/Y");

			$this->file .= $this->Build_Record(0, $output);

			$total_amount	+= $output['amount'];
		}

		$this->ach_utils->Set_Total_Amount($total_amount);
		$this->credit_count  = $total_credit_count;
		$this->credit_amount = $total_credit_amount;
		$this->debit_count   = $total_debit_count;
		$this->debit_amount  = $total_debit_amount;

	}

	/**
	 * A simple method to truncate a field value to it's maximum
	 * length according to the ACH Structure
	 *
	 * @param string $field - The field value
	 * @param array $field_def - The field definition in the ACH Structure
	 * @return string
	 */
	private function truncate($field = NULL, $field_def = NULL)
	{
		if(empty($field) || empty($field_def))
			return $field;

		$length = $field_def[self::BATCH_FIELD_SIZE];

		if(strlen($field) > $length)
		{
			$field = substr($field, 0, $length);
		}

		return $field;
	}

	/**
	 * Creates a CSV record using the ACH structure record type specified
	 */
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

		// Quoted CSV
		$record = '"' . implode('","', $record_values) . '"' . $this->RS;

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

		if ( isset($value) && strlen($value) > self::$achstruct[$rectype][$fieldname][self::BATCH_FIELD_SIZE] )
		{
			throw new General_Exception("ACH internal failure -- value '$value' is too long to fit in field '$fieldname' of record type '$rectype'.");
		}

		if ( (!isset($value) || strlen($value) == 0) && isset(self::$achstruct[$rectype][$fieldname][self::BATCH_FIELD_DEFAULT]) )
		{
			$value = self::$achstruct[$rectype][$fieldname][self::BATCH_FIELD_DEFAULT];
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

	/**
	 * @todo: Figure out what we need to use to generate a filename
	 *
	 * @return unknown
	 */
	public function Get_Remote_Filename()
	{
		$transport_type   = ECash::getConfig()->ACH_TRANSPORT_TYPE;
		$transport_url    = ECash::getConfig()->ACH_BATCH_URL;
		$client_id = ECash::getConfig()->CLIENT_ID;

		// If we're using SFTP, we need to specify the whole path including a filename
		if(in_array($transport_type, array('SFTP', 'SFTP_AGEAN', 'FTP', 'FTPS'))) {
			// This needs to be modified based on the company
			if($this->batch_type == 'credit')
			{
				$suffix = 'c';
			}
			else if($this->batch_type == 'debit')
			{
				$suffix = 'd';
			}
			else
			{
				$suffix = '';
			}
			$filename =  '22_1676_' . date('Ymd') . $suffix . ".txt";
		} else {
			$filename = $transport_url;
		}

		return $filename;
	}
	protected function Send_Batch ()
	{
			$bc  = $this->batch_count;       // Batch Count
			$cc  = 0;	// Credit Count
			$ca  = 0;	// Credit Amount
			$dc  = 0;	// Debit Count
			$da  = 0;	// Debit Amount
			$fs  = 0;	// File Size (bytes)
			$er  = 0;	// Error Code
			$ref = '';	// Reference Number (Intercept)
			$ac  = 0;	// Unknown
			$ic  = 0;	// Unknown

			if($this->batch_type === 'credit')
			{
				$cc = $this->batch_count;
				$ca = floatval($this->clk_total_amount);
			}
			else if ($this->batch_type === 'debit')
			{
				$dc = $this->batch_count;
				$da = floatval($this->clk_total_amount);
			}
			else
			{
				$cc = $this->credit_count;
				$ca = floatval($this->credit_amount);
				$dc = $this->debit_count;
				$da = floatval($this->debit_amount);
			}

			$batch_response = "BC=$bc&DC=$dc&CC=$cc&CA=$ca&DA=$da&AC=$ac&FS=%fs&IC=$ic&REF=$ref&ER=$er";
		$batch_status = 'created';
		$this->Update_ACH_Batch_Response($batch_response, $batch_status);
		// Set up return values
		$return_val = array();
		parse_str($batch_response, $return_val['intercept']);
		$return_val['status'] = $batch_status;

		return $return_val;
	}
}
?>
