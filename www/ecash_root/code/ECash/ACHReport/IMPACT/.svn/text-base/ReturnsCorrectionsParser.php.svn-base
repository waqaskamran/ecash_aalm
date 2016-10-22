<?php
/*
* Parses a given file into a standard data object ECash_ACHReport_ReportData
* 
*
*/
class ECash_ACHReport_IMPACT_ReturnsCorrectionsParser extends ECash_ACHReport_Parser implements ECash_ACHReport_IParser
{
	
	/**
	 * Impact's Return File Format
	 *
	 * Impact uses the same file format for both returns and corrections.
	 *
	 * Items with an asterisk (*) next to them are required
	 * for the returns handling to work.
	 */
	protected $return_file_format = array(
		'recipient_id', 			// Our ACH ID (*)
		'effective_entry_date', 	// Submission Date
		'AccountId',				// Application ID
		'LenderTag',				// The Lender Tag / Application Tag
		'ConsumerFirstName',		// Customer's First Name
		'ConsumerLastName',			// Customer's Last Name
		'ABA', 						// Customer's ABA
		'AccountNumber', 			// Customer's Account #
		'AmountInCents',			// Total amount of the return in cents
		'PrincipalInCents',			// Total principal returned in cents
		'FeeInCents',				// Total fee/service charge returned in cents
		'ReturnDate',				// Date the transaction was returned by the bank
		'reason_code', 				// Return Code (*)
		'corrected_info' 			// Addenda Info (*)
	);
	
	
	
	/**
	 * Used by the parser to determine how many rows to skip before
	 * the actual report data starts.
	 */
	const REPORT_OFFSET      = 0;


	public function parseFile($file_contents)
	{
		try
		{
			/**
			 * If the parser fails, we can't process the report, so return FALSE
			 */
			if(! $ach_report_data = ECash_CSVParser::parse($file_contents, $this->return_file_format, self::REPORT_OFFSET))
			{
				$this->log->Write("Parsing of file failed!");
				return FALSE;
			}

			$records = array();
			$this->log->Write("ACH: Found " . count($ach_report_data) . " records.");
			foreach ($ach_report_data as &$report_data)
			{
				/**
				 * Append these new fields to track the status of the processing
				 * for this particular record.  This is used by the manual processing.
				 *
				 * 'process_status' will always contain either 'Updated', 'Corrected', or 'Exception'.
				 * If 'process_status' is 'Exception', then 'process_status_details' will contain
				 * the details of the failure.
				 */
				
				$record = array();
				//Let's go through each record and return a standardized set of data that the processor can use for updating the records.
				$report_data['processing_status'] = '';
				$report_data['processing_status_details'] = '';
				$app_update_ary = array();
				$comment_text = '';

				if(!is_array($report_data))
					continue;



				$ach_id	= ltrim($report_data['recipient_id'], '0');
				$record['ach_id'] = $ach_id;
				if(! isset($report_data['recipient_id']) || empty($report_data['recipient_id']))
				{
					$this->log->Write("Unrecognized Report Entry: " . var_export($report_data,true));

					$report_data['processing_status'] = 'Exception';
					$report_data['processing_status_details'] = 'No recipient_id in return record.';

					$exception = new ECash_ACHReport_ACHException();
					$exception->ach_id = $ach_id;
					$exception->details = "Unrecognized Report Entry: " . var_export($report_data,true);
					$this->exceptions_report->addException($exception);

					continue;
				}
				
				if (is_numeric($ach_id))
				{
					$reason_code = trim($report_data['reason_code']);
					$record['reason_code'] = $reason_code;
					$this->log->Write("Process_ACH_Report: ach_id: $ach_id");
					// Process corrections -- update related application data, if possible
					$data_ary = explode(";", $report_data['corrected_info']);
					$corrected_data_ary = preg_split("/\s+/", trim($data_ary[1]));

					$do_update = false;

					switch($reason_code)
					{
					case 'C01':
						// Incorrect account number
						if ( $this->Validate_COR_Account($corrected_data_ary[0], $normalized_account) )
						{
							$app_update_ary = array (
										 'bank_account'	=> $normalized_account
										 );
							$comment_text = "Acct# auto correction: Set to $normalized_account";
							$do_update = true;
						}
						break;

					case 'C02':
						// Incorrect routing number
						if ( $this->Validate_COR_ABA($corrected_data_ary[0], $normalized_ABA) )
						{
							$app_update_ary = array (
										 'bank_aba'		=> $normalized_ABA
										 );
							$comment_text = "ABA# auto correction: Set to $normalized_ABA";
							$do_update = true;
						}
						break;

					case 'C03':
						// Incorrect routing number AND account number
						if ( $this->Validate_COR_ABA($corrected_data_ary[0], $normalized_ABA)			&&
						     $this->Validate_COR_Account($corrected_data_ary[1], $normalized_account) 		)
						{
							$app_update_ary = array (
										 'bank_aba'		=> $normalized_ABA,
										 'bank_account'	=> $normalized_account
										 );
							$comment_text = "ABA/Acct# auto correction: Set to $normalized_ABA / $normalized_account";
							$do_update = true;
						}
						break;

					case 'C04':
						// Incorrect individual name
						if ( $this->Validate_Name($corrected_data_ary[0], $normalized_name_last, $normalized_name_first) )
						{
							$app_update_ary = array (
										 'name_last'		=> $normalized_name_last,
										 'name_first'	=> $normalized_name_first
										 );
							$comment_text = "Applicant Name auto correction: Set to $normalized_name_last, $normalized_name_first";
							$do_update = true;
						}
						break;

					case 'C05':
						// Incorrect transaction code
						if ( $this->Validate_Tran_Code($corrected_data_ary[0], $bank_account_type) )
						{
							$app_update_ary = array (
										 'bank_account_type'	=> $bank_account_type
										 );
							$comment_text = "Acct Type auto correction: Set to $bank_account_type";
							$do_update = true;
						}
						break;

					case 'C06':
						// Incorrect account number AND transaction code
						if ( $this->Validate_COR_Account($corrected_data_ary[0], $normalized_account)	&&
						     $this->Validate_Tran_Code($corrected_data_ary[1], $bank_account_type)			)
						{
							$app_update_ary = array (
										 'bank_account'		=> $normalized_account,
										 'bank_account_type'	=> $bank_account_type
										 );
							$comment_text = "Acct#/Type auto correction: Set to $normalized_account / $bank_account_type";
							$do_update = true;
						}
						break;

					case 'C07':
						// Incorrect routing number, account number, AND transaction code
						if ( $this->Validate_COR_ABA($corrected_data_ary[0], $normalized_ABA)			&&
						     $this->Validate_COR_Account($corrected_data_ary[1], $normalized_account)	&&
						     $this->Validate_Tran_Code($corrected_data_ary[2], $bank_account_type)			)
						{
							$app_update_ary = array (
										 'bank_aba'				=> $normalized_ABA,
										 'bank_account'			=> $normalized_account,
										 'bank_account_type'	=> $bank_account_type
										 );
							$comment_text = "ABA/Acct#/Type auto correction: Set to $normalized_ABA / $normalized_account / $bank_account_type";
							$do_update = true;
						}
						break;
					}
					
					$record['app_updates'] = $app_update_ary;
					$record['comments'] = $comment_text;
					$records[] = $record;
				}
				else
				{
					$this->log->Write("Unrecognized Report Entry: " . var_export($report_data,true));

					$report_data['processing_status'] = 'Exception';
					$report_data['processing_status_details'] = 'recipient_id is not a number.';

					$exception = new ECash_ACHReport_ACHException();
					$exception->ach_id = $ach_id;
					$exception->reason_code = $report_data['reason_code'];
					$exception->details = "Unrecognized Report Entry: " . var_export($report_data,true);
					$this->exceptions_report->addException($exception);
				}

			}
		}
		catch(Exception $e)
		{
			$this->log->Write("ACH: Processing of $report_type failed and transaction will be rolled back.", LOG_ERR);
			$this->log->Write("ACH: No data recovery should be necessary after the cause of this problem has been determined.", LOG_INFO);
			throw $e;
		}


		return $records;
	}
	
	
	
	
	/**
	 * Validates the corrected ABA number is the correct string length
	 * and is a numeric value
	 *
	 * @param string $value
	 * @param string $normalized_value
	 * @return BOOL
	 */
	protected function Validate_COR_ABA ($value, &$normalized_value)
	{
		if ( is_numeric($value)			&&
		     strlen($value) == 9		)
		{
			$normalized_value = $value;
			return true;
		}

		return false;
	}

	/**
	 * Validates the corrected Account number is the correct string
	 * length and is a numeric value
	 *
	 * @param string $value
	 * @param string $normalized_value
	 * @return BOOL
	 */
	protected function Validate_COR_Account ($value, &$normalized_value)
	{
		if ( is_numeric($value)			&&
		     strlen($value) > 3		&&
		     strlen($value) < 18		)
		{
			$normalized_value = $value;
			return true;
		}
		return false;
	}

	/**
	 * Validates the banking transaction code and sets the normalized
	 * value to 'checking' or 'savings' since that is what is stored
	 * in the eCash application table
	 *
	 * @param string $value
	 * @param string $normalized_value
	 * @return BOOL
	 */
	protected function Validate_Tran_Code ($value, &$normalized_value)
	{
		if ( is_numeric($value)			&&
		     $value >= 22	&&
		     $value <= 39 		)
		{
			if ($value <= 29)
			{
				$bank_account_type = 'checking';
			}
			else
			{
				$bank_account_type = 'savings' ;
			}
			$normalized_value = $bank_account_type;
			return true;
		}

		return false;
	}

	/**
	 * Validates the the customer name is a string and within
	 * the appropriate lengths and returns the normalized
	 * first and last names.
	 *
	 * @param string $value
	 * @param string $normalized_name_last
	 * @param string $normalized_name_first
	 * @return BOOL
	 */
	protected function Validate_Name ($value, &$normalized_name_last, &$normalized_name_first)
	{
		$name_ary = explode(" ", $value);
		$name_first	= strtolower(trim($name_ary[0]));
		$name_last	= strtolower(trim($name_ary[1]));
		if ( strlen($name_last ) >  1	&&
		     strlen($name_last ) < 50	&&
		     strlen($name_first) >  0	&&
		     strlen($name_first) < 50		)
		{
			$normalized_name_last	= $name_last;
			$normalized_name_first	= $name_first;
			return true;
		}

		return false;
	}



}


?>
