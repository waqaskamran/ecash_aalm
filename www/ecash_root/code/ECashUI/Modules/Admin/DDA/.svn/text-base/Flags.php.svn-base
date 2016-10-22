<?php

/**
 * The internal message manager.
 * 
 * @package ECashUI.Modules.Admin
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashUI_Modules_Admin_DDA_Flags extends ECashUI_Modules_Admin_Page implements ECashUI_Traits_Layout, ECashUI_Traits_Authentication
{

	/**
	 * Processes the given request.
	 * 
	 * @param Site_Request $request
	 * @return Site_IResponse
	 */
	public function processRequest(Site_Request $request)
	{
		$response = ECashUI::getTemplatedResponse('admin/dda/flags.php');

		$application = NULL;
		$app_flags = NULL;
		$flag_list = ECash::getFactory()->getReferenceList('FlagType');

		if(isset($request['save']))
		{
			$application = ECash::getApplicationById($request['application_id']);
			$app_flags = $application->getFlags();
			$set_flags = $app_flags->getAll();
			foreach($flag_list as $flag)
			{
				$checked = in_array($flag->name_short, $request['flags']);
				$set = isset($set_flags[$flag->name_short]);
				
				if($checked && !$set)
				{
					$app_flags->set($flag->name_short);
				}

				if(!$checked && $set)
				{
					$app_flags->clear($flag->name_short);
				}
			}
		}

		
		if($request['application_id'])
		{
			try
			{
				$response->application_id = $request['application_id'];

				if(!$application) //may have been loaded from a save above
					$application = ECash::getApplicationById($request['application_id']);

				$response->flag_list = $flag_list;
				$response->app_flags = empty($app_flags) ? $application->getFlags() : $app_flags;
			}
			catch(ECash_Application_NotFoundException $e)
			{
				//application not found
				$response->app_not_found = FALSE;
			}
		}
		
		return $response;
	}

}
?>