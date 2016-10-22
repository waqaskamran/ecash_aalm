<?php

/**
 * Processor specific ACH_Batch class extension
 */
class ACH_Batch_Viking extends ACH_Batch
{
	protected static $achstruct =  array(
					      0 =>
					      array(	
					      		'file_type'		=> array(1, 3, 'A', 'CSV'), 	// 1
							'version'               => array(2, 3, 'A', '001'), 	// 2
							'customer_name'         => array(3, 100, 'A'),		// 3
							'lender_unique_id'     	=> array(4, 15, 'A'), 		// 4 ach_id ?
							'routing_number'        => array(7, 9, 'A'),		// 7
							'account_number'        => array(8, 17, 'A'),		// 8
							'amount'                => array(9, 11, 'A', '0'),	// 9
							'ach_transaction_code'  => array(10, 2, 'A'),     	// 10
							'company_name'          => array(11, 3, 'A', 'CLH'),	// 11
							'entry_class_code'      => array(14, 3, 'A', 'PPD'),    // 14
							'date_event'        	=> array(15, 6, 'A'),		// 15
							'date_effective'        => array(16, 6, 'A'),		// 16
							'customer_address'     	=> array(17, 50, 'A'),		// 17
							'customer_city'         => array(18, 50, 'A'),		// 18
							'customer_state'        => array(19, 2, 'A'),		// 19
							'customer_zip'          => array(20, 5, 'A'),		// 20
							'customer_ssn'          => array(21, 9, 'A'),		// 21
							'total_debit_rows'      => array(22, 6, 'A', '0'),      // 22
							'total_credit_rows'     => array(23, 6, 'A', '0'),      // 23
							'total_rows'      	=> array(24, 6, 'A', '0'),      // 24
							'total_debit'           => array(25, 12, 'A', '0'),     // 25
							'total_credit'          => array(26, 12, 'A', '0'),     // 26
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
		$total_amount = 0;
		$total_credit_count = 0;
		$total_credit_amount = 0;
		$total_debit_count = 0;
		$total_debit_amount = 0;

		foreach ($ach_transaction_ary AS $record)
		{
			if($record['ach_type'] === 'credit')
			{
				$total_credit_count++;
				$total_credit_amount += $record['amount'];
			}
			else
			{
				$total_debit_count++;
				$total_debit_amount += $record['amount'];
			}			
		}
		
		$total_rows = $total_credit_count + $total_debit_count;
		$total_amount = $total_credit_amount + $total_debit_amount;

		foreach ($ach_transaction_ary AS $record)
		{
			$output['transaction_type']		= 'CSV'; 									// 1
			$output['version']                     	= '001';									// 2
			$output['customer_name']               	= strtoupper($record['name_first'] . " " . $record['name_last']);		// 3
			$output['lender_unique_id']             = $record['ach_id'];								// 4
			$output['routing_number']              	= substr($record['bank_aba'], 0, 9);						// 7
			$output['account_number']              	= substr($record['bank_account'], 0, 17);					// 8
			$output['amount']                      	= number_format($record['amount'], 2, ".", "");					// 9
			$output['ach_transaction_code']         = $record['viking_tran_code'];							// 10
			$output['company_name']            	= 'CLH';									// 11
			$output['entry_class_code']         	= 'PPD';									// 14
			$output['date_event']               	= $record['date_event'];							// 15
			$output['date_effective']            	= $record['date_effective'];							// 16
			if (strlen($record['address_2']) > 0)											// 17
				$output['customer_address']           	= strtoupper($record['address_1'] . " " . $record['address_2']);
			else
				$output['customer_address']             = strtoupper($record['address_1']);
			$output['customer_city']               	= strtoupper($record['city']);							// 18
			$output['customer_state']               = strtoupper($record['state']);							// 19
			$output['customer_zip']                 = $record['zip_code'];								// 20
			$output['customer_ssn']                 = $record['ssn'];								// 21
			$output['total_debit_rows']             = $total_debit_count;                                                  		// 22
			$output['total_credit_rows']            = $total_credit_count;                                                 		// 23
			$output['total_rows']            	= $total_rows;                                                 			// 24
			$output['total_debit']                  = number_format($total_debit_amount, 2, ".", "");                            	// 25
			$output['total_credit']                 = number_format($total_credit_amount, 2, ".", "");                           	// 26

			$this->file .= $this->Build_Record(0, $output);
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

		$record .= "\"";
		$record .= implode("\",\"", $record_values);
		$record .= "\"";
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
		if ($batch_id !== NULL)
		{
			$batch_model = ECash::getFactory()->getModel('AchBatch');
			$batch_model->loadBy(array('ach_batch_id' => $batch_id,));
			$ach_provider_id = $batch_model->ach_provider_id;
			$replace = array(" ",":","-");
			$batch_date_created = str_replace($replace, '', $batch_model->date_created);
			$batch_date_created = substr($batch_date_created, 0, 8);

			$batch_type = ucfirst($batch_model->batch_type);

			$pr_model = ECash::getFactory()->getModel('AchProvider');
			$pr_model->loadBy(array('ach_provider_id' => $ach_provider_id,));

			$ach_provider_name = $pr_model->name_short;
		}
		else
		{
			$ach_provider_name = "";
		}

		$file_type = ($batch_type == "Credit" ? "4" : "3");

		$filename = "FFF" . "_" . "LL" . $file_type . "_" . $batch_type . "_" . $batch_date_created . "_" . $ach_provider_name . ".csv";
		
		return $filename;
	}

}
?>
