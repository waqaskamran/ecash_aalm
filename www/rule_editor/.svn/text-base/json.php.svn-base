<?php

define('ECASH_WWW_DIR', getenv('ECASH_WWW_DIR'));
require_once(ECASH_WWW_DIR . 'config.php');

//require_once('/virtualhosts/ecash_cfe/www/config.php'); //Change this to the config of your ecash instance
error_reporting(E_ALL);
define('ECASH_COMMON', '/virtualhosts/ecash_common.cfe/code/');

if(!defined('ECASH_COMMON_DIR')) define('ECASH_COMMON_DIR', '/virtualhosts/ecash_common.cfe/code/');

require_once('libolution/DB/Models/IterativeModel.1.php');
require_once(ECASH_COMMON_CODE_DIR . 'ECash/Models/IterativeModel.php');
require_once(ECASH_COMMON_CODE_DIR . 'ECash/CFE/API/CFE.php');
require_once(ECASH_COMMON_CODE_DIR . 'ECash/Models/LoanType.php');
require_once(ECASH_COMMON_CODE_DIR . 'ECash/Models/LoanTypeList.php');
require_once(ECASH_COMMON_CODE_DIR . 'ECash/CFE/API/RuleSetList.php');
require_once(ECASH_COMMON_CODE_DIR . 'ECash/CFE/API/RuleSetDef.php');
require_once(ECASH_COMMON_CODE_DIR . 'ECash/CFE/API/RuleDef.php');
require_once(ECASH_COMMON_CODE_DIR . 'ECash/CFE/API/EventDef.php');
require_once(ECASH_COMMON_CODE_DIR . 'ECash/CFE/API/ConditionDef.php');
require_once(ECASH_COMMON_CODE_DIR . 'ECash/CFE/API/ActionTypeDef.php');
require_once(ECASH_COMMON_CODE_DIR . 'ECash/CFE/IAction.php');
require_once(ECASH_COMMON_CODE_DIR . 'ECash/CFE/API/DefinedActionDef.php');
require_once(ECASH_COMMON_CODE_DIR . 'ECash/CFE/API/ActionDef.php');
require_once(ECASH_COMMON_CODE_DIR . 'ECash/CFE/API/VariableDef.php');
require_once(ECASH_COMMON_CODE_DIR . 'ECash/CFE/Base/BaseAction.php');

include_once('services_json.php');

/**
 *A web service helper class, should only contain functions you want exposed to public access
 */
class ConfigJsonService
{
	protected $json;
	protected $cfe;

	public function __construct()
	{
		$this->json = new Services_JSON();
		$this->cfe = new ECash_CFE_API();
	}
	
	public function run()
	{	
		try
		{
			$result = call_user_func_array(array($this,$this->getAction()), $this->getParamArray());
			echo $this->json->encode($result);
		}
		catch(Exception $e)
		{
			echo $e;
			exit;
		}
	}
	
	protected function getAction()
	{
		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;
		
		if (false === $action)
		{
			throw new Exception('Action Not Sent');
		}
		
		if (false === method_exists($this,$action))
		{
			throw new Exception('Invalid Action');
		}
		
		return $action;
	}
	
	protected function getParamArray()
	{
		$param_array = isset($_REQUEST['params']) ? $_REQUEST['params'] : array();
		
		if (false === is_array($param_array))
		{
			$param_array = explode(',', $param_array);
			
			for($i = 0; $i < count($param_array); ++$i)
			{
				$param_array[$i] = urldecode($param_array[$i]);
			}
		}
		
		return $param_array;
	}
	
	public function getApplicationStatuses()
	{
		$model = ECash::getFactory()->getModel('ApplicationStatusList');
		$status_array = $model->getTree();
		//var_dump("<pre>",$status_array);
		return $status_array;
	}
	
	public function getCompanies()
	{
		$company_array = $this->cfe->fetchCompanies();
		$companies = array();
		foreach ($company_array as $id => $company) 
		{
			$companies[] = array(
				'text'		=> $company->name,
				'company_id' => $company->company_id,
				'name_short' => $company->name_short,
				'leaf'		=> true,
				'name' =>	$company->name);
		}
		return $companies;	
	}
	
	public function getLoanTypeAll($company_id=null)
	{
		$lt_array = $this->cfe->fetchAllLoanTypes($company_id);

		$list = array();
		foreach($lt_array as $loan_type)
		{
			$list[] = array
			(
				'date_modified'	=> $loan_type->date_modified,
				'date_created'	=> $loan_type->date_created,
				'active_status'	=> $loan_type->active_status,
				'company_id'	=> $loan_type->company_id,
				'loan_type_id'	=> $loan_type->loan_type_id,
				'name'			=> $loan_type->name,
				'name_short'	=> $loan_type->name_short
			);
		}

		return $list;
	}
	
	public function getLoanTypesForCopy()
	{
		$loan_types = $this->cfe->fetchAllLoanTypes();
		$retval = array();
		foreach($loan_types as $loan_type)
		{
			try
			{
				$this->cfe->fetchActiveRuleset($loan_type->name_short, $loan_type->company_id);
				$company = $this->cfe->fetchCompany($loan_type->company_id);
			
				$retval[] = array($loan_type->loan_type_id, 
					"(".$company->name_short.") ".$loan_type->name." - ".date('Y-m-d H:i',strtotime($loan_type->date_modified)));
			
			}
			catch(Exception $e)
			{
				
			}
		}
		return $retval;
	}
	
	public function getLoanTypeForTree($company_id = null)
	{

		$node = @$_REQUEST['node'];
		$list = array();
		
		if('0' === $node)
		{
			foreach($this->getLoanTypeAll($company_id) as $loan_type)
			{
				try
				{
					$rule_set = $this->cfe->fetchActiveRuleset($loan_type['name_short'],$company_id);
					
					$loan_type['id'] = $loan_type['name_short'];
					$loan_type['text'] = $loan_type['name'] .' ('. date('m-d-y', strtotime($rule_set->date_created)) .')';
					$loan_type['rule_set_id'] = $rule_set->cfe_rule_set_id;
					$list[] = $loan_type;
				}
				catch(Exception $e)
				{
				//	echo 'Error with ', $loan_type['name_short'], " skipping\n";
				}
			}
		}
		else
		{
			foreach($this->cfe->fetchAllRulesets($node) as $rule_set)
			{
				if ('inactive' == $rule_set->active_status)
				{
					$list[] = array
					(
						'text' => date('m-d-y', strtotime($rule_set->date_created)),
						'id' => $node, 
						'date_modified' => $rule_set->date_modified, 
						'date_created' => $rule_set->date_created, 
						'active_status' => $rule_set->active_status,
						'rule_set_id' => $rule_set->cfe_rule_set_id, 
						'name' => $rule_set->name, 
						'loan_type_id' => $rule_set->loan_type_id, 
						'date_effective' => $rule_set->date_effective,
						'leaf' => true
					);
				}
			}
		}
		return $list;
	}
		
	
	public function getLoanType($short_name, $rule_set_id)
	{
		$event_array = $this->cfe->getAvailableEvents();
		$event_array_map = array();
		foreach($event_array as $event)
		{
			$event_array_map[$event->cfe_event_id] = $event;
		}

		$loan_type = $this->cfe->fetchLoanType($short_name);

		$rule_set = $this->cfe->fetchRuleset($rule_set_id);

		$rule_array = array();

		if (false != $rule_set)
		{
			$rule_models = $this->cfe->fetchAllRules($rule_set->cfe_rule_set_id);
			
			foreach($rule_models as $rule)
			{
				// Conditions
				$condition_models = $this->cfe->fetchAllConditions($rule->cfe_rule_id);
				$condition_array = array();
				foreach($condition_models as $condition)
				{
					$condition_array[] = array
					(
						'operand1'		=> $condition->operand1, 
						'operand1_type'	=> $condition->operand1_type, 
						'operand2'		=> $condition->operand2,
						'operand2_type'	=> $condition->operand2_type,
						'operator'		=> $condition->operator,
						'sequence_no'	=> $condition->sequence_no,
					);
				}
	
				// Actions
				$action_models = $this->cfe->fetchAllActions($rule->cfe_rule_id);
				$action_array = array();
				$else_action_array = array();
				foreach($action_models as $action)
				{
					$new_action = array
					(
						'action'		=> $action->cfe_action_id,
						'params'		=> unserialize($action->params),
						'sequence_no'	=> $action->sequence_no
					);
					if($action->rule_action_type == ECash_CFE_API_ActionDef::TYPE_EXECUTE_ON_SUCCESS)
					{
						$action_array[] = $new_action;
					}
					else
					{
						$else_action_array[] = $new_action;
					}
				}
	
				//Rules
				$rule_array[] = array
				(
					'name' => $rule->name,
					'event' => @$event_array_map[$rule->cfe_event_id]->short_name,
					'conditions' => $condition_array,
					'actions' => $action_array,
					'else_actions' => $else_action_array,
					'salience' => $rule->salience
				);
			}
		
		}

		$return_array =  array
		(
			'name' => $loan_type->name,
			'short_name' => $loan_type->name_short,
			'created' => $rule_set->date_created,
			'created_by' => '',
			'id' => $loan_type->loan_type_id,
			'unique_id' => $loan_type->name_short. '-'. $rule_set_id,
			'min_age' => null,
			'max_loans' => null,
			'states' => array(),
			'rule_set_id' => $rule_set_id,
			'rules' => $rule_array
		);

		return array('rows' => array($return_array));
	}
	
	public function getBusinessRuleConfig()
	{
		$event_array = array();
		foreach($this->cfe->getAvailableEvents() as $event)
		{
			$event_array[$event->short_name] = array('key' => $event->short_name, 'name' => $event->name);
		}

		$action_array = array();
		foreach($this->cfe->getAvailableActions() as $action)
		{
			/* @var $action ECash_CFE_API_DefinedActionDef */
			$param_array = array();
			foreach($action->getParams() as $param)
			{
				$param_array[] = array('name' => $param->name, 'type' => $param->type, 'reference_data' => $action->getReferenceData($param->name));
			}
			$action_array[$action->cfe_action_id] = array('name' => $action->name, 'params' => $param_array, 'is_ecash_only' => $action->getIsEcashOnly());
		}

		$variable_array = array();
		foreach($this->cfe->getAvailableVariables() as $variable)
		{
			$variable_array[$variable->name_short] = array('name' => $variable->name, 'type' => $variable->type);
		}
		
		$application_statuses = $this->getApplicationStatuses();

		return array
		(
			'events' => $event_array,
			'variables' => $variable_array,
			'actions' => $action_array,
			'application_statuses' => $application_statuses,
		);
	}
	
	/**
	 * Creates a copy of a loan_type
	 *
	 * @param int $loan_type_id - The loan_type_id of the loan_type you want to create a copy of
	 * @param String $loan_name - The name of your loan type copy.
	 * @param int $company_id - The company_id of the company you want to create the copy for
	 */
	public function copyLoanType($loan_type_id,$loan_name,$company_id)
	{
		$this->cfe->copyLoanType($loan_type_id,$loan_name,$company_id);
		
	}
	
	public function saveLoanType()
	{
		$event_array = $this->cfe->getAvailableEvents();
		$event_array_map = array();
		foreach($event_array as $event)
		{
			$event_array_map[$event->short_name] = $event;
		}

		$current_time = date('Y-m-d H:i:s');
		list(
			$name, 
			$short_name, 
			$loan_type_id, 
			$min_age, 
			$max_loans, 
			$states,
			$rules,
			$rule_set_id,
			$company_id
		) = $this->json->decode(stripslashes($_REQUEST['json']));
		
		$is_new_loan_type = false;
		if ('new_' == substr($loan_type_id, 0, 4))
		{
			$is_new_loan_type = true;
		}

		$is_new_rule_set = false;
		if ('new' === $rule_set_id)
		{
			$is_new_rule_set = true;
		}

		if ($is_new_loan_type)
		{
			$lt = ECash::getFactory()->getModel('LoanType');
			$lt->name = $name;
			$lt->name_short = $short_name;
			$lt->date_modified = $current_time;
			$lt->date_created = $current_time;
			$lt->active_status = 'active';
			$lt->company_id = $company_id;
			$this->cfe->save($lt);
		}
		else
		{
			$lt = $this->cfe->fetchLoanType($short_name,$company_id);
		}

		if($is_new_rule_set)
		{
			$old_rule_set = $this->cfe->fetchActiveRuleset($lt->name_short,$company_id);
			$old_rule_set->active_status = 'inactive';
			$this->cfe->save($old_rule_set);
		}

		if ($is_new_loan_type or $is_new_rule_set)
		{
			$rule_set = new ECash_CFE_API_RuleSetDef(ECash::getFactory()->getDB());
			$rule_set->active_status = 'active';
			$rule_set->name = $lt->name .' Rule Set';
			$rule_set->loan_type_id = $lt->loan_type_id;
			$rule_set->date_effective = $current_time;
			$rule_set->date_created = $current_time;
			$this->cfe->save($rule_set);
		}
		else
		{
			$rule_set = $this->cfe->fetchRuleset($rule_set_id);
		}


		// Remove any old rules, conditions and actions
		if (false === $is_new_loan_type)
		{
			$this->emptyRuleSet($rule_set->cfe_rule_set_id);
		}

		// Create rules, conditions and actions
		foreach($rules as $rule)
		{
			$new_rule = new ECash_CFE_API_RuleDef(ECash::getFactory()->getDB());
			$new_rule->date_created = $current_time;
			$new_rule->cfe_rule_set_id = $rule_set->cfe_rule_set_id;
			$new_rule->name = $rule->name;
			$new_rule->salience = $rule->salience;
			$new_rule->cfe_event_id = $event_array_map[$rule->event]->cfe_event_id;
			$this->cfe->save($new_rule);

			foreach($rule->conditions as $condition)
			{
				$new_condition = new ECash_CFE_API_ConditionDef(ECash::getFactory()->getDB());
				$new_condition->cfe_rule_id		= $new_rule->cfe_rule_id;
				$new_condition->operator		= $condition->operator;
				$new_condition->operand1		= $condition->operand1;
				$new_condition->operand1_type	= $condition->operand1_type;
				$new_condition->operand2		= $condition->operand2;
				$new_condition->operand2_type	= $condition->operand2_type;
				$new_condition->sequence_no		= $condition->sequence_no;
				$this->cfe->save($new_condition);
			}

			foreach($rule->actions as $action)
			{
				$new_action = new ECash_CFE_API_ActionDef(ECash::getFactory()->getDB());
				$new_action->cfe_rule_id		= $new_rule->cfe_rule_id;
				$new_action->cfe_action_id		= $action->action;
				$new_action->params				= serialize( get_object_vars($action->params));
				$new_action->sequence_no		= $action->sequence_no;
				$new_action->rule_action_type	= ECash_CFE_API_ActionDef::TYPE_EXECUTE_ON_SUCCESS;
				$this->cfe->save($new_action);
			}

			foreach($rule->else_actions as $action)
			{
				$new_action = new ECash_CFE_API_ActionDef(ECash::getFactory()->getDB());
				$new_action->cfe_rule_id		= $new_rule->cfe_rule_id;
				$new_action->cfe_action_id		= $action->action;
				$new_action->params				= serialize( get_object_vars($action->params));
				$new_action->sequence_no		= $action->sequence_no;
				$new_action->rule_action_type	= ECash_CFE_API_ActionDef::TYPE_EXECUTE_ON_FAILURE;
				$this->cfe->save($new_action);
			}
		}

		return true;
	}
	
	protected function emptyRuleSet($rule_set_id)
	{
			$rule_array = $this->cfe->fetchAllRules($rule_set_id);
			$sub_model_array = array();

			foreach($rule_array as $rule)
			{
				$sub_model_array = array_merge($sub_model_array, $this->cfe->fetchAllConditions($rule->cfe_rule_id), $this->cfe->fetchAllActions($rule->cfe_rule_id));
			}

			foreach(array_merge($rule_array, $sub_model_array) as $model)
			{
				$this->cfe->delete($model);
			}
	}

	public function deleteLoanType($loan_type_id, $loan_type_name, $rule_set_id)
	{
		$this->emptyRuleSet($rule_set_id);
		$this->cfe->deleteRuleSet($rule_set_id);

		$most_recent = false;
		$active_found = false;
		foreach($this->cfe->fetchAllRulesets($loan_type_name) as $rs)
		{
			if(false === $most_recent)
			{
				$most_recent = $rs;
			}
			else if(strtotime($rs->date_created) > strtotime($most_recent->date_created))
			{
				$most_recent = $rs;
			}

			if('active' == $rs->active_status)
			{
				$active_found = true;
				break;
			}
		}

		if(false !== $most_recent && false === $active_found)
		{
			$most_recent->active_status = 'active';
			$this->cfe->save($most_recent);
		}

		if(false === $most_recent)
		{
			$this->cfe->deleteLoanType($loan_type_id);
		}

		return true;
	}
}

$service = new ConfigJsonService();

$service->run();

?>
