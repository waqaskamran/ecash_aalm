<?php

require_once(SERVER_CODE_DIR . "master_module.class.php");
require_once(SERVER_CODE_DIR . "module_interface.iface.php");

// Collections module
class Module extends Master_Module
{
	public $collections;
	public $corrections;
	public $from_date;
	public $to_date;
	
	protected $default_mode;

	public function __construct(Server $server, $request, $module_name)
	{
        parent::__construct($server, $request, $module_name); 
		$this->_add_edit_object();

		$section_names = ECash::getACL()->Get_Acl_Access('collections');

		$allowed_submenus = array();
		foreach($section_names as $key => $value)
		{
			$allowed_submenus[] = $value;
		}

		ECash::getTransport()->Set_Data((object) array('allowed_submenus' => $allowed_submenus));
		if(count($allowed_submenus) == 0 ||
		   (!empty($request->mode) && !in_array($request->mode, $allowed_submenus)))
		{
			/**
			 * Follow Ups set the mode to Customer Service for some reason.  It's this way in
			 * CLK eCash as well.  This may call for a different way of handling the mode
			 * in this request, or a rewrite of the button form.  Currently it just blows up. [BR]
			 */
			print "<pre>";
			var_dump($allowed_submenus);
			var_dump($request);
			print "</pre>";
			die("You have no permission for mode {$request->mode}, so we showed you this instead.  That mode might not be implemented.");
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
		
		if (isset($this->request->mode) && ($this->request->mode == 'internal')
		    && (!isset($this->request->action)))
		{
			$this->request->action = 'load_values';
		}

		$mode = '';

		ECash::getTransport()->Add_Levels($module_name);
		
		if (!empty($this->request->mode) || $this->request->action == 'search' || $this->request->action == 'get_next_application')
		{
			$mode = isset($this->request->mode) ? $this->request->mode : 'internal';

			if ($this->request->action == 'search')
			{
				$mode = 'internal';
				ECash::getTransport()->Add_Levels('internal');
			}
			else if (($this->request->action == 'get_next_application') || ($this->request->action == 'get_next_affiliated_application'))
			{
				ECash::getTransport()->Add_Levels('internal');
			}
			else if ($this->request->action == 'search_quick_checks')
			{
				ECash::getTransport()->Add_Levels($mode);
			}
			else if ($this->request->action == 'receive_quick_checks')
			{

			}
			else
			{
				ECash::getTransport()->Add_Levels($mode);

				// added && application_id not set so that it will display apps when linked from reports
				if ($this->request->mode == 'internal' && !isset($this->request->application_id))
				{
					ECash::getTransport()->Add_Levels('search');
				}
				elseif ($this->request->mode == 'external')
				{
					ECash::getTransport()->Add_Levels('external');
					if ( !isset($this->request->action) || ($this->request->action != 'refresh' && $this->request->action != 'monitor_batch' && $this->request->action != 'external_batch_process' && $this->request->action != 'download_external_batch' && $this->request->action != 'external_adj_process') )
					{
						$this->request->action = 'external_apps';							
					}					
	
					ECash::getTransport()->Add_Levels($this->request->action);
				}
				elseif ($this->request->mode == 'corrections')
				{
					ECash::getTransport()->Add_Levels('corrections');
					if(!isset($this->request->action))
					{
						$this->request->action = "corrections_overview";
					}
				}
				elseif ($this->request->mode == "incoming")
				{
					if (!$this->request->action)
					{
						$this->request->action = "post_collections";
					}
					ECash::getTransport()->Add_Levels($this->request->mode, $this->request->action);
				}
				else
				{
					ECash::getTransport()->Add_Levels($this->request->mode);				
				}
			}
		}
		else
		{
			if (isset($this->request->action) && $this->request->action == 'download_external_apps')
			{
				ECash::getTransport()->Add_Levels('external');
				ECash::getTransport()->Add_Levels('external');
			}
			else if  (isset($this->request->action) && $this->request->action == 'process_incoming_collections')
			{
				ECash::getTransport()->Add_Levels($module_name, 'incoming', 'incoming', $this->request->action);				
			}
			else if (isset($this->request->action) && ($this->request->action != ""))
			{
				$mode = $this->default_mode;
				ECash::getTransport()->Add_Levels('internal');
			}
			else
			{
				// Default to First Available Mode
				$mode = $this->default_mode;
				ECash::getTransport()->Add_Levels($mode, 'search');
			}
		}

		$_SESSION['collections_mode'] = $mode;

		//create any objects needed by any certain modes
		require_once(SERVER_MODULE_DIR . $module_name . "/collections.class.php");
		$this->collections = new Collections($server, $request, $mode, $this);

		require_once (LIB_DIR . "Document/Document.class.php");

		require_once(SERVER_CODE_DIR . "external_collections_query.class.php");
		$this->external_collections = new External_Collections($server);

		
		// Set needed request data into the transport

		$m = isset($this->request->from_date_month) ? $this->request->from_date_month : 0;
		$d = isset($this->request->from_date_day)   ? $this->request->from_date_day   : 0;
		$y = isset($this->request->from_date_year)  ? $this->request->from_date_year  : 0;

		$date = $this->Get_Date($m, $d, $y);
		$this->from_date = new stdClass();
		$this->from_date->from_date_month = $date['month'];
		$this->from_date->from_date_day   = $date['day'];
		$this->from_date->from_date_year  = $date['year'];
		ECash::getTransport()->Set_Data($this->from_date);

		$m = isset($this->request->to_date_month) ? $this->request->to_date_month : 0;
		$d = isset($this->request->to_date_day)   ? $this->request->to_date_day   : 0;
		$y = isset($this->request->to_date_year)  ? $this->request->to_date_year  : 0;

		$date = $this->Get_Date($m, $d, $y);
		$this->to_date = new stdClass();
		$this->to_date->to_date_month = $date['month'];
		$this->to_date->to_date_day   = $date['day'];
		$this->to_date->to_date_year  = $date['year'];
		ECash::getTransport()->Set_Data($this->to_date);
	}



	public function Main()
	{
		switch($this->request->action)
		{
			
			/**
			 * I am pretty sure this action is not used by CLK (or even impact?) */
			case "ClaimApp":
			//	require_once(SQL_LIB_DIR."/queues.lib.php");
				
				//Create_Agent_Affiliation($this->request->application_id, $_SESSION["agent_id"], $_SESSION["company_id"], NULL);
				$app = ECash::getApplicationById($this->request->application_id);
				$affiliations = $app->getAffiliations();
				$affiliations->add(ECash::getAgent(), 'collections', 'owner', null);
			
				require_once(SQL_LIB_DIR."util.func.php");
				$queue_log = get_log("queues");
				$queue_log->Write(__FILE__.":".'$Revision$'.":".__LINE__.":".__METHOD__."()",LOG_NOTICE);

			//	remove_from_automated_queues($this->request->application_id);

				$qi = new ECash_Queues_BasicQueueItem($this->request->application_id);
				$qm = ECash::getFactory()->getQueueManager();
				$qm->removeFromAllQueues($qi);
		
				$this->request->criteria_type_1      = 'application_id';
				$this->request->search_deliminator_1 = 'is';
				$this->request->search_criteria_1    = $this->request->application_id;
				$this->request->criteria_type_2      = '';
				$this->request->search_deliminator_2 = 'is';
				$this->request->search_criteria_2    = '';
				$num = $this->search->Search();
				ECash::getTransport()->Add_Levels('overview','personal','view','general_info','view');
				break;
			
			case "recall_2nd_tier" :
				$this->collections->Recall_Loan($this->request->application_id);
				break;

			case "download_external_batch":
				$sreport = ECash::getFactory()->getModel('Sreport');
				$sreport->loadBy(array('sreport_id' => $this->request->report_id));		

				$sreport_data = ECash::getFactory()->getModel('SreportData');

				if ($sreport_data->loadBy(array('sreport_id' => $this->request->report_id)))
				{
					header("Accept-Ranges: bytes\n");
					header("Content-Length: ".strlen($sreport_data->sreport_data)."\n");
					header("Content-Disposition: attachment; filename={$sreport_data->filename}.{$sreport_data->filename_extension}\n");
					switch (strtolower($sreport_data->filename_extension))
					{
						case 'csv':
						case 'txt':
							header("Content-Type: text/csv\n\n");
						break;
						
						case 'xml':
								header("Content-Type: text/xml\n\n");
						break;
						
						case 'pdf':
								header("Content-Type: application/pdf\n\n");
						break;
					}
			
				print($sreport_data->sreport_data);

				}

				exit;	
				break;
			//That's right, BEN, they both call the exact same code, and life is 100X better for it!
			// second tier batches
			case "external_batch_process":
			// quick check batches
			case "process_quick_checks":
				// Create a new 2nd tier batch with given company
				$batch_type = 'external';
				
				$num_apps         = intval($this->request->num_apps);
				$company_id       = ECash::getCompany()->company_id;
				$batch_company_ns = $this->request->batch_report;
				// This sets the progress type so the batch_progress.html  calls the right
				// progress queue from the get_progress.php script.
				ECash::getLog()->Write("Agent:".ECash::getAgent()->AgentId." hit Process for external batch");
								
				//Clear Out The Old Batch History
				ECash_BatchProgress::purgeMessageQueue($batch_type, ECash::getCompany()->company_id);
			
	
				/**
				 * String to launch the background process to start the batch
				 * sending process.
				*/
				$exec_string = CLI_EXE_PATH."php -f ".BASE_DIR."cronjobs/ecash_engine.php " . ECash::getCompany()->name_short . " external_batch external_batch >>/virtualhosts/log/applog/" . APPLOG_SUBDIRECTORY . "/external_batch_log/external_batch.log &";
		
				/**
				 * Define the descriptor spec.  We're not worried about
				 * stdin, stdout, or stderr since we're launching this
				 * as a background process with a redirector on the
				 * command line.
				*/
				$desc = array();
		
				/**
				 * We're not using any pipes, so this is empty.
				*/
				$pipe = array();
		
				/**
				 * Environment Variables that ecash_exec.php requires
				 * to load the appripriate configurations for the
				 * current company.
				*/
				$env = array(
						'ECASH_CUSTOMER_DIR' 	=> getenv('ECASH_CUSTOMER_DIR'),
						'ECASH_CUSTOMER' 		=> getenv('ECASH_CUSTOMER'),
						'ECASH_EXEC_MODE' 		=> getenv('ECASH_EXEC_MODE'),
						'ECASH_COMMON_DIR' 		=> getenv('ECASH_COMMON_DIR'),
						'LIBOLUTION_DIR'		=> getenv('LIBOLUTION_DIR'),
						'COMMON_LIB_DIR'		=> getenv('COMMON_LIB_DIR'),
						'COMMON_LIB_ALT_DIR'	=> getenv('COMMON_LIB_ALT_DIR'),
						'APPS_TO_PROCESS'		=> $num_apps,
						'BATCH_COMPANY'			=> $batch_company_ns
						
					);

		
				 // Current working directory will be the cronjobs dir
		
				$cwd = BASE_DIR . "cronjobs/";
		
				/**
				 * Execute the shell command and close the handle since
				 * it's now running as a background process.
				*/
				$ph = proc_open($exec_string, $desc, $pipe, $cwd, $env);
				proc_close($ph);
		
				ECash::getLog()->Write("Launched External Batch Send Process: $exec_string");
				$load_balanced_domain = ECash::getConfig()->LOAD_BALANCED_DOMAIN;
				ECash::getTransport()->Set_Levels('application', 'collections', 'external', 'refresh', "http://$load_balanced_domain/?module=collections&mode=external&action=monitor_batch&process_type=external");

				break;
			case "external_apps":         // this is the inquiry screen.
				$start_date = (isset($this->request->start_date_display)) ? $this->request->start_date_display : NULL;
				$end_date   = (isset($this->request->end_date_display))   ? $this->request->end_date_display   : NULL;


				$this->external_collections->Get_Batch_Report_List();
				$this->external_collections->Get_Batch_List($start_date, $end_date);
				$this->external_collections->Get_Pending_Adjustment_Count();
				$this->external_collections->Get_Pending_Count();
				break;

         	case "process_incoming_collections":
         		// TODO need to maintain date range in the html
         		$this->external_collections->Process_Incoming_EC_File( $this->request->incoming_collections_batch_id );
         		$this->external_collections->Process_Incoming_EC_Items( $this->request->incoming_collections_batch_id );
         		
         	case "post_collections":
         		$this->external_collections->Show_Incoming_Batches($this->from_date, $this->to_date);
         		break;
				
         	case "success_report":
         		require_once(SERVER_MODULE_DIR . "reporting/incoming_collections_report.class.php");
         		
         		// run report
         		$_SESSION['reports']['incoming_collections'] = NULL;
         		$_SESSION['reports']['incoming_collections_exception'] = NULL;
         		$rep = new Report($this->server, $this->request, 'reporting', 'incoming_collections_report', $this);
         		$rep->Generate_Report();

         		// set levels appropriately         		
//         		ECash::getTransport()->Set_Levels('application','collections','incoming','refresh','http://'.LOAD_BALANCED_DOMAIN.'/?module=reporting');
         		ECash::getTransport()->Set_Levels('application','collections','incoming','refresh','/?module=reporting');
         		return;
         		
         		// eat a sandwich 
         		break;
         		
         	case "exceptions_report":
         		require_once(SERVER_MODULE_DIR . "reporting/incoming_collections_exception_report.class.php");
         		
         		// run report
         		$_SESSION['reports']['incoming_collections'] = NULL;
         		$_SESSION['reports']['incoming_collections_exception'] = NULL;
         		$rep = new Report($this->server, $this->request, 'reporting', 'incoming_collections_exception_report', $this);
         		$rep->Generate_Report();

         		// set levels appropriately         		
//         		ECash::getTransport()->Set_Levels('application','collections','incoming','refresh','http://'.LOAD_BALANCED_DOMAIN.'/?module=reporting');
         		ECash::getTransport()->Set_Levels('application','collections','incoming','refresh','/?module=reporting');
         		return;
         		
         		// eat a sandwich 
         		break;

				
			case "search_quick_checks":
				$this->collections->Get_Quick_Checks_Pending_Info();
				$this->collections->Search_Quick_Checks($this->request->start_date_display, $this->request->end_date_display);
				break;		
				
         	case "activate_and_regenerate_schedule":
				$this->collections->Active_And_Regenerate_Schedule();
				ECash::getTransport()->Set_Levels('application','collections','internal','overview','loan_actions','view','general_info','view');
				break;

			case "monitor_batch":
				$data = ECash::getTransport()->Get_Data();
				$data->company_id = ECash::getCompany()->company_id;
				$data->progress_process_type = $this->request->process_type;
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Set_Levels('application','collections','external','monitor_batch','refresh_view');
				break;
			default:
				$this->Master_Main();
				break;
		}
		
		return;
	}


	protected function Change_Status()
	{
		$this->collections->Change_Status();
	}

	// [Mantis:3180] Removed function Adjustment() to master_module.class.php

	protected function Add_Comment($comment_reference = null)
	{
		if (!empty($comment_reference)) $this->collections->Add_Comment($comment_reference = null);
		else $this->collections->Add_Comment();
	}

	/* [Mantis:3224] Moved to parent class   server/code/master_module.class.php
	protected function Add_Current_Level()
	{
		ECash::getTransport()->Add_Levels('collections');
	}
	*/

	protected function Get_Date($month = 0, $day = 0, $year = 0)
	{
		$date = array();
		$date['month'] = ($month > 0 ? $month : date('m'));
		$date['day']   = ($day   > 0 ? $day   : date('d'));
		$date['year']  = ($year  > 0 ? $year  : date('Y'));

		if( checkdate($date['month'], $date['day'], $date['year']) )
			return $date;
		else
			return array( 'month' => date('m'),
			              'day'   => date('d'),
				      'year'  => date('Y')
				      );
	}

	protected function Make_Zero_If_Null( $s )
	{
		if ( isset($s) )
		{
			$s = trim($s);
			if ( strlen($s) > 0 ) return $s;
		}

		return 0;
	}
}
?>
