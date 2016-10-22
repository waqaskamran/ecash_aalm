<?php
/**
 * This class is model transport for the admin access of the campaign to inquiry rules.
 *
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */

require_once (SERVER_CODE_DIR.'module_interface.iface.php');

class Suppression_Rule
{
	private $transport;
	private $request;
	private $last_agent_id;
	private $agent_login_id;
	private $acl;
	private $logged_in_company_id;
	private $agent_id;
	private $sup_rules;

	/**
	 *
	 */
	public function __construct(Server $server, $request)
	{
		$this->logged_in_company_id = $server->company_id;
		$this->transport = ECash::getTransport();
		$this->request = $request;
		$this->agent_login_id = $server->agent_id;
		$this->sup_rules = new ECash_SuppressionRulesCache(ECash::getMasterDb());
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
		$sup_list = $this->sup_rules->Get_Suppression_Lists();
		$sup_list_values = $this->sup_rules->Get_Suppression_List_Values();
		$sup_values = $this->sup_rules->Get_Suppression_Values();


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
		$data->sup_list = $sup_list;
		$data->sup_list_values = $sup_list_values;
		$data->sup_values = $sup_values;

		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}

	/**
	 *
	 */
	public function Add_Suppression_List_Value()
	{
		$result = array();

		$list_id = $this->request->list_id;
		$revision_id = $this->request->revision_id;
		$value = $this->request->value_added;
		
		$data->value_id = $this->sup_rules->Add_Suppression_List_Value($list_id, $revision_id, $value);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}

	/**
	 *
	 */
	public function Remove_Suppression_List_Value()
	{
		$result = array();

		$list_id = $this->request->list_id;
		$revision_id = $this->request->revision_id;
		$value_id = $this->request->value_id;
		
		$data->value_id = $this->sup_rules->Delete_Suppression_List_Value($list_id, $revision_id, $value_id);

		$result = $this->_Fetch_Data();

		ECash::getTransport()->Set_Data($data);

		return $result;
	}
}

?>
