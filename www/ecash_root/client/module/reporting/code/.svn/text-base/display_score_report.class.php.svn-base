<?php

/**
 * @package Reporting
 * @category Display
 */
class Score_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Score Report";
		$this->column_names       = array( 'application_id' => 'Application ID',
		                                   'name_last'      => 'Last Name',
		                                   'name_first'     => 'First Name',
		                                   'fund_amount'    => 'Fund Amount',
		                                   'score'          => 'Score',
		                                   'fund_date'      => 'Date Funded' );
		$this->column_format       = array(
		                                   'fund_amount'    => self::FORMAT_TEXT ,
		                                   'score'          => self::FORMAT_NUMBER ,
		                                   'fund_date'      => self::FORMAT_DATE );
		$this->sort_columns       = array( 'name_last',      'name_first',
		                                   'application_id', 'fund_amount',
		                                   'score',
		                                   'fund_date' );
		$this->link_columns       = array( 'application_id' => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%' );
		$this->totals             = array( 'company' => array( 'rows' ),
		                                   'grand'   => array( 'rows' ) );
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = true;
		$this->download_file_name = null;

		parent::__construct($transport, $module_name);
	}
}

?>
