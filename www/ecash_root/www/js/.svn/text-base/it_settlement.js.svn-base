function get_settlement_overlay(sreport_id)
{
    var result = '';

    result += '<div style="background-color:lightsteelblue;border:solid black 1px;text-align:left;padding:5px; width: 250px;">';

	result += '<ul style=">';

    for (i = 0; i < json_reports.length; i ++)
    {
		if (json_reports[i].sreport_id == sreport_id)
		{
	        result += '<li>';;
	        result += '<b><u>Report ID: ' + json_reports[i].sreport_id + '</u></b>';
	        result += '</li>';

	        result += '<li>';;
	        result += 'Date Created: ' + json_reports[i].date_created;
	        result += '</li>';

	        result += '<li>';;
	        result += 'Start Date: ' + json_reports[i].sreport_start_date;
	        result += '</li>';

	        result += '<li>';;
	        result += 'End Date: ' + json_reports[i].sreport_end_date;
	        result += '</li>';

			if (json_reports[i].sreport_last_send_date != null)
			{
		        result += '<li>';;
		        result += 'Last Send Date: ' + json_reports[i].sreport_last_send_date;
	    	    result += '</li>';
			}


			break;
		}
    }
	result += '</ul>';
    result += '</div>';
    return result;
}

function toggle_current_only()
{
	clear_settlement_report_table();
	display_current_reports_only();
}

function clear_settlement_report_table()
{
	var report_table = document.getElementById('it_settlement_table');
	
	if ( report_table.hasChildNodes() )
	{
		while (report_table.childNodes.length >= 1)
		{
			report_table.removeChild( report_table.firstChild );
		}
	}
}


function display_current_reports_only()
{
	var report_table = document.getElementById('it_settlement_table');
	

	// Create the header
	var thead   = document.createElement('thead');

	// Add it to the list
	try
	{
		report_table.add(thead, null); // Standards compliant, broken in IE
	}
	catch(ex)
	{
		report_table.add(thead, 0); // Should work fine for IE
	}

	// Added the header header to it
	var tr = document.createElement('tr');
	
	try
	{
		thead.add(tr, null); // Standards compliant, broken in IE
	}
	catch(ex)
	{
		thead.add(tr, 0); // Should work fine for IE
	}

	// Added the header header to it
	var td = document.createElement('td');
	
	try
	{
		tr.add(td, null); // Standards compliant, broken in IE
	}
	catch(ex)
	{
		tr.add(td, 0); // Should work fine for IE
	}

	td.text = "foo";

	
}

function display_all_reports()
{
	var report_table = document.getElementById('it_settlement_table');

}

