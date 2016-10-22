<?php

/**
 * @package Reporting
 * @category Display
 */
class Corrections_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{

		$this->report_title       = "Corrections Report";
		$this->column_names       = array( 'application_id' => 'Application ID',
		                                   'correction'     => 'Correction');

		$this->sort_columns       = array( 'application_id', 'correction');
		$this->link_columns       = array( 'application_id' => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%' );
		$this->totals             = array( 'company' => array( 'rows' ),
		                                   'grand'   => array( 'rows') );
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = true;
		$this->download_file_name = null;
		$this->ajax_reporting 	  = true;
		parent::__construct($transport, $module_name);
	}
}

?>
