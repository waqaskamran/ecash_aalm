<?php

require_once(LIB_DIR. "form.class.php");
require_once("admin_parent.abst.php");
require_once(COMMON_LIB_DIR . "ecash_admin_resources.php");
require_once(LIB_DIR. "Decision_Rules.class.php");

//ecash module
class Display_View extends Admin_Parent
{
	private $rules;
	private $blackbox;
	private $company_short;
	private $log;
	
	public function __construct(ECash_Transport $transport, $module_name)
	{
		parent::__construct($transport, $module_name);
		
		$this->log           = ECash::getLog();
		$this->company_short = ECash::getCompany()->getModel()->name_short;
		$enterprise          = getenv('ECASH_CUSTOMER');
		$url                 = BLACKBOX_ADMIN_QUERY_URL;
		
		$this->blackbox = new Decision_Rules($enterprise, $url);
	}

	public function Get_Module_HTML()
	{
		$fields = new stdClass();

		try
		{
			$rules = $this->blackbox->getAll($this->company_short);
			
			$fields->rule_set_options = $this->getRuleSetOptions($rules);
			$fields->rules_javascript = $this->getJavascript($rules);
			$fields->rule_error       = '';
		}
		catch(Exception $e)
		{
			$this->log->Write("Error Connecting To Decisioning Rule API. Error Message: ".$e->getMessage());
			$fields->rule_error       = "Could not connect to Decisioning Rules server.";
			$fields->rule_set_options = '';
			$fields->rules_javascript = '';
		}
		
		$form = new Form(CLIENT_MODULE_DIR.$this->module_name."/view/decision_rules.html");
		
		return $form->As_String($fields);
	}
	
	private function getRuleSetOptions($rules)
	{
		$rule_set_options = array();
		
		if(!empty($rules['company']))
		{
			$rule_set_options[] = "<option value='company'>Company Rules</option>";
		}
		
		if(!empty($rules['suppression_list']))
		{
			$rule_set_options[] = "<option value='suppression_list'>Suppression List</option>";
		}
		
		foreach($rules['campaigns'] as $campaign)
		{
			$name = $campaign['name'];
			$id   = $campaign['property_short'];
			
			$rule_set_options[] = "<option value='{$id}'>({$id}) {$name}</option>";
		}
		
		return implode("\n", $rule_set_options);
	}
	
	private function getJavascript($rules)
	{
		$javascript = '';
		
		//Company Rules
		if(!empty($rules['company']))
		{
			$counter     = 0;
			$javascript .= "rules['company'] = new Array();\n\n";
			
			foreach($rules['company'] as $rule)
			{
				$javascript .= $this->getRuleJs('company', $counter, $rule);
				
				$counter++;
			}
		}
		
		//Campaign Rules
		if(!empty($rules['campaigns']))
		{
			foreach($rules['campaigns'] as $campaign)
			{
				$counter 	    = 0;
				$campaign_short = $campaign['property_short'];
				$javascript    .= "rules['{$campaign_short}'] = new Array();\n\n";
				
				foreach($campaign['rules'] as $rule)
				{
					$javascript .= $this->getRuleJs($campaign_short, $counter, $rule);	
					$counter++;
				}
			}
		}
		
		//Suppression List
		if(!empty($rules['suppression_list']))
		{
			$counter     = 0;
			$javascript .= "rules['suppression_list'] = new Array();\n\n";

			foreach($rules['suppression_list'] as $rule)
			{
				$javascript .= $this->getRuleJs('suppression_list', $counter, $rule);
				$counter++;	
			}
		}
		return $javascript;
	}
	
	private function getRuleJs($rule_set, $rule_number, $rule)
	{
		if($rule_set != "suppression_list")
			$prefix = "rule_";
		else
			$prefix = "";
		
		//Because OLP sometimes returns string dates, and other times timestamps,
		//we need to figure out which one we're dealing with.
		if(!empty($rule['date_created']))
		{
			$timestamp    = strtotime($rule['date_created']);
			if(empty($timestamp))
				$timestamp = $rule['date_created'];
			
			$date_created = date("m-d-Y h:ia", $timestamp);
		}
		else
		{
			$date_created = NULL;
		}
		
		$javascript = "";
		
		$javascript .= "var rule_temp = new Array();\n";
		 
		$javascript .= "rule_temp['name'] = '". $rule[$prefix.'name'] ."';\n";
		$javascript .= "rule_temp['description'] = '". $rule[$prefix.'description'] ."';\n";
		
		//Switch the value to a string if it's an array
		$rule_value = $this->getRuleValue(empty($rule['value']) ? $rule['values'] : $rule['value']);
		$rule_modes = '';
		if(!empty($rule['modes']))
			$rule_modes = implode(",", (array)$rule['modes']);
		
		$javascript .= "rule_temp['value'] = '". $rule_value ."';\n";
		$javascript .= "rule_temp['date_modified'] = '". $date_created ."';\n";
		$javascript .= "rule_temp['mode'] = '". $rule_modes ."';\n";
		
		$javascript .= "rules['{$rule_set}'][{$rule_number}] = rule_temp;\n";
		
		$javascript .= "rule_temp = null;\n\n";	

		return $javascript;
	}
	
	private function getRuleValue($value)
	{
		if(is_array($value))
		{
			$return = '';
			
			foreach($value as $key=>$val)
			{
				if(!is_numeric($key))
					$return .= "{$key}: ";
				
				$return .= "{$val}\\n";
			}
		}
		else
		{
			$return = $value;
		}

		return trim($return, "\\n");
	}
	
}

?>
