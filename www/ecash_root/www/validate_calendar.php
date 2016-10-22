<?php
// Used for Date validated (Uses Javascript XMLHTTPRequest aka ajax)
// Ray Lopez
require_once("config.php");

require_once(COMMON_LIB_DIR . "pay_date_calc.3.php");

require_once(LIB_DIR . "common_functions.php");
require_once(SQL_LIB_DIR . "util.func.php");


function AJAXDateCheck($checkts)
{
	// Connect to the database
	$holiday_ary 	= Fetch_Holiday_List();	
	$error = false;
	if(isset($_REQUEST["includeHolidays"]) && ($_REQUEST["includeHolidays"] != "yes"))
	{
		if(in_array(date("w",$checkts),array(0,6)))
		{
			$error = "WEEKEND";
		}
		else
		{
			$format_date = date("Y-m-d",$checkts);
		
			if(in_array($format_date,$holiday_ary))
			{
				$error = "HOLIDAY";
			}
		}
	}	
	return $error;	
}

function AJAXMonthlyPayDateGen($numdates,$start_date)
{
	$pdc = new Pay_Date_Calc_3(Fetch_Holiday_List());
	
	$info = new stdClass();	
	$info->direct_deposit = false;
	$info->day_int_one = date("j",strtotime($start_date));
	$info->paydate_model = "dm";
	
	$dates = $pdc->Calculate_Pay_Dates("dm",$info,TRUE,$numdates,$start_date);
	for($i=0; $i<count($dates); $i++)
		$dates[$i] = date("m/d/Y",strtotime($dates[$i]));

	return $dates;

}

/*
 Start AJAX Validation
*/
if(isset($_REQUEST["validate"]) && ($_REQUEST["validate"] == "date"))
{
	$error = false;
	$checkts = strtotime($_REQUEST["datestr"]);	
	$error = AJAXDateCheck($checkts);
	switch ($error)
	{
		CASE "WEEKEND":
			$error = "Selected date is on a weekend.";
		break;
		
		CASE "HOLIDAY":
			$error = "Selected date is on a holiday.";
		break;		
	}
	// If we pass validation then return 1 so that the javascript can submit the form	
	$error = ($error != false) ? $error : 1;
	print($error);
	die();
}
else if(isset($_REQUEST["generate"]) && ($_REQUEST["generate"] == "daterange"))
{
	$paydate = AJAXMonthlyPayDateGen($_REQUEST["daterange"],$_REQUEST["datestr"]);
	print(implode("<br>",$paydate));
	
}
?>
