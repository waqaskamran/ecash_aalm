// SCRIPT FILE customer_centric_interface.js
	function SelectAllCheckBoxes()
	{
		if(allSelected == false) {
			selectType = true;
		} else {
			selectType = false;
		}
		
		for (var i = 0; i < maxCheckBoxValues; i++)
		{
			box = document.getElementById('app[' + i + ']');
			box.checked = selectType;
		}
		allSelected = selectType;
	}
	
	function ConfirmSplit()
	{
		var numSelected = 0;
		
		for (var i = 0; i < maxCheckBoxValues; i++)
		{
			box = document.getElementById('app[' + i + ']');
			if(box.checked) {
				numSelected++;
			}
		}
		
		form = document.getElementById('customer_ssn_change');
		if(numSelected > 0) {
			if(confirm('Area you sure you wish to split these accounts?')) {
				form.submit();
			}
		} else {
			alert('No accounts have been selected for this operation!');
		}
	}

	function ConfirmMerge()
	{
		var numSelected = 0;
		
		for (var i = 0; i < maxCheckBoxValues; i++)
		{
			box = document.getElementById('app[' + i + ']');
			if(box.checked) {
				numSelected++;
			}
		}
		
		form = document.getElementById('customer_ssn_change');
		if(numSelected > 0) {
			if(confirm('Area you sure you wish to merge these accounts?')) {
				form.submit();
			}
		} else {
			alert('No accounts have been selected for this operation!');
		}
	}

		function viewAppIds()
	{
		for (x=0; x < other_applications.length; x++)
		{
			alert(other_applications[x].application_id);
		}
	}
	
	function viewNextApp()
	{
		if(view_pointer == other_applications.length - 1) {
			view_pointer = 0;
		}
		else {
			view_pointer++;
		}
		viewApp(view_pointer);
	}

	function viewPreviousApp()
	{
		if(view_pointer == 0) {
			view_pointer = other_applications.length - 1;
		}
		else {
			view_pointer--;
		}
		
		viewApp(view_pointer);
	}
	
	function viewApp(pointer)
	{
		app = other_applications[pointer];

		element = document.getElementById('view_old_app_id'); 
		element.innerHTML = app.application_id;
		
		element = document.getElementById('view_old_app_ssn'); 
		element.innerHTML = app.formatted_ssn;

		element = document.getElementById('view_old_app_name'); 
		element.innerHTML = app.customer_name;

		element = document.getElementById('view_old_app_address'); 
		element.innerHTML = app.street_address;

		element = document.getElementById('view_old_app_city'); 
		element.innerHTML = app.city;

		element = document.getElementById('view_old_app_state'); 
		element.innerHTML = app.state;

		element = document.getElementById('view_old_app_zip_code'); 
		element.innerHTML = app.zip_code;

		element = document.getElementById('view_old_app_phone_home'); 
		element.innerHTML = app.phone_home;

		element = document.getElementById('view_old_app_phone_cell'); 
		element.innerHTML = app.phone_cell;

                element = document.getElementById('view_old_app_employer_phone');
                element.innerHTML = app.employer_phone;

		element = document.getElementById('view_old_app_employer'); 
		element.innerHTML = app.employer_name;

		element = document.getElementById('view_old_app_status'); 
		element.innerHTML = app.status_long;
	}

	function react_verify_submit()
	{
		form = document.getElementById('react_verify'); 
		var radio_index = 0;
		
		//a variable that will hold the index number of the selected radio button
		for (i=0; i <form.react_type.length; i++)
		{
			if (form.react_type[i].checked == true)
				radio_index = i;
		}
		
		switch(form.react_type[radio_index].value)
		{
			case 'different' :
				form.comment.value = prompt("Enter a reason why the application is being split:", '');
				form.new_ssn.value = prompt("The system assumes that you have already contacted the customer prior to the change of the SSN. If not, please enter SSN as 000-00-0000 and set a Follow Up.\n\nEnter a new Social Security Number (XXX-XX-XXXX)", ''); //mantis:6569
				while(validateSSN(form.new_ssn.value) === false)
				{
					alert("Sorry, the SSN '" + form.new_ssn.value + "' is in an invalid format.\n  Please re-enter the SSN.");
					form.new_ssn.value = prompt("The system assumes that you have already contacted the customer prior to the change of the SSN. If not, please enter SSN as 000-00-0000 and set a Follow Up.\n\nEnter a new Social Security Number (XXX-XX-XXXX)", ''); //mantis:6569
				}
				if(form.new_ssn.value == form.old_ssn.value)
				{
					alert("SSN has not changed!  No changes will be committed to this account.");
					return;
				}
				
				form.submit();
				
				break;
			case 'same' :
			case 'fraud':
				form.submit();
				break;
		}
	}
	
	function validateSSN(val)
	{
		var ssn_reg = /(\d{3}-\d{2}-\d{4})/
		
		if (ssn_reg.test(val))
		{
			return true;
		}

		return false;
	}
