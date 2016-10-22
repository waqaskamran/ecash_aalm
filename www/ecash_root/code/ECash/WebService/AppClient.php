<?php
/**
 * ECash Commercial specific application client implementation
 *
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 * @package WebService
 */
class ECash_WebService_AppClient extends WebServices_Client_AppClient
{
	/**
	 * Constructor for the ECash_WebService_AppClient object (Use NULL when possible for defaults)
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
		//	throw new Exception('Unknown company name short');
		$company->loadBy(array('name_short' => 'mls'));
		}
		
		$api_factory = new ECash_ApplicationService_ECashAPIFactory($failover_db, $company);
		$use_web_services = isset(ECash::getConfig()->USE_WEB_SERVICES_READS) ? ECash::getConfig()->USE_WEB_SERVICES_READS : FALSE;

		$app_service = new ECash_ApplicationService_API(
			ECash::getFactory(), //$ecash_factory,
			$api_factory,
			$company->company_id,
			ECash::getAgent()->getAgentId(),
			$use_web_services);
		/*
		if(empty($webservice))
		{
			$url =  ECash::getconfig()->APP_SERVICE_URL;
			$user = ECash::getConfig()->APP_SERVICE_USER;
			$pass =  ECash::getConfig()->APP_SERVICE_PASS;
			$app_service = new ECash_BufferedWebService(
				$log,
				$url,
				$user,
				$pass,
				"application",
				ECash::getconfig()->AGGREGATE_SERVICE_URL,
				new WebServices_Buffer($log)
			);
		}
		else
		{
			$app_service = $webservice;
		}
		*/
		parent::__construct($log, $app_service, ECash::getAgent()->getAgentId(), $cache);
	}

	/**
	 * Performs a search of the app service for applications which meet the proper criteria
	 * returns an array of applications
	 * 
	 * @param array $request
	 * @param int $limit
	 * @return array
	 */
	public function applicationSearch($request, $limit)
	{
		/**
		 * @todo: Unsure of what we're getting back in the result or how we're supposed to transpose the status
		 */
		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		
		$results = parent::applicationSearch($request, $limit);
//		echo "<pre>\n";
//		echo "Request: " . var_export($request, TRUE) . "\n";
//		echo "Limit: " . var_export($limit, TRUE) . "\n";
//		echo "Results: " . var_export($results, TRUE) . "\n";
//		echo "</pre>\n";
		
		if (is_array($results) && count($results) > 0)
		{
			foreach ($results as $i => $result)
			{
				$id = $asf->toId($result->application_status_name);
				$status = ECash::getFactory()->getModel('ApplicationStatusFlat', NULL);
				$status->loadBy(array('application_status_id' => $id));

				$results[$i]->application_status_id = $id;
				$results[$i]->application_status = $status->level0_name;
				$results[$i]->application_status_short = $status->level0; 
				
				$results[$i]->dnl = (isset($result->dnl) && $result->dnl) ? "1" : "0";
			}
		}
		
		return $results;
	}

}

?>
