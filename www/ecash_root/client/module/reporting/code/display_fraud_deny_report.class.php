<?php

/**
 * @package Reporting
 * @category Display
 */
class Fraud_Deny_Report extends Report_Parent
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

		$this->report_title       = "Fraud Denied Report";

		$this->column_names       = array('company' => 'Company',
										  'name' => 'Rule Name',
										  'comments' => 'Rule Description',
										  'count' => '# Denied'
										   );
		
		$this->sort_columns       = array( 'name', 'comments', 'count' );

        $this->totals = null;
		$this->totals_conditions  = NULL;
		$this->ajax_reporting = true;
		
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = true;
		$this->download_file_name = NULL;

		//fraud module can show all companies
		$this->company_list = true;


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
	protected function Format_Field( $name, $data, $totals = FALSE, $html = TRUE )
	{
		switch( $name )
		{
			case 'name':
			case 'comments':
				return ucwords($data);
			case 'count':
				return number_format($data);
			default:
				return $data;
		}
	}
}

?>
