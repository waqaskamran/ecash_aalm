<?php

class ECashCra_Driver_Commercial_PaymentQueryBuilder
{
	const TRANSACTION_HISTORY_TEMP_TABLE = 'temp_payment_query_transaction_history';
	const APPLICATION_TEMP_TABLE = 'temp_payment_query_application';
	
	const ACH_RETURN_TEMP_TABLE = 'temp_payments_ach_return';
	const ACH_RETURN_APPLICATION_TEMP_TABLE = 'temp_payments_ach_return_application';
	
	const ACH_PAYMENT_TEMP_TABLE = 'temp_payments_ach_payment';
	const ACH_PAYMENT_APPLICATION_TEMP_TABLE = 'temp_payments_ach_payment_application';
	
	/**
	 * Returns the query to create the temporary transaction history table.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	public function getTemporaryTransactionHistoryQuery($date, $company, array &$args)
	{
		$query = "
			CREATE TEMPORARY TABLE " . self::TRANSACTION_HISTORY_TEMP_TABLE . " (
				PRIMARY KEY (transaction_register_id),
				INDEX (application_id)
			)
			SELECT
				th.transaction_register_id,
				th.application_id,
				th.status_after,
				th.date_created
			FROM
				transaction_history th
			WHERE
				th.date_created BETWEEN ? AND ?
				AND (
					(th.status_before = 'pending' AND th.status_after = 'failed')
					OR
					(th.status_before = 'new' AND th.status_after = 'pending')
				)
				AND th.company_id = (SELECT company_id FROM company WHERE name_short = ?)";
		
		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);
		
		return $query;
	}
	
	public function getTemporaryTransactionHistoryApplicationsQuery()
	{
		return "SELECT application_id FROM " . self::TRANSACTION_HISTORY_TEMP_TABLE;
	}
	
	public function getNonACHPaymentsQuery()
	{
		$fields = $this->getCommonApplicationFields();
		$fields['payment_id'] = 'tr.transaction_register_id';
		$fields['payment_type'] = "IF (tr.amount > 0, 'CREDIT', 'DEBIT')";
		$fields['payment_date'] = 'DATE(th.date_created)';
		$fields['payment_amount'] = 'ABS(tr.amount)';
		$fields['payment_return_code'] = "
			IF (
				(th.status_after = 'failed')
				OR (
					tt.name_short IN (
						'chargeback',
						'ext_recovery_reversal_fee',
						'ext_recovery_reversal_pri'
					)
				), 
			'R', NULL)";
		$fields['payment_method'] = "
				CASE
					WHEN tt.name_short IN (
					'moneygram_fees',
					'moneygram_princ',
					'western_union_princ',
					'western_union_fees',
					'ext_recovery_fees',
					'ext_recovery_princ',
					'ext_recovery_reversal_fee',
					'ext_recovery_reversal_pri'
					)
					THEN 'EFT'
					
					WHEN tt.name_short IN (
					'money_order_fees',
					'money_order_princ'
					)
					THEN 'MONEY ORDER'
					
					WHEN tt.name_short IN (
					'credit_card_princ',
					'credit_card_fees',
					'chargeback',
					'chargeback_reversal'
					)
					THEN 'CARD1'
					
					WHEN tt.name_short IN (
					'quickcheck'
					)
					THEN 'DEMAND DRAFT'
					
					ELSE NULL
				END
		";
		
		unset($fields['bank_acct_number']);
		unset($fields['bank_aba']);
		$query = "
			SELECT
				{$this->buildFields($fields)}
			FROM
				transaction_register tr
				JOIN " . self::APPLICATION_TEMP_TABLE . " a USING (application_id)
				JOIN " . self::TRANSACTION_HISTORY_TEMP_TABLE . " th USING (transaction_register_id)
				JOIN transaction_type tt USING (transaction_type_id)
			HAVING
				payment_method IS NOT NULL
			ORDER BY
				tr.date_created ASC
		";
		
		return $query;
	}
	
	public function getACHPaymentsQuery()
	{
		$fields = $this->getCommonApplicationFields();
		$fields['payment_id'] = 'ach.ach_id';
		$fields['payment_type'] = "IF (ach.ach_type = 'credit', 'CREDIT', 'DEBIT')";
		$fields['payment_date'] = 'ach.ach_date';
		$fields['payment_amount'] = 'ach.amount';
		$fields['payment_return_code'] = "NULL";
		$fields['payment_method'] = "'ACH'";
		
		$query = "
			SELECT
				{$this->buildFields($fields)}
			FROM
				" . self::ACH_PAYMENT_TEMP_TABLE . " ach
				INNER JOIN " . self::ACH_PAYMENT_APPLICATION_TEMP_TABLE . " a
					ON ach.application_id = a.application_id
			ORDER BY
				ach.date_created ASC
		";
		
		$args = array(
			$date,
			$company
		);
		
		return $query;
	}
	
	/**
	 * Returns a query that will create a temporary table for the ACH data for ACH Payments.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	public function getACHPaymentTempTableQuery($date, $company, array &$args)
	{
		$query = "
			CREATE TEMPORARY TABLE " . self::ACH_PAYMENT_TEMP_TABLE . " (INDEX (application_id))
			SELECT
				*
			FROM
				ach
			WHERE
				ach.ach_date = ?
				AND ach.company_id = ( SELECT company_id FROM company WHERE name_short = ? )
		";
		
		$args = array(
			$date,
			$company
		);
		
		return $query;
	}
	
	/**
	 * Returns a query to retrieve all the application ID's from the ACH payment temp table.
	 * 
	 * @return string
	 */
	public function getACHPaymentApplications()
	{
		return 'SELECT application_id FROM ' . self::ACH_PAYMENT_TEMP_TABLE;
	}
	
	/**
	 * Returns a query that will create a temporary table with the ACH Returns data.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	public function getACHReturnsTemporaryTableQuery($date, $company, array &$args)
	{
		$query = "
			CREATE TEMPORARY TABLE " . self::ACH_RETURN_TEMP_TABLE . " (INDEX (application_id))
			SELECT
				ach.application_id,
				ach.ach_id,
				ach.ach_type,
				ach.date_created,
				ar.date_request,
				ach.amount,
				arc.name_short,
				ach.ach_status
			FROM
				ach_report AS ar
				INNER JOIN ach
					ON ar.ach_report_id = ach.ach_report_id
				LEFT JOIN ach_return_code AS arc
					ON ach.ach_return_code_id = arc.ach_return_code_id
			WHERE
				ar.date_request = ?
				AND ar.company_id = ( SELECT company_id FROM company WHERE name_short = ? )
			ORDER BY ach.date_created ASC
		";
		
		$args = array(
			$date,
			$company
		);
		
		return $query;
	}
	
	/**
	 * Returns a query to get all the application ID's from the ACH returns temporary table.
	 * 
	 * @return string
	 */
	public function getACHReturnsTempTableApplicationsQuery()
	{
		return 'SELECT application_id FROM ' . self::ACH_RETURN_TEMP_TABLE;
	}
	
	public function getACHReturnsQuery()
	{
		$fields = $this->getCommonApplicationFields();
		$fields['payment_id'] = 'ach.ach_id';
		$fields['payment_type'] = "IF (ach.ach_type = 'credit', 'CREDIT', 'DEBIT')";
		$fields['payment_date'] = 'ach.date_request';
		$fields['payment_amount'] = 'ach.amount';
		$fields['payment_return_code'] = "IFNULL(ach.name_short, 'R')";
		$fields['payment_method'] = "'ACH'";
		
		$query = "
			SELECT
				{$this->buildFields($fields)}
			FROM
				" . self::ACH_RETURN_TEMP_TABLE . " AS ach
				INNER JOIN " . self::ACH_RETURN_APPLICATION_TEMP_TABLE . " AS a
					ON ach.application_id = a.application_id
			WHERE
				ach.ach_status = 'returned'
			ORDER BY
				ach.date_created ASC
		";
		
		return $query;
	}
	
	protected function getCommonApplicationFields()
	{
		return array(
			'application_id' => 'a.application_id',
			'fund_date' => 'a.date_fund_actual',
			'fund_amount' => 'a.fund_actual',
			'date_first_payment' => 'a.date_first_payment',
			'fee_amount' => 'a.fee_amount',
			'employer_name' => 'a.employer_name',
			'employer_street1' => 'a.work_address_1',
			'employer_street2' => 'a.work_address_2',
			'employer_city' => 'a.work_city',
			'employer_state' => 'a.work_state',
			'employer_zip' => 'a.work_zip_code',
			'pay_period' => 'a.pay_period',
			'phone_work' => 'a.phone_work',
			'phone_ext' => 'a.phone_work_ext',
			'name_first' => 'a.name_first',
			'name_middle' => 'a.name_middle',
			'name_last' => 'a.name_last',
			'street1' => 'a.street',
			'street2' => 'a.unit',
			'city' => 'a.city',
			'state' => 'a.state',
			'zip' => 'a.zip_code',
			'phone_home' => 'a.phone_home',
			'phone_cell' => 'a.phone_cell',
			'email' => 'a.email',
			'ip_address' => 'a.ip_address',
			'dob' => 'a.dob',
			'ssn' => 'a.ssn',
			'driver_license_number' => 'a.legal_id_number',
			'driver_license_state' => 'a.legal_id_state',
			'bank_name' => 'a.bank_name',
			'bank_aba' => 'a.bank_aba',
			'bank_acct_number' => 'a.bank_account'
		);
	}
	
	protected function buildFields($fields)
	{
		$field_string = '';
		foreach ($fields as $label => $value)
		{
			$field_string .= "{$value} {$label},\n";
		}
		
		return substr($field_string, 0, -2);
	}
}

?>
