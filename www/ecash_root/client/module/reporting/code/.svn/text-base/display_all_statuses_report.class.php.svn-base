<?php

/**
 * @package Reporting
 * @category Display
 */
class All_Statuses_Report extends Report_Parent
{
	private $grand_totals;

	public function __construct(ECash_Transport $transport, $module_name)
	{
		$hardcoded_status_array_because_jared_kleinman_isnt_a_bsa = array(
		'Pending',
		'Prospect Confirmed',
		'Confirm Declined',
		'Disagree',
		'Agree',
		'Withdrawn',
		'Denied',
		'Confirmed',
		'Confirmed Followup',
		'Approved',
		'Approved Followup',
		'Pending Expiration',
		'Pre-Fund',
		'Active',
		'Funding Failed',
		'Amortization',
		'Bankruptcy Notified',
		'Bankruptcy Verified',
		'Servicing Hold',
		'Past Due',
		'Collections New',
		'Collections Contact',
		'Contact Followup',
		'Made Arrangements',
		'Arrangements Failed',
		'Arrangements Hold',
		'Inactive (Paid)',
		'Chargeoff',
		'Second Tier (Pending)',
		'Second Tier (Sent)',
		'Inactive (Recovered)');

		$this->report_title       = "Status Snapshot Report";
		
		$columns = array(	'date' => 			'Date');
							
		foreach ($hardcoded_status_array_because_jared_kleinman_isnt_a_bsa as $status)
		{
			$columns[strtolower($this->sanitizeName($status))] = $status;
		}
		$this->column_names = $columns;
		
		$this->sort_columns       = null;
		$this->link_columns       = null;
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = false;
		$this->ajax_reporting 	  = true;
		$this->download_file_name = null;

		parent::__construct($transport, $module_name);
	}
	
	public function sanitizeName($name)
	{
		$fixed = str_replace(' ','_',$name);
		$fixed = str_replace('(','',$fixed);
		$fixed = str_replace(')','',$fixed);
		return $fixed;
	}
}

?>
