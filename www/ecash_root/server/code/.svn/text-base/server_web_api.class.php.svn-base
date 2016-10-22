<?php
require_once(SERVER_CODE_DIR . 'server_web.class.php');
global $HTTP_RAW_POST_DATA;

/**
 * eCash Server Web API Class - This class is what handles all
 * requests for web api calls.
 */
class Server_Web_Api extends Server_Web
{

	public function __construct($session_id = FALSE)
	{
		parent::__construct($session_id);
	}

    protected function Process_Request_Vars() 
	{
		parent::Process_Request_Vars();
	
		$json_input = file_get_contents('php://input');

		$this->request = json_decode(urldecode($json_input));

		$this->api_module = $this->request->method;

	}

	// This is how we do our processing
	protected function Execute_Processing() 
	{
	
		$return_object = new StdClass();
		
		if (isset($this->request->id)) $return_object->id = $this->request->id;

		try 
		{
        	if ($module = $this->Load_API_Module($this->api_module)) 
			{
        		$return_object->result = $module->Main();
			} 
			else 
			{
        		throw new Exception ('Unknown Module (' . $this->api_module . ')');
			}
		} 
		catch (Exception $e) 
		{
			$return_object->error = $e->getMessage();
		}

		ECash::getTransport()->Set_Data($return_object);

		ECash::getTransport()->Set_Levels('json');

	}

	protected function Load_API_Module($name) 
	{

        if (file_exists(SERVER_CODE_DIR . 'api_' . strtolower($name) . '.class.php'))
        {
			$class_name = 'API_' . $name;
            $module = new $class_name($this, $this->request, $name);
       		if (!$this->Has_Permissions($module->get_permissions()))
       		{
       			throw new Exception("Permission Denied");
       		}
        
        } 
		else 
		{
			throw new Exception("No such file api_" . strtolower($name));
		}	
        return $module;

	}
	
	protected function Has_Permissions(array $permissions)
	{
		
		$this->Reset_ACL();
		$acl = ECash::getTransport()->acl;
			
		foreach ($permissions as $permission)
		{
			if ($acl->Acl_Check_For_Access($permission)) return true;
		}
		return false;
	}

    protected function Fetch_Attribute_State($class)
    {
		parent::Fetch_Attribute_State(get_parent_class($this));
    }

}

?>
