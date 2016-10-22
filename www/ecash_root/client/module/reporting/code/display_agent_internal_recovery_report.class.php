<?php

/**
 * @package Reporting
 * @category Display
 */
class Agent_Internal_Recovery_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{
/*
			$row['Paid_Perc'] = ($row['Completed_Arrangments'] / $row['Total_Arrangments']) * 100;
			$row['Paid_Amount_Perc'] = ($row['Completed_Amount'] / $row['Total_Amount']) * 100;
			$row['Failed_Perc'] = ($row['Failed_Arrangments'] / $row['Total_Arrangments']) * 100;
			$row['Failed_Amount_Perc'] = ($row['Failed_Amount'] / $row['Total_Amount']) * 100;		
			*/
		$this->report_title = "Agent Internal Recovery Report";
		$this->column_names = array( 'company_name' => 'Company',
									'agent'    			=> 'Agent',
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
		$this->sort_columns = array(	'agent',    
										'company_name',      
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
				                                         'Paid_Perc'				=> Report_Parent::$TOTAL_AS_SUM,
				                                         'Paid_Amount_Perc'			=> Report_Parent::$TOTAL_AS_SUM,
				                                         'Failed_Perc'				=> Report_Parent::$TOTAL_AS_SUM,
				                                         'Failed_Amount_Perc'		=> Report_Parent::$TOTAL_AS_SUM,		                                                 
		                                                 ),
		                             
		                             'grand'   => array( 'Total_Arrangments'    	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Total_Amount' 			=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Completed_Arrangments'	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Completed_Amount'    		=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Failed_Arrangments'   	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'Failed_Amount'   			=> Report_Parent::$TOTAL_AS_SUM,
				                                         'Paid_Perc'				=> Report_Parent::$TOTAL_AS_SUM,
				                                         'Paid_Amount_Perc'			=> Report_Parent::$TOTAL_AS_SUM,
				                                         'Failed_Perc'				=> Report_Parent::$TOTAL_AS_SUM,
				                                         'Failed_Amount_Perc'		=> Report_Parent::$TOTAL_AS_SUM,		                                                 
		                                                 )
		                             
		                            );
		$this->column_format      = array(
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
		$this->loan_type          = TRUE;
		$this->agent_list = TRUE;
		$this->wrap_header = FALSE;
		$this->ajax_reporting 	  = true;
		parent::__construct($transport, $module_name);
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
			
				if( ! empty($this->totals['company'][$data_name]) )
				{
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
					$line .=  "\t";
				}
				else if( ! $total_own_line )
				{
					if( ! empty($this->totals['company']['rows']) )
					{
						$line .= "$company_name Totals \t";
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
			// removes the last tab if we're at the end of the loop and replaces it with a newline
			$line = substr( $line, 0, -1 ) . "\n";
			//$line .= "\n";
		}

		return $line;
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
		$line = "";

		if( ! empty($this->totals['grand']['rows']) )
		{
			$line .= "Grand Totals : " . $grand_totals['rows'] . " rows\n";
		}
		else
		{
			$line .= "Grand Totals\n";
		}

		// Column grand totals
		if( (! empty($this->totals['grand']['rows']) && count($this->totals['grand']) > 1 ) ||
			(empty($this->totals['grand']['rows']) && count($this->totals['grand']) > 0 ))
		{
			// Column names again for eash reference
			$line .= $this->Get_Column_Headers( false, $grand_totals );

			// An extra tab to skip the company name field which is usually first
		//	$line .= "\t";
			foreach( $this->column_names as $data_name => $column_name )
			{
				if( ! empty($this->totals['grand'][$data_name]) )
				{
					$line .= $this->Format_Field($data_name, $grand_totals[$data_name],false,false) . "\t";
				}
				else
				{
					$line .= "\t";
				}
			}
			// removes the last tab if we're at the end of the loop and replaces it with a newline
			$line = substr( $line, 0, -1 ) . "\n";
		} // end column grand totals

		return $line;
	}
}

?>
