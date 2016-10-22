<?php

require_once(dirname(__FILE__) . '/../www/config.php');
require_once(LIB_DIR . 'common_functions.php');
require_once('general_exception.1.php');

/**
 * API Data
 */

switch (strtolower($argv[1]))
{
	case 'live':
		$hostname = 'funding.impactcashusa.com';
		$login = 'olp_api';
		$password = '28eDJsrc';
		break;
	case 'rc':
		$hostname = 'rc.ecash.aalm.justinf.tss';
		$login = 'olp_api';
		$password = '28eDJsrc';
		break;
	default:
		die("Invalid environment!");
}

$company = $argv[2];
$application_id = $argv[3];

$url = "http://$hostname/api/olp.2.php?company={$company}&user={$login}&pass={$password}";
$olp_api = new Rpc_Client_1($url);

$data = $olp_api->getDataByApplicationId($application_id);

var_dump($data);

?>
