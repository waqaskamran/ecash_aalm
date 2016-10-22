<?php

/**
 * @package Reporting
 * @category Display
 */
class Internal_Recovery_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{
/*
			$row['Paid_Perc'] = ($row['Completed_Arrangments'] / $row['Total_Arrangments']) * 100;
			$row['Paid_Amount_Perc'] = ($row['Completed_Amount'] / $row['Total_Amount']) * 100;
			$row['Failed_Perc'] = ($row['Failed_Arrangments'] / $row['Total_Arrangments']) * 100;
			$row['Failed_Amount_Perc'] = ($row['Failed_Amount'] / $row['Total_Amount']) * 100;		
			*/
		$this->report_title = "Internal Collections Recovery Report";
		$this->column_names = array('company_name' => 'Company', 
									'trans_date'    			=> 'Date',
		                            'Completed_Arrangments' => 'Paid',
		                            'Paid_Perc' 			=> 'Paid %',
 									'Completed_Amount'		=> 'Paid Amt.',
 									'Paid_Amount_Perc' 		=> 'Paid Amt. %',
 									'Failed_Arrangments' 	=> 'Failed',
 									'Failed_Perc' 			=> 'Failed %',
 									'Failed_Amount'			=> 'Failed Amt.',
 									'Failed_Amount_Perc' 	=> 'Failed Amt. %',
 		                            'Total_Arrangments'   	=> 'Total Sched.',
		                            'Total_Amount'   		=> 'Total Sched. Amt.',									
		                             );
		$this->sort_columns = array(	'trans_date',          
										'Total_Arrangments',
                                         'Total_Amount',
                                         'Completed_Arrangments',
                                         'Completed_Amount',
                                         'Failed_Arrangments',
                                         'Failed_Amount',
                                         'Paid_Perc',
                                         'Paid_Amount_Perc',
                                         'Failed_Perc',
                                         'Failed_Amount_Perc',
		                             	);
		$this->link_columns = array();
		$this->totals       = array( 'company' => array( 'Total_Arrangments'    	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Total_Amount' 			=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Completed_Arrangments'	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Completed_Amount'    		=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Failed_Arrangments'   	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Failed_Amount'   			=> Report_Parent::$TOTAL_AS_SUM,
				                                         'Paid_Perc'				=> Report_Parent::$TOTAL_AS_AVERAGE,
				                                         'Paid_Amount_Perc'			=> Report_Parent::$TOTAL_AS_AVERAGE,
				                                         'Failed_Perc'				=> Report_Parent::$TOTAL_AS_AVERAGE,
				                                         'Failed_Amount_Perc'		=> Report_Parent::$TOTAL_AS_AVERAGE,		                                                 
		                                                 ),
		                             
		                             'grand'   => array( 'Total_Arrangments'    	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Total_Amount' 			=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Completed_Arrangments'	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Completed_Amount'    		=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Failed_Arrangments'   	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Failed_Amount'   			=> Report_Parent::$TOTAL_AS_SUM,
				                                         'Paid_Perc'				=> Report_Parent::$TOTAL_AS_AVERAGE,
				                                         'Paid_Amount_Perc'			=> Report_Parent::$TOTAL_AS_AVERAGE,
				                                         'Failed_Perc'				=> Report_Parent::$TOTAL_AS_AVERAGE,
				                                         'Failed_Amount_Perc'		=> Report_Parent::$TOTAL_AS_AVERAGE,		                                                 
		                                                 )
		                             
		                            );
		$this->column_format      = array(
														 'trans_date'				=> self::FORMAT_DATE,
														 'Total_Arrangments'   		=> self::FORMAT_NUMBER,
		                                                 'Total_Amount' 			=> self::FORMAT_CURRENCY, 
		                                                 'Completed_Arrangments'	=> self::FORMAT_NUMBER,
		                                                 'Completed_Amount'    		=> self::FORMAT_CURRENCY, 
		                                                 'Failed_Arrangments'   	=> self::FORMAT_NUMBER,
		                                                 'Failed_Amount'   			=> self::FORMAT_CURRENCY,
		                                                 
		                                             	 'Paid_Perc'				=> self::FORMAT_PERCENT,
		                                                 'Paid_Amount_Perc'    		=> self::FORMAT_PERCENT, 
		                                                 'Failed_Perc'   			=> self::FORMAT_PERCENT,
		                                                 'Failed_Amount_Perc'   	=> self::FORMAT_PERCENT,  		
		                                   );		                            
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->download_file_name = null;
		$this->ajax_reporting = true;
		$this->loan_type          = true;
		$this->agent_list = FALSE;
		$this->wrap_header = FALSE;
		parent::__construct($transport, $module_name);
	}
	
	private function FormatFloatDiv($apparr,$data,$dname)
	{
		$appstr = "";
		$row_toggle = null;
		if(count($apparr))
		{
			$appstr .= "<tr>";
			$appstr .= "<th nowrap class=report_head>Date</th>";	
			$appstr .= "<th nowrap class=report_head>Application ID</th>";
			$appstr .= "<th nowrap class=report_head>Transaction Status</th>";
			$appstr .= "<th nowrap class=report_head>Amount</th>";
			$appstr .= "<th nowrap class=report_head>Application Status</th>";
			$appstr .= "<th nowrap class=report_head>Transaction Type</th>";
			$appstr .= "</tr>";
			for($i=0; $i<count($apparr); $i++)
			{
				$app_data = $apparr[$i];
				$app_key =  $app_data["application_id"];
				$td_class = ($row_toggle = ! $row_toggle) ? "align_left" : "align_left_alt";
				$appstr .= "<tr>";
				$save_date = $app_data['trans_date'];
				$appstr .= "<td class={$td_class}>{$app_data['trans_date']}</td>";
				
				$appurl = "/?module=collections&mode=internal&show_back_button=1&action=show_applicant&application_id={$app_key}";
				$appstr .= "<td class={$td_class}><a href=\\\'#\\\' onClick=parent.window.location=\\\'$appurl\\\'>{$app_key}</a></td>";
				$appstr .= "<td class={$td_class}>{$app_data['tran_status']}</td>";
				$appstr .= "<td class={$td_class}>{$app_data['amount']}</td>";				
				$appstr .= "<td class={$td_class}>{$app_data['app_status_name']}</td>";
				$appstr .= "<td class={$td_class}>{$app_data['transaction_type']}</td>";
				$appstr .= "</tr>";				
			}
			$download_link = "<a href=\'/?module=reporting&mode=internal_recovery&action=download_report&detailed_view=$dname&detailed_date={$save_date}\'>Download Detailed Displayed Data to CSV File</a>";
			$appstr .= "</table>";
			
			$appstr_top = "<table class=report width=100%>";
			$appstr_top .= "<tr>";
			$appstr_top .= "<th nowrap class=report_head colspan=6>[ {$download_link} ]</th>";
			$appstr_top .= "</tr>";			
			$appstr_top .= $appstr;
			
			$data = "<a href='#' onclick=\"ShowAppIDsPopup('appdetails',event,'$appstr_top')\">".$data."</a>";
		}
		
		return $data;		
	}
	
	protected function Get_Report_Foot($html = true)
	{
		$html  = "<tr><th class='report_foot' colspan=".(count($this->totals['grand']) + 1).">";
		$html .= "Applicatin Details";
		$html .= "</th></tr>";		
		$html .= "<tr><th class='report_foot' colspan=".(count($this->totals['grand']) + 1).">";
		$html .= "<div id='appdetails' class='align_left_alt'' style='position:relative;visibility:hidden;'></div>";
		$html .= "</th></tr>";
		return $html;
	}
	
	protected function Get_Data_HTML($company_data, &$company_totals)
	{
		$row_toggle = true;  // Used to alternate row colors
		$line       = "
		<script type=\"text/javascript\">
		function getElement(id) 
		{
			var elem;
			if (document.getElementById) { // standard browsers
				elem = document.getElementById(id);
			} else if (document.all) { // IE 4
				elem = document.all[id];
			}
			return elem;
		}

		function ShowAppIDsPopup(id,event,appids) 
		{
			var elem = getElement(id);
			elem.innerHTML = appids;
			var elemStyle = elem.style || elem; // for NS4, not used here
			elemStyle.visibility = 'visible';
			
		}
		
		</script>				
		";

		$wrap_data   = $this->wrap_data ? '' : 'nowrap';

		foreach($company_data as $x => $values )
		{
			$td_class = ($row_toggle = ! $row_toggle) ? "align_left" : "align_left_alt";

			// 1 row of data
			$line .= "    <tr>\n";
			foreach( $this->column_names as $data_name => $column_name )
			{
				$align = 'left';
				$data = $this->Format_Field($data_name,  $company_data[$x][$data_name], false, true, $align);
				switch($data_name)
				{
					case 'Completed_Arrangments':
						$apparr = $company_data[$x]["Completed_App_IDS"];
						$data = $this->FormatFloatDiv($apparr,$data,"Completed_App_IDS");
						break;		
					case 'Failed_Arrangments':
						$apparr = $company_data[$x]["Failed_App_IDS"];
						$data = $this->FormatFloatDiv($apparr,$data,"Failed_App_IDS");
						break;		
					case 'Total_Arrangments':
						$apparr = $company_data[$x]["Total_App_IDS"];
						$data = $this->FormatFloatDiv($apparr,$data,"Total_App_IDS");
						break;
						
				}

				// the the data link to somewhere?
				if( count($this->link_columns) > 0 && isset($this->link_columns[$data_name]) && isset($company_data[$x]['mode']))
				{
					// do any replacements necessary in the link
					$this->parse_data_row = $company_data[$x];
					$href  = preg_replace_callback("/%%%(.*?)%%%/", array($this, 'Link_Parse'), $this->link_columns[$data_name]);
					$line .= "     <td $wrap_data class=\"$td_class\" style=\"text-align: $align;\"><a href=\"#\" onClick=\"parent.window.location='$href'\">" . $data . "</a></td>\n";
				}
				else
				{
					$line .= "     <td $wrap_data class=\"$td_class\" style=\"text-align: $align;\">" . $data . "</td>\n";
				}

				// If the col's data matches the criteria, total it up
				if( $this->check_eval($company_data[$x], $data_name) && isset($this->totals['company'][$data_name]) )
				{
					if (empty($company_totals[$data_name])) $company_totals[$data_name] = 0;
					switch($this->totals['company'][$data_name])
					{
						case self::$TOTAL_AS_COUNT:
							$company_totals[$data_name]++;
							break;
						case self::$TOTAL_AS_SUM:
							$company_totals[$data_name] += $company_data[$x][$data_name];
							break;
                        case self::$TOTAL_AS_AVERAGE;
                            $company_totals[$data_name] += ($company_data[$x][$data_name]/count($company_data));
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
	
		public function Download_Data()
	{
		// Holds output
		$dl_data = "";

		$dl_data .= $this->report_title . " - Run Date: " . date('m/d/Y') . "\n";


		if( !empty($this->prompt_reference_agents))
		{
			$agents = $this->Get_Agent_List();
			
			if(isset($this->search_criteria['agent_id']))
			{
				foreach($this->search_criteria['agent_id'] as $agent_id)
				{
					if(isset($agents[$agent_id]))
					{
						$dl_data .= "For agent: ".$agents[$agent_id]."\n";
					}
				}
			}
		}

		// Is the report run for a specific date, date range, or do dates not matter?
		switch($this->date_dropdown)
		{
			case self::$DATE_DROPDOWN_RANGE:
				if (isset($this->search_criteria['start_date_MM']))
				{
					$dl_data .= "Date Range: " . $this->search_criteria['start_date_MM']   . '/'
											   . $this->search_criteria['start_date_DD']   . '/'
											   . $this->search_criteria['start_date_YYYY'] . " to "
											   . $this->search_criteria['end_date_MM']     . '/'
											   . $this->search_criteria['end_date_DD']     . '/'
											   . $this->search_criteria['end_date_YYYY']   . "\n";
				}
				break;
			case self::$DATE_DROPDOWN_SPECIFIC:
				if (isset($this->search_criteria['specific_date_MM']))
				{
					$dl_data .= "Date: " . $this->search_criteria['specific_date_MM'] . '/'
									 	. $this->search_criteria['specific_date_DD'] . '/'
									 	. $this->search_criteria['specific_date_YYYY'] . "\n";
				}
				break;
			case self::$DATE_DROPDOWN_NONE:
			default:
				// Nothing to do
				break;
		}

		$total_rows = 0;

		// An empty array for the grand totals
		$grand_totals = array();
		foreach( $this->totals['grand'] as $which => $unused )
		{
			$grand_totals[$which] = 0;
		}

		$dl_data .= "\n";

		$dl_data .= $this->Get_Column_Headers( false );

		// Sort through each company's data
		foreach ($this->search_results as $company_name => $company_data)
		{
			// Short-circuit the loop if this is the "summary" data.
			if ($company_name == 'summary')
			{
				continue;
			}

			// An array of company totals which gets added to grand_totals
			$company_totals = array();
			foreach ($this->column_names as $data_name => $column_name)
			{
				$company_totals[$data_name] = 0;
			}

			// If isset($x), this is the 2nd+ company, insert a blank line to seperate the data
			if (isset($x))
			{
				$dl_data .= "\n";
			}

			foreach (array_keys($company_data) as $x)
			{
				$dl_data .= "";
				foreach (array_keys($this->column_names) as $data_col_name)
				{
                    $this->totals['company'][$data_col_name] = isset($this->totals['company'][$data_col_name]) ? $this->totals['company'][$data_col_name] : null;
                    $company_data[$x][$data_col_name] = isset($company_data[$x][$data_col_name]) ? $company_data[$x][$data_col_name]: null;
					$dl_data .= $this->Format_Field($data_col_name, $company_data[$x][$data_col_name], false, false) . "\t";

					/**
					 * 				if( ! empty($this->totals['company'][$data_name]) )
				{
					$newval = "";
					$line .= "     <th class=\"report_foot\">";
					switch($data_name)
					{
						case "Paid_Perc":
							$newval = ($company_totals["Completed_Arrangments"] / $company_totals["Total_Arrangments"])  * 100;
							$line .= $this->Format_Field($data_name, $newval, true);
							break;							
						case "Paid_Amount_Perc":
							$newval = ($company_totals["Completed_Amount"] / $company_totals["Total_Amount"])  * 100;
							$line .= $this->Format_Field($data_name, $newval, true);
							break;
						case "Failed_Perc":	
							$newval = ($company_totals['Failed_Arrangments'] / $company_totals["Total_Arrangments"])  * 100;
							$line .= $this->Format_Field($data_name, $newval, true);
							break;
						case "Failed_Amount_Perc":
							$newval = ($company_totals['Failed_Amount'] / $company_totals["Total_Amount"])  * 100;
							$line .= $this->Format_Field($data_name, $newval, true);
							break;								  
						default:
							$line .= $this->Format_Field($data_name, $company_totals[$data_name], true);
							break;
					}
					 */
					
					switch($this->totals['company'][$data_col_name])
                    {
                        case self::$TOTAL_AS_COUNT:
                            $company_totals[$data_col_name]++;
                            break;
                        case self::$TOTAL_AS_SUM:
                            $company_totals[$data_col_name] += $company_data[$x][$data_col_name];
                            break;
                        case self::$TOTAL_AS_AVERAGE;
							$company_totals[$data_col_name] += ($company_data[$x][$data_col_name]/count($company_data));
							break;
                        default:
                            // Dont do anything, somebody screwed up
                    }

				}
				
				//Now that we've got the totals, let's get the totals which have to get a percentage based on 
				//specific columns! QEASY RC #12975
				
				foreach (array_keys($company_data) as $x)
				{
					foreach (array_keys($this->column_names) as $data_col_name)
					{

						switch($data_col_name)
	                    {
	                    	case "Paid_Perc":
								$company_totals[$data_col_name] = ($company_totals["Completed_Arrangments"] / $company_totals["Total_Arrangments"])  * 100;
							break;							
	
							case "Paid_Amount_Perc":
								$company_totals[$data_col_name] = ($company_totals["Completed_Amount"] / $company_totals["Total_Amount"])  * 100;
							break;
							
							case "Failed_Perc":	
								$company_totals[$data_col_name] = ($company_totals['Failed_Arrangments'] / $company_totals["Total_Arrangments"])  * 100;
							break;
							
							case "Failed_Amount_Perc":
								$company_totals[$data_col_name] =  ($company_totals['Failed_Amount'] / $company_totals["Total_Amount"])  * 100;
							break;								  
	                    }
                    }
				}
				// removes the last tab if we're at the end of the loop and replaces it with a newline
				$dl_data = substr($dl_data, 0, -1) . "\n";
			}

			$total_rows += count($company_data);
			$company_totals['rows'] = count($company_data);

			// If there's more than one company, show a company totals line
			if (count($this->totals['company']) > 0)
			{
				// Was commented by JRS: [Mantis:1651]... Uncommented by [tonyc][mantis:5861]
				$dl_data .= $this->Get_Company_Total_Line($company_name, $company_totals) . "\n\n";
			}

			// Add the company totals to the grand totals
			foreach ($grand_totals as $key => $value)
			{
				// Flash report (and maybe others) does something special with the totals
				if (isset($company_totals[$key]))
				{
					$grand_totals[$key] += $company_totals[$key];
				}
			}
		}

		// grand totals
		// dont show grand totals if only 1 company... exact same #s are in company totals above it
		if (count($this->totals['grand']) > 0 && $this->num_companies > 1)
		{
			$dl_data .= $this->Get_Grand_Total_Line($grand_totals);
		}

		/* Mantis:1508#2 */
		if(isset($this->search_results['summary']))
		{
			$dl_data .= "\n\n"; // This ends the "Count = ..." row and one empty row

			$company_names = array_keys($this->search_results);
			// Next line commented out: Additional change from Mantis:1508
			// $company_names[] = "Grand";
			$this->search_results['summary']['Grand'] = array();
			$grand_totals =& $this->search_results['summary']['Grand'];

			foreach ($company_names as $company_name)
			{
				if ($company_name == 'summary')
				{
					continue;
				}

				$dl_data .= "${company_name} Totals:\tCount\tDebit\tCredit\n"; // Add header line

				foreach($this->search_results['summary'][$company_name] as $item => $data)
				{
					if('notes' == $item || 'code' == $item)
					{
						$dl_data .= ucwords($item)."\n"; // Name of subsection

						foreach( $data as $special => $data2 )
						{
							if( 'Grand' != $company_name )
							{
								if( ! isset( $grand_totals[$item] ) || ! isset( $grand_totals[$item][$special] ) )
								{
									$grand_totals[$item][$special] = array(
											'count'  => 0,
											'debit'  => 0,
											'credit' => 0,
											);
								}

								$grand_totals[$item][$special]['count' ] += $data2['count' ];
								$grand_totals[$item][$special]['debit' ] += $data2['debit' ];
								$grand_totals[$item][$special]['credit'] += $data2['credit'];
							}

							$dl_data .= $special
									.	"\t"
									.	$data2['count']
									.	"\t"
									.	number_format($data2['debit'],2,".",",")
									.	"\t"
									.	number_format($data2['credit'],2,".",",")
									.	"\n"
									;
						}
					}
					else
					{
						if( 'Grand' != $company_name )
						{
							if( ! isset( $grand_totals[$item] ) )
							{
								$grand_totals[$item] = array(
										'count'  => 0,
										'debit'  => 0,
										'credit' => 0,
										);
							}

							$grand_totals[$item]['count' ] += $data['count' ];
							$grand_totals[$item]['debit' ] += $data['debit' ];
							$grand_totals[$item]['credit'] += $data['credit'];
						}

						$dl_data .= $item
								.	"\t"
								.	$data['count']
								.	"\t"
								.	number_format($data['debit'],2,".",",")
								.	"\t"
								.	number_format($data['credit'],2,".",",")
								.	"\n"
								;
					}
				}

				$dl_data .= "\n"; // Add one empty row beneath this company
			}
		}

		// for the html headers
		$data_length = strlen($dl_data);

		header( "Accept-Ranges: bytes\n");
		header( "Content-Length: $data_length\n");
		header( "Content-Disposition: attachment; filename={$this->download_file_name}\n");
		header( "Content-Type: text/csv\n\n");

		//mantis:4324
		$generic_data = ECash::getTransport()->Get_Data();

		if($generic_data->is_upper_case)
			$dl_data = strtoupper($dl_data);
		else
			$dl_data = strtolower($dl_data);
		//end mantis:4324

		echo $dl_data;
	}
	
	
	protected function Get_Total_HTML($company_name, &$company_totals)
	{
		$line = "";

		// If column 1 has no totals, totals header will go in column 1
		//    else, put totals header on own line
		reset($this->column_names);
		$total_own_line = (! empty($this->totals['company'][$this->column_names[key($this->column_names)]]) ? true : false);

		// If the total header should be on its own line,
		//    or only the # of rows is desired
		if( $total_own_line ||
			( count($this->totals['company']) == 1 &&
			  ! empty($this->totals['company']['rows'])))
		{
			if( ! empty($this->totals['company']['rows']) )
			{
				$line .= "    <tr><th class=\"report_foot\" colspan=\"{$this->num_columns}\">$company_name Totals ";
				$line .= ": " . $company_totals['rows'] . " row" . ($company_totals['rows']!=1?"s":"") . "</th></tr>\n";
			}
			else
			{
				$line .= "    <tr><th class=\"report_foot\" colspan=\"{$this->num_columns}\">$company_name Totals</th></tr>\n";
			}
		}

		if( (! empty($this->totals['company']['rows']) && count($this->totals['company']) > 1 ) ||
			(empty($this->totals['company']['rows']) && count($this->totals['company']) > 0 ))
		{
			$line .= "    <tr>\n";
			foreach( $this->column_names as $data_name => $column_name )
			{
				if( ! empty($this->totals['company'][$data_name]) )
				{
					$newval = "";
					$line .= "     <th class=\"report_foot\">";
					switch($data_name)
					{
						case "Paid_Perc":
							$newval = ($company_totals["Completed_Arrangments"] / $company_totals["Total_Arrangments"])  * 100;
							$line .= $this->Format_Field($data_name, $newval, true);
							break;							
						case "Paid_Amount_Perc":
							$newval = ($company_totals["Completed_Amount"] / $company_totals["Total_Amount"])  * 100;
							$line .= $this->Format_Field($data_name, $newval, true);
							break;
						case "Failed_Perc":	
							$newval = ($company_totals['Failed_Arrangments'] / $company_totals["Total_Arrangments"])  * 100;
							$line .= $this->Format_Field($data_name, $newval, true);
							break;
						case "Failed_Amount_Perc":
							$newval = ($company_totals['Failed_Amount'] / $company_totals["Total_Amount"])  * 100;
							$line .= $this->Format_Field($data_name, $newval, true);
							break;								  
						default:
							$line .= $this->Format_Field($data_name, $company_totals[$data_name], true);
							break;
					}
					$line .= "</th>\n";
					
				}
				else if( ! $total_own_line )
				{
					if( ! empty($this->totals['company']['rows']) )
					{
						$line .= "     <th class=\"report_foot\">$company_name Totals ";
						$line .= ": " . $company_totals['rows'] . " row" . ($company_totals['rows']!=1?"s":"") . "</th>\n";
					}
					else
					{
						$line .= "     <th class=\"report_foot\">$company_name Totals</th>\n";
					}

					// Don't put the total header field again
					$total_own_line = ! $total_own_line;
				}
				else
				{
					$line .= "     <th class=\"report_foot\"></th>\n";
				}
			}
			$line .= "    </tr>\n";
		}

		return $line;
	}
	
		
}

?>
