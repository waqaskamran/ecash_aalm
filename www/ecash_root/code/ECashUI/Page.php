<?php

/**
 * The base page from which to create ecash pages.
 *
 * Provides the functions to implement the RequestLog trait
 * (ECashUI_Traits_RequestLog). If you want a particular page to be logged
 * (most of them should be) just implement the above trait.
 *
 * @package ECashUI
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
abstract class ECashUI_Page implements Site_IRequestProcessor
{
	public function getSessionStore()
	{
		$db = ECash::getConfig()->getMasterDbConnection();
		return new ECashUI_SessionStore($db);
	}

	public function getAuthenticator()
	{
		return new ECashUI_SessionAuthenticator();
	}

	public function getLoginPage()
	{
		return new ECashUI_Pages_Login();
	}

	/**
	 * Returns the timer for the current request.
	 *
	 * @return ECash_RequestTimer
	 */
	public function getRequestTimer()
	{
		$timer = ECash::getRequestTimer();
		$timer->setLog(ECash::getServer()->log);

		return $timer;
	}

	/**
	 * Sets the timer data for the request.
	 *
	 * @param ECash_RequestTimer $timer
	 * @param Site_Request $request
	 */
	public function setRequestTimerData(ECash_RequestTimer $timer, Site_Request $request, Site_IResponse $response)
	{
		$timer->setRequestInformation(ECash::getCompanyId(). ECash::getAgent()->getAgentId(), $request['module'], $request['mode'], $request['page'], '');
	}
}

?>
