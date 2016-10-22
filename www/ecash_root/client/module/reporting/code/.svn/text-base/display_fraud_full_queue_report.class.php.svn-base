<?php

/**
 * @package Reporting
 * @category Display
 */
class Fraud_Full_Queue_Report extends Report_Parent
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

		$this->report_title       = "Fraud Full Queue Report";

		$this->column_names       = array( 'company_name'	=> 'Company',
										   'queue_name'     => 'Queue',
										   'application_id' => 'App ID',
		                                   'last_name'		=> 'Last Name',
		                                   'first_name' 	=> 'First Name',
		                                   'home_street' 	=> 'Home Street',
		                                   'home_city'  	=> 'Home City',
		                                   'home_state'  	=> 'Home State',
		                                   'home_zip'  		=> 'Home Zip Code',
		                                   'home_phone'  	=> 'Home Phone',
		                                   'employer'  		=> 'Employer',
		                                   'employer_phone' => 'Employer Phone',
		                                   'income'  		=> 'Income',
		                                   'pay_period'  	=> 'Pay Period',
		                                   'bank_name'  	=> 'Bank Name',
		                                   'bank_aba' 		=> 'Bank ABA',
		                                   'principal_amount'  	=> 'Principal Amount',
		                                   'first_due'  	=> 'First Due Date',
		                                   'email_address' 	=> 'Email Address',
		                                   'ip_address' 	=> 'IP Address',
		                                   'timestamp'		=> 'Timestamp' );

		$this->column_format       = array(
		                                   'next_due'	=> self::FORMAT_DATE,
		                                   'first_due' => self::FORMAT_DATE,
		                                   'income'		=> self::FORMAT_CURRENCY,
		                                   'principal_amount'	=> self::FORMAT_CURRENCY,
						   'timestamp'          => self::FORMAT_DATETIME );

		$this->sort_columns       = array(	'company_name', 	'queue_name',
											'application_id',	'last_name',
											'first_name', 		'home_city',
											'home_state', 		'home_zip',
											'employer',			'employer_phone',
											'bank_name',	
											'bank_aba',			'email_address',
											'ip_address');

        $this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

        $this->totals = array();
		$this->totals_conditions  = null;

		$this->date_dropdown      = null;
		$this->loan_type          = false;
		$this->download_file_name = null;
		$this->queue_dropdown = true;
		$this->ajax_reporting = true;
		parent::__construct($transport, $module_name);
	}

	protected function Get_Form_Options_HTML(stdClass &$substitutions)
	{
		parent::Get_Form_Options_HTML($substitutions);
		//echo "<!-- Get_Form_Options_HTML -->";
		//overrwrite the queue_name_list var
		
		//queue names
		$selected = $this->search_criteria['queue_name'];

		$list = "Queue : <select name='queue_name' size='1' style='width: 140px;'>";
		$queues = array('high_risk_queue' => 'High Risk', 'fraud_queue' => 'Fraud', 'both' => 'Both');
		foreach($queues as $queue_short => $queue_name)
		{
			$is_selected = ($selected == $queue_short ? "selected=\"selected\"" : "");
			$queue_name = htmlentities($queue_name);
			$list .= "<option value='$queue_short' $is_selected>$queue_name</option>";
		}
		$list .= "</select>";

		$substitutions->queue_name_list = $list;
		//end queue names
	}
}

?>
