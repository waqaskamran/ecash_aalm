<?php
/**
 * VendorAPI_Loader
 *
 * @author Raymond Lopez <raymond.lopez@selingsource.com>
 */
class ECash_VendorAPI_Loader extends VendorAPI_BasicLoader
{
	/**
	 * @var ECash_Factory
	 */
	private $factory;

	/**
	 * @var ECash_Company
	 */
	private $company_obj;

	/**
	 * @var ECash_Config
	 */
	private $config;

	/**
	 * @var DB_IConnection_1
	 */
	private $db;

	/**
	 * ECash Configuration Bootstrapper
	 *
	 * Provides the necessary includes and defines that will be needed for
	 * the to implement specific ECash Config.
	 * @return void
	 */
	public function bootstrap()
	{
		AutoLoad_1::addSearchPath(dirname(__FILE__)."/../../");
		$company 	= $this->getCompany();
		$enterprise = $this->getEnterprise();
		$mode_map = array (
			"LIVE" 	=> "Live",
			"RC"	=> "RC",
			"DEV"	=> "Local",
			"LOCAL" => "Local",
			"QA"    => "QA_MANUAL",
			"QA_MANUAL"     => "QA_MANUAL",
			"QA_AUTOMATED"  => "QA_AUTOMATED",
			"QA_SEMI_AUTOMATED"     => "QA_SEMI_AUTOMATED",
		);
		$mode = $mode_map[$this->getMode()];

		$base_dir = dirname(__FILE__).'/../../../../';

		$ecash_commercial_directory = realpath($base_dir.'/ecash_commercial');
		if ($ecash_commercial_directory === FALSE)
		{
			$ecash_commercial_directory = "/virtualhosts/ecash_commercial/";
			trigger_error("$ecash_commercial_directory should not be a absolute path");
		}

		$ecash_common_directory = realpath($base_dir.'/ecash_common_cfe');
		if ($ecash_common_directory === FALSE)
		{
			$ecash_common_directory 	= "/virtualhosts/ecash_common_cfe/";
			trigger_error("$ecash_common_directory should not be a absolute path");
		}

		$ecash_customer_directory = realpath($base_dir.'/ecash_'.strtolower($enterprise));
		if ($ecash_customer_directory === FALSE)
		{
			$ecash_customer_directory 	= "/virtualhosts/ecash_".strtolower($enterprise).'/';
			trigger_error("$ecash_customer_directory should not be a absolute path");
		}

		putenv("EXECUTION_MODE=".$this->getMode());
		putenv("ECASH_CUSTOMER=".strtoupper($enterprise));
		putenv("ECASH_CUSTOMER_DIR=$ecash_customer_directory/");
		putenv("ECASH_COMMON_DIR=$ecash_common_directory/");
		putenv("ECASH_COMMON_CODE_DIR={$ecash_common_directory}/code/");
		putenv("LIBOLUTION_DIR=/virtualhosts/libolution/");
		putenv("ECASH_WWW_DIR={$ecash_commercial_directory}/www/");
		putenv("ECASH_CODE_DIR={$ecash_commercial_directory}/code/");
		putenv("ECASH_EXEC_MODE=$mode");
		putenv("COMMON_LIB_DIR=/virtualhosts/lib/");
		putenv("CUSTOMER_CODE_DIR={$ecash_customer_directory}/code/");

		require_once($ecash_commercial_directory.'/www/config.php');

		// Load the company specific configuration file
		$enterprise_prefix = ECash::getConfig()->ENTERPRISE_PREFIX;
		$base_config = getenv('ECASH_CUSTOMER') . '_Config_' . getenv('ECASH_EXEC_MODE');
		$config_filename = realpath(getenv('CUSTOMER_CODE_DIR') . $enterprise_prefix . '/Config/' . $company . '.php');

		try
		{
			include_once $config_filename;
			$class_config_name = $company . '_CompanyConfig';
			ECash::setConfig(new $class_config_name(new $base_config()));
		}
		catch (Exception $e)
		{
			throw new Exception("invalid company configuration class or company config file does not exist: $config_filename");
		}

		$this->config = ECash::getConfig();
		$this->factory = ECash::getFactory();
		$this->company_obj = $this->factory->getCompanyByNameShort($company, $this->getDatabase());

		ECash::setCompany($this->company_obj);
		
		require_once(COMMON_LIB_DIR . 'applog.1.php');
	}

	/**
	 * Gets the Commercial driver implementation
	 * @return ECash_VendorAPI_Driver
	 */
	public function getDriver()
	{
		if (!$this->driver)
		{
			$timer_log = new Log_StreamLogger_1(fopen('/var/log/vendor_api/request_timer.log', 'a'));
			$timer = new VendorAPI_RequestTimer($timer_log);
			$timer->setCompany($this->getCompany());
			$timer->start();

			/**
			 * Allow for Customer Specific Driver overrides
			 */
			$driver_name = strtoupper($this->getEnterprise()) . '_VendorAPI_Driver';
			if (! class_exists($driver_name))
			{
				$driver_name = 'ECash_VendorAPI_Driver';
			}

			$this->driver = new $driver_name(
				$this->config,
				$this->factory,
				$timer,
				$this->getDatabase(),
				$this->company_obj,
				$this->getLog(),
				$this->use_master
			);
			$this->driver->setEnvironment($this->getMode());
		}
		return $this->driver;
	}

	/**
	 * Gets a database connection
	 *
	 * This will attempt to connect to each defined database in the failover order
	 *
	 * @return DB_IConnection_1
	 */
	public function getDatabase()
	{
		if (!$this->db)
		{
			$db = new DB_FailoverConfig_1();
			if (!$this->use_master)
			{
				$db->addConfig($this->config->DB_API_CONFIG);
				$db->addConfig($this->config->DB_SLAVE_CONFIG);
			}
			$db->addConfig($this->config->DB_MASTER_CONFIG);
			$this->db = $db->getConnection();
		}
		return $this->db;
	}
}
?>
