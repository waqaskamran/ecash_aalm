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
abstract class ECashUI_AbstractPage implements Site_IRequestProcessor
{
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
		$timer->setRequestInformation(ECash::getCompanyId(). ECash::getAgent()->getAgentId(), $request['module'], $request['mode'], $request['page'], '',$request);
	}
	
	/**
	 * Returns the name of the layout template
	 *
	 * @return string
	 */
	public function getLayoutTemplateName()
	{
		return 'layout.php';
	}
	
	/**
	 * Returns the HTML that will be placed in the header.
	 */
	public function getHeaderHtml()
	{
		return '';
	}
	
	/**
	 * Returns the HTML that will enable hot keys
	 */
	public function getHotKeys()
	{
		return '';
	}
	
	/**
	 * Returns the body tag data
	 */
	public function getBodyTags()
	{
		return '';
	}
	
	/**
	 * Returns the Module menu
	 */
	public function getModuleMenu()
	{
		return '';
	}
}

?>