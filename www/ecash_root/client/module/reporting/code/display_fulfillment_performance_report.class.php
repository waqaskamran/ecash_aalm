<?php

/**
 * @package Reporting
 * @category Display
 */
class Fulfillment_Performance_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Fulfillment Performance Report";
		$this->column_names       = array( 'promo_id'  	 => 'Promo ID',
		                                   'total'       => 'Total Submitted',
		                                   'funded' 	 => 'Total Fulfilled',
		                                   'performance' => 'Performance Ratio');

		$this->sort_columns       = array( 'promo_id', 'total', 'funded', 'performance' );
		
		$this->column_format	  = array( 'promo_id'    => self::FORMAT_ID,
										   'total'       => self::FORMAT_NUMBER,
										   'funded'      => self::FORMAT_NUMBER,
										   'performance' => self::FORMAT_PERCENT);
										   
//		$this->column_width		  = array('description'		=>	250);
//		$this->totals             = array('company'   => array( 'rows','batch_total_amount' => Report_Parent::$TOTAL_AS_SUM, 'batch_total' => Report_Parent::$TOTAL_AS_SUM, 'num_returned' => Report_Parent::$TOTAL_AS_SUM, 'num_returned_amount' => Report_Parent::$TOTAL_AS_SUM, 'return_rate' => Report_Parent::$TOTAL_AS_AVERAGE));
//		$this->totals_conditions  = null;

		$this->totals		      = array('company' => array('rows', 
                                                             'total'  => Report_Parent::$TOTAL_AS_SUM,
															 'funded' => Report_Parent::$TOTAL_AS_SUM));


		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = false;
		$this->company_list		  = false;
		$this->ajax_reporting     = true;
		$this->download_file_name = null; 
		parent::__construct($transport, $module_name);
	}
	

	
}

?>
