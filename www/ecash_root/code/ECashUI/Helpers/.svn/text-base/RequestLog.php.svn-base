<?php

/**
 * A page helper to perform request timing and logging
 *
 * @package ECashUI
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashUI_Helpers_RequestLog implements Site_Page_IRequestHelper, Site_Page_IResponseHelper
{
	/**
	 * @var ECashUI_Traits_RequestLog
	 */
	protected $page;
	
	/**
	 * Create a new request log helper.
	 *
	 * @param ECashUI_Traits_RequestLog $page
	 */
	public function __construct(ECashUI_Traits_RequestLog $page)
	{
		$this->page = $page;
	}
	
	/**
	 * Executed to preprocess a given request.
	 *
	 * @param Site_Request $request
	 * @return mixed Site_IResponse or NULL 
	 */
	public function onRequest(Site_Request $request)
	{
		$timer = $this->page->getRequestTimer();
		$timer->start();
	}

	/**
	 * Executed to post process a given response.
	 *
	 * @param Site_Request $request
	 * @param Site_IResponse $response
	 * @return mixed Site_IResponse or NULL 
	 */
	public function onResponse(Site_Request $request, Site_IResponse $response)
	{
		$timer = $this->page->getRequestTimer();
		$this->page->setRequestTimerData($timer, $request, $response);
		
		return new ECashUI_Response_RequestLogDecorator($response, $timer);
	}
}

?>