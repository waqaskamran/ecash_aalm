<?php

require_once(LIB_DIR. "form.class.php");
require_once("admin_parent.abst.php");
require_once(COMMON_LIB_DIR . "ecash_admin_resources.php");


//ecash module
class Display_View extends Admin_Parent
{
	private $agents;
	private $sections;
	private $companies;
	private $section_acl;
	private $last_agent_id;
	private $groups;
	private $master_tree;
	private $group_sections;
	private $unsorted_master_tree;
	private $agent_groups;
	private $ecash_admin_resources;


	public function __construct(ECash_Transport $transport, $module_name)
	{
		parent::__construct($transport, $module_name);
		$returned_data = ECash::getTransport()->Get_Data();
		$this->agents = $returned_data->agents;
		$this->groups = $returned_data->groups;
		$this->companies = $returned_data->companies;
		$this->group_sections = $returned_data->group_sections;
		$this->agent_groups = $returned_data->agent_groups;
		$this->unsorted_master_tree = $returned_data->unsorted_master_tree;

		$this->ecash_admin_resources = new ECash_AdminResources($this->unsorted_master_tree, $this->display_level, $this->display_sequence);

		// get the last agent id if it exists
		if (!empty($returned_data->last_agent_id))
		{
			$this->last_agent_id = $returned_data->last_agent_id;
		}
		else
		{
			$this->last_agent_id = 0;
		}
	}



	/**
	 *
	 */
	public function Get_Header()
	{
		$fields = new stdClass();
		$fields->agent_count = count($this->agents);

		$fields->agents = "[";

		// get agent info
		$temp_agent = '';
		reset($this->agents);
		foreach ($this->agents as $agent)
		{
			$fields->agents .= "\n{agent_id:'" . $agent->agent_id . "', agent_login:'" . addslashes($agent->login)
			. "', name_first:'" . addslashes($agent->name_first) . "', name_last:'" . addslashes($agent->name_last) . "', groups:[";

			reset($this->agent_groups);
			foreach($this->agent_groups as $key)
			{
				if ($agent->agent_id == $key->agent_id)
				{
					$fields->agents .= "\n{id:'" . $key->group_id . "'},";
				}
			}

			$fields->agents .= "]},";
		}
		$fields->agents .= "];";
		
		// get companies
		$fields->companies = "[";
		reset($this->companies);
		foreach($this->companies as $value)
		{
			$fields->companies .= "{id:'" . $value->company_id . "', desc:'" . addslashes($value->name) . "'},";
		}
		$fields->companies .= "];";

		// get company groups
		$fields->groups = "[";
		reset($this->groups);
		foreach ($this->groups as $group)
		{
			$fields->groups .= "\n{group_id:'" . $group->group_id . "', group_name:'" . addslashes($group->name)
			. "', company_id:'" . $group->company_id . "', sections:[";

			reset($this->group_sections);
			foreach($this->group_sections as $group_section)
			{
				if ($group_section->group_id == $group->group_id)
				{
					$fields->groups .= "\n\t\t\t\t\t\t\t\t{id:'". $group_section->section_id . "'}, ";
				}
			}

			$fields->groups .= "]},\n";
		}
		$fields->groups .= "]";

		//  sort the master keys
		$sorted_master_keys = $this->ecash_admin_resources->Get_Sorted_Master_Tree();

		// set the master checkbox tree
		$fields->master_tree = '[';
		$temp_head = -1;
		$array_parents = array();
		reset($sorted_master_keys);
		foreach ($sorted_master_keys as $element => $key)
		{
			reset($this->unsorted_master_tree);
			foreach ($this->unsorted_master_tree as $element)
			{
				if ($key == $element->section_id)
				{
					// start tree
					if ($element->sequence_no == $this->display_sequence && $element->level == $this->display_level)
					{
						$temp_head = $element->section_parent_id;
						array_push($array_parents, $temp_head);
						$temp_head = $element->section_id;
						array_push($array_parents, $temp_head);
						$fields->master_tree .= "{id:'". $element->section_id ."', section_desc:'" . $element->description . "', sections:[";
					}
					else if ($element->section_parent_id == $temp_head) // chldren
					{
						$temp_head = $element->section_id;
						array_push($array_parents, $temp_head);

						$fields->master_tree .= "\n";
						for ($a = 0; $a < count($array_parents); $a++)
						{
							$fields->master_tree .= "\t";
						}

						$fields->master_tree .= "{id:'". $element->section_id ."', section_desc:'" . $element->description . "', sections:[";
					}
					else if ($element->section_parent_id != $temp_head) // new node
					{
						$count = 0;
						while ($element->section_parent_id != $temp_head && (count($array_parents) > 0))
						{
							$temp_head = $array_parents[count($array_parents) - 1];
							array_pop($array_parents);
							$count++;
						}

						for ($a = 1; $a < $count; $a++)
						{
							$fields->master_tree .= "]},";
						}

						$fields->master_tree .= "\n";
						for ($a = 0; $a < count($array_parents); $a++)
						{
							$fields->master_tree .= "\t\t\t";
						}

						$fields->master_tree .= "{id:'" . $element->section_id . "', section_desc:'" . $element->description . "', sections:[";

						$temp_head = $element->section_parent_id;
						array_push($array_parents, $temp_head);
						$temp_head = $element->section_id;
						array_push($array_parents, $temp_head);
					}
				}
			}
		}
		for ($a=1; $a<sizeof($array_parents); $a++)
		{
			$fields->master_tree .= "]}";
		}
		$fields->master_tree .= "]";

		$fields->last_agent_id = $this->last_agent_id;

		$js = new Form(ECASH_WWW_DIR.'js/admin_privs.js');

		return parent::Get_Header() . $js->As_String($fields);
	}



	private function _Add_The_Child(&$fields )
	{

	}



	public function Get_Module_HTML()
	{
		switch ( ECash::getTransport()->Get_Next_Level() )
		{
			case 'default':
			default:
			$fields = new stdClass();
			$fields->agent_count = count($this->agents);
			reset($this->agents);
			foreach ( $this->agents as $agent )
			{
				$fields->exisiting_agent_list .= "<option value='" . $agent->agent_id . "'>" . $agent->login . "</option>";
				if($agent->active_status == 'active')
				{
					$fields->exisiting_active_agent_list .= "<option value='" . $agent->agent_id . "'>" . $agent->login . "</option>";
				}
			}

			// company list
			$company_id = -1;
			$first_id = true;
			$fields->add_company_list = "";
			reset($this->companies);
			foreach($this->companies as $value)
			{
				$fields->add_company_list .= "<option value='" . $value->company_id . "'>" . $value->name . "</option>";

				if ($first_id)
				{
					$company_id = $value->company_id;
					$first_id = false;
				}
			}

			// company groups
			$fields->add_company_group_list = "";
			reset($this->groups);
			foreach($this->groups as $elements)
			{
				if ($company_id == $elements->company_id)
				{
					$elements->name = stripslashes($elements->name);
					$fields->add_company_group_list .= "<option value='" . $elements->group_id . "'>" . $elements->name . "</option>";
				}
			}

			$form = new Form(CLIENT_MODULE_DIR.$this->module_name."/view/admin_privs.html");

			return $form->As_String($fields);
		}
	}
}

?>
