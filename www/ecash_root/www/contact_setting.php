<?php

$flag = $_REQUEST['setting'];
$checked_fields = empty($_REQUEST['checked_field']) ? array() : explode(",", $_REQUEST['checked_field']);
$icon_str = stripslashes($_REQUEST['icon']);
$title = strtoupper(str_replace('_', ' ', $flag));

//echo "<pre>", print_r($checked_fields, TRUE), "</pre>";

//phone_home,phone_cell,phone_work,customer_email,ref_phone_1,ref_phone_2
$fields = array('phone_home', 'phone_cell', 'phone_work', 'customer_email', 'ref_phone_1', 'ref_phone_2', 'ref_phone_3', 'ref_phone_4', 'ref_phone_5', 'ref_phone_6', 'street');


foreach($fields as $field_name)
{
	$var_name = $field_name . "_str";
	$$var_name = '';
	if(in_array($field_name, $checked_fields))
	{
		//echo "<pre>setting variable: ", $var_name, " to checked</pre>";
		$$var_name = " checked";
	}
}


?>

<html>
<head>
<link rel="stylesheet" href="css/style.css">
<script type="text/javascript" src="js/layout.js"></script>
<title><?php  echo $title; ?></title>

<script type="text/javascript">

function checkAll(thisForm)
{
for (i = 0; i < thisForm.elements.length; i++)
	{
	thisForm.elements[i].checked=true;
		document.getElementById('<?=$flag?>_all').value = 'Deselect All';
	}
}

function uncheckAll(thisForm)
{
for (i = 0; i < thisForm.elements.length; i++)
	{
	thisForm.elements[i].checked=false;
	document.getElementById('<?=$flag?>_all').value = 'Select All';

	}
}

function check(thisForm)
{
	if (document.getElementById('<?=$flag?>_all').value == 'Select All')
	{
		checkAll(thisForm);
	}
	else
	{
		uncheckAll(thisForm);
	}
}
function verifyAll(thisForm)
{
	
	for (i = 0; i < thisForm.elements.length; i++)
	{
		if (thisForm.elements[i].type == 'checkbox' && thisForm.elements[i].checked == false)
		{ 
			document.getElementById('<?=$flag?>_all').value = 'Select All';
			return false;
		}
	}
	document.getElementById('<?=$flag?>_all').value = 'Deselect All';
	return true;
}
</script>

</head>
<body class="bg" onload="self.focus(); verifyAll(document.getElementById('<?=$flag?>'));">

<tr style="height: 30px;" allign="right">
	<td>
		<?= $icon_str; ?>
	</td>
	<td colspan="2">
		<b><?= $title; ?></b>
	</td>
</tr>

<form method="post" action="/" class="no_padding" name="<?= $flag; ?>" id="<?=$flag?>">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="height: 80px;" align="center">
<tbody>
<tr>
<input type="checkbox" id="<?= $flag.'_phone_home'?>"<?= $phone_home_str ?>><label for="<?=$flag.'_phone_home'?>">Home Phone</label><br>
<input type="checkbox" id="<?= $flag.'_phone_cell'?>"<?= $phone_cell_str ?>><label for="<?=$flag.'_phone_cell'?>">Cellular Phone</label><br>
<input type="checkbox" id="<?= $flag.'_phone_work'?>"<?= $phone_work_str ?>><label for="<?=$flag.'_phone_work'?>">Work Phone</label><br>
<input type="checkbox" id="<?= $flag.'_customer_email'?>"<?= $customer_email_str ?>><label for="<?=$flag.'_customer_email'?>">Email</label><br>
<input type="checkbox" id="<?= $flag.'_ref_phone_1'?>"<?= $ref_phone_1_str ?>><label for="<?=$flag.'_ref_phone_1'?>">Reference #1 Phone</label>
<input type="checkbox" id="<?= $flag.'_ref_phone_2'?>"<?= $ref_phone_2_str ?>><label for="<?=$flag.'_ref_phone_2'?>">Reference #2 Phone</label><br>
<input type="checkbox" id="<?= $flag.'_ref_phone_3'?>"<?= $ref_phone_3_str ?>><label for="<?=$flag.'_ref_phone_3'?>">Reference #3 Phone</label>
<input type="checkbox" id="<?= $flag.'_ref_phone_4'?>"<?= $ref_phone_4_str ?>><label for="<?=$flag.'_ref_phone_4'?>">Reference #4 Phone</label><br>
<input type="checkbox" id="<?= $flag.'_ref_phone_5'?>"<?= $ref_phone_5_str ?>><label for="<?=$flag.'_ref_phone_5'?>">Reference #5 Phone</label>
<input type="checkbox" id="<?= $flag.'_ref_phone_6'?>"<?= $ref_phone_6_str ?>><label for="<?=$flag.'_ref_phone_6'?>">Reference #6 Phone</label><br>
<input type="checkbox" id="<?= $flag.'_street'?>"<?= $street_str ?>><label for="<?=$flag.'_street'?>">Home Address</label><br>
<input type="button" id="<?= $flag.'_all'?>" onclick="check(this.form)" value="Select All"><br>

<td><input type="button" value="Cancel" onClick="window.close();"></td>
<td><input type="button" value="Save" onClick="javascript:Set_Contact1('<?= $flag; ?>');"></td>
</tr>
</tbody>
</table>
</form>

<form id="change_contact" method="post" action="/" class="no_padding">
	<input type="hidden" name="application_id" value="<?= $_REQUEST['application_id']; ?>">
	<input type="hidden" name="panel" id="change_contact_panel" value="">
	<input type="hidden" name="action" value="change_contact">
	<input type="hidden" name="contact_setting" id="new_contact_status" value="">
	<input type="hidden" name="phone_home" id="phone_home" value="">
	<input type="hidden" name="phone_cell" id="phone_cell" value="">
	<input type="hidden" name="phone_work" id="phone_work" value="">
	<input type="hidden" name="customer_email" id="customer_email" value="">
	<input type="hidden" name="ref_phone_1" id="ref_phone_1" value="">
	<input type="hidden" name="ref_phone_2" id="ref_phone_2" value="">
	<input type="hidden" name="ref_phone_3" id="ref_phone_3" value="">
	<input type="hidden" name="ref_phone_4" id="ref_phone_4" value="">
	<input type="hidden" name="ref_phone_5" id="ref_phone_5" value="">
	<input type="hidden" name="ref_phone_6" id="ref_phone_6" value="">
	<input type="hidden" name="street" id="street" value="">
</form>

</body>
</html>
