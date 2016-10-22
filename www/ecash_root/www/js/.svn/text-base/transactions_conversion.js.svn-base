// SCRIPT FILE transactions_conversion.js
// This is written (and also overrides the old function binding from "transactions.js")
// to account for the conversion. Otherwise, do NOT use this function.

function SavePayments(base, save_btn)
{
	// [#40758] disable button so agent doesn't double-submit
	save_btn.disabled = true;

	var date;
	var amount;
	var total_arranged;
	var total_balance;
	var form = document.forms[base+'_payment_form'];
	var table = document.getElementById(base+'_payment_table');	
	var rows = table.getElementsByTagName("TR");
	var num_rows_visible = parseInt(document.getElementById("num_"+base).value);

	for (var i = 0; i < num_rows_visible; i++)
	{
		var date = Date.parse(document.getElementById(base+"_date_"+i).value);
		if (isNaN(date))
		{
			var str = "The dates for all intended \npayments";
			str += "must be valid.";
			alert(str);
			save_btn.disabled = false;
			return;
		}
		else
		{
			var now = new Date();
			//This is to eliminate the difference in minutes, seconds, etc.
			var now = new Date(now.getFullYear(), now.getMonth(), now.getDate());
			if (date < now)
			{
				var str = "There are dates earlier than the current\n";
				str += "date. Continue?";
				if (!confirm(str))
				{
					save_btn.disabled = false;
					return;
				}
			}
		}
	}
	total_arranged = parseFloat(document.getElementById(base+"_principal_val").value) +
		parseFloat(document.getElementById(base+"_service_val").value);
	total_balance = parseFloat(document.getElementById(base+"_total_balance").value);
	
	if ((total_arranged != total_balance) && (base == 'payment_arrangement'))
	{
		var str = "The exact amount of the balance must be paid\n";
		str += "when making payment arrangements.";		
		alert(str);
		save_btn.disabled = false;
		return;
	}
	
	var has_payment_arrangements = document.forms['Payment Arrangements'].has_payment_arrangements.value;

	if ( has_payment_arrangements == '0' )
	{
		form.submit();
	}
	else
	{
		if (confirm("This application already has payment arrangements. \nDo you wish to replace the existing payment arrangements with these new payment arrangements?"))
		{
			form.submit();
		}	
	}
	save_btn.disabled = false;
}
