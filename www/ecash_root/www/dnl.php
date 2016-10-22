<?php
require_once("config.php");
require_once(SQL_LIB_DIR . "do_not_loan.class.php");

$db = ECash::getMasterDb();

$dnl = new Do_Not_Loan($db);
$categories = $dnl->Get_Category_Info();

$str1 = "<option value=\"";
$str2 = "\">";
$str3 = "</option>";
?>

<html>
<head>
<link rel="stylesheet" href="css/style.css">
<script type="text/javascript" src="js/layout.js"></script>
<title>Do Not Loan</title>

<script type="text/javascript">
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

<table align="center">
<tr>
	<td><img src='/image/standard/i_do_not_loan.gif'></td>
	<td colspan="3"><b>Do Not Loan</b></td>
	<td><b>SET</b></td>
</tr>
</table>

<form method="post" action="/" class="no_padding" name="do_not_loan">
<table align="center">
<tbody>
			<tr><td class="height">&nbsp;</td></tr>
     		
			<tr>
			<td class="align_left">Category:</td>
			<td class="align_left">
			<select name="dnl_category_drop_box" id="dnl_category_drop_box" onChange="javascript:Display_Other_Reason()">
			
			<?php
			foreach($categories as $value)
			{
				echo ($str1 . $value->name . $str2 . ucwords(str_replace('_', ' ', $value->name)) . $str3);
			}
			?>

			</select>
			</td>
			<td class="align_left"><input type="text" style="visibility: hidden;" id="do_not_loan_other_specify" name="do_not_loan_other_specify" size="21"></td>
			</tr>
			
			<tr><td class="height">&nbsp;</td></tr>
</tbody>
</table>

<table align="center">
<tbody>
     		<tr>
			<td class="align_left">Explanation: </td>
     		<td class="align_left"><input type="text" id="do_not_loan_explanation" name="do_not_loan_explanation" size="40"></td>    
			</tr>
			
			<tr><td class="height">&nbsp;</td></tr>
</tbody>
</table>			
 
<table align="center">
<tbody>			
			<tr>
			<td><input type="button" value="Cancel" onClick="window.close();"></td>&nbsp;&nbsp;
			<td><input type="button" value="Submit" class="button" onClick="javascript:Set_DNL(document.getElementById('dnl_category_drop_box').value);"></td>
			</tr>
</tbody>
</table>
</form>

<form id="set_do_not_loan" method="post" action="/" class="no_padding">
	<input type="hidden" name="action" value="set_do_not_loan">
	<input type="hidden" name="application_id" value="<?php echo $_REQUEST['application_id']; ?>">
	<input type="hidden" name="do_not_loan_category" id="do_not_loan_category" value="">
	<input type="hidden" name="do_not_loan_exp" id="do_not_loan_exp" value="">
	<input type="hidden" name="do_not_loan_other_reason" id="do_not_loan_other_reason" value="">
</form>

</body>
</html>
