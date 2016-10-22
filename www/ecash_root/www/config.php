<?php
/**
 * Configuration File
 *
 * This file defines all of the basic defaults required for eCash
 * to run.  These values should almost never need to be changed.
 */

if (!function_exists('defineIf'))
{
	function defineIf($name, $value)
	{
		if (!defined($name))
		{
			define($name, $value);
		}
	}
}

/**
 * Software Name & Version Info
 */
defineIf('SOFTWARE_NAME', 'ecash');
defineIf('MAJOR_VERSION', 3);
defineIf('MINOR_VERSION', 5);
defineIf('BUILD_NUM', 92);
defineIf('ECASH_VERSION', MAJOR_VERSION);

/**
 * Default Paths
 */
defineIf('BASE_DIR', dirname(__FILE__) . '/../' );
defineIf('CLI_EXE_PATH', '/usr/bin/');
defineIf('CLIENT_CODE_DIR', BASE_DIR . 'client/code/');
defineIf('CLIENT_VIEW_DIR', BASE_DIR . 'client/view/');
defineIf('CLIENT_MODULE_DIR', BASE_DIR . 'client/module/');
defineIf('SERVER_CODE_DIR', BASE_DIR . 'server/code/');
defineIf('SERVER_MODULE_DIR', BASE_DIR . 'server/module/');
defineIf('WWW_DIR', BASE_DIR . 'www/');
defineIf('ECASH_WWW_DIR', BASE_DIR . 'www/');
defineIf('LIB_DIR', BASE_DIR . 'lib/');
defineIf('SQL_LIB_DIR', BASE_DIR . 'sql/lib/');
defineIf('CLI_SCRIPT_PATH', SERVER_CODE_DIR);
defineIf('REQUEST_LOG_PATH', BASE_DIR . 'data/sqlite/request_log.sq3');
defineIf('ECASH_DIR',BASE_DIR.'code/ECash/'); /** @depricated use ECASH_CODE_DIR */
defineIf('ECASH_CODE_DIR',BASE_DIR.'code/ECash/');
defineIf('ECASH_SHARED_DIR',BASE_DIR.'../ecash_shared/code/');

/**
 * Sets up path defines
 */
require_once('paths.php');

/**
 * Define the autoloader
 */
require_once(LIBOLUTION_DIR . 'AutoLoad.1.php');

$customer_dir = getenv('ECASH_CUSTOMER_DIR');
$customer = getenv('ECASH_CUSTOMER');
$exec_mode = getenv('ECASH_EXEC_MODE');

if (empty($customer_dir)) { die("ECASH_CUSTOMER_DIR not set in ENV!\n"); }
if (empty($customer)) { die("ECASH_CUSTOMER not set in ENV!\n"); }
if (empty($exec_mode)) { die("ECASH_EXEC_MODE not set in ENV!\n"); }

/**
 * Look for an Enterprise configuration file symlinked
 * in the config/ directory.  If it doesn't exist, die.
 */
require_once(ECASH_COMMON_DIR . '/code/ECash/Config.php');

if(! empty($customer_dir) &&
		! empty($customer) &&
		! empty($exec_mode) &&
		file_exists("{$customer_dir}/code/{$customer}/Config/{$exec_mode}.php"))
{
	require_once("{$customer_dir}/code/{$customer}/Config/{$exec_mode}.php");
}
else
{
	/**
	 * This should now instead die a horrible death rather than loading CLK
	 *
	 * @TODO replace with red screen of death or similar
	 */
	die("No config found in '{$customer_dir}/code/{$customer}/Config/{$exec_mode}.php'");
}

ini_set('include_path',ini_get('include_path') .':'.LIBOLUTION_DIR . ':' . BASE_DIR . ':' . ECASH_SHARED_DIR . ':' . WEB_SERVICES_DIR . ':' . ECASH_COMMON_CODE_DIR . ':'. COMMON_LIB_ALT_DIR.':'.COMMON_LIB_DIR.':'.LIB_DIR);

$class_config_name = $customer . '_Config_' . $exec_mode;
ECash::setConfig(new $class_config_name());
DB_DatabaseConfigPool_1::add(ECash_Models_WritableModel::ALIAS_MASTER, ECash::getConfig()->getDbConfig(ECash_Config::DB_MASTER_ID));
DB_DatabaseConfigPool_1::add(ECash_Models_WritableModel::ALIAS_SLAVE, ECash::getConfig()->getDbConfig(ECash_Config::DB_SLAVE_ID));

/**
 * Set the Title Bar
 */
$short_title = 'eCash Commercial - '.MAJOR_VERSION.'.'.MINOR_VERSION;
$hostname = exec('hostname');
$mode = ECash::getConfig()->mode;

if (file_exists(BASE_DIR . '.svnversion'))
{
	$micro_build_num = file_get_contents(BASE_DIR . '.svnverion');
}
elseif (EXECUTION_MODE != 'LIVE')
{
	$micro_build_num = exec('svnversion -n ' . BASE_DIR);
}
else
{
	$micro_build_num = '';
}
defineIf('MICRO_BUILD_NUM', $micro_build_num);
defineIf('ECASH_VERSION_FULL', MAJOR_VERSION . '.' . MINOR_VERSION . '.' . BUILD_NUM . 'b' . MICRO_BUILD_NUM);


/**
 * Set the run-time environment to use the time zone.
 */
if($tz = ECash::getConfig()->TIME_ZONE)
{
	date_default_timezone_set($tz);
}

if (EXECUTION_MODE != 'LIVE')
{
	try
	{
		$dsn= ECash::getMasterDb()->getDSN();
		defineIf('TITLE', $short_title . ' Build '.BUILD_NUM." ({$mode} - $dsn) (PS: $hostname)");
	}
	catch (	PDOException $e)
	{
		// Catch the Exception and die so we don't expose any
		// connection information.
		die("Unable to connect to database!\n");
	}
}
else defineIf('TITLE', $short_title . " (PS: $hostname)");

/**
 * Our language abbstraction class
 */
require_once(LIB_DIR . '/DisplayMessage.class.php');

/**
 * AppLog Defaults
 */
defineIf('APPLOG_SIZE_LIMIT', 10000000);
defineIf('APPLOG_FILE_LIMIT', 80);
defineIf('APPLOG_SUBDIRECTORY', "ecash3.0/{$mode}");

/**
 * Some very basic defaults
 */
defineIf('SESSION_EXPIRATION_HOURS', 12);
defineIf('SCRIPT_TIME_LIMIT_SECONDS', 120);
defineIf('PHP_MEMORY_USE_THRESHOLD', 50000000);

/**
 *Define the precision for BC Math #10338
 */
bcscale(2);



/**
 * Set the default locale to en_US
 */
setlocale(LC_ALL, 'en_US');

/**
 * Data fix Tracking flag.
 */
defineIf('DEFAULT_SOURCE_TYPE', 'ecashinternal');

/**
 * Queue timeout options, used for the Queue Configuration
 */
$GLOBALS["DEFAULT_QUEUE_TIMEOUTS"] = array
	(	"COMPANY"		=> "Company Default",
		"5" 	=> "5 Minutes",
		"10" 	=> "10 Minutes",
		"15" 	=> "15 Minutes",
		"30" 	=> "30 Minutes",
		"60" 	=> "1 Hour",
		"120"	=> "2 Hours",
		"180"	=> "3 Hours",
		"240" 	=> "4 Hours",
		"360" 	=> "6 Hours",
		"480" 	=> "8 Hours",
		"600" 	=> "10 Hours",
		"720" 	=> "12 Hours",
		"1440" 	=> "1 Day",
		"2880" 	=> "2 Days",
		"4320" 	=> "3 Days",
		"10080"	=> "7 Days",
		"none" 	=> "Never",
	);

/**
 * DataX URL, used for making DataX calls
 */
if (EXECUTION_MODE == 'LIVE')
{
	defineIf('DATAX_URL', 'https://verihub.com/datax/index.php');
}
else
{
	defineIf('DATAX_URL', 'https://rc.verihub.com/datax/');
}
/**
 * Blackbox Admin Query URL
 */
defineIf('BLACKBOX_ADMIN_QUERY_URL', 'https://api.bbxadmin.sellingsource.com/query.php');

/*
 * Turn off caching on non-live environments
 */
if (EXECUTION_MODE != 'LIVE')
{
	ini_set("soap.wsdl_cache", 0);
}
