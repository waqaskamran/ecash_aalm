<?php

/**
 * @package Reporting
 * @category Display
 */
class Fraud_Queue_Report extends Report_Parent
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

		$this->report_title       = "High Risk/Fraud Queue Report";

		$this->column_names       = array( 'company_name'		=> 'Company',
											'time_in_queue' 	=> 'Hours',
										   'application_id' => 'Application ID',
										   'rules_display'  => 'Matching Rules'
										   );

		$this->sort_columns       = array( 'time_in_queue',	'application_id');

        $this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

        $this->totals = null;
		$this->totals_conditions  = null;

		$this->date_dropdown      = null;
		$this->loan_type          = false;
		$this->download_file_name = null;
		$this->ajax_reporting     = true;
		$this->queue_dropdown = true;
		
		parent::__construct($transport, $module_name);
	}

	/**
	 * Definition of abstract method in Report_Parent
	 * Used to format field data for printing
	 *
	 * @param  string  $name   column name to format
	 * @param  string  $data   field data
	 * @param  boolean $totals formatting totals or data?
	 * @param  boolean $html   format for html?
	 * @return string          formatted field
	 * @access protected
	 */
	protected function Format_Field( $name, $data, $totals = false, $html = true )
	{
		switch( $name )
		{
			case 'status':
			case 'time_in_queue':
				$data = round($data / (60 * 60));
				return $data;
			case 'application_id':
				if( $html === true )
					return "<div style='text-align:center;'>$data</div>";
				else
					return $data;
			case 'order':
				if( $html === true )
					return "<div style='text-align:right;'>$data</div>";
				else
					return $data;
			default:
				return $data;
		}
	}

	
	protected function Get_Form_Options_HTML(stdClass &$substitutions)
	{
		parent::Get_Form_Options_HTML($substitutions);

		//overrwrite the queue_name_list var
		
		//queue names
		$selected = $this->search_criteria['queue_name'];

		$list = "Queue : <select name='queue_name' size='1' style='width: 140px;'>";
		$queues = array('high_risk_queue' => 'High Risk', 'fraud_queue' => 'Fraud');
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
