<?php

require_once(SERVER_CODE_DIR . "base_module.class.php");
require_once(SERVER_CODE_DIR . "application_fraud_query.class.php");
require_once(ECASH_COMMON_DIR . 'Fraud/FraudCheck.php');
require_once(ECASH_COMMON_DIR . 'Fraud/FraudRule.php');
require_once("libolution/Mail/Trendex.1.php");
require_once(SQL_LIB_DIR . "customer_contact.class.php");
require_once(LIB_DIR . "business_rules.class.php");
require_once(SQL_LIB_DIR . "fetch_status_map.func.php");


/** Fraud rules were commissioned to be global across CLK companies.
 *  To facilitate this and still allow for fraud to be split later
 *  on, we'll do the following:
 *
 * Put all fraud business rules under UFC (simply because they're
 * the first eCash 3.0 customer).  If fraud rules do become
 * company-specific this process will need to be changed to not
 * examine just UFC business rules, but ones for that company.  JRF
 *
 */
class Fraud extends Base_Module
{
	// TODO: combine these constants with the identical ones in cronjobs/fraud_reminder.php
	//for retrieving business rules
	const RULE_COMPONENT_NAME_SHORT = 'fraud_settings';
	const EMAIL_NEW_FRAUD = "New Fraud Rule Email";
	const EMAIL_NEW_PROP = "New Fraud Proposition Email";
	const COMPANY_SUPPORT_EMAIL = "COMPANY_SUPPORT_EMAIL";
	const ECASH_ADDRESS = "ECASH_ADDRESS";

	protected $queue;

	private $fraud_query;
	private $rule_type;
	private $customer_contact;
	private $settings;
	private $net_effect = array('fraud_apps_removed' => array(),
								'fraud_apps_added' => array(),
								'fraud_apps_confirmed' => array(),
								'fraud_apps_unconfirmed' => array(),
								'risk_apps_removed' => array(),
								'risk_apps_added' => array(),
								'preview' => TRUE,
								'confirm' => FALSE,
								'unconfirm' => FALSE
								);

	public function __construct(Server $server, $request = NULL, $mode = NULL, Module_Interface $module = NULL)
	{		
		parent::__construct($server, $request, $mode, $module);
		$this->module_name = 'fraud';
		$this->fraud_query = new Application_Fraud_Query($server);
		$this->customer_contact = new Customer_Contact(ECash::getMasterDb());
		if($mode == 'fraud_rules')
		{
			$this->rule_type = FraudRule::RULE_TYPE_FRAUD;
		}
		else
		{
			$this->rule_type = FraudRule::RULE_TYPE_RISK;
		}
	}

// 	public function Get_Queue_Count()
// 	{
// 		$queues = array(
// 			"Fraud",
// 			"High Risk"
// 			);
// 		$counts = array();
//
// 		foreach($queues as $queue_name)
// 		{
// 			$counts[$queue_name] = count_queue($queue_name, FALSE);
// 		}
// 		return $counts;
// 	}

	public function Change_Status()
	{
		//print_r($this->request); exit;

		$loan_data = new Loan_Data($this->server);
		$application_id = $this->request->application_id;

		$action_result = NULL;
		$set_data = TRUE;

		switch($this->request->submit_button)
		{
			case "Release":
				// Loan Action
			   	if($this->mode == 'fraud_queue')
				{
					// Uses a radio button, so getting the first element of the array should be
					// safe.
					$loan_action_name = Get_Loan_Action_Name_Short($this->request->loan_actions[0]);

					// GF 
					if ($loan_action_name == 'confirmed_fraud')
					{
						$action_result = Update_Status($this->server, $this->request->application_id, array('confirmed','fraud','applicant','*root'));
					}
					else
					{
						//GF 27268 - If the app is determined not to be fraud, remove the flag
						$action_result = $loan_data->To_Verify_Queue($application_id); 
						$this->Remove_Fraud($application_id);
					}
				}
				else //high_risk_queue
				{
					//[#36588] QA says these should go to the verification queue as well
					$action_result = $loan_data->To_Verify_Queue($application_id);
				}
				ECash::getTransport()->Set_Levels('close_pop_up');
				break;

			case "Deny":
				// The rule from CLK is to send the Teletrack letter if the Teletrack
				// box is checked, even if other boxes are checked too. If the Teletrack
				// box is not checked, send the generic letter.

				// Loan Action
				$action_result = $loan_data->Deny($application_id);

				if ($action_result)
				{
					$document_type = 'DENIAL_LETTER_GENERIC';
					foreach ($this->request->loan_actions as $id)
					{
						$loan_action_name_short = Get_Loan_Action_Name_Short($id);
						if ($loan_action_name_short == 'ON-ALL-TeleTrack Failure')
						{
							$document_type = 'DENIAL_LETTER_TELETRACK';
						}
					}

					if(isset($this->request->document_list)) ECash_Documents_AutoEmail::Send($application_id, $document_type);
				}

				require_once(SQL_LIB_DIR."util.func.php");
				$queue_log = get_log("queues");
				$queue_log->Write(__FILE__.":".'$Revision$'.":".__LINE__.":".__METHOD__."()",LOG_NOTICE);

				$qm = ECash::getFactory()->getQueueManager();
				$qm->getQueueGroup('automated')->remove(new ECash_Queues_BasicQueueItem($application_id));
				$_SESSION['previous_module'] = 'fraud';
                $_SESSION['previous_mode'] = $this->mode;
				ECash::getTransport()->Set_Levels('close_pop_up');
				break;

			case "Withdraw":
				// Loan Action
				$action_result = $loan_data->Withdraw($application_id);
				$_SESSION['previous_module'] = 'fraud';
                $_SESSION['previous_mode'] = $this->mode;
				ECash::getTransport()->Set_Levels('close_pop_up');

				if ($action_result)
				{
					require_once(SQL_LIB_DIR."util.func.php");
					$queue_log = get_log("queues");
					$queue_log->Write(__FILE__.":".'$Revision$'.":".__LINE__.":".__METHOD__."()",LOG_NOTICE);

					$qm = ECash::getFactory()->getQueueManager();
					$qm->getQueueGroup('automated')->remove(new ECash_Queues_BasicQueueItem($application_id));

					// (EMAIL) Withdrawn Letter E-Sig=No
					if(isset($this->request->document_list)) ECash_Documents_AutoEmail::Send($application_id, 'WITHDRAWN_LETTER');
				}
				break;

			case "Reverify":
				// Comment
				$action_result = $loan_data->To_Verify_Queue($application_id);
				ECash::getTransport()->Set_Levels('close_pop_up');
				break;

			case "InProcess":
				// Comment
				$action_result = $loan_data->To_In_Process_Queue($application_id);
				ECash::getTransport()->Set_Levels('close_pop_up');
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

	public function Get_Rules(&$data)
	{
		//save the filters for the front-end
		$data->business_rules = $this->Load_Fraud_Business_Rules();
		$data->filter_active = isset($this->request->active) && is_numeric($this->request->active) ? $this->request->active : NULL;
		$data->filter_confirmed = isset($this->request->confirmed) && is_numeric($this->request->confirmed) ? $this->request->confirmed : NULL;
		//get the rules with the filters
		return $this->fraud_query->Get_Rules($this->rule_type,
											 $data->filter_active,
											 $data->filter_confirmed);
	}

	public function Load_Rule()
	{
		return $this->fraud_query->Get_Rule_And_Conditions($this->request->rule_id);
	}

	public function Save_Proposition()
	{
		//see if we have to save a file
		$file_name = '';
		$file_size = 0;
		$file_type = '';
		$attachment = NULL;

		//echo "<!-- ", print_r($_FILES, TRUE), " -->";

		$file_index = 'prop_file';

		if($_FILES[$file_index]['size'] > 0)
		{
			$file_name = $_FILES[$file_index]['name'];
			$tmp_name  = $_FILES[$file_index]['tmp_name'];
			$file_size = $_FILES[$file_index]['size'];
			$file_type = $_FILES[$file_index]['type'];

			$fp = fopen($tmp_name, 'r');
			$attachment = fread($fp, filesize($tmp_name));
			fclose($fp);
		}

		//save the record
		$prop_id = $this->fraud_query->Save_Proposition($this->request->rule_id,
														$this->request->question,
														$this->request->description,
														$this->request->quantify,
														$file_name, $file_size, $file_type, addslashes($attachment));

		$this->Load_Fraud_Business_Rules();

		$tokens = array('support_email' => $this->settings[self::COMPANY_SUPPORT_EMAIL],
						'ecash_address' => $this->settings[self::ECASH_ADDRESS],
						'prop_id' => $prop_id,
						'prop_question' => $this->request->question,
						'prop_description' => $this->request->description,
						'prop_quantify' => $this->request->quantify);

		$attachment_array = array();
		if($file_size)
		{
			$attachment_array[] = array(
				'method' => 'ATTACH',
				'filename' => $file_name,
				'mime_type' => $file_type,
				'file_data' => gzcompress($attachment),
				'file_data_size' => $file_size);
		}

		try
		{
			require_once(LIB_DIR . '/Mail.class.php');
			$recipient = $this->settings[self::EMAIL_NEW_PROP];
			eCash_Mail::sendMessage('ECASH_FRAUD_PROP_NEW', $recipient, $tokens, $attachment_array);
		}
		catch( Exception $e )
		{
			$this->log->Write(print_r($e, TRUE) . "Could not connect - email {$mail_id} not sent" , LOG_ERR);
		}

		return $prop_id;
	}

	/**
	 *
	 *
	 * @return int number of applications this change affected
	 *
	 */
	public function Save_Rule($preview = TRUE)
	{
		$this->net_effect['preview'] = $preview;

		if(!$preview) //get the preview rule to save
		{
			$rule = $this->Get_Preview();
		}
		else
		{
			if(!empty($this->request->rule_name))
			{
				//new rule
				$rule = $this->Get_Rule();
			}
			else
			{
				//update rule
				$rule = $this->Get_Rule($this->request->fraud_rule_id);
			}
		}

		if($rule->getFraudRuleID())
		{
			$this->Update_Rule($rule, $preview);
		}
		else
		{
			$this->New_Rule($rule, $preview);
		}

		//is it active or inactive?
		if($rule->getIsActive())
		{
			//get the current rule before updating
			$this->Rule_On($rule, $preview);
			//if this rule is going active, send an email with the rule's results
			if(!$preview)
				$this->Send_Rule_Mail($rule);
		}
		else //means this is an update -- no point in turning off a rule when it's a new inactive rule -- JRF
		{
			//Rule_Off?
			if($rule->getRuleType() == FraudRule::RULE_TYPE_FRAUD)
			{
				$this->Fraud_Rule_Off($rule, $preview);
			}
			else // FraudRule::RULE_TYPE_RISK
			{
				//preview rule off
				$this->Risk_Rule_Off($rule, $preview);
			}
		}

		return $this->net_effect;
	}

	public function Confirm_Fraud($preview = TRUE)
	{
		//echo "<!-- FRAUD CONFIRMED -->\n";
		$this->net_effect['preview'] = $preview;
		$this->net_effect['confirm'] = TRUE;

		if(!$preview) //get the preview rule to save
		{
			$rule = $this->Get_Preview();
		}
		else
		{
			$rule = $this->Get_Rule($this->request->fraud_rule_id);
		}

		if(!$preview)
			$this->fraud_query->Confirm_Rule($rule);

		//fraud, fraud_confirmed
		$apps = $this->fraud_query->Get_Queue_Apps(FraudRule::RULE_TYPE_FRAUD, $rule->getConditions());

		foreach($apps as $application)
		{
			//if not already confirmed
			if(!($application->status == 'confirmed' && $application->level1 == 'fraud'))
			{
				//echo '<!-- ', print_r($application, TRUE), ' -->';
				//set status to fraud_confirmed
				if(!$preview)
					$this->Update_Status($application->application_id, array('confirmed', 'fraud', 'applicant', '*root' ));
				$this->Update_Effect($application->display_short, 'fraud_apps_confirmed');
			}
		}
		return $this->net_effect;
	}

	public function Unconfirm_Fraud($preview = TRUE)
	{
		//echo "<!-- FRAUD UNCONFIRMED -->\n";
		$this->net_effect['preview'] = $preview;
		$this->net_effect['unconfirm'] = TRUE;

		if(!$preview) //get the preview rule to save
		{
			$rule = $this->Get_Preview();
		}
		else
		{
			$rule = $this->Get_Rule($this->request->fraud_rule_id);
		}

		if(!$preview)
			$this->fraud_query->Confirm_Rule($rule);

		//fraud, fraud_confirmed
		$apps = $this->fraud_query->Get_Queue_Apps(FraudRule::RULE_TYPE_FRAUD);

		$checker = new FraudCheck(ECash::getMasterDb(), FraudRule::RULE_TYPE_FRAUD, TRUE, $rule);

		foreach($apps as $application)
		{
			$results = $checker->processApplication($application);

			//echo '<!-- ', print_r($application, TRUE), ' -->';
			if(empty($results) && $application->status == 'confirmed' && $application->level1 == 'fraud')
			{
				//set status to just fraud rather than fraud_confirmed
				if(!$preview)
					$this->Update_Status($application->application_id, array('queued', 'fraud', 'applicant', '*root' ));
				$this->Update_Effect($application->display_short, 'fraud_apps_unconfirmed');
			}
		}
		return $this->net_effect;
	}

	public function Cancel_Preview()
	{
		unset($_SESSION['preview_rule']);
	}

	public function Check_Fraud(FraudCheck $checker, ECashApplication $application, $preview = FALSE, $fraud_rule_off = FALSE)
	{
		$results = $checker->processApplication($application);
		$status = NULL;
		
		/** Returns ECash_Application, which is different than ECashApplication! **/
		$ecash_application = ECash::getApplicationById($application->application_id);
		$current_status = $ecash_application->getStatus();

		if(empty($results[FraudRule::RULE_TYPE_FRAUD]))
		{

			$app_contact = $this->customer_contact->Get_Contact_Info($application->application_id);
			$need_to_remove = false;
			foreach ($app_contact as $record)
			{
				if($record->field_name == 'fraud')
				{
					$need_to_remove = true;
					break;
				}

			}
			if($need_to_remove)
			{
				if(!$preview)
				{

					//remove fraud flags

					$this->customer_contact->Remove_All_Of_Type($application->company_id, $application->application_id, 'fraud');
					$this->fraud_query->Remove_Rule_Type_From_App($application->application_id, FraudRule::RULE_TYPE_FRAUD);
					//if currently in the fraud queue, move to previous status before fraud, or comfirmed if no history
					if($application->level1 == 'fraud')
					{
						$status_history = $ecash_application->getStatusHistory();

						$history_array = array();
						foreach($status_history as $entry)
						{
							$history_array[] = $entry;
						}

						if(!empty($history_array))
						{
							$history_array = array_reverse($history_array);
						
							$prev_status = $history_array[1];
							$statuses = Fetch_Status_Map();
							$temp_status =explode('::',$statuses[$prev_status->application_status_id]['chain']);
							$i=2;
							// while previous status is in fraud
							while($temp_status[1] == 'fraud')
							{
								$temp_status =explode('::',$statuses[$history_array[$i]->application_status_id]['chain']);
								$i++;
							}

							$status = $temp_status;
						}
						else
						{
							$status = array('queued', 'verification', 'applicant', '*root' );
						}
						$queue_manager = ECash::getFactory()->getQueueManager();
						$queue_item = new ECash_Queues_BasicQueueItem($application->application_id);
						$queue_manager->getQueueGroup('automated')->remove($queue_item);
					}
				}
				$this->Update_Effect($application->display_short, 'fraud_apps_removed');
			}
		}
		else if($current_status->level2 === 'applicant' || $current_status->level1 === 'applicant')
		{				
			$rule_data = $this->Get_Confirmed_And_Columns($results[FraudRule::RULE_TYPE_FRAUD]);
			$fraud_confirmed = $rule_data['fraud_confirmed'];

			if(!$preview)
			{
				//remove fraud flags (delete all)
				$this->customer_contact->Remove_All_Of_Type($application->company_id, $application->application_id, 'fraud');
				$this->fraud_query->Remove_Rule_Type_From_App($application->application_id, FraudRule::RULE_TYPE_FRAUD);
				//and insert new fraud flags
				$this->customer_contact->Add_Many_Columns($application->company_id, $application->application_id, $rule_data['columns'], 'fraud', $this->server->agent_id);
				$this->fraud_query->Add_Rules_To_App($application->application_id, $this->Get_Rule_IDs($results[FraudRule::RULE_TYPE_FRAUD]));
				//those two ops should effectively update the (new) correct flags
			}

			//take out of fraud confirmed if there is none anymore
			if(!$fraud_confirmed && $application->level1 == 'fraud' && $application->status == 'confirmed')
			{
				//flag status to just fraud now
				$status = array('queued', 'fraud', 'applicant', '*root' );
				$this->Update_Effect($application->display_short, 'fraud_apps_added');
				$this->Update_Effect($application->display_short, 'fraud_apps_unconfirmed');
			}
			//add to fraud if not already
			else if($application->level1 != 'fraud')
			{
				//flag status to fraud queued or fraud confirmed
				if($fraud_confimed)
				{
					$status = array('confirmed', 'fraud', 'applicant', '*root' );
					$this->Update_Effect($application->display_short, 'fraud_apps_confirmed');
				}
				else
				{
					$status = array('queued', 'fraud', 'applicant', '*root' );
					$this->Update_Effect($application->display_short, 'fraud_apps_added');
				}
			}
		}

		//echo '<!-- ' , print_r($status, TRUE), ' -->';

		if(empty($results[FraudRule::RULE_TYPE_RISK]))
		{
			if(!$preview)
			{
				//remove high-risk flags
				$this->customer_contact->Remove_All_Of_Type($application->company_id, $application->application_id, 'high_risk');
				$this->fraud_query->Remove_Rule_Type_From_App($application->application_id, FraudRule::RULE_TYPE_RISK);
			}

			//if it was in the high risk queue and not flagged for fraud & not flagged for verification, flag move to underwriting
			if($application->level1 == 'high_risk' && (empty($status) || ($status[1] != 'fraud' && $status[1] != 'verification')))
			{

//				$prev_status = Get_Previous_Status($application->application_id);
//					if($prev_status)
//					{
//						$statuses = Fetch_Status_Map();
//						$status = explode('::',$statuses[$prev_status]['chain']);
//					}
//					else 
//					{
						//flag status to underwriting
						$status = array('queued', 'underwriting', 'applicant', '*root' );
		//			}
				$queue_manager = ECash::getFactory()->getQueueManager();
				$queue_item = new ECash_Queues_BasicQueueItem($application->application_id);
				$queue_manager->getQueueGroup('automated')->remove($queue_item);
				$this->Update_Effect($application->display_short, 'risk_apps_removed');
			}
		}
		//high-risk flags shouldn't have changed if we turned off a fraud rule
		else if(!$fraud_rule_off && ($current_status->level2 === 'applicant' || $current_status->level1 === 'applicant'))
		{
			//echo '<!-- checking for high risk rules -->';
			if(!$preview)
			{
				//echo "<!-- update risk flags: ", print_r($application, TRUE),  " -->\n";
				$rule_data = $this->Get_Confirmed_And_Columns($results[FraudRule::RULE_TYPE_RISK]);
				//remove risk flags (delete all) +
				$this->customer_contact->Remove_All_Of_Type($application->company_id, $application->application_id, 'high_risk');
				$this->fraud_query->Remove_Rule_Type_From_App($application->application_id, FraudRule::RULE_TYPE_RISK);
				//insert new risk flags
				$this->customer_contact->Add_Many_Columns($application->company_id, $application->application_id, $rule_data['columns'], 'high_risk', $this->server->agent_id);
				$this->fraud_query->Add_Rules_To_App($application->application_id, $this->Get_Rule_IDs($results[FraudRule::RULE_TYPE_RISK]));
				//those two ops should effectively update the (new) correct flags

				//if not in fraud and not in high_risk and not slated for fraud, move to high_risk
				if($application->level1 != 'fraud' && $application->level1 != 'high_risk' && (empty($status) || $status[1] != 'fraud')  )
				{
					//echo "<!-- updating to risk: -->\n";
					//set status to high_risk
					if(!$preview)
						$status = array('queued', 'high_risk', 'applicant', '*root' );
					$this->Update_Effect($application->display_short, 'risk_apps_added');
				}
			}
		}

		//set status
		//move to appropriate queue if necc.
		if(!empty($status) && !$preview)
		{
			$this->Update_Status($application->application_id, $status);
		}
	}

	private function Set_Preview(FraudRule $rule)
	{
		unset($_SESSION['preview_rule']);
		//serialize here so that we don't need to insure FraudRule is included before the session is loaded
		$_SESSION['preview_rule'] = serialize($rule);
	}

	private function Get_Preview()
	{
		return(unserialize($_SESSION['preview_rule']));
	}

	private function Get_Rule($rule_id = NULL)
	{
		if($rule_id) //update a rule
		{
			//get the rule
			$rule = $this->fraud_query->Get_Rule_And_Conditions($this->request->fraud_rule_id);
			//update it with proposed changes
			if($this->request->action == 'confirm')
			{
				$rule->setIsConfirmed(TRUE);
			}
			else if($this->request->action == 'unconfirm')
			{
				$rule->setIsConfirmed(FALSE);
			}
			else //regular update
			{
				$rule->setIsActive($this->request->active_status);
				$rule->setExpDate(strtotime($this->request->exp_date));
			}
		}
		else //new rule
		{
			$rule = new FraudRule(NULL, //new rule_id
								  NULL, //new timestamp
								  NULL, //new timestamp
								  $this->request->active_status,
								  strtotime($this->request->exp_date),
								  $this->rule_type,
								  0, //not confirmed
								  $this->request->rule_name,
								  $this->request->rule_notes);

			$numeric_fields = array('phone_home', 'phone_cell', 'phone_fax', 'ssn', 'zip_code', 'phone_work');

			for($i = 0; $i < $this->request->rule_count; $i++)
			{
				if(in_array($this->request->rule_field_set[$i], $numeric_fields))
					$field_value = preg_replace('/[^0-9]/', '', $this->request->rule_value_set[$i]);
				else
					$field_value = $this->request->rule_value_set[$i];

				$rule->addCondition(new FraudCondition($this->request->rule_field_set[$i],
													   $this->request->rule_compares_set[$i],
													   $field_value));
			}
		}
		$this->Set_Preview($rule);
		return $rule;
	}

	private function New_Rule(FraudRule $rule, $preview = TRUE)
	{
		//insert rule
		if(!$preview)
		{
			$rule_id = $this->fraud_query->Insert_Rule($rule);
			$rule->setFraudRuleID($rule_id);
		}
	}

	private function Update_Rule(FraudRule $rule, $preview = TRUE)
	{
		//update rule
		if(!$preview)
			$this->fraud_query->Update_Rule($rule);
	}

	private function Load_Fraud_Business_Rules()
	{
		if(empty($this->settings))
		{
			// TODO: combine the business rules lookup stuff here with cronjobs/fraud_reminder.php
			//send an email
			$business_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());
			$this->settings = $business_rules->Get_Rule_Set_Component_Parm_Values($this->server->company, self::RULE_COMPONENT_NAME_SHORT);
			$this->settings[self::ECASH_ADDRESS] = ECash::getConfig()->LOAD_BALANCED_DOMAIN;
			$this->settings[self::COMPANY_SUPPORT_EMAIL] = ECash::getConfig()->COMPANY_SUPPORT_EMAIL;
		}
		return $this->settings;
	}

	private function Rule_On(FraudRule $rule, $preview = TRUE)
	{
		//echo '<!-- RULE: ', print_r($rule, TRUE), ' -->';

		//for setting on the applications that match
		$rule_ids = array($rule->getFraudRuleID());

		//given only one rule, we can just select the offending apps
		$apps = $this->fraud_query->Get_Queue_Apps('ALL', $rule->getConditions());

		$columns = $rule->getColumns();

		foreach($apps as $application)
		{
			if($this->rule_type == FraudRule::RULE_TYPE_FRAUD)
			{
				//echo '<br> in it '.$application->level1 . ' ' . $application->status['level0'];
				if(!$preview)
				{
					//insert new flags (on dupe key update)
					$this->customer_contact->Add_Many_Columns($application->company_id, $application->application_id, $columns, 'fraud', $this->server->agent_id);
					$this->fraud_query->Add_Rules_To_App($application->application_id, $rule_ids);
				}
				
				//move to fraud
				//echo "<!-- mark as fraud: ", print_r($application, TRUE),  " -->\n";
				if($rule->IsConfirmed)
				{
					//set status to fraud::confirmed
					if(!$preview)
						$this->Update_Status($application->application_id, array('confirmed', 'fraud', 'applicant', '*root' ));
					$this->Update_Effect($application->display_short, 'fraud_apps_confirmed');
				}
				else 
				{
					//set status to fraud & move to fraud queue
					if(!$preview)
						$this->Update_Status($application->application_id, array('queued', 'fraud', 'applicant', '*root' ));
					$this->Update_Effect($application->display_short, 'fraud_apps_added');
				}
			}
			else //$rule_type == FraudRule::RULE_TYPE_RISK
			{
				if(!$preview)
				{
					//insert new flags (on dupe key update)
					$this->customer_contact->Add_Many_Columns($application->company_id, $application->application_id, $columns, 'high_risk', $this->server->agent_id);
					$this->fraud_query->Add_Rules_To_App($application->application_id, $rule_ids);
				}
				//if not in fraud or high_risk, move to high_risk
				if($application->level1 != 'fraud' && $application->level1 != 'high_risk')
				{
					//echo "<!-- updating to risk: -->\n";
					//set status to high_risk
					if(!$preview)
						$this->Update_Status($application->application_id, array('queued', 'high_risk', 'applicant', '*root' ));
					$this->Update_Effect($application->display_short, 'risk_apps_added');
				}
			}
		}
	}

	/** The XXXX_Rule_Off methods examine apps in fraud and high-risk,
	 *  to update their flags.  I couldn't figure out a good way to
	 *  combine the two at the time (like I did with Rule_On()).  If
	 *  you can, please do.
	 *
	 *  The Rule_Off methods get all of the apps in the 'fraud' or
	 *  'high_risk' status, and checks them against current fraud and
	 *  risk rules (after a fraud or risk rule has been deactivated).
	 *
	 *  This method may be able to be simplified by getting the
	 *  applications that match this rule (via the fraud_application
	 *  table) and re-checking rules on just those applications.
	 *
	 *  JRF
	 *
	 * @return int number of applications this change affected
	 */
	private function Fraud_Rule_Off(FraudRule $rule, $preview = TRUE)
	{
		//echo "<!-- FRAUD RULE OFF -->\n";
		$apps = $this->fraud_query->Get_Queue_Apps('ALL',$rule->getConditions());

		$checker = new FraudCheck(ECash::getMasterDb(), NULL, NULL, $rule);
//		echo '<pre>' . print_r($apps,true) .'</pre>';
		foreach($apps as $application)
		{
			//put this work in another method as it's the most
			//comprehensive check and we'll want it to be available
			//for when application edits are made
			$this->Check_Fraud($checker, $application, $preview, TRUE);
		}
	}

	private function Risk_Rule_Off(FraudRule $rule, $preview = TRUE)
	{
                
		$apps = $this->fraud_query->Get_Queue_Apps('ALL',$rule->getConditions());
		//echo '<!-- RISK APPS: ', print_r($apps, TRUE), ' -->';

		$checker = new FraudCheck(ECash::getMasterDb(), NULL, NULL, $rule);

		foreach($apps as $application)
		{

			$this->Check_Fraud($checker, $application, $preview, TRUE);

		}
	}

	private function Get_Confirmed_And_Columns($rules)
	{
		$fraud_confirmed = FALSE;
		$columns = array();
		foreach($rules as $rule)
		{
			//check for any fraud confirmed
			if($rule->IsConfirmed)
				$fraud_confirmed = TRUE;
			$fields = $rule->getColumns();
			foreach($fields as $field)
			{
				//just keep this a unique list of columns
				$columns[$field] = NULL;
			}
		}
		return array('fraud_confirmed' => $fraud_confirmed,
					 'columns' => array_keys($columns));
	}

	private function Get_Rule_IDs($rules)
	{
		$rule_ids = array();
		foreach($rules as $rule)
		{
			$rule_ids[] = $rule->getFraudRuleID();
		}
		return $rule_ids;
	}

	private function Update_Status($application_id, $status)
	{
		Update_Status($this->server, $application_id, $status);
	}

	private function Send_Rule_Mail(FraudRule $rule)
	{
		$this->Load_Fraud_Business_Rules();
		$type = ($rule->RuleType == FraudRule::RULE_TYPE_FRAUD ? 'Fraud' : 'High Risk');
		$mode = ($rule->RuleType == FraudRule::RULE_TYPE_FRAUD ? 'fraud_rules' : 'high_risk_rules');

		$tokens = array('support_email' => $this->settings[self::COMPANY_SUPPORT_EMAIL],
						'ecash_address' => $this->settings[self::ECASH_ADDRESS],
						'rules' => $rule->Name,
						'rule_type' => $type,
						'mode' => $mode,
						'summary' => $this->Get_Results_For_Email());

		try
		{
			require_once(LIB_DIR . '/Mail.class.php');
			$recipient = $this->settings[self::EMAIL_NEW_FRAUD];
			$response = eCash_Mail::sendMessage('ECASH_FRAUD_NEW', $recipient, $tokens);
		}
		catch( Exception $e )
		{
			$this->log->Write(print_r($e, TRUE) . "Could not connect - email {$mail_id} not sent" , LOG_ERR);
		}
	}

	private function Get_Results_For_Email()
	{
		$fraud_apps_label = "Applications moved to Fraud queue:";
		$risk_apps_label = "Applications moved to High Risk queue:";

		//pick the longest label for formatting
		$padding = strlen($risk_apps_label) + 1;
		//left justify the label, right justify the number
		$format = "%-{$padding}s%6d";

		$results = "Results:\n";
		foreach($this->net_effect as $index => $company_array)
		{
			if(is_array($company_array))
			{
				$title_set = FALSE;
				foreach($company_array as $display_short => $count)
				{
					if($index == 'fraud_apps_added')
						$label = $fraud_apps_label;
					if($index == 'risk_apps_added')
						$label = $risk_apps_label;

					if($title_set)
					{
						$label = '';
					}
					else
					{
						$title_set = TRUE;
					}

					$results .= sprintf($format, $label, $count) . " from {$display_short}\n";
				}
			}
		}

		return $results;
	}

	private function Update_Effect($display_short, $index)
	{
		//echo "<!-- updating {$index} for {$display_short} +1 -->\n";
		if(empty($this->net_effect[$index][$display_short]))
		{
			$this->net_effect[$index][$display_short] = 1;
		}
		else
		{
			$this->net_effect[$index][$display_short]++;
		}
	}

	public function Add_Watch()
	{
		$ld = new Loan_Data($this->server);
		$data = new stdClass();
		$data->application_id = $this->request->application_id;
		switch($this->request->action_type)
		{
		case 'fetch': // Display the pop up
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Set_Levels('popup', 'add_watch_status');
			break;
		case 'save': // Save the posted data from the pop up
			$this->Change_Watch_Status($this->request->application_id,'yes');
			$this->Add_Comment();
		//	Add_Comment($this->server->company_id, $this->request->application_id, $this->server->agent_id,
		//				"Added Watch Flag", $this->request->comment_type, $this->server->system_id);
			ECash::getTransport()->Set_Data($ld->Fetch_Loan_All($this->request->application_id));
			ECash::getTransport()->Add_Levels('overview','personal','view','general_info','view');
			ECash::getTransport()->Set_Levels('close_pop_up');
			break;
		}
	}

	public function Remove_Watch()
	{
		$ld = new Loan_Data($this->server);
		$data = new stdClass();
		$data->application_id = $this->request->application_id;
		switch($this->request->action_type)
		{
		case 'fetch':
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Set_Levels('popup', 'remove_watch_status');
			break;
		case 'save':
			$this->Change_Watch_Status($this->request->application_id, 'no');
			$this->Add_Comment();

			if(!is_null($this->request->application_id))
			{
				$qm = ECash::getFactory()->getQueueManager();
				$qm->getQueue('watch')->remove(new ECash_Queues_BasicQueueItem($this->request->application_id));
			}

			ECash::getTransport()->Set_Data($ld->Fetch_Loan_All($this->request->application_id));
			ECash::getTransport()->Add_Levels('overview','personal','view','general_info','view');
			ECash::getTransport()->Set_Levels('close_pop_up');
			break;
		}
	}

	// Changes the state of the watch flag and expires or creates new agent affiliations
	private function Change_Watch_Status ($application_id, $state)
	{
		$agent_id = $_REQUEST["agent_id"];
		// If the application_id isn't passed as a parameter, grab it from the session

		$data = Get_Transactional_Data($application_id);
		$rules = $data->rules;
		$app = ECash::getApplicationById($application_id);
		$affiliations = $app->getAffiliations();
		switch($state)
		{
			case "yes":
				// If the watch status is no, turn it on, expire any current affiliations,
				// then add a new watcher affiliation
				Set_Watch_Status_Flag($application_id, 'yes');
				Remove_Standby($application_id);
				$affiliations->expireAll();
				// Need to use the business rule to set the expiration period
				$normalizer= new Date_Normalizer_1(new Date_BankHolidays_1());
				$date_expiration = $normalizer->advanceBusinessDays(time(), $rules['watch_period'] + 1);
			
				$affiliations->add(ECash::getAgentById($agent_id), 'watch', 'owner', $date_expiration);
				ECash::getAgentById($agent_id)->getQueue()->insertApplication($app, 'other', $date_expiration, time());
				// If they're in the arrangements statuses, we don't want to remove any scheduled events
				if(($_SESSION['current_app']->status != 'arrangements') || ($_SESSION['current_app']->level1 != 'arrangements'))
				{
					ECash::getLog()->Write("Rescheduling - deleting schedule for App ID {$application_id} (Agent ${agent_id} enabled watch flag).", LOG_INFO);
					Remove_Unregistered_Events_From_Schedule($application_id);
				}
				break;
			case "no":
				// If the watch status is yes, turn it off, then expire the current affiliation.
				Set_Watch_Status_Flag($application_id, 'no');
				$affiliations->expire('watch', 'owner');
			
				// If they're in the arrangements statuses, we don't want to do anything to their existing
				// schedule.
				if(($_SESSION['current_app']->status != 'arrangements') || ($_SESSION['current_app']->level1 != 'arrangements'))
				{
					// This should reschedule the loan for us.  (If an existing one doesn't exist, we'll
					// see some errors, but that shouldn't be a problem with real-world accounts.
					Complete_Schedule($application_id);
					ECash::getLog()->Write("Rebuilding Schedule - recreating schedule for App ID {$application_id} (Agent ${agent_id} disabled watch flag).", LOG_INFO);
				}
				break;
		}
	}
	
	public function Remove_Fraud($application_id)
	{
		$fraud = ECash::getFactory()->getModel('FraudApplication', ECash::getMasterDb());
		$fraud->loadBy(array("application_id"=>$application_id));
		$fraud->delete();
		
		$company_id    = ECash::getFactory()->getData('Application')->getCompanyId($application_id);
		$contact_flags = ECash::getFactory()->getData('Application')->clearContactFlagsByRow('application', $company_id, $application_id);	
	}
}
?>
