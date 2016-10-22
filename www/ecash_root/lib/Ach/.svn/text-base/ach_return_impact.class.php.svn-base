<?php

/**
 * Class for handling returns from Impact Payments.  This processor is currently
 * used by both Impact and HMS.  They use a typical CSV file for their returns
 * format.
 *
 * History:
 * 20090309 - Started refactoring the class so that it's modeled after the
 *            ACH_Return_Regal class which was modeled to easily process return
 *            files outside of the normal automated returns processing. [BR]
 */
class ACH_Return_Impact extends ACH_Return implements ACH_Return_Interface
{
	/**
	 * Impact's Return File Format
	 *
	 * Impact uses the same file format for both returns and corrections.
	 *
	 * Items with an asterisk (*) next to them are required
	 * for the returns handling to work.
	 */
	static protected $return_file_format = array(
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
			case 'corrections';
				return self::$return_file_format;
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
	 *
	 * Will update $response rows with feedback regarding the processing of each transaction
	 *
	 * Corrections Parsing Code Regex:  ^([A-z0-9 ]+); (\d+)
	 * Will catch something like "Incorrect DFI Number; 2342342  "
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

				if (is_numeric($ach_id))
				{
					$reason_code = trim($report_data['reason_code']);

					$this->log->Write("Process_ACH_Report: ach_id: $ach_id");

					if ($report_type == 'returns')
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
							if (strtolower($report_data['trans_code']) === 'debit')
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
						$corrected_data_ary = explode("/", $report_data['corrected_info']);
						foreach ($corrected_data_ary as $key => $correction_item)
						{
							$corrected_data_ary[$key] = trim($correction_item);
						}

						$do_update = false;

						switch($reason_code)
						{
						case 'C01':
							// Incorrect account number
							if ( $this->Validate_COR_Account($corrected_data_ary[1], $normalized_account) )
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
							if ( $this->Validate_Tran_Code($corrected_data_ary[2], $bank_account_type) )
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
							if ( $this->Validate_COR_Account($corrected_data_ary[1], $normalized_account)	&&
							     $this->Validate_Tran_Code($corrected_data_ary[2], $bank_account_type)			)
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
					$report_data['processing_status_details'] = 'recipient_id is not a number.';

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
	 * Processes a specific report based on it's ach_report_id
	 *
	 * Note:  This is untested!  This also should be moved
	 * to the parent class once we've refactored all of the
	 * processor specific child classes.
	 *
	 * @param int $ach_report_id
	 * @param string $report_type
	 * @return boolean
	 */
	public function processReportByID($ach_report_id, $report_type)
	{
		if($report_data = $this->fetchReportById($ach_report_id))
		{
			if($this->Process_ACH_Report_Data($report_data, $report_type))
			{
				$this->Update_ACH_Report_Status($ach_report_id, 'processed');
				return TRUE;
			}
			else
			{
				$this->Update_ACH_Report_Status($ach_report_id, 'failed');
				return FALSE;
			}
		}

		return FALSE;
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
}
?>
