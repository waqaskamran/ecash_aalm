<?php

/**
 * @package Reporting
 * @category Display
 */
class Flash_Report extends Report_Parent
{
	private $grand_totals;

	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->current_model      = -1;
		$this->report_title       = "Flash Report";
		$this->column_names       = array( 'model'  => 'Pay Period',
		                                   'status' => 'Status',
		                                   'count'  => 'Customer Count' );
		$this->sort_columns       = null;
		$this->link_columns       = null;
		// This is now used just to ensure get_grand_total_html
		$this->totals             = array("grand" => array('weekly','bi_weekly','twice_monthly','monthly'));
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_SPECIFIC;
		$this->loan_type          = true;
		$this->download_file_name = null;

		parent::__construct($transport, $module_name);
	}

	// This report is entirely whacked, just override everything and print all in the get_company_foot method
	protected function Get_Data_HTML($company_data, &$company_totals)
	{
		$current_model = null;

		$row_toggle    = true;  // Used to alternate row colors
		$output        = "";
		$company_grand = 0;
		
		//This is a hack to keep Firefox 3's overly-ridiculous overflow scrollbar from clipping the last line
		$Style_Padding = " style=\"padding-right: 17px;\"";
		
		foreach( $company_data as $row )
		{
			$td_class = ($row_toggle = ! $row_toggle) ? "align_left" : "align_left_alt";

			if( $row['model'] !== $current_model )
			{
				if( isset($count) )
				{
					$output  .= "    <tr><td class=\"{$td_class}\"></td><td class=\"{$td_class}\" style=\"text-align:right;font-weight:bold;\">Total:</td>";
					if ($td_class == 'align_left')
					{
						$output  .= "<td class=\"align_right\"{$Style_Padding}><b>" . $this->Format_Field('count', $count) . "</b></td></tr>\n";
					}
					else
					{
						$output  .= "<td class=\"align_right_alt\"{$Style_Padding}><b>" . $this->Format_Field('count', $count) . "</b></td></tr>\n";
					}

					$td_class = ($row_toggle = ! $row_toggle) ? "align_left" : "align_left_alt";
					$company_grand += $count;
					if( isset($this->grand_totals[$current_model]) )
						$this->grand_totals[$current_model] += $count;
					else
						$this->grand_totals[$current_model] = $count;
				}
				$count   = 0;

				$current_model = $row['model'];
				$output .= "    <tr><td class=\"{$td_class}\">{$current_model}</td>";
			}
			else
				$output .= "    <tr><td class=\"{$td_class}\"></td>";

			if ($td_class == 'align_left')
			{
				$output .= "<td class=\"{$td_class}\">" . $row['status'] . "</td><td class=\"align_right\"{$Style_Padding}>" .
			  	         $this->Format_Field('count',$row['count']) . "</td></tr>\n";
			}
			else
			{
				$output .= "<td class=\"{$td_class}\">" . $row['status'] . "</td><td class=\"align_right_alt\"{$Style_Padding}>" .
			  	         $this->Format_Field('count',$row['count']) . "</td></tr>\n";
			}

			$count += $row['count'];
		}

		$company_grand += $count;

		if( isset($this->grand_totals[$current_model]) )
			$this->grand_totals[$current_model] += $count;
		else
			$this->grand_totals[$current_model] = $count;

		$td_class = ($row_toggle = ! $row_toggle) ? "align_left" : "align_left_alt";
		$output  .= "    <tr><td class=\"{$td_class}\"></td><td class=\"{$td_class}\" style=\"text-align:right;font-weight:bold;\">Total:</td>";
	
		if ($td_class == 'align_left')
		{
			$output  .= "<td class=\"align_right\"{$Style_Padding}><b>" . $this->Format_Field('count', $count) . "</b></td></tr>\n";
		}
		else
		{
			$output  .= "<td class=\"align_right_alt\"{$Style_Padding}><b>" . $this->Format_Field('count', $count) . "</b></td></tr>\n";
		}


		$td_class = ($row_toggle = ! $row_toggle) ? "align_left" : "align_left_alt";
		$output  .= "    <tr><td class=\"{$td_class}\"></td><td class=\"{$td_class}\" style=\"text-align:right;font-weight:bold;\">Grand:</td>";
		if ($td_class == 'align_left')
		{
			$output  .= "<td class=\"align_right\"{$Style_Padding}><b>" . $this->Format_Field('count', $company_grand) . "</b></td></tr>\n";
		}
		else
		{
			$output  .= "<td class=\"align_right_alt\"{$Style_Padding}><b>" . $this->Format_Field('count', $company_grand) . "</b></td></tr>\n";
		}

		return $output;
	}

	
	
	
	protected function Get_Data_LINE($company_data, &$company_totals)
	{
		$current_model = null;

		$output        = "";
		$company_grand = 0;

		foreach( $company_data as $row )
		{

			if( $row['model'] !== $current_model )
			{
				if( isset($count) )
				{
					$output  .= "Total:";
					$output  .= $this->Format_Field('count', $count) . "\n";
					$company_grand += $count;
					if( isset($this->grand_totals[$current_model]) )
						$this->grand_totals[$current_model] += $count;
					else
						$this->grand_totals[$current_model] = $count;
				}
				$count   = 0;

				$current_model = $row['model'];
				$output .= "{$current_model}\t";
			}
			else
				$output .= "\t";

			$output .= $row['status'] . "\t" .
			           $this->Format_Field('count',$row['count']) . "\n";

			$count += $row['count'];
		}

		$company_grand += $count;

		if( isset($this->grand_totals[$current_model]) )
			$this->grand_totals[$current_model] += $count;
		else
			$this->grand_totals[$current_model] = $count;

		$output  .= "\tTotal:\t";
		$output  .= $this->Format_Field('count', $count) . "\n";
		$output  .= "\tGrand:\t";
		$output  .= $this->Format_Field('count', $company_grand) . "\n";

		return $output;
	}
		
	/**
	* Gets the html for the grand totals
	* used only by Get_Module_HTML()
	*
	* @param  array  the grand totals for the entire report
	* @return string
	*/
	protected function Get_Grand_Total_HTML($grand_totals)
	{
		$output  = "    <tr><th class=\"report_foot\" colspan=\"{$this->num_columns}\" ";
		$output .= "style=\"border-top: thin solid black;\">Grand Totals</th></tr>\n";

		foreach( $this->grand_totals as $period => $count )
		{
			$output .= "    <tr><td class=\"report_foot\" style=\"text-align:right;font-weight:bold;\">";
			$output .= "{$period} Grand:</td><td class=\"report_foot\">" . $this->Format_Field('count',$count) . "</td><td></td></tr>\n";
		}

		return $output;
	}

	/**
	* Gets the text for the grand totals
	* used only by Download_Data()
	*
	* @param  array  the grand totals for the entire report
	* @return string
	* @access protected
	*/
	protected function Get_Grand_Total_Line($grand_totals)
	{
		$output  = "Grand Totals\n";

		foreach( $grand_totals as $period => $count )
		{
			// Yeah, it's ugly, but it's the easiest way to replace the underscores
			// with dashes and uppercase the first letter of each word to make
			// it look pretty
			$tmp_period = preg_replace("/_/"," ", $period);
			$tmp_period = ucwords($tmp_period);
			$tmp_period = preg_replace("/ /","-", $tmp_period);
			$output .= "\t\t{$tmp_period} Grand:\t" . $this->Format_Field('count',$count) . "\n";
		}

		return $output;
	}
	
	
	protected function Get_Total_HTML($company_name, &$company_totals)
	{
		return "";
	}

	/**
	* prints the html for the extra totals
	*
	* @param  array  $count data to print
	* @return string        output
	* @todo   Handle download data properly
	*/
	private function Output_Totals( $count )
	{
		$output  = "    <tr><th colspan=\"{$this->num_columns}\">\n";
		$output .= "     <table cellpadding=\"0\" class=\"report_company_foot\" width=\"50%\">\n";
		$output .= "      <tr><th>Pay Period</th><th>Status</th><th>Customer Count</th></tr>\n";

		foreach( $count as $frequency => $data )
		{
			$row_toggle = true;

			$output .= "      <tr><td>{$frequency}</td>";
			$first = true;
			foreach( $data as $status => $dd )
			{
				$td_class = ($row_toggle = ! $row_toggle) ? "align_left" : "align_left_alt";

				if( $first === true )
				{
					$first = false;
					$output .= "<td class=\"{$td_class}\">{$status}</td><td class=\"{$td_class}\">" .
					           array_shift($dd) . "</td><td class=\"{$td_class}\">" . array_shift($dd) . "</td></tr>\n";
				}
				else
					$output .= "      <tr><th></th><td class=\"{$td_class}\">{$status}</th><td class=\"{$td_class}\">" .
					           array_shift($dd) . "</td><td class=\"{$td_class}\">" . array_shift($dd) . "</td></tr>\n";
			}
		}

		$output .= "    </table>\n";
		$output .= "   </th></tr>\n";

		return $output;
	}

	/**
	* Used to format field data for printing
	*
	* @param string  $name column name to format
	* @param string  $data field data
	* @param boolean $totals formatting totals or data?
	* @param boolean $html format for html?
	* @return string
	*/
	protected function Format_Field( $name, $data, $totals = false, $html = true )
	{
		if( $name === 'count' )
			return number_format($data, 0, null, ",");

		return $data;
	}


  /**
	* Overrides Report_Parent->Download_Data() since this report has funk processing
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
					throw new Exception( "Unrecognized loan type: " . $this->search_criteria['loan_type'] );
					break;
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
			// Nothing to do		$current_model = null;
				break;
		}
			
		// Insert a blank line in between report header and column headers
		$dl_data .= "\n";

		$total_rows = 0;

		// An empty array for the grand totals
		$grand_totals = array();
		foreach( $this->totals['grand'] as $which => $unused )
		{
			$grand_totals[$unused] = 0;
		}

		// The Column Headers
		$dl_data .= $this->Get_Column_Headers( false );

		// Sort through each company's data
		foreach( $this->search_results as $company_name => $company_data )
		{	
			$company_grand = 0;
			unset($count);
			// Pretty the company name up a bit
			$show_company_name = strtoupper($company_name);

			foreach( $company_data as $row )
			{
				if( $row['model'] !== $current_model ) 
				{
					if( isset($count) ) 
					{
						$dl_data  .= "$show_company_name\t\tTotal:\t" . $this->Format_Field('count', $count) . "\n";
						$company_grand += $count;
					}
					$count   = 0;

					$current_model = $row['model'];
					// Display Company Name & Pay Period
					$show_current_model = preg_replace("/_/"," ", $current_model);
					$show_current_model = ucwords($show_current_model);
					$show_current_model = preg_replace("/ /","-", $show_current_model);
					
					$dl_data .= "$show_company_name\t" . $show_current_model . "\t";

				} 
				else 
				{
					// Just show the company name
					$dl_data .= "$show_company_name\t\t";
				}
				// Display the Status and the Customer Count
				$dl_data .= $row['status'] . "\t" . $this->Format_Field('count',$row['count']) . "\n";
				// Add the count to the total count
				$count += $row['count'];

				//If the current model is part of the grand total, add count to it.
				if( isset($grand_totals[$current_model])) 
				{
					$grand_totals[$current_model] += $row['count'];
				}

			}

			$company_grand += $count;

			$dl_data  .= "$show_company_name\t\tTotal:\t" . $this->Format_Field('count', $count) . "\n";
			$dl_data  .= "$show_company_name\t\tGrand:\t" . $this->Format_Field('count', $company_grand) . "\n\n";

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
					$company_totals[$data_col_name] += $company_data[$x][$data_col_name];
				}
			}
			$total_rows += count($company_data);
			$company_totals['rows'] = count($company_data);

			// If there's more than one company, show a company totals line
			if( count($this->totals['company']) > 0 ) 
			{
				$dl_data .= $this->Get_Company_Total_Line($company_name, $company_totals) . "\n";
			}

		}

		// grand totals
		// dont show grand totals if only 1 company... exact same #s are in company totals above it
		if( count($this->totals['grand']) > 0 && $this->num_companies > 1 )
			$dl_data .= $this->Get_Grand_Total_Line($grand_totals) . "\n";

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
