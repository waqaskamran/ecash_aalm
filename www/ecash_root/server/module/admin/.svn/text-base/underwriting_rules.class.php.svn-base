<?php
/**
 * This class is model transport for the admin access of the campaign to inquiry rules.
 *
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */

require_once (SERVER_CODE_DIR.'module_interface.iface.php');
//require_once (LIB_DIR.'underwriting_rules.class.php');

class Underwriting_Rule
{
	private $transport;
	private $request;
	private $last_agent_id;
	private $agent_login_id;
	private $acl;
	private $logged_in_company_id;
	private $agent_id;
	private $uw_rules;

	/**
	 *
	 */
	public function __construct(Server $server, $request)
	{
		$this->logged_in_company_id = $server->company_id;
		$this->transport = ECash::getTransport();
		$this->request = $request;
		$this->agent_login_id = $server->agent_id;
		$this->uw_rules = new ECash_UnderwritingRulesCache(ECash::getMasterDb());
		$this->acl = ECash::getACL();
		$this->agent_id = $server->agent_id;
	}

	/**
	 *
	 */
	public function Display()
	{
		return $this->_Fetch_Data();
	}

	/**
	 *
	 */
	private function _Fetch_Data()
	{
		$companies = ECash::getFactory()->getReferenceList('Company');
		$uw_rule_set = $this->uw_rules->Get_Underwriting_Rules();
		$campaign_groups = $this->uw_rules->Get_Campaign_Groups();
		$campaign_pubs = $this->uw_rules->Get_Campaign_Publishers();
		$campaigns = $this->uw_rules->Get_Campaigns();
		$inquiries = $this->uw_rules->Get_Inquiries();
		$providers = $this->uw_rules->Get_Providers();
		$stores = $this->uw_rules->Get_Stores();

		// get company id
		$company_name = '';
		foreach($companies as $company)
		{
			if ($company->active_status == 'active' &&
				$company->company_id == $this->logged_in_company_id)
			{
				$company_name = $company->name;
				break;
			}
		}

		// set the data
		$data = new StdClass();
		$data->company_name = $company_name;
		$data->uw_rule_set = $uw_rule_set;
		$data->campaign_groups = $campaign_groups;
		$data->campaign_pubs = $campaign_pubs;
		$data->campaigns = $campaigns;
		$data->inquiries = $inquiries;
		$data->providers = $providers;
		$data->stores = $stores;

		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}

	/**
	 *
	 */
	public function Insert_Group()
	{
		$result = array();

		$group_name = $this->request->campaign_group_name;
		$risk_value = $this->request->campaign_group_risk;
		
		$data->campaign_group_id = $this->uw_rules->Add_Campaign_Group($group_name, $risk_value);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Insert_Publisher()
	{
		$result = array();

		$publisher_name = $this->request->publisher_name;
		
		$data->campaign_publisher_id = $this->uw_rules->Add_Campaign_Publisher($publisher_name);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Insert_Campaign()
	{
		$result = array();

		$campaign_name = $this->request->campaign_name;
		$publisher_id = $this->request->campaign_publisher;
		$group_id = $this->request->campaign_group;
		$income_source = $this->request->income_source;
		$income_frequency = $this->request->income_frequency;
		
		$data->campaign_id = $this->uw_rules->Add_Campaign($campaign_name, $publisher_id, $group_id, $income_source, $income_frequency);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Insert_Inquiry()
	{
		$result = array();

		$inquiry_name = $this->request->uw_inquiry_name;
		$provider_id = $this->request->uw_provider;
		$store_id = $this->request->uw_store;
		
		$data->uw_inquiry_id = $this->uw_rules->Add_Inquiry($inquiry_name, $provider_id, $store_id);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Insert_Provider()
	{
		$result = array();

		$provider_name = $this->request->provider_name;
		$provider_name_short = $this->request->provider_name_short;
		
		$data->uw_inquiry_id = $this->uw_rules->Add_Provider($provider_name, $provider_name_short);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Insert_Store()
	{
		$result = array();

		$store_id = $this->request->store_id_name;
		$provider_id = $this->request->store_provider;
		$group_id = $this->request->store_group_id;
		$merchant = $this->request->merchant_id;
		$username = $this->request->username;
		$password = $this->request->password;
		
		$data->uw_store_id = $this->uw_rules->Add_Store($store_id, $provider_id, $group_id, $merchant, $username, $password);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Set_Campaign_Inquiry()
	{
		$result = array();

		$campaign_id = $this->request->ci_campaign;
		$inquiry_id = $this->request->ci_inquiry;
		$store_id = $this->request->ci_count;
		
		$data->uw_inquiry_id = $this->uw_rules->Set_Campaign_Inquiry($campaign_id, $inquiry_id, $store_id);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Update_Group()
	{
		$result = array();

		$campaign_group_id = $this->request->campaign_group_id;
		$group_name = $this->request->campaign_group_name;
		$risk_value = $this->request->campaign_group_risk;
		
		$data->campaign_group_id = $this->uw_rules->Update_Campaign_Group($campaign_group_id, $group_name, $risk_value);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Update_Publisher()
	{
		$result = array();

		$publisher_id = $this->request->publisher_id;
		$publisher_name = $this->request->publisher_name;
		
		$data->campaign_publisher_id = $this->uw_rules->Update_Campaign_Publisher($publisher_id,$publisher_name);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Update_Campaign()
	{
		$result = array();

		$campaign_id = $this->request->campaign_id;
		$campaign_name = $this->request->campaign_name;
		$publisher_id = $this->request->campaign_publisher;
		$group_id = $this->request->campaign_group;
		$income_source = $this->request->income_source;
		$income_frequency = $this->request->income_frequency;
		
		$data->campaign_id = $this->uw_rules->Update_Campaign($campaign_id, $campaign_name, $publisher_id, $group_id, $income_source, $income_frequency);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Update_Inquiry()
	{
		$result = array();

		$inquiry_id = $this->request->uw_inquiry_id;
		$inquiry_name = $this->request->uw_inquiry_name;
		$provider_id = $this->request->uw_provider;
		$store_id = $this->request->uw_store;
		
		$data->uw_inquiry_id = $this->uw_rules->Update_Inquiry($inquiry_id, $inquiry_name, $provider_id, $store_id);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Update_Provider()
	{
		$result = array();

		$provider_id = $this->request->provider_id;
		$provider_name = $this->request->provider_name;
		$provider_name_short = $this->request->provider_name_short;
		
		$data->uw_inquiry_id = $this->uw_rules->Update_Provider($provider_id, $provider_name, $provider_name_short);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Update_Store()
	{
		$result = array();

		$uw_store_id = $this->request->uw_store_id;
		$store_id = $this->request->store_id_name;
		$provider_id = $this->request->store_provider;
		$group_id = $this->request->store_group_id;
		$merchant = $this->request->merchant_id;
		$username = $this->request->username;
		$password = $this->request->password;
		
		$data->uw_store_id = $this->uw_rules->Update_Store($uw_store_id, $store_id, $provider_id, $group_id, $merchant, $username, $password);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Delete_Group()
	{
		$result = array();

		$campaign_group_id = $this->request->campaign_group_id;
		
		$data->campaign_group_id = $this->uw_rules->Delete_Campaign_Group($campaign_group_id);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Delete_Publisher()
	{
		$result = array();

		$publisher_id = $this->request->publisher_id;
		
		$data->campaign_publisher_id = $this->uw_rules->Delete_Campaign_Publisher($publisher_id);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Delete_Campaign()
	{
		$result = array();

		$campaign_id = $this->request->campaign_id;
		
		$data->campaign_id = $this->uw_rules->Delete_Campaign($campaign_id);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Delete_Inquiry()
	{
		$result = array();

		$inquiry_id = $this->request->uw_inquiry_id;
		
		$data->uw_inquiry_id = $this->uw_rules->Delete_Inquiry($inquiry_id);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Delete_Provider()
	{
		$result = array();

		$provider_id = $this->request->provider_id;
		
		$data->uw_inquiry_id = $this->uw_rules->Delete_Provider($provider_id);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Delete_Store()
	{
		$result = array();

		$uw_store_id = $this->request->uw_store_id;
		
		$data->uw_store_id = $this->uw_rules->Delete_Store($uw_store_id);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Delete_Campaign_Inquiry()
	{
		$result = array();

		$campaign_inquiry_id = $this->request->campaign_inquiry_id;
		
		$data->uw_inquiry_id = $this->uw_rules->Delete_Campaign_Inquiry($campaign_inquiry_id);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

}

?>
