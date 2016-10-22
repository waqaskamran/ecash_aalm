<?php

class ECash_VendorAPI_DriverTest extends PHPUnit_Framework_TestCase
{
	protected $_driver;
	
	protected $_factory;

	public function setUp()
	{
		$db = $this->getMock('DB_Database_1', array(), array(), '', FALSE);
		$this->_factory = $this->getMock('ECash_Factory', array(), array(), '', FALSE);
		$log = $this->getMock('Log_ILog_1');

		TestConfig::setFactory($this->_factory);
		$config = ECash::getConfig();

		$company_model = new ECash_Models_Company($db);
		$company_model->company_id = 1;
		$company_model->name_short = 'test';

		$company = $this->getMock('ECash_Company', array('getModel', 'getCompanyId'), array(), '', FALSE);
		$company->expects($this->any())->method('getCompanyId')->will($this->returnValue(1));
		$company->expects($this->any())->method('getModel')->will($this->returnValue($company_model));

		$this->_driver = new ECash_VendorAPI_Driver(
			$config,
			$this->_factory,
			$db,
			$company,
			$log
		);
	}

	public function tearDown()
	{
		$this->_driver = NULL;
	}

	public function testGetActionReturnsBaseAction()
	{
		$action = $this->_driver->getAction('Noop');
		$this->assertEquals('VendorAPI_Actions_Noop', get_class($action));
	}

	public function testAuthenticatorImplementsIAuthenticator()
	{
		if (!defined('SESSION_EXPIRATION_HOURS'))
		{
			define('SESSION_EXPIRATION_HOURS', 1);
		}
		
		$this->_factory->expects($this->any())
			->method('getACL')
			->will($this->returnValue($this->getMock('ECash_ACL', array(), array(), '', FALSE)));

		$this->_factory->expects($this->any())
			->method('getACL')
			->will($this->returnValue($this->getMock('ECash_ACL', array(), array(), '', FALSE)));

		$auth = $this->_driver->getAuthenticator();
		$this->assertType('VendorAPI_IAuthenticator', $auth);
	}

	public function testGetDatabaseReturnsConnection()
	{
		$db = $this->_driver->getDatabase();
		$this->assertType('DB_IConnection_1', $db);
	}
}

?>
