#!/usr/bin/php
<?php
/**
 * This script is the second part of the customer conversion.  
 * 
 * The first part of the handles all applications that do not have a customer_id 
 * associated with the ssn.  We have to do this because OLP will be inserting apps 
 * during conversion and it causes some problems.
 * 
 * This script handles the second part where we take any applications that DO have
 * a customer_id associated with their SSN and need to be associated and updates the
 * customer_id on that account.
 *
 * This utility uses the current configuration based on the BASE_DIR/www/config.php file.
 *
 * Before using this utility, verify all of the defines below.  Only a few precautions
 * are made to verify data is not modified on the wrong servers.
 */


require_once("../www/config.php");
require_once(COMMON_LIB_DIR."mysqli.1.php");
require_once("mini-server.class.php");
require_once(LIB_DIR . 'common_functions.php');

if($argc <= 1) 
{
	echo "\nConvert a company's applications to Customer-Centric\n";
	echo "Usage: {$argv[0]} [company_id]\n";
	exit;
}

$company_id = strtolower($argv[1]);
$cust_conversion = new Customer_Conversion($company_id);
$cust_conversion->Do_Conversion();

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
	
	public function Do_Conversion()
	{
		$total = 0; $last = 0; $average = 0;
		$start_time = time();

		syslog(LOG_INFO, "Starting customer conversion for company {$this->company_id}");
		// Grab SSN by SSN
		do	
		{
			$accounts = $this->Fetch_Unassociated_Accounts();
			foreach($accounts as $a)
			{
				$start = time();

				$this->Set_Customer_ID_on_Application($a);
		
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

		echo "Converted $total applications for company {$this->company_id}\n";
		syslog(LOG_INFO, "Converted $total applications for company {$this->company_id}");

	}
	
	private function Set_Customer_ID_on_Application($account)
	{
		$sql = "
		UPDATE application
		SET customer_id = $account->customer_id
		WHERE ssn = '{$account->ssn}'
		AND company_id = {$this->company_id}
		";
	
		//echo $sql;
		$result = $this->mysqli->Query($sql);
	}

	private function Fetch_Unassociated_Accounts()
	{
		$sql = "
		SELECT a.ssn, c.customer_id
		FROM application a
        LEFT JOIN customer AS c ON c.ssn = a.ssn AND c.company_id = a.company_id
		WHERE a.company_id = {$this->company_id}
		AND a.customer_id = 0
		AND EXISTS ( SELECT 'X'
					 FROM customer AS c
					 WHERE c.ssn = a.ssn
					 AND c.company_id = a.company_id
				   )
        GROUP BY customer_id
        ";

		$accounts = array();
		$result = $this->mysqli->Query($sql);
		while($row = $result->Fetch_Object_Row())
		{
			$accounts[] = $row;
		}
	
		return $accounts;
	}
	
}