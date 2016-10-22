<?php

// Do a security check and re-establish current ecash session
if(!isset($_COOKIE['ssid']) || strlen($_COOKIE['ssid']) < 26)
{
	die ("<h3>You are not logged in. Your session may have expired.</h3>");
}
$session_id = $_COOKIE['ssid'];
$session_obj = new ECash_Session('ssid', $session_id, ECash_Session::GZIP);

if(!isset($_SESSION["security_6"]["login_time"]))
{
	die ("<h3>You are not logged in. Your session may have expired.</h3>");
}

?>
