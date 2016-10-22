<?php

/**
 * OLP API RPC Server
 *
 * @copyright Copyright &copy; 2009 The Selling Source, Inc.
 * @package
 * @author Bill Szerdy <bill.szerdy@sellingsource.com>
 * @created Mar 23, 2009
 * @version $Revision$
 */

require_once(dirname(__FILE__) . '/../config.php');
require_once(LIB_DIR . 'common_functions.php');
require_once(COMMON_LIB_DIR . 'security.6.php');
require_once(COMMON_LIB_ALT_DIR . 'prpc2/server.php');
require_once(COMMON_LIB_ALT_DIR . 'prpc2/proxy.php');

$company_name	= (get_magic_quotes_gpc()) ? stripslashes($_REQUEST['company']) : $_REQUEST['company'];
$user_name      = empty($_SERVER['PHP_AUTH_USER']) ? '' : $_SERVER['PHP_AUTH_USER'];
if(get_magic_quotes_gpc())
	$user_name  = stripslashes($user_name);
$password       = empty($_SERVER['PHP_AUTH_PW']) ? '' : $_SERVER['PHP_AUTH_PW'];
if(get_magic_quotes_gpc())
	$password   = stripslashes($password);

$failover_config = new DB_FailoverConfig_1();
$failover_config->addConfig(ECash::getConfig()->DB_API_CONFIG);
$failover_config->addConfig(ECash::getConfig()->DB_SLAVE_CONFIG);
$failover_config->addConfig(ECash::getConfig()->DB_MASTER_CONFIG);
$failover_db = $failover_config->getConnection();

$host = $_SERVER['HTTP_HOST'];
$https = !empty($_SERVER['HTTPS']);
$soap_path = ($https ? 'https://' : 'http://') .$host. '/api/nada.php?company=' . urlencode($company_name);
$xsdPath = ($https ? 'https://' : 'http://') .$host. '/api/nada.php?xsd&company=' . urlencode($company_name);



if(array_key_exists('wsdl', $_GET))
{
	include("nada_wsdl.php");
}
elseif(array_key_exists('xsd', $_GET))
{
	include "nada_xsd.php";
}
else
{
	$server = new Server_Web(NULL);

	$company = ECash::getFactory()->getModel('Company');

	if (!$company->loadBy(array('name_short' => $company_name)))
	{
		throw new Exception('Unknown company name short');
	}

	$company_id = $company->company_id;

	$server->Set_Company($company_id);

	$security = new ECash_Security(SESSION_EXPIRATION_HOURS);

	$flag = true;
	if ($security->loginUser('eCash_RPC', $user_name, $password))
	{
		// Ok wtf
		$agent_id  = $security->getAgent()->getModel()->agent_id;

		// Yes I did it
		$system_id = 4;

		$acl = ECash::getACL($failover_db);
		$acl->setSystemId($system_id);
		$acl->fetchUserACL($agent_id, $company_id);

		if ($acl->Acl_Access_Ok('olp_api', $company_id))
		{
			$server->acl = $acl;
			$api = new ECash_NADAAPI_API($failover_db);
			$soap = new SoapServer($soap_path."&wsdl");
			$soap->setObject($api);
			$soap->handle();
			$flag = false;
			exit;
		}	
	}
	
	if ($flag)
	{
		
		header("Content-type: text/xml", TRUE);
		header('WWW-Authenticate: Basic realm="Ecash NADA Soap API"');
		header('HTTP/1.0 401 Unauthorized');
		echo 'This account does not have access to the NADA soap api.';
		exit;
	}
}