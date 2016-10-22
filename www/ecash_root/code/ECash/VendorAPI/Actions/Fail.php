<?php
/**
 * Processes Application Failures. Should be passed the serialized state object as the first parameter
 *
 * @author Raymond Lopez <raymond.lopez@sellingsource.com>
 */
class ECash_VendorAPI_Actions_Fail extends VendorAPI_Actions_Fail
{

	
	/**
	 * Executes the Fail Action
	 *  - Added CFE Result Sets
	 *
	 * @param string $state - serialized state object
	 * @return VendorAPI_Response
	 */
	public function execute(array $data = NULL, $state = NULL)
	{
		$engine = new ECash_CFE_AsynchEngine(
                        $this->driver->getDatabase(),
                        $this->driver->getCompanyId()
                );
		
		$result = $engine->beginExecution($data, FALSE);
		$data['cfe_result'] = $result;

		$rules = $this->driver->getBusinessRules($result->getLoanTypeId());
		$data['rule_set_id'] = $this->driver->getRuleSetID();
		$data['cfe_rule_set_id'] = $result->getRulesetId();
		$data['loan_type_id'] = $result->getLoanTypeId();		
		return parent::execute($data, $state);
	}
		
}
?>