<?php

	/**
	 * Module Display Object - Moved from server web
	 *
	 * @author Ray Lopez <raymond.lopez@sellingsource.com>
	 */
	class ECash_Module extends Object_1
	{
		public $active_module;

		protected $data;

	    // The basic processing that the class will do.
	    public function Execute_Processing() 
	    {
			$this->Run_Active_Module();
	    }

	    public function Get_Default_Module()
		{
			if(ECash::getConfig()->DEFAULT_MODULE)
			{
				$default_modules = explode("::",ECash::getConfig()->DEFAULT_MODULE);
				$this->active_module = $default_modules[0];
				foreach ($default_modules as $module)
				{
					ECash::getTransport()->Add_Levels($module);
				}
			}
		}

	    public function Run_Active_Module()
	    {
			if (isset($this->active_module))
			{

			/**
			 * By putting this in a Try/Catch block, we're catching the errors
			 * before they get back to Server_Web::Process_Data() which displays
			 * more/less information based on the Execution Mode and will email
			 * people the exception message.  At some point Server_Web will go away
			 * and we may want this, but with better handling.  [BR]
				try
			    {
					$module_result = $this->Load_Module($this->active_module);
			    }
			    catch (Exception $e)
			    {
					ECash::getLog()->Write("Module Exception:   " . $e->getMessage());
					ECash::getLog()->Write($e->getTraceAsString());
					ECash::getTransport()->Set_Levels('exception');
			    }
			 */

				$module_result = $this->Load_Module($this->active_module);
			}
	    }

	    /**
	     * After validating access to the requested module name,
	     * returns a new instance of the module.
	     *
	     * @param string $name
	     * @param mixed $request
	     * @return object
	     */
	    public function Load_Module($name)
	    {
			if($class_name=$this->Validate_Module($name))
			{
				$module = new $class_name(ECash::getServer(), ECash::getRequest(), $name);
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
	    public function Validate_Module($name)
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
		  $error  = "[Agent: ". ECash::getAgent()->getAgentId() ."] Invalid module: {$name}. ";
		  $error .= "Requested: \"{$_SERVER['PHP_SELF']}{$_SERVER['REQUEST_URI']}\"";
		  throw new Exception($error);
		}
	      }
	      else
	      {
		$error  = "[Agent: ". ECash::getAgent()->getAgentId() ."] Insufficient permissions to access {$name} module. ";
		$error .= "Requested: \"{$_SERVER['PHP_SELF']}{$_SERVER['REQUEST_URI']}\"";
		return false;
	      }
	    }

	    /**
	     * Accessor for the current module name
	     */
	    public function Get_Active_Module() { return $this->active_module; }

		/**
		 * Convert this object to a human-readable string. This makes for easy debugging.
		 *
		 * @return string The string of this object, enclosed in HTML pre tags.
		 */
		public function __toString()
		{
			$string = "<pre>";
			$string .= "page_array: " . To_String($this->page_array);
			$string .= "data: " . To_String($this->data);
			$string .= "user_acl: " . To_String($this->user_acl);
			$string .= "company_list: " . To_String($this->company_list);
			$string .= "company_id: {$this->company_id}";
			$string .= "</pre>";
			return $string;
		}

		/**
		 * Clone the data object if we get cloned.
		 */
		public function __clone()
		{
			if (isset($this->data) && is_object($this->data))
			{
				$this->data = clone $this->data;
			}
		}

		public function __get($value)
		{
			return $this->data->$value;
		}

		public function __set($key, $value)
		{
			$this->data->$key = $value;
		}
	}

?>
