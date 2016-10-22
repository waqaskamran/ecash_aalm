<?php
/**
 * ECash Commercial specific application client implementation
 *
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 * @package WebService
 */
class ECash_WebService_LoanActionClient extends WebServices_Client_LoanActionClient
{
	/**
	 * Constructor for the ECash_WebService_LoanActionClient object (Use NULL when possible for defaults)
	 *
	 * @param Applog $log
	 * @param ECash_WebService $webservice
	 * @return void
	 */
	public function __construct(Applog $log = NULL, $webservice = NULL, WebServices_Cache $cache)
	{
		$log = is_null($log) ? ECash::getLog('application_service') : $log;
		
		// Get the database connection
		$failover_config = new DB_FailoverConfig_1();
		// [#46323] writer goes first for paydown / payout
		$failover_config->addConfig(ECash::getConfig()->DB_MASTER_CONFIG);
		$failover_config->addConfig(ECash::getConfig()->DB_API_CONFIG);
		$failover_config->addConfig(ECash::getConfig()->DB_SLAVE_CONFIG);
		$failover_db = $failover_config->getConnection();

		$company = ECash::getFactory()->getModel('Company');
		if (!$company->loadBy(array('name_short' => strtolower(ECash::getConfig()->COMPANY_NAME_SHORT)))) {
			throw new Exception('Unknown company name short');
		}
		
		$api_factory = new ECash_LoanActionHistoryService_ECashAPIFactory($failover_db, $company);
		$use_web_services = isset(ECash::getConfig()->USE_WEB_SERVICES_READS) ? ECash::getConfig()->USE_WEB_SERVICES_READS : FALSE;

		$la_service = new ECash_LoanActionHistoryService_API(
			ECash::getFactory(), //$ecash_factory,
			$api_factory,
			$company->company_id,
			ECash::getAgent()->getAgentId(),
			$use_web_services);
		
		/*
		if(empty($webservice))
		{
			$url =  ECash::getconfig()->LOAN_ACTION_SERVICE_URL;
			$user = ECash::getConfig()->APP_SERVICE_USER;
			$pass =  ECash::getConfig()->APP_SERVICE_PASS;
			$la_service = new ECash_BufferedWebService(
				$log,
				$url,
				$user,
				$pass,
				"loanaction",
				ECash::getconfig()->AGGREGATE_SERVICE_URL,
				new WebServices_Buffer($log)
			);
		}
		else
		{
			$la_service = $webservice;
		}
		*/
		parent::__construct($log, $la_service, ECash::getAgent()->getAgentId(), $cache);
	}
}

?>
