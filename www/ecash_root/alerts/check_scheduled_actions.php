<?php

/**
 * Looks for accounts in most statuses that have a transactional account
 * balance, but no scheduled events. If it finds them, it sends a warning/alert.
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

	// Don't skip Collections Contact or Contact Follow Up. mantis:5595
	$excl_query = "
		SELECT application_status_id
		FROM application_status_flat
		WHERE
			(level0='pending' AND level1='external_collections' AND level2='*root')
				OR (level0='sent' AND level1='external_collections' AND level2='*root')
				OR (level1='contact' AND level2='collections' AND level3='customer' AND level4='*root')
				OR (level0 IN('ready','return') AND level1='quickcheck' AND level2='collections' AND level3='customer' AND level4='*root'
		)
	";

// TEMPORARY ADD TO REMOVE THE BANKRUPTCY STATUSES
	$excl_query .= "OR (level1='bankruptcy' AND level2='collections' AND level3='customer')";

	$excl_statuses = $db->querySingleColumn($excl_query);


// These are the statuses I want to conditionally add back in using the UNION below
	$incl_query = "
		SELECT application_status_id
		FROM application_status_flat
		WHERE
			(level1='contact' AND level2='collections' AND level3='customer' AND level4='*root')";

	$incl_statuses = $db->querySingleColumn($incl_query);

	$query = "(
		SELECT
			a.application_id,
			ass.name as 'Status',
			app.ssn,
			app.is_watched ,
			a.total,
			co.name_short
		FROM
			application app,
			(	select tr.application_id, sum(tr.amount) as 'total'
				from transaction_register tr
				where transaction_type_id not in (33,34)
				and transaction_status <> 'failed'
				group by tr.application_id
				having total > 0
			) a ,
  			application_status ass,
  			company co
		WHERE a.application_id = app.application_id
		AND co.company_id = app.company_id
		AND ass.application_status_id = app.application_status_id
		AND app.application_status_id not in (".implode(",",$excl_statuses).")
		AND not exists (select 'x' from event_schedule es where es.event_status = 'scheduled' and app.application_id = es.application_id)
		AND not exists (select 'x' from transaction_register tr where tr.transaction_status = 'pending' and app.application_id = tr.application_id)
		ORDER BY app.application_status_id)
		UNION
		(SELECT
			a.application_id,
			ass.name as 'Status',
			app.ssn,
			app.is_watched ,
			a.total,
			co.name_short
		FROM
			application app,
			(	select tr.application_id, sum(tr.amount) as 'total'
				from transaction_register tr
				where transaction_type_id not in (33,34)
				and transaction_status <> 'failed'
				group by tr.application_id
				having total > 0
			) a ,
  			application_status ass,
  			company co
		WHERE a.application_id = app.application_id
		AND co.company_id = app.company_id
		AND ass.application_status_id = app.application_status_id
		AND app.application_status_id in (".implode(",",$incl_statuses).")
		AND not exists (select 'x' from event_schedule es where es.event_status = 'scheduled' and app.application_id = es.application_id)
		AND not exists (select 'x' from transaction_register tr where tr.transaction_status = 'pending' and app.application_id = tr.application_id)
		AND (	SELECT acr.date_created FROM ach
    				JOIN ach_report as acr using (ach_report_id)
    				WHERE application_id = app.application_id
    				ORDER BY acr.date_created DESC LIMIT 1
				) < DATE_SUB(CURDATE(), INTERVAL 5 DAY)
		ORDER BY app.application_status_id)
		";

	$st = $db->query($query);

	$specifics = $st->fetchAll(PDO::FETCH_OBJ);
	$total = count($specifics);

	if ($total > 0) 
	{
		$subject = "eCash: Found {$total} positive balance accounts with no scheduled actions or pending transactions.";
		echo $subject . "\n";
		Email_Report($notify_list, $subject, $specifics);
		exit(1);
	} 
	else 
	{
		exit(0);
	}
} 
catch (Exception $e) 
{
	echo "check_scheduled_actions: Unknown error occurred:".$e->getTraceAsString()."\n";
	exit(3);
}

function Email_Report($recipients, $body, $results)
{
	require_once(LIB_DIR . '/CsvFormat.class.php');

	$csv = CsvFormat::getFromArray(array(
		'Application ID',
		'Status',
		'SSN',
		'Watched',
		'Amount',
		'Company'));

	$co = array(); //mantis:7727

	foreach ($results as $result)
	{
		$csv .= CsvFormat::getFromArray(array(
			$result->application_id,
			$result->Status,
			$result->ssn,
			$result->is_watched,
			$result->total,
			$result->name_short));

		//mantis:7727
		if(!in_array($result->name_short, $co))
			$co[] = $result->name_short;
	}

	$subject = 'Ecash Alert '. strtoupper(implode(", ", $co)); //mantis:7727

	$attachments = array(
		array(
			'method' => 'ATTACH',
			'filename' => 'alert_errors.csv',
			'mime_type' => 'text/plain',
			'file_data' => gzcompress($csv),
			'file_data_length' => strlen($csv)));

	require_once(LIB_DIR . '/Mail.class.php');
	return eCash_Mail::sendExceptionMessage($recipients, $body, $subject, array(), $attachments); //mantis:7727 - $subject
}

?>
