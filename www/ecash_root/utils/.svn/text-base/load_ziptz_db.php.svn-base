#!/usr/bin/php
<?php

/**
 * retrieves the Zip/TZ data into db
 */

if($argc > 1) { $file = $argv[2]; $company = strtolower($argv[1]);}
else Usage($argv);

require_once("../www/config.php");
define ('CUSTOMER_LIB', BASE_DIR . "customer_lib/{$company}/");
require_once("mini-server.class.php");
require_once(COMMON_LIB_DIR."mysqli.1.php");
require_once(SQL_LIB_DIR . "get_mysqli.func.php");
require_once(LIB_DIR . 'common_functions.php');

if (!is_readable(realpath($file))) {
	echo "File not found\n";
	Usage($argv);
}

$map = array("ZIP_CODE" => "zip_code",
			 "CITY" => "city",
			 "STATE" => "state",
			 "TIME_ZONE" => "tz",
			 "DAY_LIGHT_SAVING" => "dst");

$rmap = array_flip($map);
			 
$fh = fopen(realpath($file),"r");

$fields = fgetcsv($fh);

$fields = array_flip($fields);

$my = get_mysqli();

try {
	$my->Start_Transaction();
	
	$my->Query("Delete from zip_tz");
	
	for ($i = 1 ; ($row = fgetcsv($fh)) !== FALSE ; $i++) {
		if ( !isset($row[$fields['ZIP_CODE']]) ||
			!isset($row[$fields['CITY']]) ||
			!isset($row[$fields['STATE']]) ||
			!isset($row[$fields['TIME_ZONE']]) ||
			!isset($row[$fields['DAY_LIGHT_SAVING']])
			) {
				var_dump($fields);
				throw new Exception("Invalid CSV Row: " . var_export($row,true));
			}
		$insrow[] = "('".$row[$fields['ZIP_CODE']]."','".$row[$fields['CITY']]."','".$row[$fields['STATE']]."',".$row[$fields['TIME_ZONE']].",'".$row[$fields['DAY_LIGHT_SAVING']]."')";
//		$my->Query("INSERT IGNORE INTO zip_tz VALUES ('".$row[$fields['ZIP_CODE']]."','".$row[$fields['CITY']]."','".$row[$fields['STATE']]."',".$row[$fields['TIME_ZONE']].",'".$row[$fields['DAY_LIGHT_SAVING']]."')");

		if(!($i % 100)) {
			$my->Query("INSERT IGNORE INTO zip_tz VALUES " . implode(",",$insrow));
			$insrow = array();
		}
		
	}
	
	$my->Query("INSERT IGNORE INTO zip_tz VALUES " . implode(",",$insrow));
	
} catch (Exception $e) {
	var_dump($e);
	$my->Rollback();
//	var_dump($insrow);
}

function Usage($argv)
{
        echo "Usage: {$argv[0]} [ic|clk] [csv file]\n";
        exit;
}