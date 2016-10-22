<?php

/**
 * @package Reporting
 * @category Display
 */
class CSO_Broker_Fee_Revenue_Report extends Report_Parent
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

		$this->report_title       = "CSO Broker Fee Revenue Report";

		$this->column_names       = array( 
				'company_name'               => 'Company',
				'date_effective'             => 'Date',
				'amount'                     => 'CSO Broker Fee',
				);
										
		$this->column_format       = array( 
				'date_effective'   => self::FORMAT_DATE,
				'amount'           => self::FORMAT_CURRENCY,
				);

		$this->sort_columns       = array_keys($this->column_names);

        $this->totals 			   = null;
		$this->totals_conditions   = null;

		$this->date_dropdown       = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->download_file_name  = null;
		$this->ajax_reporting 	   = true;
		$this->company_list_no_all = true;
		parent::__construct($transport, $module_name);
	}

}

?>