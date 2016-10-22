<?php

require_once(LIB_DIR . "Ach/ach.class.php");
require_once(LIB_DIR . "business_rules.class.php");
require_once(SERVER_CODE_DIR . "base_module.class.php");
require_once(SERVER_CODE_DIR . "external_collections.class.php");
require_once(SQL_LIB_DIR."util.func.php");
require_once(SERVER_CODE_DIR . "email_queue.class.php");
require_once(ECASH_COMMON_DIR . 'ecash_api/interest_calculator.class.php');
require_once(LIB_DIR.'AgentAffiliation.php');
require_once(SQL_LIB_DIR . "fetch_status_map.func.php");
require_once(ECASH_DIR.'ITSettlement/it_settlement.class.php');

class Loan_Servicing extends Base_Module
{
	private $ach;
	private $card;
	private $ld;
	private $pdc;
	private $ec;
	protected $request;

	public function __construct(Server $server, $request, $mode, Module_Interface $module = NULL)
	{
		parent::__construct($server, $request, $mode, $module);

		$this->ld 	= new Loan_Data($server);
		$this->module_name = 'loan_servicing';
		$this->request     = $request;

		$obj = new stdClass();
		$obj->nextSafeACHActionDate = $this->getNextSafeAchActionDate();
		$obj->nextSafeACHDueDate = $this->getNextSafeAchDueDate();
		ECash::getTransport()->Set_Data($obj);
	}

	private function Get_ACH()
	{
		if(! isset($this->ach))
		{
			$this->ach = ACH::Get_ACH_Handler($this->server, 'batch');
		}

		return $this->ach;
	}

	private function Get_Card()
	{
		if(! isset($this->card))
		{
			$this->card = Card::Get_Card_Process($this->server, 'batch');
		}

		return $this->card;
	}

	/**
	 * gets the payday calculator
	 *
	 * @return Pay_Date_Calc_3
	 */
	private function Get_PDC()
	{
		if(! isset($this->ach)) {
			$holidays = Fetch_Holiday_List();
			$this->pdc 	= new Pay_Date_Calc_3($holidays);
		}

		return $this->pdc;
	}

	public function Get_Closing_Timestamp($date)
	{
		$obj = new stdClass();
		if ($stamp = $this->Get_ACH()->Get_Closing_Timestamp($date))
		{
			$obj->closing_time = $stamp;
		}
		return $obj;
	}

	// This function was moved to Batch_Maintenance to make it easier to call
	// from a nightly cron job, and the remaining method here is simply a wrapper
	// that returns the message to the client.
	public function Close_Out()
	{
		$bm = new Batch_Maintenance($this->server);
		$obj = $bm->Close_Out();
		//asm 80
		//$ach_providers = $this->Get_ACH()->getAchProviders();
		$this->Get_ACH()->assignApplicationsToAchProviders();
		/////
		ECash::getTransport()->Set_Data($obj);
		ECash::getTransport()->Add_Levels('batch', 'message');
	}

	// This function was moved to Batch_Maintenance to make it easier to call
	// from a nightly cron job, and the remaining method here is simply a wrapper
	// that returns the message to the client.
	public function Process_Cards()
	{
		$cp = new Card_Processor($this->server);
		$obj = $cp->Process_Cards();

		ECash::getTransport()->Set_Data($obj);
		ECash::getTransport()->Add_Levels('batch', 'message');
	}

	/**
	 * Displays a list of IT Settlement reports
	 *
	 */
	public function Review_Settlement()
	{
		$it_settlement = new IT_Settlement($this->server);
		$obj = new stdClass();

		$start_date = NULL;
		if (isset($this->request->start_date_month) && isset($this->request->start_date_day) && isset($this->request->start_date_year))
		{
			$start_date = $this->request->start_date_year . '-' . $this->request->start_date_month . '-' . $this->request->start_date_day;
		}

		$end_date = NULL;
		if (isset($this->request->end_date_month) && isset($this->request->end_date_day) && isset($this->request->end_date_year))
		{
			$end_date = $this->request->end_date_year . '-' . $this->request->end_date_month . '-' . $this->request->end_date_day;
		}

		$current_only = (isset($this->request->current_only)) ? TRUE : FALSE;

		if ($start_date != NULL && $end_date != NULL)
			$obj->settlement_report_list =  $it_settlement->fetchReportsList($current_only, $start_date, $end_date);
		else
			$obj->settlement_report_list =  $it_settlement->fetchReportsList($current_only);

		ECash::getTransport()->Add_Levels('it_settlement', 'display_it_settlement');
		ECash::getTransport()->Set_Data($obj);

	}

	/**
	 * Resends any selected IT Settlement report set
	 *
	 */
	public function Resend_Settlement()
	{
		$settlements = new IT_Settlement($this->server);
		$settlements->sendReport($this->request->report_id);
		$this->Review_Settlement();

	}
	/**
	 * This regenerates the specified settlement report and obsolutes the old version
	 *
	 */
	public function Regenerate_Settlement()
	{
		$settlement_id = $this->request->report_id;
		$settlements = new IT_Settlement($this->server);
		$settlements->regenerateReport($settlement_id);
		$this->Review_Settlement();

	}

	public function Review_Batch($sort_by = NULL)
	{
		$obj = new stdClass();
		$today = date("Y-m-d",strtotime("now"));
		$tomorrow = $this->Get_PDC()->Get_Next_Business_Day($today);
		$close_time = $this->Get_ACH()->Get_Closing_Timestamp($today);

		if ($close_time)
		{
			// generate our results, and sort them if required
			$batchlist = $this->Get_ACH()->Preview_ACH_Batches($tomorrow);


			if ($sort_by !== NULL) $batchlist = $this->Sort_Batch_Review($batchlist, $sort_by);

			if ($batchlist == false)
			{
				$obj->message = DisplayMessage::get(array('generic', 'no entries to send'));
				ECash::getTransport()->Add_Levels('batch', 'message');
			}
			else
			{
				$obj->batchlist = $batchlist;
				$validatemanager = new ECash_ACHBatch_ValidatorManager();
				$validatemanager->Validate($batchlist);
				$messages = $validatemanager->getMessageArray();
				$obj->batchmessages = $messages;
				ECash::getTransport()->Add_Levels('batch', 'review_batch');
			}

		}
		else
		{
			$str = DisplayMessage::get(array('report', 'need to close out business'));
			$obj->message = $str;
			ECash::getTransport()->Add_Levels('batch', 'message');
		}

		ECash::getTransport()->Set_Data($obj);
	}

	public function Review_Cards($sort_by = NULL)
	{
		$obj = new stdClass();
		$today = date("Y-m-d",strtotime("now"));

		$tomorrow = $this->Get_PDC()->Get_Next_Business_Day($today);
		$close_time = $today;

		if ($close_time)
		{
			// generate our results, and sort them if required
			$batchlist = $this->Get_Card()->Preview_Card_Batches($tomorrow);


			if ($sort_by !== NULL) $batchlist = $this->Sort_Batch_Review($batchlist, $sort_by);

			if ($batchlist == false) {
				$obj->message = DisplayMessage::get(array('generic', 'no entries to send'));
				ECash::getTransport()->Add_Levels('batch', 'message');
			} else {
				$obj->batchlist = $batchlist;
				$validatemanager = new ECash_CardBatch_ValidatorManager();
				$validatemanager->Validate($batchlist);
				$messages = $validatemanager->getMessageArray();
				$obj->batchmessages = $messages;
				ECash::getTransport()->Add_Levels('batch', 'review_cards');
			}
		}
		else
		{
			$str = DisplayMessage::get(array('report', 'need to close out business'));
			$obj->message = $str;
			ECash::getTransport()->Add_Levels('batch', 'message');
		}

		ECash::getTransport()->Set_Data($obj);
	}

	protected function Sort_Batch_Review($batchlist, $sort_by = NULL)
	{

		include_once("advanced_sort.1.php");

		$sort_order = (isset($_SESSION['batch_last_sort']) && ($_SESSION['batch_last_sort'] == $sort_by)) ? (!$_SESSION['batch_last_sort_order']) : FALSE;
		$sort_order = ($sort_order) ? SORT_DESC : SORT_ASC;

		$batchlist = Advanced_Sort::Sort_Data($batchlist, $sort_by, $sort_order);

		$_SESSION['batch_last_sort'] = $sort_by;
		$_SESSION['batch_last_sort_order'] = ($sort_order == SORT_DESC);

		return $batchlist;

	}

	public function Batch_Resend($batch_id)
	{
		$today = date("Y-m-d", strtotime("now"));
		$tomorrow = $this->Get_PDC()->Get_Next_Business_Day($today);

		$resend_receipt = $this->Get_ACH()->Resend_Failed_Batch($batch_id, $tomorrow);

		$str  = "Re-sent batch information:\n";
		$str .= "Batch ID: {$resend_receipt['batch_id']}\n";
		$str .= "Status: " . ucfirst($resend_receipt['status']) . "\n";
		$str .= "Reference No: {$resend_receipt['ref_no']}\n";
		if ($resend_receipt['status'] == 'failed')
		{
			$str .= "\nTransmission failed again or batch was not eligible to be re-sent!";
		}
		$obj->message = $str;

		ECash::getTransport()->Set_Data($obj);
		ECash::getTransport()->Add_Levels('batch_history', 'message');
	}

	public function Download_Batch($batch_id)
	{
		$file = $this->Get_ACH()->getBatchFile($batch_id);
		if(!empty($file))
		{
			//asm 114
			$batch_model = ECash::getFactory()->getModel('AchBatch');
			$batch_model->loadBy(array('ach_batch_id' => $batch_id,));
			$ach_provider_id = $batch_model->ach_provider_id;

			$pr_model = ECash::getFactory()->getModel('AchProvider');
			$pr_model->loadBy(array('ach_provider_id' => $ach_provider_id,));

			$ach_provider_name = $pr_model->name_short;

			//$filename = $this->Get_ACH()->Get_Remote_Filename($batch_id); //asm 80
			$filename = ACH::Get_ACH_Handler($this->server, 'batch', $ach_provider_name)->Get_Filename_Download($batch_id); //asm 114
			header("Accept-Ranges: bytes\n");
			header("Content-Length: ".strlen($file)."\n");
			header("Content-Disposition: attachment; filename={$filename}\n");
			header("Content-Type: text\n\n");
			echo $file;
		}
		else
		{

		}
		exit();
	}

	public function Batch_History()
	{
		$obj = new stdClass();

		//$start_date = isset($this->request->start_date)? date("Y-m-d", strtotime($this->request->start_date)) : date("Y-m-d", strtotime("-1 year"));
		$start_date = isset($this->request->start_date)? date("Y-m-d", strtotime($this->request->start_date)) : date("Y-m-d");
		$end_date = isset($this->request->end_date)? date("Y-m-d", strtotime($this->request->end_date)) : date("Y-m-d");

		$batchlist = $this->Get_ACH()->Fetch_ACH_Batch_Stats($start_date, $end_date);
		if ($batchlist == false)
		{
			//$obj->message = DisplayMessage::get(array('ach', 'no ach batches'));
			//ECash::getTransport()->Add_Levels('batch_history', 'message');
			ECash::getTransport()->Add_Levels('batch_history', 'display_batch_history');
		}
		else
		{
			$obj->batchlist = $batchlist;
			ECash::getTransport()->Add_Levels('batch_history', 'display_batch_history');
		}

		$obj->batch_start_date = date("m/d/Y", strtotime($start_date));
		$obj->batch_end_date = date("m/d/Y", strtotime($end_date));

		ECash::getTransport()->Set_Data($obj);
	}

	public function Return_File_History()
	{
		$obj = new stdClass();

		$start_date	= isset($this->request->start_date)? date("Y-m-d", strtotime($this->request->start_date)) : date("Y-m-d", strtotime("-1 year"));
		$end_date	= isset($this->request->end_date)? date("Y-m-d", strtotime($this->request->end_date)) : date("Y-m-d");

		$report_list = ECash::getFactory()->getModel('AchReportList');
		$report_list->loadHistory($start_date, $end_date, ECash::getCompany()->company_id);

		$obj->report_list = $report_list;
		ECash::getTransport()->Add_Levels('return_file_history', 'display_return_file_history');
		$obj->start_date = date("m/d/Y", strtotime($start_date));
		$obj->end_date = date("m/d/Y", strtotime($end_date));

		ECash::getTransport()->Set_Data($obj);
	}

	public function Upload_Return_File()
	{
		$obj = new stdClass();

		$start_date	= isset($this->request->start_date)? date("Y-m-d", strtotime($this->request->start_date)) : date("Y-m-d", strtotime("-1 year"));
		$end_date	= isset($this->request->end_date)? date("Y-m-d", strtotime($this->request->end_date)) : date("Y-m-d");

		$report_list = ECash::getFactory()->getModel('AchReportList');
		$report_list->loadHistory($start_date, $end_date, ECash::getCompany()->company_id);

		if(isset($this->request->processfile))
		{
			/**
			 * User uploaded the file and gave the go ahead to process the 
			 * file.
			 */
			$this->Process_Return_File();
		}
		else
		{
			/**
			 * User uploaded the file, we're just going to save it, validate it, 
			 * and preview the results before processing.
			 */
			$this->Handle_Return_File();
		}

	//	if(count($report_list) == 0)
	//	{
	//		$obj->message = DisplayMessage::get(array('ach', 'no return files'));
	//		ECash::getTransport()->Add_Levels('return_file_history', 'message');
	//	}
	//	else
	//	{
			$obj->report_list = $report_list;
			ECash::getTransport()->Add_Levels('return_file_history', 'display_upload_return_file');
	//	}

		$obj->start_date = date("m/d/Y", strtotime($start_date));
		$obj->end_date = date("m/d/Y", strtotime($end_date));

		ECash::getTransport()->Set_Data($obj);
	}

	/*
		Verifies if requested ach id exists
		Processes Return file
		Updates Ach Report row to mark as processed
	*/
	protected function Process_Return_File()
	{
		$obj = new stdClass();
		$report_id = $_SESSION['ach_report_id'];
		$report_model = ECash::getFactory()->getModel('AchReport');
		$report_model->loadBy(array('ach_report_id' => $report_id ));

		//asm 104
		$obj->file_format = $this->request->file_format;
		if (empty($this->request->file_format))
			$ach = ACH::Get_ACH_Handler($this->server, 'return');
		else
			$ach = ACH::Get_ACH_Handler($this->server, 'return', $this->request->file_format);
		
		$obj->file_formats = $ach->getFormatTypes();
		////////

		$obj->file_type = $this->request->file_type;
		$obj->file_types = $ach->getReportTypes();
		if(!empty($report_model->remote_response))
		{

			if($report_data = $ach->Process_ACH_Report_Data($ach->fetchReportById($report_id), $this->request->file_type))
			{
				$report_model->report_status = 'processed';
				$report_model->save();
				$obj->report_data = $report_data;
				$obj->message = 'Return File Processed';
				$obj->report_data = $report_data;
				$obj->Process_Reschedule_Disabled = 'none';
				$obj->Process_Disabled = 'none';
				$return_value = count($report_data);
			}
			else
			{
				$obj->message = 'Error Processing Return File, Processing failed';
				$obj->report_data = array();
				$return_value = false;
			}

		}
		else
		{
			$obj->message = 'Error Processing Return File, File was Empty';
			$obj->report_data = array();
			$return_value = false;
		}
		ECash::getTransport()->Set_Data($obj);
		return $return_value;
	}

	/*
		Handles the uploaded return file for manual processing
	*/
	protected function Handle_Return_File()
	{
		$obj = new stdClass();
		$obj->message = '';
		$obj->Process_Reschedule_Disabled = 'none';
		$obj->Process_Disabled = 'none';
		$obj->file_type = $this->request->file_type;
		$file = $_FILES;
		//asm 104
		$obj->file_format = $this->request->file_format;
		if (empty($this->request->file_format))
			$ach = ACH::Get_ACH_Handler($this->server, 'return');
		else
			$ach = ACH::Get_ACH_Handler($this->server, 'return', $this->request->file_format);
		
		$obj->file_formats = $ach->getFormatTypes();
		
		$pr_model = ECash::getFactory()->getModel('AchProvider');
		$pr_model->loadBy(array('name_short' => $this->request->file_format,));
		$ach_provider_id = $pr_model->ach_provider_id;
		////////
		$obj->file_types = $ach->getReportTypes();
		if(!empty($file['return_file_upload']))
		{
			$file_name = $file['return_file_upload']['tmp_name'];
			$file_contents = file_get_contents($file_name);
			if ($this->request->file_format == "empire_ofs")
				$file_contents = str_replace("\t", ",", $file_contents);
			//Parsing uploaded file into an array if formated correctly

			$return_format = $ach->getReportFormat($obj->file_type);
			$report_data = $ach->Parse_Report_Batch($file_contents, $return_format);

			if(!empty($report_data))
			{
				$data_hash = md5($file_contents);
				$report_model = ECash::getFactory()->getModel('AchReport');
				//checking if file has been previously processed
				//if not creating new ach report row
				if(!$report_model->loadBy(array('content_hash' => $data_hash, 'report_status' => 'processed')))
				{
					$obj->report_data = $report_data;
					$obj->Process_Reschedule_Disabled = 'block';
					$obj->Process_Disabled = 'block';

					$report_model->date_created = Date('Y-m-d');
					$report_model->date_request = Date('Y-m-d');
					$report_model->company_id = ECash::getCompany()->company_id;
					$report_model->ach_report_request = $file_name;
					$report_model->remote_response = $file_contents;
					$report_model->report_status = 'received';
					$report_model->delivery_method = 'manual';
					$report_model->content_hash = $data_hash;
					$report_model->ach_provider_id = $ach_provider_id; //asm 114
					$report_model->save();
					$_SESSION['ach_report_id'] = $report_model->ach_report_id;
				}
				else
				{
					$obj->message = 'File has already been Processed';
					$obj->report_data = array();
				}
			}
			else
			{
				$obj->message = 'Invalid File Format';
				$obj->report_data = array();
			}
		}
		else
		{
			$obj->report_data = array();
		}
		ECash::getTransport()->Set_Data($obj);
	}

	public function Return_File_History_Detail()
	{
		$obj = new stdClass();
		$report_id = $this->request->report_id;
		$report_model = ECash::getFactory()->getModel('AchReport');
		$report_model->loadBy(array('ach_report_id' => $report_id ));

		//asm 114
		$ach_provider_id = $report_model->ach_provider_id;
		if ($ach_provider_id > 0)
		{
			$pr_model = ECash::getFactory()->getModel('AchProvider');
			$pr_model->loadBy(array('ach_provider_id' => $ach_provider_id,));
			$ach_provide_name_short = $pr_model->name_short;
		}
		else
		{
			$ach_provide_name_short = NULL;
		}
		//////////

		if(!empty($report_model->remote_response))
		{
			$ach = ACH::Get_ACH_Handler($this->server, 'return', $ach_provide_name_short); //asm 114
			$return_format = $ach->getReportFormat('returns');
			$report_data = $ach->Parse_Report_Batch($report_model->remote_response, $return_format);
			ECash::getTransport()->Add_Levels('return_file_history', 'display_return_file_history_detail');
			$obj->report_data = $report_data;
		}
		else
		{
			ECash::getTransport()->Add_Levels('return_file_history', 'display_return_file_history_detail');
			$obj->report_data = array();
		}
		ECash::getTransport()->Set_Data($obj);
	}
	
	public function Send_Card()
	{
		//Clear Out The Old Batch History
		ECash_BatchProgress::purgeMessageQueue('card', $this->server->company_id);
		$exec_string = CLI_EXE_PATH."php -f ".BASE_DIR."cronjobs/ecash_engine.php " . $this->server->company . " card batch_maintenance >>/virtualhosts/log/applog/" . APPLOG_SUBDIRECTORY . "/card/card_batch.log &";

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
				'ECASH_CUSTOMER_DIR' => getenv('ECASH_CUSTOMER_DIR'),
				'ECASH_CUSTOMER' => getenv('ECASH_CUSTOMER'),
				'ECASH_EXEC_MODE' => getenv('ECASH_EXEC_MODE'),
				'ECASH_COMMON_DIR' => getenv('ECASH_COMMON_DIR'),
				'LIBOLUTION_DIR' => getenv('LIBOLUTION_DIR'),
				'COMMON_LIB_DIR' => getenv('COMMON_LIB_DIR'),
				'COMMON_LIB_ALT_DIR' => getenv('COMMON_LIB_ALT_DIR')
			);

		/**
		 * Current working directory will be the cronjobs dir
		 */
		$cwd = BASE_DIR . "cronjobs/";

		/**
		 * Execute the shell command and close the handle since
		 * it's now running as a background process.
		 */
		$ph = proc_open($exec_string, $desc, $pipe, $cwd, $env);
		proc_close($ph);

		$this->server->log->Write("Launched Card Batch Send Process: $exec_string");
		$load_balanced_domain = ECash::getConfig()->LOAD_BALANCED_DOMAIN;
		ECash::getTransport()->Add_Levels('refresh', "http://$load_balanced_domain/?module=loan_servicing&mode=batch_mgmt&action=monitor_batch&process_type=card");
	}

	// This function was moved to Batch_Maintenance to make it easier to call
	// from a nightly cron job, and the remaining method here is simply a wrapper
	// that returns the message to the client.
	public function Send_Batch()
	{
		//Clear Out The Old Batch History
		ECash_BatchProgress::purgeMessageQueue('ach', $this->server->company_id);

		/**
		 * 5/29/2008
		 *
		 * Switched from exec() to proc_open() because certain environment
		 * variables need to be passed through to the shell environment
		 * and proc_open() is the only way to do that.  -- BrianR
		 */


		/**
		 * String to launch the background process to start the batch
		 * sending process.
		 */
		$exec_string = CLI_EXE_PATH."php -f ".BASE_DIR."cronjobs/ecash_engine.php " . $this->server->company . " ach batch_maintenance >>/virtualhosts/log/applog/" . APPLOG_SUBDIRECTORY . "/ach/ach_batch.log &";

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
				'COMMON_LIB_ALT_DIR'	=> getenv('COMMON_LIB_ALT_DIR')
			);

		/**
		 * Current working directory will be the cronjobs dir
		 */
		$cwd = BASE_DIR . "cronjobs/";

		/**
		 * Execute the shell command and close the handle since
		 * it's now running as a background process.
		 */
		$ph = proc_open($exec_string, $desc, $pipe, $cwd, $env);
		proc_close($ph);

		$this->server->log->Write("Launched ACH Batch Send Process: $exec_string");
		$load_balanced_domain = ECash::getConfig()->LOAD_BALANCED_DOMAIN;
		ECash::getTransport()->Add_Levels('refresh', "http://$load_balanced_domain/?module=loan_servicing&mode=batch_mgmt&action=monitor_batch&process_type=ach");
	}

	public function Change_Status()
	{
		$action_result = NULL;
		switch(strtolower($this->request->submit_button))
		{
			//GForge [#20937]
			case "withdraw app":
			case "withdraw":
				$action_result = $this->Withdraw();
				break;

			case 'deny':
				$action_result = $this->Deny();
				break;
			//end GForge [#20937]

			case "send to verify":
			case "reverify":
				// Comment
				$action_result = $this->ld->To_Verify_Queue($this->request->application_id);
				$set_data = FALSE;
				ECash::getTransport()->Set_Levels('close_pop_up');
				break;
			case "in_process":
				// Comment
				$action_result = $this->ld->To_In_Process_Queue($this->request->application_id);
				$set_data = FALSE;
				$fresh_app = $this->ld->Fetch_Loan_All($this->request->application_id);
				ECash::getTransport()->Set_Data($fresh_app);
				ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
				break;
			case 'bankruptcy_notification' :
				$action_result = $this->ld->Bankruptcy($this->request->application_id, false);
				if ($action_result)
				{
					//Remove_Unregistered_Events_From_Schedule($this->request->application_id); //mantis:4454
					$fresh_app = $this->ld->Fetch_Loan_All($_SESSION['current_app']->application_id);
					ECash::getTransport()->Set_Data($fresh_app);
					ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
				}
				$this->request->comment = "Bankruptcy notified";
				break;
			case 'bankruptcy_verified' :
				$action_result = $this->ld->Bankruptcy($this->request->application_id, true);

				//mantis:7366
				if ($action_result)
				{
					$fresh_app = $this->ld->Fetch_Loan_All($_SESSION['current_app']->application_id);
					ECash::getTransport()->Set_Data($fresh_app);
					ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
				}

				$this->request->comment = "Bankruptcy Verified";
				break;
			case 'not_bankruptcy':
				if (Has_Fatal_ACH_Codes($this->request->application_id))
				{
					if(ECash::getConfig()->USE_QUICKCHECKS === TRUE)
					{
						$action_result = $this->ld->Quickcheck($this->request->application_id);
					}
				}
				else
				{
					$action_result = $this->ld->Not_Bankruptcy($this->request->application_id);
					//if ($action_result) Schedule_Full_Pull($this->request->application_id, ECash::getMasterDb());
				}
				break;
		}

		if (!$action_result)
		{
			ECash::getTransport()->Set_Data($_SESSION['current_app']);
			ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
			return FALSE;
		}

		$comm_ref = null;
 		if(! empty($this->request->loan_actions))
			$comm_ref  = $this->Add_Loan_Action();

		if((! empty($this->request->comment)) && ($comm_ref != NULL))
			$this->Add_Comment(true, $comm_ref);
			
		return($action_result);

	}

	public function Send_Docs()
	{
		if(!empty($this->request->document_list))
		{
			// HACKED -- MarcC (7/13/05)
//			if (!is_array($this->request->document_list))
//			{
//				$docs = eCash_Document::Get_Document_List($this->server,"all");
//				foreach ($docs as $doc)
//				{
//					if( $doc->description == $this->request->document_list )
//					{
//						$this->request->document_list = array($doc->document_list_id => $doc->description);
//						break;
//					}
//				}
//			}



			$app = ECash::getApplicationById($_SESSION['current_app']->application_id);
			$docs = $app->getDocuments();
			if($template = $docs->getTemplateByNameShort($this->request->document_list))
			{
				if($doc = $docs->create($template))
				{

					$transport_types = $doc->getTransportTypes();
					switch (strtolower($this->request->submit)) {
						case "send fax":
							$send_method = "fax";
							$transport = $transport_types[$send_method];
							$cust_phone = isset($this->request->customer_fax) ? $this->request->customer_fax : NULL;
							$transport->setPhoneNumber($cust_phone);
							$transport->setCoverSheet(ECash::getConfig()->DOCUMENT_DEFAULT_FAX_COVERSHEET);
							break;

						case "send esig":
						case "send email":
						case "send package":
						default:
							$send_method = "email";
							$transport = $transport_types[$send_method];
							$cust_email = isset($this->request->customer_email) ? $this->request->customer_email : NULL;
							$transport->setEmail($cust_email);
					}


					if(!$doc->send($transport, ECash::getAgent()->getAgentId()))
					{
						throw new exception('Document Failed to Send');
					}

				}
				else
				{
					throw new exception('Document Failed Creation');
				}
			}
			else
			{
				throw new exception('Document Template Failed Creation');
			}
//			eCash_Document::singleton($this->server, $this->request)->Send_Document($_SESSION['current_app']->application_id, $this->request->document_list, $send_method, $cust_email);

			$_SESSION['current_app']->docs = $docs->getSentandRecieved();
			$_SESSION['current_app']->receive_doc_list = $docs->getRecievable();
			ECash::getTransport()->Set_Data($_SESSION['current_app']);
			ECash::getTransport()->Set_Levels('close_pop_up');
		}
	}
	
	public function Add_Rollover($request)
	{
		$application_id = $_SESSION['current_app']->application_id;
		$renewal =  ECash::getFactory()->getRenewalClassByApplicationID($application_id);
		switch ($this->request->action_type)
		{
		case 'fetch':
			// First make sure they're eligible
			$data = Get_Transactional_Data($application_id);

			$data->amount = $data->amt;
			$data->application_id = $application_id;

			ECash::getTransport()->Set_Data($data);
			if($request)
			{
				$result = $renewal->getRequestEligibility($application_id);
				if($result['eligible'])
				{
					ECash::getTransport()->Set_Levels('popup', 'rollover_request');
				}
				else
				{
					$data->reason = $result['reason'];
					ECash::getTransport()->Set_Data($data);
					ECash::getTransport()->Set_Levels('popup', 'refinance_ineligible');
				break;
				}
			}
			else
			{
				ECash::getTransport()->Set_Levels('popup', 'rollover');
			}
			break;
		case 'save':
			if($request)
			{
				$return = $renewal->requestRollover($application_id);
				if($return['eligible'])
				{
					//Used to give notice of success on parent page reload
					$_SESSION['javascript_on_load'] = "alert('Renewal Request Has Been Sent.');";
				}
				else
				{
					//Used to give notice of failure on parent page reload
					$_SESSION['javascript_on_load'] = "alert('Failure!! Renewal Request Has not Been Sent.\\n Reason: " . $return['reason'] ."');";
				}
			}
			else
			{
				$return = $renewal->createRollover($application_id, $this->request->amount);
				if($return['eligible'])
				{
					//Used to give notice of success on parent page reload
					$_SESSION['javascript_on_load'] = "alert('Loan has been Renewed.')";
				}
				else
				{
					//Used to give notice of failure on parent page reload
					$_SESSION['javascript_on_load'] = "alert('Failure!!! Loan has not been Renewed.\\n Reason: " . $return['reason'] ."');";
				}
			}

			$_SESSION['popup_display_list'] = array('overview', 'schedule', 'view');
			ECash::getTransport()->Set_Levels('close_pop_up');
			break;
		}
	}

	public function Add_Refinance()
	{
		$application_id = $_SESSION['current_app']->application_id;
		$renewal =  ECash::getFactory()->getRenewalClassByApplicationID($application_id);
		switch ($this->request->action_type)
		{
		case 'fetch':
			// First make sure they're eligible
			$data = Get_Transactional_Data($application_id);

			$result = $renewal->getRolloverEligibility($application_id);

			if ($result['eligible'] == FALSE)
			{
				$data->reason = $result['reason'];
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Set_Levels('popup', 'refinance_ineligible');
				break;
			}

			list($status, $schedule) = $this->ld->Fetch_Schedule_Data($application_id, false, false);
			$pd_calc = $this->Get_PDC();
			$today = date('m/d/Y', strtotime($pd_calc->Get_Business_Days_Forward(date('m/d/Y'), 1)));
			// If the batch has closed, the next business day will be two days ahead
			if(Has_Batch_Closed($this->server->company_id))
			{
				$data->next_business_day = date('m/d/Y', strtotime($pd_calc->Get_Business_Days_Forward($today, 1)));
			}
			else
			{
				$data->next_business_day = $today;
			}

			$data->amount = $data->amt;
			$data->application_id = $application_id;
			$rules = Prepare_Rules($data->rules, $data->info);
			$paydates = Get_Date_List($data->info,date('m/d/Y'),$rules,2,null,null);
			$data->next_due_date = date('m/d/Y', strtotime($paydates['effective'][0]));
			$data->calendar_end = date('m/d/Y', strtotime($paydates['effective'][1]));
			$data->schedule_status = $status;
			$data->has_pending = $data->schedule_status->num_pending_items > 0 ? TRUE : FALSE;
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Set_Levels('popup', 'refinance');
			break;
		case 'save':
			$renewal->createRollover($application_id, bcadd($this->request->minimum_payment, $this->request->amount));
			$_SESSION['popup_display_list'] = array('overview', 'schedule', 'view');
			ECash::getTransport()->Set_Levels('close_pop_up');
			break;
		}
	}

	public function Add_Grace_Period_Arrangement()
	{
		$application_id = $_SESSION['current_app']->application_id;

		switch($this->request->action_type)
		{
		case 'fetch':
			$data = Get_Transactional_Data($application_id);
			list($status, $schedule) = $this->ld->Fetch_Schedule_Data($application_id, false, false);
			$pd_calc = $this->Get_PDC();

			$today = date('m/d/Y', strtotime($pd_calc->Get_Business_Days_Forward(date('m/d/Y'), 1)));

			// If the batch has closed, the next business day will be two days ahead
			if(Has_Batch_Closed($this->server->company_id))
			{
				$data->date_effective = date('m/d/Y', strtotime($pd_calc->Get_Business_Days_Forward($today, 1)));
			}
			else
			{
				$data->date_effective = $today;
			}

			$data->action_date = date('m/d/Y', strtotime($pd_calc->Get_Business_Days_Backward($data->date_effective, 1)));

			$data->amount = $data->amt;
			$data->application_id = $application_id;

			$rules = Prepare_Rules($data->rules, $data->info);
			$paydates = Get_Date_List($data->info,date('m/d/Y'),$rules,2,null,null);

			$data->next_due_date = date('m/d/Y', strtotime($paydates['effective'][0]));
			$data->calendar_end = date('m/d/Y', strtotime($paydates['effective'][1]));

			$data->schedule_status = $status;
			$data->has_pending = $data->schedule_status->num_pending_items > 0 ? TRUE : FALSE;

			$bi = Fetch_Balance_Information($application_id);

			$analysis = Analyze_Schedule($schedule);

			// We're going to put in grace period arrangements as reattempted transactions
			// of those which have failed, and do not have a corresponding successful reatt

			// To do this, I am going to go through the failset, for each item in the fail set
			// I'm going to go through the schedule, and look for successful reattempted payments of those
			// transactions which failed. If they do not exist with that criteria. Make a list of them
			// to create based on what date is selected in the popup.
			$data->arrange_principal = 0;
			$data->arrange_fees      = 0;
			$data->arrange_interest  = 0;

			// Get NSF fees
			foreach($analysis->posted_schedule as $trans)
			{
				if (is_int($trans->origin_id))
					continue;

				// If it's a NSF Fee
				if ($trans->type == "lend_assess_fee_ach")
				{
					// Assume a payment needs generated
					$need_payment = TRUE;

					// Check schedule to see if anyone was scheduled or completed for this already
					foreach ($schedule as $checktran)
					{
						// This debit links with the assessment
						if ($checktran->origin_group_id == -$trans->transaction_register_id && $checktran->fee < 0)
						{
							// It's already completed, get the hell out of here
							if ($checktran->status != 'failed')
							{
								$need_payment = FALSE;
								break;
							}
						}
					}

					// A payment needs generated, go ahead and add it to the total
					if ($need_payment == TRUE)
					{
						$data->arrange_fees  += $trans->fee;
						$data->arrange_total += $trans->fee;
					}
				}
			}

			foreach($analysis->fail_set as $bunce)
			{
				// We don't want to consider reattempts
				if (is_int($bunce->origin_id))
					continue;

				// We only want debits
				if ($bunce->principal > 0 || $bunce->fee > 0 || $bunce->service_charge > 0 || $bunce->irrecoverable > 0)
					continue;

				// Assume it needs a reatt
				$need_reatt = TRUE;

				// Now check to see if a successful reattempt of this transaction has already been made.
				foreach($analysis->debits as $debit)
				{
					// if the origin id of the debit matches the ID of the failure, there's already been a reatt,
					// also make sure it completed or is scheduled
					if ($debit->origin_id == -$bunce->origin_group_id && $debit->status == 'complete')
					{
						$need_reatt = FALSE; // We don't need a reattempt
						break;
					}
				}

				// Add it to the list of transactions to reattempt with this
				if ($need_reatt == TRUE)
				{
					$data->arrange_principal += abs($bunce->principal);
					$data->arrange_fees      += abs($bunce->fee);
					$data->arrange_interest  += abs($bunce->service_charge);

					$data->arrange_total     += abs($bunce->principal) + abs($bunce->fee) + abs($bunce->service_charge);
				}
			}

			$data->arrange_principal = '$' . number_format($data->arrange_principal, 2);
			$data->arrange_interest  = '$' . number_format($data->arrange_interest, 2);
			$data->arrange_fees      = '$' . number_format($data->arrange_fees, 2);
			$data->arrange_total     = '$' . number_format($data->arrange_total, 2);


			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Set_Levels('popup', 'grace_period_arrangement');
			break;
		case 'save':
			$data = Get_Transactional_Data($this->request->application_id);

            list($status, $schedule) = $this->ld->Fetch_Schedule_Data($this->request->application_id, false, false);

			$analysis = Analyze_Schedule($schedule);

			// We're going to put in grace period arrangements as reattempted transactions
			// of those which have failed, and do not have a corresponding successful reatt

			// Add fees
			foreach($analysis->posted_schedule as $trans)
			{
				if (is_int($trans->origin_id))
					continue;

				// If it's a NSF Fee
				if ($trans->type == "lend_assess_fee_ach")
				{
					// Assume a payment needs generated
					$need_payment = TRUE;

					// Check schedule to see if anyone was scheduled and completed for this already
					foreach ($schedule as $checktran)
					{
						// This debit links with the assessment
						if ($checktran->origin_group_id == -$trans->transaction_register_id && $checktran->fee < 0)
						{
							// It's already completed, get the hell out of here
							if ($checktran->status != 'failed')
							{
								$need_payment = FALSE;
								break;
							}
						}
					}

					// A payment needs generated, go ahead and add it to the total
					if ($need_payment == TRUE)
					{
						$amounts   = array();

						// Make the event for the negative amount of the fees
						$amounts[] = Event_Amount::MakeEventAmount('fee', $trans->fee * -1);
						$amounts[] = Event_Amount::MakeEventAmount('service_charge', 0);
						$amounts[] = Event_Amount::MakeEventAmount('principal', 0);

						$e = Schedule_Event::MakeEvent($this->request->action_date, $this->request->date_effective, $amounts, 'lend_pay_fee_ach', $trans->event_name . ' Payment for Grace Period Arrangement', 'scheduled', 'manual', $trans->transaction_register_id, -$trans->transaction_register_id);

						// Save the event
						Record_Event($this->request->application_id, $e);
					}
				}
			}


			// I should be putting this all in the request, but I'm not. [benb]
			foreach($analysis->fail_set as $bunce)
			{
				// We don't want to consider reattempts
				if (is_int($bunce->origin_id))
					continue;

				// We only want debits
				if ($bunce->principal > 0 || $bunce->fee > 0 || $bunce->service_charge > 0 || $bunce->irrecoverable > 0)
					continue;

				// Assume it needs a reatt
				$need_reatt = TRUE;

				// Now check to see if a successful reattempt of this transaction has already been made.
				foreach($analysis->debits as $debit)
				{
					// if the origin id of the debit matches the ID of the failure, there's already been a reatt,
					// also make sure it completed or is scheduled
					if ($debit->origin_id == $bunce->origin_group_id && $debit->status == 'complete')
					{
						$need_reatt = FALSE; // We don't need a reattempt
						break;
					}
				}

				// Add it to the list of transactions to reattempt with this
				if ($need_reatt == TRUE)
				{
					$amounts   = array();
					// Make the event
					$amounts[] = Event_Amount::MakeEventAmount('service_charge', $bunce->service_charge);
					$amounts[] = Event_Amount::MakeEventAmount('principal',      $bunce->principal);
					$amounts[] = Event_Amount::MakeEventAmount('fee',            $bunce->fee);

					$e = Schedule_Event::MakeEvent($this->request->action_date, $this->request->date_effective, $amounts, $bunce->event_name_short, $bunce->event_name . ' for Grace Period Arrangement', 'scheduled', 'manual', $bunce->transaction_register_id, -$bunce->transaction_register_id);

					// Save the event
					Record_Event($this->request->application_id, $e);
				}
			}


			$_SESSION['popup_display_list'] = array('overview', 'schedule', 'view');
			ECash::getTransport()->Set_Levels('close_pop_up');
			break;
		}
	}

	public function Add_Paydown($manual_ach = FALSE)
	{
		$application_id = $_SESSION['current_app']->application_id;

		switch($this->request->action_type)
		{
		case 'fetch':
			$data = Get_Transactional_Data($application_id);
			list($status, $schedule) = $this->ld->Fetch_Schedule_Data($application_id, false, false);
			$pd_calc = $this->Get_PDC();

			$today = date('m/d/Y');

			// This used to do some half assed checks before. I have modified it to use the next business day if today
			// is a holiday, a weekend, or the batch has closed already.
			if ($pd_calc->Is_Holiday($today) || $pd_calc->Is_Weekend($today) || Has_Batch_Closed($this->server->company_id))
			{
				$data->next_business_day = date('m/d/Y', strtotime($pd_calc->Get_Closest_Business_Day_Forward(date('m/d/Y'))));
			}
			else
			{
				$data->next_business_day = $today;
			}

			$data->amount = $data->amt;
			$data->application_id = $application_id;
			$rules = Prepare_Rules($data->rules, $data->info);
			$paydates = Get_Date_List($data->info,date('m/d/Y'),$rules,2,null,null);
			$data->next_due_date = date('m/d/Y', strtotime($paydates['effective'][0]));

			// CSO Integration
			if ($rules['loan_type_model'] == 'CSO')
			{
				$data->calendar_end = date('m/d/Y', strtotime($paydates['effective'][0]));

				// Check to see if the next business day is greater than the calendar end (the next paydate)
				if (strtotime($data->next_business_day) >  strtotime($paydates['effective'][0]))
				{
					// If so, make $data->next_business_day NULL
					$data->next_business_day = NULL;
				}
			}
			else
				$data->calendar_end = date('m/d/Y', strtotime($paydates['effective'][1]));

			// The weekly batch escalations annoyed me, so we're going to populate a maximum_amount here
			$balance_info = Fetch_Balance_Information($application_id);
			$data->maximum_payment = $balance_info->principal_pending;

			$data->schedule_status = $status;
			$data->has_pending = $data->schedule_status->num_pending_items > 0 ? TRUE : FALSE;
			ECash::getTransport()->Set_Data($data);
			if($manual_ach)
				ECash::getTransport()->Set_Levels('popup', 'manual_ach');
			else
				ECash::getTransport()->Set_Levels('popup', 'paydown');
			break;
		case 'save':
			if($manual_ach)
				$this->ld->Add_Manual_ACH($this->request);
			else
				$this->ld->Add_Paydown($this->request);
			$_SESSION['popup_display_list'] = array('overview', 'schedule', 'view');
			ECash::getTransport()->Set_Levels('close_pop_up');
			break;
		}
	}

	public function Add_Payment_Card_Payoff()
	{
		$application_id = $_SESSION['current_app']->application_id;
		$pd_calc = $this->Get_PDC(); // auto does holiday stuff

		switch ($this->request->action_type)
		{
		case 'fetch':
			list($status, $schedule) = $this->ld->Fetch_Schedule_Data($application_id,false,false);
			$data = Get_Transactional_Data($application_id);
			$rules = Prepare_Rules($data->rules, $data->info);
			$interest = Interest_Calculator::getInterestPaidPrincipalAndDate($schedule, FALSE, $rules);
			$balance_info = Fetch_Balance_Information($application_id);
			$data->fee = $balance_info->fee_pending;
			$data->svc_charge_type = $data->rules['service_charge']['svc_charge_type'];
			$data->service_charge_balance = $balance_info->service_charge_balance;
			$data->start_date = (strtotime($interest['date'])>strtotime($interest['first_failure_date']))?$interest['date']:$interest['first_failure_date'];
			$data->principal = $interest['principal'];
			$data->amt = $data->principal + $data->fee + $data->service_charge_balance;

			$today = date('m/d/Y');

			// This used to do some half assed checks before. I have modified it to use the next business day if today
			// is a holiday, a weekend, or the batch has closed already.
			if ($pd_calc->Is_Holiday($today) || $pd_calc->Is_Weekend($today) || Has_Batch_Closed($this->server->company_id))
			{
				$data->next_business_day = date('m/d/Y', strtotime($pd_calc->Get_Closest_Business_Day_Forward(date('m/d/Y'))));
			}
			else
			{
				$data->next_business_day = $today;
			}

			$data->application_id = $application_id;
			$paydates = Get_Date_List($data->info,date('m/d/Y'),$rules,2,null,null);
			$data->next_due_date = date('m/d/Y',strtotime($paydates['effective'][0]));


			// CSO Integration
			if ($rules['loan_type_model'] == 'CSO')
			{
				$data->calendar_end = date('m/d/Y', strtotime($paydates['effective'][0]));

				// Check to see if the next business day is greater than the calendar end (the next paydate)
				if (strtotime($data->next_business_day) >  strtotime($paydates['effective'][0]))
				{
					// If so, make $data->next_business_day NULL
					$data->next_business_day = NULL;
				}
			}
			else
				$data->calendar_end = date('m/d/Y', strtotime($paydates['effective'][1]));

			// The weekly batch escalations annoyed me, so we're going to populate a maximum_amount here
			$balance_info = Fetch_Balance_Information($application_id);
			$data->maximum_payment = $balance_info->principal_balance + $balance_info->service_charge_balance + $balance_info->fee_balance;

			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Set_Levels('popup', 'payment_card_payoff');
		break;

		case 'save':
			$_SESSION['popup_display_list'] = array('overview', 'schedule', 'view');
			$this->ld->Schedule_Payment_Card_Payoff($this->request);
			ECash::getTransport()->Set_Data($this->ld->Fetch_Loan_All($application_id), false);
			ECash::getTransport()->Add_Levels('overview', 'schedule','view');

			ECash::getTransport()->Set_Levels('close_pop_up');
		break;

		}
	}

	public function Schedule_Payout()
	{
		$application_id = $_SESSION['current_app']->application_id;
		$pd_calc = $this->Get_PDC(); // auto does holiday stuff

		switch ($this->request->action_type)
		{
		case 'fetch':
			list($status, $schedule) = $this->ld->Fetch_Schedule_Data($application_id,false,false);
			$data = Get_Transactional_Data($application_id);
			$rules = Prepare_Rules($data->rules, $data->info);
			$interest = Interest_Calculator::getInterestPaidPrincipalAndDate($schedule, FALSE, $rules);
			$balance_info = Fetch_Balance_Information($application_id);
			$data->fee = $balance_info->fee_pending;
			$data->svc_charge_type = $data->rules['service_charge']['svc_charge_type'];
			$data->service_charge_balance = $balance_info->service_charge_balance;
			$data->start_date = (strtotime($interest['date'])>strtotime($interest['first_failure_date']))?$interest['date']:$interest['first_failure_date'];
			$data->principal = $interest['principal'];
			$data->amt = $data->principal + $data->fee + $data->service_charge_balance;

			$today = date('m/d/Y');

			// This used to do some half assed checks before. I have modified it to use the next business day if today
			// is a holiday, a weekend, or the batch has closed already.
			if ($pd_calc->Is_Holiday($today) || $pd_calc->Is_Weekend($today) || Has_Batch_Closed($this->server->company_id))
			{
				$data->next_business_day = date('m/d/Y', strtotime($pd_calc->Get_Closest_Business_Day_Forward(date('m/d/Y'))));
			}
			else
			{
				$data->next_business_day = $today;
			}

			$data->application_id = $application_id;
			$paydates = Get_Date_List($data->info,date('m/d/Y'),$rules,2,null,null);
			$data->next_due_date = date('m/d/Y',strtotime($paydates['effective'][0]));


			// CSO Integration
			if ($rules['loan_type_model'] == 'CSO')
			{
				$data->calendar_end = date('m/d/Y', strtotime($paydates['effective'][0]));

				// Check to see if the next business day is greater than the calendar end (the next paydate)
				if (strtotime($data->next_business_day) >  strtotime($paydates['effective'][0]))
				{
					// If so, make $data->next_business_day NULL
					$data->next_business_day = NULL;
				}
			}
			else
				$data->calendar_end = date('m/d/Y', strtotime($paydates['effective'][1]));

			// The weekly batch escalations annoyed me, so we're going to populate a maximum_amount here
			$balance_info = Fetch_Balance_Information($application_id);
			$data->maximum_payment = $balance_info->principal_balance + $balance_info->service_charge_balance + $balance_info->fee_balance;

			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Set_Levels('popup', 'payout');
		break;

		case 'save':
			$_SESSION['popup_display_list'] = array('overview', 'schedule', 'view');
			$this->ld->Schedule_Payout($this->request);
			ECash::getTransport()->Set_Data($this->ld->Fetch_Loan_All($application_id), false);
			ECash::getTransport()->Add_Levels('overview', 'schedule','view');

			ECash::getTransport()->Set_Levels('close_pop_up');
		break;

		}
	}

	public function scheduleReattempt()
	{
		$application_id = $_SESSION['current_app']->application_id;
		$pd_calc = $this->Get_PDC(); // auto does holiday stuff
	
		switch ($this->request->action_type)
		{
			case 'fetch':
				list($status, $schedule) = $this->ld->Fetch_Schedule_Data($application_id,false,false);
				$data = Get_Transactional_Data($application_id);
				$rules = Prepare_Rules($data->rules, $data->info);
				$interest = Interest_Calculator::getInterestPaidPrincipalAndDate($schedule, FALSE, $rules);
				$balance_info = Fetch_Balance_Information($application_id);
				$data->fee = $balance_info->fee_pending;
				$data->svc_charge_type = $data->rules['service_charge']['svc_charge_type'];
				$data->service_charge_balance = $balance_info->service_charge_balance;
				$data->start_date = (strtotime($interest['date'])>strtotime($interest['first_failure_date']))?$interest['date']:$interest['first_failure_date'];
				$data->principal = $interest['principal'];
				$data->amt = $data->principal + $data->fee + $data->service_charge_balance;
			
				$today = date('m/d/Y');
				$next_business_day = date('m/d/Y', strtotime($pd_calc->Get_Next_Business_Day(date('m/d/Y'))));
				
				if ($pd_calc->Is_Holiday($today) || $pd_calc->Is_Weekend($today) || Has_Batch_Closed($this->server->company_id))
				{
					//$data->next_business_day = date('m/d/Y', strtotime($pd_calc->Get_Closest_Business_Day_Forward(date('m/d/Y'))));
					$data->next_business_day = $pd_calc->Get_Business_Days_Forward($next_business_day, 1);
				}
				else
				{
					//$data->next_business_day = $today;
					$data->next_business_day = $next_business_day;
				}
			
				$data->application_id = $application_id;
				$paydates = Get_Date_List($data->info,date('m/d/Y'),$rules,2,null,null);
				$data->next_due_date = date('m/d/Y',strtotime($paydates['effective'][0]));
		
		
				// CSO Integration
				if ($rules['loan_type_model'] == 'CSO')
				{
					$data->calendar_end = date('m/d/Y', strtotime($paydates['effective'][0]));
			
					// Check to see if the next business day is greater than the calendar end (the next paydate)
					if (strtotime($data->next_business_day) >  strtotime($paydates['effective'][0]))
					{
						// If so, make $data->next_business_day NULL
						$data->next_business_day = NULL;
					}
				}
				else
					$data->calendar_end = date('m/d/Y', strtotime($paydates['effective'][1]));
			
				// The weekly batch escalations annoyed me, so we're going to populate a maximum_amount here
				$balance_info = Fetch_Balance_Information($application_id);
				$data->maximum_payment = $balance_info->principal_balance + $balance_info->service_charge_balance + $balance_info->fee_balance;
			
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Set_Levels('popup', 'reattempt');
				break;
	
			case 'save':
				$_SESSION['popup_display_list'] = array('overview', 'schedule', 'view');
				
				//REATTEMPT
				Remove_Unregistered_Events_From_Schedule($application_id);
			
				$scheduled_date = !empty($this->request->scheduled_date) ? $this->request->scheduled_date : $this->request->edate;

				$schedule = Fetch_Schedule($application_id);
				$status = Analyze_Schedule($schedule,false);
			
				foreach($status->fail_set as $f) 
				{
					$ogid = -$f->transaction_register_id;
					Reattempt_Event_Manual($application_id, $f, $scheduled_date, $ogid);
				}
			
				//Complete_Schedule($application_id);
				///////////////////////////

				ECash::getTransport()->Set_Data($this->ld->Fetch_Loan_All($application_id), false);
				ECash::getTransport()->Add_Levels('overview', 'schedule','view');
			
				ECash::getTransport()->Set_Levels('close_pop_up');
				break;
		}
	}

	public function Refund()
	{
		$data = new stdClass();
		switch($this->request->action_type)
		{
		case 'fetch':
			list($status, $schedule) = $this->ld->Fetch_Schedule_Data($_SESSION['current_app']->application_id, false, false);
			$data->schedule_status = $status;
			$data->fund_amount = $_SESSION['current_app']->fund_amount;
			$data->transaction_history = Gather_App_Transactions($_SESSION['current_app']->application_id);
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Set_Levels('popup', 'refund');
			break;
		case 'save':
			$this->ld->Save_Refund($this->request);
			$_SESSION['popup_display_list'] = array('overview', 'schedule', 'view');
			ECash::getTransport()->Set_Levels('close_pop_up');
			break;
		}
	}

	public function RecoveryReversal()
	{
		$data = new stdClass();
		switch($this->request->action_type)
		{
			case 'fetch':
				ECash::getTransport()->Set_Levels('popup',  $this->request->action);
				break;
			case 'save':
				require_once(SQL_LIB_DIR . "comment.func.php");
				$app_id 	= $_SESSION['current_app']->application_id;
				$app = ECash::getApplicationById($app_id);
				$status = $app->getStatus();

				$this->ld->Save_RecoveryReversal($this->request,$app_id);

				if($status->level0_name === 'recovered')
				{
					$apps = Get_Other_Active_Loans(ECash::getMasterDb(), $app_id);
					if(0 != Get_Current_Balance($app_id))
					{
						Update_Status($this->server, $app_id,array("sent","external_collections","*root"));
					}

					if(1 == count($apps))
					{
						$new_app_id = $apps[0];

						$comments = ECash::getApplicationById($app_id)->getComments();
						$comments->add("Recovery Reversal Receieved", ECash::getAgent()->AgentId);

						$comments = ECash::getApplicationById($new_application_id)->getComments();
						$comments->add("Recovery Reversal on: $app_id", ECash::getAgent()->AgentId);

					}
				}
				/* mantis:4853 - written second time
				else
				{
					$this->ld->Save_RecoveryReversal($this->request);
				}
				*/

				$_SESSION['popup_display_list'] = array('overview', 'schedule', 'view');
				ECash::getTransport()->Set_Levels('close_pop_up');
				break;
		}
	}

	public function ChargeBack($action_name = NULL)
	{
		$app_id = $_SESSION['current_app']->application_id;
		$application = ECash::getApplicationbyId($app_id);
		$data = new stdClass();
		switch($this->request->action_type)
		{
			case 'fetch':
				list($status, $schedule) = $this->ld->Fetch_Schedule_Data($_SESSION['current_app']->application_id, false, false);
				$data->schedule_status = $status;
				$data->transaction_history = Gather_App_Transactions($_SESSION['current_app']->application_id);
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Set_Levels('popup', $this->request->action);
				break;

			case 'save':
				$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');

				$status_id = array(
					'active' => $asf->toId("active::servicing::customer::*root"),
					'qc_ready' => $asf->toId("ready::quickcheck::collections::customer::*root"),
					'inactive' => $asf->toId("paid::customer::*root"),
					'recovered' => $asf->toId("recovered::external_collections::*root"),
					'bankruptcy' => $asf->toId("verified::bankruptcy::collections::customer::*root"));

				$this->ld->Save_Chargeback($this->request, $app_id);

				$status = $application->getStatus()->toArray();
				$stat_id = $status['application_status_id'];

				// This function was originally in Save_Chargeback, but we need more control over
				// this particular check since it actually also resets the status id.
				if (empty($_SESSION['error_message']) && $stat_id != $status_id['bankruptcy'])
				{
					Check_Inactive($app_id);
					$stat_id = $application->getStatusId();
				}

				switch($action_name)
				{
					case "chargeback":
						$queue_manager = ECash::getFactory()->getQueueManager();
						$queue_item = new ECash_Queues_BasicQueueItem($app_id);
						$queue_manager->getQueueGroup('automated')->remove($queue_item);

						if (in_array($stat_id, array($status_id['active'],$status_id['qc_ready'])))
						{
							// Active	-> Collection Contact
							// QC Ready	-> Collection Contact
							// Increase balance for chargeback amount
							Update_Status($this->server, $app_id,array("queued","contact","collections","customer","*root"));
							Remove_Unregistered_Events_From_Schedule($app_id);
						}
						elseif (in_array($stat_id,array($status_id['inactive'],$status_id['recovered'])))
						{
							$apps = Get_Other_Active_Loans(ECash::getMasterDb(), $app_id);
							Update_Status($this->server, $app_id,array("queued","contact","collections","customer","*root"));
							Remove_Unregistered_Events_From_Schedule($app_id);

							if(count($apps) >= 1)
							{
								// Inactive Paid/Recovered  (New loan)	- > Collection Contact
								// Increase balance for chargeback amount
								// 1.  Add a note to the inactive loan: Chargeback received and posted to balance of open app id
								// 2.  Add chargeback amount to balance to the new loan.
								// 3.  Add a note to new loan: Chargeback on previous account.  Do not accept credit card/ debit card as payment
								// 4.  Create ability to link to the originating transaction in the inactive loan

								$comments = ECash::getApplicationById($app_id)->getComments();
								$comments->add("Chargeback received and posted to balance", ECash::getAgent()->AgentId);

								foreach ($apps as $new_app_id)
								{
									$comments = ECash::getApplicationById($new_app_id)->getComments();
									$comments->add("Chargeback on previous account $app_id.  Do not accept credit card / debit card as payment", ECash::getAgent()->AgentId);
								}
								//throw(new Exception("Applicant has several open applications, please choose one for chargeback usage"));
							}
						}
						else
						{
						/*
							Defaults For:
							// QC Returned 			-> QC Returned
							// 2nd Tier Pending		-> 2nd Tier Pending
							// 2nd Tier Sent		-> 2nd Tier Sent
							// Bankruptcy Verified	-> Bankruptcy Verified
							// Bankruptcy Notified	-> Bankruptcy Notified
							// (All Collections Statues)
							// QC Sent	-> QC Sent
							// Increase balance for chargeback amount
							// If QC is pending, then add amount to balance.
							// If check completes, then move to QC Ready and drop another check for the new balance.
							// If check fails ? fatal, move to QC Returned then 2nd tier pending (the entire new balance).
							// If check fails, non-fatal second check, move to QC Returned then 2nd tier pending (the entire new balance).
							// If check fails non-fatal first time, move to QC Returned then QC Ready and drop check for entire new balance.
							// Increase balance for chargeback amount
						*/
						}

						/**
						 * Will added this block for [#20289], here's the SQL for the Chargeback event:
						 *
						 * INSERT INTO cfe_event VALUES (NOW(), NOW(), NULL, "Chargeback", "CHARGEBACK");
						 */
						ECash::getApplicationById($app_id);
						$engine = ECash::getEngine();
						$engine->executeEvent('CHARGEBACK', array());
						break;

					case "chargeback_reversal":
						if(Get_Current_Balance($app_id) <= 0 && $stat_id != $status_id['bankruptcy'])
						{
//							Update_Status($this->server, $app_id,array("paid","customer","*root"));
//							Remove_Unregistered_Events_From_Schedule($app_id);
//
//							$queue_log = get_log("queues");
//							$queue_log->Write(__FILE__.":".'$Revision$'.":".__LINE__.":".__METHOD__."()",LOG_NOTICE);
//
//							//remove_from_automated_queues($app_id);
//							$queue_manager = ECash::getFactory()->getQueueManager();
//							$queue_item = new ECash_Queues_BasicQueueItem($app_id);
//							$queue_manager->getQueueGroup('automated')->remove($queue_item);
						}
						else
						{

//							$prev_status = Get_Previous_Status($app_id);
//							$statuses = Fetch_Status_Map();
//
//							$current_schedule  = Fetch_Schedule($app_id);
//							$schedule_status = Analyze_Schedule($current_schedule, TRUE);
//
//							if($chedule_status->has_arrangement || $status['level1'] == 'arrangements' )
//							{
//								$to_status = array("queued","contact","collections","customer","*root");
//								Update_Status($this->server, $app_id,$to_status);
//								$date_expiration = eCash_AgentAffiliation::getEndOfDayForward(1);
//								Follow_Up::createCollectionsFollowUp($app_id, date('Y-m-d H:i:s',(time()+60)),  $_SESSION['agent_id'], $this->server->company_id, "ChargeBack Reversal has Occured.", $date_expiration, 'broken_arrangements');
//
//
//							}
//							else
//							{
//
//								switch ($statuses[$prev_status]['chain'])
//								{
//
//									case 'active::servicing::customer::*root':
//									case 'approved::servicing::customer::*root':
//									case 'funding_failed::servicing::customer::*root':
//									case 'hold::servicing::customer::*root':
//									case 'past_due::servicing::customer::*root':
//										$to_status = explode('::',$statuses[$prev_status]['chain']);
//
//									break;
//									default:
//										$to_status = array("queued","contact","collections","customer","*root");
//									break;
//								}
//								Update_Status($this->server, $app_id,$to_status);
//							}
//
						}
						break;

					default:
						throw(new Exception("Unknown mode: $mode"));
				}

				$_SESSION['popup_display_list'] = array('overview', 'schedule', 'view');
				ECash::getTransport()->Set_Levels('close_pop_up');
				break;
		}
	}

	public function Cancel_Loan()
	{
		$this->ld->Cancel_Loan($_SESSION['current_app']->application_id);
		ECash::getTransport()->Set_Data($this->ld->Fetch_Loan_All($_SESSION['current_app']->application_id), false);
	}

	public function Reminder_Remove()
	{
		// GF 7553: Log this so there's reliable information on whether an agent explicitly hit "remove from queue"
		// or it was merely just pulled from the queue.
		$loan_action_id = Get_Loan_Action_ID('reminder_queue_removal');
		$app = ECash::getApplicationById($this->request->application_id);
		$app_status = $app->getStatus();

		Insert_Loan_Action($this->request->application_id, $loan_action_id, $app_status->getApplicationStatus(), $this->server->agent_id);

		// Also make an agent action
		$agent = ECash::getAgent();
		$agent->getTracking()->add('reminder_queue_removal', $this->request->application_id);
		// This does nothing but remove the queue entry, which keeps it from removing it that day
		$qm = ECash::getFactory()->getQueueManager();
		$qm->getQueue('pd_reminder_queue')->remove(new ECash_Queues_BasicQueueItem($this->request->application_id));
		$qm->getQueue('at_reminder_queue')->remove(new ECash_Queues_BasicQueueItem($this->request->application_id));
		$fresh_app = $this->ld->Fetch_Loan_All($this->request->application_id);
		ECash::getTransport()->Set_Data($fresh_app);
		ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
	}

	public function Add_Hold()
	{
		$data = new stdClass();
		$data->application_id = $this->request->application_id;

		switch($this->request->action_type)
		{
		case 'fetch': // Display the pop up
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Set_Levels('popup', 'place_in_hold_status');
			break;
		case 'save': // Save the posted data from the pop up
			$this->ld->Hold_Status($this->request->hold_type);
			$this->Add_Comment();
			ECash::getTransport()->Set_Levels('close_pop_up');
			break;
		}
	}

	public function Remove_Hold()
	{
		$data = new stdClass();
		$data->application_id = $this->request->application_id;
		switch($this->request->action_type)
		{
		case 'fetch':
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Set_Levels('popup', 'return_from_service_hold');
			break;
		case 'save':
			$this->ld->Hold_Status($this->request->hold_type);
			$this->Add_Comment();
			ECash::getTransport()->Set_Levels('close_pop_up');
			break;

		}
	}

	public function Place_In_Amortization()
	{
		$application_id = $this->request->application_id;
		$agent_id = Fetch_Current_Agent();
		$company_id = ECash::getCompany()->company_id;
		$comment = 'Customer placed in Amortization';

		Remove_Unregistered_Events_From_Schedule($application_id);

		$biz_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());
		$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->server->company, 'offline_processing');
		$rule_set_id = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
		$rules = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

		// Add a 90 day follow_up time for the action queue
		$this->log->Write("Starting with: " . date('Y-m-d H:i:s', strtotime(date('Y-m-d'))));
		$this->log->Write("Start Period: " . $rules['amortization_start_period']);
		$interval = Follow_Up::Add_Time(strtotime(date('Y-m-d')), $rules['amortization_start_period'], 'day');
		$this->log->Write("Add_Time() Result: $interval");

		Update_Status(NULL, $application_id, 'amortization::bankruptcy::collections::customer::*root');

		Follow_Up::Create_Follow_Up($application_id, 'amortization_start', $interval, $agent_id, $company_id, $comment, NULL, FALSE);

		$data = $this->ld->Fetch_Loan_All($application_id);
		ECash::getTransport()->Set_Data($data);
		ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
	}

	public function Dissolve_Amortization()
	{
		$application_id = $this->request->application_id;
		$agent_id = Fetch_Current_Agent();
		$company_id = ECash::getCompany()->company_id;

		Add_Comment($company_id, $application_id, $agent_id, 'Agent dissolved Amortization for customer');

		Follow_Up::Expire_Follow_Ups($application_id);

		$queue_manager = ECash::getFactory()->getQueueManager();
		$queue_item = new ECash_Queues_BasicQueueItem($application_id);
		$queue_manager->getQueueGroup('automated')->remove($queue_item);

		Update_Status($this->server, $application_id, array("queued","contact","collections","customer","*root"));

		$data = $this->ld->Fetch_Loan_All($application_id);
		ECash::getTransport()->Set_Data($data);
		ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
	}

}
?>
