<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once(SERVER_CODE_DIR.'module_interface.iface.php');

class Module implements Module_Interface
{
	private $action;
	private $report;
	private $server;
	const DEFAULT_MODE = 'default';

	public function __construct(Server $server, $request, $module_name)
	{
		$mode = '';

		if (!isset($request->action)) $request->action = NULL;
		$this->server = $server;
		$this->action = $request->action;
		//save the mode in the session and set a default mode
		if(!empty($request->mode))
		{
			$mode = $request->mode;
		}
		else
		{

			$mode = self::DEFAULT_MODE;
		}


		$_SESSION['reporting_mode'] = $mode;

		if ($mode == "report_initialization")
		{
			require("report_generic.class.php");
			$this->report = new Report_Generic($server, $request, $module_name, $mode);
		}
		else
		{
			if(file_exists(CUSTOMER_LIB . "/reporting/server_{$mode}_report.class.php"))
			{
				require_once(CUSTOMER_LIB . "/reporting/server_{$mode}_report.class.php");
				$this->report = new Customer_Report($server, $request, $module_name, $mode);
			}
			else 
			{	
				require("{$mode}_report.class.php");
				$this->report = new Report($server, $request, $module_name, $mode);
			}

		}
		$server->transport->Add_Levels($module_name, $mode);

	}

	public function Main()
	{
		switch($this->action)
		{
			case 'generate_report':
				$this->report->Get_Prompt_Reference_Data();
				try 
				{ 
					$this->report->Generate_Report();
				} 
				catch (Exception $e) 
				{
					$data = new stdClass();
					$data->search_message = $e->getMessage();
					get_log('reporting')->Write($e->getMessage());
					ECash::getTransport()->Set_Data($data);
					ECash::getTransport()->Add_Levels("report_results");
				}
				break;
			case 'download_report':
				$this->report->Get_Prompt_Reference_Data();
				$this->report->Download_Report();
				break;
			case 'ajax_report_data':
				$this->report->Download_XML_Report();	
				break;
			default:
				$this->report->Get_Prompt_Reference_Data();
				$this->report->Get_Last_Report();
				break;
		}
		return;
	}

}

?>
