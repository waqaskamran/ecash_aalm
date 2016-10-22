<?php

require_once(LIB_DIR . "Ach/ach.class.php");
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once (LIB_DIR . "Document/Document.class.php");

class Batch_Maintenance
{

	private $ach;
	private $server;
	private $db;
	private $pdc;
	private $cash_report_enabled;
	private $cso_enabled;

	const CONCURRENT_BATCH_RETRY = 6;
	const CONCURRENT_BATCH_SLEEP = 10; //seconds
	
	public function __construct(Server $server)
	{
		$this->server = $server;
		$this->db = ECash::getMasterDb();

		$this->ach = ACH::Get_ACH_Handler($server, 'batch');
		
		$holidays = Fetch_Holiday_List();
		$this->pdc = new Pay_Date_Calc_3($holidays);
		$this->cash_report_enabled = (ECash::getConfig()->DAILY_CASH_REPORT_ENABLED != NULL) ? ECash::getConfig()->DAILY_CASH_REPORT_ENABLED : FALSE;

		/**
		 * @deprecated - CSO Loan Functionality will need to be rewritten
		 * to support the Application Service.
		 */
		$this->cso_enabled = (ECash::getConfig()->CSO_ENABLED != NULL) ? ECash::getConfig()->CSO_ENABLED : FALSE;
	}

	// The $obj in this isn't really necessary, it's mostly to return a message when 
	// the method is called from the user interface, but for the nightly cronjob
	// at most it'll get logged.
	public function Close_Out()
	{
		$obj = new stdClass();

		$ach_providers = $this->ach->getAchProviders(TRUE);
		if (count($ach_providers) > 0)
		{
			$close_for_string = "";
			foreach ($ach_providers as $ach_provider => $name_short)
			{
				$close_for_string .= $name_short . " ";
			}

			$date = date("Y-m-d", strtotime("now"));
			$this->ach->Set_Closing_Timestamp($date);
			$stamp = $this->ach->Get_Closing_Timestamp($date);		
			$obj->message = "The closing time has been set to {$stamp} for {$close_for_string}\n";
			$obj->closing_time = $stamp;
		}
		else
		{
			$obj->message = "It's too early to close the ach batch. Please check the ACH Batch Time config value.\n";
		}

		return($obj);
	}
	
	public function Create_Daily_Cash_Report() 
	{
		require_once(SERVER_MODULE_DIR."reporting/daily_cash_report.class.php");
		$dcr = new Daily_Cash_Report_Query($this->server);
		$dcr->Create_Daily_Cash_Report();
	}

	/**
	 * Used for CSO loan types only to generate the IT Settlement Report
	 */
	public function Create_IT_Settlement_Report()
	{
		require_once(ECASH_DIR.'ITSettlement/it_settlement.class.php');
		$settlement = new IT_Settlement($this->server);
		//Update_Progress('ach','Getting previous settlement date','97.5');
		//get the start date by checking to see the last time the settlement report was generated
		$previous_settlement = date("Y-m-d H:i:s", strtotime('+1 second', strtotime($settlement->getLastSettlementTime('completed'))));
		
		//If there isn't one, go back a month
		if (!$previous_settlement) 
		{
			$previous_settlement = 	date("Y-m-d H:i:s",strtotime('-1 month'));
		}
		//create the process
		$process_id = Set_Process_Status($this->db,$this->server->company_id,'it_settlement','started');
		//generate the reports
		//Update_Progress('ach','Generating IT Settlement report','98');
		$settlement_time = $settlement->getLastSettlementTime('started');
		$report_id = $settlement->generateReport($previous_settlement,$settlement_time,date('Y-m-d'));
		//send the reports
		//Update_Progress('ach','Sending IT Settlement report','98.5');
		$settlement->sendReport($report_id);
		//end the process
		Set_Process_Status($this->db,$this->server->company_id,'it_settlement','completed',null,$process_id);
	}

	// The $obj in this isn't really necessary, it's mostly to return a message when 
	// the method is called from the user interface, but for the nightly cronjob
	// at most it'll get logged.
	public function Send_Batch()
	{
		$obj = new stdClass();
		$progress = new ECash_BatchProgress(ECash::getFactory(), $this->server->company_id, 'ach');
		 
		$today = date('Y-m-d');
		$tomorrow = $this->pdc->Get_Next_Business_Day($today);
		$close_time = $this->ach->Get_Closing_Timestamp($today);
		
		if (!$close_time)
		{
			$obj->message = "You must set a closing time before sending.\n";
			$progress->update($obj->message, 999);
		}	       
		else
		{	    
			if ($this->ach->Has_Sent_ACH($tomorrow))
			{
				$str  = "You have already sent\n";
				$str .= "an ACH batch for {$tomorrow}.\n";
				$str .= "You cannot resend for this business day.";
				$obj->message = $str;
				$progress->update($obj->message, 999);
			}
			else
			{				
				try 
				{
					//[#31518] Check for concurrent batch process
					if(!$this->Check_Running_Batch($progress, $obj))
					{
						//DO NOT PROCEED
						return $obj;
					}

					//asm 80
					$ach_providers = $this->ach->getAchProviders(TRUE);
					//$ach_providers = array_keys($ach_providers);
					
					$batch_type_array = array();
					$ach_providers_config = $this->ach->getAchProvidersConfig();
					foreach ($ach_providers_config as $config)
					{
						$batch_type_array[$config->ach_provider_id] = $config->ach_batch_type;
					}
					///////////////

					//$progress->update('Recording current scheduled events to the Transaction Register', 10);
					//Record_Current_Scheduled_Events_To_Register($today, NULL, NULL, 'ach');

					$batch_ids = array();

					foreach ($ach_providers as $ach_provider_id => $name_short) //asm 80
					{
						//asm 114
						$this->ach = ACH::Get_ACH_Handler($this->server, 'batch', $name_short);

						$progress->update("\nRecording current scheduled events to the Transaction Reigister for {$name_short}", 10);	
						Record_Current_Scheduled_Events_To_Register($today, NULL, NULL, 'ach', NULL, $ach_provider_id);

						if ($batch_type_array[$ach_provider_id] == 'combined')
						{
							$progress->update("\nSending Combined batch for {$name_short}", 60); //asm 80
							$this->ach->Initialize_Batch(); //asm 80
							// Already initialized from its initial __construct
							$ach_receipt	= $this->ach->Do_Batch('combined', $tomorrow, $ach_provider_id); //asm 80
		
							$str = "ACH batch information:\n"; //asm 80
							$str .= "\tBatch ID: \t{$ach_receipt['batch_id']}\n";
							$str .= "\tStatus: \t" . ucfirst($ach_receipt['status']) . "\n";
	
							if(isset($ach_receipt['ref_no']) && ! empty($ach_receipt['ref_no']))
							{
								$str .= "\tReference No: \t{$ach_receipt['ref_no']}\n";
							}
							
							$str .= "\tNumber of Debit Transactions: \t{$ach_receipt['db_count']}\n";
							$str .= "\tTotal Debit Amount: $" . number_format($ach_receipt['db_amount'],2) . "\n";
							$str .= "\tNumber of Credits Transactions: \t{$ach_receipt['cr_count']}\n";
							$str .= "\tTotal Credit Amount: $" . number_format($ach_receipt['cr_amount'],2); //asm 80
							//adding batch id to list for tagging file
							if(!empty($ach_receipt['batch_id']))
								$batch_ids[] = $ach_receipt['batch_id'];
						}
						else 
						{
							$this->ach->Initialize_Batch(); //asm 80
							$progress->update("\nSending Credit batch for {$name_short}", 50);
							// Already initialized from its initial __construct
							$credit_receipt	= $this->ach->Do_Batch('credit', $tomorrow, $ach_provider_id); //asm 80
	
							// Re-Initialize the new batch (do this for each batch)
							$this->ach->Initialize_Batch();
							$progress->update("\nSending Debit batch for {$name_short}", 60);
							$debit_receipt	= $this->ach->Do_Batch('debit' , $tomorrow, $ach_provider_id); //asm 80
						
							$str  = "\nCredit batch information:\n";
							$str .= "\tBatch ID: {$credit_receipt['batch_id']}\n";
							$str .= "\tStatus: " . ucfirst($credit_receipt['status']) . "\n";
							$str .= "\tReference No: {$credit_receipt['ref_no']}\n";
							$str .= "\tNumber of credits: {$credit_receipt['cr_count']}\n";
							$str .= "\tTotal Credit Amount: {$credit_receipt['cr_amount']}\n";
							$str .= "\nDebit batch information:\n";
							$str .= "\tBatch ID: {$debit_receipt['batch_id']}\n";
							$str .= "\tStatus: " . ucfirst($debit_receipt['status']) . "\n";
							$str .= "\tReference No: {$debit_receipt['ref_no']}\n";
							$str .= "\tNumber of debits: {$debit_receipt['db_count']}\n";
							$str .= "\tTotal Debit Amount: {$debit_receipt['db_amount']}\n";
							//adding batch ids to list for tagging file
							if(!empty($debit_receipt['batch_id']))
								$batch_ids[] = $debit_receipt['batch_id'];
							if(!empty($credit_receipt['batch_id']))	
								$batch_ids[] = $credit_receipt['batch_id'];
						}
						//asm 80
						$progress->update($str, 75);
						$obj->message = $str;

						$progress->update('Moving Approved statuses to Active', 85);
						$this->Status_Approved_Move_To_Active($ach_provider_id); //asm 80
						$progress->update('Account Statuses have been updated', 90);
						
						ECash::getLog()->Write('Registering accrued charges for ' . $name_short . ' for ' . $today);
						$progress->update("\nRegistering accrued charges for {$name_short}", 91);
						$this->Register_Accruals($ach_provider_id);
					}

					/**
					 * @deprecated - CSO Loan Functionality will need to be rewritten
					 * to support the Application Service.
					 */
					if($this->cso_enabled)
					{
						//$progress->update('Registering accrued charges', 92);
						//$this->Register_Accruals();
						
						$progress->update('Updating accounts with grace period arrangements', 94);
						$this->gracePeriodToActive();
						
						$progress->update('Past Due accounts have been updated to Active', 95);
						$progress->update('Creating IT Settlement Reports', 97);
						$this->Create_IT_Settlement_Report();
						
						$progress->update('The IT Settlement report has been created and sent', 99);
					}
					
					if($this->cash_report_enabled)
					{
						$progress->update("\nCreating Daily Cash Report", 97); //asm 80
						$this->Create_Daily_Cash_Report();
						$progress->update('The Daily Cash Report Has Been Created', 99);
					}

					//asm 80
					$ach_providers = $this->ach->getAchProviders();
					if (count($ach_providers) > 0)
					{
						$today = date('Y-m-d');
						$process_model = ECash::getFactory()->getModel('ProcessLog');
						$process_model_delete = ECash::getFactory()->getModel('ProcessLog');
						$process_model_array = $process_model->loadAllBy(array('business_day' => $today,
											'step' => 'ach_batchclose',
											'state' => 'completed',
						));
						if (count ($process_model_array > 0))
						{
							foreach ($process_model_array as $process_model_record)
							{
								$loaded = $process_model_delete->loadBy(array('process_log_id' => $process_model_record->process_log_id,
								));
								if ($loaded)
								{
									$process_model_delete->delete();
								}
							}
						}
					}

					$progress->update('Finished Batch Processing', 100);
				}
				catch( Exception $e ) 
				{
					$error_message = $e->getMessage();
					ECash::getLog()->Write("ACH Batch Error: $error_message");
					ECash::getLog()->Write($e->getTraceAsString());

					/* If there is an error, try sending it to the NOTIFICATION_ERROR_RECIPIENTS.
					 * If we forgot to define it, and the EXECUTION_MODE is Live, then
					 * email the right TSS people.  This is done so that for RC environments
					 * the NOTIFICATION_ERROR_RECIPIENTS can be defined for whomever is testing
					 * but if it's not defined and we're not in the LIVE environment 
					 * nothing happens.
					 */
					if(ECash::getConfig()->NOTIFICATION_ERROR_RECIPIENTS != NULL) 
					{
						$recipients = ECash::getConfig()->NOTIFICATION_ERROR_RECIPIENTS;
					} 
					else if (EXECUTION_MODE == 'LIVE') 
					{
						// Fail-safe, the notifications go to Support
						$recipients = 'support@epointmarketing.com';
					}

					if(! empty($recipients))
					{
						$subject = 'Ecash Alert '. strtoupper($this->server->company); //mantis:7727
						$body = 'An ERROR has occured with the ACH Batch - EXECUTION MODE: ' . EXECUTION_MODE . "\n\n";
						$body .= "Error Message: \n" . $error_message . "\n\n";
						$body .= "Trace: \n" . $e->getTraceAsString() . "\n\n";

						require_once(LIB_DIR . '/Mail.class.php');
						eCash_Mail::sendExceptionMessage($recipients, $body, $subject);
					}
					
					$progress->update('An ERROR has occured with the ACH Batch!');
					$progress->update($error_message);
					return($obj);
				}
				
				//Register scheduled accrued charges, since we just sent off payments for them. [AGEAN LIVE #8325]
				//ECash::getLog()->Write('Registering accrued charges for '.$today);
				//$this->Register_Accruals();
				
				/*
				//All those items just created pending transactions on a lot of applications.  Some instances (only MMP right now)
				//require that applications get removed from certain queues when they have a pending transaction. Rather than calling a CFE
				//Event from within the batch send process, let's go ahead and find these actions, and perform these actions now.
				//After we've sent the batch.
				$renewal_queue = 'renewal';
				if($this->ach->useCombined() === TRUE)
				{
					ECash::getLog()->Write("Running 'PENDING_TRANSACTION' CFE event on applications in {$renewal_queue} queue from batch {$ach_receipt['batch_id']}");
					$this->Run_Pending($ach_receipt['batch_id'], $renewal_queue);
				}
				else 
				{
					ECash::getLog()->Write("Running 'PENDING_TRANSACTION' CFE event on applications in {$renewal_queue} queue from batch {$debit_receipt['batch_id']}");
					$this->Run_Pending($debit_receipt['batch_id'], $renewal_queue);
					ECash::getLog()->Write("Running 'PENDING_TRANSACTION' CFE event on applications in {$renewal_queue} queue from batch {$credit_receipt['batch_id']}");
					$this->Run_Pending($credit_receipt['batch_id'], $renewal_queue);
				}
				*/
			}
		}

		return($obj);
	}

	/**
	 * @todo move this logic to the next-gen batch class when it's created
	 * @return boolean TRUE if OK to run batch (no others running),
	 * FALSE if another batch is running and did not complete during
	 * the specified time
	 */
	protected function Check_Running_Batch(ECash_BatchProgress $progress, &$obj)
	{
		$process_log_data = ECash::getFactory()->getData('ProcessLog');
		$pct_step = 100 / self::CONCURRENT_BATCH_RETRY;
		
		for($i = 1; $i <= self::CONCURRENT_BATCH_RETRY; $i++)
		{
			$company = $process_log_data->getStartedACHBatch();
			if($company === NULL)
			{
				$progress->update('Concurrent batch check OK', 0);
				return TRUE;
			}
			$progress->update("Waiting for {$company} batch to complete", $pct_step * $i);
			sleep(self::CONCURRENT_BATCH_SLEEP);
		}
		
		$obj->message = "Sorry, the other processor ({$company}) has not completed yet, try again later.";
		$progress->update($obj->message, 999);
		return FALSE;
	}
		
	
	/**
	 * Run_Pending
	 * Triggers the 'TRANSACTION_PENDING' CFE event on all applications from a specific ACH batch that exist in a specific queue
	 * (or any queue if null is passed in as the queue name)
	 *
	 * @param int $ach_batch_id - The ACH Batch's ID that we're grabbing applications from.
	 * @param string $queue_name - the name_short of the queue that the application has to be in for the CFE event to fire (null for any time sensitive queue)
	 */
	protected function Run_Pending($ach_batch_id,$queue_name = null)
	{
		$queue_where = NULL;
		if($queue_name)
		{
			$queue_where = "AND nq.name_short = {$this->db->quote($queue_name)}";
		}
		if(!$ach_batch_id)
		{
			ECash::getLog()->Write("No ACH Batch ID, not doing PENDING TRANSACTION stuff");
			return NULL;
		}
		$query = "
				SELECT 
    				distinct ach.application_id 
				FROM 
    				ach
    			JOIN 
    				n_time_sensitive_queue_entry ntsqe ON ntsqe.related_id = ach.application_id
    			JOIN
    				n_queue nq ON nq.queue_id = ntsqe.queue_id
				WHERE ach_batch_id = '$ach_batch_id'
				{$queue_where}
				";

		$result = $this->db->query($query);
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			
			$application_id = $row->application_id;
			ECash::getLog()->Write("Running PENDING_TRANSACTION CFE event on {$application_id}");
			$app = ECash::getApplicationById($application_id);	
			$engine = $app->getEngine();
			$engine->executeEvent('PENDING_TRANSACTION');
		}
	}

	/**
	 * For CSO Loans
	 */
	protected function Register_Accruals($ach_provider_id = NULL) 
	{
			$today = date('Y-m-d');
			
			//Record accrued charges to the register
			Record_Current_Scheduled_Events_To_Register($today, NULL, NULL, 'accrued charge', NULL, $ach_provider_id);
			
			//get accrued charge transaction types
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			SELECT
			    transaction_type_id
			FROM 
				transaction_type
			WHERE
				company_id = '{$this->server->company_id}'
			AND 
				clearing_type = 'accrued charge'";
			
			$typelist = array();
			$st = $this->db->query($query);
			while ($row = $st->fetch(PDO::FETCH_OBJ))
			{
				$typelist[] = $row->transaction_type_id;
			}
			
			$agent_id = Fetch_Current_Agent();
		
			//Set newly registered transactions to pending status
			$upd_query = "
			UPDATE transaction_register
			SET transaction_status = 'pending',
			modifying_agent_id = '{$agent_id}'
			WHERE transaction_status = 'new'
			AND transaction_type_id IN (".implode(",",$typelist).")";
		
			$rows = $this->db->exec($upd_query);
			ECash::getLog()->Write("Updated {$rows} non-ACH rows from 'new' to 'pending'.");
		
			//Get statuses to complete
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT  
						tr.transaction_register_id,
						tr.application_id,
						tr.transaction_type_id,
						tt.pending_period,
						tt.period_type,
						tr.date_effective
					FROM    transaction_register tr
					JOIN transaction_type tt ON tr.transaction_type_id = tt.transaction_type_id
					WHERE   tr.company_id = {$this->server->company_id}
					AND	tr.transaction_type_id	IN (" . implode(",", $typelist) . ")
					AND tr.transaction_status	IN ('pending')
					AND tr.date_effective <= '{$today}'
		";

		$result = $this->db->query($query);
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			//Determine the pending window of the statuses.
			$window = intval($row->pending_period);
			
			switch ($row->period_type) 
			{
				case "business":
					$limit = $this->pdc->Get_Business_Days_Backward(date("Y-m-d"), $window);
					break;
				case "calendar":
				default:
					$limit = date("Y-m-d", strtotime("-{$window} days", strtotime(date("Y-m-d"))));
					break;
			}
			
			//Complete them!
			if (strtotime($row->date_effective) <= strtotime($limit))
			{
				$post_result = Post_Transaction($row->application_id, $row->transaction_register_id);
			}
		}
	}

	/**
	 * Updates the Pre-Fund applications to Active after we've sent the ACH Batch
	 *
	 * @return <bool>
	 */
	public function Status_Approved_Move_To_Active($ach_provider_id)
	{
		$ach_provider_id = intval($ach_provider_id);
		try
		{
			/**
			 * Retrieve a list of applications and some very basic info
			 * based on their status.
			 */
			$applications = ECash::getFactory()->getData('Application')->getAppIdsByStatus('approved::servicing::customer::*root');

			if(count($applications) == 0) return TRUE;

			$application_list = array();
			foreach($applications as $application)
			{
				$application_list[$application['application_id']] = $application;
			}

			/**
			 * List of the application_ids to include in the query
			 * of transactions
			 */
			$query = "
					SELECT DISTINCT tr.application_id
					FROM transaction_register AS tr
					JOIN application_ach_provider AS aap ON (aap.application_id = tr.application_id)
					WHERE tr.application_id IN (".implode(',', array_keys($application_list)) .")
					AND tr.company_id = {$this->server->company_id}
					AND aap.ach_provider_id = {$ach_provider_id}
			";

			 $result = $this->db->query($query);
			 $funded_app_list = $result->fetchAll(PDO::FETCH_COLUMN);
			 /**
			  * This may look a little confusing, but what we're doing is using the application_id list
			  * from the transactions result to use as a filter against the array of objects
			  * containing application information in the App Service.
			  */
			 $filtered_application_list = array_intersect_key($application_list, array_flip($funded_app_list));
			 unset($funded_app_list);

			 foreach($filtered_application_list as $row)
			 {
				ECash::getLog()->Write("Account {$row['application_id']}: Approved -> Active.");
				doOnlyUpdateStatus($row['application_id'], 'active::servicing::customer::*root');
				Set_Standby($row['application_id'], $this->server->company_id, 'approval_terms');
				ECash_Documents_AutoEmail::Queue_For_Send($row['application_id'], 'APPROVAL_TERMS');
			 }
		}
		catch(Exception $e)
		{
			ECash::getLog()->Write("Movement of apps from approved to active status failed.", LOG_ERR);
			throw $e;
		}

		return TRUE;
	}

	/**
	 * Used by CSO loan types to move accounts from Past Due to Active
	 * after arrangements have been sent in the ACH batch
	 *
	 * @DEPRECATED - This needs to be rewritten for the App Service in order to work again!
	 * @return <bool>
	 */
	public function gracePeriodToActive()
	{
		 try
		 {
		 	$business_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());
		 	$rule_set_ids = $business_rules->Get_Rule_Set_Ids_By_Parm_Value('loan_type_model','CSO');
		 	$rule_sets = implode(',',$rule_set_ids);
		 	
			 $query = "
 					 SELECT
						 a.application_id, a.date_modified
					 FROM
						 application a
                     JOIN
                          application_status_flat asf ON a.application_status_id = asf.application_status_id
                     JOIN 
                          event_schedule es ON es.application_id = a.application_id
                     JOIN
                          transaction_register tr ON tr.event_schedule_id = es.event_schedule_id
					 WHERE
					 	(level0 = 'past_due' AND level1 = 'servicing' AND level2 = 'customer' AND level3 = '*root')
					 AND	
                        a.company_id = {$this->server->company_id}
                     AND 
                     	a.rule_set_id IN ({$rule_sets})
                     AND
                        es.origin_id IS NOT NULL
                     AND
                        tr.transaction_status = 'pending'
					 FOR UPDATE
			 ";

			 $result = $this->db->query($query);

			 while($row = $result->fetch(PDO::FETCH_OBJ))
			 {
				ECash::getLog()->Write("Account {$row->application_id}: Successful arrangements, moving from Past Due -> Active.");
				Update_Status(null,$row->application_id, array( 'active', 'servicing', 'customer', '*root' ));
			 }
		}
		catch(Exception $e)
		{
			ECash::getLog()->Write("Movement of apps from past due to active status failed.", LOG_ERR);
			throw $e;
		}

		return true;
	}
}

?>
