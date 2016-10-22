#!/usr/bin/php
<?php
/*  Usage example:  php -f ecash_data_utility.php  */

/*
 * Before using this utility, verify all of the defines below.  Only a few precautions 
 * are made to verify data is not modified on the wrong servers.  Be sure to have a full 
 * set of reference data loaded on the local DB before attempting to run the 'fund' command.
 */

/* DB3 Live  */
define("DB_HOST", 'writer.ecash3clk.ept.tss');
define("DB_NAME", 'ldb');
define("DB_USER", 'ecash');
define("DB_PORT", '3306');
define("DB_PASS", 'ugd2vRjv');

require_once(dirname(__FILE__)."/../www/config.php");
require_once('mysqli.1.php');

$mysqli = new MySQLi_1(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if($argc < 2) {
	die("No report name specified!\n");
}
$report_name = $argv[1];

if($argc > 2) {
	$date = $argv[2];
}
$timezone = "US/Central";
Set_Server_Timezone($mysqli, $timezone);

//define("RECIPIENT", 'jcorpus@fc500.com, thonors@fc500.com, nterzis@fc500.com, normatu@fc500.com, ndempsey@fc500.com, pgreenlee@NIDFCIC.com, brian.ronald@sellingsource.com, andy.roberts@sellingsource.com, mike.lively@sellingsource.com');
//define("RECIPIENT", 'mike.lively@sellingsource.com');
define("RECIPIENT", 'rebel75cell@gmail.com, brian.gillingham@gmail.com, randy.klepetko@sbcglobal.net');

if($report_name != NULL)
{
	if(empty($date))
	{
		$date = date('Ymd');
		$previous_day = date("Ymd", strtotime('Yesterday'));
	}
	else
	{
		$previous_day = $date;
	}
	
	//echo "Processing Report {$report_name}\n";
	switch($report_name)
	{
		case 'current_day':
			$report = Current_Day_Funded_Report($mysqli, $date);
			$report_name = "Current Day Funded Report as of  " . date("H:i:s") ." PST / " . date("m-d-Y");
			$template = "ECASH_CURRENT_DAY_FUNDED_REPORT";
			$report_filename = "Current_Day_Funded_Report_" . date("YmdHis") . ".csv";
			break;
		case 'previous_day':
			$report = Funded_Previous_Day_Report($mysqli, $previous_day);
			$report_name = "Applications Funded Previous Business Day Report for " . date("m-d-Y", strtotime($previous_day));
			$template = "ECASH_PREVIOUS_DAY_FUNDED_REPORT";
			$report_filename = "Applications_Funded_Previous_Day_Report_" . $previous_day . ".csv";
			break;
		default:
			echo "Can't find report.\n";
			exit;
			
	}

	//Output_Report($report);
	$attachment = new stdClass();
	$attachment->contents = Format_Report($report);
	$attachment->filename = $report_filename;
	
//	Email_Report(RECIPIENT, "reports@nowhere.com", $report_name, "Open the attached file(s) with Excel.", $attachment);
	TrendX_Email_Report(RECIPIENT, "reports@nowhere.com", $report_name, $template, $attachment);
	
}

exit;

function TrendX_Email_Report($to, $from, $subject, $template, $attachment)
{
	require_once("tx/Mail/Client.php");
	
	$tx = new tx_Mail_Client();

	$attachment = array( array(	'method' => 'ATTACH',
								'filename' => basename($attachment->filename),
								'mime_type' => 'application/csv',
								'file_data' => gzcompress($attachment->contents),
								'file_data_size' => strlen($attachment->contents),
								)
						);
						
	$to_ary = explode(",",$to);
	foreach ($to_ary as $recip) {
		$r = $tx->sendMessage('live', $template, trim($recip), NULL, array(	'source' => 'ecash', //REQUIRED FOR MARKETING
																			'signup_date' => date('Y-m-d H:i:s'), //REQUIRED FOR MARKETING
																			'ip_address' => '127.0.0.1', //REQUIRED FOR MARKETING
																			'subject' => $subject
																			),
																	$attachment );
																	
	}
	
	
}

function Email_Report($to, $from, $subject, $body, $attachment)
{

	include_once('mime_mail.1.php');
	
	$mailer = new mime_mail();

	$mailer->to = $to;
	$mailer->from = $from;
	$mailer->subject = $subject;
	$mailer->body = $body;

	$mailer->add_attachment($attachment->contents, basename($attachment->filename));

	$message = $mailer->get_mail();

	$mailer->send();

}


function Format_Report($report)
{
	$output = "";
	foreach($report as $line)
	{
		$output .= $line . "\n";
	}
	return($output);
}

function Set_Server_Timezone($mysqli, $timezone)
{
	$set = "set time_zone = '" . $timezone."'";
    $mysqli->Query($set);

}

function Funded_Previous_Day_Report($mysqli, $date)
{
	$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
-- Applications Funded Previous Business Day
SELECT  a.application_id AS `App ID`,
		a.name_last as `Last Name`,
		a.name_first as `First Name`,
        a.street AS `Home Street`,
        a.city AS `Home City`,
        a.county AS `Home County`,
        a.state AS `Home State`,
        a.zip_code AS `Home Zip Code`,
        CONCAT(SUBSTR(a.ssn, 1,3), '-', SUBSTR(a.ssn, 4,2), '-', SUBSTR(a.ssn, -4)) AS `SSN`,
        a.phone_home AS `Home Phone`,
        a.employer_name AS `Employer`,
        (CASE WHEN a.phone_work_ext IS NOT NULL
              THEN concat(a.phone_work, ' ext. ', a.phone_work_ext)
              ELSE a.phone_work
         END) as `Employer Phone`,
        a.income_monthly AS `Income`,
        (CASE WHEN a.paydate_model = 'dwpd'
              THEN 'Bi-Weekly'
              WHEN a.paydate_model = 'dw'
              THEN 'Weekly'
              WHEN a.paydate_model = 'dmdm'
              THEN 'Twice Monthly'
              WHEN a.paydate_model = 'wwdw'
              THEN 'Twice Monthly'
              WHEN a.paydate_model = 'dm'
              THEN 'Monthly'
              WHEN a.paydate_model = 'wdw'
              THEN 'Monthly'
              WHEN a.paydate_model = 'dwdm'
              THEN 'Monthly'
              ELSE 'Other'
         END) as `Pay Period`,
        a.bank_name AS `Bank Name`,
        CONCAT(' ', a.bank_account) AS `Bank Account Number`,
        a.bank_aba AS `Bank ABA`,
        a.fund_actual AS `Principal Amount`,
        a.date_first_payment AS `Next Due Date`,
        a.email AS `Email Address`,
        a.ip_address AS `IP Address`

FROM    status_history sh,
        application a
WHERE   sh.date_created BETWEEN {$date}000000
                            AND {$date}235959
AND     sh.application_status_id = 121 -- Pre-Fund
AND     a.application_id = sh.application_id
AND     a.is_react = 'no'
	";
	$result = $mysqli->Query($query);
	$report = array();
	
	$fields = $result->Get_Fields();
	$line = "";
	foreach($fields as $heading)
	{
		$line .= '"' . $heading->name . '",';
	}
    $line = rtrim($line, ",");
    $report[] = $line;
	
	while($row = $result->Fetch_Array_Row(MYSQLI_NUM))
	{
		$line = "";
		foreach($row as $column)
		{
			$line .= '"' . $column . '",';
		}
		$line = rtrim($line, ",");
		//$line .= "\n";
		$report[] = $line;
	}

	return($report);
}


function Current_Day_Funded_Report($mysqli, $date)
{
	$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
-- Current Day Funded Report
SELECT  a.application_id AS `App ID`,
		a.name_last as `Last Name`,
		a.name_first as `First Name`,
        a.street AS `Home Street`,
        a.city AS `Home City`,
        a.county AS `Home County`,
        a.state AS `Home State`,
        a.zip_code AS `Home Zip Code`,
        CONCAT(SUBSTR(a.ssn, 1,3), '-', SUBSTR(a.ssn, 4,2), '-', SUBSTR(a.ssn, -4)) AS `SSN`,
        a.phone_home AS `Home Phone`,
        a.employer_name AS `Employer`,
        (CASE WHEN a.phone_work_ext IS NOT NULL
              THEN concat(a.phone_work, ' ext. ', a.phone_work_ext)
              ELSE a.phone_work
         END) as `Employer Phone`,
        a.income_monthly AS `Income`,
        (CASE WHEN a.paydate_model = 'dwpd'
              THEN 'Bi-Weekly'
              WHEN a.paydate_model = 'dw'
              THEN 'Weekly'
              WHEN a.paydate_model = 'dmdm'
              THEN 'Twice Monthly'
              WHEN a.paydate_model = 'wwdw'
              THEN 'Twice Monthly'
              WHEN a.paydate_model = 'dm'
              THEN 'Monthly'
              WHEN a.paydate_model = 'wdw'
              THEN 'Monthly'
              WHEN a.paydate_model = 'dwdm'
              THEN 'Monthly'
              ELSE 'Other'
         END) as `Pay Period`,
        a.bank_name AS `Bank Name`,
        a.bank_account AS `Bank Account Number`,
        a.bank_aba AS `Bank ABA`,
        a.email AS `Email Address`,
        a.ip_address AS `IP Address`,
        sh.date_created AS `Timestamp`
FROM    status_history sh,
        application a
WHERE   sh.date_created BETWEEN {$date}000000
                            AND {$date}235959
AND     sh.application_status_id = 121 -- Pre-Fund
AND     a.application_id = sh.application_id
AND     a.is_react = 'no'
	";
	$result = $mysqli->Query($query);
	$report = array();
	
	$fields = $result->Get_Fields();
	$line = "";
	foreach($fields as $heading)
	{
		$line .= '"' . $heading->name . '",';
	}
    $line = rtrim($line, ",");
    $report[] = $line;
	
	while($row = $result->Fetch_Array_Row(MYSQLI_NUM))
	{
		$line = "";
		foreach($row as $column)
		{
			$line .= '"' . $column . '",';
		}
		$line = rtrim($line, ",");
		//$line .= "\n";
		$report[] = $line;
	}

	return($report);
}

?>
