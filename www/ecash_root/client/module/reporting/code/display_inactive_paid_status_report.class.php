<?php

/**
 * @package Reporting
 * @category Display
 */
class Inactive_Paid_Status_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Inactive Paid Status Report";
		$this->column_names       = array( 'name_short'    => 'Company',
											'application_id' => 'Application ID',
		                                   'name_last'      => 'Last Name',
		                                   'name_first'     => 'First Name',
		                                   'date_application_status_set'      => 'Paid Off Date',
		                                   'fund_actual'    => 'Fund Amount',
		                                   );
		$this->column_format = array(
			'date_application_status_set' => self::FORMAT_DATE ,
			'fund_actual' => self::FORMAT_CURRENCY 
		);
		$this->sort_columns       = array();
		$this->link_columns       = array( 'application_id' => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%' );
		$this->totals             = array( 'company' => array( 'rows' ),
		                                   'grand'   => array( 'rows' )
		                                 );
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = true;
		$this->ajax_reporting     = true;

		parent::__construct($transport, $module_name);
	}
}

?>
