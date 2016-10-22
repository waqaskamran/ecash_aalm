<?php
/**
 * This is a generic response packet. It should only be used for parsing responses.
 * Never use for making/saving FactorTrust calls.
 *
 * @author Kyle Barrett <kyle.barrett@sellingsource.com>
 */
class ECash_FactorTrust_Responses_Generic extends FactorTrust_UW_Response implements ECash_FactorTrust_IResponse
{
	/**
	 * Gets the global decision from the FactorTrust response.
	 *
	 * @return string Global Decision
	 */

    public function getDecision()
    {
        // we translate the FT result 'A' & 'D' to dataX 'Y' and 'N'
        if ($this->findNode('/ApplicationResponse/ApplicationInfo/TransactionStatus') == 'A') return 'Y';
        else return 'N';
    }

	/**
	 * Gets the FactorTrust authentication score.
	 *
	 * @return int Score
	 */ 
    public function getScore()
    {
        return $this->findNode('/ApplicationResponse/ApplicationInfo/LendProtectScore');
    }
	

	/**
	 * Gets customer pay rate (this has yet to be defined)
	 *
	 * @return NULL
	 */
	public function getPayRate()
	{
		return NULL;	
	}
	
	/**
	 * Is the package valid? It should be, it's generic.
	 */
	public function isValid()
	{
		return TRUE;
	}
	
	/**
	 * Get the global decision buckets.
	 */
	public function getDecisionBuckets()
	{
		return $this->getGlobalDecisionBuckets();	
	}
}
