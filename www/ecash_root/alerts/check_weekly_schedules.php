<?php

/**
 * Checks all weekly and bi-weekly customers for eCash 3.0
 * companies and compares their next two payments for
 * date discrepancies.
 *
 * @author Brian Ronald
 */

try
{
	require_once(dirname(__FILE__)."/../www/config.php");

	// If passed a recipient list, use it instead, otherwise
	// use the NOTIFICATION_ERROR_RECIPIENTS, which will
	// probably be a larger list than we may want to use.
	if($argc > 1) {
		$notify_list = $argv[1];
	}
	else 
	{
		if(ECash::getConfig()->NOTIFICATION_ERROR_RECIPIENTS === NULL)
			throw new Exception("No Notification List defined!");
		$notify_list = ECash::getConfig()->NOTIFICATION_ERROR_RECIPIENTS;
	}

	require_once(SQL_LIB_DIR . "fetch_status_map.func.php");

	$db = ECash::getMasterDb();

	define('ONE_WEEK', 604800);
	define('TWO_WEEKS', 1209600);
	define('TWO_AND_A_HALF_WEEKS', 1512000);
	define('THREE_WEEKS', 1814400);

	$problems = 0;
	$company_ids = array();

	$status_map = Fetch_Status_Map();

	$eligible_companies = Get_Eligible_Companies();
	// If there are no eligible companies, just exit;
	if (count($eligible_companies) === 0)
	{
		exit(0);
	}

	foreach ($eligible_companies as $c)
	{
		$company_ids[] = $c->company_id;
	}

	$apps = Get_Weekly_Apps($company_ids);

	require_once(LIB_DIR . '/CsvFormat.class.php');
	$csv = CsvFormat::getFromArray(array(
		'Company',
		'Application',
		'Status',
		'Issue'));

	$co = array(); //mantis:7727

	foreach ($apps as $a)
	{
		$issue = null;

		// If there is no following payment, then we have nothing to worrk about.
		if (empty($a->following_payment))
		{
			continue;
		}

		// Find the difference between the next payment and the following payment.
		$difference = (strtotime($a->following_payment) - strtotime($a->next_payment));

		if ($difference < ONE_WEEK)
		{
			$issue = "Payments less than 1 WEEK apart! Next: {$a->next_payment}  Following: {$a->following_payment}";
			$problems++;
		}
		else if ($difference > THREE_WEEKS)
		{
			$issue = "Payments over 3 WEEKS apart! Next: {$a->next_payment}  Following: {$a->following_payment}";
			$problems++;
		}
		else if ($difference > TWO_AND_A_HALF_WEEKS)
		{
			$issue = "Payments over 2.5 WEEKS apart!  Next: {$a->next_payment}  Following: {$a->following_payment}";
			$problems++;
		}

		if ($issue != null)
		{
			$csv .= CsvFormat::getFromArray(array(
				$a->company_name,
				$a->application_id,
				$status_map[$a->application_status_id]['name'],
				$issue));

			//mantis:7727
			if(!in_array($a->company_name, $co))
				$co[] = $a->company_name;
		}
	}

	if ($problems > 0)
	{
		$recipients = $notify_list;
		$body = $problems . ' Application(s) have invalid weekly schedules!';
		$subject = 'Ecash Alert '. strtoupper(implode(", ", $co)); //mantis:7727
		$attachments = array(
			array(
				'method' => 'ATTACH',
				'filename' => 'alert_weekly-schedules.csv',
				'mime_type' => 'text/plain',
				'file_data' => gzcompress($csv),
				'file_data_length' => strlen($csv)));

		require_once(LIB_DIR . '/Mail.class.php');
		eCash_Mail::sendExceptionMessage($recipients, $body, $subject, array(), $attachments); //mantis:7727 - $subject
		echo "eCash 3.0: {$subject}\n";
		exit(2);
	}

	exit(0);
}
catch (Exception $e)
{
	echo "{$argv[0]}: Error occurred: {$e->getMessage()}\nTrace:\n{$e->getTraceAsString()}\n";
	exit(3);
}

exit(0);

/**
 * Fetches Eligible Companies using the same configuration file
 * and configuration parameters.
 *
 * @return array of objects with company_id, name_short, and name
 */
function Get_Eligible_Companies(DB_Database_1 $db)
{
	$eligible_companies = array();

	$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
		SELECT c.company_id, c.name_short, c.name
		FROM company AS c
		WHERE
			c.active_status = 'active'
		AND
			c.name_short NOT LIKE '%_a'
	";
	$st = $db->query($sql);
	return $st->fetchAll(PDO::FETCH_OBJ);
}

/**
 * Grabs all of the weekly and bi-weekly applicants
 *
 * @param  array 		Array of company id's to search
 * @return array		Array of results
 */
function Get_Weekly_Apps($companies)
{
	$company_list = implode(',', $companies);

	$db = ECash::getMasterDb();

	$sql = "
	select  a.application_id,
			a.company_id,
			c.name_short as company_name,
			a.application_status_id,
        	date_format(es.date_effective,'%Y-%m-%d') as next_payment,
        	(   select es2.date_effective
	            from event_schedule es2
                join event_type as et2 using (event_type_id)
            	where es2.application_id = es.application_id
            	and es2.event_status = 'scheduled'
            	and es2.event_schedule_id >es.event_schedule_id
            	and et2.name_short = 'payment_service_chg'
            	and es2.origin_group_id > 0
            	order by es2.date_event asc
            	limit 1
        	) as following_payment
	from application as a
    join company as c using (company_id)
	join event_schedule as es using (application_id)
    join event_type as et using (event_type_id)
	left join application_status_flat as asf using (application_status_id)
	where a.company_id in ($company_list)
    and (asf.level2 = 'customer' or asf.level1 = 'customer' or asf.level3 = 'customer')
	and a.income_frequency in ('weekly', 'bi_weekly')
	and es.event_status = 'scheduled'
	and et.name_short = 'payment_service_chg'
	and es.origin_group_id > 0
	group by application_id
	order by next_payment, following_payment asc ";

	$st = $db->query($sql);
	return $st->fetchAll(PDO::FETCH_OBJ);
}

?>
