<?php

class ECash_VendorAPI_ApplicationFactoryTest extends PHPUnit_Framework_TestCase
{
	protected $_appfactory;
	protected $_driver;
	protected $_db;
	protected $_factory;
	protected $_persistor;
	protected $_statpro;
	protected $_state;
	
	const CFE_RULE_ID = 1;
	const LOAN_TYPE_ID = 3;
	const RULE_SET_ID = 12;
	const AGENT_ID = 61;
	const COMPANY_ID = 1;
	
	public function setUp()
	{
		$this->_factory = $this->getMock('ECash_Factory', 
			array('getModel'), array(), '', FALSE);
		$this->_db = $this->getMock('DB_IConnection_1');
		$this->_driver = $this->getMock('ECash_VendorAPI_Driver',
			array('getDataModelByTable', 'getStatProClient', 
				'getEnterpriseSiteId', 'getBusinessRules', 'getRuleSetID'), 
			array(), '', FALSE);
			
		$this->_statpro = $this->getMock('VendorAPI_StatProClient',
			array(), array(), '', FALSE);
	
		
		$this->_appfactory = new ECash_VendorAPI_ApplicationFactory($this->_driver, $this->_factory, $this->_db);
		
		$this->_driver->expects($this->any())
			->method('getFactory')->will($this->returnValue($this->_factory));
			
		$this->_driver->expects($this->any())
			->method('getDatabase')->will($this->returnValue($this->_db));
			
		$this->_driver->expects($this->any())
			->method('getStatProClient')
			->will($this->returnValue($this->_statpro));
		
		$this->_driver->expects($this->any())
			->method('getEnterpriseSiteId')
			->will($this->returnValue(2));
	}
	
	public function testCreateStateObjectWithNoApp()
	{
		$test_id = 99999;
		$mockApplication = $this->getMock(
			'ECash_Models_ApplicationVersion',
			array('save', 'loadby', 'loadByKey'),
			array($this->_db),
			'', 
			FALSE
		);
		$mockApplication->expects($this->once())->method('loadByKey')
			->with($test_id)
			->will($this->returnValue(FALSE));
		$this->_factory->expects($this->once())
			->method('getModel')->with('ApplicationVersion', $this->_db)
			->will($this->returnValue($mockApplication));
		$state = $this->_appfactory->createStateObject($test_id, new VendorAPI_CallContext());
		$this->assertEquals(1, $state->getCurrentVersion());
		$this->assertEquals($test_id, $state->application_id);
	}
	
	public function testCreateStateObjectWithApp()
	{
		$test_id = 99999;
		$mockApplication = $this->getMock(
			'ECash_Models_ApplicationVersion',
			array('save', 'loadbyKey'),
			array($this->_db),
			'', 
			FALSE
		);
		$mockApplication->expects($this->once())->method('loadByKey')
			->with($test_id)
			->will($this->returnValue(TRUE));
		$mockApplication->version = 4;
		$this->_factory->expects($this->once())
			->method('getModel')->with('ApplicationVersion', $this->_db)
			->will($this->returnValue($mockApplication));
		$state = $this->_appfactory->createStateObject($test_id, new VendorAPI_CallContext());
		$this->assertEquals(5, $state->getCurrentVersion());
		$this->assertEquals($test_id, $state->application_id);
	}
	
	public function testCreateApplicationReturnsApplicationObject()
	{
		$data = $this->getApplicationDataArray();
		$data['is_title_loan'] = FALSE;
		$this->createApplicationModelStubs($this->at(1));
		$this->createAppVersionStubs($this->at(0), $data['application_id']);
		$this->createCustomerStubs($this->at(2));
		$this->createSiteModelStubs($this->at(3));
		$this->createCampaignInfoStubs($this->at(4));
		$this->createBusinessRuleStubsForDriver();
		$this->createPersonalReferenceFactoryStub($this->at(5));
		$this->createPersonalReferenceFactoryStub($this->at(6));
		$application = $this->createApplication($data, $this->getValidAsynchResult());
		$this->assertThat($application, $this->isInstanceOf('ECash_VendorAPI_Application'), "Create returns application.");	
	}
	

	
	public function testCreateApplicationAddsApplicationData()
	{
		$data = $this->getApplicationDataArray();
		$this->createApplicationModelStubs($this->at(1));
		$this->createAppVersionStubs($this->at(0), $data['application_id']);
		$this->createCustomerStubs($this->at(2));
		$this->createSiteModelStubs($this->at(3));
		$this->createCampaignInfoStubs($this->at(4));
		$this->createBusinessRuleStubsForDriver();
		$this->createPersonalReferenceFactoryStub($this->at(5));
		$this->createPersonalReferenceFactoryStub($this->at(6));
		$application = $this->createApplication($data, $this->getValidAsynchResult());
		$expected = $this->getExpectedApplicationData();
		$actual = $this->_state->application->getData();
		// we sort both of them, because the models / state object
		// do not guarentee the order the come out in and we really
		// don't care
		ksort($actual[0]);
		ksort($expected[0]);
		$this->assertThat($actual[0]['customer_id'], $this->isInstanceOf('VendorAPI_ReferenceColumn_Locator'));
		unset($expected[0]['customer_id']);
		unset($actual[0]['customer_id']);
		unset($actual[0]['date_created']);
		
		$this->assertEquals($expected, $actual);
	}
	
	public function testCreateApplicationMakesApplicationPart()
	{
		$data = $this->getApplicationDataArray();
		$this->createApplicationModelStubs($this->at(1));
		$this->createAppVersionStubs($this->at(0), $data['application_id']);
		$this->createCustomerStubs($this->at(2));
		$this->createSiteModelStubs($this->at(3));
		$this->createCampaignInfoStubs($this->at(4));
		$this->createBusinessRuleStubsForDriver();
		$this->createPersonalReferenceFactoryStub($this->at(5));
		$this->createPersonalReferenceFactoryStub($this->at(6));
		$application = $this->createApplication($data, $this->getValidAsynchResult());
		$this->assertTrue($this->_state->isPart('application'));
	}
	
	public function testCreateApplicationReturnsFalseOnCfeFail()
	{
		$application = $this->createApplication($this->getApplicationDataArray(), $this->getInvalidAsynchResult());
		$this->assertFalse($application);
	}
	
	public function testCreateApplicationWithCallCenterFlag()
	{
		$data = $this->getApplicationDataArray();
		$data['call_center'] = 1;
		$this->createApplicationModelStubs($this->at(1));
		$this->createAppVersionStubs($this->at(0), $data['application_id']);
		$this->createCustomerStubs($this->at(2));
		$this->createSiteModelStubs($this->at(3));
		$this->createCampaignInfoStubs($this->at(4));
		$this->createBusinessRuleStubsForDriver();
		$this->createPersonalReferenceFactoryStub($this->at(5));
		$this->createPersonalReferenceFactoryStub($this->at(6));
		$application = $this->createApplication($data, $this->getValidAsynchResult());
		$this->assertTrue($this->_state->call_center);
		
	}
	
	public function testCreateApplicationWithVehicleData()
	{
		$vehicle_data = array(
			'vin' => "super_vin_Number",
			'license_plate' => 'MyLicense',
			'make' => "MyMake",
			'model' => "VehicleModel",
			'series' => "VehicleSeries",
			'style' => "VechileStyle",
			'color' => 'Red',
			'year' => '1999',
			'value' => '8000000000000',
			'mileage' => '99234952352352',
			'title_state' => 8
		);
		$data = $this->getApplicationDataArray();
		$data = array_merge($data, $vehicle_data);
		$data['is_title_loan'] = 1;
		
		$this->createApplicationModelStubs($this->at(1));
		$this->createAppVersionStubs($this->at(0), $data['application_id']);
		$this->createCustomerStubs($this->at(2));
		$this->createSiteModelStubs($this->at(3));
		$this->createCampaignInfoStubs($this->at(4));
		$this->createBusinessRuleStubsForDriver();
		$this->createVehicleStubs($this->at(5));
		$this->createPersonalReferenceFactoryStub($this->at(6));
		$this->createPersonalReferenceFactoryStub($this->at(7));
		$application = $this->createApplication($data, $this->getValidAsynchResult());
		$vehicle_data['modifying_agent_id'] = self::AGENT_ID;
		$vehicle_data['application_id'] = $data['application_id'];
		$expected = array($vehicle_data);
		$this->assertTrue($this->_state->isPart('vehicle'));
		$actual = $this->_state->vehicle->getData();
		ksort($expected[0]);
		ksort($actual[0]);
		$this->assertEquals($expected, $actual);
		
	}
	
	public function testCreateApplicationPersonalReferences()
	{
		$data = $this->getApplicationDataArray();
		$expected = $data['personal_reference'];
		$expected[0]['application_id'] = $data['application_id'];
		$expected[1]['application_id'] = $data['application_id'];
		$expected[0]['company_id'] = self::COMPANY_ID;
		$expected[1]['company_id'] = self::COMPANY_ID;
		
		$this->createApplicationModelStubs($this->at(1));
		$this->createAppVersionStubs($this->at(0), $data['application_id']);
		$this->createCustomerStubs($this->at(2));
		$this->createSiteModelStubs($this->at(3));
		$this->createCampaignInfoStubs($this->at(4));
		$this->createBusinessRuleStubsForDriver();
		$this->createPersonalReferenceFactoryStub($this->at(5));
		$this->createPersonalReferenceFactoryStub($this->at(6));
		$app = $this->createApplication($data, $this->getValidAsynchResult());
		$this->assertTrue($this->_state->isPart('personal_reference'));
		$actual = $this->_state->personal_reference->getData();
		unset($actual[0]['date_created']);
		unset($actual[1]['date_created']);
		ksort($expected[0]);
		ksort($expected[1]);
		ksort($actual[0]);
		ksort($actual[1]);
		$this->assertEquals($expected, $actual);
	}
	
	public function testCreateApplicationWithReactAffiliation()
	{
		$react_app_id = 123456;
		
		$data = $this->getApplicationDataArray();
		$data['is_react'] = 1;
		$data['react_application_id'] = $react_app_id;
		
		$this->createApplicationModelStubs($this->at(1));
		$this->createAppVersionStubs($this->at(0), $data['application_id']);
		$this->createCustomerStubs($this->at(2));
		$this->createSiteModelStubs($this->at(3));
		$this->createCampaignInfoStubs($this->at(4));
		$this->createBusinessRuleStubsForDriver();
		$this->createPersonalReferenceFactoryStub($this->at(5));
		$this->createPersonalReferenceFactoryStub($this->at(6));
		
		$mock = $this->getMock('ECash_Models_ReactAffiliation', array('loadBy', 'save', 'loadByKey'), array(), '', FALSE);
		$this->_factory->expects($this->at(7))->method('getModel')
			->with('ReactAffiliation')
			->will($this->returnValue($mock));
		$app = $this->createApplication($data, $this->getValidAsynchResult());
		$this->assertTrue($this->_state->isPart('react_affiliation'));
		$actual = $this->_state->react_affiliation->getData();
		$actual = array_pop($actual);
		unset($actual['date_created']);
		$expected = array(
			'company_id' => self::COMPANY_ID,
			'agent_id' => self::AGENT_ID,
			'application_id' => $react_app_id,
			'react_application_id' => $data['application_id']
		);
		$this->assertEquals($expected, $actual);
		
	}
	
	protected function createCampaignInfoStubs($at)
	{
		$mock = $this->getMock('ECash_Models_CampaignInfo', array('save', 'loadBy'), array(), '', FALSE);
		$this->_factory->expects($at)->method('getModel')
			->with('CampaignInfo')
			->will($this->returnValue($mock));
	}
	
	protected function createSiteModelStubs($at)
	{
		$mock = $this->getMock('ECash_Models_Site', array('save', 'loadBy'), array(), '', FALSE);
		$this->_factory->expects($at)->method('getModel')
			->with('Site')
			->will($this->returnValue($mock));
	}
	
	protected function createCustomerStubs($at)
	{
		$mock = $this->getMock('ECash_Models_Customer', array('save', 'loadBy', 'getUsernameCount',  'loadBySSN'), array(), '', FALSE);
		$mock->expects($this->any())->method('getusernameCount')
			->will($this->returnValue(0));
		$this->_factory->expects($at)->method('getModel')
			->with('Customer')
			->will($this->returnValue($mock));
	}
	
	protected function createVehicleStubs($at)
	{
		$model = $this->getMock('ECash_Models_Vehicle', array('save','loadBy'), array(), '', FALSE);
		$this->_factory->expects($at)->method('getModel')
			->with('Vehicle', $this->isInstanceOf('DB_IConnection_1'))
			->will($this->returnValue($model));
	}
	
	protected function getApplicationDataArray()
	{
		return unserialize(file_get_contents(dirname(__FILE__).'/_fixtures/post_sent_data.dat'));
	}
	
	protected function getExpectedStateObject()
	{
		return unserialize(file_get_contents(dirname(__FILE__).'/_fixtures/post_sent_expected_state_object.dat'));
	}
	
	protected function getExpectedApplicationData()
	{
		return array (                         
			array (                                   
				'application_id' => '900162659',        
				'application_type' => 'paperless',      
				'bank_aba' => '101203256',              
			    'bank_account' => '71227718',           
			    'bank_account_type' => 'CHECKING',      
			    'bank_name' => 'BANK OF CREWER',        
			    'city' => 'Counterdecision',            
			    'company_id' => self::COMPANY_ID,                      
			    'date_hire' => '2009-03-22',            
			    'day_of_week' => 'MON',                 
			    'dob' => '1976-12-24',                  
			    'email' => '15866207@TSSMASTERD.COM',   
			    'employer_name' => 'NEFARIOUS TOXOPHILY',
			    'enterprise_site_id' => 2,               
			    'income_direct_deposit' => 'yes',        
			    'income_frequency' => 'WEEKLY',          
			    'income_monthly' => '6435',              
			    'income_source' => 'EMPLOYMENT',         
			    'ip_address' => '208.67.191.194',        
			    'is_react' => 'no',                      
			    'last_paydate' => '2009-06-01',          
			    'legal_id_number' => '22344737',         
			    'legal_id_state' => 'CO',                
			    'legal_id_type' => 'dl',                 
			    'modifying_agent_id' => self::AGENT_ID,              
			    'name_first' => 'ENGLANDTSSTEST',        
			    'name_last' => 'BROOMETSSTEST',          
			    'olp_process' => 'online_confirmation',  
			    'paydate_model' => 'DW',                 
			    'phone_cell' => '8705551789',            
			    'phone_home' => '8445558244',            
			    'phone_work' => '9565551575',            
			    'ssn' => '893788706',                    
			    'state' => 'CO',                         
			    'street' => '18538 THEOPATHIC ST',       
			    'track_id' => '2ptCE-FBvjwRtZTh1mQv5SHSuT0',
			    'zip_code' => '74298',    
				'cfe_rule_set_id' => self::CFE_RULE_ID,
				'loan_type_id' => self::LOAN_TYPE_ID,
				'rule_set_id' => self::RULE_SET_ID,
				'ssn_last_four' => '8706'              
  			),                                            	
		);
	}
	
	protected function getValidAsynchResult()
	{
		return new ECash_CFE_AsynchResult(self::CFE_RULE_ID, array(
			'name_short' => 'standard',
			'name' => 'standard',
			'loan_type_id' => self::LOAN_TYPE_ID
		));
	}
	
	protected function getInvalidAsynchResult()
	{
		return new ECash_CFE_AsynchResult();
	}
	protected function createPersonalReferenceFactoryStub($at) 
	{
		$model = $this->getMock('ECash_Models_PersonalReference',
			array('save', 'loadBy'), array(), '', FALSE);
		$this->_factory->expects($at)->method('getModel')
			->with('PersonalReference', $this->isInstanceOf('DB_IConnection_1'))
			->will($this->returnValue($model));
	}

	protected function createBusinessRuleStubsForDriver()
	{
		$this->_driver->expects($this->once())
			->method('getBusinessRules')
			->with(self::LOAN_TYPE_ID)
			->will($this->returnValue(array()));
		$this->_driver->expects($this->any())
			->method('getRuleSetID')
			->will($this->returnValue(self::RULE_SET_ID));
	}
	
	protected function createAppVersionStubs($factoryAt, $app_id)
	{
		$app_version = $this->getMock(
			'ECash_Models_ApplicationVersion',
			array('save', 'loadbyKey'),
			array($this->_db),
			'', 
			FALSE
		);
		$app_version->expects($this->once())->method('loadByKey')
			->with($app_id)
			->will($this->returnValue(TRUE));
		$this->_factory->expects($factoryAt)
			->method('getModel')->with('ApplicationVersion', $this->_db)
			->will($this->returnValue($app_version));
		return $app_version;
		
	}

	protected function createApplicationModelStubs($factoryAt)
	{
		$mockApp = $this->getMock('FakeApplicationModel', 
			array('save', 'loadBy', 'loadByKey'), array(), '', FALSE);
		$this->_factory->expects($factoryAt)
			->method('getModel')->with('Application', $this->_db)
			->will($this->returnValue($mockApp));
	}
	protected function createApplication($data, ECash_CFE_AsynchResult $asynch_result)
	{
					
		$asynch_engine = $this->getMock('ECash_CFE_AsynchEngine', array('beginExecution'), array(), '', FALSE);
		
		$asynch_engine->expects($this->once())->method('beginExecution')
			->with($data, FALSE)
			->will($this->returnValue($asynch_result));
			
		$context = new VendorAPI_CallContext();
		$context->setApiAgentId(self::AGENT_ID);
		$context->setCompanyId(self::COMPANY_ID);
			
		$this->_state = new VendorAPI_StateObject();
		//$persistor = $this->getMock('VendorAPI_StateObjectPersistor', array(), array($this->_state));
		$persistor = new VendorAPI_StateObjectPersistor($this->_state);
		$appfactory = $this->getMock(
			'ECash_VendorAPI_ApplicationFactory',
			array('getQualify', 'getAsynchEngine'), 
			array($this->_driver, $this->_factory, $this->_db)
		);
		
		$appfactory->expects($this->any())->method('getAsynchEngine')
			->with($this->isInstanceOf('DB_IConnection_1', $context->getCompanyId()))
			->will($this->returnValue($asynch_engine));
			
		$appfactory->expects($this->any())->method('getQualify')->
			will($this->returnValue($this->getMock('VendorAPI_IQualify')));
			
		return $appfactory->createApplication($persistor, $this->_state, $context, $data);
	}
}

