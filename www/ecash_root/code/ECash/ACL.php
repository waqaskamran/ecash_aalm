<?php

/**
 * Access Control List class
 *
 * v3 - Split this out because we changed some functionality
 *      that will break other apps that use this class.
 *
 * This is a namespaced version of the old ACL class.  Eventually all the simple queries should be moved
 * to models and the business logic remaining should be seriously re-examined [JustinF]
 */
class ECash_ACL
{
	/**
	 * Holds the system ID. This variable should be assigned as soon as the
	 * system_id becomes avaliable. This should be assigned with the
	 * setSystemId(..) function.
	 *
	 * @access private
	 * @var	 object
	 */
	private $system_id;

	/**
	 * @TODO maybe implement these are ArrayAccess, Iterator, etc.
	 */
	private $sorted_user_acl;
	private $unsorted_user_acl;

	/**
	 * @var DB_IConnection_1
	 */
	private $db;


	/**
	 * @access public
	 * @return void
	 */
	public function __construct(DB_IConnection_1 $db)
	{
		$this->db = $db;
		$this->sorted_user_acl = array();
		$this->unsorted_user_acl = array();
	}

	public function setSystemId($system_id)
	{
		$this->system_id = $system_id;
	}

	/**
	 * Returns an array of all groups plus an indicator
	 * if there are any agents associated
	 *
	 * @access public
	 * @throws MySQL_Exception
	 * @return array		array of group row objects
	 */
	public function getGroups()
	{
		$system_sql = ($this->system_id == NULL) ? "" : "WHERE ag.system_id = " . $this->system_id . " ";

		$query = "
			select
			ag.active_status,
			ag.company_id,
			ag.access_group_id as group_id,
			ag.name,
			if(count(aag.agent_id) = 0, 0, 1) is_used
				from
					access_group ag
					left join agent_access_group aag on (aag.access_group_id = ag.access_group_id)
					{$system_sql}
		group by
			ag.active_status,
			ag.company_id,
			ag.name, 
			ag.access_group_id
		order by 
			ag.name;
		";
		$db = ECash::getSlaveDb();

		$st = $this->db->query($query);
		return $st->fetchAll(PDO::FETCH_OBJ);
	}

	public function addGroup($name, $company_id, $section_ids, $read_onlys)
	{
		$group = ECash::getFactory()->getModel('AccessGroup', $this->db);
		$group->name = $name;
		$group->company_id = $company_id;
		$group->system_id = $this->system_id;
		$group->active_status = 'active';
		$group->date_modified = time();
		$group->date_created = time();
		$group->save();

		$this->updateSections($group->access_group_id, $company_id, $section_ids, $read_onlys);

		return $group->access_group_id;
	}

	private function updateSections($group_id, $company_id, $section_ids, $read_onlys)
	{
		$this->deleteACL($group_id);


		$acl_list = new DB_Models_ModelList_1(
			ECash::getFactory()->getModel('Acl', $this->db),
				$this->db
		);

		foreach($section_ids as $section_id)
		{
			$acl = ECash::getFactory()->getModel('Acl', $this->db);
			$acl->date_modified = time();
			$acl->date_created = time();
			$acl->active_status = 'active';
			$acl->company_id = $company_id;
			$acl->access_group_id = $group_id;
			$acl->section_id = $section_id;
			//Changed in GF #22280
			$acl->setReadOnlyAcl(!empty($read_onlys[$section_id]) ? 1 : 0);

			$acl_list->add($acl);
		}

		$acl_list->save();
	}

	private function deleteACL($group_id)
	{
		$query = "delete from acl where access_group_id = ?";
		$this->db->queryPrepared($query,array($group_id));
	}

	public function updateGroup($group_id, $name, $company_id, $section_ids, $read_onlys)
	{
		$group = ECash::getFactory()->getModel('AccessGroup', $this->db);
		$group->loadBy(array('access_group_id' => $group_id));
		$group->access_group_id = $group_id;
		$group->setDataSynched();
		$group->name = $name;
		$group->company_id = $company_id;
		$group->system_id = $this->system_id;
		$group->active_status = 'active';
		$group->date_modified = time();
		$group->date_created = time();
		$group->save();

		$this->updateSections($group_id, $company_id, $section_ids, $read_onlys);
	}

	public function removeGroup($group_id)
	{
		$this->deleteACL($group_id);
		// delete the access group
		$query = "delete from access_group where access_group_id = ?";
		$this->db->queryPrepared($query, array($group_id));

		$this->removeAccessGroupControlOptions($group_id);
		return TRUE;
	}

	public function removeAccessGroupControlOptions($access_group_id)
	{
		$query = "delete from access_group_control_option where access_group_id = ?";
		$this->db->queryPrepared($query, array($access_group_id));

		return TRUE;
	}

	/**
	 * Returns an array of all sections that an agent
	 * is allowed to see/use based on relationships and
	 * active status
	 *
	 * @TODO this could probably be carefully replaced with a reference list (models) of sections [JustinF]
	 *
	 * @param integer $agent_id	agent_id to check for
	 *
	 * @access public
	 * @throws MySQL_Exception
	 * @return array		array of section row objects
	 */
	public function getAllowedSections($agent_id)
	{
		$system_sql = ($this->system_id == NULL) ? "" : "and s.system_id = " . $this->system_id . " ";

		$query = "
			select
			acl.company_id,
			s.active_status,
			s.name,
			s.description,
			s.system_id,
			s.section_id,
			s.section_parent_id,
			s.sequence_no,
			s.level,
			acl.read_only
				from
				section s,
			agent a,
			acl,
			agent_access_group aag
				where
				a.agent_id = ?
				and a.active_status = 'Active'
				and a.agent_id = aag.agent_id
				and aag.active_status = 'Active'
				and aag.access_group_id = acl.access_group_id
				and acl.active_status = 'Active'
				and acl.section_id = s.section_id
				and s.active_status = 'Active'
				{$system_sql}
		ORDER BY s.section_parent_id, s.sequence_no
			";

		$st = $this->db->prepare($query);
		$st->execute(array($agent_id));
		$sections = array();
		while($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$sections[$row->company_id][$row->section_id] = $row;
		}

		return $sections;
	}

	/**
	 * @TODO change this method and it's callers to camel case
	 */
	public function Get_Company_Agent_Allowed_Sections($agent_id, $company_id)
	{
		$system_sql = ($this->system_id == NULL) ? "" : "and s.system_id = " . $this->system_id . " ";

		$query = "select
			acl.company_id,
			s.active_status,
			s.name,
			s.description,
			s.system_id,
			s.section_id,
			s.section_parent_id,
			s.sequence_no,
			s.level,
			acl.read_only
				from
				section s,
			agent a,
			acl,
			agent_access_group aag
				where
				a.agent_id = ?
				and a.active_status = 'Active'
				and a.agent_id = aag.agent_id
				and aag.active_status = 'Active'
				and aag.access_group_id = acl.access_group_id
				and acl.active_status = 'Active'
				and acl.section_id = s.section_id
				and acl.company_id = ?
				and s.active_status = 'Active'
				{$system_sql}
		";


		$st = $this->db->prepare($query);
		$st->execute(array($agent_id, $company_id));
		$sections = array();
		while($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$sections[$row->section_id] = $row;
		}

		return $sections;
	}

	public function getAllControlOptions()
	{
		$option_list = ECash::getFactory()->getReferenceList('ControlOption', $this->db);

		$control_option = array();
		foreach($option_list as $row)
		{
			$control_option[$row->control_option_id] = $row;
		}

		return $control_option;
	}

	public function getAllAccessGroupControlOptions()
	{
		$query = "
			select
			access_group_id,
			control_option_id
				from
				access_group_control_option
				";

		$st = $this->db->prepare($query);
		$st->execute();

		$all = array();

		while($row = $st->fetch(PDO::FETCH_OBJ))
		{

			if (!isset($all[$row->access_group_id]))
			{
				$all[$row->access_group_id] = array(
						'access_group_id' => (int)$row->access_group_id,
						'sections' => array(),
						);
			}

			$all[$row->access_group_id]['sections'][] = $row->control_option_id;

		}

		$all = array_values($all);

		return $all;

	}

	public function addAccessGroupControlOptions($access_group_id, $used_options)
	{

		$agco_list = new DB_Models_ModelList_1(
			ECash::getFactory()->getModel('AccessGroupControlOption', $this->db),
			$this->db
		);

		foreach($used_options as $key => $value)
		{
			$agco = ECash::getFactory()->getModel('AccessGroupControlOption', $this->db);
			$agco->date_modified = time();
			$agco->date_created = time();
			$agco->access_group_id = $access_group_id;
			$agco->control_option_id = $value;

			$agco_list->add($agco);
		}

		$agco_list->save();

		return TRUE;
	}

	public function Get_Control_Info($agent_id, $company_id)
	{
		$query = "
			SELECT name_short
			FROM control_option AS co
			JOIN access_group_control_option AS agco ON (agco.control_option_id = co.control_option_id)
			JOIN agent_access_group AS aag ON (aag.access_group_id = agco.access_group_id)
			JOIN access_group AS ag ON (ag.access_group_id = aag.access_group_id)
			WHERE ag.company_id = ?
			AND aag.agent_id = ?
			";
		$names = $this->db->querySingleColumn($query, array($company_id, $agent_id));
		return $names;
	}

	/**
	 * Adds an agent to a group
	 *
	 * @param integer $agent_id	agent_id to associate
	 * @param integer $group_id	group_id to associate
	 *
	 * @access public
	 * @throws MySQL_Exception
	 * @return boolean		TRUE if the add was successful
	 */
	public function addAgentToGroup($agent_id, $group_id)
	{
		$query = "insert into agent_access_group
			(date_created, active_status,
			 company_id,
			 agent_id, access_group_id)
			values
			(now(), 'Active',
			 (select company_id from access_group where access_group_id = ?),
			 ?, ?)
			";

		$st = $this->db->queryPrepared($query, array($group_id, $agent_id, $group_id));
		return TRUE;
	}

	/**
	 * Remove an agent to a group
	 *
	 * @param integer $agent_id
	 * @param integer $group_id
	 *
	 * @access public
	 * @throws MySQL_Exception
	 * @return boolean		TRUE if the add was successful
	 */
	public function deleteAgentFromGroup($agent_id, $group_id)
	{
		$aag = ECash::getFactory()->getModel('AgentAccessGroup', $this->db);
		$aag->agent_id = $agent_id;
		$aag->access_group_id = $group_id;
		$aag->delete();
		return TRUE;
	}

	/**
	 *
	 * @param $logged_in_agent_id  This is the loged in agent id.
	 * @param $level  This is the level and its childern to retrieve.
	 */
	public function getGroupsSections($logged_in_agent_id, $level = 0)
	{

		$system_sql = ($this->system_id == NULL) ? "" : "and section.system_id = " . $this->system_id . " ";

		$query = "
			SELECT
			DISTINCT acl.company_id
			from
			agent_access_group as aag,
							   acl,
							   section
								   where
								   aag.agent_id = ?
								   and aag.access_group_id = acl.access_group_id
								   and acl.section_id = section.section_id
								   {$system_sql}
		";

		$st = $this->db->prepare($query);
		$st->execute(array($logged_in_agent_id));

		$companies = array();

		while($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$companies[] = $row->company_id;
		}

		if(empty($companies))
		{
			$companies[] = 0;
		}
		
		$query = "
			select distinct
			acl.access_group_id as group_id,
			acl.read_only as read_only,
			section.section_id
				from
				acl,
			section
				where
				acl.section_id = section.section_id
				{$system_sql}
		and acl.company_id in (".implode(', ', $companies).")
			and section.level >= ?
			";
		$st = $this->db->queryPrepared($query, array($level));

		return $st->fetchAll(PDO::FETCH_OBJ);
	}

	/** @TODO this should be replaced with a reference list, except
	 *  the access_group_id is currently being aliased to 'group_id'
	 *  so wherever it's accessed will need to be changed as well [JustinF]
	 */
	public function getAgentsGroups()
	{
		$query = "
			select
			aag.agent_id,
			aag.access_group_id as group_id
				from
				agent_access_group aag
				";
		$st = $this->db->query($query);

		return $st->fetchAll(PDO::FETCH_OBJ);
	}

	public function fetchUserACL($agent_id, $company_id)
	{
		$user_acl = $this->getAllowedSections($agent_id);

		foreach ($user_acl as $key=>$value)
		{
			$company_array = $user_acl[$key];

			// kill this..?
			if (isset($company_array['ecash']))
			{
				unset($company_array['ecash']);
			}

			foreach ($company_array as $values)
			{
				if (isset($values->level)) $values->level -= 2;
			}
		}

		$this->unsorted_user_acl = $user_acl;
		$this->sortUserAcl($company_id);

		return TRUE;
	}

	private function sortUserAcl($company_id)
	{
		if (isset($this->unsorted_user_acl[$company_id]))
		{
			$admin_resources = new ECash_AdminResources($this->unsorted_user_acl[$company_id], 0, 0);
			$this->sorted_user_acl = $admin_resources->getTree();
		}
		else
		{
			$this->sorted_user_acl = array();
		}
	}

	/**	Given a sequence of section names, tells you whether this acl allows access to it
	 * @return boolean
	 **/
	public function Acl_Check_For_Access ($hierarchal_sequence = NULL)
	{
		if ($hierarchal_sequence == NULL) {
			return false;
		}
		if (!is_array($hierarchal_sequence)) {
			return false;
		}

		// Intialize return value
		$return_value = false;
		// Start with the root of the sorted_user_acl
		$acl = $this->sorted_user_acl;
		// Check each level given in the sequence
		while (sizeof($hierarchal_sequence) > 0)
		{
			// The current level
			$thislevel = array_shift($hierarchal_sequence);
			$return_value = false;
			//if this ACL doesn't exist, then we obviously don't have access to it!
			if ($acl != NULL)
			{
				// As long as we have a children key, permission is granted.
				$return_value = isset($acl[$thislevel]) && is_array($acl[$thislevel]) && array_key_exists('children', $acl[$thislevel]);
				// Set the ACL for the next level
				if(isset($acl[$thislevel]['children']))
				{
					$acl = $acl[$thislevel]['children'];
				}
			}
		}

		return $return_value;
	}

	public function Acl_Access_Ok($section, $company_id = NULL)
	{
		if ($company_id === NULL)
		{
			$company_id = $this->company_id;
		}

		if (!empty($this->unsorted_user_acl[$company_id]))
		{
			foreach($this->unsorted_user_acl[$company_id] as $section_obj)
			{
				if($section_obj->name === $section)
				{
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	public function Get_Section_Id($company_id, $module, $mode = NULL)
	{
		foreach ($this->sorted_user_acl as $child)
		{
			if ($child['name'] == $module)
			{
				if ($mode !== NULL)
				{
					foreach ($child['children'] as $subchild)
					{
						if ($subchild['name'] == $mode)
						{
							return (int)$subchild['section_id'];
						}
					}
				}
				else
				{
					return (int)$child['section_id'];
				}
			}
		}
		return NULL;
	}

	/**
	 * This gets an the module name and mode name of the supplied mode section_id.
	 * Typically used for queues (section_id) to find out what module applications are
	 * supposed open in when de-queued.
	 *
	 * @param $mode_section_id section_id (typically from queue->section_id)
	 * @return array [0] module name, [1] mode name
	 */
	public function getModuleAndMode($mode_section_id)
	{
		foreach($this->sorted_user_acl as $module => $module_details)
		{
			foreach($module_details['children'] as $mode => $mode_details)
			{
				if($mode_details['section_id'] == $mode_section_id)
					return array($module, $mode);
			}
		}
		return NULL;
	}

	public function Get_Mode($section_id)
	{
		die('<pre>'. print_r($this->sorted_user_acl, TRUE));

	}

	public function Get_Acl_Access($parent = NULL)
	{
		$result = array();
		if ($parent == NULL)
		{
			foreach($this->sorted_user_acl as $sorted_parent => $sorted_child)
			{
				$result[] = $sorted_parent;
			}
		}
		else
		{
			foreach($this->sorted_user_acl as $sorted_parent => $sorted_child)
			{
				if ($sorted_child['name'] == $parent)
				{
					if (isset($sorted_child['children']))
					{
						foreach($sorted_child['children'] as $sorted_child_key => $sorted_child_value)
						{
							$result[] = $sorted_child_key;
						}
					}
					else
					{
						$result = array();
					}
				}
			}
		}

		return $result;
	}

	public function Get_Acl_Name($name)
	{
		foreach($this->unsorted_user_acl as $company)
		{
			foreach($company as $value)
			{
				if ($name == $value->name)
				{
					return $value->description;
				}
			}
		}
		return $name;
	}

	public function Get_Acl_Names($array_of_names)
	{
		$result = array();
		$found = FALSE;

		foreach ($array_of_names as $name)
		{
			foreach($this->sorted_user_acl as $key => $value)
			{
				if ($key == $name)
				{
					$result[$key] = $value['name'];
					$found = TRUE;
				}
			}
		}

		if (!$found)
		{

			foreach($this->sorted_user_acl as $key => $value)
			{

				foreach ($value as $child_name => $child_value)
				{
					if (is_array($child_value) && count($child_value) > 0 )
					{
						foreach($child_value as $xxx => $yyy)
						{
							if (in_array($xxx, $array_of_names))
							{
								$result[$xxx] = $yyy['name'];
							}
						}
					}
				}

			}

		}

		return $result;
	}

	/**
	 * Get a list of all of the companies the current agent is
	 * allowed to use.
	 *
	 * @return array $company_ids
	 */
	public function getAllowedCompanyIDs()
	{
		static $company_ids;

		if(! is_array($company_ids))
		{
			$company_ids = array();
			foreach($this->unsorted_user_acl as $id => $acl)
			{
				$company_ids[] = $id;
			}
		}

		return $company_ids;
	}

	/**
	 * Get a list of all of the members of all the groups
	 * used for admin lists
	 *
	 * @return array $company_ids
	 */
	public function Get_Group_Membership($group_id = null, $active = true)
	{
		$query = "
			SELECT
			access_group_id, agent.agent_id, agent.name_first, agent.name_last, agent.login
			FROM agent_access_group AS aag
			JOIN agent USING (agent_id)
			";

		$where = '';
		if(isset($group_id))
		{
			$where .= $where ? 'AND ' : '';
			$where .= "access_group_id = '$group_id' ";
		}

		if($active)
		{
			$where .= $where ? 'AND ' : '';
			$where .= "agent.active_status = 'active' ";
		}

		if($where)
		{
			$query .= $where ? 'WHERE ' : '';
			$query .= $where;
		}

		$query .= "ORDER BY agent.login ";

		$st = $this->db->query($query);

		$group_members = array();
		while($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$group_members[$row->access_group_id][] = $row;
		}
		return $group_members;
	}
}

class Access_Denied_Exception extends Exception
{
    public function __construct($message) {
        parent::__construct($message);
    }
}

?>
