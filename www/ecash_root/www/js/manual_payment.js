// SCRIPT FILE manual_payment.js
var data = new Object();

function FormatAmount(amt)
{
	var checking = false;
	if (amt < 0) checking = true;

	var dollars = 0;
	var cents = 0;
	var re = /(-?\d*)?([.](\d*))?/; 

	if (re.test(amt))
	{		
		dollars = RegExp.$1;
		cents = RegExp.$3;
		divisor = 1.00;
		for (var i = 0; i < (cents.length - 2); i++) divisor *= 10;
		cents = parseFloat(cents) / divisor;
		cents = Math.round(cents);
	}
	
	if (isNaN(dollars) || (dollars == null)) dollars = "0";
	if (isNaN(cents)) cents = "00";	
	else if (cents < 10) cents = cents + "0";
	amt = dollars + "." + cents;
	return amt;
}

function SetMode(selector)
{
	data.mode = selector.value;
}

function Initialize()
{	
	// Get the individual elements
	data.total_span = document.getElementById('manual_payment_label');
	data.unused_span = document.getElementById('unused_manual_payment_label');
	data.fees_span = document.getElementById('fees_label');
	data.princ_span = document.getElementById('principal_label');
	data.amount_paid = document.getElementById('manual_payment_amount');
	
	// Out labels and inputs
	data.fees_applied = document.getElementById('fees_applied');
	data.princ_applied = document.getElementById('princ_applied');
	
	data.fees_item = document.getElementById('fees_applied_item');
	data.princ_item = document.getElementById('princ_applied_item');

	data.fees_item.style.display = 'none';
	data.princ_item.style.display = 'none';

	data.fees_remain = document.getElementById('fees_remain');
	data.princ_remain = document.getElementById('princ_remain');

	// Set the hidden, submittable values
	data.fees_paid = document.getElementById("fees_paid");
	data.princ_paid = document.getElementById("princ_paid");

	// Now parse out the current outstanding values
	data.fees_outstanding = parseFloat(data.fees_span.innerHTML);
	data.princ_outstanding = parseFloat(data.princ_span.innerHTML);
	data.mode = "normal";
	data.current_screen = -1;
	data.new_screen = 0;
	SetScreen();
}

function Previous()
{
	data.new_screen = Math.max(0, data.current_screen - 1);
	SetScreen();
}

function Next()
{
	data.new_screen = (data.current_screen + 1) % 4;
	SetScreen();
}

function SetScreen()
{
	var radios;
	var found = false;
	var str;

	if (data.current_screen == 0)
	{
		// Check for a valid dollar amount
		var dollars = parseFloat(document.getElementById("manual_payment_amount").value);
		if (isNaN(dollars))
		{
			alert("Please enter a valid dollar amount.");
			return;
		}
		
		if (dollars < 0.0)
		{
			alert("Please enter a positive amount.");
			return;
		}
		
	  
		// Check for a source
		radios = document.forms[0].manual_payment_source;
		found = false;
		for (var i = 0; i < radios.length; i++)
		{
			if (radios[i].checked)
			{
				found = true;
				break;
			}
		}

		if (!found)
		{
			str = "Please select a payment source.";
			alert(str);
			return;
		}       
	}

	SetValues();

	data.current_screen = data.new_screen;
	SetTextSection();
	SetTopSection();
	SetBottomSection();
	SetButtons();
}

function SetValues()
{
	var remaining = parseFloat(data.amount_paid.value);
	data.total_span.innerHTML = FormatAmount(remaining);

	if (data.mode == 'normal')
	{
		if (remaining >= data.fees_outstanding)
		{
			data.fees_applied.innerHTML = FormatAmount(data.fees_outstanding);
			remaining -= data.fees_outstanding;
			data.fees_remain.innerHTML = "0.00";
		}
		else
		{
			var diff = data.fees_outstanding - remaining;
			data.fees_remain.innerHTML = FormatAmount(diff);
			data.fees_applied.innerHTML = FormatAmount(remaining);
			remaining = 0.0;
		}

		if (remaining >= data.princ_outstanding)
		{
			data.princ_applied.innerHTML = FormatAmount(data.princ_outstanding);
			remaining -= data.princ_outstanding;
			data.princ_remain.innerHTML = "0.00";			
		}
		else
		{
			var diff = data.princ_outstanding - remaining;
			data.princ_remain.innerHTML = FormatAmount(diff);
			data.princ_applied.innerHTML = FormatAmount(remaining);
			remaining = 0.0;
		}

	}
	else
	{
		var diff;
		var applied;
		
		// Set values for fees
		diff = data.fees_outstanding - applied;
		data.fees_item.value = FormatAmount(applied);
		data.fees_remain.innerHTML = FormatAmount(diff);
		remaining -= applied;		

		applied = parseFloat(data.princ_item.value);
		applied = CheckValue(applied, data.princ_outstanding);

		// Set values for principal
		diff = data.princ_outstanding - applied;
		data.princ_item.value = FormatAmount(applied);
		data.princ_remain.innerHTML = FormatAmount(diff);
		remaining -= applied;		
	}

	data.unused_span.innerHTML = FormatAmount(remaining);
}

function CheckValue(original, cap)
{
	var str;

	if (original > cap)
	{
		str = "Please do not apply more than  $";
		str += FormatAmount(cap) + ".";
		alert(str);
		return cap;
	}
	
	return original;
}

function SetTextSection()
{
	var pid;

	if (data.current_screen == 0)
	{
		pid = "text0";
	}
	else
	{
		if (data.mode == 'normal') pid = 'text1';
		else pid = 'text2';
	}

	var div = document.getElementById("textSection");
	var ps = div.getElementsByTagName("p");
	for (var i = 0; i < ps.length; i++)
	{
		if (ps[i].id == pid)
			ps[i].style.display = 'block';
		else
			ps[i].style.display = 'none';
	}

	div.style.display = 'none';
	div.style.display = 'block';
}

function SetTopSection()
{
	var toOpen, toClose;

	if (data.current_screen == 0)
	{
		toOpen = document.getElementById("topSectionTable0");
		toClose = document.getElementById("topSectionTable1");
	}
	else
	{
		toOpen = document.getElementById("topSectionTable1");
		toClose = document.getElementById("topSectionTable0");
	}
	
	toClose.style.display = 'none';
	toOpen.style.display = 'table';
}

function SetBottomSection()
{
	var current_table_id = "bottomSectionTable" + data.current_screen;	
	var div = document.getElementById("bottomSection");
	var tables = div.getElementsByTagName("table");
	
	if (data.mode == 'normal')
	{
		data.fees_item.style.display = 'none';
		data.princ_item.style.display = 'none';		
		data.fees_applied.style.display = 'inline';
		data.princ_applied.style.display = 'inline';
	}
	else
	{
		data.fees_item.style.display = 'inline';
		data.princ_item.style.display = 'inline';
		data.fees_applied.style.display = 'none';
		data.princ_applied.style.display = 'none';
	}


	for (var i = 0; i < tables.length; i++)
	{
		if (tables[i].id == current_table_id)
			tables[i].style.display = 'table';
		else
			tables[i].style.display = 'none';
	}	
}

function SetButtons()
{
	var nextButton = document.getElementById("nextButton");
	var prevButton = document.getElementById("prevButton");
	var finishButton = document.getElementById("finishButton");

	nextButton.style.display = 'none';
	prevButton.style.display = 'none';
	finishButton.style.display = 'none';
	
	switch(data.current_screen)
	{
	case 0: nextButton.style.display = 'inline'; break;
	case 1:
		prevButton.style.display = 'inline';
		finishButton.style.display = 'inline';
		break;
	}       	
}

function Finish()
{
	if (data.mode == 'normal')
	{
		data.fees_paid.value = parseFloat(data.fees_applied.innerHTML);
		data.princ_paid.value = parseFloat(data.princ_applied.innerHTML);
	}
	else
	{
		data.fees_paid.value = parseFloat(data.fees_item.value);
		data.princ_paid.value = parseFloat(data.princ_paid.value);
	}

	var form = document.forms['manual_payment_form'];
	form.submit();
}
