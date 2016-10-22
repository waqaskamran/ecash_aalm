<?php

require_once(COMMON_LIB_DIR  . "general_exception.1.php");
require_once(LIB_DIR         . "business_rules.class.php");
require_once(LIB_DIR         . "common_functions.php");
require_once(SERVER_CODE_DIR . "paydate_handler.class.php");
require_once(SERVER_CODE_DIR . "paydate_info.class.php");
require_once(SERVER_CODE_DIR . "stat.class.php");
require_once(SQL_LIB_DIR . "/application.func.php");

/**
 * ACH class
 * 
 * This class wraps the ACH related functions and calls the appropriate factories for 
 * batches, returns, and transports.
 *
 */
class Second_Tier
{
	private $server;
	private $mysqli;
	private $log;
	private $company_abbrev;
	private $company_id;
	private $batch_type;
	private $batch_date;
	private $business_day;
	
	private $holiday_ary;
	private $paydate_obj;
	private $paydate_handler;
	private $biz_rules;

	private $ach_company_name;
	private $ach_tax_id;
	private $ach_company_id;
	private $ach_report_company_id;
	private $ach_credit_bank_aba;
	private $ach_debit_bank_aba;
	private $ach_credit_bank_acct;
	private $ach_debit_bank_acct;
	private $ach_credit_bank_acct_type;
	private $ach_debit_bank_acct_type;
	private $ach_phone_number;

	private $process_log_ids;
	private $ach_batch_id;
	private $clk_ach_id;
	private $ach_filename;
	private $file;
	private $rowcount;
	private $blockcount;
	private $clk_total_amount;
	private $clk_trace_numbers;
	private $customer_trace_numbers;
	private $return_file_format;
	private $ach_exceptions;
	private $ach_exceptions_flag;
	
	public $process;
	
	public function __construct ($server, $process_type)
	{
		$this->server			= $server;
		$this->company_id		= $server->company_id;
		$this->company_abbrev	= strtolower($server->company);
		// Set up separate log object for ACH purposes
		$this->log = new Applog(APPLOG_SUBDIRECTORY.'/collections', APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, strtoupper($this->company_abbrev));

		if($process_type)
		{
			require_once(LIB_DIR . "second_tier_" . $process_type . "_" . strtolower(ECash::getConfig()->SECOND_TIER_FORMAT) . ".class.php");
			$class = "Second_Tier_" . ucfirst($process_type) . "_" . ECash::getConfig()->SECOND_TIER_FORMAT;

			if (class_exists($class)) {
				$this->process = new $class($this->server);
			} else {
				// Throw Exception
			}
		}
		else 
		{
			//Throw Exception requiring process_type
			
		}
	}
	

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $server
	 * @param unknown_type $process_type
	 * @return unknown
	 */
	public static function Get_Second_Tier_Handler($server, $process_type)
	{
		if($process_type)
		{
			require_once(LIB_DIR . "second_tier_" . $process_type . "_" . strtolower(ECash::getConfig()->SECOND_TIER_FORMAT) . ".class.php");
			$class = "Second_Tier_" . ucfirst($process_type) . "_" . ECash::getConfig()->SECOND_TIER_FORMAT;

			if (class_exists($class)) {
				return new $class($server);
			} else {
				// Throw Exception
			}
		}
		else 
		{
			//Throw Exception requiring process_type
			
		}
		
	}
	
	public function Initialize_Batch()
	{
		
	}
	
}
?>
