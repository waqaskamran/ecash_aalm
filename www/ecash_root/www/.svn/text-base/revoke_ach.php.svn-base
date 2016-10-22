<?php
require_once ("config.php");
$bgcolor = NULL;
$application_id = $_GET["application_id"];
?>

<html>
<head>
<link rel="stylesheet" href="css/style.css">
<script type="text/javascript" src="js/layout.js"></script>
<script type="text/javascript">
function CheckRevoke()
{
	 document.customer_revoke_ach.submit();
}
</script>
<title>Customer Revokes ACH</title>
</head>
<!--
<body class="bg" onload="self.focus();">
-->
<body class="bg" onload="document.getElementById('remove_schedule').focus();">
<form method="post" action="/" name="customer_revoke_ach" class="no_padding">

<table align="center" class="%%%mode_class%%%">
<tr class="height"></tr>
<tr style="font-size:17px;">
<td align="center">
<p><b>Action: Add ACH Flag</b></p>
</td>
</tr>
</table>

<table align="center">
<tr class="height"></tr>
<tr class="height"></tr>
<tr>
<td class="align_left"><label for="remove_schedule">Remove ACH Schedule:</label></td>
<td class="align_left"><input type=checkbox name="remove_schedule" id="remove_schedule"></td>
</tr>

<tr class="height"></tr>
<tr class="height"></tr>

<tr class="height">
<td>
<input type="hidden" name="action" value="revoke_ach">
<input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
<input type="button" value="Submit" class="button" onClick="javascript: return CheckRevoke();">
<input type="button" name="cancel" value="Cancel" onClick="javascript:self.close();" class="button">
</td>
</tr>
</table>

</form>
</body>
</html>
