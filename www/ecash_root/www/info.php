<?php 

$realm = "PHP_Info";

$authorization_required_string = "
<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">
<HTML>
<HEAD>
<TITLE>401 Authorization Required</TITLE>
</HEAD>
<BODY>
<H1>Authorization Required</H1>
This server could not verify that you are authorized to access the document
requested.  Either you supplied the wrong credentials (e.g., bad password), or your
browser doesn't understand how to supply the credentials required.<P>
<HR>
<ADDRESS>{$_SERVER['SERVER_SIGNATURE']}</ADDRESS>
</BODY>
</HTML>
";

if ( !isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != 'tss' || $_SERVER['PHP_AUTH_PW'] != 'phpmunkey' )
{
	header("WWW-Authenticate: Basic realm=$realm");
	header("HTTP/1.0 401 Unauthorized");
	echo $authorization_required_string;
	exit;
}

require_once("config.php");

// Authentication successful
phpinfo();

?> 
