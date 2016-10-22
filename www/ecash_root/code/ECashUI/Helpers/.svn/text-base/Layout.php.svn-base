<?php

/**
 * A page helper to set application layout
 *
 * @package ECashUI
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashUI_Helpers_Layout implements Site_Page_IResponseHelper
{
	/**
	 * @var ECashUI_Traits_Layout
	 */
	protected $page;
	
	/**
	 * Create a new request log helper.
	 *
	 * @param ECashUI_Traits_Layout $page
	 */
	public function __construct(ECashUI_Traits_Layout $page)
	{
		$this->page = $page;
	}

	/**
	 * Executed to post process a given response.
	 * 
	 * This should only be executed with Site_Response_Templated responses.
	 *
	 * @param Site_Request $request
	 * @param Site_IResponse $response
	 * @return mixed Site_IResponse or NULL 
	 */
	public function onResponse(Site_Request $request, Site_IResponse $response)
	{
		if ( !($response instanceof Site_Response_Templated) )
		{
			return NULL;
		}
		
		$layout = $this->page->getLayoutTemplateName();
		
		if (!empty($layout))
		{
			$template = ECashUI::getTemplatedResponse($layout);
			$template->Content = $response;
			$template->HeaderHtml = $this->page->getHeaderHtml();
			$template->BodyTags = $this->page->getBodyTags();
			$template->HotKeys = $this->page->getHotKeys();
			$template->ModuleMenu = $this->page->getModuleMenu();
			
			return $template;
		}
		else
		{
			return NULL;
		}
	}
}

?>