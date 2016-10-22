<?php
/**
 * This class is used to get black box decision rules and suppression lists from OLP.
 *
 * @author Kyle Barrett <kyle.barrett@sellingsource.com>
 */
class Decision_Rules
{
	/**
	 * @var Rpc_Client_1
	 */
	private $rpc;
	
	/**
	 * @var string Enterprise name
	 */
	private $enterprise;
	
	/**
	 * @var array
	 */
	private $campaigns = array();
	
	/**
	 * @var array
	 */
	private $campaign_rules = array();
	
	/**
	 * @var array
	 */
	private $company_rules = array();
	
	/**
	 * @var array
	 */
	private $modes = array(
					'BROKER',
					'ONLINE_CONFIRMATION',
					'ECASH_REACT',
					);	   
	/**
	 * Constructor
	 *
	 * @param string $enterprise
	 * @param string $url URL to Black Box Api Query
	 */
	public function __construct($enterprise, $url)
	{
		$this->enterprise = ucfirst(strtolower($enterprise));
		$this->rpc        = new Rpc_Client_1($url);
	}
	
	/**
	 * Gets all campaigns, rules, and suppression lists for a company
	 *
	 * @param string $company
	 * @return array
	 */
	public function getAll($company)
	{
		$rules            = array();
		$suppression_list = array();
		
		$campaigns = $this->getCampaigns($company);
		
		foreach($campaigns as $name_short=>$campaign)
		{
			$campaigns[$name_short]['rules'] = $this->getRulesByCampaign($name_short);
		}
		
		$company_rules = $this->getRulesByCompany($company);
		
		//Find the suppression list information inside of the company rules and grab the full list from Black Box
		foreach($company_rules as $key=>$rule)
		{
			if($rule['rule_name'] == "suppression_lists")
			{
				$lists = $rule['value'];
				
				foreach($lists as $list)
				{
					$temp = $list;
					$temp = array_merge($temp, $this->getSuppressionList($temp['suppression_id']));	
					$suppression_list[] = $temp;
				}
				
				//Remove the suppression list info from the company rules
				unset($company_rules[$key]);
			}
		}
		
		$rules['company']          = $company_rules;
		$rules['campaigns']        = $campaigns;
		$rules['suppression_list'] = $suppression_list;
		
		return $rules;
	}
	
	/**
	 * Gets all campaigns for a specific enterprise.
	 *
	 * @param string $company Options parameter to filter results by company.
	 * @return array Campaign info
 	 */
	public function getCampaigns($company)
	{
		if(empty($this->campaigns))
		{
			$this->campaigns = $this->rpc->getCampaignsByCompany($this->enterprise);
			
			$this->validate($this->campaigns);
		}
		
		return $this->filterCampaignsByCompany($this->campaigns, $company);
	}
	
	/**
	 * Filters a compaign array using the specific company name. Please note that currently this is a
	 * bit of a hack. There is no way to associate campaigns with individual companies except to
	 * check if the company_short is part of the campaigns property_short (example: Campaign 'bgc-2' probably belongs
	 * to bgc.
	 *
	 * @param array $campaigns
	 * @param string $company
	 * @return array Filtered campaign array
	 */
	private function filterCampaignsByCompany($campaigns, $company)
	{
		$campaign_results = array();
		
		if(is_array($campaigns))
		{
			//Iterate through the campaign array and only return the campaigns belongs 
			foreach($campaigns as $campaign=>$data)
			{
				if(preg_match("/{$company}/is", $campaign))
				{
					$campaign_results[$campaign] = $data;
				}	
			}
		}
		else
		{
			throw new Exception("Argument 1 of method " . __METHOD__ . " must be an array in class " . __CLASS__ . "!");
		}

		return $campaign_results;
	}
	
	/**
	 * Gets all company specific decision rules
	 *
	 * @param string $company
	 * @return array Company rules
	 */
	public function getRulesByCompany($company)
	{
		if(empty($this->company_rules[$company]))
		{
			$this->company_rules[$company]['TARGET'] = array();
			
			foreach($this->modes as $mode)
			{
				$array = $this->rpc->getTargetRules($company, $mode);
				$this->validate($array);
				$this->company_rules[$company]['TARGET'] = array_merge($this->company_rules[$company]['TARGET'], $array['TARGET']);
			}
			
		}
		
		return $this->company_rules[$company]['TARGET'];
	}
	
	/**
	 * Get rules by compaign name_short
	 *
	 * @param string $campaign
	 * @return array Campaign rules
	 */
	public function getRulesByCampaign($campaign)
	{
		if(empty($this->campaign_rules[$campaign]))
		{
			$this->campaign_rules[$campaign]['CAMPAIGN'] = array();
			
			foreach($this->modes as $mode)
			{
				$array = $this->rpc->getCampaignRules($campaign, $mode);
				$this->validate($array);
				$this->campaign_rules[$campaign]['CAMPAIGN'] = array_merge((array)$this->campaign_rules[$campaign]['CAMPAIGN'], $array['CAMPAIGN']);
			}
		}

		return $this->campaign_rules[$campaign]['CAMPAIGN'];
	}
	
	/**
	 * Gets suppression list information by id
	 *
	 * @param int $suppression_list_id
	 * @return array Suppression list information
	 */
	public function getSuppressionList($suppression_list_id)
	{
		if(empty($this->suppression_list[$suppression_list_id]))
		{
			$this->suppression_list[$suppression_list_id] = $this->rpc->getSuppressionList($suppression_list_id);	
		}
		
		return $this->suppression_list[$suppression_list_id];
	}
	
	/**
	 * Validates the information returned from the black box. If it returned nothing, then we'll
	 * throw an exception and catch it later on.
	 *
	 * @param array $data Data returned from the black box
	 */
	public function validate($data)
	{
		if(!is_array($data))
			throw new Exception("Blackbox Returned Invalid Data.");	
	}
}
?>
