<?php
require_once ("config.php");
require_once(SQL_LIB_DIR . "loan_actions.func.php");

$bgcolor = NULL;

// this file is a multi-mode php/html/javascript document.
// if it get's as ugly as pizza's paydate wizard, I'll rewrite it.
// Sorry -- Justin Foell
$type = $_GET["type"];
if (!empty($_GET['loan_section'])) $loan_section = $_GET["loan_section"];
else $loan_section = "";

if (!empty($_GET['mode'])) $mode = $_GET['mode'];
else $mode = '';

if(!empty($_GET['mutually_exclusive'])) $input_type = "radio";
else $input_type = "checkbox";

$dd = null;
switch($type)
{
	case "Deny":
		$dd = "Denial Letter";
		$loan_action_type = "FUND_DENIED";			
		break;
	 
	case "Withdraw": 
		if($loan_section == "CS")
		{
			$dd = "Withdrawn Application";
			$loan_action_type = "CS_WITHDRAW";
		}
		else 
		{
			$dd = "Withdrawn Application";
			$loan_action_type = "FUND_WITHDRAW";			
		}			
		break;
	 
	case "Reverify": 
		if($loan_section == "CS")
		{
			$loan_action_type = "CS_REVERIFY";
		}
		break;
	 
	case "InProcess": 
		$loan_action_type = "IN_PROCESS";
		break;

	case "Approve": 
		$loan_action_type = "FUND_APPROVE";
		break;
	 
	case "Dequeue": 
		$loan_action_type = "DEQUEUE";

	case "Release":
		$loan_action_type = $loan_section;
		break;
        
}

$loan_action_types = Get_Loan_Action_Types($loan_action_type);
$opts = "<table cellpadding=0 cellspacing=0 border=0 width=100%>";
$lastopts = "";
foreach ($loan_action_types as $item) 
{
	if($item->name_short == "specify_other") 
	{
		$js = " onChange=\"javascript:Other_Reason_Swap(true);\" ";
		$checkbox = "<input type=\"{$input_type}\" name=loan_actions[] id=loan_actions value='{$item->loan_action_id}' $js>";
		$lastopts = "<tr bgcolor='lime'><td>$checkbox</td>";
		$lastopts .= "<td style='text-align: left'><font size='-1'>{$item->description}</font></td></tr>";

	}
	else
	{
		$bgcolor = is_null($bgcolor) ? "silver" : "white";
		$checkbox = "<input type=\"{$input_type}\" name=loan_actions[] id=loan_actions value='{$item->loan_action_id}'>";
		$opts .= "<tr bgcolor='$bgcolor'><td>$checkbox</td>";
		$opts .= "<td style='text-align: left'><font size='-1'>{$item->description}</font></td></tr>\n";
		$bgcolor = ($bgcolor == "white") ? null : $bgcolor;
	}
		
}

$opts .= "$lastopts</table>";	


?>

<html>
<head>
<link rel="stylesheet" href="css/style.css">
<script>
function Other_Reason_Swap()
{
	var status = document.getElementById("change_status_comment").disabled;
	document.getElementById("change_status_comment").disabled = ((status) ? false : true);
	document.getElementById("text").style.visibility = ((status) ? "visible" : "hidden");
}

function atLeastOne()
{
  var objForm = document.forms[0];
  var el = document.getElementsByName('loan_actions[]');
  for(i=0;i<el.length;i++)
  {
    if(el[i].checked)
    {    	
       return checkOther();
    }
  }
  alert("Please check at least one reason.");
  return false;
}

function checkOther()
{
	if((!document.getElementById("change_status_comment").disabled))
	{
		if(document.getElementById("change_status_comment").value == "")
		{
			alert("Please enter 'other' reason.");	
			return false;
		}
	}
	return true;
}

</script>
</head>
<body class="bg" onload="self.focus();">
<form method="post" action="/" name="Deny Comment" class="no_padding" onSubmit="return atLeastOne()">
<table>
<tr>
<th colspan="2" class="<?php echo htmlentities($mode).' '; ?>" style="border: 2px solid gray; text-align: center;">
<b><?php echo htmlentities($type); ?></b>
</th>
</tr>
<tr>
<td rowspan="2">
<div style="width: 290px; height: 150px; overflow: auto;">
<?php echo $opts; ?>
</div>
<div id="text" style="visibility: hidden;">
Other: <input type="text" name="comment" id="change_status_comment" disabled></div>
</td>
<?php if ((($type == "Deny") || ($type == "Withdraw")) && $dd){ ?>	
<td valign=bottom>
<input type="checkbox" id="document_list" name="document_list" value="<?php echo $dd; ?>" checked>Send Docs<bR>
</td>
<?php } else { ?>
<td></td>
<?php } ?>
</tr>
<tr>
<td valign=bottom>
<input type="hidden" name="action" value="change_status">
<input type="hidden" name="application_id" value="<?php echo $_GET['application_id']; ?>">
<input type="hidden" name="customer_email" value="<?php if(!empty($_GET['customer_email'])) echo $_GET['customer_email']; ?>">
<input type="hidden" name="submit_button" value="<?php echo $_GET['type']; ?>">
<input type="submit" name="submit" value="Save" class="button" onSubmit="javascript:return CheckLoanActions();">
<input type="button" name="cancel" value="Cancel" onClick="javascript:self.close();" class="button">
</td>
</tr>

</table>

</form>
</body>
</html>
