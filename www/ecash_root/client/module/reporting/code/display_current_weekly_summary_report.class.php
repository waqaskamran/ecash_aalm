<?php

/**
 * @package Reporting
 * @category Display
 */
class Current_Weekly_Summary_Report extends Report_Parent
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

		$this->report_title       = "Weekly Summary Report";

		$this->column_names       = array(	'company'                   => 'Company',
											'week_bought'               => 'Week',
											'num_bought'                => 'Leads Bought',
											'num_funded'                => 'Funded',
											'funded_amt'                => 'Funded Amount',
											'deposits_amt'              => 'Deposits',
											'disbursements_amt'         => 'Disbursements');
										
		$this->column_format      = array(	'funded_amt'               => self::FORMAT_CURRENCY,
											'deposits_amt'             => self::FORMAT_CURRENCY,
											'disbursements_amt'        => self::FORMAT_CURRENCY );

		$this->sort_columns       = array(	'week_bought',
											'num_bought',
											'num_funded',
											'funded_amt',
											'deposits_amt',
											'disbursements_amt');


        $this->totals 			   = array(
											'company' => array(	'num_bought',
																'num_funded',
																'funded_amt',
																'deposits_amt',
																'disbursements_amt'),
											'grand'   => array( 'num_bought',
																'num_funded',
																'funded_amt',
																'deposits_amt',
																'disbursements_amt'));
;


		$this->totals_conditions   = null;

		$this->date_dropdown       = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type           = false;
		$this->download_file_name  = null;
		$this->ajax_reporting 	   = true;
		$this->company_list_no_all = false;
		parent::__construct($transport, $module_name);
	}

	// This method is overridden because of the stupid requirement
	// to add text that has nothing to do with anything in ecash.
	// Hopefully this chunk of code can DIAF. [benb]
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

		$dl_data .= "\n";
		
		// Add Stupid headers
		$dl_data .= "Outstanding Balance of Original Investment:\n";
		$dl_data .= "Accrued Interest for Week:\n";
		$dl_data .= "Accrued Interest To-Date:\n";

		// for the html headers
		$data_length = strlen($dl_data);

		header( "Accept-Ranges: bytes\n");
		header( "Content-Length: $data_length\n");
		header( "Content-Disposition: attachment; filename={$this->download_file_name}\n");
		header( "Content-Type: text/csv\n\n");

		//mantis:4324
		$generic_data = $this->transport->Get_Data();

		if($generic_data->is_upper_case)
			$dl_data = strtoupper($dl_data);
		else
			$dl_data = strtolower($dl_data);
		//end mantis:4324

		echo $dl_data;
	}
}

?>
