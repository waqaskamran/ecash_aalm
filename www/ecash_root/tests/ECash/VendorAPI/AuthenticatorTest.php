<?php
require_once('PHPUnit/Extensions/Database/TestCase.php');

/**
 * Tests the CLK authenticator class
 *
 * @package Tests
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECash_VendorAPI_AuthenticatorTest extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * @var VendorAPI_Authenticator
	 */
	protected $auth;

	/**
	 * Returns the db connection
	 *
	 * @return PHPUnit_Extensions_Database_DB_DefaultDbConnection
	 */
	public function getConnection()
	{
		return $this->createDefaultDbConnection(getTestPDODatabase(), $GLOBALS['db_name']);
	}

	/**
	 * Returns the dataset for this test
	 *
	 * @return PHPUnit_Extensions_Database_DataSet_Xml
	 */
	public function getDataSet()
	{
		return $this->createXmlDataSet('ECash/VendorAPI/_fixtures/AuthenticatorTest.xml');
	}

	/**
	 * Test Fixture
	 *
	 * @return NULL
	 */
	public function setUp()
	{
		parent::setup();

		$db_config = getTestDBConfig();

		$this->auth = new ECash_VendorAPI_Authenticator(
			1,
			new ECash_Security(2),
			new ECash_ACL($db_config->getConnection())
		);

		//DB_DatabaseConfigPool_1::add('ECASH_MASTER',  $db_config);
		$factory = new ECash_Factory('Test', $db_config);
		TestConfig::setFactory($factory);
		$company = $factory->getCompanyById(1);
		ECash::setCompany($company);
	}

	/**
	 * Tests good auths
	 *
	 * @return NULL
	 */
	public function testSuccessfulAuthenticate()
	{
		$this->assertTrue($this->auth->authenticate('api_user', 'api_pass'));
	}

	/**
	 * Tests bad auths
	 *
	 * @return NULL
	 */
	public function testUnsuccessfulAuthenticate()
	{
		$this->assertFalse($this->auth->authenticate('api_user', 'bad_pass'));
	}
}

?>