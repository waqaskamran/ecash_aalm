<?php
/**
 * Tests the Commerical DAO Application class
 *
 * @package Tests
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class ECash_VendorAPI_DAO_ApplicationTest extends PHPUnit_Framework_Testcase
{
	/**
	 * Mock of The Driver
	 *
	 * @var ECash_VendorAPI_Driver
	 */
	protected $_driver;

	/**
	 * Mock of the ecash factory
	 *
	 * @var ECash_Factory
	 */
	protected $_factory;

	/**
	 * Mock of DB_IConnection_1
	 *
	 * @var $db
	 */
	protected $_db;

	/**
	 * Mock app model?
	 *
	 * @var ECash_Models_Application
	 */
	protected $_application_model;
	
	protected $_mock_app_factory;

	/**
	 * The setup?
	 *
	 */
	public function setUp()
	{
		$this->_db = $this->getMock('DB_IConnection_1');

		$this->_driver = $this->getMock(
			'ECash_VendorAPI_Driver',
			array('getFactory', 'getDatabase'),
			array(), '', FALSE);

		$this->_app_version = $this->getMock('ECash_Models_ApplicationVersion', array('loadForUpdate', 'save', 'loadBy'), array($this->_db));

		$this->_driver->expects($this->any())->method('getDatabase')
			->will($this->returnValue($this->_db));
		
		$this->_factory = $this->getMock(
			'ECash_Factory', array('getDB', 'getModel', 'getModelByTable'), array(), '', FALSE);
		
		$this->_factory->expects($this->any())->method('getDB')
			->will($this->returnValue($this->_db));
			
		$this->_application_model = $this->getMock(
			'ECash_Models_Application', array('save'));
		$this->_factory->expects($this->any())->method('getModel')
			->will($this->returnValue($this->_application_model));
		
		$this->_driver->expects($this->any())->method('getFactory')
			->will($this->returnValue($this->_factory));

		$this->_mock_app_factory = $this->getMock('ECash_VendorAPI_ApplicationFactory', array(), array($this->_driver, $this->_factory, $this->_db));
		$mock_app = $this->getMock('ECash_VendorAPI_Application', array(), array(), '', FALSE);
		$mock_app->expects($this->any())->method('getCfeContext')
			->will($this->returnValue($this->getMock('VendorAPI_CFE_ApplicationContext', array(), array($mock_app, new VendorAPI_CallContext()))));
		$this->_mock_app_factory->expects($this->any())->method('getApplication')
			->will($this->returnValue($mock_app));
	}

	public function testSaveInTransaction()
	{
		$this->_db->expects($this->once())->method('beginTransaction');
		$this->_db->expects($this->once())->method('commit');

		$application = $this->getMock(
			'ECash_VendorAPI_DAO_Application',
			array(
				'setEcashCompany',
				'processVersion',
				'getEcashApplicationModel',
				'sendConfirmEmail',
				'loadVersionWithRetry',
				'getApplicationFactory',
				'getVersionModel'
			),
			array($this->_driver)
		);

		$application->expects($this->any())->method('processVersion')->will($this->returnValue(TRUE));
		$application->expects($this->any())->method('loadVersionWithRetry')->will($this->returnValue($this->_app_version));
		$application->expects($this->any())->method('getVersionModel')->will($this->returnValue($this->_app_version));
		$application->expects($this->any())->method('getApplicationFactory')->will($this->returnValue($this->_mock_app_factory));
		$state = new VendorAPI_StateObject();
		$state->createPart('application');
		$state->application->name = "hello";
		$application->expects($this->once())->method('getECashApplicationModel')
			->will($this->throwException(new ECash_Application_NotFoundException()));
		$this->assertTrue($application->save($state));

	}

	/**
	 *
	 * @expectedException RuntimeException
	 */
	public function testSaveRollsBackOnException()
	{
		$this->_db->expects($this->once())->method('beginTransaction');
		$this->_db->expects($this->never())->method('commit');
		$this->_db->expects($this->once())->method('rollBack');

		$application = $this->getMock(
			'ECash_VendorAPI_DAO_Application',
			array(
				'setEcashCompany',
				'processVersion',
				'getEcashApplicationModel',
				'sendConfirmEmail',
				'loadVersionWithRetry',
				'getApplicationFactory',
				'getVersionModel'
			),
			array($this->_driver)
		);
		$application->expects($this->any())->method('processVersion')
			->will($this->throwException(new RuntimeException("")));
		$application->expects($this->any())->method('getApplicationFactory')->will($this->returnValue($this->_mock_app_factory));
		$application->expects($this->any())->method('loadVersionWithRetry')->will($this->returnValue($this->_app_version));
		$application->expects($this->any())->method('getVersionModel')->will($this->returnValue($this->_app_version));
		$state = new VendorAPI_StateObject();
		$state->createPart('application');
		$state->application->name = "hello";
		$application->expects($this->once())->method('getECashApplicationModel')
			->will($this->throwException(new ECash_Application_NotFoundException()));
		$this->assertTrue($application->save($state));
	}

	public function testCFEGetsRun()
	{
		$application = $this->getMock(
			'ECash_VendorAPI_DAO_Application',
			array(
				'getCfeAsynchEngine',
				'sendConfirmEmail',
				'getEcashApplicationModel',
				'setECashCompany',
				'processVersion',
				'loadVersionWithRetry',
				'getApplicationFactory',
				'getVersionModel'
			),
			array($this->_driver)
		);
		$application->expects($this->once())->method('getECashApplicationModel')
			->will($this->throwException(new ECash_Application_NotFoundException()));
		$this->_factory->expects($this->once())->method('getModel')
			->with('Application', $this->isInstanceOf('DB_IConnection_1'))
			->will($this->returnValue($this->_application_model));
		$application->expects($this->any())->method('getApplicationFactory')->will($this->returnValue($this->_mock_app_factory));
		$application->expects($this->any())->method('loadVersionWithRetry')->will($this->returnValue($this->_app_version));
		$application->expects($this->any())->method('getVersionModel')->will($this->returnValue($this->_app_version));
		$state = new VendorAPI_StateObject();
		$state->application_id = 99999;
		$state->createPart('application');
		$state->application->application_id = 99999;
		$state->application->company_id = 1;
		$state->cfe_result = new ECash_CFE_AsynchResult();

		$asynch_engine = $this->getMock('ECash_CFE_AsynchEngine', array('endExecution'), array($this->_db, 1));
		$asynch_engine->expects($this->once())->method('endExecution')
			->with($this->isinstanceof('ECash_Models_Application'), $state->cfe_result);
		$application->expects($this->once())->method('getCfeAsynchEngine')->will($this->returnValue($asynch_engine));

		$application->save($state);
	}
	
	public function testReferenceModelsInsertedInSeperateTransaction()
	{
		$application = $this->getMock(
			'ECash_VendorAPI_DAO_Application',
			array(
				'getCfeAsynchEngine',
				'sendConfirmEmail',
				'getEcashApplicationModel',
				'setECashCompany',
				'processVersion',
				'loadVersionWithRetry',
				'getApplicationFactory',
				'getVersionModel'
			),
			array($this->_driver)
		);
		$application->expects($this->any())->method('loadVersionWithRetry')->will($this->returnValue($this->_app_version));
		$application->expects($this->any())->method('getVersionModel')->will($this->returnValue($this->_app_version));
		$loadby_array = array('col1' => 'val1', 'col2' => 'val2', 'col3' => 'val3');
		
		$test_model = $this->getMock('DAOApplicationTestModel', array('loadBy'), array($loadby_array));
		$test_model->expects($this->once())->method('loadBy')
			->with($loadby_array)
			->will($this->returnValue(FALSE));
			
		$this->_db->expects($this->at(0))->method('beginTransaction');
		$this->_db->expects($this->at(1))->method('commit');
		$this->_db->expects($this->at(2))->method('beginTransaction');
		$this->_db->expects($this->at(3))->method('commit');
		
		$this->_factory->expects($this->once())->method('getModelByTable')
			->with('table')->will($this->returnValue($test_model));
		
		$state_object = new VendorAPI_StateObject();
		$state_object->addReferencePart('table', $loadby_array);

		$application->save($state_object);
	}
}

class DAOApplicationTestModel extends DB_Models_WritableModel_1
{
	protected $expected_values;
	public function __construct($expected_values = NULL)
	{
		$this->expected_values = $expected_values;
	}
	
	public function save() 
	{
		if (is_array($this->expected_values))
		{
			foreach ($this->expected_values as $col => $val)
			{
				if ($this->$col != $val)
				{
					throw new RuntimeException("Value {$col} doesn't match $val\n");
				}
			}
		}
	}
	
	public function getTableName()
	{
		return 'table';
	}
	public function getAutoIncrement()
	{
		return 'table_id';
	}
	public function getPrimaryKey()
	{
		return array('table_id');
	}
	public function getColumns()
	{
		return array('col1', 'col2', 'col3', 'table_id');
	}
}