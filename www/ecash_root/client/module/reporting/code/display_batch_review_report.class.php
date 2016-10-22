<?php

/**
 * @package Reporting
 * @category Display
 */
class Batch_Review_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Batch Review Report";
		$this->column_names       = array( 'company_name'      => 'Company',
											'application_id'    => 'Application ID',
		                                   'name'              => 'Customer Name',
		                                   'bank_aba'          => 'ABA #',
		                                   'bank_account_type' => 'Account Type',
		                                   'amount'            => 'Amount',
		                                   'ach_type'          => 'ACH Type' );

		// Ignored by customized Get_Data_HTML() in this file
		$this->column_format       = array( 'bank_account'     => self::FORMAT_ID,
						    'bank_aba'         => self::FORMAT_ID,
						    'amount'           => self::FORMAT_CURRENCY );

		$this->sort_columns       = array( 'company_name', 'application_id',    'name',
		                                   'bank_account_type', 'amount',
		                                   'ach_type' );

		$this->link_columns       = array( 'application_id' => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%' );


		$this->totals             = array();
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_NONE;
		$this->loan_type          = false;
		$this->download_file_name = null;
		$this->ajax_reporting     = true;

		parent::__construct($transport, $module_name);
	}


	/**
	 * Have to override this becasue of funkiness in the report
	 * some companies may have error messages while other companies work
	 */
	protected function Get_Data_HTML($company_data, &$company_totals)
	{
		$row_toggle = true;  // Used to alternate row colors
		$line       = "";

		if( ! empty($company_data['message']) )
		{
			return "<tr><td colspan=\"{$this->num_columns}\">{$company_data['message']}</td></tr>\n";
		}

		for( $x = 0 ; $x < count($company_data) ; ++$x )
		{
			$td_class = ($row_toggle = ! $row_toggle) ? "align_left" : "align_left_alt";

			// 1 row of data
			$line .= "    <tr>\n";
			foreach( $this->column_names as $data_name => $column_name )
			{
				// the the data link to somewhere?
				if( count($this->link_columns) > 0 && isset($this->link_columns[$data_name]) )
				{
					// do any replacements necessary in the link
					$this->parse_data_row = $company_data[$x];
					$href  = preg_replace_callback("/%%%(.*?)%%%/", array($this, 'Link_Parse'), $this->link_columns[$data_name]);
					$line .= "     <td class=\"$td_class\"><a href=\"#\" onClick=\"parent.window.location='$href'\">" . $this->Format_Field($data_name, $company_data[$x][$data_name]) . "</a></td>\n";
				}
				else
				{
//print_r($data_name . '<br>');
					if ($data_name == 'bank_aba' || $data_name == 'bank_account')
					{
						$line .= "<td class=\"$td_class\"><div style=\"text-align:right\">" . $this->Format_Field($data_name, $company_data[$x][$data_name]) . "</div></td>\n";
					}
					else
					{
						$line .= "<td class=\"$td_class\">" . $this->Format_Field($data_name, $company_data[$x][$data_name]) . "</td>\n";
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

	/**
	 * Checks the total_conditions for specified column does necessary replacements, evals it and returns result
	 *
	 * @param  array   column's data to check
	 * @param  string  column condition to check
	 * @return boolean
	 * @access private
	 */
	protected function check_eval($line, $column)
	{
		$conditional = str_replace( "%%%var%%%", addslashes($line[$column]), $this->totals_conditions[$column] );
		return eval($conditional);
	}

	public function Download_Data()
	{
		// Holds output
		$dl_data = "";

		$dl_data .= $this->report_title . " - Run Date: " . date('m/d/Y') . "\n";

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

		// Insert a blank line in between report header and column headers
		$dl_data .= "\n";
		$total_rows = 0;

		// An empty array for the grand totals
		$grand_totals = array();
		foreach( $this->totals['grand'] as $which => $unused )
			$grand_totals[$which] = 0;

		$dl_data .= $this->Get_Column_Headers( false );

		// Sort through each company's data
		foreach( $this->search_results as $company_name => $company_data )
		{
			if( ! empty($company_data['message']) )
			{
				$dl_data .= "{$company_name}: " . $company_data['message'] . "\n";
				continue;
			}

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
				}
				// removes the last tab if we're at the end of the loop and replaces it with a newline
				$dl_data = substr( $dl_data, 0, -1 ) . "\n";
			}
			$total_rows += count($company_data);
			$company_totals['rows'] = count($company_data);

			// If there's more than one company, show a company totals line
			if( count($this->totals['company']) > 0 )
			{
				$dl_data .= $this->Get_Company_Total_Line($company_name, $company_totals) . "\n\n";
			}

			// Add the company totals to the grand totals
			foreach( $grand_totals as $key => $value )
			{
				// Flash report (and maybe others) does something special with the totals
				//  I dont remember what or why, but without isset, errors appear on other reports
				if( isset($company_totals[$key]) )
					$grand_totals[$key] += $company_totals[$key];
			}
		}

		// grand totals
		// dont show grand totals if only 1 company... exact same #s are in company totals above it
		if( count($this->totals['grand']) > 0 && $this->num_companies > 1 )
			$dl_data .= $this->Get_Grand_Total_Line($grand_totals);

		$dl_data .= "\nCount = $total_rows";

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
