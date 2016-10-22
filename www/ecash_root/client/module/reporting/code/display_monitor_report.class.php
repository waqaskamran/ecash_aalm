<?php

/**
 * @package Reporting
 * @category Display
 */
class Monitor_Report extends Report_Parent
{
	private $report_data;

	public function __construct(ECash_Transport $transport, $module_name)
	{
/*
		$this->report_title       = "Monitor Report";
		$this->column_names       = array();
		$this->sort_columns       = array();
		$this->link_columns       = array();
		$this->totals             = array();
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_NONE;
		$this->loan_type          = false;
		$this->download_file_name = null;
*/
		parent::__construct($transport, $module_name);
		$this->report_data = ECash::getTransport()->Get_Data();
	}

	public function Get_Module_HTML()
	{
		$html = "";

		$html .= file_get_contents(CLIENT_MODULE_DIR . "reporting/view/ecash_monitor.html");

		$options = array("performance_1_minute"  => "Performance 1 Minute Average",
		                 "performance_5_minute"  => "Performance 5 Minute Average",
		                 "performance_10_minute" => "Performance 10 Minute Average",
		                 "performance_1_hour"    => "Performance 1 Hour Average",
		                 "performance_1_day"     => "Performance 1 Day Average");

		$report_options = "";

		foreach($options as $value => $name)
		{
			if( isset($this->report_data->last_monitor_report_type) && $this->report_data->last_monitor_report_type == $value )
			{
				$report_options .= "<option value=\"$value\" selected>$name</option>";
			}
			else
			{
				$report_options .= "<option value=\"$value\">$name</option>";
			}
		}
		
		$agent_options = '';
		foreach ($this->prompt_reference_agents as $agent_id => $agent_name) 
		{
			if( isset($this->report_data->last_agent_id) && in_array($agent_id, $this->report_data->last_agent_id))
			{
				$agent_options .= "<option value=\"$agent_id\" selected>$agent_name</option>";
			}
			else
			{
				$agent_options .= "<option value=\"$agent_id\">$agent_name</option>";
			}
		}
		
		$legend = '';
		if (isset($this->report_data->color_data) && is_array($this->report_data->color_data)) 
		{
			$legend = '<div style="background: #eeeeff; border: 1px solid black">';
			if (empty($this->report_data->color_data))
			{
				$legend .= '<div style="float: left; width: 31%; margin: 2px 1% 2px 1%; text-align: left;">';
				$legend .= '<div style="height: 12px; width: 12px; border: 1px solid black; background-color: #000000; float: left; margin-right: 3px"></div>';
				$legend .= 'Combined';
				$legend .= '</div>';
			}
			else
			{
				foreach ($this->report_data->color_data as $agent_id => $color) 
				{
					$legend .= '<div style="float: left; width: 31%; margin: 2px 1% 2px 1%; text-align: left;">';
					$legend .= '<div style="height: 12px; width: 12px; border: 1px solid black; background-color: '.$color.'; float: left; margin-right: 3px"></div>';
					$legend .= $this->prompt_reference_agents[$agent_id];
					$legend .= '</div>';
				}
			}
			$legend .= '<div style="clear: both;"></div>';
			$legend .= '</div>';
		}
		
		$html = str_replace("%%%legend%%%", $legend, $html);
		$html = str_replace("%%%report_options%%%", $report_options, $html);
		$html = str_replace("%%%agent_list%%%", $agent_options, $html);

		if( isset($this->report_data->monitor_images) && count($this->report_data->monitor_images) )
		{
			foreach($this->report_data->monitor_images as $image)
			{
				$html .= "<br><img src=\"/img_send.php?image_name={$image}&session_id={$this->report_data->session_id}\"><br>";
			}
		}
		else if (!empty($this->report_data->monitor_submit))
		{
			$html .= "<br><br><table width=\"100%\" border=\"0\"><tr><td><b>Could not find any data for the requested graph.</b></td></tr></table>";
		}

		return $html;
	}
}

?>
