<?php

require_once('config.php');
require_once(LIB_DIR.'common_functions.php');
require_once(CLIENT_CODE_DIR . 'display_application.class.php');

/**
 * Clean up magic quotes.
 *
 * @todo We need to eventualy completely remove any dependence on magic
 * quoting. For now, any request variables that should be stripped if
 * magic quoting is on can be added to this array and the code below will
 * complete the stripping
 */
$strip_magic_quotes = array(
	'search_criteria_1', 'search_criteria_2', 'employer_name','name_first','name_last','agent_login', 'group_name', 'group_company_name'
);

// Begin magic quotes stripping
$magic_quotes_check_arrays = array('_GET', '_POST', '_COOKIE', '_REQUEST');
if (get_magic_quotes_gpc()) {
	foreach ($strip_magic_quotes as $var) {
		foreach ($magic_quotes_check_arrays as $arr)
		if (isset(${$arr}[$var])) {
			${$arr}[$var] = preg_replace('#\\\\(.)#', '$1', ${$arr}[$var]);
		}
	}
}
// End magic quotes stripping

try
{
	$request = Site_Request::fromGlobals(FALSE);

	$dispatcher = new ECashUI_Dispatcher();
	$response = $dispatcher->processRequest($request);
	$response->render();
}
catch(Exception $e)
{
	$exception = new Display_Exception();
	$exception->Do_Display($e);
}

?>
