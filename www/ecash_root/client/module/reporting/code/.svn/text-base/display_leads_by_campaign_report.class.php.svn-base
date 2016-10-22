<?php

/**
 * @package Reporting
 * @category Display
 */
class Leads_By_Campaign_Report extends Report_Parent
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

		$this->report_title       = "Leads By Campaign Report";

		$this->column_names       = array(	'company'                   => 'Company',
											'rpt_campaign_name'         => 'Campaign Name',
											'num_bought'                => 'Bought',

											'num_agree'                 => 'Agree',

											'num_funded'                => 'Funded' );
										
//		$this->column_format      = array( 'date_bought'               => self::FORMAT_DATE );

		$this->sort_columns       = array(					'campaign_name',		
											'num_bought', 
											
											'num_agree',
											
											'num_funded'); 


        $this->totals 			   = array( 
											'campaign_name' => array('num_bought', 
																
																     'num_agree',
																 
																     'num_funded', 
																),
											'grand' => array(	     'num_bought', 
																
																     'num_agree',
																
																     'num_funded', 
																)
											);

		$this->totals_conditions   = null;

		$this->date_dropdown       = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type           = false;
		$this->download_file_name  = null;
		$this->ajax_reporting 	   = true;
		$this->company_list_no_all = false;
		parent::__construct($transport, $module_name);
		
		if( isset($this->search_results) && is_array($this->search_results))
		{
			foreach( $this->column_names as $data_col_name => $not_used )
			{
				// Ensure totals array is in valid format
				//GF #21769 - Validated campaign_name array
				if( empty($this->totals['campaign_name'][$data_col_name]) && is_array($this->totals['campaign_name']))
				{
					if( in_array($data_col_name, $this->totals['campaign_name'], true) )
					{
						$this->totals['campaign_name'][$data_col_name] = self::$TOTAL_AS_SUM;
						unset($this->totals['campaign_name'][array_search($data_col_name, $this->totals)]);		
					}
				}
			}
		}
		
		if( in_array('rows', $this->totals['campaign_name'], true) )
		{
			$this->totals['campaign_name']['rows'] = self::$TOTAL_AS_COUNT;
			unset($this->totals['campaign_name'][array_search('rows', $this->totals)]);	
		}
	}
	
	/** GF #21769
	 * Generates an HTML summary, organizing data by campaign name. Returns false
	 * if campaign_name was not specified as a total type.
	 *
	 * @param array $company_data Search result set
	 * @param array $campaign_totals Array containing campaign total information
	 * @return string HTML data
	 */
	protected function Get_Campaign_Total_HTML($company_data, &$campaign_totals)
	{
		if(is_array($this->totals['campaign_name']))
		{
			$line = "";
	
			//Iterate through the results and add the column amount
			//to the campaign_totals if it is a valid column
			foreach($company_data as $data)
			{
				$campaign_name = $data['rpt_campaign_name'];
				reset($this->column_names);
				
				$line .= "<tr>\n";
				$line .= "<th class=\"report_foot\" style=\"text-align: right\">{$campaign_name} Totals</th>\n";
				
				foreach($this->column_names as $data_name=>$column_name)
				{
					if(isset($this->totals['campaign_name'][$data_name]))
					{
						//This column is one that we want included in our totals, and it
						//has data associated with it, so we include it
						if(isset($data[$data_name]))
						{
							$campaign_totals[$campaign_name][$data_name] += $data[$data_name];
							
							$line .= "<th class=\"report_foot\">" . $this->Format_Field($data_name, $data[$data_name]) . "</th>\n";
						}
					}
					//This column doesn't belong in the totals list
					else
					{
						if($data_name != 'rpt_campaign_name')
							$line .= "<th class=\"report_foot\">&nbsp;</th>\n";
					}
				}
				
				$line .= "</tr>\n";
			}

			return $line;
		}
		else
		{
			return false;
		}
	}
	
	public function Get_Module_AJAX_Data()
	{
		$mode = ECash::getTransport()->page_array[2];
		// New Page, New Items
		unset($_SESSION['reports'][$mode]["xml_items"]);
		
		$total_rows = 0;
         // substitutions to make in the html template
		$substitutions = new stdClass();

		$substitutions->report_title = $this->report_title;

		// Get the date dropdown & loan type html stuff
		$this->Get_Form_Options_HTML( $substitutions );

		$substitutions->search_message    = "";
		$substitutions->download_link	  = "";
		$substitutions->search_result_set = "";
		$result_body_foot = null;

		$grand_totals = array();
		foreach( $this->totals['grand'] as $which => $unused )
		{
			$grand_totals[$which] = 0;
		}
		
		while( ! is_null($next_level = ECash::getTransport()->Get_Next_Level()) )
		{
			if( $next_level == 'message' )
			{
				$substitutions->search_message = "<span style='color: red'>{$this->search_message}<span>\n";
			}
			else if( $next_level == 'report_results' && $this->num_companies > 0 )
			{
				// First turn on the download link
				if ($substitutions->download_link == '')
				{
					$substitutions->download_link = "[ <a href=\"?module=reporting&mode=" . urlencode($mode) . "&action=download_report\" class=\"download\">Download Displayed Data to CSV File</a> ]";				
				}

				// Export The Message
				if ($substitutions->search_message == '')
				{
					if ($this->num_companies == 1)
					{
						$message = "Data for {$this->num_companies} company displayed.";
					}
					else
					{
						$message = "Data for {$this->num_companies} companies displayed.";
					}
	
					$substitutions->search_message = "<span style=\"color: darkblue\">$message</span>\n";	
				}

				// Create Result Set
				if ($substitutions->search_result_set == '')
				{
					
					foreach ($this->column_names as $field => $field_name)
					{
						$company_totals[$field] = 0;
						$width = (isset($this->column_width[$field])) ? $this->column_width[$field] : 100;
						$json_header[] = "\n{header: \"{$field_name}\", width: {$width}}";
						$filter_options[] = "<option value=\'$field\'>$field_name</option>";
					}

					
					$result_body = "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/grid-report.css?".time()."\" />\n";
					$result_body .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/tabs.css?".time()."\" />\n";
					$result_body .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/toolbar-report.css?".time()."\" />\n";
					//$result_body .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/reports.css\" />\n";
					$result_body .= "<script src=\"js/yui/utilities/utilities.js?timer=".time()."\" ></script>\n";
					$result_body .= "<script src=\"js/lib/yui-ext.js?timer=".time()."\"></script>\n";
															
					$result_body .= "<tr>\n";
					$result_body .= " <td class=\"align_left\">\n";
					$result_body .= "  <div id=\"report_result\" class=\"reporting\">\n";

					$result_body .= "	<div id=\"result_tab_panel\">\n";
					$result_body .= "		<div id=\"result_tab\" class=\"tab-content\">\n";
					$result_body .= "			<div id=\"ajax_result\" style=\"width:780px; height:350px;background: #EEEEEE;\"></div>\n";
					
					$result_body .= "<div id='report_sum' style=\"height:350px;overflow: auto;\"><table style=\"border-spacing: 2px;\">";
					
					$campaign_header = $this->Get_Column_Headers();
					
					foreach ($this->search_results as $company_name => $company_data)
                    {
                    	$campaign_totals = array();
                    	$campaign_totals['rows'] = 0;
                    	
                    	$total_rows += count($company_data);
						$this->Get_Data_HTML($company_data, $company_totals);

						// Show Campaign totals [GF #21769]
						$campaign_result = $this->Get_Campaign_Total_HTML($company_data, $campaign_totals);
						if($campaign_result !== false)
						{
							$result_body .= $campaign_header;
							$result_body .= $campaign_result;
							$campaign_header       = '';
						}
						
						foreach ($grand_totals as $key => $value)
						{
							/* [GF #The numbers inside of company totals and campaign totals
							 * will always sum up to the same amount, so if we've already
							 * added company totals to the grand totals, then don't add
							 * it again. 
							 */
							foreach($campaign_totals as $campaign_name => $totals)
							{
								if(isset($totals[$key]) && empty($company_totals[$key]))
								{
									$grand_totals[$key] += $totals[$key];
								}
							}
						}

                    }

                    if (count($this->totals['grand']) > 0)
					{
						$result_body .= $this->Get_Grand_Total_HTML($grand_totals);
					}
					
					$result_body .= "</table></div>";
					
					$result_body .= "		</div>\n";
					$result_body .= "	</div>";									

					$result_body .= "  </div>\n";
                    $result_body .= " </td>\n";
                    $result_body .= "</tr>\n";

					// YUI EXT Selection Box
					$result_body .= "<script>\n";
					$result_body .= "sm = new YAHOO.ext.grid.SingleSelectionModel();\n";
					$result_body .= "cm = new YAHOO.ext.grid.DefaultColumnModel([".implode(",",$json_header)."]);\n";
					$result_body .= "cm.defaultSortable = true;\n";

					$result_body .= "dm = new YAHOO.ext.grid.XMLDataModel({
					    tagName: 'ITEM',
					    totalTag: 'TOTAL_ITEMS',
					    fields: ['".implode("','",array_keys($this->column_names))."']
					});\n";

					$result_body .= "dm.createParams_original = dm.createParams;\n";
					$result_body .= "dm.createParams = function(pageNum, sortColumn, sortDir){
					    params = dm.createParams_original(pageNum, sortColumn, sortDir);
					    params['sort'] = dm.schema.fields[sortColumn];
					    if(document.getElementById('filter_field')) 
						{
							params['filter_field'] = document.getElementById('filter_field').value;
					    }
					    if(document.getElementById('filter_text')) 
						{
							params['filter_text'] = document.getElementById('filter_text').value;
					    }
					    return params;
					};\n";

					// initialize paging
					$result_body .= "dm.initPaging('/?module=reporting&mode=".ECash::getTransport()->page_array[2]."&action=ajax_report_data', 100);\n";
					$result_body .= "grid = new YAHOO.ext.grid.Grid('ajax_result', dm, cm, sm);\n";
					$result_body .= "grid.render();\n";

					// toolbar
					$result_body .= "var toolbar = grid.getView().getPageToolbar();\n";
					$result_body .= "toolbar.addSeparator();\n";
					$result_body .= "toolbar.addText('{$total_rows} total record(s)');\n";
					$result_body .= "toolbar.addSeparator();\n";
					$result_body .= "toolbar.addText('Filter:');\n";
					$result_body .= "toolbar.addText('<select name=filter_field id=filter_field>".implode("",$filter_options)."</select>');\n";
					$result_body .= "toolbar.addText('<input type=text name=filter_text id=filter_text>');\n";
					$jsbtn = "dm.loadPage(1);";
					$result_body .= "toolbar.addText('<input type=button name=button_submit id=button_submit value=Update onClick=$jsbtn>');\n";
					$jsbtn = "document.getElementById(\'filter_text\').value=\'\';dm.loadPage(1);";
					$result_body .= "toolbar.addText('<input type=button name=button_clear id=button_clear value=Reset onClick=$jsbtn>');\n";					
					// the grid is ready, load page 1 of items
					$result_body .= "dm.loadPage(1);\n";

					$result_body .= "var tabs = new YAHOO.ext.TabPanel('result_tab_panel');\n";
					$result_body .= "tab1 = tabs.addTab( 'result_tab', \"Report\" );\n";
					$result_body .= "tabs.activate('result_tab');\n";

					$result_body .= "tab2 = tabs.addTab( 'report_sum', \"Summary\" );\n";
					if($result_body_foot == '' && $campaign_result === false)
					{
						$result_body .= "tabs.disableTab('report_sum');\n";
					}

					$result_body .= "  </script>\n";                    

                    $substitutions->search_result_set = $result_body;
				}
			}
			else if ($next_level == 'report_results')
			{
				$message = "No application data was found that meets the specified report criteria.";
				$substitutions->search_message = "    <tr><td class=\"align_left\" style=\"color: darkblue\">$message</td></tr>\n";
			}

		}
		// Set Defaults
		if($substitutions->search_message == "")
			$substitutions->search_message = "<tr><td>&nbsp;</td></tr>";
		
		if($substitutions->search_result_set == "")
			$substitutions->search_result_set = "<tr><td><div id=\"report_result\" class=\"reporting\"></div></td></tr>";
		
		
		return $substitutions;
	}
	
	protected function Get_Column_Headers( $html = true, $grand_totals = null )
	{
		$column_headers = "";
		$wrap_header = $this->wrap_header ? '' : 'nowrap';

		// For company headers (with sort links)
		if( $html === true && ! isset($grand_totals) )
		{
			// Column names
			$column_headers .= "    <tr>\n";
			foreach( $this->column_names as $data_col_name => $column_name )
			{
				//print_r($this)
				// make the column name a sort link if wanted
				if( in_array( $data_col_name, $this->sort_columns ) &! $this->ajax_reporting)
				{
					$column_headers .= "     <th $wrap_header class=\"report_head\"><a href=\"?module=reporting&mode=".$_REQUEST['mode']."&sort=" . urlencode($data_col_name) . "\">$column_name</a></th>\n";
				}
				elseif ($this->ajax_reporting && !in_array($data_col_name,array_keys($this->totals['company'])) && !in_array($data_col_name, array_keys((array)$this->totals['campaign_name'])))
				{
					$column_headers .= "     <th $wrap_header class=\"report_head\"></th>\n";
				}
				else
				{
					$column_headers .= "     <th $wrap_header class=\"report_head\">$column_name</th>\n";
				}
			}
			$column_headers .= "    </tr>\n";
		}
		else if( $html === true ) // For grand totals (no sort links)
		{
			// Column names again for eash reference
			$column_headers .= "    <tr>\n";
			foreach( $this->column_names as $data_col_name => $column_name )
			{
				// Only print the column headers for columns showing a grand total
				if( isset($grand_totals[$data_col_name]) )
				{
					$column_headers .= "     <th class=\"report_head\">$column_name</th>\n";
				}
				else
				{
					$column_headers .= "     <th></th>\n";
				}
			}
			$column_headers .= "    </tr>\n";
		}
		else // For downloading (tab seperated)
		{
			$column_headers .= "";

			if (isset($grand_totals)) {
				foreach( $this->column_names as $data_name => $column_name )
				{
					if( !empty($this->totals['grand'][$data_name]) )
					{
						$column_headers .= $column_name . "\t";
					}
					else
					{
						$column_headers .= "\t";
					}
				}
			} else {
				foreach( $this->column_names as $data_name => $column_name ) {
					$column_headers .= $column_name . "\t";
				}
			}

			$column_headers = substr( $column_headers, 0, -1 ) . "\n";
		}

		return $column_headers;
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
			$campaign_totals = array();
			foreach ($this->column_names as $data_name => $column_name)
			{
				$campaign_totals[$data_name] = 0;
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
                    $this->totals['campaign_name'][$data_col_name] = isset($this->totals['campaign_name'][$data_col_name]) ? $this->totals['company'][$data_col_name] : null;
                    
                    
                    $company_data[$x][$data_col_name] = isset($company_data[$x][$data_col_name]) ? $company_data[$x][$data_col_name]: null;

                    $dl_data .= $this->Format_Field($data_col_name, $company_data[$x][$data_col_name], false, false) . "\t";

                    $company_totals[$data_col_name] += $company_data[$x][$data_col_name];
                    
                  	$campaign_name = $company_data[$x]['rpt_campaign_name'];
                    $campaign_totals[$campaign_name][$data_col_name] += $company_data[$x][$data_col_name];
				}

				// removes the last tab if we're at the end of the loop and replaces it with a newline
				$dl_data = substr($dl_data, 0, -1) . "\n";
			}
			
			$total_rows += count($company_data);
			$company_totals['rows'] = count($company_data);

			// Add the company totals to the grand totals
			foreach ($grand_totals as $key => $value)
			{
				foreach($campaign_totals as $campaign_name=>$totals)
				{
					$grand_totals[$key] += $totals[$key];	
				}
			}
		}

		// grand totals
		// dont show grand totals if only 1 company... exact same #s are in company totals above it
		if (count($this->totals['grand']) > 0 && $this->num_companies > 1)
		{
			$dl_data .= $this->Get_Grand_Total_Line($grand_totals);
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
