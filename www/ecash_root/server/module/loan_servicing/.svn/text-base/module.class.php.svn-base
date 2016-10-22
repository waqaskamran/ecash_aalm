<?php

require_once(SERVER_CODE_DIR . "master_module.class.php");
require_once(SERVER_CODE_DIR . "module_interface.iface.php");

// loan servicing module
//class Module implements Module_Interface
class Module extends Master_Module
{
	protected $loan_servicing;
	protected $edit;
	protected $search;

	protected $default_mode;

	public function __construct(Server $server, $request, $module_name)
	{
        parent::__construct($server, $request, $module_name); 
		$this->_add_edit_object();

		$section_names = ECash::getACL()->Get_Acl_Access('loan_servicing');

		$allowed_submenus = array();
		foreach($section_names as $key => $value)
		{
			$allowed_submenus[] = $value;
		}
		ECash::getTransport()->Set_Data((object) array('allowed_submenus' => $allowed_submenus));
		if(count($allowed_submenus) == 0 ||
		   (!empty($request->mode) && !in_array($request->mode, $allowed_submenus)))
		{
			$request->action = 'no_rights';
			//tell the client to display the right screen
			ECash::getTransport()->Add_Levels($module_name, 'no_rights', 'no_rights');
			return;
		}

		//GF 18216
		$this->default_mode = strtolower($allowed_submenus[0]);
		
		$all_sections = ECash::getACL()->Get_Company_Agent_Allowed_Sections($server->agent_id, $server->company_id);
		ECash::getTransport()->Set_Data((object) array('all_sections' => $all_sections));

		$read_only_fields = ECash::getACL()->Get_Control_Info($server->agent_id, $server->company_id);
		ECash::getTransport()->Set_Data((object) array('read_only_fields' => $read_only_fields));

		if (!isset($request->action)) $request->action = NULL;

		// This is empty b/c we never want the 'mode' to be 'search' or 'overview' or anything.
		// Set the mode since display styles are keyed off the mode.
		if (!empty($request->mode)) 
		{
			$mode = $request->mode;
		} 
		elseif (isset($_SESSION['loan_servicing_mode'])) 
		{
			//if ($request->action == NULL)
			if //mantis:3800
			(
				(($request->action == NULL) 
			    	&& ($_SESSION['loan_servicing_mode'] == 'batch_mgmt'))
				|| (preg_match("/process_cards|close_out|review_batch|batch_history|send_batch|resend_batch/", $request->action))
			) 
			{
				$mode = $this->default_mode;
			} 
			else 
			{
				$mode = $_SESSION['loan_servicing_mode'];
			}

		} 
		else 
		{
			$mode = $this->default_mode;
		}
		
		if ((isset($mode)) && $mode == "it_settlement" && !isset($request->action))
		{
			$request->action = "review_settlement";
		}
		else if (!isset($request->action)) $request->action = "show_search";

		
		$_SESSION['loan_servicing_mode'] = $mode;

		ECash::getTransport()->Add_Levels($module_name, $mode);
		//create any objects need by any certain modes

		require_once(SERVER_MODULE_DIR . $module_name . "/loan_servicing.class.php");
		$this->loan_servicing = new Loan_Servicing($server, $request, $mode, $this);

		require_once (LIB_DIR . "Document/Document.class.php");

		// Push the mod name (loan servicing) and
		// mode (right now either search or loan servicing again) onto the level stack
	}

	public function Main()
	{
		switch($this->request->action)
		{
			case "place_in_hold_status":
				$this->loan_servicing->Add_Hold();
				$this->Add_Current_Level();
				break;

			case "return_from_service_hold":
				$this->loan_servicing->Remove_Hold();
				$this->Add_Current_Level();
				break;

			case "place_in_amortization":
				$this->loan_servicing->Place_In_Amortization();
				$this->Add_Current_Level();
				break;

			case "dissolve_amortization":
				$this->loan_servicing->Dissolve_Amortization();
				$this->Add_Current_Level();
				break;

			/** ACH Batch Related **/
			case "send_batch":
				// This sets the progress type so the batch_progress.html  calls the right
				// progress queue from the get_progress.php script.
				ECash::getLog()->Write("Agent:".ECash::getAgent()->AgentId." hit Send Batch");
				$data = ECash::getTransport()->Get_Data();
				$data->progress_process_type = "ach";
				ECash::getTransport()->Set_Data($data);
				$this->loan_servicing->Send_Batch();
				break;

			case "batch_history":
				$this->loan_servicing->Batch_History();
				break;

			case "return_history":
				$this->loan_servicing->Return_File_History();
				break;
			case "return_history_detail":
				$this->loan_servicing->Return_File_History_Detail();
				break;
			case "upload_return":
				$this->loan_servicing->Upload_Return_File();
				break;
			case "review_batch":
				$sort_by = isset($this->request->sort) ? $this->request->sort : NULL;
				$this->loan_servicing->Review_Batch($sort_by);
				break;
			case "review_cards":
				$sort_by = isset($this->request->sort) ? $this->request->sort : NULL;
				$this->loan_servicing->Review_Cards($sort_by);
				break;

			case "close_out":
				ECash::getLog()->Write("Agent:".ECash::getAgent()->AgentId." hit Close Out");
				$this->loan_servicing->Close_Out();
				break;

			case "process_cards":
				ECash::getLog()->Write("Agent:".ECash::getAgent()->AgentId." hit Processed Payment Cards");
				$data = ECash::getTransport()->Get_Data();
				$data->progress_process_type = "ach";
				ECash::getTransport()->Set_Data($data);
				$this->loan_servicing->Process_Cards();
				break;

			case "resend_batch":
				if ( !empty($this->request->batch_id) && is_numeric($this->request->batch_id) )
				{
					$this->loan_servicing->Batch_Resend($this->request->batch_id);
				}
				else
				{
					$this->loan_servicing->Batch_Resend(NULL);
				}
				break;
			case "download_batch":
					$this->loan_servicing->Download_Batch($this->request->batch_id);
				break;
			/** ACH Batch Related **/
			
			//this is to quickly download a report.  This is incredibly quick and dirty functionality to allow you to download an existing
			//report.  Please feel free to improve this, or, if it proves to be too problematic, remove it.  This is not required
			//by the spec.
			case 'download_report':
				
				//get the report 
				$type = ECash::getFactory()->getModel('SreportDataType');
			
				$report_type_id = $type->getTypeId($this->request->report_type);
				$report = ECash::getFactory()->getModel('SreportData');
				$report->loadBy(array('sreport_id' => $this->request->report_id, 'sreport_data_type_id' => $report_type_id));
				$data = $report->sreport_data;
				
				$extension = strtolower($report->filename_extension);
				header("Accept-Ranges: bytes\n");
				header("Content-Length: ".strlen($data)."\n");
				header("Content-Disposition: attachment; filename={$report->filename}.{$extension}\n");
				switch (strtolower($report->filename_extension))
				{
					case 'csv':
					case '.csv':
						header("Content-Type: text/csv\n\n");
					break;
					
					case 'xml':
							header("Content-Type: text/xml\n\n");
					break;
					
					case 'pdf':
							header("Content-Type: application/pdf\n\n");
					break;
				}
			
				print($data);
				//Yeah, really, I put a die there.  You tell me a better way to make sure it doesn't do anything else!
				die;
				break;
			case 'review_settlement':
				$this->loan_servicing->Review_Settlement();
				break;
			case 'resend_settlement':
				$this->loan_servicing->Resend_Settlement();
				break;
				
			case 'generate_settlement':
				$this->loan_servicing->Generate_Settlement();
				break;
				
			case 'regenerate_settlement':
				$this->loan_servicing->Regenerate_Settlement();
				break;
			case "ReminderRemove":
				$this->loan_servicing->Reminder_Remove();
				break;
								
			default:
				$this->Master_Main();
		}
				

// 		$data = ECash::getTransport()->Get_Data();
// 		if(is_object($data))
// 		{
// 			$data->queues = $this->loan_servicing->Get_Queue_Count();
// 			ECash::getTransport()->Set_Data($data);
// 		}
		
		return;
	}

	protected function Change_Status()
	{
		$this->loan_servicing->Change_Status();
	}

	protected function Add_Comment()
	{
		$this->loan_servicing->Add_Comment();
	}

}
?>
