<?php

  /**
   *
   *
   */

//make this something retarded so it won't likely interfere with
//future defines in www/config.php
if(!defined('MY_CRON_DIR'))
	define('MY_CRON_DIR', dirname(__FILE__) . '/');

require_once (LIB_DIR.'Mail.class.php');
require_once 'libolution/DB/MySQLConfig.1.php';
require_once 'libolution/Mail/Trendex.1.php';
require_once LIB_DIR . 'business_rules.class.php';
require_once 'fbod_batch/GoldRecord.php';
require_once 'fbod_batch/ClassicRecord.php';
require_once (WWW_DIR.'config.php');
require_once (LIB_DIR."mysqli.1e.php");
require_once('logsimple.php');
require_once(SERVER_CODE_DIR . "stat.class.php");
class FBOD_Batch
{
	//this can be replaced once all other
	//nightly crons are using libolution/PDO
	private $db;
	private $mysqli;
	
	public function __construct()
	{
		$this->mysqli = MySQLi_1e::Get_Instance();
		$this->log = get_log('certegy');
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
		exit(FBOD_Batch::Call());
	}

	public static function Call()
	{		
		$batch = new FBOD_Batch();
		return($batch->Run());
	}

	private function Run()
	{

		//Get the applications for the batch
		$apps = $this->getApps();
		
		if(count($apps) > 0)
		{
			$this->log->Write("Found " . count($apps) . " to process.");

			//build batch file
			$file = $this->buildBatch($apps);

			//send batch
			$batch_status = $this->sendBatch($file);
			if (strtolower($batch_status) == 'sent')
			{	
				
				//mark applications as approved/sent & hit certegy_sent stat
				$this->updateApps($apps);
				
				//E-mail Certegy to inform them that the new batch is available
				$mail = eCash_Mail::FBOD_CERTEGY_BATCH();
			}
			
			$this->log->Write("Batch Status: {$batch_status}");
			
			//write batch to table
			$batch_id = $this->insertBatch($file,$batch_status);
		}
		else
		{
			$this->log->Write("There were no applications to process.");
		}
		
		//Check to see if we'll be needing new Certegy Bill dates any time soon.
		$this->Verify_Bill_Dates();
	}

	/**
	 * This isn't used because I HATE IT!
	 *
	 */
	private function connectDB()
	{
		$this->db = ECash::getMasterDb();
		
	//	$this->dbconn = $this->db->getConnection();
	}

	/**
	 * Inserts batch file's contents in to the certegy_batch table, so that we have a record of old batches sent.
	 *
	 * @param unknown_type $file
	 * @return unknown
	 */
	private function insertBatch($file,$status)
	{
		if(filesize($file))
		{
		$fp = fopen($file, 'r');
		$batch_data = fread($fp,filesize($file));
		fclose($fp);
		
		$compressed_batch_data = pack('L', strlen($batch_data)) . gzcompress($batch_data);

		// This isn't necessary with a prepared query
		//$batch_data = Mqgcp_Escape($batch_data);

		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
		INSERT INTO certegy_batch 
			(batch_type, batch_status, batch_data)
		VALUES
			('Send',?, ?)
		";
		
		$stmt = $this->mysqli->Prepare($query);
		$stmt->bind_param('ss',$status, $compressed_batch_data);
		
		$stmt->execute();
		$id = $this->mysqli->Insert_Id();
		
		$stmt->close();		

		return $id;	
		}	

	}
	
	private function updateApps($rows)
	{
		$mysqli = $this->mysqli;
		
		foreach ($rows as $app) 
		{
			$app_id = $app['application_id'];
			Update_Status(null,$app_id,'active::servicing::customer::*root');

			//Hit certegy_sent Stats
			$stat = new Stat();
			$stat->Setup_Stat($app_id);
			$stat->Hit_Stat('certegy_sent');

		}
	}
	private function sendBatch($file)
	{
		require_once(LIB_DIR . "Achtransport/achtransport_sftp.class.php");
		require_once(LIB_DIR . "Achtransport/achtransport_https.class.php");
		$batch_login = ECash::getConfig()->CERTEGY_BATCH_LOGIN;
		$batch_pass  = ECash::getConfig()->CERTEGY_BATCH_PASS;

		try 
		{
			$transport_type   = ECash::getConfig()->CERTEGY_TRANSPORT_TYPE;
			$transport_url    = ECash::getConfig()->CERTEGY_BATCH_URL;
			$transport_server = ECash::getConfig()->CERTEGY_BATCH_SERVER;
			$transport_port   = ECash::getConfig()->CERTEGY_BATCH_SERVER_PORT;

			$transport = ACHTransport::CreateTransport($transport_type, $transport_server, $batch_login, $batch_pass, $transport_port);

			$filename =  ECash::getConfig()->CERTEGY_BATCH_FILE;

			// If we're using SFTP, we need to specify the whole path including a filename
			if($transport_type === 'SFTP') $remote_filename = "{$transport_url}/{$filename}";
			else $remote_filename = $transport_url;

			$batch_success = ECash::getTransport()->sendBatch($file, $remote_filename, $batch_response);
		} 
		catch (Exception $e) 
		{
			$this->log->write($e->getMessage());
			$batch_success = false;
		}

		if ($batch_success) 
		{
			$batch_status = 'sent';
		} 
		else 
		{
			$this->log->write("ACH file send: No response from '" . $remote_filename . "'.", LOG_ERR);
			$batch_status = 'failed';
		}

		echo "BATCH STATUS = {$batch_status}\n";
		return $batch_status;

	}	
	
	private function buildBatch($rows)
	{
		$file = '/tmp/fbod_batch.txt';
		$fp = fopen($file, 'w');
		for($i = 0; $i < count($rows); $i++)
		{
			$record = $this->getRecord($rows[$i]);
			//echo 'record: ', print_r($record, TRUE), PHP_EOL;
			foreach($record as $element)
			{
				fwrite($fp, $element);
				//echo 'Writing ', $record->key(), PHP_EOL;
			}
			fwrite($fp, "\r\n");
		}
		//echo 'Done writing', PHP_EOL;
		fclose($fp);
		//echo 'Closed File', PHP_EOL;
		return $file;
	}

	private function getRecord($data)
	{
		$loan_type = $data['loan_type'];
		unset($data['loan_type']);
		switch($loan_type)
		{
			case 'gold':
			default:
				return new GoldRecord($data);
				break;
		}
	}
	
	private function getApps()
	{
		$date_modifier = '';

		/**
		 * If the batch is run after 5PM (which it normally is) then
		 * we want to use the next billing date.  I'm using the check against
		 * 4pm to be safe since the batch will probably not get
		 */
		if(date('H') >= 16)
		{
			$date_modifier = "+1";
		}
		
		$sql = "
			-- eCash 3.5 : File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
		SELECT
				lt.name_short as loan_type,
				ap.application_id,
				tr.transaction_register_id,
				tr.transaction_status,
				tt.name as trans_type,
				tt.name_short as trans_type_short,
				tt.pending_period,
				ap.name_first,
				ap.name_last,
				ap.name_middle,
				ap.name_suffix,
				ap.street,
				ap.unit,
				ap.phone_work,
				ap.phone_home,
				ap.phone_cell,
				ap.bank_aba,
				ap.bank_account,
				if(trim(ap.phone_cell) <> NULL, 'O', NULL) as other_phone_location,
				ap.city,
				ap.state,
				ap.zip_code,
				ci.promo_id,
				ci.promo_sub_code,
				date_format(ap.dob, '%m%d%Y') as dob,
				ap.ssn,
				uncompress(bi.received_package) as idv,
				(case
					when ap.tenancy_type = 'own' then 'O'
					when ap.tenancy_type = 'rent' then 'R'
					else 'Z'
					end) as tenancy_type,
				unix_timestamp(ap.date_application_status_set) as approved_date,
				if(d.has_checking = 'yes', 'Y', 'N') as has_checking,
				--
				-- Per GF FBOD LIVE 4584 this field should be blank
				-- if(d.opt_in = 'yes', 'Y', 'N') as opt_in,
				--
				(SELECT
				 	billing_date 
				 FROM 
				 	billing_date 
				 WHERE
				 	approved_date BETWEEN CURDATE(){$date_modifier} AND DATE_ADD(CURDATE(){$date_modifier}, INTERVAL 1 WEEK) ORDER BY approved_date LIMIT 1
				 ) as billing_date
			FROM    application ap
			INNER JOIN loan_type lt on ap.loan_type_id = lt.loan_type_id
			INNER JOIN application_status_flat asf on (asf.application_status_id = ap.application_status_id)
			INNER JOIN demographics d on (d.application_id = ap.application_id)
			INNER JOIN transaction_register tr ON (tr.application_id = ap.application_id)
			INNER JOIN transaction_type tt ON (tr.transaction_type_id = tt.transaction_type_id)
			INNER JOIN bureau_inquiry bi ON (bi.application_id = ap.application_id)
			INNER JOIN campaign_info ci ON (ci.application_id = ap.application_id)
			WHERE 	tr.transaction_status = 'complete' 
			AND     tt.name_short = 'payment_processing_fee'
			AND     ( asf.level0 = 'approved' AND asf.level1 = 'servicing' AND asf.level2 = 'customer' and asf.level3 = '*root')
				";
		
		$mysqli = $this->mysqli;
		$results = $mysqli->Query($sql);
		$search_results = array();

		while($row = $results->Fetch_Array_Row(MYSQLI_ASSOC))
		{
			//get the vantage score from the bureau inquiry response.
			$mini = new MiniXMLDoc();
			$mini->fromString(strtoupper($row['idv']));
			$idv_array = $mini->toArray();
			$fico = $idv_array['DATAXRESPONSE']['RESPONSE']['DETAIL']['TRANSUNIONSEGMENT']['VANTAGESCORE'];
			$row['fico_score'] = $fico?$fico:'';

			$search_results[] = $row;
		}

			/*
			no data for: 
			fico score,  --will not be used.
			approval_type -- blank
			has_savings, -- not used
			*/
	
			/*Justin's fake data:
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
			
		return $search_results;
	}

	/**
	 * Bill_Date_Warning
	 * Send out error notification to warn that we're running out of bill dates from certegy
	 * And we need to get some new ones, otherwise our Certegy batches will e'splode!
	 *
	 */
   	private function Bill_Date_Warning()
	{
		$recipients = (EXECUTION_MODE !== 'LOCAL') ? ECash::getConfig()->ECASH_NOTIFICATION_ERROR_RECIPIENTS : '';
		$body = "WE ARE RUNNING OUT OF CERTEGY BILL DATES!  PLEASE CONTACT CERTEGY AND GET SOME MORE!!!!!!!!!!!!!!!!!!!!!!1!!!!!!!";
		$subject = "Certegy batch warning:  Running out of Certegy Bill Dates!";
				// send the email
		$result = eCash_Mail::sendExceptionMessage($recipients,$body,$subject);
		
	}
	
	private function Verify_Bill_Dates()
	{
		//check and see if we are running low on bill dates using whatever criteria we decide
		$query = '
				SELECT 
					count(*) as count
				FROM 
					billing_date 
				WHERE
					approved_date > DATE_ADD(NOW(), INTERVAL 2 MONTH)
				';
		$results = $this->mysqli->Query($query);
		
		
		
		$row = $results->Fetch_Array_Row(MYSQLI_ASSOC);
		$count = $row['count'];
		if ($count==0) 
		{
			//if we are running low, fire off an E-mail stating such.
			$this->Bill_Date_Warning();	
		}
		
	}
	
}


function FBOD_Batch($server)
{
	//throw that shitty server var away
	//echo __FUNCTION__ . " called\n";
	FBOD_Batch::Call();
}

// uncomment to run from the CLI:
FBOD_Batch::main($_SERVER['argv']);

?>
