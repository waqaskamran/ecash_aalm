<?php

$bg = 'class=';

switch (EXECUTION_MODE)
{
	case 'LIVE':
		$bg .= '"bg_live"';
		break;
	case 'RC':
		$bg .= '"bg_rc"';
		break;
	case 'QA':
	case 'QA_MANUAL':
	case 'QA_SEMI_AUTOMATED':
	case 'QA_AUTOMATED':
		$bg .= '"bg_qa"';
		break;
	case 'LOCAL':
	default:
		$bg .= '"bg_local"';
		break;
}

if (isset($this->BodyTags))
{
	$bg .= ' ' . $this->BodyTags;
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?php echo TITLE, " - " , $this->ECash->getAgent()->Model->login , " - " , $this->ECash->getCompany()->Model->name_short; ?>
</title>
<link rel="stylesheet" href="css/style.css">
<?php
echo $this->HeaderHtml;

if (isset($_SESSION['disable_queues']))
{
	echo '<script type="text/javascript">var disable_queues = ' . json_encode($_SESSION['disable_queues']) . ';</script>' . "\n";
}

?>
</head>
<body <?php echo $bg; ?>>
<?php
if (!empty($this->HotKeys))
{
	echo $this->HotKeys;
}
elseif (!isset($this->Content->ModuleName))
{
	echo "<script type=\"text/javascript\" src=\"get_js.php?override=no_module_hotkeys&version=" . ECASH_VERSION_FULL . "\"></script>";
}
?>

<div id="overDiv" style="position:absolute; visibility:hidden; z-index: 1000;"></div>
<div id="tooltip"></div>
<center>
<div id="iface_wrapper">
	<!-- Top Nav Bar -->
	<div id="top_nav_bar">

<?php
 //var_dump($this->ECash->getAcl());

	$translation = array("Admin" => "Admin", "Funding" => "Funding", "Fraud" => "Fraud", "Watch" => "Watch", "New App" => "New App", "Reporting" => "Reports", "Collections" => "Collections", "Loan Servicing" => "Servicing");
	foreach ($this->ECash->getAcl()->Get_Acl_Names($this->ECash->getAcl()->Get_Acl_Access()) as $menu_item)
	{
		$show_name = ECash::getTransport()->acl->Get_Acl_Name($menu_item);
		$menu_item = strtolower($menu_item);
		
		$selected =  ($this->ECash->getModule()->active_module == $menu_item)
			? '_selected'
			: '';
		
		if ($menu_item == 'My Applications' || $menu_item == 'myapps')
		{
			// This suppresses the output of the my applications item
			/*
			$count = 0;
			if (isset($_SESSION["agent_id"]))
			{
				$count = ECash::getAgent()->getQueue()->count();
			}
			echo "<a href=\"/?module=collections&mode=internal&action=personal_queue_pull\"><span class=\"top_menu_item{$selected}\">My Queue $count</span></a>\n";
			*/
		}
		elseif($menu_item == 'new_application')
		{
			$url = ECash::getTransport()->new_app_url;
			echo "<a href=\"\" onClick=\"window.open('{$url}');\" id=\"AppTopMenuLink" . str_replace(" ","",$show_name) . "\">";
			echo "<span class=\"top_menu_item{$selected}\" id=\"AppTopMenuSpan" . str_replace(" ","",$show_name) . "\"> {$show_name} </span>";
			echo "</a>\n";
		}
		elseif ($menu_item == 'call_accounting' || $menu_item == 'Call Accounting')
		{
			echo "";
		}
		else
		{
			echo "<a href=\"/?module={$menu_item}&amp;mode=\" id=\"AppTopMenuLink" . str_replace(" ","",$show_name) . "\"><span class=\"top_menu_item{$selected}\" id=\"AppTopMenuSpan" . str_replace(" ","",$show_name) . "\"> {$show_name} </span></a>\n";
		}
	}
	
?>
<a href="/?logout=true" id="AppLogoutLink"> <span id="AppLogoutSpan" onMouseOver="tooltip(event, 'Logout the current Agent.', 30, 20);" onMouseOut="tooltip(null);">Log Out</span></a><div id="agent_name"><?php echo strtoupper(ECash::getAgent()->getFirstLastName()); ?></div><div style="clear: both;"></div></div><!-- Module Menu & Module Display -->
<?php
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
}
?>
<?php
	//no summary bar (yet)
	echo $this->ModuleMenu;
	echo $this->Content->render();
?>

</center>
</body>
</html>
