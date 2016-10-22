<script type='text/javascript'>
// SCRIPT FILE admin_privs.js

// global variables set by the php script. 
// this is how i pass data to the form.
var agents = %%%agents%%%;
var groups = %%%groups%%%;
var companies = %%%companies%%%;
var last_agent_id = %%%last_agent_id%%%;
var master_tree = %%%master_tree%%%;


var ADD_PRIVS = 'add_privs';
var DELETE_PRIVS = 'delete_privs';
var FIELD_AGENT_SECTION = 'Agent';
var FIELD_GROUP_SECTION = 'Groups';
var FIELD_AGENT_GROUP_SECTION = 'Agent Group';
var FIELD_AGENT_COMPANY_SECTION = 'Agent Company';
var FIELD_AGENT_GROUP_EMPTY = 'Agent Privs';
var MESG_GROUP_SELECTION = 'You must select a group to add';
var MESG_AGENT_SELECTION = 'You must select an agent to add the group to';
var MESG_GROUP_SELECTION_EXISTS = 'This group is already associated with the agent';
var MESG_AGENT_COMPANY_SELECTION = 'You must select an agent company';
var MESG_AGENT_GROUP_SELECTION = 'You must select an agent group to remove';
var MESG_AGENT_GROUP_EMPTY = 'There are no groups to remove from this agent';
var MESG_AGENT_GROUP_WILL_BE_EMPTY = 'If this group is removed, this agent will have no access to the system until a group is added.  Proceed?';

function copy_agent_list(from_agent_list)
{
	var index = 0;
	var agent_list = document.getElementById('agent_list');

	for (var end = from_agent_list.options.length; index < end; index++)
	{
		var new_option   = document.createElement('option');
		new_option.text  = from_agent_list.options[index].text;
		new_option.value = from_agent_list.options[index].value;

		// Add it to the list
		try
		{
			agent_list.add(new_option, null); // Standards compliant, broken in IE
		}
		catch(ex)
		{
			agent_list.add(new_option, index); // Should work fine for IE
		}
	}
}

function clear_agent_list()
{
	var agent_list = document.getElementById('agent_list');

	for (var end = agent_list.length; end >= 0; --end)
	{
		agent_list.remove(end);
	}

	return;
}


function get_agent_index(agent_id)
{
	for(var x = 0; x < agents.length; x++)
	{
		if(agents[x]['agent_id'] == agent_id)
		{
			return x;
		}
	}

	return 0;
}

function toggle_agents_list()
{
	var toggle = document.getElementById('agents_toggle');

	var old_agent_id = -1;

	// Get the old agent_id
	if (document.priv_info.agent_list.selectedIndex != -1)
	{
		old_agent_id = document.priv_info.agent_list.options[document.priv_info.agent_list.selectedIndex].value;
	}

	// Clear out the old agent list
	clear_agent_list();	

	if(toggle.checked == true)
	{
		copy_agent_list(document.getElementById('agent_list_active'));
	}else{
		copy_agent_list(document.getElementById('agent_list_all'));
	}

	// Nothing was selected before
	if (old_agent_id == -1)
		return;

	var found = 0;

	// See if we can locate the old selected agent_id in the new list
	for (var i = 0; i < document.priv_info.agent_list.options.length; i++)
	{
		if (document.priv_info.agent_list.options[i].value == old_agent_id)
		{
			document.priv_info.agent_list.options.selectedIndex = i;
			found = 1;
			break;
		}
	}

	if (found == 0)
	{
		// Not found in new list, clear out all the old fields
		// If they disappear from the list, they won't have any information but
		// the name_first and name_last
		_clear_agent_group_list();
		_clear_agent_privs();
		document.priv_info.name_first.value = "";
		document.priv_info.name_last.value  = "";	
	}
}


/**
 *
 */
function display_agent(agent_id)
{
	var agent_index = get_agent_index(agent_id, 'name_last');

	_clear_agent_privs();

	//agent_index = document.priv_info.agent_list.selectedIndex;

	// clear the action so that a submit just brings us right back
	document.priv_info.action.value = '';

	// get agent info
	document.priv_info.name_first.value	= agents[agent_index]['name_first'];
	document.priv_info.name_last.value = agents[agent_index]['name_last'];

	_display_agent_companies(agent_index);
}




/**
 *
 */
function _clear_agent_privs()
{
	document.priv_info.agent_company_list.selectedIndex = -1;

	// clear agent company list
	while (document.priv_info.agent_company_list.length > 0)
	{
		document.priv_info.agent_company_list.options[document.priv_info.agent_company_list.length - 1] = null;
	}

	document.priv_info.agent_group_list.selectedIndex = -1;

	// clear agent group list
	while (document.priv_info.agent_group_list.length > 0)
	{
		document.priv_info.agent_group_list.options[document.priv_info.agent_group_list.length - 1] = null;
	}
}



/**
 *
 */
function get_company_groups()
{
	var selected_company;
	var company_id;
	var has_groups;

	// empty the group list
	while (document.priv_info.group_list.length > 0)
	{
		document.priv_info.group_list.options[document.priv_info.group_list.length - 1] = null;
	}
	// populate the groups list
	if (groups.length > 0)
	{
		// get the selected company
		selected_company = document.priv_info.all_companies_list.selectedIndex;
		company_id = document.priv_info.all_companies_list.options[selected_company].value;

		has_groups = false;
		// flip through groups  and add ones with the same company id
		for(var a = 0; a < groups.length; a++)
		{
			if (company_id == groups[a]['company_id'])
			{
				has_groups = true;
				document.priv_info.group_list.options[document.priv_info.group_list.length]
					= new Option(groups[a]['group_name'], groups[a]['group_id'], false, false);
			}
		}

		// load select and load the privs list
		if (has_groups)
		{
			document.priv_info.group_list.selectedIndex = 0;
			get_privilege_tree();
		}
		else
		{
			// empty the privilege list
			while (document.priv_info.add_privilege_list.length > 0)
			{
				document.priv_info.add_privilege_list.options[document.priv_info.add_privilege_list.length - 1] = null;
			}
		}
	}
}



/*
 *
 */
function get_privilege_tree()
{
	// gather the group information
	var selected_group_id = document.priv_info.group_list.selectedIndex;
	var group_id = document.priv_info.group_list.options[selected_group_id].value;
	var sections_array;
	for (var a = 0; a < groups.length; a++)
	{
		if (group_id == groups[a]['group_id'])
		{
			sections_array = groups[a]['sections'];
			break;
		}
	}

	// empty the privilege list
	while (document.priv_info.add_privilege_list.length > 0)
	{
		document.priv_info.add_privilege_list.options[document.priv_info.add_privilege_list.length - 1] = null;
	}

	genterate_diaplay_tree(sections_array);
}




/*
*/
function genterate_diaplay_tree(sections_array)
{
	depth = 0;
	for (var a = 0; a < master_tree.length; a++)
	{
		level_one = master_tree[a];
		level_two = level_one['sections'];

		if (is_this_a_child(level_one, sections_array, depth))
		{
			print_level(level_one['id'], level_one['section_desc'], depth);
			depth++;
			for (b = 0; b < level_two.length; b++)
			{
				level_three = level_two[b]['sections'];
				if (is_this_a_child(level_two[b], sections_array, depth))
				{
					print_level(level_two[b]['id'], level_two[b]['section_desc'], depth);
					depth++;
					for (var c = 0; c < level_three.length; c++)
					{
						level_four = level_three[c]['sections'];

						if (is_this_a_child(level_three[c], sections_array, depth))
						{
							print_level(level_three[c]['id'], level_three[c]['section_desc'], depth);
							depth++;
							for (var d = 0; d < level_four.length; d++)
							{
								if (is_this_a_child(level_four[d], sections_array, depth))
								{
									print_level(level_four[d]['id'], level_four[d]['section_desc'], depth);
								}
							}
							depth--;
						}
					}
					depth--;
				}
			}
			depth--;
		}
	}
}



/*
*/
function is_this_a_child(current_level, sections_array, depth)
{
	result = false;
	for (var b = 0; b < sections_array.length; b++)
	{
		if (current_level['id'] == sections_array[b]['id'])
		{
			result = true;
			break;
		}
	}

	return result;
}




/*
 *
 */
function print_level(id, desc, depth)
{
	insert_string = '';

	for (x = 0; x < depth; x++)
	{
		insert_string = insert_string + '--->';
	}

	document.priv_info.add_privilege_list.options[document.priv_info.add_privilege_list.length]
		= new Option(insert_string + desc, id);
}




/**
 *
 */
function _display_agent_companies(agent_index)
{
	var found_company;
	var company_id_array = new Array();

	// get all the companies for agents
	for(var a = 0; a < agents[agent_index]['groups'].length; a++)
	{
		for (var b = 0; b < groups.length; b++)
		{
			if (agents[agent_index]['groups'][a]['id'] == groups[b]['group_id'])
			{
				found_company = false;
				for (var c = 0; c < company_id_array.length; c++)
				{
					if (groups[b]['company_id'] == company_id_array[c])
					{
						found_company = true;
						break;
					}
				}

				if (!found_company)
				{
					company_id_array[company_id_array.length] = groups[b]['company_id'];
				}
			}
		}
	}

	// load the companies list
	for (var d = 0; d < company_id_array.length; d++)
	{
		for (var e = 0; e < companies.length; e++)
		{
			if (company_id_array[d] == companies[e]['id'])
			{
				document.priv_info.agent_company_list.options[document.priv_info.agent_company_list.length]
					= new Option(companies[e]['desc'], companies[e]['id'], false, false);
				break;
			}
		}
	}


	if (document.priv_info.agent_company_list.length > 0)
	{
		document.priv_info.agent_company_list.selectedIndex = 0;
		load_agent_company_groups();
	}
}



/**
 *
 */
function load_agent_company_groups()
{
	var selected_company_index = document.priv_info.agent_company_list.selectedIndex;
	var company_id = document.priv_info.agent_company_list.options[selected_company_index].value;
	var selected_agent = document.priv_info.agent_list.selectedIndex;
	var agent_id = document.priv_info.agent_list.options[selected_agent].value;
	var agent_index = get_agent_index(agent_id);

	_clear_agent_group_list();

	for (var a = 0; a < groups.length; a++)
	{
		if (groups[a]['company_id'] == company_id)
		{
			// check if agent has 
			for(var b = 0; b < agents[agent_index]['groups'].length; b++)
			{
				if (agents[agent_index]['groups'][b]['id'] == groups[a]['group_id'])
				{
					document.priv_info.agent_group_list.options[document.priv_info.agent_group_list.length]
						= new Option(groups[a]['group_name'], groups[a]['group_id'], false, false);
				}
			}
		}
	}

	if (document.priv_info.agent_group_list.length > 0)
	{
		document.priv_info.agent_group_list.selectedIndex = 0;
	}

}



/**
 *
 */
function _clear_agent_group_list()
{
	while (document.priv_info.agent_group_list.length > 0)
	{
		document.priv_info.agent_group_list.options[document.priv_info.agent_group_list.length - 1] = null;
	}
}



/**
 *
 */
function add_group_to_agent()
{
	var found;
	var group_id;
	var selected_agent_index;
	var selected_group_index;
	var agent_array = new Array();

	var selected_agent = document.priv_info.agent_list.selectedIndex;
	var agent_id = document.priv_info.agent_list.options[selected_agent].value;
	var agent_index = get_agent_index(agent_id);

	if (agent_index > -1)
	{
		agent_array = agents[agent_index]['groups'];
		selected_group_index = document.priv_info.group_list.selectedIndex;

		if (selected_group_index > -1)
		{
			group_id = document.priv_info.group_list.options[selected_group_index].value;
			found = false;
			for (var a = 0; a < agent_array.length; a++)
			{
				if(agent_array[a]['id'] == group_id)
				{
					found = true;
					break;
				}
			}

			if (!found)
			{
				document.priv_info.action.value = ADD_PRIVS;
				document.priv_info.submit();
			}
			else
			{
				_display_alert(FIELD_GROUP_SECTION, MESG_GROUP_SELECTION_EXISTS);
			}
		}
		else
		{
			_display_alert(FIELD_GROUP_SECTION, MESG_GROUP_SELECTION);
		}
	}
	else
	{
		_display_alert(FIELD_AGENT_SECTION, MESG_AGENT_SELECTION);
	}
}



/**
 *
 */
function _display_confirmation(field, mesg)
{
	return confirm("" + mesg + ".");
}


/**
 *
 */
function _display_alert(field, mesg)
{
	alert("Error Field: " + field + "\nError Message: " + mesg + ".");
}



/**
 *
 */
function remove_group_from_agent()
{
	var selected_agent_index;
	var selected_agent_company_index;
	var selected_agent_group_index;

	selected_agent_index = document.priv_info.agent_list.selectedIndex;
	if (selected_agent_index > -1)
	{
		if (document.priv_info.agent_company_list.length > 0)
		{
			selected_agent_company_index = document.priv_info.agent_company_list.selectedIndex;
			if (selected_agent_company_index > -1)
			{
				if (document.priv_info.agent_group_list.length > 0)
				{
					if (document.priv_info.agent_company_list.length > 1 
							|| document.priv_info.agent_group_list.length > 1 
							|| _display_confirmation(FIELD_AGENT_GROUP_EMPTY, MESG_AGENT_GROUP_WILL_BE_EMPTY)) 
					{

						selected_agent_group_index = document.priv_info.agent_group_list.selectedIndex;
						if (selected_agent_group_index > -1)
						{
							document.priv_info.action.value = DELETE_PRIVS;
							document.priv_info.submit();
						}
						else
						{
							_display_alert(FIELD_AGENT_GROUP_SECTION, MESG_AGENT_GROUP_SELECTION);
						}
					}
				}
				else
				{
					_display_alert(FIELD_AGENT_GROUP_EMPTY, MESG_AGENT_GROUP_EMPTY);
				}
			}
			else
			{
				_display_alert(FIELD_AGENT_COMPANY_SECTION, MESG_AGENT_COMPANY_SELECTION);
			}
		}
		else
		{
			_display_alert(FIELD_AGENT_GROUP_EMPTY, MESG_AGENT_GROUP_EMPTY);
		}
	}
	else
	{
		_display_alert(FIELD_AGENT_SECTION, MESG_AGENT_SELECTION);
	}
}




/**
 *
 */
function get_last_settings()
{
	// populate and select agent info
	if (agents.length > 0)
	{
		if (last_agent_id > 0)
		{
			for(var a = 0; a < agents.length; a++)
			{
				if (agents[a]['agent_id'] == last_agent_id)
				{
					document.priv_info.agent_list.selectedIndex = a;
				}
			}
		}
		else
		{
			document.priv_info.agent_list.selectedIndex = 0;
		}

		display_agent();
	}

	// select group info
	if (document.priv_info.group_list.length > 0)
	{
		document.priv_info.group_list.selectedIndex = 0;
		get_privilege_tree();
	}
}


</script>

