<?php
require_once(SQL_LIB_DIR . "loan_actions.func.php");
require_once(SERVER_CODE_DIR . "base_module.class.php");
require_once(SQL_LIB_DIR . "customer_contact.func.php");

class Funding extends Base_Module
{

	protected $queue;

	public function __construct(Server $server, $request, $mode, Module_Interface $module = NULL)
	{
		parent::__construct($server, $request, $mode, $module);
		$this->module_name = 'funding';
	}

	/**
	 * Updates Status & handles some Funding...
	 *
	 * @todo Fix hard-coded values for testing Agean
	 */
	public function Change_Status($action = null)
	{

		$action = ($action) ? $action : $this->request->submit_button;
		$loan_data = new Loan_Data($this->server);
		$application_id = $this->request->application_id;

		$action_result = NULL;
		$set_data = TRUE;

		switch($action)
		{
			case 'Decline':
				$action_result = $loan_data->decline($application_id);
				$id = $this->request->loan_actions;
				$loan_action_name_short = Get_Loan_Action_Name_Short($id);
				if ($action_result)
				{
					$document_type = $loan_action_name_short;
					ECash_Documents_AutoEmail::Send($application_id, $document_type);
				}

				ECash::getTransport()->Set_Levels('close_pop_up');
			break;


			case "Approve":
				// Loan Action
				$action_result = $loan_data->To_Underwriting_Queue($application_id);
				ECash::getTransport()->Set_Levels('close_pop_up');
				break;

			case "Additional":
				$action_result = $loan_data->To_Addl_Verify_Queue($application_id);
                                // display the application 
				ECash::getTransport()->Set_Data($loan_data->Fetch_Loan_All($application_id));
				ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
				break;
			
			case "Hotfile":
				$action_result = $loan_data->To_Hotfile_Queue($application_id);
				ECash::getTransport()->Set_Data($loan_data->Fetch_Loan_All($application_id));
				ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
				break;
				
			case "Fund":
			case "Fund_Check":
			case "Fund_Moneygram":
			case "Fund_Paydown":
			case "Fund_Payout":
				
				// If they're not marked Do Not Loan, and they don't have other active applications, go ahead.
				$customer = ECash::getCustomerByApplicationId($application_id);
				$dnl = $customer->getDoNotLoan();

				if(Check_For_Application_Modifications($application_id))
				{
					$fund = FALSE;
					$_SESSION['error_message'] = 'WARNING! This account has been modified!  Your changes will not be submitted.';
				}
				else if ($dnl->get())
				{
					$fund = FALSE;
					$_SESSION['error_message'] = 'WARNING! This account is marked DO NOT LOAN.  Your changes will not be submitted.';
				} 
				else if ($this->Check_For_Other_Active_Loans($application_id)) 
				{
					$fund = FALSE;
					$_SESSION['error_message'] = 'WARNING! This account has other active loans.  Your changes will not be submitted.';
				} 
				else 
				{
					$results = $this->verifyFunding($application_id);
					switch($results['status'])
					{
						case 'CONFIRMED':
							$fund = TRUE;
							break;
						case 'DECLINED':
							$fund = FALSE;
							break;
						case 'UNAVAILABLE':
							//@TODO: Should build something in here to control the behavior when unavailable
							$fund = FALSE;
							break;
					}
					$this->request->comment = $results['comment'];
				}

				if($fund === TRUE)
				{
					try
					{
						$action_result = $loan_data->Fund($application_id,
										  $this->request->submit_button,
										  $this->request->funding,
										  $this->request->payment
						);
						if($action_result)
						{
							//write the fund date and amount if funding
							$this->Update_Application();
						}

						if (!empty($this->request->investor_group))
						{
							require_once(SQL_LIB_DIR.'tagging.lib.php');
							Remove_Application_Tags($application_id);
							Tag_Application($application_id, $this->request->investor_group);
						}
					}
					catch(Exception $e)
					{
						ECash::getLog()->Write("Exception: Error funding application {$application_id}  " . $e->getMessage());
						ECash::getLog()->Write($e->getTraceAsString());
						$_SESSION['error_message'] = 'WARNING! There was an error funding this account!\n' . $_SESSION['error_message'];
						throw $e;
					
					}
					
					// This loan is getting funded so we should not have any followups. [rlopez][mantis:8058]
					Follow_Up::Expire_Follow_Ups($application_id);

					ECash::getTransport()->Set_Data($loan_data->Fetch_Loan_All($application_id));
					ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
				}
				else // Do Not Fund is set or they have other active loans
				{
					$data = new stdClass();
					ECash::getLog()->write("Agent tried to fund " . $application_id . " - [Do Not Loan: " . ($do_not_loan ? 'true' : 'false') . "] [Active Account: " . ($has_active_loans ? 'true' : 'false') ."] [Existing Events: " . ($has_scheduled_events ? 'true' : 'false') . "]", LOG_WARNING);
					ECash::getTransport()->Set_Data($loan_data->Fetch_Loan_All($application_id));
					ECash::getTransport()->Add_Levels('overview','personal','view','general_info','view');
				}

				break;

			//GForge [#20937]
			case "Deny":
				$action_result = $this->Deny();
				break;

			case "Withdraw":
				$action_result = $this->Withdraw();
				break;
			//end GForge [#20937]				

			case "Reverify":
				// Comment
				$action_result = $loan_data->To_Verify_Queue($application_id);
				ECash::getTransport()->Set_Levels('close_pop_up');
				break;

			case "In_Process":
				// Comment
				$action_result = $loan_data->To_In_Process_Queue($application_id);
				ECash::getTransport()->Set_Data($loan_data->Fetch_Loan_All($application_id));
				ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
				break;

			case "Cashline Duplicate":
				$action_result = $loan_data->Cashline_Dup($application_id);
				ECash::getTransport()->Set_Data($loan_data->Fetch_Loan_All($application_id));
				ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
				break;

		}

		if (!$action_result)
		{
			//this fixes the 911 from Feb. 3, 2005
			//thx for finding it George -- JRF

			//nothing happened, but we may have to set the data
			if($set_data)
			{
				//ECash::getTransport()->Add_Levels('overview');
				ECash::getTransport()->Set_Data($_SESSION['current_app']);
			}
			return FALSE;
		}

		$comm_ref = null;
 		if(! empty($this->request->loan_actions))
			$comm_ref  = $this->Add_Loan_Action($set_data);
		if(! empty($this->request->comment))
			$this->Add_Comment($comm_ref);

	}

	public function Update_Application()
	{
		require_once(SERVER_CODE_DIR . "edit.class.php");
		$edit = new Edit($this->server, $this->request);
		$edit->Save_Application(TRUE);
	}

	public function Send_Docs()
	{
		if(!empty($this->request->document_list))
		{
			// HACKED -- MarcC (7/13/05)
			if (!is_array($this->request->document_list))
			{
				$docs = eCash_Document::Get_Document_List($this->server,"all", "AND active_status = 'active'");
				foreach ($docs as $doc)
				{
					if ($doc->description == $this->request->document_list)
					{
						$this->request->document_list =
						array($doc->document_list_id => $doc->description);
						break;
					}
				}
			}

         ECash::getTransport()->Set_Data($_SESSION['current_app']);
		ECash::getTransport()->Set_Levels('close_pop_up');
		}
	}

	public function Check_For_Other_Active_Loans($application_id, $ssn = null)
	{
			$app = ECash::getApplicationById($application_id);
			// If there's no SSN supplied, lets find it.
		if(($ssn === null) || ($ssn == ''))
		{

			$ssn = $app->ssn;
		}

		$customer = ECash::getFactory()->getCustomerBySSN($ssn, $app->company_id);

		$applications = $customer->getApplications();
		$status_filter = array('prospect','applicant','paid','funding failed','recovered','settled','refi');
		foreach($applications as $app)
		{
			if($app->application_id != $application_id)
			{			
				$status = $app->getStatus();
				if($status)
				{
					if($status->level0 == 'refi')
					{
						$status_name = 'refi';
					}
					elseif($status->level1 == 'external_collections' && $status->level0 == 'recovered')
					{
						$status_name = 'Recovered';	
					}
					elseif($status->level1 ==  'external_collections' || $status->level2 == 'collections')
					{
						$status_name = 'Collections';
					}
					elseif($status->level2 == 'customer')
					{
						$status_name = 'Customer';
					}			
					elseif($status->level2 == 'applicant' || $status->level1 == 'applicant')
					{
						$status_name = 'Applicant';
					}
					elseif($status->level1 == 'prospect')
					{
						$status_name = 'Prospect';
					}
					elseif($status->level1 == 'cashline')
					{
						$status_name = 'Customer';
					}	
					else
					{
						$status_name = $status->level0;
					}
				}
				else
				{
					$status_name = '';
				}
				// Only allow the Prospect and Applicant trees, along with the Paid Customer, refi, 
				// and Funding Failed leaf statuses.
				if(!in_array(strtolower($status_name),$status_filter))
				{
					return true;
				}
			}

	
		}

		return false;
	}

	public function Search_Dequeue()
	{
		//[#34840] Don't dequeue if business rule is set to 'No'
		//Get dequeue business rule
		$business_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());
		$dequeue_settings = $business_rules->Get_Rule_Set_Component_Parm_Values($this->server->company, 'search_dequeue');

		//default to TRUE if not set
		$should_dequeue = isset($dequeue_settings['search_dequeue_funding']) && $dequeue_settings['search_dequeue_funding'] == 'No' ?
			FALSE : TRUE;

		if($should_dequeue)
		{		
			$app = ECash::getTransport()->Get_Data();
			
			$queue_name = $this->mode . (($app->is_react === 'yes' || $app->is_react === TRUE) ? '_react' : '');
			$queue_manager = ECash::getFactory()->getQueueManager();
			if($queue_manager->hasQueue($queue_name))
			{
				$queue = $queue_manager->getQueue($queue_name);
				$queue_item = new ECash_Queues_BasicQueueItem($app->application_id);
	
				// are we in this mode's queue?
				if ($queue->contains($queue_item))
				{
					// run the pull thing -- this hits the pulled stat, if applicable
					//pull_from_automated_queue($this->server, $queue_name, $app->application_id);
					/**
					 * @todo: special stats treatment here?
					 */
					//Logic from old queues and new queues has changed actually removing from the queue would be bad
					//$queue->remove($queue_item);
					$queue->dequeue($queue_item->RelatedId);
				}
			}
		}
	}

	public function Check_For_Loan_Conditions()
	{
		require_once(SQL_LIB_DIR . "scheduling.func.php");

		$data = ECash::getTransport()->Get_Data();

		if(isset($data->application_id))
		{
			$application_id = $data->application_id;
		}
		else
		{
			throw new Exception ("No application_id in transport object!");
		}

		$balance = Fetch_Balance_Information($application_id);

		$new_data = new stdClass();
		if($data->do_not_loan)
		{
			$new_data->fund_warning = "Application is marked DO NOT LOAN!";
		}
		else if($this->Check_For_Other_Active_Loans($application_id))
		{
			$new_data->fund_warning = "Found other active loans for this company! &nbsp;&nbsp; Please review the Application History.";
		}
		//Only adding the fund warning if it has a pending principal amount AND it has a fund amount/fund event as well
		else if (is_object($balance) && $balance->principal_pending != 0 && $data->schedule_status->initial_principal)
		{
			$new_data->fund_warning = "Application has a principal balance!";
		}

		ECash::getTransport()->Set_Data($new_data);
	}
	
	/**
	 * Executes funding verification for the supplied application_id
	 *
	 * @param integer $application_id
	 */
	public function verifyFunding($application_id)
	{
		$app = ECash::getFactory()->getModel('Application');
		$app->loadBy(array('application_id' => $application_id));
		//Get verification_type business rule
		$business_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());
		$rule_set_id = $business_rules->Get_Rule_Set_Id_For_Application($application_id);
		$rule_set = $business_rules->Get_Rule_Set_Tree($rule_set_id);
		
		/**
		 * If the funding_verification rule is set, use it and return
		 * the result.  If it isn't, just reutrn a successful value.
		 */
		if(isset($rule_set['funding_verification']))
		{
			$type = $rule_set['funding_verification'];
			
			//Instantiate verification class
			$class_file = strtolower($type) . ".class.php";
	 
			require_once(LIB_DIR . $class_file);
			$verifier = new $type($app);
			//Call verification with necessary parameters
			$results = $verifier->runVerification();
			
			//Return the verification outcome
			return $results;
		}
		else
		{
			/**
			 * These are the generic values to send back if there is no verifier.
			 * 
			 * The 'Confirmed and Approved' comment is the generic response which 
			 * Impact is used to seeing so it's being set here as it was the old
			 * default.  [GForge #15144]
			 */
			return array('status'  => 'CONFIRMED',
						 'comment' => 'Confirmed and Approved');
		}
	}
}

?>
