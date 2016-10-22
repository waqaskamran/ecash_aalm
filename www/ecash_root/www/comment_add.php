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
function CheckAddComment()
{
	if(!isNotWhiteSpace(document.getElementById('new_comment').value))
	{
		alert('Please Enter Comments');
		return false;
	}
	else
	{
		document.comment_add.submit();
	}
}
</script>
<title>Add Comment</title>
</head>
<!--
<body class="bg" onload="self.focus();">
-->
<body class="bg" onload="document.getElementById('new_comment').focus();">
<form method="post" action="/" name="comment_add" class="no_padding">

<table>
<tr><td class="align_left"><label for="new_comment"><b>Please Enter Comments:</b></label></td></tr>
<tr><td class="align_left"><input type=textbox name="new_comment" id="new_comment" size="60" value=""></td></tr>
<tr class="height">
<tr><td class="align_left"><label for="comment_flag"><b>Resolved:</b></label></td></tr>
<tr><td class="align_left"><input type=checkbox name="comment_flag" id="comment_flag"></td></tr>

<tr class="height">
<td>
<input type="hidden" name="action" value="add_comment_new">
<input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
<input type="button" value="Submit" class="button" onClick="javascript: return CheckAddComment();">
<input type="button" name="cancel" value="Cancel" onClick="javascript:self.close();" class="button">
</td>
</tr>
</table>

</form>
</body>
</html>
