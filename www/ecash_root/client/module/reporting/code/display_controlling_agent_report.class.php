<?php

/**
 * Controlling_Agent_Report
 * Display class for the Controlling Agent Report
 *
 * Created on Dec 7, 2006
 *
 * @package Reporting
 * @category Display
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */
class Controlling_Agent_Report extends Report_Parent {

	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title = "Collections Agent";
		$this->column_names = array(
				'company_name' => 'Company' ,
				'app_id' => 'Application Id' ,
				'last' => 'Last Name' ,
				'first' => 'First Name' ,
				'arranged' => 'Contact Arranged' ,
				'agent' => 'Owning Agent' ,
				);
		$this->sort_columns = array(
				'company_name',
				'app_id',
				'last',
				'first',
				'arranged',
				'agent',
				);
				
		$this->link_columns = array(
				'app_id' => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%app_id%%%'
				);
		$this->totals = array(
				'company' => array(),
				'grand' => array(),
				);

		$this->totals_conditions = null;
		$this->date_dropdown = null;
		$this->loan_type = TRUE;
		$this->agent_list = TRUE;
		$this->download_file_name = null;
		$this->ajax_reporting 	  = true;
		parent::__construct($transport, $module_name);
	}
}

?>
