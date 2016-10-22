<?php
/**
 * Class for calling the Model service
 *
 * @author Todd Huish <toddh@sellingsource.com>
 * @package WebService
 */
class ECash_WebService_SOAPModelClient extends WebServices_Client_SOAPModelClient
{
	/**
	 * Constructor for the ECash_WebService_SOAPModelClient object (Use NULL when possible for defaults)
	 *
	 * @param Applog $log
	 * @param string $url
	 * @param string $user
	 * @param string $pass
	 * @return void
	 */
	public function __construct(Applog $log = NULL, $service)
	{

		$log = is_null($log) ? ECash::getLog('application_service') : $log;
		parent::__construct($log, $service);
	}
}

?>
