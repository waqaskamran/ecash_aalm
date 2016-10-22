<?php
/* check_action_dates.php
 *
 * This script will check the past 24 hours of PBX data to make sure
 * PBX Originates are not more the PBX Dials. If so an email alert is sent out with totals.
 */


try 
{
	if($argc > 1) $notify_list = $argv[1];
	else $notify_list = "rebel75cell@gmail.com, brian.gillingham@gmail.com, randy.klepetko@sbcglobal.net";
	
	require_once(dirname(__FILE__)."/../www/config.php");

	$db = ECash::getMasterDb();

	$events   = array();

	$query = "
		SELECT
		   pbx_event,
		   count(pbx_event) as count
		FROM pbx_history
		WHERE date_created > (CURRENT_DATE() - 1)
		GROUP BY pbx_event
	";
	$st = $db->query($query);

	while ($row = $st->fetch(PDO::FETCH_OBJ))
	{
		$pbx_events[$row->pbx_event] = $row->count;
	}

	// There are more Originates then Dials in the
	// Past 24 hours. Alert
	if($pbx_events["Dial"] < $pbx_events["Originate"])
	{
		$subject = "eCash: Detected possible PBX error.";
		echo $subject . "\n";
		Email_Report($notify_list, $subject, $pbx_events);
		exit(1);
	}
	else
	{
		exit(0);
	}

} catch(Exception $e) {
	echo "check_events: Unknown error occurred.\n";
	exit(3);
}

function Email_Report($recipients, $body, $results)
{
	require_once(LIB_DIR . '/CsvFormat.class.php');

	$csv = CsvFormat::getFromArray(array(
		'PBX Event',
		'Count'));

	foreach ($results as $key => $value)
	{
		$csv .= CsvFormat::getFromArray(array(
			$key,
			$value));
	}

	$attachments = array(
		array(
			'method' => 'ATTACH',
			'filename' => 'alert_pbx-dial.csv',
			'mime_type' => 'text/plain',
			'file_data' => gzcompress($csv),
			'file_data_length' => strlen($csv)));

	require_once(LIB_DIR . '/Mail.class.php');
	return eCash_Mail::sendExceptionMessage($recipients, $body, null, array(), $attachments);
}

?>
