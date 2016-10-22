<?php

/**
 * Utilities specificaly for ECashUI code.
 *
 * @package ECashUI
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashUI
{
	/**
	 * Creates a new templated response
	 *
	 * @param string $template
	 * @return Site_Response_Templated
	 */
	public static function getTemplatedResponse($template)
	{
		$response = new ECashUI_Response_Templated($template, BASE_DIR.'templates/');
		$response->externalResources = new ECashUI_Template_ExternalResources(ECASH_VERSION_FULL);
		$response->ECash = new ECash();
		return $response;
	}
}

?>