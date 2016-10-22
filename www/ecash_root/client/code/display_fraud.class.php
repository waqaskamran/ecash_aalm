<?php

require_once(CLIENT_CODE_DIR . 'FraudColumns.php');
require_once(LIB_DIR. 'form.class.php');
require_once('display_parent.abst.php');


class Display_View extends Display_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{
		parent::__construct($transport, $module_name, $_SESSION['fraud_mode']);
		$this->opt_compare 	= array("EQUALS" => "equals",
									//"STARTS" => "starts with", //these are disabled untile further notice -- JRF
									//"ENDS" => "ends with",
									//"CONTAINS" => "contains"
									);
	}


	public function Get_Header()
	{
		include_once(WWW_DIR . "include_js.php");
		return "<link rel=\"stylesheet\" href=\"css/transactions.css\">
		        <link rel=\"stylesheet\" href=\"js/calendar/calendar-dp.css\">
               " . include_js();
	}

	public function Get_Body_Tags()
	{
		return "";
	}

	public function Get_Module_HTML()
	{
		$return_val = "";
		$data = ECash::getTransport()->Get_Data();
		//echo "<!-- FRAUD_MOD ", print_r($data, TRUE), " -->";
		
		switch($this->mode)
		{
			case "fraud_rules":
				$data->rule_extension_limit = $data->business_rules['Fraud Rule Extension Limit'];
				$return_val = $this->Prepare_Data($data, "Fraud Rules", "+{$data->business_rules['New Fraud Rule Expiration']} days");
				break;
				
			case "high_risk_rules":	
				$data->rule_extension_limit = $data->business_rules['High Risk Rule Extension Limit'];			
				$return_val = $this->Prepare_Data($data, "High Risk Rules", "+{$data->business_rules['New High Risk Rule Expiration']} days");
				break;
		}

		return $return_val;
	}

	private function Get_Option_Form($arritems, $for_js = TRUE, $selected_key = NULL)
	{
		$eol = "\\n";
		if(!$for_js)
			$eol = "\n";
		
		$option_str = "";
		foreach ($arritems as $item_key => $item )
		{
			$selected = "";
			if($item_key == $selected_key)
			{
				$selected = " selected";
			}
			$option_str .= "<option value='{$item_key}'{$selected}>{$item}</option>{$eol}";
		}
		
		return $option_str;
	}

	private function Prepare_Data(&$data, $title, $default_date)
	{
		//set some sane defaults
		$data->rule_name_value 	= '';
		$data->rule_notes_value	= '';
		$data->rule_date_value	= '';
		$data->created_agent = '';
		$date->modified_agent = '';
		$data->active_checked = '';
		$data->confirmed = '';
		$data->confirm_text = 'Confirm';
		$data->confirm_button = 0;
		$data->prop_date_created = '';
		$data->prop_id = '(New)';
		$data->prop_agent = '';
		$data->prop_question = '';
		$data->prop_description = '';
		$data->prop_quantify = '';
		$data->prop_file_name = '';
		$data->prop_file_size = 0;
		$data->prop_file_type = '';
		$data->preview_confirm = 'false';
		$data->preview_unconfirm = 'false';
		
		//get any neccessary permissions
		$current_section_id = $this->Get_Section_ID(); //will be fraud_rules or high_risk_rules
		if($this->Get_Section_ID_by_Name($current_section_id, 'confirm') != -1)
		{
			$data->confirm_button = 1;
		}		
		
		$data->rule_title = $title;
		$data->select_fields	= $this->Get_Option_Form($this->opt_compare);
		$data->select_compares	= $this->Get_Option_Form(FraudColumns::$columns);
		$date->fraud_mode 		= $_SESSION['fraud_mode'];
		//for new rules
		$data->default_rule_date_value	= date("m/d/Y",strtotime($default_date));
		$data->fraud_rule_id_loaded = empty($data->fraud_rule_loaded) ? '' : $data->fraud_rule_loaded->getFraudRuleID();
		if($data->fraud_rule_id_loaded)
		{
			$dateval = $data->fraud_rule_loaded->ExpDate;
			$data->rule_name_value 	= $data->fraud_rule_loaded->Name;
			$data->rule_notes_value	= $data->fraud_rule_loaded->Comments;
			$data->created_agent = $data->fraud_rule_loaded->CreatedAgentName . " at " . date('g:i:s A D M. jS, Y', strtotime($data->fraud_rule_loaded->DateCreated));
			$data->modified_agent = $data->fraud_rule_loaded->ModifiedAgentName . " at " . date('g:i:s A D M. jS, Y', strtotime($data->fraud_rule_loaded->DateModified));
			$data->confirmed = $data->fraud_rule_loaded->IsConfirmed ? 'Yes' : 'No';
			if($data->fraud_rule_loaded->IsConfirmed)
			{
				$data->confirm_text = 'Unconfirm';
			}

			if($proposition = $data->fraud_rule_loaded->Proposition)
			{
				$data->prop_date_created = $proposition->DateCreated;
				$data->prop_id = $proposition->PropositionID;
				$data->prop_agent = $proposition->AgentName;
				$data->prop_question = $proposition->Question;
				$data->prop_description = $proposition->Description;
				$data->prop_quantify = $proposition->Quantify;
				$data->prop_file_name = $proposition->FileName;
				$data->prop_file_size = $proposition->FileSize;
				$data->prop_file_type = $proposition->FileType;
			}
			
			$data->rule_date_value	= date("m/d/Y",strtotime($dateval));
			$data->active_checked = $data->fraud_rule_loaded->IsActive ? 'checked' : '';
					
			// Show the Rules 
			$conds = $data->fraud_rule_loaded->getConditions();
			$count = 1;
			foreach ($conds as $cond)
			{				
				$field_name 			= $cond->FieldName;
				$field_comp		 		= $cond->FieldComparison;
				$field_value 			= $cond->FieldValue;
				
				if($field_name == 'ssn')
				{
					$field_value = substr($field_value,0,3) . '-' . substr($field_value,3,2) . '-' . substr($field_value,5);
				}
				
				if( strstr($field_name,'phone'))
				{
					$field_value = '(' . substr($field_value,0,3) . ') ' . substr($field_value,3,3) . '-' . substr($field_value,6);
								
				}
				if($field_name == 'zip_code' && strlen($field_value) > 5)
				{
					$field_value = substr($field_value,0,5) . '-' . substr($field_value,5);
				}
							
				$data->condition_view  .= "<tr><td class=\"align_left\">".FraudColumns::$columns[$field_name]."</td><td class=\"align_left_bold\">{$this->opt_compare[$field_comp]}</td><td class=\"align_left\">{$field_value}</td>";
				if($count < count($conds))
					$data->condition_view .= '<td class="align_left_bold">AND</td></tr>';
				else
					$data->condition_view .= '<td></td></tr>';
				$count++;
			}
			
		}
		else if(!empty($data->affected_count) && $data->affected_count['preview']) //preview results
		{
			$data->frm_rule_action = 'preview_results';
			$data->preview_confirm = $data->affected_count['confirm'] ? 'true' : 'false';
			$data->preview_unconfirm = $data->affected_count['unconfirm'] ? 'true' : 'false';
			$data->affected_results = $this->Format_Affected_App_Count($data->affected_count, TRUE);
		}
		else if(!empty($data->affected_count)) //show results of a save
		{
			$data->frm_rule_action = 'show_results';
			$data->affected_results = $this->Format_Affected_App_Count($data->affected_count, FALSE);
		}
		else if(!empty($data->proposition_saved)) //show results of a save
		{
			$data->frm_rule_action = 'show_results';
			$data->affected_results = "<b>Proposition saved.</b>";
		}

		//remove any newlines so javascript can handle it
		if (isset($data->condition_view)) $data->condition_view = preg_replace("/\\r\\n|\\n|\\r/", "", $data->condition_view);
		
		$data->rule_select_list = $this->Get_Option_Form($data->fraud_rules, FALSE, $data->fraud_rule_id_loaded);
		//echo "<!-- ", print_r($data, TRUE), " -->";
		$this->Format_Filter(&$data);
		$form = new Form(CLIENT_MODULE_DIR.$this->module_name."/view/rules_view.html");
		return $form->As_String($data);
	}

	private function Format_Affected_App_Count($net_effect, $preview)
	{
		//echo '<!-- ', print_r($net_effect, TRUE), ' -->';
		$title = 'Results';
		$prefix = '';
		if($preview)
		{
			$title = 'Preview ' . $title;
			$prefix = 'will be ';
		}
		
		$results = "<table><tr><td colspan=\"2\"><b>{$title}:</b></td></tr>";

		$table_rows = '';
		
		foreach($net_effect as $index => $company_array)
		{
			if(is_array($company_array))
			{
				$row_title_set = FALSE;
				foreach($company_array as $display_short => $count)
				{
					if($index == 'fraud_apps_confirmed')
						$row_title = "Fraudulent applications {$prefix}confirmed:";
					if($index == 'fraud_apps_unconfirmed')
						$row_title = "Fraud applications {$prefix}no longer confirmed:";
					if($index == 'fraud_apps_added')
						$row_title = "Applications {$prefix}moved to fraud queue:";
					if($index == 'fraud_apps_removed')
						$row_title = "Applications {$prefix}removed from fraud queue:";
					if($index == 'risk_apps_added')
						$row_title = "Applications {$prefix}moved to high risk queue:";
					if($index == 'risk_apps_removed')
						$row_title = "Applications {$prefix}removed from high risk queue:";

					if($row_title_set)
					{
						$table_rows .= "<tr><td></td><td class=\"align_left\">{$count} from {$display_short}</td></tr>";
					}
					else
					{
						$table_rows .= "<tr><td class=\"align_left\">{$row_title}</td><td class=\"align_left\">{$count} from {$display_short}</td></tr>";
						$row_title_set = TRUE;
					}
				}
			}
		}
		
		if(empty($table_rows))
			$table_rows = "<tr><td>No applications {$prefix}moved</td></tr>";
				
		$results .= "{$table_rows}</table>";

		if($preview)
		{			
			$results .= '<center><input id="save_preview_btn" name="agent_action" type="button" value="Save" onClick="javascript:savePreview();">';
			$results .= '&nbsp;<input id="cancel_preview_btn" name="agent_action" type="button" value="Cancel" onClick="javascript:cancelPreview();"></center>';
		}

		return $results;
	}

	private function Format_Filter(&$data)
	{
		//echo "<!-- {$data->filter_confirmed} -->";
		$data->filter_label = "Off";
		if(is_numeric($data->filter_active) || is_numeric($data->filter_confirmed))
		{
			$data->filter_label = "On";
		}

		$active_checked = $data->filter_active ? ' checked' : '';
		$inactive_checked = ($data->filter_active === '0') ? ' checked' : '';
		$active_both_checked = ($data->filter_active === NULL) ? ' checked' : '';

		$data->filter_rows = "<tr>
										<td class=\"align_left\"><input type=\"radio\" name=\"active\" value=\"1\"{$active_checked}></td>
										<td class=\"align_left\">Active</td>
										<td class=\"align_left\"><input type=\"radio\" name=\"active\" value=\"0\"{$inactive_checked}></td>
										<td class=\"align_left\">Inactive</td>
										<td class=\"align_left\"><input type=\"radio\" name=\"active\" value=\"\"{$active_both_checked}></td>
										<td class=\"align_left\">Both</td>
									</tr>";
		
		$data->legend_html = "*&nbsp;=&nbsp;Inactive<br>";

		if($_SESSION['fraud_mode'] == 'fraud_rules')
		{
			$confirmed_checked = $data->filter_confirmed ? ' checked' : '';
			$unconfirmed_checked = ($data->filter_confirmed === '0') ? ' checked' : '';
			$confirmed_both_checked = ($data->filter_confirmed === NULL) ? ' checked' : '';
			
			$data->filter_rows .= "<tr>
										<td class=\"align_left\"><input type=\"radio\" name=\"confirmed\" value=\"1\"{$confirmed_checked}></td>
										<td class=\"align_left\">Confirmed</td>
										<td class=\"align_left\"><input type=\"radio\" name=\"confirmed\" value=\"0\"{$unconfirmed_checked}></td>
										<td class=\"align_left\">Unconfirmed</td>
										<td class=\"align_left\"><input type=\"radio\" name=\"confirmed\" value=\"\"{$confirmed_both_checked}></td>
										<td>Both</td>
									</tr>";

			$data->legend_html .= "
				(c)&nbsp;=&nbsp;Confirmed<br>
				(c)*&nbsp;=&nbsp;Not&nbsp;Fraud";
		}
			
	}

	private function Get_Confirm_Button($section_id)
	{
		$result = '';

		// does personal link appear
		foreach($this->data->all_sections as $key => $value)
		{
			if ($section_id == $value->section_parent_id
					&& $value->name == 'confirm')
			{
				$result = '<a href="#" onClick="SetDisplay(0,0,1,\'view\', \''
								. $this->mode
								. '_buttons\');"><nobr>[ID and Credit]</nobr></a>';
				break;
			}
		}

		return $result;
	}
	

}

?>
