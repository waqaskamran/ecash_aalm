// SCRIPT FILE login.js
function Set_Company_Host_Location()
{
	// Login form
	var form    = document.getElementById('login_form');

	// Company user is logging into
	var company = document.getElementById('login_company').value;

	// Set the destination
//	form.action = href[company];

	// All done, submit the form
	return true;
}

function checkParentWindow()
{
	if(parent.location != window.location)
	{
		parent.location = window.location;
	}
}
