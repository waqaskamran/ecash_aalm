<?php

/**
 * @package Reporting
 * @category Display
 */
class Preact_Pending_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{

		$this->report_title = "Preact Pending Report";
		$this->column_names = array( 'date_created'					=> 'Date Created',
									 'parent_application_id'    	=> 'Parent App ID',
									 'preact_application_id'    	=> 'Preact App ID',
									 'preact_date_fund_estimated'	=> 'Preact Est. Fund Date'
		                             );
		$this->column_format      = array(
		                                   'date_created'   			=> self::FORMAT_DATE,
		                                   'preact_date_fund_estimated' => self::FORMAT_DATE,

		                                   );		                             
		$this->sort_columns = array(	'date_created',          
										'parent_application_id',
										'preact_application_id',
										'preact_date_fund_estimated'
   									 );
		$this->link_columns = array( 'parent_application_id'  => '?module=%%%parent_module%%%&mode=%%%parent_mode%%%&show_back_button=1&action=show_applicant&application_id=%%%parent_application_id%%%',
									 'preact_application_id'  => '?module=%%%preact_module%%%&mode=%%%preact_mode%%%&show_back_button=1&action=show_applicant&application_id=%%%preact_application_id%%%',
									 );
		$this->totals       = array( 'company' => array(),
		                             
		                             'grand'   => array( ) 
		                            );
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->download_file_name = null;
		$this->ajax_reporting	  = true;
		parent::__construct($transport, $module_name);
	}
}

?>
