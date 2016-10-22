<?php

require_once(COMMON_LIB_DIR . 'pay_date_calc.3.php');
require_once(SERVER_CODE_DIR . 'module_interface.iface.php');
require_once(SQL_LIB_DIR . 'util.func.php');
require_once(SQL_LIB_DIR . 'scheduling.func.php');

class API_Payment_Types implements Module_Interface
{
	public function __construct(Server $server, $request, $module_name) 
	{
		$this->request = $request;
		$this->server = $server;
		$this->name = $module_name;
        $this->permissions = array(
            array('loan_servicing'),
            array('funding'),
            array('collections'),
            array('fraud'),
        );
	
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
			case 'save_payment_types':
				// Run them all through the engine first
				$app = ECash::getFactory()->getModel('Application');
				$app->loadBy(array('is_react' => 'no'));

				$decider = new ECash_PaymentTypesRestrictions(ECash::getMasterDb(), 
															  $app->application_id,
															  NULL, // module
															  NULL, // mode
															  NULL, // loan_type_model
															  $app->loan_type_id,
															  TRUE);

				foreach ($input->qualifying as $condition)
				{
					$decider->evaluateCondition($condition->lvalue, $condition->operator, $condition->rvalue);
				}

				foreach ($input->disqualifying as $condition)
				{
					$decider->evaluateCondition($condition->lvalue, $condition->operator, $condition->rvalue);
				}

				$rules = array_merge($input->qualifying, $input->disqualifying);

				// Delete them all beforehand
				if ($input->loan_type_id == 'all')
				{

					$ptls = ECash::getFactory()->getModel('PaymentTypeConditionList');
					$ptls->loadBy(array('payment_type_id' => $input->payment_type_id));

					foreach ($ptls as $ptl)
					{
						$ptl->delete();
					}
				}

				foreach($rules as $rule)
				{
					if ($input->loan_type_id == 'all')
					{
						$lts = ECash::getFactory()->getModel('LoanTypeList');
						$lts->loadBusinessLoanTypes();

						foreach($lts as $lt)
						{
							$ptc = ECash::getFactory()->getModel('PaymentTypeCondition');

							$ptc->date_created = time();

							$ptc->lvalue                      = $rule->lvalue;
							$ptc->operator                    = $rule->operator;
							$ptc->rvalue                      = $rule->rvalue;
							$ptc->loan_type_id                = $lt->loan_type_id;
							$ptc->payment_type_id             = $rule->payment_type_id;
							$ptc->payment_type_condition_type = $rule->payment_type_condition_type;
							$ptc->date_modified               = time();

							$ptc->save();
						}
					}
					else
					{
						$ptc = ECash::getFactory()->getModel('PaymentTypeCondition');

						if ($rule->payment_type_condition_id != 'null')
						{
							$ptc->loadBy(array('payment_type_condition_id' => $rule->payment_type_condition_id));
						}
						else
						{
							$ptc->date_created = time();
						}

						$ptc->lvalue           = $rule->lvalue;
						$ptc->operator         = $rule->operator;
						$ptc->rvalue           = $rule->rvalue;
						$ptc->loan_type_id     = $rule->loan_type_id;
						$ptc->payment_type_id  = $rule->payment_type_id;
						$ptc->payment_type_condition_type = $rule->payment_type_condition_type;
						$ptc->date_modified    = time();

						$ptc->save();
					}
				}

				foreach ($input->deleted as $foo => $payment_type_condition_id)
				{
					if ($input->loan_type_id != 'all')
					{
						$ptc = ECash::getFactory()->getModel('PaymentTypeCondition');
						$ptc->loadBy(array('payment_type_condition_id' => $payment_type_condition_id));
						$ptc->delete();
					}

				}
				
				
				// Find Disqualifying conditions
				$data = JSON_Encode(array('result' => 'success'));
				break;
			case 'test_payment_types':
				$rules = array_merge($input->qualifying, $input->disqualifying);
				$app = ECash::getApplicationById($input->application_id);

				if ($input->loan_type_id == 'all')
				{
					$lts = ECash::getFactory()->getModel('LoanTypeList');
					$lts->loadBusinessLoanTypes();
					
					foreach($lts as $lt)
					{
						$loan_type_id = $lt->loan_type_id;
						break;
					}
				}
				else
					$loan_type_id = $input->loan_type_id;


				$decider = new ECash_PaymentTypesRestrictions(ECash::getMasterDb(), 
															  $input->application_id,
															  NULL, // module
															  NULL, // mode
															  NULL, // loan_type_model
															  $loan_type_id,
															  TRUE);
		
				$qualifying = array();
                foreach ($input->qualifying as $condition)
                {
					if ($decider->evaluateCondition($condition->lvalue, $condition->operator, $condition->rvalue))
					{
						$qualifying[] = $condition->row;
					}
				}


				$disqualifying = array();
                foreach ($input->disqualifying as $condition)
                {
					if ($decider->evaluateCondition($condition->lvalue, $condition->operator, $condition->rvalue))
					{
						$disqualifying[] = $condition->row;
					}
				}

				
				// Find Disqualifying conditions
				$data = JSON_Encode(array('qualifying' => $qualifying, 'disqualifying' => $disqualifying));
				break;

			case 'get_payment_types':
                $ptc = ECash::getFactory()->getModel('PaymentTypeConditionList');
	
				if ($input->loan_type_id == 'all')
				{
					$lts = ECash::getFactory()->getModel('LoanTypeList');
					$lts->loadBusinessLoanTypes();
					
					foreach($lts as $lt)
					{
						$loan_type_id = $lt->loan_type_id;
						break;
					}
				}
				else
					$loan_type_id = $input->loan_type_id;
				
                $ptc->loadBy(array('loan_type_id' => $loan_type_id, 'payment_type_id' => $input->payment_type_id));

                $myconditions = array();
                foreach ($ptc as $condition)
                {
                    $myconditions[] = array('payment_type_condition_id'   => $condition->payment_type_condition_id,
                                            'loan_type_id'                => $condition->loan_type_id,
											'payment_type_id'             => $condition->payment_type_id,
                                            'payment_type_condition_type' => $condition->payment_type_condition_type,
                                            'lvalue'                      => $condition->lvalue,
                                            'operator'                    => $condition->operator,
                                            'rvalue'                      => $condition->rvalue);

                }

                $data = JSON_Encode($myconditions);
				break;
			default:
				throw new Exception("Unknown action {$input->action}");
		}
		return $data;
	}
}

?>
