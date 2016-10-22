<?

require_once(SQL_LIB_DIR . "util.func.php");
require_once(SQL_LIB_DIR . "application.func.php");

// This will return an array of applications that were created from this applications
// it is possibe that there can be multiple reacts from one app but thats not a smart thing
// to do or have. [rlopez]
function Get_Reacts_From_App($application, $company_id)
{
		$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
		$raf = $app_client->getReactAffiliationChildren($application);

		if(!empty($raf))
		{
			foreach ($raf as $entry)
			{
				$app = ECash::getApplicationById($entry->react_application_id);
				$entry->olp_process = $app->olp_process;
				$entry->application_status_id = $app->application_status_id;
				$values[] = $entry;
			}
			return $values;
		}
		else
		{
			return array();
		}
}

// This will return the application that this react was created from
// there can be only one parent [rlopez]
function Get_Parent_From_React($application)
{
	$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
	$raf = $app_client->getReactAffiliation($application);
	if(is_numeric($raf->application_id))
	{
		$entry = new stdclass();

		$app = ECash::getApplicationById($raf->application_id);
		$entry->application_id = $app->application_id;
		$entry->olp_process = $app->olp_process;
		$entry->application_status_id = $app->application_status_id;
		$entry->agent_id = $raf->agent_id;
		return $entry;
	}
	else
	{
		return null;
	}
}


?>
