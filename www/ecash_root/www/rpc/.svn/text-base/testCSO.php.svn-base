<?php

require_once realpath(dirname(__FILE__) . "/..") . "/config.php";

$test = '900036771';

$client = new SoapClient("http://ecash_api:urf1r3d@" . $_SERVER['SERVER_NAME'] . "/rpc/soap.php?rpc_class=eCash_Custom_RPC_CSO&company=mcc&wsdl", 
						array(	"login" => "ecash_api",
								"password" => "urf1r3d",
								"trace" => true,		
								"exceptions" => true
								
								));					


try {	
	echo "<hr><pre>";							
	var_dump($client->getRolloverEligible($test));
	echo "</pre>";
	echo "<hr><pre>";						
	var_dump($client->createRollover($test));
	echo "</pre>";
//var_dump($client);
	
} catch (SoapFault $e) {
		echo "</pre>";
	echo "<hr><pre>";					

	var_dump($e);
}
