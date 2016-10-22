<?php

require_once SERVER_CODE_DIR.'module_interface.iface.php';
class Profiles
{
	private $transport;
	private $request;
	private $last_agent_id;
	private $last_action;
	private $agent_login_id;
	private $acl;
	private $agent;

	public function __construct($agent_login_id, ECash_Transport $transport, $request, $acl)
	{
		$this->agent_login_id = $agent_login_id;
		$this->transport      = ECash::getTransport();
		$this->request        = $request;
		$this->acl            = $acl;
		$this->agent          = ECash::getAgent()->getModel();
		$this->last_action    = (!empty($this->request->action)) ? "'" . $this->request->action . "'": NULL;
		$this->last_agent_id  = (!empty($this->request->agent))  ? "'" . $this->request->agent  . "'" : NULL;
	}

	public function Add_Profile()
	{
		$system = ECash::getFactory()->getModel('System');
		$system->loadBy(array('name_short' => 'ecash3_0'));

		$agent = ECash::getFactory()->getModel('Agent');
		$agent->login = $this->request->agent_login;
		$agent->name_first = $this->request->name_first;
		$agent->name_last = $this->request->name_last;
		$agent->crypt_password = ECash_Security::manglePassword(strtolower($this->request->password1));
		$agent->system_id = $system->system_id;
		$agent->date_created = date('Y-m-d h:i:s');

		$agent->cross_company_admin = ((isset($this->request->is_cross_company_admin) && isset($this->agent->cross_company_admin)) && $this->agent->cross_company_admin == 1) ? 1 : 0;

		$agent->save();

		return $this->_Fetch_Data();
	}

	public function Modify_Profile()
	{
		$agent = ECash::getFactory()->getModel('Agent');
		$agent->loadBy(array('agent_id' => $this->request->selected_agent));

		if(!empty($agent->agent_id))
		{
			$agent->setDataSynched();
			$agent->login = $this->request->agent_login;
			$agent->name_first = $this->request->name_first;
			$agent->name_last = $this->request->name_last;
			if($this->request->password2)
				$agent->crypt_password = ECash_Security::manglePassword(strtolower($this->request->password2));
			$agent->active_status = isset($this->request->is_active) ? 'active' : 'inactive';
			$agent->cross_company_admin = ((isset($this->request->is_cross_company_admin) && isset($this->agent->cross_company_admin)) && $this->agent->cross_company_admin == 1) ? 1 : 0;
			$agent->save();
		}
		
		return $this->_Fetch_Data();
	}

	public function Display()
	{
		return $this->_Fetch_Data();
	}

	private function _Fetch_Data()
	{
		$data = new stdClass();

		// The 'all_agents' list is required for a check to disallow admins from creating agents that already exist but that they might
		// not have the ability to see.
		$data->all_agents = ECash::getFactory()->getModel('AgentList')->sortedGetBy("login","ASC", array('system_id' => '3'))->toList();

		// Modified this for #16930 so that the Cross Company admin feature (#8655) is part of Impact's 
		// company specific AgentList model.  Since this required a change to the way the list was 
		// retrieved, this method was added to the parent and the customer model. [BR]
		$data->agents = ECash::getFactory()->getModel('AgentList')->getAgentList(ECash::getCompany()->company_id, $this->agent, 3);
			
		$data->last_agent_id = (isset($this->last_agent_id)) ? $this->last_agent_id : 0;
		$data->last_action   = (isset($this->last_action))   ? $this->last_action   : 0;

		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}
}

?>
