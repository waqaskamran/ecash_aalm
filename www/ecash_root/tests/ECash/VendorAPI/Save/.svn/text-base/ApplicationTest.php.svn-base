<?php

class ECash_VendorAPI_Save_ApplicationTest extends PHPUnit_Framework_TestCase
{
	protected $_customer;
	protected $_application;
	protected $_factory;
	protected $_save;
	protected $_batch;
	protected $_driver;

	public function setUp()
	{
		$this->_customer = $this->getMock('ECash_Models_Customer', array('loadBySSN', 'getUsernameCount', 'insert'));
		$this->_application = new ECash_Models_Application();
		$this->_factory = $this->getMock('ECash_Factory', array('getModel', 'getReferenceList'), array(), '', FALSE);
		$this->_batch = $this->getMock('DB_Models_Batch_1', array('save'), array(), '', FALSE);
		$this->_driver = $this->getMock('ECash_VendorAPI_Driver', array(), array(), '', FALSE);
		$auth = $this->getMock('VendorAPI_IAuthenticator');
		$this->_driver->expects($this->any())->method('getAuthenticator')->will($this->returnValue($auth));
		$this->_driver->expects($this->any())->method('getFactory')->will($this->returnValue($this->_factory));
		$auth->expects($this->any())->method('getAgentId')->will($this->returnValue(12));

		$db = $this->getMock('DB_IConnection_1');

		$this->_save = $this->getMock('ECash_VendorAPI_Save_Application', array('setEcashCompany', 'setEcashAgent', 'setEcashApplication'), array($this->_factory, $this->_driver, $db));
		$this->_save->setApplicationModel($this->_application);
	}

	public function tearDown()
	{
		$this->_customer = null;
		$this->_application = null;
		$this->_factory = null;
		$this->_batch = null;
		$this->_save = null;
	}

	public function testApplicationIsSavedToBatch()
	{
		$data = array(
			'ssn' => '123121234',
			'customer_id' => '',
			'company_id' => 1,
			'application_id' => 1,
		);

		$this->_batch->expects($this->once())
			->method('save')
			->with($this->_application);

		$this->_save->saveTo($data, $this->_batch);
	}

}

?>
