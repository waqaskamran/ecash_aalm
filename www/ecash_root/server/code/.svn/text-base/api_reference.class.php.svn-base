<?php
require_once(SERVER_CODE_DIR . 'module_interface.iface.php');


/**
 * Defines the ajax function for reference data for admin screens
 */
class API_Reference implements Module_Interface
{
	public function __construct(Server $server, $request, $module_name) 
	{
		$this->request = $request;
		$this->server = $server;
		$this->name = $module_name;
		$this->permissions = array(array('admin'));
	
	}
	public function get_permissions()
	{
		return $this->permissions; 
	}
	public function Main() 
	{

		$input = $this->request->params[0];
		switch ($input->action)
		{
			case 'reference':
				switch($input->function)
				{
					case 'getCompanies':
						$data = $this->getCompanies();
					break;
					case 'getLoanTypesByCompany':
						$data = $this->getLoanTypesByCompany($input->company_id);
					break;
					case 'getApplicationColumns':
						$data = $this->getApplicationColumns();
					break;
					case 'getRuleComponents':
						$data = $this->getRuleComponents($input->loan_type_id);
					break;
					case 'getRuleParams':
						$data = $this->getRuleParams($input->component_id, $input->loan_type_id);
					break;
					default:
						throw new Exception("Unknown reference function {$input->function}");					
					
					
				}			
			
			break;

			default:
				throw new Exception("Unknown action {$input->action}");		
		}
		return $data;
	}	
	/**
	 * retrieves the current active companies
	 * 
	 * @return array $companies
	 */
	protected function getCompanies()
	{
		$model = ECash::getFactory()->getModel('CompanyList');
		$model->getCFECompanies();
		$companies = array();
		foreach($model as $company)
		{
			if($company->active_status == 'active')
				$companies[$company->company_id] = $company->name;
		}
		return $companies;
	}
	/**
	 * retrives the columns on an application
	 * 
	 * @return array $columns
	 */
	protected function getApplicationColumns()
	{
		$model = ECash::getFactory()->getModel('Application');
		$list = $model->getColumns();
		sort($list);
		return $list;
	}
	/**
	 * retrieves the current active componenets
	 * 
	 * @return array $componenets
	 */
	protected function getRuleComponents($loan_type_id)
	{
		$rulesCache = new ECash_BusinessRulesCache(ECash::getSlaveDb());
		$current_rule_set_id = $rulesCache->Get_Current_Rule_Set_Id($loan_type_id);
		$rules = $rulesCache->Get_Rule_Set_Tree($current_rule_set_id);
		$components = array();
		
		foreach($rules as $component_name => $parm_name)
		{
				$components[$component_name] =  $component_name;
		}
		return $components;
	}
	/**
	 * retrieves the Params for a given component
	 * 
	 * @param int $rule_component_id
	 * @return array $params
	 */
	protected function getRuleParams($rule_component, $loan_type_id)
	{
		$rulesCache = new ECash_BusinessRulesCache(ECash::getSlaveDb());
		$current_rule_set_id = $rulesCache->Get_Current_Rule_Set_Id($loan_type_id);
		$componentslist = $rulesCache->Get_Rule_Components();
		$components = array();
		
		foreach($componentslist as $component)
		{
			if($component->active_status == 'active' && $component->name_short == $rule_component)
				$rule_component_id = $component->rule_component_id;
		}
		$paramslist = $rulesCache->Get_Rule_Component_Params();
		$params = array();
		foreach($paramslist as $param)
		{
			if($param->active_status == 'active' && $param->rule_component_id == $rule_component_id)
				$params[$param->parm_name] = $param->display_name;
		}
		return $params;
	}
	/**
	 * retrieves the loan types for a company
	 * 
	 * @param int $company_id
	 * @return array $loan_types
	 */
	protected function getLoanTypesByCompany($company_id)
	{
		$model = ECash::getFactory()->getModel('LoanTypeList');
		$model->loadBy(array('active_status' => 'active', 'company_id' => $company_id));
		$loan_types = array();
		foreach($model as $loan_type)
		{
			if(!empty($loan_type->abbreviation))
				$loan_types[$loan_type->loan_type_id] = $loan_type->name;
		}
		return $loan_types;
	}

}

?>
