<?php

  /**
   *
   *
   */

//make this something retarded so it won't likely interfere with
//future defines in www/config.php
if(!defined('MY_CRON_DIR'))
	define('MY_CRON_DIR', dirname(__FILE__) . '/');

require_once 'libolution/DB/MySQLConfig.1.php';
require_once 'libolution/Mail/Trendex.1.php';
require_once LIB_DIR . 'business_rules.class.php';
require_once 'cfc_batch/GoldRecord.php';
require_once 'cfc_batch/ClassicRecord.php';


class CFC_Batch
{
	//this can be replaced once all other
	//nightly crons are using libolution/PDO
	private $db;
	
	public function __construct()
	{
	}
	
	/**
	 * This is mostly garbage to create a stub server class so this
	 * file can be tested both from: ecash_engine.php -> nightly.php
	 * *AND* standalone from the CLI
	 */
	public static function main($argv)
	{
		require_once MY_CRON_DIR.'../www/config.php';

		//use this same code if calling externally (i.e. not from CLI)
		exit(CFC_Batch::Call());
	}

	public static function Call()
	{		
		$batch = new CFC_Batch();
		return($batch->Run());
	}

	private function Run()
	{
		//connect to the DB
		$this->connectDB();
		
		//build batch file
		$this->buildBatch($this->getApps());
		
		//mark applications as approved/sent
		
		//send batch
	}

	private function connectDB()
	{
		$this->db = ECash::getMasterDb();
	}


	private function buildBatch($rows)
	{
		$file = '/tmp/cfc_batch.txt';
		$fp = fopen($file, 'w');
		foreach($rows as $row)
		{
			$record = $this->getRecord($row);
			echo 'record: ', print_r($record, TRUE), PHP_EOL;
			foreach($record as $element)
			{
				fwrite($fp, $element);
				echo 'Writing ', $record->key(), PHP_EOL;
			}
			fwrite($fp, "\r\n");
		}
		echo 'Done writing', PHP_EOL;
		fclose($fp);
		echo 'Closed File', PHP_EOL;
	}

	private function getRecord($data)
	{
		$loan_type = $data['loan_type'];
		unset($data['loan_type']);
		switch($loan_type)
		{
			case 'gold':
				return new GoldRecord($data);
				break;

			default:
			case 'classic':
				return new ClassicRecord($data);
				break;
		}
	}
	
	private function getApps()
	{
		$start_date = date("Y-m-d", strtotime("yesterday"));
		$end_date = date("Y-m-d");
		$sql = "
select
  lt.name_short as loan_type,
  ap.name_first,
  ap.name_last,
  ap.name_middle,
  ap.name_suffix,
  ap.street,
  ap.unit,
  ap.phone_work,
  ap.phone_home,
  ap.phone_cell,
  if(trim(ap.phone_cell) <> NULL, 'O', NULL) as other_phone_location,
  ap.city,
  ap.state,
  ap.zip_code,
  date_format(ap.dob, '%m%d%Y') as dob,
  ap.ssn,
  (case
		when ap.tenancy_type = 'own' then 'O'
		when ap.tenancy_type = 'rent' then 'R'
		else 'Z'
   end) as tenancy_type,
   unix_timestamp(ap.date_application_status_set) as approved_date,
  bd.billing_date,
  if(d.has_checking = 'yes', 'Y', 'N') as has_checking,
  if(d.opt_in = 'yes', 'Y', 'N') as opt_in
from application ap
inner join loan_type lt on ap.loan_type_id = lt.loan_type_id
inner join application_status_flat asf on (asf.application_status_id = ap.application_status_id)
inner join demographics d on (d.application_id = ap.application_id)
left join billing_date bd on (bd.approved_date = date(ap.date_application_status_set))
where ap.date_application_status_set between '{$start_date}' and '{$end_date}'
and  asf.level0_name = 'Approved'

		/*
		fico score,
		approval_type
		has_savings,
		*/
		";

		/*
		return array(0 => array('loan_type' => 'gold',
						 		'name_first' => 'Peter',
								'name_last' => 'Pan',
								'name_middle' => NULL,
								'name_suffix' => '',
								'street' => '1001 Apt Dr',
								'unit' => '',
								'phone_work' => '',
								'fico_score' => '',
								'phone_home' => '9324342342',
								'other_phone_location' => 'O',
								'phone_cell' => '4044569856',
								'approved_date' => '1182322800',
								'city' => 'Atlanta',
								'approval_type' => '3',
								'state' => 'GA',
								'zip_code' => '30305',
								'bill_date' => '08',
								'dob' => '01011942',
								'ssn' => '456607059',
								'tenancy_type' => 'O',
								'has_checking' => 'y',
								'has_savings' => 'y',
								'opt_in' => 'y'
								));
		*/
	}
	
   	private function Send_Email($mail_id, $address, $tokens)
	{
				/** for OLE
				 * 'email_primary' => $address,
				 * 'email_primary_name' => 'eCash Support',
				 * 'from' => $this->from_address,
				 * 'site_name' => $this->ecash_address,
				 */
				$data = array('support_email' => $this->from_address,
							  'rules' => $list,
							  'ecash_address' => $this->ecash_address
						 	);

				// send the email
				$this->Send_Email('CERTEGY_BILL_DATE_RENEW', $address, $data);
				
		//echo "sending email: {$mail_id}, to {$address}\n";
		try
		{
			$response = eCash_Mail::sendMessage($mail_id, $address, $tokens);
		}
		catch( Exception $e )
		{
			$this->log->Write(print_r($e, TRUE) . "Could not connect to send email, {$mail_id} not sent" , LOG_ERR);
		}

		// log if we don't get a response
		if (!$response)
		{
			$this->log->Write("Bad response from eCash_Mail::sendMessage - email {$mail_id} not sent ", LOG_ERR);
		}
		
		//echo "recieved response {$response}\n";
	}
}


function CFC_Batch($server)
{
	//throw that shitty server var away
	//echo __FUNCTION__ . " called\n";
	CFC_Batch::Call();
}

// uncomment to run from the CLI:
CFC_Batch::main($_SERVER['argv']);

?>