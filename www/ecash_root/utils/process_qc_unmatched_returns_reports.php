#!/usr/bin/php
<?php
/*
 * This script will view a customer's schedule or optionally view the
 * results of an Analyze_Schedule() as 'status'.
 *
 * This utility uses the current configuration based on the BASE_DIR/www/config.php file.
 *
 * Before using this utility, verify all of the defines below.  Only a few precautions
 * are made to verify data is not modified on the wrong servers.
 */

require_once("../www/config.php");
define ('CUSTOMER_LIB', BASE_DIR . "customer_lib/clk/");
require_once(COMMON_LIB_DIR."mysqli.1.php");
require_once(LIB_DIR . 'common_functions.php');
require_once(SQL_LIB_DIR . "scheduling.func.php");

define('DEBUG', FALSE); 
define('REPORT_MODE', TRUE);
define('USE_HTML_LINKS', FALSE);
define('INCLUDE_RETURNED_ITEMS', FALSE);

if($argc > 3) 
{ 
	$process = strtolower($argv[1]);
	$company_id = $argv[2]; 
	$input_file = $argv[3]; 
	if($argc >4 )
	{
		$threshold = $argv[4];
	}
	else
	{
		$threshold = 2;
	}
	define('THRESHOLD', $threshold);
}
else
{ 
	Usage($argv);
}

if(in_array($process, array('biweekly','full')))
{
	switch($process) 
	{
		case 'biweekly':
			define('FIX_DATES', FALSE); // Dates are in MMDDYYYY
			// The standard bi-weekly file format
			$file_format = array ('clk_acct_number','gen_date','aba','acct_number','check_number','amount','return_code','batch_date');
			break;
		case 'full':
			define('FIX_DATES', TRUE); // Dates are in MMDDYYYY
			// The large Fast_Cash_Analysis File Format
			$file_format = array ('clk_acct_number','batch_date','posting_date','aba','acct_number','check_number','amount','return_code','return_reason','reporting');
			break;
	}

	Process_File($company_id, $input_file, $file_format);

}
else
{
        Usage($argv);
}

exit;

function Usage($argv)
{
        echo "\nUsage: {$argv[0]} [biweekly|full] [company_id] [input] {threshold}\n";
        echo " - biweekly|full = Report to process is Monthly of a Full report.\n";
        echo " - company_id    = The id of the company to process\n";
        echo " - input         = The file to read in\n";
        echo " - threshold     = Min number of columns to match (default 2)\n";
        exit;
}

function Process_File($company_id, $filename, $file_format)
{
	$mysqli = MySQLi_1e::Get_Instance();

	// For Reports
	$report_append_heading = array('application_id','ecld_id','returned','amount_match','aba_match','account_match','batch_date_match','check_number_match', 'score');

	$file_contents = file($filename);
	$checks = Parse_CSV_File($file_contents, $file_format);
	$x = 1; $total = count($checks); $found_checks = array();
	if (REPORT_MODE === FALSE )echo "Processing File: $filename\n";
	if (REPORT_MODE === FALSE )echo "Total of $total checks\n";
	
	// Display Heading Line
	if(REPORT_MODE === TRUE)
	{
		$str = '';
		foreach($file_format as $heading) {
			$str .= "$heading,";
		}
		foreach($report_append_heading as $heading) {
			$str .= "$heading,";
		}
		$str = rtrim($str, ',');
		echo $str . "\n";
	}
	
	foreach ($checks as $c)
	{
		// First line is the heading.  Skip.
		if($x == 1) {
			$x++;
			continue;
		}
		
		$c['issue_number'] = $x;
		$c['matches'] = array();

		$c['amount'] = trim($c['amount'], '$');
		$c['aba'] = ltrim($c['aba'], '0');
		$c['aba'] = rtrim($c['aba'], '0');

		if((defined('FIX_DATES')) && (FIX_DATES === TRUE)) {
			preg_match('/(\d{2})(\d{2})(\d{4})/', $c['batch_date'], $m);
			$c['batch_date'] = date('Y-m-d', mktime(0,0,0,$m[1],$m[2],$m[3]));
		}

		$c['amount'] = intval($c['amount']);
		$c['batch_date'] = trim($c['batch_date']);
		$c['issue_number'] = str_pad($c['issue_number'],3," ", STR_PAD_LEFT);
		
		$results = Find_Check($c, $mysqli);
		$size = count($results);
		if($size == 0 && REPORT_MODE == FALSE) echo "Row: {$c['issue_number']} :: Score: $score\n";
		if(DEBUG) echo "Found $size results\n";
		foreach ($results as $r) 
		{
			if(DEBUG) echo "Issue Number: $x\n";
			$returned = false;
			$amount_match = false;
			$aba_match = false;
			$account_match = false;
			$batch_date_match = false;
			$check_number_match = false;

			$score = 0; $str = "";
			$r->bank_aba = ltrim($r->bank_aba, '0');
			$r->bank_aba = rtrim($r->bank_aba, '0');

			// If there is a return code, mark it returned
			if(DEBUG) echo "Return: " .var_export($r, true) . "\n";

			if(INCLUDE_RETURNED_ITEMS === TRUE) {
				if(! empty($r->return_reason_code)) {
					$score++; // For having the return reason code
					$returned = true;
				}
			}

			if(DEBUG) echo "Comparing {$c['batch_date']} to {$r->business_date}\n";
			if($c['batch_date'] == $r->business_date) {
				$score++;
				$str .= "Batch Date matches .. ";
				$batch_date_match = true;
			}
			if(DEBUG) echo "Comparing Check Numbers {$c['check_number']} to {$r->ecld_id}\n";
			if($c['check_number'] == $r->ecld_id) {
				$score++;
				$str .= "Check number matches .. ";
				$check_number_match = true;
			}
			if(DEBUG) echo "Comparing ABA's {$c['aba']} to {$r->bank_aba}\n";
			if($c['aba'] == $r->bank_aba) {
				$score++;
				$str .= "ABA Matches .. ";
				$aba_match = true;
			}
			if(DEBUG) echo "Comparing Acct Numbers {$c['acct_number']} to {$r->bank_account}\n";
			if($c['acct_number'] == $r->bank_account) {
				$score++;
				$str .= "Account Number Matches .. ";
				$account_match = true;
			}
			if(DEBUG) echo "Comparing Amounts {$c['amount']} to {$r->amount}\n";
			if($c['amount'] == $r->amount) {
				$score++;
				$str .= "Amounts Match .. ";
				$amount_match = true;
			}
			if(DEBUG) echo "Score: $score\n";
			if($score >= THRESHOLD) {
				$str .= "Possibly Found check # {$r->ecld_id} ... Application ID: {$r->application_id}";
				// If there are no matches, populate
				if(count($c['matches']) == 0) 
				{
					$c['matches'] = array (	'score' 		=> $score,
											'returned'		=> $returned,
											'application_id' => $r->application_id,
											'ecld_id'		=> $r->ecld_id,
											'batch_date_match' => $batch_date_match,
											'check_number_match' => $check_number_match,
											'aba_match' 	=> $aba_match,
											'account_match' => $account_match,
											'amount_match'  => $amount_match);
				}
				else 
				{ // If the previously matched score is less
					if($c['matches']['score'] < $score) 
					{
						$c['matches'] = array (	'score' 		=> $score,
												'returned'		=> $returned,
												'application_id' => $r->application_id,
												'ecld_id'		=> $r->ecld_id,
												'batch_date_match' => $batch_date_match,
												'check_number_match' => $check_number_match,
												'aba_match' 	=> $aba_match,
												'account_match' => $account_match,
												'amount_match'  => $amount_match);
					} // If the score is the same but the app_id or check number is newer ...  
					else if ($c['matches']['score'] == $score) {
						if(($r->ecld_id > $c['matches']['ecld_id']) || ($r->application_id > $c['matches']['application_id_id']))
						{
							$c['matches'] = array (	'score' 		=> $score,
													'returned'		=> $returned,
													'application_id' => $r->application_id,
													'ecld_id'		=> $r->ecld_id,
													'batch_date_match' => $batch_date_match,
													'check_number_match' => $check_number_match,
													'aba_match' 	=> $aba_match,
													'account_match' => $account_match,
													'amount_match'  => $amount_match);
						}
					}
				}
				if (REPORT_MODE === FALSE ) echo "Row: {$c['issue_number']} :: Score: $score :: $str\n";
				$found_checks[] = $c['issue_number'];
			}
		}

		if(REPORT_MODE === TRUE)
		{
			// The CSV itself
			$row = '';
			foreach($file_format as $idx) {
				switch($idx) 
				{
					case 'batch_date' :
						if(FIX_DATES) {
							$row .= date('mdY', strtotime($c[$idx])) . ",";
						} else {
							$row .= $c[$idx] . ',';	
						}
						break;
					case 'aba' :
					case 'acct_number' :
						if($c[$idx] == '') {
							$row .= '0,';
						} else {
							$row .= $c[$idx] . ',';
						}
						break;
					case 'amount' :
						$row .= '$' . floatval($c[$idx]) . ",";	
						break;
					default:
						$row .= $c[$idx] . ',';
						break;
				}
			}

			// The Additional data we tag
			foreach($report_append_heading as $heading) {
				if($c['matches'][$heading] === true) {
					$row .= 'Y,';
				} else if ($c['matches'][$heading] === false) { 
					$row .= 'N,'; 
				} else {
					if($heading == 'application_id' && USE_HTML_LINKS === true) {
						$row .= 'https://live.ecash.clkonline.com/?mode=account_mgm&action=show_applicant&application_id=';
					}
					$row .= $c['matches'][$heading] . ",";
				}
			}
			
			$row = rtrim($row, ',');
			echo $row . "\n";
		}

		$x++;
		unset($c); // Just for kicks
	}

	if(REPORT_MODE === false)
	{
		$lost_string = "";
		for ($i = 2; $i < $total; $i++) {
			if(! in_array($i, $found_checks))
				$lost_string .= "$i,";
		}
		$lost_string = rtrim($lost_string, ',');
		echo "Unable to locate checks for rows: $lost_string\n";
	}
}

function Find_Check($c, $mysqli)
{
	$ecld_id = trim($c['check_number']);
	$acct    = trim($c['acct_number']);
	$aba     = trim($c['aba']);
	$amount  = trim($c['amount']);

	if($acct != "")
	{
		$acct_sql = "OR  bank_account   LIKE  '%$acct%'";
	}
	else
	{
		$acct_sql = "";
	}
		
	$sql = "
        SELECT *
        FROM ecld
        WHERE   
        (
           bank_aba        LIKE  '%$aba%'
        $acct_sql
        OR  ecld_id         = 	 '$ecld_id'
        )
        AND                amount = '$amount' ";

	if(DEBUG) echo "$sql\n";
	$results = array();
	$result = $mysqli->Query($sql);
	while($row = $result->Fetch_Object_Row())
	{
			$results[] = $row;
	}
	
	return $results;
	
}

function Parse_CSV_File ($file, $format)
{
		
	// Split file into rows
	//$return_data_ary = explode("\n", $file);
	$return_data_ary = $file;
	//var_dump($return_data_ary);
	
	$parsed_data_ary = array();
	$i = 0;

	foreach ($return_data_ary as $line)
	{
		$line = trim($line);
		if ( strlen(trim($line)) > 0 )
		{
			$matches = array();
			preg_match_all('#(?<=^"|,")(?:[^"]|"")*(?=",|"$)|(?<=^|,)[^",]*(?=,|$)#', $line, $matches);
			$col_data_ary = $matches[0];
				
			$parsed_data_ary[$i] = array();
			foreach ($col_data_ary as $key => $col_data)
			{
				$parsed_data_ary[$i][$format[$key]] = str_replace('"', '', $col_data);
			}
			$i++;
		}
	}
	return $parsed_data_ary;
}
