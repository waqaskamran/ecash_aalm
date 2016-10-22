<?php

/**
 * @package Reporting
 * @category Display
 */
class Current_Lead_Status_Report extends Report_Parent
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

		$this->report_title       = "Current Lead Status Report";

		$this->column_names       = array(	'company'                   => 'Company',
											'date_bought'               => 'Date Lead Bought',
											'num_bought'                => 'Bought',
											'num_unsigned'              => 'Unsigned',
											'num_expired'               => 'Expired',
											'num_agree'                 => 'Agree',
											'num_confirm_declined'      => 'Confirm Declined',
											'num_disagree'              => 'Disagree',
											'num_pending'               => 'Pending',
											'num_withdrawn'             => 'Withdrawn',
											'num_denied'                => 'Denied',
											'num_funded'                => 'Funded',
											'num_funding_failed'        => 'Funding Failed');
										
		$this->column_format      = array( 'date_bought'               => self::FORMAT_DATE );

		$this->sort_columns       = array(	'date_bought',	
											'num_bought', 
											'num_unsigned',
											'num_expired', 
											'num_agree',
											'num_confirm_declined',
											'num_disagree',
											'num_pending', 
											'num_withdrawn', 
											'num_denied', 
											'num_funded', 
											'num_funding_failed' ); 


        $this->totals 			   = array( 
											'company' => array(	'num_bought', 
																'num_unsigned',
																'num_expired', 
																'num_agree',
																'num_confirm_declined',
																'num_disagree',
																'num_pending', 
																'num_withdrawn', 
																'num_denied', 
																'num_funded', 
																'num_funding_failed' ),
											'grand' => array(	'num_bought', 
																'num_unsigned',
																'num_expired', 
																'num_agree',
																'num_confirm_declined',
																'num_disagree',
																'num_pending', 
																'num_withdrawn', 
																'num_denied', 
																'num_funded', 
																'num_funding_failed' )
											);




		$this->totals_conditions   = null;

		$this->date_dropdown       = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type           = false;
		$this->download_file_name  = null;
		$this->ajax_reporting 	   = true;
		$this->company_list_no_all = false;
		parent::__construct($transport, $module_name);
	}
}

?>
