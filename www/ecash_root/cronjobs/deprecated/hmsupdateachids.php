<?php

/**
 * The purpose for this script is to hit stats for everyone that was in the
 * Past Due status.  I realize we've already hit stats for them, but I guess 
 * we can't use past stats for the campaign, everything has to be a new stat
 * hit.  GForge #18295 - BR
 */
function main()
{
	global $server;
	
	$fix = new Impact_Identify_Missing_Transactions($server->company_id);
	$fix->run();

}

class Impact_Identify_Missing_Transactions
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
	
	
	public function __construct($company_id)
	{
		$this->company_id = $company_id;
		$this->db = ECash_Config::getSlaveDbConnection();	
		$this->dbw = ECash_Config::getMasterDbConnection();
	}
	
	public function run()
	{
		$reverse_map = $this->Impact_Reverse_Transaction_Type_Map();
		
		$data = $this->getFileData('tags3/');
		echo "Found " . count($data) . " records.  Mapping .. ";
		$mapped_data = $this->mapDataArray($data, $this->format);
		echo "Done.\n";
		
		echo "Reconciling file data with DB .. \n";
		$missing = array();
		$transaction_dates = array();
		
		foreach($mapped_data as $t)
		{

			if($t->ach_flag === 'ACH')
			{
				if($transaction = $this->getTransaction($t->total_payment, $t->application_id, $t->transaction_date, $reverse_map[$t->transaction_type]))
				{
					echo "Transaction needed to be updated, Batch Date: {$t->transaction_date}, ACH ID: {$transaction->ach_id} to {$t->transaction_id}, Amount: {$t->total_payment}!\n";
					
					$transaction->new_ach_id = $t->transaction_id;
					$missing[] = $transaction;
				}
				else
				{
			  	   echo "Transaction not found: " . print_r($t,true) . "\n";
			
				}
			}
		}

		echo count($missing) . " transactions to update ach id.\n";
		// Write report to CSV
		//$this->writeCSV($missing, '18647_Missing_Transactions_Datafix.csv');
		$reverse_ach = array();
		// Data Fix
		foreach($missing as $m)
		{
			echo "Fixing {$m->application_id}, ACH ID: {$m->ach_id} to {$m->new_ach_id}, EventID: {$m->event_schedule_id}, Amount: {$m->amount}\n";
			$reverse_ach[$m->new_ach_id] = $m->ach_id;
			$this->dataFix($m);
		}
		print_r($reverse_ach);
	}
	
	private function fetchTransactionsForDate($date)
	{
		$transactions = array();
		
		$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
		SELECT 	tr.date_created,
				tr.date_modified,
				tr.company_id,
				tr.application_id,
				tr.transaction_register_id,
				tr.ach_id,
				tr.date_effective,
				tt.name_short as transaction_type,
				tr.transaction_status,
				tr.amount
		FROM   transaction_register AS tr
		JOIN   transaction_type AS tt USING (transaction_type_id)
		WHERE  tr.date_effective = '$date' 
		AND    tt.clearing_type = 'ach'";

		$result = $this->db->query($sql);

		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$transactions[$row->transaction_register_id] = $row;
		}

		return $transactions;
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

	private function getTransaction($payment_amount, $application_id, $effective_date, $type)
	{
		$amount = ($payment_amount/100) * -1;
		$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
		SELECT *
		FROM transaction_register
		JOIN transaction_type using (transaction_type_id)		
		WHERE application_id = '{$application_id}'
		AND amount = '{$amount}'
		AND date_effective = '{$effective_date}'
		AND name_short in ({$type})
		";

		
		$result = $this->db->query($sql);

		if($row = $result->fetch(PDO::FETCH_OBJ))
		{
			return $row;
		}
	
		return false;
	}

	// * Need: ach_id, amount, batch_date, transaction_register_id, event_schedule_id
	private function fetchGivenTransactionData($ach_id)
	{
		$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
		SELECT 	ach.application_id,
				ach.company_id,
				ach.ach_id,
				ach.amount AS ach_amount,
				ach.ach_date,
			   	tr.transaction_register_id,
			   	tr.amount as transaction_amount,
			   	tr.transaction_status,
			   	tr.event_schedule_id,
			   	tr.date_effective,
			   	tr.date_created,
			   	tr.date_modified,
			   	tr.source_id,
			   	tr.modifying_agent_id
		FROM ach
		JOIN transaction_register AS tr USING (ach_id)
		WHERE ach_id = '{$ach_id}'";

		$result = $this->db->query($sql);

		return $result->fetch(PDO::FETCH_OBJ);
	}
	
	/**
	 * Writes a CSV file
	 *
	 * @param array of stdClass objects $data
	 * @param string $filename
	 */
	private function writeCSV($data, $filename)
	{
		$header = array(
		 		'date_effective',
		 		'company_id',
		 		'application_id',
		 		'transaction_register_id',
		 		'ach_id',
		 		'transaction_type',
		 		'transaction_status',
		 		'amount'
		 	);
		
		$fp = fopen($filename, 'w');
		
		fputcsv($fp, $header);
		
		foreach($data as $row)
		{
			fputcsv($fp, array(
		 		$row->date_effective,
		 		$row->company_id,
		 		$row->application_id,
		 		$row->transaction_register_id,
		 		$row->ach_id,
		 		$row->transaction_type,
		 		$row->transaction_status,
		 		$row->amount
		 	));
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
			'T11'	=> "'adjustment_internal_fees'",
			'T11'	=> "'adjustment_internal_princ'",
			'T06'	=> "'assess_fee_ach_fail', 'assess_service_chg'",
			'T16'	=> "'bad_data_payment_debt_fee'",
			'T16'	=> "'bad_data_payment_debt_pri'",
			'T26'	=> "'cancel_fees','cancel_principal'",
			'T31'	=> "'chargeback'",
			'T32'	=> "'chargeback_reversal'",
			'T09'	=> "'converted_principal_bal'",
			'T23'	=> "'converted_sc_event'",
			'T10'	=> "'converted_service_chg_bal'",
			'T21'	=> "'credit_card_fees'",
			'T21'	=> "'credit_card_princ'",
			'T16'	=> "'debt_writeoff_fees'",
			'T16'	=> "'debt_writeoff_princ'",
			'T17'	=> "'ext_recovery_fees'",
			'T17'	=> "'ext_recovery_princ'",
			'T29'	=> "'ext_recovery_reversal_fee'",
			'T29'	=> "'ext_recovery_reversal_pri'",
			'T14'	=> "'full_balance'",
			'T01'	=> "'loan_disbursement'",
			'T19'	=> "'moneygram_fees'",
			'T19'	=> "'moneygram_princ'",
			'T18'	=> "'money_order_fees'",
			'T18'	=> "'money_order_princ'",
			'T25'	=> "'paydown'",
			'T12'	=> "'payment_arranged_fees', 'payment_arranged_princ'",
			'T28'	=> "'payment_debt_fees', 'payment_debt_principal'",
			'T07'	=> "'payment_fee_ach_fail'",
			'T13'	=> "'payment_manual_fees', 'payment_manual_princ'",
			'T04'	=> "'payment_service_chg'",
			'T27'	=> "'payout_fees', 'payout_principal'",
			'T22'	=> "'personal_check_fees'",
			'T22'	=> "'personal_check_princ'",
			'T15'	=> "'quickcheck'",
			'T30'	=> "'refund_3rd_party_fees'",
			'T30'	=> "'refund_3rd_party_princ'",
			'T24'	=> "'refund_fees'",
			'T24'	=> "'refund_princ'",
			'T03'	=> "'repayment_principal'",
			'T20'	=> "'western_union_fees'",
			'T20'	=> "'western_union_princ'",
			'T08'	=> "'writeoff_fee_ach_fail'",
		);
	}

	
	/**
	 * Add amount to ACH *
	 * Add transaction_register item *
	 * Add transaction_ledger item (if applicable) *
	 * Add event_amount
	 * 
	 * Need: ach_id, amount, batch_date, transaction_register_id, event_schedule_id
	 */
	private function dataFix($data)
	{
		$this->updateAchRow($data);
		$this->fixTransactionRegister($data);

	}
	
	
	private function updateAchRow($data)
	{
		$application_id = $data->application_id;
		
		$sql = "UPDATE ach SET ach_id = {$data->new_ach_id} WHERE ach_id = {$data->ach_id} AND application_id = {$application_id}";
		echo $sql, PHP_EOL;
	//	die();		
	//	$this->dbw->exec($sql);
	}
	
	private function fixTransactionRegister(&$data)
	{
		$sql = "
			UPDATE transaction_register
			SET ach_id = {$data->new_ach_id}
			WHERE transaction_register_id = {$data->transaction_register_id}
			
			 ";
		echo $sql, PHP_EOL;
		//die();
		//$this->dbw->exec($sql);
	}
	

	
}
