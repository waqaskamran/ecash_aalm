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
require_once("mini-server.class.php");
require_once(COMMON_LIB_DIR."mysqli.1.php");
require_once(LIB_DIR . 'common_functions.php');

// Map Cashline directories to company name
$company_map = array ( 	'ca'  => 'nevis', // Ameriloan
						'd1'  => 'nms', // Fast Cash 
						'pcl' => 'pcl', //Preferred Cash Loans
						'ucl' => 'ucl', // United Cash Loan
						'ufc' => 'usf'); // US Fast Cash

if($argc > 2) { $run_date = $argv[2]; $company = strtolower($argv[1]);}
else Usage($argv, $company_map);

if(! empty($company_map[$company]))
{
	$apps = Lookup_Cashline_Dump_Files($company_map[$company], $run_date);
	$missing = Find_Missing_Applications($company, $run_date, $apps);
	
	if(count($missing) > 0) {
		echo "Found " . count($missing) . " apps that did not have Cashline files created for $company on $run_date\n";
		foreach($missing as $application_id) {
			echo "$application_id \n";
		}
	} else {
		echo "No missing apps found for $company on $run_date\n";
	}
	die();
} else {
	echo "Invalid company: $company!\n";
	Usage($argv, $company_map);
}

function Usage($argv, $company_map)
{
	foreach($company_map as $comp => $dir) {
		$available .= "$comp ";
	}
	
        echo "\nUsage: {$argv[0]} [company_abbrev] [date]\n";
        echo " - company_abbrev = one of the following: $available \n";
        echo " - date           = example: ".date('Y-m-d')."\n\n";
        exit;
}
	
function Lookup_Cashline_Dump_Files($company, $date)
{
	$datestamp = strtotime($date);
	$day   = date('d', $datestamp);
	$month = date('m', $datestamp);
	$year  = date('Y', $datestamp);
		
	$basedir = "/var/cashline/archive/$company/$year/$month/$day/";
	$file_pattern = "/\d{6,8}/"; // 40969486.txt
	$applications = array();
	
	if((file_exists($basedir) && $dh = opendir($basedir)))
	{
		while(false !== ($file = readdir($dh)))
		{
			if(preg_match($file_pattern, $file, $matches))
			{
				$applications[] = $matches[0];
			}
		}
		closedir($dh);
	} else {
		die("Error!  Could not open $basedir\n");
	}
	
	return $applications;
}

function Find_Missing_Applications($company, $date, $applications)
{
	$mysql = get_mysqli();
	
	$base_date  = date('Ymd', strtotime($date));
	$start_date = "$base_date"."000000";
	$end_date   = "$base_date"."235959";
	
	$sql = "
	SELECT
		DISTINCT application_id 
	FROM
		status_history AS sh 
		JOIN company AS c ON (c.company_id = sh.company_id) 
	WHERE
		    sh.date_created BETWEEN '$start_date' AND '$end_date'
		AND c.name_short = '$company' 
		AND sh.application_status_id = 20" ;
	
	if(count($applications) > 0) {
		$sql .= "		AND application_id NOT IN ( ".implode(',', $applications) ." )";
	}
	
	$apps = array();
	$result = $mysql->Query($sql);
	while($row = $result->Fetch_Object_Row())
	{
		$apps[] = $row->application_id;
	}
	
	return $apps;
}