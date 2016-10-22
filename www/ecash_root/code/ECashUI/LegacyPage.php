<?php

/**
 * Provides support for legacy display code.
 *
 * @package ECashUI.Pages
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashUI_LegacyPage extends ECashUI_Page
{
	/**
	 * Processes the given request.
	 *
	 * @param Site_Request $request
	 * @return Site_IResponse
	 */
	public function processRequest(Site_Request $request)
	{
		//these two lines determine what comm protocol to use
		//require_once WWW_DIR.'comm_prpc_client.php';
		//$comm = new Comm_Prpc_Client();
		require_once WWW_DIR.'comm_class.php';
		$comm = new Comm_Class();

		$comm->Process_Data(ECash::getRequest());
		$transport = ECash::getTransport();
		$top_level = $transport->Get_Next_Level();

		switch($top_level)
		{
			case "json":
				$page = new Display_Json();
				break;

			case "popup":
				$page = new Display_Popup();
				break;

			case "close_pop_up":
				include(WWW_DIR . "close_pop_up.html");
				exit;
				break;

			case "download":
				ECash::getTransport()->download = true;  // fall through ok
			case "application":
				$page = new Display_Application();
				break;

			case "login":
				$page = new Display_Login();
				break;

			case "exception":
				if (EXECUTION_MODE == 'LOCAL')
				{
					include(CLIENT_VIEW_DIR . "exception_local.html");
				}
				else
				{
					include(CLIENT_VIEW_DIR . "exception.html");
				}
				exit;
				break;

			default:
				$page = new Display_Unknown();
				break;
		}

		return new ECashUI_LegacyResponse($page, $transport);
	}
}

?>
