<?php
require_once COMMON_LIB_DIR.'/security.8.php';
define('PASSWORD_ENCRYPTION', 'ENCRYPT');

/**
 * ECash Commercial application factory
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class ECash_VendorAPI_ApplicationFactory implements VendorAPI_IApplicationFactory
{
	/**
	 * @var ECash_VendorAPI_Driver
	 */
	protected $driver;

	/**
	 * @var ECash_Factory
	 */
	protected $factory;

	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	static protected $timestamp_columns = array(
		'date_modified', 'date_created', 'date_application_status_set',
		'date_next_contact', 'date_fund_estimated', 'date_fund_actual',
		'date_first_payment', 'income_date_soap_1', 'income_date_soap_2',
		'last_paydate', 'date_hire', 'dob', 'residence_start_date', 'banking_start_date'
	);

	/**
	 * @param ECash_VendorAPI_Driver $driver
	 * @param ECash_Factory $factory
	 * @param DB_IConnection_1 $db
	 */
	public function __construct(
		ECash_VendorAPI_Driver $driver,
		ECash_Factory $factory,
		DB_IConnection_1 $db
	)
	{
		$this->driver = $driver;
		$this->factory = $factory;
		$this->db = $db;
	}

	/**
	 * Creates a new applciation object.
	 * @param VendorAPI_IModelPersistor $persistor
	 * @param VendorAPI_StateObject $state
	 * @param VendorAPI_CallContext $context
	 * @param array $data
	 * @return ECash_VendorAPI_Application
	 */
	public function createApplication(
		VendorAPI_IModelPersistor $persistor,
		VendorAPI_StateObject $state,
		VendorAPI_CallContext $context,
		array $data
	)
	{
		// this is a magic value; the actual application ID is created later, in the
		// application service, however, we need something to reference the application
		// by in the temporary persistor (see VendorAPI_IApplication::updateApplicationId)
		$application_id = 0;

		$cfe_engine = $this->getAsynchEngine($this->db, $context->getCompanyId());
		$cfe_result = $cfe_engine->beginExecution($data, FALSE);

		if ($cfe_result->getIsValid())
		{
			// endExecution is called in DAO_Application
			// which processes queue insertions, etc.
			$state->cfe_result = $cfe_result;

			$application = $this->factory->getModel('Application', $this->db, FALSE);
			$this->saveDataToModel($application, $context, $data);

			$application->application_id = $application_id;
			$this->driver->getBusinessRules($cfe_result->getLoanTypeId());
			$application->enterprise_site_id = $this->driver->getEnterpriseSiteId();
			$application->application_type = 'paperless';
			$application->legal_id_type = 'dl';
			$application->loan_type_id = $cfe_result->getLoanTypeId();
			$application->cfe_rule_set_id = $cfe_result->getRulesetId();
			$application->income_direct_deposit = $application->income_direct_deposit ? 'yes' : 'no';
			$application->rule_set_id = $this->driver->getRuleSetID();
			$application->is_react = !empty($data['is_react']) ? 'yes' : 'no';
			$application->company_id = $context->getCompanyId();
			$application->date_created = time();
			$application->age = ECash_Dates::calculateAge(date("Y-m-d", strtotime($data['dob'])));

			foreach ($data['campaign_info'] as $campaign)
			{
				$site = $this->factory->getModel('Site', $this->db, FALSE);
				$site->name = $campaign['name'];
				$site->license_key = $campaign['license_key'];
				$site->active_status = 'active';

				$site_ref = new VendorAPI_ReferenceColumn_Locator($site);
				$site_ref->addLoadByMethod('loadByLicenseKey', $campaign['license_key']);

				$model = $this->factory->getModel('CampaignInfo', $this->db, FALSE);
				$model->application_id = $application->application_id;
				$model->promo_id = $campaign['promo_id'];
				$model->promo_sub_code = $campaign['promo_sub_code'];
				$model->site_id = $site_ref;
				$model->campaign_name = $data['campaign'];
				$model->reservation_id = $campaign['reservation_id'];
				$model->company_id = $this->driver->getCompanyId();
				$model->date_created = time();
				$persistor->save($model);
			}

			if ($data['is_title_loan'])
			{
				$vehicle = $this->factory->getModel('Vehicle', $this->db, FALSE);
				$this->saveDataToModel($vehicle, $context, $data);
				$persistor->save($vehicle);
			}
			if ($data['personal_reference'])
			{
				foreach ($data['personal_reference'] as $ref)
				{
					$model = $this->factory->getModel('PersonalReference', $this->db, FALSE);
					$ref['application_id'] = $application->application_id;
					$ref['date_created'] = date('Y-m-d H:i:s');
					$this->saveDataToModel($model, $context, $ref);
					$persistor->save($model);
				}
			}
			$state->call_center = isset($data['call_center']) && $data['call_center'];
			$persistor->save($application);

			$app = new ECash_VendorAPI_Application(
				$persistor,
				$application,
				$state,
				$this->getQualify($application),
				$this->factory,
				$this->driver->getStatProClient(),
				$this->driver->getAppClient()
			);
			if (!empty($data['customer_id']))
			{
				$app->addPostData('vendor_customer_id', $data['customer_id']);
			}

			if ($application->is_react
				&& !empty($data['react_application_id']))
			{
				$app->setIsReact($data['react_application_id'], $context, $data['agent_id']);
			}
			return $app;
		}
		return FALSE;
	}

	/**
	 * Return a new asynch engine
	 * @param DB_IConnection_1 $db
	 * @param Integer $company_id
	 * @return ECash_CFE_AsynchEngine
	 */
	protected function getAsynchEngine(DB_IConnection_1 $db, $company_id)
	{
		return new ECash_CFE_AsynchEngine($db, $company_id);
	}

	protected function saveDataToModel(
		DB_Models_IWritableModel_1 $model,
		VendorAPI_CallContext $context, array $data)
	{
		$cols = $model->getColumns();
		foreach ($cols as $col)
		{
			if (!empty($data[$col]))
			{
				if (in_array($col, self::$timestamp_columns))
				{
					$model->$col = strtotime($data[$col]);
				}
				else
				{
					$model->$col = $data[$col];
				}
			}
		}
		if (in_array('company_id', $cols))
		{
			$model->company_id = $context->getCompanyId();
		}
		if (in_array('modifying_agent_id', $cols))
		{
			$model->modifying_agent_id = $context->getApiAgentId();
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see code/VendorAPI/VendorAPI_IApplicationFactory#createStateObject()
	 */
	public function createStateObject(VendorAPI_CallContext $context, $application_id = NULL)
	{
		$state_object = new VendorAPI_StateObject();

		if ($application_id != null)
		{
			$version_model = $this->getApplicationVersionModel($application_id);
			$state_object->setCurrentVersion($version_model->version);
			$state_object->application_id = $application_id;
		}

		$state_object->application_id = $application_id;
		$state_object->context = $context;
		$state_object->ecash = "Commercial";
		return $state_object;
	}

	/**
	 * Load and return an existing application
	 *
	 * @param int $application_id
	 * @param VendorAPI_StateObject $state
	 * @return VendorAPI_IApplication
	 */
	public function getApplication($application_id, VendorAPI_IModelPersistor $persistor, VendorAPI_StateObject $state)
	{
		$version_model = $this->getApplicationVersionModel($application_id);
		$persistor->setVersion($version_model->version);

		$app = $persistor->loadBy(
			$this->getApplicationModel(),
			array('application_id' => $application_id,
				  'company_id' => $this->driver->getCompanyId())
		);

		if (!$app)
		{
			throw new VendorAPI_ApplicationNotFoundException($application_id);
		}

		// allow company-level overrides
		$class = $this->factory->getClassString('VendorAPI_Application');

		return new $class(
			$persistor,
			$app,
			$state,
			$this->getQualify($app),
			$this->factory,
			$this->driver->getStatProClient(),
			$this->driver->getAppClient()
		);
	}

	/**
	 * Enter description here...
	 *
	 * @return unknown
	 */
	protected function getApplicationModel()
	{
		return $this->factory->getModel('Application', $this->db, FALSE);
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $application_id
	 * @return unknown
	 */
	protected function getApplicationVersionModel($application_id)
	{
		$version_model = $this->factory->getModel('ApplicationVersion', $this->db, FALSE);
		$version_model->loadByKey($application_id);
		return $version_model;
	}

	/**
	 * Build the IQualify implementation
	 *
	 * @param array $app
	 * @return VendorAPI_IQualify
	 */
	protected function getQualify(ECash_Models_Application $app)
	{
		$type = $this->factory->getModel('LoanType', $this->db, FALSE);
		$type->loadByKey($app->loan_type_id);

		$rate_model = $this->factory->getModel('RateOverride', $this->db, FALSE);
		$rate_model->loadByKey($app->application_id);

		$br = new ECash_BusinessRules($this->db);
		$rules = $br->Get_Rule_Set_Tree($app->rule_set_id);

		$application_data = $this->factory->getData('Application');
		$num_paid_apps = $application_data->getNumberPaidApplicationsBySSN($app->ssn, $app->company_id);

		$ratebuilder = new ECash_Transactions_RateCalculatorBuilder(
			$rules, 				// Business Rules
			$type->name, 			// Loan Type Name
			$num_paid_apps,			// Number of Paid Applications
			$rate_model->rate_override); 	// The service charge rate if it's been overridden
		$rate_calculator = $ratebuilder->buildRateCalculator();

		$qualify = $this->driver->getQualify();
		$qualify->setLoanTypeName($type->name, $type->name_short);
		$qualify->setBusinessRules($rules);
		$qualify->setRateCalculator($rate_calculator);

		return $qualify;
	}

}

?>
