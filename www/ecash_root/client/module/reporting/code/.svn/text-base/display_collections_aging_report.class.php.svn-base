<?php

/**
 * @package Reporting
 * @category Display
 */
class Collections_Aging_Report extends Report_Parent
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
		$this->report_title       = "Collections Aging report";
	
		$this->column_names       = array('today_date' => 'Date' ,
						'total_open'       => 'Total # Open Loans',
						'total_amount' =>          'Total $ PIF',
						'total_principal'  =>             'Total $ Principal',
						'total_interest' =>         'Total $ Interest',
						'total_fee' =>           'Total $ Fees',
						'total_del' =>  'Total # Delinquent',
						'total_amount_del'  =>         'Total $ Delinquent',
						'new_collections' =>      'New Collections #',
						'new_collections_amount' =>   'New Collections $',
						'num_pending' =>       'Pending Clear #',
						'amount_pending' =>          'Pending Clear $',
						'num_complete' =>           'Cleared Payments #',
						'amount_complete' =>            'Cleared Payments $',
						'ct1' =>            '1-29 CT',
						'amt1' =>            '1-29 $',
						'ct2' =>            '30-59 CT',
						'amt2' =>            '30-59 $',	
						'ct3' =>            '60-89 CT',
						'amt3' =>            '60-89 $',	
						'ct4' =>           '90 Charge Off #',
						'amt4' =>          '90 Charge Off $',
						'amount_recovered' =>   'Recovery $'
		                                   
										 );

		$this->column_format      = array(	'total_amount'           => self::FORMAT_CURRENCY,
							'amount_pending'           => self::FORMAT_CURRENCY,
							'new_collections_amount'           => self::FORMAT_CURRENCY,
							'today_date'				=> self::FORMAT_DATE,
							'amt1'           => self::FORMAT_CURRENCY,
							'amt2'           => self::FORMAT_CURRENCY,
							'amt3'           => self::FORMAT_CURRENCY,
							'amt4'           => self::FORMAT_CURRENCY,
							'amount_recovered'           => self::FORMAT_CURRENCY,
							'total_principal'           => self::FORMAT_CURRENCY,
							'total_interest'           => self::FORMAT_CURRENCY,
							'total_fee'           => self::FORMAT_CURRENCY,
							'total_amount_del'           => self::FORMAT_CURRENCY,
							

							'amount_complete'      => self::FORMAT_CURRENCY );

		$this->sort_columns       = array( 'date_created'  ,
										'total_open'     ,
										'total_amount' ,
										'total_principal'  ,
										'total_interest',
										'total_fee' ,
										'total_del' ,
										'total_amount_del'  ,
										'new_collections' ,
										'new_collections_amount',
										'num_pending' ,
										'amount_pending',
										'num_complete',
										'amount_complete' ,
										'ct1' ,
										'amt1' ,
										'ct2' ,
										'amt2' ,	
										'ct3' ,
										'amt3' ,	
										'ct4' ,
										'amt4' ,
										'recovery_amt' );

		$this->link_columns       = null;

		$this->totals             = null;
		$this->totals_conditions  = null;  // special is either 0 or 1 so SUM should give us the correct count.
		// $this->totals_conditions  = array( 'special' => " strlen('%%%var%%%') > 0 && '%%%var%%%' != '0' ? true : false " );

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		
		$this->download_file_name = null;
//		$this->company_list_no_all = true;
		$this->ajax_reporting 	  = true;
		parent::__construct($transport, $module_name);
	}
}

?>
