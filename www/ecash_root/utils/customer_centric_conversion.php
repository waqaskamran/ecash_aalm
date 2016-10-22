#!/usr/bin/php
<?php
/*
 * This script will merge all of the applications for a customer into a new customer account.
 * It currently converts on a company by company basis.
 *
 * This utility uses the current configuration based on the BASE_DIR/www/config.php file.
 *
 * Before using this utility, verify all of the defines below.  Only a few precautions
 * are made to verify data is not modified on the wrong servers.
 */


require_once("../www/config.php");
define ('CUSTOMER_LIB', BASE_DIR . "customer_lib/clk/");
require_once("mini-server.class.php");
require_once(COMMON_LIB_DIR."mysqli.1.php");
require_once(LIB_DIR . 'common_functions.php');
require_once(SQL_LIB_DIR . 'fetch_status_map.func.php');
require_once("crypt.3.php");

define('SSN_BATCH_SIZE', 1000);

if($argc <= 1) 
{
	echo "\nConvert a company's applications to Customer-Centric\n";
	echo "Usage: {$argv[0]} [company_id] [opt. application_id]\n";
	exit;
}

$company_id = $argv[1];
$application_id = $argv[2];

$cust_conversion = new Customer_Conversion($company_id);
$cust_conversion->Do_Conversion($application_id);

Class Customer_Conversion
{
	private $mysqli;
	private $company_id;
	private $logins;
	
	function __construct($company_id)
	{
		$this->mysqli = get_mysqli();
		$this->company_id = $company_id;
		openlog("customer_conversion", LOG_PID, LOG_LOCAL0);
	}
	
	function __destruct()
	{
		closelog();
	}
	
	public function Do_Conversion($application_id = NULL)
	{
		$total = 0; $last = 0; $average = 0;
		$start_time = time();

		syslog(LOG_INFO, "Starting customer conversion for company {$this->company_id}");
		// Grab SSN by SSN
		do	
		{
			// Hack to allow specifying a specific app id
			if($application_id != NULL)
			{
				$ssn_numbers = array(1);				
			}
			else
			{
				$ssn_numbers = $this->Fetch_SSN_Numbers($company_id);
			}
			foreach($ssn_numbers as $ssn)
			{
				$start = time();
				if($application_id != NULL)
				{
					$customer_apps = $this->Fetch_Applications_Info($application_id);
				}
				else
				{
					$customer_apps = $this->Fetch_Applications_By_SSN($ssn);
				}
		
				// Get the most recent Application
				$app_info = $this->Pick_Best_Application($customer_apps);

				// Create the customer
				$customer_id = $this->Create_Customer($app_info->ssn, $app_info->login, $app_info->password);

				syslog(LOG_INFO, "Created customer ID {$customer_id} - login: {$app_info->login} - SSN: {$app_info->ssn}");
				// Associate the applications with the customer id
				$this->Set_Customer_ID_on_Applications($customer_apps, $customer_id);
				//echo "Associated ". count($customer_apps) . " applications\n";
		
				$end = time();
				if($end > $start) {
					$performance = $total - $last;
					$time = time() - $start_time;
					$average = round($total / $time);
					echo date('h:i:s') . " [$performance customers][Average $average per sec] - " . number_format($total) . " Total\n";
					$last = $total;
				}

				$total++;
			}
		}
		while (count($ssn_numbers) > 0);

		syslog(LOG_INFO, "Converted $total applications for company {$this->company_id}");

	}
	
	private function Create_Customer($ssn, $login, $password)
	{
		$sql = "
		INSERT INTO customer
			( company_id, ssn, login, password, modifying_agent_id )
		VALUES
			( $this->company_id, '$ssn', '$login', '$password', 1 ) ";

		$result = $this->mysqli->Query($sql);

		return $this->mysqli->Insert_ID();
	}

	private function Set_Customer_ID_on_Applications($applications, $customer_id)
	{
		$app_ids = array();
		$ssn = $applications[0]->ssn;
		foreach($applications as $application) {
			$app_ids[] = $application->application_id;
		}
	
		$sql = "
		UPDATE application
		SET customer_id = $customer_id
		WHERE application_id IN ( " . implode(',', $app_ids) . " ) 
		AND ssn = '{$ssn}'";
	
		$result = $this->mysqli->Query($sql);
	}

	private function Fetch_Applications_By_SSN($ssn)
	{
		$sql = "
		SELECT 	ssn, 
				company_id, 
				application_id,
				UCASE(CONCAT(SUBSTRING(name_first, 1, 1), name_last)) as login_prefix,
				application_status_id, 
				date_created, 
				date_application_status_set
		FROM application
		WHERE ssn = '$ssn'
		AND company_id = {$this->company_id} ";

		$applications = array();
		$result = $this->mysqli->Query($sql);
		while($row = $result->Fetch_Object_Row())
		{
			$applications[] = $row;
		}
	
		return $applications;
	}	
	
	private function Fetch_Applications_Info($application_id)
	{
		$sql = "
		SELECT 	ssn, 
				company_id, 
				application_id,
				UCASE(CONCAT(SUBSTRING(name_first, 1, 1), name_last)) as login_prefix,
				application_status_id, 
				date_created, 
				date_application_status_set
		FROM application
		WHERE application_id = '$application_id'
		AND company_id = {$this->company_id} ";

		$applications = array();
		$result = $this->mysqli->Query($sql);
		while($row = $result->Fetch_Object_Row())
		{
			$applications[] = $row;
		}
	
		return $applications;
	}

	private function Fetch_SSN_Numbers()
	{
		static $count;
		if(is_null($count)) {
			$count = 1;
		} else {
			$count += SSN_BATCH_SIZE;
		}
	
		$sql = "
		SELECT DISTINCT ssn 
		FROM application
		WHERE company_id = {$this->company_id}
		AND customer_id = 0
		LIMIT $count,". SSN_BATCH_SIZE;
	
		$ssns = array();
		$result = $this->mysqli->Query($sql);
		while($row = $result->Fetch_Object_Row())
		{
			$ssns[] = $row->ssn;
		}
	
		return $ssns;
	}

	private function Pick_Best_Application($customer_apps)
	{
		$customer_apps[0]->login_prefix = preg_replace('/[^A-Za-z0-9\-_]/', '', $customer_apps[0]->login_prefix);

		list($login, $password) = $this->Generate_Login_and_Password($customer_apps[0]);

		$app_info = new stdClass();
		$app_info->ssn = $customer_apps[0]->ssn;
		$app_info->login = $login;
		$app_info->password = $password;
			
		return $app_info;
	}

	private function Generate_Login_and_Password($customer)
	{
		$number = 1;
		$prefix = preg_replace("/\W/", '', $customer->login_prefix);
		
		do
		{
			$login = $prefix . '_' . $number;
			$number++;
		}
		while ($this->Login_Exists($login));
		
		return array($login, $this->Generate_Password());
	}

	public function Login_Exists($login)
	{
		$prefix_array = str_split($login, 2);
		$prefix = $prefix_array[0];
		
		if(! is_array($this->logins))
		{
			$this->logins = $this->Fetch_Existing_Logins();
		}
		
		if(! isset($this->logins[$prefix]) && ! is_array($this->logins[$prefix]))
		{
			$this->logins[$prefix] = array();
		}
		
		if(in_array($login, $this->logins[$prefix]))
		{
			return true;
		}
		else
		{
			$this->logins[$prefix][] = $login;
			return false;
		}
	}

	private function Generate_Password()
	{
		$prefix = 'cash';
		$suffix = rand(100, 999);
		$password = $prefix . $suffix;
		return crypt_3::Encrypt($password);
	}

	private function Fetch_Existing_Logins()
	{
		$logins = array();
		
		$sql = "
			SELECT login
			FROM customer
			WHERE company_id = {$this->company_id}";

		$result = $this->mysqli->Query($sql);
		while($row = $result->Fetch_Object_Row())
		{
			$prefix_array = str_split($row->login, 2);
			$prefix = $prefix_array[0];
			
			if(! isset($logins[$prefix]) && ! is_array($logins[$prefix]))
			{
				$logins[$prefix] = array();
			}
			
			$logins[$prefix][] = $row->login;
		}
		
		return $logins;
	}
	
}