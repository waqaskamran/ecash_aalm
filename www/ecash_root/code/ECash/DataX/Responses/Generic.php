<?php
/**
 * This is a generic response packet. It should only be used for parsing responses.
 * Never use for making/saving DataX calls.
 *
 * @author Kyle Barrett <kyle.barrett@sellingsource.com>
 */
class ECash_DataX_Responses_Generic extends TSS_DataX_Response implements ECash_DataX_IResponse
{
	/**
	 * @var array
	 */
	private static $decision_nodes = array(
					'//GlobalDecision/Result',
					'//Response/Summary/Decision',
				);
				
	/**
	 * Gets the global decision from the DataX response.
	 *
	 * @return string Global Decision
	 */
	public function getDecision()
	{
		foreach(self::$decision_nodes as $decision_node)
		{
			if($decision = $this->findNode($decision_node))
			{
				break;
			}
		}
		
		return $decision;
	}
	
	/**
	 * Gets the DataX authentication score.
	 *
	 * @return int Score
	 */ 
	public function getScore()
	{
		return $this->findNode('//AuthenticationScoreSet/AuthenticationScore');
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
