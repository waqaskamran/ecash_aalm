<?php
require_once(dirname(__FILE__)."/../../www/config.php");
require_once(dirname(__FILE__)."/../../www/paths.php");

class IDV_CLI_Wrapper
{
	public $argc;
	public $argv;
	private $log;
	private $company;
	private $application_id;
	private $db;
	private $mode;

	public function __construct()
	{
		//this is the default
		$this->mode = 'LOCAL';
	}

	public function Usage($app_name)
	{
		$php = trim(`which php`);
		print <<<END_USAGE
Usage: {$php} {$app_name} MODE company_short application_id datax_license_key datax_password agent_id
   eg: {$php} {$app_name} RC ucl 440419 key password
   eg: {$php} {$app_name} LIVE d1 900918 key password

Notes: PHP interpreter must be PHP5


END_USAGE;
		exit(1);
	}
	
	private function Set_Defines()
	{
		//get LOCAL, LIVE or RC
		$this->mode = $this->CLI_Get_Parm(1, "called without environment parm. DataX Fund call lost.");
	
		switch($this->mode)
		{
			case 'LIVE':
			case 'RC':
			case 'LOCAL':
                	case 'QA_MANUAL':
        	        case 'QA_SEMI_AUTOMATED':
	                case 'QA_AUTOMATED':
			$_BATCH_XEQ_MODE = $this->mode;
			break;
			default:
			die("\n" . __FILE__ . " called with invalid environment parm ('{$this->mode}'). DataX Fund call lost. \n\n");
		}
		require_once("applog.1.php");
		require_once("minixml/minixml.inc.php");
		require_once(LIB_DIR . "common_functions.php");
		require_once(SQL_LIB_DIR.'util.func.php');
	
		//get UFC, UCL, etc.
		$this->company = $this->CLI_Get_Parm(2, "called without required Company parm. DataX Fund call lost.");
		$this->log = get_log();
	
		//get the application ID
		$this->application_id = $this->CLI_Get_Parm(3, "called without required Application ID parm. DataX Fund call lost.");
	
		$this->log->Write(__FILE__ . " called in {$this->mode} environment for application_id {$this->application_id}", LOG_INFO);
	
		$this->db = ECash::getMasterDb();
	}
	
	public function main($argc, $argv)
	{
		if($argc != 7)
		{
			IDV_CLI_Wrapper::Usage($argv[0]);
		}
	
		//get down to business
		$idv = new IDV_CLI_Wrapper();
		$idv->argc = $argc;
		$idv->argv = $argv;
		$idv->Set_Defines();
		echo date("d-M-Y H:i:s ") . ": datax fund forked OK.\n";
		$idv->DataX_Call();
	}
	
	private function DataX_Call()
	{
		$bureau_data = ECash::getFactory()->getData('Bureau');
		try
		{
			//only sending one ID, only expect one back
			$idv_info = $bureau_data->getIDVInformation($this->application_id);
			$data = current($idv_info);
		}
		catch(Exception $e)
		{
			$this->log->Write(__FILE__ . " Error in bureau_inquiry query", LOG_CRIT);
			throw $e;
		}
		
		//Get the fundup call name from the business rules
		$business_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());
		$rule_set_id = $business_rules->Get_Rule_Set_Id_For_Application($this->application_id);
		$rules = $business_rules->Get_Rule_Set_Tree($rule_set_id);
		
		//If the company has a specified call name, use that. fundupd_l1 is the default
		$call_name = (!empty($rules["FUNDUPD_CALL"]) ? $rules["FUNDUPD_CALL"] : 'fundupd_l1');
		
		$Datax_License_Key = $this->argv[4];
		$Datax_Password    = $this->argv[5];
		$Agent_ID          = $this->argv[6];

		$DataX = new ECash_DataX($Datax_License_Key, $Datax_Password, $call_name);
		$DataX->setRequest('Fund_Update');
		$DataX->setResponse('Fund_Update');
		$DataX->execute((array)$data);
		
		//Save The Request
		$DataX->saveResult($Agent_ID);
	}
	
	//for gathering command line parameters
	private function CLI_Get_Parm($arg_no, $message)
	{
		if ($this->argc < ($arg_no + 1) || strlen($this->argv[$arg_no]) == 0)
		{
			if($this->log != NULL)
			{
				$this->log->Write(__FILE__ . " {$message}", LOG_CRIT);
			}
			else
			{
				die("\n" . __FILE__ . " {$message} \n\n");
			}
		}
		return strtoupper($this->argv[$arg_no]);
	}
}

IDV_CLI_Wrapper::main($argc, $argv);

?>
