<?php

/**
 * Checks various processes that are essential for operation
 * for eCash 3.0 -- ALL companies.
 *
 * @author Marc Cartright
 * @author Jason Schmidt
 * @author Brian Ronald
 */

try
{
	require_once(dirname(__FILE__)."/../www/config.php");

	if(ECash::getConfig()->NOTIFICATION_ERROR_RECIPIENTS === NULL)
		throw new Exception("No Notification List defined!");

	require_once(SQL_LIB_DIR . "util.func.php");
	require_once(LIB_DIR. "common_functions.php");

	$db = ECash::getMasterDb();

	$holidays = Fetch_Holiday_List();
	$HAVE_FAILURES = FALSE;

	$today = date("Y-m-d");

	// The processes and their parameters
	// Period and Time  = Run Before Time, Run After Time
	$check_processes = array(
			'ach_returns' 		=> array('time' 	=> '10:00 am',
										 'period' 	=> 'after',
										 'run_holidays' => false,
										 'run_weekends' => false,
										 'alternate_process' => null,
										 'notification_list' => ECash::getConfig()->NOTIFICATION_ERROR_RECIPIENTS,
										 'error_message' => 'The ACH Returns from ' . date('m/d/Y') . 'have not processed as of '. date('H:i:s m/d/Y') .'.',
										 'subject_label' => 'ACH Returns'),
			'ach_corrections' 	=> array('time' 	=> '10:00 am',
										 'period' 	=> 'after',
										 'run_holidays' => false,
										 'run_weekends' => false,
										 'alternate_process' => null,
										 'notification_list' => ECash::getConfig()->NOTIFICATION_ERROR_RECIPIENTS,
										 'error_message' => 'The ACH Corrections from ' . date('m/d/Y') . ' have not processed as of '. date('H:i:s m/d/Y') .'.',
										 'subject_label' => 'ACH Corrections'),
			'ach_reschedule' 	=> array('time' 	=> '10:00 am',
										 'period' 	=> 'after',
										 'run_holidays' => false,
										 'run_weekends' => false,
										 'alternate_process' => null,
										 'notification_list' => ECash::getConfig()->NOTIFICATION_ERROR_RECIPIENTS,
										 'error_message' => 'The ACH Rescheduling for ' . date('m/d/Y') . ' has not run as of '. date('H:i:s m/d/Y') .'.',
										 'subject_label' => 'ACH Rescheduling'),
			'qc_process_returns' => array('time' 	=> '10:00 am',
										 'period' 	=> 'after',
										 'run_holidays' => false,
										 'run_weekends' => false,
										 'alternate_process' => 'qc_upload',
										 'notification_list' => 'rebel75cell@gmail.com, brian.gillingham@gmail.com, randy.klepetko@sbcglobal.net, brronald@gmail.com,', //, crystal@fc500.com, alindsay@fc500.com, ndempsey@fc500.com, sboch@fc500.com',
										 'error_message' => 'The QuickCheck return file for ' . date('m/d/Y') . ' from US Bank has not been received as of '. date('H:i:s m/d/Y') .'.',
										 'subject_label' => 'Quick Check Returns'),
			'nightly' 			=> array('time' 	=> '9:00 pm',
										 'period' 	=> 'after',
										 'run_holidays' => true,
										 'run_weekends' => true,
										 'alternate_process' => null,
										 'notification_list' => ECash::getConfig()->NOTIFICATION_ERROR_RECIPIENTS,
										 'error_message' => 'The nightly events processing for ' . date('m/d/Y') . ' has not run as of '. date('H:i:s m/d/Y') .'.',
										 'subject_label' => 'Nightly'));

	$is_holiday = in_array($today, $holidays);
	$parts = getdate(strtotime($today));
	$is_weekend = (($parts["wday"] == 0) || ($parts["wday"] == 6));

	$eligible_companies = Get_Eligible_Companies($db);
	// If there are no eligible companies, just exit;
	if(count($eligible_companies) === 0)
		exit(0);

	foreach($check_processes as $process => $parameters)
	{
		$time          = $parameters['time'];
		$period        = $parameters['period'];
		$run_holidays  = $parameters['run_holidays'];
		$run_weekends  = $parameters['run_weekends'];
		$error_message = $parameters['error_message'];
		$alternate_process = $parameters['alternate_process'];
		$subject_label = $parameters["subject_label"];

		// Force the defined recipient list if we're running in RC
		if(EXECUTION_MODE === 'RC') {
			$notification_list = ECash::getConfig()->NOTIFICATION_ERROR_RECIPIENTS;
		} 
		else 
		{
			$notification_list = $parameters['notification_list'];
		}

		// If it's a weekend or holiday and we're not supposed to run
		// then skip this process.
		if($run_holidays === false && $is_holiday === true)
			continue;
		if($run_weekends === false && $is_weekend === true)
			continue;

		// Should we run now?
		$run = false;
		if($period == 'before') {
			// If the the current time is before the time period
			if (strtotime($time) > time())
				$run = true;
		} 
		else 
		{
			// If the current time is after the time period
			if (strtotime($time) < time())
				$run = true;
		}

		// If we're supposed to run, check for completed
		// instances of $process for $today and then
		// we'll check to see if our eligible companies
		// are listed.
		if($run) {
			$process_companies = array();

			$addtl_sql = "";

			if($alternate_process != NULL)
			{
				$addtl_sql = "OR step = '{$alternate_process}'";
			}

			$sql = "
				SELECT company_id, COUNT(*)
				FROM process_log
				WHERE business_day = '{$today}'
				AND (step = '{$process}'
				$addtl_sql)
				AND state = 'completed'
				GROUP BY company_id
			";
			$process_companies = $db->querySingleColumn($query);

			foreach ($eligible_companies as $e) {
				if(! in_array($e->company_id, $process_companies))
				{
					$subject = empty($subject_label) ? $process : $subject_label;
					$body = "eCash 3.0: The {$subject} Process may have failures for {$e->name}!"
						. "\n\n" . $error_message;
					Email_Report($notification_list, $body);
					//echo $str . "\n";
					$HAVE_FAILURES = TRUE;
				}
			}
		}

	}

	if($HAVE_FAILURES == TRUE) {
		exit(2);
	} 
	else 
	{
		exit(0);
	}

}
catch (Exception $e) {
	echo "{$argv[0]}: Error occurred: {$e->getMessage()}\nTrace:\n{$e->getTraceAsString()}\n";
	exit(3);
}

exit(0);


function Email_Report($recipients, $body) {
	require_once(LIB_DIR . '/Mail.class.php');
	return eCash_Mail::sendExceptionMessage($recipients, $body);
}

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
			c.name_short NOT LIKE '%_a'	";

	$result = $db->query($sql);
	return $result->fetchAll(PDO::FETCH_OBJ);
}

?>
