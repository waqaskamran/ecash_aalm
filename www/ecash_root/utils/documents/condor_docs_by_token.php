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

$documents = eCash_Document_DeliveryAPI_Condor::Prpc()->Get_Template_Names();

$token_docs = array();

$tokens = array();

foreach($documents as $doc_name) {
	$ttokens = eCash_Document_DeliveryAPI_Condor::Prpc()->Get_Template_Tokens($doc_name);
	$new_tokens = array_diff($ttokens, $tokens);
	$tokens = array_merge($tokens, $new_tokens);

	foreach ($tokens as $token) {
		if(in_array($token, $ttokens) && (!is_array($token_docs[$token]) || !in_array($doc_name, $token_docs[$token]))) {
			$token_docs[$token][] = $doc_name;
		}
	}
	
}

$fp = fopen('php://stdout','w');

fputcsv($fp, array("Token Name", "Docs Using Token"));

foreach($token_docs as $token => $docs) {
	fputcsv($fp,array("%%%{$token}%%%", implode("\n",$docs)));
}

fclose($fp);
