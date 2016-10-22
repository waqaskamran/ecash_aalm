<?php
require_once ("config.php");
require_once(SQL_LIB_DIR . "scheduling.func.php");
$bgcolor = NULL;
$type = $_GET["type"];
$application_id = $_GET["application_id"];
$may_use_card_schedule = mayUseCardSchedule($application_id);
$disabled = ($may_use_card_schedule ? "" : "disabled");
$wire_disabled = "disabled";
?>

<html>
<head>
<link rel="stylesheet" href="css/style.css">
<script type="text/javascript" src="js/layout.js"></script>
<script>
function CheckFundTypes()
{
	document.fund_types.submit();
}
</script>
<title>Fund</title>
</head>

<body class="bg" onload="self.focus();">
<form method="post" action="/" name="fund_types" class="no_padding">
<table>

<tr class="height"></tr>

<tr>
<td class="align_left"><label for="funding">FUNDING METHOD:</label></td>
</tr>
<tr>
<td class="align_left"><input type="radio" name="funding" id ="funding" value="ach" checked>ACH</td>
<td class="align_left"><input type="radio" name="funding" id ="funding" value="card" <?php echo $disabled;?> >Card</td>
<td class="align_left"><input type="radio" name="funding" id ="funding" value="wire" <?php echo $wire_disabled;?> >Wire Transfer</td>
</tr>

<tr class="height"></tr><tr class="height"></tr>

<tr>
<td class="align_left"><label for="payment">PAYMENT METHOD:</label></td>
</tr>
<tr>
<td class="align_left"><input type="radio" name="payment" id ="payment" value="ach" checked>ACH</td>
<td class="align_left"><input type="radio" name="payment" id ="payment" value="card" <?php echo $disabled;?> >Card</td>
<!--
<td class="align_left"><input type="radio" name="payment" id ="payment" value="remote">Remote Deposit</td>
-->
</tr>

<tr class="height"></tr><tr class="height"></tr>

<tr class="height">
<td>
<input type="hidden" name="action" value="change_status">
<input type="hidden" name="application_id" value="<?php echo $_GET['application_id']; ?>">
<input type="hidden" name="submit_button" value="<?php echo $_GET['type']; ?>">
<input type="submit" name="submit" value="Submit" class="button" onSubmit="javascript:return CheckFundTypes();">
<input type="button" name="cancel" value="Cancel" onClick="javascript:self.close();" class="button">
</td>
</tr>

</table>
</form>
</body>
</html>
