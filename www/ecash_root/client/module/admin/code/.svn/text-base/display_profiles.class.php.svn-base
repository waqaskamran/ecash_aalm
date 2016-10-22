<?php

require_once(LIB_DIR . "form.class.php");
require_once(COMMON_LIB_DIR . "advanced_sort.1.php");
require_once("admin_parent.abst.php");
require_once(SQL_LIB_DIR . "agent_affiliation.func.php");

//ecash module
class Display_View extends Admin_Parent
{
	private $agents;
	private $last_agent_id;
	private $last_action;
	private $agent;

	public function __construct(ECash_Transport $transport, $module_name)
	{
		parent::__construct($transport, $module_name);
		$returned_data = ECash::getTransport()->Get_Data();

		$this->agents = $returned_data->agents;
		$this->all_agents = $returned_data->all_agents;

		$this->agent          = ECash::getFactory()->getModel('Agent')->loadBy(array('agent_id' => ECash::getTransport()->agent_id));

		// get the last agent id if it exists
		$this->last_agent_id =  (!empty($returned_data->last_agent_id)) ? $returned_data->last_agent_id : 0;

		// get the last acl action if it exists
		$this->last_action =  (!empty($returned_data->last_action))     ? $returned_data->last_action   : 0;
	}


	/**
	 *
	 */
	public function Get_Header()
	{
		$fields = new stdClass();
		$fields->agent_count = count($this->agents);

		$agents = array();

		foreach ($this->agents as $agent)
		{
			$agents[] = array(
					'id'                      => $agent->agent_id,
					'agent_login'             => $agent->login,
					'name_first'              => $agent->name_first,
					'name_last'               => $agent->name_last,
					'active_status'           => $agent->active_status,
					'cross_company_admin'     => $agent->cross_company_admin,
					'date_last_login'		  => $agent->date_last_login == NULL ? NULL : date('Y-m-d H:i:s', $agent->date_last_login),
					);

		}

		$fields->agents = json_encode($agents);

		foreach ($this->all_agents as $agent)
		{
			$all_agents[] = array(
					'id'                      => $agent->agent_id,
					'agent_login'             => $agent->login,
					'name_first'              => $agent->name_first,
					'name_last'               => $agent->name_last,
					'active_status'           => $agent->active_status,
					'cross_company_admin'     => $agent->cross_company_admin,
					'date_last_login'		  => $agent->date_last_login == NULL ? NULL : date('Y-m-d H:i:s', $agent->date_last_login),
					);

		}

		$fields->all_agents = json_encode($all_agents);

		$fields->last_agent_id = (isset($this->last_agent_id)) ? $this->last_agent_id : 0;
		$fields->last_action   = (isset($this->last_action))   ? $this->last_action   : 0;

		$js = new Form(ECASH_WWW_DIR.'js/admin_profiles.js');

		return parent::Get_Header() . $js->As_String($fields);
	}

	public function Get_Module_HTML()
	{

		switch ( ECash::getTransport()->Get_Next_Level() )
		{
			case 'default':
			default:
				$fields = new stdClass();
				$fields->agent_count = count($this->agents);

				/**
				 * This is so existing cross company admins can edit the cross company admin
				 * flag on another profile.
				 */
				if ((isset($this->agent->cross_company_admin)) && $this->agent->cross_company_admin == 1)
				{
					$fields->cross_company_admin_tr  = "<tr>";
					$fields->cross_company_admin_tr .= "<td width=\"26%\">&nbsp;</td>";
					$fields->cross_company_admin_tr .= "<td class=\"align_left\">Cross Company Admin:</td>";
					$fields->cross_company_admin_tr .= "<td class=\"align_left\">";
					$fields->cross_company_admin_tr .= "<input tabindex=\"6\" name=\"is_cross_company_admin\" type=\"checkbox\" value=\"cca\">";
					$fields->cross_company_admin_tr .= "</td>";
					$fields->cross_company_admin_tr .= "<td width=\"26%\">&nbsp;</td>";
					$fields->cross_company_admin_tr .= "</tr>";
				}

				foreach ( $this->agents as $user )
				{
					$fields->user_select_list .= "<option value={$user->agent_id}>{$user->login}</option>";

					if($user->active_status == 'active')
					{
						$fields->user_select_list_active .= "<option value={$user->agent_id}>{$user->login}</option>";
					}
				}

				$form = new Form(CLIENT_MODULE_DIR.$this->module_name."/view/admin_profiles.html");

				return $form->As_String($fields);
		}
	}
}

?>
