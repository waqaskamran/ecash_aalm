<?php

/**
 * Looks for accounts in most statuses that have a transactional account
 * balance, but no scheduled events. If it finds them, it sends a warning/alert.
 *
 */


try 
{
	if($argc > 1) $notify_list = $argv[1];
	else $notify_list = "rebel75cell@gmail.com, brian.gillingham@gmail.com, randy.klepetko@sbcglobal.net";

	require_once(dirname(__FILE__)."/../www/config.php");

	$db = ECash::getMasterDb();

	$query = "
		SELECT transaction_type_id
		FROM   transaction_type
		WHERE name_short LIKE 'refund_3rd_party%'
	";
	$irrecoverable_types = $db->querySingleColumn($query);

	$query = "
select
    c.name_short as company,
	app.application_id ,
	app.application_status_id,
	ass.name as 'Status',
	a.total,
	(exists(
		select 'x'
		from event_schedule es
		where es.application_id = a.application_id
		and es.event_status = 'scheduled')
	) as 'schedule',
	(
	select
		tra.date_effective
	from transaction_register tra
	where
		tra.transaction_status <> 'failed'
		and tra.application_id = app.application_id
		and tra.transaction_type_id not in (33,34)
	group by tra.application_id, tra.date_effective
	having 	(
		select sum(tra2.amount)
		from transaction_register tra2
		where
			tra2.application_id = tra.application_id
			and tra2.transaction_type_id not in (".implode(',', $irrecoverable_types).")
			and tra2.transaction_status <> 'failed'
			and tra2.date_effective <= tra.date_effective
		) < 0
	order by tra.date_effective
	limit 1
	) as date_first_negative
	from
		application app,
        company c,
		application_status ass,
		(
		select tr.application_id, sum(tr.amount) as 'total'
		from transaction_register tr
		where transaction_type_id not in (33,34)
		and transaction_status <> 'failed'
		group by tr.application_id
		having total < 0
		) a
	where
		a.application_id = app.application_id
        and c.company_id = app.company_id
		and ass.application_status_id = app.application_status_id
	order by company, schedule, app.application_status_id
	";
	$st = $db->query($query);

	$specifics = $st->fetchAll(PDO::FETCH_OBJ);
	$total = count($specifics);

	if ($total > 0) 
	{
		$subject = "eCash: Found {$total} negative balance accounts.";
		echo $subject . "\n";
		Email_Report($notify_list, $subject, $specifics);
		exit(1);
	} 
	else exit(0);
} catch (Exception $e) {
	echo "check_scheduled_actions: Unknown error occurred:".$e->getTraceAsString()."\n";
	exit(3);
}

function Email_Report($recipients, $body, $results)
{
	require_once(LIB_DIR . '/CsvFormat.class.php');

	$csv = CsvFormat::getFromArray(array(
		'Company',
		'Application ID',
		'Status',
		'Amount',
		'Has Scheduled Items',
		'Date Added to Alert'));

	$co = array(); //mantis:7727

	foreach ($results as $result)
	{
		$csv .= CsvFormat::getFromArray(array(
			$result->company,
			$result->application_id,
			$result->Status,
			$result->total,
			(($result->schedule == 1) ? 'Yes' : 'No'),
			$result->date_first_negative));

		//mantis:7727
		if(!in_array($result->company, $co))
			$co[] = $result->company;
	}

	$subject = 'Ecash Alert '. strtoupper(implode(", ", $co)); //mantis:7727

	$attachments = array(
		array(
			'method' => 'ATTACH',
			'filename' => 'alert_negative-balances.csv',
			'mime_type' => 'text/plain',
			'file_data' => gzcompress($csv),
			'file_data_length' => strlen($csv)));

	require_once(LIB_DIR . '/Mail.class.php');
	return eCash_Mail::sendExceptionMessage($recipients, $body, $subject, array(), $attachments); //mantis:7727 - $subject
}

?>
