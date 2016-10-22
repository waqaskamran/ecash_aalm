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

$company_name	= (get_magic_quotes_gpc()) ? stripslashes($_REQUEST['company']) : $_REQUEST['company'];
$username = $_SERVER['PHP_AUTH_USER'];
$password = $_SERVER['PHP_AUTH_PW'];

$soap_url = (empty($_SERVER['HTTPS']) ? 'http' : 'https').'://'
	.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?company='.$company_name;

if (isset($_GET['wsdl']))
{
	include 'olp.2.wsdl.php';
	exit;
}

$failover_config = new DB_FailoverConfig_1();
$failover_config->addConfig(ECash::getConfig()->DB_API_CONFIG);
$failover_config->addConfig(ECash::getConfig()->DB_SLAVE_CONFIG);
$failover_config->addConfig(ECash::getConfig()->DB_MASTER_CONFIG);
$failover_db = $failover_config->getConnection();

$server = new Server_Web($session_id);
$company = ECash::getFactory()->getModel('Company');

if (!$company->loadBy(array('name_short' => $company_name)))
{
	header('Http/1.0 500 Unknown company');
	exit;
}

$server->Set_Company($company->company_id);
$security = new ECash_Security(SESSION_EXPIRATION_HOURS);

if (!$security->loginUser('eCash_RPC', $username, $password))
{
	header('HTTP/1.0 401 You do not have access to this API');
	header('WWW-Authenticate: Basic realm="ECash"');
	exit;
}

$agent_id = $security->getAgent()->getModel()->agent_id;
$system_id = 4; // sweeeet
$acl = ECash::getAcl();
$acl->setSystemId($system_id);
$acl->fetchUserAcl($agent_id, $company->company_id);

if (!$acl->Acl_Access_Ok('olp_api', $company->company_id))
{
	header('HTTP/1.0 401 Username and/or password is incorrect');
	header('WWW-Authenticate: Basic realm="ECash"');
	exit;
}

$server->acl = $acl;
$api = new ECash_OLPAPI_API($failover_db, ECash::getFactory());

$soap = new SOAPServer($soap_url.'&wsdl');
$soap->setObject($api);
$soap->handle();

?>
