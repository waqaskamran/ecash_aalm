<script type='text/javascript'>
// SCRIPT FILE admin_agent.js

// global variables set by the php script. 
// this is how i pass data to the form.
var users = %%%users%%%

var sections = %%%sections%%%

var companies = %%%companies%%%

var acl_last_agent_id = %%%acl_last_agent_id%%%;

var acl_last_action = %%%acl_last_action%%%;


// global constants
var ADD = 'add'
var DELETE = 'delete'
var MODIFY = 'modify'
var FIELD_PASSWORD = 'Password';
var FIELD_LOGIN = 'Login';
var FIELD_EXISTING_USER	 = 'Existing Agent';
var FIELD_COMP_SECT = 'Company-Section Privileges';
var FIELD_COMPANY = 'All Companies';
var FIELD_SECTIONS = 'All Sections';
var MESG_PASSWORD =  'Your passwords do not match';
var MESG_LOGIN = 'You must enter a unique login. This login already exists';
var MESG_EMPTY = 'This field cannot be empty';
var MESG_EXISTING_USER	 = 'You must select an agent to remove';
var MESG_PASSWORD_MISMATCH = 'The passwords do not match'
var MESG_COMP_SECT = 'This Company-Section Privilege already exists';
var MESG_COMPANY = 'You must select a company to add';
var MESG_SECTIONS = 'You must select a section to add';
var MESG_COMP_SECT_NONE = "You must select a Company-Section Privilege to remove";
var MESG_LONG_LENGTH = "You must limit this field to 50 characters"

/**
 * Desc:
 * 	This function populates the user info when a user is selected in the
 * 	existing users list.
 *
 * Param:
 *		none.
 *
 * Return:
 *		none.
 */
function display_user()
{
	var user_idx;

	_clear_all_privs();

	// keep the index into the user list as a global so other functions can ref it
	user_idx = document.user_info.agent.selectedIndex;

	// clear the action so that a submit just brings us right back
	document.user_info.action.value = '';

	// don't set these fields if we are using an existing user template
	if (!document.user_info.add_radio_btn.checked)
	{
		// get user login info
		document.user_info.agent_login.value	= users[user_idx]['agent_login'];
		document.user_info.name_first.value	= users[user_idx]['name_first'];
		document.user_info.name_last.value	= users[user_idx]['name_last'];
	}

	_display_privs(user_idx);
}



/**
 * Desc:
 *		This fills the Company-Section Privileges Section
 *
 *	Param:
 *		index: This is the index of the selected list item.
 *
 * Return:
 *		none.
 */
function _display_privs(index)
{
	var count;

	if (index > -1)
	{
		if (users[index]['company'])
		{
			count = 0;
			for (companyKey in users[index]['company'])
			{
				for (sectionKey in users[index]['company'][companyKey])
				{
					if (sectionKey != 'company_name')
					{
						tmp_value = companyKey + '-' + users[index]['company'][companyKey][sectionKey]['section_id'];
						tmp_option = users[index]['company'][companyKey]['company_name']
											+ ',\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0' + users[index]['company'][companyKey][sectionKey]['section_name'];
						document.user_info.priv_list.options[count] = new Option(tmp_option, tmp_value, false, false);
						count = count + 1;
					}
				}
			}
		}
	}
}



/**
 * Desc:
 *		If the user template check box changes, this is called.
 *		It enables and disables the Agent list based on the
 *		selected value.
 *
 *	Param:
 *		none.
 *
 *	Return:
 *		none.
 */
function user_template_changed()
{
	if (!document.user_info.use_template.disabled)
	{
		// if false it is really checked. HAHAHA stupid HTML
		if (!document.user_info.use_template.checked)
		{
			document.user_info.agent.disabled = false;
			if (users.length > 0)
			{
				document.user_info.agent.selectedIndex = 0;
				_display_privs(0);
			}
		}
		else
		{
			document.user_info.agent.disabled = true;
			document.user_info.agent.selectedIndex = -1;
			_clear_all_privs();
		}
	}
}




/**
 * Desc:
 *		This sets the last radio button and selects the approrate agnent in the list.
 *
 * Param:
 *		none.
 *
 *	Return:
 *		none.
 */
function get_last_settings()
{
	var count = 0;

	if (acl_last_action == 'add')
	{
		document.user_info.add_radio_btn.checked = true;
		radio_btn_actions(ADD);
	}
	else if (acl_last_action == 'modify')
	{
		if (acl_last_agent_id > 0)
		{
			document.user_info.mod_radio_btn.checked = true;
			radio_btn_actions(MODIFY);

			// loop through users
			for (testKey in users)
			{
				if (users[testKey]['id'] == acl_last_agent_id)
				{
					document.user_info.agent.options[count].selected = true;
					display_user();
					break;
				}
				count = count + 1;
			}
		}
	}
	else
	{
		document.user_info.remove_radio_btn.checked = true;
		radio_btn_actions(DELETE);
	}
}





/**
 * Desc:
 *		This function validates the agent information and submits it.
 *
 * Param:
 *		none.
 *
 * Return:
 *		none.
 */
function _add_agent()
{
	if (_are_fields_populated(ADD))
	{
		if (_confirm_field_length())
		{
			if (_confirm_passwords_match())
			{
				if (_confirm_login_doesnt_exist())
				{
					// disable the fields that do not need to be sent over
					document.user_info.all_companies_list.disabled = true;
					document.user_info.all_sections_list.disabled = true;
					document.user_info.password2.disabled = true;

					document.user_info.action.value = ADD;
					_select_company_section_privs();
					document.user_info.submit();
				}
				else
				{
					_display_alert(FIELD_LOGIN, MESG_LOGIN);
				}
			}
			else
			{
				_display_alert(FIELD_PASSWORD, MESG_PASSWORD);
			}
		}
		else
		{
			_display_alert(_get_exceeded_length_field(), MESG_LONG_LENGTH);
		}
	}
	else
	{
		_display_alert(_get_empty_error_field(ADD), MESG_EMPTY);
	}
}




/**
 * Desc:
 *    This returns the field that exceeds the limit of 50 chars
 *
 * Param:
 * 	none.
 *
 *	Return:
 *	   result: This is the field name.
 */
function _get_exceeded_length_field()
{
	var result = "Unknown Field";

	if (document.user_info.name_first.value.length > 50)
	{
		result = "First Name";
	}
	else if ( document.user_info.name_last.value.length > 50)
	{
		result = "Last Name";
	}
	else if ( document.user_info.agent_login.value.length > 50)
	{
		result = "Login";
	}
	else if (document.user_info.password1.value.length > 50)
	{
		result = "New Password";
	}
	else if (document.user_info.password2.value.length > 50)
	{
		result = "Conf. Password";
	}

	return result;
}



/**
 * Desc:
 *    This verifies none of the field lengths exceed 50 chars.
 *
 * Param:
 * 	none.
 *
 *	Return:
 *	   result: 'true' if all field lenghts are under 50 chars, or
 *					'false' if they exceed 50 chars.
 */
function _confirm_field_length()
{
	var result = false;

	if ( document.user_info.name_last.value.length <= 50
			&& document.user_info.name_first.value.length <= 50
			&& document.user_info.agent_login.value.length <= 50
			&& document.user_info.password1.value.length <= 50
			&& document.user_info.password2.value.length <= 50)
	{
		result = true;
	}

	return result;
}



/**
 * Desc:
 *    This verifies that a user login doesn't already exist.
 *
 * Param:
 * 	none.
 *
 *	Return:
 *	   result: 'true' if the login doesn't exist, 'false' if it does.
 */
function _confirm_login_doesnt_exist()
{
	var result = true;

	for (var i in users)
	{
		if (document.user_info.agent_login.value == users[i].agent_login)
		{
			result = false;
		}
	}

	return result;
}




/**
 * Desc:
 *		This selects all the items in the company-section privileges
 *		so that all the selected values will be sent over on submit.
 *
 * Param:
 *		none.
 *
 *	Return:
 *		none.
 */
function _select_company_section_privs()
{
	// enable multiple selection to save
	document.user_info.priv_list.multiple = true;

	if (document.user_info.priv_list.length > 0)
	{
		for (a = 0; a < document.user_info.priv_list.length; a++)
		{
			document.user_info.priv_list.options[a].selected = true;
		}
	}

	// disable multiple selection to save
	document.user_info.priv_list.multiple = false;
}



/**
 * Desc:
 *		This displays an alert dialog to a user. This alert
 *		informs the user of any errors in their data.
 *
 * Param:
 *		field: This is the field that contains the error.
 *		mesg: This is the message describing the error.
 *
 * Return:
 *		none.
 */
function _display_alert(field, mesg)
{
	alert("Error Field: " + field + "\nError Message: " + mesg + ".");
}



/**
 *	Desc:
 *		This function determines the empty field to the user which must
 *		must be populated.
 *
 *	Param:
 *		action: This describes the fields that need to be checked.
 *
 *	Return:
 *		result: This is the field that is blank and shouldn't be.
 */
function _get_empty_error_field(action)
{
	var result = 'Unknown';

	if (document.user_info.name_first.value == '')
	{
		result = 'First Name';
	}
	else if (document.user_info.name_last.value == '')
	{
		result = 'Last Name';
	}
	else if (document.user_info.agent_login.value == '')
	{
		result = 'Login';
	}
	else // the next fields relate to the action
	{
		// passwords can be blank if not add
		if (action == ADD)
		{
			if (document.user_info.password1.value == '' )
			{
				result = 'New Password';
			}
			else if (document.user_info.password2.value == '' )
			{
				result = 'Conf. Password';
			}
		}
	}

	return result;
}



/**
 * Desc:
 *		This checks to make sure the necessary fields are populated.
 *
 *	Param:
 *		action: This determines which fields can be blank and which
 *					ones can not.
 *	Return:
 *		result: This is 'true' if all fields are populated. It is 'false'
 *					if they are not all populated.
 */
function _are_fields_populated(action)
{
	var result = false;

	// is there info in name and login fields
	if (document.user_info.name_first.value != ''
		&& document.user_info.name_last.value != ''
		&& document.user_info.agent_login.value != '')
	{
		if (action == ADD)
		{
			if (document.user_info.password1.value != '' 
				 && document.user_info.password2.value != '') 
			{
				result = true;
			}
		}
		else // action is not add
		{
			result = true;
		}
	}

	return result;
}





/**
 * Desc:
 *		This is called to delete an agent.
 *
 *	Param:
 *		none.
 *
 *	Return:
 *		none.
 */
function _delete_agent()
{
   if (_are_fields_populated(DELETE))
   {
      document.user_info.action.value = DELETE;
      document.user_info.submit();
   }
   else
   {
      _display_alert(FIELD_EXISTING_USER, MESG_EXISTING_USER);
   }
}



/**
 * Desc:
 *		This is called to modify an agent.
 *
 *	Param:
 *		none.
 *
 *	Return:
 *		none.
 */
function _modify_agent()
{
	if (_are_fields_populated(MODIFY))
	{
		if (_confirm_field_length())
		{
			if (_confirm_passwords_match())
			{
				// disable the fields that do not need to be sent ofer
				document.user_info.all_companies_list.disabled = true;
				document.user_info.all_sections_list.disabled = true;
				document.user_info.agent_login.disabled = true;
				document.user_info.password2.disabled = true;

				if (document.user_info.password1.value == '')
				{
					document.user_info.password1.disabled = true;
				}

				document.user_info.action.value = MODIFY;
				_select_company_section_privs();
				document.user_info.submit();
			}
			else
			{
				_display_alert(FIELD_PASSWORD, MESG_PASSWORD_MISMATCH);
			}
		}
		else
		{
			_display_alert(_get_exceeded_length_field(), MESG_LONG_LENGTH);
		}
	}
	else
	{
		_display_alert(_get_empty_error_field(MODIFY), MESG_EMPTY);
	}
}



/**
 *	Desc:
 *		This confirms the passwords entered by the user match.
 *
 *	Param:
 *		none.
 *
 * Return:
 *		result: This is 'true' if the passwords match. It is 'flase'
 *					if they do not match.
 */
function _confirm_passwords_match()
{
	var result = false;

	if (document.user_info.password1.value == document.user_info.password2.value)
	{
		result = true;
	}

	return result;
}



/**
 * Desc:
 *		This is calls the correct action depending on the
 *		button that is pressed.
 *
 *	Param:
 *		none.
 *
 *	Return:
 *		none.
 */
function save()
{
	// disable the button to prevent it from being pushed by
	// impatient users
	document.user_info.commit.disabled = true;

	if (document.user_info.commit.value == 'Add Agent')
	{
		_add_agent();
	}
	else if (document.user_info.commit.value == 'Modify Agent')
	{
		_modify_agent();
	}
	else // delete agent
	{
		_delete_agent();
	}

	// enable the button
	document.user_info.commit.disabled = false;
}





/**
 *	Desc:
 *		This is used to add a privilege to the list of Company-Section Privileges.
 *
 *	Param:
 *		none.
 *
 *	Return:
 *		none.
 */
function add_priv()
{
	var company_selection_index;
	var section_selection_index;
	var temp_selected_company_name;
	var temp_selected_company_id;
	var temp_selected_section_name;
	var temp_selected_section_id;
	var new_company;
	var new_section;
	var add_new_priv;
	var temp_new_list_value;
	var temp_new_list_option;

	// get the selected indexs for all companies and all sections
	company_selection_index = document.user_info.all_companies_list.selectedIndex;
	section_selection_index = document.user_info.all_sections_list.selectedIndex;

	// make sure a company and section are both selected
	if ((company_selection_index > -1) && (section_selection_index > -1))
	{
		// loop through the companies
		for (a = 0; a < document.user_info.all_companies_list.length; a++)
		{
			new_company = false;
			temp_selected_company_name = 'UNKNOWN';
			temp_selected_company_id = "666";
			if (document.user_info.all_companies_list.options[a].selected)
			{
				new_company = true;
				temp_selected_company_name = document.user_info.all_companies_list.options[a].text;
				temp_selected_company_id = document.user_info.all_companies_list.options[a].value;
			} // end if company is selected

			if (new_company)
			{
				// loop through the sections
				for (b = 0; b < document.user_info.all_sections_list.length; b++)
				{
					new_section = false;
					temp_selected_section_name = "UNKNOWN";
					temp_selected_section_id = "666";

					if (document.user_info.all_sections_list.options[b].selected)
					{
						new_section = true;
						temp_selected_section_name = document.user_info.all_sections_list.options[b].text;
						temp_selected_section_id = document.user_info.all_sections_list.options[b].value;
					} // end if section is selected

					if (new_section)
					{
						temp_new_list_value = temp_selected_company_id + '-' + temp_selected_section_id;
						add_new_priv = true;
						if (document.user_info.priv_list.length > 0)
						{
							for (c = 0; c < document.user_info.priv_list.length; c++)
							{
								if (document.user_info.priv_list.options[c].value == temp_new_list_value)
								{
									add_new_priv = false;
									break;
								} // if priv exists in the list
							} // end for each exising priv
						} // end if priv list > 0

						if (add_new_priv)
						{
							temp_new_list_option = temp_selected_company_name
															+ ',\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0'
															+ temp_selected_section_name;
							document.user_info.priv_list.options[document.user_info.priv_list.length]
								= new Option(temp_new_list_option, temp_new_list_value, false, false);
						} // end if add new priv
					} // end if new section
				} // end for each section
			} // end if new company
		} // end for each company
	}
	else // a company or section is not selected
	{
		if (company_selection_index <= -1)
		{
			_display_alert(FIELD_COMPANY, MESG_COMPANY);
		}
		else // no section was selected
		{
			_display_alert(FIELD_SECTIONS, MESG_SECTIONS);
		} // end else no section selection
	}
}



/**
 * Desc:
 *		This removes a privilege from the Company-Section Privileges.
 *
 *	Param:
 *		none.
 *
 *	Return:
 *		none.
 */
function remove_priv()
{
	if (document.user_info.priv_list.selectedIndex > -1)
	{
		for (a = document.user_info.priv_list.length - 1; a >= 0; a--)
		{
			if (document.user_info.priv_list.options[a].selected)
			{
				document.user_info.priv_list.options[a] = null;
			} // end if selected privs
		}

		document.user_info.priv_list.setSelectedIndex = -1;
	}
	else // no selected index
	{
		_display_alert(FIELD_COMP_SECT, MESG_COMP_SECT_NONE);
	}
}




/**
 * Desc:
 *		This clears the Company-Section Privileges.
 *
 *	Param:
 *		none.
 *
 *	Return:
 *		none.
 */
function _clear_all_privs()
{
   if (document.user_info.priv_list.length > 0)
   {
		document.user_info.priv_list.setSelectedIndex = -1;
      while (document.user_info.priv_list.length > 0)
      {
         document.user_info.priv_list.options[document.user_info.priv_list.length - 1] = null;
      }
   }
}



/**
 * Desc:
 *		This resets the screen when the radio buttons change.
 *
 *	Param:
 *		btn: This is the radio button currently selected.
 *
 *	Return:
 *		none.
 */
function radio_btn_actions(btn)
{
	_clear_fields();

	if (btn == ADD)
	{
		_set_fields(ADD);
	}
	else if (btn == MODIFY) 
	{
		_set_fields(MODIFY);
	}
	else // delete
	{
		_set_fields(DELETE);
	}
}




/**
 * Desc: This enables and disables fields depneding on the param
 * 			passed in.
 *
 *	Param:
 *		action: This determines the enabled and disabled screens.
 *
 *	Return:
 *		none.
 */
function _set_fields(action)
{
	isDisabled = false;
	if (action == ADD)
	{
		isDisabled = false;

		// disable existing agent list
		document.user_info.agent.disabled = true;

		// set commit butto
		document.user_info.commit.value = 'Add Agent';

		// set the commit button
		document.user_info.commit.disabled = false;

		// set the list of company and section privs
		document.user_info.priv_list.disabled = false;

		// set login
		document.user_info.agent_login.disabled = false;

		// use template is disabled
		document.user_info.use_template.disabled = false;
	}
	else if (action == MODIFY)
	{
		// disable fields
		isDisabled = false;

		// disable existing agent list
		document.user_info.agent.disabled = false;

		// set the commit button
		document.user_info.commit.value = 'Modify Agent';

		// set the commit button
		document.user_info.commit.disabled = false;

		// set the list of company and section privs
		document.user_info.priv_list.disabled = false;

		// set login
		document.user_info.agent_login.disabled = true;

		// use template is disabled
		document.user_info.use_template.disabled = true;

		// set selected agent index to 0
		if (users.length > 0)
		{
			document.user_info.agent.selectedIndex = 0;
			display_user(0);
		}
	}
	else if (action == DELETE)
	{
		// disable fields
		isDisabled = true;

		// disable existing agent list
		document.user_info.agent.disabled = false;

		// set the commit button
		document.user_info.commit.value = 'Remove Agent';

		// set the commit button
		document.user_info.commit.disabled = false;

		// set the list of company and section privs
		document.user_info.priv_list.disabled = true;

		// set login
		document.user_info.agent_login.disabled = true;

		// use template is disabled
		document.user_info.use_template.disabled = true;

		// set selected agent index to 0
		if (users.length > 0)
		{
			document.user_info.agent.selectedIndex = 0;
			display_user(0);
		}
	}

	// profile info
	document.user_info.name_first.disabled = isDisabled;
	document.user_info.name_last.disabled = isDisabled;
	document.user_info.password1.disabled = isDisabled;
	document.user_info.password2.disabled = isDisabled;

	// agent lists
	document.user_info.all_sections_list.disabled = isDisabled;
	document.user_info.all_companies_list.disabled = isDisabled;
	document.user_info.priv_list.disabled = isDisabled;

	// privilege buttons
	document.user_info.add_privs.disabled = isDisabled;
	document.user_info.remove_privs.disabled = isDisabled;

	document.user_info.use_template.checked = false;
	document.user_info.use_template.setvisible = false;

}



/**
 * Desc:
 *		This clears the list selections and the text fields for the screen.
 *
 *	Param:
 *		none.
 *
 *	Return:
 *		none.
 */
function _clear_fields()
{
	// clear the selected index
	document.user_info.agent.selectedIndex = -1;
	document.user_info.all_sections_list.selectedIndex = -1;
	document.user_info.all_companies_list.selectedIndex= -1;
	document.user_info.priv_list.selectedIndex = -1;

	// clear profile info
	document.user_info.name_first.value = '';
	document.user_info.name_last.value = '';
	document.user_info.agent_login.value = '';
	document.user_info.password1.value = '';
	document.user_info.password2.value = '';

	// clear privs
	_clear_all_privs();
}



</script>
