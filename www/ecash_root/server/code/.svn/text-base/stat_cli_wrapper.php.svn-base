<?php

if ($_SERVER["argc"] < 2 || strlen($_SERVER["argv"][1]) == 0)
{
	die("\n" . __FILE__ . " called without environment parm. Stats lost. \n\n");
}
$environment_parm = strtoupper($_SERVER["argv"][1]);

switch($environment_parm)
{
	case 'LIVE':
	case 'RC':
	case 'LOCAL':
		$_BATCH_XEQ_MODE = $environment_parm;
		break;
	default:
		die("\n" . __FILE__ . " called with invalid environment parm ('$environment_parm'). Stats lost. \n\n");
}

require_once("../../www/config.php");

if ($_SERVER["argc"] < 3 || strlen($_SERVER["argv"][2]) == 0)
{
	die("\n" . __FILE__ . ": called without required Company parm. Stats lost. \n\n");
}
$co = strtoupper($_SERVER["argv"][2]);

if (!defined('DB_USER'))
{
	die("\n" . __FILE__ . ": called with invalid Company parm. Stats lost. \n\n");
}

require_once("applog.1.php");
$log = new Applog(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, $co);

require_once(SERVER_CODE_DIR . "stat.class.php");
require_once("common_functions.php");

$log->Write(__FILE__ . " called in $environment_parm environment.", LOG_INFO);

if ($_SERVER["argc"] < 4 || strlen($_SERVER["argv"][3]) == 0)
{
	$log->Write(__FILE__ . " called without required Transaction ID parm. Stats lost.", LOG_CRIT);
	exit;
}
$transaction_id = $_SERVER["argv"][3];

if ($_SERVER["argc"] < 5 || strlen($_SERVER["argv"][4]) == 0)
{
	$log->Write(__FILE__ . " called without required Transaction Status parm. Stats lost.", LOG_CRIT);
	exit;
}
$status = $_SERVER["argv"][4];

if ($_SERVER["argc"] < 6 || strlen($_SERVER["argv"][5]) == 0)
{
	$log->Write(__FILE__ . " called without required Agent parm. Stats lost.", LOG_CRIT);
	exit;
}
$agent_id = $_SERVER["argv"][5];
$is_react = null;
// Handle optional react_pulled stat type
if ($_SERVER["argc"] > 6 && strlen($_SERVER["argv"][6]) > 0)
{
	$is_react = $_SERVER["argv"][6];
}

echo date("d-M-Y H:i:s ") . ": stat forked OK.\n";

$server = new Server($log, $co, $agent_id);

$stat_obj = new Stat($server);

$stat_obj->Ecash_Hit_Stat($transaction_id, $status, $is_react);

exit;


// skeletal Server class
class Server
{
	public	$log;
	public	$co;
	public 	$company_id;
	public	$agent_id;
	private	$db;

	public function __construct($log, $co, $agent_id)
	{
		$this->log			= $log;
		$this->co			= $co;
		$this->agent_id		= $agent_id;

		$this->db = ECash::getMasterDb();

		$query = "
					SELECT 
						company_id
					FROM
						company
					WHERE
						name_short = '" . $this->db->quote($co) . "'
		";

		$this->company_id = $this->db->querySingleValue($query);

	}
}

?>
