<?php

/**
 * @package Reporting
 * @category Display
 */
class Cc_Payments_Due_Report extends Report_Parent
{
	/**
	* count of customers in each pay period
	* @var array
	* @access private
	*/
	private $run_totals;

	/**
	* Grand totals for all companies
	* @var array
	* @access private
	*/
	private $grand_totals;

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

		$this->report_title       = "Projected Credit Card Payments Due";


		$this->column_names       = array(
						   'company_name' => 'Company',
						   'agent_login' => 'Agent' ,
						   'application_id' => 'Application ID',
						   'customer_name'	=> 'Customer Name',
						   'princ_amt'		=> 'Principal Amt.',
						   'non_princ_amt'	=> 'Fee Amt.',
						   'total_due'		=> 'Total Due',
						   
						 );
						 
		$this->column_format      = array(
		                                   'princ_amt'	=> self::FORMAT_CURRENCY, 
		                                   'non_princ_amt'        => self::FORMAT_CURRENCY, 
		                                   'total_due'        => self::FORMAT_CURRENCY, 
		                                   );						 

		$this->sort_columns       = array( 'application_id', 'date_effective', 'payment_type', 'agent_login','customer_name','princ_amt','non_princ_amt' );

		$this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

		$this->totals             = array( 'company' => array(),
		                                   'grand'   => array()
		                                 );

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_SPECIFIC;
		$this->agent_list         = true;
		$this->loan_type          = true;
		$this->download_file_name = null;
		$this->ajax_reporting 	  = true;
		parent::__construct($transport, $module_name);
	}

	/**
	 * Definition of abstract method in Report_Parent
	 * Used to format field data for printing
	 *
	 * @param  string  $name   column name to format
	 * @param  string  $data   field data
	 * @param  boolean $totals formatting totals or data?
	 * @param  boolean $html   format for html?
	 * @return string          formatted field
	 * @access protected
	 */
	protected function Format_Field( $name, $data, $totals = false, $html = true )
	{
		switch( $name )
		{
			case 'date_effective':
				return(date('D M. jS, Y',strtotime($data)));
			default:
				return parent::Format_Field($name, $data, $totals, $html);
		}
	}
}

?>
