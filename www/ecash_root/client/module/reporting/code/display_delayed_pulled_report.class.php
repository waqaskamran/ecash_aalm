<?php

/**
 * @package Reporting
 * @category Display
 */
class Delayed_Pulled_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{

		$this->report_title = "Unpulled Audit Report";
		$this->column_names = array( 
										'company_name'		=> 'Company',
										'date_created'		=> 'Date Created',
										'date_agreed' 		=> 'Date Agreed',					
										'application_id' 	=> 'Application ID',
										'full_name'			=> 'Name',
										'type'				=> 'Type',
										'loan_type'				=> 'Loan Type',
		                             );
		$this->sort_columns = array(	'date_created',
										'date_agreed',
										'application_id',
										'full_name','type', 'loan_type');
		
		$this->column_format       = array(
								   'date_created'				=> self::FORMAT_DATETIME ,
								   'date_agreed'				=> self::FORMAT_DATETIME ,
                                   );
		$this->link_columns = array('application_id' => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%');
		$this->totals       = array( 'company' => array( 'rows' ),
		                             'grand'   => array( 'rows' ),
		                            );
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->download_file_name = null;
		$this->ajax_reporting 	  = true;
		parent::__construct($transport, $module_name);
	}
}

?>
