<?php

/**
 * A page trait to perform request timing and logging
 *
 * @package ECashUI
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
interface ECashUI_Traits_RequestLog extends Site_Page_ITrait 
{
	/**
	 * Returns the timer for the current request.
	 *
	 * @return ECash_RequestTimer
	 */
	public function getRequestTimer();
	
	/**
	 * Sets the timer data for the request.
	 *
	 * @param ECash_RequestTimer $timer
	 * @param Site_Request $request
	 */
	public function setRequestTimerData(ECash_RequestTimer $timer, Site_Request $request, Site_IResponse $response);
}

?>