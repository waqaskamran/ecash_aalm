<?php

/**
 * The internal message manager.
 * 
 * @package ECashUI.Modules.Admin
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashUI_Modules_Admin_CompanyData_ACHCodes extends ECashUI_Modules_Admin_Page implements ECashUI_Traits_Layout, ECashUI_Traits_Authentication
{

	/**
	 * Processes the given request.
	 * 
	 * @param Site_Request $request
	 * @return Site_IResponse
	 */
	public function processRequest(Site_Request $request)
	{
		$response = ECashUI::getTemplatedResponse('admin/company_data/ach_codes.php');
		$response->code_list = ECash::getFactory()->getReferenceList('AchReturnCode');
		
		return $response;
	}

}
?>