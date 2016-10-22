<?php

/**
 * @package Reporting
 * @category Display
 */
class Batch_Totals_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Landmark Batch Totals";
		$this->column_names       = array( 'batch_date'  => 'Batch Date',
		                                   'batch_total_amount'          => 'Batch Total',
		                                   'batch_total' => 'Batch Count',
		                                   'num_returned'          => 'Return Count',
		                                   'num_returned_amount'	=> 'Returned Amount',
		                                   'return_rate' => 'Return Rate');
	
		$this->column_format	  = array( 'batch_date' => self::FORMAT_DATE,
											'batch_total_amount' => self::FORMAT_CURRENCY,
											'num_returned_amount' => self::FORMAT_CURRENCY,
											'return_rate' => self::FORMAT_PERCENT);
		$this->column_width		  = array('description'		=>	250);
		$this->totals             = array('company'   => array( 'rows','batch_total_amount' => Report_Parent::$TOTAL_AS_SUM, 'batch_total' => Report_Parent::$TOTAL_AS_SUM, 'num_returned' => Report_Parent::$TOTAL_AS_SUM, 'num_returned_amount' => Report_Parent::$TOTAL_AS_SUM, 'return_rate' => Report_Parent::$TOTAL_AS_AVERAGE));
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = false;
		$this->company_list		  = false;
		$this->download_file_name = null;
		$this->ajax_reporting = true;
		parent::__construct($transport, $module_name);
	}
	
	/**
	 * As the data comes into this function, it will have the return rate as an average of the rows' return rates, 
	 * not the numbers. This fixes that.
	 */
	protected function Get_Total_HTML($company_name, &$company_totals)
	{
		$company_totals['return_rate'] = $company_totals['num_returned'] / $company_totals['batch_total'] * 100;
		return parent::Get_Total_HTML($company_name, $company_totals);
	}
	
}

?>
