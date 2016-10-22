<?php
setDefaultValue('db_host', 'localhost');
setDefaultValue('db_user', 'vendortest');
setDefaultValue('db_pass', 'vendortest');
setDefaultValue('db_name', 'ldb_vndrtst_com');
setDefaultValue('db_port', 3306);


setDefaultValue('paths_root', realpath(dirname(__FILE__).'/../'));
define('ROOT', $GLOBALS['paths_root']);

setDefaultValue('paths_commercial_root', realpath(ROOT.'/../ecash_commercial/'));
define('COMMERCIAL_ROOT', $GLOBALS['paths_commercial_root']);

setDefaultValue('paths_ecash_common_root', realpath(ROOT.'/../ecash_common_cfe/'));
define('ECASH_COMMON_ROOT', $GLOBALS['paths_ecash_common_root']);

setDefaultValue('paths_vendor_api_root', realpath(ROOT . '/../vendor_api'));
define('VENDOR_API_ROOT', $GLOBALS['paths_vendor_api_root']);

setDefaultValue('COMMON_LIB_DIR', realpath(ROOT.'/../lib/'));
define('COMMON_LIB_DIR', $GLOBALS['COMMON_LIB_DIR']);

define('ECASH_WWW_DIR', COMMERCIAL_ROOT.'/www/');
define('ECASH_CODE_DIR', COMMERCIAL_ROOT.'/code/');
define('SERVER_CODE_DIR', ROOT . '/server/code/');
define('ECASH_COMMON_DIR', ECASH_COMMON_ROOT);
define('ECASH_COMMON_CODE_DIR', ECASH_COMMON_ROOT.'/code/');
define('SERVER_CODE_DIR', ROOT.'/server/code/');
define('LIB_DIR', COMMERCIAL_ROOT.'/lib/');
define('SQL_LIB_DIR', COMMERCIAL_ROOT.'/sql/lib/');

require 'libolution/AutoLoad.1.php';
AutoLoad_1::addSearchPath(
	ROOT.'/code/',
	VENDOR_API_ROOT.'/code/',
	VENDOR_API_ROOT.'/lib/',
	COMMERCIAL_ROOT.'/code/',
	ECASH_COMMON_ROOT.'/code/',
	ROOT.'/../ecash_cra/code/',
	COMMON_LIB_DIR.'/../lib5/',
	COMMON_LIB_DIR
);

/**
 * @return PDO
 */
function getTestPDODatabase()
{
	return new PDO("mysql:host={$GLOBALS['db_host']};dbname={$GLOBALS['db_name']};port={$GLOBALS['db_port']}", $GLOBALS['db_user'], $GLOBALS['db_pass']);
}

/**
 * @return DB_MySQLConfig_1
 */
function getTestDBConfig()
{
	return new DB_MySQLConfig_1($GLOBALS['db_host'], $GLOBALS['db_user'], $GLOBALS['db_pass'], $GLOBALS['db_name'], $GLOBALS['db_port']);
}

/**
 * @return DB_Database_1
 */
function getTestDatabase()
{
        return new DB_Database_1("mysql:host={$GLOBALS['db_host']};dbname={$GLOBALS['db_name']};port={$GLOBALS['db_port']}", $GLOBALS['db_user'], $GLOBALS['db_pass']);
}
/**
 * Sets a global variable with a default value if that variable doesn't
 * already exist.
 *
 * @param string $var
 * @param string $val
 * @return NULL
 */
function setDefaultValue($var, $val)
{
	if (!array_key_exists($var, $GLOBALS))
	{
		$GLOBALS[$var] = $val;
	}
}

/**
 * A test config class for commercial that allows manually setting the factory.
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 * @package Tests
 */
class TestConfig extends ECash_Config
{
	/**
	 * @var ECash_Factory
	 */
	protected static $factory;

	/**
	 * @return NULL
	 */
	protected function init()
	{

	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		if ($name == 'FACTORY')
		{
			return self::$factory;
		}
	}

	/**
	 * Sets the factor for all TestConfigs
	 *
	 * @param ECash_Factory $factory
	 * @return NULL
	 */
	public static function setFactory(/*ECash_Factory*/ $factory)
	{
		self::$factory = $factory;
	}
}

ECash::setConfig(new TestConfig());
class FakeApplicationModel extends ECash_Models_Application 
{
	public function getColumnData()
    {
        $modified = $this->column_data;
        //mysql timestamps
        $modified['date_modified'] = date("Y-m-d H:i:s", $modified['date_modified']);
        $modified['date_created'] = date("Y-m-d H:i:s", $modified['date_created']);
        $modified['date_application_status_set'] = date("Y-m-d H:i:s", $modified['date_application_status_set']);
        $modified['date_next_contact'] = $modified['date_next_contact'] === NULL ? NULL : date("Y-m-d H:i:s", $modified['date_next_contact']); //was    date("Y-m-d H:i:s", is_numeric($modified['date_next_contact']) ? $modified['date_next_contact'] : strtotime($modified['date_next_contact']));
        //mysql dates
        $modified['date_fund_estimated'] = date("Y-m-d", $modified['date_fund_estimated']);
        $modified['date_fund_actual'] = $modified['date_fund_actual'] === NULL ? NULL : date("Y-m-d", $modified['date_fund_actual']);
        $modified['date_first_payment'] = $modified['date_first_payment'] === NULL ? NULL : date("Y-m-d", $modified['date_first_payment']);
        $modified['income_date_soap_1'] = $modified['income_date_soap_1'] === NULL ? NULL : date("Y-m-d", $modified['income_date_soap_1']);
        $modified['income_date_soap_2'] = $modified['income_date_soap_2'] === NULL ? NULL : date("Y-m-d", $modified['income_date_soap_2']);
        $modified['last_paydate'] = $modified['last_paydate'] === NULL ? NULL : date("Y-m-d", $modified['last_paydate']);

        $modified['date_hire'] = date("Y-m-d", $modified['date_hire']);
        $modified['residence_start_date'] = date("Y-m-d", $modified['residence_start_date']);
        $modified['banking_start_date'] = date("Y-m-d", $modified['banking_start_date']);
        $modified['dob'] = $modified['dob'] === NULL ? NULL : date("Y-m-d", is_numeric($modified['dob']) ? $modified['dob'] : strtotime($modified['dob']));
        //SSN Last Four
        $modified['ssn_last_four'] = $modified['ssn'] === NULL ? NULL : substr($modified['ssn'],
                                                                               strlen($modified['ssn']) - 4,
                                                                               4);

        if($modified['ssn_last_four'] != $this->column_data['ssn_last_four'])
            $this->altered_columns['ssn_last_four'] = 'ssn_last_four';
		return $modified;
    }
}