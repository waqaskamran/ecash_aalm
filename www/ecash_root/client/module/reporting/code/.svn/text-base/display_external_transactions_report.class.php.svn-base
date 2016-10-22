<?php

/**
 * @package Reporting
 * @category Display
 */
class External_Transactions_Report extends Report_Parent
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

		$this->report_title       = "External Transactions Report";

		$this->column_names       = array( 
										'company_name' 	=> 'Company',
										'application_id' 	=> 'Application ID',
										'customer_name'  	=> 'Customer Name',
										'transaction_type' 	=> 'Transaction Type',
										'transaction_date' 	=> 'Transaction Date',
										'status'         	=> 'Status', 
										'amount'         	=> 'Amount'
										);

		$this->column_format       = array(
										'payment_date'   => self::FORMAT_DATE,
										'amount'         => self::FORMAT_CURRENCY );

		$this->sort_columns       = array( 
										'company_name',
										'application_id', 	'customer_name',
										'payment_type',		'payment_date',
										'amount',			'status');

        $this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

		$this->totals             = array( 'company' => array( 'rows',
		                                                       'amount'      => Report_Parent::$TOTAL_AS_SUM
		                                                     ),
		                                   'grand'   => array( 'rows',
		                                                       'amount'      => Report_Parent::$TOTAL_AS_SUM) );
		
	

	//	$this->totals_conditions  = null;

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = true;
		$this->download_file_name = null;

		// Turns on AJAX Reporting
		$this->ajax_reporting 	  = true;

		parent::__construct($transport, $module_name);
	}
	
	
	protected function Get_Data_HTML($company_data, &$company_totals)
        {
                $row_toggle = true;  // Used to alternate row colors
                $line       = "";

		$wrap_data   = $this->wrap_data ? '' : 'nowrap';
		$company_totals['types'] = array();
		for( $x = 0 ; $x < count($company_data) ; ++$x )
		{
			$td_class = ($row_toggle = ! $row_toggle) ? "align_left" : "align_left_alt";

			// 1 row of data
			$line .= "    <tr>\n";
			$current_type = null;
			
			foreach( $this->column_names as $data_name => $column_name )
			{
				$align = 'left';
				$data = $this->Format_Field($data_name,  $company_data[$x][$data_name], false, true, $align);
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


					switch($data_name)
					{
						case 'transaction_type':
							if($current_type !=  $company_data[$x][$data_name])
							{
								$current_type =  $company_data[$x][$data_name]; 
								$current_status ='';
								$company_totals['types'][$current_type] =  array();
							}							
						break;
						
						case 'status':
							if (isset($company_totals[$current_type .  $company_data[$x][$data_name]])) 
								$company_totals[$current_type .  $company_data[$x][$data_name]]++;
							else
								$company_totals[$current_type .  $company_data[$x][$data_name]] = 1;
							if($current_status != $company_data[$x][$data_name])
							{
								$current_status =  $company_data[$x][$data_name];
								$company_totals['types'][$current_type][$current_status] = $current_status;
							}
						break;
					
						case 'amount':
							if (isset($company_totals[$current_type . $current_status .'amount']))
								$company_totals[$current_type . $current_status .'amount'] += $company_data[$x][$data_name];
							else 
								$company_totals[$current_type . $current_status .'amount'] =  $company_data[$x][$data_name];
							if (isset($company_totals[$current_type .'amount'] ))
								$company_totals[$current_type .'amount'] +=  $company_data[$x][$data_name];
							else 
								$company_totals[$current_type .'amount'] =  $company_data[$x][$data_name];
						break;
			
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
		$line = "";

		$line .= "<tr><th nowrap colspan=\"{$this->num_columns}\" class=\"report_head\">Company<th nowrap colspan=\"{$this->num_columns}\" class=\"report_head\" align=left>Transaction Type<th nowrap colspan=\"{$this->num_columns}\" class=\"report_head\">Status<th nowrap colspan=\"{$this->num_columns}\" class=\"report_head\">Rows  <th nowrap colspan=\"{$this->num_columns}\" class=\"report_head\">Total Amount";
		$line .= "<tr border =1 ><th class=\"report_foot\" colspan=\"{$this->num_columns}\" nowrap>$company_name  ";
		
		foreach(array_keys($company_totals['types']) as $trans_type)
		{
			$line .= "<th class=\"report_foot\" colspan=\"{$this->num_columns}\" nowrap> $trans_type";
			$running_total = 0;
			$i=0;
			foreach($company_totals['types'][$trans_type] as $status)
			{
				$line .= "<th class=\"report_foot\" colspan=\"{$this->num_columns}\" nowrap> ". ucfirst($status);
				$line .= "<th class=\"report_foot\" colspan=\"{$this->num_columns}\" nowrap ><center>" . $company_totals[$trans_type . $status];
				$running_total += $company_totals[$trans_type . $status];
				$line .= "</center><th class=\"report_foot\" colspan=\"{$this->num_columns}\" nowrap>" . $this->Format_Field('amount',$company_totals[$trans_type . $status . 'amount']);
				
			  	if(($i+1) == count($company_totals['types'][$trans_type]))
			  	{
			  		$line .= "<tr><td colspan=\"{$this->num_columns}\" nowrap>";
			  	}
			  	else 
			  	{
			  		$line .= "<tr><td colspan=\"{$this->num_columns}\" nowrap><td colspan=\"{$this->num_columns}\" nowrap>";	
			  	}
				$i++;
			}
			$line .= "<th nowrap colspan=\"{$this->num_columns}\" class=\"report_head\" >Totals<th class=\"report_foot\" colspan=\"{$this->num_columns}\" nowrap>";
			$line .= "<th class=\"report_foot\" colspan=\"{$this->num_columns}\" nowrap> <center>$running_total</center>";
			$line .= "<th class=\"report_foot\" colspan=\"{$this->num_columns}\" nowrap >" . $this->Format_Field('amount',$company_totals[$trans_type . 'amount']);
			$line .= "<tr><td colspan=\"{$this->num_columns}\" nowrap height='5px'><tr border=1 ><td colspan=\"{$this->num_columns}\" nowrap>";	
			
		}
		return $line;
		

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
					$result_body .= "			<div id=\"ajax_result\" style=\"height:350px;background: #EEEEEE;\"></div>\n";
					
					$result_body .= "<div id='report_sum' style=\"height:350px;overflow: auto;\"><table>";
					$footer_cols = $this->Get_Column_Headers();
					
					foreach ($this->search_results as $company_name => $company_data)
                    {
                    	$company_totals = array();
                    	$company_totals['rows'] = 0;
                    	$total_rows += count($company_data);
						$this->Get_Data_HTML($company_data, $company_totals);

						// Show Extended company data
						$result_body_foot = $this->Get_Total_HTML($company_name, $company_totals);
						if ($result_body_foot != '')
						{
							$result_body .= $this->Get_Company_Head($company_name);
						//	$result_body .= $footer_cols;
							$result_body .= $result_body_foot;
							$result_body .= $this->Get_Company_Foot($company_name);
							$footer_cols = '';
						}
						
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
					if($result_body_foot == '')
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


	
}

?>
