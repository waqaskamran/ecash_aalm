<script type='text/javascript'>
// SCRIPT FILE rules.js

var loan_type_array = %%%loan_types%%%;
var loan_rule_set_array = %%%loan_rule_set%%%;
var rule_component_map = %%%rule_components%%%;
var rule_component_parms_array = %%%rule_component_parms%%%;
var rule_set_component_value_array= %%%rule_set_component_value%%%;
var rule_set_component_array= %%%rule_set_components%%%;
var last_loan_type_id = %%%loan_type_id%%%;
var last_rule_set_id = %%%rule_set_id%%%;
var last_rule_conponent_id = %%%rule_conponent_id%%%;
var last_rule_component_parm_id = %%%rule_component_parm_id%%%;
var last_rule_component_parm_value = %%%rule_component_parm_val%%%;

var min_length = %%%minimum_length%%%;
var max_length = %%%maximum_length%%%;


/**
* Sort things by sequence number
*/
function sequence_sort (a, b) {
	var i='sequence_no'; 
	return parseInt(a[i]) > parseInt(b[i]) ? 1 : parseInt(a[i]) < parseInt(b[i]) ? -1 : 0;
}

/**
* This obtains a nested div
*/
function getRefToDivNest( divID, oDoc ) {
	if( !oDoc ) { oDoc = document; }
	if( document.layers ) {
		if( oDoc.layers[divID] ) { return oDoc.layers[divID]; } else {
			for( var x = 0, y; !y && x < oDoc.layers.length; x++ ) {
				y = getRefToDivNest(divID,oDoc.layers[x].document); }
			return y; } }
	if( document.getElementById ) { return document.getElementById(divID); }
	if( document.all ) { return document.all[divID]; }
	return document[divID];
}


/**
* This clears and deslects all items in the loan rules box
*/
function clear_loan_rules()
{
	// clear loan rules
	document.rule_info.loan_rule_status.value = '';
	document.rule_info.loan_rule_effective.value = '';
	document.rule_info.loan_rule_list.selectedIndex = -1;
	while (document.rule_info.loan_rule_list.length > 0)
	{
		document.rule_info.loan_rule_list.options[document.rule_info.loan_rule_list.length - 1] = null;
	}
}



/**
* This clears and deselects all items in the rule componenets box
*/
function clear_rule_components()
{
	// clear rule components
	document.rule_info.rule_component_list.selectedIndex = -1;
	// document.rule_info.rule_component_status.value = '';
	document.rule_info.grandfather.value = '';
	while (document.rule_info.rule_component_list.length > 0)
	{
		document.rule_info.rule_component_list.options[document.rule_info.rule_component_list.length - 1] = null;
	}
}


/**
* This clears and deselects all the items in the rule component parameter box
*/
function clear_rule_component_parms()
{
	// clear rule component param
	document.rule_info.component_parm_list.selectedIndex = -1;
	document.rule_info.user_configurable.value = '';
	document.rule_info.unit_of_measure.value = '';
	while (document.rule_info.component_parm_list.length > 0)
	{
		document.rule_info.component_parm_list.options[document.rule_info.component_parm_list.length - 1] = null;
	}
}

/**
* This clears the rule component parameter value and restrictions
*/
function clear_comp_parm_value_and_restrictions()
{
	// clear rule component parameter value and restrictions
	document.rule_info.parm_description.value = "";
	document.rule_info.component_parm_value.value = '';
	//  clear select list
	while (document.rule_info.select_component_parm_value.length > 0)
	{
		document.rule_info.select_component_parm_value.options[document.rule_info.select_component_parm_value.length - 1] = null;
	}

	// disable the modify button
	document.rule_info.modify_button.disabled = true;
	
	// GF-17060
	// enable the Original rule param text field
	document.getElementById('component_parm_disabled').value = false;
}



/**
* This sets the loan rules fields and clears everything below it.
*/
function get_loan_rules()
{
	// clear fields
	clear_loan_rules();
	clear_rule_components();
	
	clear_rule_component_parms();
	clear_comp_parm_value_and_restrictions();

	loan_index = document.rule_info.loan_type_list.selectedIndex;
	selected_loan_type_id = loan_type_array[loan_index]['id'];
	document.rule_info.loan_type_status.value = loan_type_array[loan_index]['status'];
	
	var b = 0;
	for (var a in loan_rule_set_array) 
	{
		
		
		if (loan_rule_set_array[a]['loan_type_id'] == selected_loan_type_id)
		{
			if (b == 0) {
				document.rule_info.loan_rule_list.options[document.rule_info.loan_rule_list.length]
					= new Option('* ' + loan_rule_set_array[a]['name'], loan_rule_set_array[a]['id'], false, false);
			} 
			else {			
				document.rule_info.loan_rule_list.options[document.rule_info.loan_rule_list.length]
					= new Option(loan_rule_set_array[a]['name'], loan_rule_set_array[a]['id'], false, false);
			}
		b++;
		}
		else
		{
			b=0;
		}
	}
}

function get_rule_set_info(id) {
	for (var i in loan_rule_set_array) { 
		if (loan_rule_set_array[i].id == id) {
			return loan_rule_set_array[i];
		}
	}
	return false;
}

/**
* Populate the Loan Rule fields, the Rule Component list, and clear the other fields.
*/
function get_rule_components()
{
	// clear fields
	clear_rule_components();
	clear_rule_component_parms();
	clear_comp_parm_value_and_restrictions();

	// set the loan rules info
	loan_rule_index = document.rule_info.loan_rule_list.selectedIndex;
	selected_loan_rule_id = document.rule_info.loan_rule_list[loan_rule_index].value;
	loan_rule_set_info = get_rule_set_info(selected_loan_rule_id);
	
	document.rule_info.loan_rule_status.value = loan_rule_set_info['status'];
	document.rule_info.loan_rule_effective.value = loan_rule_set_info['date_effective'];

	// clear the selection
	document.rule_info.rule_component_list.selectedIndex = -1;

	// get the rule components
	unsorted = new Array();
	for (var a in rule_set_component_array)
	{
		if (rule_set_component_array[a]['rule_set_id'] == selected_loan_rule_id)
			unsorted.push(rule_set_component_array[a]);
    }

	// fill the component list
	var sorted = unsorted.sort(sequence_sort);
	for (var a in sorted)
	{
		document.rule_info.rule_component_list.add(new Option(rule_component_map[sorted[a]['rule_component_id']]['name'], sorted[a]['rule_component_id'], false, false), null);
	}
}



/*
* Clear fields, display rule component info, populate param fields.
*/
function get_component_params()
{
	// clear fields
	clear_rule_component_parms();
	clear_comp_parm_value_and_restrictions();

	// select the coponent status and grandfather settings
	rule_component_index = document.rule_info.rule_component_list.selectedIndex;
	rule_component_id = document.rule_info.rule_component_list.options[rule_component_index].value;
	document.rule_info.grandfather.value = rule_component_map[rule_component_id]['grandfathering_enabled'];

	// sort the parameters
	unsorted_component_parms = new Array();
	for (a in rule_component_parms_array) 
	{
		if (rule_component_parms_array[a]['rule_component_id'] == rule_component_id)
			unsorted_component_parms.push(rule_component_parms_array[a]);
	}

	// sort the component array
	sorted_component_parms = unsorted_component_parms.sort(sequence_sort);
	// populate the list
	for (a in sorted_component_parms)
	{
		var rec = sorted_component_parms[a];
		document.rule_info.component_parm_list.add(new Option(rec['parm_name'], rec['rule_component_parm_id'], false, false), null);
	}
}


/*
*
*/
function get_component_parm_value()
{
	// clear fields
	clear_comp_parm_value_and_restrictions();

	rule_component_parm_index = document.rule_info.component_parm_list.selectedIndex;
	rule_component_parm_id = document.rule_info.component_parm_list.options[rule_component_parm_index].value;
	//GF 17060 - The hidden element component_parm_disabled will dictate whether or not the 
	//the text field is writable or not
	rule_component_parm_disabled = document.getElementById('component_parm_disabled');
	
	rule_div = getRefToDivNest('rule_param_select_div');
	rule_div.style.visibility = 'visible';

	do_not_change = false;

	for (var a in rule_component_parms_array)
	{
		if (rule_component_parms_array[a]['rule_component_parm_id'] == rule_component_parm_id)
		{
			document.rule_info.user_configurable.value = rule_component_parms_array[a]['user_configurable'];
			document.rule_info.unit_of_measure.value = rule_component_parms_array[a]['unit_of_measure'];

			parm_value = 'UNKNOWN';
			for (var b in rule_set_component_value_array)
			{
				if ((rule_set_component_value_array[b]['rule_component_parm_id'] == rule_component_parm_id)
						&& (document.rule_info.loan_rule_list.options[document.rule_info.loan_rule_list.selectedIndex].value
							== rule_set_component_value_array[b]['rule_set_id'])
						&& (document.rule_info.rule_component_list.options[document.rule_info.rule_component_list.selectedIndex].value
							== rule_set_component_value_array[b]['rule_component_id'])
					)
				{

					parm_value = rule_set_component_value_array[b]['parm_value'];
				}
			}

			if (rule_component_parms_array[a]['input_type'] == 'select')
			{
				// enable and disable the correct fields
				rule_component_parm_disabled.value = true;
				
				if (rule_component_parms_array[a]['user_configurable'] == 'no')
				{
					document.rule_info.select_component_parm_value.disabled = true;
					document.rule_info.modify_button.disabled = true;
				}
				else
				{
					document.rule_info.select_component_parm_value.disabled = false;
					document.rule_info.modify_button.disabled = false;
				}

				if (rule_component_parms_array[a]['enum_array'].length > 0)
				{
					//  populate select list
					selected_index = 0;
					for (var c in rule_component_parms_array[a]['enum_array'])
					{
						document.rule_info.select_component_parm_value.options[document.rule_info.select_component_parm_value.length]
							= new Option(rule_component_parms_array[a]['enum_array'][c]['value'],
											rule_component_parms_array[a]['enum_array'][c]['value'], false, false);

						if (rule_component_parms_array[a]['enum_array'][c]['value'] == parm_value)
						{
							selected_index = c;
						}
					}	

					// select the item in the drop down box
					document.rule_info.select_component_parm_value.selectedIndex = selected_index;
				}
				else
				{
					selected_index = 0;
					for (var c in rule_component_parms_array[a]['increment_array'])
					{
						document.rule_info.select_component_parm_value.options[document.rule_info.select_component_parm_value.length]
							= new Option(rule_component_parms_array[a]['increment_array'][c]['value'],
											rule_component_parms_array[a]['increment_array'][c]['value'], false, false);

						if (rule_component_parms_array[a]['increment_array'][c]['value'] == parm_value)
						{
							selected_index = c;
						}
					}
					
					// select the item in the drop down box
					document.rule_info.select_component_parm_value.selectedIndex = selected_index;
				}
			}
			else if (rule_component_parms_array[a]['input_type'] == 'text')
			{
				rule_div = getRefToDivNest('rule_param_select_div');
				rule_div.style.visibility = 'hidden';
			 	document.rule_info.component_parm_value.disabled = false;
			 	document.rule_info.component_parm_value.value = parm_value;
			 	document.rule_info.modify_button.disabled = false;
			 	rule_component_parm_disabled.value = false;
			 	do_not_change = true;

				if (rule_component_parms_array[a]['user_configurable'] == 'no')
				{
					document.rule_info.modify_button.disabled = true;
					rule_component_parm_disabled.value = true;
				}
			}
			else
			{
				// enable and disable the correct fields
				document.rule_info.select_component_parm_value.disabled = true;
				if (rule_component_parms_array[a]['user_configurable'] == 'no')
				{
					document.rule_info.modify_button.disabled = true;
					rule_component_parm_disabled.value = true;
				}
				else
				{
					document.rule_info.modify_button.disabled = false;
					rule_component_parm_disabled.value = false;
				}
				document.rule_info.component_parm_value.value = parm_value;
			}

			set_description( rule_component_parms_array[a]['parm_subscript'],
									rule_component_parms_array[a]['description'],
									rule_component_parms_array[a]['parm_type'],
									rule_component_parms_array[a]['user_configurable'],
									rule_component_parms_array[a]['input_type'],
									rule_component_parms_array[a]['unit_of_measure'],
									rule_component_parms_array[a]['value_min'],
									rule_component_parms_array[a]['value_max'],
									rule_component_parms_array[a]['value_increment'],
									rule_component_parms_array[a]['length_min'],
									rule_component_parms_array[a]['length_max'],
									rule_component_parms_array[a]['enum_values'],
									rule_component_parms_array[a]['preg_pattern']);


			min_length = rule_component_parms_array[a]['length_min'];
			max_length = rule_component_parms_array[a]['length_max'];

			break;
		}
	}
	if(do_not_change == false)
		document.rule_info.component_parm_value.value = document.rule_info.select_component_parm_value.value; //mantis:2757
}

function isComponentParmDisabled()
{
	var disabled = document.getElementById('component_parm_disabled').value;
	if(disabled == 'true')
	{
		return false;
	}
	//If none of the other flags caught it, then we can allow typing
	return true;
}

/**
*
*/
function set_description(subscript, desc, type, user_configurable, input_type,
								unit_of_measure, value_min, value_max, value_increment, length_min,
								length_max, enum_values, preg_pattern)
{
	ta_desc = "DESCRIPTION: " + desc;
	ta_subscript = "SUBSCRIPT: " + subscript;
	ta_type = "TYPE: " + type;
	ta_user_configurable = "USER CONFIGURABLE: " + user_configurable;
	ta_input_type = "INPUT TYPE: " + input_type;
	ta_unit_of_measure = "UNIT OF MEASURE: " + unit_of_measure;
	ta_value_min = "MINIMUM VALUE: " + value_min;
	ta_value_max = "MAXIMUM VALUE: " + value_max;
	ta_value_increment = "INCREMENT VALUE: " + value_increment;
	ta_length_min = "MINIMUM LENGTH VALUE: " + length_min;
	ta_length_max = "MAXIMUM LENGTH VALUE: " + length_max;
	ta_enum_values = "ENUM VALUE: " + enum_values;
	ta_preg_pattern = "PREG PATTERN: " + preg_pattern;

	ta_two_spaces = "\n\n";

	document.rule_info.parm_description.value = ta_desc
																+ ta_two_spaces
																+ ta_subscript
																+ ta_two_spaces
																+ ta_type
																+ ta_two_spaces
																+ ta_user_configurable
																+ ta_two_spaces
																+ ta_input_type
																+ ta_two_spaces
																+ ta_unit_of_measure
																+ ta_two_spaces
																+ ta_value_min
																+ ta_two_spaces
																+ ta_value_max
																+ ta_two_spaces
																+ ta_value_increment
																+ ta_two_spaces
																+ ta_length_min
																+ ta_two_spaces
																+ ta_length_max
																+ ta_two_spaces
																+ ta_value_increment
																+ ta_two_spaces
																+ ta_enum_values
																+ ta_two_spaces
																+ ta_preg_pattern;
}



/**
*
*/
function save_business_rule()
{
	disable_fields(false);
	valid = false;

	if (!document.rule_info.component_parm_value.disabled)
	{
		if (document.rule_info.component_parm_value.value.length >= min_length
			&& document.rule_info.component_parm_value.value.length <= max_length)
		{
			valid = true;
		}
		else
		{
			alert("This value exceeds or does not meet the length requirements.\nMin length: "
					+ min_length + ", max length: " + max_length + ".");
		}
	}
	else
	{
		valid = true;
	}

	if (valid)
	{
		document.rule_info.submit();
	}
}



/**
*
*/
function get_last_settings()
{
	// disable necessary fields
	document.rule_info.modify_button.disabled = true;

	disable_fields(true);


	if (last_loan_type_id > 0)
	{
		document.rule_info.loan_type_list.value = last_loan_type_id;
		get_loan_rules();
		document.rule_info.loan_rule_list.value = last_rule_set_id;
		get_rule_components();
		document.rule_info.rule_component_list.value = last_rule_conponent_id;
		get_component_params();
		document.rule_info.component_parm_list.value = last_rule_component_parm_id;
		get_component_parm_value();
	}
}


/**
*
*/
function disable_fields(value)
{
	document.rule_info.loan_type_status.disabled = value; 
	document.rule_info.loan_rule_status.disabled = value;
	document.rule_info.loan_rule_effective.disabled = value;
	document.rule_info.grandfather.disabled = value;
	document.rule_info.user_configurable.disabled = value;
	document.rule_info.unit_of_measure.disabled = value;
}

</script>
