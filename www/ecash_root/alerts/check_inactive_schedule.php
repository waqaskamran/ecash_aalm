<?php

/**
 * Looks for accounts that have scheduled events in statuses that shouldn't.
 *
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

	$inactive_statuses = array();
	$query = "
		SELECT application_status_id
		FROM application_status_flat
		WHERE active_status = 'active'
        AND ((level0 ='paid' AND level1 = 'customer' AND level2 = '*root')
		OR    (level1 = 'applicant')
		OR    (level2 = 'applicant')
		OR	  (level1 = 'cashline')
		OR	  (level1 = 'prospect'))
        ORDER BY application_status_id
  ";
	$inactive_statuses = $db->querySingleColumn($query);

	$query = "
        SELECT c.name_short as company_name,
            es.application_id,
            asf.level0_name as status,
            es.event_schedule_id,
            et.name as `event_type`,
            es.amount_principal as `principal`,
            es.amount_non_principal as `non_principal`,
            es.date_event,
            es.date_effective
        FROM event_schedule as es
        JOIN application AS a USING (application_id)
        JOIN application_status_flat AS asf USING (application_status_id)
        JOIN event_type AS et USING (event_type_id)
        JOIN company AS c ON (c.company_id = a.company_id)
        WHERE a.application_status_id IN (".implode(',',$inactive_statuses).")
        AND event_status = 'scheduled'
        ORDER BY application_id, date_event
  ";
	$st = $db->query($query);

	$total = $st->rowCount();
	$specifics = $st->fetchAll(PDO::FETCH_OBJ);

	if ($total > 0) 
	{
		$subject = "eCash: Found {$total} scheduled items for Inactive or Pre-Customer accounts.";
		//echo $subject . "\n";
		Email_Report($notify_list, $subject, $specifics);
		exit(1);
	} 
	else exit(0);
} 
catch (Exception $e) 
{
	echo "check_inactive_schedule: Unknown error occurred:".$e->getTraceAsString()."\n";
	exit(3);
}

function Email_Report($recipients, $body, $results)
{
	require_once(LIB_DIR . '/CsvFormat.class.php');

	$csv = CsvFormat::getFromArray(array(
		'Company',
		'Application ID',
		'Status',
		'Event ID',
		'Event Type',
		'Principal',
		'Non-Principal',
		'Action Date',
		'Effective Date'));

	$co = array(); //mantis:7727

	foreach ($results as $result)
	{
		$csv .= CsvFormat::getFromArray(array(
			$result->company_name,
			$result->application_id,
			$result->status,
			$result->event_schedule_id,
			$result->event_type,
			$result->principal,
			$result->non_principal,
			$result->date_event,
			$result->date_effective));

		//mantis:7727
		if(!in_array($result->company, $co))
			$co[] = $result->company;
	}

	$subject = 'Ecash Alert '. strtoupper(implode(", ", $co)); //mantis:7727

	$attachments = array(
		array(
			'method' => 'ATTACH',
			'filename' => 'alert_inactive-schedule.csv',
			'mime_type' => 'text/plain',
			'file_data' => gzcompress($csv),
			'file_data_length' => strlen($csv)));

	require_once(LIB_DIR . '/Mail.class.php');
	return eCash_Mail::sendExceptionMessage($recipients, $body, $subject, array(), $attachments); //mantis:7727 - $subject
}

?>
