<?php
require_once ("config.php");
$bgcolor = NULL;
$application_id = $_GET["application_id"];

$providers = array();
$pr_model = ECash::getFactory()->getModel('AchProvider');
$pr_array = $pr_model->loadAllBy(array('active_status' => 'active',));
foreach ($pr_array as $pr)
{
	$providers[$pr->name_short] = $pr->name;
}

$designated_ach_provider = NULL;
$app =  ECash::getApplicationByID($application_id);
$flags = $app->getFlags();
foreach ($providers as $name_short => $name)
{
	if($flags->get($name_short))
	{
		$designated_ach_provider = $name_short;
		break;
	}
}
?>

<html>
<head>
<link rel="stylesheet" href="css/style.css">
<script type="text/javascript" src="js/layout.js"></script>
<script type="text/javascript">
function CheckDesignate()
{
	 document.designate_ach_provider.submit();
}
</script>
<title>Designate ACH Provider</title>
</head>
<!--
<body class="bg" onload="self.focus();">
-->
<body class="bg" onload="document.getElementById('remove_schedule').focus();">
<form method="post" action="/" name="designate_ach_provider" class="no_padding">

<table align="center" class="%%%mode_class%%%">
<tr class="height"></tr>
<tr style="font-size:17px;">
<td align="center">
<p><b>Designate ACH Provider:</b></p>
</td>
</tr>
</table>

<table align="center">
<tr class="height"></tr>
<tr>
<td class="align_left">
<select id="ach_provider" name="ach_provider">
<option value=''>Any</option>

<?php
foreach ($providers as $name_short => $name)
{
	$select = '';
	if ($designated_ach_provider == $name_short)
	{
		$select = 'SELECTED';
	}
	echo "<option value='" . $name_short . "' " . $select . ">" . $name . "</option>";
}
?>

</select>
</td>
</tr>

<tr class="height"></tr>
<tr class="height"></tr>

<tr class="height">
<td>
<input type="hidden" name="action" value="designate_ach_provider">
<input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
<input type="button" value="Submit" class="button" onClick="javascript: return CheckDesignate();">
<input type="button" name="cancel" value="Cancel" onClick="javascript:self.close();" class="button">
</td>
</tr>
</table>

</form>
</body>
</html>
