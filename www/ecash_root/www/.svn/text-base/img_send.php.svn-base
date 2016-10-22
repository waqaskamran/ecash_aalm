<?php

require_once("config.php");


if( isset($_GET['session_id']) )
{
	$db = ECash::getMasterDb();

	$sess_id = $_REQUEST["ssid"];

	$session = new ECash_Session('ssid', $sess_id, ECash_Session::GZIP);

	if( isset($_GET['image_name']) && isset($_SESSION['monitor_images'][$_GET['image_name']]) )
	{
		$type = isset($_GET['image_type']) ? $_GET['image_type']: "png"; // Default to png if no type is passed
		$img = $_SESSION['monitor_images'][$_GET['image_name']];
	}
	else
	{
		// Send an image that says the image could not be found in the session
		$img = file_get_contents("image/image_errors/error_session_image_not_found.png");
		$type = "png";
	}
}
else
{
	// Send an image that says a session_id is requried.
	$img = file_get_contents("image/image_errors/error_session_image_not_found.png");
	$type = "png";
}

// Setup the image header
header ("Content-type: image/{$type}");

// Send the image
echo $img;

?>
