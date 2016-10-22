<?php

/**
 * @package Reporting
 * @category Display
 */
class Agent_Email_Output_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title = "Outgoing Email Report";
		
		// use dynamic columns from the query.
		$this->column_names = null;
		$this->sort_columns = null;
		$this->totals = null;
		$this->link_columns = array();
		$this->column_format = null;
		
/*
		$this->column_names = $this->getColumnsNamesarray('company_name' => 'Company', 
			'agent' => 'Agent',
			'sent' => 'Sent',
			'opened' => 'Opened',
			'associated' => 'Associated',
			'responded' => 'Responded',
			'followups' => 'Follow Ups',
			'filed' => 'Filed',
			'queued' => 'Queue Change',
			'canned' => 'Canned Responses',
			'removed' => 'Removed',						
			'received' => 'Received',							
			'service_level' => 'Service Level',							
			'reply_rate' => 'Reply Rate',						
			'file_rate' => 'File Rate'							
		);

		$this->sort_columns = array(
			'agent',          
			'opened',
			'associated',
			'responded',
			'followups',
			'filed',
			'queued',
			'canned',
			'removed',
			'received',
			'service_level',							
			'reply_rate',						
			'file_rate'							
		);

		$this->link_columns = array();

		$this->totals = array( 'company' => array(
				'opened' => Report_Parent::$TOTAL_AS_SUM,
				'associated' => Report_Parent::$TOTAL_AS_SUM,
				'responded' => Report_Parent::$TOTAL_AS_SUM,
				'followups' => Report_Parent::$TOTAL_AS_SUM,
				'filed' => Report_Parent::$TOTAL_AS_SUM,
				'queued' => Report_Parent::$TOTAL_AS_SUM,
				'canned' => Report_Parent::$TOTAL_AS_SUM,
				'removed' => Report_Parent::$TOTAL_AS_SUM,
				'received' => Report_Parent::$TOTAL_AS_SUM
			), 'grand' => array(
				'opened' => Report_Parent::$TOTAL_AS_SUM,
				'associated' => Report_Parent::$TOTAL_AS_SUM,
				'responded' => Report_Parent::$TOTAL_AS_SUM,
				'followups' => Report_Parent::$TOTAL_AS_SUM,
				'filed' => Report_Parent::$TOTAL_AS_SUM,
				'queued' => Report_Parent::$TOTAL_AS_SUM,                                             
				'canned' => Report_Parent::$TOTAL_AS_SUM,
				'received' => Report_Parent::$TOTAL_AS_SUM,
			));

		$this->column_format = array(
			'opened' => self::FORMAT_NUMBER,
			'associated' => self::FORMAT_NUMBER,
			'responded' => self::FORMAT_NUMBER,
			'followups' => self::FORMAT_NUMBER,
			'filed' => self::FORMAT_NUMBER,
			'queued' => self::FORMAT_NUMBER,
			'canned' => self::FORMAT_NUMBER,
			'removed' => self::FORMAT_NUMBER,		
			'recieved' => self::FORMAT_NUMBER		
		);
*/

		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->download_file_name = null;
		$this->loan_type          = TRUE;
		$this->agent_list = FALSE;
		$this->wrap_header = FALSE;
		$this->ajax_reporting 	  = true;
		parent::__construct($transport, $module_name);
	}
}

?>
