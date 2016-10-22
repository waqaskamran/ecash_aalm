#!/usr/bin/php
<?php

function Usage()
{
	echo "Usage: {$argv[0]} [company] '[rule_name]' '[rule_short]' '[rule_value]' '[rule_options]' \n";
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

$_BATCH_XEQ_MODE = strtoupper(EXECUTION_MODE);
$server = new Server();

$companies = $server->Fetch_Company_IDs();

// Handle argument checks and setting of config constants...
$arg_error = false;

if ($argc < 6 || (!in_array($argv[1], $companies))) {
	Usage();
}

// Yeah, this is kinda cheeseball, but I need $server for the MySQLi Object to get the company list
$server->Set_Company($argv[1]);

// Again, this is cheeseball, but it's still getting set.
$logname = "main";
// Object setup
$log	= new Applog(APPLOG_SUBDIRECTORY.'/'.$logname, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, strtoupper($server->company));
$server->Set_Log($log);

require_once LIB_DIR . "/company_rules.class.php";

$cr = Company_Rules::Singleton($server->company_id);

$lts = $cr->Get_Loan_Types($server->company_id);
foreach($lts as $lt) {
	if ($lt->name == Company_Rules::$set_name) {
		$loan_type_id = $lt->loan_type_id;
		break;
	}
}

$rule_set_id = $cr->Get_Current_Rule_Set_Id($loan_type_id);

$rule_name = $argv[2];
$rule_short = $argv[3];
$rule_value = $argv[4];
$rule_options = $argv[5];

$mysqli = get_mysqli();

try {
	$mysqli->Start_Transaction();

	$new_component_check_query = "
		-- eCash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
		SELECT rule_component_id
		FROM rule_component
		WHERE name = '{$rule_name}' AND name_short = '{$rule_short}'
	";

	if ($row = $mysqli->Get_Column($new_component_check_query))
	{
		$component_id = $row[0];
	}

	if (!isset($component_id))
	{
		$new_component_query =  "		
	        -- eCash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
			INSERT INTO rule_component (active_status, name, name_short, grandfathering_enabled)
			VALUES ('active', '{$rule_name}', '{$rule_short}', 'no')
			ON DUPLICATE KEY UPDATE active_status = 'active'
		";

		$mysqli->Query($new_component_query);
		$component_id = $mysqli->Insert_Id();
	}

	$next_in_seq_query = "
		-- eCash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
		SELECT IFNULL(MAX(sequence_no), 0) + 1 AS next_seq
		FROM rule_set_component
		WHERE rule_set_id = {$rule_set_id}
	";
	$res = $mysqli->Get_Column($next_in_seq_query);
	$next_in_seq = array_pop($res);

	$component_seq_query = "
		-- eCash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
		INSERT INTO rule_set_component (active_status, rule_set_id, rule_component_id, sequence_no)
		VALUES ('active', {$rule_set_id}, {$component_id}, {$next_in_seq} )
		ON DUPLICATE KEY UPDATE active_status = 'active'
	";
	$mysqli->Query($component_seq_query);

	$param_query_check = "
		-- eCash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
		SELECT rule_component_parm_id
		FROM rule_component_parm
		WHERE rule_component_id = {$component_id}
		AND parm_name = '{$rule_name}'
		AND display_name = '{$display_name}'
	";

	if ($row = $mysqli->Get_Column($param_query_check))
	{
		$param_id = $row[0];
	}

	if (!isset($param_id))
	{
		$param_query = "	
	        -- eCash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
			INSERT INTO rule_component_parm (active_status, rule_component_id, parm_name, sequence_no, display_name,
											 parm_type, user_configurable, input_type, presentation_type, value_label,
											 enum_values) 
			VALUES ('active', {$component_id}, '{$rule_name}', 1, '{$rule_name}',
					'string', 'yes', 'select', 'scalar', 'none',
					'{$rule_options}')
		";
		$mysqli->Query($param_query);
		$param_id = $mysqli->Insert_Id();
	}

	$value_query = "
		-- eCash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
		INSERT INTO rule_set_component_parm_value (agent_id, rule_set_id, rule_component_id, rule_component_parm_id, parm_value)
		VALUES (0, {$rule_set_id}, {$component_id}, {$param_id}, '{$rule_value}')
		ON DUPLICATE KEY UPDATE parm_value = '{$rule_value}'
	";
	$mysqli->Query($value_query);

	$mysqli->Commit();
} catch (Exception $e) {
	$mysqli->Rollback();
	var_dump($e);
}

?>
