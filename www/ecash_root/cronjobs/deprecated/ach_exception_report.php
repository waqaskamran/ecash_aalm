<?php

include_once("../www/config.php");
/**
 * Report some events from returns_and_corrections.php
 * 
 * Should be run after returns_and_corrections.php
 * 
 * Event 1: No pending applications found corresponding to Cashline ID from return (Mantis 2042)
 *
 */

// TO DO
// file name .csv
// add date to filename
// better filename root

// Don't need to run from ecash_engine

Report_Cashline_ID_Condition();

function Report_Cashline_ID_Condition()
{
	$log_file = "/virtualhosts/log/applog/". APPLOG_SUBDIRECTORY ."/ach/current";
	$report_file = "ach_return_anomalies_".date("mdY").".csv";
	$output_file = "/tmp/".$report_file;
	
	$fh = fopen($output_file, "w");
	$message = '';
	
	$fdata = file($log_file);
	$match = false;
	$state = "START";
	$clid = -1;
	$log_item_date = date("Y.m.d");
	//echo "Look for exceptions for log date $log_item_date\n";
	
	// The following logic should cover both RC and non-RC cases.

	$failed = 0; // Track # of failures.
	// Find the relevant lines
	foreach ($fdata as $line) 
	{		
	
		if (@preg_match("/"."Attempting to associate return item (.+) with Cashline ID (.+)"."/", $line, $matches)) 
		{
		
			echo "Attempting to associate ID $matches[2] ...\n";
			$next_clid = $matches[2];
			if ($next_clid == $clid) 
			{
				
				if ($state == "START") 
				{
					// Succeeded association for this ID, ignore subsequent match failures			
					$state = "SUCCESS";
					echo "Found a match for ID $clid\n";
				}
			}
			else 
			{
				// a new CL ID
				$clid = $next_clid;
				$state = "START";
			}
				
		}
					
		if (@preg_match("/"."ACH_REPORT_CASHLINE_ID_NOT_FOUND"."/", $line) 
			&& @preg_match("/".$log_item_date."/", $line)		
			&& $state != "SUCCESS") {
			$line2 = explode("[ACH_REPORT_CASHLINE_ID_NOT_FOUND]", $line);
			$x = explode(",", $line2[1]);
			// The fields are ID, last name, first name, return code, return description, amount, date
			// $x[6] ends in \n 
			$next_line = $x[0].",".$x[1].",".$x[2].",".$x[3].",".$x[4].",".$x[5].",".$x[6];
			fwrite($fh, $next_line);
			$message .= $next_line;
			$state = "ASSOCIATION_FAILED";
			//echo "Association failed for ID $clid\n";
			$failed++;
		}
	}
	
	// Store report file locally
	fclose($fh);
	
	// If there are no failures, do not send a report.
	if($failed === 0)
		return true;
	
	// Email to CLK
	// They want it as an attachment so it's easier to import into CSV File

	$recipients = $_SERVER['argv'][4];
	$body = 'ACH Returns: Cashline IDs not found for ' . $log_item_date . "\n"
		. 'The attached file contains the ACH return anomalies.';
	$attachments = array(
		array(
			'method' => 'ATTACH',
			'filename' => $report_file,
			'mime_type' => 'text/plain',
			'file_data' => gzcompress($message),
			'file_data_size' => strlen($message)));

	require_once(LIB_DIR . '/Mail.class.php');
	return eCash_Mail::sendExceptionMessage($recipients, $body, null, array(), $attachments);

}
?>
