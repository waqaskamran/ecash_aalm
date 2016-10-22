<script type='text/javascript'>
// SCRIPT FILE tags.js

function get_last_settings() {
	
}

function Modify_Weights(submitted) {
	var form = document.getElementById('investor_groups');
	
	var number_of_tags = document.getElementById('number_of_tags').value;
	var amount = 0;
	
	for (var i = 0; i < number_of_tags; i++) 
	{
		if(document.getElementById('weights_'+i).value == '')
			document.getElementById('weights_'+i).value = 0;

		if(!isWeight(document.getElementById('weights_'+i).value) || document.getElementById('weights_'+i).value < 0)
		{
			alert("The value of all weights must be a positive integer or zero.");
			return false;
		}

		amount += parseInt(document.getElementById('weights_'+i).value);
	}

	document.getElementById('total_weight').value = amount;
	
	if (amount != 100) {
		alert("The value of all weights must equal 100.");
		return false;
	} else {
		if (submitted) {
			return true;
		} else {
			form.submit();
			return true;
		}
	}
}

function Add_Investor_Group()
{
	var form     = document.getElementById('add_investor_groups');
	var name     = form.new_ig_name.value;
	var tag_name = form.new_ig_name_short.value;
	
	if(name == "") {
		alert("The Investor Group Name must not be empty!");
		return false;
	}
	else if(tag_name == "") {
		alert("The Investor Group Tag must not be empty!");
		return false;
	}
	else {
		form.submit();
		return true;
	}
}


/*
function CalculateTotal()  // works onChange
{
	var form = document.getElementById('investor_groups');
	
	var number_of_tags = document.getElementById('number_of_tags').value;
	var amount = 0;
	var current_weight = 0;	

	for (var i = 0; i < number_of_tags; i++) 
	{
		current_weight = document.getElementById('weights_'+i).value;

		if(current_weight == '')
			document.getElementById('weights_'+i).value = 0;

		if(!isWeight(current_weight) || current_weight < 0)
		{
			alert("The value of all weights must be a positive integer or zero.");
			return false;
		}

		amount += parseInt(current_weight);
	}

	document.getElementById('total_weight').value = amount;
}
*/


function isWeight(value) 
{
    var emailPattern = /\d/;
    var regex = new RegExp(emailPattern);

    return value.match(regex);
}


function ValidateNumber(e)
{
	var keynum;
	var keychar;
	var numcheck;
	
	if(window.event) // IE
	{
		keynum = e.keyCode;
		
	}
	else if(e.which) // Netscape/Firefox/Opera
	{
		keynum = e.which;
		
	}	
	keychar = String.fromCharCode(keynum);

	if(	keychar>= 0 && keychar <= 9 
		|| keynum == 8 			// backspace
		|| keynum <= 105 && keynum >= 96	// numpads 
		//|| keynum == 190 			// comma
		|| keynum == 46			// delete
		|| keynum == 37 || keynum == 39	// left and right arrow
	)	
		return true;
	else
		return false;
}

function CalculateTotal()
{
	var number_of_tags = document.getElementById('number_of_tags').value;
	var amount = 0;
	var current_weight = 0;	

	for (var i = 0; i < number_of_tags; i++) 
	{
		current_weight = document.getElementById('weights_'+i).value;
		
		if(current_weight == '')
			current_weight = 0;
		
		amount += parseInt(current_weight);

		if(current_weight != 0)
			document.getElementById('weights_'+i).value = parseInt(current_weight);
	}
	
	document.getElementById('total_weight').value = amount;	
}

function InsertZero()
{
	var number_of_tags = document.getElementById('number_of_tags').value;
	var amount = 0;
	var current_weight = 0;	

	for (var i = 0; i < number_of_tags; i++) 
	{	
		current_weight = document.getElementById('weights_'+i).value;

		if(current_weight == '')
			document.getElementById('weights_'+i).value = 0;
		
		if(current_weight.charAt(0) == '0')
			document.getElementById('weights_'+i).value = 0;

		if(isNaN(current_weight))
			document.getElementById('weights_'+i).value = parseInt(document.getElementById('weights_'+i).value);
	}
}

</script>
