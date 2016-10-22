<?php
/**
 * Vendor API DAO Application management within eCash
 *
 * @author Raymond Lopez <raymond.lopez@selingsource.com>
 */
class ECash_VendorAPI_DAO_Application implements VendorAPI_DAO_IApplication
{

	protected $state_data;
	protected $cfe_result;

	protected $application_model;

	/**
	 * @var DB_Models_Batch_1
	 */
	protected $batch;

	/**
	 * @var VendorAPI_IDriver
	 */
	protected $driver;

	/**
	 * @var ECash_Factory
	 */
	protected $factory;

	/**
	 *
	 * @var VendorAPI_IApplicationFactory
	 */
	protected $app_factory;

	/**
	 *
	 * @var VendorAPI_CallContext
	 */
	protected $call_context;

	/**
	 * @var boolean
	 */

	/**
	 * Create VendorAPI_Application object with Driver
	 *
	 * @param VendorAPI_IDriver $driver
	 */
	public function __construct(VendorAPI_IDriver $driver)
	{
		$this->driver = $driver;
		$this->factory = $driver->getFactory();
	}

	/**
	 * Saves the given state object to the database
	 *
	 * Only versions which have not been written previously (determined
	 * by the version stored in the 'version' column on the application
	 * row) will be processed.
	 *
	 * @return boolean
	 */
	public function save(VendorAPI_StateObject $state)
	{
		$this->state_data = $state;
		$this->db = $this->factory->getDB();

		$this->version_model = $this->getVersionModel();
		$this->version_model->loadBy(array('application_id' => $state->application_id));
		$this->insertReferenceParts($state->application_id, $state->getReferenceData($this->version_model->version));

		$this->db->beginTransaction();

		try
		{
			$this->version_model = $this->loadVersionWithRetry($state->application_id);
			$versioned_info = $state->getVersionedData();

			foreach ($versioned_info as $version=>$data)
			{
				$this->application_model = $this->getApplicationModel($state);
				$this->application_model->setAlterIndifference(true);
				if ($this->version_model->version < $version)
				{
					$this->processVersion($state, $data);
					$this->runAsyncEngine($this->application_model);
					$this->runSyncEngine($state, $data);

					$this->version_model->version = $version;
					$this->version_model->save();
				}
			}
			$this->db->commit();
		}
		catch (Exception $e)
		{
			$this->db->rollBack();
			throw $e;
		}
		return TRUE;
	}

	/**
	 * Creates a new CFE Engine with the application
	 * context and sync ruleset
	 *
	 * @param VendorAPI_StateObject $state
	 * @return ECash_CFE_Engine
	 */
	public function getCfeEngineForSync(VendorAPI_StateObject $state)
	{
		$persistor = new VendorAPI_StateObjectPersistor($state);
		$context = $this->getCfeContext(
			$this->getApplicationFactory()
				->getApplication(
					$state->application_id,
					$persistor,
					$state
			),
			$this->getCallContext()
		);
		$cfe_engine = $this->getCfeEngine(
			$context,
			$this->getCfeRuleset()
		);
		return $cfe_engine;
	}

	/**
	 * Returns the status path based on a status id
	 * @return Integer
	 */
	public function getApplicationStatus($id)
	{
		$ref_list = $this->factory->getReferenceList('ApplicationStatusFlat');
		if (is_numeric($id))
		{
			return $ref_list->toName($id);
		}
	}

	/**
	 * Return a new instance of the rule factory
	 * @param VendorAPI_CFE_IFactory $factory
	 * @return VendorAPI_CFE_RulesetFactory
	 */
	protected function getCFERulesetFactory(VendorAPI_CFE_IFactory $factory)
	{
		return new VendorAPI_CFE_RulesetFactory($factory);
	}

	/**
	 * Return a CFEFactory for things
	 * @return VendorAPI_CFE_Factory
	 */
	protected function getCFEFactory()
	{
		return new VendorAPI_CFE_Factory();
	}

	/**
	 *
	 * @param ECash_CFE_IContext $context
	 * @return ECash_CFE_Engine
	 */
	protected function getCfeEngine(ECash_CFE_IContext $context, array $ruleset)
	{
		// WE don't want to use a singleton here, because we're
		// actually running 2 separate cfe things
		$e = new ECash_CFE_Engine();
		$e->setContext($context);
		$e->setRuleset($ruleset);
		return $e;
	}

	/**
	 * Processes a single version within the state object
	 *
	 * @param VendorAPI_StateObject $state
	 * @param array $data Version data, indexed by table
	 * @return void
	 */
	protected function processVersion(VendorAPI_StateObject $state, array $data)
	{
		$batch = new DB_Models_Batch_1(NULL, FALSE);

		if (isset($state->context)
			&& $state->context instanceof VendorAPI_CallContext)
		{
			$context = $state->context;
			$this->setECashAgent($context->getApiAgentId());
		}

		foreach ($data as $table => $table_data)
		{
			$handler = $this->getHandler($table);
			if ($state->isMultiPart($table))
			{
				foreach ($table_data as $d)
				{
					$handler->saveTo($d, $batch);
				}
			}
			else
			{
				$handler->saveTo($table_data, $batch);
			}
		}

		$batch->execute();
	}

	/**
	 * Run CFE again
	 *
	 * @return void
	 */
	public function runAsyncEngine(ECash_Models_Application $app)
	{
		if ($app->isStored()
			&& $this->cfe_result instanceof ECash_CFE_AsynchResult)
		{
			$cfe = $this->getCfeAsynchEngine();
			$cfe->endExecution($app, $this->cfe_result);
			unset($this->cfe_result);
			if ($app->isAltered())
			{
				$app->save();
			}
		}
	}

	/**
	 * get the cfe engine
	 *
	 * @return ECash_CFE_AsynchEngine
	 */
	protected function getCfeAsynchEngine()
	{
		$cfe = new ECash_CFE_AsynchEngine(
			$this->db,
			$this->driver->getCompanyID()
		);
		return $cfe;
	}

	protected function getHandler($table)
	{
		$table_class = implode('', array_map('ucfirst', explode('_', $table)));
		$class = 'ECash_VendorAPI_Save_'.$table_class;

		if (class_exists($class))
		{
			$handler = new $class($this->factory, $this->driver, $this->db);

			if ($handler instanceof ECash_VendorAPI_Save_Application)
			{
				$handler->setApplicationModel($this->application_model);
			}
			return $handler;
		}
		return new VendorAPI_Save_Default($this->driver, $table, $this->db);
	}

	protected function setECashAgent($agent_id)
	{
		ECash::setAgent($this->driver->getFactory()->getAgentById($agent_id, $this->db));
	}

	protected function getECashApplicationModel($app_id)
	{
		$app = ECash::getApplicationById($app_id, $this->db, TRUE, FALSE);
		if(! $app->exists(TRUE))
		{
			throw new ECash_Application_NotFoundException();
		}

		return $app->getModel(TRUE);
	}

	/**
	 * Find an existing application model from teh ecash
	 * factory that has the cfe observers or create a new
	 * one and set us up to run the cfe async crap.
	 *
	 * @return ECash_Models_Application
	 */
	protected function getApplicationModel(VendorAPI_StateObject $state)
	{
		// reset this to avoid processing it twice
		$this->cfe_result = NULL;

		try
		{
			$model = $this->getEcashApplicationModel($state->application_id);
		}
		catch (ECash_Application_NotFoundException $e)
		{
			$model = $this->factory->getModel('Application', $this->db, FALSE);

			// Observer needed for app service dual write.
			$app_observer = new ECash_WebServices_Models_Observers_Application();
			$app_observer->attach($model);
			
			if ($state->cfe_result instanceof ECash_CFE_AsynchResult)
			{
				$this->cfe_result = $state->cfe_result;
			}
		}
		return $model;
	}

	protected function loadVersionWithRetry($application_id, $retry = 3)
	{
		$version_model = $this->getVersionModel();

		for ($i = 0; $i < $retry && !$version_model->isStored(); $i++)
		{
			if (!$version_model->loadForUpdate($application_id))
			{
				$version_model->version = 0;
				$version_model->application_id = $application_id;
				$version_model->date_created = time();

				try
				{
					$version_model->save();
				}
				catch (Exception $e)
				{
					$last_error = $e->getMessage();
					$this->driver->getLog()->write("Could not insert application_version model for {$application_id}: {$last_error} [try {$i}]");
				}
			}
		}

		if (!$version_model->isStored())
		{
			throw new RuntimeException("Could not insert application_version model for {$application_id}: {$last_error}");
		}

		return $version_model;
	}

	protected function getVersionModel()
	{
		return new ECash_Models_ApplicationVersion($this->db);
	}
	/**
	 *
	 * @param VendorAPI_IApplication $application
	 * @param VendorAPI_CallContext $context
	 * @return unknown_type
	 */
	protected function getCfeContext(
		VendorAPI_IApplication $application,
		VendorAPI_CallContext $context)
	{
		return new VendorAPI_CFE_ApplicationContext($application, $context);
	}

	/**
	 * Return a new call context
	 * @return VendorAPI_CallContext
	 */
	protected function getCallContext()
	{
		if (!$this->call_context instanceof VendorAPI_CallContext)
		{
			$this->call_context = new VendorAPI_CallContext();
			$this->call_context->setCompanyId($this->driver->getCompanyID());
		}
		return $this->call_context;
	}

	/**
	 * return a cfe ruleset array
	 * @return array
	 */
	protected function getCfeRuleset()
	{
		$rules = $this->getCFERulesetFactory($this->getCFEFactory())
			->getRuleset($this->driver->getCfeConfig('sync'));
		return $rules;
	}

	/**
	 *
	 * @return VendorAPI_IApplicationFactory
	 */
	protected function getApplicationFactory()
	{
		if (!$this->app_factory instanceof VendorAPI_IApplicationFactory)
		{
			$this->app_factory = $this->driver->getApplicationFactory();
		}
		return $this->app_factory;
	}

	protected function runSyncEngine($state, $data)
	{
		if (!empty($data['application']))
		{
			$cfe_engine = $this->getCfeEngineForSync($state);
			$call_center = !empty($state->call_center);
			if (!$state->isMultipart('application'))
			{
				$app_data = array($data['application']);
			}
			else
			{
				$app_data = $data['application'];
			}
			foreach ($app_data as $k => $v)
			{
				$cfe_data = array(
					'application_status' => $this->getApplicationStatus($v['application_status_id']),
					'ecash_sign_doc' => (bool)$state->ecash_sign_docs,
					'is_call_center' => $call_center
				);
				$result = $cfe_engine->executeEvent(
					'syncApplication', $cfe_data, TRUE
				);
			}
		}
	}

	/**
	 * Inserts referenceParts to parts
	 * @param array $parts
	 * @return void
	 */
	public function insertReferenceParts($application_id, array $parts)
	{
		foreach ($parts as $table => $rows)
		{
			foreach ($rows as $row)
			{
				try
				{
					$this->db->beginTransaction();

					$model = $this->driver->getDataModelByTable($table, $this->db);

					$loadby = array_filter($row);
					unset($loadby['date_created']);
					unset($loadby['date_modified']);
					if (!$model->loadBy($loadby))
					{
						foreach ($row as $c => $v)
						{
							$model->$c = $v;
						}
						$model->save();
					}
					$this->db->commit();
				}
				catch (Exception $e)
				{
					$this->db->rollBack();
					throw $e;
				}
			}
		}
	}

}
?>
