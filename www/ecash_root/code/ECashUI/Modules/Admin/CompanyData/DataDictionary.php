<?php

/**
 * The internal message manager for the Data Dictionary -- Essentially just dispatches the page.
 * 
 * @package ECashUI.Modules.Admin
 * @author Brian Ronald <brian.ronald@sellingsource.com>
 */
class ECashUI_Modules_Admin_CompanyData_DataDictionary extends ECashUI_Modules_Admin_Page implements ECashUI_Traits_Layout, ECashUI_Traits_Authentication
{

	/**
	 * Processes the given request.
	 * 
	 * @param Site_Request $request
	 * @return Site_IResponse
	 */
	public function processRequest(Site_Request $request)
	{
		$response = ECashUI::getTemplatedResponse('admin/company_data/data_dictionary.php');
		//$response->code_list = ECash::getFactory()->getReferenceList('AchReturnCode');
		
		return $response;
	}

}
?>
