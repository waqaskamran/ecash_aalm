<?php

/**
 * @package Reporting
 * @category Display
 */
class Second_Tier_Report extends Report_Parent
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
		$this->report_title       = "2nd Tier Report";

		$this->column_names       = array( 
				'company_name'     => 'Company',
				'loan_type'        => 'Loan Type',
				'loan_status'      => 'Loan Status',
				'second_tier_date' => 'Date',
				'application_id'   => 'Application ID',
				'name_first'       => 'First Name',
				'name_last'        => 'Last Name',
				'street'           => 'Street',
				'city'             => 'City',
				'state'            => 'State',
				'princ_balance'    => 'Principal Balance',
				'svc_chg_balance'  => 'Interest Balance',
				'fee_balance'      => 'Fees Balance',
				'total_balance'    => 'Total Balance'
		);

		$this->column_format       = array( 
				'chargeoff_date'  => self::FORMAT_DATE,
				'application_id'  => self::FORMAT_ID,
				'princ_balance'   => self::FORMAT_CURRENCY,
				'svc_chg_balance' => self::FORMAT_CURRENCY,
				'total_balance'   => self::FORMAT_CURRENCY 
		);


		$this->sort_columns       = array(
				'company_name',
				'loan_type',
				'loan_status',
				'second_tier_date',
				'application_id',
				'name_first',
				'name_last',
				'street',
				'city',
				'state',
				'princ_balance',
				'svc_chg_balance',
				'fee_balance',
				'total_balance'
		);

		// FIXME: Formatting

        $this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

        $this->totals 	= array('company' => array( 'rows' => Report_Parent::$TOTAL_AS_COUNT, 'princ_balance','svc_chg_balance', 'fee_balance', 'total_balance'),
        						'grand'   => array( 'rows' => Report_Parent::$TOTAL_AS_COUNT, 'princ_balance','svc_chg_balance', 'fee_balance', 'total_balance'));
		$this->totals_conditions  = NULL;

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = TRUE;
		$this->download_file_name = NULL;
		$this->ajax_reporting     = TRUE;

		parent::__construct($transport, $module_name);
	}
}

?>
