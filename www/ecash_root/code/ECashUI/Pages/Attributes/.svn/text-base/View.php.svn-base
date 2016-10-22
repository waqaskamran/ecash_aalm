<?php

/**
 * @author Russell Lee <russell.lee@sellingsource.com>
 * @package ECashUI.Attributes
 */
class ECashUI_Pages_Attributes_View extends ECashUI_Modules_Admin_Page
{
	/**
	 * Processes the given request.
	 *
	 * @param Site_Request $request
	 * @return Site_IResponse
	 */
	public function processRequest(Site_Request $request)
	{
		$response = ECashUI::getTemplatedResponse('application/attributes.php');
		$response->application_id = $request['application_id'];
		$response->flag = $request['flag'];

		return $response;
	}
}

?>
