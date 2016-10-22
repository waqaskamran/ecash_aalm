<?php
require_once(COMMON_LIB_DIR  . "general_exception.1.php");
require_once(LIB_DIR         . "business_rules.class.php");
require_once(LIB_DIR         . "common_functions.php");
require_once(SERVER_CODE_DIR . "paydate_handler.class.php");
require_once(SERVER_CODE_DIR . "paydate_info.class.php");
require_once(SQL_LIB_DIR . "/application.func.php");

/**
 * General ACH-related Utilities
 *
 */
class ACH_Utils
{
	private $process_log_ids;
	private $business_day;
	private $override_date;
	private $server;
	private $company_id;
	private $log;
	private $db;

	private $batch_date;
	public $ach_batch_id;

	private $total_amount;

	private $paydate_obj;
	private $paydate_handler;
	private $biz_rules;

	public function __construct($server)
	{
		$this->server = $server;
		$this->company_id = $server->company_id;
		$this->db = ECash::getMasterDb();
		$this->log = new Applog(APPLOG_SUBDIRECTORY.'/ach', APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, strtoupper($this->server->company));
		$holidays = Fetch_Holiday_List();
		$this->paydate_obj = new Pay_Date_Calc_3($holidays);

		// Set up stuff for loan rescheduling
		$this->paydate_handler	= new Paydate_Handler($this->server);
		$this->biz_rules	= new ECash_Business_Rules($this->db);
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $processing_step
	 * @param unknown_type $processing_state
	 * @param unknown_type $override_date
	 * @return unknown
	 */
	public function Set_Process_Status($processing_step, $processing_state, $override_date=NULL)
	{
		if (empty($this->business_day))
		{
			$this->business_day = date("Y-m-d");
		}

		if (!empty($override_date))
		{
			$query_business_day = $override_date;
		}
		elseif ( in_array($processing_step, array('ach_returns','ach_corrections','ach_post')) )
		{
			$query_business_day = $this->business_day;
		}
		elseif ( !empty($this->override_date) )
		{
			$query_business_day = $this->override_date;
		}
		else
		{
			$query_business_day = date("Y-m-d");
		}

		$pid = isset($this->process_log_ids[$processing_step]) ? $this->process_log_ids[$processing_step] : null;
		$pid = Set_Process_Status($this->db, $this->company_id, $processing_step, $processing_state, $query_business_day, $pid);
		$this->process_log_ids[$processing_step] = $pid;
		return ($pid != 0);

	}

	public function Insert_ACH_Row($table, $row_data, $ach_bundling = FALSE)
	{
		// If we are to coalesce ACH transactions belonging to the same batch and origin_group_id, we
		//	will act accordingly by performing an update instead of an insert.
		// 	We do a select first so that we can return the updated ach_id (as if we had done an insert).
		//

		//If bundling is enabled, we're going to bundle based on app_id.  If not, we're grouping on origin_group_id
		if($ach_bundling)
		{
			$batch_grouping = "AND	application_id = {$this->db->quote($row_data['application_id'])}";
		}
		else
		{
			$batch_grouping = "AND	origin_group_id		= {$this->db->quote($row_data['origin_group_id'])}";
		}
		$bank_account = new ECash_Models_BankAccount($this->db);
		$bank_account_id = $bank_account->getBankAccountIdByBankAccount($row_data['bank_account']);
		if ($table == 'customer')
		{
			// The WHERE clause in this update contains additional, possibly extraneous criteria...
			//	but we want to be sure we don't coalesce anything we shouldn't
			$query_sel = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
						SELECT
							ach_id
						FROM
							ach
						WHERE
								ach_batch_id		= "  . $this->ach_batch_id . "
							" . $batch_grouping . "
							AND	ach_date			= '" . $this->batch_date . "'
							AND ach_type			= '" . $row_data['ach_type'] . "'
							AND bank_aba			= '" . $row_data['bank_aba'] . "'
							AND bank_account		= '" . $bank_account_id . "'
							AND bank_account_type	= '" . $row_data['bank_account_type'] . "'
							AND ach_status			= 'created'
						FOR UPDATE
			";

			$result_sel = $this->db->Query($query_sel);

			if ($row_sel = $result_sel->fetch(PDO::FETCH_ASSOC))
			{
				$ach_id = $row_sel['ach_id'];

				$query_upd = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
							UPDATE ach
								SET
									amount	= amount + " . $row_data['amount'] . "
								WHERE
									ach_id = $ach_id
				";

				$this->db->Query($query_upd);

				return $ach_id;
			}
		}

		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					INSERT INTO " . (($table == 'company') ? 'ach_company' : 'ach') . "
						(
							date_created,
							company_id,
		";
		if ($table != 'company')
		{
			$query .= "
							application_id,
							origin_group_id,
			";
		}
		$query .= "
							ach_batch_id,
							ach_date,
							amount,
							ach_type,
							bank_aba,
							bank_account,
							bank_account_type,
							ach_status,
							ach_trace_number
						)
					VALUES
						(
							current_timestamp," . "
							" . $this->company_id . ",
							";
		if ($table != 'company')
		{
			//If bundling is enabled, we're using an origin_group_id of 0, because this ACH record will apply to multiple events
			if($ach_bundling)
			{
				$query .= 			$row_data['application_id'] . ",
									0,
								";
			}
			else
			{
				$query .= 			 $row_data['application_id'] . ",
								"  . $row_data['origin_group_id'] . ",
								";
			}
		}
		$query .=				 $this->ach_batch_id . ",
							'" . $this->batch_date . "',
							"  . $row_data['amount'] . ",
							'" . $row_data['ach_type'] . "',
							'" . $row_data['bank_aba'] . "',
							'" . $bank_account_id . "',
							'" . $row_data['bank_account_type'] . "',
							'created',
							'TBD'
						)
		";

		$result = $this->db->Query($query);
		$ach_id = $this->db->lastInsertId();

		return $ach_id;
	}

	public function Update_ACH_Row($table, $ach_id, $status, $ach_trace_number=NULL, $ach_return_code=NULL, $ach_report_id=NULL, &$ach_exceptions)
	{

		// Add some error checking as suggested by MarcC.
		// Verify that a record with ACH ID actually exists.
		// This should never happen. If it does it's a major problem.
		//
		// Check for row with ACH ID using a SELECT COUNT(*) because
		// using post-update mysql_affected_rows or similar mysqli function reports a zero count if
		// record exists but no update was needed.
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT ach_id, ach_status, ach_return_code_id
					FROM " . (($table == 'company') ? 'ach_company' : 'ach') . "
					WHERE ach_id = '$ach_id'";

		$result = $this->db->Query($query);
		$ach_ids = array();
		while($row = $result->fetch(PDO::FETCH_ASSOC)){
			$ach_ids[] = $row;
		}
		$count = count($ach_ids);

		if ($count == 0) {
			$this->log->Write("Update_ACH_Row() for ACH ID {$ach_id} failed to find matching record in {$table} table.\n");
			$exception = array(
				'ach_id'  => $ach_id,
				'exception' => "Update_ACH_Row() failed to find matching record in $table",
				'recipient_name' => $recipient_name
			);
			$ach_exceptions[$ach_id] = $exception;
			 return false;
		}

		// There should never be more than one ACH ID returned, but since it is an array ...
		foreach($ach_ids as $ach_item) {
			if($ach_item['ach_status'] == 'returned') {
				$this->log->Write("Update_ACH_Row() for ACH ID {$ach_id} found an already returned record in {$table} table.\n");
				$exception = array(
					'ach_id'  => $ach_id,
					'exception' => "Update_ACH_Row() found an already returned record in $table",
					'recipient_name' => $recipient_name
				);
				$ach_exceptions[$ach_id] = $exception;
				 return false;
			}
		}

		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					UPDATE " . (($table == 'company') ? 'ach_company' : 'ach') . "
					SET
						ach_status			= '$status'";
		if (strlen($ach_trace_number) > 0)
		{
			$query .= ",
						ach_trace_number	= '$ach_trace_number'";
		}
		if ($table == 'company')
		{
			$query .= ",
						amount				= {$this->total_amount}";
		}
		if (strlen($ach_return_code) > 0)
		{
			$query .= ",
						ach_return_code_id	= (	SELECT ach_return_code_id
												FROM ach_return_code
												WHERE name_short = " . $this->db->quote($ach_return_code) . ")";
		}
		if (strlen($ach_report_id) > 0)
		{
			$query .= ",
						ach_report_id	= $ach_report_id";
		}
		$query .= "
					WHERE
							ach_id		= $ach_id
		";

		$this->db->Query($query);

		return true;
	}

	public function Update_Transaction_Register ($transaction_register_id, $ach_id)
	{
		$agent_id = Fetch_Current_Agent($this->server);

		Set_Loan_Snapshot($transaction_register_id,"pending");

		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					UPDATE transaction_register
					SET
						transaction_status	= 'pending',
						modifying_agent_id  = '$agent_id',
						ach_id				= $ach_id
					WHERE
							transaction_register_id = $transaction_register_id
						AND	transaction_status		<> 'pending'
		";

		$this->db->Query($query);

		return true;
	}

	public function Set_Total_Amount($total)
	{
		$this->total_amount = $total;
	}

	public function Set_Batch_Date($batch_date)
	{
		$this->batch_date = $batch_date;
	}

	public function ACH_Deem_Successful($business_day)
	{
		$this->business_day = $business_day;

		// If we are already inside of a transaction, bail out with error
		if ($this->db->InTransaction)
		{
			throw new General_Exception("ACH: Cannot be invoked from within a transaction; this class manages its own transactions.");
		}

		// If it's a weekend or holiday, just don't run this today.
		$stamp = strtotime($business_day);
		if ($this->paydate_obj->Is_Weekend($stamp) || ($this->paydate_obj->Is_Holiday($stamp)))
		{
			return false;
		}

		// Note start of ACH process
		$this->Set_Process_Status('ach_post', 'started');
		$ach_hold_days = ECash::getConfig()->ACH_HOLD_DAYS;
		$ach_hold_expire_threshold_date = $this->paydate_obj->Get_Business_Days_Backward($business_day, $ach_hold_days);

		if(!empty(ECash::getConfig()->CHECK_HOLD_DAYS))
		{
			$check_hold_expire_threshold_date = $this->paydate_obj->Get_Business_Days_Backward($business_day, ECash::getConfig()->CHECK_HOLD_DAYS);
		}
		else
		{
			$check_hold_expire_threshold_date = $ach_hold_expire_threshold_date;
		}
		$query_sel = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT
						tr.application_id,
						tr.transaction_register_id,
						tr.ach_id
					FROM
						transaction_register tr join transaction_type tt using (transaction_type_id),
						ach
					WHERE
							 tr.ach_id		=  ach.ach_id
						AND	 tr.transaction_status	=  'pending'
						AND	 tr.company_id		=  {$this->company_id}
						AND  ((tr.date_effective <= '$ach_hold_expire_threshold_date' and tt.name_short not in ('personal_check_fees','personal_check_princ'))
							 or (tr.date_effective <= '$check_hold_expire_threshold_date' and tt.name_short in ('personal_check_fees','personal_check_princ')))
						AND ach.ach_status		=  'batched'
					ORDER BY
						tr.transaction_register_id
					FOR UPDATE
		";

		$result_sel = $this->db->Query($query_sel);

		try
		{
			$empty_array = array();
			while($row = $result_sel->fetch(PDO::FETCH_ASSOC))
			{
				$this->Update_ACH_Row('customer', $row['ach_id'], 'processed', NULL, NULL, NULL, $empty_array);
				Post_Transaction($row['application_id'], $row['transaction_register_id']);

				$app_info = ECash::getFactory()->getAppClient()->getApplicationInfo($row['application_id']);
				if(isset($app_info->applicationStatus->name))
				{
					if ($app_info->applicationStatus->name == 'current::arrangements::collections::customer::*root' &&
						Application_Flag_Exists($row['application_id'], 'arr_incl_pend' ))
					{
						Add_Comment($this->company_id, $row['application_id'], Fetch_Default_Agent_ID(),
							"This customer has had an ach complete that was part of the arrangement. The outstanding balance has been decreased and arrangements may need to be renegotiated.",
							'add_follow_up', $parameters->server->system_id);
					}
				}
			}
		}
		catch(Exception $e)
		{
			$this->log->Write("ACH: Posting of \"ACH transactions deemed successful\" failed and transaction will be rolled back.", LOG_ERR);
			$this->log->Write("ACH: This process must be re-run after the cause of this problem has been determined.", LOG_INFO);
			$this->Set_Process_Status('ach_post', 'failed');
			throw $e;
		}

		$this->log->Write("ACH transactions deemed successful have been posted.", LOG_INFO);
		$this->Set_Process_Status('ach_post', 'completed');

		return true;
	}

	/*  Check for the state of a particular process
	 *  Specify a date or else the most recent state
	 *  will be returned.
	 */
	public function Check_Process_State($process, $business_day = false)
	{
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT 	state
					FROM	process_log
					WHERE	company_id = {$this->server->company_id}
					AND     step = '{$process}'
			";

			if($business_day != false) {
				$query .= "
					AND		business_day = '{$business_day}'";
			}
			$query .= "
					ORDER BY date_started desc
					LIMIT 1
			";
			$result = $this->db->Query($query);
			if($result->rowCount() > 0)
			{
				$state = $result->fetch(PDO::FETCH_OBJ)->state;
				return $state;
			}
			else
			{
				return false;
			}
	}

	/**
	 * Checks to see if a process has run for any company
	 *
	 * @return mixed company name_short of the 1st company to run the
	 * process on the given business day, or FALSE if none found
	 */
	public function Check_Enterprise_Has_Run($process, $business_day, $state = 'completed')
	{
			$query = "
					SELECT 	c.name_short as company
					FROM	process_log pl
					JOIN 	company c on (pl.company_id = c.company_id)
					WHERE	pl.step = '{$process}'
					AND		pl.business_day = '{$business_day}'
					AND		pl.state = '{$state}'
					ORDER BY pl.date_started asc
					LIMIT 1
			";
			$result = $this->db->Query($query);
			if($result->rowCount() > 0)
			{
			   	return $result->fetch(PDO::FETCH_OBJ)->company;
			}
			else
			{
				return FALSE;
			}
	}

	public function Add_Comment($application_id, $comment)
	{
		$agent_id = "NULL";
		if (isset($this->server->agent_id))
		{
			$agent_id = $this->server->agent_id;
		}

		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					INSERT INTO comment
					(
						date_created,
						date_modified,
						application_id,
						company_id,
						agent_id,
						comment,
						source,
						type
					)
					VALUES
				  	(
				  		current_timestamp,
				  		current_timestamp,
				  		$application_id,
				  		{$this->server->company_id},
				  		IFNULL(
				  				$agent_id,
				  				(SELECT agent_id
				  				 FROM agent
				  				 WHERE login = CONVERT((SUBSTRING_INDEX(USER(),'@',1)) USING latin1) ORDER BY date_created DESC LIMIT 1)
				  		),
				 		" . $this->db->quote(trim($comment)) . ",
						'system',
						'ach_correction'
					)
		";

		$this->db->Query($query);

		return true;
	}

	public function getInterceptAuthCodes()
	{
			$batch_login = ECash::getConfig()->ACH_REPORT_LOGIN;
			$batch_pass = ECash::getConfig()->ACH_REPORT_PASS;

			$url = ECash::getConfig()->ACH_AUTH_URL;

			///////////////////  First Hit  //////////////
			$curlopt = array();
			$curlopt['CURLOPT_POSTFIELDS']['login'] = $batch_login;
			$curlopt['CURLOPT_POSTFIELDS']['pass'] = $batch_pass;
			$curlopt['CURLOPT_RETURNTRANSFER'] = TRUE;

			$h_curl = curl_init($url);
			foreach ($curlopt as $option => $value)
			{
				$return = curl_setopt($h_curl, constant($option), $value);
			}

			$hit1_returned_xml = curl_exec($h_curl);
			curl_close($h_curl);

			//analyze first hit
			require_once('minixml/minixml.inc.php');
			$card_array = Fetch_Intercept_Card($this->server->company_id);

			$mini = new MiniXMLDoc();
			$mini->fromString($hit1_returned_xml);
			$hit1_returned_array = $mini->toArray();

			$err_code_hit1 = intval($hit1_returned_array["xdoc"]["errorcode"]);
			if($err_code_hit1 == 0)
			{
				$card_serial_hit1_returned = $hit1_returned_array["xdoc"]["serial"];
				$session_id_for_hit2 = $hit1_returned_array["xdoc"]["sessionid"];

				$challenge1_letter = substr($hit1_returned_array["xdoc"]["challenge1"], 0, 1);
				$challenge1_digit = intval(substr($hit1_returned_array["xdoc"]["challenge1"], 1, 1));

				$challenge2_letter = substr($hit1_returned_array["xdoc"]["challenge2"], 0, 1);
				$challenge2_digit = intval(substr($hit1_returned_array["xdoc"]["challenge2"], 1, 1));

				$challenge3_letter = substr($hit1_returned_array["xdoc"]["challenge3"], 0, 1);
				$challenge3_digit = intval(substr($hit1_returned_array["xdoc"]["challenge3"], 1, 1));

				$card_val1_for_hit2 = $card_array[$challenge1_letter][$challenge1_digit];
				$card_val2_for_hit2 = $card_array[$challenge2_letter][$challenge2_digit];
				$card_val3_for_hit2 = $card_array[$challenge3_letter][$challenge3_digit];

				$ret_val = array(
					'session_id' => $session_id_for_hit2,
					'value_1' => $card_val1_for_hit2,
					'value_2' => $card_val2_for_hit2,
					'value_3' => $card_val3_for_hit2
					);

				return $ret_val;

			}
			else
			{
				return false;
			}
	}

}
?>
