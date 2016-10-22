<?php
/* check_action_dates.php
 *
 * This script looks for events that are still marked as scheduled with actions dates
 * (date_event) in the past.  These sorts of events should not occur, but occasionally do
 * and they will screw up the payments due report since they are going out with the batch
 * but not showing up on the report.  The events that are found simply need to be adjusted
 * to the correct dates.
 */

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

	$events   = array();

	$query = "
		SELECT  es.date_created,
		        c.name_short as company,
		        es.application_id,
		        es.event_schedule_id,
		        et.name_short as event_type,
		        es.date_event,
		        es.date_effective,
		        es.configuration_trace_data as trace_data
		FROM    event_schedule AS es
		JOIN    company AS c USING (company_id)
		JOIN    event_type AS et USING (event_type_id)
        JOIN    (SELECT event_type_id, transaction_type_id FROM event_transaction GROUP BY event_type_id) AS evt USING (event_type_id)
        JOIN    transaction_type AS tt USING (transaction_type_id)
		WHERE   es.event_status = 'scheduled'
		AND     es.date_event < CURDATE()
        AND     tt.clearing_type = 'ach'
		ORDER BY es.date_event ASC
	";
	$st = $db->query($query);
	$events = $st->fetchAll(PDO::FETCH_OBJ);

	$num_events = count($events);
	// Critical errors exit with 2, other errors with 1

	if ($num_events > 0) 
	{
		$return_value = 1;
	}
	else 
	{
		// All quiet on the western front
		exit(0);
	}

	$subject = "eCash: Found $num_events events with action dates in the past.";
	echo $subject . "\n";
	Email_Report($notify_list, $subject, $events);
	exit($return_value);

} 
catch(Exception $e) 
{
	echo "check_events: Unknown error occurred.\n";
	exit(3);
}

function Email_Report($recipients, $body, $results)
{
	require_once(LIB_DIR . '/CsvFormat.class.php');

	$csv = CsvFormat::getFromArray(array(
		'Date Created',
		'Company',
		'Application ID',
		'Event',
		'Event Type',
		'Action Date',
		'Due Date',
		'Trace Data'));

	$co = array(); //mantis:7727

	foreach($results as $result)
	{
		$csv .= CsvFormat::getFromArray(array(
			$result->date_created,
			$result->company,
			$result->application_id,
			$result->event_schedule_id,
			$result->event_type,
			$result->date_event,
			$result->date_effective,
			$result->trace_data));

		//mantis:7727
		if(!in_array($result->company, $co))
			$co[] = $result->company;
	}

	$subject = 'Ecash Alert '. strtoupper(implode(", ", $co)); //mantis:7727

	$attachments = array(
		array(
			'method' => 'ATTACH',
			'filename' => 'alert_action-dates.csv',
			'mime_type' => 'text/plain',
			'file_data' => gzcompress($csv),
			'file_data_length' => strlen($csv)));

	require_once(LIB_DIR . '/Mail.class.php');
	return eCash_Mail::sendExceptionMessage($recipients, $body, $subject, array(), $attachments); //mantis:7727 - $subject
}

?>
