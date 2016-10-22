<?php

require_once realpath(dirname(__FILE__) . "/..") . "/config.php";

//$client = new SoapClient("http://" . $_SERVER['SERVER_NAME'] . "/rpc/soap.php?rpc_class=eCash_RPC_Interface_Dummy&company=ufc&wsdl", 
//						array(	"login" => "ecash_rpc",
//								"password" => "ecash",
//								"trace" => true,		
//								"exceptions" => true
//								));
								
require_once realpath(dirname(__FILE__) . "/../../..") . "/lib5/prpc/client.php";

/**
 * If you want your class to be persistent across your calls
 * you need to specify ssid, in order for sessions to propagate properly
 */
$ssid = md5(uniqid(mktime()));

$client = new Prpc_Client("prpc://ecash_rpc:ecash@" . $_SERVER['SERVER_NAME'] . "/rpc/prpc.php?rpc_class=eCash_RPC_Interface_Dummy&company=ufc&ssid={$ssid}");						

/**
 * HTTP Auth is only supported in non-compressed mode.
 * So, Turn off compression
 */

$client->_prpc_use_pack = PRPC_PACK_NO;

var_dump($client->reflect('reflect this string'));

var_dump($client->increment());
var_dump($client->increment());
var_dump($client->increment());

var_dump($client);