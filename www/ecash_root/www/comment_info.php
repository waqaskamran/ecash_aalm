<?php
require_once ("config.php");
require_once(SQL_LIB_DIR . "scheduling.func.php");
$bgcolor = NULL;
$comment_id = $_GET["comment_id"];
$agent = $_GET["agent"];
$date_time = $_GET["date_time"];
//$comment = $_GET["comment"];
$comment_flag = $_GET["is_resolved"];
$button_text = ($comment_flag == "Y") ? "Unresolve": "Resolve";

$comment_model = ECash::getFactory()->getModel('Comment');
$comment_model->loadBy(array('comment_id'=>$comment_id,));
$comment = $comment_model->comment;

$comment = trim(stripslashes($comment));

function make_clean($text)
{
//Replacing new lines with colons!
$text = str_replace(array("\r","\n"), "; ", $text);
//That's right, I'm changing the text to htmlentities.
$text = htmlentities($text);

//That's right, I'm replacing double quotes with single quotes
$text = str_replace('"', "'"  , $text );

//That's right, I'm adding slashes twice, you wanna fight about it?!
$text = addslashes($text);
//Yes, I do.
//$text = addslashes($text);
return $text;
}
?>

<html>
<head>
<link rel="stylesheet" href="css/style.css">
<script type="text/javascript" src="js/layout.js"></script>
<script>
function CheckCommentFlag()
{
	document.comment_info.submit();
}
</script>
<title>Comment Info</title>
</head>

<body class="bg" onload="self.focus();">
<form method="post" action="/" name="comment_info" class="no_padding">
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
<td class="align_left"><label for="comment"><b>Comment:</b></label></td>
<td class="align_left"><?php echo htmlentities($comment); ?></td>
</tr>

<tr>
<td class="align_left"><label for="comment_flag"><b>Resolved?</b>:</label></td>
<td class="align_left"><?php echo $comment_flag; ?></td>
</tr>

<tr class="height"></tr>
</table>

<table>
<tr class="height">
<td>
<input type="hidden" name="action" value="change_comment_flag">
<input type="hidden" name="comment_id" value="<?php echo $_GET['comment_id']; ?>">
<input type="submit" name="submit" value="<?php echo $button_text; ?>" class="button" onSubmit="javascript:return CheckCommentFlag();">
<input type="button" name="cancel" value="Cancel" onClick="javascript:self.close();" class="button">
</td>
</tr>
</table>

</form>
</body>
</html>
