<?php

/**
 *
 * @author stephan.soileau <stephan.soileau@sellingsource.com>
 */
class ECash_VendorAPI_ApplicationTest extends PHPUnit_Framework_TestCase
{

	public function setUp()
	{

	}

	public function testQualifyNoSet()
	{
		$app_model = $this->getMock('FakeApplicationModel', array('save', 'loadBy'), array(), '', FALSE);
		$app_model->version = 2;
		$app_model->application_id = 3;
		$app_model->fund_qualified = 200;
		$app_model->last_paydate = '2009-03-02';
		$app_model->day_of_week = "mon";
		$app_model->paydate_model = "dw";
		$app_model->is_react = "no";
		$app_model->income_direct_deposit = "yes";
		$app_model->income_monthly = "6917.00";
		$app_model->loan_type_id = 1;
		
		$expected = $app_model->getColumnData();

		$version_model = $this->getMock('ECash_Models_ApplicationVersion', null, array(), '', FALSE);
		$version_model->application_id = 3;
		$version_model->version = 2;

		$data = array();
		$data['income_monthly'] = $app_model->income_monthly;
		$data['is_react'] = $app_model->is_react;
		$data['loan_type_name'] = "standard";
		$data['income_frequency'] = $app_model->income_frequency;
		$data['paydate_model'] = $app_model->paydate_model;
		$data['last_paydate'] = $app_model->last_paydate;
		$data['day_of_week'] = $app_model->day_of_week;
		$data['day_of_month_1'] = $app_model->day_of_month_1;
		$data['day_of_month_2'] = $app_model->day_of_month_2;
		$data['week_1'] = $app_model->week_1;
		$data['week_2'] = $app_model->week_2;
		$data['income_direct_deposit'] = TRUE;

		$qualify = $this->getMock('VendorAPI_IQualify');
		$qualify->expects($this->once())->method('getQualifyInfo')
			->will($this->returnValue(
					new VendorAPI_QualifyInfo(300, 300, 644.1200, '2009-04-03', '2009-04-20', 90, 390)
			)
		);
		$factory = $this->getMock('ECash_Factory', NULL, array(), '', FALSE);
		$stat_client = $this->getMock('VendorAPI_StatProClient', NULL, array(), '', FALSE);

		$persistor = $this->getMock('VendorAPI_IModelPersistor');
	
		$application = $this->getMock(
			'ECash_VendorAPI_Application',
			array('getLoanTypeName'),
			array(
				$persistor, 
				$app_model, 
				$version_model, 
				new VendorAPI_StateObject(), 
				$qualify, 
				$factory, 
				$stat_client
			)
		);
		$application->expects($this->once())->method('getLoanTypeName')
			->with(1)->will($this->returnValue('standard'));

		$application->calculateQualifyInfo(FALSE, NULL);
		$this->assertEquals($expected, $app_model->getColumnData());

	}

	public function testQualifyWithSet()
	{
		$app_model = $this->getMock('FakeApplicationModel', NULL, array(), '', FALSE);
		$app_model->application_id = 3;
		$app_model->fund_qualified = 200;
		$app_model->last_paydate = strtotime('2009-03-02');
		$app_model->day_of_week = "mon";
		$app_model->paydate_model = "dw";
		$app_model->is_react = "no";
		$app_model->income_direct_deposit = "yes";
		$app_model->income_monthly = "6917.00";
		$app_model->loan_type_id = 1;

		$expected = $app_model->getColumnData();
		$version_model = $this->getMock('ECash_Models_ApplicationVersion', null, array(), '', FALSE);
		$version_model->application_id = 3;
		$version_model->version = 2;

		$data = array();
		$data['income_monthly'] = $app_model->income_monthly;
		$data['is_react'] = $app_model->is_react;
		$data['loan_type_name'] = "standard";
		$data['income_frequency'] = $app_model->income_frequency;
		$data['paydate_model'] = $app_model->paydate_model;
		$data['last_paydate'] = $app_model->last_paydate;
		$data['day_of_week'] = $app_model->day_of_week;
		$data['day_of_month_1'] = $app_model->day_of_month_1;
		$data['day_of_month_2'] = $app_model->day_of_month_2;
		$data['week_1'] = $app_model->week_1;
		$data['week_2'] = $app_model->week_2;
		$data['income_direct_deposit'] = TRUE;

		$qualify = $this->getMock('VendorAPI_IQualify');
		$qualify->expects($this->once())->method('getQualifyInfo')
			->will($this->returnValue(
					new VendorAPI_QualifyInfo(300, 300, 644.1200, strtotime('2009-04-03'), strtotime('2009-04-20'), 90, 390)
			)
		);

		$factory = $this->getMock('ECash_Factory', NULL, array(), '', FALSE);
		$stat_client = $this->getMock('VendorAPI_StatProClient', NULL, array(), '', FALSE);
		$state = new VendorAPI_StateObject();
		$persistor = $this->getMock('VendorAPI_IModelPersistor');
		
		$application = $this->getMock(
			'ECash_VendorAPI_Application',
			array('getLoanTypeName'),
			array(
				$persistor, 
				$app_model, 
				$version_model, 
				new VendorAPI_StateObject(), 
				$qualify, 
				$factory, 
				$stat_client
			)
		);
		$application->expects($this->once())->method('getLoanTypeName')
			->with(1)->will($this->returnValue('standard'));

		$application->calculateQualifyInfo(TRUE, NULL);
		$this->assertEquals('2009-04-03', date('Y-m-d', $app_model->date_fund_estimated));
		$this->assertEquals('2009-04-20', date('Y-m-d', $app_model->date_first_payment));
		$this->assertEquals('300', $app_model->fund_qualified);
		$this->assertEquals('644.1200', $app_model->apr);
		$this->assertEquals('90', $app_model->finance_charge);
		$this->assertEquals('390', $app_model->payment_total);

	}
	public function testRecordDocumentPreview()
	{
		$app_model = $this->getMock('ECash_Models_Application', NULL, array(), '', FALSE);
		$app_model->version = 2;
		$app_model->application_id = 3;
		$app_model->fund_qualified = 200;

		$version_model = $this->getMock('ECash_Models_ApplicationVersion', null, array(), '', FALSE);
		$version_model->application_id = 3;
		$version_model->version = 2;

		$qualify = $this->getMock('VendorAPI_IQualify');
		$factory = $this->getMock('ECash_Factory', array('getModel'), array(), '', FALSE);
		
		$stat_client = $this->getMock('VendorAPI_StatProClient', NULL, array(), '', FALSE);
		$hash_model = $this->getMock('ECash_Models_DocumentHash', array('loadBy','save'), array(), '', FALSE);
		$factory->expects($this->any())->method('getModel')->with('DocumentHash')
			->will($this->returnValue($hash_model));
		$state = new VendorAPI_StateObject();
  		$persistor = $this->getMock('VendorAPI_IModelPersistor');
	
		$application = $this->getMock(
			'ECash_VendorAPI_Application',
			array('getLoanTypeName'),
			array(
				$persistor, 
				$app_model, 
				$version_model, 
				$state, 
				$qualify, 
				$factory, 
				$stat_client
			)
		);
		
		$application->expects($this->any())->method('getApplicationId')
			->will($this->returnValue(99999));
	
		$context = new VendorAPI_CallContext();
		$context->setApiAgentId(2);
		$context->setCompanyId(3);
		
		$document = $this->getMock('VendorAPI_DocumentData', array('getDocumentId', 'getDocumentListId', 'getHash'));

		$document->expects($this->any())->method('getDocumentId')
			->will($this->returnValue(24));
		$document->expects($this->any())->method('getDocumentListId')
			->will($this->returnValue(2));
		$document->expects($this->any())->method('getHash')
			->will($this->returnValue('abcdefg'));
 		$persistor->expects($this->once())->method('save')->with($this->isInstanceOf('ECash_Models_DocumentHash'));	
		$application->recordDocumentPreview($document, $context);

		$this->assertEquals($app_model->application_id, $hash_model->application_id);
		$this->assertEquals($context->getCompanyId(), $hash_model->company_id);
		$this->assertEquals($document->getHash(), $hash_model->hash);
		$this->assertEquals($document->getDocumentListId(), $hash_model->document_list_id);
		$this->assertEquals('active', $hash_model->active_status);
	}

	public function testExpireDocumentHash()
	{
		$app_model = $this->getMock('ECash_Models_Application', NULL, array(), '', FALSE);
		$app_model->version = 2;
		$app_model->application_id = 3;
		$app_model->fund_qualified = 200;

		$version_model = $this->getMock('ECash_Models_ApplicationVersion', null, array(), '', FALSE);
		$version_model->application_id = 3;
		$version_model->version = 2;

		$qualify = $this->getMock('VendorAPI_IQualify');
		$factory = $this->getMock('ECash_Factory', array('getModel'), array(), '', FALSE);
		
		$stat_client = $this->getMock('VendorAPI_StatProClient', NULL, array(), '', FALSE);
		$hash_model = $this->getMock('ECash_Models_DocumentHash', array('loadBy','save'), array(), '', FALSE);
		$factory->expects($this->any())->method('getModel')->with('DocumentHash')
			->will($this->returnValue($hash_model));
		$state = new VendorAPI_StateObject();
  		$persistor = $this->getMock('VendorAPI_IModelPersistor');
	
		$application = $this->getMock(
			'ECash_VendorAPI_Application',
			array('getLoanTypeName'),
			array(
				$persistor, 
				$app_model, 
				$version_model, 
				$state, 
				$qualify, 
				$factory, 
				$stat_client
			)
		);
		
		$application->expects($this->any())->method('getApplicationId')
			->will($this->returnValue(99999));
	
		$context = new VendorAPI_CallContext();
		$context->setApiAgentId(2);
		$context->setCompanyId(3);
		
		$document = $this->getMock('VendorAPI_DocumentData', array('getDocumentId', 'getDocumentListId', 'getHash'));

		$document->expects($this->any())->method('getDocumentId')
			->will($this->returnValue(24));
		$document->expects($this->any())->method('getDocumentListId')
			->will($this->returnValue(2));
		$document->expects($this->any())->method('getHash')
			->will($this->returnValue('abcdefg'));
 		$persistor->expects($this->once())->method('save')->with($this->isInstanceOf('ECash_Models_DocumentHash'));	
		$application->expireDocumentHash($document, $context);

		$this->assertEquals($app_model->application_id, $hash_model->application_id);
		$this->assertEquals($context->getCompanyId(), $hash_model->company_id);
		$this->assertEquals($document->getHash(), $hash_model->hash);
		$this->assertEquals($document->getDocumentListId(), $hash_model->document_list_id);
		$this->assertEquals('inactive', $hash_model->active_status);
	}
	/**
	 * 	public function __construct(
		VendorAPI_IModelPersistor $persistor,
		ECash_Models_Application $application,
		ECash_Models_ApplicationVersion $version_model,
		VendorAPI_StateObject $state_object,
		VendorAPI_IQualify $qualify,
		ECash_Factory $factory,
		VendorAPI_StatProClient $stat_client
	)
	**/
	public function testSetPaydateModelData() 
	{
		$data = array(                     
			'income_frequency'      => 'WEEKLY',
			'paydate_model' => 'DW',            
			'day_of_week' => 'MON',             
			'last_paydate' => date('Y-m-d'),    
			'day_of_month_1' => 12,             
			'day_of_month_2' => 13,             
			'week_1' => 1,                      
			'week_2' => 2);                     
		$state = new VendorAPI_StateObject();       

		$app_model = $this->getMock('FakeApplicationModel', array('loadBy', 'save', 'loadByKey'), array(), '', FALSE);
		$version_model = $this->getMock("ECash_Models_ApplicationVersion", array('loadBy', 'save', 'loadByKey'), array(), '', FALSE);
		$stat_client = $this->getMock('VendorAPI_StatProClient', array(), array(), '', FALSE);
		$qualify = $this->getMock('VendorAPI_IQualify');
		$factory = $this->getMock("ECash_Factory", array('getModel'), array(), '', FALSE);

		$persistor = new VendorAPI_StateObjectPersistor($state);
		$application = $this->getMock(                          
			'ECash_VendorAPI_Application',                  
			array('getApplicationId'),                      
			array($persistor, $app_model, $version_model, $state, $qualify, $factory, $stat_client));
		$application->setApplicationData($data);                                                           
		$this->assertEquals($data['income_frequency'], $app_model->income_frequency);                     
		$this->assertEquals($data['paydate_model'], $app_model->paydate_model);                           
		$this->assertEquals($data['day_of_week'], $app_model->day_of_week);                               
		$this->assertEquals($data['last_paydate'], $app_model->last_paydate);                             
		$this->assertEquals($data['day_of_month_1'], $app_model->day_of_month_1);                         
		$this->assertEquals($data['day_of_month_2'], $app_model->day_of_month_2);                         
		$this->assertEquals($data['week_1'], $app_model->week_1);                                         
		$this->assertEquals($data['week_2'], $app_model->week_2);                                         
	}

	public function testAddPersonalReference()
	{
		$state = new VendorAPI_StateObject();       

		$app_model = $this->getMock('FakeApplicationModel', array('loadBy', 'save', 'loadByKey'), array(), '', FALSE);
		$version_model = $this->getMock("ECash_Models_ApplicationVersion", array('loadBy', 'save', 'loadByKey'), array(), '', FALSE);
		$stat_client = $this->getMock('VendorAPI_StatProClient', array(), array(), '', FALSE);
		$qualify = $this->getMock('VendorAPI_IQualify');
		$factory = $this->getMock("ECash_Factory", array('getModel'), array(), '', FALSE);

		$now = time();
		$persistor = new VendorAPI_StateObjectPersistor($state);
		$application = $this->getMock(                          
			'ECash_VendorAPI_Application',                  
			array('getApplicationId', 'getNow'),                      
			array($persistor, $app_model, $version_model, $state, $qualify, $factory, $stat_client));
		$application->expects($this->any())->method('getNow')->will($this->returnValue($now));
		$mock_model = $this->getMock('ECash_Models_PersonalReference', array('loadBy', 'save', 'loadByKey'), array(), '', FALSE);
		$factory->expects($this->once())->method('getModel')->with('PersonalReference')->will($this->returnValue($mock_model));
		$application->expects($this->any())->method('getApplicationId')->will($this->returnValue(8));
		
		$context = new VendorAPI_CallContext();
		$context->setCompanyId(12);	
		
		$application->addPersonalReference($context, "name_full", "1234567890", "parent");
		$this->assertTrue($state->isPart('personal_reference'));                          
		$expected = array(                                                                
			array(                                                                    
				'application_id' => 8,                                            
				'company_id' => 12,                                               
				'name_full' => 'name_full',                                       
				'phone_home' => '1234567890',                                     
				'relationship' => 'parent',                                       
				'date_created' => date('Y-m-d H:i:s', $now)                       
			)                                                                         
		);                                                                                
		ksort($expected[0]);                                                              
		$actual = $state->personal_reference->getData();                                  
		ksort($actual[0]);                                                                
		$this->assertEquals($expected, $actual);                                          
	}

	public function testAddCampaignInfo()
	{
		$state = new VendorAPI_StateObject();
		$state = new VendorAPI_StateObject();       

		$app_model = $this->getMock('FakeApplicationModel', array('loadBy', 'save', 'loadByKey'), array(), '', FALSE);
		$version_model = $this->getMock("ECash_Models_ApplicationVersion", array('loadBy', 'save', 'loadByKey'), array(), '', FALSE);
		$stat_client = $this->getMock('VendorAPI_StatProClient', array(), array(), '', FALSE);
		$qualify = $this->getMock('VendorAPI_IQualify');
		$factory = $this->getMock("ECash_Factory", array('getModel'), array(), '', FALSE);
		$context = new VendorAPI_CallContext();
		$context->setCompanyId(24);

		$now = time();
		$persistor = new VendorAPI_StateObjectPersistor($state);
		$application = $this->getMock(                          
			'ECash_VendorAPI_Application',                  
			array('getApplicationId', 'getNow'),                      
			array($persistor, $app_model, $version_model, $state, $qualify, $factory, $stat_client));
		$application->expects($this->any())->method('getNow')->will($this->returnValue($now));
		
		$mock_ci = $this->getMock('ECash_Models_CampaignInfo', array('loadBy', 'save', 'loadByKey'), array(), '', FALSE);
		$factory->expects($this->at(1))->method('getModel')->with('CampaignInfo')->will($this->returnValue($mock_ci));
		
		$mock_site = $this->getMock('ECash_Models_Site', array('loadBy', 'save', 'loadByKey'), array(), '', FALSE);
		$factory->expects($this->at(0))->method('getModel')->with('Site')->will($this->returnValue($mock_site)); 
		$application->expects($this->any())->method('getApplicationId')->will($this->returnValue(8));
		
		$application->addCampaignInfo($context, "license_key", "site", "promo_id", "sub_code", "cam");
		$this->assertTrue($state->ispart('campaign_info'));
		
		$actual = $state->campaign_info->getData();
		$actual = array_pop($actual);
		$this->assertThat($actual['site_id'], $this->isInstanceOf('VendorAPI_ReferenceColumn_Locator'));
		unset($actual['site_id']);
		$expected = array (
			'application_id' => 8,
			'company_id' => 24,
			'date_created' => date('Y-m-d H:i:s', $now),
			'promo_id' => 'promo_id',
			'promo_sub_code' => 'sub_code',
		);
		$this->assertEquals($expected, $actual);		
	}

}
