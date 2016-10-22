<?php

require_once COMMON_LIB_DIR.'/security.8.php';
define('PASSWORD_ENCRYPTION', 'ENCRYPT');
class ECash_VendorAPI_Save_Application extends VendorAPI_Save_Default implements VendorAPI_Save_ITableHandler
{
	/**
	 * @var ECash_Factory
	 */
	protected $factory;

	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	/**
	 * @var DB_Models_ReferenceTable_1
	 */
	protected $application_status_reference;

	/**
	 * @var ECash_Models_Application
	 */
	protected $ecash_application;

	public function __construct(ECash_Factory $factory, ECash_VendorAPI_Driver $driver, DB_IConnection_1 $db)
	{
		$this->factory = $factory;
		$this->driver = $driver;
		$this->db = $db;
		parent::__construct($driver, 'application', $db);
	}

	public function setApplicationModel(ECash_Models_Application $app)
	{
		$this->ecash_application = $app;
	}

	public function getModel()
	{
		if ($this->ecash_application instanceof ECash_Models_Application)
		{
			return $this->ecash_application;
		}
		return parent::getModel();
	}
	
	public function saveTo(array $data, DB_Models_Batch_1 $batch)
	{
		if (!empty($data['application_status']))
		{
			$data['application_status_id'] = $this->getApplicationStatusId($data['application_status']);
		}
		unset($data['application_status']);

		$app = $this->getModel();
		if (!$app->isStored())
		{
			$app->date_created = time();
		}

		parent::saveTo($data, $batch);
		
		$this->setEcashCompany($app->company_id);
		$this->setEcashAgent($app->modifying_agent_id);
		$this->setEcashApplication($app);
	}

	protected function setEcashCompany($company_id)
	{
		ECash::setCompany($this->driver->getFactory()->getCompanyById($company_id));
	}

	protected function setEcashAgent($agent_id)
	{
		ECash::setAgent($this->factory->getAgentById($agent_id));
	}

	protected function setEcashApplication($app)
	{
		ECash::setApplication($app);
	}
	
	/**
	 * Returns the status id based on path
	 * @return Integer 
	 */
	public function getApplicationStatusId($string)
	{
		$ref_list = $this->factory->getReferenceList('ApplicationStatusFlat');
		return $ref_list->toId($string);
	}

}

?>
