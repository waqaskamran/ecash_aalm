<?php

/**
 * @package Reporting
 * @category Display
 */
class NonACH_Payments_Due_Report extends Report_Parent
{
	/**
	* count of customers in each pay period
	* @var array
	* @access private
	*/
	private $run_totals;

	/**
	* Grand totals for all companies
	* @var array
	* @access private
	*/
	private $grand_totals;

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
		$this->report_title       = "Projected Non ACH Payments Due";

		$this->column_names       = array( 'application_id' => 'Application ID',
		                                   'name_last'      => 'Last Name',
		                                   'name_first'     => 'First Name',
		                                   'status'         => 'Status',
		                                   'frequency'      => 'Pay Period',
		                                   'dd'             => 'DD',
		                                   'principal'      => 'Principal',
		                                   'fees'           => 'Fees',
		                                   'service_charge' => 'Interest',
		                                   'amount_due'     => 'Total Due',
		                                   'next_due'       => 'Next Scheduled',
		                                   'loan_type'      => 'Loan Type',
		                                   'first_payment'  => 'First Time Due',
		                                   'payout'         => 'Pay Out',
		                                   'special'        => 'Special Arrangements',
										 );

		$this->sort_columns       = array( 'application_id', 'name_last',
		                                   'name_first',     'status',
		                                   'frequency',      'principal',
		                                   'fees',           'service_charge',
		                                   'amount_due',     'next_due',
		                                   'loan_type',
		                                   'first_payment',  'payout' );

		$this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

		$this->totals             = array( 'company' => array( 'rows',
		                                                       'principal'      => Report_Parent::$TOTAL_AS_SUM,
		                                                       'fees'           => Report_Parent::$TOTAL_AS_SUM,
		                                                       'service_charge' => Report_Parent::$TOTAL_AS_SUM,
		                                                       'amount_due'     => Report_Parent::$TOTAL_AS_SUM,
		                                                       'first_payment'  => Report_Parent::$TOTAL_AS_SUM,
		                                                       'payout'         => Report_Parent::$TOTAL_AS_SUM,
		                                                       'special'        => Report_Parent::$TOTAL_AS_SUM ),
		                                   'grand'   => array( 'rows',
		                                                       'principal'      => Report_Parent::$TOTAL_AS_SUM,
		                                                       'fees'           => Report_Parent::$TOTAL_AS_SUM,
		                                                       'service_charge' => Report_Parent::$TOTAL_AS_SUM,
		                                                       'amount_due'     => Report_Parent::$TOTAL_AS_SUM,
		                                                       'first_payment'  => Report_Parent::$TOTAL_AS_COUNT,
		                                                       'payout'         => Report_Parent::$TOTAL_AS_SUM,
		                                                       'special'        => Report_Parent::$TOTAL_AS_SUM ) );

		$this->totals_conditions  = null;  // special is either 0 or 1 so SUM should give us the correct count.
		// $this->totals_conditions  = array( 'special' => " strlen('%%%var%%%') > 0 && '%%%var%%%' != '0' ? true : false " );

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_SPECIFIC;
		$this->loan_type          = true;
		$this->download_file_name = null;

		parent::__construct($transport, $module_name);
	}

	/**
	* Additional info for the bottom of the company
	*
	* @param string  $company name of the company currently working
	* @param boolean $html should html code be returned?
	* @return string
	* @access protected
	*/
	protected function Get_Company_Foot($company, $html = true)
	{
		$count = array();

		foreach( $this->search_results[$company] as $row => $data )
		{
			// counts
			if( isset($count['counts'][$data['frequency']][$data['status']][$data['dd']]) )
				$count['counts'][$data['frequency']][$data['status']][$data['dd']]++;
			else
				$count['counts'][$data['frequency']][$data['status']][$data['dd']] = 1;

			// dollar amounts
			if( isset($count['dollars'][$data['frequency']][$data['status']][$data['dd']]) )
				$count['dollars'][$data['frequency']][$data['status']][$data['dd']] += $data['amount_due'];
			else
				$count['dollars'][$data['frequency']][$data['status']][$data['dd']]  = $data['amount_due'];

		}

		$output = $this->Output_Totals($count, $html);

		$this->run_totals[$company] = $count;

		return $output;
	}

	/**
	* Additional info for the bottom of the report
	*
	* @param boolean $html should html code be returned?
	* @return string       output
	* @access protected
	*/
	protected function Get_Report_Foot($html = true)
	{
		if( count($this->search_results) > 1 )
		{
			foreach( $this->run_totals as $comp_data )
			{
				foreach( $comp_data as $frequency => $data )
				{
					foreach( $data as $status => $dd )
					{
						$totals[$frequency][$status][key($dd)] = array_shift($dd);
						$totals[$frequency][$status][key($dd)] = array_shift($dd);
					}
				}
			}

			$this->grand_totals = $totals;
			$output = $this->Output_Totals( $totals, $html );
		}
		else
			$output = "";

		return $output;
	}

	/**
	* prints the html for the extra totals
	*
	* @param  array   $count totals to print
	* @param  boolean $html  should the output be html or text?
	* @return string         output
	* @access private
	*/
	private function Output_Totals( $count, $html = true )
	{
		if( $html === true )
		{
			$output          = "    <tr><td colspan=\"{$this->num_columns}\">&nbsp;</td></tr>\n";
			$output         .= "    <tr><th colspan=\"{$this->num_columns}\">\n";

			$table_start_1   = "     <table cellpadding=\"0\" class=\"report_company_foot\" width=\"50%\">\n";
			$table_start_1  .= "      <tr><th>";
			$table_start_2   = "      </th><th></th><th colspan=2>DD</th><th colspan=2>No DD</th></tr>\n";
			$table_start_2  .= "      <tr><th>Pay Period</th><th>Status</th><th>#</th><th>$</th><th>#</th><th>$</th></tr>\n";
			$table_end       = "     </table>\n";

			$output_end      = "    </th></tr>\n";
			$row_start       = "      <tr>";
			$row_end         = "</tr>\n";
			$col_start       = "<td>";
			$col_end         = "</td>";
			$head_start      = "<th>";
			$head_end        = "</th>";
			$toggle_left     = "<td class=\"align_left\">";
			$toggle_left_alt = "<td class=\"align_left_alt\">";
			$space           = "&nbsp;";
			$dollar          = '\\$';
			$div_open	 = '<div style="text-align:right">';
			$div_close	 = '</div>';
		}
		else
		{
			$output          = "\n";
			$output         .= "Pay Period\tStatus\tDD Quantity\tDD Totals\tNo DD Quantity\tNo DD Totals\n";

			$output_end      = "";
			$row_start       = "";
			$row_end         = "\n";
			$col_start       = "";
			$col_end         = "\t";
			$head_start      = "";
			$head_end        = "\t";
			$toggle_left     = "";
			$toggle_left_alt = "";
			$space           = " ";
			$dollar          = '$';
			$div_open	 = "";
			$div_close	 = "";
		}

		$first_table = TRUE;
		$output .= $table_start_1 . "Other" . $table_start_2;
		foreach( $count['counts'] as $frequency => $data )
		{
			$row_toggle = true;

			$output .= $row_start . $col_start . $frequency . $col_end;
			$first = true;

			foreach( $data as $status => $dd )
			{
				$td_class = ($row_toggle = ! $row_toggle) ? $toggle_left : $toggle_left_alt;


				(is_array($dd)) ? $dd_count_yes = array_shift($dd) : false;
				(is_array($dd)) ? $dd_count_no  = array_shift($dd) : false;

                                if ($dd_count_yes == null)
                                {
                                        $dd_count_yes = 0;
                                }

                                if ($dd_count_no == null)
                                {
                                        $dd_count_no = 0;
                                }

				// Old Method.  I'm lazy so I did the quick fix on this without researching
				// it.  It appears to not adversely affect the report results.  The bug was
				// that an error was appearing because it could no longer shift $dd.  - BR
			
				//$dd_count_yes  = array_shift($dd);
				//$dd_count_no  = array_shift($dd);


				if( isset( $count['dollars'][$frequency][$status]['yes'] ) &&
					is_numeric($count['dollars'][$frequency][$status]['yes']) )
				{
					$dd_dollar_yes = $dollar . number_format($count['dollars'][$frequency][$status]['yes'], 2, '.', ',');
				}
				else
				{
					$dd_dollar_yes = $dollar . "0.00";
				}

				if( isset( $count['dollars'][$frequency][$status]['no'] ) &&
					is_numeric($count['dollars'][$frequency][$status]['no']) )
				{
					$dd_dollar_no = $dollar . number_format($count['dollars'][$frequency][$status]['no'], 2, '.', ',');
				}
				else
				{
					$dd_dollar_no  = $dollar . "0.00";
				}

				if( $first === true )
				{
					$first = false;
					$output .= $td_class . $status . $col_end . $td_class
						   . $div_open . $dd_count_yes . $div_close . $col_end;
					$output .= $td_class . $div_open . $dd_dollar_yes . $div_close
						   . $col_end . $td_class . $div_open . $dd_count_no . $div_close . $col_end;
					$output .= $td_class . $div_open . $dd_dollar_no . $div_close . $col_end . $row_end;
				}
				else
				{
					$output .= $row_start . $head_start . $head_end . $td_class . $status . $col_end;
					$output .= $td_class . $div_open . $dd_count_yes . $div_close . $col_end
						   . $td_class . $div_open. $dd_dollar_yes . $div_close . $col_end;
					$output .= $td_class . $div_open . $dd_count_no  . $div_close. $col_end . $td_class
						   . $div_open . $dd_dollar_no  . $div_close . $col_end . $row_end;
				}
			}
		}
		$output .= $table_end;

		$output .= $output_end;

		return $output;
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
			case 'totals':
				if( $html === true )
					return ' &nbsp;(\\$' . $data . ')';
				else
					return '  ($' . $data . ')';
				break;
			case 'principal':
			case 'fees':
			case 'service_charge':
			case 'amount_due':
				if( $html === true )
					return '\\$' . number_format( $data, 2, '.', ',' );
				else
					return '$' . number_format( $data, 2, '.', ',' );
				break;

			case 'first_payment':
			case 'payout':
			case 'special':
				if( $totals === true )
					return $data;

				if( $html === false )
					return ($data==1?"YES":"no");

				if( $data == 1 )
				{
					return '<center><span style="font-weight:bold;color:green;">YES</span></center>';
				}
				else
				{
					return '<center><span style="font-weight:bold;color:red;">no</span></center>';
				}
				break;
			default:
				return $data;
		}
	}

	/**
	* Overrides Report_Parent since this report has custom elements
	*
	* @throws Exception on invalid data stored in this->search_criteria
	* @access public
	*/
	public function Download_Data()
	{
		// Holds output
		$dl_data = "";

		$dl_data .= $this->report_title . " - Run Date: " . date('m/d/Y') . "\n";

		// All loans?  Cards only?  Standard only?
		if( $this->loan_type === true )
		{
			switch($this->search_criteria['loan_type'])
			{
				case 'all':
					$dl_data .= "For loan types: All\n";
					break;
				case 'standard':
					$dl_data .= "For loan type: Standard\n";
					break;
				case 'card':
					$dl_data .= "For loan type: Card\n";
					break;
				default:
					$dl_data .= "For loan type: " . $dl_data . "\n";
					//throw new Exception( "Unrecognized loan type: " . $this->search_criteria['loan_type'] );
					break;
			}
		}

		// Is the report run for a specific date, date range, or do dates not matter?
		$dl_data .= "Date: " . $this->search_criteria['specific_date_MM'] . '/'
		                     . $this->search_criteria['specific_date_DD'] . '/'
		                     . $this->search_criteria['specific_date_YYYY'] . "\n";

		// Insert a blank line in between the report header and the column headers
		$dl_data .= "\n";

		$total_rows = 0;
		$dl_data .= "Company\t";
		$dl_data .= $this->Get_Column_Headers( false );
		foreach( $this->search_results as $company_name => $company_data )
		{
			$company_totals = array();

			// If isset($x), this is the 2nd+ company, insert a blank line to seperate the data
			if( isset($x) )
				$dl_data .= "\n";

			for( $x = 0 ; $x < count($company_data) ; ++$x )
			{
				$dl_data .= "$company_name\t";
				foreach( $this->column_names as $data_col_name => $not_used )
				{
					$dl_data .= $this->Format_Field($data_col_name, $company_data[$x][$data_col_name], false, false) . "\t";
				}

				foreach( $this->column_names as $data_name => $column_name )
				{
					if( $this->check_eval($company_data[$x], $data_name) && isset($this->totals['company'][$data_name]) )
					{
						if(!is_array($company_totals))
						{
							$company_totals = array();
						}
						if(!isset($company_totals[$data_name]))
						{
							$company_totals[$data_name] = 0;
						}

						switch($this->totals['company'][$data_name])
						{
							case self::$TOTAL_AS_COUNT:
								$company_totals[$data_name]++;
								break;
							case self::$TOTAL_AS_SUM:
								$company_totals[$data_name] += $company_data[$x][$data_name];
								break;
							default:
								// Dont do anything - This should
								// never be reached
						}
					}
				}
				$company_totals['rows']++;

				$dl_data = substr( $dl_data, 0, -1 ) . "\n";
			}

			$total_rows += count($company_data);

			// If there's more than one company, show a company totals line
			if( count($this->totals['company']) > 0 )
			{
				$dl_data .= $this->Get_Company_Total_Line($company_name, $company_totals) . "\n\n";
			}

			$dl_data .= $this->Get_Company_Foot($company_name, false);
		}

		if( $this->num_companies > 0 )
			$dl_data .= $this->Get_Report_Foot( false );

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

	/**
	* Overrides Report_Parent since this report has custom processing
	*/
	protected function Get_Data_HTML($company_data, &$company_totals)
	{
		$row_toggle = true;  // Used to alternate row colors
		$return = "";

		for( $x = 0 ; $x < count($company_data) ; ++$x )
		{
			$td_class = ($row_toggle = ! $row_toggle) ? "align_left" : "align_left_alt";

			// 1 row of data
			$return .= "    <tr>\n";
			foreach( $this->column_names as $data_name => $column_name )
			{
				// the the data link to somewhere?
				if( count($this->link_columns) > 0 && isset($this->link_columns[$data_name]) && isset($company_data[$x]['mode']))
				{
					// do any replacements necessary in the link
					$this->parse_data_row = $company_data[$x];
					$href  = preg_replace_callback("/%%%(.*?)%%%/", array($this, 'Link_Parse'), $this->link_columns[$data_name]);
					$return .= "     <td class=\"$td_class\"><a href=\"#\" onClick=\"parent.window.location='$href'\">" . $this->Format_Field($data_name, $company_data[$x][$data_name]) . "</a></td>\n";
				}
				else
				{
					if (is_numeric($company_data[$x][$data_name]))
					{
						if ($td_class == 'align_left')
						{
							$return .= "<td class=\"align_right\">" . $this->Format_Field($data_name, $company_data[$x][$data_name]) . "</td>\n";
						}
						else
						{
							$return .= "<td class=\"align_right_alt\">" . $this->Format_Field($data_name, $company_data[$x][$data_name]) . "</td>\n";
						}
					}
					else
					{
						$return .= "<td class=\"$td_class\">" . $this->Format_Field($data_name, $company_data[$x][$data_name]) . "</td>\n";
					}
				}

				// If the col's data matches the criteria, total it up
				if( $this->check_eval($company_data[$x], $data_name) && isset($this->totals['company'][$data_name]) )
				{
					if(!is_array($company_totals))
					{
						$company_totals = array();
					}
					if(!isset($company_totals[$data_name]))
					{
						$company_totals[$data_name] = 0;
					}
					
					switch($this->totals['company'][$data_name])
					{
						case self::$TOTAL_AS_COUNT:
							$company_totals[$data_name]++;
							break;
						case self::$TOTAL_AS_SUM:
							$company_totals[$data_name] += $company_data[$x][$data_name];
							break;
						default:
							// Dont do anything - This should never be
							// reached
					}
				}
			}
			$company_totals['rows']++;
			$return .= "    </tr>\n";
		}

		return $return;
	}

	/**
	* Overrides Report_Parent since this report has custom processing
	*/
	public function Get_Module_HTML()
	{
		//echo "<!-- Module Name: " . ECash::getTransport()->page_array[2] . " -->\n";
		$mode = ECash::getTransport()->page_array[2];
		switch($mode)
		{
			case 'applicant_status':
				$form = new Form( CLIENT_MODULE_DIR . "/reporting/view/display_app_staus_report.html" );
				break;
			case 'follow_up':
				$form = new Form( CLIENT_MODULE_DIR . "/reporting/view/display_follow_up_report.html" );
				break;
			case 'manual_payment':
				$form = new Form( CLIENT_MODULE_DIR . "/reporting/view/display_manual_payment_report.html" );
				break;
			default:
				$form = new Form( CLIENT_MODULE_DIR . "/reporting/view/display_report.html" );
		}

		// substitutions to make in the html template
		$substitutions = new stdClass();

		$substitutions->report_title = $this->report_title;

		// Get the date dropdown & loan type html
		$this->Get_Form_Options_HTML( $substitutions );

		$substitutions->search_message    = "<tr><td>&nbsp;</td></tr>";
		$substitutions->search_result_set = "<tr><td><div id=\"report_result\" class=\"reporting\"></div></td></tr>";

		while( ! is_null($next_level = ECash::getTransport()->Get_Next_Level()) )
		{
			if( $next_level == 'message' )
			{
				$substitutions->search_message = "<tr><td class='align_left' style='color: red'>{$this->search_message}</td></tr>\n";
			}
			else if( $next_level == 'report_results' && $this->num_companies > 0 )
			{
				// First turn on the download link
				$substitutions->download_link = "[ <a href=\"?module=reporting&mode=" . urlencode($mode) . "&action=download_report\" class=\"download\">Download Displayed Data to CSV File</a> ]";

				// results header
				$result_body  = "<tr>\n";
				$result_body .= " <td class=\"align_left\">\n";
				$result_body .= "  <div id=\"report_result\" class=\"reporting\">\n";
				$result_body .= "   <table class=\"report\">\n";
				// addtional html to toss at the top of the report data, could be anything
				$result_body .= $this->Get_Report_Head();

				// for each company
				foreach( $this->search_results as $company_name => $company_data )
				{
					/* Mantis:1508#2 */
					if( "summary" != "$company_name" )
					{
						// If company_totals is set, this is the 2nd+ company
						//    Insert a blank line between the two companies
						if( isset($company_totals) )
							$result_body .= "    <tr><td colspan=\"{$this->num_columns}\" class=\"align_left_alt\">&nbsp;</td></tr>\n";

						// Addtional data for the start of each company, could be anything
						$result_body .= $this->Get_Company_Head($company_name);

						$company_totals = array();
						foreach( $this->column_names as $data_name => $column_name )
							$company_totals[$data_name] = 0;

						$company_totals['rows'] = 0;

						// company header
						$result_body .= "    <tr><td colspan=\"{$this->num_columns}\" class=\"report_head\">$company_name</td></tr>\n";

						// Column names
						$result_body .= $this->Get_Column_Headers();

						// Company data
						$result_body .= $this->Get_Data_HTML($company_data, $company_totals);

						// company totals
						if( count($this->totals['company']) > 0 )
							$result_body .= $this->Get_Total_HTML($company_name, $company_totals);

						// Additional data for the end of each company, could be anything
						$result_body .= $this->Get_Company_Foot($company_name);
					}
				} // end foreach company

				// Additional data for the bottom of the report data, could be anything
				$result_body .= $this->Get_Report_Foot();

				// results footer
				$result_body .= "   </table>\n";
				$result_body .= "  </div>\n";
				$result_body .= " </td>\n";
				$result_body .= "</tr>\n";

				$substitutions->search_result_set = $result_body;

				if( $this->num_companies == 1 )
					$message = "Data for {$this->num_companies} company displayed.";
				else
					$message = "Data for {$this->num_companies} companies displayed.";

				$substitutions->search_message = "    <td class=\"align_left\" style=\"color: darkblue\">$message</td>\n";
			}
			else if( $next_level == 'report_results' )
			{
				$message = "No application data was found that meets the specified report criteria.";
				$substitutions->search_message = "    <tr><td class=\"align_left\" style=\"color: darkblue\">$message</td></tr>\n";
			}
		}

		return $form->As_String($substitutions);
	}

	/**
	* Overrides Report_Parent since this report has custom processing
	*/
	protected function Get_Total_HTML($company_name, &$company_totals)
	{
		$line = "";

		$slug = "$company_name Totals : " . $company_totals['rows'] . " row" . ($company_totals['rows']!=1?"s":"");

		$line .= "<tr><th class=\"report_foot\" colspan=\"{$this->num_columns}\">$slug</th></tr>";

		if( (! empty($this->totals['company']['rows']) && count($this->totals['company']) > 1 ) ||
			(empty($this->totals['company']['rows']) && count($this->totals['company']) > 0 ))
		{
			$line .= "<tr>\n";
			foreach( $this->column_names as $data_name => $column_name )
			{
				$line .= "<th class=\"report_foot\"><div style=\"text-align:right\">";
				if( ! empty($this->totals['company'][$data_name]) )
				{
					$line .= @$this->Format_Field($data_name, $company_totals[$data_name], true);
				}
				$line .= "</div></th>";
			}
			$line .= "</tr>\n";
		}

		return $line;
	}

	/**
	* Overrides Report_Parent since this report has custom processing
	*/
	protected function Get_Company_Total_Line($company_name, &$company_totals)
	{
		$line = "";

		$slug = "$company_name Totals : " . $company_totals['rows'] . " row" . ($company_totals['rows']!=1?"s":"");

		$line .= "$slug\n\t";

		if( (! empty($this->totals['company']['rows']) && count($this->totals['company']) > 1 ) ||
			(empty($this->totals['company']['rows']) && count($this->totals['company']) > 0 ))
		{
			foreach( $this->column_names as $data_name => $column_name )
			{
				if( ! empty($this->totals['company'][$data_name]) )
				{
					$line .= stripslashes($this->Format_Field($data_name, $company_totals[$data_name], true));
				}
				$line .= "\t";
			}
			$line .= "\n";
		}

		return $line;
	}
}

?>
