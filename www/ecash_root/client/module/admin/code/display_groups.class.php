<?php

require_once(LIB_DIR. "form.class.php");
require_once("admin_parent.abst.php");
require_once(COMMON_LIB_DIR . "ecash_admin_resources.php");

//ecash module
class Display_View extends Admin_Parent
{	
	private $companies;
	private $unsorted_master_tree;
	private $sorted_master_keys;
	private $groups;
	private $group_sections;
	private $last_group_id;
	private $last_action;
	private $ecash_admin_resources;
	private $access_group_control_options;
	private $control_options;



	/**
	 *
	 */
	public function __construct(ECash_Transport $transport, $module_name)
	{
		parent::__construct($transport, $module_name);
		$returned_data = ECash::getTransport()->Get_Data();

		$this->companies = $returned_data->companies;
		$this->groups = $returned_data->groups;
		$this->group_sections = $returned_data->group_sections;
		$this->unsorted_master_tree = $returned_data->master_tree;
		$this->access_group_control_options = $returned_data->access_group_control_options;
		$this->control_options = $returned_data->control_options;
		$this->agents = $returned_data->memberships;
		$this->message = $returned_data->message;


		$this->ecash_admin_resources = new ECash_AdminResources($this->unsorted_master_tree, $this->display_level, $this->display_sequence);

		// get the last agent id if it exists
		if (!empty($returned_data->last_group_id))
		{
			$this->last_group_id = $returned_data->last_group_id;
		}
		else
		{
			$this->last_group_id = 0;
		}

		// get the last acl action if it exists
		if (!empty($returned_data->last_action))
		{
			$this->last_action = "'" . $returned_data->last_action . "'";
		}
		else
		{
			$this->last_action = "'" . 'add_groups' . "'";
		}
	}



	/**
	 *
	 */
	public function Get_Header()
	{
		$fields = new stdClass();
		$fields->group_count = count($this->groups);
		$fields->message = $this->message;
	
		$fields->groups = "[";

		$myGroups = $this->groups;
		usort($myGroups, array("Display_View", "cmp_obj"));

		foreach ( $myGroups as $group )
		{
			$fields->groups .= "\n{group_id:'" . $group->group_id
			. "', group_name:'" .  addslashes($group->name)
			. "', company_id:'" . $group->company_id
			. "', is_used:'" . $group->is_used
			. "', sections:[";
	
			reset($this->group_sections);
			foreach($this->group_sections as $key)
			{
				if ($key->group_id == $group->group_id)
				{
					$fields->groups .= "\n\t\t\t\t\t\t\t\t\t{id:'" . $key->section_id . "'}, ";
				}
			}

			$fields->groups .= "],\n";
			$fields->groups .= " agents:[";
			if (isset($this->agents[$group->group_id]))
			{
				foreach($this->agents[$group->group_id] as $agent)
				{
					$fields->groups .= "\n\t\t{"
					. "id:'{$agent->agent_id}', "
					. "first_name:'" . addslashes($agent->name_first) . "', "
					. "last_name:'" . addslashes($agent->name_last) . "', "
					. "login:'" . addslashes($agent->login) . "'}, ";
				}
			}
			$fields->groups .= "]},\n";
		}
		$fields->groups .= "]";

		$this->sorted_master_keys = $this->ecash_admin_resources->Get_Sorted_Master_Tree();

		$fields->sorted_master_keys = '[';

		foreach($this->sorted_master_keys as $key => $value)
		{
			$fields->sorted_master_keys .= "{value:'" . $value . "'},";
		}
		$fields->sorted_master_keys .= "]";

		$fields->all_group_sections = '[';
		reset($this->group_sections);
		foreach($this->group_sections as $key)
		{
			$fields->all_group_sections .= "{ group_id:'" . $key->group_id . "', section_id:'" . $key->section_id ."', read_only: ".($key->read_only ? 1 : 0).",},";
		}
		$fields->all_group_sections .= "]";

		$sorted_master_tree = $this->Format_Sorted_Master_Tree();

		$fields->sorted_master_tree = $sorted_master_tree;
		$fields->last_group_id = $this->last_group_id;
		$fields->last_action = $this->last_action;

		$fields->all_control_options = '[';
		foreach($this->control_options as $key)
		{
			$fields->all_control_options .= "{ control_option_id:'" . $key->control_option_id . "',  name: '" . $key->name . "', ";
			$fields->all_control_options .= " description:'" . $key->description . "', field: '" . $key->type . "'},\n ";
		}
		$fields->all_control_options .= "]";

		$fields->all_access_group_control_options = '[';
		foreach($this->access_group_control_options as $key => $value)
		{
			$temp_group_id = -1;
			foreach ($value as $value_key => $value_value)
			{
				if ($value_key == 'access_group_id')
				{
					$temp_group_id = $value_value;
				}
				else
				{
					foreach ($value_value as $section_key => $section_value)
					{
						$fields->all_access_group_control_options .= "{ group_id:'" . $temp_group_id
						. "',  control_option_id: '" . $section_value . "'},\n ";
					}
				}
			}
		}
		$fields->all_access_group_control_options .= "]";

		// set all companies list
		$fields->companies = "[";
		foreach ( $this->companies as $comp )
		{
			$fields->companies .= "{company_id:'" . $comp->company_id . "', name:'" . $comp->name
			. "', name_short:'" . $comp->name_short . "', property_id:'" . $comp->property_id . "'},\n";
		}
		$fields->companies .= "]";
	
		$js = new Form(ECASH_WWW_DIR.'js/admin_groups.js');

		return parent::Get_Header() . $js->As_String($fields);
	}



	/**
	 *
	 */
	private function Format_Sorted_Master_Tree()
	{
		$master_tree = "[";

		$array_parents = array();
		reset($this->sorted_master_keys);
		foreach ($this->sorted_master_keys as $element => $key)
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
						$master_tree .= "{id:'". $element->section_id ."', section_desc:'" . $element->description . "', sections:[";
					}
					else if ($element->section_parent_id == $temp_head) // chldren
					{
						$temp_head = $element->section_id;
						array_push($array_parents, $temp_head);
	
						$master_tree .= "\n";
						for ($a = 0; $a < count($array_parents); $a++)
						{
							$master_tree .= "\t";
						}

						$master_tree .= "{id:'". $element->section_id ."', section_desc:'" . $element->description . "', sections:[";
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
							$master_tree .= "]},";
						}

						$master_tree .= "\n";
						for ($a = 0; $a < count($array_parents); $a++)
						{
							$master_tree .= "\t\t\t";
						}
						$master_tree .= "{id:'" . $element->section_id . "', section_desc:'" . $element->description . "', sections:[";

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
			$master_tree .= "]}";
		}
		$master_tree .= "]\n";
		return $master_tree;
	}


	/**
	 * Sorts a multi-dimentional array. Stolen directly from php.net [BS]
	 */
    private static function cmp_obj($a, $b) {
        $al = strtolower($a->name);
        $bl = strtolower($b->name);
        if ($al == $bl) {
            return 0;
        }
        return ($al > $bl) ? +1 : -1;
    }

	/**
	 *
	 */
	public function Get_Module_HTML()
	{
		switch ( ECash::getTransport()->Get_Next_Level() )
		{
			case 'default':
			default:
				$fields = new stdClass();
				$fields->group_count = count($this->groups);
				$fields->message = $this->message;
				
				/* Sorts the array on the group name */
				$myGroups = $this->groups;
				usort($myGroups, array("Display_View", "cmp_obj"));

				foreach ( $myGroups as $group ) {
					$fields->group_select_list .= "<option value='{$group->group_id}' onmouseover=\"return overlib(get_agent_list_html(".$group->group_id."), LEFT, RIGHT);\" onmouseout=\"return nd();\">"
					. " {$group->name}</option>\n";
				}

				// set all companies list
				foreach ( $this->companies as $comp )
				{

					$fields->all_companies_list .= "<option value='" . $comp->company_id . "'";
					if (ECash::getTransport()->company_id == $comp->company_id ) 
					{
						$fields->all_companies_list .= " selected";
					}
					$fields->all_companies_list .= ">" . $comp->name . "</option>";
				}

				// set the master checkbox tree
				$fields->master_tree = "";

				$array_parents = array();
				reset($this->sorted_master_keys);
				foreach ($this->sorted_master_keys as $element => $key)
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
	
								$fields->master_tree .= "<input type='checkbox' name='section_" . $element->section_id
								."', id='". $element->section_id
								. "' onChange='javascript:reconcile_tree_state(this.checked);'><b>"
								. $element->description . '</b>';
								if($element->read_only_option)
								{
									$fields->master_tree .= "&nbsp;&nbsp;&nbsp;&nbsp;(<input type='checkbox' name='read_only_".$element->section_id."' id='read_only_".$element->section_id."'> <i>read only</i>)";
								}
								$fields->master_tree .= '<br/>';
							}
							else if ($element->section_parent_id == $temp_head) // chldren
							{
								$temp_head = $element->section_id;
								array_push($array_parents, $temp_head);

								for ($a = 2; $a < count($array_parents); $a++)
								{
									$fields->master_tree .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
								}

								$fields->master_tree .= ""
								. "<input type='checkbox' name='section_" . $element->section_id ."', id='" . $element->section_id
								. "' onChange='javascript:reconcile_tree_state(this.checked);'>"
								. $element->description;
								if($element->read_only_option)
								{
									$fields->master_tree .= "&nbsp;&nbsp;&nbsp;&nbsp;(<input type='checkbox' name='read_only_".$element->section_id."' id='read_only_".$element->section_id."'> <i>read only</i>)";
								}
								$fields->master_tree .= '<br/>';
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

								for ($a = 0; $a < count($array_parents); $a++)
								{
									$fields->master_tree .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
								}

								if ($element->section_parent_id == $this->display_level)
								{
									$fields->master_tree .= "<input type='checkbox' name='section_" . $element->section_id ."', id='"
									. $element->section_id . "' onChange='javascript:reconcile_tree_state(this.checked);'> <b>"
									. $element->description
									. '</b>';
									if($element->read_only_option)
									{
										$fields->master_tree .= "&nbsp;&nbsp;&nbsp;&nbsp;(<input type='checkbox' name='read_only_".$element->section_id."' id='read_only_".$element->section_id."'> <i>read only</i>)";
									}
									$fields->master_tree .= '<br/>';
								}
								else
								{
									$fields->master_tree .= "<input type='checkbox' name='section_" . $element->section_id ."', id='"
									. $element->section_id . "' onChange='javascript:reconcile_tree_state(this.checked);'>"
									. $element->description;
									if($element->read_only_option)
									{
										$fields->master_tree .= "&nbsp;&nbsp;&nbsp;&nbsp;(<input type='checkbox' name='read_only_".$element->section_id."' id='read_only_".$element->section_id."'> <i>read only</i>)";
									}
									$fields->master_tree .= '<br/>';
								}

								$temp_head = $element->section_parent_id;
								array_push($array_parents, $temp_head);
								$temp_head = $element->section_id;
								array_push($array_parents, $temp_head);
							}	
						}
					}
				}

				$fields->unused_options .=  '<select name="unused_options" id="unused_options_id" style="width:240px;" size="3" onChange="javascript:get_unused_description();">';
				foreach ($this->control_options as $key)
				{
					$fields->unused_options .= "<option value='{$key->control_option_id}'>{$key->name}</option>";
				}
				$fields->unused_options .=  '</select>';
				$fields->used_options .=  '<select name="used_options[]" id="used_options_id" style="width:240px;" size="3" onChange="javascript:get_used_description();"></select>';

				$fields->option_description .= '<textarea rows="2" cols="26" name="option_description" ></textarea>';

				$form = new Form(CLIENT_MODULE_DIR.$this->module_name."/view/admin_groups.html");
				return $form->As_String($fields);
		}
	}
}

?>
