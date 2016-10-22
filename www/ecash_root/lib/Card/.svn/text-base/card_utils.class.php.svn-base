<?php
    /* Authorize.net card utility class.
     *
     * Written by Randy Klepetko (randy.klepetko@sbcglobal.net)
     * 2013-08-14
     */ 
    require_once 'anet_php_sdk/AuthorizeNet.php';
     
    class Card_Utils {
        
        private $data;
        private $sale;
        
        private $process_log_ids;
	private $business_day;
	private $override_date;
	private $server;
	private $company_id;
	private $log;
	private $db;

	private $batch_date;
	private $card_bin;

	private $total_amount;

	private $paydate_obj;
	private $paydate_handler;
	private $biz_rules;

	public function __construct($server)
	{
            $this->server = $server;
            $this->company_id = $server->company_id;
            $this->db = ECash::getMasterDb();
            $this->log = new Applog(APPLOG_SUBDIRECTORY.'/card', APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, strtoupper($this->server->company));

            // Set up stuff for loan rescheduling
            $holidays = Fetch_Holiday_List();
            $this->paydate_obj = new Pay_Date_Calc_3($holidays);
            $this->paydate_handler	= new Paydate_Handler($this->server);
            $this->biz_rules	= new ECash_Business_Rules($this->db);
	}

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
		elseif ( in_array($processing_step, array('card_returns','card_corrections','card_post')) )
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

	public function Insert_Card_Process_Row($row_data)
	{
            $query = "-- /* SQL LOCATED IN file=" . __FILE__ . " line=" . __LINE__ . " method=" . __METHOD__ . " */
                INSERT INTO card_process
                    (
                        date_created,
                        card_info_id,
			application_id,
                        amount,
                        process_status
                    )
                VALUES
                    (
                        '".date("Y-m-d H:m:s")."'," .
                        $row_data['card_info_id'] . "," .
                        $row_data['application_id'] . "," .
                        $row_data['amount'] . "," .
                        "'sent'
                    )
            ";

            $result = $this->db->Query($query);
            $card_process_id = $this->db->lastInsertId();

            return $card_process_id;
	}

	public function Get_Card_Info($application_id)
	{
            $query = "-- /* SQL LOCATED IN file=" . __FILE__ . " line=" . __LINE__ . " method=" . __METHOD__ . " */
                SELECT card_info_id FROM card_info
                    WHERE application_id = " . $application_id . "
                    AND active_status = 'active'
                    ORDER BY date_modified
                    LIMIT 1
            ";

            $result = $this->db->Query($query);

            if ($row = $result->fetch(PDO::FETCH_OBJ))
            {
                    return $row->card_info_id;
            }
            return FALSE;
	}

	public function Update_Card_Process_Row($data, $response)
	{
            $status_array = array(1=>'complete',2=>'failed',3=>'failed',4=>'sent');
            
            $query = "-- /* SQL LOCATED IN file=" . __FILE__ . " line=" . __LINE__ . " method=" . __METHOD__ . " */
                UPDATE card_process
                    SET
                        date_modified = '" . date('Y-m-d H:i:s') . "',
                        process_status = '" . $status_array[$response->response_code] . "',
                        transaction_id = '" . $response->transaction_id . "',
                        result_code = '" . $response->result . "',
                        result_subcode = '" . $response->response_subcode . "',
                        reason_code = '" . $response->response_reason_code . "',
                        authorization_code = '" . $response->authorization_code . "',
                        avs_response = '" . $response->avs_response . "',
                        card_code_response = '" . $response->card_code_response . "',
                        cavv_response = '" . $response->cavv_response . "'
                WHERE card_process_id = '".$data['card_process_id']."'";
            $result = $this->db->Query($query);
            return $result;
	}

	public function Update_Transaction_Register($transaction_register_id,$status,$card_process_id)
	{
            $agent_id = Fetch_Current_Agent($this->server);

            //Set_Loan_Snapshot($transaction_register_id,"pending");

            $query = "-- /* SQL LOCATED IN file=" . __FILE__ . " line=" . __LINE__ . " method=" . __METHOD__ . " */ 
                UPDATE transaction_register ".
                "SET ".
                    "transaction_status = '" . $status . "', " .
                    "modifying_agent_id = " . $agent_id . ", " .
                    "card_process_id = " . $card_process_id . " " .
                "WHERE ".
                    "transaction_register_id = ".$transaction_register_id;
            $result = $this->db->Query($query);

            return $result;
	}


	public function Set_Total_Amount($total)
	{
		$this->total_amount = $total;
	}

	public function Set_Batch_Date($batch_date)
	{
		$this->batch_date = $batch_date;
	}
	
	public function setCardFatalFlag($application_id)
	{
	    $app = 	ECash::getApplicationByID($application_id);
	    $flags = $app->getFlags();
	    
	    // only set it if its not set already
	    $flags->set('has_fatal_card_failure');
	    $flags->set('had_fatal_card_failure');
	    
	    return 0;
	}

    }

?>
