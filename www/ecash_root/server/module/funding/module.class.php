<?php

require_once(SERVER_CODE_DIR . "master_module.class.php");
require_once(SERVER_CODE_DIR . "edit.class.php");
require_once(LIB_DIR . "Document/Document.class.php");

//ecash module
class Module extends Master_Module
{
	
	protected $funding;
	protected $search;
	protected $edit;
	protected $default_mode;
	
	public function __construct(Server $server, $request, $module_name)
	{
        parent::__construct($server, $request, $module_name); 
		$this->_add_edit_object();


		require_once(SERVER_MODULE_DIR . $module_name . "/funding.class.php");

		$section_names = ECash::getACL()->Get_Acl_Access('funding');
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

		if (!isset($request->action)) $request->action = NULL;
		
		/*GF 18216 - We need to set the defualt Mode to the first available
		  mode rather than simply setting it to 'verification.'*/

		$this->default_mode = strtolower($allowed_submenus[0]);
		
		// HACK-O-MATIC
		if (empty($request->mode) && isset($_SESSION['funding_mode']) &&
		    ($_SESSION['funding_mode'] == 'batch_mgmt'))
			unset($_SESSION['funding_mode']);

		//save the mode in the session and set a default mode
		if(!empty($request->mode))
		{
			if(isset($request->mode))
				$mode = $request->mode;
			else
				$mode = $this->default_mode;
		}
		elseif(!empty($_SESSION['funding_mode']))
		{
			$mode = $_SESSION['funding_mode'];
		}
		else
		{
			$mode = $this->default_mode;
		}

		$_SESSION['funding_mode'] = $mode;

		$this->funding = new Funding($server, $request, $mode, $this);

		//tell the client to display the right screen
		ECash::getTransport()->Add_Levels($module_name, $mode);
	}

	public function Main()
	{
		switch($this->request->action)
		{
			case "search":
				$num = $this->search->Search();
				if($num == 1)
				{
					$this->funding->Check_For_Loan_Conditions();
					ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
					
					// check to see if this application needs to be dequeued
					$this->funding->Search_Dequeue();
				}
				break;
				
			case "show_applicant":
				$this->Show_Applicant();
				break;
				
			case "save_vehicle_data":
				$this->edit->Save_Vehicle_Data();
				break;
			default:
				$this->Master_Main();
				break;

		}
		
		return;
		
	}

	protected function Change_Status($action = null)
	{
		$this->funding->Change_Status($action);
	}

	protected function	Add_Comment()
	{
		$this->funding->Add_Comment();
	}

	protected function Send_Hotfile_Docs()
	{
		$this->funding->Send_Hotfile_Docs();
	}
	
	protected function Show_Applicant()
	{
		if($this->search->Show_Applicant())
		{
     
			$this->funding->Check_For_Loan_Conditions($this->request->application_id);
				
			ECash::getTransport()->Add_Levels('overview','loan_actions','view','general_info','view');
				
			// check to see if this application needs to be dequeued
		//	$this->funding->Search_Dequeue();
       	}
	}

}

?>
