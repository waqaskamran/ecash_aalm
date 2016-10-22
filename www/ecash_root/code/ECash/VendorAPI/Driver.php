<?php
/**
 * Commercial version of VendorAPI_IDriver
 *
 * @author Raymond Lopez <raymond.lopez@selingsource.com>
 */
class ECash_VendorAPI_Driver extends VendorAPI_BasicDriver
{
	const SYSTEM_ID = 4;

	/**
	 * @var ECash_Config
	 */
	protected $config;

	/**
	 * @var ECash_Factory
	 */
	protected $factory;

	/**
	 * @var DB_IConnection_1
	 */
	protected $db;
	
	/**
	 * @var AppClient
	 */
	protected $app_client;
	
	/**
	 * @var InquiryClient
	 */
	protected $inquiry_client;

	/**
	 * @var array
	 */
	protected $business_rules;

	/**
	 * @var int
	 */
	protected $rule_set_id;

	/**
	 *
	 * @var VendorAPI_RequestTimer
	 */
	protected $request_timer;

	/**
	 * @var DB_IConnection_1
	 */
	protected $state_object_db;

	protected $statpro;

	/**
	 * @param ECash_Config $config
	 * @param ECash_Factory $factory
	 * @param DB_IConnection_1 $db
	 * @param ECash_Company $company
	 * @param Log_ILog_1 $log
	 * @param boolean $use_master
	 */
	public function __construct(ECash_Config $config, ECash_Factory $factory, VendorAPI_RequestTimer $timer, DB_IConnection_1 $db, ECash_Company $company, Log_ILog_1 $log, $use_master = FALSE)
	{
		$this->config = $config;
		$this->factory = $factory;
		$this->request_timer = $timer;
		$this->enterprise = $config->ENTERPRISE_PREFIX;
		$this->company = $company->getModel()->name_short;
		$this->company_id = $company->getCompanyId();
		$this->db = $db;
		$this->log = $log;
		$this->use_master = $use_master;
	}

	/**
	 * Gets the action class for the specified API call
	 *
	 * @param string $name
	 * @return object
	 */
	public function getAction($name)
	{
		$method_name = "get{$name}Action";
		if (method_exists($this, $method_name))
		{
			return $this->$method_name();
		}

		$class = "ECash_VendorAPI_Actions_{$name}";
		if (class_exists($class))
		{
			return new $class($this);
		}

		return parent::getAction($name);
	}

	/**
	 * Returns a new PreAgree Action
	 *
	 * @return ECash_VendorAPI_Actions_PreAgree
	 */
	protected function getPreAgreeAction()
	{
		return new VendorAPI_Actions_PreAgree($this, $this->getApplicationFactory());
	}

	/**
	 * Creates the Fail action
	 * @return VEndorAPI_Actions_Fail
	 */
	protected function getFailAction()
	{
		return new VendorAPI_Actions_Fail(
			$this,
			$this->getApplicationFactory()
		);
	}


	/**
	 * Returns a new Application Factory
	 *
	 * @return VendorAPI_IApplicationFactory
	 */
	public function getApplicationFactory()
	{
         	return new ECash_VendorAPI_ApplicationFactory($this, $this->getFactory(), $this->getDatabase());
	}
	
	protected function getGetApplicationDataAction()
	{
		return new VendorAPI_Actions_GetApplicationData(
			$this,
			new ECash_VendorAPI_ApplicationFactory($this, $this->getFactory(), $this->getDatabase())
		);
	}
	
	/**
	 * Returns the application service object
	 * 
	 * @return AppClient
	 */
	public function getAppClient()
	{
		if (!$this->app_client)
		{
			$this->app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
		}
		
		return $this->app_client;
	}
	
	/**
	 * Returns the inquiry service object
	 * 
	 * @return VendorAPI_AppClient_InqiryClient
	 */
	public function getInquiryClient()
	{
		if (!$this->inquiry_client)
		{
			$this->inquiry_client = ECash::getFactory()->getWebServiceFactory()->getWebService('inquiry');
		}
		
		return $this->inquiry_client;
	}

	/**
	 * Returns a new Post action.
	 *
	 * @return ECash_VendorAPI_Actions_Post
	 */
	protected function getPostAction()
	{
        $class_name = strtoupper($this->enterprise) . "_VendorAPI_Actions_Post";
        if (! class_exists($class_name))
        {
            $class_name = 'VendorAPI_Actions_Post';
        }

		return new $class_name(
			$this,
			new VendorAPI_Actions_Validators_Post(),
			$this->getApplicationFactory(),
			new VendorAPI_CFE_RulesetFactory(new VendorAPI_CFE_Factory()),
			$this->getAppClient()
        	);
	}

	/**
	 * This grabs a qualify object, and will set up the
	 * business rules and whatnots if the loan type id
	 * is passed into it.
	 *
	 * @return VendorAPI_IQualify
	 */
	public function getQualify($loan_type_id = NULL)
	{
		$qualify = $this->getQualifyObject();
		if (is_numeric($loan_type_id))
		{
			$this->setQualifyLoanType($qualify, $loan_type_id);
		}
		return $qualify;
	}

	/**
	 * Loads the business rules and sets them on a qualify
	 * object
	 *
	 * @param VendorAPI_IQualify$qualify
	 * @param Integer $loan_type_id
	 * @return Boolean
	 */
	public function setQualifyLoanType(VendorAPI_IQualify $qualify, $loan_type_id)
	{
		$rules = $this->getBusinessRules($loan_type_id);
		$qualify->setBusinessRules($rules);
		$qualify->setLoanTypeId($loan_type_id);
	}



	/**
	 * Returns qualify object. This object will have no business rules
	 * in it, so you'll need to set them.. See ECash_VendorAPI_Driver::getQualify
	 * if you need a method that sets the business rules.
	 *
	 * @return VendorAPI_IQualify
	 */
	public function getQualifyObject()
	{
		include_once 'mysqli.1.php';
		include_once 'pay_date_calc.3.php';
		$method = 'get'.ucwords($this->getEnterprise()).'Qualify';
		if (method_exists($this, $method))
		{
			return $this->$method();
		}
		else
		{
			include_once getenv('ECASH_COMMON_DIR') . 'ecash_api/ecash_api.2.php';
			include_once getenv('ECASH_COMMON_DIR') . 'ecash_api/loan_amount_calculator.class.php';
			include_once getenv('ECASH_COMMON_DIR') . 'ecash_api/interest_calculator.class.php';

			$holiday_list = $this->getHolidayList();
			$pdc = new Pay_Date_Calc_3($holiday_list);

			return new ECash_VendorAPI_Qualify(
				new ECash_Qualify(array(), $pdc),
				$pdc,
				eCash_API_2::Get_eCash_API($this->getCompany(), $this->getDatabase(), -1, $this->getCompanyID()),
				LoanAmountCalculator::Get_Instance(
					$this->getDatabase(),
					$this->getCompany()
				),
				$this->getCompany(),
				NULL,
				NULL,
				$this->getFactory()
			);

		}
	}

	/**
	 * Return the qualify object specific to HMS
	 *
	 * @return VendorAPI_IQualify
	 */
	public function getHmsQualify()
	{
		include_once 'qualify.2.php';
		include_once getenv('ECASH_COMMON_DIR') . 'ecash_api/loan_amount_calculator.class.php';

		$holiday_list = $this->getHolidayList();
		// Currently HMS uses the original Qualify_2 code, but uses the QualifyLoanAmountCalculator
		return new HMS_VendorAPI_Qualify(
			new Qualify_2(
				$this->getCompany(),
				$holiday_list,
				new stdClass(),
				new ECash_Legacy_MySQLiAdapter($this->getDatabase())
			),
			new Pay_Date_Calc_3($holiday_list),
			LoanAmountCalculator::Get_Instance($this->getDatabase(), $this->getCompany())
		);
	}

	/**
	 * Return qualify for Impact
	 *
	 * @return VendorAPI_IQualify
	 */
	public function getImpactQualify()
	{
		include_once 'qualify.2.php';

		$holiday_list = $this->getHolidayList();
		// Currently Impact use the original Qualify_2 code
		return new IMPACT_VendorAPI_Qualify(
			new Qualify_2(
				$this->getCompany(),
				$holiday_list,
				new stdClass(),
				new ECash_Legacy_MySQLiAdapter($this->getDatabase())
			),
			new Pay_Date_Calc_3($holiday_list)
		);
	}

	/**
	 * Returns the Vendor API Authenticator
	 * @return VendorAPI_Authenticator
	 */
	public function getAuthenticator()
	{
		if (!$this->auth instanceof VendorAPI_IAuthenticator)
		{
			$security = new ECash_Security(SESSION_EXPIRATION_HOURS, $this->db);

			$acl = ECash::getAcl($this->db);
			$acl->setSystemId(self::SYSTEM_ID);

			$this->auth = new ECash_VendorAPI_Authenticator($this->getCompanyId(), $security, $acl);
		}
		return $this->auth;
	}

	/**
	 * Gets a database connection
	 *
	 * NOTE: Since all commercial enterprises currently share databases
	 * between all companies, we don't do anything fancy with $company.
	 *
	 * @param string $company
	 * @return DB_IConnection_1
	 */
	public function getDatabase($company = NULL)
	{
		// failover order is determined in loader...
		return $this->db;
	}

	/**
	 * Returns a new TSS_DataX_IRequest object.
	 *
	 * @param int $loan_type_id
	 * @return TSS_DataX_IRequest
	 */
	public function getDataXRequest($loan_type_id)
	{
		// Try and get the DataX request class name from the ECash_Factory
		$class_name = $this->factory->getClassString('DataX_Request');
		// Default to TSS_DataX_Request if no valid request can be found in the factory
		if (!class_exists($class_name)) $class_name = 'TSS_DataX_Request';

		return new $class_name(
			$this->config->DATAX_LICENSE_KEY,
			$this->config->DATAX_PASSWORD,
			$this->getDataXCallType($loan_type_id)
		);
	}

	/**
	 * Returns a TSS_DataX_IResponse object.
	 *
	 * @param int $loan_type_id
	 * @return TSS_DataX_IResponse
	 */
	public function getDataXResponse($loan_type_id)
	{
		$this->getBusinessRules($loan_type_id);
		// If a response class name is defined in the business rules, use that class name
		if (isset($this->business_rules['datax_call_types'])
			&& is_array($this->business_rules['datax_call_types'])
			&& isset($this->business_rules['datax_call_types']['perf_response_class']))
		{
			$class = $this->business_rules['datax_call_types']['perf_response_class'];
		}
		// Otherwise default to the perf response class name
		else
		{
			$class = 'DataX_Responses_Perf';
		}
		return $this->factory->getClass($class);
	}

	/**
	 * Returns the call type for the DataX call.
	 *
	 * @param int $loan_type_id
	 * @return string
	 */
	public function getDataXCallType($loan_type_id)
	{
		$this->getBusinessRules($loan_type_id);
		return $this->business_rules['IDV_CALL'];
	}

	/**
	 * Returns the VendorAPI_StatProClient
	 * @return VendorAPI_StatProClient
	 */
	public function getStatProClient()
	{
		if (!$this->statpro)
		{
			$mode = (EXECUTION_MODE == "LIVE") ? "live" : "test";
			$statpro_key = 'spc_' . $this->config->STATPRO_USERNAME. '_' . $mode;
			$history = new VendorAPI_StatPro_Unique_ApplicationEventHistory(
				ECash::getDb("DB_STATUNIQUE_CONFIG"));
			$this->statpro = new VendorAPI_StatPro_Unique_Client(
				$statpro_key,
				$this->config->STATPRO_USERNAME,
				$this->config->STATPRO_PASSWORD);
			$this->statpro->addHistory($history);
			$this->statpro->setLog($this->log);
		}

		return $this->statpro;
	}

	/**
	 * Returns the factory.
	 *
	 * @return ECash_Factory
	 */
	public function getFactory()
	{
		return $this->factory;
	}

	/**
	 * Returns a DB_Models_DatabaseModel_1 object.
	 *
	 * @param String $table
	 * @param DB_IConnection_1 $db
	 * @return DB_Models_DatabaseModel_1
	 */
	public function getDataModelByTable($table, DB_IConnection_1 $db = NULL)
	{
		return $this->factory->getModelByTable($table, $db, FALSE);
	}

	/**
	 * Instantiates and returns an instance of Commercial's LoanAmountCalculator
	 * @return LoanAmountCalculator
	 */
	public function getECashCalculator()
	{
		// impact used qualify_2 on the OLP side, but the LoanAmountCalculator
		// on the Ecash side -- these DON'T calculate react amounts the same...
		// please resolve this at your earliest convenience!
		if (strcasecmp($this->getEnterprise(), 'impact') === 0)
		{
			require_once ECASH_COMMON_DIR.'/ecash_api/qualify_loan_amount_calculator.class.php';
			return new QualifyLoanAmountCalculator($this->getDatabase());
		}
		return LoanAmountCalculator::Get_Instance(
			$this->getDatabase(),
			$this->getCompany()
		);
	}

	/**
	 * Defined by VendorAPI_IDriver
	 *
	 * @return string
	 */
	public function getMode()
	{
		return getenv('ECASH_EXEC_MODE');
	}

	/**
	 * Gets business rules
	 *
	 * @param Integer $loan_type_id
	 * @return array
	 */
	public function getBusinessRules($loan_type_id)
	{
		if (!$this->business_rules)
		{
			$br = new ECash_BusinessRules($this->getDatabase());
			$this->rule_set_id = $br->Get_Current_Rule_Set_Id($loan_type_id);
			return $this->business_rules = $br->Get_Rule_Set_Tree($this->rule_set_id);
		}
		return $this->business_rules;
	}

	/**
	 * Gets the ruleset id
	 *
	 * @return Integer
	 */
	public function getRuleSetID()
	{
		return $this->rule_set_id;
	}

	/**
	 * Creates and returns a VendorAPI_Blackbox_Rule_Factory instance.
	 *
	 * @param Blackbox_Config $config
	 * @param int $loan_type_id
	 * @return VendorAPI_Blackbox_Rule_Factory
	 */
	public function getBlackboxRuleFactory(Blackbox_Config $config, $loan_type_id)
	{
		$class = $this->factory->getClassString('VendorAPI_Blackbox_Rule_Factory');
		if (class_exists($class))
		{
			return new $class($this, $config, $loan_type_id);
		}
		else
		{
			return new ECash_VendorAPI_Blackbox_Rule_Factory($this, $config, $loan_type_id);
		}
	}

	/**
	 * Returns the holiday list as an array
	 *
	 * @return array
	 */
	protected function getHolidayList()
	{
		$holidays = new Date_BankHolidays_1(NULL, Date_BankHolidays_1::FORMAT_ISO);
		return  $holidays->getHolidayArray();
	}

	/**
	 * Defined by VendorAPI_IDriver.
	 *
	 * @return string
	 */
	public function getEnterpriseSiteLicenseKey()
	{
		return $this->config->ENTERPRISE_SITE_LICENSE_KEY;
	}

	/**
	 * Defined by VendorAPI_IDriver.
	 *
	 * @return int
	 */
	public function getEnterpriseSiteId()
	{
		static $site_id;

		if (!is_int($site_id))
		{
			$site_model = $this->factory->getModel('Site', $this->getDatabase());
			$site_model->loadBy(array(
				'license_key' => $this->getEnterpriseSiteLicenseKey(),
				'active_status' => 'active'
			));

			$site_id = (int)$site_model->site_id;
		}

		return $site_id;
	}
	
	/**
	 * Defined by VendorAPI_IDriver.
	 *
	 * @return string
	 */
	public function getEnterpriseSiteName()
	{
		static $site_name;

		if (is_null($site_name))
		{
			$site_model = $this->factory->getModel('Site', $this->getDatabase());
			$site_model->loadBy(array(
				'license_key' => $this->getEnterpriseSiteLicenseKey(),
				'active_status' => 'active'
			));

			$site_name = $site_model->name;
		}

		return $site_name;
	}
	
	/**
	 * Defined by VendorAPI_IDriver.
	 *
	 * @return string
	 */
	public function getEnterpriseSiteURL()
	{
		static $site_url;

		if (is_null($site_url))
		{
			if (trim($this->config->ENTERPRISE_SITE_URL) != "") 
			{
				$site_url = $this->config->ENTERPRISE_SITE_URL;
			}
			else
			{
				$site_url = ($this->config->IS_HTTPS ? "https://": "http://") . $this->config->SITE_PREFIX . $this->getEnterpriseSiteName();
			}
		}

		return $site_url;
	}
	
	

	/**
	 * Defined by VendorAPI_Actions_Post
	 *
	 * @return bool
	 */
	public function runQualifyFirst()
	{
		return (isset($this->config->RUN_QUALIFY_FIRST) && $this->config->RUN_QUALIFY_FIRST);
	}

	/**
	 * Returns the new getTokens action with the
	 * correct provider
	 *
	 * @return VendorAPI_IAction
	 */
	protected function getGetTokensAction()
	{
		return new VendorAPI_Actions_GetTokens(
			$this,
			new ECash_VendorAPI_TokenProvider($this),
			 $this->getApplicationFactory()
		);
	}

	protected function getViewDocumentAction()
	{
		return new VendorAPI_Actions_ViewDocument(
			$this->getApplicationFactory(),
			new ECash_VendorAPI_Document(new ECash_Documents_Condor(), $this->getFactory()),
			new VendorAPI_Actions_Validators_ViewDocument,
			$this
		);
	}

	protected function getPreviewDocumentAction()
	{
		return new VendorAPI_Actions_PreviewDocument(
			$this,
			$this->getApplicationFactory(),
			new ECash_VendorAPI_TokenProvider($this),
			new ECash_VendorAPI_Document(new ECash_Documents_Condor(), $this->getFactory())
		);
	}

	protected function getSignDocumentAction()
	{
		return new VendorAPI_Actions_SignDocument(
			 $this->getApplicationFactory(),
			new ECash_VendorAPI_Document(new ECash_Documents_Condor(), $this->getFactory()),
			new ECash_VendorAPI_TokenProvider($this),
			$this
		);
	}

	protected function getFindDocumentAction()
	{
		return new VendorAPI_Actions_FindDocument(
			$this,
			new ECash_VendorAPI_ApplicationFactory($this, $this->getFactory(), $this->getDatabase()),
			new ECash_VendorAPI_Document(new ECash_Documents_Condor(), $this->getFactory())
		);
	}

	protected function getGetPageAction()
	{
		return new VendorAPI_Actions_GetPage(
			$this,
			new VendorAPI_CFE_RulesetFactory(
				new VendorAPI_CFE_Factory()
			),
			 $this->getApplicationFactory()
		);
	}

	protected function getSubmitPageAction()
	{
		return new VendorAPI_Actions_SubmitPage(
			$this,
			 $this->getApplicationFactory(),
			new ECash_VendorAPI_TokenProvider($this),
			new ECash_VendorAPI_Document(new ECash_Documents_Condor(), $this->getFactory()),
			new VendorAPI_CFE_RulesetFactory(
				new VendorAPI_CFE_Factory()
			),
			$this->getAppClient(),
			new VendorAPI_Actions_Validators_SubmitPage()
		);
	}
	/**
	 * Returns a DOMDocument with a cfe xml config
	 * @return unknown_type
	 */
	public function getCfeConfig($basefile)
	{
		$file = sprintf(
			'%s/code/%s/Config/VendorAPI/%s.xml',
			getEnv('ECASH_CUSTOMER_DIR'),
			strtoupper($this->getEnterprise()),
			$basefile
		);
		if (!file_exists($file))
		{
			$ecash_config_dir = realpath(getEnv('ECASH_WWW_DIR').'../config/');
			$file = sprintf(
				'%s/VendorAPI/%s.xml',
				$ecash_config_dir,
				$basefile
			);
			if (!file_exists($file))
			{
				throw new RuntimeException('Could not load pageflow config ('.$file.').');
			}
		}
		$doc = new DOMDocument();
		$doc->load($file);
		return $doc;
	}
	
	/**
	 * Returns a DOMDocument with a cfe xml config
	 * @return unknown_type
	 */
	public function getPageFlowConfig()
	{
		return $this->getCfeConfig('page_flow');
	}

	public function getPostConfig()
	{
		return $this->getCfeConfig('post');
	}

	public function getGetStatusAction()
	{
		return new VendorAPI_Actions_GetStatus(
			$this,
			 $this->getApplicationFactory()
		);
	}
	
	public function getWithdrawAction()
	{
		return new VendorAPI_Actions_Withdraw(
			$this,
			 $this->getApplicationFactory()
		);
	}
	
	public function getHandleTriggersAction()
	{
		return new VendorAPI_Actions_HandleTriggers (
			$this,
			new ECash_VendorAPI_ApplicationFactory($this, $this->getFactory(), $this->getDatabase())
		);
	}
	
	public function getPreConfirmAction()
	{
		return new VendorAPI_Actions_PreConfirm (
			$this,
			new ECash_VendorAPI_ApplicationFactory($this, $this->getFactory(), $this->getDatabase())
		);
	}
	
	public function getPreviousCustomerAction()
	{
		return new VendorAPI_Actions_PreviousCustomer (
			$this,
			new ECash_VendorAPI_ApplicationFactory($this, $this->getFactory(), $this->getDatabase())
		);
	}	
	
	public function getLoginAction()
	{
		return new VendorAPI_Actions_Login(
			$this,
			new ECash_VendorAPI_ApplicationFactory($this, $this->getFactory(), $this->getDatabase())
		);
	}
	
	public function getGetLoanDocumentAction()
	{
		return new VendorAPI_Actions_GetLoanDocument(
			$this->getApplicationFactory(),
			new ECash_VendorAPI_Document(new ECash_Documents_Condor(), $this->getFactory()),
			new VendorAPI_Actions_Validators_ViewDocument,
			$this
		);
	}
	
	protected function getGetLoanDocumentPreviewAction()
	{
		return new VendorAPI_Actions_GetLoanDocumentPreview(
			$this->getApplicationFactory(),
			new ECash_VendorAPI_Document(new ECash_Documents_Condor(), $this->getFactory()),
			$this,
			new ECash_VendorAPI_TokenProvider($this)			
		);
	}
	
	public function getMemcachePool()
	{
		$memcache = new Memcache();
		if (!empty($this->config->VAPI_MEMCACHE_SERVERS))
		{
			foreach ($this->config->VAPI_MEMCACHE_SERVERS as $server)
			{
				$memcache->addServer($server['host'], $server['port']);
			}
		}
		return $memcache;
	}

	/**
	 * Get the sites configuration
	 * @param string 32+ character license
	 * @param $promo_id int Promo ID
	 * @param $promo_sub_code int Promo Sub Code
	 * @return Returns the site configuration as an object
	 */
	public function getSiteConfig($license, $promo_id = NULL, $promo_sub_code = NULL)
	{
		require_once 'config.6.php';
		require_once 'mysql.4.php';

		$config = ECash::getConfig();
		$db = new MySQL_4(
			$config->STAT_MYSQL_HOST,
			$config->STAT_MYSQL_USER,
			$config->STAT_MYSQL_PASS
		);
		$db->Connect();
		// The following is a quirk in how Config_6 is using MySQL_4
		$db->db_info['db'] = 'management';
		$db->Select('management');
		$config_6 = new Config_6($db);

		return $config_6->Get_Site_Config($license, $promo_id, $promo_sub_code);
	}

	/**
	 * Retrieves the Request Timer
	 * 
	 * @return VendorAPI_RequestTimer
	 */
	public function getTimer()
	{
		return $this->request_timer;
	}
	
	/**
	 * Retrieves the document client
	 * 
	 * @return ECash_WebService_DocumentClient
	 */
	public function getDocumentClient()
	{
		if (!$this->document_client)
		{
			$this->document_client = $this->factory->getDocumentClient();
		}
		
		return $this->document_client;
	}

	/**
	 * Returns the database connection holding the state objects.
	 * @return DB_IConnection_1
	 */
	public function getStateObjectDB()
	{
		if (empty($this->state_object_db))
		{
			$this->state_object_db = ECash::getStateObjectDB();
		}

		return $this->state_object_db;
	}

	/**
	 * Used for retrieving the VendorAPI_PurchasedLeadStore_Memcache, but has to be implemented
	 * at the Enterprise level.  This is just a stub method so the Interface doesn't complain.
	 */
	public function getPurchasedLeadStore()
	{
		return FALSE;
	}
}

