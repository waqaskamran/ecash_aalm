<?php
require_once(LIB_DIR . "Card/card_batch_interface.iface.php");
require_once(LIB_DIR . "Card/card_utils.class.php");
require_once(LIB_DIR . "Card/anet_php_sdk/AuthorizeNet.php");
require_once(LIB_DIR . "Payment_Card.class.php");
require_once(CUSTOMER_LIB."failure_dfa.php");
require_once(SQL_LIB_DIR . "util.func.php");

/**
 * Card Batch Class
 * 
 *  - controls the batch processing for payment cards
 *
 */
abstract class Card_Batch implements Card_Batch_Interface 
{
	protected $log;
	protected $server;
	protected $db;
	
	protected $card_utils;
	protected $processor;
	protected $crypter;
	
	protected $card_company_name;
	protected $card_tax_id;
	protected $card_login_id;
	protected $card_key;
	protected $sandbox;
	
	protected $credit_count = 0;
	protected $credit_amount = 0;
	protected $debit_count = 0;
	protected $debit_amount = 0;
	
	protected $company_abbrev;
	protected $company_id;
	
	protected $card_process_id = 0;
	
	protected static $modifiers = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	protected $RS		  = "\n";
	
	protected $result_ary;
	protected $avs_result_ary = array(
		'A' => "Address (Street) matches, ZIP does not",
		'B' => "Address information not provided for AVS check",
		'E' => "AVS error",
		'G' => "Non-U.S. Card Issuing Bank",
		'N' => "No Match on Address (Street) or ZIP",
		'P' => "AVS not applicable for this transaction",
		'R' => "Retry—System unavailable or timed out",
		'S' => "Service not supported by issuer",
		'U' => "Address information is unavailable",
		'W' => "Nine digit ZIP matches, Address (Street) does not",
		'X' => "Address (Street) and nine digit ZIP match",
		'Y' => "Address (Street) and five digit ZIP match",
		'Z' => "Five digit ZIP matches, Address (Street) does not");

	
	public function __construct(Server $server)
	{
		$this->server = $server;
		$this->db = ECash::getMasterDb();
		$this->company_id = $server->company_id;
		$this->company_abbrev = strtolower($server->company);
		$this->log = ECash::getLog('card');
		$this->card_utils = new Card_Utils($server);
		$this->crypter = new Payment_Card();
		
		// Now grab a few company-based properties
		$this->card_company_name = ECash::getConfig()->COMPANY_NAME;
		$this->card_phone_number = ECash::getConfig()->COMPANY_PHONE_NUMBER;
		$this->card_login_id = ECash::getConfig()->AUTHORIZENET_API_LOGIN_ID;
		$this->card_key = ECash::getConfig()->AUTHORIZENET_TRANSACTION_KEY;
		$this->sandbox = ECash::getConfig()->AUTHORIZENET_SANDBOX;
		$this->md5 = ECash::getConfig()->AUTHORIZENET_MD5_HASH;
		
		// get the results array
		$this->result_ary = ECash::getFactory()->getModel('CardProcessResponse', $this->db);
		
		define("AUTHORIZENET_API_LOGIN_ID", $this->card_login_id);
		define("AUTHORIZENET_TRANSACTION_KEY", $this->card_key);
		define("AUTHORIZENET_SANDBOX", $this->sandbox);
		$this->processor = new AuthorizeNetAIM();
		
		$this->Initialize_Batch();
	}
	
	public function Initialize_Batch()
	{
		$this->rowcount		= 0;
		$this->blockcount	= 0;
		$this->clk_total_amount	= 0;

		$this->process_log_ids		= array();
		$this->clk_trace_numbers	= array();
		$this->customer_trace_numbers	= array();
	}
	
	public function Do_Batch($batch_type, $batch_date)
	{
		$this->batch_type = strtolower($batch_type);
		$this->batch_date = $batch_date;
		$this->card_utils->Set_Batch_Date($batch_date);

		// If we are already inside of a transaction, bail out with error
		if ($this->db->getInTransaction())
		{
			throw new General_Exception("CARD: Cannot be invoked from within a transaction; this class manages its own transactions.");
		}

		$this->log->Write("CARD: Received request to run a {$batch_type} batch for date {$batch_date}");

		// Note start of Card process
		$this->card_utils->Set_Process_Status('card_send', 'started');

		// Generate data for a new batch
		$response = $this->Create_Batch();

		if ($response['status'] == 'sent')
		{
			$this->log->Write("CARD '{$this->batch_type}' batch card prepared and sent for {$this->batch_date}.", LOG_INFO);
			$this->card_utils->Set_Process_Status('card_send', 'completed');

			return $response;
		}
		else if($response['status'] == 'created') 
		{
			$this->log->Write("CARD '{$this->batch_type}' batch card created for {$this->batch_date}.", LOG_INFO);
			$this->card_utils->Set_Process_Status('card_send', 'completed');

			return $response;
		}		
		else
		{
			$this->log->Write("CARD transmission of '{$this->batch_type}' batch card for {$this->batch_date} has failed.", LOG_ERR);
			$this->card_utils->Set_Process_Status('card_send', 'failed');
			
			return array(	'status'	=> 'failed', 
							'batch_id'	=> $this->card_batch_id, 
							'ref_no'	=> NULL );
		}
	}
	
	private function Create_Batch ()
	{
		$today = date('Y-m-d');
		
		$return = false;

		$closing_timestamp = $this->Get_Closing_Timestamp($today);
		if (!$closing_timestamp)
		{
			$this->log->Write("CARD: No batch close has been performed for today.", LOG_ERR);
			return false;
		} else {
			$this->log->Write("CARD: Using closing stamp of $closing_timestamp");
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

		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			SELECT DISTINCTROW
				tr.transaction_register_id,
				tr.event_schedule_id,
				es.origin_group_id,
				tr.application_id,
				IF(tr.amount < 0, 'debit', 'credit') as process_type,
				abs(tr.amount) as amount,
				tt.name_short as transaction_type
			FROM transaction_register tr
			JOIN event_schedule es ON (tr.event_schedule_id = es.event_schedule_id)
			JOIN transaction_type tt ON (tr.transaction_type_id = tt.transaction_type_id)
			WHERE tt.clearing_type		= 'card'
				AND  tr.transaction_status	= 'new'
				AND  tr.company_id      	=  {$this->company_id}
				AND  tr.date_effective		<= '{$this->batch_date}'
				AND  es.date_created		< '$closing_timestamp'
				AND  tr.amount {$sign} 0
			ORDER BY
				tr.date_effective, transaction_register_id
		";
		$result = $this->db->Query($query);
		$count = $result->rowCount();
		if ($count == 0)
		{
			$this->log->Write("CARD: No new transactions of type '{$this->batch_type}' were eligible for batch processing.", LOG_INFO);
			return FALSE;
		} else {
			$this->log->Write("CARD: Discovered {$count} transactions for processing.");
		}

		$transactions = array();
		$applications_ids = array();
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$transactions[] = $row;
			if(!isset($application_ids[$row['application_id']])) $application_ids[$row['application_id']] = $row['application_id'];
		}

		/**
		 * So we've got our list of transactions, so let's try to grab the application info for it
		 */
		$application_data = ECash::getFactory()->getData('Application')->getApplicationData($application_ids);
		if(empty($application_data))
		{
			throw new Exception("Unable to retrieve Application Info for the CARD Batch.  Something is very wrong!");
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
				throw new Exception("Unable to locate application {$event['application_id']} in AppService!  This is very bad...");
			}

			/**
			 * Ignore accounts in a 'Hold' status
			 */
			if($app_info['application_status'] == 'hold::servicing::customer::*root' ||
			   $app_info['application_status'] == 'hold::arrangements::collections::customer::*root')
			{
				//do nothing continue;
			} 

			else $combined_data[] = array_merge($event, $app_info);
		}

		unset($transactions);
		unset($application_ids);
		unset($application_data);
		unset($app_info);

		$first_card_process_id = false;	
		
		$biz_rules = new ECash_Business_Rules($this->db);
		$rule_sets = array();
		$fatal_applications = array();
		
		try {
			$card_process_ary = array();

			$batch_date = strtotime($this->batch_date);

			// Loop through customer data
			foreach($combined_data as $tr_row)
			{
				$rule_set_id = $tr_row['rule_set_id'];
				if(! isset($rule_sets[$rule_set_id]))
				{
					$rule_sets[$rule_set_id] = $biz_rules->Get_Rule_Set_Tree($rule_set_id);
				}
				$tr_row['card_info_id'] = $this->card_utils->Get_Card_Info($tr_row['application_id']);
				
				// make sure that there is a card info to use before processing
				if ($tr_row['card_info_id']) {
					
					$card_info = ECash::getFactory()->getModel('CardInfo');
					$card_info->loadBy(array('card_info_id' => $tr_row['card_info_id']));
	
					$tr_row['cardholder_name'] = $card_info->cardholder_name;
					$tr_row['card_number'] = $card_info->card_number;
					$tr_row['expiration_date'] = $card_info->expiration_date;
					$tr_row['card_street'] = $card_info->card_street;
					$tr_row['card_zip'] = $card_info->card_zip;
	
					$this->db->beginTransaction();
					
					// Insert customer card row as status='sent'
					$card_process_id = $this->card_utils->Insert_Card_Process_Row($tr_row);
					
					$card_process = ECash::getFactory()->getModel('CardProcess');
					
					if (!$first_card_process_id) $first_card_process_id = $card_process_id;
					
					$tr_row['card_process_id'] = $card_process_id;
					$card_process_ary[] = $tr_row;
					
					$this->card_process_id = $card_process_id;
	
									
					// Mark transaction_register row as 'pending' and note the card_id
					$this->card_utils->Update_Transaction_Register($tr_row['transaction_register_id'], 'pending',$card_process_id);
	
					$this->db->commit();
					
					if (!$fatal_applications[$tr_row['application_id']]) $result = $this->process_transaction($tr_row);
					else $result = $fatal_applications[$tr_row['application_id']];
					
					$this->result_ary->loadBy(array('reason_code' => $result->response_reason_code));
	
					$this->card_utils->Update_Card_Process_Row($tr_row,$result);
					if ($result->approved) {
						$this->card_utils->Update_Transaction_Register($tr_row['transaction_register_id'], 'complete',$card_process_id);
						$return = 'sent';
						Check_Inactive($tr_row['application_id']);
					} else {
						$fatal_applications[$tr_row['application_id']] = $result;
						$this->card_utils->Update_Transaction_Register($tr_row['transaction_register_id'], 'failed',$card_process_id);
						if ($this->result_ary->fatal_fail) {
							$this->card_utils->setCardFatalFlag($tr_row['application_id']);
						}
						if ($return != 'sent') $return = 'created';

						// Update the schedule
						$fdfap = new stdClass();
						$fdfap->application_id = $tr_row['application_id'];
						$fdfap->server = $this->server;
		
						$fdfa = new FailureDFA($tr_row['application_id']);
						$fdfa->run($fdfap);
					}
					
					// the returning result is systemic error in nature, revert last transaction back to new and crash.
					if ($this->result_ary->process_fail) {
						$this->card_utils->Update_Transaction_Register($tr_row['transaction_register_id'], 'new',$card_process_id);
						return false;
					}
				
					$this->credit_count += 1;
					$this->credit_amount += $tr_row['amount'];
				} else {  // no credit card information, fail transactions
					$this->card_utils->Update_Transaction_Register($tr_row['transaction_register_id'], 'failed',$card_process_id);
				}
			}
		}
	
				
		catch(Exception $e) {
			$this->log->Write("CARD: Creation of batch {$this->card_process_id} failed and transaction will be rolled back.", LOG_ERR);
			$this->log->Write("CARD: No data recovery should be necessary after the cause of this problem has been determined.", LOG_INFO);
			$return = false;
			if ($this->db->getInTransaction())
			{
				$this->db->rollback();
			}
			$this->card_utils->Set_Process_Status('card_send', 'failed');
			throw $e;
		}
		
		$ret = array();
		$ret['status'] = $return;
		$ret['batch_id'] = $this->card_batch_id = $first_card_process_id;
		$ret['ref_no'] = $card_process_id;
		$ret['db_amount'] = 0;
		$ret['db_count'] = 0;
		$ret['cr_count'] = $this->credit_count;
		$ret['cr_amount'] = $this->credit_amount;
		
		return $ret;
	}

	public function Preview_Card_Batches ($batch_date)
	{
		$today = date('Y-m-d');

		$card_event_id_ary = array();
		$card_disbursement_ary = array();
		
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT DISTINCT 
					et.event_type_id 
				FROM 
					event_transaction et, 
					transaction_type tt 
				WHERE
					et.transaction_type_id	= tt.transaction_type_id 
					AND tt.clearing_type		= 'card'
					AND tt.name_short != 'loan_disbursement'
					AND et.active_status = 'active'
					AND tt.company_id = {$this->company_id}
		";

		$result = $this->db->Query($query);
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$card_event_id_ary[] = $row['event_type_id'];
		}
		
		if (count($card_event_id_ary) == 0)
		{
			return false;
		}

		$closing_timestamp = $today;
		
		$return_ary = false;
		$i = 0;
		$query = "-- /* SQL LOCATED IN file=" . __FILE__ . " line=" . __LINE__ . " method=" . __METHOD__ . " */
				SELECT DISTINCTROW
					es.application_id,
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
				LEFT JOIN transaction_register tr ON (tr.event_schedule_id = es.event_schedule_id)
				WHERE
					(
						es.event_status			= 'scheduled'
						OR
						(es.event_status 		= 'registered'
						AND
						tr.transaction_status	= 'new')
					)
					AND es.company_id =  ".$this->company_id."
					AND es.event_type_id IN (" . implode(",", $card_event_id_ary) . ")
					AND es.date_effective <= '".$today."'
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
			throw new Exception("Unable to retrieve Application Info for the Card Batch.  Something is very wrong!");
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

			$return_ary[$i]['application_id'] = $row['application_id'];
			$return_ary[$i]['name']	= $row['name']; //"last, first"
			$return_ary[$i]['full_name'] = $row['name']; //"first last" for [#50910]
			$return_ary[$i]['bank_aba'] = $row['bank_aba'];
			$return_ary[$i]['bank_account'] = $row['bank_account'];
			$return_ary[$i]['bank_account_type'] = $row['bank_account_type'];
			$return_ary[$i]['amount'] = $row['amount'];
			$return_ary[$i]['ach_type'] = $row['ach_type'];
			$return_ary[$i]['action_date'] = $row['action_date'];
			$return_ary[$i]['due_date'] = $row['due_date'];
			$return_ary[$i]['real_amount'] = $row['real_amount'];
			$return_ary[$i]['event_name_short'] = $row['event_name_short'];
			$return_ary[$i]['event_name'] = $row['event_name'];
			$return_ary[$i]['application_company_id'] = $row['application_company_id'];
			$return_ary[$i]['event_company_id'] = $row['event_company_id'];	
			$return_ary[$i]['event_schedule_id'] = $row['event_schedule_id'];
			$return_ary[$i]['origin_id'] = $row['origin_id'];	
			$return_ary[$i]['amount_principal'] = $row['amount_principal'];
			$return_ary[$i]['amount_interest'] = $row['amount_interest'];
			$return_ary[$i]['amount_fees'] = $row['amount_fees'];
			$i++;
		}
		unset($combined_data);
		return $return_ary;
	}

	/**
	 * Helper method to get the COMBINED_BATCH flag from the class.
	 *
	 * @return bool
	 */
	public function useCombined() {
		return $this->COMBINED_BATCH;
	}
	
	/**
	 * Public function to verify a credit card 
	 *
	 * @returns results from card submission
	 */
	public function Verify_Card($data){
		$this->db->beginTransaction();
		// Insert customer card row as status='sent'
		$data['amount'] = '0.00';
		if (!isset($data['card_info_id']) || (!is_numeric($data['card_info_id']))) $data['card_info_id'] = 0;
		$card_process_id = $this->card_utils->Insert_Card_Process_Row($data);
		
		if (!$first_card_process_id) $first_card_process_id = $card_process_id;
		
		$data['card_process_id'] = $card_process_id;
		$card_process_ary[] = $data;
		
		$this->card_process_id = $card_process_id;
		
		$result = $this->process_verify($data);

		$this->result_ary->loadBy(array('reason_code' => $result->response_reason_code));
		
		$this->card_utils->Update_Card_Process_Row($data,$result);
		$return = array();
		if ($result->declined) {
			$return = 'DECLINED: '. $result->response_reason_code . ':' . $result->response_reason_text . ' | avs: ' . $result->avs_response . ':' . $this->avs_result_ary[$result->avs_response];
		} elseif ($result->approved || $result->held) {
			$return = 'APPROVED!   avs: ' . $result->avs_response . ':' . $this->avs_result_ary[$result->avs_response];;
		} else {
			$return = 'ERROR: '. $result->response_reason_code . ':' . $result->response_reason_text . ' | ' . $result->avs_response . ':' . $this->avs_result_ary[$result->avs_response];
		}

		return $return;
	}
	
	/**
	 * Public function to processes a credit card transaction
	 *
	 * @returns results from card submission
	 */
	public function Charge_Card($data){

		return process_transaction($data);
	}

	/**
	 * Processes a credit card transaction
	 *
	 * @returns results from card submission
	 */
	private function process_transaction($data){
		
		$name = $this->crypter->decrypt($data['cardholder_name']);
		$fname = substr($name,0,strpos($name,' '));
		$lname = substr($name,strpos($name,' ')+1);
		
		$this->processor->invoice_num = $data['card_process_id'];
		$this->processor->description = $data['application_id'];;
		$this->processor->card_num = $this->crypter->decrypt($data['card_number']);
		$this->processor->exp_date = date('m/y',strtotime($data['expiration_date']));
		$this->processor->amount = $data['amount'];
		
		$this->processor->first_name = $fname;
		$this->processor->last_name = $lname;
		
		$this->processor->address = $data['card_street'];
		$this->processor->zip = $data['card_zip'];
		$this->processor->setCustomField("application_id", $data['application_id']);
		
		//$this->processor->md5_hash = $this->md5;
		$response  = $this->processor->authorizeAndCapture();

		$this->result_ary->loadBy(array('reason_code' => $response->response_reason_code));
		$response->result = $this->result_ary->response;
		
		return $response;
	}
	
	/**
	 * Processes a credit card verification
	 *
	 * @returns results from card submission
	 */
	private function process_verify($data){
		
		$name = $data['cardholder_name'];
		$fname = substr($name,0,strpos($name,' '));
		$lname = substr($name,strpos($name,' ')+1);
		
		$this->processor->invoice_num = $data['card_process_id'];
		$this->processor->description = $data['application_id'];;
		$this->processor->card_num = $data['card_number'];
		$this->processor->exp_date = $data['expiration_date'];
		$this->processor->amount = 0;
		
		$this->processor->first_name = $fname;
		$this->processor->last_name = $lname;
		
		$this->processor->address = $data['card_street'];
		$this->processor->zip = $data['card_zip'];
		$this->processor->setCustomField("application_id", $data['application_id']);
		
		//$this->processor->md5_hash = $this->md5;
		$response  = $this->processor->authorizeOnly();
		
		$this->result_ary->loadBy(array('reason_code' => $result->response_reason_code));
		$response->result = $this->result_ary->response;
		
		return $response;
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
							step			= 'card_batchclose'
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
		
		$this->card_utils->Set_Process_Status('card_batchclose', 'started');
		$this->card_utils->Set_Process_Status('card_batchclose', 'completed');
		
		return true;
	}
}

?>
