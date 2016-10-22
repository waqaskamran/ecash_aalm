<?php

/**
 * @package Reporting
 * @category Display
 */
class Fraud_Rules_Expiring_Report extends Report_Parent
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
		$this->report_title       = "Expiring Active Fraud/High Risk Rules Report";

		$this->column_names       = array( 'rule_type' 	=> 'Rule Type',
										   'name' => 'Rule Name',
										   'exp_date'  => 'Expiration Date'
										   );

		$this->sort_columns       = array( 'rule_type',	'name', 'exp_date');

		$this->column_format      = array('exp_date' => self::FORMAT_DATE);

		$this->company_list = FALSE;
        $this->totals = NULL;
		$this->totals_conditions  = NULL;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = FALSE;
		$this->download_file_name = NULL;
		
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
			default:
				return $data;
		}
	}	
}

?>
