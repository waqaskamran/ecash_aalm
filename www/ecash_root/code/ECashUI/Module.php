<?php

/**
 * The base class for eCash Modules
 *
 * @package ECashUI
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
abstract class ECashUI_Module extends Site_Page_Dispatcher
{

	/**
	 * Factories a page object, given a request.
	 *
	 * @param Site_Request $request
	 * @return Site_IRequestProcessor
	 */
	protected function getPage(Site_Request $request)
	{
		if (isset($request['page']))
		{
			$page_class = get_class($this) . '_' . ucfirst($request['page']);
			$global_page_class = 'ECashUI_Pages_' . ucfirst($request['page']);
			if (class_exists($page_class))
			{
				return new Site_Page_Decorator(new $page_class);
			}
			elseif (class_exists($global_page_class))
			{
				return new Site_Page_Decorator(new $global_page_class);
			}
			else
			{
				throw new Site_Page_PageNotFoundException($request);
			}
		}
		else
		{
			throw new Site_Page_PageNotFoundException($request);
		}
	}
}

?>
