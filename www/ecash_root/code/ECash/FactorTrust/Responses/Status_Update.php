<?php

/**
 * FactorTrust Status Update Response
 */
class ECash_FactorTrust_Responses_Status_Update extends FactorTrust_UW_Response implements ECash_FactorTrust_IResponse
{
	public function isValid()
	{
		return $this->getDecision() == 'TRUE';
	}

	public function getDecision()
	{
		return $this->findNode('/DataxResponse/Response/Data/Complete');
	}
	
	public function getScore()
	{
		return NULL;
	}
	
	public function getPayRate()
	{
		return NULL;
	}
}

?>