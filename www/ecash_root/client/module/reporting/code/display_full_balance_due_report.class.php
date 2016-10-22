<?php

/**
 * @package Reporting
 * @category Display
 */
class Full_Balance_Due_Report extends Report_Parent
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

		$this->report_title       = "Projected Full Balance Due";


		$this->column_names       = array(
						   'company_name' => 'Company',
						   'princ_amt'		=> 'Principal Balance',
						   'int_amt'		=> 'Interest Balance',
						   'fee_amt'		=> 'Fee Balance',
						   'total_due'		=> 'Total Balance',
						   'date_event'		=> 'Action Date',
						   'date_effective'	=> 'Effective Date',
						   'application_id' => 'Application ID',
						   'name_last'		=> 'Last Name',
						   'name_first'		=> 'First Name',
						   'phone_home'		=> 'Home Phone',
						   'phone_cell'		=> 'Cell Phone',
						   'phone_work'		=> 'Work Phone',
						   'street'			=> 'Address',
						   'city'			=> 'City',
						   'state'			=> 'State',
						   'zip_code'		=> 'Zip',
						 );
						 
		$this->column_format      = array(
		                                   'princ_amt'		=> self::FORMAT_CURRENCY, 
		                                   'int_amt' 		=> self::FORMAT_CURRENCY, 
		                                   'fee_amt' 		=> self::FORMAT_CURRENCY, 
		                                   'total_due'      => self::FORMAT_CURRENCY,
										   'date_event'		=> self::FORMAT_DATE,
										   'date_effective'	=> self::FORMAT_DATE,
		                                   );						 

		$this->sort_columns       = array( 'application_id', 'date_effective', 'princ_amt','int_amt', 'fee_amt', 'total_due' );

		$this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

		$this->totals             = array( 'company' => array(),
		                                   'grand'   => array()
		                                 );

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = true;
		$this->download_file_name = null;
		$this->ajax_reporting 	  = true;
		parent::__construct($transport, $module_name);
	}
}

?>
