<?php

/**
 * ECash Loan API
 *
 * @copyright Copyright &copy; 2009 The Selling Source, Inc.
 * @package
 * @author Bryan Campbell <bryan.campbell@dataxltd.com>
 */

require_once(dirname(__FILE__) . '/../config.php');
require_once(LIB_DIR . 'common_functions.php');
require_once(COMMON_LIB_DIR . 'security.6.php');

$company_name_short	= (get_magic_quotes_gpc()) ? stripslashes($_REQUEST['company']) : $_REQUEST['company'];
$username 			= $_SERVER['PHP_AUTH_USER'];
$password			= $_SERVER['PHP_AUTH_PW'];

// Build the service URL
$host = $_SERVER['HTTP_HOST'];
$https = !empty($_SERVER['HTTPS']);
$soap_url = ($https ? 'https://' : 'http://') . $host
		. '/api/loan.php?company=' . urlencode($company_name_short);

// If the WSDL was requested show it and stop processing
if (array_key_exists('wsdl', $_GET))
{
	$tokens = array('%%%soap_url%%%' => htmlentities($soap_url));
	$wsdl = file_get_contents(ECASH_SHARED_DIR.'/ECash/Service/Loan.wsdl');
	header('Content-Type: text/xml');
	echo str_replace(array_keys($tokens), array_values($tokens), $wsdl);
	exit;
}

// Get the database connection
$failover_config = new DB_FailoverConfig_1();
// [#46323] writer goes first for paydown / payout
$failover_config->addConfig(ECash::getConfig()->DB_MASTER_CONFIG);
$failover_config->addConfig(ECash::getConfig()->DB_API_CONFIG);
$failover_config->addConfig(ECash::getConfig()->DB_SLAVE_CONFIG);
$failover_db = $failover_config->getConnection();

$server = new Server_Web($session_id);

$company = ECash::getFactory()->getModel('Company');
if (!$company->loadBy(array('name_short' => $company_name_short)))
{
	throw new Exception('Unknown company name short');
}
$server->Set_Company($company->company_id);

$security = new ECash_Security(SESSION_EXPIRATION_HOURS);
if ($security->loginUser('eCash_RPC', $username, $password))
{
	$agent_id  = $security->getAgent()->getModel()->agent_id;

	$system_id = 4;

	$acl = ECash::getACL($failover_db);
	$acl->setSystemId($system_id);
	$acl->fetchUserACL($agent_id, $company->company_id);

	if ($acl->Acl_Access_Ok('olp_api', $company->company_id))
	{
		$server->acl = $acl;
	}
	else
	{
		header('HTTP/1.0 401 You do not have access to this API');
		header('WWW-Authenticate: Basic realm="ECash"');
		throw new SoapFault(ECash_Service_Loan_Exception::CREDENTIALS_API_ACCESS,
							"You do not have access to this api.");
	}
}
else
{
	header('HTTP/1.0 401 You do not have access to this API');
	header('WWW-Authenticate: Basic realm="ECash"');
	throw new SoapFault(ECash_Service_Loan_Exception::CREDENTIALS_LOGIN,
						"Invalid Username and/or Password.");
}

$api_factory = new ECash_Loan_ECashAPIFactory($failover_db, $company);
$use_web_services = isset(ECash::getConfig()->USE_WEB_SERVICES_READS) ? ECash::getConfig()->USE_WEB_SERVICES_READS : FALSE;

$loan_provider = new ECash_Service_Loan_StandardCustomerLoanProvider(
		$failover_db,
		ECash::getFactory(),
		$api_factory,
		$company->company_id,
		TRUE);

$soap_server = new SoapServer($soap_url . '&wsdl', array('cache_wsdl' => WSDL_CACHE_NONE));

$loan_api_class = ECash::getFactory()->getClassString("Loan_API");
$soap_server->setClass(
	$loan_api_class,
	ECash::getFactory(),
	$api_factory,
	$loan_provider,
	$company->company_id,
	$username,
	$use_web_services);
$soap_server->handle();
