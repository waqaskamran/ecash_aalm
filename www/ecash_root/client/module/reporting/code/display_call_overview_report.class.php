<?php

/**
 * @package Reporting
 * @category Display
 */
class Call_Overview_Report extends Report_Parent
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

		$this->report_title       = "Call Overview Report";

		$this->column_names       = array( 'date_created'	=> 'Date Created',
										   'application_id' => 'App ID',
		                                   'contact_type'	=> 'Contact Type',
		                                   'category_type' 	=> 'Contact Catagory',
		                                   'contact_phone' 	=> 'Contact Phone',
		                                   'agent_name'     => 'Agent',
		                                   'CallTime'  		=> 'Call Time (secs)',
		                                   'CallLatency'   	=> 'Call Latency (secs)'
		                                   );

		$this->column_format       = array();

		$this->sort_columns       = array(	'date_created', 	'agent_name',
											'application_id',	'contact_type',
											'category_type', 		'contact_phone',
											'CallTime', 		'CallLatency');

        $this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

        $this->totals = array();
		$this->totals_conditions  = null;

		$this->agent_list = TRUE;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = false;
		$this->download_file_name = null;
		$this->queue_dropdown = true;
		$this->agent_list_include_unassigned = false;
		$this->ajax_reporting 	  = true;
		
		parent::__construct($transport, $module_name);
	}


}

?>
