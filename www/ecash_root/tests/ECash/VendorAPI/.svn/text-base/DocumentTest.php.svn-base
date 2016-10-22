<?php

class ECash_VendorAPI_DocumentTest extends PHPUnit_Framework_TestCase
{
	protected $_condor;
	protected $_factory;
	protected $_doc_list;
	protected $_context;
	protected $_document_hash;
		
	protected $fake_tokens = array(
		'LoanFundAmount' => "300.00"
	);
	
	const TEMPLATE_NAME = 'Test Template';
	const DOC_LIST_SHORT = 'test_template';
	const DOC_LIST_ID = 1;
	
	const COMPANY_ID = 1;
	CONST SYSTEM_ID = 3;
	
	const APPLICATION_ID = 9999;
	const TRACK_ID = 'mytrack'; 
	const ARCHIVE_ID = 12;
	
	const API_AGENT_ID = 3;
		
	public function setUp()
	{
		$this->_condor = $this->getMock('ECash_Documents_Condor');
		$this->_factory = $this->getMock('ECash_Factory', array(), array(), '', FALSE);
		$this->_context = new VendorAPI_CallContext();
		$this->_context->setCompanyId(self::COMPANY_ID);
		$this->_context->setApiAgentId(self::API_AGENT_ID);
	}

	/**
	 * 
	 * @expectedException VendorAPI_DocumentNotFoundException
	 */
	public function testViewDocExceptionOnNotFound()
	{
		$this->_condor->expects($this->once())->
			method('Find_By_Archive_Id')->with(1)
			->will($this->returnValue(FALSE));
		$document = new ECash_VendorAPI_Document($this->_condor, $this->_factory);
		$document->getByArchiveId(1);	
	}
	
	public function testReturnsVendorAPI_Document()
	{
		$doc_data = new stdClass();
		$doc_data->document_id = 1;
		$doc_data->template_name = "test";
		$this->_condor->expects($this->once())
			->method('Find_By_Archive_Id')->with(1)
			->will($this->returnValue($doc_data));
		$document = new ECash_VendorAPI_Document($this->_condor, $this->_factory);
		$this->assertThat($document->getByArchiveId(1), $this->isInstanceOf('VendorAPI_DocumentData'));
	}

	public function testCreate()
	{
		$app = $this->createMockApplication(new VendorAPI_StateObject());
		$token_provider = $this->createMockTokenProvider($app);		
		$this->mockCondorAPICreateMethod();
		$this->createDocumentListMock();
		$document = new ECash_VendorAPI_Document($this->_condor, $this->_factory);
			
		$document->create(self::TEMPLATE_NAME, $app, $token_provider, $this->_context);
	}
	
	public function testCreateReturnsDocumentData()
	{
		$app = $this->createMockApplication(new VendorAPI_StateObject());
		$provider = $this->createMockTokenProvider($app);
		
		$this->mockCondorAPICreateMethod();
		$this->createDocumentListMock();
		$document = new ECash_VendorAPI_Document($this->_condor, $this->_factory);
		
		$doc = $document->create(self::TEMPLATE_NAME, $app, $provider, $this->_context);
		$this->assertThat($doc, $this->isInstanceOf('VendorAPI_DocumentData'));
	}
	
	public function testCreateDocumentDataHasArchiveId()
	{
		$app = $this->createMockApplication(new VendorAPI_StateObject());
		$provider = $this->createMockTokenProvider($app);
		
		$this->mockCondorAPICreateMethod();
		$this->createDocumentListMock();
		$document = new ECash_VendorAPI_Document($this->_condor, $this->_factory);
		
		$doc = $document->create(self::TEMPLATE_NAME, $app, $provider, $this->_context);
		$this->assertEquals(self::ARCHIVE_ID, $doc->getDocumentId());
	}
	
	public function testCreateDocumentDataHasListId()
	{
		$app = $this->createMockApplication(new VendorAPI_StateObject());
		$provider = $this->createMockTokenProvider($app);
		
		$this->mockCondorAPICreateMethod();
		$this->createDocumentListMock();
		$document = new ECash_VendorAPI_Document($this->_condor, $this->_factory);
		
		$doc = $document->create(self::TEMPLATE_NAME, $app, $provider, $this->_context);
		$this->assertEquals(self::DOC_LIST_ID, $doc->getDocumentListId());
	}
	
	public function testDocumentPreviewReturnsDocumentData()
	{
		$app = $this->createMockApplication(new VendorAPI_StateObject());
		$provider = $this->createMockTokenProvider($app);
		$this->createDocumentListMock();
		$this->mockCondorAPICreateForPreview();
		
		$document = new ECash_VendorAPI_Document($this->_condor, $this->_factory);
		$doc = $document->previewDocument(self::TEMPLATE_NAME, $provider->getTokens($app, FALSE), $this->_context);
		$this->assertThat($doc, $this->isInstanceOf('VendorAPI_DocumentData')); 
	}
	
	public function testDocumentMatchesHashReturnsTrueWhenMatch()
	{
		$app = $this->createMockApplication(new VendorAPI_StateObject());
		$provider = $this->createMockTokenProvider($app);
		$this->mockCondorAPICreateForPreview();
		$this->createDocumentListMock();
		$app->expects($this->once())->method('getDocumentHash')->with(self::DOC_LIST_ID, self::COMPANY_ID)
			->will($this->returnValue(sha1("Hello World")));
		$document = new ECash_VendorAPI_Document($this->_condor, $this->_factory);

		$b = $document->documentMatchesHash($app, self::TEMPLATE_NAME, $provider->getTokens($app, FALSE), $this->_context);
		$this->assertTrue($b);
	}
	
	public function testDocumentMatchesHashReturnsFalseWhenNoMatch()
	{
		$app = $this->createMockApplication(new VendorAPI_StateObject());
		$provider = $this->createMockTokenProvider($app);
		$this->mockCondorAPICreateForPreview();
		$this->createDocumentListMock();
	
		$app->expects($this->once())->method('getDocumentHash')->with(self::DOC_LIST_ID, self::COMPANY_ID)
			->will($this->returnValue(sha1("Not Hello World")));
		$document = new ECash_VendorAPI_Document($this->_condor, $this->_factory);

		$b = $document->documentMatchesHash($app, self::TEMPLATE_NAME, $provider->getTokens($app, FALSE), $this->_context);
		$this->assertFalse($b);
	}
	
	public function testDocumentMatchesHashReturnsFalseWhenNoModel()
	{
		$app = $this->createMockApplication(new VendorAPI_StateObject());
		$provider = $this->createMockTokenProvider($app);
		$this->createDocumentListMock();
		$app->expects($this->once())->method('getDocumentHash')->with(self::DOC_LIST_ID, self::COMPANY_ID)
			->will($this->returnValue(NULL));
		
		$document = new ECash_VendorAPI_Document($this->_condor, $this->_factory);

		$b = $document->documentMatchesHash($app, self::TEMPLATE_NAME, $provider->getTokens($app, FALSE), $this->_context);
		$this->assertTrue($b);
	}
	
	protected function mockCondorAPICreateForPreview()
	{
		$document = new stdClass();
		$document->data = "Hello World";
		
		$this->_condor->expects($this->once())->method('Create')
			->with(self::TEMPLATE_NAME, $this->isType('array'), FALSE, NULL, NULL, NULL, TRUE)
			->will($this->returnValue($document));
	
	}
	protected function mockCondorAPICreateMethod()
	{
		$this->_condor->expects($this->once())->method('Create')
			->with(self::TEMPLATE_NAME, $this->fake_tokens, TRUE, self::APPLICATION_ID, self::TRACK_ID)
			->will($this->returnValue(array('archive_id' => self::ARCHIVE_ID, 'document' => new stdClass)));
	}
	
	
	
	protected function createMockApplication($state)
	{
		$qmock = $this->getMock('VendorAPI_IQualify');
		$app_model_mock = $this->getMock('ECash_Models_Application', array(), array(), '', FALSE);
		$app_model_version_mock = $this->getMock('ECash_Models_ApplicationVersion', array(), array(), '', FALSE);
		$stat_mock = $this->getMock('VendorAPI_StatProClient', array(), array(), '', FALSE);
		$mock_persistor = $this->getMock('VendorAPI_IModelPersistor');
		
		$mock_app = $this->getMock('ECash_VendorAPI_Application', 
			array('getApplicationId', 'getTrackId', 'getDocumentHash'),
			array($mock_persistor, $app_model_mock, $app_model_version_mock, $state, $qmock, $this->_factory, $stat_mock),
		 	'', TRUE);
		$mock_app->expects($this->any())->method('getApplicationId')
			->will($this->returnValue(self::APPLICATION_ID));
		$mock_app->expects($this->any())->method('getTrackId')
			->will($this->returnValue(self::TRACK_ID));
		$mock_app->application_id = self::APPLICATION_ID;
		$mock_app->track_id = self::TRACK_ID;
		return $mock_app;	
	}
	
	protected function createMockTokenProvider($mock_app)
	{
		$token_provider = $this->getMock('VendorAPI_ITokenProvider');
		$token_provider->expects($this->once())->method('getTokens')
			->with($mock_app, FALSE)->will($this->returnValue($this->fake_tokens));
		return $token_provider;
	}
	
	protected function createDocumentListMock()
	{
		$this->_doc_list = $this->getMock('ECash_Models_DocumentList', 
			array('loadBy', 'save'),
			array(),
			'',
			FALSE
		);
		$this->_doc_list->company_id = self::COMPANY_ID;
		$this->_doc_list->name = self::TEMPLATE_NAME;
		$this->_doc_list->name_short = self::DOC_LIST_SHORT;
		$this->_doc_list->document_list_id = self::DOC_LIST_ID;
		$this->_doc_list->expects($this->any())
			->method('loadBy')
			->with(array(
				'name' => self::TEMPLATE_NAME,
				'company_id' => self::COMPANY_ID,
				'active_status' => 'active',
				'system_id' => self::SYSTEM_ID,
			))->will($this->returnValue(TRUE));	
		$this->_factory->expects($this->any())->method('getModel')
			->with('DocumentList')
			->will($this->returnValue($this->_doc_list));	
	}
	
}