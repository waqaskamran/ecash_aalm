<?php
require_once("config.php");
require_once(SQL_LIB_DIR . "do_not_loan.class.php");

$name = $_REQUEST['name'];
$ssn = $_REQUEST['ssn'];
$company_id = $_REQUEST['company_id'];
$ssn_wk = trim(str_replace('-', '', $ssn));

$db = ECash::getMasterDb();

$dnl = new Do_Not_Loan($db);
$current_exists = $dnl->Does_SSN_In_Table_For_Company($ssn_wk, $company_id);
$other_exists = $dnl->Does_SSN_In_Table_For_Other_Company($ssn_wk, $company_id);
$override_exists = $dnl->Does_Override_Exists_For_Company($ssn_wk, $company_id);
$dnl_info = $dnl->Get_DNL_Info($ssn_wk);

?>

<html>
<head>
<link rel="stylesheet" href="css/style.css">
<script type="text/javascript" src="js/layout.js"></script>
<title>Do Not Loan</title>

<script type="text/javascript">

function Remove_DNL()
{	
	document.getElementById('do_not_loan_action').value = "remove_do_not_loan";
	document.getElementById('do_not_loan_form').submit();
}

function Override_DNL()
{	
	document.getElementById('do_not_loan_action').value = "override_do_not_loan";
	document.getElementById('do_not_loan_form').submit();
}

function Remove_Override_DNL()
{	
	document.getElementById('do_not_loan_action').value = "remove_override_do_not_loan";
	document.getElementById('do_not_loan_form').submit();
}

function Display_Other_Reason()
{
	if(document.getElementById("dnl_category_drop_box").value == 'other')
		document.getElementById("do_not_loan_other_specify").style.visibility = "visible";
	else
		document.getElementById("do_not_loan_other_specify").style.visibility = "hidden";
}

function Set_DNL(category)
{
	if(document.getElementById("do_not_loan_other_specify").style.visibility == "visible" 
		&& !isNotWhiteSpace(document.getElementById('do_not_loan_other_specify').value))
	{
		alert("The Other (Specify) field cannot be empty.");
		return false;
	}
	
	if(!isNotWhiteSpace(document.getElementById('do_not_loan_explanation').value))
	{
		alert("The Explanation field cannot be empty.");
		return false;
	}
	document.getElementById('do_not_loan_exp').value = document.getElementById('do_not_loan_explanation').value;
	
	
	document.getElementById('do_not_loan_category').value = category;
	
	if(category == 'other')
	{
		document.getElementById('do_not_loan_other_reason').value = document.getElementById('do_not_loan_other_specify').value;
	}	
	
	document.getElementById('set_do_not_loan').submit();
}

</script>

</head>
<body class="bg" onload="self.focus();">
<?php 
if(!$current_exists)
{
	$categories = $dnl->Get_Category_Info();

	$str1 = "<option value=\"";
	$str2 = "\">";
	$str3 = "</option>";

	echo "
<table align=\"center\">
<tr>
	<td><img src='/image/standard/i_do_not_loan.gif'></td>
	<td colspan=\"3\"><b>Do Not Loan</b></td>
</tr>
</table>
<form method=\"post\" action=\"/\" class=\"no_padding\" name=\"do_not_loan\">
<table align=\"center\">
<tbody>
	<tr><td class=\"height\">&nbsp;</td></tr>
     	<tr>
	<td class=\"align_left\">Category:</td>
	<td class=\"align_left\">
	<select name=\"dnl_category_drop_box\" id=\"dnl_category_drop_box\" onChange=\"javascript:Display_Other_Reason()\">
";
	
	foreach($categories as $value)
	{
		echo ($str1 . $value->name . $str2 . ucwords(str_replace('_', ' ', $value->name)) . $str3);
	}
	
	echo "
	</select>
	</td>
	<td class=\"align_left\"><input type=\"text\" style=\"visibility: hidden;\" id=\"do_not_loan_other_specify\" name=\"do_not_loan_other_specify\" size=\"21\"></td>
	</tr>
	<tr><td class=\"height\">&nbsp;</td></tr>
</tbody>
</table>

<table align=\"center\">
<tbody>
     	<tr>
	<td class=\"align_left\">Explanation: </td>
     	<td class=\"align_left\"><input type=\"text\" id=\"do_not_loan_explanation\" name=\"do_not_loan_explanation\" size=\"40\"></td>    
	</tr>
	<tr><td class=\"height\">&nbsp;</td></tr>
</tbody>
</table>			
 
<table align=\"center\">
<tbody>			
	<tr>
	<td><input type=\"button\" value=\"Cancel\" onClick=\"window.close();\"></td>&nbsp;&nbsp;
	<td><input type=\"button\" value=\"Submit\" class=\"button\" onClick=\"javascript:Set_DNL(document.getElementById('dnl_category_drop_box').value);\"></td>
	</tr>
</tbody>
</table>
</form>
";	
}

if($current_exists || $other_exists)
{
$html_before_title_alt = " 	<tr class=\"height\">
				<td class=\"align_left_alt_bold\" width=\"30%\">&nbsp;
			";
$html_after_title_alt = " 					&nbsp;</td>
				<td class=\"align_left_alt\" width=\"5%\">&nbsp;</td>
				<td class=\"align_left_alt\" width=\"65%\">	
			";

$html_before_title = 	" 	<tr class=\"height\">
				<td class=\"align_left_bold\" width=\"30%\">&nbsp;
			";

$html_after_title = 	" 					&nbsp;</td>
				<td class=\"align_left\" width=\"5%\">&nbsp;</td>
				<td class=\"align_left\" width=\"65%\">	
			";
$html_space_alt = "	<tr class=\"height\">
			<td class=\"align_left_alt_bold\" width=\"30%\">&nbsp;&nbsp;</td>
			<td class=\"align_left_alt\" width=\"5%\">&nbsp;</td>
			<td class=\"align_left_alt\" width=\"65%\"></td></tr>
		";

$html_space = 	"	<tr class=\"height\">
			<td class=\"align_left_bold\" width=\"30%\">&nbsp;&nbsp;</td>
			<td class=\"align_left\" width=\"5%\">&nbsp;</td>
			<td class=\"align_left\" width=\"65%\"></td></tr>
		";

	echo "<table cellpadding=0 cellspacing=0 width=\"100%\">
		<tr>
		<td class=\"border\" align=\"left\" valign=\"top\">
		<table cellpadding=0 cellspacing=0 width=\"100%\">
";

	if($current_exists)
	{
		$ind = 0;
		foreach($dnl_info as $key => $value)
		{
			if($dnl_info[$key]->company_id == $company_id)
			{
				$ind = $key;
				break;
			}
		}

		$comp_id = $dnl_info[$ind]->company_id;			
		$category = $dnl_info[$ind]->name;
		$explanation = $dnl_info[$ind]->explanation;
		$agent_name = ucwords($dnl_info[$ind]->name_last . ', ' . $dnl_info[$ind]->name_first);
		$date_created = $dnl_info[$ind]->date_created;

		unset($dnl_info[$ind]);

		echo "	<tr class=\"height\" bgcolor=\"#FFEFD5\">
			<td class=\"align_left_bold\" width=\"30%\">Current</td>
			<td width=\"5%\"><nobr>&nbsp;</nobr></td>
			<td class=\"align_right\" width=\"65%\"><input type=\"button\" value=\"Remove DNL\" class=\"button\" onClick=\"javascript:Remove_DNL();\"></td>
			</tr>
		";
		echo ($html_before_title_alt . "Name on Account:" . $html_after_title_alt . $name . "</td></tr>");
		echo ($html_before_title . "SSN:" . $html_after_title . $ssn . "</td></tr>");
		echo ($html_before_title_alt . "DNL Category:" . $html_after_title_alt . ucwords(str_replace('_', ' ', $category)) . "</td></tr>");
		echo ($html_before_title . "DNL Explanation:" . $html_after_title . $explanation . "</td></tr>");
		echo ($html_before_title_alt . "Agent ID:" . $html_after_title_alt . $agent_name . "</td></tr>");
		echo ($html_before_title . "DNL Set Date:" . $html_after_title . $date_created . "</td></tr>");
		echo $html_space_alt . $html_space;
	}
					
	if($other_exists)
	{ 
		echo "	<tr class=\"height\" bgcolor=\"#FFEFD5\">
			<td class=\"align_left_bold\" width=\"30%\">Other Companies</td>
			<td width=\"5%\"><nobr>&nbsp;</nobr></td>
			<td width=\"65%\"><nobr></nobr></td>
			</tr>
		";
		foreach($dnl_info as $key => $value)
		{
			echo ($html_before_title_alt . "Company:" . $html_after_title_alt . $dnl_info[$key]->company_name . "</td></tr>");
			echo ($html_before_title . "Name on Account:" . $html_after_title . $name . "</td></tr>");
			echo ($html_before_title_alt . "DNL Category:" . $html_after_title_alt . ucwords(str_replace('_', ' ', $dnl_info[$key]->name)) . "</td></tr>");
			echo ($html_before_title . "DNL Explanation:" . $html_after_title . $dnl_info[$key]->explanation . "</td></tr>");
			echo ($html_before_title_alt . "Agent ID:" . $html_after_title_alt . ucwords($dnl_info[$key]->name_last . ', ' . $dnl_info[$key]->name_first) . "</td></tr>");
			echo ($html_before_title . "DNL Set Date:" . $html_after_title . $dnl_info[$key]->date_created . "</td></tr>");
			echo $html_space_alt . $html_space;
		}
						
		if($override_exists)
			echo "	<tr class=\"height\">
				<td class=\"align_left\"><input type=\"button\" value=\"Remove Override DNL\" class=\"button\" onClick=\"javascript:Remove_Override_DNL();\"></td>
				</tr>
			";
		else
			echo "	<tr class=\"height\">
				<td class=\"align_left\"><input type=\"button\" value=\"Override DNL\" class=\"button\" onClick=\"javascript:Override_DNL();\"></td>
				</tr>
			";
	}
	echo "</table>
		</td>
		</tr>
		</table>
	";
}
?>
	<form id="set_do_not_loan" method="post" action="/" class="no_padding">
	<input type="hidden" name="action" value="set_do_not_loan">
	<input type="hidden" name="application_id" value="<?php echo $_REQUEST['application_id']; ?>">
	<input type="hidden" name="ssn" value="<?php echo $ssn_wk; ?>">
	<input type="hidden" name="do_not_loan_category" id="do_not_loan_category" value="">
	<input type="hidden" name="do_not_loan_exp" id="do_not_loan_exp" value="">
	<input type="hidden" name="do_not_loan_other_reason" id="do_not_loan_other_reason" value="">
	</form>

	<form id="do_not_loan_form" method="post" action="/" class="no_padding">
	<input type="hidden" name="action" id="do_not_loan_action" value="">
	<input type="hidden" name="application_id" value="<?php echo $_REQUEST['application_id']; ?>">
	<input type="hidden" name="ssn_wk" value="<?php echo $ssn_wk; ?>">
	</form>
</body>
</html>
