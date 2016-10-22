<?php

require_once(LIB_DIR. "form.class.php");
require_once("admin_parent.abst.php");
require_once(COMMON_LIB_DIR . "ecash_admin_resources.php");


//ecash module
class Display_View extends Admin_Parent
{
	private $loan_types;
	private $rule_sets;
	private $rule_components;
	private $rule_set_components;
	private $rule_component_parms;
	private $rule_set_component_value;
	private $company_name;
	private $last_rule_component_parm_id;
	private $loan_type_id;
	private $rule_set_id;
	private $rule_conponent_id;
	private $rule_component_parm_id;
	private $rule_component_parm_value;



	public function __construct(ECash_Transport $transport, $module_name)
	{
		parent::__construct($transport, $module_name);
		$returned_data = ECash::getTransport()->Get_Data();

		$this->loan_types = $returned_data->loan_types;
		$this->rule_sets = $returned_data->rule_sets;
		$this->rule_components = $returned_data->rule_components;
		$this->rule_set_components = $returned_data->rule_set_components;
		$this->rule_component_parms = $returned_data->rule_component_parms;
		$this->rule_set_component_value = $returned_data->rule_set_component_values;
		$this->company_name = $returned_data->company_name;

		// get previous info
		if (isset($returned_data->loan_type_id))
		{
			$this->loan_type_id = $returned_data->loan_type_id;
		}
		else
		{
			$this->loan_type_id = 0;
		}

		if (isset($returned_data->rule_set_id))
		{
			$this->rule_set_id = $returned_data->rule_set_id;
		}
		else
		{
			$this->rule_set_id = 0;
		}

		if (isset($returned_data->rule_conponent_id))
		{
			$this->rule_conponent_id = $returned_data->rule_conponent_id;
		}
		else
		{
			$this->rule_conponent_id = 0;
		}

		if (isset($returned_data->rule_component_parm_id))
		{
			$this->rule_component_parm_id = $returned_data->rule_component_parm_id;
		}
		else
		{
			$this->rule_component_parm_id = 0;
		}

		if (isset($returned_data->rule_component_parm_value))
		{
			if (is_numeric($returned_data->rule_component_parm_value))
			{
				$this->rule_component_parm_value = $returned_data->rule_component_parm_value;
			}
			else
			{
				$this->rule_component_parm_value = "'" . $returned_data->rule_component_parm_value . "'";
			}
		}
		else
		{
			$this->rule_component_parm_value = 0;
		}
	}




	/**
	 *
	 */
	public function Get_Header()
	{
		$fields = new stdClass();

		// loan types
		$fields->loan_types .= "[";
		foreach($this->loan_types as $loan_key)
		{
			$fields->loan_types .= "\t\t\n{id:'" . $loan_key->loan_type_id
										. "', name:'" . $loan_key->name . "', status:'"
										. $loan_key->active_status."'},";
		}
		$fields->loan_types .= "]";

		// loan rule set
		$fields->loan_rule_set .= "[";
		foreach($this->rule_sets as $loan_rules_key)
		{
			$fields->loan_rule_set .= "\t\t\n{id:'" . $loan_rules_key->rule_set_id 
														. "', name:'" . $loan_rules_key->name
														. "', loan_type_id:'" . $loan_rules_key->loan_type_id
														. "', date_effective:'" . $loan_rules_key->date_effective
														. "', status:'" . $loan_rules_key->active_status."'},";
		}
		$fields->loan_rule_set .= "]";

		// rule components
		$fields->rule_components = "{";
		foreach($this->rule_components as $key => $value)
		{

			if (trim(strtolower($value->active_status)) == 'active')
			{
				$fields->rule_components .= "\t\t\n{$value->rule_component_id}: {active_status:'" . $value->active_status
															. "', rule_component_id:'" . $value->rule_component_id
															. "', name:'" . $value->name
															. "', grandfathering_enabled:'" . $value->grandfathering_enabled ."'}, ";
			}
		}
		$fields->rule_components .= "}";

		// rule set components
		$fields->rule_component_parms = "[";

		foreach($this->rule_component_parms as $key => $value)
		{
			if (trim(strtolower($value->active_status)) == 'active')
			{
				$fields->rule_component_parms .= "\t\t\n{active_status:'" . $value->active_status
															. "', rule_component_parm_id:'" . $value->rule_component_parm_id
															. "', rule_component_id:'" . $value->rule_component_id
															. "', parm_name:'" . $value->display_name
															. "', parm_subscript:'" . $value->parm_subscript
															. "', sequence_no:'" . $value->sequence_no 
															. "', description:'" . $value->description 
															. "', parm_type:'" . $value->parm_type 
															. "', user_configurable:'" . $value->user_configurable 
															. "', input_type:'" . $value->input_type 
															. "', unit_of_measure:'" . $value->value_label 
															. "', value_min:'" . $value->value_min 
															. "', value_max:'" . $value->value_max 
															. "', value_increment:'" . $value->value_increment 
															. "', length_min:'" . $value->length_min 
															. "', length_max:'" . $value->length_max 
															. "', enum_values:'" . $value->enum_values 
															. "', preg_pattern:'" . $value->preg_pattern
															. "', enum_array:[";


				$enum_array = explode(",", $value->enum_values);
				foreach($enum_array as $enum_key => $enum_value)
				{
					if (trim($enum_value) != '')
					{
						$fields->rule_component_parms .= "{value:'" . trim($enum_value) . "'},";
					}
				}
				$fields->rule_component_parms .= "], increment_array:[";
				$value_min = $value->value_min;
				$value_max = $value->value_max;
				if($value_min >= 0 && ($value_max > $value_min) && ($value->input_type == 'select'))
				{
					$fields->rule_component_parms .= "{value:'" . trim($value_min) . "'},";
					while (($value_min + $value->value_increment) <= $value_max)
					{
						$value_min += $value->value_increment;
						$fields->rule_component_parms .= "{value:'" . trim($value_min) . "'},";
					}
				}
				$fields->rule_component_parms .= "]},";
			}
		}

		$fields->rule_component_parms .= "]";

		// rule set components
		$fields->rule_set_component_value = "[";
		foreach($this->rule_set_component_value as $key => $value)
		{
				$fields->rule_set_component_value .= "\t\t\n{rule_set_id:'" . $value->rule_set_id
															. "', rule_component_id:'" . $value->rule_component_id
															. "', rule_component_parm_id:'" . $value->rule_component_parm_id
															. "', parm_value:'" . $value->parm_value ."'},";
		}
		$fields->rule_set_component_value .= "]";

		// rule set components
		$fields->rule_set_components = "[";
		foreach($this->rule_set_components as $key => $value)
		{
			if (trim(strtolower($value->active_status)) == 'active')
			{
				$fields->rule_set_components .= "\t\t\n{active_status:'" . $value->active_status
															. "', rule_set_id:'" . $value->rule_set_id
															. "', rule_component_id:'" . $value->rule_component_id
															. "', sequence_no:'" . $value->sequence_no ."'},";
			}
		}
		$fields->rule_set_components .= "]";

		$fields->loan_type_id = $this->loan_type_id;
		$fields->rule_set_id = $this->rule_set_id;
		$fields->rule_conponent_id = $this->rule_conponent_id;
		$fields->rule_component_parm_id = $this->rule_component_parm_id;
		$fields->rule_component_parm_val = $this->rule_component_parm_value;
		$fields->minimum_length = 0;
		$fields->maximum_length = 0;




		$js = new Form(ECASH_WWW_DIR.'js/rules.js');

		return parent::Get_Header() . $js->As_String($fields);
	}

	public function Get_Module_HTML()
	{
		$fields = new stdClass();

		foreach($this->loan_types as $loan_key)
		{
			#Filtering menu to only display active loan types - #GF 18182 [NT]
			if($loan_key->active_status == 'active')
			{
			$fields->loan_type_list .= "<option value='" . $loan_key->loan_type_id . "'>" . $loan_key->name . "</option>";
		}
		}

		$fields->company_name = $this->company_name;

		$form = new Form(CLIENT_MODULE_DIR.$this->module_name."/view/rules.html");

		return $form->As_String($fields);
	}
}

?>
