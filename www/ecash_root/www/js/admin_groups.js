<script type="text/javascript" src='js/layout.js'></script>
<script type='text/javascript'>
// SCRIPT FILE admin_groups.js

// global variables set by the php script.
// this is how i pass data to the form.
var groups = %%%groups%%%;
var master_tree = %%%sorted_master_tree%%%;
var master_tree_ids = %%%sorted_master_keys%%%;
var last_group_id = %%%last_group_id%%%;
var last_action = %%%last_action%%%;
var group_sections = %%%all_group_sections%%%;
var control_options = %%%all_control_options%%%;
var access_group_control_options = %%%all_access_group_control_options%%%;
var companies = %%%companies%%%;

// global constants
var ADD_GROUPS = 'add_groups';
var DELETE_GROUPS = 'delete_groups';
var MODIFY_GROUPS = 'modify_groups';
var COPY_GROUPS = 'copy_groups';
var FIELD_GROUP_NAME = 'Group Name';
var FIELD_GROUP_STRUCTURE = 'Group Structure';
var MESG_EMPTY = 'This field cannot be empty';
var MESG_LONG_LENGTH = "You must limit this field to 50 characters";
var MESG_GROUP_EXISTS = 'You must enter a unique group name. This group name already exists';

// Go through and set each field label to its normal text
function reset_fields_status()
{
    for (field in admin_group_field_array)
    {
        if (document.getElementById(field + '_span'))
        {
            document.getElementById(field + '_span').className = "std_text";
        }
    }
}

// Make sure only valid characters are in the field values
function validate_group_info()
{
    var focusobj = null;
    var isvalid = validate_fields(admin_group_field_array,'std_text','error_text');

    return isvalid;
}

var admin_group_field_array = {
'group_company_name' : /^[a-zA-Z0-9\-\._]{2,30}$/,
'group_name' : /^[ a-zA-Z0-9\-\._]{2,30}$/
};


/**
 *
 */
function display_group()
{
	reset_fields_status();
	_clear_section_tree();
	group_index = document.group_info.groups.selectedIndex;
	document.group_info.action.value = '';

	if (document.group_info.commit.value == 'Remove Group' && groups[group_index]['is_used'] == 1 ) {
		document.group_info.groups.blur();
	}
	_display_info(group_index);
	fill_unused_and_remove_used_options();
	set_the_company();
	set_the_company_dest();

}




/**
 *
 */
function _display_info(group_index)
{
	var group_id = groups[group_index]['group_id'];

	document.group_info.company.value = groups[group_index]['company_id'];

	// group
	document.group_info.group_name.value = groups[group_index]['group_name'];

	//get the company name_short
	company_id = groups[group_index]['company_id'];
	company_name_short = 'UNKNOWN';
	for (c = 0; c < companies.length; c++)
	{
		if (companies[c]['company_id'] == groups[group_index]['company_id'])
		{
			company_name_short = companies[c]['name_short'];
			break;
		}
	}

	document.group_info.group_name.value = groups[group_index]['group_name'].replace(company_name_short.toUpperCase() + " ", "");

	// group sections
   if (group_sections.length > 0)
   {
		for (var i = 0; i < group_sections.length; i++)
		{
			if (group_sections[i].group_id == group_id && document.getElementById(group_sections[i].section_id))
			{
				document.getElementById(group_sections[i].section_id).checked = true;
				if (group_sections[i]['read_only'] == 1)
				{
					document.getElementById('read_only_'+group_sections[i].section_id).checked = true;
				}
			}
		}
   }
}

var ol_textcolor = "black";
var ol_textfont = "Arial, Helvetica, sans-serif";
var ol_offsetx = 200;
var ol_offsety = 10;
var ol_width = 300;
var ol_background = "image/standard/overlib_bg.png";

function setHeight()
{
    var docHeight=100;
    var docWidth=100;
    if (document.documentElement && document.documentElement.scrollHeight)
    {
        docWidth = document.scrollWidth;
        docHeight = document.documentElement.scrollHeight;
    }
    if (document.body)
    {
        docWidth = document.body.scrollWidth;
        docHeight = document.body.scrollHeight;
    }
    self.resizeTo((docWidth),(docHeight));
}


function get_agent_list_html(group_id) 
{
	var agent_list = [];
	var group = {};
	var result = '';
	for (var i = 0; i < groups.length; i ++) 
	{
		if (groups[i].group_id == group_id) 
		{
			group = groups[i];
			agent_list = groups[i].agents;
		}
	}
	result += '<div style="border:solid black 1px;text-align:left;padding:5px;">' + agent_list.length + ' members in Group ' + group.group_name + ":<ol>\n";
	
	for (i = 0; i < agent_list.length; i ++)
	{
		var agent = agent_list[i];
		result += '<li>(' + agent.id + ') ';
		result += agent.login;
		result += ' (' + agent.first_name + ' ' + agent.last_name + ")</li>";
	}
	result += '</ol></div>';
	return result;
}


/**
 *
 */
function get_last_settings()
{
	document.group_info.group_company_name.disabled = true;


	if (last_action == ADD_GROUPS )
	{
		document.group_info.add_groups_radio_btn.checked = true;
		radio_btn_actions(ADD_GROUPS);
	}
	else if (last_action == MODIFY_GROUPS)
	{
		document.group_info.mod_groups_radio_btn.checked = true;
		radio_btn_actions(MODIFY_GROUPS);

		if (last_group_id > 0)
		{
			document.group_info.mod_groups_radio_btn.checked = true;
			radio_btn_actions(MODIFY_GROUPS);

			// loop through groups
			for (var a = 0;  a < groups.length; a++)
			{
				if (groups[a]['group_id'] == last_group_id)
				{
					document.group_info.groups.selectedIndex = a;
					display_group();
					break;
				}
			}
		}
	}
	else if (last_action == COPY_GROUPS)
	{
		document.group_info.copy_groups_radio_btn.checked = true;
		radio_btn_actions(COPY_GROUPS);
	}
	else
	{
		document.group_info.remove_groups_radio_btn.checked = true;
		radio_btn_actions(DELETE_GROUPS);
	}
}


function set_the_company()
{
	for (a = 0; a < companies.length; a++)
	{
		if (companies[a]['company_id'] ==  document.group_info.company.value)
		{
			document.group_info.group_company_name.value = companies[a]['name_short'].toUpperCase();
		}
	}
}

function set_the_company_dest()
{
	for (a = 0; a < companies.length; a++)
	{
		if (companies[a]['company_id'] ==  document.group_info.dest_company.value)
		{
			document.group_info.group_dest_company_name.value = companies[a]['name_short'].toUpperCase();
		}
	}
}



function reconcile_tree_state(checked) {
	if (checked) {
		check_parent_is_properly_checked();
	} else {
		uncheck_children_if_parent_is_unchecked(master_tree, true);
	}
}

/*
 *
 */
function uncheck_children_if_parent_is_unchecked(tree, parent_state)
{
	for (var a = 0; a < tree.length; a++)
	{
		if (!parent_state) {
			document.getElementById(tree[a]['id']).checked = false;
		}
		if (tree[a]['sections']) {
			uncheck_children_if_parent_is_unchecked(tree[a]['sections'], document.getElementById(tree[a]['id']).checked);
		}
	}
}

/*
 *
 */
function check_parent_is_properly_checked()
{
	path_array = new Array();
	for (var a = 0; a < master_tree.length; a++)
	{
		level_one = master_tree[a];
		path_array.push(level_one['id']);
		select_necessary_parents(level_one['id'], path_array);
		level_two = level_one['sections'];
		for (b = 0; b < level_two.length; b++)
		{
			path_array.push(level_two[b]['id']);
			select_necessary_parents(level_two[b]['id'], path_array);
			level_three = level_two[b]['sections'];
			for (var c = 0; c < level_three.length; c++)
			{
				path_array.push(level_three[c]['id']);
				select_necessary_parents(level_three[c]['id'], path_array);
				level_four = level_three[c]['sections'];
				for (var d = 0; d < level_four.length; d++)
				{
					path_array.push(level_four[d]['id']);
					select_necessary_parents(level_four[d]['id'], path_array);
					path_array.pop();
				}
				path_array.pop();
			}
			path_array.pop();
		}
		path_array.pop();
	}
}


/*
 */
function select_entire_tree(tree)
{
	for (var a = 0; a < tree.length; a++)
	{
		document.getElementById(tree[a]['id']).checked = true;
		parent_tree = tree[a];
		child_tree = parent_tree['sections'];
		select_entire_tree(child_tree);
	}
}




/*
 */
function deselect_entire_tree(tree)
{
	for (var a = 0; a < tree.length; a++)
	{
		document.getElementById(tree[a]['id']).checked = false;
		if(undefined != document.getElementById('read_only_'+tree[a]['id']))
		{
			document.getElementById('read_only_'+tree[a]['id']).checked = false;
		}
		parent_tree = tree[a];
		child_tree = parent_tree['sections'];
		deselect_entire_tree(child_tree);
	}
}



/*
 */
function select_necessary_parents(current_level_id, path_array)
{
	if (document.getElementById(current_level_id).checked)
	{
		for (e = 0; e < path_array.length; e++)
		{
			document.getElementById(path_array[e]).checked = true;
		}
	}
}




/**
 *
 */
function _confirm_group_doesnt_exists(action)
{
	var result = false;

	if (action == MODIFY_GROUPS)
		return false;

	if(action == COPY_GROUPS)
	{
		var new_group_name = document.group_info.group_dest_company_name.value.toUpperCase() + " " +  document.group_info.group_name.value;
		var selected_index = -1;
	}
	else
	{
		var new_group_name = document.group_info.group_company_name.value.toUpperCase() + " " +  document.group_info.group_name.value;
		var selected_index = document.group_info.groups.selectedIndex;
	}
	

	for (var a = 0; a < groups.length; a++)
	{
		if (selected_index != a)
		{
			if (groups[a]['group_name'].toLowerCase() == new_group_name.toLowerCase())
			{
				result = true;
				break;
			}
		}
	}

	return result;
}





/**
 *
 */
function _confirm_field_length()
{
	var result = false;

	if (document.group_info.group_name.value.length <= 50)
	{
		result = true;
	}

	return result;
}




/**
 *
 */
function _confirm_group_name_doesnt_exist()
{
	var result = true;

	for (var i in groups)
	{
		if (document.group_info.group_name.value == groups[i]['group_name'])
		{
			result = false;
		}
	}

	return result;
}





/**
 *
 *
 */
function save()
{
	document.group_info.commit.disabled = true;
	if (document.group_info.commit.value == 'Add Group')
	{
		_add_modify_groups(ADD_GROUPS);
	}
	else if (document.group_info.commit.value == 'Modify Group')
	{
		_add_modify_groups(MODIFY_GROUPS);
	}
	else if (document.group_info.commit.value == 'Copy Group')
	{
		_add_modify_groups(COPY_GROUPS);
	}
	else
	{
		_delete_group();
	}

	document.group_info.commit.disabled = false;
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
function _are_fields_populated(action)
{
	var result = false;

	if (document.group_info.group_name.value != '')
	{
		result = true;
	}

	return result;
}



/**
 *
 */
function _are_sections_selected()
{
	var result = false;

	for (var a = 0; a < master_tree_ids.length; a++)
	{
		if (document.getElementById(master_tree_ids[a]['value']).checked)
		{
			result = true;
			break;
		}

	}

	return result;
}



/**
 *
 */
function _delete_group()
{
	document.group_info.action.value = DELETE_GROUPS;

	group_index = document.group_info.groups.selectedIndex;
	document.group_info.company.value = groups[group_index]['company_id'];
	if(groups[group_index]['is_used']==1)
	{
		if(confirm("Agents are assigned to this group, are you sure you want to delete this group?"))
		{
			document.group_info.submit();		
		}
	}
	else
	{
		document.group_info.submit();
	}
}

/**
 *
 */
function _add_modify_groups(action)
{
	var myform_used = document.getElementById('used_options_id');

	if (_are_fields_populated())
	{
		if (_are_sections_selected())
		{
			if (_confirm_field_length())
			{
				if (!_confirm_group_doesnt_exists(action))
				{
					myform_used.multiple = true;
					for (var i = 0; i < myform_used.options.length; i++)
					{
						myform_used.options[i].selected = true;
					}

					document.group_info.group_company_name.disabled = false;

					if (action == ADD_GROUPS)
					{
						document.group_info.action.value = ADD_GROUPS;
						document.group_info.submit();
					}
					else if (action == COPY_GROUPS)
					{
						document.group_info.action.value = COPY_GROUPS;
						document.group_info.submit();
					}
					else
					{
						document.group_info.action.value = MODIFY_GROUPS;
						document.group_info.submit();
					}

					document.group_info.group_company_name.disabled = true;
					myform_used.multiple = false;
				}
				else
				{
					_display_alert(FIELD_GROUP_NAME, MESG_GROUP_EXISTS);
				}
			}
			else
			{
				_display_alert(FIELD_GROUP_NAME, MESG_LONG_LENGTH);
			}
		}
		else
		{
			_display_alert(FIELD_GROUP_STRUCTURE, MESG_EMPTY);
		}
	}
	else
	{
		_display_alert(FIELD_GROUP_NAME, MESG_EMPTY);
	}
}



/**
 *
 */
function _clear_section_tree()
{
   if (document.group_info.groups.length > 0)
   {
		for (var i = 0; i < master_tree_ids.length; i++)
		{
			document.getElementById(master_tree_ids[i]['value']).checked = false;
			if(undefined != document.getElementById('read_only_'+master_tree_ids[i]['value']))
			{
				document.getElementById('read_only_'+master_tree_ids[i]['value']).checked = false;
			}
		}
   }
}



/**
 *
 */
function _disable_section_tree()
{
   if (document.group_info.groups.length > 0)
   {
		for (var i = 0; i < master_tree_ids.length; i++)
		{
			document.getElementById(master_tree_ids[i]['value']).disabled = true;
		}
   }
}



/**
 *
 */
function _enable_section_tree()
{
   if (document.group_info.groups.length > 0)
   {
		for (var i = 0; i < master_tree_ids.length; i++)
		{
			document.getElementById(master_tree_ids[i]['value']).disabled = false;
		}
   }
}



/**
 *
 */
function radio_btn_actions(btn)
{
	reset_fields_status();
	_clear_fields();

	if (btn == ADD_GROUPS)
	{
		_set_fields(ADD_GROUPS);
	}
	else if (btn == MODIFY_GROUPS)
	{
		_set_fields(MODIFY_GROUPS);
	}
	else if (btn == COPY_GROUPS)
	{
		_set_fields(COPY_GROUPS);
	}
	else // delete
	{
		_set_fields(DELETE_GROUPS);
	}

	set_the_company();
	set_the_company_dest();
}




/**
 *
 */
function _set_fields(action)
{
	document.group_info.dest_company.style.visibility = 'hidden';
	if (action == ADD_GROUPS)
	{
		// disable existing groups list
		document.group_info.groups.disabled = true;

		// set commit butto
		document.group_info.commit.value = 'Add Group';
		document.group_info.commit.style.visibility = '';

		// set the commit button
		document.group_info.commit.disabled = false;
		document.group_info.group_name.disabled = false;
		document.group_info.company.disabled = false;

		// disable selections
		if (groups.length > 0)
		{
			for (var i = 0; i < groups.length; i++)
			{
				document.group_info.groups[i].disabled = true;
			}
		}

		_enable_section_tree();

		fill_unused_and_remove_used_options();
	}
	else if (action == MODIFY_GROUPS)
	{
		// disable existing groups list
		document.group_info.groups.disabled = false;

		// set the commit button
		document.group_info.commit.value = 'Modify Group';
		document.group_info.commit.style.visibility = '';

		// set the commit button
		document.group_info.commit.disabled = false;
		document.group_info.group_name.disabled = false;
		document.group_info.company.disabled = false;

		// enable selections, set selected groups index to 0
		if (groups.length > 0)
		{
			for (var i = 0; i < groups.length; i++)
			{
				document.group_info.groups[i].disabled = false;
			}

			document.group_info.groups.selectedIndex = 0;
			display_group();

		}

		_enable_section_tree();

	}
	else if (action == COPY_GROUPS)
	{
		// disable existing groups list
		document.group_info.groups.disabled = false;

		// set the commit button
		document.group_info.commit.value = 'Copy Group';
		document.group_info.commit.style.visibility = '';
		document.group_info.dest_company.style.visibility = '';
		// set the commit button
		document.group_info.commit.disabled = false;
		document.group_info.group_name.disabled = false;


		document.group_info.company.disabled = false;

		if (groups.length > 0)
		{
			for (var i = 0; i < groups.length; i++)
			{
				document.group_info.groups[i].disabled = false;
			}

			document.group_info.groups.selectedIndex = 0;
			display_group();

		}

		_enable_section_tree();
	}
	else if (action == DELETE_GROUPS)
	{
		// disable existing groups list
		document.group_info.groups.disabled = false;

		// set the commit button
		document.group_info.commit.value = 'Remove Group';
		document.group_info.commit.style.visibility = '';

		// set the commit button
		document.group_info.commit.disabled = false;
		document.group_info.group_name.disabled = true;


		document.group_info.company.disabled = false;

		// set selected groups index to first unused
		if (groups.length > 0)
		{
			var skip;
			for (var i = 0; i < groups.length; i++)
			{
				if (groups[i]['is_used'] == 1) {
					document.group_info.groups[i].disabled = false;
				} else {
					document.group_info.groups[i].disabled = false;
					if(skip != 1) {
						document.group_info.groups.selectedIndex = i;
						skip = 1;
					}
				}
			}
			display_group();
		}

		_disable_section_tree();
	}
}


/**
 *
 */
function _clear_fields()
{
	// clear the selected index
	document.group_info.groups.selectedIndex = -1;

	// clear profile info
	document.group_info.company.value = '';
	document.group_info.group_name.value = '';

	// clear privs
	_clear_section_tree();
}



/**
 *
 */
function get_unused_description()
{
	var myform = document.getElementById('used_options_id');
	myform.options.selectedIndex = -1;

	document.group_info.option_description.value = "";
   unused_options_id = document.group_info.unused_options.value;
	for (var i = 0; i < control_options.length; i++)
	{
		if (control_options[i]['control_option_id'] == unused_options_id)
		{
			document.group_info.option_description.value = control_options[i]['description'];
			break;
		}
	}
}



/**
 *
 */
function get_used_description()
{
	var myform_unused = document.getElementById('unused_options_id');
	var myform_used = document.getElementById('used_options_id');
	myform_unused.options.selectedIndex = -1;

	document.group_info.option_description.value = "";
   used_options_id = myform_used.value;

	for (var i = 0; i < control_options.length; i++)
	{
		if (control_options[i]['control_option_id'] == used_options_id)
		{
			document.group_info.option_description.value = control_options[i]['description'];
			break;
		}
	}
}



/**
 *
 */
function add_feature()
{
	var myform_unused = document.getElementById('unused_options_id');
	var myform_used = document.getElementById('used_options_id');

   selected_index = myform_unused.selectedIndex;

   if (selected_index > -1)
	{
		unused_desc = document.group_info.unused_options[selected_index].text;
   	unused_options_id = document.group_info.unused_options.value;

   	myform_used.options[myform_used.options.length] = new Option(unused_desc, unused_options_id);
		document.group_info.unused_options.options[document.group_info.unused_options.selectedIndex] = null;
		document.group_info.unused_options.selectedIndex = -1;
		document.group_info.option_description.value = "";
	}
}



/**
 *
 */
function remove_feature()
{
	var myform_unused = document.getElementById('unused_options_id');
	var myform_used = document.getElementById('used_options_id');

   selected_index = myform_used.selectedIndex;

   if (selected_index > -1)
	{
		used_desc = myform_used[selected_index].text;
   	used_options_id = myform_used.value;
   	myform_unused.options[myform_unused.length] = new Option(used_desc, used_options_id);
		myform_used.options[myform_used.selectedIndex] = null;
		myform_used.selectedIndex = -1;
		document.group_info.option_description.value = "";
	}
}




function remove_all_options_in_used()
{
	var myform_used = document.getElementById('used_options_id');

	used_length = myform_used.length;
	while (used_length > 0)
	{
		myform_used.options[used_length - 1] = null;
		used_length = myform_used.length;
	}

	document.group_info.option_description.value = "";
}



function remove_all_options_in_unused()
{
	var myform_unused = document.getElementById('unused_options_id');

	unused_length = myform_unused.length;
	while (unused_length > 0)
	{
		myform_unused.options[unused_length - 1] = null;
		unused_length = myform_unused.length;
	}

	document.group_info.option_description.value = "";
}



/*
 *
 */
function load_all_options_in_unused()
{
	var myform_unused = document.getElementById('unused_options_id');

	for (var j = 0; j < control_options.length; j++)
	{
		myform_unused.options[myform_unused.options.length] = new Option(control_options[j]['name'], control_options[j]['control_option_id']);
   }
}




/*
 *
 */
function load_all_used_and_unused_options()
{
	var myform_unused = document.getElementById('unused_options_id');
	var myform_used = document.getElementById('used_options_id');

	for (var j = 0; j < control_options.length; j++)
	{
		is_found = false;
		control_option_id = control_options[j]['control_option_id'];
   	for (var i = 0; i < access_group_control_options.length; i++)
   	{
      	if (access_group_control_options[i]['group_id'] == document.group_info.groups.value &&
				control_options[j]['control_option_id'] == access_group_control_options[i]['control_option_id'])
			{
				myform_used.options[myform_used.options.length] = new Option(control_options[j]['name'], control_option_id);
				is_found = true;
				break;
			}
		}

		if (!is_found)
		{
			myform_unused.options[myform_unused.options.length] = new Option(control_options[j]['name'], control_option_id);
		}
	}
}





/*
 *
 */
function fill_unused_and_remove_used_options()
{
	remove_all_options_in_used();
	remove_all_options_in_unused();

	has_used_options = false;
   for (var i = 0; i < access_group_control_options.length; i++)
   {
      if (access_group_control_options[i]['group_id'] == document.group_info.groups.value)
      {
			has_used_options = true;
			break;
      }
   }

	if (has_used_options)
	{
		load_all_used_and_unused_options();
	}
	else
	{
		load_all_options_in_unused();
	}
}




</script>
