<?php
/**
 * ECash Commercial specific webservice factory
 *
 * @author Richard Bunce <richard.bunce@sellingsource.com>
 * @package WebService
 */
class ECash_WebService_Factory extends WebServices_Factory
{
	protected function getConfigSettings()
	{
		if(empty($this->config))
		{
			$this->config = new stdclass();
			$this->config->user = ECash::getConfig()->APP_SERVICE_USER;
			$this->config->pass = ECash::getConfig()->APP_SERVICE_PASS;	
			$this->config->aggregate_url = ECash::getconfig()->AGGREGATE_SERVICE_URL;
			$this->config->app_url = ECash::getconfig()->APP_SERVICE_URL;
			$this->config->inquiry_url = ECash::getconfig()->INQUIRY_SERVICE_URL;
			$this->config->references_url = ECash::getConfig()->REFERENCES_SERVICE_URL;
			$this->config->document_url = ECash::getConfig()->DOCUMENT_SERVICE_URL;
			$this->config->documenthash_url = ECash::getConfig()->DOCUMENT_HASH_SERVICE_URL;
			$this->config->loanaction_url = ECash::getConfig()->LOAN_ACTION_SERVICE_URL;
			$this->config->queryservice_url = ECash::getConfig()->QUERY_SERVICE_URL;
			$this->config->queryservice_log = ECash::getLog('application_service');
			$this->config->aggregate_log = ECash::getLog('aggregate_service');
			$this->config->app_log = ECash::getLog('application_service');
			$this->config->inquiry_log = ECash::getLog('inquiry_service');
			$this->config->references_log = ECash::getLog('references_service');
			$this->config->document_log = ECash::getLog('document_service');
			$this->config->documenthash_log = ECash::getLog('document_service');
		}
		return $this->config;
	}

}

?>
