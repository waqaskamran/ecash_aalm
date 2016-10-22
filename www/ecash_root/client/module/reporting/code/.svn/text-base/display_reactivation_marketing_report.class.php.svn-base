<?php

/**
 * @package Reporting
 * @category Display
 */
class Reactivation_Marketing_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Customer Marketing Report";
		$this->column_names       = array( 'company_name'   => 'Company',
										   'application_id' => 'Application ID',
										   'status'				=>	'Status',
											'date_fund_actual'    => 'Fund Date',
										   'date_payoff'	=>	'Payoff Date',	
		                                   'fund_actual'    => 'Fund Amount',
										   'customer_email'  => 'Email Address',		                                   										   												                                   
											'name_last'      => 'Last Name',
		                                   'name_first'     => 'First Name',
										   'phone_home'     => 'Home Phone',
										   'phone_cell'     => 'Cell Phone',
										   'phone_work'     => 'Work Phone',
											'work_ext'		=>	'Work Ext.',
											'ip_address'		=>	'IP Address',
											'esign_date'		=>	'Sign Up Date',
											'source_site'		=>	'Source Site',
										   'address'        => 'Address',
										   'city'           => 'City',
										   'state'          => 'State',
										   'zip_code'       => 'Zip Code',

										   

		                                   );
		$this->column_format = array(
			'date_application_status_set' => self::FORMAT_DATE ,
			'esign_date' => self::FORMAT_DATE ,
			'date_fund_actual' => self::FORMAT_DATE ,
			'date_payoff' => self::FORMAT_DATE ,
			'fund_actual' => self::FORMAT_CURRENCY 
		);
		$this->sort_columns       = array();
		$this->link_columns       = array( 'application_id' => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%' );
		$this->totals             = array( 'company' => array( 'rows' ),
		                                   'grand'   => array( 'rows' )
		                                 );

		$this->ajax_reporting     = true;
		$this->date_dropdown      = Report_Parent::$AGE_RANGE;
		$this->loan_type          = true;
		$this->download_file_name = "customer_marketing.txt";

		parent::__construct($transport, $module_name);
	}

	// GF #12208: The rest of the code here is all a HACK
	// It will hopefully be replaced by something more permanent soon. [benb]
	public function Download_Data()
	{
		if (function_exists('gzencode')) 
		{
			$zlib_compression = strtolower(ini_get('zlib.output_compression'));

			if ($zlib_compression != '' && $zlib_compression != 'off' && $zlib_compression != '0') 
			{
				ini_set('zlib.output_compression', 'Off');
			}
		}

		header( "Accept-Ranges: bytes\n");
		header( "Content-Disposition: attachment; filename={$this->download_file_name}\n");
		header( "Content-Type: text/csv\n\n");

		echo $this->report_title . " - Run Date: " . date('m/d/Y') . "\n";

		if( !empty($this->prompt_reference_agents))
		{
			$agents = $this->Get_Agent_List();

			if(isset($this->search_criteria['agent_id']))
			{
				foreach($this->search_criteria['agent_id'] as $agent_id)
				{
					if(isset($agents[$agent_id]))
					{
						echo "For agent: ".$agents[$agent_id]."\n";
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
					echo "Date Range: " . $this->search_criteria['start_date_MM']   . '/'
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
					echo "Date: " . $this->search_criteria['specific_date_MM'] . '/'
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

		echo "\n";

		echo $this->Get_Column_Headers( false );

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
				echo "\n";
			}

			foreach (array_keys($company_data) as $x)
			{
				$line = "";

				foreach (array_keys($this->column_names) as $data_col_name)
				{
					$this->totals['company'][$data_col_name] = isset($this->totals['company'][$data_col_name]) ? $this->totals['company'][$data_col_name] : null;
					$company_data[$x][$data_col_name] = isset($company_data[$x][$data_col_name]) ? $company_data[$x][$data_col_name]: null;
					$line .= $this->Format_Field($data_col_name, $company_data[$x][$data_col_name], false, false) . "\t";
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
						default:
							// Dont do anything, somebody screwed up
					}

				}

				// removes the last tab if we're at the end of the loop and replaces it with a newline
				echo substr($line, 0, -1) . "\n";
				flush();
			}

			$total_rows += count($company_data);
			$company_totals['rows'] = count($company_data);

			// If there's more than one company, show a company totals line
			if (count($this->totals['company']) > 0)
			{
				// Was commented by JRS: [Mantis:1651]... Uncommented by [tonyc][mantis:5861]
				echo $this->Get_Company_Total_Line($company_name, $company_totals) . "\n\n";
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
			echo $this->Get_Grand_Total_Line($grand_totals);
		}

		/* Mantis:1508#2 */
		if(isset($this->search_results['summary']))
		{
			echo "\n\n"; // This ends the "Count = ..." row and one empty row

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

				echo "${company_name} Totals:\tCount\tDebit\tCredit\n"; // Add header line

				foreach($this->search_results['summary'][$company_name] as $item => $data)
				{
					if('notes' == $item || 'code' == $item)
					{
						echo ucwords($item)."\n"; // Name of subsection

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

							echo $special
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

						echo $item
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

				echo "\n"; // Add one empty row beneath this company
			}
		}

		//mantis:4324
		$generic_data = $this->transport->Get_Data();
	}

	public function Download_XML_Data( )
	{
		if (function_exists('gzencode')) 
		{
			$zlib_compression = strtolower(ini_get('zlib.output_compression'));

			if ($zlib_compression != '' && $zlib_compression != 'off' && $zlib_compression != '0') 
			{
				ini_set('zlib.output_compression', 'Off');
			}
		}

		if ($this->search_criteria['date_type'] == 'funding_date')
			$ordercol = "fund_date";
		else
			$ordercol = "payment_date";

		// This is a hack the reporting framework doesn't seem to have a way to specify
		// a default arbitrary sort column in the initial display of the report. Apparently
		// CLK has a new framework so I'm not going to worry about it much now. [benb]
		if ($_REQUEST['sort'] == "undefined")
		{
			foreach( $this->search_results as $company_name => $company_data )
			{
				$this->search_results[$company_name] = Advanced_Sort::Sort_Data($company_data, $ordercol, SORT_ASC);
			}
		}

		// GF 11016 (related: 9464) : Impact has too much data, I have to output and flush constantly in order for
		// PHP not to reach its memory limit, resulting in blank windows when clicking the csv file report, etc
		// This _really_ needs reworked. [benb]
		header( "Content-Type: text/xml\n\n");

		echo "<root>\n";
		echo "<Report_Title>$this->report_title</Report_Title>\n";
		echo "<RunDate>" . date('m/d/Y') . "</RunDate>\n";


		if( TRUE === $this->agent_list )
		{
			$agents = $this->Get_Agent_List();
			foreach($this->search_criteria['agent_id'] as $agent_id)
			{
				if(isset($agents[$agent_id]))
				{
					echo "<Agent>".$agents[$agent_id]."</Agent>\n";
				}
				else
				{
					echo "<Agent>Unassigned</Agent>\n";
				}
			}
		}

		// Is the report run for a specific date, date range, or do dates not matter?
		switch($this->date_dropdown)
		{
			case self::$DATE_DROPDOWN_RANGE:
				// GF 8641: If <DateRange> is inside this conditional block, it will not validate
				// if condition is not met. 
				echo "<DateRange>";
				if (isset($this->search_criteria['start_date_MM']))
				{
					echo $this->search_criteria['start_date_MM']   . '/'
						. $this->search_criteria['start_date_DD']   . '/'
						. $this->search_criteria['start_date_YYYY'] . " to "
						. $this->search_criteria['end_date_MM']     . '/'
						. $this->search_criteria['end_date_DD']     . '/'
						. $this->search_criteria['end_date_YYYY'];
				}
				echo "</DateRange>\n";										   
				break;
			case self::$DATE_DROPDOWN_SPECIFIC:
				echo "<Date>";

				if (isset($this->search_criteria['specific_date_MM']))
				{
					echo $this->search_criteria['specific_date_MM'] . '/'
						. $this->search_criteria['specific_date_DD'] . '/'
						. $this->search_criteria['specific_date_YYYY'];
				}
				echo "</Date>\n";					 
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

		// Sort through each company's data
		echo "<ITEMS>\n";
		if(!isset($_SESSION['reports'][$_REQUEST["mode"]]["xml_items"]) ||
				(
				 $_SESSION['reports'][$_REQUEST["mode"]]["xml_sort_field"] != $_REQUEST["sortColumn"] ||
				 $_SESSION['reports'][$_REQUEST["mode"]]["xml_sort_direction"] != $_REQUEST["sortDir"]
				)
		  )
		{
			$_SESSION['reports'][$_REQUEST["mode"]]["xml_items"] = array();
			unset($_SESSION['reports'][$_REQUEST["mode"]]["xml_filter_items"]);
			foreach( $this->search_results as $company_name => $company_data )
			{
				// An array of company totals which gets added to grand_totals
				$company_totals = array();
				foreach( $this->column_names as $data_name => $column_name )
				{
					$company_totals[$data_name] = 0;
				}
				for( $x = 0 ; $x < count($company_data) ; ++$x )
				{
					$data_item = "\t<ITEM>\n";
					$data_item .= "\t\t<company_name>$company_name</company_name>\n";
					foreach( $this->column_names as $data_col_name => $not_used )
					{
						if( count($this->link_columns) > 0 && isset($this->link_columns[$data_col_name]) && isset($company_data[$x]['mode']))
						{
							// do any replacements necessary in the link
							$this->parse_data_row = $company_data[$x];
							$href  = preg_replace_callback("/%%%(.*?)%%%/", array($this, 'Link_Parse'), $this->link_columns[$data_col_name]);
							$line_item = $this->Format_Field($data_col_name, $company_data[$x][$data_col_name]);
							$line_item = htmlentities("<a href='#' onClick=\"parent.window.location='$href'\">{$line_item}</a>");
						}
						else
						{
							$line_item = htmlentities(stripslashes($this->Format_Field($data_col_name, isset($company_data[$x][$data_col_name]) ? $company_data[$x][$data_col_name] : null)));
						}						

						$data_item .= "\t<{$data_col_name}>".$line_item."</{$data_col_name}>\n";
						$company_totals[$data_col_name] += isset($company_data[$x][$data_col_name]) ? $company_data[$x][$data_col_name] : 0;
					}
					$data_item .= "\t</ITEM>\n";
					$_SESSION['reports'][$_REQUEST["mode"]]["xml_items"][] = $data_item; 

				}
			}
		}

		// Process Filiter and Pages		
		$report_object = $_SESSION['reports'][$_REQUEST["mode"]];
		$search_method = "NORMAL";
		if(count($report_object["xml_items"]))
		{	
			$xmlitems = $report_object["xml_items"];

			// Process Filter
			if(trim($_REQUEST["filter_text"]) == "")
			{
				// Use Main List if no filter
				$search_method = "NON_FILTERED";
				$_SESSION['reports'][$_REQUEST["mode"]]["xml_filter_items"] = null;
				$report_object = $_SESSION['reports'][$_REQUEST["mode"]];
			}
			else if(	($_REQUEST["filter_field"] != $_SESSION['reports'][$_REQUEST["mode"]]["xml_filter_field"]) ||
					($_REQUEST["filter_text"] != $_SESSION['reports'][$_REQUEST["mode"]]["xml_filter_text"]) ||
					($_REQUEST["sort"] != $_SESSION['reports'][$_REQUEST["mode"]]["xml_sort_field"]) ||
					($_REQUEST["sortDir"] != $_SESSION['reports'][$_REQUEST["mode"]]["xml_sort_direction"])) 
			{
				// Ok we found out that we want to filter or we have new filter criteria
				// so now we need to create a subset of filitered data
				$search_method = "FILTERED";
				$_REQUEST["page"] = 1;
				$_SESSION['reports'][$_REQUEST["mode"]]["xml_filter_items"] = array();
				$xmlitems = array();

				for($i=0; $i<count($_SESSION['reports'][$_REQUEST["mode"]]["xml_items"]); $i++)
				{
					$line_item = $_SESSION['reports'][$_REQUEST["mode"]]["xml_items"][$i];
					$parse_items = split("\n",$line_item);					
					for($x=0; $x<count($parse_items); $x++)
					{
						// We found a macth so save it
						if(stristr($parse_items[$x],"<{$_REQUEST["filter_field"]}>"))
						{
							if(stristr($parse_items[$x],$_REQUEST["filter_text"]))
								$xmlitems[] = $line_item;

							break;
						}
					}
				}
				$_SESSION['reports'][$_REQUEST["mode"]]["xml_filter_items"] = $xmlitems;
				$report_object = $_SESSION['reports'][$_REQUEST["mode"]];						
			}

			// If we have filitered Items use it instead of mainlist
			$xmlitems = is_array($report_object["xml_filter_items"]) 
				? $report_object["xml_filter_items"] 
				: $report_object["xml_items"];

			$startpage = ($_REQUEST["page"] - 1) * $_REQUEST["pageSize"];

			foreach(array_slice($xmlitems, $startpage, $_REQUEST['pageSize']) as $item)
			{
				echo $item . "\n";
				flush();
			}
		}

		$_SESSION['reports'][$_REQUEST["mode"]]["xml_filter_field"] = $_REQUEST["filter_field"];
		$_SESSION['reports'][$_REQUEST["mode"]]["xml_filter_text"] = $_REQUEST["filter_text"];
		$_SESSION['reports'][$_REQUEST["mode"]]["xml_sort_field"] =  $_REQUEST["sort"];		
		$_SESSION['reports'][$_REQUEST["mode"]]["xml_sort_direction"] = $_REQUEST["sortDir"];

		echo "</ITEMS>\n";
		echo "<FILTER_FIELD>{$_REQUEST["filter_field"]}</FILTER_FIELD>\n";
		echo "<FILTER_TEXT>{$_REQUEST["filter_text"]}</FILTER_TEXT>\n";
		echo "<SORT_COL>{$_REQUEST["sort"]}</SORT_COL>\n";
		echo "<SORT_DIR>{$_REQUEST["sortDir"]}</SORT_DIR>\n";
		echo "<SEARCH_METHOD>{$search_method}</SEARCH_METHOD>\n";
		echo "<TOTAL_ITEMS>".count($xmlitems)."</TOTAL_ITEMS>\n";
		echo "</root>";
	}

}

?>
