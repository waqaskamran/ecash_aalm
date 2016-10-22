<?php
/**
 * Fund Update response
 */
class ECash_DataX_Responses_Fund_Update extends TSS_DataX_Response implements ECash_DataX_IResponse
{
	/**
	 * (non-PHPdoc)
	 * @see code/ECash/DataX/ECash_DataX_IResponse#isValid()
	 */
	public function isValid()
	{
		return $this->getDecision() == 'TRUE';
	}

	/**
	 * (non-PHPdoc)
	 * @see code/ECash/DataX/ECash_DataX_IResponse#getDecision()
	 */
	public function getDecision()
	{
		return $this->findNode('/DataxResponse/Response/Data/Complete');
	}
	
	public function getPayRate()
	{
		return NULL;
	}
	
	public function getScore()
	{
		return NULL;
	}
}

?>
