<?php

require_once(SERVER_CODE_DIR . "master_module.class.php");

//ecash module
class Module extends Master_Module
{
	
	protected $fraud;
	protected $search;
	protected $edit;
	static $DEFAULT_MODE = 'fraud_queue';

	public function __construct(Server $server, $request, $module_name)
	{
		parent::__construct($server, $request, $module_name); 
		$this->_add_edit_object();

		if (isset($request->action) && $request->action === 'refresh_pop_up' && isset($_SESSION['previous_module']))
		{
			$section_names = ECash::getACL()->Get_Acl_Access($_SESSION['previous_module']);
		}
		else 
		{
			$section_names = ECash::getACL()->Get_Acl_Access('fraud');
		}
		
		$allowed_submenus = array();
		foreach($section_names as $key => $value)
		{
			$allowed_submenus[] = $value;
		}

		
		ECash::getTransport()->Set_Data( (object) array('allowed_submenus' => $allowed_submenus) );

		$all_sections = ECash::getACL()->Get_Company_Agent_Allowed_Sections($server->agent_id, $server->company_id);
		ECash::getTransport()->Set_Data((object) array('all_sections' => $all_sections));

		if(count($allowed_submenus) == 0 ||
		(!empty($request->mode) && !in_array($request->mode, $allowed_submenus)))
		{
			$request->action = 'no_rights';
			//tell the client to display the right screen
			ECash::getTransport()->Add_Levels($module_name, 'no_rights', 'no_rights');
			return;
		}

		$read_only_fields = ECash::getACL()->Get_Control_Info($server->agent_id, $server->company_id);
		ECash::getTransport()->Set_Data((object) array('read_only_fields' => $read_only_fields));


		// TODO: this standard mode & action setting should probably be moved to a parent
		//echo "<!-- ", print_r($request), " -->";
		//save the mode in the session and set a default mode
		if(isset($this->OVERRIDE_DEFAULT_MODE))
		{
			$default_mode = $this->OVERRIDE_DEFAULT_MODE;
		}
		else
		{
			$default_mode = self::$DEFAULT_MODE;
		}
		
		// #21012: the problem is, the default here is invalid, rather than overriding this
		// class, I decided to check which submenus were allowed before just trying to use an
		// invalid mode. This is a bit of a hack, but this section is used by absolutely nobody
		if (!in_array($default_mode, $allowed_submenus))
		{
			if (isset($allowed_submenus[0]))
				$default_mode = $allowed_submenus[0];	
		}

		if(!empty($request->mode))
		{
			if(isset($request->mode))
				$mode = $request->mode;
			else
				$mode = $default_mode;
		}
		elseif(!empty($_SESSION['fraud_mode']))
		{
			$mode = $_SESSION['fraud_mode'];
		}
		else
		{
			$mode = $default_mode;
		}

		$_SESSION['fraud_mode'] = $mode;

		//this is a bit of a hack to get the rules to display (rather than search)
		//if you click back up on the top-level [Fraud] link after viewing rules
		if (!isset($request->action))
		{
		   	if($_SESSION['fraud_mode'] == 'high_risk_rules' || $_SESSION['fraud_mode'] == 'fraud_rules')
				$request->action = 'show_fraud_rules';
			else
				$request->action = NULL;
		}

		require_once(SERVER_MODULE_DIR . $module_name . "/fraud.class.php");
		
		$this->fraud = new Fraud($server, $request, $mode, $this);

		require_once (LIB_DIR . "Document/Document.class.php");

		//tell the client to display the right screen
		ECash::getTransport()->Add_Levels($module_name, $mode);
	}

	public function Main()
	{
		$data = ECash::getTransport()->Get_Data();

		//echo "<!-- FRAUD_MOD ", print_r($this->request, TRUE), " -->";
		switch($this->request->action)
		{
			case "save":
				$data->affected_count = $this->fraud->Save_Rule();
				$data->fraud_rules = $this->fraud->Get_Rules(&$data);
				ECash::getTransport()->Set_Data($data);
				$this->Add_Current_Level();
				break;

			case "save_preview":
				//really save the rule
				$data->affected_count = $this->fraud->Save_Rule(FALSE);
				$data->fraud_rules = $this->fraud->Get_Rules(&$data);
				ECash::getTransport()->Set_Data($data);
				$this->Add_Current_Level();
				break;

			case "cancel_preview":
				//put a rule back
				$this->fraud->Cancel_Preview();
				$data->fraud_rules = $this->fraud->Get_Rules(&$data);
				ECash::getTransport()->Set_Data($data);
				$this->Add_Current_Level();
				break;				
								
			case "save_prop":
				$data->proposition_saved = $this->fraud->Save_Proposition();
				$data->fraud_rules = $this->fraud->Get_Rules(&$data);
				ECash::getTransport()->Set_Data($data);
				$this->Add_Current_Level();
				break;				
				
			case "confirm":
				$data->affected_count = $this->fraud->Confirm_Fraud();
				$data->fraud_rules = $this->fraud->Get_Rules(&$data);
				ECash::getTransport()->Set_Data($data);
				$this->Add_Current_Level();
				break;

			case "confirm_preview":
				$data->affected_count = $this->fraud->Confirm_Fraud(FALSE);
				$data->fraud_rules = $this->fraud->Get_Rules(&$data);
				ECash::getTransport()->Set_Data($data);
				$this->Add_Current_Level();
				break;

			case "unconfirm":
				$data->affected_count = $this->fraud->Unconfirm_Fraud();
				$data->fraud_rules = $this->fraud->Get_Rules(&$data);
				ECash::getTransport()->Set_Data($data);
				$this->Add_Current_Level();
				break;

			case "unconfirm_preview":
				$data->affected_count = $this->fraud->Unconfirm_Fraud(FALSE);
				$data->fraud_rules = $this->fraud->Get_Rules(&$data);
				ECash::getTransport()->Set_Data($data);
				$this->Add_Current_Level();
				break;

			case "load_rule":
				$data->fraud_rules = $this->fraud->Get_Rules(&$data);
				$data->fraud_rule_loaded = $this->fraud->Load_Rule();
				ECash::getTransport()->Set_Data($data);
				$this->Add_Current_Level();
				break;
				
			case "show_fraud_rules":
				$data->fraud_rules = $this->fraud->Get_Rules(&$data);
				ECash::getTransport()->Set_Data($data);
				$this->Add_Current_Level();
				break;

			default:
				$this->Master_Main();
				break;
				
		}

		return;
		
	}
	

	protected function Check_For_Loan_Conditions()
	{
		$data = new stdClass();
		//echo "Application ID: {$this->request->application_id}";
		if(Is_Do_Not_Loan_Set($this->request->application_id, $this->server->company_id))
		{
			$data->fund_warning = "Application is marked DO NOT LOAN ";
		} else if($this->funding->Check_For_Other_Active_Loans($this->request->application_id))
		{
			$data->fund_warning = "Found other active loans! &nbsp;&nbsp; Please review the Application History.";
		} else if (Has_A_Scheduled_Event($this->request->application_id))
		{
			$data->fund_warning = "Application has scheduled events";
		}
		ECash::getTransport()->Set_Data($data);
	}

	protected function Change_Status()
	{
		$this->fraud->Change_Status();
	}

	protected function Get_Next_App()
	{
		$this->fraud->Get_Next_Application();
	}

	protected function Add_Comment()
	{
		$this->fraud->Add_Comment();
	}

}
?>
