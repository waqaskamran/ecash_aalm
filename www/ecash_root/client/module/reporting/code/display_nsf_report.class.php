<?php

/**
 * @package Reporting
 * @category Display
 */
class NSF_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title = "NSF Report";

		$data = ECash::getTransport()->Get_Data();
		$achtype = '';
		$reptype = '';
		if (isset($data->search_criteria)) 
		{
			$achtype = ucwords( $data->search_criteria['achtype'] );
			$reptype = $data->search_criteria['reptype'];
		}
		
		if($reptype == "nsfperstattypek")
		{
			$view_batch_id 		= "Status";
			$view_batch_created = "-";
			$_SESSION["nsfreport"]["batch_id"] 		= "Status";
			$_SESSION["nsfreport"]["batch_created"] = "-";
		}
		else if($reptype)
		{
			$_SESSION["nsfreport"]["batch_id"] 		= "Batch ID";
			$_SESSION["nsfreport"]["batch_created"] = "Batch Created";					
		}

		
		if(isset($_SESSION["nsfreport"]["batch_created"]) && $_SESSION["nsfreport"]["batch_created"] == "-")
		{
			$this->column_names       = array( 'Company_Name'   => 'Company',
										   'Batch_ID'		=>	$_SESSION["nsfreport"]["batch_id"],	
		                                   'Non_Reported'		=>	"Non Reported {$achtype}",	
		                                   'Reported'			=>	"Reported {$achtype}",	
		                                   'Total'				=>	"Total {$achtype}",	
		                                   'NonDebit'			=>	"Non {$achtype} Amount",	
		                                   'RepDebit'			=>	"Rep {$achtype} Amount",	
		                                   'TotalAmount'		=>	"Total {$achtype} Amount",		
		                                   'Reported_Percent'	=>	"Reported {$achtype} Percent",	
		                                   'Amount_Percent'		=>	"Amount {$achtype} Percent",	
		                                   );				
		}
		else 
		{
			$this->column_names       = array( 'Company_Name'   => 'Company',
											'Batch_ID'		=>	isset($_SESSION["nsfreport"]["batch_id"]) ? $_SESSION["nsfreport"]["batch_id"] : '', 	
		                                   'Batch_Created'		=>	isset($_SESSION["nsfreport"]["batch_created"]) ? $_SESSION["nsfreport"]["batch_created"] : '',	
		                                   'Non_Reported'		=>	"Non Reported {$achtype}",	
		                                   'Reported'			=>	"Reported {$achtype}",	
		                                   'Total'				=>	"Total {$achtype}",	
		                                   'NonDebit'			=>	"Non {$achtype} Amount",	
		                                   'RepDebit'			=>	"Rep {$achtype} Amount",	
		                                   'TotalAmount'		=>	"Total {$achtype} Amount",		
		                                   'Reported_Percent'	=>	"Reported {$achtype} Percent",	
		                                   'Amount_Percent'		=>	"Amount {$achtype} Percent",
		                                   );		
		
		}
		
		$this->column_format       = array(		     
												   'Batch_Created'		=> self::FORMAT_DATE,
				                                   'Non_Reported'    	=> self::FORMAT_NUMBER ,
				                                   'Reported'        	=> self::FORMAT_NUMBER ,
				                                   'Total'         	 	=> self::FORMAT_NUMBER ,
				                                   'NonDebit'        	=> self::FORMAT_CURRENCY, 
				                                   'RepDebit'        	=> self::FORMAT_CURRENCY ,
				                                   'TotalAmount'     	=> self::FORMAT_CURRENCY ,
				                                   'Reported_Percent'	=> self::FORMAT_PERCENT,
				                                   'Amount_Percent'		=> self::FORMAT_PERCENT,
				                                   );			
		$this->sort_columns       = array( 'Company_Name'   =>  'Company',
									   'Batch_ID'		=>	'Batch_ID',	
	                                   'Batch_Created'		=>	'Batch_Created',	
	                                   'Non_Reported'		=>	'Non_Reported',	
	                                   'Reported'			=>	'Reported',	
	                                   'Total'				=>	'Total',	
	                                   'NonDebit'			=>	'NonDebit',	
	                                   'RepDebit'			=>	'RepDebit',	
	                                   'TotalAmount'		=>	'TotalAmount',		
	                                   'Reported_Percent'	=>	'Reported_Percent',	
	                                   'Amount_Percent'		=>	'Amount_Percent',	
	                                   );			
		$this->link_columns       = array();
		$this->totals             = array();
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = true;
		$this->nsf_type   	      = true;
		$this->download_file_name = null;

		parent::__construct($transport, $module_name);
	}
	
/**
	 * Builds and returns the html for the report
	 *
	 * @return string
	 * @access public
	 */
	public function Get_Module_HTML()
	{
		$form = new Form( CLIENT_MODULE_DIR . "/reporting/view/display_nsf_report.html" );

		$sub = ($this->ajax_reporting) ? $this->Get_Module_AJAX_Data(): $this->Get_Module_HTML_Data();
		return $form->As_String($sub);
	}
	
	/**
	 * Gets the html for the data section of the report
	 * also updates running totals
	 * used only by Get_Module_HTML()
	 *
	 * @param  string name of the company
	 * @param  &array running totals
	 * @return string
	 * @access protected
	 */
	protected function Get_Data_HTML($company_data, &$company_totals)
	{
		$row_toggle = true;  // Used to alternate row colors
		$line       = "";

		for( $x = 0 ; $x < count($company_data) ; ++$x )
		{
			$td_class = ($row_toggle = ! $row_toggle) ? "align_left" : "align_left_alt";

			// 1 row of data
			$line .= "    <tr>\n";
			$bold_style = "";
			foreach( $this->column_names as $data_name => $column_name )
			{
				//$align = $this->getAlign($data_name);
				// the the data link to somewhere?
				$align = "left";		
				$field = (is_null($company_data[$x][$data_name])) ? $company_data[$x][$data_name] : $this->Format_Field($data_name, $company_data[$x][$data_name],false, true,$align);	
				if(is_null($field)) $bold_style = "font-weight: bold;";						
				if( count($this->link_columns) > 0 && isset($this->link_columns[$data_name]) && isset($company_data[$x]['mode']))
				{
					// do any replacements necessary in the link
					$this->parse_data_row = $company_data[$x];
					$href  = preg_replace_callback("/%%%(.*?)%%%/", array($this, 'Link_Parse'), $this->link_columns[$data_name]);
					$line .= "     <td class=\"$td_class\" style=\"text-align: $align;$bold_style\"><a href=\"#\" onClick=\"parent.window.location='$href'\">" . $field . "</a></td>\n";
				}
				else
				{
					$line .= "     <td class=\"$td_class\" style=\"text-align: $align;$bold_style\">" . $field . "</td>\n";
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

	// Fix empty totals lines
	protected function Get_Company_Total_Line($company_name, &$company_totals)
	{
		return;
	}
}

?>
