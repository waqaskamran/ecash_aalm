<?php
try 
{
	if($argc > 1) 
	{
		$notify_list = $argv[1];
	}
	else 
	{
		$notify_list = "rebel75cell@gmail.com, brian.gillingham@gmail.com, randy.klepetko@sbcglobal.net";
	}

	require_once(dirname(__FILE__)."/../www/config.php");

	$db = ECash::getMasterDb();

	$main_query ="
		SELECT *
		FROM standby
		WHERE process_type = 'error_state_11'
	";
	$st = $db->query($main_query);
	$entries = $st->fetchAll(PDO::FETCH_OBJ);

	// Clean it up
	$db->exec("DELETE FROM standby WHERE process_type = 'error_state_11'");
	$count = count($entries);
	// Determine our entries
	if ($count > 0) 
	{
		$msg = "State 11 warning: Found {$count} apps that hit State 11\n";
		Email_Report($notify_list, $msg, $entries);
		echo $msg;
		exit(1);
	} else exit(0);// All quiet on the western front
} 
catch(Exception $e) 
{
	echo "State 11 error: Unknown error occurred.\n";
	exit(3);
}

function Email_Report($recipients, $body, $results)
{
	require_once(LIB_DIR . '/CsvFormat.class.php');

	$csv = CsvFormat::getFromArray(array(
		'Application ID',
		'Date Created'));

	foreach ($results as $result)
	{
		$csv .= CsvFormat::getFromArray(array(
			$result->application_id,
			$result->date_created));
	}

	$attachments = array(
		array(
			'method' => 'ATTACH',
			'filename' => 'alert_errors.csv',
			'mime_type' => 'text/plain',
			'file_data' => gzcompress($csv),
			'file_data_length' => strlen($csv)));

	require_once(LIB_DIR . '/Mail.class.php');
	return eCash_Mail::sendExceptionMessage($recipients, $body, null, array(), $attachments);
}

?>
