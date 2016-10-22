<?php
require_once(CLIENT_CODE_DIR . "display_utility.class.php");

# cache buster
header("Expires: Mon, 01 Jan 1997 01:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

$bg = 'class=';

switch(EXECUTION_MODE) {
	case 'LIVE': $bg .= '"bg_live"'; break;
	case 'RC':  $bg .= '"bg_rc"'; break;
	case 'QA':
	case 'QA_MANUAL':
	case 'QA_SEMI_AUTOMATED':
	case 'QA_AUTOMATED':
		$bg .= '"bg_qa"'; break;
	case 'LOCAL':
	default: $bg .= '"bg_local"'; break;
}

if (isset($body_tags)) {
	$bg .= " " . $body_tags;
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?php echo TITLE; ?><?php echo " - " . ECash::getTransport()->login . " - " . ECash::getTransport()->company; ?>
<?php
if(!empty($_SESSION['faketime']))
{
	echo " - FAKETIME: ", date(DATE_ATOM);
}
?>
</title>
<link rel="stylesheet" href="css/style.css">
<?php
if (isset($header)) echo $header;
?>
</head>
<?php

if($module_name == "reporting" && ECash::getTransport()->page_array[2] != "default") 
{
	echo "<body onload=\"document.getElementById('loading').style.visibility = 'hidden';\">";
	echo "<div id=\"loading\"><div id=\"loading-image\">Loading</div></div>";

	// We dont want to display anything if all we are doing is clearing the session
	// for the reports
	if(empty($_REQUEST["clear_session"]))
	{
		print($module_html);
	}
}
else 
{
$loadTabs = ($module_name == "reporting" && ECash::getTransport()->page_array[2] == "default") ? "onLoad='LoadReportTabs();'" : NULL;
echo "<body {$bg} {$loadTabs}>";

if(isset($hotkeys)) { echo $hotkeys; } 
elseif(!isset($module_name)) { 
   include_once(WWW_DIR . "include_js.php");
   echo include_js(Array('no_module_hotkeys')); 
}
?>
<div id="overDiv" style="position:absolute; visibility:hidden; z-index: 1000;"></div>
<div id="tooltip"></div>
<center>
<div id="iface_wrapper">
	<!-- Top Nav Bar -->
	<div id="top_nav_bar">

<?php

foreach (ECash::getTransport()->user_acl as $menu_item)
{
    $show_name = ECash::getTransport()->acl->Get_Acl_Name($menu_item);
    $menu_item = strtolower($menu_item);
    

	if(isset(ECash::getTransport()->page_array[1]) && ECash::getTransport()->page_array[1] == $menu_item)
	{
		$sel_indicator = "_selected";
	}
	else
	{
		$sel_indicator = "";
	}

	if($menu_item == 'myapps')
	{
		// This suppresses the output of the my applications item
	}
	elseif($menu_item == 'new_application')
	{
			$url = ECash::getTransport()->new_app_url;
			echo "<a href=\"\" onClick=\"window.open('{$url}');\" id=\"AppTopMenuLink" . str_replace(" ","",$show_name) . "\">";
			echo "<span class=\"top_menu_item{$sel_indicator}\" id=\"AppTopMenuSpan" . str_replace(" ","",$show_name) . "\"> {$show_name} </span>";
			echo "</a>\n";
	}
	else
	{
		echo "<a href=\"/?module={$menu_item}&amp;mode=\" id=\"AppTopMenuLink" . str_replace(" ","",$show_name) . "\">";
		echo "<span class=\"top_menu_item{$sel_indicator}\" id=\"AppTopMenuSpan" . str_replace(" ","",$show_name) . "\"> {$show_name} </span>";
		echo "</a>\n";
	}
}

// Logout Button (reverse order because they are float: right)
echo "<a href=\"/?logout=true\" id=\"AppLogoutLink\">";
echo " <span id=\"AppLogoutSpan\" onMouseOver=\"tooltip(event, 'Logout the current Agent.', 30, 20);\" onMouseOut=\"tooltip(null);\">Log Out</span>";
echo "</a>";


/**
 * Multi-Company selection drop-down if Multi-Company is enabled.
 */
$multi_company = ECash::getConfig()->MULTI_COMPANY_ENABLED;
if($multi_company === TRUE)
{
	$use_archive = ECash::getConfig()->MULTI_COMPANY_INCLUDE_ARCHIVE;
	
	echo "<div id=\"company_selector\">";
	echo "<form id=\"change_company_form\" method=\"post\" action=\"/\" class=\"no_padding\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"switch_company\">";
	// Company Selector
	echo "<select name=\"new_company_id\" id=\"AppChangeCompany\" onChange=\"changeCompany();\">";
	foreach(ECash::getTransport()->company_list as $id => $company)
	{
		// Disable archive companies if they're not enabled.
		if($id > 100 & $use_archive !== TRUE) continue;
		if($company['agent_allowed'] === FALSE) continue;
		
		$short_upper = strtoupper($company['name_short']);

		if(ECash::getCompany()->name_short === $company['name_short'])
		{
			echo "<option value=\"{$id}\" SELECTED>{$short_upper}</option>\n";
		}
		else
		{
			echo "<option value=\"{$id}\">{$short_upper}</option>\n";
		}
	}
	echo "</select>";
	echo "</form>";
	echo "</div>";
} else {
	echo '<div id="company_name" class="'.strtolower(ECash::getCompany()->name_short).'_company">'.strtoupper(ECash::getCompany()->name_short)."</div>";
}
echo '<div id="agent_name">'.strtoupper(ECash::getAgent()->getFirstLastName())."</div>";
echo "<div style=\"clear: both;\"></div>";
echo "</div>";




$any_values = FALSE;
if(ECash::getConfig()->SHOW_SUMMARY_BAR !== FALSE)
{

	$data = ECash::getTransport()->Get_Data();

	$application_id = 'N/A';
	if (isset($data->application_id))
	{
		$application_id = $data->application_id;
		$any_values = TRUE;
	}	

	$company = 'N/A';
	if (isset($data->display_short))
	{
		$company = $data->display_short;
		$any_values = TRUE;
	}

	$name = 'N/A';
	if (isset($data->name_first) && isset($data->name_last))
	{
		$name = $data->name_first . '&nbsp;' . $data->name_last;
		$any_values = TRUE;
	}

	//mantis:4416
	$ssn = 'N/A';
	if (isset($data->ssn))
	{
		if (in_array("ssn_last_four_digits", $data->read_only_fields))
			$ssn = 'XXX-XX-' . substr($data->ssn, 7);
		else
			$ssn = $data->ssn;

		$any_values = TRUE;
	}

	$next_amount_due = 'N/A';
	if (isset($data->schedule_status) && isset($data->schedule_status->next_amt_due))
	{
		$next_amount_due = number_format($data->schedule_status->next_amt_due, 2, '.', '');
		$any_values = TRUE;
	}

	$next_due_date = 'N/A';
	$num_days_due = 'N/A';
	if (isset($data->schedule_status) && (isset($data->schedule_status->next_due_date) && $data->schedule_status->next_due_date != 'N/A'))
	{
		$next_due_date = $data->schedule_status->next_due_date;	
		$due_array = explode('-',$next_due_date);
		$num_days_due = floor((mktime(idate('H'), idate('i'), idate('s'),$due_array[0], $due_array[1], $due_array[2]) - time()) / (60*60*24));
		$any_values = TRUE;
	}

	$next_action_date = 'N/A';
	if (isset($data->schedule_status) && isset($data->schedule_status->next_action_date))
	{
		$next_action_date = $data->schedule_status->next_action_date;	
		$any_values = TRUE;
	}

	$posted_balance = 'N/A';
	if (isset($data->schedule_status) && isset($data->schedule_status->posted_total))
	{
		$posted_balance = number_format($data->schedule_status->posted_total,2,'.','');	
		$any_values = TRUE;
	}

	//mantis:4105
	$posted_and_pending_total = 'N/A';
	if (isset($data->schedule_status) && isset($data->schedule_status->posted_and_pending_total))
	{	
		$posted_and_pending_total = number_format($data->schedule_status->posted_and_pending_total,2,'.','');	
		$any_values = TRUE;
	}
	
	if (!empty($data->delinquency_date))
	{
		$next_due_date = $data->delinquency_date;
		$due_array = explode('-',$next_due_date);
		$num_days = (mktime(idate('H'), idate('i'), idate('s'),$due_array[1], $due_array[2], $due_array[0]) - time()) / (60*60*24);
		//Formatting this number so that it is always a whole number [#15930]
		$num_days_due = number_format($num_days,0,'.','');
	}
	
	if ($any_values)
	{
		//mantis:4648
		if(ECash::getTransport()->Get_Data()->do_not_loan)
		{
			$bar_type = "_dnl";
		}
		else if(!(ECash::getTransport()->Get_Data()->is_dnl_set_for_company) &&
		ECash::getTransport()->Get_Data()->is_dnl_set_for_other &&
		ECash::getTransport()->Get_Data()->is_override_dnl_set
		)
		{
			$bar_type = "_dnl_override";
		}
		else
		{
			$bar_type = "";
		}
		$info_bar = "<div id=\"app_info_bar{$bar_type}\">";
		$info_bar .= "<div class=\"info_item\">App ID:&nbsp;<span class=\"value\">{$application_id} </span></div>";
		$info_bar .= "<div class=\"info_item\">Co:&nbsp;<span class=\"value\">{$company}</span></div>";
		$info_bar .= "<div class=\"info_item\">Name:&nbsp;<span class=\"value\">{$name}</span></div>";
		$info_bar .= "<div class=\"info_item\">SSN:&nbsp;<span class=\"value\">{$ssn}</span></div>";
		$info_bar .= "<div class=\"info_item\">Amt Due:&nbsp;<span class=\"value\">\${$next_amount_due}</span></div>";
		$info_bar .= "<div class=\"info_item\">Due Date:&nbsp;<span class=\"value\">{$next_due_date}</span></div>";
		$info_bar .= "<div class=\"info_item\">Days Until Due:&nbsp;<span class=\"value\">{$num_days_due}</span></div>";
		$info_bar .= "<div class=\"info_item\">Action Date:&nbsp;<span class=\"value\">{$next_action_date}</span></div>";
		if(ECASH_EXEC_MODE != 'LIVE')
		{
			$application = ECash::getApplicationByID($application_id);
			$info_bar .= "<div class=\"info_item\">Application Version:&nbsp;<span class=\"value\">{$application->getApplicationVersion()}</span></div>";
		}
		//mantis:4105
		
		if(isset($data->schedule_status) && isset($data->interest_accrued) && $data->interest_accrued <> 0
			&& ($data->schedule_status->pending_total <> 0))
		{
			$balance_if_paid = $posted_and_pending_total;

			// Interest accrued is the total interest accrued till the next business day, so I'm 
			// taking the posted total and deducting the posted fees, then adding the interest 
			// accrued to get the total balance if not paid. [BR]
			$balance_if_not_paid = $data->schedule_status->posted_total - $data->schedule_status->posted_fees;
			$balance_if_not_paid = $balance_if_not_paid + abs($data->interest_accrued);
			$balance_if_not_paid = number_format($balance_if_not_paid,2,'.','');	
			
			$info_bar .= "<div class=\"info_item\">Bal if not Paid:<span class=\"value\">\${$balance_if_not_paid}</span></div>";
			$info_bar .= "<div class=\"info_item\">Bal if Paid:<span class=\"value\">\${$balance_if_paid}</span></div>";
		}
		else if(isset($data->schedule_status) && isset($data->schedule_status->pending_total) && $data->schedule_status->pending_total < 0)
		{
			$info_bar .= "<div class=\"info_item\">Bal if not Paid:<span class=\"value\">\${$posted_balance}</span></div>";
			$info_bar .= "<div class=\"info_item\">Bal if Paid:<span class=\"value\">\${$posted_and_pending_total}</span></div>";
		}
		else if (isset($data->interest_accrued) && $data->interest_accrued != 0)
		{
			//$info_bar .= "<div class=\"info_item\">Bal:&nbsp;<span class=\"value\">\${$posted_balance}</span></div>";

			//$balance_if_not_paid = $data->schedule_status->posted_total - $data->schedule_status->paid_fees;
			$balance_if_not_paid = $data->schedule_status->posted_total;			
			$balance_if_not_paid = $balance_if_not_paid + abs($data->interest_accrued);
			$balance_if_not_paid = number_format($balance_if_not_paid,2,'.','');
			
			$info_bar .= "<div class=\"info_item\">Payoff Balance:&nbsp;<span class=\"value\">\${$balance_if_not_paid}</span></div>";
		}
		else
		{
			$info_bar .= "<div class=\"info_item\">Bal:&nbsp;<span class=\"value\">\${$posted_balance}</span></div>";
		}
		
		if(isset($data->daily_interest_amount))
		{
			$info_bar .= "<div class=\"info_item\">Daily Interest Amt:&nbsp;<span class=\"value\">\${$data->daily_interest_amount}</span></div>";
		}
		
		$info_bar .= "<div class=\"clearer\"></div>";
		$info_bar .= "</div>";
	}
}
else if(!$any_values)
{
	echo "<div id=\"app_info_bar\"><div style=\"clear: both;\"></div></div>";
}

?>
<!-- Module Menu & Module Display -->
<?php

// Token replacing the app info bar into the module html
if (isset($module_html)) $module_html = Display_Utility::Token_Replace($module_html, array('app_info_bar' => isset($info_bar) ? $info_bar : null));

				if(isset($module_menu_html))
				{
					//echo "<tr class=\"clear\"><td>{$module_menu_html}</td></tr>";
					echo "<div id=\"module_menu\">{$module_menu_html}</div>";
				}

				if(isset($module_html))
				{
					echo "<div id=\"module_body\">{$module_html}";
					echo "<div style=\"clear: both;\"></div>";
					echo "</div>";
				}
?>

</center>
<?php
}
?>
	</body>
</html>
