<?php
require_once ("config.php");
require_once(SQL_LIB_DIR . "scheduling.func.php");
$bgcolor = NULL;
$loan_action_history_id = $_GET["loan_action_history_id"];
$agent = $_GET["agent"];
$date_time = $_GET["date_time"];

$lah_model = ECash::getFactory()->getModel('LoanActionHistory');
$lah_model->loadBy(array('loan_action_history_id'=>$loan_action_history_id,));
$application_id = $lah_model->application_id;
$loan_action_id = $lah_model->loan_action_id;
$loan_action_flag = $lah_model->is_resolved;

$la_model = ECash::getFactory()->getModel('LoanActions');
$la_model->loadBy(array('loan_action_id'=>$loan_action_id,));
$loan_action = $la_model->description;

$button_text = ($loan_action_flag) ? "Unresolve": "Resolve";
$loan_action_flag_display = ($loan_action_flag) ? "Y": "N";
?>

<html>
<head>
<link rel="stylesheet" href="css/style.css">
<script type="text/javascript" src="js/layout.js"></script>
<script>
function CheckLoanActionFlag()
{
	document.loan_actions_info.submit();
}
</script>
<title>Loan Actions Info</title>
</head>

<body class="bg" onload="self.focus();">
<form method="post" action="/" name="loan_actions_info" class="no_padding">
<table>

<tr class="height"></tr>

<tr>
<td class="align_left"><label for="agent"><b>Agent:</b></label></td>
<td class="align_left"><?php echo $agent; ?></td>
</tr>

<tr>
<td class="align_left"><label for="date_time"><b>Date/Time:</b></label></td>
<td class="align_left"><?php echo $date_time; ?></td>
</tr>

<tr>
<td class="align_left"><label for="loan_action"><b>Loan Action:</b></label></td>
<td class="align_left"><?php echo $loan_action; ?></td>
</tr>

<tr>
<td class="align_left"><label for="loan_action_flag"><b>Resolved?</b>:</label></td>
<td class="align_left"><?php echo $loan_action_flag_display; ?></td>
</tr>

<tr class="height"></tr>
</table>

<table>
<tr class="height">
<td>
<input type="hidden" name="action" value="change_loan_action_flag">
<input type="hidden" name="loan_action_history_id" value="<?php echo $loan_action_history_id; ?>">
<input type="submit" name="submit" value="<?php echo $button_text; ?>" class="button" onSubmit="javascript:return CheckLoanActionFlag();">
<input type="button" name="cancel" value="Cancel" onClick="javascript:self.close();" class="button">
</td>
</tr>
</table>

</form>
</body>
</html>
