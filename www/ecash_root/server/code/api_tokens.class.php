<?php
require_once(SERVER_CODE_DIR . 'module_interface.iface.php');


/**
 * Defines the ajax function for the tokens admin screen
 */
class API_Tokens implements Module_Interface
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
			case 'tokens':
				switch($input->function)
				{
					case 'getTokens':
						$data = $this->getTokens($input->company_id, $input->loan_type_id);
					break;
					case 'updateToken':
						$data = $this->updateToken($input->token_id, $input->params);
					break;
					case 'addToken':
						$data = $this->addToken($input->params);
					break;
					case 'deleteToken':
						$data = $this->deleteToken($input->token_id);
					break;
					default:
						throw new Exception("Unknown token function {$input->function}");					
					
					
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
		return $model->getColumns();
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
		$componentslist = $rulesCache->Get_Rule_Components();
		$components = array();
		
		foreach($componentslist as $component)
		{
			if($component->active_status == 'active')
				$components[$component->name_short] = $component->name;
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
	/**
	 * updates existing token
	 * 
	 * @param int $token_id
	 * @param array $params
	 * @return int $token_id
	 */
	protected function updateToken($token_id, $params)
	{
		$manager = ECash::getFactory()->getTokenManager();
		
		$token = $manager->getTokenById($token_id);
		//1. Check if Token Type Changed, must create a new token if it did
		//2. Switch on Type to determine setvalue
		if(empty($token) || $token->getType() != $params->type)
		{
			$new_token = $manager->getNewToken($params->type);
			$new_token->setName($params->name);
			$new_token->setCompanyId($params->company_id);
			$new_token->setLoanTypeId($params->loan_type_id);
			$new_token->setId($token_id);
			$token = $new_token;
		}
		
		switch($token->getType())
		{
			case 'static':
				$token->setValue(urldecode($params->value));
			break;
			case 'business_rule':
				$token->setValue($params->component, $params->componentParm);
			break;
			case 'application':
				$token->setValue($params->columnName);
			break;
		}
		if($token->save())
			return array('id' => $token->getId(), 'name' => $token->getName(), 'date_created' => $token->getDateCreated(), 'date_modified' => $token->getDateModified());
		else
			return false;
	}
	/**
	 * creates a token
	 * 
	 * @param array $params
	 * @return int $token_id
	 */
	protected function addToken($params)
	{
		$manager = ECash::getFactory()->getTokenManager();
		$token = $manager->getNewToken($params['type']);
		$token->setName($params['name']);
		$token->setCompanyId($params['company_id']);
		$token->setLoanTypeId($params['loan_type_id']);		
		
		switch($token->getType())
		{
			case 'static':
				$token->setValue(urldecode($params['value']));
			break;
			case 'business_rule':
				$token->setValue($params['component'], $params['componentParm']);
			break;
			case 'application':
				$token->setValue($params['columnName']);
			break;
		}
		if($token->save())
			return $token->getId();
		else
			return false;
	}
	/**
	 * deletes a token
	 * 
	 * @param int $token_id
	 */
	protected function deleteToken($token_id)
	{
		$manager = ECash::getFactory()->getTokenManager();
		
		$token = $manager->getTokenById($token_id);		
		
		return $token->delete();
	}
	/**
	 * retrives the tokens for a given company and loan type
	 * 
	 * @param int $company_id
	 * @param int $loan_type_id
	 * 
	 * @return array $tokenlist
	 */
	protected function getTokens($company_id, $loan_type_id)
	{
		$manager = ECash::getFactory()->getTokenManager();
		if($loan_type_id == 0)
		{
			$tokens = $manager->getTokensByCompanyId($company_id, false);
		}
		else
		{
			$tokens = $manager->getTokensByLoanTypeId($company_id, $loan_type_id, null, false);
		}
		$tokenlist = array();
		foreach($tokens as $token)
		{
			$params = array('name' => $token->getName(), 'company_id' => $token->getCompanyId(), 'loan_type_id' => $token->getLoanTypeId(), 'date_created' => $token->getDateCreated(), 'date_modified' => $token->getDateModified());
			switch($token->getType())
			{
				case 'static':
					$params['type'] = 'static';
					$params['value'] = $token->getValue();
				break;
				case 'business_rule':
					$params['type'] = 'business_rule';
					$params['component'] = $token->getComponent();
					$params['componentParm'] = $token->getComponentParam();
				break;
				case 'application':
					$params['type'] = 'application';
					$params['columnName'] = $token->getColumnName();
				break;
			}			
			$tokenlist[$token->getId()] = $params;
		}
		return $tokenlist;
		
	}
	
	
}

?>
