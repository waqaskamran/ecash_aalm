<?php

require_once SERVER_CODE_DIR.'module_interface.iface.php';

class Privs
{
	private $transport;
	private $request;
	private $last_agent_id;
	private $agent_login_id;
	private $acl;
	private $server;

	/**
	 *
	 */
	public function __construct(Server $server, $request)
	{
		$this->server = $server;
		$this->agent_login_id = $server->agent_id; //agent_login_id;
		$this->transport = ECash::getTransport();
		$this->request = $request;
		$this->acl = ECash::getACL();

		if(!empty($this->request->agent_list) && is_numeric($this->request->agent_list) )
		{
			$this->last_agent_id = $this->request->agent_list;
		}
	}



	/**
	 *
	 */
	public function Add_Privs()
	{
		if( $this->acl->Acl_Access_Ok("admin", $this->request->all_companies_list) &&
			$this->acl->Acl_Access_Ok("privs", $this->request->all_companies_list) )
		{			
			$this->acl->addAgentToGroup($this->request->agent_list, $this->request->group_list);
		}

		return $this->_Fetch_Data();
	}



	/**
	 *
	 */
	public function Delete_Privs()
	{
		if( $this->acl->Acl_Access_Ok("admin", $this->request->agent_company_list)
		&& $this->acl->Acl_Access_Ok("privs", $this->request->agent_company_list) )
		{
			$this->acl->deleteAgentFromGroup($this->request->agent_list, $this->request->agent_group_list);
		}

		return $this->_Fetch_Data();
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
		$data = new stdClass();
		$data->agents = ECash::getFactory()->getModel('AgentList')->sortedGetBy("login","ASC",array('system_id' => '3'))->toList();
		$data->groups = $this->acl->getGroups();
		$companies = ECash::getFactory()->getReferenceList('Company');
		$data->companies = array();
		foreach($companies as $company)
		{
			if( $company->active_status == 'active' &&
				$this->acl->Acl_Access_Ok("admin", $company->company_id) &&
				$this->acl->Acl_Access_Ok("privs", $company->company_id) )
			{
				$data->companies[] = $company;
			}
		}
		$data->group_sections = $this->acl->getGroupsSections($this->agent_login_id, TRUE);
		$data->agent_groups = $this->acl->getAgentsGroups();
		$data->unsorted_master_tree = ECash::getFactory()->getReferenceList('Section',NULL, array('system_id' => '3'));

		if(isset($this->last_agent_id))
		{
			$data->last_agent_id = $this->last_agent_id;
		}

		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}
}

?>
