<?php

/**
 * @package Reporting
 * @category Display
 */
class Fraud_Balance_Report extends Report_Parent
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
		$this->report_title       = 'Fraud Balance Report';

		$this->column_names       = array( 
											'company_name' => 'Company',
											'destination' => '',
										   'underwriting' => 'Underwriting',
										   'verification' => 'Verification',
										   'withdrawn' => 'Withdrawn',
										   'denied' => 'Denied',
										   'total' => 'Total'
										   );
		
		$this->column_format      = array( 
		                                   'underwriting'	=> self::FORMAT_ABS,
		                                   'verification'   => self::FORMAT_ABS,
		                                   'withdrawn'      => self::FORMAT_ABS,
		                                   'denied'     	=> self::FORMAT_ABS,
		                                   'total'	     	=> self::FORMAT_ABS
		                                   );

		$this->sort_columns       = NULL;
		$this->totals             = array( 'company' => array( 
		                                                       'underwriting'	=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'verification'	=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'withdrawn'		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'denied'			=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'total'			=> Report_Parent::$TOTAL_AS_SUM,
															   ));

		$this->company_list = TRUE;
		$this->totals_conditions  = NULL;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = TRUE;
		$this->download_file_name = NULL;
		
		parent::__construct($transport, $module_name);
	}
}

?>
