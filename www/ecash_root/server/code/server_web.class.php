<?php
/**
 * eCash Server Class - This class is what handles all
 * requests, session creation and destruction, and security
 * validation.
 *
 */
class Server_Web extends Server
{
	public $log;
	public $company_list;
	public $system_id;
	public $agent_phone;
	public $login;
	public $timer;
	public $new_app_url;
	public $php_memory_usage;
	protected $active_module;

	private $attribute_state_list;

	public function __construct($session_id)
	{
		parent::__construct();

		if (defined("SCRIPT_TIME_LIMIT_SECONDS"))
		{
			set_time_limit(SCRIPT_TIME_LIMIT_SECONDS);
		}

		if(!$session_id)
		{
			if(!empty($_COOKIE['ssid']))
			{
				$session_id = $_COOKIE['ssid'];
			}
			else
			{
				$session_id = md5(microtime());
			}
		}

		$this->attribute_state_list = array('active_module','company', 'company_id','agent_id',
											'user_acl', 'agent_phone', 'company_list', 'system_id');

		$this->company_list = array();

		// SET DEFAULT DISPLAY
		ECash::getTransport()->Set_Levels('application');

		// Added TSS TLD check to not annoy developers
		if (ECash::getConfig()->COOKIE_DOMAIN != NULL && strtolower(substr($_SERVER['SERVER_NAME'], -3, 3)) !== 'tss')
		{
			session_set_cookie_params(0, '/', ECash::getConfig()->COOKIE_DOMAIN);
		}
		$session = new ECash_Session('ssid', $session_id, ECash_Session::GZIP);

		$this->Fetch_Attribute_State(get_class($this));
		$context = ( isset($_SESSION["Server_state"]["company"]) ) ? $_SESSION["Server_state"]["company"] : "";

		$this->log = ECash::getLog();
		$this->timer = ECash::getMonitoring()->getTimer();
		// Trap condition where PHP memory limit is reached or approached and write log entry
		$this->php_memory_usage = memory_get_usage();
	    if ($this->php_memory_usage > PHP_MEMORY_USE_THRESHOLD)
	    {
			if (empty($_SESSION["php_memory_usage_threshold_reached"]) )
			{
				$_SESSION["php_memory_usage_threshold_reached"] = $this->php_memory_usage;
				$this->log->Write("PHP Memory usage reached " . $this->php_memory_usage . " bytes.", LOG_DEBUG);
				$this->log->Write("SESSION recursive count: " . count($_SESSION, COUNT_RECURSIVE) . ".", LOG_DEBUG);
				$this->log->Write("Server state data follows...", LOG_DEBUG);
				$this->log->Write("\n" . print_r($_SESSION["Server_state"], TRUE) . "\n", LOG_DEBUG);
			}
		}

		ECash::getTransport()->acl = ECash::getACL();
		$this->loadCompanyList();
		$this->transport = ECash::getTransport();
	}

    public function __destruct()
    {
        $this->Save_Attribute_State(get_class($this));
    }

    protected function Run_Active_Module()
    {
        if (isset($this->active_module))
        {
            try
            {
                $module_result = $this->Load_Module($this->active_module);
            }
            catch (Access_Denied_Exception $e)
            {
                unset($this->active_module);
                $this->transport->Set_Levels('application');
            }
        }
    }

    // The basic processing that the class will do.
    // this may look not needed, but is for extended classes
    protected function Execute_Processing() {
        ECash::getModule()->Execute_Processing();
    }

    public function Process_Data()
    {

		$modes = array('QA_MANUAL','QA_AUTOMATED', 'QA_SEMI_AUTOMATED');
		if(in_array(strtoupper(EXECUTION_MODE),$modes))
		{
			if( isset(ECash::getRequest()->faketime) || isset($_SESSION['faketime']) )
			{
				$faketime = ECash::getRequest()->faketime? ECash::getRequest()->faketime : $_SESSION['faketime'];
				$_SESSION['faketime'] = $faketime;
				putenv("FAKETIME={$faketime}");
			}
		}
        try
        {
            // CHECK SECURITY
		    if ( !$this->Security_Check() )
			{
                return $this->Generate_Login();
            }
          	// PROCESS ROOT LEVEL REQUEST VARIABLES
			$this->Process_Request_Vars();

          	// RUN MODULE
			$this->Execute_Processing();
        }
        catch( ECash_Application_NotFoundException $e)
        {
			$data = new stdClass();
			$data->search_message = $e->getMessage();
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels('search');
        }
        catch( Exception $e)
        {
        	$hostname = exec('hostname -f');

            // For LOCAL execution mode just spit out the error, otherwise
            // suppress the error message and email the notification recipients.
            $modes = array('LOCAL', 'RC', 'QA_MANUAL','QA_AUTOMATED', 'QA_SEMI_AUTOMATED');
		    if(in_array(strtoupper(EXECUTION_MODE),$modes))
            {
                echo "<pre>Please return the following data to the eCash Development Team:\n</pre>\n";
                echo "<pre>Request:\r\n" .  print_r($_REQUEST, TRUE) . "</pre>\r\n";
                echo "<pre>Error Message:\r\n" . $e->getMessage() . "</pre>\r\n";
                echo "<pre>Trace:\r\n" .    $e->getTraceAsString() . "</pre>\r\n";
            }

			$log_message =
					"eCash Exception Alert!\n" .
					"Execution Mode:  " . EXECUTION_MODE . "\n" .
					"Company: " . $this->company . "\n" .
					"Agent ID: {$this->agent_id}\r\n" .
					"Request:  \n" . var_export($_REQUEST, true) . "\n" .
					"Exception: {$e->getMessage()}\n" .
					"Trace:\n {$e->getTraceAsString()}\n";
			$this->log->Write($log_message, Log_ILog_1::LOG_ERROR);

		    $recipients = (strtoupper(EXECUTION_MODE) !== 'LOCAL') ? ECash::getConfig()->ECASH_NOTIFICATION_ERROR_RECIPIENTS : '';
			if(strlen($recipients) > 0)
			{
				$recipients = explode(',', $recipients);
				$body = "This is an eCash Process Data Exception Alert\r\n<br>\r\n<br>Execution Mode:  " . EXECUTION_MODE . "\r\n<br>\r\n<br>" .
						"Company:  " . $this->company . "\r\n<br>" .
						"Process Server: {$hostname}\r\n<br>" .
						"Agent ID: {$this->agent_id}\r\n<br>" .
						"Request:  <PRE>" . print_r($_REQUEST, true) . "</PRE>\r\n<br>\r\n<br>" .
						"Exception: {$e->getMessage()} \r\n<br>" .
						"Trace:\r\n<br> {$e->getTraceAsString()} \r\n<br>";
				require_once(LIB_DIR . '/Mail.class.php');
//				eCash_Mail::sendExceptionMessage($recipients, $body);
			}

			ECash::getTransport()->Set_Levels("exception");
        }

        $this->Save_Attribute_State(get_class($this));

        return $this->transport;
    }

    /**
     * After validating access to the requested module name,
     * returns a new instance of the module.
     *
     * @param string $name
     * @param mixed $request
     * @return object
     */
    private function Load_Module($name)
    {
        if($class_name=$this->Validate_Module($name))
        {
            $module = new $class_name($this, $this->request, $name);
            $this->active_module = $name;
            return $module->Main();
        }

        return FALSE;
    }

    /**
     * Validates access to a particular module, verifies it's existance
     * then requires it.
     *
     * @param string $name
     * @return boolean or Exception
     */
    private function Validate_Module($name)
    {
    	// @todo figure out why this was necessary
    	$name = strtolower($name);

    	if( ECash::getACL()->Acl_Access_Ok($name, ECash::getCompany()->company_id))
    	{

    		if (file_exists(CUSTOMER_LIB.$name."/server_module.class.php"))
    		{
    			$module_file = CUSTOMER_LIB.$name."/server_module.class.php";
    			$class_name = "Server_Module";
    		}
    		else
    		{
    			$module_file = SERVER_MODULE_DIR . $name . "/module.class.php";
    			$class_name = "Module";
    		}

    		if(file_exists($module_file))
    		{
    			require_once($module_file);
    			return $class_name;
    		}
    		else
    		{
    			$error  = "[Agent: {$this->agent_id}] Invalid module: {$name}. ";
    			$error .= "Requested: \"{$_SERVER['PHP_SELF']}{$_SERVER['REQUEST_URI']}\"";
    			throw new Access_Denied_Exception($error);
    		}
    	}
    	else
    	{
    		$error  = "[Agent: {$this->agent_id}] Insufficient permissions to access {$name} module. ";
    		$error .= "Requested: \"{$_SERVER['PHP_SELF']}{$_SERVER['REQUEST_URI']}\"";
    		throw new Access_Denied_Exception($error);
    	}
    }

    protected function Save_Attribute_State($class)
    {
    	foreach($this->attribute_state_list as $attribute)
    	{
    		if (!empty($this->{$attribute}))
    		{
    			$_SESSION[$class . "_state"][$attribute] = $this->{$attribute};
    		}
    	}
    	return TRUE;
    }

    protected function Fetch_Attribute_State($class)
    {
    	foreach($this->attribute_state_list as $attribute)
    	{
    		if( isset($_SESSION[$class . "_state"][$attribute]) )
    		{
    			$this->{$attribute} = $_SESSION[$class . "_state"][$attribute];
    			ECash::getModule()->{$attribute} = $_SESSION[$class . "_state"][$attribute];
    		}
    	}
    	return TRUE;
    }

	/**
	 * public for new page/template Authentication Helper
	 */
    public function Security_Check ()
    {
		$ip_address = $_SERVER['REMOTE_ADDR'];

		// Security element - Handle Logouts
    	if(!empty(ECash::getRequest()->logout) )
    	{
    		ECash::getTransport()->Set_Levels('login');
			ECash::getLog()->Write("[LOGOUT - $ip_address] Agent ID: " . $this->agent_id . " - " . $this->getAgentName());
    		session_destroy();
    		return false;
    	}

    	$security = new ECash_Security(SESSION_EXPIRATION_HOURS);

    	// Security element - Handle Login Timeouts
    	if( isset($this->agent_id) )
    	{
    		if( !$security->checkTimeout($_SESSION['security_6']['login_time']) )
    		{
    			ECash::getTransport()->Add_Error("Login expired");
    			session_destroy();
    			return false;
    		}

    		// Security element - Handle Logins
    	}
    	else
    	{
    		if( isset(ECash::getRequest()->page) && ECash::getRequest()->page == 'login' && isset(ECash::getRequest()->abbrev) )
    		{
    			$this->company = strtolower(ECash::getRequest()->abbrev);
    			foreach ($this->company_list as $id => $value)
    			{
    				if ($value['name_short'] == $this->company)
    				{
    					$_SESSION['system_id'] = $this->system_id;
    					$this->Load_Company_Config($id);
    					break;
    				}
    			}

    			$sys_name = ECash::getConfig()->SYSTEM_NAME;
    			if( $security->loginUser($sys_name, Mqgcp_Escape(ECash::getRequest()->login), ECash::getRequest()->password, $_SESSION['security_6']['login_time']) )
    			{
					$agent = $security->getAgent();
    				$this->setAgent($agent);
    				$this->agent_phone = (ECash::getRequest()->phone_extension) ? ECash::getRequest()->phone_extension : NULL ;
    				setcookie("previous_phone_extension", $this->agent_phone, time() + 86400 * 7);
    				ECash::getTransport()->Set_Levels('application');

    				# Check for logging into to a company you don't have any permissions for.
    				$this->Setup_ACL();
    				ECash::getModule()->Get_Default_Module();
    				foreach ($this->company_list as $id => $value)
    				{
    					if ($value['name_short'] == ECash::getRequest()->abbrev && !$value['agent_allowed'])
    					{
    						ECash::getTransport()->Add_Error("Company Permission Denied");
    						session_destroy();
    						return false;
    					}
    				}
					//[#38463] record sucessful login
					$agent->updateLogin();
    				ECash::getLog()->Write("[LOGIN - $ip_address] Agent ID: " . $this->agent_id . " - " . $this->getAgentName());
    			}
    			else
    			{
    				ECash::getLog()->Write("[INVALID LOGIN - $ip_address] Username: " . ECash::getRequest()->login);
    				ECash::getTransport()->Add_Error("Invalid login");
    				return false;
    			}
    		}
    		else
    		{
    			return false;
    		}
    	}

    	ECash::getTransport()->login = $this->login;
    	ECash::getTransport()->agent_id = $this->agent_id;

    	// Setup ACL for the active module
    	$this->Setup_ACL();

    	return true;

    }

    /**
     * This is necessary so that if an agent who happens
     * to be in company a searches for an application in company b,
     * they will need to switch companies mid-request.
     *
     * @param int $company_id
     */
    public function Set_Company($company_id = NULL)
    {
    	if(isset($this->company_list[$company_id]))
    	{
    		$this->company_id = $company_id;
    		$this->company    = $this->company_list[$company_id]['name_short'];
    		$_SESSION['company']    = $this->company;

    		$this->Reset_ACL();
    	}
    	else
    	{
    		throw new Exception ("Invalid company id: {$company_id}");
    	}
    }

	//new style
	private function setCompanyID($company_id)
	{
		$company = ECash::getFactory()->getCompanyById($company_id);
		ECash::setCompany($company);
	}

	private function setCompanyNameShort($name)
	{
		foreach ($this->company_list as $id => $value)
		{
			if ($value['name_short'] == $name)
			{
				$this->setCompanyID($id);
				break;
			}
		}
	}

    protected function Get_Default_Module()
    {
    	if(ECash::getConfig()->DEFAULT_MODULE)
    	{
    		$default_modules = explode("::",ECash::getConfig()->DEFAULT_MODULE);
    		$this->active_module = $default_modules[0];
    		foreach ($default_modules as $module)
    		{
    			$this->transport->Add_Levels($module);
    		}
    	}
    }

    public function Reset_ACL()
    {
        $this->Setup_ACL();
        //If they don't have access to any groups, they're getting logged out!
        $acl = ECash::getACL()->getGroupsSections($this->agent_id);
        if(empty($acl))
        {
        		$data = new stdClass();
        		$data->companies = $this->company_list;
    			ECash::getTransport()->Set_Data($data);
        		ECash::getTransport()->Set_Levels('login');
        		ECash::getTransport()->Add_Error("User does not have appropriate access.  Logging out.");
				session_destroy();
				$this->company = strtolower(ECash::getRequest()->abbrev);
    			foreach ($this->company_list as $id => $value)
    			{
    				if ($value['name_short'] == $this->company)
    				{
    					$_SESSION['system_id'] = $this->system_id;
    					$this->Load_Company_Config($id);
    					break;
    				}

    			}
				return false;
        }
        else
        {
        	return true;
        }

    }

    /**
     * Consolidated area to load up all of the ACL information
     * for an agent
     */
    private function Setup_ACL()
    {
    	ECash::getACL()->fetchUserACL($this->agent_id, $this->company_id);
    	$this->Set_Agent($this->agent_id);

    	$acl_sub_access = array();
    	$user_acl_sub_names = array();
    	if (isset(ECash::getModule()->active_module))
    	{
    		$acl_sub_access = ECash::getACL()->Get_Acl_Access(ECash::getModule()->active_module);
    		$user_acl_sub_names = ECash::getACL()->Get_Acl_Names($acl_sub_access);
    	}
    	ECash::getTransport()->user_acl_sub_access = $acl_sub_access;
    	ECash::getTransport()->user_acl_sub_names = $user_acl_sub_names;
    	$acl_descriptions = ECash::getACL()->Get_Acl_Access();

    	ECash::getTransport()->company = $this->company;
    	ECash::getTransport()->company_name = $this->company_list[$this->company_id]['name'];
    	ECash::getTransport()->company_id = $this->company_id;
    	$this->loadCompanyList();
    	ECash::getTransport()->user_acl = $acl_descriptions;
    	ECash::getTransport()->user_acl_names = ECash::getACL()->Get_Acl_Names($acl_descriptions);

    	// If the agent has permission, we'll set this to true.
    	// This is used for the company selection drop-down.
    	$agent_allowed_company_list = ECash::getACL()->getAllowedCompanyIDs();
    	foreach($this->company_list as $company_id => $data)
    	{
    		$this->company_list[$company_id]['agent_allowed'] = in_array($company_id, $agent_allowed_company_list);
    	}

    	ECash::getTransport()->company_list = $this->company_list;

    }

    protected function Process_Request_Vars()
    {
    	$request = ECash::getRequest();
    	$module  = ECash::getModule()->Get_Active_Module();
    	// company_id is application state that can change when you load an app for a different company
    	//
    	// GF 20210: The old bit of code that was here was allowing agents to switch companies even if
    	// the MULTI_COMPANY_ENABLED config var was set to true. Now it will only override the current
    	// company ID if MULTI_COMPANY is set to true, or we are in the fraud module.
    	$multi_company = ECash::getConfig()->MULTI_COMPANY_ENABLED;

    	if($multi_company === TRUE || $module == "fraud")
    	{
    		$company_id = isset($request->new_company_id) ? $request->new_company_id : ECash::getCompany()->company_id;
    		if (isset($request->company_id) && $request->company_id > 0) $company_id = $request->company_id;
    	}
    	else
    	{
    		$company_id = ECash::getCompany()->company_id;
    	}

    	// GF 10346: This hellacious hack was causing the agent's company to change if they ran a report for a different company
    	// than the one they are in. Unfortunately, the reason for this hack really can't be traced as this happens
    	// whenever a request is made, which is in about a bazillion different places, so I'm just going to comment this out
    	// test basic functionality, and hope it doesn't blow up something somewhere. [benb]

    	// This hack has been re-enabled for further testing in a limited environment.
    	//if (isset($this->request->company_id) && $this->request->company_id > 0) $company_id = $this->request->company_id;

    	$this->Load_Company_Config($company_id);

    	// The module can be requested too, so change active from the default if its specified
    	if( isset($request->module) )
    	{
    		$this->active_module = $request->module;
    		ECash::getModule()->active_module = $this->active_module;
    		$this->Reset_ACL(); // We changed it, so reload it.
    	}

    	// PBX Setup can be forced on, so its dependant on a request varable.
    	$this->Setup_PBX();
    }

    protected function Setup_PBX ()
    {

    	/*
    	* TODO: does this need to be here?
    	*/
    	// set whether PBX is enabled
    	// using constant due to login environment needing enabling set before company id determined
    	ECash::getTransport()->pbx_enabled = (ECash::getConfig()->PBX_ENABLED && $this->company_list[$this->company_id]['pbx_enabled'] === true && $this->agent_phone != NULL) ? ECash::getConfig()->PBX_ENABLED : false;
    	if(ECash::getTransport()->pbx_enabled !== false && isset(ECash::getRequest()->pbx_force_live))
    	{
    		$_SESSION['pbx_force_live'] = TRUE;
    	}

    	if (ECash::getTransport()->pbx_enabled === TRUE)
    	{
    		eCash_PBX::registerLogin($this);
    	}
    }

    private function Generate_Login()
    {
    	$data = new stdClass();
    	$data->companies = $this->company_list;
    	ECash::getTransport()->Set_Data($data);
    	ECash::getTransport()->Set_Levels('login');
    	return ECash::getTransport();
    }

    protected function loadCompanyList()
    {

    	// @todo remove this require_once
    	require_once LIB_DIR . "/PBX/PBX.class.php";
    	$company_list = ECash::getFactory()->getReferenceList('Company');

    	foreach($company_list as $row)
    	{
    		//only use 'active' companies
    		if($row->active_status == 'active')
    		{
    			$pbx_enabled = eCash_PBX::isEnabled($this, $row->company_id);

    			$this->company_list[$row->company_id]['name_short'] = $row->name_short;
    			$this->company_list[$row->company_id]['name'] = $row->name;
    			$this->company_list[$row->company_id]['ecash3_company'] = true;
    			$this->company_list[$row->company_id]['pbx_enabled'] = $pbx_enabled;
    		}
    	}
    	return TRUE;
    }

    /**
     * Accessor for the current module name
     */
    public function Get_Active_Module() { return $this->active_module; }

    /**
     * Set the Session and Object values for the current Agent ID
     *
     * @param unknown_type $agent_id
     */
    public function Set_Agent($agent_id)
    {

    	$this->agent_id = $agent_id;
    	$_SESSION["agent_id"] = $agent_id;
    }

    public function setAgent(ECash_Agent $agent)
    {
    	ECash::setAgent($agent);
    	$agent_model = $agent->getModel();
    	$_SESSION['agent_id'] = $agent_model->agent_id;

    	$this->system_id = ECash::getSystemId();
    	$this->login = $agent_model->login;
    }

    public function setAgentID($agent_id)
    {
    	$model = ECash::getFactory()->getAgentById($agent_id);
    	$this->setAgent($model);
    }

    public function getAgentName()
    {

    	$agent_model = ECash::getAgent()->getModel();
    	return $agent_model->name_first . " " . $agent_model->name_last;
    }

    public function getAgentOrange()
    {
    	$agent_model = ECash::getAgent()->getModel();
    	return $agent_model->name_first . " " . substr($agent_model->name_last, 0, 1) . '.';
    }

    public function __toString()
    {
    	$string = "<pre>";
    	$string .= "agent_id = {$this->agent_id}\n";
    	$string .= "acl = " . To_String(ECash::getACL());
    	$string .= "successful_modules = " . To_String($this->successful_modules);
    	$string .= "active_module = ".ECash::getModule()->active_module."\n";
    	$string .= "</pre>";

    	return $string;
    }

    /**
     * Load the company configuration file and set the session
     * variables.  Does some basic validation checking.
     *
     * @param unknown_type $company_id
     */
    public function Load_Company_Config($company_id)
    {
    	if(!ctype_digit((string)$company_id) || !isset($this->company_list[$company_id]))
    	{
    		throw new Exception("Invalid company id: $company_id");
    	}

    	$company = $this->company_list[$company_id]['name_short'];

    	// Load the company specific configuration file
    	$enterprise_prefix = ECash::getConfig()->ENTERPRISE_PREFIX;
    	$config_filename = CUSTOMER_CODE_DIR . $enterprise_prefix . '/Config/' . $company . '.php';

    	try
    	{
    		require_once($config_filename);
        	$company_class = $company . '_CompanyConfig';
		ECash::setConfig(new $company_class(ECash::getConfig()->getBaseConfig()));
       	}
    	catch(Exception $e)
    	{
    		throw new Exception("Invalid company configuration class or company config file does not exist: $config_filename");
    	}

    	// If we've loaded the configs without error, go ahead and set the company data in the session
    	$this->setCompanyID($company_id);

    	$_SESSION['Server_Web_state']['company_id'] = $this->company_id;
    	$_SESSION['Server_Web_state']['company'] = $this->company;

    	ECash::getTransport()->new_app_url = ECash::getConfig()->NEW_APP_SITE;
    }

	//we'll have to make these smarter when we start replacing more items
	//one at a time for now [JustinF]
	public function __get($name)
	{
		switch($name)
		{
			/** @todo: Move the Get_ACL() method into the new ECash
			 *  object
			 *
			 * THIS IS ARE THE WORST THING I'VE SEEN... IT'S
			 * LIKE WE'RE DIGGING A DITCH AND SIMULTANEOUSLY SOMEONE
			 * IS FILLING IT IN [JUSTINF]
			 */
			case 'acl':
				return ECash::getACL()->Get_ACL();
				break;

			case 'company_id':
				if (ECash::getCompany() !== NULL)
				{
					return ECash::getCompany()->company_id;
				}
				else
				{
					return 0;
				}
				break;

			case 'company':
				if (ECash::getCompany() !== NULL)
				{
					return ECash::getCompany()->name_short;
				}
				else
				{
					return 'login';
				}
				break;

			case 'agent_id':
				$agent = ECash::getAgent();
				if ($agent !== NULL) return $agent->getAgentId();
				else return NULL;
				break;

			case 'agent_name':
				return $this->getAgentName();
				break;

			case 'agent_orange':
				return $this->getAgentOrange();
				break;

			default:
				return $this->$name;
				break;
		}
    }

	public function __set($name, $value)
	{
		switch($name)
		{
			case 'company_id':
				$this->setCompanyID($value);
				break;

			case 'company':
				$this->setCompanyNameShort($value);
				break;

			case 'agent_id':
				$this->setAgentID($value);
				break;

			case 'agent_name':
			case 'agent_orange':
				die("<pre>who is setting {$name}?\n". print_r(debug_backtrace(), TRUE));
				break;

			default:
				$this->$name = $value;
				break;
		}
	}

	public function __isset($name)
	{
		switch($name)
		{
			case 'agent_id':
				return (ECash::getAgent() !== NULL && ECash::getAgent()->getAgentId() !== ECash::getConfig()->DEFAULT_AGENT_ID);
				break;

			// Some code does an isset() on $server->company_id so we
			// have to provide a valid result.
			case 'company_id':
				$company = ECash::getCompany();
				return isset($company);
				break;

			case 'company':
				$company = ECash::getCompany();
				return isset($company);
				break;

			default:
				return isset($this->{$name});
				break;
		}
	}

}

?>
