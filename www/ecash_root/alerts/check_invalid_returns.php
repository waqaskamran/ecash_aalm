<?php
/**
 * This alert is used to look for Transaction Register items that are still 
 * marked as 'pending' but have a corresponding ACH item that is marked as
 * 'returned'.  The original issue comes from Impact #8264, where transactions
 * for applications with the PDL application tag were sent out from the original
 * Impact company.  These applications were moved to the Impact PDL company
 * but the transactions returns came back to the original company.  The ACH
 * items were updated, but the transactions were not.
 * 
 * @author Brian Ronald <brian.ronald@sellingsource.com>
 * @package Alerts
 */

try {
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
	
	$transactions   = array();
	
	$query = "
		SELECT
			tr.application_id,
			tr.company_id,
			a.company_id as app_company_id,
			ar.company_id as returned_company_id,
			tr.transaction_register_id,
			tr.date_effective,
			ach.ach_id,
			DATE_FORMAT(ar.date_created, '%Y-%m-%d') as return_date
		FROM transaction_register AS tr
		JOIN ach USING (ach_id)
		JOIN application AS a ON a.application_id = tr.application_id
		LEFT JOIN ach_report AS ar ON ar.ach_report_id = ach.ach_report_id
		WHERE tr.transaction_status = 'pending'
		AND   ach.ach_status = 'returned'
		ORDER BY tr.company_id, tr.transaction_register_id ";
	$st = $db->query($query);

	while ($row = $st->fetch(PDO::FETCH_OBJ))
	{
		$transactions[] = $row;
	}

	if(count($transactions) === 0) 
	{
		// All quiet on the western front
		exit(0);
	}

	$subject = "eCash: Found " . count($transactions) . " unfailed returns.";
	echo $subject . "\n";
	Email_Report($notify_list, $subject, $transactions);
	exit($return_value);

} catch(Exception $e) {
	echo __FILE__ . " :: Unknown error occurred.\n";
	echo $e->getMessage();
	echo $e->getTraceAsString();
	exit(3);
}

function Email_Report($recipients, $body, $results)
{
	require_once(LIB_DIR . '/CsvFormat.class.php');

	$csv = CsvFormat::getFromArray(array(
		'Application ID',
		'Transaction Company ID',
		'App Company ID',
		'Returned Company ID',
		'Transaction Register ID',
		'Date Effective',
		'ACH ID',
		'ACH Return Date'));

	foreach ($results as $result)
	{
		$csv .= CsvFormat::getFromArray(array(
			$result->application_id,
			$result->company_id,
			$result->app_company_id,
			$result->returned_company_id,
			$result->transaction_register_id,
			$result->date_effective,
			$result->ach_id,
			$result->return_date));
	}

	$attachments = array(
		array(
			'method' => 'ATTACH',
			'filename' => 'alert_events.csv',
			'mime_type' => 'text/plain',
			'file_data' => gzcompress($csv),
			'file_data_length' => strlen($csv)));

	require_once(LIB_DIR . '/Mail.class.php');
	return eCash_Mail::sendExceptionMessage($recipients, $body, array(), $attachments);
}

?>
