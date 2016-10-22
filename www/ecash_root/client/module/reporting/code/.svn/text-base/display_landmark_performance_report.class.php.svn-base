<?php
/**
 * @package Reporting
 * @category Display
 */
class Landmark_Performance_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Landmark Performance Report";

		$this->column_names       = array( 'num_completed'   => 'Completed',
		                                   'num_failed'      => 'Failed',
		                                   'num_pending' 	 => 'Pending',
		                                   'total'           => 'Total');

		$this->sort_columns       = array( 'num_completed', 'num_failed', 'num_pending', 'total' );
		
		$this->column_format	  = array( 'num_completed'    => self::FORMAT_NUMBER,
										   'num_failed'       => self::FORMAT_NUMBER,
									       'num_pending'      => self::FORMAT_NUMBER,
										   'total'            => self::FORMAT_NUMBER);
										   
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = false;
		$this->company_list		  = false;
		$this->ajax_reporting     = true; 
		$this->download_file_name = null; 
		parent::__construct($transport, $module_name);
	}
}
?>
