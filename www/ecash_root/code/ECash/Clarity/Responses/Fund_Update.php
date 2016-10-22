<?php
/**
 * Fund Update response
 */
class ECash_Clarity_Responses_Fund_Update extends Clarity_UW_Response implements ECash_Clarity_IResponse
{
	/**
	 * (non-PHPdoc)
	 * @see code/ECash/Clarity/ECash_Clarity_IResponse#isValid()
	 */
	public function isValid()
	{
		return $this->getDecision() == 'TRUE';
	}

	/**
	 * (non-PHPdoc)
	 * @see code/ECash/Clarity/ECash_Clarity_IResponse#getDecision()
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
