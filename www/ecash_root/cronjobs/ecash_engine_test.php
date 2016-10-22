<?php

// Most of the setup code here is taken from Doug Harris's test driver for ACH functions.
// However to standardize across different cron jobs, we're going to use it to set up all
// cron-style jobs for the system. 

// 2/07/2006 -- In accordance with the new configuration setup, I'm removing the
// need to enter the execution environment at the command line. The config file
// defined with the checkout will determine the execution mode.

// Data fix Tracking flag.

putenv("MAILTO=randy@gametruckparty.com");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_COMMON_DIR=/virtualhosts/ecash_common.cfe/");

if (!defined('DEFAULT_SOURCE_TYPE')) define('DEFAULT_SOURCE_TYPE', 'ecashcronjob');
define('IS_CRONJOB', true);

declare(ticks=1);

set_time_limit(0);
ob_implicit_flush(true);
set_magic_quotes_runtime(0);

// Common functions

function print_usage($companies = null)
{
	if (is_array($companies))
	{
		$company_description = 'can be: ' . implode(' ', $companies);
	}
	else
	{
		$company_description = 'short name of company';
	}

	echo "Usage: {$GLOBALS['argv'][0]} <company> <log> <execution_command>\n";
	echo "\n";
	echo "<log>                 is the name of the log directory to use\n";
	echo "<company>             " . $company_description . "\n";
	echo "<execution_command>   name of cronjob to run\n";
	echo "\n";
	echo 'Companies can be a comma-delimited list. It can include include the pseudo "all".' . "\n";
	echo 'Names can also be prepended with a operand of "+" or "-" and can be wildcard matches.' . "\n";
	echo 'Example: all,-ufc,-*_a' . "\n";
}

function signal_handler($signal)
{
	$db = ECash::getMasterDb();

	global $log;

	switch($signal)
	{
		case SIGTERM:
			// Handle graceful termination tasks
			echo "\n\nProcess termination requested\n\n";
			@$log->Write("Process termination requested", LOG_ERR);
			@$db->rollBack();
			exit;
		case SIGINT:
			// Handle graceful termination tasks
			echo "\n\nCTRL-C interrupt\n\n";
			@$log->Write("CTRL-C interrupt", LOG_ERR);
			@$db->rollBack();
			exit;
		default:
			// Handle all other signals
	}
}

/**
 * Parse a string and compare it against a given company list to determine what companies to run.
 *
 * Per normal PHP & MySQL execution, we silently continue if something is amiss.
 *
 * @param array $companies Known possible companies
 * @param string $arg Argument to parse for selected companies. Comma-delimited string and can use
 *                    shell-like wildcards.
 * @return array
 */
function getSelectedCompanies($companies, $arg)
{
	$selected = array();
	$argv = array_map('strtolower', array_unique(explode(',', $arg)));

	// Check if we want all companies.
	if (in_array('all', $argv))
	{
		// Preload the selected companies array with all companies
		$selected = $companies;

		// Unset the 'all' option from the parsed company array.
		// We don't really have to do this, but it's good to clean up after ourselves.
		unset($argv[array_search('all', $argv)]);
	}

	// Loop through the arguments and add or remove them from the selected list.
	foreach ($argv as $company_short)
	{
		// Assume that no operand means we want to add it
		$operand = '+';

		// Check for an operand then remove it from the company short name
		if (in_array($company_short[0], array('+', '-')))
		{
			$operand = $company_short[0];
			$company_short = substr($company_short, 1);
		}

		switch ($operand)
		{
			case '-':
				// Don't bother doing anything if there's nothing to remove.
				foreach ($selected as $key => $selected_short)
				{
					if (fnmatch($company_short, $selected_short))
					{
						unset($selected[$key]);
					}
				}
				break;

			case '+':
				// Only add a company if it isn't already in the list AND it is a known company
				foreach ($companies as $known_short)
				{
					if (fnmatch($company_short, $known_short) && !in_array($known_short, $selected))
					{
						$selected[] = $known_short;
					}
				}
				break;
		}
	}

	return $selected;
}

function validate_datestamp($datestamp_string)
{
	if ( !preg_match("/^(\d{4,4})-(\d\d)-(\d\d)$/", $datestamp_string, $matches) )
	{
		return FALSE;
	}

	$datestamp_year		= $matches[1];
	$datestamp_month	= $matches[2];
	$datestamp_day		= $matches[3];

	if ( !checkdate($datestamp_month, $datestamp_day, $datestamp_year) )
	{
		return FALSE;
	}

	return TRUE;
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
	private	$db;

	public function __construct()
	{
		$this->db = ECash::getMasterDb();
		$this->agent_id   = Fetch_Agent_ID_by_Login($this->db, 'ecash');
		$agent = ECash::getAgentById($this->agent_id);
		ECash::setAgent($agent);
	}

	public function Set_Company($company_name)
	{
		$this->company = $company_name;
		$this->company_id = Fetch_Company_ID_by_Name($this->db, $company_name);
		$_SESSION['company'] = $this->company;
		$_SESSION['company_id'] = $this->company_id;
		$_SESSION["agent_id"] = $this->agent_id;

		$sys_name = ECash::getConfig()->SYSTEM_NAME;
		$this->system_id = Get_System_ID_By_Name($this->db, $sys_name);
		
		/**
		 * Sets the company in the ECash object.
		 */
		$company = ECash::getFactory()->getCompanyById($this->company_id);
		ECash::setCompany($company);
	}

	public function Set_Log ($log)
	{
		$this->log = $log;
		$this->timer = new Timer($this->log);
	}

	/**
	 * This exists in the real Server class, but in the CRON
	 * world we don't need  
	 *
	 * @return unknown
	 */
	static public function Get_ACL()
	{
		return FALSE;
	}
	
	/* This method is stupid and ugly because it appears in about 5
	 * different places in different incarnations.  The real solution
	 * is combining all of the 'Server' classes into one comprehensive
	 * object heirarchy that can be reused anywhere and everywhere -- JRF
	 */
	public function Fetch_Company_IDs()
	{
		$sql = "
		SELECT company_id, name_short FROM company WHERE active_status = 'active'";
		$result = $this->db->query($sql);
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$companies[$row->company_id] = $row->name_short;

			if(Is_ECash_3_Company($row->company_id)) 
			{
				$info = array('ecash3_company' => true);
			} 
			else 
			{
				$info = array('ecash3_company' => false);
			}
			$info['name_short'] = $row->name_short;
			$_SESSION['Server_state']['company_list'][$row->company_id] = $info;
		}

		return $companies;
	}
}

// Load process control

$pcntl_support_exists = TRUE;
if (!extension_loaded('pcntl'))
{
	if (!@dl('pcntl.so'))
	{
		echo "\n\nNo pcntl support!\n";
		echo "The 'extension_dir' php.ini value '" . ini_get('extension_dir') . 
			 "' may not be the correct location for PHP modules on this server.\n\n";
		$pcntl_support_exists = FALSE;
	}
}
if ($pcntl_support_exists)
{
	pcntl_signal(SIGTERM, 'signal_handler');
	pcntl_signal(SIGINT , 'signal_handler');
}

// Include the config file here to load our execution context
require_once(dirname(__FILE__) . '/../www/config.php');
require_once(COMMON_LIB_DIR . 'applog.1.php');
require_once(LIB_DIR . 'timer.class.php');
require_once(LIB_DIR . 'common_functions.php');
require_once(LIB_DIR . 'Ach/ach.class.php');
require_once(SQL_LIB_DIR . 'util.func.php');

$_BATCH_XEQ_MODE = strtoupper(EXECUTION_MODE);
$server = new Server();
$_SESSION['server'] = $server;
ECash::setServer($server);
$companies = $server->Fetch_Company_IDs();
$selected_companies = array();

if(isset($argv[1]))
{
	$selected_companies = getSelectedCompanies($companies, $argv[1]);
}

if ($argc < 4 || empty($selected_companies))
{
	print_usage($companies);
	exit;
}
list ($file, $company_arg, $logname, $cmd) = $argv;

$overall_ret_val = 0; // 0 = success, -1 = failure.
foreach ($selected_companies as $company)
{
	// Backwards compatibility with crons that read this array directly.
	$argv[1] = $company;

	// Run this once for each company.
	$ret_val = include('ecash_engine_exec.php');

	// If the return value we have on file is more than the new one then use the new one instead
	if ($overall_ret_val > $ret_val)
	{
		$overall_ret_val = $ret_val;
	}
}

return $overall_ret_val;

?>
