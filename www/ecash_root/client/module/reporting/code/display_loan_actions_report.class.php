<?php

/**
 * @package Reporting
 * @category Display
 */
class Loan_Actions_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title = "Verification Triggers Report";
		$this->column_names = array( 
									'company_name'	  => 'Company Name',
									'application_id' => 'App ID',
		                             'current_status' => 'Current Status',
		                             'verification'   => 'Verification',
		                             'disposition'    => 'Disposition',
		                             'agent'          => 'Agent',
		                             'status'         => 'Status',
		                             'date'           => 'Date' );
		$this->sort_columns       = array();
		$this->link_columns       = array( 'application_id' => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%' );
		$this->totals             = array( 'company' => array( 'rows' ),
		                                   'grand'   => array() );

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->download_file_name = null;

		$this->column_format       = array('date'				=> self::FORMAT_DATETIME);
		$this->ajax_reporting      = TRUE;

		parent::__construct($transport, $module_name);
	}
}

?>
