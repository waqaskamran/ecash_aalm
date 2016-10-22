<?php

/**
 * A page helper to perform request timing and logging
 *
 * @package ECashUI
 * @author Asa Ayers <Asa.Ayers@sellingsource.com>
 */
class ECashUI_Helpers_Authentication implements Site_Page_IRequestHelper
{
	/**
	 * @var ECashUI_Traits_RequestLog
	 */
	protected $page;
	
	/**
	 * 
	 *
	 * @param ECashUI_Traits_RequestLog $page
	 */
	public function __construct(ECashUI_Traits_Authentication $page)
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
		$server = ECash::getServer();

		//if (empty($server->agent_id) || empty($server->company))
		if(!$server->Security_Check())
		{
			return new ECashUI_Response_Redirect('/?urlcode='.trim(base64_encode($_SERVER['REQUEST_URI']), '='));
		}
		return NULL;
	}
}

?>