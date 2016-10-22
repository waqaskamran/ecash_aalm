<?php
/**
 * Fund Update response
 */
class ECash_FactorTrust_Responses_Fund_Update extends FactorTrust_UW_Response implements ECash_FactorTrust_IResponse
{
	/**
	 * (non-PHPdoc)
	 * @see code/ECash/FactorTrust/ECash_FactorTrust_IResponse#isValid()
	 */
	public function isValid()
	{
		return $this->getDecision() == 'TRUE';
	}

	/**
	 * (non-PHPdoc)
	 * @see code/ECash/FactorTrust/ECash_FactorTrust_IResponse#getDecision()
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
