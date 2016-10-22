<?php

require_once(CLIENT_CODE_DIR . "display_parent.abst.php");
require_once("dropdown.1.generic.php");

//ecash module
class Display_View extends Display_Parent
{
	/**
	 * This is the order that the columns should be displayed in.  
	 * It should reflect the order in the template.
	 * @var array
	 */
	protected $display_order = array('display_short', 'application_id', 'name_first', 'name_last', 'ssn', 'street', 'city', 'state', 'application_status', 'application_balance');
	
	public function Get_Body_Tags()
	{
		return NULL;
	}

	public function Get_Header()
	{
		//the inline "Check_Data" is a hack b/c it's defined in the menu.js script (not needed for search)
		include_once(WWW_DIR . "include_js.php");
		return "
				<script type=\"text/javascript\" src=\"js/layout.js\"></script>
				<script>function Check_Data() { return true; }</script>
				".include_js();
	}
				
	public function Get_Module_HTML()
	{		
		$html = "";
		$include_company = FALSE;

		$multi_company = ECash::getConfig()->MULTI_COMPANY_ENABLED;
				
		if($multi_company === TRUE)
		{
			$html = file_get_contents(CLIENT_VIEW_DIR . "search_all.html");
			$include_company = TRUE;
		}
		else
		{	
			$file = file_exists(CUSTOMER_LIB . "/view/search.html")?CUSTOMER_LIB."/view/search.html":CLIENT_VIEW_DIR."search.html";
			$html =	file_get_contents($file);	   
		}
		
		$data = ECash::getTransport()->Get_Data();
		if(!empty($data->search_message))
		{
			$search_message = $data->search_message;
			$search_results = array();
		}
		elseif(isset($data->search_results))
		{
			$search_message = "Your search returned " . count($data->search_results) . " results.";
			$search_results = $data->search_results;
		}
		else
		{
			$search_message = '';
			$search_results = array();
		}

		//set drops
		$this->Set_Criteria_HTML($data);		
		
		$search_replacements = array('search_message' => $search_message,
									 'search_results' => $this->Build_Search_Results($search_results, $include_company), 
					     			 'view_history' => $this->Build_View_History());
		
		foreach($search_replacements as $replacement_var => $replacement_text)
		{
			$html = str_replace("%%%{$replacement_var}%%%", $replacement_text, $html);
		}

		foreach($data->replacement_vars as $name => $value)
		{
			$html = str_replace("%%%{$name}%%%", $value, $html);
		}

		$html = str_replace("%%%mode_class%%%", $this->mode, $html);
		$html = str_replace("%%%mode%%%", $this->mode, $html);
		$html = str_replace("%%%module%%%", $this->module_name, $html);
		
		//mantis:4926
		if (is_array($this->data->read_only_fields) && in_array("disable_starts_with_ssn", $this->data->read_only_fields))
		{
			$html = str_replace("<select name=\"criteria_type_1\" id=\"criteria_type_1\">", "<select name=\"criteria_type_1\" id=\"criteria_type_1\" onChange=\"javascript:disableContains('1', '1')\">", $html);
			$html = str_replace("<select name=\"criteria_type_2\" id=\"criteria_type_2\">", "<select name=\"criteria_type_2\" id=\"criteria_type_2\" onChange=\"javascript:disableContains('2', '1')\">", $html);
					
		}
		else
		{
			//mantis:4284
			$html = str_replace("<select name=\"criteria_type_1\" id=\"criteria_type_1\">", "<select name=\"criteria_type_1\" id=\"criteria_type_1\" onChange=\"javascript:disableContains('1')\">", $html);
			$html = str_replace("<select name=\"criteria_type_2\" id=\"criteria_type_2\">", "<select name=\"criteria_type_2\" id=\"criteria_type_2\" onChange=\"javascript:disableContains('2')\">", $html);
		}

		return $html;
	}

	private function Build_Search_Results($search_results, $include_company = FALSE)
	{
		//mantis:4416
		if (is_array($this->data->read_only_fields) && in_array("ssn_last_four_digits", $this->data->read_only_fields))
		{
			foreach($search_results as $row)
			{	
				$row->ssn = 'XXXXX' . substr($row->ssn, 5);
			}		
		}

		$results = "<table width=\"100%%\" style=\"height:100%; margin: 0 0 .1px 0;\"><tr><td valign=top><table class=\"search\" width=\"100%%\">\n";
		foreach($search_results as $row)
		{
			// Format data for display
			if( !strpos($row->ssn, "-") ) // Semi ghetto test to not format multiple times.
			{
				$format_list = array(
										"ssn"			=> "social",						      
						    			"phone_cell" 	=> "phone",
						     			"name_first" 	=> "smart_case",
						    			"name_last" 	=> "smart_case",
						     			"state"			=> "upper case",
						     			"street"  		=> "smart_case",
						     			"city"	 		=> "smart_case",
						     			"county"	 	=> "smart_case"
						     		);
									
				$this->data_format->Display_Many($format_list, $row);
			}
			
			$row_class = "";
			if(!empty($row->application_status_short))
			{
				$row_class = "app_status_" . $row->application_status_id;
			}
						
			$results .= "<tr class=\"{$row_class}\" onClick=\"window.location.href='?action=show_applicant&company_id={$row->company_id}&application_id={$row->application_id}'\">\n";
			
			// Assumed to be payday unless otherwise indicated
			$loan_type_abbrev = ($row->loan_type_abbreviation == 'PD') ? '' : '-'.$row->loan_type_abbreviation;
			
			foreach($this->display_order as $column_name)
			{
				$column = $row->$column_name;
				
				$column_class = NULL;
				$column_data = NULL;
				$column = trim($column);
				
				switch($column_name)
				{
					case "display_short":
						$column_class = NULL;						
						if($include_company)
							$column_class = "company_id";
							$column_data = "&nbsp;{$column}{$loan_type_abbrev}";
						break;
						
					case "application_id":
						$column_data = "&nbsp;" . $column;
						$column_class = "app_id";
						break;
					
					case "name_first":
						$column_class = "first_name";
						break;

					case "name_last":
						$column_class = "last_name";
						break;

					case "ssn":
						$column_data = "&nbsp;" . $column;
						$column_class = "ssn";
						break;

					case "street":
						$column_class = "street";
						if(isset($row->unit))
						{
							$column_data = "&nbsp;" . $row->street;
							if( strlen(trim($row->unit)) >= 1)
							{
								$column_data .= ", " . $row->unit;
							}							
						} else {
							$column_data = "&nbsp;" . $column;							
						}
						break;

					case "city":
						$column_class = "city";
						break;

					case "state":
						$column_data = "&nbsp;" . $column;
						$column_class = "state";
						break;

					case "application_status":
						$column_class = "status";
						break;

					case "application_balance":
						$column_class = "amount";
						$column_data = "&nbsp;" . $column;
						break;

					default:
						break;
						
				}

				if($column_data == NULL)
				{
					$column_data = $column;
				}

				//fixes the bug that one big, long word would break the search box [gf mls rc 4635]
				$column_data = preg_replace('/(\S{20})(\S)/','$1 - $2',$column_data);

				if($column_class != null)
					$results .= "<td class=\"{$column_class}\">{$column_data}</td>\n";
			}
			
			$results .= "</tr>\n";
		}
		
		$results .= "</table></td></tr></table>\n";
		return $results;
	}
	
	private function Build_View_History()
	{
		if((isset($_SESSION['view_history'])) && (is_array($_SESSION['view_history'])))
		{
			//style="border:solid thin black;"
			$history = '<table class="view_history" cellpadding=1 cellspacing=0>';
			$history .= "<th colspan=4 class='view_history'>Last Accounts Viewed:</th>\n";
			foreach($_SESSION['view_history'] as $app)
			{
				$first_name = ucwords(strtolower($app['name_first']));
				$last_name  = ucwords(strtolower($app['name_last']));
				
				$history .= '<tr class="view_history">';
				$history .= "<td class='view_history'><a href=\"/?action=show_applicant&company_id={$app['company_id']}&application_id={$app['application_id']}\">{$app['application_id']}</a>&nbsp;&nbsp;</td>";
				$history .= "<td class='view_history'>{$first_name}</td>";
				$history .= "<td class='view_history'>{$last_name}</td>";
				$history .= "</tr>\n";
			}
			$history .= "</table>\n";
			return $history;
		}
		else
		{
			return "";
		}
		
	}
	
	private function Set_Criteria_HTML(&$data)
	{
		require_once(CUSTOMER_LIB.'list_available_criteria_types.php');
		//changed to pizza-style.  A little more cumbersome, but it works w/ "id"
		$dropdowns = array(
			
		new Dropdown(
			array("name" => 'criteria_type_1',
				  "unselected" => FALSE,
				  "attrs" => array("id" => 'AppSearchAllCriteriaType1'),
				  "keyvals" => list_available_criteria_types())),
		new Dropdown(
			array("name" => 'search_deliminator_1',
				  "unselected" => FALSE,
				  "attrs" => array("id" => 'AppSearchAllDeliminator1'),
				  "keyvals" => array('is' => "is",
									 'starts_with' => "starts with",
									 'contains' => "contains"))),

		new Dropdown(
			array("name" => 'criteria_type_2',
				  "unselected" => '',
				  "attrs" => array("id" => 'AppSearchAllCriteriaType2'),
				  "keyvals" => list_available_criteria_types())),

		new Dropdown(
			array("name" => 'search_deliminator_2',
				  "unselected" => FALSE,
				  "attrs" => array("id" => 'AppSearchAllDeliminator2'),
				  "keyvals" => array('is' => "is",
									 'starts_with' => "starts with",
									 'contains' => "contains")))
		);
		$data->replacement_vars = new StdClass();
		
		foreach($dropdowns as $drop)
		{			
			if( !empty($data->search_criteria) && isset($data->search_criteria[$drop->name]) )
			{
				$drop->setSelected($data->search_criteria[$drop->name]);
			}
						
			$drop_name = $drop->name . "_drop";

			$data->replacement_vars->{$drop_name} = $drop->display(TRUE);
		}		
		
		$data->replacement_vars->search_criteria_1 = NULL;
		$data->replacement_vars->search_criteria_2 = NULL;
		$data->replacement_vars->search_option_checked = NULL;
		
		if(!empty($data->search_criteria))
		{
			$data->replacement_vars->search_criteria_1 = $data->search_criteria['search_criteria_1'];
			$data->replacement_vars->search_criteria_2 = $data->search_criteria['search_criteria_2'];
			$data->replacement_vars->search_option_checked = $data->search_criteria['search_option_checked'];
			$sd_1 = $data->search_criteria['search_deliminator_1'];
			$sd_2 = $data->search_criteria['search_deliminator_2'];
		}
		
		// Dynamically creating 'search_deliminator' selects and selecting previously used value
		$drop_options = array('is','starts_with','contains');
		for($x=1;$x<3;$x++)
		{
			$sd_drop_{$x} = "<select name=\"search_deliminator_{$x}\" id=\"AppSearchAllDeliminator{$x}\">";
			foreach($drop_options as $option)
			{
				$val = "sd_$x";
				$select = (isset(${$val}) && ${$val} == $option) ?  "SELECTED" : NULL;
				$option_name = str_replace("_"," ", $option);
				$sd_drop_{$x} .= "<option id=\"{$option}_{$x}\" value=\"{$option}\" {$select}>{$option_name}</option>";
			}
			$sd_drop_{$x} .= "</select>";
			$data_var = "search_deliminator_{$x}_drop";
			$this->data->replacement_vars->{$data_var} = $sd_drop_{$x};
		}
				
		return TRUE;
	}

}

?>
