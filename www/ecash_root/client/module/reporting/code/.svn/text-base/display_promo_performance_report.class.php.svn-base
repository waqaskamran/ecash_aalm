<?php

/**
 * @package Reporting
 * @category Display
 */
class Promo_Performance_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Promo ID Performance";
		$this->column_names       = array( 'promo_id'  => 'Promo ID',
		                                   'application_id'          => 'Application ID',
		                                   'return_reason' => 'Return Reason');
	
//		$this->column_format	  = array( 'batch_date' => self::FORMAT_DATE,
//											'batch_total_amount' => self::FORMAT_CURRENCY,
//											'num_returned_amount' => self::FORMAT_CURRENCY,
//											'return_rate' => self::FORMAT_PERCENT);
		$this->column_width		  = array('description'		=>	250);
		$this->totals             = array('company'   => array( 'promo_id' => parent::$TOTAL_AS_COUNT));
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = false;
		$this->company_list		  = false;
		$this->download_file_name = null;
		$this->ajax_reporting =  true;
		parent::__construct($transport, $module_name);
	}
	


	/**
	 * Gets the html for the data section of the report
	 * also updates running totals
	 * used only by Get_Module_HTML()
	 *
	 * @param  string name of the company
	 * @param  &array running totals
	 * @param  bool nowrap use the nowrap option in the table data tag
	 * @return string
	 * @access protected
	 */
	protected function Get_Data_HTML($company_data, &$company_totals)
        {
                $row_toggle = true;  // Used to alternate row colors
                $line       = "";

		$wrap_data   = $this->wrap_data ? '' : 'nowrap';
		$this->totals['company']['promo'] = array();
		for( $x = 0 ; $x < count($company_data) ; ++$x )
		{
			$td_class = ($row_toggle = ! $row_toggle) ? "align_left" : "align_left_alt";


			// 1 row of data
			$line .= "    <tr>\n";
			
			foreach( $this->column_names as $data_name => $column_name )
			{
				if (empty($company_totals[$data_name])) $company_totals[$data_name] = 0;
				$align = 'left';
				$data = $this->Format_Field($data_name,  isset($company_data[$x][$data_name]) ? $company_data[$x][$data_name] : null, false, true, $align);
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
					switch($this->totals['company'][$data_name])
					{
						case self::$TOTAL_AS_COUNT:
							if($data_name == 'promo_id')
							{
								
								$this->totals['company']['promo'][$data]++;
							}
							else 
							{
								$company_totals[$data_name]++;
							}
							break;
						case self::$TOTAL_AS_SUM:
							$company_totals[$data_name] += $company_data[$x][$data_name];
							break;
						case self::$TOTAL_AS_AVERAGE;
							$company_totals[$data_name] += ($company_data[$x][$data_name]/count($company_data));
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

/**
	 * Gets the html for 1 company's totals
	 * and updates running totals
	 * used only by Get_Module_HTML()
	 *
	 * @param  array  name of the company (ufc, d1, etc)
	 * @param  &array running totals so far
	 * @return string
	 * @access protected
	 */
	protected function Get_Total_HTML($company_name, &$company_totals)
	{
	//	$company_totals['return_rate'] = $company_totals['num_returned'] / $company_totals['batch_total'] * 100;
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
				$line .= "    <tr><th class=\"report_foot\" colspan=\"{$this->num_columns}\" nowrap>$company_name Totals ";
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
				if( ! empty($this->totals['company'][$data_name]) && $data_name != 'promo_id')
				{
					$align = 'left';
					$data = $this->Format_Field($data_name,  $company_totals[$data_name], true, true, $align);
					$line .= "     <th class=\"report_foot\" style=\"text-align: $align;\">$data</th>\n";
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
			$align = 'left';
			$line .= "     <tr><th class=\"report_foot\" style=\"text-align: $align;\">Promo ID</th>\n";
			$align = 'right';
			$line .= "     <th class=\"report_foot\" style=\"text-align: $align;\">App Count</th>\n";
			$line .= "    </tr>\n";
			$total = 0;
			foreach ($this->totals['company']['promo'] as $date => $count) {
				$align = 'left';
				$line .= "     <tr><th class=\"report_foot\" style=\"text-align: $align;\">$date</th>\n";
				$align = 'right';
				$line .= "     <th class=\"report_foot\" style=\"text-align: $align;\">$count</th>\n";
				$total += $count;
				$line .= "    </tr>\n";
			}
			$align = 'left';
			$line .= "     <tr><th class=\"report_foot\" style=\"text-align: $align;\">Total:</th>\n";
			$align = 'right';
			$line .= "     <th class=\"report_foot\" style=\"text-align: $align;\">$total</th>\n";
			$line .= "    </tr>\n";
			
		}

		return $line;
	}
	
		/**
	 * Gets the output for 1 company's totals
	 * and updates running totals
	 * used only by Get_Module_HTML()
	 *
	 * Basically a rewritten Get_Total_HTML for use with the display_downloads
	 *
	 * @param  array  name of the company (ufc, d1, etc)
	 * @param  &array running totals so far
	 * @return string
	 * @access protected
	 */
	protected function Get_Company_Total_Line($company_name, &$company_totals)
	{
		$line = "";

		// If column 1 has no totals, totals header will go in column 1
		//    else, put totals header on own lines
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
				// Odd for loop meant to tab us to the right column
				for ($num_tabs = 1; $num_tabs < $this->num_columns; $num_tabs++)
				{
					$line .= "\t";
				}
				$line .= "$company_name Totals " . $company_totals['rows'] . " row" . ($company_totals['rows']!=1?"s":"") . "\n";
			}
			else
			{
				$line .= "    <tr><th class=\"report_foot\" colspan=\"{$this->num_columns}\">$company_name Totals</th></tr>(680)\n";
			}
		}

		if( (! empty($this->totals['company']['rows']) && count($this->totals['company']) > 1 ) ||
			(empty($this->totals['company']['rows']) && count($this->totals['company']) > 0 ))
		{
			$line .= "\n";
			foreach( $this->column_names as $data_name => $column_name )
			{
				if( ! empty($this->totals['company'][$data_name]) && $data_name != 'promo_id')
				{
					$line .= $this->Format_Field($data_name, $company_totals[$data_name], true, false) . "\t";
				}
				else if( ! $total_own_line )
				{
					if( ! empty($this->totals['company']['rows']) )
					{
						$line .= "$company_name Totals : ";
						$line .= $company_totals['rows'] . " row" . ($company_totals['rows']!=1?"s":"") . "\t";
					}
					else
					{
						$line .= "$company_name Totals\t";
					}

					// Don't put the total header field again
					$total_own_line = ! $total_own_line;
				}
				else
				{
					$line .= "\t";
				}
			}
			$line .= "\n";
			$line .= "Promo ID\t";
			$line .= " App Count\n";
			$total = 0;
			foreach ($this->totals['company']['promo'] as $date => $count) {
				
				$line .= "$date\t";
				$line .= "$count\n";
				$total += $count;
			}
			$line .= "Total:\t $total\n";
			// removes the last tab if we're at the end of the loop and replaces it with a newline
			$line = substr( $line, 0, -1 ) . "\n";
			//$line .= "\n";
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
			$this->totals['company']['promo'] = array();
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
                    switch($this->totals['company'][$data_col_name])
                    {
                        case self::$TOTAL_AS_COUNT:
                            if($data_col_name == 'promo_id')
							{
								
								$this->totals['company']['promo'][$company_data[$x][$data_col_name]]++;
							}
							else 
							{
								$company_totals[$data_name]++;
							}
                            break;
                        case self::$TOTAL_AS_SUM:
                            $company_totals[$data_col_name] += $company_data[$x][$data_col_name];
                            break;
                        case self::$TOTAL_AS_AVERAGE;
                            $company_totals[$data_col_name] += ($company_data[$x][$data_col_name]/count($company_data));
                        default:
                            // Dont do anything, somebody screwed up
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
}

?>
