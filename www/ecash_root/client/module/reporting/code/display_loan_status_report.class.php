<?php

/**
 * @package Reporting
 * @category Display
 */
class Loan_Status_Report extends Report_Parent
{
	/**
	 * constructor, initializes data used by report_parent
	 *
	 * @param Transport $transport the transport object
	 * @param string $module_name name of the module we're in, not used, but keeps
	 *                            universal constructor call for all modules
	 * @access public
	 */
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->company_totals = array();

		$this->report_title       = "Loan Status Report";

		$this->column_names       = array(	'company'                   => 'Company',
											'date_bought'               => 'Date Lead Bought',
											'application_id'            => 'Application ID',
											'current_status'            => 'Current Loan Status');
										
		$this->column_format      = array(	'date_bought'               => self::FORMAT_DATE);

		$this->link_columns        = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );


		$this->sort_columns       = array(	'date_bought',
											'application_id',
											'current_status');


/*        $this->totals 			   = array('company' => array(	'num_bought',
																'num_funded',
																'funded_amt',
																'deposits_amt',
																'disbursements_amt'));*/


		$this->totals_conditions   = null;

		$this->date_dropdown       = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type           = false;
		$this->download_file_name  = null;
		$this->ajax_reporting 	   = true;
		$this->company_list_no_all = false;
		parent::__construct($transport, $module_name);
	}
}

?>
