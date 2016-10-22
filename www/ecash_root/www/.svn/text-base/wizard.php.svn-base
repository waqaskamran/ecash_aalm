<html>
<head>
<link rel="stylesheet" href="css/style.css">
<title>Paydate Wizard</title>
<script language=javascript>
            function clear_radio (name_fragment)
            {
                    button_name = 'paydate['+name_fragment+']';
                    radio_buttons = document.getElementsByName(button_name);
                    num_buttons = radio_buttons.length;
                    if (num_buttons > 0)
                    {
                            for (i=0; i<num_buttons; i++)
                            {
                                    radio_buttons[i].checked = false;
                            }
                    }
            }
         
            function validate_paydate()
            {
            	if (document.getElementById('how_often').value == "")
            		return false;
            		
            	if (document.getElementById('how_often').value == "WEEKLY" && document.getElementById('paydate[weekly_day]').value == "" )
            		return false;
            		
            	if (document.getElementById('how_often').value == "BI_WEEKLY" && document.getElementById('biweekly_twice_day').value == "" )
            		return false;
            		
            	if (document.getElementById('how_often').value == "BI_WEEKLY" && document.getElementById('biweekly_twice_day').value != "")
            	{
            			radio_buttons = document.getElementsByName('paydate[biweekly_date]');
            			num_buttons = radio_buttons.length;
	                    if (num_buttons > 0)
	                    {
	                            for (i=0; i<num_buttons; i++)
	                            {
	                                    if(radio_buttons[i].checked)
	                                    	return true;
	                            }
	                    }
            		
            			return false;
            	
            	}
            	
            
            		
            	if (document.getElementById('how_often').value == "TWICE_MONTHLY")
            	{
					if (!document.getElementsByName('paydate[twicemonthly_type]')[1].checked && !document.getElementsByName('paydate[twicemonthly_type]')[2].checked)
            			return false;
            		
            		if (document.getElementsByName('paydate[twicemonthly_type]')[1].checked)
            		{
            			if (document.getElementById('paydate[twicemonthly_date1]').value == "" || document.getElementById('paydate[twicemonthly_date2]').value == "")
            				return false;
            			            		
            		
            		}
            		
            		if (document.getElementsByName('paydate[twicemonthly_type]')[2].checked)
            		{
            			if (document.getElementById('paydate[twicemonthly_week]').value == "" || document.getElementById('paydate[twicemonthly_day]').value == "")
            				return false;
            			            		
            		
            		}
            		
            			
            			            		
            	}
 	
            	if (document.getElementById('how_often').value == "MONTHLY")
            	{
            		if (!document.getElementsByName('paydate[monthly_type]')[0].checked && !document.getElementsByName('paydate[monthly_type]')[1].checked && !document.getElementsByName('paydate[monthly_type]')[2].checked)
            			return false;
            			
            		if (document.getElementsByName('paydate[monthly_type]')[0].checked)
            		{
            			if (document.getElementById('paydate[monthly_date]').value == "")
            				return false;        		
            		
            		}
            		
            		if (document.getElementsByName('paydate[monthly_type]')[1].checked)
            		{
            			if (document.getElementById('paydate[monthly_week]').value == "" || document.getElementById('paydate[monthly_day]').value == "")
            				return false;        		
            		
            		}
            		
             		if (document.getElementsByName('paydate[monthly_type]')[2].checked)
            		{
            			if (document.getElementById('paydate[monthly_after_day]').value == "" || document.getElementById('paydate[monthly_after_date]').value == "")
            				return false;        		
            		
            		}
            			
            			
            	
            	
            	
            	}           	
            	
            
            	return true;
            
            
            }
            
		function setHeight()
		{
		
		var docHeight=100;
		var docWidth=100;
		 if (document.documentElement && document.documentElement.scrollHeight)
			{
				docWidth = document.scrollWidth;
				docHeight= document.documentElement.scrollHeight + 10;
			}
			if (document.body)
			{
				docWidth= document.body.scrollWidth;
				docHeight = document.body.scrollHeight + 10;
			}
		
		self.resizeTo((docWidth),(docHeight));
		 setTimeout("setHeight();",500);
		}
		</script>
</head>
<!-- NOTE: This does not work as intended -->
<!-- <body class="bg" onload="self.focus();setHeight();"> -->
<body class="bg" onload="self.focus()">
<form method="post" action="/" class="no_padding">
<table width="450"><tr><td class="align_left" valign="top" height="310">
<?php
require_once("config.php");
//require_once(LIB_DIR . "/Config.class.php");

# build querystring
$qs = array();
if (isset($_GET['paydate']))
{
	// Check the data format to work with the widget
	if( isset($_GET['paydate']['biweekly_date']) )
	{
		$temp  = explode( " ", $_GET['paydate']['biweekly_date'] );
		$date  = explode( "-", $temp[0] );
		$stamp = mktime( 0, 0, 0, $date[1], $date[2], $date[0] );

		// Forward the date in the database to either this week or last week
		while( strtotime(date("d-M-Y", $stamp)) < strtotime("-2 weeks") )
		{
			$stamp = strtotime( "+2 weeks", $stamp );
		}

		$_GET['paydate']['biweekly_date'] = date( "m/d/Y", $stamp );
	}
	// Some should be upper case, some lower...
	isset($_GET['paydate']['biweekly_day'])      && $_GET['paydate']['biweekly_day']      = strtoupper($_GET['paydate']['biweekly_day']);
	isset($_GET['paydate']['twicemonthly_type']) && $_GET['paydate']['twicemonthly_type'] = strtolower($_GET['paydate']['twicemonthly_type']);
	isset($_GET['paydate']['monthly_type'])      && $_GET['paydate']['monthly_type']      = strtolower($_GET['paydate']['monthly_type']);

	foreach( $_GET['paydate'] as $k => $v )
	{
		$qs[] = urlencode("paydate[" . $k . "]") . "=" . urlencode($v);
	}
}

$url_paydate_widget = ECash::getConfig()->URL_PAYDATE_WIDGET;
echo file_get_contents($url_paydate_widget . "?" . join("&", $qs));

?>
</td></tr>
<tr><td>
<input type="hidden" name="action" value="save_wizard">
<input type="hidden" name="application_id" value="<?php echo $_REQUEST['application_id']; ?>">
<input type="submit" name="submit" value="Save" class="button" onclick="return validate_paydate();">
<input type="button" name="cancel" value="Cancel" onClick="javascript:self.close();" class="button">
</td></tr></table>
</form>
</body>
</html>
