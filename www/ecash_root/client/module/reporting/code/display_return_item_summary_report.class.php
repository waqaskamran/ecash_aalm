<?php

/**
 * @package Reporting
 * @category Display
 * @todo Move __construct settings into the class itself.
 */
class Return_Item_Summary_Report extends Report_Parent
{
	/**
	 * Contains the summary data for the end of the report
	 * @var    array
	 * @access private
	 */
	private $summary_data;

	/**
	* Grand totals shown at the bottom of the report.
	* @var    array
	* @access private
	*/
	private $grand_totals;

	// array $date => $count_of_first_sc_for_date
	private $first_sc;

	/**
	* Constructor, sets a few choice vars then lets Report_Parent work
	*
	* @param Transport $transport   Data passed from the server side
	* @param string    $module_name not used
	* @access public
	*/
	public function __construct(ECash_Transport $transport, $module_name)
	{
		if (defined("SCRIPT_TIME_LIMIT_SECONDS"))
		{
			set_time_limit(SCRIPT_TIME_LIMIT_SECONDS);
		}

		$this->report_title       = "Return Item Summary";
		$this->column_names       = array(
						'company_name'   => 'Company',
						'date_bought'    => 'Date Bought' ,  /* GForge:16605 */
						'application_id' => 'Application ID',
						'date_funded'    => 'Date Funded' ,  /* GForge:16605 */
						'name_last'      => 'Last Name',
						'name_first'     => 'First Name',
						'date_sent'      => 'Date Sent',
						'reason'         => 'Reason and Code',
						'debit'          => 'Debits',
						'credit'         => 'Credits',
						'notes'          => 'Notes' ,
						'reattempt'      => 'Is Reattempt' , /* Mantis:1508#5 */
						'fatal'      	=> 'Fatal Return' , /* Mantis:1508#5 */
						'ach_provider' => 'Ach Provider',
		);
		$this->sort_columns       = array(
						'application_id',
						'name_last',
						'name_first',
						'date_sent',
						'date_bought',
						'date_funded',
						'reason',
						'debit',
						'credit',
						'notes', 
						'fatal',
						'ach_provider',
		);
		$this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%' );
		$this->totals             = array( 'company' => array(),
										   'grand' => array() );
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->company_list_no_all = true;
		$this->loan_type          = true;
		$this->ach_batch_company = TRUE;
		$this->download_file_name = null;
		$this->ajax_reporting	  = true;
		
		// gotta do it all manually here
		// Basic data setup
		// All this comes from the transport class
		$this->transport             = ECash::getTransport();
		$this->data                  = new stdClass();
		$temp                        = ECash::getTransport()->Get_Data();
		$this->report_title          = ( isset($this->report_title) ? $this->report_title : "Report" );
		$this->search_results        = ( isset($temp->search_results) && count($temp->search_results) > 0 ? $temp->search_results : null );
		$this->search_criteria       = ( isset($temp->search_criteria)       ? $temp->search_criteria       : null );
		$this->prompt_reference_data = ( isset($temp->prompt_reference_data) ? $temp->prompt_reference_data : null );
		$this->search_message        = ( isset($temp->search_message)        ? $temp->search_message        : null );
		//$this->auth_company_name     = ( isset($temp->auth_company_name)     ? $temp->auth_company_name     : null );
		$this->download_file_name    = ( isset($this->download_file_name)    ? $this->download_file_name    :
						 preg_replace("/\s/", "_", $this->report_title) . date('Ymd') . ".txt" );
		$this->loan_type_list          = ( isset($temp->loan_type_list)) ? $temp->loan_type_list : array('name_short' => 'all', 'name_short' => 'standard', 'name_short' => 'fcp');

		$this->summary_data = $this->search_results['summary'];
		unset($this->search_results['summary']);

		// Verify there is data
		if( isset($this->search_results) )
		{
			// Verify there are column names, formatting, & totals conditions for all columns
			//   If not, just use defaults
			foreach( $this->column_names as $data_col_name => $not_used )
			{
				// Is the company not there ?
				if( ! isset($this->column_names[$data_col_name]) )
				{
					$this->column_names[$data_col_name] = $data_col_name;
				}

				// Ensure the conditions are all set and have returns and ;
				if( ! isset($this->totals_conditions[$data_col_name]) )
				{
					$this->totals_conditions[$data_col_name] = "true";
				}
				else
				{
				//	$this->totals_conditions[$data_col_name] = $this->totals_conditions[$data_col_name];
				}

				// Ensure totals array is in valid format
				if( empty($this->totals['company'][$data_col_name]) )
				{
					if( in_array($data_col_name, $this->totals['company'], true) )
					{
						$this->totals['company'][$data_col_name] = self::$TOTAL_AS_SUM;
						unset($this->totals['company'][array_search($data_col_name, $this->totals)]);
					}
				}
				if( empty($this->totals['grand'][$data_col_name]) )
				{
					if( in_array($data_col_name, $this->totals['grand'], true) )
					{
						$this->totals['grand'][$data_col_name] = self::$TOTAL_AS_SUM;
						unset($this->totals['grand'][array_search($data_col_name, $this->totals)]);
					}
				}
			} // end foreach company

			// Find out how many columns there are
			$this->num_columns   = count($this->column_names);
			$this->num_companies = count($this->search_results) - (isset($this->search_results["summary"]) ? 1 : 0);
		} // end data validation

		if( in_array('rows', $this->totals['company'], true) )
		{
			$this->totals['company']['rows'] = self::$TOTAL_AS_COUNT;
			unset($this->totals['company'][array_search('rows', $this->totals)]);
		}

		if( in_array('rows', $this->totals['grand'], true) )
		{
			$this->totals['grand']['rows'] = self::$TOTAL_AS_COUNT;
			unset($this->totals['grand'][array_search('rows', $this->totals)]);
		}

		if( ! is_array($this->totals_conditions) )
		{
			$this->totals_conditions = array();
		}
		parent::__construct($transport, $module_name);
	}

	/*
		Place holder for Summary
	*/
	protected function Get_Total_HTML($company, $html = true)
	{
		return "&nbsp;";
	}
	
	/*protected function Get_Column_Headers( $html = true, $grand_totals = null )
	{
		return "&nbsp;";
	}*/
		
	/**
	* Additional info for the bottom of the company
	* overrides Report_Parent version
	*
	* @param string  $company name of the company currently working
	* @param boolean $html should html code be returned?
	* @return string
	* @access protected
	*/
	protected function Get_Company_Foot($company, $html = true)
	{
		if( $html === true )
		{
			$output           = "    <tr><td colspan=\"{$this->num_columns}\">&nbsp;</td></tr>\n";
			$output          .= "    <tr><th colspan=\"{$this->num_columns}\">\n";
			$output          .= "     <table cellpadding=\"0\" class=\"report_company_foot\" width=\"50%\">\n";
			$output          .= "      <tr><th colspan=\"2\" width=\"40%\">{$company} Totals:</th><th width=\"20%\">Count</th><th width=\"20%\">Debit</th><th width=\"20%\">Credit</th></tr>\n";
			$output_end       = "     </table>\n";
			$output_end      .= "    </th></tr>\n";
			$row_start        = "      <tr>";
			$row_end          = "</tr>\n";
			$col_start        = "<td>";
			$col_end          = "</td>\n";
			$head_start       = "<th width=\"20%\" align=\"left\">";
			$head_start_alt   = "<th colspan=\"2\" width=\"40%\">";
			$head_end         = "</th>\n";
			$head_end_alt     = "</th>\n";
			$toggle_left      = "<td class=\"align_left\" width=\"20%\">";
			$toggle_left_alt  = "<td class=\"align_left_alt\" width=\"20%\">";
			$div_start        = '<div style="text-align:right">';
			$div_end          = "</div>\n";
			$dollar           = '\\$';
		}
		else
		{
			$output           = "\n";
			$output          .= "\t\tCount\tDebit\tCredit\n";
			$output_end       = "";
			$row_start        = "";
			$row_end          = "\n";
			$col_start        = "";
			$col_end          = "\t";
			$head_start       = "";
			$head_start_alt   = "";
			$head_end         = "\t";
			$head_end_alt     = "\t\t";
			$toggle_left      = "";
			$toggle_left_alt  = "";
			$div_start        = '';
			$div_end          = '';
			$dollar           = '$';
		}

		$row_toggle = true;

        if ($company == 'summary')
        {
        	$output = $output_end;
        	return $output;
		}

		foreach ($this->summary_data[strtoupper($company)] as $item => $data)
		{
			$this->grand_totals[$item]['count'] = 0;
			$this->grand_totals[$item]['debit'] = 0;
			$this->grand_totals[$item]['credit'] = 0;
			$td_class = ($row_toggle = !$row_toggle) ? $toggle_left : $toggle_left_alt;

			switch ($item)
			{
				case 'returns':
					$output .= $row_start
						. $head_start_alt . 'Return Status' . $head_end_alt
						. $head_start . 'Fatal' . $head_end
						. $head_start . 'Non-Fatal' . $head_end
						. $head_start . 'Total' . $head_end
						. $row_end;

					$fatal = $nonFatal = $total = 0;
					foreach ($data as $special => $data2)
					{
						if (!isset($this->grand_totals[$item][$special]))
						{
							$this->grand_totals[$item][$special] = array('fatal' => 0, 'non fatal' => 0, 'total' => 0);
						}
						$this->grand_totals[$item][$special]['fatal'] += $data2['fatal'];
						$this->grand_totals[$item][$special]['non fatal'] += $data2['non fatal'];
						$this->grand_totals[$item][$special]['total'] += $data2['total'];
						
						$output .= $row_start
							. $head_start . $head_end
							. $head_start . $special . $head_end
							
							. $td_class . $div_start
							. $dollar . number_format($data2['fatal'], 2, '.', ',')
							. $div_end . $col_end
							
							. $td_class . $div_start
							. $dollar . number_format($data2['non fatal'], 2, ".", ",")
							. $div_end . $col_end
							
							. $td_class . $div_start
							. $dollar. number_format($data2['total'], 2, ".", ",")
							. $div_end . $col_end
							. $row_end;

						$fatal += $data2['fatal'];
						$nonFatal += $data2['non fatal'];
						$total += $data2['total'];
					}

					$output .= $row_start
						. $head_start . $head_end
						. $head_start . 'Total' . $head_end
						
						. $td_class . $div_start
						. $dollar .  number_format($fatal, 2)
						. $div_end . $col_end
						
						. $td_class . $div_start
						. $dollar . number_format($nonFatal, 2)
						. $div_end . $col_end
						
						. $td_class . $div_start
						. $dollar . number_format($total, 2)
						. $div_end. $col_end
						. $row_end;
					break;

				case 'notes':
				case 'code':
					$output .= $row_start
						. $head_start_alt . ucwords($item) . $head_end_alt
						. $head_start . $head_end
						. $head_start . $head_end
						. $head_start . $head_end
						. $row_end;

					foreach ($data as $special => $data2)
					{
						if (!isset($this->grand_totals[$item]) || !isset($this->grand_totals[$item][$special]))
						{
							$this->grand_totals[$item][$special] = array('count' => 0, 'debit' => 0, 'credit' => 0);
						}

						$this->grand_totals[$item][$special]['count'] += $data2['count'];
						$this->grand_totals[$item][$special]['debit'] += $data2['debit'];
						$this->grand_totals[$item][$special]['credit'] += $data2['credit'];

						$output .= $row_start
							. $head_start . $head_end
							. $head_start . $special . $head_end
							
							. $td_class
							. $data2['count']
							. $col_end
							
							. $td_class
							. $dollar . number_format($data2['debit'], 2, ".", ",")
							. $col_end
							
							. $td_class
							. $dollar . number_format($data2['credit'], 2, ".", ",")
							. $col_end
							. $row_end;
					}
					break;

				default:
					if (!isset($this->grand_totals[$item]))
					{
						$this->grand_totals[$item] = array('count' => 0, 'debit' => 0, 'credit' => 0);
					}

					$this->grand_totals[$item]['count'] += isset($data['count']) ? $data['count'] : 0;
					$this->grand_totals[$item]['debit'] += isset($data['debit']) ? $data['debit'] : 0;
					$this->grand_totals[$item]['credit'] += isset($data['credit']) ? $data['credit'] : 0;

					$output .= $row_start
						. $head_start_alt . ucwords(str_replace('_', ' ', $item)) . $head_end_alt
						
						. $td_class
						. (isset($data['count']) ? $data['count'] : '')
						. $col_end
						
						. $td_class
						. $dollar . number_format((isset($data['debit']) ? $data['debit'] : 0), 2, ".", ",")
						. $col_end
						
						. $td_class
						. $dollar . number_format((isset($data['credit']) ? $data['credit'] : 0), 2, ".", ",")
						. $col_end
						. $row_end;
			}
		}

		if (isset($this->first_sc) && $this->first_sc != NULL && is_array($this->first_sc))
		{
			$output .= $row_start 
					.  $head_start_alt . "1stSC Subtotals" . $head_end_alt
					.  $head_start . "\t" . $head_end
					.  $head_start . "\t" . $head_end
					.  $head_start . "\t" . $head_end
					.  $row_end;

			$output .= $row_start 
					.  $head_start_alt . "Date" . $head_end_alt
					.  $head_start . "Count" . $head_end
					.  $head_start . "\t" . $head_end
					.  $head_start . "\t" . $head_end
					.  $row_end;


			foreach ($this->first_sc as $first_sc_date => $first_sc_count)
			{
				$output .= $row_start
						.  $head_start_alt . $first_sc_date . $head_end_alt

						. $td_class
						. $first_sc_count
						. $col_end
						
						. $td_class
						. "\t"
						. $col_end

						. $td_class
						. "\t"
						. $col_end

						. $row_end;

			}
		}

		$output .= $output_end;
		return $output;
	}

	/**
	* Additional info for the bottom of the report
	* overrides Report_Parent version
	*
	* @param boolean $html should html code be returned?
	* @return string
	* @access protected
	*/
	protected function Get_Report_Foot($html = true)
	{
		if( $this->num_companies < 2 )
			return "";

		if( $html === true )
		{
			$output           = "    <tr><td colspan=\"{$this->num_columns}\">&nbsp;</td></tr>\n";
			$output          .= "    <tr><th colspan=\"{$this->num_columns}\">\n";
			$output          .= "     <table cellpadding=\"0\" class=\"report_company_foot\" width=\"50%\">\n";
			$output          .= "      <tr><th colspan=\"2\" width=\"40%\">Grand Totals:</th><th width=\"20%\">Count</th><th width=\"20%\">Debit</th><th width=\"20%\">Credit</th></tr>\n";
			$output_end       = "     </table>\n";
			$output_end      .= "    </th></tr>\n";
			$row_start        = "      <tr>";
			$row_end          = "</tr>\n";
			$col_start        = "<td>";
			$col_end          = "</td>";
			$head_start       = "<th width=\"20%\" align=\"left\">";
			$head_start_alt   = "<th colspan=\"2\" width=\"40%\">";
			$head_end         = "</th>";
			$head_end_alt         = "</th>";
			$toggle_left      = "<td class=\"align_left\" width=\"20%\">";
			$toggle_left_alt  = "<td class=\"align_left_alt\" width=\"20%\">";
			$div_start        = '<div style="text-align:right">';
			$div_end          = '</div>';
			$dollar           = '\\$';
		}
		else
		{
			$output           = "\n";
			$output          .= "\t\tCount\tDebit\tCredit\n";
			$output_end       = "";
			$row_start        = "";
			$row_end          = "\n";
			$col_start        = "";
			$col_end          = "\t";
			$head_start       = "";
			$head_start_alt   = "";
			$head_end         = "\t";
			$head_end_alt     = "\t\t";
			$toggle_left      = "";
			$toggle_left_alt  = "";
			$div_start        = '';
			$div_end          = '';
			$dollar           = '$';
		}

		$row_toggle = true;

		foreach ($this->grand_totals as $item => $data)
		{
			$td_class = ($row_toggle = !$row_toggle) ? $toggle_left : $toggle_left_alt;

			switch ($item)
			{
				case 'returns':
					$output .= $row_start
						. $head_start_alt . 'Returns' . $head_end_alt
						. $head_start . 'Fatal' . $head_end
						. $head_start . 'Non-Fatal' . $head_end
						. $head_start . 'Total' . $head_end
						. $row_end;

					$fatal = $nonFatal = $total = 0;
					foreach ($data as $special => $data2)
					{
						$output .= $row_start . $head_start . $head_end
							. $head_start . $special . $head_end
							
							. $td_class . $div_start
							. $dollar . number_format($data2['fatal'], 2, '.', ',')
							. $div_end . $col_end
							
							. $td_class . $div_start
							. $dollar . number_format($data2['non fatal'], 2, ".", ',')
							. $div_end . $col_end
							
							. $td_class . $div_start
							. $dollar . number_format($data2['total'], 2, ".", ",")
							. $div_end . $col_end . $row_end;

						$fatal += $data2['fatal'];
						$nonFatal += $data2['non fatal'];
						$total += $data2['total'];
					}

					$output .= $row_start . $head_start . $head_end
						. $head_start . 'Total' . $head_end
						
						. $td_class . $div_start
						. $dollar .  number_format($fatal, 2)
						. $div_end . $col_end
						
						. $td_class . $div_start
						. $dollar . number_format($nonFatal, 2)
						. $div_end . $col_end
						
						. $td_class . $div_start
						. $dollar.  number_format($total, 2)
						. $div_end . $col_end
						. $row_end;
					break;

				case 'notes':
				case 'code':
					$output .= $row_start
						. $head_start_alt . ucwords($item) . $head_end_alt
						. $head_start . $head_end
						. $head_start . $head_end
						. $head_start . $head_end
						. $row_end;

					foreach( $data as $special => $data2 )
					{
						$output .= $row_start
							. $head_start . $head_end
							. $head_start . $special . $head_end
							
							. $td_class . $div_start
							. $data2['count']
							. $div_end . $col_end
							
							. $td_class . $div_start
							. $dollar . number_format($data2['debit'], 2, '.', ',')
							. $div_end . $col_end
							
							. $td_class . $div_start
							. $dollar. number_format($data2['credit'], 2, ".", ",")
							. $div_end . $col_end
							. $row_end;
					}
					break;

				default:
					$output .= $row_start
						. $head_start_alt . ucwords(str_replace('_', ' ', $item)) . $head_end_alt
						
						. $td_class . $div_start
						. $data['count']
						. $div_end . $col_end
						
						. $td_class . $div_start
						. $dollar . number_format($data['debit'], 2, '.', ',')
						. $div_end . $col_end
						
						. $td_class . $div_start
						. $dollar . number_format($data['credit'],2,".",",")
						. $div_end . $col_end
						. $row_end;
			}
		}

		if (isset($this->first_sc) && $this->first_sc != NULL && is_array($this->first_sc))
		{
			$output .= $row_start 
					.  $head_start_alt . "1stSC Subtotals" . $head_end_alt
					.  $head_start . "&nbsp;" . $head_end
					.  $head_start . "&nbsp;" . $head_end
					.  $head_start . "&nbsp;" . $head_end
					.  $row_end;

			$output .= $row_start 
					.  $head_start_alt . "Date" . $head_end_alt
					.  $head_start . "Count" . $head_end
					.  $head_start . "&nbsp;" . $head_end
					.  $head_start . "&nbsp;" . $head_end
					.  $row_end;


			foreach ($this->first_sc as $first_sc_date => $first_sc_count)
			{
				$output .= $row_start
						.  $head_start_alt . $first_sc_date . $head_end_alt

						. $td_class
						. $first_sc_count
						. $col_end
						
						. $td_class
						. '&nbsp;'
						. $col_end

						. $td_class
						. '&nbsp;'
						. $col_end

						. $row_end;

			}
		}

		$output .= $output_end;

		return $output;
	}

	/**
	* Definition of abstract method in Report_Parent
	* Used to format field data for printing
	*
	* @param string  $name column name to format
	* @param string  $data field data
	* @param boolean $totals formatting totals or data?
	* @param boolean $html format for html?
	* @return string
	*/
	protected function Format_Field($name, $data, $totals = false, $html = true)
	{
		switch( $name )
		{
			case 'date_sent':
			case 'date_funded':
			case 'date_bought':
				$return_val = date('m/d/y',strtotime($data));
				break;
			case 'name_last':
			case 'name_first':
				$return_val = ucwords($data);
				break;
			case 'debit':
			case 'credit':
            if( $html === true )
            {
               $markup = ($data < 0 ? 'color: red;' : '');
               $open = ($data < 0 ? '(' : '');
               $close = ($data < 0 ? ')' : '');
               $data = abs($data);
               return '<div style="text-align:right;'. $markup . '">' .$open.'\\$' . number_format($data, 2, '.', ',') . $close . '</div>';
            }
            else
            {
               return '$' . number_format($data, 2, '.', ',');
            }
				break;
			default:
				$return_val = $data;
				break;
		}

		return $return_val;
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
					$line .= "     <td $wrap_data class=\"$td_class\" style=\"text-align: $align; font-size: 8pt; padding: 0px 4px 0px 4px;\"><a href=\"#\" onClick=\"parent.window.location='$href'\">" . $data . "</a></td>\n";
				}
				else
				{
					$line .= "     <td $wrap_data class=\"$td_class\" style=\"text-align: $align; font-size: 8pt; padding: 0px 4px 0px 4px;\">" . $data . "</td>\n";
				}

				// Hack
				if ((isset($company_data[$x]['notes'])) && $column_name == 'Notes' && $company_data[$x]['notes'] == '1stSC')
				{
					// If it's not set for that date, set a new one
					if (!isset($this->first_sc[$company_data[$x]['date_bought']]))
						$this->first_sc[$company_data[$x]['date_bought']] = 1;
					else
						$this->first_sc[$company_data[$x]['date_bought']]++; // or just increment it
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

	public function Download_Data()
	{
		// Holds output
		$dl_data = "";

		$dl_data .= $this->report_title . " - Run Date: " . date('m/d/Y') . "\n";

		if( TRUE === $this->agent_list )
		{
			$agents = $this->Get_Agent_List();
			foreach($this->search_criteria['agent_id'] as $agent_id)
			{
				if(isset($agents[$agent_id]))
				{
					$dl_data .= "For agent: ".$agents[$agent_id]."\n";
				}
				else
				{
					throw new Exception( "Unrecognized agent id: $agent_id" );
				}
			}
		}

		// Is the report run for a specific date, date range, or do dates not matter?
		switch($this->date_dropdown)
		{
			case self::$DATE_DROPDOWN_RANGE:
				$dl_data .= "Date Range: " . $this->search_criteria['start_date_MM']   . '/'
										   . $this->search_criteria['start_date_DD']   . '/'
										   . $this->search_criteria['start_date_YYYY'] . " to "
										   . $this->search_criteria['end_date_MM']     . '/'
										   . $this->search_criteria['end_date_DD']     . '/'
										   . $this->search_criteria['end_date_YYYY']   . "\n";
				break;
			case self::$DATE_DROPDOWN_SPECIFIC:
				$dl_data .= "Date: " . $this->search_criteria['specific_date_MM'] . '/'
									 . $this->search_criteria['specific_date_DD'] . '/'
									 . $this->search_criteria['specific_date_YYYY'] . "\n";
				break;
			case self::$DATE_DROPDOWN_NONE:
			default:
				// Nothing to do
				break;
		}

		// Insert a blank line in between the report header and the column headers
		$dl_data .= "\n";

		$total_rows = 0;

		// An empty array for the grand totals
		$grand_totals = array();
		foreach( $this->totals['grand'] as $which => $unused )
		{
			$grand_totals[$which] = 0;
		}
		
		// Insert an extra tab to make the columns match up
		//$dl_data .= "\t";

		$dl_data .= $this->Get_Column_Headers( false );

		// Sort through each company's data
		foreach( $this->search_results as $company_name => $company_data )
		{
			// Mantis:1508#2
			if( "summary" != $company_name )
			{
				// An array of company totals which gets added to grand_totals
				$company_totals = array();
				foreach( $this->column_names as $data_name => $column_name )
				{
					$company_totals[$data_name] = 0;
				}

				// If isset($x), this is the 2nd+ company, insert a blank line to seperate the data
				if( isset($x) )
					$dl_data .= "\n";

				for( $x = 0 ; $x < count($company_data) ; ++$x )
				{
					foreach( $this->column_names as $data_col_name => $not_used )
					{
						$dl_data .= $this->Format_Field($data_col_name, $company_data[$x][$data_col_name], false, false) . "\t";
						$company_totals[$data_col_name] += $company_data[$x][$data_col_name];

						// Hack
						if ((isset($company_data[$x]['notes'])) && $data_col_name == 'notes' && $company_data[$x]['notes'] == '1stSC')
						{
							// If it's not set for that date, set a new one
							if (!isset($this->first_sc[$company_data[$x]['date_bought']]))
								$this->first_sc[$company_data[$x]['date_bought']] = 1;
							else
								$this->first_sc[$company_data[$x]['date_bought']]++; // or just increment it
						}

					}
					// removes the last tab if we're at the end of the loop and replaces it with a newline
					$dl_data = substr( $dl_data, 0, -1 ) . "\n";
				}
				$total_rows += count($company_data);
				$company_totals['rows'] = count($company_data);

				// If there's more than one company, show a company totals line
				if( count($this->totals['company']) > 0 )
				{
					// Was commented by JRS: [Mantis:1651]... Uncommented by [tonyc][mantis:5861]
					$dl_data .= $this->Get_Company_Total_Line($company_name, $company_totals) . "\n\n";
				}

				$dl_data .= $this->Get_Company_Foot($company_name, false);
				
				// Add the company totals to the grand totals
				foreach( $grand_totals as $key => $value )
				{
					// Flash report (and maybe others) does something special with the totals
					if( isset($company_totals[$key]) )
						$grand_totals[$key] += $company_totals[$key];
				}
			}
		}

		// grand totals
		// dont show grand totals if only 1 company... exact same #s are in company totals above it
		if( count($this->totals['grand']) > 0 && $this->num_companies > 1 )
			$dl_data .= $this->Get_Grand_Total_Line($grand_totals);

		// JRS: [Mantis:1651]
		//$dl_data .= "\nCount = $total_rows";

		// Mantis:1508#2
		$dl_data .= $this->Get_Report_Foot(false);
		//die($dl_data);

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
