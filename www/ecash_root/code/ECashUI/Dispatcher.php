<?php

/**
 * The root level dispatcher for requests.
 *
 * Determines the appropriate module to handle the request. Includes support
 * for legacy pages through the ECashUI_Pages_Legacy class.
 *
 * @package ECashUI.Pages
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashUI_Dispatcher extends Site_Page_Dispatcher
{
	/**
	 * Processes a request object.
	 *
	 * Overridden to allow fall through to legacy pages if the modules cannot
	 * locate the appropriate page for a request.
	 *
	 * @param Site_Request $request
	 * @return Site_IResponse
	 */
	public function processRequest(Site_Request $request)
	{
		try
		{
			return parent::processRequest($request);
		}
		catch (Site_Page_PageNotFoundException $e)
		{
			$page = new Site_Page_Decorator(new ECashUI_LegacyPage());
			return $page->processRequest($request);
		}

		return NULL;
	}

	/**
	 * Factories a page object, given a request.
	 *
	 * @param Site_Request $request
	 * @return Site_IRequestProcessor
	 */
	protected function getPage(Site_Request $request)
	{
		if (isset($request['module']))
		{
			$module_class = 'ECashUI_Modules_' . ucfirst($request['module']);

			if (class_exists($module_class))
			{
				return new Site_Page_Decorator(new $module_class);
			}
		}

		return new Site_Page_Decorator(new ECashUI_Modules_Default());
	}
}

?>