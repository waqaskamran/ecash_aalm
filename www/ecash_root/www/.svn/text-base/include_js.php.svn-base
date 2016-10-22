<?php
require_once( "config.php" );

function include_js($overrides = NULL) {

	$release_string = 'name='.SOFTWARE_NAME.'&major='.MAJOR_VERSION.'&minor='.MINOR_VERSION.'&build='.BUILD_NUM;


	$files = Array('menu', 'layout', 'calendar/calendar', 'calendar/lang/calendar-en', 'transactions', 'date_validation', 'overlib', 'disable_link', 'documents', 'nada');

	if (!empty($overrides)) {
		$files = $overrides;
	}

	$js = '';
	foreach ($files as $f)
	{
		if( file_exists(WWW_DIR . "js/{$f}.js") )
		{
			if (EXECUTION_MODE == 'LOCAL') 
			{
				$stat = stat(WWW_DIR . "js/{$f}.js");
				$release_string = 'mod=' . $stat[9];
			}

			$js .= "<script type='text/javascript' src='js/{$f}.js?{$release_string}'></script>\n";
		}
	}
	$js .= 	'<script type="text/javascript">
	var serverdate = "'.date('M j Y H:i:s').'";
	</script>';

	return $js;
}
?>
