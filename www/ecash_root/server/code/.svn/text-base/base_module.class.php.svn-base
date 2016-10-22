<?

class Base_Module
{
	protected $server;
	protected $log;
	protected $transport;
	protected $mode;
	protected $request;
	protected $queue_config;
	
	public function __construct(Server $server, $request, $mode, Module_Interface $module_interface = null)
	{
		$this->server = $server;
		$this->log = ECash::getLog();
		$this->mode = $mode;
		$this->request = ECash::getRequest();
		$this->setupQueues($module_interface);
	}

	public function Get_Next_Queued_Collections_Item()
	{
		$data = ECash::getTransport()->Get_Data();

		$item        = NULL;
		$use_old_app = FALSE;

		// Fake out the collections queues to give back the same app if they do not select
		// any call dispositions
		if (in_array("lock_collections_queues_until_called", $data->read_only_fields) && isset($_SESSION['last_queued_app']))
		{
			$qm         = ECash::getFactory()->getQueueManager();
			$queues     = $qm->getQueuesByType('Queues_CollectionsQueue');

			$cur_app    = ECash::getApplicationById($_SESSION['last_queued_app']);
		
			$use_old_app = TRUE;

			if ($cur_app)
			{
				foreach($queues as $queue)
				{
					// Check if the last application is in a collections queue
					if ($queue->entryExists(new ECash_Queues_BasicQueueItem($cur_app->application_id)))
					{
						// Get the entry
						$item = new ECash_Queues_BasicQueueItem($cur_app->application_id);

						// Now we want to find the last dequeue time, which apparently is only available in the
						// n_queue_history table. So we check the last n_queue_history entry for this application
						// id, and we check that timestamp against the loan actions to see if an acceptable
						// call disposition was entered since it was last pulled.
						$dequeue_time = $item->getLastDequeueTime();

						// If it is, check to see if there was a disposition entered for this by this agent
						$lah_l = ECash::getFactory()->getModel('LoanActionHistoryList');
						$lah_l->loadBy(array('application_id' => $cur_app->application_id));

						// Go through the loan action history, check for a call disposition by the current
						// agent. We're looking for a loan action AFTER the date_modified on the queue entry
						foreach ($lah_l as $lah)
						{
							// This loan action occurred after the last time the queue entry was modified
							if (strtotime($lah->date_created) > strtotime($dequeue_time))
							{
								$las = ECash::getFactory()->getModel('LoanActionSection');

								if ($las->loadBy(array('loan_action_section_id' => $lah->loan_action_section_id)))
								{
									if (strpos($las->name_short, "CALL") != FALSE)
									{
										$use_old_app = FALSE;
										break;
									}
								}
							}
						}
					}
				}
			}
		}

		if ($use_old_app == TRUE)
			return $item;
		else
			return NULL;
	}

	/**
	 * [#55353] Add generic call disposition locking mechanism for all
	 * queues.  Similar to above Get_Next_Queued_Collections_Item() but
	 * requires "lock_{$queue_name_short}_queue_until_called"
	 * control option and can work on any individual queue.
	 */
	public function Get_Next_Queued_Item($queue_name_short)
	{
		$data = ECash::getTransport()->Get_Data();

		$item        = NULL;
		$use_old_app = FALSE;

		// Fake out the collections queues to give back the same app if they do not select
		// any call dispositions
		if (in_array("lock_{$queue_name_short}_queue_until_called", $data->read_only_fields) && isset($_SESSION["last_queued_app_{$queue_name_short}"]))
		{
			$qm         = ECash::getFactory()->getQueueManager();

			$cur_app    = ECash::getApplicationById($_SESSION["last_queued_app_{$queue_name_short}"]);
		
			$use_old_app = TRUE;

			if ($cur_app)
			{
				//search all queues, b/c sometimes CFE rules moves stuff immediately upon dequeue
				$entries = $qm->findInAllQueues(new ECash_Queues_BasicQueueItem($cur_app->application_id));

				if(count($entries))
				{
					$entry = NULL;
					//get the first item that's not in the Agent's 'My Queue'
					foreach($entries as $queue => $qmodel)
					{
						if($queue != 'Agent')
						{
							$entry = $qmodel;
							break;
						}
					}
					
					// Check if we found the last application in a queue
					if ($entry)
					{					
						// Get the item
						$item = new ECash_Queues_BasicQueueItem($cur_app->application_id);

						// Now we want to find the last dequeue time, which apparently is only available in the
						// n_queue_history table. So we check the last n_queue_history entry for this application
						// id, and we check that timestamp against the loan actions to see if an acceptable
						// call disposition was entered since it was last pulled.
						$dequeue_time = $item->getLastDequeueTime();

						// If it is, check to see if there was a disposition entered for this by this agent
						$lah_l = ECash::getFactory()->getModel('LoanActionHistoryList');
						$lah_l->loadBy(array('application_id' => $cur_app->application_id));

						// Go through the loan action history, check for a call disposition by the current
						// agent. We're looking for a loan action AFTER the date_modified on the queue entry
						foreach ($lah_l as $lah)
						{
							// This loan action occurred after the last time the queue entry was modified
							if (strtotime($lah->date_created) > strtotime($dequeue_time))
							{
								$las = ECash::getFactory()->getModel('LoanActionSection');

								if ($las->loadBy(array('loan_action_section_id' => $lah->loan_action_section_id)))
								{
									if (strpos($las->name_short, "CALL") != FALSE)
									{
										$use_old_app = FALSE;
										break;
									}
								}
							}
						}
					}
				}
			}
		}

		if ($use_old_app == TRUE)
			return $item;
		else
			return NULL;
	}

	// Attempt to bring this back to the module level
	public function Get_Next_Application()
	{
		$item = $this->Get_Next_Queued_Item($this->request->queue);

		$search = new Search($this->server, $this->request);
		$queue_manager = ECash::getFactory()->getQueueManager();
		$queue = $queue_manager->getQueue($this->request->queue);

        // Only dequeue when there's an acceptable call disposition
		if ($item == NULL)
		{
			$item = $queue->dequeue();
			$_SESSION["last_queued_app_{$this->request->queue}"] = $item->RelatedId;
		}

		$related_id = $item->RelatedId;

 		if ($item != NULL)
		{
			// Agent Tracking
			$agent = ECash::getAgent();
			$agent->getTracking()->add($this->request->queue, $related_id);

			ECash::getApplicationById($related_id);
			$engine = ECash::getEngine();

			$engine->executeEvent('DEQUEUED', array('Queue' => $this->request->queue));
			
			$search->Show_Applicant($related_id);
		
			$data = ECash::getTransport()->Get_Data();
			/*			
			if($this->request->queue == 'follow_up')
                        {
                                //cannot use because of mysql and ecash time difference
                                //$follow_ups = Follow_Up::Get_Uncompleted_Follow_Ups_For_Application($related_id);
                                $follow_ups = Follow_Up::Get_Follow_Ups_For_Application($related_id);
                                if (count($follow_ups))
                                {
                                        $data = new stdClass();
                                        //$data->javascript_on_load = "alert('The application has an uncompleted follow-up')";
                                        $data->javascript_on_load = "if(confirm('The follow-up time for this application has been exceeded. Would you like to clear the follow-up now? FYI: You can clear the follow-up anytime from the follow-up info window that can be opened by clicking on the F icon.')) window.open('/?action=get_followup_info&application_id={$related_id}', 'followup_info', 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,copyhistory=no,width=450,height=200,left=150,top=150,screenX=150,screenY=150');";
                                        ECash::getTransport()->Set_Data($data);
                                }
                        }
			*/
			if($this->request->queue == 'account_summary')
			{

				$payments = Fetch_API_Payments($related_id);
				if (count($payments)) 
				{
					$payment = array_shift($payments);

					switch ($payment->event_type) 
					{
						case 'payout':
							$_SESSION['api_payment'] = $payment;
							$data = new stdClass();
							$data->javascript_on_load = 'VerifyPayout();';
							ECash::getTransport()->Set_Data($data);
							break;
						case 'paydown':
							$_SESSION['api_payment'] = $payment;
							$data = new stdClass();
							$data->javascript_on_load = "if(confirm('Would you like to add a paydown to this application?')) OpenTransactionPopup('paydown', 'Add Paydown', 'customer_service');";
							ECash::getTransport()->Set_Data($data);
							break;
					}
				}
				
			}
			
			if(!empty($data->fraud_rules) || !empty($data->risk_rules)) //show idv pane if they're high risk/fraud
			{
				ECash::getTransport()->Add_Levels('overview','idv','view','general_info','view');
			}
			else if (isset($this->module_name) && ($this->module_name == 'collections'))
			{
				ECash::getTransport()->Add_Levels('overview','personal','view','general_info','view');
			}
			else
			{
				ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
			}
			

		}
		else
		{
			if ($GLOBALS['queue_result_message'])
			{
				$duh = new stdClass;
				$duh->search_message = $GLOBALS['queue_result_message'];
				ECash::getTransport()->Set_Data($duh);
			}
			$search->Get_Last_Search($this->module_name, $this->mode);
		}
	}

	// Originally from Funding/Loan_Servicing
	public function Add_Loan_Action($set_data = TRUE)
	{
		if ($set_data)
		{
			$loan_data = new Loan_Data($this->server);
			ECash::getTransport()->Set_Data($loan_data->Fetch_Loan_All($this->request->application_id, !$set_data));

			if($this->mode != 'verification')
			{
				ECash::getTransport()->Add_Levels('overview','application','view','general_info','view');
			}
			else
			{
				ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
			}

			// Grabbing the last Status_History ID
			$application = ECash::getApplicationByID($this->request->application_id);
			$agent_id = ECash::getAgent()->getAgentId();
			$status_id = $application->application_status_id;

			// Insert each of the loan actions
			if($this->request->loan_actions)
			{
				$loan_action_list = ECash::getFactory()->getReferenceList('LoanActions');
				$stats = new ECash_Stats();

				if(is_array($this->request->loan_actions))
				{

					for($i=0; $i<count($this->request->loan_actions); $i++)
					{
						$loan_item = $this->request->loan_actions[$i];

						$lah = ECash::getFactory()->getModel('LoanActionHistory');
						$lah->loan_action_id = $loan_item;
						$lah->application_id = $application->application_id;
						$lah->application_status_id = $application->application_status_id;
						$lah->agent_id = $agent_id;
						$lah->date_created = date('Y-m-d H:i:s');
						$lah->save();
						
						$loan_history_id = $lah->loan_action_history_id;

						$stats->hitStatLoanAction($application,$loan_action_list->toName($loan_item));
					}
				}
				else
				{
					$loan_item = $this->request->loan_actions;
					$lah = ECash::getFactory()->getModel('LoanActionHistory');
					$lah->loan_action_id = $loan_item;
					$lah->application_id = $application->application_id;
					$lah->application_status_id = $application->application_status_id;
					$lah->agent_id = $agent_id;
					$lah->date_created = date('Y-m-d H:i:s');
					$lah->save();
					
					$loan_history_id = $lah->loan_action_history_id;

					$stats->hitStatLoanAction($application,$loan_action_list->toName($loan_item));
				}
	
				// TO DO - make a nice wrapper for the following
				// Select Other for Loan Disposition so lets email it
				if(isset($this->request->comment))
				{
					$header = array(
					'sender_name' => 'Selling Source',
					'subject'     => '[eCash 3.5] - Other Loan Action',
					'site_name'   => 'sellingsource.com',
					'message'     => "\r\n<br>Mode: " . EXECUTION_MODE . " \r\n" .
						"<br>New Other Action: '{$this->request->comment}'\r\n" .
						"<br>Agent: {$this->server->agent_id} \r\n" .
						"<br>Application: {$this->request->application_id} \r\n" .
						"<br> App Status: {$status_id} \r\n" .
						"<br>Section(Button): {$this->request->submit_button} \r\n" .
						"<br>Company: {$this->server->company}");
					if (EXECUTION_MODE == 'LIVE')
					{
						$recipients = array(
						array(
							'email_primary_name' => 'Natalie',
							'email_primary' => 'ndempsey@fc500.com'),
						array(
							'email_primary_name' => 'Crystal',
							'email_primary' => 'crystal@fc500.com'));
					}
					else
					{
						$recipients = array(
						array(
							'email_primary_name' => 'Programmer',
							'email_primary' => 'rebel75cell@gmail.com, brian.gillingham@gmail.com, randy.klepetko@sbcglobal.net'));
					}

					require_once(LIB_DIR . '/Mail.class.php');
					foreach ($recipients as $recipient)
					{
						$tokens = array_merge($recipient, $header);
						$recipient_email = $tokens['email_primary'];
						// Disabled for now - BR
						//eCash_Mail::sendMessage('ECASH_COMMENT', $recipient_email, $tokens);
					}
				}
			}
		}

		return  $loan_history_id;
	}

	public function Add_Comment($comment_reference = null)
	{
		// Requiring the file here because the functions are only used here.
		require_once(SERVER_CODE_DIR . "comment.class.php");

		$comment = isset($this->request->new_comment) ? trim($this->request->new_comment) : trim($this->request->comment);

		if(isset($comment) && $comment != '')
		{
			// Set the type of comment
			//   REQUIRED
			switch( $this->request->action )
			{
				case 'add_follow_up':
					$this->request->comment_type = 'followup';
					break;
				case 'change_status':
					switch( $this->request->submit_button )
					{
						case 'Deny':
							$this->request->comment_type = 'deny';
							break;
						case 'Withdraw':
							$this->request->comment_type = 'withdraw';
							break;
						case 'Reverify':
							$this->request->comment_type = 'reverify';
							break;
						default:
							$this->request->comment_type = 'standard';
							break;
					}
					break;
						case 'add_comment':
						case 'add_comment_new':
						default:
							$this->request->comment_type = 'standard';
							break;
			}

			$resolved = (isset($this->request->comment_flag) || isset($this->request->comment)) ? TRUE : FALSE;

			/* GF #21266
			 * Commented this line out, otherwise every single comment added via a loan action was going to
			 * be set to 'row.' 
			 */
			//$this->request->comment_type = is_null($comment_reference) ? $this->request->comment_type : "row";

			$comments = ECash::getApplicationById($this->request->application_id)->getComments();
			/* GF #21266
             * The add() method was not being passed the $comment_reference id. I suspect Bunce did it.
			 */
			$comments->add($comment, ECash::getAgent()->AgentId, $this->request->comment_type, ECash_Application_Comments::SOURCE_LOAN_AGENT, $comment_reference, $resolved);
		}

		$loan_data = new Loan_Data($this->server);
		ECash::getTransport()->Set_Data($loan_data->Fetch_Loan_All($this->request->application_id));

		if($this->mode == 'underwriting')
		{
			ECash::getTransport()->Add_Levels('overview','application_info','view','general_info','view');
		}
		else
		{
			ECash::getTransport()->Add_Levels('overview','personal','view','general_info','view');
		}
	}

	/**
	 * GForge [#20937]
	 * 
	 * Moved the Deny loan action here so it can occur on any screen
	 */
	public function Deny()
	{
		// The rule from CLK is to send the Teletrack letter if the Teletrack
		// box is checked, even if other boxes are checked too. If the Teletrack
		// box is not checked, send the generic letter.

		// Loan Action
		$loan_data = new Loan_Data($this->server);
		$action_result = $loan_data->Deny($this->request->application_id);

		if ($action_result)
		{
			$document_type = 'DENIAL_LETTER_GENERIC';
			foreach ($this->request->loan_actions as $id)
			{
				$loan_action_name_short = strtoupper(Get_Loan_Action_Name_Short($id));
				switch ($loan_action_name_short)
				{
					case 'ON-ALL-TELETRACK FAILURE':
					case 'ON-TELETRACK FAILURE':
						$document_type = 'DENIAL_LETTER_TELETRACK';
						break;
							
					case 'DATAX_PERFORM_FAIL':
					case 'D_ON_DATAX_FAIL':
						$document_type = 'DENIAL_LETTER_DATAX';
						break;
							
					case 'D_ON_CREDIT_BUREAU_FAIL':
						$document_type = 'DENIAL_LETTER_CREDIT_BUREAU';
						break;
							
					case 'D_ON_CL_VERIFY':
						$document_type = 'DENIAL_LETTER_CL_VERIFY';
						break;
							
					case 'D_ON_VERITRAC':
						$document_type = 'DENIAL_LETTER_VERITRAC';
						break;
							
					case 'D_MILITARY':
						$document_type = 'DENIAL_LETTER_MILITARY';
						break;
				}
			}
			if(isset($this->request->document_list)) ECash_Documents_AutoEmail::Send($this->request->application_id, $document_type);
		}

		$queue_manager = ECash::getFactory()->getQueueManager();
		$queue_manager->getQueueGroup('automated')->remove(new ECash_Queues_BasicQueueItem($this->request->application_id));

		// This loan is getting denied so we should not have any followups. [rlopez][mantis:8239]
		$fup = new Follow_Up();
		$fup->Expire_Follow_Ups($this->request->application_id);

		ECash::getTransport()->Set_Levels('close_pop_up');
		return $action_result;
	}

	/**
	 * GForge [#20937]
	 * 
	 * Moved the Withdraw loan action here so it can occur on any screen
	 */
	public function Withdraw()
	{
		// Loan Action
		$loan_data = new Loan_Data($this->server);		
		$action_result = $loan_data->Withdraw($this->request->application_id);
		ECash::getTransport()->Set_Levels('close_pop_up');

		if ($action_result)
		{
			$queue_manager = ECash::getFactory()->getQueueManager();
			$queue_manager->getQueueGroup('automated')->remove(new ECash_Queues_BasicQueueItem($this->request->application_id));

			// (EMAIL) Withdrawn Letter E-Sig=No
			if(isset($this->request->document_list)) ECash_Documents_AutoEmail::Send($this->request->application_id, 'WITHDRAWN_LETTER');
		}
		// This loan is getting withdrawn so we should not have any followups. [rlopez][mantis:8239]
		$fup = new Follow_Up();
		$fup->Expire_Follow_Ups($this->request->application_id);
		return $action_result;
	}

	/**
	 * [#32997] moved from collections so it's available for other modules
	 */ 
	public function Deceased_Notification($application_id)
	{
		$status_map = Fetch_Status_Map(FALSE);

		$deceased_status = Search_Status_Map('unverified::deceased::collections::customer::*root', $status_map);

		// Set it as a deceased unverified status
		Update_Status(NULL, $application_id, $deceased_status, NULL, NULL, FALSE);

		$loan_data = new Loan_Data($this->server);
		$data = $loan_data->Fetch_Loan_All($application_id);
		ECash::getTransport()->Set_Data($data);
		ECash::getTransport()->Add_Levels('overview','personal','view','general_info','view');	
	}

	public function Deceased_Verification($application_id)
	{
		$status_map = Fetch_Status_Map(FALSE);

		$deceased_status = Search_Status_Map('verified::deceased::collections::customer::*root', $status_map);

		// Set it as a deceased unverified status
		Update_Status(NULL, $application_id, $deceased_status, NULL, NULL, FALSE);

		$loan_data = new Loan_Data($this->server);
		$data = $loan_data->Fetch_Loan_All($application_id);
		ECash::getTransport()->Set_Data($data);
		ECash::getTransport()->Add_Levels('overview','personal','view','general_info','view');	
	}
	
	public function setupQueues($module_interface = null)
	{
		$acl = ECash::getACL();
		$queue_manager = ECash::getFactory()->getQueueManager();
		$module = ECash::getModule()->Get_Active_Module();
		$section_names = $acl->Get_Acl_Access($module);
		$allowed_submenus = $acl->Get_Acl_Names($section_names);
		$available_queues = array();
		$queues = $queue_manager->getQueuesBySectionId($acl->Get_Section_Id(ECash::getCompany()->company_id, $module, $this->mode));
				
		foreach($queues as $queue_name => $queue)
		{
			$section_model = ECash::getFactory()->getReferenceModel('Section');
			$section_model->loadBy(array('section_id' => $queue->getModel()->section_id));
			if ($acl->Acl_Access_Ok($section_model->name, ECash::getCompany()->company_id))
			{
				$mode_section_id = $acl->Get_Section_Id(ECash::getCompany()->company_id, $module, $this->mode);
				$qp = array();
				$qp['name_short'] = $queue->Model->name_short;
				$qp['display_name'] = $queue->Model->display_name; 
				$qp['count'] = $queue->count();
				list($module, $mode) = $acl->getModuleAndMode($queue->Model->section_id);
				$qp['link_module'] = $module;
				$qp['link_mode'] = $mode;
				$available_queues[$queue_name] = $qp;
			}
		}
		ECash::getTransport()->available_queues = $available_queues;

		// Email Queue Count moved to the module code so that it can get the real numbers in the queues
		
		$eq = new Incoming_Email_Queue($this->server, $this->request);
		
		if ($module_interface !== NULL)
		{
			$module_interface->Register_Action_Handler($eq, 'handle_actions');	
		}
		
		if(is_object(ECash::getAgent()))
		{
			ECash::getTransport()->my_queue_count = ECash::getAgent()->getQueue()->count();
		}

		//Follow Up Queue
		$queue = $queue_manager->getQueue("follow_up");
		$count = $queue->count();
		ECash::getTransport()->followup_queue_count = $count;
	}

	/**
	 * Returns the next ACH Safe Action Date
	 * Safe, meaning this won't conflict with sent batches or
	 * weekends and holidays.  This is used for the 
	 * @return string
	 */
	public function getNextSafeAchActionDate()
	{
		$calc = new Date_Normalizer_1(new Date_BankHolidays_1());
		if(Has_Batch_Closed())
		{
			$action_date = $calc->seekBusinessDays(time(), 1);
		}
		else
		{
			$action_date = strtotime(date('Y-m-d'));
		}

		return date('M j Y H:i:s', $action_date);
	}

	public function getNextSafeAchDueDate()
	{
		$calc = new Date_Normalizer_1(new Date_BankHolidays_1());
		if(Has_Batch_Closed())
		{
			$due_date = $calc->seekBusinessDays(time(), 2);
		}
		else
		{
			$due_date = $calc->seekBusinessDays(time(), 1);
		}

		return date('M j Y H:i:s', $due_date);
	}
	
}

?>
