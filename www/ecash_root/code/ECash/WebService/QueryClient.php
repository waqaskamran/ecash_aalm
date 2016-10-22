<?php
/**
 * Class for calling the query service
 *
 * @author Eric Johney <eric.johney@sellingsource.com>
 * @package WebService
 */
class ECash_WebService_QueryClient extends WebServices_Client_QueryClient
{
	/**
	 * Constructor for the WebServices_Client_QueryClient object (Use NULL when possible for defaults)
	 *
	 * @param Applog $log
	 * @param ECash_WebService $webservice
	 * @return void
	 */
	public function __construct(Applog $log = NULL, $webservice = NULL, WebServices_Cache $cache = NULL)
	{
		$log = is_null($log) ? get_log('application_service') : $log;

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

		$qc_service = new ECash_LoanActionHistoryService_API(
			ECash::getFactory(), //$ecash_factory,
			$api_factory,
			$company->company_id,
			ECash::getAgent()->getAgentId(),
			$use_web_services);		
		/*
		if(empty($webservice))
		{
			$url = is_null($url) ? LOAN_ACION_SERVICE_URL : $url;
			$user = is_null($user) ? $GLOBALS["APP_SERVICE_COMPANY_LOGINS"][ECash::getCompanyName()]['user'] : $user;
			$pass = is_null($pass) ? $GLOBALS["APP_SERVICE_COMPANY_LOGINS"][ECash::getCompanyName()]['pwd'] : $pass;
			$qc_service = new ECash_WebService(
				$log,
				$url,
				$user,
				$pass
			);
		}
		else
		{
			$qc_service = $webservice;
		}
		*/
		parent::__construct($log, $qc_service, $_SESSION['agent_id'], $cache);
	}


}
