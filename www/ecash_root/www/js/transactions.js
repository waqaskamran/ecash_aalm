// SCRIPT FILE transactions.js
var bases = [];
var payment_mode = 'fixed';
var holidays;
var CurrentPayment;
// There is too much interest retrieval done at page initialization, so I
// disable it for a couple seconds.
var interest_retrieve_disabled = true;
setTimeout(function () {
		interest_retrieve_disabled = false;
	}, 2000);

// The holiday list needs to be loaded and the most direct way is to do an api
// call.
// setTimeout(function () {
// ecash_calculator({
// id: 469,
// params:[{
// action:'paydate',
// function:'get_Holiday_List',
// dateformat:'epoch'
// }],
// onSuccess: function (transport) {
// var result = transport.responseText.parseJSON();
// holidays = result.result;
// },
// onFailure: function (transport) {
// alert('holiday ajax call fail');
// }
// }) }, 2000);

function FormatAmount(amt)
{
	var number = parseFloat(amt);

	if (number) {
		return String(number.toFixed(2));
	} else {
		return "0.00";
	}
}

function OpenTransactionPopup(popType, popName, mode)
{       
	var opts = 'toolbar=no,location=no,directories=no,status=no,menubar=no';
	opts += ',scrollbars=yes,resizable=yes,copyhistory=no,width=506';
	opts += ',height=590,left=50,top=0,screenX=25,screenY=0';

	var win = window.open("/?action="+popType+"&action_type=fetch&mode="+mode, popName, opts);
	win.focus();
}


function OpenDebtCompanyPopup(popType, popName, mode,debt_company)
{       
	var opts = 'toolbar=no,location=no,directories=no,status=no,menubar=no';
	opts += ',scrollbars=no,resizable=yes,copyhistory=no,width=550';
	opts += ',height=320,left=200,top=200,screenX=200,screenY=200';

	var win = window.open("/?action="+popType+"&action_type=fetch&mode="+mode+"&debt_company_id="+debt_company, popName, opts);
	win.focus();
}

function OpenCompletedTransactionPopup(popType, popName, mode)
{       
	var opts = 'toolbar=no,location=no,directories=no,status=no,menubar=no';
	opts += ',scrollbars=yes,resizable=yes,copyhistory=no,width=506';
	opts += ',height=590,left=200,top=200,screenX=200,screenY=200';

	var win = window.open("/?action="+popType+"&action_type=fetch&mode="+mode, popName, opts);
	win.focus();
}

function ToggleScheduled(chkbox)
{
	var first_date = null;
	var table = document.getElementById("schedule_rows_table");

	if (table == null) return;

	var rows = table.getElementsByTagName("tr");
	
	for (var i = 1; i < rows.length; i++)
	{      
		if (rows[i].className == "transactions_scheduled")
		{
			var value;

			value = rows[i].cells[0].innerHTML;
			var altvalue = rows[i].cells[6].innerHTML;

			if (altvalue && altvalue != '&nbsp;') {
				value = altvalue;
			}

			if (first_date == null) first_date = value;
			
			if (first_date == value) continue;
			
			if (chkbox.checked) rows[i].style.visibility = "visible";
			else rows[i].style.visibility = "hidden";
		}      
	}
}

function UpdateDiscountDate(base)
{
	var last_date = '';
	var num_rows_visible = parseInt(document.getElementById(base+"_num").value, 10);
	if(document.getElementById(base+"_date_"+(num_rows_visible-1)))
	{
		var latest_date = new Date(serverdate);
		var latest_idx = 0;
		for(var i = 0; i < num_rows_visible; i++)
		{
			var current_date = new Date(document.getElementById(base+"_date_"+(i)).value);
			if(current_date > latest_date)
			{
				latest_date = current_date;
				latest_idx = i;
			}
		}

		last_date = document.getElementById(base+"_date_"+(latest_idx)).value;
	}

	// Now set the discount date
	var disc_date = document.getElementById(base+"_discount_date");
	if (disc_date != null) disc_date.value = last_date;
	if(document.getElementById(base+"_discount_date_displayed"))
	{
		document.getElementById(base+"_discount_date_displayed").innerHTML = last_date;
	}

	if(base == 'next_payment_adjustment')
	{
		UpdateAmountLeftToArrange(base);
	}
}

// While just calling "showCalendar" is easier, sometimes the position is jacked
// up b/c
// the element coordinates are taken from the container, which is a scrollable
// viewport
// in this case. Most of the code here is from "showCalendar", "showAtElement"
// and "showAt"
// in calendar/calendar.js
function PopCalendar1(target, event, fund_date, pastDates, pt_dropdown) { 
	return (PopCalendar(target, event.clientX, event.clientY, fund_date, pastDates, pt_dropdown)); 
}
function PopCalendar2(target, event, fund_date, pastDates, pt_dropdown) { 
	return (PopCalendar(target, event.clientX, event.clientY - 70, fund_date, pastDates, pt_dropdown,null,true)); 
}
function PopCalendar3(target, event, fund_date, pastDates, pt_dropdown) { 
	return (PopCalendar(target, event.clientX - 230, event.clientY - 180, fund_date, pastDates, pt_dropdown)); 
}

// Hack for manual payments -- All payment methods use past dates
// except for personal checks
// and arrangements which happen in the future
function PopCalendar4(target, event, fund_date, pastDates, pt_dropdown) 
{
	var allowHolidays = true;
	if(pt_dropdown != null) 
	{
		var payment_type = document.getElementById(pt_dropdown);
		if(payment_type.value == 'personal_check' && gpo('manual_payment_check_type').value == 'ACH') {
			pastDates = false;
			allowHolidays = false;
		}
	}

	return (PopCalendar(target, event.clientX, event.clientY, fund_date, pastDates, pt_dropdown, null, allowHolidays)); 
}

// For calendars that have a start date and an end date
function PopCalendar6(target,event,fund_date,end_date,pt_dropdown)
{
	return (PopCalendar(target, event.clientX - 230, event.clientY - 180, fund_date,false, pt_dropdown,end_date)); 
}

//changed allowWeekends to Sat/Sun for #42980
function PopCalendar(target, x, y, fund_date, pastDates, pt_dropdown, end_date, allowHolidays, allowSaturday, allowSunday)
{
    var hasHolidays = false;
    allowHolidays = typeof(allowHolidays) != 'undefined' ? allowHolidays : false;
	hasHolidays = allowHolidays;

	allowSaturday = typeof(allowSaturday) != 'undefined' ? allowSaturday : false;
	allowSunday = typeof(allowSunday) != 'undefined' ? allowSunday : false;
	
    // selectHandler is the handler used to validate the date when
    // the user clicks on it. This is where holiday & weekend checks occur
	var selectHandler;
	if (pastDates == null || pastDates) 
	{
		selectHandler = selectedPastDate;
	} 
	else if (pastDates == 2) 
	{
		selectHandler = function(){ return true;};
	} 
	else 
	{
		selectHandler = selected;
	}
	var el = document.getElementById(target);

	// set up for the isACH modification.
	var isACH = false;
	var typeInput = document.getElementById(pt_dropdown);
	
	if (typeInput && typeInput.value != '') 
	{
		var type = typeInput.value;
		if (type == 'payment_arranged' || (type == 'personal_check' && gpo('manual_payment_check_type').value == 'ACH')) {
			isACH = true;
		}
	}

	if (calendar != null)
	{
			calendar.destroy();
			calendar = null;
	}

	if (calendar == null)
	{
		var today = new Date(serverdate);
		today.setHours(0);
		today.setMinutes(0);
		today.setSeconds(0);
		today.setMilliseconds(0);

		var cal = new Calendar(true, serverdate, selectHandler, closeHandler);
		calendar = cal;
		calendar.hasHolidays = hasHolidays;
		cal.allowWeekends = allowSaturday | allowSunday;

		if(isACH)
		{
			var achDate = new Date(nextSafeACHDueDate);
			achDate.setHours(0);
			achDate.setMinutes(0);
			achDate.setSeconds(0);
			achDate.setMilliseconds(0);

			var safeAchDate = calendar.parseDate(nextSafeACHDueDate, 'm-d-y');
			//[#44306] do not tell the calendar to set 'today' to nextSafeACHDueDate,
			//it has already been set in the input
			//calendar.dateStr = safeAchDate;
		}				
	
		calendar.cachekey = pastDates + isACH;
		cal.setRange(1969, 2070);
		var funddate = calendar.parseDate(fund_date, 'm-d-y');
		var enddate = calendar.parseDate(end_date, 'm-d-y');

		if (pastDates == 2)
        {
		}
        else if (pastDates != null)
        {
			cal.setDisabledHandler(function (thisdate) {
				thisdate.setHours(0);
				thisdate.setMinutes(0);
				thisdate.setSeconds(0);
				thisdate.setMilliseconds(0);

				// not after end
				if (enddate != null && thisdate > enddate) return true;

				// Never before funding date
				if (thisdate < funddate) return true;

				// Never on the weekends
				if (!hasHolidays &&
					(((thisdate.getDay() == 6 && !allowSaturday) ||
					  (thisdate.getDay() === 0 && !allowSunday))))
				{
					return true;
				}
				
				/**
				 * We don't want to allow any days that aren't on or after our
				 * first safe ACH date if the payment type is ACH.
				 */
				if (isACH) 
				{
					if (thisdate >= achDate || thisdate > today) {
						return false;
					}
				}

				if (pastDates) 
				{
					// If we only allow past dates, set future dates disabled.
					if (thisdate > today) return true;
				} 
				else 
				{
					// If we only allow future dates, set past dates disabled.
					if (thisdate < today) return true;
				}

				// Check if this day is a holiday
				if(!hasHolidays)
				{
					for (var idx in holidays) {
						var holiday = new Date(holidays[idx] * 1000);
						if (thisdate.toString() == holiday.toString()) {
							return true;
						}
					}
				}

				return false;
			});
		}
        else
        {
			cal.setDisabledHandler(function (thisdate) {
				thisdate.setHours(0);
				thisdate.setMinutes(0);
				thisdate.setSeconds(0);
				thisdate.setMilliseconds(0);
				// Never before funding date
				if (fund_date != null && thisdate < funddate) return true;
				// Never on the weekends
				if ((thisdate.getDay() == 6 && !allowSaturday) ||
					(thisdate.getDay() === 0 && !allowSunday))
				{
					return true;
				}
				
				// Check if this day is a holiday
				if(!hasHolidays)
				{
					for (var idx in holidays) {
						var holiday = new Date(holidays[idx] * 1000);
						if (thisdate.toString() == holiday.toString()) {
							return true;
						}
					}
				}
				
				return false;
			});
		}
		calendar.create();
	}

	calendar.sel = el;
	calendar.pt_dropdown = pt_dropdown;

	// Don't show *at* the element, b/c the position might be jacked.
	// Show at the cursor location
	calendar.showAt(x, y);

	// Need this to hide the calendar
	Calendar.addEvent(document, "mousedown", checkCalendar);
	
	if(isACH)
	{
		var safeAchDate = calendar.parseDate(nextSafeACHDueDate, 'm-d-y');
		cal.setDate(safeAchDate);
	}

	return false;
}

function TransactionGenerationPopCalendar(target, x, y, fund_date) 
{
	PopCalendar(target, x, y, fund_date, true);
}

function checkDate(input, past_date, pt_dropdown, checkOrder, allowWeekends) 
{
   var date = input.value;
   var intdate = date;
   var today = new Date(serverdate);
   today.setHours(0);
   today.setMinutes(0);
   today.setSeconds(0,0);
   if (date == '') 
   		return true;
  
   if(checkOrder == null)
	   checkOrder = true;
   
   if(allowWeekends == null)
	   allowWeekends = true;
   
   var isACH = false;
   
	if (pt_dropdown) 
	{
		pt_dropdown = document.getElementById(pt_dropdown);

		if (pt_dropdown.options[pt_dropdown.selectedIndex].value == 'payment_arranged') 
		{
			isACH = true;
		}

		if (pt_dropdown.options[pt_dropdown.selectedIndex].value == 'personal_check' && gpo('manual_payment_check_type').value == 'ACH') 
		{
			past_date = false;
			isACH = true;
		}
	}

	// Better date validation (date_validation.js)
	if (!isValidDate(date))
	{
		input.value = '';
		input.focus();
		input.select();
		return false;
	}
	
	if (!past_date && (Date.parse(intdate) < today))
	{
		alert("Past dates cannot be selected.");
		input.value = '';
		input.focus();
		input.select();
		return false;
	}

	if (!past_date && isACH && (Date.parse(intdate) == Date.parse(today))) {
		alert("You cannot choose today's date for ACH transactions.");
		input.value = '';
		input.focus();
		input.select();
		return false;
	}

	// This is starting to get confusing. If we get reports that an agent should
	// NOT be able to pick today's date but SHOULD be able to pick past dates,
	// this check is what will need rewritten.
	if (past_date && (Date.parse(intdate) > today))
	{
		alert("You must select a past date.");
		input.value = '';
		input.focus();
		input.select();
		return false;
	}
   
   // Re-enabled to check for paydown/payout weekend dates
   var dow = new Date(date).getDay();
   if (!allowWeekends && ((dow == 0) || (dow == 6))) 
   {
     alert("Weekends cannot be selected." + allowWeekends);
     input.value = '';
     input.focus();
     input.select();
     return false;
   }

	// Check if this day is a holiday
	for (var idx in holidays) 
	{
		var holiday = new Date(holidays[idx] * 1000);
		if (date.toString() == holiday.toString()) 
		{
			alert('Date cannot be a holiday.');
			return false;
		}
	}

	// If you try to check the date order on a paydown/payout, javascript will
	// kill itself
	if (checkOrder && !CheckDateOrder(input)) {
		alert('Dates are invalid, out of order or identical.  Repair so no date fields are red.');
		return false;
	}

	return true;
}

function SetErrorDisplay (element, html, xpos, ypos) 
{
	if (html == null) {
		if (element != null) {
			element.onmouseover = null;
			var f = element.onmouseout;
			element.onmouseout = null;
			element.errorDiv = null;
			if ('function' == typeof f) {
				f(null, element);
			} else {
				if (element.parentNode && element.errorDiv) {
					element.parentNode.removeChild(element.errorDiv);
				}
			}
		}
	} else {
		ClearErrorDisplay(element);
		var container = document.createElement('span');
		container.innerHTML = html;
		var error = container.firstChild;
		error.style.position = 'absolute'; 
		error.style.top = xpos;
		error.style.left = ypos;
		element.errorDiv = error;
		element.onmouseover = function() {
			this.parentNode.appendChild(this.errorDiv);
		};
		element.onmouseout = function(event, element) {
			if (element) {
				if (element.parentNode && element.errorDiv) {
					element.parentNode.removeChild(element.errorDiv);
				}
			} else {
				this.parentNode.removeChild(this.errorDiv);
			}
		};
	}
}

function ClearErrorDisplay(element) 
{
	SetErrorDisplay(element);
}

function CheckDateOrder(input)
{
	// Check the order of the dates
	if (input == null) return false;
	var form = input.form;
	var rows = 0;
	for (var ele in form.elements) {
		var element = form.elements[ele];
		if (!element.name) continue;
		if (element.name == 'num_payment_arrangement') {
			rows = form.elements[ele].value;
			break;
		} 
	}
	var dates = [];
	for (var ele in form.elements) {
		var element = form.elements[ele];
		if (!element.name) continue;
		var pieces = element.name.match('^(.+)_date_(\\d+)$');
		if (pieces == null) continue;
		var row = pieces[2];
		var datechunks = form.elements[ele].value.match('^(\\d+)/(\\d+)/(\\d+)$');
		if (datechunks == null) {
			dates[row] = { Value: null, Element:element };
		} else {
			if (datechunks[2] < 10) datechunks[2] = '0' + parseInt(datechunks[2], 10);
			if (datechunks[1] < 10) datechunks[1] = '0' + parseInt(datechunks[1], 10);
			dates[row] = { Value: datechunks[3] + datechunks[1] + datechunks[2], Element: element };
		}
	}
	var last = dates[0].Value - 1;
	var lastvalue = dates[0].Element.value;
	var valid = true;
	for (var i = 0; i < rows; i ++)
    {
		var element = dates[i].Element;
		var value = dates[i].Value;
		if (value <= last && last != null && element.value != '') {
			element.parentNode.style.position = 'relative';
			var html = "<div style='background-color:#FF8888; padding:3px; border:1px black solid; color: white; font-size:12pt; font-weight:bold; z-index:2;'>";
			if (isNaN(Date.parse(element.value))) {
				html += "Error: " + element.value + ' is not a valid date';
				element.value = '';
			} else {
				html += "Error: " + element.value + ' cannot be ' + ( value == last ? 'the same date as ' : 'previous to ' ) + lastvalue;
			}
			html += "</div>";
			SetErrorDisplay (element, html, element.parentNode.style.offsetHeight, element.parentNode.style.offsetLeft);
			element.style.backgroundColor = '#FF8888';
			valid = false;
		} else {
			ClearErrorDisplay(element);
			element.style.backgroundColor = 'white';
		}
		last = dates[i].Value;
		lastvalue = dates[i].Element.value;
	}
	return valid;
}

function SetDiscountDisplay(base)
{	
	var pe = document.getElementById(base+"_percent_discount");
	if (pe == null) return;
	
	var percent = pe.value;
	if (percent != 'absolute') percent = parseInt(percent, 10) / 100.00;	

	var disc_row_el = document.getElementById(base+"_discount_row");

	if ((percent == 'absolute') || (percent > 0))
	{
		disc_row_el.style.visibility = 'visible';
	}
	else
	{
		disc_row_el.style.visibility = 'hidden';
	}

	gpo(base+"_discount_amount").readOnly = ((percent == 'absolute') ? false : true);

// var table = document.getElementById(base+"_payment_table");
	
	ChangeRowDisplay(base);
	// table.style.overflow = 'hidden';
	// table.style.display = 'none';
	// table.style.display = 'block';
}

function ClearPayments(base)
{
	for (var i = 0; i < amounts.length; i++)
	{
		amounts[i].value = "0.00";
		dates[i].value = "";
		values[i] = 0.0;
	}
}

function IsNumeric(sText)
{
	var ValidChars = "0123456789";
	var IsNumber=true;
	var Char;

	for (i = 0; i < sText.length && IsNumber == true; i++)
	{
		Char = sText.charAt(i);
		if (ValidChars.indexOf(Char) == -1)
		{
			IsNumber = false;
		}
	}

	return IsNumber;
}

function IsAlpha(sText)
{
	var ValidChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
	var IsNumber=true;
	var Char;
 
	for (i = 0; i < sText.length && IsNumber == true; i++) 
	{ 
		Char = sText.charAt(i); 
		if (ValidChars.indexOf(Char) == -1) 
		{
			IsNumber = false;
		}
	}

	return IsNumber;
}

function AddDebtCompany()
{
	var validate_check = "";
	if(document.forms['debt_company_form'].debt_company_name.value == "")
		validate_check =  validate_check + "Please specify a company name.\n";

	if(document.forms['debt_company_form'].debt_company_address1.value == "")
		validate_check = validate_check + "Please specify address\n";

	if(document.forms['debt_company_form'].debt_company_city.value == "")
		validate_check =  validate_check + "Please specify city.\n";

	if(document.forms['debt_company_form'].debt_company_state.value == "")
	{
		validate_check =  validate_check + "Please specify state.\n";
	} 
	else if(IsAlpha(document.forms['debt_company_form'].debt_company_state.value) == false)
	{
		validate_check =  validate_check + "Please specify a valid state.\n";
	}

	if(document.forms['debt_company_form'].debt_company_zipcode.value == "")
	{
		validate_check =  validate_check + "Please specify zip code.\n";	
	}
	else if(IsNumeric(document.forms['debt_company_form'].debt_company_zipcode.value) == false)
	{
		validate_check =  validate_check + "Zip code must be numeric.\n";
	}

	if(document.forms['debt_company_form'].debt_company_phone.value == "")
	{
		validate_check =  validate_check + "Please specify phone number.\n";				
	}
	else if(IsNumeric(document.forms['debt_company_form'].debt_company_phone.value) == false)
	{
		validate_check =  validate_check + "Phone Number must be numeric.\n";
	}		

	// All checks cleared, submit it.
	if(validate_check == "")
	{
		document.forms['debt_company_form'].submit();	
		return true;
	}
	elseIsNumeric
	{
		alert(validate_check);
		return false;
	}
}

function SaveCallDispositions()
{
	document.forms['disposition_form'].submit();
}

function SaveAdjustment(save_btn)
{
	// [#40758] disable button so agent doesn't double-submit
	save_btn.disabled = true;

	var errMsg;
	var ae = document.getElementById("adjustment_amount");
	if (ae == null) return;
	var amt = parseFloat(FormatAmount(ae.value));
	var bal = parseFloat(FormatAmount(document.getElementById("posted_total").value));
	var date = document.getElementById("adjustment_date").value;
	var adjustment_type_el = document.getElementById('adjustment_type');
	var adjustment_type = adjustment_type_el.options[adjustment_type_el.selectedIndex].value;
       
	// Amount check
	if (!(/\d+(\.\d{2})?/.test(ae.value)))
	{
		errMsg = "Please enter a dollar amount\n";
		errMsg += "for the 'Amount' field.";
		alert(errMsg);
		save_btn.disabled = false;
		return;
	}
	
	if (amt > bal && adjustment_type == 'debit') {
		errMsg = "The credit adjustment is for more than the total account ";
		errMsg += "balance. Please enter an amount less than or equal to "+bal;
		alert(errMsg);
		save_btn.disabled = false;
		return;
	}

	// Date check
	if (isNaN(Date.parse(date)))
	{
		errMsg = "A date is required for the adjustment.";
		alert(errMsg);
		save_btn.disabled = false;
		return;
	}
	else
	{
		var d = Date.parse(date);
		var now = new Date(serverdate);
		var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
		if (d < today)
		{
			errMsg = "The date cannot be before today.";
			alert(errMsg);
			save_btn.disabled = false;
			return;
		}
	}
	// All checks cleared, submit it.
	document.forms['adjustment_form'].submit();
}

function calcDays(date1, date2) 
{
	date1 = date1.split("/");
	date2 = date2.split("/");
	var sDate = new Date(date1[2], date1[0], date1[1]);
	var eDate = new Date(date2[2], date2[0], date2[1]);
	return Math.abs(Math.round((sDate-eDate)/86400000));
}

function include(file) 
{
	if (document.createElement && document.getElementsByTagName) {
		var head = document.getElementsByTagName('head')[0];

		var script = document.createElement('script');
		script.setAttribute('type', 'text/javascript');
		script.setAttribute('src', file);
	
		head.appendChild(script);
	} else {
 		alert('Your browser can\'t deal with the DOM standard. That means it\'s old. Go fix it!');
	}
}

var delay_adjust_timer = 0;
// Our keyup handler. Don't run a recalc immediately, but hang tight for a
// couple seconds to see if they enter more.
function DelayAdjustArranged(base, event)
{
	if (delay_adjust_timer > 0) 
	{
		clearTimeout(delay_adjust_timer);
	}
	var row = 0;
	var re_amt = new RegExp(base+"_amount_(\\d+)");
	if ((event != null) && (event.target.id.match(re_amt)))
	{
		row = parseInt(RegExp.$1, 10);
	}
	if (row >= 0) 
	{
		delay_adjust_timer = setTimeout('CatchDelayAdjustArranged("' + base + '", null, ' + row + ');', 2000);
	}
}

function CatchDelayAdjustArranged(base, idx) 
{
	delay_adjust_timer = 0;
	AdjustArrangedAmounts(base, null, idx);
}

function AdjustArrangedSettlementAmounts(base, event, idx)
{
	if (delay_adjust_timer > 0) 
	{
		clearTimeout(delay_adjust_timer);
	}
	var re_amt = new RegExp(base+"_amount_(\\d+)");
	var actuals = []; // actual input values
	var displayed = []; // displayed input values
	var dates = []; // the payment dates
	var begin = []; // the payment dates
	var end = []; // the payment dates
	var num_rows_visible = parseInt(gfv(base+"_num"), 10);
	
	CheckDateOrder(gpo(base + '_amount_0'));

	if (("undefined" == typeof(idx)) && (event != null) && (event.target.id.match(re_amt)))
		idx = parseInt(RegExp.$1, 10);
	else 
		idx = 0;

	// collect the input data for the entire form
	for (var i = 0; i < num_rows_visible; i++)
	{
		actuals[i] = document.getElementById(base+"_actual_amount_"+i);
		displayed[i] = document.getElementById(base+"_amount_"+i);
		dates[i] = document.getElementById(base+"_date_"+i);
		begin[i] = document.getElementById(base+"_interest_range_begin_"+i);
		end[i] = document.getElementById(base+"_interest_range_end_"+i);
		if (i > idx) {
			gpo(base+"_payment_row_"+i+"_interest").innerHTML = '';
			gpo(base+"_payment_row_"+i+"_interest_balance").innerHTML = '';
			gpo(base+"_payment_row_"+i+"_fee_balance").innerHTML = '';
			gpo(base+"_payment_row_"+i+"_balance").innerHTML = '';
		}
	} 

	// If the event isn't null and the id passed the regexp test, we need to
	// change a
	// "pure" value
	if ((event != null) && (event.target.id.match(re_amt)))
	{
		if (event.target.value) {
			actuals[idx].value = parseFloat(event.target.value);
			if (isNaN(actuals[idx].value)) actuals[idx].value = 0.0;
		}
	}

	// Get the principal balance. Depends on the pending state.
	var principal_balance = parseFloat(document.getElementById(base+"_principal_balance").value);
	var checkBox = document.getElementById(base+"_arr_incl_pend");
	if (checkBox && checkBox.checked) { // Include pending items
		principal_balance = parseFloat(document.getElementById(base+"_principal_balance_with_pending").value);
	}

	var interest_calc_start_date = document.getElementById(base+"_interest_paid_to_date").value;
	var date_fund_actual;
	if (interest_calc_start_date) {
		date_fund_actual = interest_calc_start_date;
	} else {
		date_fund_actual = document.getElementById("date_fund_actual_hidden").value;
	}

	if (idx > 0) {
		principal_balance = parseFloat(document.getElementById(base+'_payment_row_'+(idx-1)+'_balance').innerHTML);
		date_fund_actual = dates[idx - 1].value;
	}

	date_fund_actual = date_fund_actual.replace('-', '/');
	date_fund_actual = date_fund_actual.replace('-', '/');

	if (dates[idx] == null) {
		var today = new Date(serverdate);
		date_payment = today.getFullYear() + '-' + (today.getMonth()+1) + '-' + today.getDate();
	} else {
		date_payment = dates[idx].value;
	}

	if (num_rows_visible) {
		gpo(base + '_interest_range_begin_' + idx).value = date_fund_actual;
		gpo(base + '_interest_range_end_' + idx).value = date_payment;
	}

	get_interest(AdjustArrangedSettlementAmountsRecursive, base, idx, principal_balance, date_fund_actual, date_payment);
}

// Sets up the actuals array and calls get_interest for the first row
function AdjustArrangedAmounts(base, event, idx)
{
	if (delay_adjust_timer > 0) 
	{
		clearTimeout(delay_adjust_timer);
	}
	var re_amt = new RegExp(base+"_amount_(\\d+)");
	var actuals = []; // actual input values
	var displayed = []; // displayed input values
	var dates = []; // the payment dates
	var begin = []; // the payment dates
	var end = []; // the payment dates
	var num_rows_visible = parseInt(gfv(base+"_num"), 10);
	
	CheckDateOrder(gpo(base + '_amount_0'));

	if (("undefined" == typeof(idx)) && (event != null) && (event.target.id.match(re_amt)))
		idx = parseInt(RegExp.$1, 10);
	else 
		idx = 0;

	// collect the input data for the entire form
	for (var i = 0; i < num_rows_visible; i++)
	{
		actuals[i] = document.getElementById(base+"_actual_amount_"+i);
		displayed[i] = document.getElementById(base+"_amount_"+i);
		dates[i] = document.getElementById(base+"_date_"+i);
		begin[i] = document.getElementById(base+"_interest_range_begin_"+i);
		end[i] = document.getElementById(base+"_interest_range_end_"+i);
		if (i > idx) {
			gpo(base+"_payment_row_"+i+"_interest").innerHTML = '';
			gpo(base+"_payment_row_"+i+"_interest_balance").innerHTML = '';
			gpo(base+"_payment_row_"+i+"_fee_balance").innerHTML = '';
			gpo(base+"_payment_row_"+i+"_balance").innerHTML = '';
		}
	} 

	// If the event isn't null and the id passed the regexp test, we need to
	// change a
	// "pure" value
	if ((event != null) && (event.target.id.match(re_amt)))
	{
		if (event.target.value) {
			actuals[idx].value = parseFloat(event.target.value);
			if (isNaN(actuals[idx].value)) actuals[idx].value = 0.0;
		}
	}

	// Get the principal balance. Depends on the pending state.
	var principal_balance = parseFloat(document.getElementById(base+"_principal_balance").value);
	var checkBox = document.getElementById(base+"_arr_incl_pend");
	if (checkBox && checkBox.checked) { // Include pending items
		principal_balance = parseFloat(document.getElementById(base+"_principal_balance_with_pending").value);
	}
	
	var interest_calc_start_date = document.getElementById(base+"_interest_paid_to_date").value;
	var date_fund_actual;
	if (interest_calc_start_date) {
		date_fund_actual = interest_calc_start_date;
	} else {
		date_fund_actual = document.getElementById("date_fund_actual_hidden").value;
	}

	if (idx > 0) {
		principal_balance = parseFloat(document.getElementById(base+'_payment_row_'+(idx-1)+'_balance').innerHTML);
		date_fund_actual = dates[idx - 1].value;
	}

	date_fund_actual = date_fund_actual.replace('-', '/');
	date_fund_actual = date_fund_actual.replace('-', '/');

	if (dates[idx] == null) {
		var today = new Date(serverdate);
		var date_payment = today.getFullYear() + '-' + (today.getMonth()+1) + '-' + today.getDate();
	} else {
		var date_payment = dates[idx].value;
	}

	if (num_rows_visible) {
		gpo(base + '_interest_range_begin_' + idx).value = date_fund_actual;
		gpo(base + '_interest_range_end_' + idx).value = date_payment;
	}

	get_interest(AdjustArrangedAmountsRecursive, base, idx, principal_balance, date_fund_actual, date_payment);
}

function gpo (id) { // Get Page Object - gpo
	return document.getElementById(id);
}

function gfv (id) { // Get Form element Value - gfv
	try {
		return gpo(id).value;
	} catch (err) {
		return '';
	}
}

function payment_mode_daily (base) 
{
	payment_mode = 'daily';
}

function set_payment_mode (base) 
{
	/**
	 * For non-ach payments, we'll use today as the default.
	 * All others will default to the next safe ach due date
	 */
	var payment_type = document.getElementById(base+'_payment_type_0');
	if(payment_type != null && payment_type.value != 'payment_arranged')
	{
		var now = new Date(serverdate);
	}
	else
	{
		var now = new Date(nextSafeACHDueDate);
	}

	gpo(base+'_date_0') ? gpo(base+'_date_0').value = (now.getMonth()+1) + '/' + now.getDate() + '/' + now.getFullYear() : null;
	
	if (gpo(base+'_svc_charge_type')) {
		if (gpo(base+'_svc_charge_type').value == 'fixed') {
			payment_mode_fixed(base);
		} else {
			payment_mode_daily(base);
		}
	} else {
		payment_mode_fixed(base);
	}
}

function payment_mode_fixed (base) 
{
	if (!gpo(base + "_num"))  return; 
	gpo(base+'_discount_row_interest').style.visibility = 'hidden';
	var num_rows_visible = parseInt(gpo(base+"_num").value, 10);
	for (var i = 0; i < num_rows_visible; i ++) {
// gpo(base+'_payment_row_'+i+'_interest_balance').style.visibility = 'hidden';
		gpo(base+'_payment_row_'+i+'_interest').style.visibility = 'hidden';
	}
// gpo(base+'_interest_balance_title').innerHTML = '';
	gpo(base+'_interest_accrued_title').innerHTML = '';
// gpo(base+'_interest_paid_title').style.visibility = 'hidden';
// gpo(base+'_summary_interest').style.visibility = 'hidden';
// gpo(base+'_interest_paid_title').style.border = '0px';
// gpo(base+'_fees_paid_title').style.borderLeft = '1px solid #AAAAAA';
	payment_mode = 'fixed';
}

function JSON_RPC(request) {
	if ('undefined' != typeof(Ajax)) {
		var req = new Ajax.Request(request.url, {
				parameters: Object.toJSON({
					method:request.method,
					id:request.id,
					params:request.params
				}),
				onFailure: request.onFailure,
				onSuccess: request.onSuccess,
				onComplete: request.onComplete,
				contentType: "text/x-json"
			});
		return req;
	}
	return null;
}

function ecash_JSON_RPC(request) {
	request.url = '/?api=json-rpc';
	return JSON_RPC(request);
}

function ecash_get_schedule(request) {
	request.method = 'Schedule';
	return ecash_JSON_RPC(request);
}

function ecash_calculator(request) {
	request.method = 'Calculator';
	return ecash_JSON_RPC(request);
}

function get_payment_arrangements(action, callback, application_id, company_id) {
	var req = ecash_get_schedule({
			id: 0, 
			params:[{
				action:action,
				application_id:application_id,
				company_id:company_id
			}], 
			onSuccess: function (transport) {
				var result = transport.responseText.parseJSON();

				if (result) {
					if (result.result) 
					{
						callback(result.result);
					}
					if (result.error) 
					{
						error_overlay(result.error);
					}
				} else {
					error_overlay(transport.responseText);
				}
			},
			onFailure: function (transport) {
				alert('ajax fail');
			}
		});
}

function error_overlay(error) 
{
	var body = document.getElementsByTagName('body')[0];
	var div = body.appendChild(document.createElement('div'));
	div.style.position = 'fixed';
	div.style.display = 'block';
	div.style.top = '0px';
	div.style.bottom = '0px';
	div.style.right = '0px';
	div.style.left = '0px';
	div.style.opacity = '0.9';
	div.style.backgroundColor = 'white';
	div.style.borderColor = 'black';
	div.style.borderWidth = '3px';
	div.style.borderStyle = 'solid';
	div.style.whiteSpace = 'pre';
	div.style.padding = '5px';
	div.style.zIndex = '8';
	div.style.overflow = 'auto';
	var h1 = div.appendChild(document.createElement('H1'));
	h1.appendChild(document.createTextNode('Ajax Error Report'));
	var but = div.appendChild(document.createElement('button'));
	but.appendChild(document.createTextNode('CLOSE'));
	but.setAttribute('onclick', 'this.parentNode.style.display="none";this.parentNode.parentNode.removeChild(this.parentNode);');
	div.appendChild(document.createElement("hr"));
	div.appendChild(document.createElement('div')).innerHTML = error;
}

function get_schedule_preview(application_id, company_id)
{
	tbody = document.getElementById('preview_schedule_contents');
	tbody.innerHTML = '<tr><td><td><td><td>Updating...<td><td><td><td>';

	if (!document.getElementById('next_payment_adjustment_date_0').value || !document.getElementById('next_payment_adjustment_amount_0').value) {
		// silently return, we have no date or value
		return;
	}

	var req = ecash_get_schedule({
			id: 0, 
			params:[{
				action:'preview',
				application_id:application_id,
				company_id:company_id,
				payments:[
					{
						type:document.getElementById('next_payment_adjustment_payment_type_0').value,
						date:document.getElementById('next_payment_adjustment_date_0').value,
						amount:document.getElementById('next_payment_adjustment_amount_0').value,
						interest:parseFloat(document.getElementById('next_payment_adjustment_payment_row_0_interest').innerHTML),
				// interest_start_date:document.getElementById('next_payment_adjustment_interest_range_begin_0').value,
				// interest_end_date:document.getElementById('next_payment_adjustment_interest_range_end_0').value,
						fee_balance:parseFloat(document.getElementById('next_payment_adjustment_payment_row_0_fee_balance').innerHTML),
						interest_balance:parseFloat(document.getElementById('next_payment_adjustment_payment_row_0_interest_balance').innerHTML)
					}
				]
			}], 
			onSuccess: function (transport) {
				var result;
				try {
					result = transport.responseText.parseJSON();
      			} catch(e) {
					error_overlay(transport.responseText);
				}

				if (result) 
				{
					if (result.result) populate_schedule_preview(result.result);
					if (result.error) error_overlay(result.error);
				}

			},
			onFailure: function (transport) {
				alert('ajax fail');
			}
		});
}

function populate_schedule_preview(darray) 
{
	
	tbody = document.getElementById('preview_schedule_contents');
	if(document.getElementById('schedule_preview').style.display == 'none')
		return 0;
	
	document.getElementById('schedule_preview').style.display =  'none';
	tbody.innerHTML = darray;
	
	table = document.getElementById("schedule_rows_table_preview");
	if (table == null) return;
	rows = table.getElementsByTagName("tr");
	
	for (var i = 1; i < rows.length; i++)
	{      
				
			rows[i].style.visibility = "visible";
				    
	}
	document.getElementById('schedule_preview').style.display =  'block';
	
	// error_overlay(darray.toJSONString());
	// alert(darray);
	
}

function save_next_adjustment(application_id, company_id, save_btn)
{
		// [#40758] disable button so agent doesn't double-submit
		save_btn.disabled = true;

		if(!checkDate(document.getElementById('next_payment_adjustment_date_0'), false, 'next_payment_adjustment_payment_type_0', false))
		{
			save_btn.disabled = false;
			return false;
		}	

	var req = ecash_get_schedule({
		id: 0, 
		params:[
		   {
			  action:'save_adjustment',
			  application_id:application_id,
			  company_id:company_id,
// agent_id:agent_id,
			  payment_type:'next_payment_adjustment',
			  next_payment_adjustment:{
				 rows:[
					{
					   type:document.getElementById('next_payment_adjustment_payment_type_0').value,
					   date:document.getElementById('next_payment_adjustment_date_0').value,
					   amount:document.getElementById('next_payment_adjustment_actual_amount_0').value,
					   interest:parseFloat(document.getElementById('next_payment_adjustment_payment_row_0_interest').innerHTML),
					   fee_balance:parseFloat(document.getElementById('next_payment_adjustment_payment_row_0_fee_balance').innerHTML),
					   interest_balance:parseFloat(document.getElementById('next_payment_adjustment_payment_row_0_interest_balance').innerHTML),
					   desc:document.getElementById('next_payment_adjustment_desc_0').value
					}
				 ]
			  }
		   }
		],
		onSuccess: function (transport) {
			var result;
			try {
				result = transport.responseText.parseJSON();
  			} catch(e) {
				error_overlay(transport.responseText);
			}

			if (result) 
			{
				window.location.reload();
			}
		},
		onFailure: function (transport) {
			alert('ajax fail');
		}
	});
}

/* An on-display function for the next payment adjustment screen. */
function display_layout1group1layer7 ()
{
	CurrentPayment = 'next';
	get_payment_arrangements('schedule', populate_next_payment_adjustment, document.forms['Application'].application_id.value, document.forms['Application'].company_id.value);
// get_schedule_preview(document.getElementById('application_id').value,
// document.getElementById('company_id').value);
	document.getElementById('schedule_preview').style.display =  'block';
}

/* An on-display function for the payment arrangements screen. */
function display_layout1group1layer1 ()
{
	CurrentPayment = 'arrange';
	get_payment_arrangements('arrangements', populate_payment_arrangements, document.forms['Application'].application_id.value, document.forms['Application'].company_id.value);
}
function display_layout1group1layer3 ()
{
	CurrentPayment = 'Manual';
}

/*
 * callback is what get_interest should call when its gotten the interest
 * amount. it will be called per this signature. row, base, and principal are
 * passthroughs the correponding values it was called with callback(String base,
 * String row, Float interest, Float principal);
 */
function get_interest(callback, base, row, principal, first_date, payment_date) {

		if (interest_retrieve_disabled) {
		return;
	}
	if (principal > 0 && payment_date.length > 0 && payment_mode == 'daily') 
	{

		var interest_rate = parseFloat(document.getElementById(base+"_svc_charge_percentage").value);
		var accrual_limit = parseFloat(document.getElementById(base+"_interest_accrual_limit").value);
		var app_id = document.getElementById('application_id').value;
		row = parseInt(row, 10);
		if(gpo(base + "_payment_type_" + row) && gpo(base + "_payment_type_" + row).value != 'payment_arranged')
		{
			use_next_day_payment = 0;
		}
		else
		{
			use_next_day_payment = 1;
		}
		if(gpo(base + "_payment_type_" + (row - 1)) && gpo(base + "_payment_type_" + (row - 1)).value != 'payment_arranged')
		{
			use_next_day_first = 0;
		}
		else
		{
			use_next_day_first = 1;
		}
		var req = ecash_calculator({
				id: row, 
				params:[{
					action:'interest',
					function:'calculateDailyInterest',
					rules:{
						svc_charge_percentage: interest_rate,
						interest_accrual_limit: accrual_limit
					},
					application_id:app_id,
					amount:principal,
					first_date:first_date,
					last_date:payment_date,
					use_next_business_day_payment:use_next_day_payment,
					use_next_business_day_first:use_next_day_first
				}], 
				onSuccess: function (transport) {
					var result = transport.responseText.parseJSON();
					var interest = parseFloat(result.result);

					callback(base, row, interest, principal);
				},
				onFailure: function (transport) {
					alert('ajax fail');
				}
			});

	} else {
		callback(base, row, 0.00, principal);
	}
}

function make_next_payment_adjustment_table_row (event) {
}

/**
 * Callback function used by get_payment_arrangements which is called by
 * display_layout1group1layer7() to generate the preview for "Next Payment
 * Adjustment" AKA Arrange Next Payment.
 */
function populate_next_payment_adjustment (schedule) 
{
	if (schedule.length <= 0) return false;
	var type_element = document.getElementById("next_payment_adjustment_payment_type_0");
	var date_element = document.getElementById("next_payment_adjustment_date_0");
	var amount_element = document.getElementById("next_payment_adjustment_amount_0");

	//[#45473] arrange next payment will now add all payments
	//(including reattempts) up to and including the next paydate
	var paydate_0 = document.getElementById("paydate_0");
	var next_paydate = undefined;

	if(typeof(paydate_0) != 'undefined')
	{
		var date = paydate_0.value;
		date = date.replace('-', '/');
		date = date.replace('-', '/');
		next_paydate = Date.parse(date);
	}

	if(typeof(next_paydate) == 'undefined')
	{
		next_paydate = Date.parse(schedule[0].event_date);
	}

	var total = 0;
	var i;
	for (i = 0; i < schedule.length; i++) 
	{
		var event_date = Date.parse(schedule[i].event_date);
		
		//if(schedule[0].event_date == schedule[i].event_date)
		if(event_date <= next_paydate)
		{
			var value = parseFloat(schedule[i].amount);
			if (value < 0)
			{
				total += value * -1;
			}
		}
	}

	/**
	 * Somewhat hackish way of getting the next due date. We're iterating
	 * through and just using the first debit to determine the date and type of
	 * the next payment.
	 */
	for (i = 0; i < schedule.length; i++) 
	{
		var value = parseFloat(schedule[i].amount);
		if (value < 0) 
		{
			date_element.value = schedule[i].date;
			type_element.value = schedule[i].type;
			break;
		}
	}
	
	amount_element.value = total;

	for(i=i;i<schedule.length;i++)
	{
		make_next_payment_adjustment_table_row(schedule[i]);
		
		/*
		 * payment_type date interest-balance fee_balance interest_accrued
		 * amount principal-balance description
		 * 
		 * var type_element =
		 * document.getElementById("next_payment_adjustment_payment_type_" + i);
		 * var date_element =
		 * document.getElementById("next_payment_adjustment_date_" + i); var
		 * amount_element =
		 * document.getElementById("next_payment_adjustment_amount_" + i); var
		 * actual_amount_element =
		 * document.getElementById("next_payment_adjustment_actual_amount_" +
		 * i); if (!(type_element && date_element && amount_element &&
		 * actual_amount_element)) { alert("Missing element" + type_element + ' - ' +
		 * date_element + ' - ' + amount_element + ' - ' + actual_amount_element ); }
		 * type_element.value = arrangements[i].type; date_element.value =
		 * arrangements[i].date; amount_element.value = arrangements[i].amount;
		 * actual_amount_element.value = arrangements[i].amount;
		 */
	}

	AdjustArrangedAmounts('next_payment_adjustment');
}

function populate_payment_arrangements (arrangements) 
{
	var num_arrangements = document.getElementById("payment_arrangement_num");
	num_arrangements.value = arrangements.length;
	ChangeRowDisplay('payment_arrangement');
	var last_date = document.getElementById("payment_arrangement_interest_paid_to_date").value;
	
	for(i=0;i<arrangements.length;i++)
	{
		// if its an adjustment_internal type transaction, that means its the
		// discount!
		if(arrangements[i].type == 'adjustment_internal')
		{
			var base = 'payment_arrangement';
			num_arrangements.value--;// = arrangements.length-1;

			// Get the principal balance. Depends on the pending state.
			var principal_balance = parseFloat(document.getElementById(base+"_principal_balance").value);
			var checkBox = document.getElementById(base+"_arr_incl_pend");
			if (checkBox && checkBox.checked) 
			{ // Include pending items
				principal_balance = parseFloat(document.getElementById(base+"_principal_balance_with_pending").value);
			}
	
			document.getElementById("payment_arrangement_percent_discount").value = discountamt;

			AdjustArrangedAmounts('payment_arrangement');
			UpdateDiscountDate(base);
		}
		else
		{
			var elements = {};
			elements.type = document.getElementById("payment_arrangement_payment_type_" + i);
			elements.date = document.getElementById("payment_arrangement_date_" + i);
			elements.amount = document.getElementById("payment_arrangement_amount_" + i);
			elements.actual_amount = document.getElementById("payment_arrangement_actual_amount_" + i);
			elements.interest_range_begin = document.getElementById("payment_arrangement_interest_range_begin_" + i);
			elements.interest_range_end = document.getElementById("payment_arrangement_interest_range_end_" + i);
			if (elements.type && elements.date && elements.amount && elements.actual_amount && elements.interest_range_begin && elements.interest_range_end) 
			{
				elements.type.value = arrangements[i].type;

				// Always set to the Due Date (date_effective)
				elements.date.value = arrangements[i].date;

				elements.amount.value = Math.abs(arrangements[i].amount);
				elements.actual_amount.value = Math.abs(arrangements[i].amount);
				elements.interest_range_end.value = arrangements[i].event_date;
				elements.interest_range_begin.value = last_date;
				last_date = arrangements[i].event_date;
			}
		}
	}
	AdjustArrangedAmounts('payment_arrangement');
}

function Get_Previous_Business_Day(action_type,principal,start,date)
{
	date = date.replace('-', '/');
	date = date.replace('-', '/');
	var req = ecash_calculator({
				id: action_type + '_total', 
				params:[{
					action:'paydate',
					function:'Get_Last_Business_Day',
					date:date
					}], 
				onSuccess: function (transport) {
					var result = transport.responseText.parseJSON();
					get_payout_total(action_type, action_type + '_total',principal , start ,result.result);
					
				},
				onFailure: function (transport) {
					alert('ajax fail');
				}
			});
}

function get_payout_total(base, row, principal, first_date, payment_date) {

	first_date = first_date.replace('-', '/');
	first_date = first_date.replace('-', '/');
	payment_date = payment_date.replace('-', '/');
	payment_date = payment_date.replace('-', '/');

	var payment_type = document.getElementById(base+"_svc_charge_type").value.toLowerCase();
	var interest_rate = parseFloat(document.getElementById(base+"_svc_charge_percentage").value);
	var accrual_limit = parseFloat(document.getElementById(base+"_interest_accrual_limit").value);
	var fees = parseFloat(document.getElementById("fee").value);
	var svc_charge_balance = parseFloat(document.getElementById("service_charge_balance").value);
	var amt = (fees + principal + svc_charge_balance);
	if (principal > 0 && payment_date.length > 0 && payment_type == "daily") 
	{
		
		interest_rate = parseFloat(document.getElementById(base+"_svc_charge_percentage").value);
		accrual_limit = parseFloat(document.getElementById(base+"_interest_accrual_limit").value);
		var app_id = document.getElementById("application_id").value;
		
	
		var req = ecash_calculator({
				id: row, 
				params:[{
					action:'interest',
					function:'calculateDailyInterest',
					rules:{
						svc_charge_percentage:interest_rate,
						interest_accrual_limit:accrual_limit
					},
					amount:principal,
					application_id:app_id,
					first_date:first_date,
					last_date:payment_date
				}], 
				onSuccess: function (transport) {
					var result = transport.responseText.parseJSON();
					var interest = parseFloat(result.result);
					var total = (interest + amt);
					document.getElementById(row).innerHTML = String(total.toFixed(2));
				

				},
				onFailure: function (transport) {
					alert('ajax fail');
				}
			});

	} 
	else 
	{
		document.getElementById(row).innerHTML = String(amt.toFixed(2));
	}
}

// Update a row. Our principal WAS 'principal' and our interest charge IS
// 'interest' on ROW 'row'
function AdjustArrangedSettlementAmountsRecursive(base, row, interest, previous_principal) 
{
	var total_arranged = 0.0, arranged_principal = 0.0, arranged_sc = 0.0;
	var arranged_interest = 0.0;
	var i, last_val;
	var current_value;
	var num_rows_visible = parseInt(document.getElementById(base+"_num").value, 10);

	row = parseInt(row, 10);

	// Data we need for initialization

	// Get the principal and fee balance. Depends on the pending state.
	var checkBox = document.getElementById(base+"_arr_incl_pend");
	var pending = '';
	if (checkBox && checkBox.checked) { // Include pending items
		pending = '_with_pending';
	}
	var principal_balance = parseFloat(document.getElementById(base+"_principal_balance"+pending).value);
	var sc_balance = parseFloat(gpo(base+"_service_charge_balance"+pending).value);
	var interest_balance = parseFloat(gpo(base+"_interest_balance"+pending).value);
	
	var remaining_sc = sc_balance;
    var remaining_princ = principal_balance;
	var remaining_interest = interest_balance;

	var last_payment_date = document.getElementById(base+"_interest_paid_to_date").value;

	// Set the interest and fee for this row.
	if (num_rows_visible < 1) return;
	
	var fee_balance = remaining_sc;
	gpo(base + '_interest_amount_' + row).value = FormatAmount(interest);
	gpo( base + '_payment_row_' + row + '_interest').innerHTML = FormatAmount(interest);

	var summary_balance;

	// Now total up the amounts, and set the subtotals
	for (i = 0; i < num_rows_visible; i ++) {

		summary_balance = gpo(base+"_summary_balance").innerHTML;

		current_value = parseFloat(gpo(base+"_actual_amount_"+i).value);
		
		if (isNaN(current_value)) current_value = 0;

		var current_interest = parseFloat(gpo(base+"_payment_row_"+i+"_interest").innerHTML);
		if (isNaN(current_interest)) current_interest = 0;

		var paid_princ = 0.0;

		var row_interest_balance = remaining_interest;
		fee_balance = remaining_sc;
		remaining_interest += current_interest;
		if (current_value >= remaining_interest) {
		// total_arranged += remaining_interest;
			arranged_interest += remaining_interest;
			current_value -= remaining_interest;
// alert('interest paid off ' + remaining_interest + ' - ' + arranged_interest);
			remaining_interest -= remaining_interest;

			if (current_value >= remaining_sc) {
			// total_arranged += remaining_sc;
				arranged_sc += remaining_sc;
				current_value -= remaining_sc;
// alert('fee paid off ' + remaining_sc + ' - ' + arranged_sc);
				remaining_sc -= remaining_sc;
				
				if (current_value >= remaining_princ) {
				// total_arranged += current_value;
					paid_princ = remaining_princ;
					arranged_principal += remaining_princ;
					var temp = remaining_princ - current_value;
					current_value -= remaining_princ;
// alert('principal paid off ' + remaining_princ + ' - ' + paid_princ);
					// remaining_princ -= remaining_princ;
					remaining_princ = temp;
				} else {
				// total_arranged += current_value;
					remaining_princ -= current_value;
					paid_princ = current_value;
					arranged_principal += current_value;_date;
// alert('principal paid ' + current_value + ' - ' + paid_princ);
					current_value -= current_value;
				}
			} else {
			// total_arranged += current_value;
				remaining_sc -= current_value;
				arranged_sc += current_value;
// alert('fee paid ' + current_value + ' - ' + arranged_sc);
				current_value -= current_value;
			}
		} else {
		// total_arranged += current_value;
			remaining_interest -= current_value;
			arranged_interest += current_value;
// alert('interest paid ' + current_value + ' - ' + arranged_interest);
			current_value -= current_value;
		}

		// Don't let them over or underpay for payment arrangements. Only act on
		// the last row displayed.
		// if (FormatAmount(current_value) > 0 && summary_balance != 0) //
		// imprecision leads to format.
		if (base == 'payment_arrangement') {
			if (num_rows_visible == i + 1) {
				// Adjust for discount
				var discount_amount = get_discount_amount(base, principal_balance);

				// not exact paying? Not legal. You can't submit unless the
				// balance is zero, you know.
				var left_to_pay = remaining_interest + remaining_sc + (remaining_princ < 0 ? 0 : remaining_princ) - discount_amount - current_value;

				var amount_final_payment = parseFloat(gpo(base+"_actual_amount_"+i).value) + left_to_pay;

				gpo(base+"_actual_amount_"+i).value = gpo(base+"_amount_"+i).value = FormatAmount(amount_final_payment);

			// total_arranged += amount_final_payment;
				total_arranged += discount_amount;
				arranged_interest += remaining_interest;
				arranged_sc += remaining_sc;
				arranged_principal += (remaining_princ < 0 ? 0 : remaining_princ);

				remaining_princ -= (left_to_pay - remaining_sc - remaining_interest);
				
				remaining_sc -= remaining_sc;
				remaining_interest -= remaining_interest;

			}
			else
			{
	// total_arranged += parseFloat(gpo(base+"_actual_amount_"+i).value);
			}
		}
		else
		{
	// total_arranged += parseFloat(gpo(base+"_actual_amount_"+i).value);
		}

		gpo(base+"_payment_row_"+i+"_balance").innerHTML = FormatAmount(remaining_princ);
		gpo(base+"_payment_row_"+i+"_interest_balance").innerHTML = FormatAmount(row_interest_balance);
		gpo(base+"_payment_row_"+i+"_fee_balance").innerHTML = FormatAmount(fee_balance);
		gpo(base+"_interest_range_begin_"+i).value = last_payment_date;
		last_payment_date = document.getElementById(base+"_date_"+i).value;
		gpo(base+"_interest_range_end_"+i).value = document.getElementById(base+"_date_"+i).value;
		
	}

	var nextrow = parseInt(row, 10) + 1;
	
	var this_principal_remaining = parseFloat(gpo(base+"_payment_row_"+row+'_balance').innerHTML);
	var this_event_date = document.getElementById(base+"_date_"+row).value;
	var last_event_date = gpo(base+"_date_"+nextrow) ? gpo(base+"_date_"+nextrow).value : null;


	if (nextrow < num_rows_visible && this_principal_remaining > 0 && last_event_date.length > 0) {
		get_interest(AdjustArrangedAmountsRecursive, base, nextrow, this_principal_remaining, this_event_date, last_event_date);
	}
	
	last_val = 0;

	var actuals = [];
	var displayed = [];
	var dates = [];
	var begins = [];
	var ends = [];
	for (i = 0; i < num_rows_visible; i++)
	{
		dates[i] = document.getElementById(base+"_date_"+i);
		actuals[i] = document.getElementById(base+"_actual_amount_"+i);
		displayed[i] = document.getElementById(base+"_amount_"+i);
		begins[i] = document.getElementById(base+"_interest_range_begin_"+i);
		ends[i] = document.getElementById(base+"_interest_range_end_"+i);
	
	}
	// Now reformat and redisplay all the visible rows
	var last_date = document.getElementById(base+"_interest_paid_to_date").value;
	for (i = 0; i < num_rows_visible; i++)
	{
		if (actuals[i].value > 0.0) last_val = i;
		actuals[i].value = FormatAmount(displayed[i].value);
		displayed[i].value = FormatAmount(displayed[i].value);
		begins[i].value = last_date;
		ends[i].value = last_date = dates[i].value;
		total_arranged += parseFloat(gpo(base+"_actual_amount_"+i).value);
	}

	if (discount_amount > 0.0)
	{
		actuals[last_val].value = FormatAmount(actuals[last_val].value - discount_amount);
		UpdateDiscountDate(base);
// document.getElementById("payment_discount_date").value =
// dates[last_val].value;
		document.getElementById(base+"_discount_displayed").innerHTML = FormatAmount(discount_amount);
		document.getElementById(base+"_discount_amount").value = FormatAmount(discount_amount);
	}

	// Finally set the displays at the top of the table
	document.getElementById(base+"_arranged_amount").innerHTML = FormatAmount(total_arranged);
	document.getElementById(base+"_principal_total").innerHTML = FormatAmount(arranged_principal);
	document.getElementById(base+"_principal_val").value = arranged_principal;
	document.getElementById(base+"_service_val").value = arranged_sc;
	document.getElementById(base+"_service_total").innerHTML = FormatAmount(arranged_sc);

	// Finally set the summary at the bottom of the table
	gpo(base+"_summary_payments_value").innerHTML = FormatAmount(total_arranged);
	gpo(base+"_summary_interest").innerHTML = FormatAmount(arranged_interest);
	gpo(base+"_summary_fee").innerHTML = FormatAmount(arranged_sc);
	gpo(base+"_summary_principal").innerHTML = FormatAmount(arranged_principal);

	// Show the balance remaining to be arranged
	var arranged_remaining = principal_balance.toFixed(2) + arranged_sc.toFixed(2) - total_arranged.toFixed(2);
	arranged_remaining = arranged_remaining.toFixed(2);
	
	gpo(base+"_summary_balance").innerHTML = FormatAmount(remaining_princ + remaining_sc + remaining_interest);
	
	if(arranged_remaining > 0) {
		document.getElementById(base+"_arranged_remaining").innerHTML = FormatAmount(arranged_remaining);
	} else if (parseInt(arranged_remaining, 10) == 0) {
		document.getElementById(base+"_arranged_remaining").innerHTML = '<font color=green>' + FormatAmount(arranged_remaining) + '</font>';
	} else {
		document.getElementById(base+"_arranged_remaining").innerHTML = '<font color=red>' + FormatAmount(arranged_remaining) + '</font>';
	}

	if(base == 'next_payment_adjustment' || base == 'partial_payment')
	{
		if (document.getElementById(base + '_amount_0').value < parseFloat(gpo(base+"_arrangement_min_payment").value) )
		{
			document.getElementById(base + '_amount_0').value = parseFloat(gpo(base+"_arrangement_min_payment").value);
		
		}
		
		if(base == 'next_payment_adjustment')
		{
			get_schedule_preview(document.getElementById('application_id').value, document.getElementById('company_id').value);
		}
	}
}
// Update a row. Our principal WAS 'principal' and our interest charge IS
// 'interest' on ROW 'row'
function AdjustArrangedAmountsRecursive(base, row, interest, previous_principal) 
{
	var total_arranged = 0.0, arranged_principal = 0.0, arranged_sc = 0.0;
	var arranged_interest = 0.0;
	var i, last_val;
	var current_value;
	var num_rows_visible = parseInt(document.getElementById(base+"_num").value, 10);

	row = parseInt(row, 10);

	// Data we need for initialization

	// Get the principal and fee balance. Depends on the pending state.
	var checkBox = document.getElementById(base+"_arr_incl_pend");
	var pending = '';
	if (checkBox && checkBox.checked) { // Include pending items
		pending = '_with_pending';
	}
	var principal_balance = parseFloat(document.getElementById(base+"_principal_balance"+pending).value);
	var sc_balance = parseFloat(gpo(base+"_service_charge_balance"+pending).value);
	var interest_balance = parseFloat(gpo(base+"_interest_balance"+pending).value);
	
	var remaining_sc = sc_balance;
    var remaining_princ = principal_balance;
	var remaining_interest = interest_balance;

	var last_payment_date = document.getElementById(base+"_interest_paid_to_date").value;

	// Set the interest and fee for this row.
	if (num_rows_visible < 1) return;
	
	var fee_balance = remaining_sc;
	gpo(base + '_interest_amount_' + row).value = FormatAmount(interest);
	gpo( base + '_payment_row_' + row + '_interest').innerHTML = FormatAmount(interest);

	var summary_balance;

	// Now total up the amounts, and set the subtotals
	for (i = 0; i < num_rows_visible; i ++) {

		summary_balance = gpo(base+"_summary_balance").innerHTML;

		current_value = parseFloat(gpo(base+"_actual_amount_"+i).value);
		
		if (isNaN(current_value)) current_value = 0;

		var current_interest = parseFloat(gpo(base+"_payment_row_"+i+"_interest").innerHTML);
		if (isNaN(current_interest)) current_interest = 0;

		var paid_princ = 0.0;

		var row_interest_balance = remaining_interest;
		fee_balance = remaining_sc;
		remaining_interest += current_interest;

			if (current_value >= remaining_sc) {
			// total_arranged += remaining_sc;
				arranged_sc += remaining_sc;
				current_value -= remaining_sc;
// alert('fee paid off ' + remaining_sc + ' - ' + arranged_sc);
				remaining_sc -= remaining_sc;
		if (current_value >= remaining_interest) {
		// total_arranged += remaining_interest;
			arranged_interest += remaining_interest;
			current_value -= remaining_interest;
// alert('interest paid off ' + remaining_interest + ' - ' + arranged_interest);
			remaining_interest -= remaining_interest;
				
				if (current_value >= remaining_princ) {
				// total_arranged += current_value;
					paid_princ = remaining_princ;
					arranged_principal += remaining_princ;
					var temp = remaining_princ - current_value;
					current_value -= remaining_princ;
// alert('principal paid off ' + remaining_princ + ' - ' + paid_princ);
					// remaining_princ -= remaining_princ;
					remaining_princ = temp;
				} else {
				// total_arranged += current_value;
					remaining_princ -= current_value;
					paid_princ = current_value;
					arranged_principal += current_value;
// alert('principal paid ' + current_value + ' - ' + paid_princ);
					current_value -= current_value;
				}
			} else {
			// total_arranged += current_value;
			remaining_interest -= current_value;
			arranged_interest += current_value;
// alert('interest paid ' + current_value + ' - ' + arranged_interest);
			current_value -= current_value;
			}
		} else {
			// total_arranged += current_value;
			remaining_sc -= current_value;
			arranged_sc += current_value;
// alert('fee paid ' + current_value + ' - ' + arranged_sc);
			current_value -= current_value;
		}

		// Don't let them over or underpay for payment arrangements. Only act on
		// the last row displayed.
		// if (FormatAmount(current_value) > 0 && summary_balance != 0) //
		// imprecision leads to format.
		if (base == 'payment_arrangement') {
			if (num_rows_visible == i + 1) {
				// Adjust for discount
				var discount_amount = get_discount_amount(base, principal_balance);

				// not exact paying? Not legal. You can't submit unless the
				// balance is zero, you know.
				var left_to_pay = remaining_interest + remaining_sc + (remaining_princ < 0 ? 0 : remaining_princ) - discount_amount - current_value;

				var amount_final_payment = parseFloat(gpo(base+"_actual_amount_"+i).value) + left_to_pay;

				gpo(base+"_actual_amount_"+i).value = gpo(base+"_amount_"+i).value = FormatAmount(amount_final_payment);

			// total_arranged += amount_final_payment;
				total_arranged += discount_amount;
				arranged_interest += remaining_interest;
				arranged_sc += remaining_sc;
				arranged_principal += (remaining_princ < 0 ? 0 : remaining_princ);

				remaining_princ -= (left_to_pay - remaining_sc - remaining_interest);
				
				remaining_sc -= remaining_sc;
				remaining_interest -= remaining_interest;
			}
			else
			{
	// total_arranged += parseFloat(gpo(base+"_actual_amount_"+i).value);
			}
		}
		else
		{
	// total_arranged += parseFloat(gpo(base+"_actual_amount_"+i).value);
		}

		gpo(base+"_payment_row_"+i+"_balance").innerHTML = FormatAmount(remaining_princ);
		gpo(base+"_payment_row_"+i+"_interest_balance").innerHTML = FormatAmount(row_interest_balance);
		gpo(base+"_payment_row_"+i+"_fee_balance").innerHTML = FormatAmount(fee_balance);
		gpo(base+"_interest_range_begin_"+i).value = last_payment_date;
		last_payment_date = document.getElementById(base+"_date_"+i).value;
		gpo(base+"_interest_range_end_"+i).value = document.getElementById(base+"_date_"+i).value;
		
	}

	var nextrow = parseInt(row, 10) + 1;
	
	var this_principal_remaining = parseFloat(gpo(base+"_payment_row_"+row+'_balance').innerHTML);
	var this_event_date = document.getElementById(base+"_date_"+row).value;
	var last_event_date = gpo(base+"_date_"+nextrow) ? gpo(base+"_date_"+nextrow).value : null;


	if (nextrow < num_rows_visible && this_principal_remaining > 0 && last_event_date.length > 0) {
		get_interest(AdjustArrangedAmountsRecursive, base, nextrow, this_principal_remaining, this_event_date, last_event_date);
	}
	
	last_val = 0;

	var actuals = [];
	var displayed = [];
	var dates = [];
	var begins = [];
	var ends = [];
	for (i = 0; i < num_rows_visible; i++)
	{
		dates[i] = document.getElementById(base+"_date_"+i);
		actuals[i] = document.getElementById(base+"_actual_amount_"+i);
		displayed[i] = document.getElementById(base+"_amount_"+i);
		begins[i] = document.getElementById(base+"_interest_range_begin_"+i);
		ends[i] = document.getElementById(base+"_interest_range_end_"+i);
	
	}
	// Now reformat and redisplay all the visible rows
	var last_date = document.getElementById(base+"_interest_paid_to_date").value;
	for (i = 0; i < num_rows_visible; i++)
	{
		if (actuals[i].value > 0.0) last_val = i;
		actuals[i].value = FormatAmount(displayed[i].value);
		displayed[i].value = FormatAmount(displayed[i].value);
		begins[i].value = last_date;
		ends[i].value = last_date = dates[i].value;
		total_arranged += parseFloat(gpo(base+"_actual_amount_"+i).value);
	}

	if (discount_amount > 0.0)
	{
		actuals[last_val].value = FormatAmount(actuals[last_val].value - discount_amount);
		UpdateDiscountDate(base);
// document.getElementById("payment_discount_date").value =
// dates[last_val].value;
// document.getElementById(base+"_discount_displayed").innerHTML =
// FormatAmount(discount_amount);
		document.getElementById(base+"_discount_amount").value = FormatAmount(discount_amount);
	}

	// Finally set the displays at the top of the table
	document.getElementById(base+"_arranged_amount").innerHTML = FormatAmount(total_arranged);
	document.getElementById(base+"_principal_total").innerHTML = FormatAmount(arranged_principal);
	document.getElementById(base+"_principal_val").value = arranged_principal;
	document.getElementById(base+"_service_val").value = arranged_sc;
	document.getElementById(base+"_service_total").innerHTML = FormatAmount(arranged_sc);

	// Finally set the summary at the bottom of the table
	gpo(base+"_summary_payments_value").innerHTML = FormatAmount(total_arranged);
	gpo(base+"_summary_interest").innerHTML = FormatAmount(arranged_interest);
	gpo(base+"_summary_fee").innerHTML = FormatAmount(arranged_sc);
	gpo(base+"_summary_principal").innerHTML = FormatAmount(arranged_principal);

	// Show the balance remaining to be arranged
	var arranged_remaining = principal_balance.toFixed(2) + arranged_sc.toFixed(2) - total_arranged.toFixed(2);
	arranged_remaining = arranged_remaining.toFixed(2);
	
	gpo(base+"_summary_balance").innerHTML = FormatAmount(remaining_princ + remaining_sc + remaining_interest);
	
	if(arranged_remaining > 0) {
		document.getElementById(base+"_arranged_remaining").innerHTML = FormatAmount(arranged_remaining);
	} else if (parseInt(arranged_remaining, 10) == 0) {
		document.getElementById(base+"_arranged_remaining").innerHTML = '<font color=green>' + FormatAmount(arranged_remaining) + '</font>';
	} else {
		document.getElementById(base+"_arranged_remaining").innerHTML = '<font color=red>' + FormatAmount(arranged_remaining) + '</font>';
	}

	if(base == 'next_payment_adjustment' || base == 'partial_payment')
	{
		if (document.getElementById(base + '_amount_0').value < parseFloat(gpo(base+"_arrangement_min_payment").value) )
		{
			document.getElementById(base + '_amount_0').value = parseFloat(gpo(base+"_arrangement_min_payment").value);
		
		}
		
		if(base == 'next_payment_adjustment')
		{
			get_schedule_preview(document.getElementById('application_id').value, document.getElementById('company_id').value);
		}
	}
}

function SaveSinglePayment(paytype, save_btn)
{
	// [#40758] disable button so agent doesn't double-submit
	save_btn.disabled = true;

	var amt = parseFloat(document.getElementById("amount").value);
	var max = parseFloat(document.getElementById("posted_total").value);
	if (isNaN(amt) || (amt <= 0))
	{
		alert("Please enter a positive dollar value for the amount.");
		save_btn.disabled = false;
		return;
	}

	if ((amt > max) && (paytype != 'refund'))
	{
		alert("Please enter a dollar value less than the outstanding loan amount.");
		save_btn.disabled = false;
		return;
	}

	var date_ele = document.getElementById("scheduled_date");
	var selected = 'select';
	if (document.getElementsByName("edate"))
	{
		for(i=0;i<document.getElementsByName("edate").length;i++)
		{
			if (document.getElementsByName("edate")[i].checked)
			{
				selected = document.getElementsByName("edate")[i].value;
			}
		}
	}
	if (date_ele != null && date_ele.value.match("[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}") == null && selected == 'select' )
	{
		alert("Please enter a valid date matching mm/dd/yyyy (got " + date_ele.value + ")");
		save_btn.disabled = false;
		return;
	}
	
	// Only run validation checks when the date element isn't null
	// so we don't fail the checks for Refunds. [#24859]
	if(date_ele != null && !checkDate(date_ele, false, false, false, false))
	{
		alert("Date validation failed!");
		save_btn.disabled = false;
		return;
	}

	document.forms[0].submit();
}

function PostDebtConsolidation(paytype)
{
	var amt = parseFloat(document.getElementById("actual_amount").value);
	if (isNaN(amt) || (amt <= 0))
	{
		alert("Please enter a positive dollar value for the payment amount.");
		return false;
	}
	
	var es_id = document.getElementById("event_schedule_id").value;
	if (es_id == 0) {
		alert("Please choose a debt consolidation event to post.");
		return false;
	}

	document.forms[0].submit();
}


function SaveDebitPayments(save_btn)
{
	// [#40758] disable button so agent doesn't double-submit
	save_btn.disabled = true;

	var form = document.forms['debt_consolidation_payment_form'];	
	var has_payment_arrangements = document.forms['Payment Arrangements'].has_payment_arrangements.value;

	// GF #12858: If there's no debt consolidation companies, show an error
	// [benb]
	var debt_companies = document.getElementById('debt_consolidation_company');

	// Make sure a debt company exists
	if (debt_companies.length == 0)
	{
		alert("You need to have a debt consolidation company in order to make a debt consolidation payment!");
		save_btn.disabled = false;
		return false;
	}

	// Make sure the payment date field is not empty.
	date = document.getElementById('debt_consolidation_date');
	if (date.value == "")
	{
		alert("You cannot leave the payment date empty.");
		save_btn.disabled = false;
		return false;
	}

	amount = document.getElementById('debt_consolidation_amount');

	if (amount.value <= 0)
	{
		alert('Payment amount must be > $0');
		save_btn.disabled = false;
		return false;
	}

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
	return true;	
}

function SavePayments(base, save_btn)
{	
	// [#40758] disable button so agent doesn't double-submit
	save_btn.disabled = true;

	var date;
	var amount;
    var str = '';
	var total_arranged;
	var include_pending = document.getElementById(base+"_arr_incl_pend").checked;
	var total_balance_with_pending = parseFloat(document.getElementById(base+"_total_balance_with_pending").value);
	var total_balance_without_pending = parseFloat(document.getElementById(base+"_total_balance_without_pending").value);
	var total_balance = include_pending ? total_balance_without_pending : total_balance_with_pending;
	var form = document.forms[base+'_payment_form'];

	var num_rows_visible = parseInt(document.getElementById(base+"_num").value, 10);
	for (var i = 0; i < num_rows_visible; i++)
	{
		var interest = parseFloat(gpo(base+"_interest_amount_"+i).value);
		if(!isNaN(interest)) //[#41103] Insure total_balance doesn't get set to NaN, and then zero (0)
		{
			total_balance += interest;
		}
		date = new Date(serverdate);
		date.setTime(Date.parse(document.getElementById(base+"_date_"+i).value));
		if (date.getFullYear() < 1950) 
		{
			date = new Date(100 + date.getFullYear(), date.getMonth() + 1, date.getDate());
			document.getElementById(base+"_date_"+i).value =
				date.getMonth() + '/' + date.getDate() + '/' + date.getFullYear();
		}
		if (isNaN(date))
		{
			str = "The dates for all intended \npayments";
			str += " must be valid. (" + date + ") (" +  document.getElementById(base+"_date_"+i).value + ") (row " + (i+1) + ")";
			gpo(base+'_date_'+i).value = '';
			alert(str);
			save_btn.disabled = false;
			return;
		}
		else
		{
			var now = new Date(serverdate);
			// This is to eliminate the difference in minutes, seconds, etc.
			var now = new Date(now.getFullYear(), now.getMonth(), now.getDate());
			// Added to ensure payment dates fall within a year of the current
			// date.
			var year = new Date(now.getFullYear() + 1, now.getMonth(), now.getDate());
			if (base == 'manual_payment')
			{
				if (!checkDate(document.getElementById(base+"_date_"+i),true, base+"_payment_type_"+i))
				{
					save_btn.disabled = false;
					return;
				}
/*
 * if (date > now) { var str = "Manual Payments must be in the past.";
 * alert(str); return; }
 */			}
			else
			{
				if (!checkDate(document.getElementById(base+"_date_"+i), false, base+"_payment_type_"+i))
				{
					save_btn.disabled = false;
					return;
				}
				if (date < now)
				{
					str = "All arranged dates must be\n";
					str += "no earlier than today.";
					alert(str);
					save_btn.disabled = false;
					return;
				}
				
				if (date > year)
				{
					str = "Arranged payment dates can not\n";
					str += " be made more than a year in advance.";
					alert(str);
					save_btn.disabled = false;
					return;
				}
			}
		}

		// GF #13256: partial payment spec requires a minimum payment of $25.00
		if (base == 'partial_payment')
		{
			amount = parseFloat(document.getElementById(base+"_amount_"+i).value);

			if (isNaN(amount) || amount < 25.00)
			{
				alert("Partial Payment must be >= $25.00");
				save_btn.disabled = false;
				return;
			}
			
			if(document.getElementById("partial_payment_interest_range_begin_0").value.length < 3 || 
			document.getElementById("partial_payment_interest_range_end_0").value.length < 3)
			{
				alert("There is a problem with the Interest Calculation, please try refreshing this application and initiating another partial payments transaction.");		
				save_btn.disabled = false;
				return;
			}			
			
		}
		
		if ((base == 'payment_arrangement') || (base == 'manual_payment')) {
			
			amount = document.getElementById(base+"_amount_"+i).value;
			if (isNaN(amount) || amount <= 0) {
				str = "The amount for all intended \npayments";
				str += " must be greater than zero.";
				alert(str);
				save_btn.disabled = false;
				return;
			}
		}
		gpo(base+"_actual_amount_"+i).value = gpo(base+"_amount_"+i).value;
	}
	
	total_arranged = parseFloat(document.getElementById(base+"_arranged_amount").innerHTML);

	principal_paid = parseFloat(document.getElementById(base+"_summary_principal").innerHTML);
	total_paid = parseFloat(gpo(base+"_summary_payments_value").innerHTML);

// if ((total_arranged.toFixed(2) != total_balance) && (base ==
// 'payment_arrangement'))
	if ((total_paid.toFixed(2) != total_balance.toFixed(2)) && (base == 'payment_arrangement'))
	{
		str = "The exact amount of the balance (" + FormatAmount(total_balance) + " vs " + FormatAmount(total_paid) + ") must be paid\n";
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

/* Use to write to a DIv Dynamicly */
function writit(text,id)
{
	if (document.getElementById)
	{
		x = document.getElementById(id);
		x.innerHTML = '';
		x.innerHTML = text;
	}
	else if (document.all)
	{
		x = document.all[id];
		x.innerHTML = text;
	}
	else if (document.layers)
	{
		x = document.layers[id];
		text2 = '<P CLASS="testclass">' + text + '</P>';
		x.document.open();
		x.document.write(text2);
		x.document.close();
	}
}
function CreateDebitPayments(add_check)
{
	var include_pending = document.getElementById("debt_consolidation_arr_incl_pend").checked;
	var principal = document.getElementById("debt_consolidation_principal_balance").value;
	var pending_principal = document.getElementById("debt_consolidation_principal_balance_with_pending").value;
	var interest_date = document.getElementById("debt_consolidation_last_service_charge_date").value;
	var pending_interest_date = document.getElementById("debt_consolidation_last_pending_service_charge_date").value;
	var debit_date_base = document.getElementById("debt_consolidation_date").value;
	var fund_date = document.getElementById("debt_consolidation_fund_date").value;

	if (include_pending) {
		principal = pending_principal;
		interest_date = pending_interest_date;
	} 

	if (interest_date.length == 0) {
		interest_date = fund_date;
	}

	get_interest(CreateDebitPaymentsWithInterest, 'debt_consolidation', add_check, principal, interest_date, debit_date_base);
}

function CreateDebitPaymentsWithInterest(a, add_check, interest_accrued, c)
{
	document.getElementById("debt_consolidation_final_interest_charge").value = interest_accrued;
	var include_pending = document.getElementById("debt_consolidation_arr_incl_pend").checked;
	var posted_total = parseFloat(document.getElementById("debt_total_posted").value);
	var pending_total = parseFloat(document.getElementById("debt_total_pending").value);

	if (include_pending)
    {
		document.getElementById("interest_accrued").innerHTML = FormatAmount(document.getElementById("debt_interest_accrued_pending").value);
		posted_total = pending_total + interest_accrued;
	}
    else
    {
		document.getElementById("interest_accrued").innerHTML = FormatAmount(document.getElementById("debt_interest_accrued_posted").value);
		posted_total += interest_accrued;
	}
	
	document.getElementById("debt_amount_arrange").innerHTML = parseFloat(posted_total).toFixed(2);
	
	// var debit_comp_base =
	// document.getElementById("debt_consolidation_company").text;
	var debit_date_base = document.getElementById("debt_consolidation_date").value;
	var debit_amt_base_ele = document.getElementById("debt_consolidation_amount");
	var debit_amt_base = debit_amt_base_ele.value;
	var errmsg = "";
	
	if(debit_date_base == "") 
		errmsg = errmsg + "Please specify a valid Payment Date.\n";

	// PayDate Array cant do that many paydates
	if(parseFloat(debit_amt_base) == 1)
		return false;
		
	if (parseFloat(debit_amt_base) > posted_total) {
		debit_amt_base_ele.value = FormatAmount(posted_total);
		return false;
	}
		
	if((parseFloat(debit_amt_base) > 2) && (parseFloat(debit_amt_base) == debit_amt_base)) {

		var amt_spliiter = debit_amt_base.split(".");
		if(amt_spliiter.length < 3)
		{
			if((amt_spliiter.length == 2) && (amt_spliiter[1].length > 2))
			{
				errmsg = errmsg + "Please specify a valid Payment Amount.\n";
			}
		}
		else
		{
			errmsg = errmsg + "Please specify a valid Payment Amount.\n";
		}
	}
	else 
	{
		errmsg = errmsg + "Please specify a valid Payment Amount.\n"; 
	}

	if(errmsg != "")
	{
		if(add_check)
			alert(errmsg);
		return false;
	}

	var debit_amt = debit_amt_base;	
	var disp_date = debit_date_base + "<br>";
	var disp_amt = "$" + parseFloat(debit_amt_base).toFixed(2)  + "<br>";
	var pay_amt = disp_amt;
	
	var date_count = 0;
	while(parseFloat(debit_amt) < parseFloat(posted_total))
	{
		if(parseFloat(debit_amt) + parseFloat(debit_amt_base) > parseFloat(posted_total))
		{
			pay_amt = pay_amt + "$" + parseFloat(posted_total - debit_amt).toFixed(2) + "<br>";
			debit_amt = posted_total;
			disp_amt = disp_amt + "$" + parseFloat(posted_total).toFixed(2) + "<br>";			
		}
		else
		{
			pay_amt = pay_amt + "$" + parseFloat(debit_amt_base).toFixed(2) + "<br>";
			debit_amt = parseFloat(debit_amt) + parseFloat(debit_amt_base);
			disp_amt = disp_amt + "$" + parseFloat(debit_amt).toFixed(2) + "<br>";
		}
		date_count++;
	}
	
	if(req = newXMLReqObject())
	{
	    var url = "validate_calendar.php?generate=daterange&datestr=" + debit_date_base +"&daterange=" + date_count;
	    req.open("GET", url, false);
	    req.send("");
	}
	
	if(req.readyState == 4) {
		disp_date = disp_date + req.responseText;		
	}
	document.forms['debt_consolidation_payment_form'].debt_payments.value = date_count;
	
	writit(disp_date,"paydate_display_list");
	writit(pay_amt,"payamt_display_list");
	writit(disp_amt,"total_display_list");	

	return true;
}

function ClearPayments(base)
{
	var str = base+"_amount_\\d+|"+base+"_date_\\d+|"+base+"_desc_\\d+|"+base+"_actual_amount_\\d+";
	var re_general = new RegExp(str);
	var re_sel = new RegExp(base+"_payment_type_(\\d+)");
	var re_date = new RegExp(base+"_date_(\\d+)");
	var ctrls = window.document.forms[base+'_payment_form'].elements;	

	for (var i = 0; i < ctrls.length; i++)
	{
		if (re_date.test(ctrls[i].id)) ClearErrorDisplay(ctrls[i]);
		if (re_general.test(ctrls[i].id)) ctrls[i].value = "";
		if (re_sel.test(ctrls[i].id)) ctrls[i].selectedIndex = 0;
	}
	AdjustArrangedAmounts(base);
}

function ChangeRowDisplay(base)
{
	var el = document.getElementById(base + "_num");
	if (el != null)
	{
		var newval = parseInt(el.value, 10);
		var table = document.getElementById(base+"_payment_table");
		var num_rows = table.rows.length;
		var summary = gpo(base+'_summary_row');

		for (var i = 0; i <= num_rows; i++)
		{
			var row = i == num_rows ? gpo(base+'_discount_row') : gpo(base+'_payment_row_'+i);
			
			if (row)
            {
				var element = row.parentNode.removeChild(row);
				summary.parentNode.insertBefore(element, summary);

				if (i < num_rows) {
					if (i < newval) {
						row.style.display = 'table-row';
					} else {
						row.style.display = 'none';
						gpo(base+'_amount_'+i).value = '';
						gpo(base+'_actual_amount_'+i).value = '';
						gpo(base+'_date_'+i).value = '';
					}
				}
			}
		}

		//[#44306] Not sure if this is needed here, but it's resetting the date
		//to the default
		//set_payment_mode(base); // visibility of display:none elements doesn't
								// persist, so we need to reset them.
		UpdateDiscountDate(base);
	}
}

function get_discount_amount (base, principal_balance)
{
	var discount_amount = 0;
	var discount_percentage = 0;
	var discount_percentage_element = gpo(base+'_percent_discount');
	var discount_amount_element = gpo(base+'_discount_amount');
	var discount_principal_balance_element = gpo(base+'_discount_row_balance');
	if (null != discount_percentage_element) 
	{
		discount_percentage = discount_percentage_element.value;
		if (discount_percentage != 'absolute')
		{
			discount_amount = principal_balance * parseFloat(discount_percentage) / 100;
		}
		else if (null != discount_amount_element)
		{
			discount_amount = parseFloat(discount_amount_element.value);
		}
	}
	SetDiscountDisplay(base);
	discount_amount_element.value = discount_amount.toFixed(2);
	discount_principal_balance_element.innerHTML = (0.00).toFixed(2);
	return discount_amount;
}

function SetupTransactions()
{
	// We don't need to do anything for the funding module
	if(modestring == 'verification' || modestring == 'underwriting')
	{
		return;
	}
	 
	var el;

	// Set the discount change handlers
	for (var i = 0; i < bases.length; i++)
	{
		var base = bases[i];
		
		SetDiscountDisplay(base);
		el = document.getElementById(base + "_percent_discount");
		if (el != null) el.onchange = new Function("event", "AdjustArrangedAmounts('"+base+"', event);");

		el = document.getElementById(base + "_num");
		if (el != null) el.onchange = new Function("ChangeRowDisplay('"+base+"');AdjustArrangedAmounts('"+base+"');");

		var form = document.forms[base+'_payment_form'];
		ctrls = new Array();
		ctrls = form.getElementsByTagName("INPUT");
	
		var re_amt = new RegExp(base+"_amount_(\\d+)");
		var re_date = new RegExp(base+"_date_(\\d+)");
		var re_type = new RegExp(base+"_payment_type_(\\d+)");
		
		for (var j = 0; j < ctrls.length; j++)
		{
			if (re_amt.test(ctrls[j].id))
			{
				ctrls[j].onchange = new Function("event", "AdjustArrangedAmounts('"+base+"', event);");
				ctrls[j].onkeyup = new Function("event", "DelayAdjustArranged('"+base+"', event);");
			}
			else if(re_date.test(ctrls[j].id))
			{
				ctrls[j].onchange = new Function("event","UpdateDiscountDate('"+base+"'); AdjustArrangedAmounts('"+base+"', event);");
			}
		}
		
		ctrls = form.getElementsByTagName("SELECT");
		var PaymentTypeOffset = 0;
		var PaymentSelectID = '';
		var PaymentDateID = '';
		var PaymentDateSelectID = '';
		for (var j = 0; j < ctrls.length; j++)
		{
			if (re_type.test(ctrls[j].id))
			{
				PaymentSelectID = base + "_payment_type_" + (j - PaymentTypeOffset);
				PaymentDateID = base + "_date_" + (j - PaymentTypeOffset);
				PaymentDateSelectID = base + "_date_selectanchor_" + (j - PaymentTypeOffset);
				
				// Set the initial calendar type
				SetCalendar(PaymentSelectID, PaymentDateID, PaymentDateSelectID, (j - PaymentTypeOffset), base, null, false);
				
				// Then set the payment_type select box onchange to set the
				// calendar based on its current selection
				ctrls[j].onchange = new Function("event", "SetCalendar('" + PaymentSelectID + "', '" + PaymentDateID + "', '" + PaymentDateSelectID + "', '"+(j - PaymentTypeOffset)+"', '"+base+"', event, true);");
				ctrls[j].onkeyup = new Function("event", "DelayAdjustArranged('"+base+"', event);");
			}
			else
			{
				PaymentTypeOffset++;
			}
		
		}
		
		set_payment_mode(base);
		ChangeRowDisplay(base);
	}
}

// All of the pop calendar code for manually arranged payments
// has been moved out of build_display.class.php and into this
// function. [GF #21017]
function SetCalendar(selectid, dateinputid, dateselectid, row, base, event, adjustArrangedAmounts)
{
	// Call This If We Changed The Payment Type
	if(adjustArrangedAmounts == true)
		AdjustArrangedAmounts(base, event);

	var pullDown = document.getElementById(selectid);
	var dateInput = document.getElementById(dateinputid);
	var dateSelect = document.getElementById(dateselectid);
	
	var selectedIndex = pullDown.selectedIndex;
	var selectedName = pullDown.options[selectedIndex].value;
	
	var fundDate = document.getElementById('date_fund_actual_hidden').value;
	var dateForward = document.getElementById(base + "_date_allowed_forward_" + row).value;
	
	if(dateForward == '')
		dateForward = undefined;

	var allowHolidays = undefined;
	var allowWeekends = undefined;
	var pastDates = false;
	var endDate = undefined;
		
	if(selectedName == 'payment_arranged') // ACH Payments
	{
		var today        = new Date(serverdate);
		if((dateInput.value != '') && ! isDateACHSafe(dateInput.value))
		{
			var safeDate = new Date(nextSafeACHDueDate);
			dateInput.value = (safeDate.getMonth()+1) + "/" + safeDate.getDate() + "/" + safeDate.getFullYear();
		}

		var current_date = new Date(dateInput.value);
		var weekday      = current_date.getDay();
		
		// Incase an agent sets the date to a weekend and then tries to set the
		// select box back to ACH
		if(weekday == 0 || weekday == 6)
		{
			var day   = today.getDate();
			var month = today.getMonth();
			var year  = today.getFullYear();
			month++;
			
			dateInput.value = month+"/"+day+"/"+year;
		}
		
		dateInput.readOnly = true;
	}
	else
	{
		// If it is not an ACH payment, then allow weekends
		dateInput.readOnly = false;
		allowWeekends = true;
	}
	
	if(base == 'manual_payment')
	{
		// Manual payments (with the exception of personal checks) are always
		// made
		// in the past, so set the 'fund_date' to the next business day and
		// enable
		// past dates.
		fundDate = document.getElementById(base + "_next_day_" + row).value;
		pastDates = true;
		allowHolidays = true;
		if(selectedName == 'personal_check' && gpo('manual_payment_check_type').value == 'ACH')
			pastDates = false;
	}
	else if(base == 'next_payment_adjustment' || base == 'partial_payment')
	{
		// Set the end date so the calendar doesn't go on and on.
		endDate = dateForward;
	}

	dateSelect.onclick = new Function("event", "PopCalendar('" + dateinputid + "', event.clientX, event.clientY, '" + fundDate + "', " + pastDates + ", '" + selectid + "', " + (typeof(endDate) != undefined ? "'" + endDate + "'" : endDate)  + ", " + allowHolidays + ", " + allowWeekends + ");");
}

function UpdateAmountLeftToArrange(base) 
{
	var inputBox = document.getElementById(base+"_total_balance");
	
	var checkBox = document.getElementById(base+"_arr_incl_pend");
	if (checkBox) {
		if (checkBox.checked) { // Include pending items
			inputBox.value = document.getElementById(base+"_total_balance_with_pending").value;
		} else {
			inputBox.value = document.getElementById(base+"_total_balance_without_pending").value;
		}
	}
	
	AdjustArrangedAmounts(base);
}

function CheckAccountEdit()
{
	var status = document.getElementById("account_status").value;
	var pb = document.getElementById("principal_balance").value;
	var fb = document.getElementById("fees_balance").value;

	if (status == '18')
	{
		var ret_amt = document.getElementById("return_amount").value;
		ret_amt = parseFloat(ret_amt);
		if (isNaN(ret_amt))
		{
			alert("Please enter a valid amount for the return amount.");
			return false;
		}
	}

// if (/(7|11|14|17)/.test(status))
	if (status.match("^7|11|14|17$"))
	{
		var date = document.getElementById("status_date");
		if (date.value == '') {
			alert("Please enter a valid date. (Format: DD/MM/YYYY)");
			date.focus();
			return false;
		}
		if (!checkDate(date, true)) {
			return false;
		}
/*
 * var val = Date.parse(date); var now = new Date(); if (isNaN(val) || (val >=
 * (new Date(now.getFullYear(), now.getMonth(), now.getDate())))) { alert("For
 * the status selected you must enter a past date."); return false; }
 */
	}
	
	if (isNaN(parseFloat(pb)) || (pb == ''))
	{
		alert("Please enter a valid value\nfor the principal balance.");
		return false;
	}

	if (isNaN(parseFloat(fb)) || (fb == ''))
	{
		alert("Please enter a valid value\nfor the fees balance.");
		return false;
	}
	document.forms['account_edit'].submit();
}

function CheckVerifyImport()
{
	if (confirm("This will complete the import and commit the changes. Continue?"))
	{
		var cs = document.getElementById("change_status_action");
		cs.value = "verify_import";
		document.forms['change_status'].submit();
	}
}

function CheckResetImport()
{
	if (confirm("This will delete the schedule and leave the account in the conversion queue.\nContinue?"))
	{
		var cs = document.getElementById("change_status_action");
		cs.value = "reset_import";
		document.forms['change_status'].submit();
	}
}

function SendToManagers()
{
	var cs = document.getElementById("change_status_action");
	cs.value = "send_managers";
	document.forms['change_status'].submit();
}

function CheckStatusSelection(sel)
{
	var el1 = document.getElementById('status_date');
	var el2 = document.getElementById('return_amount');
	var el3 = document.getElementById('controlling_agent');

	if (sel.options[sel.selectedIndex].value == '18' || sel.options[sel.selectedIndex].value == '21')
	{
		el2.disabled = false;
	}
	else
	{
		el2.disabled = true;
	}

	if (/(7|11|14|17)/.test(sel.options[sel.selectedIndex].value))
	{
		el1.disabled = false;
	}
	else
	{
		el1.disabled = true;
	}

	if (/(4|5|10|11|16)/.test(sel.options[sel.selectedIndex].value))
	{
		el3.disabled = false;
	}
	else
	{
		el3.disabled = true;
	}
}

function CheckShiftDate(sel)
{
	var str = "Would you like to change\n the event's date to ";
	var text = sel.options[sel.selectedIndex].innerHTML;
	str += text + "?";

	if (confirm(str))
	{
		document.forms['schedule_form'].submit();
	}
}

function VerifyPayout()
{
	if (confirm("This will schedule a full payout on the next pay date. Continue?"))
	{
		OpenTransactionPopup('payout','Add Payout','account_mgmt');
// var form = document.forms['change_status'];
// form.elements['change_status_action'].value = "schedule_payout";
// form.submit();
	}
}

function VerifyCancel()
{
	if (confirm("This will immediately cancel the balance of the loan. Continue?"))
	{
		var form = document.forms['change_status'];
		form.elements['change_status_action'].value = "cancel_loan";
		form.submit();
	}
}

function VerifySwitchSchedule()
{
	if (confirm("This changes schedule between ach and card payment. Continue?"))
	{
		var form = document.forms['change_status'];
		form.elements['change_status_action'].value = "switch_schedule";
		form.submit();
	}
}

function VerifyPlaceInQC()
{
	if (confirm("This will make the account eligible for the\nnext Quick Check batch. Continue?"))
	{
		var form = document.forms['change_status'];
		form.elements['change_status_action'].value = "to_quickcheck_ready";
		form.submit();
	}
}

function ConfirmModify(id, type, appid, action)
{
	if (confirm("This will "+ action  +" "+ type +" "+ (id == null ? '' : id) +". Continue?"))
	{
		var form = document.forms['transaction_details_form'];
		form.specific_action.value = action;
		form.submit();
	}
}

function AddFee(type, amount, description)
{
	if(type != '')
	{
		var form = document.forms['Fees_form'];
		form.type.value = type;
	}
	
	if(amount == 0)
	{
		if(confirm("This will add a " + description + " to this account.  Continue?"))
		{
			form.submit();
		}
	}
	else if(confirm("This will add a $" + amount + " " + description + " to this account.  Continue?"))
	{
			form.submit();
	}
}

function Deceased_Unverified()
{
	if (confirm("This will place this application in Deceased Unverified status. Continue?"))
	{
		var form = document.forms['AppActionDeceasedUnverifiedForm'];
		form.submit();
	}
}

function Deceased_Verified()
{
	if (confirm("This will place this application in Deceased Verified status. Continue?"))
	{
		var form = document.forms['AppActionDeceasedVerifiedForm'];
		form.submit();
	}
}

function Regenerate()
{
	if (confirm("This will place this application in an Active status and regenerate the payment schedule. Continue?"))
	{
		var form = document.forms['AppActionRenewForm'];
		form.submit();
	}
}

function executeRollover()
{
	var form = document.forms['rollover_form'];
	if(confirm("This will create a rollover for this account.  Continue?"))
	{
		form.submit();
	}
}

function Recall_2nd_Tier()
{
	if(confirm("This will rebuild the customer's schedule and continue to collect on the account.  Continue?"))
	{
		var form = document.forms['AppActionRecallForm'];
		form.submit();
	}
}

// Calendar Functions & AJAX Calls
function CheckValue(e)
{
	var value = e.options[e.selectedIndex].value;
	if (value == 'DATE')
	{
		document.getElementById("date_span").style.display = "inline";
	}
	else
	{
		document.getElementById("date_span").style.display = "none";
	}
}

// Grab new XMLHttpRequest Object
function newXMLReqObject()
{
   var req = false;
   // branch for native XMLHttpRequest object
   if(window.XMLHttpRequest) {
      try {
         req = new XMLHttpRequest();
      } catch(e) {
         req = false;
      }
    // branch for IE/Windows ActiveX version<script type="text/javascript">
   } else if(window.ActiveXObject) {
      try {
         req = new ActiveXObject("Msxml2.XMLHTTP");
      } catch(e) {
         try {
            req = new ActiveXObject("Microsoft.XMLHTTP");
         } catch(e) {
            req = false;
         }
      }
   }

   if(req) {
      return req;
   } else {
      return false;
   }
}

function WarnESig()
{
	//return confirm("The changes you are making requires that the customer re-sign their loan documents. By proceeding, the customer will be put into the 'In Process' status, and will need to have documents resent (by clicking 'Send e-sig') to email new loan documents and put them into the Pending status. Confirm?");
        return confirm("The changes you are making requires that the customer re-sign their loan documents. If any field of the edit panel is changed, the customer will be put into the 'In Process' status, and will need to have documents resent (by clicking 'Send e-sig') to email new loan documents and put them into the Pending status. Confirm?");
}

function ConfirmESig()
{
	return confirm('Resend loan documents?');
}

function ConfirmAFOverride()
{
	return confirm('Override auto-funding?');
}

/**
 * Compares the ACH Safe Due Date with the specified field
 * to determine if it's safe.
 */
function isDateACHSafe(dateFieldIdName)
{
	var dateOne = Date.parse(nextSafeACHDueDate);
	
	var dateValue = document.getElementById(dateFieldIdName);

	if(!dateOne || !dateValue) return false;
	
	var dateTwo = Date.parse(dateValue.value);
	
	var diff = dateTwo - dateOne;
	var days = Math.floor(diff / ( 1000 * 60 * 60 * 24));

	if(days >= 0)
	{
		return true
	}

	return false;
}
