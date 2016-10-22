<?php

require_once realpath(dirname(__FILE__) . "/..") . "/config.php";

if (strtolower(EXECUTION_MODE) != 'live') 
{
	ini_set("soap.wsdl_cache_enabled", "0"); 
	ini_set("soap.wsdl_cache", "0"); 
}

$client = new SoapClient("http://ecash_rpc:ecash@" . $_SERVER['SERVER_NAME'] . "/rpc/soap.php?rpc_class=eCash_RPC_Interface_Dummy&company=ufc&wsdl", 
						array(	"login" => "ecash_rpc",
								"password" => "ecash",
								"trace" => true,		
								"exceptions" => true
								));					

var_dump($client->reflect('reflect this string'));

var_dump($client->increment());
var_dump($client->increment());
var_dump($client->increment());

var_dump($client);