<?php

function Usage()
{
	echo "Usage: {$argv[0]} [company] [queue_name] [new_expiry]\n";
	exit;
	
}

// skeletal Server class
class Server
{
	public  $timer;
	public	$log;
	public  $company_id;
	public  $system_id;
	public  $company;
	public  $agent_id;
	private	$mysqli;
	
	public function __construct()
	{
		$this->mysqli = $this->MySQLi();
		$this->agent_id   = Fetch_Agent_ID_by_Login($this->MySQLi(), 'ecash');
	}

	public function MySQLi()
	{
		require_once(LIB_DIR . "mysqli.1e.php");

		if( ! $this->mysqli instanceof MySQLi_1 )
		{
			$this->mysqli = get_mysqli();
		}

		return $this->mysqli;
	}
	
	public function Set_Company($company_name)
	{
		$this->company = $company_name;
		$this->company_id = Fetch_Company_ID_by_Name($this->MySQLi(), $company_name);
		$_SESSION['company'] = $this->company;
		$_SESSION['company_id'] = $this->company_id;
		
		$this->Set_Agent($this->agent_id);
		
		$sys_name = ECash::getConfig()->SYSTEM_NAME;
		$this->system_id = Get_System_ID_By_Name($this->mysqli, $sys_name);
		Set_Company_Constants($this->company);
	}

	public function Set_Log ($log)
	{
		$this->log = $log;
		$this->timer = new Timer($this->log);
	}
	
	public function Fetch_Company_IDs()
	{
		$sql = "
		SELECT company_id, name_short FROM company WHERE active_status = 'active'";
		$result = $this->mysqli->Query($sql);
		while($row = $result->Fetch_Object_Row())
		{
			$companies[$row->company_id] = $row->name_short;
		}
		
		return $companies;
	}

	public function Set_Agent($agent_id)
	{
		$_SESSION["agent_id"] = $agent_id;
	}
}

// Include the config file here to load our execution context
require_once(dirname(__FILE__)."/../www/config.php");
require_once(COMMON_LIB_DIR . "applog.1.php");
require_once(LIB_DIR."timer.class.php");
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR."get_mysqli.func.php");

ini_set("date.timezone", ECash::getConfig()->TIME_ZONE);

$_BATCH_XEQ_MODE = strtoupper(EXECUTION_MODE);
$server = new Server();

$companies = $server->Fetch_Company_IDs();

// Handle argument checks and setting of config constants...
$arg_error = false;

if ($argc < 4 || (!in_array($argv[1], $companies))) {
	Usage();
}

// Yeah, this is kinda cheeseball, but I need $server for the MySQLi Object to get the company list
$server->Set_Company($argv[1]);

// Again, this is cheeseball, but it's still getting set.
$logname = "queues";
// Object setup
$log	= new Applog(APPLOG_SUBDIRECTORY.'/'.$logname, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, strtoupper($server->company));
$server->Set_Log($log);

require_once SQL_LIB_DIR . "/queues.lib.php";

current_queue_reset_expiry($argv[2], $argv[3]);

