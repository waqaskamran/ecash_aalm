<?php

require_once( SERVER_CODE_DIR . "base_report_query.class.php" );

class Nonach_Payments_Due_Report_Query extends Base_Report_Query
{
	const TIMER_NAME    = "Non ACH Payments Due Report Query - New";
	const ARCHIVE_TIMER = "Non ACH Payments Due Report Query - Archive";
	const CLI_TIMER     = "CLI - ";

	// # days worth of reports
	const MAX_SAVE_DAYS = "30";

	public function __construct(Server $server)
	{
		parent::__construct($server);

		$this->Add_Status_Id('failed',           array('arrangements_failed', 'arrangements', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('current',          array('current',             'arrangements', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('hold',             array('hold',                'arrangements', 'collections', 'customer', '*root'));

		$this->Add_Status_Id('cashline',         array('queued',              'cashline',     '*root'));
		$this->Add_Status_Id('in_cashline',      array('dequeued',            'cashline',     '*root'));
		$this->Add_Status_Id('pending_transfer', array('pending_transfer',    'cashline',     '*root'));
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
		return implode( ",", array($this->failed, $this->current, $this->hold) );
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
			case 'fee':
				$name_short = " AND	name_short IN ('payment_fee_ach_fail','full_balance')";
				$principal  = "";
				//				$principal  = " AND	affects_principal = 'no'";
				break;
			case 'service_charge':
				$name_short = "AND name_short IN ('converted_sc_event', 'payment_service_chg', 'full_balance')
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
			SELECT
				event_type_id
			FROM
				transaction_type
				JOIN event_transaction USING (transaction_type_id)
			WHERE
				transaction_type.company_id    IN ({$company_list})
			 AND	clearing_type IN ('ach','external')
			{$name_short}
			{$principal}
		";
		$result = $this->db->query($query);

		$ids = '';
		while ($row = $result->fetch(PDO::FETCH_OBJ))
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
				$save_query = "
					INSERT INTO resolve_payments_due_report
						(date_created,  report_date, company_id, company_name_short, application_id, name_last,
						name_first,     status,      pay_period, direct_deposit,     principal,      fees,
						service_charge, total_due,   next_due,   loan_type,          first_time_due, pay_out,
						special_arrangements, event_schedule_id, application_status_id, is_ach)
					VALUES
					";

				// Reformat the next_due date into standard mysql format
				$next_due_split = explode("/", $line[$x]['next_due']);

				// All this comes from the database, no escaping necessary
				$company_id = $company_ids[strtolower($co)];
				$co_name    = $this->db->quote(strtolower($co));
				$name_last  = $this->db->quote($line[$x]['name_last']);
				$name_first = $this->db->quote($line[$x]['name_first']);
				$status     = $this->db->quote(strtolower($line[$x]['status']));
				$frequency  = $this->db->quote($line[$x]['frequency']);
				$dd         = $this->db->quote($line[$x]['dd']);
				$next_due   = $this->db->quote($next_due_split[2] . "-" . $next_due_split[0] . "-" . $next_due_split[1]);
				$loan_type  = $this->db->quote($line[$x]['loan_type']);
				$app_id     = $line[$x]['application_id'];
				$first_pay  = $line[$x]['first_payment'] ? 1 : 0;
				$special    = $line[$x]['special'] ? 1 : 0;
				$principal  = number_format($line[$x]['principal'],      2, ".", "");
				$fees       = number_format($line[$x]['fees'],           2, ".", "");
				$service    = number_format($line[$x]['service_charge'], 2, ".", "");
				$amount_due = number_format($line[$x]['amount_due'],     2, ".", "");
				$payout     = $line[$x]['payout'] ? 1 : 0;
				$evnt_sched = $line[$x]['event_schedule_id'];
				$app_status = $line[$x]['application_status_id'];
				$is_ach 	= $line[$x]['is_ach'] ? 1 : 0;

				$save_query .= "
					(now(),         {$date},       {$company_id}, {$co_name},   {$app_id},    {$name_last},
					 {$name_first}, {$status},     {$frequency},  {$dd},        {$principal}, {$fees},
					 {$service},    {$amount_due}, {$next_due},   {$loan_type}, {$first_pay}, {$payout},
					 {$special}, {$evnt_sched}, {$app_status}, '{$is_ach}')
					";
				
				
				/**
				 * Fix for Mantis issue 1570.
				 * 
				 * Problem was redundant records for a specific app_id.
				 * Note that app_id is not an index for this table.
				 * This fix prevents future redundant records, without altering
				 * the table description. Another way, which involves a
				 * table alteration, would be to make
				 * application_id a unique index and use a single query
				 * using REPLACE.
				 *
				 * Removing existing redundant records is a one-time job
				 * for a particular database, therefore this will be done
				 * using a separate script.
				 *  
				*/
				
				/**
				 * This was not a bug.  There may be multiple entries for 
				 * an application ID in this table, but as long as they 
				 * do not occur on the same day, it's fine.  This table is
				 * used for a snapshot of a report, so there may be entries
				 * for a single application id for many different days.
				*/
				
				/*			
				$delete_query = "
					-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
					DELETE FROM resolve_payments_due_report
						WHERE application_id = {$app_id}
						";
				
				*/
				// new record for this application_id
				// MUST use the Master when writing this.
				ECash::getMasterDb()->exec($save_query);
			}
		}
	}

	public function Fetch_Payments_Due_Data($specific_date, $loan_type, $company_id, $mode = null, $force_save = false)
	{
		$timestamp = strtotime($specific_date);
		
		
		// CLI or web?
		if( ! empty($mode) && strtolower($mode) == 'cli' )
			$timer = self::CLI_TIMER;
		else
			$timer = "";

		// Recent, should be in saved table
		$data = array();
		if( $timestamp < mktime(0, 0, 0) )
		{
			$timer .= self::ARCHIVE_TIMER;
			$data = $this->Fetch_Past_Data($specific_date, $loan_type, $company_id, $timer);
		}
		
		if (count($data)) {
			return $data;
		}
		
		$timer .= self::TIMER_NAME;
		$data = $this->Fetch_Current_Data($specific_date, $loan_type, $company_id, $timer);
		
		if ((strtotime($specific_date) < mktime(0, 0, 0)) || $force_save) {
			$this->Save_Report_Data($data, $specific_date);
		}
		return $data;
	}
	
	private function Delete_Old_Data()
	{
		// DELETE old data
		$delete_query = "
			DELETE FROM resolve_payments_due_report
			 WHERE report_date <= DATE_SUB(CURRENT_DATE(), INTERVAL " . self::MAX_SAVE_DAYS . " DAY)
			";
	}

	private function Fetch_Past_Data($specific_date, $loan_type, $company_id, $timer)
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

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		$past_query = "
			SELECT
				pd.event_schedule_id	AS event_schedule_id,
				pd.company_name_short   AS company_name,
				pd.application_id       AS application_id,
				pd.name_last            AS name_last,
				pd.name_first           AS name_first,
				pd.status               AS status,
				pd.pay_period           AS frequency,
				pd.direct_deposit       AS dd,
				pd.company_id			 AS company_id,
				pd.application_status_id AS application_status_id,
				pd.loan_type            AS loan_type,
				pd.is_ach            	AS is_ach,
				pd.next_due             AS next_due,
				pd.first_time_due       AS first_payment,
				pd.special_arrangements AS special,
				pd.pay_out              AS payout,
				pd.principal            AS principal,
				pd.fees                 AS fees,
				pd.service_charge       AS service_charge,
				pd.total_due            AS amount_due
			FROM
				resolve_payments_due_report pd
			WHERE
				pd.report_date          =  '{$specific_date}'
			 AND	pd.loan_type            IN ({$loan_type_list})
			 AND	pd.company_id           IN ({$company_list})
			 AND is_ach = '0'
			ORDER BY company_name
			";
		
		$past_result = $this->db->query($past_query);
		
		$data = array();

		while ($row = $past_result->fetch(PDO::FETCH_ASSOC))
		{
			$co = $row['company_name'];
	//		unset($row['company_name']);
			
			$this->Get_Module_Mode($row,false);
			
			$row['name_first'] = ucfirst($row['name_first']);
			$row['name_last'] = ucfirst($row['name_last']);
			$row['loan_type'] = ucwords(str_replace("_", " ", $row['loan_type']));
			$row['frequency'] = str_replace("_", " ", $row['frequency']);

			$data[$co][] = $row;
		}

		$this->timer->stopTimer( $timer );

		return $data;
	}

	public function Fetch_Current_Data($specific_date, $loan_type, $company_id, $timer)
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

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

		// get ID lists for query
		$cashline_ids            = $this->Get_Cashline_Ids();
		$arrangement_status_ids  = $this->Get_Arrangement_Status_Ids();
		$principal_type_ids      = $this->Get_Transaction_Type_Ids($company_list, 'principal');
		$fee_type_ids            = $this->Get_Transaction_Type_Ids($company_list, 'fee');
		$service_charge_type_ids = $this->Get_Transaction_Type_Ids($company_list, 'service_charge');
		$all_type_ids = "{$principal_type_ids},{$fee_type_ids},{$service_charge_type_ids}";
		$alt_date = date('Y-m-d', strtotime($specific_date));
		$not_in_payouts = implode(',', array($this->status_ids['fund_failed'], $this->status_ids['withdrawn']) );

		// For each Application Id
		$query = "
			SELECT
				es.event_schedule_id event_schedule_id,
				UPPER(c.name_short) company_name,
				a.application_id application_id,
				a.name_last name_last,
				a.name_first name_first,
				ass.name status,
				a.income_frequency frequency,
				a.income_direct_deposit dd,
				a.company_id company_id,
				a.application_status_id application_status_id,
				lt.name_short loan_type,
				0 is_ach,
				DATE_FORMAT(
					(
						SELECT MIN(date_effective)
						FROM  event_schedule ees
						WHERE 
							ees.application_id = a.application_id AND
							date_effective > '{$specific_date}' AND
							(ees.amount_non_principal < 0 OR ees.amount_principal < 0)
					),
					'%c/%e/%y'
				)  AS next_due,
				IF(
					(
						SELECT MIN(date_effective)
						FROM event_schedule evs
						WHERE
							evs.application_id = a.application_id AND
							(evs.amount_principal + evs.amount_non_principal) <= 0
					) = '{$alt_date}' AND
					a.is_react = 'no',
					1,
					0
				) first_payment,
				IF(a.application_status_id IN ({$arrangement_status_ids}), 1, 0) special,
				IF(
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
							es.date_effective <= '{$specific_date}' AND
							es.application_id = a.application_id AND
							(
								es.event_status = 'scheduled' OR
								tr.transaction_status IN ('complete','pending')
							)
					) <= 0 AND a.application_status_id NOT IN ({$not_in_payouts}), 
					1, 
					0
				) payout,
				SUM(IF(
					eat.name_short = 'principal',
					ea.amount,
					0
				)) principal,
				SUM(IF(
					eat.name_short = 'fee',
					ea.amount,
					0
				)) fees,
				SUM(IF(
					eat.name_short = 'service_charge',
					ea.amount,
					0
				)) service_charge,
				SUM(IF(
					eat.name_short <> 'irrecoverable',
					ea.amount,
					0
				)) AS amount_due
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
				IFNULL((
					SELECT DISTINCT clearing_type
					FROM 
						transaction_type
						JOIN event_transaction USING (transaction_type_id)
					WHERE 
						event_type_id = es.event_type_id AND
						clearing_type = 'ach'
				), 'non-ach') <> 'ach' AND
				es.date_effective = '{$specific_date}' AND
				es.company_id IN ({$company_list}) AND
				es.event_status <> 'suspended' AND
				(tr.transaction_status <> 'failed' OR tr.transaction_status IS NULL) AND
				lt.name_short IN ({$loan_type_list}) AND
				es.amount_principal <= 0 AND
				es.amount_non_principal <= 0 AND
				a.application_status_id NOT IN ({$cashline_ids})
			GROUP BY
				company_name,
				application_id,
				name_last,
				name_first,
				status,
				frequency,
				dd,
				company_id,
				application_status_id,
				loan_type,
				is_ach,
				next_due,
				first_payment,
				special,
				payout
		";
		// This report should ALWAYS hit the master.
		$db = ECash::getMasterDb();
		//get_log()->Write($query);
		$result = $db->query($query);

		$data = array();
		while ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$co = $row['company_name'];
			$id = $row['application_id'];

			$this->Get_Module_Mode($row,false);

			$data[$co][] = array(
				'application_id' => $row['application_id'],
				'event_schedule_id' => $row['event_schedule_id'],
				'application_status_id' => $row['application_status_id'],
				'name_last'      => ucfirst($row['name_last']),
				'name_first'     => ucfirst($row['name_first']),
				'status'         => $row['status'],
				'frequency'      => str_replace("_", " ", $row['frequency']),
				'dd'             => $row['dd'],
				'next_due'       => $row['next_due'],
				'first_payment'  => $row['first_payment'],
				'special'        => $row['special'],
				'loan_type'      => ucwords(str_replace("_", " ", $row['loan_type'])),
				'principal'      => -$row['principal'],
				'fees'           => -$row['fees'],
				'service_charge' => -$row['service_charge'],
				'amount_due'     => -$row['amount_due'],
				'payout'         => $row['payout'],
				'is_ach'         => $row['is_ach'],
				'module'         => $row['module'],
				'mode'           => $row['mode']
			);
		}

		$this->timer->stopTimer( $timer );

		return $data;
	}
}

?>
