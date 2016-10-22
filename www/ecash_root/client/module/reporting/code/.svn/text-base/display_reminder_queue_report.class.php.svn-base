<?php

/**
 * @package Reporting
 * @category Display
 */
class Reminder_Queue_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title = "Reminder Queue";
		$this->column_names = array(
				'company_name' => 'Company',
				'application_id' => 'Application Id' ,
				'name_last' => 'Last Name' ,
				'name_first' => 'First Name' ,
				'date_event' => 'Trans. Sched. Date' ,
				'arranged' => 'Contact Arranged' ,
				'agent_name' => 'Owning Agent' ,
				);
		$this->column_width = array("date" => 150);
		$this->sort_columns = array(
				'application_id',
				'last',
				'first',
				'date',
				'arranged',
				'agent',
				);
		$this->link_columns = array(
				'application_id' => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%',
				);
		$this->column_format = array(
				'date' => self::FORMAT_DATE,
				);
		$this->totals = array(
				'company' => array(),
				'grand' => array(),
				);

		$this->totals_conditions = null;
		$this->date_dropdown = null;
		$this->loan_type = FALSE;
		$this->agent_list = TRUE;
		$this->agent_list_include_unassigned = FALSE;
		$this->download_file_name = null;
		$this->ajax_reporting = true;
		parent::__construct($transport, $module_name);
	}
}

?>
