<?php
/* Reworking this a bit
 * First we're going to check to see if things aren't defined
 * if they aren't, we're going to try and get the value from environmental variables
 * if those aren't set, we're going to use what was here beforehand.
 */
//path defines -- USE if(!defined())

/** @TODO this will change to ECASH_COMMON_CODE_DIR once all of the ECASH_COMMON_DIR refs are removed */
/* ECASH_COMMON_DIR */
if (!defined('ECASH_COMMON_DIR')) 
{
	if ($env = getenv('ECASH_COMMON_DIR'))
		define('ECASH_COMMON_DIR', $env);
	else
		define('ECASH_COMMON_DIR', '/virtualhosts/ecash_common.cfe/');
}

/* ECASH_COMMON_CODE_DIR */
if (!defined('ECASH_COMMON_CODE_DIR')) 
{
	if ($env = getenv('ECASH_COMMON_CODE_DIR'))
		define('ECASH_COMMON_CODE_DIR', $env);
	else
		define('ECASH_COMMON_CODE_DIR', ECASH_COMMON_DIR . '/code/');
}

/* LIBOLUTION_DIR */
if (!defined('LIBOLUTION_DIR')) 
{
	if ($env = getenv('LIBOLUTION_DIR'))
		define('LIBOLUTION_DIR', $env);
	else
		define('LIBOLUTION_DIR', '/virtualhosts/libolution/');
}

/* ECASH_WWW_DIR -- if this isn't set here, how the hell did we get here? */
if (!defined('ECASH_WWW_DIR')) 
{
	if ($env = getenv('ECASH_WWW_DIR'))
		define('ECASH_WWW_DIR', $env);
	else
		define('ECASH_WWW_DIR', dirname(__FILE__) . '/');
}

/* ECASH_CODE_DIR */
if (!defined('ECASH_CODE_DIR')) 
{
	if ($env = getenv('ECASH_CODE_DIR'))
		define('ECASH_CODE_DIR', $env);
	else
		define('ECASH_CODE_DIR', ECASH_WWW_DIR . '../code/');
}

if (!defined('ECASH_EXEC_MODE')) 
{
	if ($exec_mode = getenv('ECASH_EXEC_MODE'))
		define('ECASH_EXEC_MODE', $exec_mode);
	else
		define('ECASH_EXEC_MODE', EXECUTION_MODE);
}

/* COMMON_LIB_DIR */
if (!defined('COMMON_LIB_DIR')) 
{
	if ($env = getenv('COMMON_LIB_DIR'))
		define('COMMON_LIB_DIR', $env);
	else
		define ('COMMON_LIB_DIR', '/virtualhosts/lib/');
}

/* COMMON_LIB_ALT_DIR */
if (!defined('COMMON_LIB_ALT_DIR')) 
{
	if ($env = getenv('COMMON_LIB_ALT_DIR'))
		define('COMMON_LIB_ALT_DIR', $env);
	else
		define ('COMMON_LIB_ALT_DIR', '/virtualhosts/lib5/');
}

/** @TODO remove this eventually */
/* LIB_DIR */
if (!defined('LIB_DIR')) 
{
	if ($env = getenv('LIB_DIR'))
		define('LIB_DIR', $env);
	else
		define('LIB_DIR', ECASH_WWW_DIR . '../lib/');
}

if (!defined('CUSTOMER_DIR'))
{
	if ($env = getenv('ECASH_CUSTOMER_DIR'))
		define('CUSTOMER_DIR', $env);
}

if (!defined('CUSTOMER_CODE_DIR'))
{
	if ($env = getenv('CUSTOMER_CODE_DIR'))
		define('CUSTOMER_CODE_DIR', $env);
	else if (defined('CUSTOMER_DIR'))
		define('CUSTOMER_CODE_DIR', CUSTOMER_DIR . '/code/');
}
/* WEBSERVICE_DIR */
if (!defined('WEB_SERVICES_DIR'))
{
	if ($env = getenv('WEB_SERVICES_DIR'))
		define('WEB_SERVICES_DIR', $env);
	else
		define('WEB_SERVICES_DIR', '/virtualhosts/web_services/code/');
}

/**
  * One caveat to libolution is that it is required to be in the path to work.
  * It was assumed that libolution would also be symlinked to /usr/share/php so
  * many of the path references in Libolution use 'libolution/'.  This doesn't
  * work well in environments that require more than one instance of libolution
  * such as testing environments, so I'm including the parent of libolution in 
  * the path as well. [BR]
  */
ini_set('include_path', ini_get('include_path') .':'.LIBOLUTION_DIR . ':' . dirname(LIBOLUTION_DIR));


require_once LIBOLUTION_DIR.'/AutoLoad.1.php';

AutoLoad_1::addSearchPath(
	LIBOLUTION_DIR,
	ECASH_CODE_DIR,
	ECASH_COMMON_CODE_DIR,
	CUSTOMER_CODE_DIR,
	COMMON_LIB_ALT_DIR,
	COMMON_LIB_DIR,
	LIB_DIR,
	WEB_SERVICES_DIR
);

if (!class_exists('ECash_AutoLoad'))
{
	class ECash_AutoLoad implements IAutoLoad_1 {
		public function load($class_name) {

			$partial_path = strtolower($class_name) . ".class.php";

			$lib_branch_offset = strpos($class_name,'_');
			if ($lib_branch_offset == 0) $lib_branch_offset = strlen($class_name);
			$lib_branch = substr($class_name,0,$lib_branch_offset);
			$lib_branch = strtoupper(substr($lib_branch,0,1)).strtolower(substr($lib_branch,1)).'/';
			if (file_exists(CLIENT_CODE_DIR . $partial_path)) {
				include_once(CLIENT_CODE_DIR . $partial_path);
			} else if (file_exists(SERVER_CODE_DIR . $partial_path)) {
				include_once(SERVER_CODE_DIR . $partial_path);
			} else if (file_exists(LIB_DIR . $lib_branch . $partial_path)) {
			        include_once(LIB_DIR . $lib_branch . $partial_path);
			} else if (file_exists(LIB_DIR . $partial_path)) {
				include_once(LIB_DIR . $partial_path);
			} else {
				$partial_path = str_replace('_', '/', $class_name . '.php');
				if (file_exists(BASE_DIR . 'code/' . $partial_path)) {
					include_once(BASE_DIR . 'code/' . $partial_path);
				} else {
					return FALSE;
				}
			}
			return TRUE;
		}
	}
}

AutoLoad_1::addLoader(new ECash_AutoLoad());

?>
