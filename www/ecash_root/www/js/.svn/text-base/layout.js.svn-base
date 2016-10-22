// SCRIPT FILE layout.js
var selectInitials = new Object();
var w;
var popped_email_warning=false;
var popped_fax_warning=false;

function Update_Deposit_Data() {
	var daily_cash_form = document.getElementById('daily_cash_form');
//	var date = new Date();
//	date.setTime(Date.parse(document.getElementById('specific_date_display').value));
	var start_date = new Date(serverdate);
	start_date.setTime(Date.parse(document.getElementById('start_date_display').value));
	var end_date = new Date();
	end_date.setTime(Date.parse(document.getElementById('end_date_display').value));
	var today = new Date(serverdate);
	today.setHours(0);
	today.setMinutes(0);
	today.setSeconds(0);
	today.setMilliseconds(0);

	//if (today.getTime() == date.getTime()) {
		if (confirm("You are viewing the cash report. Would you like to set the deposit data?\nClick Cancel to just view the report \nIf no values are entered, the last entered value for the end of the range will be used")) {
			var dialog = OpenDialogSized('', 350, 300);
			var doc = dialog.document;
			doc.write('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">');
			doc.write('<html><head><title>Update Daily Cash</title>');
			doc.write('<link rel="stylesheet" href="css/style.css">');
			doc.write('<script type="text/javascript" src="js/calendar/calendar.js"></script>');
			doc.write('<script type ="text/javascript" src="js/layout.js"></script>');
			doc.write('<script type="text/javascript" src="js/calendar/lang/calendar-en.js"></script>');
			doc.write('<script type="text/javascript" src="js/transactions.js"></script>');
			doc.write('<script type="text/javascript" src="js/overlib.js"></script>');
			doc.write('<script type="text/javascript" src="js/disable_link.js"></script>');
			doc.write('<script type="text/javascript" src="js/documents.js"></script>');
			doc.write('<script type="text/javascript" src="js/nada.js"></script>');
			doc.write('</head><body><form id="deposit_form"><table>');
			
	//		doc.write('<tr><td style="text-align: right">Credit Card Payments</td>');
	//		doc.write('<td align="left"><input id="credit_card_payments" value="0" /></td></tr>');

			doc.write('<tr><td style="text-align: right">Lead Acquisition Cost</td>');
			doc.write('<td align="left"><input id="lead_acquisition_cost" value="0" /></td></tr>');
			
			doc.write('<tr><td style="text-align: right">DataX Cost</td>');
			doc.write('<td align="left"><input id="datax_cost" value="0" /></td></tr>');
			
			doc.write('<tr><td style="text-align: right">Other Cost</td>');
			doc.write('<td align="left"><input id="hms_other_cost" value="0" /></td></tr>');
			
			
			
//			doc.write('<tr><td style="text-align: right">Western Union Deposit</td>');
//			doc.write('<td align="left"><input id="western_union_deposit" value="0" /></td></tr>');
			doc.write('<tr><td style="text-align: right">Money Order Deposit</td>');
			doc.write('<td align="left"><input id="money_order_deposit" value="0" /></td></tr>');
//mantis:2171:16603 quick_check_deposit value is now calculated automatically
//			doc.write('<tr><td style="text-align: right">Quick Check Deposit</td>');
//			doc.write('<td align="left"><input id="quick_check_deposit" value="0" /></td></tr>');
//			doc.write('<tr><td style="text-align: right">Moneygram Deposit</td>');
//			doc.write('<td align="left"><input id="moneygram_deposit" value="0" /></td></tr>');
//			doc.write('<tr><td style="text-align: right">CRSI Recovery</td>');
//			doc.write('<td align="left"><input id="crsi_recovery" value="0" /></td></tr>');
			//doc.write('<tr><td style="text-align: right">Pinion Recovery</td>');
			//doc.write('<td align="left"><input id="pinion_recovery" value="0" /></td>');
			doc.write('<tr><td align="center" colspan="2"><input type="button" value="Save" onclick="Save_Deposit_Data(document.getElementById(\'deposit_form\'));" /><input type="button" value="Cancel" onclick="window.close()"/></td>');
			doc.write('</tr></table></form></body></html>');
			doc.close();
			return;
		}
	//}


	daily_cash_form.submit();
}


function Display_Daily_Cash_Report_Email_Form(domain) {
	var dialog = OpenDialogSized('', 350, 250);
	var doc = dialog.document;
	doc.write('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">');
	doc.write('<html><head><title>Email Daily Cash Report</title>');
	doc.write('<link rel="stylesheet" href="css/style.css">');
	doc.write('<script type="text/javascript" src="js/calendar/calendar.js"></script>');
	doc.write('<script type="text/javascript" src="js/calendar/lang/calendar-en.js"></script>');
	doc.write('<script type="text/javascript" src="js/transactions.js"></script>');
	doc.write('<script type="text/javascript" src="js/overlib.js"></script>');
	doc.write('<script type="text/javascript" src="js/disable_link.js"></script>');
	doc.write('<script type="text/javascript" src="js/documents.js"></script>');
	doc.write('<script type="text/javascript" src="js/nada.js"></script>');
	doc.write('</head><body><form id="deposit_form" action="http://' + domain + '/?module=reporting&mode=daily_cash&action=download_report" method="post"><table>');
	doc.write('<tr><td style="text-align: right">Enter Email Addresses</td>');
	doc.write('<td align="left"><input name="email_to" value="" /></td></tr>');
	doc.write('<tr><td align="center" colspan="2"><input type="submit" value="Send" /><input type="button" value="Cancel" onclick="window.close()"/></td>');
	doc.write('</tr></table></form></body></html>');
	doc.close();
}

function Save_Deposit_Data(form) {
	var parent = opener.document;
//	parent.getElementById('deposit[credit card payments]').value = form.credit_card_payments.value;

////	parent.getElementById('deposit[western union deposit]').value = form.western_union_deposit.value;
	parent.getElementById('deposit[money order deposit]').value = form.money_order_deposit.value;
	//parent.getElementById('deposit[quick check deposit]').value = form.quick_check_deposit.value;
	parent.getElementById('deposit[lead acquisition cost]').value = form.lead_acquisition_cost.value;
	
	parent.getElementById('deposit[datax cost]').value = form.datax_cost.value;
	
	parent.getElementById('deposit[hms other cost]').value = form.hms_other_cost.value;
	
//	parent.getElementById('deposit[moneygram deposit]').value = form.moneygram_deposit.value;
//	parent.getElementById('deposit[crsi recovery]').value = form.crsi_recovery.value;
	//parent.getElementById('deposit[pinion recovery]').value = form.pinion_recovery.value;
	parent.getElementById('update_deposit_data').value = '1';
	parent.getElementById('daily_cash_form').submit();
	window.close();
}

function CloseOutBusinessDay()
{
	var prev_close = document.getElementById("closing_time").value;
	var choice = true;

	var str = "The previous closing time is:\n" + prev_close + "\n";
	str += "Continue anyway?";

	if (prev_close.length > 0) {
		choice = confirm(str);
	} else {
	 choice = confirm("Are you sure you want to close out the batch?");
	}


	if (choice)
	{
		location.href = "/?module=loan_servicing&mode=batch_mgmt&action=close_out";
	}

}

function SendBatch(domain)
{
	var str = "This will send all transactions. Continue?";

	if (confirm(str))
	{
	  location.href = "http://" + domain + "/?module=loan_servicing&mode=batch_mgmt&action=send_batch";
	}
}

function ProcessCards(domain)
{
	var str = "This will process all transactions. Continue?";

	if (confirm(str))
	{
	  location.href = "http://" + domain + "/?module=loan_servicing&mode=batch_mgmt&action=process_cards";
	}
}

/*
*/
function SaveInitials()
{
	var selects = document.getElementsByTagName("SELECT");
	for (var i = 0; i < selects.length; i++)
	{
		selectInitials[selects[i].name] = selects[i].selectedIndex;
	}
}


/*
*/
function SetDisplay(layout_id, group_id, layer_id, mode, buttons)
{
	if (buttons == null) buttons = modestring + "_buttons";

	// Various regexp tests
	var button_match = /\w*_buttons\b/i;
	var group_match = new RegExp("layout" + layout_id + "group" + group_id);
	var layout_match = /\blayout\d\b/i;
	var exact_match = "layout" + layout_id + "group" + group_id + "layer" + layer_id + mode;
	var float_menu_match = /\w*_float_menu\b/i;
	var scroller_match = /\w*scroll\b/i;

	/* Iterate through the layouts/layers and activate/deactivate the
	 * right business
	 */
	var divs = document.getElementsByTagName("DIV");
	for (var i = 0; i < divs.length; i++)
	{
		// Test to see if it's a layout division
		if (layout_match.test(divs[i].id))
		{
			if (("layout" + layout_id) == divs[i].id)
			{
				divs[i].style.display = "block";
			}
			else
			{
				divs[i].style.display = "none";
			}
		}
		else if (group_match.test(divs[i].id))   // Is it a layer in the group mentioned?
		{
			if (exact_match == divs[i].id)
			{
				divs[i].style.display = "block";
			}
			else
			{
				divs[i].style.display = "none";
			}
		}
		else if (button_match.test(divs[i].id))
		{
			if (divs[i].id == buttons)
			{
				divs[i].style.display = "block";
			}
			else
			{
				divs[i].style.display = "none";
			}
		}
		else if (float_menu_match.test(divs[i].id))
		{
			divs[i].style.visibility = "hidden";
		}

		if (scroller_match.test(divs[i].id))
		{
			if ((exact_match + 'scroll') == divs[i].id)
			{
				divs[i].style.overflow = "auto";
				divs[i].style.display = "block";
			}
		}
	}
	if(document.getElementById('schedule_preview')) document.getElementById('schedule_preview').style.display =  'none';
	CheckDependencies(layout_id, group_id, layer_id, buttons);
}



/*
*/
function CheckDependencies(layout_id, group_id, layer_id, buttons)
{

	var str = "layout" + layout_id + "group" + group_id + "layer" + layer_id;
	var docs_match = /layout0group1layer[12345]/i;
	var gi_match = /layout0group0layer[01237]/i;
	var summary_match = /layout1group1layer2/i;
	var trans_match = /layout1group1layer[01]/i;
	var comment_information_match = 'layout0group0layer8';
	var application_status_match = 'layout0group0layer10';
	var comment_match = /layout0group\d+layer\d+/i;
	
	/* If the layer is "send_docs" or "receive_docs",
	 * we need to activate the "documents" layer
	 */
	if (docs_match.test(str))
   {
 		SetDisplay(0,0,4,'view', buttons);
		SetDisplay(0,3,1,'view', buttons);
	}
	else if (comment_information_match == str)
	{
		//if contact information is selected, show general info 
		SetDisplay(0,1,0,'view', buttons);
		//and show the contact info 'edit' page where comments normally are
		SetDisplay(0, 2, 1, 'edit', buttons);
	}
	else if (application_status_match == str)
	{
		SetDisplay(0,1,0,'view', buttons);
		SetDisplay(0,2,2,'edit',buttons);
	}
	else if (gi_match.test(str))
	{
		SetDisplay(0,1,0,'view', buttons);
		SetDisplay(0,3,0,'view', buttons);
	}
	else if (summary_match.test(str))
	{
		SetDisplay(1,0,1,'view', buttons);
	}
	else if (trans_match.test(str))
	{
		SetDisplay(1,0,0,'view', buttons);
	}
	else if (comment_match.test(str) && str != 'layout0group2layer1' && str != 'layout0group2layer0' && str != 'layout0group2layer2')
	{
		SetDisplay(0, 2, 0, 'view', buttons);
	}

	if (window['display_' + str]) {
		window['display_' + str]();
	}
}


/*
*/
function OpenDialog(url)
{
	var opts = 'toolbar=no,location=no,directories=no,status=no,menubar=no';
	opts += ',scrollbars=no,resizable=no,copyhistory=no,width=400';
	opts += ',height=200,left=200,top=200,screenX=200,screenY=200';

	window.open(url, 'pop_up_comment', opts);
}

function OpenDialogSized(url, width, height)
{
	var opts = 'toolbar=no,location=no,directories=no,status=no,menubar=no';
	opts += ',scrollbars=no,resizable=no,copyhistory=no,width=' + width;
	opts += ',height=' + height + ',left=200,top=200,screenX=200,screenY=200';

	return window.open(url, 'pop_up_comment', opts);
}

// see: http://www.htmlcodetutorial.com/linking/linking_famsupp_72.html
// This is a good approach to popup windows because it will work even if javascript is turned off.
// Make sure to use target="_blank" in the <a target="_blank" ...> </a> so that if javascript is turned
// off a new window will be opened but if javascript is turned on, a new window will popup with the
// desired dimensions and characteristics.  The approach in the HtmlCodeTutorial is supposed to take
// care of the issue of backgrounded popups being brought into focus but I think the new version of
// firefox is preventing that feature.
function OpenDialogSizedHelper( mylink, width, height )
{
	if (! window.focus) return true;
	var href;
	if (typeof(mylink) == 'string')
	{
		href=mylink;
	}
	else
	{
		href=mylink.href;
	}
	OpenDialogSized( href, width, height );
	return false;
}

function OpenDialogTall(url)
{
	var args = OpenDialogTall.arguments.length;
	var opts = 'toolbar=no,location=no,directories=no,status=no,menubar=no';
	opts += ',scrollbars=no,resizable=no,copyhistory=no,width=400';
	opts += ',height=215,left=200,top=200,screenX=200,screenY=200';

	window.open(url, 'pop_up_comment', opts);
}

/*
*/
function Check_Data(ask_confirm, return_did_save)
{
	if(ask_confirm == null)	ask_confirm = true;
	if(return_did_save == null) return_did_save = false;

	// Iterate through the forms

	for (var i = 0; i < document.forms.length; i++)
	{
		// Cycle through the fields for this form.
		for(var j = 0; j < document.forms[i].elements.length; j++)
		{
			var elem = document.forms[i].elements[j];
			// If the hidden field does not match it's saved counter part
			// throw an error
			if ((!(/select-\w*/.test(elem.type)) &&
			    (elem.value != elem.defaultValue)) ||
			    (selectInitials[elem.name] != elem.selectedIndex))
			{
				if(ask_confirm)
				{
					if(confirm('Changes have been made to ' + document.forms[i].name + ', save?'))
					{
						document.forms[i].submit();
					}
					else
					{
						document.forms[i].reset();
						return false;
					}
				}
				else
				{
					document.forms[i].submit();
				}
			}
		}
	}

	return return_did_save;
}





/**
 * DHTML date validation script. Courtesy of SmartWebby.com (http://www.smartwebby.com/dhtml/)
 */
function isInteger(s){
	var i;
    for (i = 0; i < s.length; i++){
        // Check that current character is number.
        var c = s.charAt(i);
        if (((c < "0") || (c > "9"))) return false;
    }
    // All characters are numbers.
    return true;
}

function stripCharsInBag(s, bag){
	var i;
    var returnString = "";
    // Search through string's characters one by one.
    // If character is not in bag, append to returnString.
    for (i = 0; i < s.length; i++){
        var c = s.charAt(i);
        if (bag.indexOf(c) == -1) returnString += c;
    }
    return returnString;
}

function daysInFebruary (year){
	// February has 29 days in any year evenly divisible by four,
    // EXCEPT for centurial years which are not also divisible by 400.
    return (((year % 4 == 0) && ( (!(year % 100 == 0)) || (year % 400 == 0))) ? 29 : 28 );
}
function DaysArray(n) {
	for (var i = 1; i <= n; i++) {
		this[i] = 31
		if (i==4 || i==6 || i==9 || i==11) {this[i] = 30}
		if (i==2) {this[i] = 29}
   }
   return this
}

function isDate(dtStr){

	var minYear=1900;
	var maxYear=2100;
	var dtCh= "/";

	var daysInMonth = DaysArray(12)
	var pos1=dtStr.indexOf(dtCh)
	var pos2=dtStr.indexOf(dtCh,pos1+1)
	var strMonth=dtStr.substring(0,pos1)
	var strDay=dtStr.substring(pos1+1,pos2)
	var strYear=dtStr.substring(pos2+1)
	strYr=strYear
	if (strDay.charAt(0)=="0" && strDay.length>1) strDay=strDay.substring(1)
	if (strMonth.charAt(0)=="0" && strMonth.length>1) strMonth=strMonth.substring(1)
	for (var i = 1; i <= 3; i++) {
		if (strYr.charAt(0)=="0" && strYr.length>1) strYr=strYr.substring(1)
	}
	month=parseInt(strMonth)
	day=parseInt(strDay)
	year=parseInt(strYr)
	if (pos1==-1 || pos2==-1){
		alert("The date format should be : mm/dd/yyyy")
		return false
	}
	if (strMonth.length<1 || month<1 || month>12){
		alert("Please enter a valid month")
		return false
	}
	if (strDay.length<1 || day<1 || day>31 || (month==2 && day>daysInFebruary(year)) || day > daysInMonth[month]){
		alert("Please enter a valid day")
		return false
	}
	if (strYear.length != 4 || year==0 || year<minYear || year>maxYear){
		alert("Please enter a valid 4 digit year between "+minYear+" and "+maxYear)
		return false
	}
	if (dtStr.indexOf(dtCh,pos2+1)!=-1 || isInteger(stripCharsInBag(dtStr, dtCh))==false){
		alert("Please enter a valid date")
		return false
	}
	return true
}


function ValidateDate(max_loan)  //mantis:2812 - max_loan passed
{
	//mantis:2812
	var loan_amount = document.getElementById('fund_amount').value;

	if(loan_amount > max_loan)
	{
		alert("The loan amount selected exceeds max loan amount ($" + max_loan + ".00) for the given income.");
		return;
	}
	// end mantis:2812
	
	if (document.getElementById('date_first_payment'))
	{
		var dt = document.getElementById('date_first_payment').value;
	
		if (dt.length == 10)
		{
			if (isDate(dt) == true)
			{
				var enteredDay = dt.substring(3, 5);
				var enteredMonth = dt.substring(0, 2);
				var enteredYear = dt.substring(6, 10);
	
				var now = new Date(serverdate);
				var nowMonth = now.getMonth() + 1;
				var nowDay = now.getDate();
				var nowYear = now.getFullYear();
	
				// make sure date is in the future
				if (nowYear == enteredYear)
				{
					if (nowMonth == enteredMonth)
					{
						if (nowDay < enteredDay)
						{
							if (Is_First_Due_Date_Valid())
							{
								document.getElementById('date_first_payment_day').value = "" + enteredDay;
								document.getElementById('date_first_payment_month').value = "" + enteredMonth;
								document.getElementById('date_first_payment_year').value = "" + enteredYear;
								document.getElementById('new_first_due_date').value = "yes";
								document.getElementById('Application_form').submit();
							}
	
							return;
						}
						else
						{
							alert('The day is too far in the past.');
							return;
						}
					}
					else if (nowMonth < enteredMonth)
					{
						document.getElementById('date_first_payment_day').value = "" + enteredDay;
						document.getElementById('date_first_payment_month').value = "" + enteredMonth;
						document.getElementById('date_first_payment_year').value = "" + enteredYear;
						document.getElementById('new_first_due_date').value = "yes";
						document.getElementById('Application_form').submit();
						return;
					}
					else
					{
						alert('The month is too far in the past.');
						return;
					}
				}
				else if (nowYear < enteredYear)
				{
					document.getElementById('date_first_payment_day').value = "" + enteredDay;
					document.getElementById('date_first_payment_month').value = "" + enteredMonth;
					document.getElementById('date_first_payment_year').value = "" + enteredYear;
					document.getElementById('new_first_due_date').value = "yes";
					document.getElementById('Application_form').submit();
					return;
				}
				else
				{
					alert('The year is too far in the past.');
					return;
				}
			}
		}
		else
		{
			alert('The date is not in a valid format. It should be\n in the format of mm/dd/yyyy.');
			return;
		}
	}
	else
	{
		document.getElementById('new_first_due_date').value = "no";
		document.getElementById('Application_form').submit();
	}
}


function Is_First_Due_Date_Valid()
{
	var result = false;

    var dueIsPayDate = false;
    var dueIsBeyond = false;

	var due_date = document.Application.date_first_payment.value;
	var fund_date = document.Application.fund_date.value;
	var paydate_1 = document.Application.paydate_0.value;
	var paydate_2 = document.Application.paydate_1.value;
	var paydate_3 = document.Application.paydate_2.value;
	var paydate_4 = document.Application.paydate_3.value;
	var due_date_offset = document.Application.due_date_offset.value;

    var payDate1Parts = paydate_1.split("-");
    var payDate2Parts = paydate_2.split("-");
    var payDate3Parts = paydate_3.split("-");
    var payDate4Parts = paydate_4.split("-");
    var dueDateParts = due_date.split("/");

    var payDate1 = new Date(payDate1Parts[2], payDate1Parts[0]-1, payDate1Parts[1]);
    var payDate2 = new Date(payDate2Parts[2], payDate2Parts[0]-1, payDate2Parts[1]);
    var payDate3 = new Date(payDate3Parts[2], payDate3Parts[0]-1, payDate3Parts[1]);
    var payDate4 = new Date(payDate4Parts[2], payDate4Parts[0]-1, payDate4Parts[1]);
    var dueDate = new Date(dueDateParts[2], dueDateParts[0]-1, dueDateParts[1]);
    
    var payDayDiff = (payDate2.getTime() - payDate1.getTime()) / (1000 * 60 * 60 * 24);
    var payDueDiff = (dueDate.getTime() - payDate1.getTime()) / (1000 * 60 * 60 * 24);
    
    if ((payDate1.getTime() == dueDate.getTime()) ||
        (payDate2.getTime() == dueDate.getTime()) ||
        (payDate3.getTime() == dueDate.getTime()) ||
        (payDate4.getTime() == dueDate.getTime()) ) {
        dueIsPayDate = true;
    } else if (payDate4 < dueDate) {
        dueIsBeyond = true;
        if ((payDueDiff % payDayDiff) == 0) dueIsPayDate = true;
        else {
            if ((payDate1Parts[1] == dueDateParts[1]) ||
                (payDate2Parts[1] == dueDateParts[1]) ||
                (payDate3Parts[1] == dueDateParts[1]) ||
                (payDate4Parts[1] == dueDateParts[1]))
            {
                dueIsPayDate = true;
            }
        }
    }
    
    // hack...
	var due_date1 = due_date.replace('\/', '-');
	var new_due_date = due_date1.replace('\/', '-');

	// Need to replaces the dashes with slashes for the date object to
	// recognize the date format.
	var new_fund_date = fund_date.replace(/-/g,'/');

	if (dueIsPayDate) {
		var fund_date_obj = new Date(new_fund_date);
		var due_date_obj = new Date(due_date);
		var oneDay = 1000 * 60 * 60 * 24;

		var dayDiff = Math.ceil((due_date_obj.getTime() - fund_date_obj.getTime())/(oneDay));

		if (dayDiff < due_date_offset) {
            var answer = confirm ("Fund this application even though the due date appears within the " + due_date_offset + " of the funding date? (" + dayDiff +" days)");
            if (answer) result = true;
		} else {
			result = true;
		}
	} else if (dueIsBeyond) {
        var answer = confirm ("Fund this application even though the due date appears to miss a paydate?");
        if (answer) result = true;
    } else {
        var answer = confirm ("Fund this application even though the due date appears to miss a paydate?");
        if (answer) result = true;
	}

	return result;
}





function Check_Due_Date(change_status,app_id,email)
{
	var app_forms = document.getElementsByName( "Application" );

	if( app_forms.length == 1 )
	{
		app_form = app_forms[0];

		switch(change_status)
		{
		case 'Approve':
		case 'Additional':
			OpenDialogTall('/?action=loan_action&type='+change_status+'&application_id='+app_id+'&customer_email='+email);
			break;
		case 'Fund_Moneygram':
		case 'Fund_Check':
			if (Is_First_Due_Date_Valid())
			{
			
				
				var comment = document.getElementById('change_status_comment');
				comment.value = prompt("Check #");
				if(comment.value)
				{
					var buttons = document.getElementsByName('submit_button');
					for(i=0; i < buttons.length; i++)
					{
						if(buttons[i].id != "application_submit_button")
						{
							buttons[i].className = 'button2disabled';
							buttons[i].disabled = true;
						}
						
					}
					
					document.getElementById('application_submit_button').value = change_status;
					document.getElementById('change_status').submit();
				}
			}
			break;
		case 'Hotfile':
				document.getElementById('application_submit_button').value = change_status;
				document.getElementById('change_status').submit();
			break;
		case 'Fund':
		case 'Fund_Paydown':
		case 'Fund_Payout':
		default:
			if (Is_First_Due_Date_Valid())
			{
				/*
				var buttons = document.getElementsByName('submit_button');
				for(i=0; i < buttons.length; i++)
				{
					if(buttons[i].id != "application_submit_button")
					{
						buttons[i].className = 'button2disabled';
						buttons[i].disabled = true;
					}
					
				}
				*/
				/**
				  * This is pretty cheesy, but we're copying a value from one form 
				  * to another.  This is because we include the button file from another
				  * source and the buttons often have their own forms and you can't
				  * nest forms. [BrianR][#15323]
				  */
				if(ig = document.getElementById("investor_group_list"))
				{
					document.getElementById('investor_group').value = ig[ig.selectedIndex].value;
				}

				/**
				  * This is pretty cheesy, but we're copying a value from one form 
				  * to another.  This is because we include the button file from another
				  * source and the buttons often have their own forms and you can't
				  * nest forms. [BrianR][#15323]
				  */
				if(ig = document.getElementById("investor_group_list"))
				{
					document.getElementById('investor_group').value = ig[ig.selectedIndex].value;
				}

				//document.getElementById('application_submit_button').value = change_status;
				//document.getElementById('change_status').submit();
				OpenDialogTall('/fund_types.php?type='+change_status+'&application_id='+app_id);
			}
			break;
		}
	}
	else if( app_form.length > 1 )
	{
		//  Somebody messed something up.  There should only be 1 Application form
		return false;
	}

	return true;
}

function ClaimApp()
{
	document.getElementById('change_status_action').value = 'ClaimApp';
	document.getElementById('change_status').submit();
	return true;
}

function ReminderRemove()
{
	document.getElementById('change_status_action').value = 'ReminderRemove';
	document.getElementById('change_status').submit();
	return true;
}


//this takes the name of the comment field to update, and the name of the form to submit
function Add_Comment(comment_id, form_id)
{
	comment_element = document.getElementById(comment_id);
	var comment = window.prompt ("Please enter comments:");

	if((comment != null) && comment.length != 0)
	{
		comment_element.value = comment;
		document.getElementById(form_id).submit();
	}
}

function Update_Status_Direct(new_status, display_name)
{
	choice = confirm("Are you sure you want to change the status to " + display_name); //mantis:4454
	if(!choice)
		return false;

	document.getElementById('application_submit_button').value = new_status;

	var form = document.getElementById('change_status');
	
	var inputs = form.elements;

	form.submit();
}

/*
*/
function Update_Status(button_name,app_id,email)
{
	OpenDialog('/?action=loan_action&type='+ button_name +'&application_id='+ app_id+'&customer_email='+email);
}


/*
 * This is used to set the contact info when it chnanges.
 */
function Set_Contact(field, setting, panel)
{
	document.getElementById('new_contact_status').value = setting;
	document.getElementById('change_field').value = field;
	if (panel) {
		document.getElementById('change_contact_panel').value = panel;
	}
	document.getElementById('change_contact').submit();
}

//mantis:4646
function Set_Contact1(setting, panel)
{
	if(document.getElementById(setting + '_phone_home').checked)
	{
		document.getElementById('phone_home').value = 'phone_home';
	}

	if(document.getElementById(setting + '_phone_cell').checked)
	{
		document.getElementById('phone_cell').value = 'phone_cell';
	}

	if(document.getElementById(setting + '_phone_work').checked)
	{
		document.getElementById('phone_work').value = 'phone_work';
	}

	if(document.getElementById(setting + '_customer_email').checked)
	{
		document.getElementById('customer_email').value = 'customer_email';
	}

	if(document.getElementById(setting + '_ref_phone_1').checked)
	{
		document.getElementById('ref_phone_1').value = 'ref_phone_1';
	}
	if(document.getElementById(setting + '_ref_phone_2').checked)
	{
		document.getElementById('ref_phone_2').value = 'ref_phone_2';
	}
	if(document.getElementById(setting + '_ref_phone_3').checked)
	{
		document.getElementById('ref_phone_3').value = 'ref_phone_3';
	}
	if(document.getElementById(setting + '_ref_phone_4').checked)
	{
		document.getElementById('ref_phone_4').value = 'ref_phone_4';
	}
	if(document.getElementById(setting + '_ref_phone_5').checked)
	{
		document.getElementById('ref_phone_5').value = 'ref_phone_5';
	}
	if(document.getElementById(setting + '_ref_phone_6').checked)
	{
		document.getElementById('ref_phone_6').value = 'ref_phone_6';
	}

	//mantis:5073
	if(document.getElementById(setting + '_street').checked)
	{
		document.getElementById('street').value = 'street';
	}

	document.getElementById('new_contact_status').value = setting;

	if (panel)
		document.getElementById('change_contact_panel').value = panel;

	document.getElementById('change_contact').submit();
}


function Set_SSN(panel)
{
	if(!isNotWhiteSpace(document.getElementById('do_not_loan_comment').value))
	{
		alert("The Comment field cannot be empty.");
		return false;
	}
	document.getElementById('do_not_loan_comm').value = document.getElementById('do_not_loan_comment').value;

	if(document.getElementById('ssn_check_box').value == "checked")
	{
		document.getElementById('ssn').value = 'ssn';
	}

	document.getElementById('new_contact_status').value = 'do_not_loan';

	if (panel)
		document.getElementById('change_contact_panel').value = panel;

	document.getElementById('change_contact').submit();
}

function myjs()
{
	var company_element = document.getElementById("external_collection_companies");
	var company = company_element.options[company_element.selectedIndex].value;
	var company_id = document.getElementById("stupid_company_id").value;
	var ec_frame = document.getElementById('ec_frame');

	document.getElementById('ext_count').innerHTML = 'Processing...';

	setTimeout('Check_Done()',5000);

	return true;
}


function Process_Quick_Checks_PDF()
{
	var company_element = document.getElementById("quick_checks");
	var company_id = document.getElementById("company_id").value;
	var qc_frame = document.getElementById('qc_frame');

	qc_frame.onload = 'top.location.reload();';
	qc_frame.src = 'ec_download.php?company_id=' + company_id;

	setTimeout('Check_Done()',1000);

	return false;
}

function Process_Quick_Checks()
{
	var company_element = document.getElementById("quick_checks");
	var company_id = document.getElementById("company_id").value;
	var qc_frame = document.getElementById('qc_frame');

	qc_frame.onload = 'top.location.reload();';
	qc_frame.src = 'ec_download.php?company_id=' + company_id;

	setTimeout('Check_Done()',1000);

	return false;
}

function Check_Done()
{
	document.getElementById('ext_count').innerHTML = 'There are 0 application(s) ready.';
}


function Select_Send_Document(document_name, send_methods)
{
	// Assumptions:
	// Form name is SendDocuments
	// Checkbox array name is document_list

	var send_document = new Array();
	var dname;
	var dmethod;
	var ndocs=0;

	var s1 = send_methods.split("|");

	for (i=0; i<s1.length; i += 2) {
		dname = s1[i];
		dmethod = s1[i+1];
		send_document[dname] = dmethod;
		ndocs++;
	}

	var disable_email = false;
	var disable_fax = false;

	var send_method;

	// Loop through all checked boxes
	for (i=1; i <= ndocs; i++) {

		id = "cbid_" + i;

		if (document.getElementById(id).checked) {

			cname = document.getElementById(id).value;
			send_method = send_document[cname];

			if (send_document[cname] == 'email') {
				disable_fax = true;
			}
			else if (send_document[cname] == 'fax') {
				disable_email = true;

			}

		}

	}

	// Commented out per Mantis 2143 - will save code just in case.

	/**
	if (disable_fax & !popped_fax_warning) {
		alert("You have selected one or more 'e-mail only' documents. The Send Fax button will be disabled until all 'e-mail only' documents are unchecked.")
		popped_fax_warning = true;
	}
	if (disable_email && !popped_email_warning) {
		alert("You have selected one or more 'FAX only' documents. The Send Email button will be disabled until all 'FAX only' documents are unchecked.")
		popped_email_warning = true;
	}
	*/

	// Set the button enabled state.
	// This prevents user from using the wrong send method for a document.
	document.SendDocuments.send_fax.disabled=disable_fax;
	document.SendDocuments.send_email.disabled=disable_email;

}

// Confirmation of Amortization - Mantis:10549
function Confirm_Amortization_Toggle(url)
{
	if(confirm("Are you sure you want to toggle the Amortization status on this account?"))
	{
		location.href = url;
	}
}


// [mantis:3304]
var is_doc_selected = false; // global for VerifySelection and verifyDestination

function VerifySelection(is_ch)
{
	is_doc_selected = is_ch;
}

// [mantis:3279]
function verifyDestination(field)
{
	if(!is_doc_selected) // [mantis:3304]
	{
		alert("Please select a document.");
		return false
	}

	if (field == 'Send Fax')
	{
		var fax = document.SendDocuments.phone_fax;

		faxStrip = fax.value.replace(/[^\d]/g, '');
		if(faxStrip.length < 10 || faxStrip.length > 11)
		{
			alert("Please enter a valid fax number with 10 digits.");
			return false;
		}
		else
		{
			if (faxStrip.length == 10) {
				faxStrip = '1' + faxStrip;
			}
			fax.value = faxStrip
			return true;
		}
	}
	 else if(field == 'Send Email')
	{
		var e_mail = document.SendDocuments.customer_email.value;

		if(!isValidEmail(e_mail))
		{
			alert("Please verify the format of the email address.");
			return false;
		}
		else
			return true;
	}
	else if(field == 'Send ESig')
	{
		var e_sig = document.ESigDocuments.customer_email.value;

		if(!isValidEmail(e_sig))
		{
			alert("Please verify the format of the email address.");
			return false;
		}
		else
			return true;
	}
	else if(field == 'Email Package')
	{
		var e_pack = document.PackagedDocuments.customer_email.value;

		if(!isValidEmail(e_pack))
		{
			alert("Please verify the format of the email address.");
			return false;
		}
		else
			return true;
	}
	else;

}

function validateEmail(email)
{
	if(!isValidEmail(email))
	{
		alert("Please verify the format of the email address.");
		return false;
	}
	else
		return true;
}

function isValidEmail(email)
{
    var emailPattern = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    var regex = new RegExp(emailPattern);

    return email.match(regex);
}

//mantis:4284
function disableContains(index, is_start_with_disabled)
{
	//mantis:4926
	if(is_start_with_disabled == '1')
	{
		if(document.getElementById('criteria_type_' + index).value == 'social_security_number')
		{
			if(document.getElementById('search_deliminator_' + index).value == 'starts_with')
			{
				document.getElementById('is_' + index).selected = true;
			}

			document.getElementById('starts_with_' + index).disabled = true;
		}
		else
		{
			document.getElementById('starts_with_' + index).disabled = false;
		}
	}
	//end mantis:4926

	if(document.getElementById('criteria_type_' + index).value == 'email'
		|| document.getElementById('criteria_type_' + index).value == 'phone') //mantis:4313 - add phone
	{
		if(document.getElementById('search_deliminator_' + index).value == 'contains')
		{
			document.getElementById('is_' + index).selected = true;
		}

		document.getElementById('contains_' + index).disabled = true;
	}
	else
	{
		document.getElementById('contains_' + index).disabled = false;
	}
}

//mantis:2016
function verifySearchCriteriaFormat()
{
	var search_criteria = document.getElementById('AppSearchAllCriteria1').value;
	if(!isNotWhiteSpace(search_criteria))
	{
		alert("The Search Criteria 1 field cannot be empty.");
		return false;
	}

	//mantis:4313
	if(document.getElementById('AppSearchAllCriteriaType1').value == 'phone' && document.getElementById('AppSearchAllDeliminator1').value == 'is')
	{
		var phone1 = document.getElementById('AppSearchAllCriteria1').value;

		if(validPhoneFormat1(phone1) || validPhoneFormat2(phone1) || validPhoneFormat3(phone1))
		{
			return true;
		}
		else
		{
			alert("The allowed formats of a phone number are \nXXXXXXXXXX \n(XXX) XXX-XXXX \nXXX-XXX-XXXX");
			return false;
		}
	}

	if(document.getElementById('AppSearchAllCriteriaType2').value == 'phone' && document.getElementById('AppSearchAllDeliminator2').value == 'is')
	{
		var phone2 = document.getElementById('AppSearchAllCriteria2').value;

		if(validPhoneFormat1(phone2) || validPhoneFormat2(phone2) || validPhoneFormat3(phone2))
		{
			return true;
		}
		else
		{
			alert("The allowed formats of a phone number are \nXXXXXXXXXX \n(XXX) XXX-XXXX \nXXX-XXX-XXXX");
			return false;
		}
	}
	//end mantis:4313

	return true;
}

function isNotWhiteSpace(value)
{
    var pattern = /\S/;
    var regex = new RegExp(pattern);

    return value.match(regex);
}

//mantis:4313
function validPhoneFormat1(phone)
{
	if(phone.length != 10
		   	|| !isInteger(fax.charAt(0))
			|| !isInteger(fax.charAt(1))
		   	|| !isInteger(fax.charAt(2))
		   	|| !isInteger(fax.charAt(3))
		   	|| !isInteger(fax.charAt(4))
			|| !isInteger(fax.charAt(5))
			|| !isInteger(fax.charAt(6))
			|| !isInteger(fax.charAt(7))
			|| !isInteger(fax.charAt(8))
			|| !isInteger(fax.charAt(9))
	)
	{
		return false;
	}
	else
		return true;
}

function validPhoneFormat2(phone)
{
	if(phone.length != 14
		   	|| phone.charAt(0) != '('
			|| !isInteger(phone.charAt(1))
		   	|| !isInteger(phone.charAt(2))
		   	|| !isInteger(phone.charAt(3))
		   	|| phone.charAt(4) != ')'
			|| phone.charAt(5) != ' '
			|| !isInteger(phone.charAt(6))
			|| !isInteger(phone.charAt(7))
			|| !isInteger(phone.charAt(8))
			|| phone.charAt(9) != '-'
			|| !isInteger(phone.charAt(10))
			|| !isInteger(phone.charAt(11))
			|| !isInteger(phone.charAt(12))
			|| !isInteger(phone.charAt(13))
	)
	{
		return false;
	}
	else
		return true;
}

function validPhoneFormat3(phone)
{
	if(phone.length != 12
		   	|| !isInteger(phone.charAt(0))
			|| !isInteger(phone.charAt(1))
		   	|| !isInteger(phone.charAt(2))
		   	|| phone.charAt(3) != '-'
		   	|| !isInteger(phone.charAt(4))
			|| !isInteger(phone.charAt(5))
			|| !isInteger(phone.charAt(6))
			|| phone.charAt(7) != '-'
			|| !isInteger(phone.charAt(8))
			|| !isInteger(phone.charAt(9))
			|| !isInteger(phone.charAt(10))
			|| !isInteger(phone.charAt(11))
	)
	{
		return false;
	}
	else
		return true;
}
// end mantis:4313

function OpenCustomerManagerWindow(getArguments, popName, field_name)
{
	var ssn_val = document.getElementById(field_name).value;
	var opts = 'toolbar=no,location=no,directories=no,status=no,menubar=no';
	opts += ',scrollbars=yes,resizable=yes,copyhistory=no,width=940';
	opts += ',height=400,left=200,top=200,screenX=200,screenY=200';

	var win = window.open("/?"+getArguments+"&new_ssn="+ssn_val, popName, opts);
	win.focus();
}

function ValidDateRange(baseform)
{
	var from_date = baseform.from_date_year.value + baseform.from_date_month.value + baseform.from_date_day.value;
	var to_date = baseform.to_date_year.value + baseform.to_date_month.value + baseform.to_date_day.value;

	if(to_date < from_date)
	{
		alert("Invalid Date Range. \nEnsure 'From Date' and 'To Date' are correct.");
		return false;
	}
	else
	{
		return true;
	}
}

var keybYN = new keybEdit('yn'); 
var keybNumeric = new keybEdit('01234567890');
var keybAlpha = new keybEdit('abcdefghijklmnopqurstuvwxyz\',- ');
var keybAlphaNumeric = new keybEdit('abcdefghijklmnopqurstuvwxyz01234567890-.& '); 
var keybPureAlphaNumeric = new keybEdit('abcdefghijklmnopqurstuvwxyz01234567890'); 
var keyblegalunit= new keybEdit('abcdefghijklmnopqurstuvwxyz01234567890-.\'/# '); 
var keybEmail = new keybEdit('abcdefghijklmnopqurstuvwxyz01234567890-@._'); 
var keybDecimal = new keybEdit('01234567890.'); var keybDate = new keybEdit('01234567890/'); 

function keybEdit(strValid) { 
	var reWork = new RegExp('[a-z]','gi'); 
	
	if(reWork.test(strValid))
	this.valid = strValid.toLowerCase() + strValid.toUpperCase(); 
	else
	this.valid = strValid; 
	
	this.getValid = keybEditGetValid;
	
	function keybEditGetValid() { return this.valid.toString();}

}

function editKeyBoard(objForm, objKeyb,event) {
strWork = objKeyb.getValid(); 
blnValidChar = false; 

	if (event.keyCode == 46 || event.keyCode == 35)// delete
	{
  		return true;
		
	}	

for (i=0;i < strWork.length;i++)
if (((window.event) ? window.event.keyCode : event.keyCode ? event.keyCode : event.which ? event.which: void 0) == strWork.charCodeAt(i)) { blnValidChar = true; break;}

if (!blnValidChar) { 
	
	if (window.event)
	{
		window.event.returnValue = false;
	}
	else
	{
	
 	    if (event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 37 || event.keyCode == 39 || event.KeyCode==46)
		{
	     	return true;
		}
		else
		{
			return false;
		}
	}


	objForm.focus();
}

return true;
}

// function mask
// provides a mask of characters for a field
// args
// @str : string : text of target field
// @textbox: element : target input field
// @loc: string : string of locations in string for delimitors
// @delim: Array : array of delimitors in order of placement in string
// @event: event : event that triggered function
function mask(str,textbox,loc,delim,event){
	
	key = ((window.event) ? window.event.keyCode : event.keyCode ? event.keyCode : event.which ? event.which: void 0);
	
	if ( key == 8 || key == 37 || key == 39 || key == 16 || key.ctrlKey || str.length == 0 ) // backspace left right shift ctrl empty str
		return true;
	
	var cursor_at = textbox.selectionEnd;
	
	var locs = loc.split(','); 

	if(locs.length != delim.length)
		return true;
	
	if(cursor_at < str.length)
		cursor_at--;
	
			
	for (var i = 0; i <= delim.length; i++){
		str = str.replace(delim[i],"");
	}
    
	for (var i = 0; i <= locs.length; i++){
		
		for (var k = 0; k <= str.length; k++){
			
			if (k == locs[i]){

				if (str.substring(k, k+1) != delim[i] ) {
					
					str = str.substring(0,k) + delim[i] + str.substring(k,str.length);

					if((cursor_at + 1 == k || cursor_at == k) && key != 46 )
						  cursor_at++;
											
				}
				
			}
	
		}

	}
	textbox.value = str;
		
	if (textbox.setSelectionRange)
	{
		textbox.setSelectionRange(cursor_at + 1, cursor_at + 1);	
	}
	else
	{
		textbox.createTextRange(cursor_at + 1, cursor_at + 1);
	}
	
}

String.prototype.trim = function () {
    return this.replace(/^\s*/, "").replace(/\s*$/, "");
}

// function strip_all_but
// args
// @obj : element : text field to strip characters from
// @keys: keyBedit : class of valid chars
// @event: event : event of calling event
// @extrakeys: string : other keys to strip optional
function strip_all_but(obj,keys,event,extrakeys)
{

	extrakeys = typeof(extrakeys) != 'undefined' ? extrakeys : '';
	var strWork = keys.getValid() + extrakeys; 
	var return_str = '';
	
	for(i = 0; i < obj.value.length; i++)
	{
		
		for (j = 0; j < strWork.length; j++)
		{
			
			if(obj.value.substr(i, 1) == strWork.substr(j, 1))
			{
				
				return_str += obj.value.substr(i, 1);
				break;
				
			}
			
		}
		
		
	}
	
	if(obj.value.length != return_str.length)
	{
		obj.value = return_str.trim();
		obj.focus();
		return false;
	}
	else
	{
		obj.value = return_str.trim();
		return true;
	}
}

// function validate_fields
// args
// @validations_array : Object : a associative object with name of field and regular expression as the value to pass validation
// @stdtext: string: class name of non error style
// @errtext: string: class name of error style
// @ext: string : optional post-fix for fields 
function validate_fields(validation_array,stdtext,errtext,ext)
{
	ext = typeof(ext) != 'undefined' ? ext : '';
	focusobj = null;
	isvalid = true;
	
	for (field in validation_array)
	{
		if (document.getElementById(field + '_span' + ext))
		{
			document.getElementById(field + '_span' + ext).className = stdtext;
		}
	}
	
	for (field in validation_array)
	{
	
		if (document.getElementById(field + ext) && (document.getElementById(field + ext).value || document.getElementById(field + ext).value == ""))
		{
			value_expersion = validation_array[field];
			regex = new RegExp(value_expersion);
			
			
			if (!(document.getElementById(field + ext).value.match(regex)))
			{
				document.getElementById(field + ext).className = errtext;	
				isvalid = false;
				
				if (focusobj)
				{
					
				}
				else
				{
					focusobj = document.getElementById(field + ext);
				}	
			}
		}
	}
	if(focusobj)
	{
		focusobj.focus();
	}
	return isvalid;
}

// function reset_fields
// args
// @validations_array : Object : a associative object with name of field and regular expression as the value to pass validation
// @stdtext: string : class name of non error style
// @ext: string : optional post-fix for fields 
function reset_fields(validation_array,stdtext,ext)
{
	ext = typeof(ext) != 'undefined' ? ext : '';
	
	for (field in validation_array)
	{
		if (document.getElementById(field + '_span' + ext))
		{
			document.getElementById(field + '_span' + ext).className = stdtext;
		}
	}
		
}


