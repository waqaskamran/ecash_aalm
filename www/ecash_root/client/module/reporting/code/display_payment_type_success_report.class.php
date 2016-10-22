<?php

/**
 * @package Reporting
 * @category Display
 */
class Payment_Type_Success_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{

		$this->report_title = "Payment Type Success Report";
		$this->column_names = array( 'Payment_Type'    		=> 'Payment Type',
		                            'Completed'   			=> 'Completed',
 									'Completed_Amount'		=> 'Completed Amount', 	
 									'Returned' 				=> 'Returned',
 									'Returned_Amount'		=> 'Returned Amount',
 		                            'Total_Payments'   		=> 'Total Payments Attempted',
		                            'Total_Amount'   		=> 'Total Amount Attempted',									
		                             );
		$this->sort_columns = array(	'Payment_Type',          
										'Total_Payments',
                                         'Total_Amount',
                                         'Completed',
                                         'Completed_Amount',
                                         'Returned',
                                         'Returned_Amount',
		                             	);
		$this->link_columns = array();
		$this->totals       = array( 'company' => array( 'Total_Payments'    	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Total_Amount' 		=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Completed'			=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Completed_Amount'    	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Returned'   			=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Returned_Amount'   	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 ),
		                             
		                             'grand'   => array( 'Total_Payments'   	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Total_Amount' 		=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Completed'			=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Completed_Amount'    	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Returned'   			=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Returned_Amount'   	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 ) 
		                            );
		$this->column_format      = array(
															'Total_Payments'   	=> self::FORMAT_NUMBER,
		                                                 'Total_Amount' 		=> self::FORMAT_CURRENCY, 
		                                                 'Completed'			=> self::FORMAT_NUMBER,
		                                                 'Completed_Amount'    	=> self::FORMAT_CURRENCY, 
		                                                 'Returned'   			=> self::FORMAT_NUMBER,
		                                                 'Returned_Amount'   	=> self::FORMAT_CURRENCY, 		
		                                   );		                            
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->download_file_name = null;
		$this->ajax_reporting     = true;

		parent::__construct($transport, $module_name);
	}
}

?>
