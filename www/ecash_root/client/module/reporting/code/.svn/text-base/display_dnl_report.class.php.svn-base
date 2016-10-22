<?php

/**
 * @package Reporting
 * @category Display
 */
class DNL_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{

		$this->report_title = "DNL Report";
		
		$this->column_names = array( 	
						'company_name'  => 'Company',
						'application_id' => 'App ID',
						'company_owner' => 'Company (Owner)',
						'name_first' => 'First Name',
						'name_last' => 'Last Name', 
						'category' => 'DNL Category',
						'explanation' => 'DNL Explanation',
						'date_created' 	=> 'DNL Set Date',
						'app_status' => 'Current Status',
						'agent_name' => 'Agent',
					);
		
		$this->sort_columns = array(	'application_id',
						'company_owner',
						'name_first',
						'name_last',
						'category',
						'explanation',
						'date_created',
						'app_status',
						'agent_name',
					);
		
		$this->column_format       = array(
						
						'date_created' => self::FORMAT_DATETIME,
                                   		 );

		$this->link_columns = array('application_id' => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%');
		
//		$this->totals       = array( 'company' => array( 'rows',
//								'amount'	=> Report_Parent::$TOTAL_AS_SUM
//								),
//						'grand'   => array('rows', 
//								'amount'        => Report_Parent::$TOTAL_AS_SUM
//								),
//		                            );

		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->dnl_report  = true;
		$this->loan_type          = true;
		$this->ajax_reporting 	  = true;
		parent::__construct($transport, $module_name);
	}
}

?>
