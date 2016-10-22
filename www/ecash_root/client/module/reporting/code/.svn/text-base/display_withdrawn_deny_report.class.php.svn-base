<?php

/**
 * @package Reporting
 * @category Display
 */
class Withdrawn_Deny_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Withdrawn / Denied Report";
		$this->column_names       = array( 'application_id' => 'Application ID',
		                                   'name'           => 'Withdrawn / Denied',
		                                   'comment'        => 'Reason' );
		$this->sort_columns       = array( 'application_id', 'name' );
		$this->link_columns       = array( 'application_id' => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%' );
		$this->totals             = array( 'company' => array( 'rows' ),
		                                   'grand'   => array( 'rows' ) );
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = true;
		$this->download_file_name = null;

		parent::__construct( $transport, $module_name );
	}
}

?>
