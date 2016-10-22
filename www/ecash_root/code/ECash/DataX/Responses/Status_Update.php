<?php

/**
 * DataX Status Update Response
 */
class ECash_DataX_Responses_Status_Update extends TSS_DataX_Response implements ECash_DataX_IResponse
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