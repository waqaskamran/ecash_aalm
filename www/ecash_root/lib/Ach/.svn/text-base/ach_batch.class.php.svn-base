<?php
require_once(LIB_DIR . "Ach/ach_batch_interface.iface.php");
require_once(LIB_DIR . "Ach/ach_utils.class.php");

/**
 * Abstract ACH Batch Class
 *
 */
abstract class ACH_Batch implements ACH_Batch_Interface 
{
	protected $log;
	protected $server;
	protected $db;
	
	protected $ach_utils;
	
	protected $ach_company_name;
	protected $ach_tax_id;
	protected $ach_company_id;
	protected $ach_report_company_id;
	protected $ach_credit_bank_aba;
	protected $ach_debit_bank_aba;
	protected $ach_credit_bank_acct;
	protected $ach_debit_bank_acct;
	protected $ach_credit_bank_acct_type;
	protected $ach_debit_bank_acct_type;
	protected $ach_phone_number;
	protected $file;
	protected $ach_filename;
	protected $ach_batch_model;
	
	protected $credit_count = 0;
	protected $credit_amount = 0;
	protected $debit_count = 0;
	protected $debit_amount = 0;
	
	protected $company_abbrev;
	protected $company_id;
	
	protected static $modifiers = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	protected $RS		  = "\n";		
	
	/**
	 * Used to determine whether or not the ACH batch file will contain
	 * both the credits and debits in one file or to send two separate
	 * files.
	 */
	protected $COMBINED_BATCH = FALSE;
	
	public function __construct(Server $server)
	{
		$this->server			= $server;
		$this->db = ECash::getMasterDb();
		$this->company_id		= $server->company_id;
		$this->company_abbrev	= strtolower($server->company);
		$this->log = ECash::getLog('ach');
		$this->ach_utils = new ACH_Utils($server);
		
		// Now grab a few company-based properties
		$this->ach_company_name			 = isset(ECash::getConfig()->ACH_COMPANY_NAME) ? ECash::getConfig()->ACH_COMPANY_NAME : ECash::getConfig()->COMPANY_NAME;
		$this->ach_phone_number			 = ECash::getConfig()->COMPANY_PHONE_NUMBER;
		$this->ach_company_id 			 = ECash::getConfig()->ACH_COMPANY_ID;
		$this->ach_report_company_id 	 = ECash::getConfig()->ACH_REPORT_COMPANY_ID;
		$this->ach_tax_id				 = ECash::getConfig()->ACH_TAX_ID;

		$this->ach_debit_bank_aba		 = ECash::getConfig()->ACH_DEBIT_BANK_ABA;
		$this->ach_debit_bank_acct		 = ECash::getConfig()->ACH_DEBIT_BANK_ACCOUNT_NUMBER;
		$this->ach_debit_bank_acct_type  = ECash::getConfig()->ACH_DEBIT_BANK_ACCOUNT_TYPE;

		$this->ach_credit_bank_aba 	 	 = ECash::getConfig()->ACH_CREDIT_BANK_ABA;
		$this->ach_credit_bank_acct 	 = ECash::getConfig()->ACH_CREDIT_BANK_ACCOUNT_NUMBER;
		$this->ach_credit_bank_acct_type = ECash::getConfig()->ACH_CREDIT_BANK_ACCOUNT_TYPE;
		
		$this->Initialize_Batch();
	}
	
	public function Initialize_Batch()
	{
		$this->ach_batch_id	= NULL;
		$this->clk_ach_id	= NULL;
		$this->file		= "";
		$this->rowcount		= 0;
		$this->blockcount	= 0;
		$this->clk_total_amount	= 0;

		$this->process_log_ids		= array();
		$this->clk_trace_numbers	= array();
		$this->customer_trace_numbers	= array();

	}
	
	public function Do_Batch($batch_type, $batch_date, $ach_provider_id) //asm 80
	{

		$this->batch_type	= strtolower($batch_type);
		$this->batch_date	= $batch_date;
		$this->ach_utils->Set_Batch_Date($batch_date);

		// If we are already inside of a transaction, bail out with error
		if ($this->db->getInTransaction())
		{
			throw new General_Exception("ACH: Cannot be invoked from within a transaction; this class manages its own transactions.");
		}

		$this->log->Write("ACH: Received request to run a {$batch_type} batch for date {$batch_date}");

		// Note start of ACH process
		$this->ach_utils->Set_Process_Status('ach_send', 'started');

		// Generate data for a new batch
		$result = $this->Create_Batch($ach_provider_id); //asm 80

		// Trap condition where there isn't anything to process and exit gracefully
		if (!$result)
		{
			$this->ach_utils->Set_Process_Status('ach_send', 'completed');

			return array(	'status'	=> 'no_data', 
							'batch_id'	=> NULL, 
							'ref_no'	=> NULL );
		}

		// Launch the batch into cyberspace
		$response = $this->Send_Batch();
		if ($response['status'] == 'sent')
		{
			// We're done
			$this->log->Write("ACH '{$this->batch_type}' batch {$this->ach_batch_id} prepared and sent for {$this->batch_date}.", LOG_INFO);
			$this->ach_utils->Set_Process_Status('ach_send', 'completed');

			return array(	'status'	=> 'sent', 
							'batch_id'	=> $this->ach_batch_id, 
							'ref_no'	=> (!empty($response['intercept']['REF']) ? $response['intercept']['REF'] : NULL),
							'db_amount'     => $response['intercept']['DA'],
							'db_count'      => $response['intercept']['DC'],
							'cr_amount'     => $response['intercept']['CA'],
							'cr_count'      => $response['intercept']['CC'] );
		}
		else if($response['status'] == 'created') //[#50910] using for save but no auto-send
		{
			$this->log->Write("ACH '{$this->batch_type}' batch {$this->ach_batch_id} created for {$this->batch_date}.", LOG_INFO);
			$this->ach_utils->Set_Process_Status('ach_send', 'completed');
			return array(	'status'	=> 'created', 
							'batch_id'	=> $this->ach_batch_id,
							'ref_no'	=> (!empty($response['intercept']['REF']) ? $response['intercept']['REF'] : NULL),
							'db_amount'     => $response['intercept']['DA'],
							'db_count'      => $response['intercept']['DC'],
							'cr_amount'     => $response['intercept']['CA'],
							'cr_count'      => $response['intercept']['CC'] );
		}		
		else
		{
			// Ahhh, shit
			$this->log->Write("ACH transmission of '{$this->batch_type}' batch {$this->ach_batch_id} for {$this->batch_date} has failed.", LOG_ERR);
			$this->ach_utils->Set_Process_Status('ach_send', 'failed');
			
			return array(	'status'	=> 'failed', 
							'batch_id'	=> $this->ach_batch_id, 
							'ref_no'	=> NULL );
		}
	}
	
	private function Create_Batch ($ach_provider_id) //asm 80
	{
		$today = date('Y-m-d');

		$closing_timestamp = $this->Get_Closing_Timestamp($today);
		if (!$closing_timestamp)
		{
			$this->log->Write("ACH: No batch close has been performed for today.", LOG_ERR);
			return false;
		} else {
			$this->log->Write("ACH: Using closing stamp of $closing_timestamp");
		}

		switch($this->batch_type)
		{
			case 'debit':
				$sign = "<";
				break;
			case 'credit':
				$sign = ">";
				break;
			default:
				$sign = "<>";
				break;
		}
		$ach_provider_id = intval($ach_provider_id); //asm 80
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT DISTINCT
						tr.transaction_register_id,
						tr.event_schedule_id,
						es.origin_group_id,
						tr.application_id,
						IF(tr.amount < 0, 'debit', 'credit') as ach_type,
						abs(tr.amount) as amount,
						tt.name_short as transaction_type,
						DATE_FORMAT(es.date_event,'%y%m%d') AS date_event,
						DATE_FORMAT(es.date_effective,'%y%m%d') AS date_effective,
						(CASE 
						WHEN ap.bank_account_type = 'checking' AND tr.amount < 0 THEN '27'
						WHEN ap.bank_account_type = 'checking' AND tr.amount > 0 THEN '22'
						WHEN ap.bank_account_type = 'savings' AND tr.amount < 0 THEN '37'
						WHEN ap.bank_account_type = 'savings' AND tr.amount > 0 THEN '32'
						ELSE '27'
						END) AS viking_tran_code
					FROM transaction_register tr
					JOIN event_schedule es ON (tr.event_schedule_id = es.event_schedule_id)
					JOIN transaction_type tt ON (tr.transaction_type_id = tt.transaction_type_id)
					JOIN application_ach_provider AS appr ON (appr.application_id = tr.application_id) -- asm 80
					JOIN application AS ap ON (ap.application_id = tr.application_id) -- asm 114
					-- LEFT JOIN application_flag AS af ON (af.application_id = ap.application_id AND af.flag_type_id = 7)
					WHERE
						appr.ach_provider_id = {$ach_provider_id}
						AND tt.clearing_type		= 'ach'
						AND tr.transaction_status	= 'new'
						AND tr.company_id      	=  {$this->company_id}
						AND tr.date_effective		<= '{$this->batch_date}'
						AND es.date_created		< '$closing_timestamp'
						AND tr.amount {$sign} 0
						-- AND af.application_flag_id IS NULL
					ORDER BY
						tr.date_effective, transaction_register_id
		";
		
		$result = $this->db->Query($query);
		$count = $result->rowCount();
		if ($count == 0)
		{
			$this->log->Write("ACH: No new transactions of type '{$this->batch_type}' were eligible for batch processing.", LOG_INFO);
			return FALSE;
		} else {
			$this->log->Write("ACH: Discovered {$count} transactions for processing.");
		}

		$transactions = array();
		$applications_ids = array();
		
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$transactions[] = $row;
			if(! isset($application_ids[$row['application_id']])) $application_ids[] = $row['application_id'];
		}

		/**
		 * So we've got our list of transactions, so let's try to grab the application info for it
		 */
		$application_data = ECash::getFactory()->getData('Application')->getApplicationData($application_ids);
		if(empty($application_data))
		{
			throw new Exception("Unable to retrieve Application Info for the ACH Batch.  Something is very wrong!");
		}

		/**
		 * Now iterate through the transactions and add acceptable apps to the combined data
		 */
		$combined_data = array();
		foreach($transactions as $event)
		{
			/**
			 * Get the application info out of the list
			 */
			if(isset($application_data[$event['application_id']]))
			{
				$app_info = $application_data[$event['application_id']];
			}
			else
			{
				$this->log->Write("Unable to locate application {$event['application_id']} in AppService!  This is very bad...");
				continue;
			}

			/**
			 * Ignore accounts in a 'Hold' status
			 */
			if($app_info['application_status'] == 'hold::servicing::customer::*root' ||
			   $app_info['application_status'] == 'hold::arrangements::collections::customer::*root')
			{
				continue;
			}

			/**
			 * Ignore apps with a No-ACH flag (Agean)
			 */
			if(ECash::getFactory()->getData('Application')->getFlag( 'cust_no_ach', $app_info['application_id']))
			{
				continue;
			}

			$combined_data[] = array_merge($event, $app_info);
		}

		unset($transactions);
		unset($application_ids);
		unset($application_data);
		unset($app_info);

		/**
		 * Finally, insert the ACH records & update the transactions
		 */

		// Get next ach_batch_id via skeletal insert for use in population of ach (and ach_company) rows
		$this->Get_Next_ACH_Batch_ID($ach_provider_id); //asm 80

		/**
		 * Determine if ACH Bundling is enabled based on the ach_bundling business rule [W!-02-03-2009][#23170]
		 */
		$biz_rules = new ECash_Business_Rules($this->db);
		$bundling_enabled = (strtolower(Company_Rules::Get_Config('ach_bundling')) == 'yes');
		$rule_sets = array();

		try
		{
			$ach_transaction_ary = array();

			$batch_date = strtotime($this->batch_date);

			// Loop through customer data
			foreach($combined_data as $row)
			{
				$rule_set_id = $row['rule_set_id'];
				if(! isset($rule_sets[$rule_set_id]))
				{
					$rule_sets[$rule_set_id] = $biz_rules->Get_Rule_Set_Tree($rule_set_id);
				}

				/**
				 * Agean sends an APR Change notification for some customers, so we have to perform the 
				 * check if we sent a loan disbursement.  (Note: This may be for CA loans only!)
				 */
				if(isset($rule_sets[$rule_set_id]['apr_change_notification']) && $rule_sets[$rule_set_id]['apr_change_notification'] &&
					$row['transaction_type'] == 'loan_disbursement')
				{
					// Cheesy way to normalize the dates.
					$date_fund_estimated = strtotime($row['date_fund_estimated']);
					if($batch_date > $date_fund_estimated)
					{
						//Compute new APR using batch_date & update the value in the db
						$new_apr = Update_APR($row['date_first_payment'], $this->batch_date, $row['fund_actual'], $row['application_id'], $row['loan_type_id'], $this->server->company);
						$this->log->Write("ACH: Sending APR Change Letter for AppID: {$row['application_id']} ({$new_apr})");
						//Send APR Change Letter
						ECash_Documents_AutoEmail::Queue_For_Send($row['application_id'], 'APR_CHANGE_LETTER');
					}
				}
				
				$this->db->beginTransaction();
				// Insert customer ach row as status='created'
				//We're now passing $bundling enabled to let it know whether we're bundling by origin_group_id or by application_id
				$ach_id = $this->ach_utils->Insert_ACH_Row('customer', $row, $bundling_enabled);
				
				$row['ach_id'] = $ach_id;
				
				$ach_transaction_ary[] = $row;
								
				// Mark transaction_register row as 'pending' and note the ach_id
				$this->ach_utils->Update_Transaction_Register($row['transaction_register_id'], $ach_id);

				$this->db->commit();
			}
			unset($combined_data);
			unset($rule_sets);

			// Group array by ach_id, summarizing transaction amount
			$ach_transaction_ary = $this->Array_Summarize_Amt_By_ACH_Id($ach_transaction_ary);
			
			// Prepare ach_company insert for offsetting CLK transaction
			$ach_insert['amount']				= 0;
			if($this->batch_type == 'credit') {
				$ach_insert['bank_account']			= $this->ach_credit_bank_acct;
				$ach_insert['bank_aba']				= $this->ach_credit_bank_aba;
				$ach_insert['bank_account_type']	= $this->ach_credit_acct_type;
				$ach_insert['ach_type']				= 'debit';
			} else {
				$ach_insert['bank_account']			= $this->ach_debit_bank_acct;
				$ach_insert['bank_aba']				= $this->ach_debit_bank_aba;
				$ach_insert['bank_account_type']	= $this->ach_debit_acct_type;
				$ach_insert['ach_type']				= 'credit';
			}

			// Create ACH File content
			$this->Build_ACH_File($ach_transaction_ary);
			
			// Create ACH file in /tmp on local filesystem
			$this->Create_Local_File();

			// Insert settlement ach_company row
			$this->db->beginTransaction();
			$this->clk_ach_id = $this->ach_utils->Insert_ACH_Row('company', $ach_insert, $bundling_enabled);

			// Mark customer ach rows as 'batched' while updating the ach_trace_number
			$blank_array = array();
			foreach ($ach_transaction_ary as $record)
			{
				$this->ach_utils->Update_ACH_Row('customer', $record['ach_id'], 'batched', $this->customer_trace_numbers[$record['ach_id']], null, null, $blank_array);
			}

			// Mark CLK ach row as 'batched' while updating the total amount and ach_trace_number
			$this->ach_utils->Update_ACH_Row('company', $this->clk_ach_id, 'batched', $this->clk_trace_numbers[$this->clk_ach_id], null, null, $blank_array);
			
			// Insert batch file content into existing ach_batch row,
			$this->Insert_ACH_Batch_File($this->file);
			$this->db->commit();
		}				
		catch(Exception $e)
		{
			$this->log->Write("ACH: Creation of batch {$this->ach_batch_id} failed and transaction will be rolled back.", LOG_ERR);
			$this->log->Write("ACH: No data recovery should be necessary after the cause of this problem has been determined.", LOG_INFO);
			if ($this->db->getInTransaction())
			{
				$this->db->rollback();
			}
			$this->ach_utils->Set_Process_Status('ach_send', 'failed');
			throw $e;
		}
		
		return true;
	}
	
	public function Resend_Failed_Batch($batch_id, $batch_date)
	{
		// Batch date will get re-established upon retrieval of the actual data for the passed batch_id
		//	we set here just for the sake of the initial process log entry
		$this->batch_date	= $batch_date;
		$this->ach_batch_id = $batch_id;
		
		// Note start of ACH resend process
		$this->ach_utils->Set_Process_Status('ach_resend', 'started');
		
		$this->log->Write("ACH: User has requested retransmission of batch {$this->ach_batch_id}.", LOG_INFO);
		
		// Get batch file content stored as a CLOB in the database
		$result = $this->Retrieve_Batch_File();
		
		if (!$result)
		{
			$this->log->Write("ACH: Batch {$this->ach_batch_id} does not exist or is not eligible for retransmission.", LOG_INFO);
			$this->ach_utils->Set_Process_Status('ach_resend', 'failed');
			return array(	'status'	=> 'failed', 
					'batch_id'	=> $this->ach_batch_id, 
					'ref_no'	=> NULL
					);
		}
		
		// Create ACH file in /tmp on local filesystem
		$this->Create_Local_File();
		
		// Attempt to launch the batch into cyberspace again
		$response = $this->Send_Batch();

		if ($response['status'] == 'sent')
		{
			// We're done
			$this->log->Write("ACH '{$this->batch_type}' batch {$this->ach_batch_id} was retransmitted for {$this->batch_date}.", LOG_INFO);
			$this->ach_utils->Set_Process_Status('ach_resend', 'completed');

			return array(	'status'	=> 'sent', 
					'batch_id'	=> $this->ach_batch_id, 
					'ref_no'	=> $response['intercept']['REF']
					);
		}
		else
		{
			// Ahhh, shit
			$this->log->Write("ACH retransmission of '{$this->batch_type}' batch {$this->ach_batch_id} for {$this->batch_date} has failed.", LOG_ERR);
			$this->ach_utils->Set_Process_Status('ach_resend', 'failed');
			
			return array(	'status'	=> 'failed', 
					'batch_id'	=> $this->ach_batch_id, 
					'ref_no'	=> NULL
					);
		}
		
	}

	protected function Send_Batch ()
	{
		$batch_login = ECash::getConfig()->ACH_BATCH_LOGIN;
		$batch_pass = ECash::getConfig()->ACH_BATCH_PASS;
		
		try {
			$transport_type   = ECash::getConfig()->ACH_TRANSPORT_TYPE;
			$transport_url    = ECash::getConfig()->ACH_BATCH_URL;
			$transport_server = ECash::getConfig()->ACH_BATCH_SERVER;
			$transport_port   = ECash::getConfig()->ACH_BATCH_SERVER_PORT;

			$transport = ACHTransport::CreateTransport($transport_type, $transport_server, $batch_login, $batch_pass, $transport_port);

			if (EXECUTION_MODE != 'LIVE' && $transport->hasMethod('setBatchKey')) 
			{
				$transport->setBatchKey(ECash::getConfig()->ACH_BATCH_KEY);
			}

			$batch_response = '';

			$remote_filename = $this->Get_Remote_Filename();
			$batch_success = $transport->sendBatch($this->ach_filename, $remote_filename, $batch_response);
		} catch (Exception $e) {
			$this->log->write($e->getMessage(),FILE_APPEND);
			$batch_response = '';
			$batch_success = false;
		}

		if ($batch_success) {
			$batch_status = 'sent';
		} else {

			$this->log->write("ACH file send: No response from '" . $remote_filename . "'.", LOG_ERR);
			$batch_status = 'failed';
		}

		/**
		 * SFTP/FTP/FTPS Responses are boolean and not a string like HTTPS.  This code will 
		 * populate the fields for the Batch History and Summary since they expect a string
		 * like this which is defined by Intercept.
		 */
		if($batch_response === TRUE)
		{
			// BC=1&DC=16194&CC=0&CA=1342686.75&DA=1342686.75&AC=0&FS=0&IC=0&REF=ECASH20061129.01&ER=0
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

			/*
			//asm 80
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
			*/
				$cc = $this->credit_count;
				$ca = floatval($this->credit_amount);
				$dc = $this->debit_count;
				$da = floatval($this->debit_amount);
			//}

			$batch_response = "BC=$bc&DC=$dc&CC=$cc&CA=$ca&DA=$da&AC=$ac&FS=%fs&IC=$ic&REF=$ref&ER=$er";
		}

		// Update response and corresponding status into ach_batch table,
		$this->Update_ACH_Batch_Response($batch_response, $batch_status);

		// Delete temp ACH file
		$this->Destroy_Local_File();

		// Set up return values
		$return_val = array();
		parse_str($batch_response, $return_val['intercept']);
		$return_val['status'] = $batch_status;

		return $return_val;
	}

	public function Get_Remote_Filename($batch_id = NULL) //asm 80
	{
		$transport_type   = ECash::getConfig()->ACH_TRANSPORT_TYPE;
		$transport_url    = ECash::getConfig()->ACH_BATCH_URL;
		
		if ($batch_id !== NULL)
		{
			$batch_model = ECash::getFactory()->getModel('AchBatch');
			$batch_model->loadBy(array('ach_batch_id' => $batch_id,));
			$ach_provider_id = $batch_model->ach_provider_id;

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
				$filename = "$transport_url/{$this->server->company}_{$this->batch_type}_" . date('Ymd') . "_" . $ach_provider_name . ".txt"; 
			} else {
				$filename = $transport_url;
			}

			return $filename;
	}
	
	public function Get_Filename_Download($batch_id = NULL)
	{
		$filename = $this->Get_Remote_Filename($batch_id);
		
		return $filename;
	}
	
	public function Check_Digit_DFI ($number)
	{
		$number = str_pad((string)$number, 8, '0', STR_PAD_LEFT);
		if (!preg_match("/^[0-9]{8,8}$/", $number))
		{
			return false;
		}

		$weights = array(3, 7, 1, 3, 7, 1, 3, 7);
		$result = 0;

		for($i = 0; $i < 8; $i++)
		{
			$result += $number[$i] * $weights[$i];
		}

		$result = (10 - ($result % 10)) % 10;

		return $result;
	}
	
	private function Array_Summarize_Amt_By_ACH_Id ($input_ary)
	{
		$output_ary = array();
		
		for ($i = 0; $i < count($input_ary); $i++)
		{
			if (array_key_exists($input_ary[$i]['ach_id'], $output_ary))
			{
				$output_ary[$input_ary[$i]['ach_id']]['amount'] += $input_ary[$i]['amount'];
			}
			else
			{
				$output_ary[$input_ary[$i]['ach_id']] = $input_ary[$i];
			}
		}
		
		return array_values($output_ary);
	}
	
	protected function Create_Local_File ()
	{

		$tmp_file_sfx = date("YmdHis") . $this->microseconds();
		$this->ach_filename = "/tmp/ecash_{$this->company_abbrev}_" . $tmp_file_sfx . ".ach";

		try
		{	
			$fh = fopen($this->ach_filename, 'w+'); 
			fwrite($fh, $this->file);
			fclose($fh);
		}
		catch(Exception $e)
			{
				throw $e;
			}
		
		return true;
	}

	protected function Destroy_Local_File ()
	{
		unlink($this->ach_filename);
		return true;
	}
	
	protected function microseconds()
	{
		list($usec, $sec) = explode(" ", microtime(false));
		$microseconds = (integer)((float)$usec * 1000000);
		return str_pad($microseconds, 6, '0', STR_PAD_LEFT);
	}

	/**
	 * Creates the ach_batch record and sets the ach_batch_id on the class
	 *
	 * @return boolean success
	 */
	private function Get_Next_ACH_Batch_ID ($ach_provider_id) //asm 80
	{
		$ach_batch = new ECash_Models_AchBatch($this->db);
		$ach_batch->date_created = time();
		$ach_batch->company_id = $this->company_id;
		$ach_batch->ach_provider_id = $ach_provider_id; //asm 80
		$ach_batch->batch_status = 'created';
		$ach_batch->ach_file_outbound = '';
		$ach_batch->remote_response = NULL;
		$ach_batch->batch_type = $this->batch_type;
		$ach_batch->save();
		$this->ach_batch_model = $ach_batch;
		$this->ach_batch_id = $ach_batch->ach_batch_id;
		$this->ach_utils->ach_batch_id = $this->ach_batch_id;

		return true;
	}

	/**
	 * Updates the ach_batch record with the file contents
	 *
	 * @param string $ach_file_content
	 * @return boolean success
	 */
	private function Insert_ACH_Batch_File (&$ach_file_content)
	{

		$this->ach_batch_model->ach_file_outbound = $ach_file_content;
		$this->ach_batch_model->save();

		return true;
	}

	/**
	 * Updates the ach_batch record
	 *
	 * @param string $remote_response
	 * @param string $status
	 * @return boolean success
	 */
	protected function Update_ACH_Batch_Response (&$remote_response, $status)
	{
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					UPDATE ach_batch
						SET
							remote_response	= ?,
							batch_status	= '$status'
						WHERE
							ach_batch_id = {$this->ach_batch_id}
		";
		
		$stmt = $this->db->Prepare($query);
		$stmt->bindParam(1, $remote_response, PDO::PARAM_STR);
		$stmt->execute();

		return true;
	}
	public function getBatchFile($batch_id)
	{
		$this->ach_batch_id = $batch_id;
		$this->Retrieve_Batch_File(false);
		return $this->file;
	}
	private function Retrieve_Batch_File ($is_failed = true)
	{

		if($is_failed)
		{
			$extra_sql = " AND ach_batch.batch_status	= 'failed'";
		}
		else
		{
			$extra_sql = ' ';
		}
		
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT
						ach_batch.ach_file_outbound,
						ach.ach_type as batch_type,
						ach.ach_date as batch_date
					FROM
						ach_batch,
						ach
					WHERE
							ach_batch.ach_batch_id	= ach.ach_batch_id
						AND ach_batch.ach_batch_id	= {$this->ach_batch_id}
						{$extra_sql}
					LIMIT 1
		";
		$result = $this->db->Query($query);
		
		if($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			if (strlen($row['ach_file_outbound']) > 0)
			{
				$this->batch_type = $row['batch_type'];
				$this->batch_date = $row['batch_date'];
				$this->file = $row['ach_file_outbound'];
				
				return true;
			}
		}

		return false;
	}
	
	protected function Get_Next_File_ID_Modifier()
	{
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT
						count(*) as count
					FROM
						process_log
					WHERE
						business_day	= DATE_SUB('{$this->batch_date}', INTERVAL 1 DAY)
					AND	step			= 'ach_send'
					AND company_id = {$this->company_id}
		";
		
		$result = $this->db->Query($query);
		$row = $result->fetch(PDO::FETCH_ASSOC);

		// Count must be at least one, because of process log entry for THIS run
		$idx = $row['count'] - 1;
		if($idx < 0) $idx = 0;
		
		if ($idx > 26)
		{
			throw new General_Exception("ACH: send invoked too many times for this business date ({$this->batch_date}). Do you know what you're doing?");
		}
		else 
		{
			$char = self::$modifiers[$idx];
		}
		
		$this->log->Write("Using File ID Modifier: {$char}\n");
		return $char;
	}
	
	public function Fetch_ACH_Batch_Stats ($start_date, $end_date)
	{
		$return_ary = false;
		$i = 0;
		//asm 80
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT
						b.date_created,
						b.date_modified,
						b.ach_batch_id,
						b.ach_provider_id,
						p.name AS ach_provider_name,
						b.remote_response,
						b.batch_status
					FROM
						ach_batch AS b
					JOIN
						ach_provider AS p USING (ach_provider_id)
					WHERE
						b.date_created BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
						AND b.company_id = {$this->company_id}
					ORDER BY
						b.ach_batch_id DESC
		";
		
		$result = $this->db->Query($query);
		
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			if ($i == 0)
			{
				$return_ary = array();
			}
			$return_ary[$i] = array();

			$row_response_ary			= array();
			$row_response_ary['BC']		= "";
			$row_response_ary['DC']		= "";
			$row_response_ary['CC']		= "";
			$row_response_ary['CA']		= "";
			$row_response_ary['DA']		= "";
			$row_response_ary['FS']		= "";
			$row_response_ary['ER']		= "";
			$row_response_ary['REF']	= "";
			
			foreach (explode('&', $row['remote_response']) as $parm_value_pair)
			{
				if (strpos($parm_value_pair, "=") > 0)
				{
					list($key, $value) = explode('=', $parm_value_pair);
					if ( !in_array($key, array('IC', 'AC')) )
					{
						$row_response_ary[$key] = $value;
					}
				}
			}
			
			$return_ary[$i]['batch_created']	= $row['date_created'];
			$return_ary[$i]['batch_sent']		= $row['date_modified'];
			$return_ary[$i]['batch_id']			= $row['ach_batch_id'];
			$return_ary[$i]['batch_status']		= $row['batch_status'];
			
			$return_ary[$i]['batch_count']		= $row_response_ary['BC'];
			$return_ary[$i]['credit_count']		= $row_response_ary['CC'];
			$return_ary[$i]['credit_amount']	= $row_response_ary['CA'];
			$return_ary[$i]['debit_count']		= $row_response_ary['DC'];
			$return_ary[$i]['debit_amount']		= $row_response_ary['DA'];
			$return_ary[$i]['filesize_bytes']	= $row_response_ary['FS'];
			$return_ary[$i]['error_code']		= $row_response_ary['ER'];
			$return_ary[$i]['intercept_refno']	= $row_response_ary['REF'];
			$return_ary[$i]['ach_provider_name']    = $row['ach_provider_name']; //asm 80
			
			$i++;
		}
		
		return $return_ary;
	}

	public function Preview_ACH_Batches ($batch_date)
	{
		$today = date('Y-m-d');

		$ach_event_id_ary = array();
		$ach_disbursement_ary = array();
		
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT DISTINCT 
					et.event_type_id 
				FROM 
					event_transaction et, 
					transaction_type tt 
				WHERE
						et.transaction_type_id	= tt.transaction_type_id 
					AND tt.clearing_type		= 'ach'
					AND tt.name_short NOT IN ('loan_disbursement','refund_fees','refund_princ')
					AND et.active_status = 'active'
					AND tt.company_id = {$this->company_id}
		";
		
		$result = $this->db->Query($query);
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$ach_event_id_ary[] = $row['event_type_id'];
		}
		
		if (count($ach_event_id_ary) == 0)
		{
			return false;
		}

		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT DISTINCT 
					et.event_type_id 
				FROM 
					event_transaction et, 
					transaction_type tt 
				WHERE
						et.transaction_type_id	= tt.transaction_type_id 
					AND tt.clearing_type		= 'ach'
					AND tt.name_short IN ('loan_disbursement','refund_fees','refund_princ')
					AND et.active_status = 'active'
					AND tt.company_id = {$this->company_id}
		";

		$result = $this->db->Query($query);
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$ach_disbursement_ary[] = $row['event_type_id'];
		}
		
		if (count($ach_disbursement_ary) == 0)
		{
			return false;
		}		

		$closing_timestamp = $this->Get_Closing_Timestamp($today);

		//asm 80
		$ach_providers = $this->getAchProviders(TRUE);
		if (count($ach_providers) > 0)
		{
			$ach_providers = array_keys($ach_providers);
			$ach_providers_query = " AND appr.ach_provider_id IN  (" . implode(',', $ach_providers) . ") ";
		}
		else
		{
			$ach_providers_query = "";
		}

		$return_ary = false;
		$i = 0;

		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT DISTINCT
					es.application_id,
					pr.name AS ach_provider_name,
					abs(es.amount_principal + es.amount_non_principal) as amount,
					es.amount_principal + es.amount_non_principal as real_amount,
					(
						CASE
					 		WHEN (es.amount_principal + es.amount_non_principal) < 0 THEN 'debit'
					 		ELSE 'credit'
					 	END
					 ) as ach_type,
					es.date_event as action_date,
					es.date_effective as due_date,
					et.name_short as event_name_short,
					et.name as event_name,
					es.company_id as application_company_id,
					es.company_id as event_company_id,
					es.event_schedule_id,
					es.origin_id,
					(select amount from event_amount where event_amount_type_id = 1 and event_schedule_id = es.event_schedule_id) as amount_principal,
					(select amount from event_amount where event_amount_type_id = 2 and event_schedule_id = es.event_schedule_id) as amount_interest,
					(select amount from event_amount where event_amount_type_id = 3 and event_schedule_id = es.event_schedule_id) as amount_fees
				FROM event_schedule es 
				JOIN event_type et using (event_type_id)
				JOIN application_ach_provider AS appr ON (appr.application_id = es.application_id) -- asm 80
				JOIN ach_provider AS pr USING (ach_provider_id)
				LEFT JOIN transaction_register tr ON (tr.event_schedule_id = es.event_schedule_id)
				WHERE
					(
						es.event_status			= 'scheduled'
						OR
						(es.event_status 		= 'registered'
						AND
						tr.transaction_status	= 'new')
					)
					AND es.company_id				=  {$this->company_id}
					AND es.event_type_id 	IN (" . implode(',', array_merge($ach_event_id_ary, $ach_disbursement_ary)) . ")
					AND es.date_event			<= '$today'
					AND es.date_effective		<= '$batch_date'
					AND es.date_created			< '$closing_timestamp'
					{$ach_providers_query}
				ORDER BY es.application_id DESC, es.event_schedule_id ASC";

		$result = $this->db->Query($query);

		$transactions = array();
		$applications_ids = array();
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$transactions[] = $row;
			if(! isset($application_ids[$row['application_id']])) $application_ids[] = $row['application_id'];
		}

		/**
		 * So we've got our list of transactions, so let's try to grab the application info for it
		 */
		$application_data = ECash::getFactory()->getData('Application')->getApplicationData($application_ids);
		if(empty($application_data) && !empty($application_ids))
		{
			throw new Exception("Unable to retrieve Application Info for the ACH Batch.  Something is very wrong!");
		}

		/**
		 * Now iterate through the transactions and add acceptable apps to the combined data
		 */
		$combined_data = array();
		foreach($transactions as $event)
		{
			/**
			 * Get the application info out of the list
			 */
			if(isset($application_data[$event['application_id']]))
			{
				$app_info = $application_data[$event['application_id']];
			}
			else
			{
				$this->log->Write("Unable to locate application {$event['application_id']} in AppService!  This is very bad...");
				continue;
			}

			/**
			 * Ignore accounts in a 'Hold' status
			 */
			if($app_info['application_status'] == 'hold::servicing::customer::*root' ||
			   $app_info['application_status'] == 'hold::arrangements::collections::customer::*root')
			{
				continue;
			}

			/**
			 * Ignore apps with a No-ACH flag (Agean)
			 */
			if(ECash::getFactory()->getData('Application')->getFlag( 'cust_no_ach', $app_info['application_id']))
			{
				continue;
			}

			$combined_data[] = array_merge($event, $app_info);
		}

		unset($transactions);
		unset($application_ids);
		unset($application_data);
		unset($app_info);

		foreach($combined_data as $row)
		{
			if ($i == 0)
			{
				$return_ary = array();
			}
			
			$return_ary[$i] = array();

			$return_ary[$i]['application_id']			= $row['application_id'];
			$return_ary[$i]['name']						= $row['name']; //"last, first"
			$return_ary[$i]['full_name']				= $row['name']; //"first last" for [#50910]
			$return_ary[$i]['bank_aba']					= $row['bank_aba'];
			$return_ary[$i]['bank_account']				= $row['bank_account'];
			$return_ary[$i]['bank_account_type']		= $row['bank_account_type'];
			$return_ary[$i]['amount']					= $row['amount'];
			$return_ary[$i]['ach_type']					= $row['ach_type'];
			$return_ary[$i]['action_date']				= $row['action_date'];
			$return_ary[$i]['due_date']					= $row['due_date'];
			$return_ary[$i]['real_amount']				= $row['real_amount'];
			$return_ary[$i]['event_name_short']			= $row['event_name_short'];
			$return_ary[$i]['event_name']				= $row['event_name'];
			$return_ary[$i]['application_company_id']	= $row['application_company_id'];
			$return_ary[$i]['event_company_id']			= $row['event_company_id'];	
			$return_ary[$i]['event_schedule_id']		= $row['event_schedule_id'];
			$return_ary[$i]['origin_id']				= $row['origin_id'];	
			$return_ary[$i]['amount_principal']			= $row['amount_principal'];
			$return_ary[$i]['amount_interest']			= $row['amount_interest'];
			$return_ary[$i]['amount_fees']				= $row['amount_fees'];
			$return_ary[$i]['ach_provider_name']                    = $row['ach_provider_name']; //asm 80
			$i++;
		}
		unset($combined_data);
		return $return_ary;
	}
	
	public function Get_Closing_Timestamp ($date_current)
	{
		// Get the most recent ACH closing timestamp for "current" date
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT 
						date_started as cutoff_timestamp
					FROM 
						process_log
					WHERE
							step			= 'ach_batchclose'
						AND	state			= 'completed'
						AND business_day	= '$date_current'
						AND company_id		= {$this->company_id}
					ORDER BY
						date_started desc
					LIMIT 1
		";

		$result = $this->db->Query($query);

		if ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			return $row->cutoff_timestamp;
		}
		
		return false;
	}

	public function Set_Closing_Timestamp ($date_current)
	{
		$this->batch_date = $date_current;
		
		$this->ach_utils->Set_Process_Status('ach_batchclose', 'started');
		$this->ach_utils->Set_Process_Status('ach_batchclose', 'completed');
		
		return true;
	}

	public function Has_Sent_ACH ($date_current)
	{
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT
					process_log_id
				FROM
					process_log
				WHERE
						step			= 'ach_send'
					AND state			= 'completed'
					AND business_day	= '$date_current'
					AND company_id		= {$this->company_id}
				LIMIT 1
		";

		$result = $this->db->Query($query);
		if ($result->fetch(PDO::FETCH_OBJ))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Changes the company this ach object is focused on
	 * ONLY FOR THE batch_review_report.  I didn't check if it completely changes
	 * the company for all features
	 * @author wmf
	 * @param integer $company_id
	 */
	public function Set_Company( $company_id )
	{
		$company_id = $this->db->quote($company_id);

		$short_name_query = "
			-- eCash 3.5: File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				c.name_short AS name
			 FROM
				company      AS c
			 WHERE
				c.company_id = $company_id
			";

		$short_name_result = $this->db->Query($short_name_query);

		if( $short_name_result->rowCount() !== 1 )
			throw new Exception( "Unrecognized company_id: {$company_id} in " . __METHOD__ . " of " . __FILE__ . ".", LOG_ERR );

		$line = $short_name_result->fetch(PDO::FETCH_OBJ);

		$this->company_abbrev = strtolower($line->name);
		$this->company_id     = $company_id;
	}
	
	public function Get_Company_Id()
	{
		return $this->company_id;
	}

	public function Get_Company_Abbrev()
	{
		return $this->company_abbrev;
	}
	
	public function PGP_Encrypt_Batch()
	{
		// there's a few variables needed for this to work.
		// ACH_BATCH_USE_PGP          --- used for deciding whether to do nothing or not, actually, this should be checked before this is called
		// ACH_SENDER                 --- used for deciding which key to digitally sign with
		// ACH_RECIPIENT              --- used to decide which public key to sign with
		// ACH_PRIVATE_KEY_PASSPHRASE --- Needed to use the private key
		if (!isset(ECash::getConfig()->ACH_BATCH_USE_PGP) ||
			!isset(ECash::getConfig()->ACH_SENDER)        ||
			!isset(ECash::getConfig()->ACH_RECIPIENT)     ||
			!isset(ECash::getConfig()->ACH_PRIVATE_KEY_PASSPHRASE))
		{
			throw new Exception('Could not encrypt batchfile, one or more of the following required config variables were not set: ACH_BATCH_USE_PGP, ACH_SENDER, ACH_RECIPIENT, PRIVATE_KEY_PASSPHRASE');
		}

		putenv('GNUPGHOME=' . CUSTOMER_CODE_DIR . '/' . CUSTOMER . '/Config/gpg_keyring/');

		try
		{
			// Create our GPG class
			$gpg = new gnupg();
		}
		catch (Exception $e)
		{
			// The gnupg extension is not properly linked in
			throw new Exception('Could not initialize GPG library. http://wiki.ecash-commercial.ept.tss/index.php/GPG');
		}

		$gpg->seterrormode(gnupg::ERROR_EXCEPTION);

		$recipient  = ECash::getConfig()->ACH_RECIPIENT;
		$sender     = ECash::getConfig()->ACH_SENDER;

		$passphrase = ECash::getConfig()->ACH_PRIVATE_KEY_PASSPHRASE;
	
		// the batch when this called is located in $this->ach_filename, of course it's in plaintext
		// I'm just going to get the contents of it, then put the new encrypted contents of it until
		// something better is proposed.
		$plaintext = file_get_contents($this->ach_filename);

		try 
		{
			$gpg->addencryptkey($recipient);
			$gpg->addsignkey($sender, $passphrase);
			$ciphertext = $gpg->encryptsign($plaintext);
		} 
		catch (Exception $e) 
		{
			throw new Exception('ERROR: ' . $e->getMessage());
		}
			
		// Now $ciphertext is the encrypted batch file, put it back before anyone notices
		file_put_contents($this->ach_filename, $ciphertext);

		return;
	}

	/**
	 * Helper method to get the COMBINED_BATCH flag from the class.
	 *
	 * @return bool
	 */
	public function useCombined()
	{
		return $this->COMBINED_BATCH;
	}
	
	//asm 80
	public function getAchProviders($batch_time = FALSE)
	{
		$batch_date = strtotime(date('Y-m-d'));
		$batch_date_getdate = getdate();

		$providers = array();
		$pr_model = ECash::getFactory()->getModel('AchProvider');
		$pr_array = $pr_model->loadAllBy(array('active_status' => 'active',));
		foreach ($pr_array as $pr)
		{
			//Batch Time
			if ($batch_time)
			{
				$config_model = ECash::getFactory()->getModel('AchProviderConfig');
				$config_model->loadBy(array('ach_provider_id' => $pr->ach_provider_id,
								'config_key' => 'ach_batch_time'
				));
				$ach_batch_time = $config_model->config_value;

				if ($ach_batch_time != "off")
				{
					$ach_batch_time = strtotime($ach_batch_time);

					if ($batch_date_getdate[0] < $ach_batch_time) continue;
				}
			}

			//Today's ACH Batch
			$ach_provider_id = intval($pr->ach_provider_id);
			$query = "
				SELECT ach_batch_id
				FROM ach_batch
				WHERE DATE(date_created) = CURDATE()
				AND batch_status = 'sent'
				AND ach_provider_id = {$ach_provider_id}
				ORDER BY ach_batch_id DESC
				LIMIT 1
			";
			$result = $this->db->Query($query);
			$row = $result->fetch(PDO::FETCH_OBJ);
			if (isset($row->ach_batch_id)) continue;

			//Event Dates, Weekdays
			$config_model = ECash::getFactory()->getModel('AchProviderConfig');
			$config_model->loadBy(array('ach_provider_id' => $pr->ach_provider_id,
						    'config_key' => 'ach_event_dates_weekdays'
			));
			$event_dates_weekdays = $config_model->config_value;
			
			if (!empty($event_dates_weekdays))
			{
				$event_dates_weekdays_array = explode(",", $event_dates_weekdays);
				$batch_date_weekday = $batch_date_getdate["weekday"];
				
				if (in_array($batch_date_weekday, $event_dates_weekdays_array))
					continue;
			}
			
			//Event Dates, Monthdays
			$config_model = ECash::getFactory()->getModel('AchProviderConfig');
			$config_model->loadBy(array('ach_provider_id' => $pr->ach_provider_id,
						    'config_key' => 'ach_event_dates_monthdays'
			));
			$event_dates_monthdays = $config_model->config_value;
			
			if (!empty($event_dates_monthdays))
			{
				$event_dates_monthdays_array = explode(",", $event_dates_monthdays);
				$batch_date_monthday = $batch_date_getdate["mday"];
				
				if (in_array($batch_date_monthday, $event_dates_monthdays_array))
					continue;
			}
			
			//Event Dates, Interval
			$event_dates_start = $event_dates_end = NULL;

			$config_model = ECash::getFactory()->getModel('AchProviderConfig');
			$config_model->loadBy(array('ach_provider_id' => $pr->ach_provider_id,
						    'config_key' => 'ach_event_dates_start'
			));
			$event_dates_start = $config_model->config_value;
			
			$config_model = ECash::getFactory()->getModel('AchProviderConfig');
			$config_model->loadBy(array('ach_provider_id' => $pr->ach_provider_id,
						    'config_key' => 'ach_event_dates_end'
			));
			$event_dates_end = $config_model->config_value;
			
			if (
				(!empty($event_dates_start))
				&& (!empty($event_dates_end))
			)
			{
				$event_dates_start = strtotime($event_dates_start);
				$event_dates_end = strtotime($event_dates_end);
				
				if (
					($batch_date >= $event_dates_start)
					&&
					($batch_date <= $event_dates_end)
				)
				{
					continue;
				}
			}
			
			//Add ach provider
			$providers[$pr->ach_provider_id] = $pr->name_short;
		}

		return $providers;
	}
	
	public function getAchProvidersConfig()
	{
		$query = "
			SELECT
				pr.date_created,
				pr.ach_provider_id,
				pr.name_short,
				pr.name,
				pr.active_status,
				pc_batch_type.config_value AS ach_batch_type,
				pc_c.config_value AS credit_percentage,
				pc_d.config_value AS debit_percentage,
				pc_fund_dates_start.config_value AS ach_fund_dates_start,
				pc_fund_dates_end.config_value AS ach_fund_dates_end,
				pc_states.config_value AS states,
				pc_app_statuses.config_value AS app_statuses,
				pc_ach_time.config_value AS ach_batch_time,
				pc_event_dates_weekdays.config_value AS ach_event_dates_weekdays,
				pc_event_dates_monthdays.config_value AS ach_event_dates_monthdays,
				pc_event_dates_start.config_value AS ach_event_dates_start,
				pc_event_dates_end.config_value AS ach_event_dates_end,
				pc_new_react.config_value AS ach_new_react,
				pc_failures.config_value AS failures
			FROM
				ach_provider AS pr
			LEFT JOIN
				ach_provider_config AS pc_c ON (pc_c.ach_provider_id = pr.ach_provider_id
				AND pc_c.config_key = 'credit_percentage')
			LEFT JOIN
				ach_provider_config AS pc_d ON (pc_d.ach_provider_id = pr.ach_provider_id
				AND pc_d.config_key = 'debit_percentage')
			LEFT JOIN
				ach_provider_config AS pc_ach_time ON (pc_ach_time.ach_provider_id = pr.ach_provider_id
				AND pc_ach_time.config_key = 'ach_batch_time')
			LEFT JOIN
				ach_provider_config AS pc_fund_dates_start ON (pc_fund_dates_start.ach_provider_id = pr.ach_provider_id
				AND pc_fund_dates_start.config_key = 'ach_fund_dates_start')
			LEFT JOIN
				ach_provider_config AS pc_fund_dates_end ON (pc_fund_dates_end.ach_provider_id = pr.ach_provider_id
				AND pc_fund_dates_end.config_key = 'ach_fund_dates_end')
			LEFT JOIN
				ach_provider_config AS pc_states ON (pc_states.ach_provider_id = pr.ach_provider_id
				AND pc_states.config_key = 'ach_states')
			LEFT JOIN
				ach_provider_config AS pc_app_statuses ON (pc_app_statuses.ach_provider_id = pr.ach_provider_id
				AND pc_app_statuses.config_key = 'ach_app_statuses')
			LEFT JOIN
				ach_provider_config AS pc_batch_type ON (pc_batch_type.ach_provider_id = pr.ach_provider_id
				AND pc_batch_type.config_key = 'ach_batch_type')
			LEFT JOIN
				ach_provider_config AS pc_event_dates_weekdays ON (pc_event_dates_weekdays.ach_provider_id = pr.ach_provider_id
				AND pc_event_dates_weekdays.config_key = 'ach_event_dates_weekdays')
			LEFT JOIN
				ach_provider_config AS pc_event_dates_monthdays ON (pc_event_dates_monthdays.ach_provider_id = pr.ach_provider_id
				AND pc_event_dates_monthdays.config_key = 'ach_event_dates_monthdays')
			LEFT JOIN
				ach_provider_config AS pc_event_dates_start ON (pc_event_dates_start.ach_provider_id = pr.ach_provider_id
				AND pc_event_dates_start.config_key = 'ach_event_dates_start')
			LEFT JOIN
				ach_provider_config AS pc_event_dates_end ON (pc_event_dates_end.ach_provider_id = pr.ach_provider_id
				AND pc_event_dates_end.config_key = 'ach_event_dates_end')
			LEFT JOIN
				ach_provider_config AS pc_new_react ON (pc_new_react.ach_provider_id = pr.ach_provider_id
				AND pc_new_react.config_key = 'ach_new_react')
			LEFT JOIN
				ach_provider_config AS pc_failures ON (pc_failures.ach_provider_id = pr.ach_provider_id
				AND pc_failures.config_key = 'ach_failures')
			ORDER BY pr.ach_provider_id
		";

		$db = ECash::getMasterDb();
		$st = $db->query($query);
		$ach_provider_list = $st->fetchAll(PDO::FETCH_OBJ);

		return $ach_provider_list;
	}

	public function clearApplicationAchProvider()
	{
		//$app_ach_provider_model = ECash::getFactory()->getModel('ApplicationAchProvider');
		//$app_ach_provider_model->delete();
		$query = "DELETE FROM application_ach_provider";
		ECash::getMasterDb()->exec($query);
	}

	public function assignApplicationsToAchProviders()
	{
		$this->clearApplicationAchProvider();

		$today = date('Y-m-d');
		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);
		$batch_date = $pdc->Get_Next_Business_Day($today);
		$closing_timestamp = $this->Get_Closing_Timestamp($today);

		$ach_event_id_ary = array();
		$ach_disbursement_ary = array();
				
		$query = "
		SELECT DISTINCT 
		et.event_type_id 
		FROM 
		event_transaction et, 
		transaction_type tt 
		WHERE
		et.transaction_type_id	= tt.transaction_type_id 
		AND tt.clearing_type = 'ach'
		AND tt.name_short NOT IN ('loan_disbursement','refund_fees','refund_princ')
		AND et.active_status = 'active'
		AND tt.company_id = {$this->company_id}
		";
		$result = $this->db->Query($query);
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$ach_event_id_ary[] = $row['event_type_id'];
		}

		$query = "
		SELECT DISTINCT 
		et.event_type_id 
		FROM 
		event_transaction et, 
		transaction_type tt 
		WHERE
		et.transaction_type_id= tt.transaction_type_id 
		AND tt.clearing_type = 'ach'
		AND tt.name_short IN ('loan_disbursement','refund_fees','refund_princ')
		AND et.active_status = 'active'
		AND tt.company_id = {$this->company_id}
		";
		$result = $this->db->Query($query);
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$ach_disbursement_ary[] = $row['event_type_id'];
		}

		//////////////////////////////////////////////////////////// Distribute
		$ach_providers = $this->getAchProviders();
		$map_short_names_provider_ids = array_flip($ach_providers);
		$ach_providers_id = array_keys($ach_providers);
		$number_of_active_providers = count($ach_providers_id);
		$ach_providers_name_short = array_values($ach_providers);

		//States, New/React, App Statuses, Failures
		$state_array = array();
		$app_status_array = array();
		$new_react_array = array();
		$failure_array = array();
		$fund_dates_start_array = array();
		$fund_dates_end_array = array();

		$ach_providers_config = $this->getAchProvidersConfig();
		foreach ($ach_providers_config as $config)
		{
			$state_array[$config->ach_provider_id] = explode(",", $config->states);
			$app_status_array[$config->ach_provider_id] = explode(",", $config->app_statuses);
			$new_react_array[$config->ach_provider_id] = $config->ach_new_react;
			$failure_array[$config->ach_provider_id] = explode(",", $config->failures);
			//Fund Date
			if (
				(!empty($config->ach_fund_dates_start)) && (!empty($config->ach_fund_dates_end))
			)
			{
				$fund_dates_start_array[$config->ach_provider_id] = strtotime($config->ach_fund_dates_start);
				$fund_dates_end_array[$config->ach_provider_id] = strtotime($config->ach_fund_dates_end);
			}
			else
			{
				$fund_dates_start_array[$config->ach_provider_id] = NULL;
				$fund_dates_end_array[$config->ach_provider_id] = NULL;
			}
		}
		
		//ABAs
		$combine_aba_array = array();
		foreach ($ach_providers_id as $ach_provider_id)
		{
			$aba_list = array();

			$pr_aba_model = ECash::getFactory()->getModel('AchProviderBankAba');
			$pr_aba_array = $pr_aba_model->loadAllBy(array('active_status' => 'active',
									'ach_provider_id' => $ach_provider_id,
			));
			foreach ($pr_aba_array as $aba)
			{
				$aba_list[] = $aba->bank_aba;
			}

			$combine_aba_array[$ach_provider_id] = $aba_list;	
		}
		////////////////////////////////////////////// CREDIT
		$distribution_array = array();
		foreach ($ach_providers_id as $ach_provider_id)
		{
			$ach_pr_config_model = ECash::getFactory()->getModel('AchProviderConfig');
			$ach_pr_config_model->loadBy(array('ach_provider_id' => $ach_provider_id, 'config_key' => 'credit_percentage',));
			
			$current_config_value = round(intval($ach_pr_config_model->config_value) / 10);
			
			for ($i = 1; $i <= $current_config_value; $i++)
			{
				$distribution_array[] = $ach_provider_id;
			}
		}

		$query = "
		SELECT DISTINCT
		es.application_id,
		ap.date_fund_actual,
		UPPER(ap.state) AS state,
		ap.application_status_id,
		ap.bank_aba,
		IF(ap.is_react = 'yes','react','new') AS new_react,
		
		(
		SELECT COUNT(DISTINCT tr.date_effective)
		FROM transaction_register AS tr
		JOIN transaction_type AS tt ON (tt.company_id = tr.company_id AND tt.transaction_type_id = tr.transaction_type_id)
		WHERE tr.application_id = es.application_id
		AND tr.company_id = es.company_id
		AND tr.transaction_status = 'failed'
		AND tt.clearing_type = 'ach'	
		) AS failures,
		
		(
		SELECT ep.ach_provider_id
		FROM event_schedule_ach_provider AS ep
		JOIN ach_provider AS pr ON (pr.ach_provider_id = ep.ach_provider_id)
		WHERE ep.application_id = es.application_id
		AND ep.date_event = es.date_event
		AND ep.active_status = 'active'
		AND pr.active_status = 'active'
		ORDER BY ep.event_schedule_ach_provider_id DESC
		LIMIT 1
		) AS event_provider_id,
		(
		SELECT ft.name_short
		FROM application_flag AS af
		JOIN flag_type AS ft ON (ft.flag_type_id = af.flag_type_id)
		WHERE af.application_id = es.application_id
		AND ft.name_short IN ('" . implode("','", $ach_providers_name_short) . "')
		AND af.active_status = 'active'
		ORDER BY af.application_flag_id DESC
		LIMIT 1
		) AS name_short
		FROM application AS ap
		JOIN event_schedule es ON (es.company_id = ap.company_id AND es.application_id = ap.application_id) 
		JOIN event_type et ON (et.company_id = es.company_id AND et.event_type_id = es.event_type_id)
		LEFT JOIN transaction_register tr ON (tr.event_schedule_id = es.event_schedule_id)
		WHERE
		(
		es.event_status	= 'scheduled'
		OR
		(es.event_status = 'registered'
		AND
		tr.transaction_status = 'new')
		)
		AND es.company_id = {$this->company_id}
		AND es.event_type_id IN (" . implode(',', $ach_disbursement_ary) . ")
		AND es.date_event <= '$today'
		AND es.date_effective <= '$batch_date'
		AND es.date_created < '$closing_timestamp'
		ORDER BY es.application_id DESC, es.event_schedule_id ASC
		";
		$result = $this->db->Query($query);
		$applications_ids = array();
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$application_id = $row->application_id;

			$current_ach_provider = NULL;
			$current_ach_provider_set = FALSE;

			//Event flag
			if(
				empty($current_ach_provider)
				&& isset($row->event_provider_id)
				&& in_array($row->event_provider_id, $ach_providers_id)
			)
			{
				$current_ach_provider = $row->event_provider_id;
				$current_ach_provider_set = TRUE;
			}

			//App flag
			if(
				empty($current_ach_provider)
				&& isset($row->name_short)
				&& in_array($map_short_names_provider_ids[$row->name_short], $ach_providers_id)
			)
			{
				$current_ach_provider = $map_short_names_provider_ids[$row->name_short];
				$current_ach_provider_set = TRUE;
			}

			//Distribute
			if (empty($current_ach_provider))
			{
				if (!($current_ach_provider = next($distribution_array)))
				{
					$current_ach_provider = reset($distribution_array);
				}
			}

			//State, ABAs, New/React, App Statuses, Failures
			$reset_number = 0;
			while(
				(in_array($row->state, $state_array[$current_ach_provider]))
				||
				(in_array($row->bank_aba, $combine_aba_array[$current_ach_provider]))
				||
				(
					!$current_ach_provider_set
					&&
					(
						(($row->new_react) == ($new_react_array[$current_ach_provider]))
						||
						(in_array($row->application_status_id, $app_status_array[$current_ach_provider]))
						||
						(in_array($row->failures, $failure_array[$current_ach_provider]))
						||
						(
							(!empty($fund_dates_start_array[$current_ach_provider]))
							&&
							(!empty($fund_dates_end_array[$current_ach_provider]))
							&&
							(!empty($row->date_fund_actual))
							&&
							(strtotime($row->date_fund_actual) >= $fund_dates_start_array[$current_ach_provider])
							&&
							(strtotime($row->date_fund_actual) <= $fund_dates_end_array[$current_ach_provider])
						)
					)
				)
			)
			{
				if (!($current_ach_provider = next($distribution_array)))
				{
					$current_ach_provider = reset($distribution_array);
					$reset_number++;

					if ($reset_number > 1)
					{
						$current_ach_provider = NULL;
						break;
					}
				}
			}

			if (!empty($current_ach_provider))
			{
				$app_ach_provider_model = NULL;
				$app_ach_provider_model = ECash::getFactory()->getModel('ApplicationAchProvider');
				
				$app_ach_provider_model->date_modified = date("Y-m-d H:i:s", time());
				$app_ach_provider_model->date_created = date("Y-m-d H:i:s", time());
				$app_ach_provider_model->application_id = $application_id;
				$app_ach_provider_model->ach_provider_id = $current_ach_provider;
				$app_ach_provider_model->setInsertMode(DB_Models_WritableModel_1::INSERT_STANDARD);
				$app_ach_provider_model->insert();
			}
		}
		
		//////////////////////////////////DEBIT
		$distribution_array = array();
		foreach ($ach_providers_id as $ach_provider_id)
		{
			$ach_pr_config_model = ECash::getFactory()->getModel('AchProviderConfig');
			$ach_pr_config_model->loadBy(array('ach_provider_id' => $ach_provider_id, 'config_key' => 'debit_percentage',));
			
			$current_config_value = round(intval($ach_pr_config_model->config_value) / 10);
			
			for ($i = 1; $i <= $current_config_value; $i++)
			{
				$distribution_array[] = $ach_provider_id;
			}
		}
		
		$query = "
		SELECT DISTINCT
		es.application_id,
		ap.date_fund_actual,
		UPPER(ap.state) AS state,
		ap.application_status_id,
		ap.bank_aba,
		IF(ap.is_react = 'yes','react','new') AS new_react,
		
		(
		SELECT COUNT(DISTINCT tr.date_effective)
		FROM transaction_register AS tr
		JOIN transaction_type AS tt ON (tt.company_id = tr.company_id AND tt.transaction_type_id = tr.transaction_type_id)
		WHERE tr.application_id = es.application_id
		AND tr.company_id = es.company_id
		AND tr.transaction_status = 'failed'
		AND tt.clearing_type = 'ach'	
		) AS failures,

		(
		SELECT ep.ach_provider_id
		FROM event_schedule_ach_provider AS ep
		JOIN ach_provider AS pr ON (pr.ach_provider_id = ep.ach_provider_id)
		WHERE ep.application_id = es.application_id
		AND ep.date_event = es.date_event
		AND ep.active_status = 'active'
		AND pr.active_status = 'active'
		ORDER BY ep.event_schedule_ach_provider_id DESC
		LIMIT 1
		) AS event_provider_id,
		(
		SELECT ft.name_short
		FROM application_flag AS af
		JOIN flag_type AS ft ON (ft.flag_type_id = af.flag_type_id)
		WHERE af.application_id = es.application_id
		AND ft.name_short IN ('" . implode("','", $ach_providers_name_short) . "')
		AND af.active_status = 'active'
		ORDER BY af.application_flag_id DESC
		LIMIT 1
		) AS name_short
		FROM application AS ap
		JOIN event_schedule es ON (es.company_id = ap.company_id AND es.application_id = ap.application_id) 
		JOIN event_type et ON (et.company_id = es.company_id AND et.event_type_id = es.event_type_id)
		LEFT JOIN transaction_register tr ON (tr.event_schedule_id = es.event_schedule_id)
		LEFT JOIN application_ach_provider AS aapr ON (aapr.application_id = es.application_id)
		WHERE
		(
		es.event_status	= 'scheduled'
		OR
		(es.event_status = 'registered'
		AND
		tr.transaction_status = 'new')
		)
		AND es.company_id = {$this->company_id}
		AND es.event_type_id IN (" . implode(',', $ach_event_id_ary) . ")
		AND es.date_event <= '$today'
		AND es.date_effective <= '$batch_date'
		AND es.date_created < '$closing_timestamp'
		AND aapr.application_id IS NULL
		ORDER BY es.application_id DESC, es.event_schedule_id ASC
		";
		$result = $this->db->Query($query);
		$applications_ids = array();
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$application_id = $row->application_id;

			$current_ach_provider = NULL;
			$current_ach_provider_set = FALSE;

			//Event flag
			if(
				empty($current_ach_provider)
				&& isset($row->event_provider_id)
				&& in_array($row->event_provider_id, $ach_providers_id)
			)
			{
				$current_ach_provider = $row->event_provider_id;
				$current_ach_provider_set = TRUE;
			}

			//App flag
			if(
				empty($current_ach_provider)
				&& isset($row->name_short)
				&& in_array($map_short_names_provider_ids[$row->name_short], $ach_providers_id)
			)
			{
				$current_ach_provider = $map_short_names_provider_ids[$row->name_short];
				$current_ach_provider_set = TRUE;
			}

			//Distribute
			if (empty($current_ach_provider))
			{
				if (!($current_ach_provider = next($distribution_array)))
				{
					$current_ach_provider = reset($distribution_array);
				}
			}

			//State, ABAs, New/React, App Statuses, Failures
			$reset_number = 0;
			while(
				(in_array($row->state, $state_array[$current_ach_provider]))
				||
				(in_array($row->bank_aba, $combine_aba_array[$current_ach_provider]))
				||
				(
					!$current_ach_provider_set
					&&
					(
						(($row->new_react) == ($new_react_array[$current_ach_provider]))
						||
						(in_array($row->application_status_id, $app_status_array[$current_ach_provider]))
						||
						(in_array($row->failures, $failure_array[$current_ach_provider]))
						||
						(
							(!empty($fund_dates_start_array[$current_ach_provider]))
							&&
							(!empty($fund_dates_end_array[$current_ach_provider]))
							&&
							(!empty($row->date_fund_actual))
							&&
							(strtotime($row->date_fund_actual) >= $fund_dates_start_array[$current_ach_provider])
							&&
							(strtotime($row->date_fund_actual) <= $fund_dates_end_array[$current_ach_provider])
						)
					)
				)
			)
			{
				if (!($current_ach_provider = next($distribution_array)))
				{
					$current_ach_provider = reset($distribution_array);
					$reset_number++;

					if ($reset_number > 1)
					{
						$current_ach_provider = NULL;
						break;
					}
				}
			}

			if (!empty($current_ach_provider))
			{
				$app_ach_provider_model = NULL;
				$app_ach_provider_model = ECash::getFactory()->getModel('ApplicationAchProvider');
				
				$app_ach_provider_model->date_modified = date("Y-m-d H:i:s", time());
				$app_ach_provider_model->date_created = date("Y-m-d H:i:s", time());
				$app_ach_provider_model->application_id = $application_id;
				$app_ach_provider_model->ach_provider_id = $current_ach_provider;
				$app_ach_provider_model->setInsertMode(DB_Models_WritableModel_1::INSERT_STANDARD);
				$app_ach_provider_model->insert();
			}
		}
	}
}

?>
