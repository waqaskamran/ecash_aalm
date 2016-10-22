<?php

require_once realpath(dirname(__FILE__) . "/..") . "/config.php";

$test = '123404749';

$client = new SoapClient("http://ecash_rpc:ecash@" . $_SERVER['SERVER_NAME'] . "/rpc/soap.php?rpc_class=eCash_Custom_RPC_CallData&company=ufc&wsdl", 
						array(	"login" => "ecash_rpc",
								"password" => "ecash",
								"trace" => true,		
								"exceptions" => true
								));					

try {								
	var_dump($client->findApplications($test,'ssn'));
	var_dump($client->getBalanceInfo($test,'ssn'));
	var_dump($client->getNextDueDate($test,'ssn'));
	
} catch (SoapFault $e) {
	var_dump($client);
	var_dump($e);
}
