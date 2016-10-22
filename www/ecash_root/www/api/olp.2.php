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
$user_name		= (get_magic_quotes_gpc()) ? stripslashes($_REQUEST['user']) : $_REQUEST['user'];
$password		= (get_magic_quotes_gpc()) ? stripslashes($_REQUEST['pass']) : $_REQUEST['pass'];

$failover_config = new DB_FailoverConfig_1();
$failover_config->addConfig(ECash::getConfig()->DB_API_CONFIG);
$failover_config->addConfig(ECash::getConfig()->DB_SLAVE_CONFIG);
$failover_config->addConfig(ECash::getConfig()->DB_MASTER_CONFIG);
$failover_db = $failover_config->getConnection();

$server = new Server_Web($session_id);

$company = ECash::getFactory()->getModel('Company');

if (!$company->loadBy(array('name_short' => $company_name)))
{
	throw new Exception('Unknown company name short');
}

$company_id = $company->company_id;

$server->Set_Company($company_id);

$security = new ECash_Security(SESSION_EXPIRATION_HOURS);

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
		$api = new ECash_OLPAPI_API($failover_db, ECash::getFactory());
		$rpc = new Rpc_Server_1($api);
		$rpc->processPost();
	}
	else
	{
		throw new Exception("You do not have access to this api.");
	}

}
else
{
	throw new Exception("Invalid Username and/or Password.");
}

?>
