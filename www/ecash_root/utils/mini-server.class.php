<?php
define('DEFAULT_SOURCE_TYPE', 'manual');

require_once("acl.3.php");
require_once(LIB_DIR.'timer.class.php');

// A very simplistic version of the Server class for use with utilities
class Server
{
	public  $log;
	public  $company_id;
	public  $company;
	public  $company_list;		
	public  $agent_id;
	private $mysqli;
	public  $system_id;
	public  $acl;

	public function __construct($log, $mysqli, $company_id)
	{
		$this->log = $log;
		$this->mysqli = $mysqli;
		$this->company_id = $company_id;
		$this->system_id = 3;
		$this->acl = new ACL_3($this->MySQLi());
		$this->agent_id = 0;
		$this->timer = new Timer($log);

		$this->Fetch_Company_List();
				
		// Sets up all the default company variables and session variables
		$this->Load_Company_Config($company_id);
	}

	public function MySQLi()
	{
		return $this->mysqli;
	}

	//I hate this, but I did it anyway
	public function Fetch_Company_List()
	{
		$rows = $this->acl->Get_Companies(TRUE);

		foreach($rows as $row)
		{
			$this->company_list[$row->company_id]['name_short'] = $row->name_short;
			$this->company_list[$row->company_id]['name'] = $row->name;
			if(Is_ECash_3_Company($row->company_id)) {
				$this->company_list[$row->company_id]['ecash3_company'] = true;
			} else {
				$this->company_list[$row->company_id]['ecash3_company'] = false;
			}
		}
		return TRUE;
	}
	
	/**
	 * Load the company configuration file and set the session
	 * variables.  Does some basic validation checking.
	 *
	 * @param unknown_type $company_id
	 */
	public function Load_Company_Config($company_id)
	{
		if(! ctype_digit((string)$company_id) || ! isset($this->company_list[$company_id]))
		{
			throw new Exception("Invalid company id: $company_id");
		}
		
		$company = $this->company_list[$company_id]['name_short'];
		
		// Load the company specific configuration file
		$enterprise_prefix = ECash::getConfig()->ENTERPRISE_PREFIX;
		$config_filename = BASE_DIR . "/config/{$enterprise_prefix}/company/{$company}.php";
		if(file_exists($config_filename))
		{
			require_once($config_filename);
			$class_config = strtoupper($company) . "_CompanyConfig";
			ECash::setConfig(new $class_config(new Environment()));
		}
		else
		{
			throw new Exception("Invalid company configuration file: $config_filename");
		}

		// If we've loaded the configs without error, go ahead and set the company data in the session
		$this->company_id = $company_id;
		$this->company    = $company;

		$_SESSION['company_id'] = $this->company_id;
		$_SESSION['company'] = $this->company;

		$_SESSION['Server_state']['company_id'] = $this->company_id;
		$_SESSION['Server_state']['company'] = $this->company;
	}	
}