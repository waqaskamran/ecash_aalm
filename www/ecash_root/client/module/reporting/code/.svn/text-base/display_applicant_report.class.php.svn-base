<?php

/**
 * @package Reporting
 * @category Display
 */
class Applicant_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Applicant Report";
		$this->column_names       = array( 'company_name' => 'Company',
										   'application_id'  => 'Application ID',
		                                   'in_verify'       => 'Received Verify',
		                                   'in_underwriting' => 'Received UW',
		                                   'funded'          => 'Funded',
		                                   'approved'        => 'Approved',
		                                   'withdrawn'       => 'Withdrawn',
		                                   'denied'          => 'Denied',
		                                   'reverified'      => 'Reverified' );
		$this->sort_columns       = array( 'application_id',  'in_verify',
		                                   'in_underwriting', 'funded',
		                                   'approved',        'withdrawn',
		                                   'denied',          'reverified' );
		$this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%' );
		$this->totals             = array( 'company' => array( 'in_underwriting' => Report_Parent::$TOTAL_AS_SUM,
		                                                       'funded'          => Report_Parent::$TOTAL_AS_SUM,
		                                                       'approved'        => Report_Parent::$TOTAL_AS_SUM,
		                                                       'withdrawn'       => Report_Parent::$TOTAL_AS_SUM,
		                                                       'denied'          => Report_Parent::$TOTAL_AS_SUM,
		                                                       'reverified'      => Report_Parent::$TOTAL_AS_SUM,
		                                                       'in_verify'       => Report_Parent::$TOTAL_AS_SUM),
		                                   'grand'   => array( 'in_underwriting' => Report_Parent::$TOTAL_AS_SUM,
		                                                       'funded'          => Report_Parent::$TOTAL_AS_SUM,
		                                                       'approved'        => Report_Parent::$TOTAL_AS_SUM,
		                                                       'withdrawn'       => Report_Parent::$TOTAL_AS_SUM,
		                                                       'denied'          => Report_Parent::$TOTAL_AS_SUM,
		                                                       'reverified'      => Report_Parent::$TOTAL_AS_SUM,
		                                                       'in_verify'       => Report_Parent::$TOTAL_AS_SUM) );
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = true;
		$this->download_file_name = null;
		$this->ajax_reporting 	  = true;
		parent::__construct($transport, $module_name);
	}

}

?>
