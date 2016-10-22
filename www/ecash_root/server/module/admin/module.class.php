<?php

require_once(SERVER_CODE_DIR.'module_interface.iface.php');
require_once(SERVER_MODULE_DIR.'/admin/profiles.class.php');
require_once(SERVER_MODULE_DIR.'/admin/privs.class.php');
require_once(SERVER_MODULE_DIR.'/admin/groups.class.php');
require_once(SERVER_MODULE_DIR.'/admin/rules.class.php');
require_once(SERVER_MODULE_DIR.'/admin/blank.class.php');
require_once(SERVER_MODULE_DIR.'/admin/holidays.class.php');
require_once(SERVER_MODULE_DIR.'/admin/ach_providers.class.php'); //asm 80
require_once(SERVER_MODULE_DIR.'/admin/global_campaign_rules.class.php'); //asm 99
require_once(SERVER_MODULE_DIR.'/admin/dda.class.php');
require_once(SERVER_MODULE_DIR.'/admin/tags.class.php');
require_once(SERVER_MODULE_DIR.'/admin/queue_config.class.php');
require_once(SERVER_MODULE_DIR.'/admin/docs_config.class.php');
require_once(SERVER_MODULE_DIR.'/admin/nada_import.class.php');
require_once(SERVER_MODULE_DIR.'/admin/underwriting_rules.class.php');
require_once(SERVER_MODULE_DIR.'/admin/suppression_rules.class.php');
require_once(SERVER_MODULE_DIR.'/admin/intercept_card.class.php'); //mantis:12417

class Module implements Module_Interface
{
	private $action;
	private $profile_object;
	private $group_object;
	private $priv_object;
	private $rules_object;
	private $underwriting_rules_object;
	private $tags_object;
	private $queue_config_object;
	private $docs_config_object;
	private $nada_import_object;
	private $intercept_card_object;

	public function __construct(Server $server, $request, $module_name)
	{
		// This module doesn't use Module_Master so it doesn't have a parent scope.
		//parent::__construct($server, $request, $module_name);

		// Curious.  If the request isn't set to an array in this case, switching companies doesn't work in admin.
		if(isset($request->action) && $request->action === 'switch_company') $request = array();

		$this->blank_object = new Blank();
		$this->profile_object = new Profiles($server->agent_id, $server->transport, $request, ECash::getACL());
		$this->group_object = new Groups($server, $request);
		$this->priv_object = new Privs($server, $request);
		$this->file = $_FILES;
		$this->rules_object = new Rules($server, $request);
		$this->underwriting_rules_object = new Underwriting_Rule($server, $request);
		$this->suppression_rules_object = new Suppression_Rule($server, $request);
		$this->holiday_object = new Holidays($server, $request);
		$this->ach_provider_object = new AchProviders($server, $request); //asm 80
		$this->global_campaign_rule_object = new GlobalCampaignRules($server, $request); //asm 99
		$this->dda_object = new DDA($server, $request);
		$this->tags_object = new Tags($server, $request);
		$this->nada_import_object = new NADA_Import($server,$request);
		$this->queue_config_object = new Queue_Config($server, $request);
		$this->intercept_card_object = new Intercept_Card($server, $request);

		$this->request = $request;

		if (isset($request->mode) && (strlen(trim($request->mode)) > 0) && 
		    $request->mode != 'admin')
		{
			$mode = $request->mode;
			if (isset($request->action))
			{
				$this->action = $request->action;
			}
			else
			{
				$this->action = "display_{$mode}";
			}
		}
		elseif(isset($_SESSION['admin_mode']))
		{
			$mode = $_SESSION['admin_mode'];
			if(isset($_SESSION['admin_action']))
			{
				$this->action = $_SESSION['admin_action'];
			}
			else
			{
				$this->action = "display_{$mode}";
			}
		}
		else
		{
			$mode = "blank";
			$this->action = 'display_blank';
		}

		if (isset($request->action) && !isset($request->mode))
		{
			$this->action = $request->action;
			if (strpos($request->action, 'group') === false)
			{
				if ($request->action == 'modify_rules')
				{
					$mode = 'rules';
				}
				else if (strpos($request->action, 'priv') === false)
				{
					$mode = 'profiles';
				}
				else
				{
					$mode = 'privs';
				}
			}
			else
			{
				$mode = 'groups';
			}
		}

		if (isset($request->mode) && ($request->mode == '')) {
			$mode = 'blank';
		}

		
		$_SESSION['admin_mode'] = $mode;
		
		// We do not always want to store the last action (for instance, modify_rules)


		// the constructors above should probably be replaced here
		switch ($mode) {
			case "payment_types":
				require_once(SERVER_MODULE_DIR.'/admin/payment_types.class.php');
				$this->payment_types_object = new Payment_Types($server, $request);
				break;

			case "docs_config":
				$this->docs_config_object = new Docs_Config($server, $request);
				break;

			case "flag_type_config":
				require_once(SERVER_MODULE_DIR.'/admin/flag_types.class.php');
				$this->flag_type_object = new Flag_Type_Config($server, $request);
				break;
				
			case "transaction_type_config":
				require_once(SERVER_MODULE_DIR.'/admin/transaction_types.class.php');
				$this->transaction_type_object = new Transaction_Type_Config($server, $request);
				break;
				
			default:
				break;
		}
		ECash::getTransport()->Add_Levels($module_name, $mode);
	}

	public function Main()
	{
		if("display_dda" == $this->action)
		{
			$this->dda_object->Main();
		}
		else
		{
			switch($this->action)
			{
				// profiles
				case 'add_profile':
				$this->profile_object->Add_Profile();
				break;

				case 'modify_profile':
				$this->profile_object->Modify_Profile();
				break;

				// groups
				case 'add_groups':
				$this->group_object->Add_Groups();
				$this->group_object->Display();
				break;
				
				case 'copy_groups':
				$this->group_object->Copy_Group();
				$this->group_object->Display();
				break;

				case 'modify_groups':
				$this->group_object->Modify_Groups();
				break;

				case 'delete_groups':
				$this->group_object->Delete_Groups();
				break;

				// privs
				case 'add_privs':
				$this->priv_object->Add_Privs();
				break;

				case 'delete_privs':
				$this->priv_object->Delete_Privs();
				break;

				case 'display_privs':
				$this->priv_object->Display();
				break;

				case 'display_groups':
				$this->group_object->Display();
				break;

				case 'display_profiles':
				$this->profile_object->Display();
				break;

				case 'modify_rules':
				$this->rules_object->Modify_Rule();
				break;

				case 'display_rules':
				$this->rules_object->Display();
				break;

				case 'display_underwriting_rules':
				$this->underwriting_rules_object->Display();
				break;

				case 'insert_publisher':
				$this->underwriting_rules_object->Insert_Publisher();
				//$this->underwriting_rules_object->Display();
				break;

				case 'update_publisher':
				$this->underwriting_rules_object->Update_Publisher();
				//$this->underwriting_rules_object->Display();
				break;

				case 'delete_publisher':
				$this->underwriting_rules_object->Delete_Publisher();
				//$this->underwriting_rules_object->Display();
				break;

				case 'insert_campaign_group':
				$this->underwriting_rules_object->Insert_Group();
				//$this->underwriting_rules_object->Display();
				break;

				case 'update_campaign_group':
				$this->underwriting_rules_object->Update_Group();
				//$this->underwriting_rules_object->Display();
				break;

				case 'delete_campaign_group':
				$this->underwriting_rules_object->Delete_Group();
				//$this->underwriting_rules_object->Display();
				break;

				case 'insert_campaign':
				$this->underwriting_rules_object->Insert_Campaign();
				//$this->underwriting_rules_object->Display();
				break;

				case 'update_campaign':
				$this->underwriting_rules_object->Update_Campaign();
				//$this->underwriting_rules_object->Display();
				break;

				case 'delete_campaign':
				$this->underwriting_rules_object->Delete_Campaign();
				//$this->underwriting_rules_object->Display();
				break;

				case 'insert_provider':
				$this->underwriting_rules_object->Insert_Provider();
				//$this->underwriting_rules_object->Display();
				break;

				case 'update_provider':
				$this->underwriting_rules_object->Update_Provider();
				//$this->underwriting_rules_object->Display();
				break;

				case 'delete_provider':
				$this->underwriting_rules_object->Delete_Provider();
				//$this->underwriting_rules_object->Display();
				break;

				case 'insert_uw_store':
				$this->underwriting_rules_object->Insert_Store();
				//$this->underwriting_rules_object->Display();
				break;

				case 'update_uw_store':
				$this->underwriting_rules_object->Update_Store();
				//$this->underwriting_rules_object->Display();
				break;

				case 'delete_uw_store':
				$this->underwriting_rules_object->Delete_Store();
				//$this->underwriting_rules_object->Display();
				break;

				case 'insert_inquiry':
				$this->underwriting_rules_object->Insert_Inquiry();
				//$this->underwriting_rules_object->Display();
				break;

				case 'update_inquiry':
				$this->underwriting_rules_object->Update_Inquiry();
				//$this->underwriting_rules_object->Display();
				break;

				case 'delete_inquiry':
				$this->underwriting_rules_object->Delete_Inquiry();
				//$this->underwriting_rules_object->Display();
				break;

				case 'insert_campaign_inquiry':
				$this->underwriting_rules_object->Set_Campaign_Inquiry();
				//$this->underwriting_rules_object->Display();
				break;

				case 'update_campaign_inquiry':
				$this->underwriting_rules_object->Set_Campaign_Inquiry();
				//$this->underwriting_rules_object->Display();
				break;

				case 'delete_campaign_inquiry':
				$this->underwriting_rules_object->Delete_Campaign_Inquiry();
				//$this->underwriting_rules_object->Display();
				break;

				case 'display_suppression_rules':
				$this->suppression_rules_object->Display();
				break;

				case 'add_suppression_rule':
				$this->suppression_rules_object->Add_Suppression_List_Value();
				$this->suppression_rules_object->Display();
				break;

				case 'remove_suppression_rule':
				$this->suppression_rules_object->Remove_Suppression_List_Value();
				$this->suppression_rules_object->Display();
				break;

				case 'display_holidays':
				$this->holiday_object->Display();
				break;
			
				//asm 80
                                case 'display_ach_providers':
                                $this->ach_provider_object->Display();
                                break;

				case 'add_ach_provider':
				$this->ach_provider_object->addAchProvider();
				$this->ach_provider_object->Display();
				break;

				case 'edit_ach_provider':
				$this->ach_provider_object->editAchProvider();
			        $this->ach_provider_object->Display();
				break;
				///////

				//asm 99
				case 'display_global_campaign_rules':
				$this->global_campaign_rule_object->Display();
				break;

				case 'edit_global_campaign_rule':
				$this->global_campaign_rule_object->editGlobalCampaignRule();
				$this->global_campaign_rule_object->Display();
				break;
				///////

				case 'display_tags':
				$this->tags_object->Display();
				break;

				case 'modify_tags':
				$this->tags_object->Modify_Weights();
				break;

				case 'add_investor_group':
				$this->tags_object->Add_Investor_Group();
				break;

				case 'display_blank':
				$this->blank_object->Display();
				break;

				case 'new_queue':
					$this->queue_config_object->New_Queue();
				break;
				
				case 'display_nada':
					$this->nada_import_object->Display();
				break;
				
				case 'import_nada_zip':
					$this->nada_import_object->Import_Zip($this->request,$this->file);
				break;

				case 'update_queue_timeout':
				$this->queue_config_object->Set_Timeout();
				break;
				
				case 'recycle_queues':
					$this->queue_config_object->Recycle_Queues();
					break;

				case 'update_queue_recycle_limit':
				$this->queue_config_object->Set_Recycle_Limit();
				break;

				case 'update_document_sort':
				$this->docs_config_object->Update_Sort();
				break;

				case 'update_package':
				$this->docs_config_object->Update_Package();
				break;

				case 'update_document':
				$this->docs_config_object->Update_Document();
				break;

				case 'delete_package':
				$this->docs_config_object->Delete_Package();
				break;

				//printing_queue
				case 'update_printing_queue':
				$this->docs_config_object->UpdatePrintingQueue();
				$this->docs_config_object->DisplayPrintingQueue();
				break;

				case 'reprint_documents_by_id':
				$this->docs_config_object->Reprint_Numbered_Range();
				$this->docs_config_object->DisplayPrintingQueue();
				break;

				case 'reprint_documents_by_date':
				$this->docs_config_object->Reprint_Date_Range();
				$this->docs_config_object->DisplayPrintingQueue();
				break;

				//email_responses
				case 'add_response':
				$this->docs_config_object->AddEmailResponse();
				$this->docs_config_object->DisplayEmailResponses();
				break;

				case 'modify_response':
				$this->docs_config_object->ModifyEmailResponse();
				$this->docs_config_object->DisplayEmailResponses();
				break;

				case 'remove_response':
				$this->docs_config_object->RemoveEmailResponse();
				$this->docs_config_object->DisplayEmailResponses();
				break;

				//email_footers
				case 'update_email_footer':
				$this->docs_config_object->UpdateEmailFooter();
				$this->docs_config_object->DisplayEmailFooters();
				break;

				case 'add_incoming_email':
				$this->docs_config_object->AddIncomingEmailAddress();
				$this->docs_config_object->DisplayEmailFooters();
				break;

				case 'remove_incoming_email':
				$this->docs_config_object->RemoveIncomingEmailAddress();
				$this->docs_config_object->DisplayEmailFooters();
				break;
				
				case 'update_document_process':
				$this->docs_config_object->Update_Document_Process();
				break;
				
				case 'save_flag_type':
					$this->flag_type_object->saveFlagType();
					break;

				case 'save_transaction_type':
					$this->transaction_type_object->saveTransactionType();
					break;
				case 'display_intercept_card':
					$this->intercept_card_object->Display();
					break;
					
				case 'update_intercept_card':
					$this->intercept_card_object->Update_Intercept_Card($this->request->intercept_card_values_string);
					$this->intercept_card_object->Display();
					break;
			}
		}

		return TRUE;
	}
}

?>
