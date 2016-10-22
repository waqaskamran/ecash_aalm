<?php
// this file is a multi-mode php/html/javascript document.
// if it get's as ugly as pizza's paydate wizard, I'll rewrite it.
// Sorry -- Justin Foell
$type = $_GET["type"];
$dd = "";
switch($type) {
	case "Deny": {
		$dd = "Denial Letter";
		$opts = <<<EOS
     <option>All phone numbers are disconnected</option>
     <option>Customer is unemployed</option>
     <option>Same name/Different social</option>
     <option>Customer does not have direct deposit</option>
     <option>Does not have a checking account</option>
     <option>3 or more people on checking account</option>
     <!--  <option>Bad Loans on CLV report</option> -->
     <!--     <option>Low CLV Score</option> -->
     <option>Identity Failure</option>
     <!--     <option>CL Verify</option> -->
     <option>Teletrack</option>
     <option>Dupe bank info</option>
EOS;
} break;
case "Withdraw": {
	$dd = "Withdrawn Application";
	$opts = <<<EOS
     <option>No Documents sent/Cannot verify employment</option>
     <option>Loan Note Bad/Did not send new</option>
     <option>Customer lives in GA or KS</option>
     <option>Work # is a cell phone/Unable to Verify Employment</option>
     <option>Work # is a fax machine/Unable to Verify Employment</option>
     <option>Work # is busy/Unable to Verify Employment</option>
     <option>Work # just rings/Unable to Verify Employment</option>
     <option>Work # is wrong/Unable to Verify Employment</option>
     <option>Necessary documents not received</option>
     <option>Customer requesting to cancel</option>
     <option>Documents not sent within 3-5 days</option>
     <option>Unable to contact</option>
     <option>Identity Verification Issues</option>
EOS;
} break;
case "Reverify": {
	$opts = <<<EOS
     <option>Customer does not qualify for the loan amount</option>
     <option>Due date does not fall on a payday</option>
EOS;
} break;
}

if (($type == "Deny") || ($type == "Withdraw")) {
	$chgHandler = "onChange=\"javascript:Set_Document();\"";
	$doclist = <<<EOS
    <tr>
    <td><input type="checkbox" id="document_list" name="document_list" checked></td>
    <td class="align_left" colspan="2">Send Docs</td>
    </tr>
EOS;
} else {
	$chgHandler = "";
	$doclist = "";
}

?>

<html>
<head>
<link rel="stylesheet" href="css/style.css">
<title>Comment</title>
<script>
var default_document = "<?php echo $dd; ?>";

function Swap_Visible(select_visible)
{
	document.getElementById("select").disabled = !select_visible;
	document.getElementById("change_status_comment").disabled = select_visible;
	document.getElementById("text").style.visibility = ((select_visible) ? "hidden" : "visible");
	<?php if($type == "Deny" || $type == "Withdraw") { ?>
	if(select_visible)
	{
		Set_Document();
	}
	else
	{
		document.getElementById("document_list").value = default_document;
	}
	<?php } ?>
}

function Set_Document()
{
	var selected = document.getElementById("select").value;
	switch(selected)
	{
		case "All phone numbers are disconnected":
		case "Customer is unemployed":
		case "Same name/Different social":
		case "Customer does not have direct deposit":
		case "Does not have a checking account":
		case "3 or more people on checking account":
		case "Dupe bank info":
		// - DENIAL LETTER
		document.getElementById("document_list").value = "Denial Letter";
		break;
		<?php
		// 	case "Bad Loans on CLV report":
		// 		case "Low CLV Score":
		// 		case "CL Verify":
		// 			// - CLVERIFY DENIAL
		// 			document.getElementById("document_list").value = "CLVerify Denial";
		// 		   	break;
		?>
		case "Teletrack":
		// - TELETRACK DENIAL
		document.getElementById("document_list").value = "Teletrack Denial Letter";
		break;

		case "Identity Failure":
		case "Identity Verification Issues":
		// - CLVERIFY IDENTITY LETTER
		document.getElementById("document_list").value = "Identity Letter";
		break;

		//Withdraw Options
		case "No Documents sent/Cannot verify employment":
		case "Loan Note Bad/Did not send new":
		case "Customer lives in GA or KS":
		case "Work # is a cell phone/Unable to Verify Employment":
		case "Work # is a fax machine/Unable to Verify Employment":
		case "Work # is busy/Unable to Verify Employment":
		case "Work # just rings/Unable to Verify Employment":
		case "Work # is wrong/Unable to Verify Employment":
		case "Necessary documents not received":
		case "Customer requesting to cancel":
		case "Documents not sent within 3-5 days":
		case "Unable to contact":
		document.getElementById("document_list").value = "Withdrawn Application";
		break;

		default:
		document.getElementById("document_list").value = default_document;
		break;
	}

}

</script>
</head>
<body class="bg" onload="self.focus();">
<form method="post" action="/" name="Deny Comment" class="no_padding">
<table>
<tr>
<td><input type="radio" name="choice" onClick="javascript:Swap_Visible(true);" checked></td>
<td colspan="2">
<select name="comment" id="select" <?php echo $chgHandler; ?>>
     <option value="">(No Comment)</option>
<?php echo $opts; ?>
</select>
</td>
</tr>
<tr>
	<td><input type="radio" name="choice" onClick="javascript:Swap_Visible(false);"></td>
	<td class="align_left">Other:</td><td><div id="text" style="visibility: hidden;"><input type="text" name="comment" id="change_status_comment" disabled></div></td>
</tr>
<?php echo $doclist; ?>
</table>
<input type="hidden" name="action" value="change_status">
<input type="hidden" name="application_id" value="<?php echo $_GET['application_id']; ?>">
<input type="hidden" name="customer_email" value="<?php if(!empty($_GET['customer_email'])) echo $_GET['customer_email']; ?>">
<input type="hidden" name="submit_button" value="<?php echo $_GET['type']; ?>">
<input type="submit" name="submit" value="Save" class="button">
<input type="button" name="cancel" value="Cancel" onClick="javascript:self.close();" class="button">
</form>
</body>
</html>