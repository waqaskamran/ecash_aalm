<?php

require_once (SERVER_CODE_DIR.'module_interface.iface.php');
require_once (LIB_DIR.'business_rules.class.php');

class Rules
{
	private $transport;
	private $request;
	private $last_agent_id;
	private $agent_login_id;
	private $acl;
	private $logged_in_company_id;
	private $agent_id;
	private $biz_rules;

	/**
	 *
	 */
	public function __construct(Server $server, $request)
	{
		$this->logged_in_company_id = $server->company_id;
		$this->transport = ECash::getTransport();
		$this->request = $request;
		$this->agent_login_id = $server->agent_id;
		$this->biz_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());
		$this->acl = ECash::getACL();
		$this->agent_id = $server->agent_id;
	}

	/**
	 *
	 */
	public function Modify_Rule()
	{
		$result = array();

		$loan_type_id = $this->request->loan_type_list;
		$rule_set_id = $this->request->loan_rule_list;
		$rule_component_id = $this->request->rule_component_list;
		$rule_component_parm_id = $this->request->component_parm_list;
		$rule_component_parm_value = "";
		if (isset($this->request->select_component_parm_value))
		{
			$rule_component_parm_value = $this->request->select_component_parm_value;
		}
		else
		{
			$rule_component_parm_value = $this->request->component_parm_value;
		}

		if ($this->biz_rules->All_Fields_Are_Enabled($loan_type_id,
																	$rule_set_id,
																	$rule_component_id,
																	$rule_component_parm_id))
		{
			if ($this->biz_rules->Is_User_Configurable($rule_component_id, $rule_component_parm_id))
			{

				if ($this->biz_rules->Is_Component_Grandfathered($rule_component_id))
				{
					$new_rule_set_id = $this->_Create_New_Rule_Set();

					$data->rule_set_id = $new_rule_set_id;
				}
				else
				{
					$result = $this->_Update_Rule_Set_Value($rule_set_id,
																			$rule_component_id,
																			$rule_component_parm_id,
																			$rule_component_parm_value);

					$data->rule_set_id = $rule_set_id;
				}
			}
			else
			{
				$result = $this->_Fetch_Data();
			}
		}
		else
		{
			$result = $this->_Fetch_Data();
		}

		$data->loan_type_id = $loan_type_id;
		$data->rule_conponent_id = $rule_component_id;
		$data->rule_component_parm_id = $rule_component_parm_id;
		$data->rule_component_parm_value = $rule_component_parm_value;

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Display()
	{
		return $this->_Fetch_Data();
	}

	/**
	 *
	 */
	private function _Create_New_Rule_Set()
	{
		$new_rule_set_id = $this->biz_rules->Create_New_Rule_Set($this->request->loan_type_list, $this->request->loan_rule_list);
		$this->biz_rules->Create_New_Rule_Set_Comp($this->request->loan_rule_list, $new_rule_set_id);
		
		//GForge #18886 - The previous code used $this->request->select_component_parm_value only, resulting in NULL values for text fields
		if (isset($this->request->select_component_parm_value))
		{
			$rule_component_parm_value = $this->request->select_component_parm_value;
		}
		else
		{
			$rule_component_parm_value = $this->request->component_parm_value;
		}
		
		$this->_Update_Rule_Set_Value($new_rule_set_id,
												$this->request->rule_component_list,
												$this->request->component_parm_list,
												$rule_component_parm_value);

		return $new_rule_set_id;
	}


	/**
	 *
	 */
	private function _Update_Rule_Set_Value($rule_set_id, $rule_conponent_id, $rule_component_parm_id, $rule_component_parm_value)
	{
		$this->biz_rules->Update_Rule_Set_Component_Value( $rule_set_id,
																			$rule_conponent_id,
																			$rule_component_parm_id,
																			$rule_component_parm_value,
		$this->agent_id);

		return $this->_Fetch_Data();
	}


	/**
	 *
	 */
	private function _Fetch_Data()
	{
		$companies = ECash::getFactory()->getReferenceList('Company');
		$loan_types = $this->biz_rules->Get_Loan_Types($this->logged_in_company_id);
		$rule_sets = $this->biz_rules->Get_Rule_Sets();
		$rule_components = $this->biz_rules->Get_Rule_Components();
		$rule_set_components = $this->biz_rules->Get_Rule_Set_Components();
		$rule_component_parms = $this->biz_rules->Get_Rule_Component_Params();
		$rule_set_component_values = $this->biz_rules->Get_Rule_Set_Component_Values();

		// get company id
		$company_name = '';
		foreach($companies as $company)
		{
			if ($company->active_status == 'active' &&
				$company->company_id == $this->logged_in_company_id)
			{
				$company_name = $company->name;
				break;
			}
		}

		// sort the rule set
		$sorted_rule_sets = array();
		foreach($loan_types as $loan)
		{
			foreach($rule_sets as $rule)
			{
				if ($rule->loan_type_id == $loan->loan_type_id)
				{
					$sorted_rule_sets[] = $rule;
				}
			}
		}

		// rule set components
		$sorted_rule_set_components = array();
		foreach($rule_set_components as $rule_set_comp_key)
		{
			foreach($sorted_rule_sets as $key => $value)
			{
				if ($rule_set_comp_key->rule_set_id == $value->rule_set_id)
				{
					$sorted_rule_set_components[] = $rule_set_comp_key;
					break;
				}
			}
		}

		// rule components
		$sorted_rule_components = array();
		foreach ($rule_components as $rule_comp_key)
		{
			foreach($sorted_rule_set_components as $rule_set_key => $rule_set_value)
			{
				if ($rule_comp_key->rule_component_id == $rule_set_value->rule_component_id)
				{
					$sorted_rule_components[] = $rule_comp_key;
					break;
				}
			}
		}

		$sorted_rule_set_component_values = array();
		foreach($rule_set_component_values as $rule_set_comp_value_key)
		{
			foreach($sorted_rule_sets as $rule_set_key => $rule_set_value)
			{
				if ($rule_set_comp_value_key->rule_set_id == $rule_set_value->rule_set_id)
				{
					$sorted_rule_set_component_values[] = $rule_set_comp_value_key;
					break;
				}
			}
		}

		$sorted_rule_component_parms = array();
		foreach($rule_component_parms as $rule_component_parm_key)
		{
			foreach($sorted_rule_components as $key => $value)
			{
				if($value->rule_component_id == $rule_component_parm_key->rule_component_id)
				{
					$sorted_rule_component_parms[] = $rule_component_parm_key;
					break;
				}
			}
		}

		// set the data
		$data = new StdClass();
		$data->company_name = $company_name;
		$data->loan_types = $loan_types;
		$data->rule_sets = $sorted_rule_sets;
		$data->rule_components = $sorted_rule_components;
		$data->rule_set_components = $sorted_rule_set_components;
		$data->rule_component_parms = $sorted_rule_component_parms;
		$data->rule_set_component_values = $sorted_rule_set_component_values;

		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}
}

?>
