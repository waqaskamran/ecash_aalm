#!/usr/bin/php
<?php


function Usage()
{
	echo "Usage: {$argv[0]} [company] \n";
	exit;
	
}

require_once(dirname(__FILE__)."/../../www/config.php");
require_once LIB_DIR . "Document/Document.class.php";
require_once eCash_Document_DIR . "DeliveryAPI/Condor.class.php";

if ($argc < 2) {
	Usage();
}

Set_Company_Constants($argv[1]);

$tokens = eCash_Document_DeliveryAPI_Condor::Prpc()->Get_Tokens();

$fp = fopen('php://stdout','w');

fputcsv($fp, array("Token", "Description"));

foreach($tokens as $line) {
	fputcsv($fp,(array) $line);
}

fclose($fp);
