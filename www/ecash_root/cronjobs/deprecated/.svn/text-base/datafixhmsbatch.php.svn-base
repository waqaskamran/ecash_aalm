<?php

/**

 */
function main()
{
	global $server;
	
	$fix = new HMS_Fix_Supended_batch($server->company_id);
	$fix->run();

}

class HMS_Fix_Supended_batch
{

	private $company_id;
	private $db;
	
	private $format = array(	'transaction_date', 
								'application_id', 
								'lender_tag',
								'first_name',
								'last_name',
								'transaction_id',
								'bank_aba',
								'bank_account',
								'acct_type',
								'ach_flag',
								'transaction_type',
								'total_payment',
								'principal',
								'service_charge',
								'funded_amount',
								'total_service_charge',
								'status',
								'customer_id',
								'is_react' );
	
	

	public function lookupcompany($tag)
	{
		switch($tag)
		{
			case 'HNSC':
				return 1;
			break;
			case 'HBGC':
				return 2;
			break;
			case 'HEZC':
				return 3;
			break;
			case 'HCSG':
				return 4;
			break;
			case 'HTGC':
				return 5;
			break;
			case 'HGTC':
				return 6; 
			break;
			case 'HOBB':
				return 7;
			break;
			case 'HCVC':
				return 8;
			break;
		}
	}

	public function __construct($company_id)
	{
		$this->company_id = $company_id;
		//$this->db = ECash_Config::getSlaveDbConnection();	
		$this->db = ECash_Config::getMasterDbConnection();
	}
	
	public function run()
	{
		$reverse_map = $this->Impact_Reverse_Transaction_Type_Map();
		
		$data = $this->getFileData('tags/');
		echo "Found " . count($data) . " records.  Mapping .. ";
		$mapped_data = $this->mapDataArray($data, $this->format);
		echo "Done.\n";
		
		echo "Reconciling file data with DB .. \n";
		$bad = array();
		//split data into company and credit/debit
		$sorted_data = array();		
		foreach($mapped_data as $t)
		{
			//init company array
			if(empty($sorted_data[$t->lender_tag]))
			{
				$sorted_data[$t->lender_tag] = array();
				$sorted_data[$t->lender_tag]['credit'] = array();
				$sorted_data[$t->lender_tag]['debit'] = array();

			}
		 	//is a credit 	
		 	if($t->total_payment < 0)
			{
				$sorted_data[$t->lender_tag]['credit'][] = $t;
			}
			else
			{
				$sorted_data[$t->lender_tag]['debit'][] = $t;	
			}
		}

		$events = array();
		$notfound = 0;
		foreach($sorted_data as $company_data)
		{

			//Rebuild credit batch
			//@todo check/create ach_batch row
			//@todo findevent
			//@todo if event found  create register entry 
			//with transaction id as ach id update event_amounts with register id,
			// create ach entry with ach id from tag file
			// update event schedule to registered 
			$ach_batch_id = null;
			foreach($company_data['credit'] as $credit_row)
			{
				if($credit_row->ach_flag === 'ACH' && trim($credit_row->lender_tag) != '')
				{
					$suspended_events = $this->findEvent($credit_row);
					if(empty($suspended_events))
					{
					 	echo "Could not find event for credit row " . print_r($credit_row, true) . "\n";
						$notfound++;
						continue;
					}

					foreach($suspended_events as $event)
					{
		//				echo "Missing Transaction, Batch Date: {$t->transaction_date}, ACH ID: {$t->transaction_id}, Amount: {$amount}!\n";
						echo "Running for Event Schedule Id: " . $event->event_schedule_id . "\n";
						$ach_batch_id = $this->getAchBatchId($event, 'credit');
						if(!empty($ach_batch_id))
						{						
							if(empty($events[$event->event_schedule_id]))
							{
								$this->registerEvent($event, $credit_row);
								$this->createACHentry($event, $credit_row, $ach_batch_id, 'credit');					
								$this->updateEventScheduletoRegistered($event);					
								$events[$event->event_schedule_id]++;				
							}
							else
							{
								//ignore event done
							}
						}
						else
						{
							echo "wtf no batch id";
							exit();
						}
					}
				}
				else if(trim($credit_row->lender_tag) != '')
				{
					echo 'row does not have lender tag' . print_r($credit_row, true) . "\n";
				}
			}

			
			//Rebuild debit batch
			//@todo check/create ach_batch row 
			//@todo findevent
			//@todo if event found create register entry 
			//with transaction id as ach id update event_amounts with register id,
			// create ach entry with ach id from tag file
			// update event schedule to registered 
			$ach_batch_id = null;
			foreach($company_data['debit'] as $debit_row)
			{
				if($debit_row->ach_flag === 'ACH' && trim($debit_row->lender_tag) != '')
				{
					$suspended_events = $this->findEvent($debit_row);
					if(empty($suspended_events))
					{
					 	echo "Could not find event for debit row " . print_r($debit_row, true) . "\n";
						$notfound++;
						continue;
					}

					foreach($suspended_events as $event)
					{
		//				echo "Missing Transaction, Batch Date: {$t->transaction_date}, ACH ID: {$t->transaction_id}, Amount: {$amount}!\n";
						echo "Running for Event Schedule Id: " . $event->event_schedule_id . "\n";
						$ach_batch_id = $this->getAchBatchId($event, 'debit');
						if(!empty($ach_batch_id))
						{
							if(empty($events[$event->event_schedule_id]))
							{
								$this->registerEvent($event, $debit_row);
								$this->createACHentry($event, $debit_row, $ach_batch_id, 'debit');					
								$this->updateEventScheduletoRegistered($event);	
								$events[$event->event_schedule_id]++;				
							}
							else
							{
								//ignore event handled
							}
						}
						else
						{
							echo "wtf no batch id";
							exit();
						}					
					}
				}
				else if(trim($debit_row->lender_tag) != '')
				{
					echo 'row does not have lender tag' . print_r($debit_row, true) . "\n";
				}

			}

		}

		echo "number of events:" . count($events) . "\n";
		echo "number of rows not found:" . $notfound . "\n";

	}

	public function findEvent($row)
	{
		$company_id = $this->lookupcompany($row->lender_tag);
		$reverse_map = $this->Impact_Reverse_Transaction_Type_Map();
		$event_name = $reverse_map[$row->transaction_type];		
		$sql = "SELECT * 
				FROM event_schedule 
				JOIN event_type using (event_type_id)  
				WHERE application_id = '{$row->application_id}' 
				AND date_effective = '{$row->transaction_date}' 
				AND name_short ='{$event_name}' 
				AND event_type.company_id ={$company_id} 
				AND (ABS(amount_principal*100) = ABS({$row->principal}) OR ABS(amount_non_principal*100) = ABS({$row->service_charge})) 
					";	
		$result = $this->db->query($sql);
		$events = array();		
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
		   $events[] = $row;
		}
		return $events;
	}

	public function updateEventScheduletoRegistered($event)
	{
		$sql = "update event_schedule set event_status = 'registered' where event_schedule_id = {$event->event_schedule_id}";
	//	echo $sql . "\n";
		$this->db->query($sql);	
	}

	/**
	* Create ACH entry for event
	*/
	public function createACHentry($event, $row, $ach_batch_id, $type)
	{
		$amount = abs($event->amount_principal + $event->amount_non_principal);
		//check if ach entry exists
		$sql = "select * from ach where application_id = {$event->application_id} and ach_batch_id = '{$ach_batch_id}' and ach_type = '{$type}' and origin_group_id = {$event->event_schedule_id}";
		$result = $this->db->query($sql);
		if($ach_entry = $result->fetch(PDO::FETCH_OBJ))
		{
			//ach entry exists
		}
		else
		{
			//insert entry
			$sql = "
			INSERT ignore
				into ach 
				(date_modified,
				 date_created,
				 company_id, 
				application_id, 
				ach_id, 
				ach_batch_id,
				 origin_group_id,
				 ach_date,
				 amount, 
				ach_type, 
				bank_aba, 
				bank_account, 
				bank_account_type, 
				ach_status)
			SELECT		
				 now(),
			 	now(),
				 company_id,
				 application_id,
				 {$row->transaction_id},
				 {$ach_batch_id},
				{$event->event_schedule_id}, 
				'{$event->date_effective}', 
				{$amount}, 
				'{$type}', 
				bank_aba, 
				bank_account, 
				bank_account_type, 
				'processed'
			FROM
				application
			WHERE
				application_id = {$event->application_id}";	
		//	echo $sql . "\n";;			
		//	die();	
				$this->db->query($sql);		
		}
	}

	/**
	* insert transaction register entry and update event amounts table
	*/
	public function registerEvent($event, $row)
	{
		//check if entry exists
		$sql = "select * from transaction_register join transaction_type using (transaction_type_id) where event_schedule_id = {$event->event_schedule_id}";
		$result = $this->db->query($sql);
		$ids =  array();
		while($tr = $result->fetch(PDO::FETCH_OBJ))
		{
			if($tr->affects_principal == 'yes')
				$ids['principal'] = $tr->transaction_register_id;
			else
				$ids['non_principal'] = $tr->transaction_register_id;
		}
		//entries do not exist
		if(empty($ids))
		{
			//get types to split into	
			$sql = "select * from event_transaction join transaction_type using (transaction_type_id) where event_type_id = {$event->event_type_id}";
			$transaction_result = $this->db->query($sql);
			
			while($transaction_type = $transaction_result->fetch(PDO::FETCH_OBJ))
			{
				if($transaction_type->affects_principal == 'yes')
				{
					$amount = $event->amount_principal;	
				}
				else
				{
					$amount = $event->amount_non_principal;
				}

				if($amount <> 0)
				{
					//create register transaction entry
					$sql = "insert into transaction_register (date_modified,
										 date_created,
										 company_id,
										 application_id,
										 event_schedule_id,
										 ach_id, 
										transaction_type_id, 
										transaction_status, 
										amount, 
										date_effective, 
										source_id,
										 modifying_agent_id)
					select 
						now(), 
						now(), 
						es.company_id, 
						es.application_id, 
						es.event_schedule_id,
						 {$row->transaction_id},
						 {$transaction_type->transaction_type_id},
						 'pending',
						 $amount,
						 '{$event->date_effective}', 
						 0,
						 1
					 from
						 event_schedule es 
					where 
						event_schedule_id = {$event->event_schedule_id}";
					//	echo $sql . "\n";
									
					$this->db->query($sql);
					if($transaction_type->affects_principal == 'yes')
					{
						$ids['principal'] = $this->db->lastInsertId();	
					}
					else
					{
						$ids['non_principal'] = $this->db->lastInsertId();
					}
				}
			}
		}

		//update event amount
		foreach($ids as $type => $id)
		{		
			$types = array();
			if($type == 'principal')
				$types[] = "'principal'";
			if($type == 'non_principal')
			{
				$types[] = "'fee'";
				$types[] = "'service_charge'";		
			}
			$sql = "update event_amount join event_amount_type using (event_amount_type_id) set transaction_register_id = {$id} 
				where event_schedule_id = {$event->event_schedule_id} and name_short in (".implode(',', $types).")";
			//echo $sql . "\n";			
			$this->db->query($sql);
		}
	}
	/**
	* returns current batch id for a debit or credit batch for a company
	*/
	public function getAchBatchId($event, $type)
	{
		//check if one currently exists
		$sql = "select ach_batch_id from ach_batch where company_id = {$event->company_id} and batch_type = '{$type}' and remote_response = 'building'";
		$result = $this->db->query($sql);
		if($row = $result->fetch(PDO::FETCH_OBJ))
		{
			return $row->ach_batch_id;
		}
		else
		{
			//create new ach_batch row
			$sql = "insert into ach_batch (date_modified, date_created, company_id, remote_response, batch_status, batch_type) values (now(),now(), {$event->company_id}, 'building', 'created', '{$type}')";
		//	echo $sql . "\n";		
			$this->db->query($sql);	
			return $this->db->lastInsertId();	
		}
	}
	
	private function getFileData($directory)
	{
		$data = array();
		
		$file_list = scandir($directory);
		foreach($file_list as $filename)
		{
			if($filename !== '.' && $filename !== '..')
			{
				$full_path = $directory . $filename;
				echo "Adding $full_path .. ";

				$file_data = file($full_path);

				echo "found " . count($file_data) . " records\n";
				$data = array_merge($data, $file_data);
			}
		}

		return $data;
	}

	public function mapDataArray($file_data, $format)
	{
		$parsed_data_ary = array();
		$i = 0;

		foreach ($file_data as $line)
		{
			if ( strlen(trim($line)) > 0 )
			{
				//  Split each row into individual columns
				$matches = array();
				preg_match_all('#(?<=^"|,")(?:[^"]|"")*(?=",|"$)|(?<=^|,)[^",]*(?=,|$)#', $line, $matches);
				$col_data_ary = $matches[0];
				
				//$parsed_data_ary[$i] = array();
				$parsed_data_ary[$i] = new stdClass();
				foreach ($col_data_ary as $key => $col_data)
				{
					// Apply column name map so we can return a friendly structure
					//$parsed_data_ary[$i][$format[$key]] = str_replace('"', '', $col_data);
					$key_name = $format[$key];
					$parsed_data_ary[$i]->{$key_name} = str_replace('"', '', $col_data);
				}

				$i++;
			}
		}
		return $parsed_data_ary;
	}

	
	/**
	 * Writes a CSV file
	 *
	 * @param array of stdClass objects $data
	 * @param string $filename
	 */
	private function writeCSV($data, $filename)
	{
		$header = array(	'transaction_date', 
								'application_id', 
								'lender_tag',
								'first_name',
								'last_name',
								'transaction_id',
								'bank_aba',
								'bank_account',
								'acct_type',
								'ach_flag',
								'transaction_type',
								'total_payment',
								'principal',
								'service_charge',
								'funded_amount',
								'total_service_charge',
								'status',
								'customer_id',
								'is_react' );
		
		$fp = fopen($filename, 'w');
		
		fputcsv($fp, $header);
		
		foreach($data as $row)
		{
			fputcsv($fp, array(	$row->transaction_date, 
								$row->application_id, 
								$row->lender_tag,
								$row->first_name,
								$row->last_name,
								$row->transaction_id,
								$row->bank_aba,
								$row->bank_account,
								$row->acct_type,
								$row->ach_flag,
								$row->transaction_type,
								$row->total_payment,
								$row->principal,
								$row->service_charge,
								$row->funded_amount,
								$row->total_service_charge,
								$row->status,
								$row->customer_id,
								$row->is_react ));
		}
		fclose($fp);
	}

	private function getCompanies()
	{
		$companies = array();
		
		$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
		SELECT UPPER(name_short) as company,
			   company_id
		FROM   company
		WHERE  active_status = 'active' ";

		$result = $this->db->query($sql);

		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$companies[$row->company] = $row->company_id;
		}
	
		return $companies;
	}
	
	public function Impact_Reverse_Transaction_Type_Map() 
	{
		return array(
			'T11'	=> 'adjustment_internal',
			'T11'	=> 'adjustment_internal',
			'T06'	=> 'assess_fee_ach_fail',
			'T05'	=> 'assess_service_chg',
			'T16'	=> 'bad_data_payment_debt_fee',
			'T16'	=> 'bad_data_payment_debt_pri',
			'T26'	=> 'cancel_fees',
			'T26'	=> 'cancel_principal',
			'T31'	=> 'chargeback',
			'T32'	=> 'chargeback_reversal',
			'T09'	=> 'converted_principal_bal',
			'T23'	=> 'converted_sc_event',
			'T10'	=> 'converted_service_chg_bal',
			'T21'	=> 'credit_card',
			'T21'	=> 'credit_card',
			'T16'	=> 'debt_writeoff',
			'T16'	=> 'debt_writeoff',
			'T17'	=> 'ext_recovery',
			'T17'	=> 'ext_recovery',
			'T29'	=> 'ext_recovery_reversal',
			'T29'	=> 'ext_recovery_reversal',
			'T14'	=> 'full_balance',
			'T01'	=> 'loan_disbursement',
			'T19'	=> 'moneygram',
			'T19'	=> 'moneygram',
			'T18'	=> 'money_order',
			'T18'	=> 'money_order',
			'T25'	=> 'paydown',
			'T12'	=> 'payment_arranged',
			'T12'	=> 'payment_arranged',
			'T28'	=> 'payment_debt',
			'T28'	=> 'payment_debt',
			'T07'	=> 'payment_fee_ach_fail',
			'T13'	=> 'payment_manual',
			'T13'	=> 'payment_manual',
			'T04'	=> 'payment_service_chg',
			'T27'	=> 'payout',
			'T27'	=> 'payout',
			'T22'	=> 'personal_check',
			'T22'	=> 'personal_check',
			'T15'	=> 'quickcheck',
			'T30'	=> 'refund_3rd_party',
			'T30'	=> 'refund_3rd_party',
			'T24'	=> 'refund',
			'T24'	=> 'refund',
			'T03'	=> 'repayment_principal',
			'T20'	=> 'western_union',
			'T20'	=> 'western_union',
			'T08'	=> 'writeoff_fee_ach_fail',
		);
	}

	

	
}
