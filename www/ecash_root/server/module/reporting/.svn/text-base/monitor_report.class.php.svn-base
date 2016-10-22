<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

include (COMMON_LIB_DIR  . "panachart.php");
include (LIB_DIR."/performance_monitor.class.php");
include ("report_generic.class.php");

class Report extends Report_Generic
{
	private $graph_colors;
	private $agent_colors;

	public function __construct(Server $server, $request, $module_name, $report_name )
	{
		parent::__construct( $server, $request, $module_name, $report_name );

		$this->graph_colors = array("CA" => "#0de88d", "D1" => "#6070ff", "PCL" => "#ff0000", "UCL" => "#ffce0a", "UFC" => "#000000");
	}

	public function Get_Prompt_Reference_Data()
	{		// no reference data needed for IDV Score report prompt
		ECash::getTransport()->Add_Levels("report_initialization");
		$data = new stdClass();
		$data->prompt_reference_agents = Get_All_Agents($this->server->company_id);
		ECash::getTransport()->Set_Data($data);
	}

	public function Generate_Report()
	{

		$return_data = (object)array();

		if( isset($this->request->monitor_report_type) )
		{
			$return_data->last_monitor_report_type = $this->request->monitor_report_type;
		}
		
		if ( isset($this->request->agent_id) )
		{
			$agent_id = $return_data->last_agent_id = $this->request->agent_id;
		}
		
		if (empty($agent_id)) 
		{
			$agent_id = null;
		}

		$monitor = new Performance_Monitor(REQUEST_LOG_PATH);

/*		$monitor->Set_Track_Items(array(
			"fetch_loan_all" => "All Loan Data Total",
			"get_next_time" => "Get Next App Time",
			"id_verification" => "ID Verification Time",
			"searching_for_application" => "Application Searches",
			"overall_request" => "Total Request Time",
			)); */

		$monitor->Set_Track_Items(array(
			'elapsed_time' => "Total Request Time",
		));

		switch($this->request->monitor_report_type)
		{
			default:
			case "performance_1_minute":
			$report_type = "1 Minute Average";
			//$end_stamp = strtotime("2005-03-29 09:00");
			$end_stamp = strtotime("now");
			$start_stamp = strtotime("-15 minutes", $end_stamp);
			$interval = "1 minute";
			$date_format = "g:i";
			break;

			case "performance_5_minute";
			$report_type = "5 Minute Average";
			//$end_stamp = strtotime("2005-03-29 09:00");
			$end_stamp = strtotime("now");
			$start_stamp = strtotime("-75 minutes", $end_stamp);
			$interval = "5 minutes";
			$date_format = "g:i";
			break;

			case "performance_10_minute";
			$report_type = "10 Minute Average";
			//$end_stamp = strtotime("2005-03-29 09:00");
			$end_stamp = strtotime("now");
			$start_stamp = strtotime("-150 minutes", $end_stamp);
			$interval = "10 minutes";
			$date_format = "g:i";
			break;

			case "performance_1_hour";
			$report_type = "1 Hour Average";
			//$end_stamp = strtotime("2005-03-29 09:00");
			$end_stamp = strtotime("now");
			$start_stamp = strtotime("-10 hours", $end_stamp);
			$interval = "1 hour";
			$date_format = "g:i";
			break;

			case "performance_1_day";
			$report_type = "1 Day Average";
			//$end_stamp = strtotime("2005-03-29 09:15");
			$end_stamp = strtotime("now");
			$start_stamp = strtotime("-15 days", $end_stamp);
			$interval = "1 day";
			$date_format = "m-d";
			break;
		}

		$monitor_results = $monitor->Parse_Stats($start_stamp, $end_stamp, $interval, $date_format, $agent_id);
		
		$data_x = $monitor->Get_Ticks();

		$_SESSION['monitor_images'] = array();
		
		$return_data->color_data = $this->Build_Report_Colors($agent_id);

		$_SESSION['monitor_images'] = $this->Blue_Line_Graph($monitor_results, $data_x, $report_type, $return_data->color_data);

		$return_data->monitor_images = array_keys($_SESSION['monitor_images']);

		$return_data->session_id = session_id();

		$return_data->monitor_submit = $this->request->monitor_submit; //mantis:5614

		ECash::getTransport()->Set_Data($return_data);
	}
	
	// This function pulls structured random colors with the end
	// goal of neighboring colors not being very similar.
	protected function Build_Report_Colors($agent_ids) {
		$color_table = array('00', 'cc', '44', '88');
		$masks = array(0x11, 0x0a, 0x24);
		$index_counter = 0;
		$ic_max = pow(count($color_table), 3);
		
		$this->agent_colors = array();
		if (is_array($agent_ids)) 
		{
			foreach ($agent_ids as $agent_id) 
			{
			$color = array();
				switch ($index_counter & $masks[0]) 
				{
					case 0x01:
						$color[] = $color_table[1];
						break;
					case 0x10:
						$color[] = $color_table[2];
						break;
					case 0x11:
						$color[] = $color_table[3];
						break;
					default:
						$color[] = $color_table[0];
						break;
				}
				
				switch ($index_counter & $masks[1]) 
				{
					case 0x02:
						$color[] = $color_table[1];
						break;
					case 0x08:
						$color[] = $color_table[2];
						break;
					case 0x0a:
						$color[] = $color_table[3];
						break;
					default:
						$color[] = $color_table[0];
						break;
				}
				
				switch ($index_counter & $masks[2]) 
				{
					case 0x04:
						$color[] = $color_table[1];
						break;
					case 0x20:
						$color[] = $color_table[2];
						break;
					case 0x24:
						$color[] = $color_table[3];
						break;
					default:
						$color[] = $color_table[0];
						break;
				}
				
				$this->agent_colors[$agent_id] = '#'.implode('', $color);
				
				$index_counter = ($index_counter + 1) % $ic_max;
			}
		}
		
		return $this->agent_colors;
	}

	public function Blue_Line_Graph($monitor_results, $datax, $report_type, $color_data)
	{
		$binary_images = array();
		if (is_array($monitor_results)) 
		{
			foreach($monitor_results as $item_monitored => $company)
			{
	
				$ochart = new chart(700,300,5, 'B0C4DE');
				$ochart->setTitle($item_monitored . " - " . $report_type,"#000000", 3);
				$ochart->setPlotArea(SOLID,"#444444", '#eff8f9');
				$ochart->setFormat(2,',','.');
				$ochart->setLabels($datax, '#000000', 3, HORIZONTAL);
				$ochart->setXAxis('#000000', SOLID, 1, "Date / Time");
				$ochart->setYAxis('#000000', SOLID, 2, "Elapsed Time in Seconds");
				$ochart->setGrid("#bbbbbb", DASHED, "#bbbbbb", DOTTED);
	
				foreach($company as $company_short => $data_point)
				{
					$datay = array();
	
					// Insert the data for the y axis and the names for the x axis
					foreach($data_point as $name => $data)
					{
						$data_average = (array_sum($data) / count($data));
						//$data_pos = array_search($name, $datax);
	
						$datay[$name] = $data_average;
					}
					
					if (count($color_data)) 
					{
						$color = $color_data[$company_short];
					} 
					else 
					{
						$color = isset($this->graph_colors[$company_short]) ? $this->graph_colors[$company_short]: "#000000";
					}
	
					// Create the line
					$ochart->addSeriesAssoc($datay,'line','Series1', SOLID, $color, $color);
				}
	
				// Load image into the session
				$binary_images["{$item_monitored}.png"] = $ochart->plotString();
			}
		}
		return $binary_images;
	}

	public function Get_Last_Report()
	{
		return true;
	}

	public function Download_Report()
	{
		return true;
	}
}

?>
