<?php

require_once SERVER_CODE_DIR.'module_interface.iface.php';

class Groups
{
	private $transport;
	private $request;
	private $last_group_id;
	private $last_action;
	private $agent_login_id;
	private $acl;
	private $server;


	/**
	 *
	 */
	public function __construct(Server $server, $request)
	{
		$this->server = $server;
		$this->agent_login_id = $server->agent_id;
		$this->transport = ECash::getTransport();
		$this->request = $request;
		$this->acl = ECash::getACL();

		if (!empty($request->action))
		{
			$this->last_action = $request->action;
		}

		if (!empty($this->request->groups) && is_numeric($this->request->groups))
		{
			$this->last_group_id = $this->request->groups;
		}
	}



	/**
	 *
	 */
	public function Add_Groups()
	{
		if ($this->Has_Company_Access($this->request->company))
		{
			$section_ids = $this->_Get_Group_IDs($this->request);
			$read_onlys = $this->_Get_Group_read_onlys($this->request);
			$name = $this->request->group_company_name . " " . $this->request->group_name;
				
			/**
			 * this is already checked on the client by javascript, but it's possible (mostly during QA)
			 * that a group will get added by someone else and result in a mysql error.
			 */
			$group = ECash::getFactory()->getModel('AccessGroup');
			$group->loadBy(array('name' => $name));
			if (!empty($group->access_group_id))
			{
				$this->request->message = 'A group by that name already exists.';
			}
			else 
			{
				$access_group_id = $this->acl->addGroup($name,
														$this->request->company,
														$section_ids,
														$read_onlys);
	
				$used_options = array();
				if (isset($this->request->used_options))
				{
					foreach ($this->request->used_options as $key => $value)
					{
						$used_options[count($used_options)] = $value;
					}
				}
				
				$this->acl->addAccessGroupControlOptions($access_group_id, $used_options);
			}
		}

		// Reload the acl
		if($this->server->Reset_ACL())
		{
			return $this->_Fetch_Data();
		}
		
		return false;
	}
	/**
	 *
	 */
	public function Copy_Group()
	{
		if ($this->Has_Company_Access($this->request->dest_company))
		{
			$section_ids = $this->_Get_Group_IDs($this->request);
			$read_onlys = $this->_Get_Group_read_onlys($this->request);
			$name = $this->request->group_dest_company_name . " " . $this->request->group_name;
						
			$access_group_id = $this->acl->addGroup($name,
													$this->request->dest_company,
													$section_ids,
													$read_onlys);

			$used_options = array();
			if (isset($this->request->used_options))
			{
				foreach ($this->request->used_options as $key => $value)
				{
					$used_options[count($used_options)] = $value;
				}
			}

			$this->acl->addAccessGroupControlOptions($access_group_id, $used_options);
		}

		// Reload the acl
		if($this->server->Reset_ACL())
		{
			return $this->_Fetch_Data();
		}
		
		return false;
	}



	/**
	*
	*/
	private function Has_Company_Access($company_id)
	{
		$allowed_companies = array();
		$result = FALSE;
		$companies = ECash::getFactory()->getReferenceList('Company');

		foreach($companies as $company)
		{
			
			if( $company->active_status == 'active' &&
				$this->acl->Acl_Access_Ok('admin', $company_id)	&&
				$this->acl->Acl_Access_Ok('privs', $company_id))
			{
				$allowed_companies[] = $company;
			}
		}

		foreach($allowed_companies as $key => $value)
		{
			if ($value->company_id == $this->request->company)
			{
				$result = TRUE;
				break;
			}
		}

		return $result;
	}





	/**
	 *
	 */
	private function _Get_Group_read_onlys($request)
	{
		$section_ids = Array();
		foreach($request as $key => $value)
		{
			if (substr($key, 0, 10) == 'read_only_')
			{
				$section_ids[substr($key, 10)] = $value;
			}
		}
		return $section_ids;
	}

	private function _Get_Group_IDs($request)
	{
		$section_ids = Array();
		foreach($request as $key => $value)
		{
			if (substr($key, 0, 8) == 'section_')
			{
				$section_ids[count($section_ids)] = substr($key, 8, strlen($key) - 7);
			}
		}
		return $section_ids;
	}




	/**
	 *
	 */
	public function Delete_Groups()
	{
		if ($this->Has_Company_Access($this->request->company))
		{
			$this->acl->removeGroup($this->request->groups);
		}

		// Reload the acl
		if($this->server->Reset_ACL())
		{
			return $this->_Fetch_Data();
		}
		
		return false;
	}




	/**
	 *
	 */
	public function Modify_Groups()
	{
		if ($this->Has_Company_Access($this->request->company))
		{
			$section_ids = $this->_Get_Group_IDs($this->request);
			$read_onlys = $this->_Get_Group_read_onlys($this->request);
			$name = $this->request->group_company_name . " " . $this->request->group_name;
			$this->acl->updateGroup($this->request->groups, $name, $this->request->company, $section_ids, $read_onlys);
	     	$this->acl->removeAccessGroupControlOptions($this->request->groups);

			$used_options = array();
			if (isset($this->request->used_options))
			{
				foreach ($this->request->used_options as $key => $value)
				{
					$used_options[count($used_options)] = $value;
				}

				$this->acl->addAccessGroupControlOptions($this->request->groups, $used_options);
			}
		}

		// Reload the acl
		if($this->server->Reset_ACL())
		{
			return $this->_Fetch_Data();
		}
		
		return false;
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
		$data->control_options = $this->acl->getAllControlOptions();
		$data->access_group_control_options = $this->acl->getAllAccessGroupControlOptions();
		$data->group_sections = $this->acl->getGroupsSections($this->agent_login_id, 2);
		$data->message = $this->request->message;
		$data->master_tree = ECash::getFactory()->getReferenceList('Section',NULL, array('system_id' => '3', 'active_status' => 'active'));
		$data->memberships = $this->acl->Get_Group_Membership();

		$companies = ECash::getFactory()->getReferenceList('Company');
		$groups = $this->acl->getGroups();
		$data->groups = array();
		$data->companies = array();

		foreach($companies as $company)
		{
			if( $company->active_status == 'active' &&
				$this->acl->Acl_Access_Ok('admin', $company->company_id) &&
				$this->acl->Acl_Access_Ok('privs', $company->company_id) )
			{
				$data->companies[] = $company;

				// sort the groups so only the ones the user has access to are
				// returned.
				foreach($groups as $group)
				{
					if ($group->company_id == $company->company_id)
					{
						$data->groups[] = $group;
					}
				}
			}
		}

		if( isset($this->last_group_id) )
		{
			$data->last_group_id = $this->last_group_id;
		}

		if( isset($this->last_action) )
		{
			$data->last_action = $this->last_action;
		}

		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}




	/*


	*/
	private function Get_Unique_Group_Ids($group_sections)
	{
		$result = array();

		foreach ($group_sections as $key => $value)
		{
			if (!in_array($value->group_id, $result))
			{
				$result[count($result)] = $value->group_id;
			}

		}

		return $result;
	}



}

?>
