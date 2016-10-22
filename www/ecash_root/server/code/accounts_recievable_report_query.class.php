<?php

require_once( SERVER_CODE_DIR . "base_report_query.class.php" );

/**
 * Accounts Recievable Report for AALM
 * - Note: Impact has it's own copy
 * 
 * Note: For 17589 I've added an overloaded Get_Module_Mode method
 *       for this specific report.
 *
 */
class AR_Report_Query extends Base_Report_Query
{
	const TIMER_NAME    = "AR Report Query - New";
	const ARCHIVE_TIMER = "AR Report Query - Archive";
	const CLI_TIMER     = "CLI - ";

	// # days worth of reports
	const MAX_SAVE_DAYS = "30";

	public function __construct(Server $server)
	{
		parent::__construct($server);
		
		$this->status_ids = null;
		
		$this->Add_Status_Id('arrangements_failed', array('arrangements_failed', 'arrangements', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('made_arrangements', array('current', 'arrangements', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('arrangements_hold', array('hold', 'arrangements', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('active', array('active', 'servicing', 'customer', '*root'));
		$this->Add_Status_Id('past_due', array('past_due', 'servicing', 'customer', '*root'));
		$this->Add_Status_Id('collections_new', array('new', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('collections_contact', array('dequeued', 'contact', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('collections_contact_queued', array('queued', 'contact',  'collections', 'customer', '*root'));
		$this->Add_Status_Id('skip_trace', array('skip_trace', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('collections_(dequeued)', array('indef_dequeue', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('contact_follow up', array('follow_up', 'contact', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('bankruptcy_notification', array('unverified', 'bankruptcy','collections', 'customer', '*root'));
		$this->Add_Status_Id('bankruptcy_verified', array('verified',  'bankruptcy', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('amortization', array('amortization', 'bankruptcy', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('servicing_hold', array('hold', 'servicing', 'customer', '*root'));
		$this->Add_Status_Id('qc_arrangements', array('arrangements', 'quickcheck', 'collections','customer', '*root'));
		$this->Add_Status_Id('qc_ready', array('ready', 'quickcheck', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('qc_sent', array('sent', 'quickcheck','collections', 'customer', '*root'));
		$this->Add_Status_Id('qc_return', array('return', 'quickcheck','collections', 'customer', '*root'));
		$this->Add_Status_Id('second_tier_(pending)', array('pending', 'external_collections', '*root'));
		$this->Add_Status_Id('chargeoff', array('chargeoff', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('collections_rework', array('collections_rework', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('cccs', array('cccs', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('refi', array('refi', 'servicing', 'customer', '*root'));
		$this->Add_Status_Id('canceled', array('canceled', 'applicant', '*root'));
		
		$this->collections_new = Search_Status_Map('new::collections::customer::*root', Fetch_Status_Map(FALSE) );
		$this->collections_contact = Search_Status_Map('dequeued::contact::collections::customer::*root', Fetch_Status_Map(FALSE) );
	}

	/**
	 * Gets the application_status_id's for the cashline branch to ensure the report does not include them
	 * @returns array ids
	 */
	private function Get_Cashline_Ids()
	{
		return implode( ",", array($this->cashline, $this->in_cashline, $this->pending_transfer) );
	}

	private function Get_Arrangement_Status_Ids()
	{
		return implode( ",", array($this->arrangements_failed, $this->made_arrangements, $this->arrangements_hold) );
	}
	
	private function Get_Status_Ids()
	{
		return implode( ",", $this->status_ids);
	}

	private function Get_Transaction_Type_Ids($company_list, $type)
	{
		switch( $type )
		{
			case 'principal':
				//				$name_short = " AND	name_short NOT LIKE '%\\_fee%'";
				$name_short = "";
				$principal  = " AND	affects_principal = 'yes'";
				break;
			
			case 'principal_non_disbursement':
				$name_short =  " AND	name_short not IN ('loan_disbursement','check_disbursement', 'moneygram_disbursement')";
				$principal  = " AND	affects_principal = 'yes'";
				break;
			case 'fee':
				$name_short = " AND	name_short IN ('payment_fee_ach_fail','assess_fee_ach_fail')";
				$principal  = "";
				//				$principal  = " AND	affects_principal = 'no'";
				break;
			case 'service_charge':
				$name_short = "AND name_short IN ('assess_service_chg', 'converted_sc_event', 'payment_service_chg', 'full_balance', 'personal_check_fees', 'h_fatal_cashline_return', 'h_nfatal_cashline_return', 'payout_fees')
							   OR name_short like '%Fees%'";
				$principal  = "";
				//				$principal  = " AND	affects_principal = 'no'";
				break;
			default:
				$name_short = '';
				$principal  = '';
				break;
		}

		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				event_type_id
			FROM
				transaction_type
				JOIN event_transaction USING (transaction_type_id)
			WHERE
				transaction_type.company_id    IN ({$company_list})
			
			{$name_short}
			{$principal}
		";
	//	echo '<pre>'.$query.'</pre>';
		$result = $this->db->Query($query);

		$ids = '';
		while( $row = $result->fetch(PDO::FETCH_OBJ) )
		{
			$ids .= $row->event_type_id . ",";
		}

		return (strlen($ids) > 0 ? substr($ids, 0, -1) : "");
	}

	/**
	 * Saves the data for 1 day of the report and deletes old entries
	 * @param array  $data array as returned from Fetch_Current_Data()
	 * @param string $date MySQL5 formatted date (YYYY-MM-DD)
	 * @access public
	 * @throws Exception
	 */
	public function Save_Report_Data($data, $date)
	{
		// Check the date passed
		if( strlen($date) == 10 )
		{
			$year  = substr($date, 0, 4);
			$month = substr($date, 5, 2);
			$day   = substr($date, 8, 2);
		}
		elseif( strlen($date) == 8 )
		{
			$year  = substr($date, 0, 4);
			$month = substr($date, 4, 2);
			$day   = substr($date, 6, 2);
		}
		else
		{
			$year  = null;
			$day   = null;
			$month = null;
		}

		if( ! checkdate($month, $day, $year) )
		{
			throw new Exception( "Payments Due Report [" . __METHOD__ . ":" . __LINE__ . "] invalid date parameter:  '{$date}'" );
		}

		// Quote the date for the query
		$date = "'{$date}'";

		// First make room
		// This is disabled for now.
		//$this->Delete_Old_Data();
		
		$company_ids = $this->Get_Company_Ids();

		foreach( $data as $co => $line )
		{
			for( $x = 0 ; $x < count($line) ; ++$x )
			{
				if($line[$x]['application_id'] != 'Totals:')
				{
					$save_query = "
						-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
						INSERT IGNORE INTO resolve_ar_report
							(date_created,company_name_short ,application_id ,name_last ,name_first,status,prev_status,
					fund_date ,company_id,fund_age ,collection_age ,status_age,payoff_amt ,principal_pending ,principal_fail,principal_total  ,
					fees_pending , fees_fail, fees_total,service_charge_pending,service_charge_fail ,service_charge_total,nsf_ratio)
						VALUES
						";
	
					
	
					// All this comes from the database, no escaping necessary
					$company_id = $company_ids[strtolower($co)];
					$co_name    =  $this->db->quote(strtolower($co))              ;
					$name_last  =  $this->db->quote($line[$x]['name_last'])         ;
					$name_first =  $this->db->quote($line[$x]['name_first'])        ;
					$status     =  $this->db->quote(strtolower($line[$x]['status']));
					$prev_status  = $this->db->quote($line[$x]['prev_status'])          ;
					$fund_date         =  $this->db->quote($line[$x]['fund_date'])           ;
	
					$fund_age  =  $this->db->quote($line[$x]['fund_age'])        ;
					$app_id     = $line[$x]['application_id'];
					$collection_age  = $line[$x]['collection_age'] ;
					$status_age    = $line[$x]['status_age'];
					$principal_pending  = number_format($line[$x]['principal_pending'],      2, ".", "");
					$fees_pending       = number_format($line[$x]['fees_pending'],           2, ".", "");
					$service_charge_pending    = number_format($line[$x]['service_charge_pending'], 2, ".", "");
					$principal_fail  = number_format($line[$x]['principal_fail'],      2, ".", "");
					$fees_fail       = number_format($line[$x]['fees_fail'],           2, ".", "");
					$service_charge_fail    = number_format($line[$x]['service_charge_fail'], 2, ".", "");
					$principal_total  = number_format($line[$x]['principal_total'],      2, ".", "");
					$fees_total       = number_format($line[$x]['fees_total'],           2, ".", "");
					$service_charge_total    = number_format($line[$x]['service_charge_total'], 2, ".", "");
					$nsf_ratio = number_format($line[$x]['nsf_ration'],     2, ".", "");
					$payoff_amt     = number_format($line[$x]['payoff_amt'],     2, ".", "");
					$date_created = $line[$x]['date_created'];
					
	
					$save_query .= "
						( '{$date_created}',    {$co_name}, {$app_id},  {$name_last},
						 {$name_first}, {$status}, {$prev_status}, {$fund_date},{$company_id},{$fund_age},  {$collection_age},        {$status_age}, {$payoff_amt},
						 {$principal_pending},    {$principal_fail}, {$principal_total}, {$fees_pending},   {$fees_fail}, {$fees_total}, {$service_charge_pending}, {$service_charge_fail},
						 {$service_charge_total}, {$nsf_ratio})
						";
					
			
					
			
					$db = ECash::getMasterDb();
					
					//$save_result = $this->mysqli->Query($save_query);
					$save_result = $db->Query($save_query);
				}
			}
		}
	}

	public function Fetch_Payments_Due_Data($specific_date,  $company_id, $mode = null, $save = false)
	{
		$year  = substr($specific_date, 0, 4);
		$month = substr($specific_date, 4, 2);
		$day   = substr($specific_date, 6, 2);
		$timestamp = mktime(0, 0, 0, $month, $day, $year);

		// CLI or web?
		if( ! empty($mode) && strtolower($mode) == 'cli' )
			$timer = self::CLI_TIMER;
		else
			$timer = "";

		// Recent, should be in saved table
		$data = array();
		if( $timestamp < mktime(0, 0, 0)  && !$save)
		{
			$timer .= self::ARCHIVE_TIMER;
			$data = $this->Fetch_Past_Data($specific_date,  $company_id, $timer);
		
		
			if (count($data) > 0)
				return $data;
		}
		
		$timer .= self::TIMER_NAME;
		$data = $this->Fetch_Current_Data($specific_date,  $company_id, $timer);
		
		if ($save) {
			$this->Save_Report_Data($data, $specific_date);
		}
		return $data;
	}
	
	private function Delete_Old_Data()
	{
		// DELETE old data
		$delete_query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			DELETE FROM resolve_payments_due_report
			 WHERE report_date <= DATE_SUB(CURRENT_DATE(), INTERVAL " . self::MAX_SAVE_DAYS . " DAY)
			";
	}

	private function Fetch_Past_Data($specific_date, $company_id, $timer)
	{
		$this->timer->startTimer( $timer );

		$data = array();

		if(isset($_SESSION) && is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

	//	$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		$past_query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT DISTINCT
				ar.date_created   AS date_created,
				ar.company_name_short   AS company_name,
				ar.application_id       AS application_id,
				ar.name_last            AS name_last,
				ar.name_first           AS name_first,
				ar.status               AS status,
				ar.prev_status          AS prev_status,
				
				ar.fund_date            AS fund_date,
				ar.company_id			AS company_id,
				ar.fund_age AS fund_age,
				
				ar.collection_age           	AS collection_age,
				
				ar.status_age       AS status_age,
				ar.payoff_amt AS payoff_amt,
				ar.service_charge_total            AS service_charge_total,
				ar.principal_total            AS principal_total,
				ar.fees_total                 AS fees_total,
				ar.principal_pending            AS principal_pending,
				ar.principal_fail            AS principal_fail,
				ar.fees_pending                 AS fees_pending,
				ar.service_charge_pending      AS service_charge_pending,
				ar.service_charge_fail      AS service_charge_fail,
				ar.fees_fail      AS fees_fail,
				ar.nsf_ratio           AS nsf_ratio
			FROM
				resolve_ar_report ar
			WHERE
				ar.date_created = '{$specific_date}'
			
			 AND	ar.company_id           IN ({$company_list})
			
			ORDER BY company_name_short
			";
		//echo '<pre>' .$past_query .'</pre>';
		$past_result = $this->db->Query($past_query);

		$data = array();
		$grands = array();
		while( $row = $past_result->fetch(PDO::FETCH_ASSOC) )
		{
			$co = strtoupper($row['company_name']);
			$date_created = $row['date_created'];
		//	unset($row['company_name']);
			
		//	$this->Get_Module_Mode($row);
			$grands['payoff_amt']  += $row['payoff_amt'];
			$grands['principal_total'] += $row['principal_total'];
			$grands['principal_pending'] += $row['principal_pending'];
			$grands['fees_pending']      += $row['fees_pending'];
			$grands['fees_total']        += $row['fees_total'];
			$grands['service_charge_pending'] += $row['service_charge_pending'];
			$grands['service_charge_total'] += $row['service_charge_total'];
			$grands['principal_fail']      += $row['principal_fail'];
			$grands['fees_fail']           += $row['fees_fail'];
			$grands['service_charge_fail'] += $row['service_charge_fail'];
			
			$row['name_first'] = ucfirst($row['name_first']);
			$row['name_last'] = ucfirst($row['name_last']);
			$row['status'] = ucfirst($row['status']);			
			
			if($row['payoff_amt'] != 0)
			{
				$row['nsf_ratio'] = 100 * (( $row['principal_fail'] +  $row['service_charge_fail'])/($row['payoff_amt']));
			}
			else
			{
				$row['nsf_ratio'] = 0;
			}
		

			$data[$co][] = $row;
		}
		$this->timer->stopTimer( $timer );

		return $data;
	}

	public function Fetch_Current_Data($specific_date, $company_id, $timer)
	{
		$this->timer->startTimer( $timer );

		if(isset($_SESSION) && is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		$data = array();

	

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

			// get ID lists for query
		$status_ids            = $this->Get_Status_Ids();
		$arrangement_status_ids  = $this->Get_Arrangement_Status_Ids();
		$principal_type_ids      = $this->Get_Transaction_Type_Ids($company_list, 'principal');
		$principal_non_disbursement_type_ids      = $this->Get_Transaction_Type_Ids($company_list, 'principal_non_disbursement');
		$fee_type_ids            = $this->Get_Transaction_Type_Ids($company_list, 'fee');
		$service_charge_type_ids = $this->Get_Transaction_Type_Ids($company_list, 'service_charge');
		$all_type_ids = "{$principal_type_ids},{$fee_type_ids},{$service_charge_type_ids}";
		$alt_date = date('Y-m-d', strtotime($specific_date));
		

		// For each Application Id
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				
				UPPER(c.name_short) company_name_short,
				a.application_id application_id,
				a.name_last name_last,
				a.name_first name_first,
				ass.name status,
				a.application_status_id,
				lt.name_short as loan_type,
				DATEDIFF(now(),a.date_fund_actual) as fund_age,
				DATEDIFF(now(),(select sv.date_created from status_history sv where sv.application_id = a.application_id order by sv.date_created desc limit 0,1 )
				)	as status_age,	
				if(
            		ass.application_status_id = {$this->collections_new} 
            	or 
            		ass.application_status_id = {$this->collections_contact} 
            	or 
            		ass.application_status_id = {$this->collections_contact_queued},	
            		DATEDIFF(now(),
            					(select sv.date_created from status_history sv where sv.application_id = a.application_id and sv.application_status_id in ({$this->collections_new},{$this->collections_contact},{$this->collections_contact_queued}) order by sv.date_created asc limit 0,1 )
            		 ),		
				
					DATEDIFF(now(),if(
						ISNULL(
							(select sv.date_created from status_history sv where sv.application_id = a.application_id and sv.application_status_id in ({$this->collections_new},{$this->collections_contact},{$this->collections_contact_queued}) order by sv.date_created asc limit 0,1 )
						),
						now(),
						(select sv.date_created from status_history sv where sv.application_id = a.application_id and sv.application_status_id in ({$this->collections_new},{$this->collections_contact},{$this->collections_contact_queued}) order by sv.date_created asc limit 0,1 )
					)
				)
				
				) as collection_age,
												
				a.date_fund_actual as fund_date,
				a.company_id company_id,
				(select name from status_history sv join application_status as2 on sv.application_status_id=as2.application_status_id where sv.application_id = a.application_id and as2.name != ass.name order by sv.date_created desc limit 0,1 )
				 as prev_status,
				CURDATE() as date_created,
							
					(
						SELECT
							SUM(
								IF(
									tr.transaction_register_id IS NULL, 
									es.amount_principal + es.amount_non_principal, 
									tr.amount
								)
							)
						FROM
							event_schedule es
							LEFT JOIN transaction_register tr USING (event_schedule_id)
						WHERE
							es.date_event <= '{$specific_date}' AND
							es.application_id = a.application_id AND
						 
								tr.transaction_status IN ('complete', 'pending')
							
					) 
				 as payoff_amt,
				 (	SUM(IF(
					es.event_type_id IN ({$principal_type_ids}) AND eat.name_short = 'principal' and tr.transaction_status IN ('complete', 'pending'),
					ea.amount,
					0
				)) + SUM(IF(
					es.event_type_id IN ({$principal_non_disbursement_type_ids}) and es.origin_id is null AND eat.name_short = 'principal' and tr.transaction_status IN ('failed') ,
					ea.amount,
					0
				)))
						principal_pending,
				SUM(IF(
					es.event_type_id IN ({$principal_type_ids}) AND eat.name_short = 'principal' and tr.transaction_status IN ('complete', 'pending'),
					ea.amount,
					0
				)) principal_total,
				(SUM(IF(
					es.event_type_id IN ({$fee_type_ids}) AND eat.name_short = 'fee' and tr.transaction_status IN ('complete', 'pending'),
					ea.amount,
					0
				)) + SUM(IF(
					es.event_type_id IN ({$fee_type_ids}) and es.origin_id is null AND eat.name_short = 'fee' and tr.transaction_status IN ('failed') ,
					ea.amount,
					0
				))) fees_pending,
					SUM(IF(
					es.event_type_id IN ({$fee_type_ids}) AND eat.name_short = 'fee' and tr.transaction_status IN ('complete', 'pending'),
					ea.amount,
					0
				)) fees_total,	
				(SUM(IF(
					es.event_type_id IN ({$service_charge_type_ids}) AND eat.name_short = 'service_charge' and es.date_event <= '{$specific_date}' AND (
								es.event_status = 'scheduled' OR 
								tr.transaction_status IN ('complete', 'pending')
							),
					ea.amount,
					0
				)) + SUM(IF(
					es.event_type_id IN ({$service_charge_type_ids}) and es.origin_id is null AND eat.name_short = 'service_charge' and tr.transaction_status IN ('failed') ,
					ea.amount,
					0
				)) ) service_charge_pending,
ifnull((SELECT
        SUM(ea1.amount) as amount1
            FROM event_amount ea1
   JOIN event_amount_type eat1 ON ea1.event_amount_type_id = eat1.event_amount_type_id
            JOIN event_schedule es1 ON es1.event_schedule_id = ea1.event_schedule_id
            JOIN transaction_register tr1 ON tr1.transaction_register_id = ea1.transaction_register_id
            JOIN transaction_type tt1 ON tt1.transaction_type_id = tr1.transaction_type_id
            WHERE es1.application_id = a.application_id
		AND ea1.amount < 0
		AND eat1.name_short = 'principal'
                AND ((tt1.name_short IN ('repayment_principal') AND es1.context = 'generated')
	 OR es1.context = 'arrange_next'
	 OR es1.context = 'payout') 
	 AND tr1.transaction_status = 'failed'
         GROUP BY es1.application_id), 0) as principal_fail,
ifnull((SELECT
        SUM(ea1.amount) as amount1
            FROM event_amount ea1
   JOIN event_amount_type eat1 ON ea1.event_amount_type_id = eat1.event_amount_type_id
            JOIN event_schedule es1 ON es1.event_schedule_id = ea1.event_schedule_id
            JOIN transaction_register tr1 ON tr1.transaction_register_id = ea1.transaction_register_id
            JOIN transaction_type tt1 ON tt1.transaction_type_id = tr1.transaction_type_id
            WHERE es1.application_id = a.application_id
		AND ea1.amount < 0
		AND eat1.name_short = 'fee'
                AND ((tt1.name_short IN ('payment_fee_ach_fail') AND es1.context = 'generated')
	 OR es1.context = 'arrange_next'
	 OR es1.context = 'payout') 
	 AND tr1.transaction_status = 'failed'
         GROUP BY es1.application_id), 0) as fees_fail,
ifnull((SELECT
        SUM(ea1.amount) as amount1
            FROM event_amount ea1
   JOIN event_amount_type eat1 ON ea1.event_amount_type_id = eat1.event_amount_type_id
            JOIN event_schedule es1 ON es1.event_schedule_id = ea1.event_schedule_id
            JOIN transaction_register tr1 ON tr1.transaction_register_id = ea1.transaction_register_id
            JOIN transaction_type tt1 ON tt1.transaction_type_id = tr1.transaction_type_id
            WHERE es1.application_id = a.application_id
                AND ea1.amount < 0
		AND eat1.name_short = 'service_charge'
                AND ((tt1.name_short IN ('payment_service_chg') AND es1.context = 'generated')
	 OR es1.context = 'arrange_next'
	 OR es1.context = 'payout') 
	 AND tr1.transaction_status = 'failed'
         GROUP BY es1.application_id), 0) as service_charge_fail

		 	FROM 
				event_schedule es
				JOIN company c USING (company_id)
				JOIN event_amount ea USING (event_schedule_id)
				JOIN event_amount_type eat USING (event_amount_type_id)
				LEFT JOIN transaction_register tr USING(event_schedule_id)
				JOIN application a ON es.application_id = a.application_id
				JOIN application_status ass USING (application_status_id)
				JOIN loan_type lt USING (loan_type_id)
			WHERE 
				(
					tr.transaction_register_id = ea.transaction_register_id OR
					tr.transaction_register_id IS NULL
				) AND
				
				
				es.company_id IN ({$company_list}) AND
				es.event_status <> 'suspended' AND
				
				
				a.application_status_id IN ({$status_ids})
			GROUP BY
				company_name_short,
				application_id,
				name_last,
				name_first,
				status,
				company_id
			HAVING payoff_amt IS NOT NULL
			ORDER BY
				company_name_short,
				status
			
		";
	
		// This report should ALWAYS hit the master.
		//echo '<pre>' .$query .'</pre>';
		//exit();
		$db = ECash::getMasterDb();
		$result = $db->Query($query);
		$grands = array();
		$data = array();
		while( $row = $result->fetch(PDO::FETCH_ASSOC) )
		{
			$co = $row['company_name_short'];
			$id = $row['application_id'];
			$date_created = $row['date_created'];
			$this->Get_Module_Mode($row);

			// GF #9271: If you're going to do logic down here with the data, you can't really
			// have a SQL determined total without rewriting this logic within the query. This
			// report may be rewritten soon, so I have moved the calculation of
			// service_charge_total to down here. [benb]	
			if($row['payoff_amt'] != 0)
			{
				$row['nsf_ratio'] = 100 * ((-1 * $row['principal_fail'] + -1 * $row['service_charge_fail'])/($row['payoff_amt']));
			}
			else
			{
				$row['nsf_ratio'] = 0;
			}
			if(empty($row['nsf_ratio']))
				$row['nsf_ratio'] = '0.00';
				
			if($row['principal_pending'] < 0)
				$row['principal_pending'] = 0;

			if($row['service_charge_pending'] < 0)
				$row['service_charge_pending'] = 0;
				
			if($row['fees_pending'] < 0)
				$row['fees_pending'] = 0;


			$row['service_charge_total'] = -$row['service_charge_fail'] + $row['service_charge_pending'];
			
			$grands['payoff_amt']  += $row['payoff_amt'];
			$grands['principal_total'] += $row['principal_total'];
			$grands['principal_pending'] += $row['principal_pending'];
			$grands['fees_pending']      += $row['fees_pending'];
			$grands['fees_total']        += $row['fees_total'];
			$grands['service_charge_pending'] += $row['service_charge_pending'];
			$grands['service_charge_total'] += $row['service_charge_total'];
			$grands['principal_fail']      += -$row['principal_fail'];
			$grands['fees_fail']           += -$row['fees_fail'];
			$grands['service_charge_fail'] += -$row['service_charge_fail'];
				
			$data[$co][] = array(
				'application_id' => $row['application_id'],
				'company_name' => $row['company_name_short'],
				'name_last'    => ucfirst($row['name_last']),
				'name_first'   => ucfirst($row['name_first']),
				'status'       => $row['status'],
				'prev_status'  => $row['prev_status'],
				'fund_age'     => $row['fund_age'],
				'collection_age' => $row['collection_age'],
				'status_age'  => $row['status_age'],
				'date_created'   => $row['date_created'],
				'payoff_amt'      => $row['payoff_amt'],
				'principal_total' => $row['principal_total'],
				'principal_pending' => $row['principal_pending'],
				'fees_pending'      => $row['fees_pending'],
				'fees_total'        => $row['fees_total'],
				'service_charge_pending' => $row['service_charge_pending'],
				'service_charge_total' => $row['service_charge_total'],
				'principal_fail'      => -$row['principal_fail'],
				'fees_fail'           => -$row['fees_fail'],
				'service_charge_fail' => -$row['service_charge_fail'],
				'fund_date'         => $row['fund_date'],
				'nsf_ratio' => $row['nsf_ratio'],
				'module'         => isset($row['module']) ? $row['module'] : null,
				'mode'           => isset($row['mode']) ? $row['mode'] : null
			);
		}
		$this->timer->stopTimer( $timer );
		return $data;
	}
	
	/**
	 * Figures out the module and mode an application should be viewed in base on the app_status_id
	 * 
	 * NOTE: This is a modified copy from the report parent
	 * 
	 * @param array   &$row must include application_status_id & company_id, function adds module & mode indices
	 * @returns string $row['module']
	 * @returns string $row['mode']
	 * @access protected
	 */
	protected function Get_Module_Mode(&$row, $respect_current_company = TRUE)
	{
		if( $this->permissions == NULL)
		{
			return false;
		}
			
		if( ! isset($row['company_id']) )
			throw new Exception( "Need company_id for " . __METHOD__ . "." );
		if( ! isset($row['application_status_id']) )
			throw new Exception( "Need application_status_id for " . __METHOD__ . "." );

		switch($row['application_status_id'])
		{
			case $this->active:
			case $this->past_due:
				$row['module'] = 'loan_servicing';
				$row['mode']   = 'account_mgmt';
				break;

			default:				
				$row['module'] = 'collections';
				$row['mode']   = '';
				break;
		}

		// Now compare to acl

		// GF 8869:
		// This part has changed around a bit. When the method GetAllowedSections() is called
		// which populates $this->permissions another dimension was added to the array.
		// With the new ACL system, we can do a little something like this.
		
		// If no permission to use that section
		   // then try customer service
		// If no permission to use customer service
		   // send them to the section handling this app's status
		   // agent will get insufficient permissions error
		$section_id = $this->acl->Get_Section_Id($this->company_id, $row['module'], $row['mode']);

		// They're authorized for that section 
		if (isset($this->permissions[$this->company_id][$section_id]))
		{
			return true;
		}
			

		// If they're not authorized for the module deemed fit in the previous code, try this sane
		// pair.
		$row['module'] = 'loan_servicing';
		$row['mode']   = 'customer_service';
		
		// GF #13033: See if they're authorized to view the failover module/mode, if not, strip the
		// module and mode so no link is displayed
		$section_id = $this->acl->Get_Section_Id($this->company_id, $row['module'], $row['mode']);
		
		// They're authorized for the failover section.
		if (isset($this->permissions[$this->company_id][$section_id]))
		{
			return true;
		}

		// They're not even authorized for the failover, don't display a link.
		unset($row['module']);
		unset($row['mode']);

		return false;
	}
	
}

?>
