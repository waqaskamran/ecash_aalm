<?php
/**
 * Description of ECash_Loan_API
 *
 * @copyright Copyright &copy; 2009 The Selling Source, Inc.
 * @package ECash_Loan
 * @author Bryan Campbell <bryan.campbell@dataxltd.com>
 */
class ECash_Loan_API extends ECash_Service_Loan_API
{
	/**
	 * Override parent as commercial and AMG use different methids for this
	 * determination
	 * @see ECash_Service_Loan_API#getBalance($application_id)
	 * @param int $application_id
	 * @return array
	 */
	public function getBalance($application_id)
	{
		// Initialize values for SOAP response
		$balance_info['current_due_date'] = NULL;
		$balance_info['current_amount_due'] = NULL;
		$balance_info['next_due_date'] =  NULL;
		$balance_info['amount_due'] = NULL;
		$balance_info['principle_amount_due'] = NULL;
		$balance_info['service_charge_amount_due'] = NULL;

		// Load the fee schedule class
		$legacy_schedule = ECash::getFactory()->getData('LegacySchedule',$this->db);

		$due_dates = $legacy_schedule->fetch_due_dates($application_id, $this->getCompanyID());

		// Get the first row and setteh due information
		if ($row = $due_dates->fetch(PDO::FETCH_OBJ))
		{
			$balance_info['next_due_date'] =  $this->formatXsdDate($row->due_date);
			$balance_info['amount_due'] = $row->total_due;
			$balance_info['principle_amount_due'] = $row->principal;
			$balance_info['service_charge_amount_due'] = $row->service_charge;
		}

		// Get the balance data from teh schedule
		$balance_data = $legacy_schedule->Fetch_balance($application_id);
		// Set the payoff amount
		$balance_info['payoff_amount'] = isset($balance_data->total_balance)
			? $balance_data->total_balance : NULL;
		return $balance_info;
	}

	/**
	 * Override as Commercial uses last pament date instead of pay out date
	 * @see ECash_Service_Loan_API#getLoanData($application_id)
	 * @param int $application_id
	 * @return array
	 */
	public function getLoanData($application_id)
	{
		$loan_data = parent::getLoanData($application_id);
		
		$api = $this->ecash_api_factory->createECashApi($application_id);
		
		$loan_data["paid_out_date"] = $this
			->formatXsdDate($this->ecash_api_factory->createECashAPI($application_id)
			->Get_Last_Payment_Date());
		$loan_data['apr'] = $api->Get_Loan_APR();
			
		return $loan_data;
	}
	
	/**
	 * Inserts a message into the log.
	 *
	 * @param string $message The message to log.
	 * @return void
	 */
	protected function insertLogEntry($message) {
		$log = ECash::getLog();
		$log->write($message);
	}
	
}
