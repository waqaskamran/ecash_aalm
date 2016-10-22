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
class Process_Status_Report extends Report_Parent {

	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title = "Process Status";
		$this->column_names = array(
				'business_day' => 'Run Day',
				'process_log_id' => 'Process Log ID' ,
				'process_step' => 'Process Step Name' ,
				'process_state' => 'Status' ,
				'start_date' => 'Start' ,
				'end_date' => 'End' ,
				'duration' => 'Duration' ,
				);
		$this->sort_columns = array(
				'business_day',
				'process_step',
				'process_state',
				'start_date',
				'end_date',
				'duration',
		);
		$this->link_columns = array();
		$this->totals = array(
				'company' => array(),
				'grand' => array(),
				);

		$this->totals_conditions = null;
		$this->date_dropdown =  Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type = FALSE;
		$this->agent_list = FALSE;
		$this->download_file_name = null;

		parent::__construct($transport, $module_name);
	}

	
	public function Get_Data_HTML($company_data, &$company_totals)
	{
		$row_toggle = true;  // Used to alternate row colors
		$line       = "";

		$begin_day = $company_data[0]['business_day'];
		$line .= $this->Get_Day_Header($begin_day, count($company_data[0]));
		
		for( $x = 0 ; $x < count($company_data) ; ++$x )
		{
			if($company_data[$x]['business_day'] != $begin_day) 
			{
				$begin_day = $company_data[$x]['business_day'];
				$row_toggle = true;
				
				$line .= $this->Get_Day_Header($begin_day, count($company_data[0]));
			}
			
			$td_class = ($row_toggle = ! $row_toggle) ? "align_left" : "align_left_alt";
			$td_class_stat = $td_class;
			
			switch ($company_data[$x]['process_state']) 
			{
				case "failed":
					$td_class_stat = "align_left_warning";
					break;
					
				case "not started":
//					$td_class_stat = "align_left_alt_warning";
					$td_class_stat = "align_left_alt_yellow";
					if($company_data[$x]['business_day'] == date('Y-m-d')) 
					{
//						$td_class_stat = "align_left_alt_yellow";
						$td_class_stat = "align_left_alt_gray";
					}
					break;
					
			}
			
			$td_class_stat = ($company_data[$x]['process_state'] == 'failed') ? "align_left_warning" : $td_class_stat;

			// 1 row of data
			$line .= "    <tr>\n"; //var_dump($this->column_names);
			foreach( $this->column_names as $data_name => $column_name )
			{
				$align = 'left';
				$data = $this->Format_Field($data_name,  $company_data[$x][$data_name], false, true, $align);
				// the the data link to somewhere?
				if( count($this->link_columns) > 0 && isset($this->link_columns[$data_name]) && isset($company_data[$x]['mode']))
				{
					// do any replacements necessary in the link
					$this->parse_data_row = $company_data[$x];
					$href  = preg_replace_callback("/%%%(.*?)%%%/", array($this, 'Link_Parse'), $this->link_columns[$data_name]);
					if ($column_name == "Status") 
					{
						$line .= "     <td class=\"$td_class_stat\" style=\"text-align: $align;\"><a href=\"#\" onClick=\"parent.window.location='$href'\">" . $data . "</a></td>\n";
					} 
					else 
					{ 
						$line .= "     <td class=\"$td_class\" style=\"text-align: $align;\"><a href=\"#\" onClick=\"parent.window.location='$href'\">" . $data . "</a></td>\n";
					}
				}
				else
				{
					if ($column_name == "Status") 
					{
						$line .= "     <td class=\"$td_class_stat\" style=\"text-align: $align;\">" . $data . "</td>\n";
					} 
					else 
					{ 
						$line .= "     <td class=\"$td_class\" style=\"text-align: $align;\">" . $data . "</td>\n";
					}
				}

				// If the col's data matches the criteria, total it up
				if( $this->check_eval($company_data[$x], $data_name) && isset($this->totals['company'][$data_name]) )
				{
					switch($this->totals['company'][$data_name])
					{
						case self::$TOTAL_AS_COUNT:
							$company_totals[$data_name]++;
							break;
						case self::$TOTAL_AS_SUM:
							$company_totals[$data_name] += $company_data[$x][$data_name];
							break;
						default:
							// Dont do anything, somebody screwed up
					}
				}
			}
			$company_totals['rows']++;
			$line .= "    </tr>\n";
		}

		return $line;
		
	}
	
	public function Get_Day_Header($day, &$cnt)
	{
		$column_headers .= "    <tr>\n";
		$column_headers .= "    <th class=\"report_head\" colspan=\"".count($this->column_names)."\" style=\"text-align: right;\">$day</th>\n";
		$column_headers .= "    </tr>\n";
		
		return $column_headers;
		
	}
}

?>
