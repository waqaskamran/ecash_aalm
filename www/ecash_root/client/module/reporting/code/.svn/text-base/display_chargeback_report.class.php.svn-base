<?php

/**
 * @package Reporting
 * @category Display
 */
class Chargeback_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{

		$this->report_title = "Chargeback Report";
		$this->column_names = array( 
									 'company_name'			=> 'Company',
									 'date_created'         => 'Date',
									 'application_id'		=> 'Application ID',
									 'full_name'			=> 'Name',
									 'chargeback_type'		=> 'Chargeback Type',
									 'amount'				=> 'Amount',

		                             );
		$this->sort_columns = array(	'date_created',
										'application_id',
										'full_name',
										'chargeback_type',
										'amount');
		
		$this->column_format       = array(
								   'date_created'				=> self::FORMAT_DATETIME,
								   'date_modified'				=> self::FORMAT_DATETIME ,
                                   'amount'  					=> self::FORMAT_CURRENCY 
                                   );
		$this->link_columns = array('application_id' => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%');
		$this->totals       = array( 'company' => array( 'rows',
														 'amount'        => Report_Parent::$TOTAL_AS_SUM
														),
									 'grand'   => array('rows', 
									 					'amount'        => Report_Parent::$TOTAL_AS_SUM
									 					),		
		                            );
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->chargeback_report  = true;
		$this->ajax_reporting 	  = true;
		$this->loan_type          = true;
		parent::__construct($transport, $module_name);
	}
}

?>
