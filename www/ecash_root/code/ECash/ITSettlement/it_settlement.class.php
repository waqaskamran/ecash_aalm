<?php
require_once('it_settlement_interface.iface.php');

/**
 * IT_Settlement
 * This class is your one stop shop for all things IT_Settlement!
 *
 */
class IT_Settlement implements IT_Settlement_Interface 
{
	protected $log;
	protected $server;
	protected $db;
	
	private $holiday_ary;
	private $paydate_obj;
	private $paydate_handler;
	private $biz_rules;

	protected $company_abbrev;
	protected $company_id;
	protected $agent_id;

	protected $transport_type;
	protected $settlement_server;
	protected $settlement_port;
	protected $settlement_url;
	protected $settlement_login;
	protected $settlement_pass;
	protected $summary_file;
	protected $summary_extension;
	protected $details_file;
	protected $details_extension;
	protected $customer_file;
	protected $customer_extension;
	protected $cso_loan_types;
	protected $report_type;
	
	public function __construct(Server $server)
	{
		$this->report_type = 'it_settlement';
		$this->server			= $server;
		$this->db = ECash::getMasterDb();

		$this->company_id		= ECash::getCompany()->company_id;
		$this->company_abbrev	= strtolower(ECash::getCompany()->name_short);
		$this->agent_id			= ECash::getAgent()->getAgentId();
		
		// Set up separate log object for IT Settlement logging
		$this->log = get_log('it_settlement');
		
		// get the config defined IT Settlement stuff.

		$this->transport_type = 	ECash::getConfig()->IT_SETTLEMENT_TRANSPORT_TYPE;
		$this->settlement_server =	ECash::getConfig()->IT_SETTLEMENT_SERVER;
		$this->settlement_port =	ECash::getConfig()->IT_SETTLEMENT_SERVER_PORT;
		$this->settlement_url = 	ECash::getConfig()->IT_SETTLEMENT_URL;
		$this->settlement_login =	ECash::getConfig()->IT_SETTLEMENT_LOGIN;
		$this->settlement_pass =	ECash::getConfig()->IT_SETTLEMENT_PASS;
		
		$this->summary_file	=		ECash::getConfig()->IT_SETTLEMENT_SUMMARY_FILE;
		$this->summary_extension =	ECash::getConfig()->IT_SETTLEMENT_SUMMARY_EXTENSION;
		$this->details_file =		ECash::getConfig()->IT_SETTLEMENT_DETAILS_FILE;
		$this->details_extension =	ECash::getConfig()->IT_SETTLEMENT_DETAILS_EXTENSION;
		$this->customer_file	=	ECash::getConfig()->IT_SETTLEMENT_CUSTOMER_FILE;
		$this->customer_extension =	ECash::getConfig()->IT_SETTLEMENT_CUSTOMER_EXTENSION;
		
		
		// Set up pay date object for business day increment/decrement calculation
		$holidays = Fetch_Holiday_List();
		$this->paydate_obj = new Pay_Date_Calc_3($holidays);
		
		// Set up stuff for loan rescheduling
		$this->paydate_handler = new Paydate_Handler($this->server);
		$this->biz_rules       = new ECash_Business_Rules($this->db);
		
		// get list of loan types to include in queries
		$this->cso_loan_types = $this->getRuleSets('loan_type_model','CSO');
	}

	/**
	 * generateReport - this function generates a report set based off of the start date and end date passed to it. 
	 * It generates all the relevant reports set and stores them in the database linked to that report_id and report date.
	 * 
	 *
	 * @param date (YYY-MM-DD HH:MM:SS) $start_date - The starting period for this report set. (Typically the last time the report was run)
	 * @param date (YYY-MM-DD HH:MM:SS) $end_date - The ending period for this report set. (Typically the current timestamp)
	 * @param date (YYYY-MM-DD) $report_date - The date that this report is for (typically the current date)
	 * @return int $report_id - The ID of the newly generated report
	 */
	public function generateReport($start_date,$end_date,$report_date)
	{
		$this->log->Write("Generating report for {$report_date} using start date:{$start_date} and end date:{$end_date}");
		
		//Get status classes for easy status lookup, update, and history writing
		$status      = new ECash_Status('Sreport',$this->agent_id);
		$send_status = new ECash_Status('sreport',$this->agent_id,'sreport_send_status');

		//Insert Report record
		$report = ECash::getFactory()->getModel('Sreport');

		$report->company_id         = $this->company_id;
		$report->sreport_start_date = $start_date;
		$report->sreport_end_date   = $end_date;
		$report->sreport_date       = $report_date;

		$report->sreport_type_id = ECash::getFactory()->getModel('SreportType')->getTypeId($this->report_type);

		$report->sreport_status_id      = $status->getStatus('created');
		$report->sreport_send_status_id = $send_status->getStatus('unsent');
		$report->insert();

		$this->log->Write("Settlement report {$report->sreport_id} successfully inserted");
		$report_id = $report->sreport_id;
		
		//Now that this report has been created, its time to generate the individual reports that compose this report
		try{
			
			//Create Settlement reports
			$this->log->Write("Generating Settlement Summary Report");
			$settlement_summary = $this->createSettlementSummary($start_date,$end_date,$report_date);

			//get the filename that we should be using for this file
			$filename = $this->summary_file.str_replace('-','',$report_date);

			//If you're creating a document as HTML or PDF, you need a template to populate with the data. 
			//Template is currently hardcoded, we should definitely have a better way to do this. I <3 time restrictions and loose specs
			$template = ECASH_DIR.'ITSettlement/it_settlement_summary.html';

			//Create summary file
			$this->log->Write("Creating file for Settlement Summary Report {$filename}.{$this->summary_extension}");
			$file = $this->{'create'.$this->summary_extension}($settlement_summary,$filename,$template);

			//Insert summary report
			$this->log->Write("Inserting file for Settlement Summary Report.  Report id = {$report_id} File = {$file}");
			$this->insertFile($report_id,$file,'it_settlement_summary',$filename,$this->summary_extension);
	
			//Create Settlement Details report
			$this->log->Write("Generating Settlement Details Report");
			$settlement_details = $this->createSettlementDetails($start_date,$end_date);
			$filename = $this->details_file.str_replace('-','',$report_date);

			//Create details file
			$this->log->Write("Creating file for Settlement Details Report {$filename}.{$this->details_extension}");
			$file = $this->{'create'.$this->details_extension}($settlement_details,$filename);

			//Insert details report
			$this->log->Write("Inserting file for Settlement Details Report.  Report id = {$report_id} File = {$file}");
			$this->insertFile($report_id,$file,'it_settlement_details',$filename,$this->details_extension);
					
			//Create Loan and Customer Report
			$this->log->Write("Generating Customer Loan Table Report");
			$loan_customer = $this->createLoanAndCustomer($start_date,$end_date);
			$filename = $this->customer_file.str_replace('-','',$report_date);

			//Create loan customer file
			$this->log->Write("Creating file for Customer Loan Table Report {$filename}.{$this->customer_extension}");
			$file = $this->{'create'.$this->customer_extension}($loan_customer,$filename);

			//Insert Loan Customer
			$this->log->Write("Inserting file for Customer Loan Table Report. Report id = {$report_id} File = {$file}");
			$this->insertFile($report_id,$file,'loan_customer_table',$filename,$this->customer_extension);
					
			//Note that the report just generated is the current version.
			$this->log->Write("Report {$report_id} has been successfully generated.  Setting status to 'current'");
			$status->setStatus('current',$report_id);
		}
		catch (Exception $e)
		{	
			//Something went horribly wrong.  Update the report's ID.
			$this->log->Write("Report creation failed - ".$e->getMessage());
			$status->setStatus('failed',$report_id);
		}
	
		//Update report to denote it's created
		//$this->sendReport($report_id);
		return $report_id;
	}
	
	/**
	 * regenerateReport
	 * Regenerates the selected report.  This means setting the current report to obsolete, and then running the whole report generation
	 * process again.  This is useful for instances when the current incarnation of the report failed, or the current incarnation of the 
	 * report is incorrect or flawed (a problem with the query, a change in the client's requirements)
	 *
	 * @param int $report_id The id of the report to regenerate
	 * @return int $new_report_id the id of the new report
	 */
	public function regenerateReport($report_id)
	{
		$this->log->Write("Regenerating report {$report_id} for agent {$this->agent_id}");
		//Grab report to regenerate
	
		$report = ECash::getFactory()->getModel('Sreport');
		$report->loadBy(array('sreport_id' => $report_id));

		//Grab status object for easy status lookup, update, and history tracking
		$status = new ECash_Status('sreport',$this->agent_id,'sreport_status');
		
		$start_date  = $report->sreport_start_date;
		$end_date    = $report->sreport_end_date;
		$report_date = $report->sreport_date;

		//Obsolete current report
		$this->log->Write("Obsoleting report {$report_id}");
		$status->setStatus('obsolete',$report_id);

		//Generate new report
		$new_report_id = $this->generateReport($start_date,$end_date,$report_date);
		
		return $new_report_id;
	}
	
	/**
	 * retestReport
	 * This is a really lame debugging function I created.  It allows you to regenerate a specific report type
	 * for a specific date without storing it or logging anything.  I created it to help track down issues from 22506/22059, but
	 * really this can be used for testing any IT Settlement report bugs, and verifying 'fixes'.    I typically replace 'regenerateReport'
	 * with this function , but you can use it however you'd like.
	 * I'm leaving it in here for convenience's sake as I'm sure there will be more IT Settlement report issues that will require
	 * this sort of testing. [W!-01-05-2009][#22506]
	 *
	 * @param int $report_id
	 */
	public function retestReport($report_id)
	{
		$report = ECash::getFactory()->getModel('Sreport');
		$report->loadBy(array('sreport_id' => $report_id));

		//Grab status object for easy status lookup, update, and history tracking
		
		$start_date  = $report->sreport_start_date;
		$end_date    = $report->sreport_end_date;
		$report_date = $report->sreport_date;
		$details_report = $this->createSettlementDetails($start_date,$end_date,false);
		$filename = $this->details_file.str_replace('-','',$report_date);
		//Create details file
		$file = $this->{'create'.$this->details_extension}($details_report,$filename);
		
//		$summary_report = $this->createSettlementSummary($start_date,$end_date);
//		$filename = $this->summary_file.str_replace('-','',$report_date);
//		$template = ECASH_DIR.'ITSettlement/it_settlement_summary.html';
//		$file = $this->{'create'.$this->summary_extension}($summary_report,$filename,$template);
//		

		if(filesize($file))
		{
			$fp = fopen($file, 'r');
			$file_data = fread($fp,filesize($file));
			fclose($fp);
		}
		else 
		{
			echo "NO FILE/RECORDS!";die;
		}	
		
		header("Accept-Ranges: bytes\n");
		header("Content-Length: ".strlen($file_data)."\n");
		header("Content-Disposition: attachment; filename={$filename}.{$this->details_extension}\n");
		switch (strtolower($this->details_extension))
		{
			case 'csv':
			case '.csv':
				header("Content-Type: text/csv\n\n");
			break;
			
			case 'xml':
					header("Content-Type: text/xml\n\n");
			break;
			
			case 'pdf':
					header("Content-Type: application/pdf\n\n");
			break;
		}
	
		print($file_data);
		//Yeah, really, I put a die there.  You wanna fight about it?
		die;
		
		
	}
	/**
	 * sendReport
	 * Sends the selected report to location designated in the configs. This works for the initial send and any subsequent resends.
	 *
	 * @param int $report_id The id of the report to send.
	 */
	public function sendReport($report_id)
	{
		//get status object for easy lookup, updating, and history tracking
		$this->log->Write("Sending report:$report_id for agent:$this->agent_id");
		$send_status = new ECash_Status('sreport',$this->agent_id,'sreport_send_status');
		$send_status->setStatus('sending',$report_id);

		//get the list of files to send.
		$reports = ECash::getFactory()->getModel('SreportDataList');
		$reports->loadBy(array('sreport_id' => $report_id));

		//Grab the files
		try 
		{
			foreach ($reports as $report)
			{

				//write a temp file
				$this->log->Write("Writing temp file for report data:{$report->sreport_data_id} - {$report->filename}.{$report->filename_extension}");
				$filename = $report->filename.'.'.$report->filename_extension;
				$file = '/tmp/'.$filename;
				$fp = fopen($file, 'w');
				$file_contents = $report->sreport_data;
				fwrite($fp,$file_contents);
				fclose($fp);

				//upload the temp file
				$this->log->Write("Uploading report data:{$report->sreport_data_id} {$file}");

				try
				{			
					require_once(LIB_DIR . "Achtransport/achtransport_sftp.class.php");
					require_once(LIB_DIR . "Achtransport/achtransport_https.class.php");
					require_once(LIB_DIR . "Achtransport/achtransport_sftp_agean.class.php");

					$this->log->Write("Creating Settlement transport");
					$transport = ACHTransport::CreateTransport($this->transport_type, $this->settlement_server, $this->settlement_login, $this->settlement_pass,$this->settlement_port);
					$this->log->Write("Transport created");
					// If we're using SFTP, we need to specify the whole path including a filename
					if($this->transport_type === 'SFTP') $remote_filename = "{$this->settlement_url}/{$filename}";
					else $remote_filename = $this->settlement_url;

					$success = $transport->sendBatch($file, $remote_filename, $batch_response);
				} 
				catch (Exception $e) 
				{
					$this->log->Write("Upload of {$report->sreport_data_id} failed".$e->getMessage());
					throw new Exception($e->getMessage());

				}
				$this->log->Write("{$report->sreport_data_id} Successfully uploaded");
			}

			//Successfully sent, set the report's send status to 'sent'
			$this->log->Write("All report data for {$report_id} was successfully uploaded");
			$send_status->setStatus('sent',$report_id);
		}
		catch (Exception $e)
		{
			$this->log->Write("Upload of {$report_id} failed - ".$e->getMessage());
			$send_status->setStatus('send_failed',$report_id);
		}
		
	}
	
	/**
	 * createSettlementSummary
	 * Creates the records for the settlement summary report.  This function grabs records from createSettlement and 
	 * processes/formats them for the Settlement Summary report
	 *
	 * @param date (YYY-MM-DD HH:MM:SS) $start_date - The starting period for this report set. (Typically the last time the report was run)
	 * @param date (YYY-MM-DD HH:MM:SS) $end_date - The ending period for this report set. (Typically the current timestamp)
	 * @param date (YYYY-MM-DD) $report_date - The date that this report is for (typically the current date)
	 * @return array $report
	 */
	protected function createSettlementSummary($start_date,$end_date,$report_date = null)
	{
		//If the report date hasn't been passed in, return today's date.
		if (!$report_date) 
		{
			$report_date = date('m/d/Y');	
		}

		//get settlement records
		$settlement_summary = $this->createSettlement($start_date,$end_date);
		
		//process records
		
		//These are the currently hardcoded definitions for the transaction types which are going to be totalled up for the Settlement
		//Summary report.
		$rollover_start     = array('cso_assess_fee_broker'); // Signifies the start of a rollover if no disbursement is present, total not used.
		$loan_disbursement  = array('loan_disbursement', 'loan_refinancing');       
		$lender_ach_fee     = array('defaulted_ach_fees');  
		$lender_late_fee    = array('cso_assess_fee_late'); 
		$interest           = array('assess_service_chg'); 
		$refunds            = array('adjustment_internal_princ', 'refund_3rd_party_princ', 'refund_princ');
		$principal          = array('repayment_principal', 'paydown', 'payment_arranged_princ', 'payment_manual_princ', 'money_order_princ', 'western_union_princ', 'credit_card_princ', 'personal_check_princ', 'payout_principal', 'debt_writeoff_princ', 'moneygram_princ');

		$bankruptcy_status = Status_Utility::Get_Status_ID_By_Chain('verified::bankruptcy::collections::customer::*root');
		
		$settlement_report = array();
		
		//Initialize the report values
		$settlement_report['fund_amount'] = 0;
		$settlement_report['lender_interest'] = 0;
		$settlement_report['lender_principal'] = 0;
		$settlement_report['lender_fees'] = 0;
		$settlement_report['lender_principal_bankruptcy'] = 0;
		$settlement_report['lender_interest_bankruptcy'] = 0;
		$settlement_report['funding_failed_amount'] = 0;
		$settlement_report['rescinded_payments_subtotal'] = 0;
		$settlement_report['payments_returned_amount'] = 0;
		$settlement_report['refunded_amount'] = 0;
		$settlement_report['past_due_principal'] = 0;
		$settlement_report['past_due_interest'] = 0;
		$settlement_report['past_due_fees'] = 0;
		$settlement_report['past_due_subtotal'] = 0;
		
		$defaulted_apps = array();

		//Loop through the records and start counting up your totals.
		foreach ($settlement_summary as $settlement_record)
		{
			// While this code is hackish, an extreme amount of pre/post-processing is required to make
			// the numbers match what they're supposed to.
			
			// If we're seeing a service charge payment here, continue, we don't report that, we use the
			// assessment events
			if ($settlement_record['transaction_type'] == 'payment_service_chg')
				continue; 

			// If they defaulted, it should add their principal and interest balance as a payment
			if ($settlement_record['default_date'] != NULL)
			{
				// They've defaulted
				// actually.... report that it was paid
				// This is either going to be a lender late fee, a lender ACH fee, etc, so we have to do some hackery
				if (!in_array($settlement_record['application_id'], $defaulted_apps))
				{
					// Increase the lender principal
					$settlement_report['past_due_principal'] += $settlement_record['loan_amount'];

					// Increase the lender interest
					$settlement_report['past_due_interest']  += $settlement_record['interest_amount'];

					$settlement_report['past_due_fees']      += $settlement_record['ach_fee_amount'];
					
					$defaulted_apps[] = $settlement_record['application_id'];
				}

				continue;
			}

			// If there's more than one rollover, and the transaction type is in the above as the rollover start,
			// make it into a loan funding event for reporting purposes.
			// This is a CSO fee payment, turn it into a loan_refinancing for the full amount of the principal
			if ($settlement_record['rollover_count'] > 1 && in_array($settlement_record['transaction_type'], $rollover_start))
			{
				$settlement_record['transaction_type'] = 'loan_refinancing';
				$settlement_record['amount']           = $settlement_record['loan_amount'];

				// Add the faux payment to the active loan principal
				$settlement_report['lender_principal'] += $settlement_record['loan_amount'];
			}

			unset($settlement_record['rollover_count']);

			//I'm no longer using absolute values for the IT Settlement report.  Even though most of the values being displayed 
			//SHOULD be displayed as a positive, we should not make the blanket assumption that they are always going to be positive
			//In cases where there's an internal adjustment that decreases the balance of the account, that's considered a negative refund
			//and will be displayed incorrectly if we convert it to a positive value.  This also makes it difficult to track down issues
			//where a credit is being incorrectly added to the account, as we will not see the negative payment reported as such.
			//Values are now being displayed as the positive value they are, or inverted depending on which section of the report
			//they go in [W!-01-05-2009][#22506]
			switch ($settlement_record['transaction_type'])
			{
				// This should add the amount
				case in_array($settlement_record['transaction_type'], $refunds):
					$settlement_report['refunded_amount'] = bcadd($settlement_report['refunded_amount'],$settlement_record['amount'],2);
					break;
				case in_array($settlement_record['transaction_type'], $loan_disbursement):
					$settlement_report['fund_amount'] = bcadd($settlement_report['fund_amount'],$settlement_record['amount'],2);
					break;
				case (in_array($settlement_record['transaction_type'], $lender_ach_fee)  && $settlement_record['default_date'] != NULL):
					$settlement_report['lender_fees'] = bcadd($settlement_report['lender_fees'],$settlement_record['amount'],2);
					break;
				case in_array($settlement_record['transaction_type'], $interest):
					if ($settlement_record['application_status_id'] == $bankruptcy_status) 
					{
						$settlement_report['lender_interest_bankruptcy'] = bcadd($settlement_report['lender_interest_bankruptcy'],$settlement_record['amount'],2);
					}
					else 
					{
						$settlement_report['lender_interest'] = bcadd($settlement_report['lender_interest'],$settlement_record['amount'],2);
					}
					break;
				case in_array($settlement_record['transaction_type'], $principal):
					if ($settlement_record['application_status_id'] == $bankruptcy_status) 
					{
						$settlement_report['lender_principal_bankruptcy'] = bcadd($settlement_report['lender_principal_bankruptcy'],-$settlement_record['amount'],2);
					}
					else
					{
						$settlement_report['lender_principal'] = bcadd($settlement_report['lender_principal'],-$settlement_record['amount'],2);
					}
					break;
			}
			//
		}

		// Now deal with rescinded payments/funding failed
		$rescinded_payments = $this->getRescindedPayments($start_date, $end_date);

		foreach($rescinded_payments AS $bad_pay)
		{

			switch ($bad_pay['tt_name_short'])
			{
				case 'loan_disbursement':
					$settlement_report['funding_failed_amount'] += ($bad_pay['amount']);

					break;
				default:
			        $settlement_report['payments_returned_amount']    += -$bad_pay['amount'];

					break;
			}

		}

		// Subtotals
	    $settlement_report['rescinded_payments_subtotal'] += $settlement_report['payments_returned_amount'];
		$settlement_report['rescinded_payments_subtotal'] += $settlement_report['refunded_amount'];

		$settlement_report['past_due_subtotal'] = $settlement_report['past_due_principal'] + $settlement_report['past_due_interest'] + $settlement_report['past_due_fees'];
	

		//create the grand totals
		$settlement_report['lender_principal_interest'] = bcadd($settlement_report['lender_principal'],$settlement_report['lender_interest'],2);
		$settlement_report['lender_principal_interest_bankruptcy'] = bcadd($settlement_report['lender_principal_bankruptcy'],$settlement_report['lender_interest_bankruptcy'],2);

		// Splitting this up, because will apparently doesn't think readability is important

		// Net of A - B - C (subtotal) + D
		// (subtotal) - E (subtotal) - F
		// (subtotal)
                                              // A
		$settlement_report['lender_total']  = $settlement_report['fund_amount'];

                                              // - B
		$settlement_report['lender_total'] -= $settlement_report['funding_failed_amount'];

                                              // - C
		$settlement_report['lender_total'] -= $settlement_report['lender_principal_interest'];
	
                                              // + D
		$settlement_report['lender_total'] += $settlement_report['rescinded_payments_subtotal'];
		
	                                          // - E
		$settlement_report['lender_total'] -= $settlement_report['lender_principal_interest_bankruptcy'];

	                                          // - F
		$settlement_report['lender_total'] -= $settlement_report['past_due_subtotal'];

		//Quickly number format everything.  We can clean this up later if they get specific in their requirements
		foreach ($settlement_report as $column => $item)
		{
			$settlement_report[$column] = number_format($item,2,'.','');
		}
		
		$settlement_report['report_date'] = $report_date;

		//return records
		//Put the records in another array for file conversion.
		$report = array($settlement_report);
		return $report;
	}
	/**
	 * createSettlementDetails
	 * Creates the records for the settlement details report.  This function grabs records from createSettlement and
	 * process/formats them for the Settlement Details report
	 *
	 * @param date (YYY-MM-DD HH:MM:SS) $start_date - The starting period for this report set. (Typically the last time the report was run)
	 * @param date (YYY-MM-DD HH:MM:SS) $end_date - The ending period for this report set. (Typically the current timestamp)
	 * @return array $settlement_report the array of records for the Settlement Details report
	 */
	protected function createSettlementDetails($start_date,$end_date,$new_loans = false)
	{
		//get settlement records
		$settlement_details = $this->createSettlement($start_date,$end_date);

		$nlc_report = array();

		// Used so multiple fees aren't assessed for multiple transactions on a defaulted loan		
		$defaulted_apps = array();

		//We want to determine which apps are rolling over.  If the app is rolling over, their loan count on everything but the
		//renewal will be -1
		$renewing_apps = array();		
		foreach ($settlement_details as $settlement_record) 
		{
			if ($settlement_record['rollover_count'] > 1 && $settlement_record['transaction_type'] == 'cso_assess_fee_broker')
			{
				$renewing_apps[] = $settlement_record['application_id'];
			}
		}
		
		//process records
		$settlement_report = array();
		foreach ($settlement_details as $settlement_record)
		{
			// If we're seeing a service charge payment here, continue, we don't report that, we use the
			// assessment events
			if ($settlement_record['transaction_type'] == 'payment_service_chg')
				continue; 

			// We're turning assessments into payments for the purposes of this report
			if ($settlement_record['transaction_type'] == 'assess_service_chg')
			{
				// If they've defaulted, we don't want to count it twice
				if ($settlement_record['default_date'] != NULL)
					continue;

				$settlement_record['transaction_type'] = "payment_service_chg";
				$settlement_record['amount']           = -$settlement_record['amount'];
			}


			if (!in_array($settlement_record['application_id'], $defaulted_apps) && $settlement_record['default_date'] != NULL)
			{
				if ($settlement_record['loan_amount'] != 0)
				{
					// Add full principal to report
					$report_record = array();
					$report_record['loan_id']            = $settlement_record['application_id'];
					$report_record['transaction_date']   = $settlement_record['date_created_display'];
					$report_record['transaction_type']   = 'defaulted_principal';
					$report_record['transaction_amount'] = -$settlement_record['loan_amount'];
					$settlement_report[] = $report_record;
				}

				if ($settlement_record['interest_amount'] != 0)
				{
					// Add full interest to report
					$report_record = array();
					$report_record['loan_id']            = $settlement_record['application_id'];
					$report_record['transaction_date']   = $settlement_record['date_created_display'];
					$report_record['transaction_type']   = 'defaulted_interest';
					$report_record['transaction_amount'] = -$settlement_record['interest_amount'];
					$settlement_report[] = $report_record;
				}

				if ($settlement_record['ach_fee_amount'] != 0)
				{
					// If this hasn't been processed before, and they're a defaulted loan, add their lend_assess_fee_ach amounts
					// to the report
					$report_record = array();
					$report_record['loan_id']            = $settlement_record['application_id'];
					$report_record['transaction_date']   = $settlement_record['date_created_display']; // We're just making up dates anyways
					$report_record['transaction_type']   = 'defaulted_ach_fees';
					$report_record['transaction_amount'] = -$settlement_record['ach_fee_amount'];
					$settlement_report[] = $report_record;	
				}

				$defaulted_apps[] = $settlement_record['application_id'];
				
				continue;
			}
			
			if(in_array($settlement_record['application_id'],$defaulted_apps))
			{
				//We've already identified this app as defaulted! and assessed the appropriate fees!
				//Move along! Nothing to see here.
				continue;
			}
			// This doesn't go on the report like this
			if ($settlement_record['transaction_type'] == 'lend_assess_fee_ach' || $settlement_record['transaction_type'] == 'cso_assess_fee_late')
				continue;

			// Fake this report out to report cso assessment fees as loan refinancing.
			// We can detect a rollover (refinancing) by the CSO fee assessment
			// and the rollover count being more than 1. We never would report this
			// otherwise. Hackish, but consistent with the rest of CSO.
			if ($settlement_record['rollover_count'] > 1 && $settlement_record['transaction_type'] == 'cso_assess_fee_broker')
			{
				$settlement_record['amount']           = $settlement_record['loan_amount'];

		
				// Duplicate this with a negative amount to simulate CSO <-> Lender payments
				$report_record = array();
				
				$app_id = explode('-', $settlement_record['application_id']);
		
				$report_record['loan_id']            = $app_id[0] . "-" . ($app_id[1] - 1);
				$report_record['transaction_date']   = $settlement_record['date_created_display'];
				$report_record['transaction_type']   = 'loan_refinancing_payment';
				$report_record['transaction_amount'] = $settlement_record['amount'] * -1;
				$settlement_report[] = $report_record;

				$report_record['loan_id']            = $settlement_record['application_id'];
				$report_record['transaction_type']   = 'loan_refinancing';
				$report_record['transaction_amount'] = $settlement_record['amount'];
				$settlement_report[] = $report_record;

				// We're in a rollover, give the customer a new record with the New Loan and Customer report
				$nlc_report[] = array('customer_id'      => $settlement_record['customer_id'],
									  'last_name'        => $settlement_record['last_name'],
									  'first_name'       => $settlement_record['first_name'],
									  'address'          => $settlement_record['address'],
									  'city'             => $settlement_record['city'],
									  'state'            => $settlement_record['state'],
									  'zip_code'         => $settlement_record['zip_code'] ,
									  'phone_number'     => $settlement_record['phone_number'],
									  'date_of_birth'    => $settlement_record['date_of_birth'],
									  'ssn'              => $settlement_record['ssn'],
									  'loan_id'          => $settlement_record['application_id'],
									  'funded_date'      => $settlement_record['funded_date'],
									  'loan_amount'      => $settlement_record['loan_amount'],
									  'next_due_date'    => $settlement_record['next_due_date']);
			
				continue;
			}

			if ($settlement_record['transaction_type'] == 'cso_assess_fee_broker')
				continue;

			unset($settlement_record['rollover_count']);

			$report_record = array();
			if(in_array($settlement_record['application_id'],$renewing_apps))
			{
				$app_id = explode('-', $settlement_record['application_id']);
				$report_record['loan_id']            = $app_id[0] . "-" . ($app_id[1] - 1);
			}
			else 
			{
				$report_record['loan_id'] = $settlement_record['application_id'];
			}
	
			if ($settlement_record['transaction_type'] == 'loan_disbursement')
			{
				// We're in a rollover, give the customer a new record with the New Loan and Customer report
				$nlc_report[] = array('customer_id'      => $settlement_record['customer_id'],
									  'last_name'        => $settlement_record['last_name'],
									  'first_name'       => $settlement_record['first_name'],
									  'address'          => $settlement_record['address'],
									  'city'             => $settlement_record['city'],
									  'state'            => $settlement_record['state'],
									  'zip_code'         => $settlement_record['zip_code'] ,
									  'phone_number'     => $settlement_record['phone_number'],
									  'date_of_birth'    => $settlement_record['date_of_birth'],
									  'ssn'              => $settlement_record['ssn'],
									  'loan_id'          => $settlement_record['application_id'],
									  'funded_date'      => $settlement_record['funded_date'],
									  'loan_amount'      => $settlement_record['loan_amount'],
									  'next_due_date'    => $settlement_record['next_due_date']);
			}



			$report_record['transaction_date'] = $settlement_record['date_created_display'];
			$report_record['transaction_type'] = $settlement_record['transaction_type'];
			$report_record['transaction_amount'] = $settlement_record['amount'];
			$settlement_report[] = $report_record;
		}

		// Now we need rescinded payments
		// Since we counted everything, including failures as passing for the purposes
		// of this report. We're going to need to query the transaction_history table
		// to find all failures within the given time period.
		// I also want to make sure they're not in the local defaulted apps array before
		// inserting them on the report.
		$rescinded_payments = $this->getRescindedPayments($start_date, $end_date);

		foreach($rescinded_payments AS $bad_pay)
		{
				
			$report_record = array();
			// We don't care about defaulted apps, they already got counted
			// Date created is the return date
			switch ($bad_pay['tt_name_short'])
			{
				case 'loan_disbursement':
					$report_record['loan_id']            = $bad_pay['application_id'];
					$report_record['transaction_date']   = $bad_pay['date_created_display'];
					$report_record['transaction_type']   = "loan_disbursement_failure";
					$report_record['transaction_amount'] = $bad_pay['amount'] * -1;

					break;
				// DO NOT REPORT CSO BROKER FEE PAYMENTS AS RESCINDED
				// DO NOT REPORT CSO BROKER FEES AS RESCINDED
				case 'cso_pay_fee_broker':
				case 'cso_assess_fee_broker':
					
					break;
				default:
					$report_record['loan_id']            = $bad_pay['application_id'];
					$report_record['transaction_date']   = $bad_pay['date_created_display'];
					$report_record['transaction_type']   = $bad_pay['tt_name_short'] . "_returned";
					$report_record['transaction_amount'] = $bad_pay['amount'] * -1;

					break;
			}
			if(!empty($report_record))
			{	
				$settlement_report[]                 = $report_record;
			}

		}

		//return records
		if($new_loans)
		{
			return $nlc_report;
		}
		return $settlement_report;
	}

	/**
	 * getRescindedPayments
	 *
	 * @param date (YYY-MM-DD HH:MM:SS) $start_date - The starting period for this report set. (Typically the last time the report was run)
	 * @param date (YYY-MM-DD HH:MM:SS) $end_date - The ending period for this report set. (Typically the current timestamp)
	 * @return array $results The results of this query
	 */
	protected function getRescindedPayments($start_date,$end_date)
	{
		//List of loan types we want to include in this report
		$rule_sets = implode(',',$this->cso_loan_types);

		$query = "
			SELECT
				th.application_id                        AS application_id,
				DATE_FORMAT(th.date_created, '%m/%d/%Y') AS date_created_display,
				DATE_FORMAT(th.date_created, '%m/%d/%Y %H:%i:%s') AS date_created,
				th.status_before                         AS status_before,
				th.status_after                          AS status_after,
				SUM(IF(eat.name_short='principal',ea.amount,0)) AS amount,
				tr.date_effective                        AS date_effective,
				tt.name_short                            AS tt_name_short,
				tt.name                                  AS tt_name,
				es.context								AS context,
				arc.is_fatal							AS is_fatal,

				(
					SELECT
						COUNT(*)+1
					FROM
						transaction_register itr
					JOIN
						transaction_type itt ON (itt.transaction_type_id = itr.transaction_type_id)
					WHERE
						itt.name_short = 'cso_assess_fee_broker'
					AND
						itr.application_id = app.application_id
					AND
						itr.date_effective >= (
							SELECT
								transaction_register.date_effective
							FROM
								transaction_register
							JOIN
								transaction_type ON (transaction_type.transaction_type_id = transaction_register.transaction_type_id)
							WHERE
								transaction_register.application_id = itr.application_id
							AND
								transaction_type.name_short = 'loan_disbursement'
							AND
								transaction_status != 'failed'
							AND
								transaction_register.date_effective <= '{$end_date}'
							ORDER BY
								transaction_register.date_created DESC
							LIMIT 1
						)
				)                                        AS rollover_count,
				(
					SELECT
						DATE_FORMAT(otr.date_created, '%m/%d/%Y %H:%i:%s')
					FROM
						transaction_register otr
					JOIN
						transaction_type ott ON (ott.transaction_type_id = otr.transaction_type_id)
					WHERE
						otr.application_id = th.application_id
					AND
						ott.name_short = 'cso_assess_fee_broker'
					AND
						otr.date_created <= '{$end_date}'
                    ORDER BY 
						otr.date_created DESC
                    LIMIT 1
				)                                                         AS last_rollover_date,
				(
					SELECT
						ash.date_created
					FROM
						status_history ash
					WHERE
						ash.application_id = th.application_id
					AND
						ash.application_status_id = (select application_status_id FROM application_status_flat WHERE level0 = 'default')
					AND
						ash.date_created <= '{$end_date}'
				)                                                         AS default_date
			FROM
				transaction_history th
			JOIN
				transaction_register tr ON (tr.transaction_register_id = th.transaction_register_id)
			JOIN
				transaction_type tt ON (tt.transaction_type_id = tr.transaction_type_id)
			/* Need to join the application table to verify this is a CSO loan */
			JOIN
				application app ON app.application_id = th.application_id
			JOIN
				event_amount ea ON ea.transaction_register_id = tr.transaction_register_id AND tr.event_schedule_id = ea.event_schedule_id
			JOIN
				event_amount_type eat ON eat.event_amount_type_id = ea.event_amount_type_id
			JOIN
				event_schedule es ON es.event_schedule_id = tr.event_schedule_id
			LEFT JOIN
				ach ON ach.ach_id = tr.ach_id
			LEFT JOIN 
				ach_return_code arc ON arc.ach_return_code_id = ach.ach_return_code_id
			WHERE
				tr.transaction_status = 'failed'
			AND
                tr.company_id = {$this->company_id}
            AND
                app.rule_set_id IN ({$rule_sets})
			AND
				th.date_created BETWEEN '{$start_date}' and '{$end_date}'
			AND
				th.status_after = 'failed'
			AND
				eat.name_short = 'principal'
			GROUP BY tr.transaction_register_id
		";

		//run query
		$result = $this->db->Query($query);

		$results = array();
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			// Hax! Welcome to CSO.
			$row['application_id'] .= '-' . $row['rollover_count'];
			
			//Because this information is used in a couple places, and both places have the EXACT SAME REQUIREMENTS BECAUSE THEY
			//ARE REPORTING ON THE EXACT SAME DATA, I'm putting the requirements for what we're displaying/not displaying here [W!-12-16-2008][#22506]
			if(($row['default_date'] == NULL) || strtotime($row['default_date']) >= strtotime($row['date_created']))
			{
				//We ALWAYS rescind disbursements!
				if($row['tt_name_short'] != 'loan_disbursement')
				{
					//We don't rescind fatal returns
					if(strtoupper($row['is_fatal']) == 'YES')
					{
						continue;
					}
					//We don't rescind items that were created before, but have returned after the last rollover date
					if(strtotime($row['date_effective'] < strtotime($row['last_rollover_date'] && strtotime($row['last_rollover_date']) < $row['date_created'])))
					{
						continue;
					}
					//We don't rescind generated payments
					if($row['context'] == 'generated')
					{
						continue;
					}
				}
				// If you thought that last piece of code was a hack, check out this hacktacular feat of unabashed, unrestrained, hacktamonium
				$results[] = $row;
			}
		}

		//return results
		return $results;
	}


	/**
	 * createSettlement
	 * 
	 *
	 * @param date (YYY-MM-DD HH:MM:SS) $start_date - The starting period for this report set. (Typically the last time the report was run)
	 * @param date (YYY-MM-DD HH:MM:SS) $end_date - The ending period for this report set. (Typically the current timestamp)
	 * @return array $results The results of this query
	 */
	protected function createSettlement($start_date,$end_date)
	{
		//hardcoded list of transaction types we're interested in reporting
		$lender_items = array(
								'loan_disbursement',
							//	'cso_assess_fee_late',
								'lend_assess_fee_ach',
								'cso_assess_fee_broker',
								'assess_service_chg' 
								);
		//hardcoded list of transaction types we're interested in reporting if it isn't a reattempt
		$lender_items_original = array('repayment_principal', 'payment_arranged_princ', 'adjustment_internal_princ', 'payment_manual_princ', 'money_order_princ', 'western_union_princ', 'credit_card_princ', 'personal_check_princ', 'refund_3rd_party_princ', 'refund_princ', 'adjustment_internal_princ', 'payment_service_chg', 'paydown', 'payout', 'payout_principal', 'debt_writeoff_princ', 'moneygram_princ','cancel_principal');

		//List of loan types we want to include in this report
		$rule_sets = implode(',',$this->cso_loan_types);
		
		$items = implode("', '",$lender_items);
		$original_items = implode("', '",$lender_items_original);

		// generate query 
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			SELECT
				ap.customer_id                                            AS customer_id,
		    	ap.name_last                                              AS last_name,
			    ap.name_first                                             AS first_name,
			    ap.street                                                 AS address,
				ap.city                                                   AS city,
		    	ap.state                                                  AS state,
			    ap.zip_code                                               AS zip_code,
				tr.application_id                                         AS application_id,
				ap.application_status_id                                  AS application_status_id,
				DATE_FORMAT(tr.date_created, '%m/%d/%Y')                  AS date_created_display,
				DATE_FORMAT(tr.date_created, '%m/%d/%Y %H:%i:%s')		  AS date_created,
				tr.date_created											  AS date_comparison,
				tt.name_short                                             AS transaction_type,
				tt.name                                                   AS name,
				tr.amount                                                 AS amount,
				es.origin_id,
				CONCAT(
					'(',
					SUBSTRING(ap.phone_home, 1, 3),
					') ',
					SUBSTRING(ap.phone_home, 4, 3),
					'-',
					SUBSTRING(ap.phone_home, 7, 4)
				)                                                         AS phone_number,
			    DATE_FORMAT(ap.dob, '%m/%d/%Y') AS date_of_birth,
				/* They apparently only want 4 characters of the SSN
				CONCAT(
					SUBSTRING(ap.ssn, 1, 3),
					'-',
					SUBSTRING(ap.ssn, 4, 2),
					'-',
					SUBSTRING(ap.ssn, 6, 4)
				)                                                         AS ssn,
				*/
				ap.ssn AS ssn,
				ap.encryption_key_id,
				ap.application_id                                         AS loan_id,
			    DATE_FORMAT(tr.date_created, '%m/%d/%Y %H:%i:%s')         AS funded_date,
				(
					SELECT
						COUNT(*)+1
					FROM
						transaction_register itr
					JOIN
						transaction_type itt ON (itt.transaction_type_id = itr.transaction_type_id)
					WHERE
						itt.name_short = 'cso_assess_fee_broker'
					AND
						itr.application_id = ap.application_id
					AND
						itr.date_effective >= (
							SELECT
								transaction_register.date_effective
							FROM
								transaction_register
							JOIN
								transaction_type ON (transaction_type.transaction_type_id = transaction_register.transaction_type_id)
							WHERE
								transaction_register.application_id = itr.application_id
							AND
								transaction_type.name_short = 'loan_disbursement'
							AND
								transaction_status != 'failed'
							AND 
								transaction_register.date_effective <= '{$end_date}'
							ORDER BY
								transaction_register.date_created DESC
							LIMIT 1
						)
					AND
						itr.date_effective <= '{$end_date}'
				)                                                         AS rollover_count,
				(
					SELECT
						DATE_FORMAT(ash.date_created, '%m/%d/%Y %H:%i:%s')
					FROM
						status_history ash
					WHERE
						ash.application_id = ap.application_id
					AND
						ash.application_status_id = (select application_status_id FROM application_status_flat WHERE level0 = 'default')
					AND
						ash.date_created <= '{$end_date}'
				)                                                         AS default_date,
				(
					SELECT
						ash.date_created
					FROM
						status_history ash
					WHERE
						ash.application_id = ap.application_id
					AND
						ash.application_status_id = (select application_status_id FROM application_status_flat WHERE level0 = 'default')
					AND
						ash.date_created <= '{$end_date}'
				)                                                         AS default_comparison,
				(
					SELECT
						DATE_FORMAT(otr.date_created, '%m/%d/%Y %H:%i:%s')
					FROM
						transaction_register otr
					JOIN
						transaction_type ott ON (ott.transaction_type_id = otr.transaction_type_id)
					WHERE
						otr.application_id = ap.application_id
					AND
						ott.name_short = 'loan_disbursement'
					AND
						otr.transaction_status = 'failed'
					AND
						otr.date_created <= '{$end_date}'
					ORDER BY
						otr.date_created DESC
					LIMIT 1
				)                                                         AS funding_failed_date,
				(
					SELECT
						DATE_FORMAT(otr.date_created, '%m/%d/%Y %H:%i:%s')
					FROM
						transaction_register otr
					JOIN
						transaction_type ott ON (ott.transaction_type_id = otr.transaction_type_id)
					WHERE
						otr.application_id = ap.application_id
					AND
						ott.name_short = 'loan_disbursement'
					AND
						otr.date_created <= '{$end_date}'
					ORDER BY
						otr.date_created DESC
					LIMIT 1
				)                                                         AS loan_start_date,
				(
					SELECT
						DATE_FORMAT(otr.date_created, '%m/%d/%Y %H:%i:%s')
					FROM
						transaction_register otr
					JOIN
						transaction_type ott ON (ott.transaction_type_id = otr.transaction_type_id)
					WHERE
						otr.application_id = ap.application_id
					AND
						ott.name_short = 'cso_assess_fee_broker'
					AND
						otr.date_created <= '{$end_date}'
                    ORDER BY 
						otr.date_created DESC
                    LIMIT 1
				)                                                         AS last_rollover_date,
				(
					(
						SELECT
							amount
						FROM
							transaction_register otr
						JOIN
							transaction_type ott ON (ott.transaction_type_id = otr.transaction_type_id)
						WHERE
							ott.name_short = 'loan_disbursement'
						AND
							otr.application_id = ap.application_id
						AND
							otr.date_created <= '{$end_date}'
						LIMIT 1
					)
					+
					COALESCE((
						SELECT
							SUM(amount)
						FROM
							transaction_register otr
						JOIN
							transaction_type ott ON (ott.transaction_type_id = otr.transaction_type_id)
						JOIN
							event_schedule oes ON (oes.event_schedule_id = otr.event_schedule_id)
						WHERE
							ott.name_short IN ('repayment_principal')
						AND
							otr.application_id = ap.application_id
						AND	
							oes.origin_id IS NULL
						AND 
							otr.date_created <= '{$end_date}'
					),0)
		    	)                                                         AS loan_amount,
				(
					(
						SELECT
							SUM(amount)
						FROM
							transaction_register otr
						JOIN
							transaction_type ott ON (ott.transaction_type_id = otr.transaction_type_id)
						WHERE
							ott.name_short = 'assess_service_chg'
						AND
							otr.application_id = ap.application_id
						AND
							otr.date_created <= '{$end_date}'
					)
					+
					COALESCE((
						SELECT
							SUM(amount)
						FROM
							transaction_register otr
						JOIN
							transaction_type ott ON (ott.transaction_type_id = otr.transaction_type_id)
						JOIN
							event_schedule oes ON (oes.event_schedule_id = otr.event_schedule_id)
						WHERE
							ott.name_short IN ('payment_service_chg')
						AND
							otr.application_id = ap.application_id
						AND	
							oes.origin_id IS NULL
						AND
							otr.date_created <= '{$end_date}'
					),0)
		    	)                                                         AS interest_amount,
				(
					SELECT
						SUM(amount)
					FROM
						transaction_register otr
					JOIN
						transaction_type ott ON (ott.transaction_type_id = otr.transaction_type_id)
					WHERE
						ott.name_short = 'lend_assess_fee_ach'
					AND
						otr.application_id = ap.application_id
					AND 
						otr.date_created <= '{$end_date}'
		    	)                                                         AS ach_fee_amount
			FROM 
				transaction_register tr
			JOIN 
				application ap ON ap.application_id = tr.application_id
			JOIN 
				transaction_type tt ON tt.transaction_type_id = tr.transaction_type_id
			JOIN 
				event_schedule es ON es.event_schedule_id = tr.event_schedule_id
			WHERE
				tr.company_id = {$this->company_id}
			AND
				ap.rule_set_id IN ({$rule_sets})
			AND
		    	tr.date_created BETWEEN {$this->db->quote($start_date)} AND {$this->db->quote($end_date)}
		    AND
		    	(
		    		tt.name_short IN ('{$items}') OR 
		    		(tt.name_short IN ('{$original_items}') AND es.origin_id IS NULL)
		    	)
		    HAVING 
		    	(
		    		(default_date IS NULL
		    	 	OR
		    		date_comparison <= default_comparison)
		    		OR
		    		tt.name_short = 'lend_assess_fee_ach'
		    	)
		";		

		//run query
		$result = $this->db->Query($query);

		$crypt = new ECash_Models_Encryptor(ECash::getMasterDb());

		$results = array();
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			// Get their next due date
			$application =  ECash::getApplicationByID($row['loan_id']);
			$schedule = Fetch_Schedule($row['loan_id']);
			$status = Analyze_Schedule($schedule,true);

			$row['ssn'] = substr($crypt->decrypt($row['ssn'],$row['encryption_key_id']), -4, 4);
			unset($row['encryption_key_id']);

			$row['next_due_date'] = $status->next_due_date;

			// Hack! Welcome to CSO.
			$row['application_id'] .= '-' . $row['rollover_count'];

			// If you thought that last piece of code was a hack, check out this hacktacular feat of unabashed, unrestrained, hacktamonium
			$results[] = $row;
		}
		//return results
		return $results;
	}
	
	/**
	 * createLoanAndCustomer
	 * Generates the report for the Loan and Customer Table report.  This gets a list of applications that have a fund event within
	 * the specified period.
	 *
	 * @param date (YYY-MM-DD HH:MM:SS) $start_date - The starting period for this report set. (Typically the last time the report was run)
	 * @param date (YYY-MM-DD HH:MM:SS) $end_date - The ending period for this report set. (Typically the current timestamp)
	 * @return array $results the results of this query
	 */
	// Added rollover count, city, and separated the date and time fields, formatted the phone, and ssn fields
	// Added tracking principal payments to adjust the "new" loan amount.
	// This seems to work alright, even with spec changes, so I'm leaving it alone.
	// This query is ugly
	protected function createLoanAndCustomer($start_date,$end_date)
	{
		
		$nlc_report = $this->createSettlementDetails($start_date,$end_date,true);
		
		
		//return results
		return $nlc_report;
	}
	
	/**
	 * createXml
	 * Creates an XML file based on the resultset provided.
	 * I have absolutely no idea what this file is supposed to look like, because there is no spec for it.
	 * @param array $records the records used to populate the report
	 * @param string $filename the name to use for the file
	 * @param string $template the template to use for generating this document
	 * @return file $file the XML file that was created.
	 */
	protected function createXml($records,$filename,$template = null)
	{
		$this->log->Write("Creating XML file for {$filename}");
		//get the file to put it in
		$file = '/tmp/'.$filename.'.xml';
		
		$fp = fopen($file, 'w');
		//start writing the file
		fwrite($fp,"<xml_file>\n");
		fwrite($fp,"<edittable>false</edittable>\n");
		fwrite($fp,"<secure>true</secure>\n");
		for($i = 0; $i < count($records); $i++)
		{
			$record = $records[$i];
			fwrite($fp,'<record>');
			foreach ($record as $column =>$data)
			{
				fwrite($fp, "<{$column}>{$data}</{$column}>");
			}
			fwrite($fp,'</record>');
			fwrite($fp, "\r\n");	
		}
		fwrite($fp,"</xml_file>");
		
		fclose($fp);
		$this->log->Write("XML file successfully created");
		return $file;
	}
	
	
	/**
	 * createCsv
	 * Creates an CSV file based on the resultset provided.
	 * @param array $records the records used to populate the report
	 * @param string $filename the name to use for the file
	 * @param string $template the template to use for generating this document
	 * @return file $file the CSV file that was created.
	 */
	protected function createCsv($records,$filename,$template = null)
	{
		$this->log->Write("Creating CSV file for {$filename}");
		$file = '/tmp/'.$filename.'csv';
		$fp = fopen($file, 'w');

		// Get the headers
		if (isset($records[0]))
		{
			$buffer = "";

			foreach (array_keys($records[0]) as $key)
			{
				$buffer .= str_replace(',', '\,', $key);
				$buffer .= ",";
			}

			$buffer = substr($buffer, 0, -1) . "\n";

			fwrite($fp, $buffer);
		}
		
		for($i = 0; $i < count($records); $i++)
		{
			$j = 0;
			$record = $records[$i];
			foreach ($record as $element)
			{
				fwrite($fp, str_replace(',', '\,', $element));
				if ($j < (count($record)-1)) 
				{
					fwrite($fp,',');
				}
				$j++;
			}
			if ($i < (count($records)-1)) 
			{
				fwrite($fp, "\r\n");	
			}
		}
		
		fclose($fp);

		$this->log->Write("CSV file successfully created");
		return $file;
	}
	
	
	/**
	 * createPdf
	 * Creates a PDF file based on the resultset provided. PDF files are generated by creating an HTML file based off of the provided
	 * template.  The generated HTML file is then converted to a PDF file.
	 * 
	 * @param array $records the records used to populate the report
	 * @param string $filename the name to use for the file
	 * @param string $template the HTML template to use for generating this document 
	 * @return file $file the PDF file that was created.
	 */
	protected function createPdf($records,$filename,$template)
	{
		$this->log->Write("Creating PDF file for {$filename} using template: {$template}");

		//Create HTML
		$html_report = $this->createHtml($records,$filename,$template);

		//Process HTML and turn it into a PDF
		$contents = file_get_contents($html_report);
        
        $file = '/tmp/'.$filename.'.pdf';
	    $descriptor_spec = array(
	        0 => array('pipe', 'r'),
	        1 => array('pipe', 'w'),
	        2 => array('file', '/dev/null', 'a') // Errors will go into oblivion (for now)
        );
        
		  $pipes = array();
        
        $cmd = 'htmldoc --quiet --no-embed --gray --webpage --bodyfont helvetica --footer ... -t pdf12 -';
        $filter = proc_open($cmd, $descriptor_spec, $pipes);
        
        if(is_resource($filter))
        {
            // Write the contents to HTMLDOC
            fwrite($pipes[0], $contents);
            fclose($pipes[0]);
            
            // Get the return
            $ret_val = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            
            proc_close($filter);
        }
		file_put_contents($file, $ret_val);        

		$this->log->Write("PDF Successfully created");
		//Return PDF
		return $file;
	}

	/**
	 * createHtml
	 * Creates a HTML file based on the resultset and template provided. 
	 * 
	 * @param array $records the records used to populate the report
	 * @param string $filename the name to use for the file
	 * @param string $template the HTML template to use for generating this document 
	 * @return file $file the HTML file that was created.
	 */
	protected function createHtml($records,$filename,$template)
	{
		$this->log->Write("Creating HTML for {$filename} with template {$template}");
		
		$file = '/tmp/'.$filename.'html';
		$fp = fopen($file, 'w');
		$template_file = file_get_contents($template);
		//For each set of records, populate the template with the records
		foreach ($records as $key => $value) 
		{
			$data .= Display_Utility::Token_Replace($template_file,$value);
		}
		//Write it to the file
		fwrite($fp,$data);
		
		fclose($fp);

		$this->log->Write("HTML Successfully created");
		return $file;
	}

	/**
	 * insertFile
	 * Inserts a compressed file for archival purposes.  This allows for later retrieval of the file using the corresponding sreport_id
	 * @param int $report_id the sreport_id to link this report data to.
	 * @param file $file the file that has the data from to insert into the table
	 * @param string $type the report type to label this report data with.  Current options are (it_settlement_summary, it_settlement_details, loan_customer_table)
	 * @param string $filename the name for the file being stored.  This is used when the file is retrieved.
	 * @param string $extension the file extension for the file being stored.  This is very important to ensure proper retrieval of the file.
	 * @return int $report_data->sreport_data_id the sreport_data_id of the newly inserted file
	 */
	protected function insertFile($report_id,$file,$type,$filename,$extension)
	{
		$this->log->Write("Inserting file:  report_id: {$report_id}, file: {$file}, type: {$type}, filename:{$filename}, extension:{$extension}");
		//Get new sreport_data model.
		$report_data = ECash::getFactory()->getModel('SreportData');
		
		//set sreport_id it belongs to
		$report_data->sreport_id = $report_id;

		//set the sreport_data for the file
		if(filesize($file))
		{
			$fp = fopen($file, 'r');
			$file_data = fread($fp,filesize($file));
			fclose($fp);
			$report_data->sreport_data = $file_data;
		}

		//set the filename
		$report_data->filename = $filename;

		//set the extension
		$report_data->filename_extension = $extension;

		//get the type id and insert the type.
		$report_data->sreport_data_type_id = ECash::getFactory()->getModel('SreportDataType')->getTypeId($type);

		//insert the record;
		$report_data->insert();
	
		$this->log->Write("File successfully inserted, sreport_data_id: {$report_data->sreport_data_id}");

		//return the id for the data record.
		return $report_data->sreport_data_id;
	}
	
	/**
	 * fetchReportsList
	 *
	 * Returns the list of sreport records.  There are currently no restrictions on the list available as the spec doesn't put any requirements
	 * on what reports should/shouldn't be available
	 * 
	 * @return array $results the list of reports currently in the database.  
	 */
	public function fetchReportsList($hide_obsolete = false, $start_date = "'1982-11-09'", $end_date = 'now', $search_field = 'sreport_date')
	{
		$my_start = date('Y-m-d', strtotime($start_date));
		$my_end   = date('Y-m-d', strtotime($end_date));

		$this->log->Write("Fetching Reports list for agent {$this->agent_id}");
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT DISTINCT
					sr.sreport_id,
					DATE_FORMAT(sr.date_created,       '%m/%d/%Y %H:%i:%s') AS date_created,
					DATE_FORMAT(sr.sreport_date,       '%m/%d/%Y')          AS sreport_date,
					DATE_FORMAT(sr.sreport_start_date, '%m/%d/%Y %H:%i:%s') AS sreport_start_date,
					DATE_FORMAT(sr.sreport_end_date,   '%m/%d/%Y %H:%i:%s') AS sreport_end_date,
					(
						/* We want the last successful send date */
						SELECT
							DATE_FORMAT(sssh.date_created, '%m/%d/%Y %H:%i:%s')
						FROM
							sreport_send_status_history sssh
						JOIN
							sreport_send_status sss
						WHERE
							sssh.sreport_id = sr.sreport_id
						AND
							sss.name_short = 'sent'
						ORDER BY
							date_created DESC
						LIMIT 1
					)                                                       AS sreport_last_send_date,
					srss.name as send_status,
					srs.name as status
				FROM
					sreport sr
				JOIN
					sreport_send_status srss ON srss.sreport_send_status_id = sr.sreport_send_status_id
				JOIN
					sreport_status srs ON srs.sreport_status_id = sr.sreport_status_id
				JOIN
					sreport_data ON sreport_data.sreport_id = sr.sreport_id
				JOIN
					sreport_type ON sr.sreport_type_id = sreport_type.sreport_type_id
				JOIN
					sreport_data_type ON sreport_data.sreport_data_type_id = sreport_data_type.sreport_data_type_id
				WHERE
				
					sreport_type.name_short IN ({$this->db->quote($this->report_type)})
				AND
					sr.sreport_date BETWEEN '{$my_start}' AND '{$my_end}'
		";
	
		if ($hide_obsolete === TRUE)
			$query .= "AND srs.name != 'Obsolete'\n";
		
		$query .= "
				ORDER BY sr.sreport_date DESC, sr.date_created DESC
		";
	
		$result = $this->db->Query($query);
		$count = $result->rowCount();
		$results = array();
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$results[] = $row;	
		}
		return $results;
		
	}

	/**
	 * getRuleSets
	 * Retrieves a list of rule_set_ids that have the specified value for the specified parameter
	 * 
	 * @param string $parm_name the rule component parameter that we're looking at to get a list of rule_set_ids
	 * @param string $parm_value the value of the rule component parameter that we're looking for when fetching a list of rule_set_ids
	 * @return array $rule_sets an array of rule_set_ids that have the specified value for the specified paramter.
	 */
	private function getRuleSets($parm_name, $parm_value)
	{
		$query = "
		-- eCash 3.5 : File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT 
				rs.rule_set_id
			FROM
				rule_set_component_parm_value rscpv
			JOIN
				rule_component_parm rcp ON rscpv.rule_component_parm_id = rcp.rule_component_parm_id
			JOIN
				rule_set rs ON rscpv.rule_set_id = rs.rule_set_id
			JOIN 
				rule_component rc ON rcp.rule_component_id = rc.rule_component_id
			WHERE 
				rcp.parm_name = '$parm_name'
			AND
				rscpv.parm_value = '$parm_value'
		";
		
		$result = $this->db->Query($query);
		$results = array();
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$rule_sets[] = $row['rule_set_id'];	
		}
		
		return $rule_sets;
	}
	
	public function getLastSettlementTime($status)
	{
		$this->log->Write("Retrieving last time Settlement report was run for agent {$this->agent_id}");

		// Get the most time that the settlement report was successfully run
		$query = "
					SELECT
						date_started as timestamp
					FROM
						process_log
					WHERE
						step			= 'it_settlement'
					AND	
						state			= {$this->db->quote($status)}
					AND 
						company_id		= {$this->company_id}
					ORDER BY
						date_started desc
					LIMIT 1
		";
		$result = $this->db->query($query);

		if ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$this->log->Write("Most recent timestamp is {$row['timestamp']}");
			return $row['timestamp'];
		}

		return false;
	}
}

?>
