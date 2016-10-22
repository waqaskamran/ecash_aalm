<?php
include_once(LIB_DIR . "status_utility.class.php");

/**
 * This class is for use with the Landmark ACH system which is currently used
 * for the First Bank of Delaware (FBOD) project.  The source uses a combination
 * of data based on the original ACH class and the QuickChecks class used within
 * eCash and the mtf / DcasACH code used by Partner Weekly / Mediatel.
 *
 * The class is extremely purpose built for FBOD and uses the LandmarkItemData
 * class to create and store all of the events and transactions in the database.
 *
 * The class allows you to post transactions to the Landmark HTTPS server and Returns
 * can also be handled using SFTP.
 *
 * @author Brian Ronald <brian.ronald@sellingsource.com>
 * @package Landmark_ACH
 */

class Landmark_ACH
{
	/**
	 * Company ID used for Internal use
	 *
	 * @var integer
	 */
	private $company_id;

	/**
	 * Company short name
	 *
	 * @var string
	 */
	private $company_short;

	/**
	 * Business Day used for fetching batch data
	 * and for individual transaction records'
	 * batch date.
	 *
	 * @var string 'YYYY-mm-dd'
	 */
	private $business_day;

	/**
	 * Counter for number of batch records
	 *
	 * @var integer
	 */
	private $batch_record_count = 0;

	/**
	 * Array used to store errors within the batch
	 *
	 * @var array
	 */
	private $batch_errors;

	/**
	 * DataBase Interface
	 *
	 * @var DB_Database_1
	 */
	private $db;

	/**
	 * AppLog for Landmark ACH Class
	 *
	 * @var Applog_1 object
	 */
	private $log;

	// Miscellaneous type definitions
	const FS                    = ',';	// Field Seperator
	const RS                    = "\n";	// Record Seperator
	const BLANK                 = '';	// Blank

	// Data validation expressions
	const TYPE_B 		= '%^ *$%';				// Blank / Unknown?
	const TYPE_O 		= '%^.*$%';				// Other?  Optional?
	const TYPE_A 		= '%^[A-z.]*$%'; 		// Alphabetic
	const TYPE_AS 		= '%^[A-z. ]*$%'; 		// Alphabetic
	const TYPE_NAME 	= '%^[A-z\- ]*%';		// Alphabetic
	const TYPE_N 		= '%^[0-9]*$%';			// Numeric (non-float)
	const TYPE_NF 		= '%^[0-9.]*$%';		// Numeric (float)
	const TYPE_AN 		= '%^[A-z0-9]*$%';		// Alphanumeric
	const TYPE_ANS 		= '%^[A-z0-9 #.-]*$%';	// Alphanumeric (with spaces)

	// Record Type ID's
	const BATCH_RECORD = 1;

	// Batch Size - How many transactions to create at a time.
	const BATCH_SIZE = 500;

	// Defines the position in the array for each type of data
	const REC_SIZE              = 0;
	const REC_TYPE              = 1;
	const REC_REQUIRED          = 2;
	const REC_VALUE             = 3;

	// Application Status Chain for Denied Applications
	const DENIED_STATUS			= 'denied::applicant::*root';

	/**
	 * Record definitions
	 * These are based on the LandMark Check Flat File Specs - CA & RA
	 * Currently only populating records 1 - 12
	 * @var array
	 *       field #          size, type,                   default value			description
	 */
	private $record_definitions = array (
		Landmark_ACH::BATCH_RECORD       => array
				( 1  => array(2,   Landmark_ACH::TYPE_A,	TRUE,  'CA'), 					// Record Key (CA) (Required)
		          2  => array(9,   Landmark_ACH::TYPE_N,	TRUE,  Landmark_ACH::BLANK),	// Bank ABA (Required)
		          3  => array(16,  Landmark_ACH::TYPE_N,	TRUE,  Landmark_ACH::BLANK),	// Account Number (Required)
		          4  => array(8,   Landmark_ACH::TYPE_N,	TRUE,  Landmark_ACH::BLANK),	// Check Number (Required)
		          5  => array(9,   Landmark_ACH::TYPE_NF,	TRUE,  Landmark_ACH::BLANK),	// Amount // Dollars and cents (Required)
		          6  => array(50,  Landmark_ACH::TYPE_N,	FALSE, Landmark_ACH::BLANK),	// Invoice Number (Optional)
		          7  => array(32,  Landmark_ACH::TYPE_NAME,	TRUE,  Landmark_ACH::BLANK),	// Name (Required)
		          8  => array(50,  Landmark_ACH::TYPE_ANS,	TRUE,  Landmark_ACH::BLANK),	// Address (Required)
		          9  => array(32,  Landmark_ACH::TYPE_AS,	FALSE, Landmark_ACH::BLANK),	// City (Optional)
		          10 => array(2,   Landmark_ACH::TYPE_AS,	FALSE, Landmark_ACH::BLANK),	// State (Optional)
		          11 => array(10,  Landmark_ACH::TYPE_N,	FALSE, Landmark_ACH::BLANK),	// Zip (Optional)
		          12 => array(15,  Landmark_ACH::TYPE_N,	FALSE, Landmark_ACH::BLANK),	// Phone Number (Optional)
		          13 => array(24,  Landmark_ACH::TYPE_AN,	FALSE, Landmark_ACH::BLANK),	// Driver License Number (Optional)
		          14 => array(2,   Landmark_ACH::TYPE_A,	FALSE, Landmark_ACH::BLANK),	// Driver License State (Optional)
		          15 => array(1,   Landmark_ACH::TYPE_N,	FALSE, Landmark_ACH::BLANK),	// Third Party Check (1 = Yes, 0 = No) (Optional)
		          16 => array(15,  Landmark_ACH::TYPE_B,	FALSE, Landmark_ACH::BLANK),	// CustTraceCode (Optional)
		          17 => array(64,  Landmark_ACH::TYPE_B,	FALSE, Landmark_ACH::BLANK),	// ImageName (Optional)
		          18 => array(64,  Landmark_ACH::TYPE_B,	FALSE, Landmark_ACH::BLANK),	// BackImageName (Optional)
		          19 => array(6,   Landmark_ACH::TYPE_B,	FALSE, Landmark_ACH::BLANK),	// Type of transaction (Optional)
		          20 => array(32,  Landmark_ACH::TYPE_B,	FALSE, Landmark_ACH::BLANK),	// Internal Account Number (Optional)
		          21 => array(8,   Landmark_ACH::TYPE_B,	FALSE, Landmark_ACH::BLANK),	// Type of Account (Checking / Savings) (Optional)
		          22 => array(0,   Landmark_ACH::TYPE_B,	FALSE, Landmark_ACH::BLANK),	// Entry Class Code (ECC) (Optional)
		          23 => array(0,   Landmark_ACH::TYPE_B,	FALSE, Landmark_ACH::BLANK),	// DepositTicketABA (Optional)
		          24 => array(0,   Landmark_ACH::TYPE_B,	FALSE, Landmark_ACH::BLANK),	// DepositTicketAccountNumber (Optional)
		          25 => array(0,   Landmark_ACH::TYPE_B,	FALSE, Landmark_ACH::BLANK),	// DepositTicketDate (Optional)
		          26 => array(0,   Landmark_ACH::TYPE_B,	FALSE, Landmark_ACH::BLANK),	// DepositTicketImageName (Optional)
		          27 => array(0,   Landmark_ACH::TYPE_B,	FALSE, Landmark_ACH::BLANK),	// Automated Funds Transfer Transaction Codes (CPA Code) (Optional)
		          28 => array(0,   Landmark_ACH::TYPE_B,	FALSE, Landmark_ACH::BLANK),	// MICR (Optional)
		          29 => array(0,   Landmark_ACH::TYPE_B,	FALSE, Landmark_ACH::BLANK),	// DirtyMICR (Optional)
		          30 => array(0,   Landmark_ACH::TYPE_B,	FALSE, Landmark_ACH::BLANK),	// Endorsement (Optional)
		          31 => array(0,   Landmark_ACH::TYPE_B,	FALSE, Landmark_ACH::BLANK)		// IDImageName (Optional)
		          ));

	/**
	 * Proper names of the record types
	 * @var array
	 * @access    private
	 */
	private $reverse_map = array( Landmark_ACH::BATCH_RECORD  => '1');

	/**
	 * DCAS Normal Return File Format
	 *
	 * The file is a CSV Formatted file, and these are the
	 * individual fields.
	 *
	 * @var array
	 */
	private static $return_file_format = array(
			'record_key',		// Record Key
			'bank_aba',			// Customer Bank ABA
			'bank_account',		// Customer Account Number
			'lm_ach_id', 		// Check Number
			'amount',			// Check Amount
			'return_code',		// Return Code
			'return_reason',	// Return Reason
			'confirmation_code',// Confirmation Code
			'invoice_number',	// Invoice Number (optional)
			'late_return_flag'	// Late Return Flag (optional)
			);


	function __construct($company_id, $company_short, $business_day = NULL)
	{
		$this->db = ECash::getMasterDb();
		$this->log = get_log('landmark_ach');
		//$this->log = new DebugLog(APPLOG_SUBDIRECTORY.'/landmark_ach', APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT);

		if(ctype_digit((string)$company_id))
		{
			$this->company_id = $company_id;
		}
		else
		{
			throw new Exception('Company ID is invalid or not supplied!');
		}

		if(empty($business_day))
		{
			$this->business_day = date('Y-m-d');
		}
		else
		{
			$this->business_day = $business_day;
		}

		if(! empty($company_short))
		{
			$this->company_short = strtoupper($company_short);
		}
		else
		{
			throw new Exception('Company short name is invalid or not supplied!');
		}
	}

	/**
	 * Builds 1 record for the qc file
	 * @throws General_Exception
	 * @param  integer $record_type
	 * @param  array   $data  1 row of data
	 * @return string  1 record
	 * @access private
	 */
	private function Build_Record( $record_type, $data )
	{
		if( ! isset($this->record_definitions[$record_type]) )
			throw new Exception( "Invalid record type: '{$record_type}'." );

		$record = "";

		foreach( $this->record_definitions[$record_type] as $field => $attributes )
		{
			if( isset($data[$field]) )
			{
				$record .= $this->Set_Field_Content($record_type, $field, $data[$field]);
			}
			else
			{
				$record .= $this->Set_Field_Content($record_type, $field, $attributes[self::REC_VALUE]);
			}
		}

		$record .= self::RS;

		return $record;
	}

	/**
	 * Checks data validity and pads data as necessary
	 * @throws Field_Validation_Exception
	 * @param  integer $record_type
	 * @param  integer $field_number
	 * @param  string  $value
	 * @return string  Formatted field
	 * @access private
	 */
	private function Set_Field_Content( $record_type, $field_number, $value)
	{
		$field_def = $this->record_definitions[$record_type][$field_number];

		// Strip commas, slashes, and quotes
		$value = str_replace(',', ' ', $value);
		$value = str_replace('\\', '', $value);
		$value = str_replace('\'', '', $value);

		if($field_def[self::REC_REQUIRED] === TRUE && empty($value))
		{
			throw new Field_Validation_Exception("Required field empty for field {$field_number}, of record type " . $this->reverse_map[$record_type] . '.');
		}

		if( ! preg_match($field_def[self::REC_TYPE], $value) )
		{
			throw new Field_Validation_Exception("Invalid data for field {$field_number} [$value], of record type " . $this->reverse_map[$record_type] . '.');
		}

		if( strlen($value) > $field_def[self::REC_SIZE] )
		{
			throw new Field_Validation_Exception("Invalid field size for field {$field_number}, of record type " . $this->reverse_map[$record_type] . '.');
		}

		// String Padding not required for this record type
		//$value = str_pad( $value, $field_def[self::REC_SIZE], ' ', STR_PAD_RIGHT );
		$value .= self::FS;

		return $value;
	}

	/**
	 * This is the publicly exposed method used to create and send the batches
	 *
	 * @return boolean
	 */
	public function runBatch()
	{
		$this->batch_record_count = 0;
		$this->batch_errors = array();

		/**
		 * Create the batch and transactions, update statuses
		 */
		$status = Check_Process_State($this->db, $this->company_id, 'landmark_create_batch', $this->business_day);

		if(($status === false) || ($status != 'completed'))
		{
			$batch_data = $this->createTransactions();
			$size = count($batch_data);

			$this->log->Write("We have $size items in the returned array");

		}
		else if($status === 'completed')
		{
			// We don't want to continue.  As it sits, the process will not gracefully restart.
			$this->log->Write("[{$this->company_short}] LM: Processing has already run for today!");
			return false;
		}

		/**
		 * Generate the Post Data and send the batch
		 */
		$status = Check_Process_State($this->db, $this->company_id, 'landmark_send_batch', $this->business_day);
		if(($status === false) || ($status != 'completed'))
		{
			$pid = Set_Process_Status($this->db, $this->company_id, 'landmark_send_batch', 'started');

			try
			{
				// Format the Post data
				$batch_data = $this->formatPostData($batch_data);

				$error_count = count($this->batch_errors);
				$this->log->Write("There were $error_count errors with field validation.");

				// Post the data
				$this->sendBatch($batch_data);

				Set_Process_Status($this->db, $this->company_id, 'landmark_send_batch', 'completed', null, $pid);
			}
			catch (Exception $e)
			{
				$this->log->Write("LM: Error sending batch!");
				$this->log->Write("Exception: " . $e->getMessage());
				$this->log->Write("Trace: \n" . $e->getTraceAsString());

				Set_Process_Status($this->db, $this->company_id, 'landmark_send_batch', 'failed', null, $pid);
			}
		}
		else if($status === 'completed')
		{
			// We don't want to continue.  As it sits, the process will not gracefully restart.
			$this->log->Write("[{$this->company_short}] LM: Batch has already been sent today!");
			return false;
		}

		if(count($this->batch_errors) > 0)
		{
			$this->log->Write("Error Processing the following applications:");
			foreach($this->batch_errors as $error)
			{
				$this->log->Write("[App: {$error['application_id']}] - {$error['reason']}");
			}
		}
	}

	/**
	 * Creates the Events, Transactions, and ACH entries
	 *
	 * @return unknown
	 */
	private function createTransactions()
	{
		$pid = Set_Process_Status($this->db, $this->company_id, 'landmark_create_batch', 'started');

		$batch_data = $this->fetchBatchData();

		// This is the data we will return
		$return_data = array();

		if($batch_data === FALSE)
		{
			$this->log->Write("[{$this->company_short}] LM: No accounts were available for batch");
			Set_Process_Status($this->db, $this->company_id, 'landmark_create_batch', 'completed', null, $pid);
			return FALSE;
		}

		$number_of_batches = ceil(count($batch_data) / self::BATCH_SIZE);

		/* DEBUG */
		$this->log->Write("Found " . count($batch_data) . " applications, will run {$number_of_batches} batches of at most " . self::BATCH_SIZE ." apps");

		$batch_count = 0;

		while (count($batch_data) > 0)
		{
			$batch_count++;
			$file_data = array_splice($batch_data, 0, self::BATCH_SIZE);

			LandmarkItemData::init($this->company_id);

			/**
			 * Create the Asssessment Events
			 */
			$this->log->Write("[{$this->company_short}] LM Progress: Inserting Assessment Events.", LOG_INFO);
			LandmarkItemData::insertAssessmentEventSchedules($this->db, $file_data, date('Y-m-d'), 'assessment');
			$this->log->Write("[{$this->company_short}] LM Progress: Creating Transaction Register Entries.", LOG_INFO);
			LandmarkItemData::insertAssessmentTransactionRegisters($this->db, $file_data);
			$this->log->Write("[{$this->company_short}] LM Progress: Updating Event Amounts.", LOG_INFO);
			LandmarkItemData::insertAssessmentEventAmounts($this->db, $file_data);

			/**
			 * Create the Payment Events and the ACH Items
			 */
			$this->log->Write("[{$this->company_short}] LM Progress: Inserting Payment Events.", LOG_INFO);
			LandmarkItemData::insertPaymentEventSchedules($this->db, $file_data, date('Y-m-d'), 'payment');
			$this->log->Write("[{$this->company_short}] LM Progress: Creating ACH Entries.", LOG_INFO);
			LandmarkItemData::insertACHItems($this->db, $file_data, $ecld_file_id);
			$this->log->Write("[{$this->company_short}] LM Progress: Creating Transaction Register Entries.", LOG_INFO);
			LandmarkItemData::insertPaymentTransactionRegisters($this->db, $file_data);
			$this->log->Write("[{$this->company_short}] LM Progress: Updating Event Amounts.", LOG_INFO);
			LandmarkItemData::insertPaymentEventAmounts($this->db, $file_data);

			$this->log->Write("[{$this->company_short}] LM Progress: Moving Accounts to Pre-Fund.", LOG_INFO);
			LandmarkItemData::UpdateStatuses($file_data);

			$return_data = array_merge($return_data, $file_data);
		}

		$this->log->Write("Run $batch_count batches");

		Set_Process_Status($this->db, $this->company_id, 'landmark_create_batch', 'completed', null, $pid);

		return $return_data;
	}

	/**
	 * Takes an array of LandmarkItemData objects, performs some basic field validation
	 * and generates the data used for posting to Landmark.
	 *
	 * @param array $batch_data
	 * @return array
	 */
	private function formatPostData($batch_data)
	{
		/**
		 * Credentials required in the POST
		 */
		$company  = ECash::getConfig()->LANDMARK_ACH_BATCH_COMPANY;
		$username = ECash::getConfig()->LANDMARK_ACH_BATCH_USERNAME;
		$password = ECash::getConfig()->LANDMARK_ACH_BATCH_PASSWORD;

		/**
		 * Create Batch Records
		 */
		$this->log->Write("LM: Building Post data.");

		foreach($batch_data as $transaction)
		{
			/** @var $transaction LandmarkItemData */
			// Build Record
			try {
				$batch_record = $this->Build_Record( self::BATCH_RECORD,
				array( 	 2 => $transaction->getBankAba(),
						 3 => $transaction->getBankAccount(),
						 4 => $transaction->getACHID(),
						 5 => $transaction->getAmount(),
						 7 => $transaction->getFullName(),
						 8 => $transaction->getAddress(),
						 9 => $transaction->getCity(),
						10 => $transaction->getState(),
						11 => $transaction->getZip(),
						12 => $transaction->getHomePhone()));

				$data = array();
				$data['Company']						= $company;
				$data['Username']						= $username;
				$data['Password']						= $password;
				$data['iData'] 							= $batch_record;

				$transaction->setBatchRecord($data);

			}
			catch (Field_Validation_Exception $e)
			{
				$error_message = $e->getMessage();
				$this->log->Write("LM: Failed validation. Error message: $error_message");
				$this->batch_errors[] = array('application_id' => $transaction->getApplicationID(), 'reason' => $error_message);
				// Mark the transaction as failed
				$this->updateRecord($transaction->getACHID(), 'returned', '', $error_message);
				$this->updateTransactionRegister($transaction->getPaymentTransactionRegisterId(), NULL, 'failed');
				Update_Status(NULL, $transaction->getApplicationID(), self::DENIED_STATUS, NULL, NULL, FALSE);
			}
		}

		return $batch_data;
	}

	/**
	 * Retrieves the batch data
	 *
	 * @return array of LandmarkItemData objects or false
	 */
	private function fetchBatchData()
	{
		$batch_date		= $this->business_day;
		$batch_data 	= array();
		$statuses		= array();
		$statuses[]		= Status_Utility::Get_Status_ID_By_Chain('queued::underwriting::applicant::*root');
		$statuses[]		= Status_Utility::Get_Status_ID_By_Chain('dequeued::underwriting::applicant::*root');

		$query = "
			-- eCash 3.5 : File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
				SELECT
                        ap.application_id,
                        0 as principal,
                        0 as service_charge,
                        4.95 as fee,
                        4.95 as amount,
						ap.bank_aba,
						ap.bank_account,
                        ap.bank_account_type,
                        ap.bank_name,
						CONCAT(ap.name_first, ' ', ap.name_last) AS full_name,
						ap.name_first,
						ap.name_last,
                        IF(ap.unit != '', CONCAT(ap.street, ' #', ap.unit), ap.street) as address,
                        ap.city,
                        ap.state,
                        ap.zip_code as zip,
                        ap.phone_home as homephone,
                        ap.legal_id_number,
                        ap.legal_id_state,
                        (
                            SELECT count(es.event_schedule_id)
                            FROM event_schedule AS es
                            WHERE es.application_id = ap.application_id
                        ) as num_events
				FROM application AS ap
				WHERE
                    ap.application_status_id IN (".implode(",",$statuses).")
                HAVING
                    num_events = 0 ";

		$deposit_result = $this->db->query($query);
		$deposit_items = LandmarkItemData::loadLandmarkItemsFromResult($deposit_result);

		if (count($deposit_items)) {
			return $deposit_items;
		} else {
			return false;
		}

	}

	/**
	 * Updates a Landmark ACH record in the landmark_ach table
	 *
	 * @param integer $lm_ach_id
	 * @param string $status
	 * @param string $return_code
	 * @param string $return_reason
	 * @param string $confirmation_number
	 * @return boolean TRUE on Success, FALSE on Failure
	 */
	private function updateRecord($lm_ach_id, $status, $return_code = NULL, $return_reason = NULL, $confirmation_number = NULL)
	{
		if(empty($lm_ach_id))
		{
			throw new Exception ("ACH ID EMPTY!  {$lm_ach_id}");
		}

		if(empty($status))
		{
			// Must supply status!
			return FALSE;
		}

		// prepare args
		$args['status'] = $status;
		if ($return_reason) $args['return_reason'] = $return_reason;
		if ($return_code) $args['return_code'] = $return_code;
		if ($confirmation_number) $args['confirmation_number'] = $confirmation_number;
		$args[] = $lm_ach_id;

		$query = "
			UPDATE landmark_ach
			SET ".implode(' = ?, ', array_keys($args))." = ?
			WHERE lm_ach_id = ?
		";
		$st = $this->db->queryPrepared($query, array_values($args));

		return ($st->rowCount() > 0);
	}

	/**
	 * Update a transaction_register row's status using either it's
	 * transaction_register id or it's lm_ach_id
	 *
	 * @param integer $transaction_register_id
	 * @param integer $lm_ach_id
	 * @param string $status
	 * @return boolean TRUE on success or FALSE on failure
	 */
	private function updateTransactionRegister($transaction_register_id = NULL, $lm_ach_id = NULL, $status)
	{
		if(empty($transaction_register_id) && empty($lm_ach_id))
		{
			throw new Exception ("Missing transaction_register_id or lm_ach_id!");
		}

		$args = array($status);

		if (!empty($transaction_register_id))
		{
			$where_clause = "WHERE transaction_register_id = ?";
			$args[] = $transaction_register_id;
		}
		else
		{
			$where_clause = "WHERE lm_ach_id = {$lm_ach_id}";
			$args[] = $lm_ach_id;
		}

		$query = "
			UPDATE transaction_register
			SET transaction_status = ?
			$where_clause
		";
		$st = $this->db->queryPrepared($query, $args);

		return ($st->rowCount() > 0);
	}

	/**
	 * Updates transactions that have gone past their pending period and marks
	 * them as successful.  This is largely stolen from the ACH class in
	 * eCash with some obvious alterations.
	 *
	 * @param string $business_day
	 * @return boolean
	 */
	public function deemSuccessful($business_day)
	{
		$this->business_day = $business_day;

		// If we are already inside of a transaction, bail out with error
		if ($this->db->getInTransaction())
		{
			throw new General_Exception("LM: Cannot be invoked from within a transaction; this class manages its own transactions.");
		}

		$pdc = new Pay_Date_Calc_3(Fetch_Holiday_List());

		// If it's a weekend or holiday, just don't run this today.
		$stamp = strtotime($business_day);
		if ($pdc->Is_Weekend($stamp) || ($pdc->Is_Holiday($stamp)))
		{
			return FALSE;
		}

		// We're only grabbing the pending period for this one transaction type since it's the only
		// kind we're currently using for ACH.  We really should do something to get the pending periods
		// for all landmark_ach transaction types and process them separately for future needs.
		$hold_days = $this->getPendingPeriodForTransactionType('payment_processing_fee', $this->company_id);
		$hold_expire_threshold_date = $pdc->Get_Business_Days_Backward($business_day, $hold_days);

		$query = "
			SELECT tr.application_id,
				tr.transaction_register_id,
				tr.lm_ach_id
			FROM transaction_register AS tr
				JOIN landmark_ach AS ach ON (tr.lm_ach_id = ach.lm_ach_id)
			WHERE tr.transaction_status	= 'pending'
				AND tr.company_id = ?
				AND tr.date_effective <= ?
				AND ach.status = 'batched'
			ORDER BY tr.transaction_register_id
			FOR UPDATE
		";
		$st = $this->db->queryPrepared($query, array($this->company_id, $hold_expire_threshold_date));

		try
		{
			while($row = $st->fetch(PDO::FETCH_OBJ))
			{
				$this->updateRecord($row->lm_ach_id, 'processed');
				Post_Transaction($row->application_id, $row->transaction_register_id);
			}
		}
		catch(Exception $e)
		{
			$this->log->Write("LM: Posting of \"ACH transactions deemed successful\" failed and transaction will be rolled back.", LOG_ERR);
			$this->log->Write("LM: This process must be re-run after the cause of this problem has been determined.", LOG_INFO);
			throw $e;
		}

		$this->log->Write("LM: ACH Transactions deemed successful have been posted.", LOG_INFO);

		return TRUE;
	}

	/**
	 * Finds the pending period for a transaction type using it's
	 * name_short in the database for a particular company
	 *
	 * @param string $transaction_type
	 * @param integer $company_id
	 * @return integer Number of days on Success, or False on Failure
	 */
	private function getPendingPeriodForTransactionType($transaction_type, $company_id)
	{
		$query = "
			SELECT pending_period
			FROM transaction_type
			WHERE name_short = ?
				AND company_id = ?
		";
		$period = $this->db->querySingleValue($query, array($transaction_type, $company_id));
		return $period;
	}


	/**
	 * Send the Batch via HTTPS Post
	 *
	 * This is largely stolen from the mtf_dcas_ach.php file
	 * by Partner Weekly.
	 *
	 * @param array $batch_data
	 * @todo Rewrite to use ACHTransport Class
	 */
	private function sendBatch($batch_data)
	{
		$url = ECash::getConfig()->LANDMARK_ACH_BATCH_URL;

		foreach($batch_data as $transaction)
		{
			$record = $transaction->getBatchRecord();

			if(empty($record)) continue;

			$content="";

			foreach($record as $key => $val)
			{
				$content .= $key."=".urlencode($val)."&";
			}

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

			$result = curl_exec($ch);

			$fail_transaction = FALSE;

			if (empty($result))
			{
				$error = "Curl Error(" . curl_errno($ch) . ") - " . curl_error($ch);
				$this->log->Write("LM: Error ($error) - Posted Content: ($content)");
			}
			else
			{
				$response_array = explode("\n", $result);

				$result_code 	= trim((string)$response_array[0]);
				$error_message 	= $response_array[1];

				$odata = explode(',', $response_array[2]);

				$return_code = trim(substr($odata[3], 0, 4));
				$return_reason = trim(substr($odata[3], 5, strlen($odata[3])));
				$confirmation_number = $odata[4];

				if($result_code == "0000")
				{
					// Success
					$this->updateRecord($transaction->getACHID(), 'processed', '', '', $confirmation_number);
				}
				else if ($result_code == "9999")
				{
					$fail_transaction = TRUE;
				}
				else
				{
					// Unknown Result Code
					$this->log->Write("LM: Unknown response - " . var_export($response_array, TRUE));
					$this->log->Write("LM: Posted Content: ($content)");
					$this->batch_errors[] = array('application_id' => $transaction->getApplicationID(), 'reason' => "Unknown response: " . $result_code);

					// It's questionable whether or not we should fail the transaction
					$fail_transaction = TRUE;
				}
			}

			if($fail_transaction === TRUE)
			{
				$this->updateRecord($transaction->getACHID(), 'returned', $return_code, $error);
				$this->updateTransactionRegister($transaction->getPaymentTransactionRegisterId(), NULL, 'failed');
				Update_Status(NULL, $transaction->getApplicationID(), self::DENIED_STATUS, NULL, NULL, FALSE);
				// Update Status now - Should this care whether or not it was an unknown error
				// or if it was returned during post?
			}
		}
	}

	/**
	 * Retrieve the ACH Returns Report
	 *
	 * @return boolean  True on success, False on failure
	 */
	public function fetchReturns()
	{
		require_once(LIB_DIR . "Achtransport/achtransport_ftp.class.php");
		require_once(LIB_DIR . "Achtransport/achtransport_sftp.class.php");
		require_once(LIB_DIR . "Achtransport/achtransport_https.class.php");

		$transport_type 	= ECash::getConfig()->LANDMARK_ACH_RETURNS_TRANSPORT_TYPE;
		$hostname			= ECash::getConfig()->LANDMARK_ACH_RETURNS_SERVER;
		$username	 		= ECash::getConfig()->LANDMARK_ACH_RETURNS_USERNAME;
		$password	 		= ECash::getConfig()->LANDMARK_ACH_RETURNS_PASSWORD;
		$remote_filename 	= ECash::getConfig()->LANDMARK_ACH_RETURNS_FILENAME;

		try {

			$transport = ACHTransport::CreateTransport($transport_type, $hostname, $username, $password);
			$contents = '';
			$batch_success = $transport->retrieveReport($remote_filename, '', $contents);

		} catch (Exception $e) {
			$this->log->Write($e->getMessage());
			return FALSE;
		}

		$report_id = $this->saveReturnFile($contents);

		return TRUE;
	}

	/**
	 * Save the ACH Returns report data to the database
	 *
	 * @param string $report_data
	 * @return integer return_report_id
	 */
	private function saveReturnFile($report_data)
	{
		$query = "
			INSERT INTO landmark_report
			(date_modified, date_created, date_request, company_id, return_file_data, report_status)
			VALUES (NOW(), NOW(), ?, ?, ?, 'received')
		";
		$this->db->queryPrepared($query, array($this->business_day, $this->company_id, $report_data));

		return $this->db->lastInsertId();
	}

	/**
	 * Get the appropriate ACH Returns report ID
	 * for the date requested
	 *
	 * @param string $date_reqeust (Y-m-d)
	 * @return integer on success or FALSE on failure
	 */
	private function getReturnFileIdByDay($date_request)
	{
		$query = "
			SELECT return_report_id
			FROM landmark_report
			WHERE date_request = ?
			AND (report_status = 'received' OR report_status = 'processed')
			ORDER BY date_modified DESC
			LIMIT 1
		";
		$id = $this->db->querySingleValue($query, array($date_request));
		return $id;
	}

	/**
	 * Retrieves the ACH Returns Report based on it's ID
	 *
	 * @param integer $return_report_id
	 * @return integer on success or FALSE on failure
	 */
	private function fetchReturnFile($return_report_id)
	{
		$query = "
			SELECT return_file_data
			FROM landmark_report
			WHERE return_report_id = ?
		";
		$data = $this->db->querySingleValue($query, array($return_report_id));
		return $data;
	}

	/**
	 * Updates the status of a return file
	 *
	 * @param integer $return_report_id
	 * @param string $status One of received, processed, failed, or obsoleted.
	 * @return boolean TRUE on Success, FALSE on Failure
	 */
	private function updateReturnFileStatus($return_report_id, $status)
	{
		$allowed_statuses = array('received','processed','failed','obsoleted');

		if(! in_array($status, $allowed_statuses))
		{
			throw new Exception ("Invalid status '{$status}' supplied!");
		}

		$query = "
			UPDATE landmark_report
			SET report_status = ?
			WHERE return_report_id = ?
		";
		$st = $this->db->queryPrepared($query, array($status, $return_report_id));
		return ($st->rowCount() > 0);
	}

	/**
	 * Process the ACH Returns Report for a given day
	 *
	 * @param string $business_day (optional) Format: 'Y-m-d'
	 * @return boolean
	 */
	public function processReturns($business_day = NULL)
	{
		$business_day = (! empty($business_day)) ? $business_day : $this->business_day;

		// Fetch the best available report for the given day
		$report_id = $this->getReturnFileIdByDay($business_day);

		if(empty($report_id))
		{
			$this->log->Write("LM: Could not retrieve returns for '$business_day'");
			return FALSE;
		}

		// Fetch the Report Data
		$report_data = $this->fetchReturnFile($report_id);

		try {
			// Parse the CSV and return an associative array
			$returns = $this->parseReportFile($report_data);

			foreach($returns as $record)
			{
				if(! $this->recordFailure($record))
				{
					$this->log->Write("LM: Problem failing return!");
					$this->log->Write(var_export($record, true));
				}
			}
		}
		catch (Exception $e)
		{
			$this->updateReturnFileStatus($report_id, 'failed');
			return FALSE;
		}

		$this->updateReturnFileStatus($report_id, 'processed');
		return TRUE;
	}

	/**
	 * Marks the landmark_ach item as returned, the transacation_register
	 * item as failed, and updates the status of the application to Denied
	 * on Failure.
	 *
	 * @param array $record
	 * @return boolean TRUE on Success, FALSE on Failure
	 */
	private function recordFailure($record)
	{
		$lm_ach_id 		= $record['lm_ach_id'];
		$return_code 	= $record['return_code'];
		$return_reason 	= $record['return_reason'];

		if(! $this->updateRecord($lm_ach_id, 'returned', $return_code, $return_reason))
		{
			$this->log->Write("LM: Error failing landmark_ach item using lm_ach_id '$lm_ach_id'!");
		}

		if( !$this->updateTransactionRegister(NULL, $lm_ach_id, 'failed'))
		{
			$this->log->Write("LM: Error failing transaction_register item using lm_ach_id '$lm_ach_id'!");
		}

		if(! $application_id = $this->getApplicationIdByACHID($lm_ach_id))
		{
			$this->log->Write("LM: Error locating application_id using lm_ach_id '$lm_ach_id'!");
			return FALSE;
		}
		else
		{
			Update_Status(NULL, $application_id, self::DENIED_STATUS, NULL, NULL, FALSE);
			$this->log->Write("LM: Failed application {$application_id} due to Failure: [$return_code] - $return_reason");
			return TRUE;
		}
	}

	/**
	 * Find the application_id using the lm_ach_id
	 * from the landmark_ach table
	 *
	 * @param integer $lm_ach_id
	 * @return integer on Success or FALSE on Failure
	 */
	private function getApplicationIdByACHID($lm_ach_id)
	{
		$query = "
			SELECT application_id
			FROM landmark_ach
			WHERE lm_ach_id = ?
		";
		$app_id = $this->db->querySingleValue($query, array($lm_ach_id));
		return $app_id;
	}

	/**
	 * Parses a comma delimited string (CSV File) and maps
	 * the fields into an associative array using $this->return_file_format
	 *
	 * @param string $return_file_data
	 * @return array
	 */
	private function parseReportFile ($return_file_data)
	{
		// Split file into rows
		$return_data_ary = explode(self::RS, $return_file_data);

		$parsed_data_ary = array();
		$i = 0;

		foreach ($return_data_ary as $line)
		{
			if ( strlen(trim($line)) > 0 )
			{
				//  Split each row into individual columns
				$matches = array();
				preg_match_all('#(?<=^"|,")(?:[^"]|"")*(?=",|"$)|(?<=^|,)[^",]*(?=,|$)#', $line, $matches);
				$col_data_ary = $matches[0];

				$parsed_data_ary[$i] = array();
				foreach ($col_data_ary as $key => $col_data)
				{
					// Apply column name map so we can return a friendly structure
					$parsed_data_ary[$i][self::$return_file_format[$key]] = str_replace('"', '', $col_data);
				}

				$i++;
			}
		}

		return $parsed_data_ary;
	}
}


class LandmarkItemData
{
	static protected $assessment_pending_period;
	static protected $payment_pending_period;
	static protected $assessment_period_type;
	static protected $payment_period_type;
	static protected $assessment_event_type;
	static protected $payment_event_type;
	static protected $assessment_transaction_type;
	static protected $payment_transaction_type;

	static protected $company_id;

	protected $application_id;
	protected $principal;
	protected $service_charge;
	protected $fee;
	protected $amount;
	protected $bank_aba;
	protected $bank_account;
	protected $full_name;
	protected $name_last;
	protected $name_first;
	protected $address;
	protected $city;
	protected $state;
	protected $zip;
	protected $homephone;
	protected $bank_name;
	protected $bank_account_type;
	protected $legal_id_number;
	protected $legal_id_state;

	protected $company_name;

	protected $assessment_event_schedule_id;
	protected $payment_event_schedule_id;
	protected $date_event;
	protected $date_effective;
	protected $source_id;
	protected $lm_ach_id;
	protected $assessment_transaction_register_id;
	protected $payment_transaction_register_id;

	protected $batch_record;

	/**
	 * Returns an array of LandmarkItemData objects based on the rows in a
	 * given mysql result.
	 *
	 * @param PDOStatement $result
	 * @return Array
	 */
	static public function loadLandmarkItemsFromResult(PDOStatement $result)
	{
		$items = array();

		while ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$new_item = new LandmarkItemData();

			foreach ($row as $name => $value)
			{
				if (property_exists($new_item, $name))
				{
					$new_item->$name = $value;
				}
			}

			$items[] = $new_item;
		}

		return $items;
	}

	/**
	 * Initializes various global information needed for each run based on
	 * company id. This should be called prior to most static function calls
	 * once.
	 *
	 * @param int $company_id
	 */
	static public function init($company_id)
	{
		$event_map = Load_Event_Type_Map($company_id);
		$transaction_map = Load_Transaction_Map($company_id);

		self::$assessment_event_type = $event_map['assess_processing_fee'];
		self::$payment_event_type    = $event_map['payment_processing_fee'];

		self::$assessment_transaction_type = $transaction_map['assess_processing_fee']['transaction_type_id'];
		self::$payment_transaction_type    = $transaction_map['payment_processing_fee']['transaction_type_id'];

		self::$assessment_pending_period = $transaction_map['assess_processing_fee']['pending_period'];
		self::$payment_pending_period    = $transaction_map['payment_processing_fee']['pending_period'];

		self::$assessment_period_type = $transaction_map['assess_processing_fee']['period_type'];
		self::$payment_period_type = $transaction_map['payment_processing_fee']['period_type'];

		self::$company_id = $company_id;
	}

	static protected function getDateEffectiveFromDateEvent($date_event)
	{
		$pdc = new Pay_Date_Calc_3(Fetch_Holiday_List());

		if (self::$payment_period_type == 'business')
		{
			$date_effective = $pdc->Get_Business_Days_Forward($date_event, self::$payment_pending_period);
		}
		else
		{
			$date_effective = $pdc->Get_Calendar_Days_Forward($date_event, self::$payment_pending_period);
		}

		return $date_effective;
	}

	/**
	 * Inserts registered event schedule rows into the given database for all
	 * quickchecks in the given array using the given company_id for the given
	 * date.
	 *
	 * @param DB_Database_1 $db
	 * @param array $batch_items
	 * @param string $date_event
	 */
	static public function insertAssessmentEventSchedules(DB_Database_1 $db, array $items, $date_event)
	{
		$source_map = Get_Source_Map();
		$source_id = $source_map[DEFAULT_SOURCE_TYPE];
		$date_effective = $date_event;

		$query_batch_size = 100;

		$base_query = "
			-- eCash3.5 File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			INSERT INTO event_schedule
			(
				date_created,
				company_id,
				application_id,
				event_type_id,
				configuration_trace_data,
				event_status,
				date_event,
				date_effective,
				context,
				source_id
			)
			VALUES
		";
		$insert = "(NOW(), ?, ?, ?, '', 'registered', ?, ?, 'generated', ?)";

		$batches = array_chunk($items, $query_batch_size);

		foreach ($batches as $b)
		{
			$args = array();

			foreach ($b as $item)
			{
				/** @var $item LandmarkItemData */
				$args[] = self::$company_id;
				$args[] = $item->application_id;
				$args[] = self::$assessment_event_type;
				$args[] = $date_event;
				$args[] = $date_effective;
				$args[] = $source_id;

				$item->date_event = $date_event;
				$item->date_effective = $date_effective;
				$item->source_id = $source_id;
			}

			$query = $base_query.str_repeat($insert.', ', count($b) - 1).$insert;
			$db->queryPrepared($query, $args);

			$insert_id = $db->lastInsertId();
			$max_insert_id = ($insert_id + count($b) - 1);

			$db->exec("
				UPDATE event_schedule
				SET origin_group_id = event_schedule_id
				WHERE
					event_schedule_id BETWEEN {$insert_id} AND {$max_insert_id}
			");

			foreach ($b as $i)
			{
				$i->assessment_event_schedule_id = $insert_id++;
			}
		}
	}

	static public function insertPaymentEventSchedules(DB_Database_1 $db, Array $items, $date_event)
	{
		$source_map = Get_Source_Map();
		$source_id = $source_map[DEFAULT_SOURCE_TYPE];
		$date_effective = self::getDateEffectiveFromDateEvent($date_event);

		$query_batch_size = 100;

		$base_query = "
			INSERT INTO event_schedule
			(
				date_created,
				company_id,
				application_id,
				event_type_id,
				configuration_trace_data,
				event_status,
				date_event,
				date_effective,
				context,
				source_id
			)
			VALUES
		";
		$row = "(NOW(), ?, ?, ?, '', 'registered', ?, ?, 'generated', ?)";

		$batches = array_chunk($items, $query_batch_size);

		foreach ($batches as $batch)
		{
			$args = array();

			foreach ($batch as $item)
			{
				$args[] = self::$company_id;
				$args[] = $item->application_id;
				$args[] = self::$payment_event_type;
				$args[] = $date_event;
				$args[] = $date_effective;
				$args[] = $source_id;

				$item->date_event = $date_event;
				$item->date_effective = $date_effective;
				$item->source_id = $source_id;
			}

			$query = $base_query.str_repeat($row.', ', count($batch) - 1).$row;
			$db->queryPrepared($query, $args);

			// @todo this probably shouldn't be done
			$insert_id = $db->lastInsertId();
			$max_insert_id = ($insert_id + count($batch) - 1);

			$db->exec("
				UPDATE event_schedule
				SET origin_group_id = event_schedule_id
				WHERE
					event_schedule_id BETWEEN {$insert_id} AND {$max_insert_id}
			");

			foreach ($batch as $item)
			{
				$item->payment_event_schedule_id = $insert_id++;
			}
		}
	}

	static public function insertACHItems(DB_Database_1 $db, Array $batch_items, $ecld_file_id)
	{
		$query_batch_size = 100;

		$base_query = "
			-- eCash3.5 File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			INSERT INTO landmark_ach
			(
				date_modified,
				date_created,
				company_id,
				batch_date,
				batch_type,
				application_id,
				amount,
				bank_aba,
				bank_account,
				status
			)
			VALUES
		";

		$insert = "(NOW(), NOW(), ?, ?, 'debit', ?, ?, ?, ?, 'batched')";

		$batches = array_chunk($items, $query_batch_size);

		foreach ($batches as $b)
		{
			$args = array();

			foreach ($b as $item)
			{
				$args[] = self::$company_id;
				$args[] = $item->date_event;
				$args[] = $item->application_id;
				$args[] = $item->amount;
				$args[] = $item->bank_aba;
				$args[] = $item->bank_account;
			}

			$query = $base_query.str_repeat($insert.', ', count($b) - 1).$insert;
			$db->queryPrepared($query, $args);

			// update insert IDs
			$insert_id = $db->lastInsertId();
			foreach ($b as $item) $item->lm_ach_id = $insert_id++;
		}
	}

	static public function insertAssessmentTransactionRegisters(DB_Database_1 $db, Array $batch_items)
	{
		$query_batch_size = 100;

		$base_query = "
			-- eCash3.5 File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			INSERT INTO transaction_register
			(
				date_created,
				company_id,
				application_id,
				event_schedule_id,
				transaction_type_id,
				transaction_status,
				amount,
				date_effective,
				source_id,
				modifying_agent_id
			)
			VALUES
		";
		$insert = "(NOW(), ?, ?, ?, ?, 'complete', ?, ?, ?, ?)";

		$batches = array_chunk($batch_items, $query_batch_size);
		$agent = Fetch_Current_Agent();

		foreach ($batches as $b)
		{
			$args = array();

			foreach ($b as $item)
			{
				/* @var $item LandmarkItemData */
				$args[] = self::$company_id;
				$args[] = $item->application_id;
				$args[] = $item->assessment_event_schedule_id;
				$args[] = self::$assessment_transaction_type;
				$args[] = $item->amount;
				$args[] = $item->date_effective;
				$args[] = $item->source_id;
				$args[] = $agent;
			}

			$query = $base_query.str_repeat($insert.', ', count($b) - 1).$insert;
			$db->queryPrepared($query, $args);

			$insert_id = $db->lastInsertId();
			foreach ($b as $i) $i->assessment_transaction_register_id = $insert_id++;
		}
	}

	static public function insertPaymentTransactionRegisters(DB_Database_1 $db, Array $batch_items)
	{
		$query_batch_size = 100;

		$base_query = "
			-- eCash3.5 File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			INSERT INTO transaction_register
			(
				date_created,
				company_id,
				application_id,
				event_schedule_id,
				lm_ach_id,
				transaction_type_id,
				transaction_status,
				amount,
				date_effective,
				source_id,
				modifying_agent_id
			)
			VALUES
		";
		$insert = "(NOW(), ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)";

		$batches = array_chunk($batch_items, $query_batch_size);
		$agent_id = Fetch_Current_Agent();

		foreach ($batches as $b)
		{
			$args = array();

			foreach ($b as $item)
			{
				/* @var $item LandmarkItemData */
				$args[] = self::$company_id;
				$args[] = $item->application_id;
				$args[] = $item->payment_event_schedule_id;
				$args[] = $item->lm_ach_id;
				$args[] = self::$payment_transaction_type;
				$args[] = -$item->amount;
				$args[] = $item->date_effective;
				$args[] = $item->source_id;
				$args[] = $agent_id;
			}

			$query = $base_query.str_repeat($insert.', ', count($b) - 1).$insert;
			$db->queryPrepared($query, $args);

			$insert_id = $db->lastInsertId();
			foreach ($b as $i) $i->payment_transaction_register_id = $insert_id++;
		}
	}

	static public function insertAssessmentEventAmounts(DB_Database_1 $db, Array $batch_items)
	{
		$query_batch_size = 100;

		$amount_type_map = Retrieve_Event_Amount_Type_Map();

		$base_query = "
			-- eCash3.5 File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			INSERT INTO event_amount
			(
				date_created,
				company_id,
				application_id,
				event_schedule_id,
				transaction_register_id,
				event_amount_type_id,
				amount
			)
			VALUES
		";
		$insert = "(NOW(), ?, ?, ?, ?, ?, ?)";

		$batches = array_chunk($batch_items, $query_batch_size);

		foreach ($batches as $b)
		{
			$args = array();
			$count = 0;

			foreach ($b as $item)
			{
				$final_amounts = array(
					$amount_type_map['principal'] => $item->principal,
					$amount_type_map['service_charge'] => $item->service_charge,
					$amount_type_map['fee'] => $item->fee,
				);

				foreach ($final_amounts as $event_type_id=>$amount)
				{
					if ($amount == 0) continue;

					/* @var $item LandmarkItemData */
					$args[] = self::$company_id;
					$args[] = $item->application_id;
					$args[] = $item->assessment_event_schedule_id;
					$args[] = $item->assessment_transaction_register_id;
					$args[] = $event_type_id;
					$args[] = $amount;
					$count++;
				}
			}

			if ($count)
			{
				$query = $base_query.str_repeat($insert.', ', $count - 1).$insert;
				$db->queryPrepared($query, $args);
			}
		}
	}

	static public function insertPaymentEventAmounts(DB_Database_1 $db, Array $batch_items)
	{
		$query_batch_size = 100;
		$amount_type_map = Retrieve_Event_Amount_Type_Map();

		$base_query = "
			-- eCash3.5 File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			INSERT INTO event_amount
			(
				date_created,
				company_id,
				application_id,
				event_schedule_id,
				transaction_register_id,
				event_amount_type_id,
				amount
			)
			VALUES
		";
		$insert = "(NOW(), ?, ?, ?, ?, ?, ?)";

		$batches = array_chunk($batch_items, $query_batch_size);

		foreach ($batches as $b)
		{
			$args = array();
			$count = 0;

			foreach ($b as $item)
			{
				/* @var $item LandmarkItemData */
				$ach_amounts = array(
					'principal' => -$item->principal,
					'service_charge' => -$item->service_charge,
					'fee' => -$item->fee,
				);

				$ach_amounts = AmountAllocationCalculator::removeCreditAmounts($ach_amounts);

				$final_amounts = array(
					$amount_type_map['principal'] => $ach_amounts['principal'],
					$amount_type_map['service_charge'] => $ach_amounts['service_charge'],
					$amount_type_map['fee'] => $ach_amounts['fee'],
				);

				foreach ($final_amounts as $event_type_id => $amount)
				{
					if ($amount == 0) continue;

					$args[] = self::$company_id;
					$args[] = $item->application_id;
					$args[] = $item->payment_event_schedule_id;
					$args[] = $item->payment_transaction_register_id;
					$args[] = $event_type_id;
					$args[] = $amount;
					$count++;
				}
			}

			if ($count)
			{
				$query = $base_query.str_repeat($insert.', ', $count - 1).$insert;
				$db->queryPrepared($query, $args);
			}
		}
	}

	/**
	 * Updates the status of the given items to quickcheck sent.
	 *
	 * @param array $items - An array containing data for each
	 *        ach item being sent.
	 */
	static public function UpdateStatuses(Array $items)
	{
		foreach ($items as $item)
		{
			Update_Status(NULL, $item->application_id, 'approved::servicing::customer::*root');
		}
	}

	public function getApplicationID()
	{
		return $this->application_id;
	}

	public function getAmount()
	{
		return $this->amount;
	}

	public function getBankAba()
	{
		return $this->bank_aba;
	}

	public function getBankAccount()
	{
		return $this->bank_account;
	}

	public function getACHID()
	{
		return $this->lm_ach_id;
	}

	public function getFullName()
	{
		return $this->full_name;
	}

	public function getNameLast()
	{
		return $this->name_last;
	}

	public function getNameFirst()
	{
		return $this->name_first;
	}

	public function getAddress()
	{
		return $this->address;
	}

	public function getCity()
	{
		return $this->city;
	}

	public function getState()
	{
		return $this->state;
	}

	public function getZip()
	{
		return $this->zip;
	}

	public function getHomePhone()
	{
		return $this->homephone;
	}

	public function getBankName()
	{
		return $this->bank_name;
	}

	public function getLegalIdNumber()
	{
		return $this->legal_id_number;
	}

	public function getLegalIdState()
	{
		return $this->legal_id_state;
	}

	public function getCompanyName()
	{
		return $this->company_name;
	}

	public function getBatchRecord()
	{
		return $this->batch_record;
	}

	public function setBatchRecord($record)
	{
		$this->batch_record = $record;
	}

	public function getAssessmentTransactionRegisterId()
	{
		return $this->assessment_transaction_register_id;
	}

	public function getPaymentTransactionRegisterId()
	{
		return $this->payment_transaction_register_id;
	}

}

class Field_Validation_Exception extends Exception
{
	public function __construct($message)
	{
		parent::__construct($message, 0);
	}
}

class DebugLog extends Applog
{
	function __construct($path, $size, $limit)
	{
		parent::__construct($path, $size, $limit);
	}

	public function Write($text, $level = NULL)
	{
		$date_string = "[" . date('Y-m-d h:i:s') . "]";
		echo $date_string . " " . $text . "\n";
	}
}