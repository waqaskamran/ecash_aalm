<?php
	require_once( "config.php" );

	$js = file_get_contents( "js/menu.js" );

	if( ! empty($_GET['override']) )
	{
		$overrides = split(",", $_GET['override']);
		foreach ($overrides as $f)
		{
			if( file_exists(WWW_DIR . "js/" . basename($f) . ".js") )
				$js .= file_get_contents(WWW_DIR . "js/" . basename($f) . ".js");
			else
				$js .= "";
		}
	}
	else
	{
		$js  = file_get_contents( "js/layout.js" );
		$js .= file_get_contents( "js/calendar/calendar.js" );
		$js .= file_get_contents( "js/calendar/lang/calendar-en.js" );
		$js .= file_get_contents( "js/transactions.js" );
		$js .= file_get_contents( "js/overlib.js" );
		$js .= file_get_contents( "js/disable_link.js" );
		$js .= file_get_contents( "js/documents.js" );
		$js .= file_get_contents( "js/nada.js" );
	}

	header( "application/x-javascript" );

	echo $js;
?>
