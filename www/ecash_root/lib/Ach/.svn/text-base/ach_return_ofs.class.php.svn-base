<?php

/**
 * Class for handling ACH returns from Empire OFS
 *
 * This class is based on the ACH_Return_Teledraft class because there are some
 * customizations that AALM wanted that were integrated into the Teledraft returns
 * class.
 *
 * History:
 * 20080915 - Refactored this particular class quite a bit to make it easier to
 *            process on a file by file basis.  Process_ACH_Report() is now
 *            a wrapper for Process_ACH_Report_Data() which does the actual
 *            processing of the report.  This makes it easier to pass any report
 *            we want and process it regardless of where we got the data or what
 *            date it was requested in.  The plan is to eventually allow uploaded
 *            return files.  [BR]
 *
 */
class ACH_Return_Ofs extends ACH_Return implements ACH_Return_Interface
{
	/**
	 * Regal's Return File Format
	 *
	 * Items with an asterisk (*) next to them are required
	 * for the returns handling to work.
	 */
	static protected $return_file_format = array(
		'company_id',			// (Field 1)
		'name_first',			// (Field 2)
		'name_last',			// (Field 3)
		'recipient_id',			// In House ID (our ach_id) (Field 4)
		'amount',			// (Field 5)
		'aba',                       	// (Field 6)
		'dda',                       	// (Field 7) Bank Account
		'reason_code',	 		// (Field 8)
		'proc_return_reason', 		// (Field 9)
		'trans_code',                  	// (Field 10) DB or CR
		/*
		'proc_tran_id', 		// Processor Tran ID 		(Field  1)
		'proc_batch_id', 		// Processor Batch ID 		(Field  2)
		'trans_code', 			// Action (Debit / Credit) 	(Field  3)
		'recipient_id',			// In House ID (our ach_id) 	(Field  4)
		'proc_submit_date', 		// Submit Date 			(Field  5)
		'recipient_name', 		// Name (Customer Name) 	(Field  6)
		'return_date', 			// Return Date 			(Field  7)
		'proc_check_no', 		// Check No (Unknown) 		(Field  8)
		'proc_check_trans_date', 	// Check / Trans Date 		(Field  9)
		'cust_bank_aba',		// Bank ABA 			(Field 10)
		'cust_acct_num', 		// Account Number (Last 4) 	(Field 11)
		'proc_reference', 		// Reference 			(Field 12)
		'proc_import_id',		// Import ID (Unknown) 		(Field 13)
		'proc_submit_count', 		// Submit Count 		(Field 14)
		'proc_status',			// Status (Returned) 		(Field 15)
		'reason_code',	 		// Return Code			(Field 16)
		'proc_return_reason', 		// Status Desc 			(Field 17)
		'amount_debit',			// Amount Debit 		(Field 18)
		'amount_credit',		// Amount Credit 		(Field 19)
		*/
	);

	/**
	 * Regal's Corrections File Format
	 *
	 */
	static protected $correction_file_format = array(
		'proc_tran_id', 			// Processor Tran ID 		(Field  1)
		'proc_submit_date', 		// Submit Date 				(Field  2)
		'return_file_source',		// Orig. Batch Filename		(Field  3)
		'proc_company', 			// Company (Unknown) 		(Field  4)
		'recipient_name', 			// Name (Customer Name) 	(Field  5)
		'recipient_id',		// In House ID (our ach_id) (Field  6)
		'correct_routing_number',	// Bank ABA 				(Field  7)
		'correct_acct_number', 		// Account Number (Last 4) 	(Field  8)
		'proc_reference', 			// Reference 				(Field  9)
		'reason_code', 				// Return Code * 			(Field 10)
		'proc_return_reason', 		// Status Desc 				(Field 11)
		'addenda_info',				// Addenda					(Field 12)
	);

	/**
	 * Used by the parser to determine how many rows to skip before
	 * the actual report data starts.
	 */
	const REPORT_OFFSET      = 1;

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * A wrapper that pulls the reports for a particular date range and for a
	 * particular report type and passes it to Process_ACH_Report_Data().
	 *
	 * @param string $end_date
	 * @param string $report_type
	 * @param string $override_start_date
	 * @return boolean
	 */
	public function Process_ACH_Report ($end_date, $report_type, $override_start_date = NULL)
	{
		if(empty($override_start_date))
		{
			$this->getReportRunDates($start_date, $end_date, $report_type);
		}
		else
		{
			$start_date = $override_start_date;
		}

		$this->log->Write("Process_ACH_Report(): start date: {$start_date}, end date: {$end_date}");

		$result = $this->fetchReportIdsByDate($report_type, $start_date, $end_date);

		if($result->rowCount() > 0)
		{
			$count = 0;
			while($reportIdResult = $result->fetch(PDO::FETCH_ASSOC))
			{
				$report = $this->fetchReportById($reportIdResult['ach_report_id']);

				$this->log->Write("Processing ACH report {$reportIdResult['ach_report_id']} for {$report['date_request']}");
				if($report_results = $this->Process_ACH_Report_Data($report, $report_type))
				{
					$this->Update_ACH_Report_Status($report['ach_report_id'], 'processed');
					$this->log->Write("ACH: Successfully processed report id {$report['ach_report_id']}");
				}
				else
				{
					$this->Update_ACH_Report_Status($report['ach_report_id'], 'failed');
					$this->log->Write("ACH: Failed processing report id {$report['ach_report_id']}");
				}
				$count++;
			}
			$this->log->Write("ACH: $count " . ucfirst($report_type) . " Report(s) were processed.");
		}
		else
		{
			$this->log->Write("Unable to retrieve report type $report_type for $start_date");
			return FALSE;
		}

		if(count($this->ach_exceptions) > 0)
		{
			$this->log->Write("ACH: " . count($this->ach_exceptions) . " Exceptions found.", LOG_ERR);
			$report_body = "";

			require_once(LIB_DIR . '/CsvFormat.class.php');

			$csv = CsvFormat::getFromArray(array(
				'ACH ID',
				'Name',
				'Exception Message'));

			foreach ($this->ach_exceptions as $e)
			{
				$csv .= CsvFormat::getFromArray(array(
					$e['ach_id'],
					$e['recipient_name'],
					$e['exception']));
			}

			$attachments = array(
				array(
					'method' => 'ATTACH',
					'filename' => 'ach-exceptions.csv',
					'mime_type' => 'text/plain',
					'file_data' => gzcompress($csv),
					'file_data_length' => strlen($csv)));

			if(ECash::getConfig()->NOTIFICATION_ERROR_RECIPIENTS != NULL) {
				$recipients = ECash::getConfig()->NOTIFICATION_ERROR_RECIPIENTS;
			}

			if (!empty($recipients))
			{
				$subject = 'Ecash Alert '. strtoupper($this->company_abbrev);
				$body = $this->company_abbrev . ' - ACH ' . ucwords($report_type) . ' Exception Report';
				require_once(LIB_DIR . '/Mail.class.php');
				try
				{
					eCash_Mail::sendExceptionMessage($recipients, $body, $subject, array(), $attachments);
				}
				catch (Exception $e)
				{
					$this->log->Write("The ACH Exception Report Failed to send but returns have been logged.");
				}
			}
		}
	}

	static public function getReportFormat($report_type = NULL)
	{
		switch($report_type)
		{
			case 'returns':
				return self::$return_file_format;
				break;

			case 'corrections';
				return self::$correction_file_format;
				break;

			default:
				throw new Exception("Unknown report format $report_type!");
		}
	}

	/**
	 * Process an ACH Report
	 *
	 * @param array $response
	 * @return boolean
	 */
	public function Process_ACH_Report_Data ($response, $report_type)
	{
		$this->business_day = $end_date;
		$commented_corrections = array();
		$reschedule_list = array();
		$this->ach_exceptions = array();
		$update_array = array();
		$report_format = $this->getReportFormat($report_type);

		try
		{
			/**
			 * If the parser fails, we can't process the report, so return FALSE
			 */
			if(! $ach_report_data = ECash_CSVParser::parse($response['received'], $report_format, self::REPORT_OFFSET))
			{
				return FALSE;
			}

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
				$report_data['processing_status'] = '';
				$report_data['processing_status_details'] = '';

				$this->ach_exceptions_flag = FALSE;

				if(!is_array($report_data))
					continue;

				if(! isset($report_data['recipient_id']) || empty($report_data['recipient_id']))
				{
					$this->log->Write("Unrecognized Report Entry: " . var_export($report_data,true));

					$report_data['processing_status'] = 'Exception';
					$report_data['processing_status_details'] = 'No recipient_id in return record.';

					$exception = array(
						'ach_id'  => $ach_id,
						'exception' => "Unrecognized Report Entry: " . var_export($report_data,true),
					);
					$this->ach_exceptions[$ach_id] = $exception;
					$this->Insert_ACH_Exception($report_data);
					continue;
				}

				$ach_id	= ltrim($report_data['recipient_id'], '0');
				
				$reason_code = trim($report_data['reason_code']);
				if ($report_type == 'returns' && substr($reason_code, 0, 1) === "C")
				{
					$this->log->Write("Return Entry C: " . var_export($report_data,true));

					$report_data['processing_status'] = 'Exception';
					$report_data['processing_status_details'] = 'C return code in return record.';

					$exception = array(
						'ach_id'  => $ach_id,
						'exception' => "Return Entry C: " . var_export($report_data,true),
					);
					$this->ach_exceptions[$ach_id] = $exception;
					$this->Insert_ACH_Exception($report_data);
					continue;
				}

				// must be 5 digits long to avoid processing any records from spanish website customers
				$min_ach_id = ECash::getConfig()->MIN_ACH_ID;
				if (is_numeric($ach_id) && ($ach_id > $min_ach_id))
				{
					$reason_code = trim($report_data['reason_code']);

					// There are two different fields for amount, which is stupid since
					// a transaction can only be for one amount and there's already a
					// transaction type field telling us whether or not it's a credit
					// or debit transaction that's being returned. [BR]
					/*
					if (strtolower($report_data['trans_code']) === 'debit')
					{
						$report_data['amount'] = $report_data['amount_debit'];
					}
					else
					{
						$report_data['amount'] = $report_data['amount_credit'];
					}
					*/

					$this->log->Write("Process_ACH_Report: ach_id: $ach_id");

					if ($report_type == 'returns' && substr($reason_code, 0, 1) !== "C")
					{
						// Update status to returned in ach table
						try
						{
							if (! $this->db->getInTransaction())
								$this->db->beginTransaction();

							if($this->ach_utils->Update_ACH_Row('customer', $ach_id, 'returned', NULL, $reason_code, $response['ach_report_id'], $this->ach_exceptions))
							{
								// Update failure status into transaction_register row(s) for this ach_id
								$report_data['processing_status'] = 'Updated';
								$needs_reschedule = $this->Update_Transaction_Register_ACH_Failure($ach_id);
								$this->db->commit();
							}
							else
							{
								// No update occurred, so we won't reschedule this account.  Get out of
								// the transaction and jump to the next record.
								$report_data['processing_status'] = 'Exception';
								if(isset($this->ach_exceptions[$ach_id]))
								{
									$report_data['processing_status_details'] = $this->ach_exceptions[$ach_id]['exception'];
								}
								else
								{
									$report_data['processing_status_details'] = 'Unable to update ACH record';
								}

								$this->db->commit();
								continue;
							}

						}
						catch (Exception $e)
						{
							$this->log->Write("There was an error failing an eCash transaction: {$e->getMessage()}");
							if ($this->db->getInTransaction())
							{
								$this->db->rollback();
							}
							throw new $e;
						}

						// Add this app to the rescheduling list
						$application_id = $this->Get_Return_App_ID($ach_id);
						if(! empty($application_id))
						{
							if($needs_reschedule)
							{
								$reschedule_list[] = $application_id;
							}

							// GF #10079:
							// AALM wants to hit a stat, but not for credits, only debits,
							// trans_code == Credit for credit
							// trans_code == Debit for debits
							//if (strtolower($report_data['trans_code']) === 'debit')
							if (strtolower($report_data['trans_code']) === 'db')
							{
								if (!isset($debit_list))
									$debit_list = array();

								// We only want to send this stat once per application_id per return file
								// We can do that by making an array and only inserting unique keys into it
								if (!in_array($application_id, $debit_list))
								{
									$debit_list[] = $application_id;

									// Hit ach_return stat
									$stat = new Stat();
									$stat->Setup_Stat($application_id);
									$stat->Hit_Stat('ach_return');
								}
							}
						}
						else
						{
							$this->log->Write("Unable to locate application id for ach id: $ach_id");
							$report_data['processing_status'] = 'Exception';
							$report_data['processing_status_details'] = 'Unable to locate application.';
						}
					}
					elseif ($report_type == 'corrections')
					{
						// Process corrections -- update related application data, if possible
						
						/**
						 * Regal delimits their data fields using whitespace
						 */
						$corrected_data_ary = preg_split('/\s{1,}/', $report_data['corrected_info']);

						$do_update = false;

						switch($reason_code)
						{
						case 'C01':
							// Incorrect account number
							if ( $this->Validate_COR_Account($report_data['addenda_info'], $normalized_account) )
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
							if ( $this->Validate_COR_ABA($report_data['addenda_info'], $normalized_ABA) )
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
							if ( $this->Validate_COR_ABA($report_data['correct_routing_number'], $normalized_ABA)			&&
							     $this->Validate_COR_Account($report_data['correct_acct_number'], $normalized_account) 		)
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
							if ( $this->Validate_Name($report_data['addenda_info'], $normalized_name_last, $normalized_name_first) )
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
							if ( $this->Validate_Tran_Code($report_data['addenda_info'], $bank_account_type) )
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
							if ( $this->Validate_COR_Account($report_data['correct_acct_number'], $normalized_account)	&&
							     $this->Validate_Tran_Code($report_data['correct_trans_code'], $bank_account_type)			)
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
							if ( $this->Validate_COR_ABA($report_data['correct_routing_number'], $normalized_ABA)			&&
							     $this->Validate_COR_Account($report_data['correct_acct_number'], $normalized_account)	&&
							     $this->Validate_Tran_Code($report_data['correct_trans_code'], $bank_account_type)			)
							{
								$app_update_ary = array (
											 'bank_aba'			=> $normalized_ABA,
											 'bank_account'		=> $normalized_account,
											 'bank_account_type'	=> $bank_account_type
											 );
								$comment_text = "ABA/Acct#/Type auto correction: Set to $normalized_ABA / $normalized_account / $bank_account_type";
								$do_update = true;
							}
							break;
						}

						if ($do_update)
						{
							$application_id = $this->Get_Return_App_ID($ach_id);
							if ($application_id)
							{
								$update_array[$application_id] = $app_update_ary;	
								$updated = $this->Update_Application_Info($application_id, $app_update_ary);
								if ($updated === FALSE)
								{
									$this->log->Write("Unable to update App ID: {$application_id}");
									$report_data['processing_status_details'] = 'Unable to update application.';
								}
								else
								{
									// A Dirty hack by RayLo to keep for entering duplicate corrections comments
									// We will keep an array of commented corrections so that we dont comment
									// this application again while going through the corrections
									if(!in_array($application_id,$commented_corrections))
									{
										$commented_corrections[] = $application_id;
										$this->ach_utils->Add_Comment($application_id, $reason_code.' - '.$comment_text);
										$commented_corrections[] = $application_id;
									}
									$report_data['processing_status'] = 'Corrected';
								}
							}
							else
							{
								$this->log->Write("Unable to locate Application ID for :'{$ach_id}'");
								$report_data['processing_status'] = 'Exception';
								$report_data['processing_status_details'] = 'Unable to locate application.';
							}
						}
					}
				}
				else
				{
					$this->log->Write("Unrecognized Report Entry: " . var_export($report_data,true));

					$report_data['processing_status'] = 'Exception';
					$report_data['processing_status_details'] = (is_numeric($ach_id) && ($ach_id < $min_ach_id)) ? 
					  'recipient_id less than 5 digits' : 'recipient_id is not a number.';

					$exception = array(
						'ach_id'  => $ach_id,
						'exception' => "Unrecognized Report Entry: " . var_export($report_data,true),
					);
					$this->ach_exceptions[$ach_id] = $exception;
				}

				// Insert ach exception if any exceptions thrown for ach record.
				if($report_data['processing_status'] == 'Exception')
				{
					$this->Insert_ACH_Exception($report_data);
				}
			}
			//Send all changes to the appservice
			$this->SendChangesToAppService($update_array);
		}
		catch(Exception $e)
		{
			$this->log->Write("ACH: Processing of $report_type failed and transaction will be rolled back.", LOG_ERR);
			$this->log->Write("ACH: No data recovery should be necessary after the cause of this problem has been determined.", LOG_INFO);
			throw $e;
		}

		// Now put everyone in the reschedule_list into the standby table for later processing
		$reschedule_list = array_unique($reschedule_list);
		foreach($reschedule_list as $application_id)
		{
			Set_Standby($application_id, $this->company_id, 'reschedule');
		}

		return $ach_report_data;
	}

	/**
	 * Overload the parent method so we can do some element naming
	 * translations
	 *
	 * @param array $report_data
	 */
	protected function Insert_ACH_Exception($report_data=NULL)
	{
		if($report_data)
		{
			$report_data['debit_amount']  = isset($report_data['amount_debit']) ? trim($report_data['amount_debit']) : '0.00';
			$report_data['credit_amount'] = isset($report_data['amount_credit']) ? trim($report_data['amount_credit']) : '0.00';

			parent::Insert_ACH_Exception($report_data);
		}
	}

	public function Send_Report_Request($start_date, $report_type)
	{
		$return_val = array();
		/**
		 * Holds a query string emulating the request.
		 */
		$transport_type = ECash::getConfig()->ACH_TRANSPORT_TYPE;
		$batch_server   = ECash::getConfig()->ACH_BATCH_SERVER;
		$batch_login    = ECash::getConfig()->ACH_REPORT_LOGIN;
		$batch_pass     = ECash::getConfig()->ACH_REPORT_PASS;
		$transport_port = ECash::getConfig()->ACH_BATCH_SERVER_PORT;

		/**
		 * Get the directory prefix
		 */
		if($report_type == 'returns')
		{
			$directory_url = ECash::getConfig()->ACH_REPORT_RETURNS_URL;
		}
		else
		{
			$directory_url = ECash::getConfig()->ACH_REPORT_CORRECTIONS_URL;
		}

		// make multiple request attempts
		for ($i = 0; $i < 5; $i++)
		{
			try
			{
				$transport = ACHTransport::CreateTransport($transport_type, $batch_server,  $batch_login, $batch_pass, $transport_port);

				if ($transport->hasMethod('setDate'))
				{
					$transport->setDate($start_date);
				}

				if ($transport->hasMethod('setCompanyId'))
				{
					$transport->setCompanyId($this->ach_report_company_id);
				}

				// Fetch the filename
				if($url = $this->Get_Report_Filename($transport, $directory_url, $report_type, $start_date))
				{
					$report_response = '';
					$report_success = $transport->retrieveReport($url, $report_type, $report_response);
				}
				else
				{
					// File doesn't exist.  If we're checking for a corrections file, we'll go ahead
					// and consider it good.
					if($report_type === 'corrections' && $i === 5)
					{
						$report_success = TRUE;
					}

					$report_success = FALSE;
				}

				if (!$report_success) {
					$this->log->write('(Try '.($i + 1).') Received an error code. Not trying again.');
					$this->log->write('Error: '.$report_response);
				}
				break;
			} catch (Exception $e) {
				$this->log->write('(Try '.($i + 1).') '.$e->getMessage());
				$report_response = '';
				$report_success = false;
				sleep(5);
			}
		}

		//if ($report_success && strlen($report_response) > 0)
		if ($report_success)
		{
			$request = 'report='.$report_type.
					'&sdate='.date("Ymd", strtotime($start_date)).
					'&edate='.date("Ymd", strtotime($start_date)).
					'&compid='.$this->ach_report_company_id;

			$this->log->Write("Successfully retrieved '".strlen($report_response)."' byte(s) $report_type report for $start_date.");
			$this->Insert_ACH_Report_Response($request, $report_response, $start_date, $report_type);

			return true;

		}
		else
		{
			$this->log->Write("ACH '$report_type' report: was unable to retrieve report from $url", LOG_ERR);
			return false;
		}
	}
	public function Parse_Report_Batch ($return_file, $report_format)
	{
		try {
	
			return ECash_CSVParser::parse($return_file, $report_format, self::REPORT_OFFSET);
		}
		catch(Exception $e)
		{
			return false;
		}
	}
	/**
	 * This method returns the filename for the report by looking for a filename
	 * that matches the prefix and date.  The filename contains the time the file
	 * was created as well so this is the best alternative to a Wildcard '*'
	 * of the filename.  This will only return the first match so if there
	 * are multiple files in the directory with the same date stamp, only one will be
	 * processed.
	 *
	 * @param String $hostname
	 * @param Int $port
	 * @param String $username
	 * @param String $password
	 * @param String $directory
	 * @return String
	 */
	public function Get_Report_Filename($transport, $directory_url, $report_type, $start_date)
	{
		$list = $transport->getDirectoryList($directory_url);

		// Prefix for Returns: PreviouslyPaidReturnsReport_20080811184645.csv
		// Prefix for Corrections: CorrectionsBySubmitDateReport_20080911170217.csv

		switch($report_type)
		{
			case 'returns':
				$prefix = 'PreviouslyPaidReturnsReport';
				break;

			case 'corrections':
				$prefix = 'CorrectionsBySubmitDateReport';
				break;
		}

		if(is_array($list))
		{
			foreach($list as $filename => $attrib)
			{
				if(stristr($filename, $prefix . "_".date("Ymd",strtotime($start_date))))
				{
					return $directory_url . "/" . $filename;
				}
			}
		}
		else
		{
			if(stristr($list, $prefix . "_".date("Ymd",strtotime($start_date))))
			{
				return $directory_url . "/" . $list;
			}
		}

		return FALSE;
	}


}
?>
