<?php

/**
 * @package Reporting
 * @category Display
 */
class Transaction_History_Report extends Report_Parent
{
	/**
	 * constructor, initializes data used by report_parent
	 *
	 * @param Transport $transport the transport object
	 * @param string $module_name name of the module we're in, not used, but keeps
	 *                            universal constructor call for all modules
	 * @access public
	 */
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->company_totals = array();

		$this->report_title = "Transaction History Report";

		$this->column_names = array('company_name' => 'Company',
						'application_id' => 'Application ID',
						'app_date_created' => 'Application Date',
						'name_last' => 'Last Name',
						'name_first' => 'First Name',
						'income_monthly' => 'Monthly Income',
						'income_source' => 'Income Source',
						'income_frequency' => 'Income Frequency',
						'bank_aba' => 'Bank ABA',
						'bank_account' => 'Bank Account',
						'bank_account_type' => 'Bank Account Type',
						'new_react' => 'New/React',
						'loans_repaid' => 'Loans Repaid',
						'date_fund_actual' => 'Loan Fund Date',
						'app_status' => 'Application Status (current)',
						'fund_actual' => 'Loan Fund Amount',
						'balance' => 'Loan Balance (current)',
						'payment_sequence' => 'Payment Sequence',
						'date_effective' => 'Transaction Date',
						'day_of_week' => 'Day Of Week',
						'balance_txn' => 'Loan Balance (at time of txn)',
						'app_status_txn' => 'Application Status (at time of txn)',
						'transaction_type_name' => 'Transation Description',
						'clearing_type' => 'Transaction Clearing Type',
						'ach_provider' => 'Ach Provider',
						'amount' => 'Transaction Amount',
						'debit_credit' => 'Debit/Credit',
						'transaction_status' => 'Transaction Status',
						'agent_name' => 'Modified By',
						'return_date' => 'Return Date',
						'return_code' => 'Return Code',
						'return_description' => 'Return Decsription',
						'reattempt' => 'Reattempt',
						'reattempt_number' => 'Reattempt Number',
						'event_schedule_id' => 'Event ID',
		                                'transaction_register_id' => 'Transaction ID',
						'ach_card_id' => 'ACH/Card ID',
						'bureau_name' => 'UW Vendor',
						'inquiry_type' => 'UW Inquiry Name',
						'campaign_name' => 'Campaign Name',
						'promo_id' => 'Promo ID',
						'promo_sub_code' => 'Promo Subcode',
						'source_url' => 'Source URL',
		);

		$this->column_format = array('application_id' => self::FORMAT_ID,
						'app_date_created' => self::FORMAT_DATE,
						'income_monthly' => self::FORMAT_CURRENCY,
						'date_fund_actual' => self::FORMAT_DATE,
						'fund_actual' => self::FORMAT_CURRENCY,
						'balance' => self::FORMAT_CURRENCY,
						'balance_txn' => self::FORMAT_CURRENCY,
						'date_effective' => self::FORMAT_DATE,
						'amount' => self::FORMAT_CURRENCY,
						'return_date' => self::FORMAT_DATE,
						'event_schedule_id' => self::FORMAT_ID,
						'transaction_register_id' => self::FORMAT_ID,
						'ach_card_id' => self::FORMAT_ID,
						'promo_id' => self::FORMAT_ID,
						'promo_sub_code' => self::FORMAT_ID,
		);

		$this->sort_columns = array('application_id',
						'app_date_created',
						'name_last',
						'name_first',
						'income_monthly',
						'income_source',
						'income_frequency',
						'bank_aba',
						'bank_account',
						'bank_account_type',
						'new_react',
						'loans_repaid',
						'date_fund_actual',
						'app_status',
						'fund_actual',
						'balance',
						'payment_sequence',
						'date_effective',
						'day_of_week',
						'balance_txn',
						'app_status_txn',
						'transaction_type_name',
						'clearing_type',
						'ach_provider',
						'amount',
						'debit_credit',
						'transaction_status',
						'agent_name',
						'return_date',
						'return_code',
						'return_description',
						'reattempt',
						'reattempt_number',
						'event_schedule_id',
						'transaction_register_id',
						'ach_card_id',
						'bureau_name',
						'inquiry_type',
						'campaign_name',
						'promo_id',
						'promo_sub_code',
						'source_url',
		);

        	$this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

        	$this->totals = null;
		$this->totals_conditions  = null;

		$this->date_dropdown	= Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type	= true;
		$this->batch_type           	= TRUE;
		$this->ach_batch_company        = TRUE;
		$this->date_search_by	= true;
		$this->download_file_name = null;
		$this->ajax_reporting	= true;
		parent::__construct($transport, $module_name);
	}
}

?>
