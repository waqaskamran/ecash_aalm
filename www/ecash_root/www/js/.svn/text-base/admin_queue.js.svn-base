<script type='text/javascript'>
// SCRIPT FILE admin_queue.js


/**
 verifies that a queue is selected
 */
function save()
{
	if (document.%%%queue_form_name%%%.%%%queue_list_name%%%.value != '')
	{
		 document.%%%queue_form_name%%%.submit();
	}
	else
	{
		alert("Please select a queue!");
	}

}

/**
*Tried to replace the javascript that determined the additional value in some of the queue config screens.  
*/
function queue_company()
{
	for ( var i = 0 ; i < document.%%%queue_form_name%%%.elements['%%%queue_form_name%%%_value'].length ; i++ ) 
	{ 
		if ( document.%%%queue_form_name%%%.elements['%%%queue_form_name%%%_value'].options[i].value == document.%%%queue_form_name%%%.elements['%%%queue_form_name%%%_' + document.%%%queue_form_name%%%.%%%queue_list_name%%%[%%%queue_list_name%%%.selectedIndex].value + '_id'].value ) 
		{ 
			document.%%%queue_form_name%%%.elements['%%%queue_form_name%%%_value'].selectedIndex = i; 
		}  
	}
}
	
// suppress errors from global onload
function get_last_settings(){};

</script>
