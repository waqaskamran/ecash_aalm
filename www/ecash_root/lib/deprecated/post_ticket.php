<?php

$GLOBALS["WEBADMIN_WWW_BASE"        ] = "http://webadmin2.tss/";
$GLOBALS["WEBADMIN_USER"            ] = "Jason_Schmidt";
$GLOBALS["WEBADMIN_PASS"            ] = "xsw632";
$GLOBALS["WEBADMIN_NAME"            ] = "Automaton"; // Remember to respect maxlength, make sure this is safe to inject into a regex
$GLOBALS["WEBADMIN_EMAIL"           ] = "nobody@example.com"; // Remember to respect maxlength
$GLOBALS["WEBADMIN_MAX_SUBJECT_SIZE"] = 50;
$GLOBALS["WEBADMIN_PRIORITIES"      ] = array(
	"0" => "Low",
	"1" => "Normal",
	"2" => "High",
	"3" => "Highest",
	"4" => "911 (Showstopper)",
	);

if(!function_exists("Data_Encode"))
{
	function Data_Encode($data, $keyprefix = "", $keypostfix = "")
	{
		if(!is_array($data))
		{
			throw(new Exception("Not an array: "));
			die(1);
		}

		$vars = array();
		foreach($data as $key => $value)
		{
			if(is_array($value))
			{
				$vars[] = Data_Encode($value, $keyprefix.$key.$keypostfix.urlencode("["), urlencode("]"));
			}
			else
			{
				$vars[] = $keyprefix.$key.$keypostfix."=".urlencode($value);
			}
		}
		$vars = join("&",$vars);

		return $vars;
	}
}

/**
 * @brief Submit a new ticket to WebAdmin2
 *
 * @param $subject STRING - Subject string
 *
 * @param $body STRING - Message body
 *
 * @param $priority STRING - Priority level id
 *
 * @return NULL
 *
 * @warning Throws its poo, er, errors at you
 */
function WebAdmin2($subject, $body, $priority)
{
	if(!is_string($subject) || strlen($subject) > $GLOBALS["WEBADMIN_MAX_SUBJECT_SIZE"])
	{
		throw(new Exception("Invalid subject"));
		return(NULL);
	}
	if(!is_string($body))
	{
		throw(new Exception("Invalid body"));
		return(NULL);
	}
	if(!in_array($priority,array_keys($GLOBALS["WEBADMIN_PRIORITIES"])))
	{
		throw(new Exception("Invalid priority"));
		return(NULL);
	}

	$ch = curl_init();
	curl_setopt( $ch , CURLOPT_AUTOREFERER    , TRUE );
	curl_setopt( $ch , CURLOPT_BINARYTRANSFER , TRUE );
	curl_setopt( $ch , CURLOPT_COOKIESESSION  , TRUE );
	curl_setopt( $ch , CURLOPT_FAILONERROR    , TRUE );
	curl_setopt( $ch , CURLOPT_FOLLOWLOCATION , TRUE );
	curl_setopt( $ch , CURLOPT_FORBID_REUSE   , TRUE );
	curl_setopt( $ch , CURLOPT_FRESH_CONNECT  , TRUE );
	curl_setopt( $ch , CURLOPT_HEADER         , TRUE );
	curl_setopt( $ch , CURLOPT_MUTE           , TRUE );
	curl_setopt( $ch , CURLOPT_NOPROGRESS     , TRUE );
	curl_setopt( $ch , CURLOPT_NOSIGNAL       , TRUE );
	curl_setopt( $ch , CURLOPT_RETURNTRANSFER , TRUE );
	curl_setopt( $ch , CURLOPT_CONNECTTIMEOUT , 30 );
	curl_setopt( $ch , CURLOPT_TIMEOUT        , 60 );
	curl_setopt( $ch , CURLOPT_COOKIEFILE     , "/dev/null" );
	curl_setopt( $ch , CURLOPT_COOKIEJAR      , "/dev/null" );
	curl_setopt( $ch , CURLOPT_HTTPHEADER     , array(
		"User-Agent: Lynx/2.8.5rel.1 libwww-FM/2.14 SSL-MM/1.4.1 OpenSSL/0.9.7i",
		));

	// First load the welcome page to get the login number
	curl_setopt( $ch , CURLOPT_URL , $GLOBALS["WEBADMIN_WWW_BASE"] );
	$result = curl_exec( $ch );
	if(curl_errno($ch) || !preg_match("/<input type='hidden' name='login' value='(\\d+)' \\/>/",$result,$matched))
	{
		throw(new Exception("Failed to get login id value"));
		return(NULL);
	}
	$login_id = $matched[1];

	// Now actually attempt a login
	curl_setopt( $ch , CURLOPT_URL        , $GLOBALS["WEBADMIN_WWW_BASE"] );
	curl_setopt( $ch , CURLOPT_POSTFIELDS , Data_Encode(array(
		"login"    => $login_id,
		"lostpass" => "0",
		"redirect" => "",
		"username" => $GLOBALS["WEBADMIN_USER"],
		"password" => $GLOBALS["WEBADMIN_PASS"],
		"login"    => "login",
		)));
	$result = curl_exec( $ch );
	if(curl_errno($ch) || !preg_match("/<a href='\\?m=ticketsmith'>Tickets<\\/a>/",$result))
	{
		throw(new Exception("Failed login"));
		return(NULL);
	}

	// Now go to the Tickets page
	curl_setopt( $ch , CURLOPT_URL , $GLOBALS["WEBADMIN_WWW_BASE"] . "?m=ticketsmith" );
	curl_setopt( $ch , CURLOPT_HTTPGET , TRUE );
	$result = curl_exec( $ch );
	if(curl_errno($ch) || !preg_match("/<input type=\"submit\" class=\"button\" value=\"new ticket\">/",$result))
	{
		throw(new Exception("Failed to go to ticketsmith"));
		return(NULL);
	}

	// Now go to the new ticket page
	curl_setopt( $ch , CURLOPT_URL , $GLOBALS["WEBADMIN_WWW_BASE"] . "?m=ticketsmith&a=post_ticket" );
	curl_setopt( $ch , CURLOPT_HTTPGET , TRUE );
	$result = curl_exec( $ch );
	if(curl_errno($ch) || !preg_match("/Submit Trouble Ticket/",$result))
	{
		throw(new Exception("Failed to go to new ticket page"));
		return(NULL);
	}

	// Now actually submit the ticket... moment of truth
	curl_setopt( $ch , CURLOPT_URL , $GLOBALS["WEBADMIN_WWW_BASE"] . "?m=ticketsmith" );
	curl_setopt( $ch , CURLOPT_POSTFIELDS , Data_Encode(array(
		"dosql" => "do_ticket_aed",
		"name" => $GLOBALS["WEBADMIN_NAME"],
		"email" => $GLOBALS["WEBADMIN_EMAIL"],
		"subject" => $subject,
		"priority" => $priority,
		"description" => $body,
		)));
	$result = curl_exec( $ch );
	if(curl_errno($ch))
	{
		throw(new Exception("Failed to submit new ticket"));
		return(NULL);
	}

	return(NULL);
}

?>
