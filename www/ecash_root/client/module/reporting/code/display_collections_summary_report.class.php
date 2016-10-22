<?php

/**
 * @package Reporting
 * @category Display
 * @todo Move __construct settings into the class itself.
 */
class Collections_Summary_Report extends Report_Parent
{

	/**
	* Constructor, sets a few choice vars then lets Report_Parent work
	*
	* @param Transport $transport   Data passed from the server side
	* @param string    $module_name not used
	* @access public
	*/
	public function __construct(ECash_Transport $transport, $module_name)
	{
		if (defined("SCRIPT_TIME_LIMIT_SECONDS"))
		{
			set_time_limit(SCRIPT_TIME_LIMIT_SECONDS);
		}

		$this->report_title       = "Collections Summary";
		$this->column_names       = array( 'date_event'    => 'Date' ,
										   'num_scheduled'    => '# of Scheduled' , 
		                                   'amount_scheduled'      => '$ of Scheduled',
		                                   'num_returned'     => '# of Returns',
		                                   'amount_returned'      => '$ of Returns',
		                                   'num_scheduled_future'         => "Today\'s # of Future Scheduled Accounts",
		                                   'amount_scheduled_future'           => "Today\'s $ value of Future Scheduled Accounts",
		                     			 );
		$this->sort_columns       = array( 'date_event', 'num_scheduled',
		                                   'amount_scheduled',     'num_returned',
		                                   'amount_returned',	'num_scheduled_future',
		                                   'amount_scheduled_future', 
		                                    );
		$this->column_format       = array( 'date_event' => self::FORMAT_DATE,
											'amount_scheduled' => self::FORMAT_CURRENCY,
											'amount_returned' => self::FORMAT_CURRENCY,
											'amount_scheduled_future' => self::FORMAT_CURRENCY,
											 );
		$this->link_columns       = null;
		$this->totals             = array( 'company' => array(),
										   'grand' => array() );
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
//		$this->company_list_no_all = true;
		$this->download_file_name = null;
		$this->ajax_reporting	  = true;
		$this->max_end_date = true;
		$this->max_days_forward = 10;


		parent::__construct($transport, $module_name);	
	}
	
}

?>
