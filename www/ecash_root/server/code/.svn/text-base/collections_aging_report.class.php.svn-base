<?php

require_once( SERVER_CODE_DIR . "base_report_query.class.php" );

/**
 * Collections Aging Report for HMS
 * 
 * 
 *
 */
class Collections_Aging_Report_Query extends Base_Report_Query
{
	const TIMER_NAME    = "Collections Aging Report Query - New";
	const ARCHIVE_TIMER = "Collections Aging Report Query - Archive";
	const CLI_TIMER     = "CLI - ";

	// # days worth of reports
	const MAX_SAVE_DAYS = "30";

	public function __construct(Server $server)
	{
		parent::__construct($server);
		
		$this->status_ids = null;
		
		$this->Add_Status_Id('arrangements_failed',           array('arrangements_failed', 'arrangements', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('made_arrangements',          array('current',             'arrangements', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('arrangements_hold',             array('hold',                'arrangements', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('active',             array('active',  'servicing', 'customer', '*root'));
		$this->Add_Status_Id('past_due',             array('past_due',  'servicing', 'customer', '*root'));
		$this->Add_Status_Id('collections_new',             array('new',  'collections', 'customer', '*root'));
		$this->Add_Status_Id('collections_contact',             array('dequeued', 'contact',  'collections', 'customer', '*root'));
		$this->Add_Status_Id('collections_contact_queued',             array('queued', 'contact',  'collections', 'customer', '*root'));
		$this->Add_Status_Id('skip_trace',             array('skip_trace',  'collections', 'customer', '*root'));
		$this->Add_Status_Id('collections_(dequeued)',             array('indef_dequeue',  'collections', 'customer', '*root'));
		$this->Add_Status_Id('contact_follow up',             array('follow_up', 'contact',  'collections', 'customer', '*root'));
		$this->Add_Status_Id('bankruptcy_notification',             array('unverified',  'bankruptcy','collections', 'customer', '*root'));
		$this->Add_Status_Id('bankruptcy_verified',             array('verified',  'bankruptcy', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('amortization',             array('amortization',  'bankruptcy', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('servicing_hold',             array('hold',  'servicing', 'customer', '*root'));
		$this->Add_Status_Id('qc_arrangements',             array('arrangements',  'quickcheck', 'collections','customer', '*root'));
		$this->Add_Status_Id('qc_ready',             array('ready',  'quickcheck','collections', 'customer', '*root'));
		$this->Add_Status_Id('qc_sent',             array('sent',  'quickcheck','collections', 'customer', '*root'));
		$this->Add_Status_Id('qc_return',             array('return',  'quickcheck','collections', 'customer', '*root'));
		$this->Add_Status_Id('second_tier_(pending)',             array('pending',  'external_collections', '*root'));
		
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
	private function Get_Collections_Status_Ids()
	{
		return implode( ",", array($this->arrangements_failed, $this->made_arrangements, $this->arrangements_hold, $this->collections_new, $this->collections_contact, $this->collections_contact_queued, $this->bankruptcy_notification, $this->bankruptcy_verified, $this->amortization) );
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
		foreach( $data as $co => $list )
		{
			foreach($list as $line)
			{
					$save_query = "
						-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
						INSERT IGNORE INTO resolve_collections_aging_report (
							date_created,
							company_id,
							total_open,
							total_amount,
							total_principal,
							total_interest,
							total_fee,
							total_del,
							total_amount_del,
							new_collections,
							new_collections_amount,
							num_pending,
							amount_pending,
							num_complete,
							amount_complete,
							ct1,
							amt1,
							ct2,
							amt2,	
							ct3,
							amt3,	
							ct4,
							amt4,
							amount_recovered)
						VALUES
						";
	
					
	
					// All this comes from the database, no escaping necessary
					$company_id = $co;
					$total_num  =  number_format($line['total_open'], 0, ".", "");
					$total_amt =  number_format($line['total_amount'], 2, ".", "");
					$total_princ     =  number_format($line['total_principal'], 2, ".", "");
					$total_interest  = number_format($line['total_interest'], 2, ".", "");     
					$total_fees         =  number_format($line['total_fee'], 2, ".", "");
	
					$total_num_del  =  number_format($line['total_del'], 0, ".", "");
					$total_amt_del     = number_format($line['total_amount_del'], 2, ".", "");
					$num_new_returns  = number_format($line['new_collections'], 0, ".", "");
					$amt_new_returns    = number_format($line['new_collections_amount'], 2, ".", "");
					$num_pending  = number_format($line['num_pending'], 0, ".", "");
					$amt_pending      = number_format($line['amount_pending'], 2, ".", "");
					$num_complete   = number_format($line['num_complete'], 0, ".", "");
					$amt_complete  = number_format($line['amount_complete'], 2, ".", "");
					$ct1       = number_format($line['ct1'], 0, ".", "");
					$amt1    = number_format($line['amt1'], 2, ".", "");
					$ct2  = number_format($line['ct2'], 0, ".", "");
					$amt2       = number_format($line['amt2'], 2, ".", "");
					$ct3    = number_format($line['ct3'], 0, ".", "");
					$amt3 = number_format($line['amt3'], 2, ".", "");
					$ct4     = number_format($line['ct4'], 0, ".", "");
					$amt4     = number_format($line['amt4'], 2, ".", "");
					$recovery_amt     = number_format($line['amount_recovered'], 2, ".", "");
					$date_created = $line['today_date'];
					
	
					$save_query .= "
						( '{$date_created}',    {$company_id}, {$total_num},  {$total_amt},
						 {$total_princ}, {$total_interest}, {$total_fees}, {$total_num_del},{$total_amt_del},{$num_new_returns},  {$amt_new_returns},        {$num_pending}, {$amt_pending},
						 {$num_complete},    {$amt_complete}, {$ct1}, {$amt1},   {$ct2}, {$amt2}, {$ct3}, {$amt3},
						 {$ct4}, {$amt4}, {$recovery_amt})
						";
			
					$db = ECash::getMasterDb();
					//$save_result = $this->mysqli->Query($save_query);
					$save_result = $db->Query($save_query);
				}				
			
		}
	}

	public function Fetch_Data($start_date,  $end_date, $company_id, $mode = null)
	{
		$year  = substr($start_date, 0, 4);
		$month = substr($start_date, 4, 2);
		$day   = substr($start_date, 6, 2);
		$timestamp = mktime(0, 0, 0, $month, $day, $year);

		// CLI or web?
		if( ! empty($mode) && strtolower($mode) == 'cli' )
			$timer = self::CLI_TIMER;
		else
			$timer = "";

		// Recent, should be in saved table
		$data = array();
		$timer .= self::ARCHIVE_TIMER;
		$data = $this->Fetch_Past_Data($start_date,  $end_date,  $company_id, $timer);
		
		if (count($data) > 0)
			return $data;
		else
			return null;
		
	}
	
	private function Delete_Old_Data()
	{
		// DELETE old data
		$delete_query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			DELETE FROM resolve_collections_aging_report
			 WHERE report_date <= DATE_SUB(CURRENT_DATE(), INTERVAL " . self::MAX_SAVE_DAYS . " DAY)
			";
	}

	private function Fetch_Past_Data($start_date, $end_date, $company_id, $timer)
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
				ar.date_created   AS today_date,
				sum(ar.total_open)       AS total_open,
				sum(ar.total_amount)           AS total_amount,
				sum(ar.total_principal)               AS total_principal,
				sum(ar.total_interest)          AS total_interest,
				sum(ar.total_fee)            AS total_fee,
				sum(ar.total_del) AS total_del,
				sum(ar.total_amount_del)           	AS total_amount_del,
				sum(ar.new_collections)       AS num_new_returns,
				sum(ar.new_collections_amount)   AS amount_new_returns,
				sum(ar.num_pending)            AS num_pending,
				sum(ar.amount_pending)            AS amount_pending,
				sum(ar.num_complete)                 AS num_complete,
				sum(ar.amount_complete)            AS amount_complete,
				sum(ar.ct1)            AS ct1,
				sum(ar.amt1)                 AS amt1,
				sum(ar.ct2)            AS ct2,
				sum(ar.amt2)                 AS amt2,	
				sum(ar.ct3)            AS ct3,
				sum(ar.amt3)                 AS amt3,	
				sum(ar.ct4)            AS ct4,
				sum(ar.amt4)                 AS amt4,
				sum(ar.amount_recovered)      AS amount_recovered

			FROM
				resolve_collections_aging_report ar
			WHERE
				ar.date_created between '{$start_date}' and '{$end_date}'
			
			 AND	ar.company_id           IN ({$company_list})
			group by date_created
			ORDER BY date_created
			";
		//echo '<pre>' .$past_query .'</pre>';
		$past_result = $this->db->Query($past_query);

		$data = array();
		$grands = array();
		while( $row = $past_result->fetch(PDO::FETCH_ASSOC) )
		{
			$data['company'][] = $row;
		}
		$this->timer->stopTimer( $timer );

		return $data;
	}

	public function Fetch_Current_Data($specific_date, $company_id, $timer, $save = false)
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
		$collection_status_ids = $this->Get_Collections_Status_Ids();
		$arrangement_status_ids  = $this->Get_Arrangement_Status_Ids();
		$principal_type_ids      = $this->Get_Transaction_Type_Ids($company_list, 'principal');
		$principal_non_disbursement_type_ids      = $this->Get_Transaction_Type_Ids($company_list, 'principal_non_disbursement');
		$fee_type_ids            = $this->Get_Transaction_Type_Ids($company_list, 'fee');
		$service_charge_type_ids = $this->Get_Transaction_Type_Ids($company_list, 'service_charge');
		$all_type_ids = "{$principal_type_ids},{$fee_type_ids},{$service_charge_type_ids}";
		$alt_date = date('Y-m-d', strtotime($specific_date));
		$prev_day = date('Y-m-d', strtotime('-1 day',strtotime($specific_date)));

		// For each Application Id
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
		Select '$alt_date' as today_date,
		a.company_id,
		a.name_short as company_name,
		count(a.application_id) as total_open,
		sum(a.total_amount) as total_amount,
		sum(a.total_principal) as total_principal,
		sum(a.total_interest) as total_interest,
		sum(a.total_fee) as total_fee, 
		count(b.application_id) as total_del,
		sum(b.total_del_amount) as total_amount_del,
		new_return.num_returned as new_collections,
		new_return.amount_returned as new_collections_amount,
		sum(if(c.num_pending > 0, 1, 0)) as num_pending,
		sum(c.amount_pending) as amount_pending,
		sum(if(c.num_complete > 0, 1, 0)) as num_complete,
		sum(c.amount_complete) as amount_complete,
		sum(if(d.collections_age > 0  and d.collections_age < 30, 1, 0)) as ct1,
		sum(if(d.collections_age > 0  and d.collections_age < 30, b.total_del_amount, 0)) as amt1,
		sum(if(d.collections_age > 29  and d.collections_age < 60, 1, 0)) as ct2,
		sum(if(d.collections_age > 29  and d.collections_age < 60, b.total_del_amount, 0)) as amt2,
		sum(if(d.collections_age > 60  and d.collections_age < 90, 1, 0)) as ct3,
		sum(if(d.collections_age > 60  and d.collections_age < 90, b.total_del_amount, 0)) as amt3,
		sum(if(d.collections_age > 90, 1, 0)) as ct4,
		sum(if(d.collections_age > 90, b.total_del_amount, 0)) as amt4,
		sum(c.amount_recovered) as amount_recovered
				
		from 
		(
			select
			application.application_id,
			application.company_id,
			company.name_short,
			sum(event_amount.amount) as total_amount,
			sum(if(event_amount_type.name_short =  'principal' , event_amount.amount, 0)) as total_principal,
			sum(if(event_amount_type.name_short =  'service_charge' , event_amount.amount, 0)) as total_interest,
			sum(if(event_amount_type.name_short =  'fee' , event_amount.amount, 0)) as total_fee 
			from
			application join event_schedule using (application_id) 
			join transaction_register using (event_schedule_id)
			join event_amount using (event_schedule_id)
			join event_amount_type using (event_amount_type_id)
			join company on application.company_id = company.company_id
			where application_status_id in ($status_ids) 
			and transaction_register.transaction_status <> 'failed'
			and application.date_created <= '$alt_date'
			group by application_id,application.company_id, company.name_short
			having total_amount > 0
		) as a
		left outer join
		(
			select
			application.application_id,
			sum(event_amount.amount) as total_del_amount,
			sum(if(event_amount_type.name_short =  'principal' , event_amount.amount, 0)) as total_del_principal,
			sum(if(event_amount_type.name_short =  'service_charge' , event_amount.amount, 0)) as total_del_interest,
			sum(if(event_amount_type.name_short =  'fee' , event_amount.amount, 0)) as total_del_fee 
			from
			application join event_schedule using (application_id) 
			join transaction_register using (event_schedule_id)
			join event_amount using (event_schedule_id)
			join event_amount_type using (event_amount_type_id)
					where application_status_id in ($collection_status_ids)
			and transaction_register.transaction_status <> 'failed'
			and application.date_created <= '$alt_date'
			group by application_id
			having total_del_amount > 0
		) as b on a.application_id = b.application_id 
		left outer join
		(
			select
			application.application_id,
			sum(transaction_register.amount) as total_del_amount,
			sum(if(transaction_register.transaction_status = 'pending' and transaction_register.amount < 0,1,0)) as num_pending,
			abs(sum(if(transaction_register.transaction_status = 'pending' and transaction_register.amount < 0,transaction_register.amount,0))) as amount_pending,
			sum(if(transaction_register.transaction_status = 'complete' and transaction_register.date_modified > '$alt_date' and transaction_register.amount < 0,1,0)) as num_complete,
			abs(sum(if(transaction_register.transaction_status = 'complete' and transaction_register.date_modified > '$alt_date' and transaction_register.amount < 0,transaction_register.amount,0))) as amount_complete,
			abs(sum(if(transaction_type.name_short = 'ext_recovery_princ' or transaction_type.name_short = 'ext_recovery_fees', transaction_register.amount, 0))) as amount_recovered	
			from
			application join event_schedule using (application_id) 
			join transaction_register using (event_schedule_id)
			join transaction_type using (transaction_type_id)
			where application_status_id in ($collection_status_ids)
			and transaction_register.transaction_status <> 'failed'
			and application.date_created <= '$alt_date'
			group by application_id
			having total_del_amount > 0
		) as c on a.application_id = c.application_id
		left outer join 
		(
			select 
			max(DATEDIFF(NOW(),ifnull(sv.date_created,now()))) as collections_age,	
			sv.application_id
			from status_history sv 
			where sv.application_status_id in ({$this->collections_new},{$this->collections_contact},{$this->collections_contact_queued})
			group by application_id
				
		) as d on a.application_id = d.application_id
			left outer join (
				    SELECT
				        DATE_FORMAT(ar.date_created, '%m/%d/%Y')             AS return_date,
				        ach.company_id,
				        count(*)          AS num_returned,
				        SUM(ach.amount) AS amount_returned
					    FROM ach_report AS ar
				    JOIN ach ON ach.ach_report_id = ar.ach_report_id
				    WHERE  ach.ach_type = 'debit'
				    AND ach.ach_status = 'returned'
				    GROUP BY return_date, ach.company_id 
		
			) as new_return on new_return.return_date =  DATE_FORMAT('$alt_date', '%m/%d/%Y') and new_return.company_id = a.company_id
		where a.company_id in ($company_list)
		group by today_date, a.company_id
			
		";
	
		// This report should ALWAYS hit the master.
//		echo '<pre>' .$query .'</pre>';
		//exit();
		$db = ECash::getMasterDb();
		$result = $db->Query($query);
		$grands = array();
		$data = array();
		while( $row = $result->fetch(PDO::FETCH_ASSOC) )
		{
			$co = $row['company_id'];
			$data[$co][] = $row;
		}
		$this->timer->stopTimer( $timer );
		if ($save) {
			$this->Save_Report_Data($data, $specific_date);
		}		
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
