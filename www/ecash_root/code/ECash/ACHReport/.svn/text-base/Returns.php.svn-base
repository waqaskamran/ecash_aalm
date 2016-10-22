<?php

/**
 * Abstract ACH Return Class
 *
 */
abstract class ECash_ACHReport_Returns extends ECash_ACHReport_Process
{
	public function process($records)
	{
		foreach ($records as $record)
		{
			// Set the ach_report_id so we can associate it later
			$record['ach_report_id'] = $this->report_id;
			
			//Verify the transactionID
			if ($transaction_id = $this->getTransactionID($record))
			{
				//Fetch the Application ID - Used for Stats and Standby
				list($application_id, $company_id) = $this->getApplicationID($record);

				//Fail ACH records (using return code)
				$this->returnACH($record);

				//Fail transaction 
				$this->failTransaction($record);

				//Hit stat

				//Add rescheduling standby.
				$this->setStandBy($application_id, $company_id);
			}
		}
	}

	protected function returnACH(array $record)
	{
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					UPDATE ach
					SET
						ach_status			= 'returned'";

		if (strlen($record['reason_code']) > 0)
		{
			$query .= ",
						ach_return_code_id	= (	SELECT ach_return_code_id
												FROM ach_return_code
												WHERE name_short = " . $this->db->quote($record['reason_code']) . ")";
		}
		if (strlen($record['ach_report_id']) > 0)
		{
			$query .= ",
						ach_report_id	= {$record['ach_report_id']}";
		}
		$query .= "
					WHERE
							ach_id		= {$record['ach_id']}
		";
		$this->db->Query($query);
		$this->log->Write("Failing {$record['ach_id']} with reason code {$record['reason_code']}");
		return true;
	}
	
	/**
	 * Sets the reschedule Standby for the application
	 *
	 * @param int $application_id - the application ID...
	 * @param int $company_id
	 */
	protected function setStandBy($application_id, $company_id = NULL)
	{
		if(empty($company_id))
			$company_id = ECash::getCompany()->company_id;

		Set_Standby($application_id, $company_id, 'reschedule');
	}
	
	/**
	 * Fails the transaction_register items for a given
	 * return record
	 *
	 * @param array $record
	 * @return bool
	 */
	protected function failTransaction($record)
	{
		$ach_id      = $record['ach_id'];
		$reason_code = $record['reason_code'];
		$transactions = array();
		$my_return_status = TRUE;

		// First, look for the transaction register row
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			SELECT transaction_register_id, transaction_status
			FROM transaction_register
			WHERE ach_id = {$ach_id}";

		$result = $this->db->Query($query);
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$transactions[$row->transaction_register_id] = $row->transaction_status;
		}

		if(count($transactions) == 0) return FALSE;
		
		foreach($transactions as $transaction_register_id => $transaction_status)
		{
			if($transaction_status == 'failed') 
			{
				// If the transaction is failed already, there's something fishy going on.  We won't
				// update anything, just make a note of it.  Failure processing has probably already run
				// on the account for this particular transaction.
				$exception = new ECash_ACHReport_ACHException();
				$this->log->Write("Transaction {$transaction_register_id} already marked as failed! ACH ID:{$ach_id}");
				$exception->ach_id = $ach_id;
				$exception->reason_code = $reason_code;
				$exception->details = "Transaction {$transaction_register_id} already marked as failed! ACH ID:{$ach_id}";
				$this->exceptions->addException($exception);
				$my_return_status = FALSE;
				continue;
			}
			else if ($transaction_status == 'complete')
			{
				$this->log->Write("Transaction {$transaction_register_id} already marked as complete! ACH ID:{$ach_id}");
			}

			// If this is complete, we need to strip it out of the transaction ledger.
			// We're running this query for all failures in case there happens to be a
			// ledger, possibly due to some sort of bug. [BR]
			$query = "DELETE FROM transaction_ledger
	                  WHERE transaction_register_id = {$this->db->Quote($transaction_register_id)}";
			$result = $this->db->Query($query);

			// For anything that is pending or complete, we need to update the transaction_register
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
						UPDATE transaction_register
						SET
							transaction_status	= 'failed',
							modifying_agent_id	= '$agent_id'
						WHERE	ach_id = {$this->db->Quote($ach_id)}
						AND		transaction_register_id = {$this->db->Quote($transaction_register_id)}
						AND		transaction_status in ('pending','complete') ";

			$this->log->Write("Setting transaction {$transaction_register_id} w/ ACH ID of {$ach_id} to failed.");
			$result = $this->db->Query($query);
			$my_return_status = TRUE;
		}

		return $my_return_status;
	}

	protected function getTransactionID($record)
	{	
		$ach_id      = $record['ach_id'];
		$reason_code = $record['reason_code'];
		
		// First, look for the transaction register row
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			SELECT transaction_register_id, transaction_status
			FROM transaction_register
			WHERE ach_id = {$ach_id}";

		$result = $this->db->Query($query);
		$row = $result->fetch(PDO::FETCH_OBJ);

		if ($row == null) 
		{
			$exception = new ECash_ACHReport_ACHException();
			$this->log->Write("ACH ID $ach_id does not appear to be associated with any transaction");
			$exception->ach_id = $ach_id;
			$exception->reason_code = $reason_code;
			$exception->details = "ACH ID $ach_id does not appear to be associated with any transaction";
			$this->exceptions->addException($exception);
			return false;
		}

		$trid = $row->transaction_register_id;
		$trstat = $row->transaction_status;

		return $trid;
	}
}

?>