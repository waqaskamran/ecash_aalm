<?php

/**
 * Class for handling returns from Purepay.  This processor is currently
 * used by both TGC of HMS.  They are using a carbon copy of the Impact Payments return file.
 * The only difference is in the way the file is retrieved!
 *
 */
class ACH_Return_Purepay extends ACH_Return_Impact  implements ACH_Return_Interface
{

	/**
	 * Used to determine whether or not the returns file will contain
	 * both the returns and corretions in one file or to retrieve and process
	 * two separate files.
	 */
	
	public function Send_Report_Request($report_date, $report_type)
	{
		
		$return_val = array();
		/**
		 * Holds a query string emulating the request.
		 */
		$transport_type = ECash::getConfig()->ACH_TRANSPORT_TYPE;
		$batch_server   = ECash::getConfig()->ACH_BATCH_SERVER;
		$batch_login    = ECash::getConfig()->ACH_REPORT_LOGIN;
		$batch_pass     = ECash::getConfig()->ACH_REPORT_PASS;
		$transport_port   = ECash::getConfig()->ACH_BATCH_SERVER_PORT;

		for ($i = 0; $i < 5; $i++) { // make multiple request attempts
			try {
				$transport = ACHTransport::CreateTransport($transport_type, $batch_server, $batch_login, $batch_pass, $transport_port);

				if (EXECUTION_MODE != 'LIVE' && $transport->hasMethod('setBatchKey'))
				{
					$transport->setBatchKey(ECash::getConfig()->ACH_BATCH_KEY);
				}

				if ($transport->hasMethod('setDate'))
				{
					$transport->setDate($report_date);
				}

				if ($transport->hasMethod('setCompanyId'))
				{
					$transport->setCompanyId($this->ach_report_company_id);
				}

				switch($report_type)
				{
					case "returns":
						$directory = ECash::getConfig()->ACH_REPORT_RETURNS_URL;
						$filename = $this->Get_Return_Filename($transport, $directory, $report_date);
						$url = $directory . $filename;

						break;

					case "corrections":
						$prefix = ECash::getConfig()->ACH_REPORT_CORRECTIONS_URL_PREFIX;
						$suffix = ECash::getConfig()->ACH_REPORT_CORRECTIONS_URL_SUFFIX;
						$corrections_url = ECash::getConfig()->ACH_REPORT_CORRECTIONS_URL;

						if($prefix != NULL && $suffix != NULL)
						{
							$url = $prefix.date("Ymd",strtotime($report_date)).$suffix;
						}
						else if($corrections_url != NULL)
						{
							$url = $corrections_url;
						}
						else
						{
							$url = ECash::getConfig()->ACH_REPORT_URL;
						}

						break;
				}

				$report_response = '';
				$report_success = $transport->retrieveReport($url, $report_type, $report_response);

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

		if ($report_success)
		{
			$request = 'report='.$report_type.
					'&sdate='.date("Ymd", strtotime($report_date)).
					'&edate='.date("Ymd", strtotime($report_date)).
					'&compid='.$this->ach_report_company_id;

			$this->log->Write("Successfully retrieved '".strlen($report_response)."' byte(s) $report_type report for $report_date from '$url'");
			/** @todo: Add Filename here!  **/
			$request = $url;
			$ach_report_id = $this->Insert_ACH_Report_Response($request, $report_response, $report_date, $report_type);

			return $ach_report_id;

		}
		else
		{
			$this->log->Write("ACH '$report_type' report: was unable to retrieve report from $url", LOG_ERR);
			return false;
		}
	}
	
		/**
	 * This method returns the results filename because a portion of the filename
	 * consists of a batch_id that is generated on Teledraft's side and we can't predict it.
	 *
	 * @param String $server
	 * @param String $directory
	 * @param String $start_date
	 * @return String
	 */
	public function Get_Return_Filename($transport, $directory, $start_date)
	{
		$client_id = ECash::getConfig()->CYBR_LOCATION_ID;
		$directory = ECash::getConfig()->ACH_REPORT_RETURNS_URL;
		$list = $transport->getDirectoryList($directory);
		foreach($list as $filename => $fileproperty)
		{
			if(stristr($filename, $client_id . "_Return") && stristr($filename, date("ymd",strtotime($start_date))))
			{
				return $filename;
			}
		}

		return FALSE;
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
					$report_type = (substr($reason_code,0,1) == 'C') ? 'corrections' : 'returns'; 
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
	
}
?>
