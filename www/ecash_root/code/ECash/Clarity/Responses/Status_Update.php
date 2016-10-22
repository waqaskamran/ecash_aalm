<?php

/**
 * Clarity Status Update Response
 */
class ECash_Clarity_Responses_Status_Update extends Clarity_UW_Response implements ECash_Clarity_IResponse
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