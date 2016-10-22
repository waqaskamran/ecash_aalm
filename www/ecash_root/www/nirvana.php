<?php

/**
 * Nirvana ECash PRPC Server
 *
 * @copyright Copyright &copy; 2009 The Selling Source, Inc.
 * @author Bryan Campbell <bryan.campbell@sellingsource.com>
 */

require_once(dirname(__FILE__) . '/config.php');
require_once(LIB_DIR . 'common_functions.php');
require_once(COMMON_LIB_DIR . 'security.6.php');

$dir = ECASH_COMMON_DIR;
require_once($dir.DIRECTORY_SEPARATOR.'Condor'.DIRECTORY_SEPARATOR.'Condor_Commercial.php');
require_once($dir.DIRECTORY_SEPARATOR.'ecash_api'.DIRECTORY_SEPARATOR.'interest_calculator.class.php');
require_once($dir.DIRECTORY_SEPARATOR.'ecash_api'.DIRECTORY_SEPARATOR.'ecash_api.2.php');
		
$dir = ECASH_CODE_DIR."../..";
require_once($dir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'business_rules.class.php');
require_once($dir.DIRECTORY_SEPARATOR.'sql/lib/scheduling.func.php');

$user_name		= (get_magic_quotes_gpc()) ? stripslashes($_REQUEST['user']) : $_REQUEST['user'];
$password		= (get_magic_quotes_gpc()) ? stripslashes($_REQUEST['pass']) : $_REQUEST['pass'];

$failover_config = new DB_FailoverConfig_1();
$failover_config->addConfig(ECash::getConfig()->DB_API_CONFIG);
$failover_config->addConfig(ECash::getConfig()->DB_SLAVE_CONFIG);
$failover_config->addConfig(ECash::getConfig()->DB_MASTER_CONFIG);
$failover_db = $failover_config->getConnection();

$server = new Server_Web($session_id);

$security = new ECash_Security(SESSION_EXPIRATION_HOURS);

if ($security->loginUser('eCash_RPC', $user_name, $password))
{
	$prpc = new ECash_Nirvana_API($server, $failover_db, ECash::getFactory(), $security, $user_name);
}
else
{
	throw new Exception("Invalid Username and/or Password.");
}

?>
