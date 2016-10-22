<?php
require_once("config.php");

require_once(LIB_DIR . "common_functions.php");
require_once(SQL_LIB_DIR . "util.func.php");

// Connect to the database
$holiday_ary = Fetch_Holiday_List();


// Time to create the dropdown list based on the type passed
$type = $_GET["type"];
switch($type)
{
 case "Underwriting":
 case "Verification":
 {
	 $opts = <<<EOS
		 <option value="DATE">&lt;time&gt;</option>  // mantis:3144
		 <option value="5 minute">5 Minutes</option>
		 <option value="30 minute">30 minutes</option>
		 <option value="1 hour">1 Hour</option>
		 <option value="2 hour">2 Hours</option>
		 <option value="4 hour">4 Hours</option>
		 <option value="24 hour">24 Hours</option>
EOS;
 } break;
 default:
 {
	 $opts = <<<EOS
		 <option value="DATE">&lt;time&gt;</option>
		 <option value="5 minute">5 Minutes</option>
		 <option value="30 minute">30 minutes</option>
		 <option value="1 hour">1 Hour</option>
		 <option value="2 hour">2 Hours</option>
		 <option value="4 hour">4 Hours</option>
		 <option value="24 hour">24 Hours</option>
EOS;
 } break;

}

?>
<html>
<head>
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="js/calendar/calendar-dp.css">
<script type="text/javascript" src="js/transactions.js"></script>
<script type="text/javascript" src="js/calendar/calendar.js"></script>
<script type="text/javascript" src="js/calendar/lang/calendar-en.js"></script>
<title>Schedule Follow-Up</title>
<script type="text/javascript">
function CheckValue(e)
{
	var value = e.options[e.selectedIndex].value;
	if (value == 'DATE')
	{
		document.getElementById("date_span").style.display = "table-row";
	}
	else
	{
		document.getElementById("date_span").style.display = "none";
	}
}

function CheckInterval()
{
	var s = document.getElementById("interval");
	var choice = s.options[s.selectedIndex].value;
	
	var comment = document.getElementById('frm_comment');
	if (comment.value == '') {
		alert("Please add a comment to this followup.");
		return false;
	}
	if (choice == "DATE")
	{
		var intdate = document.getElementById("follow_up_date").value;
		if (isNaN(Date.parse(intdate)) || (Date.parse(intdate) < new Date))
		{
			alert("Please enter a valid date.");
			return false;
		}
		
		PHPValidateDate(intdate);

	}
	else
	{
		document.followup.submit();
	}

}

function processReqChange() {
    // only if req shows "loaded"
    if (req.readyState == 4) {
        // only if "OK"
        if (req.status == 200) {
           	if(req.responseText == "1")
           	{
           		document.followup.submit();
           	} 
           	else
           	{
           		alert(req.responseText);
           	}
        } else {
            alert("There was a problem retrieving the XML data:\n" +
                req.statusText);
        }
    }
}

function PHPValidateDate(intdate)
{
	req = false;
    // branch for native XMLHttpRequest object
    if(window.XMLHttpRequest) {
    	try {
			req = new XMLHttpRequest();
        } catch(e) {
			req = false;
        }
    // branch for IE/Windows ActiveX version
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
		req.onreadystatechange = processReqChange;
		var url = "validate_calendar.php?type=<?php echo $type; ?>&validate=date&datestr=" + intdate;
		req.open("GET", url, true);
		req.send("");
	}    
	

}
</script>
</head>
<body class="bg" onload="self.focus();">
<form method="post" action="/" class="no_padding" name="followup">
<table>
 <tr>
  <td class="align_left">Callback: </td>
  <td class="align_left">
   <select name="interval" id="interval" onchange="CheckValue(this);">
<?php echo $opts; ?>
   </select>
   </td>
  </tr>
  <tr id="date_span">
   <td><span style="display: inline;">
     Follow Up Date:
   </td><td class="align_left">
     <input type="text" id="follow_up_date" name="follow_up_date" value="" readonly> <a href="#" onClick="PopCalendar('follow_up_date', (event.clientX-130), event.clientY, null, false);">(select)</a>&nbsp;</span></td>
  </tr>
  <tr><td class="align_left">Comment: </td>
      <td class="align_left"><input type="text" id="frm_comment" name="comment" size="40"></td>      
  <tr>
  <td colspan="2" class="align_right">
   <input type="button" value="OK" class="button" onClick="javascript: CheckInterval();">
  	<input type="button" value="Cancel" class="button" onClick="javascript:self.close()"> 
	</td>
  </tr>
</table>
<input type="hidden" name="action" value="add_follow_up">
<input type="hidden" name="mode" value="<?php echo strtolower($_REQUEST['type']); ?>">
<input type="hidden" name="application_id" value="<?php echo $_REQUEST['application_id']; ?>">
</form>
</body>
</html>
