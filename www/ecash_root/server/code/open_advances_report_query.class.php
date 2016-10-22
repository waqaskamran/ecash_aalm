<?php

require_once( SERVER_CODE_DIR . "base_report_query.class.php" );

class Open_Advances_Report_Query extends Base_Report_Query
{
	const TIMER_NAME = "Open Advances Query";
	const ARCHIVE_TIMER = "Open Advances Query Archived Data";
	const CLI_TIMER     = "CLI - Open Advances Query";
	const MAX_SAVE_DAYS = 30;
	
	protected $server;

	public function __construct(Server $server)
	{
		parent::__construct($server);
		$this->server = $server;
		$this->db = ECash::getMasterDb();
		$this->Add_Status_Id('cashline',       array('cashline',  'customer',             '*root'));
		$this->Add_Status_Id('second_tier',    array('pending',   'external_collections', '*root'));
		$this->Add_Status_Id('st_sent',        array('sent',      'external_collections', '*root'));
		$this->Add_Status_Id('current',        array('current',   'arrangements',         'collections', 'customer', '*root'));
		$this->Add_Status_Id('failed',         array('arrangements_failed','arrangements','collections', 'customer', '*root'));
		$this->Add_Status_Id('arr_hold',       array('hold',      'arrangements',         'collections', 'customer', '*root'));
		$this->Add_Status_Id('unverified',     array('unverified','bankruptcy',           'collections', 'customer', '*root'));
		$this->Add_Status_Id('verified',       array('verified',  'bankruptcy',           'collections', 'customer', '*root'));
		$this->Add_Status_Id('in_contact',     array('dequeued',  'contact',              'collections', 'customer', '*root'));
		$this->Add_Status_Id('contact',        array('queued',    'contact',              'collections', 'customer', '*root'));
		$this->Add_Status_Id('ready',          array('ready',     'quickcheck',           'collections', 'customer', '*root'));
		$this->Add_Status_Id('sent',           array('sent',      'quickcheck',           'collections', 'customer', '*root'));
		$this->Add_Status_Id('return',         array('return',    'quickcheck',           'collections', 'customer', '*root'));
		
		$this->Add_Status_Id('colnew',         array('new', 		'collections', 	'customer', '*root'));
		//$this->Add_Status_Id('colcontact',     array('contact', 	'collections', 	'customer', '*root'));
		$this->Add_Status_Id('colfollowup',    array('follow_up',	'contact', 		'collections', 	'customer', '*root'));
	}

	private function Get_Open_Advances_Status_Ids()
	{
		return implode( ",", array( $this->cashline,
		                            $this->second_tier,
		                            $this->st_sent,
		                            $this->active,
		                            $this->funded,
		                            $this->hold,
		                            $this->past_due,
		                            $this->current,
		                            $this->failed,
		                            $this->arr_hold,
		                            $this->unverified,
		                            $this->verified,
		                            $this->in_contact,
		                            $this->contact,
		                            $this->ready,
		                            $this->sent,
		                            $this->sent,
		                            $this->return,
		                            //$this->colcontact,
		                            $this->colfollowup
		                          )
		              );
	}

	public function Fetch_Open_Advances_Data($specific_date, $loan_type, $company_id, $mode = null, $force_save = false)
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
		if( $timestamp < mktime(0, 0, 0) )
		{
			$timer .= self::ARCHIVE_TIMER;
			//return $this->Fetch_Current_Data($specific_date, $loan_type, $company_id, $timer);
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
 	
	public function Fetch_Current_Data($specific_date, $loan_type, $company_id, $timer)
	{
		
		$this->timer->startTimer( $timer );
		
		if (is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}
		
		$data = array();
		
		// nightly processing begins at 18:00, so only
		// use records before that time
		$timestamp_start = $specific_date . "000000";
		$timestamp_end   = $specific_date . '175959'; //"235959";
	
		$loan_type_list = $this->Get_Loan_Type_List($loan_type);
		
		// turned off, per Crystal
		//$open_advance_status_ids = $this->Get_Open_Advances_Status_Ids();
		
		if ($company_id > 0)
		{
			$company_list = "'{$company_id}'";
		}
		else
		{
			$company_list = "'" . implode("','", $auth_company_ids) . "'";
		}
		
		// get company info
		$company = $this->Get_Company_Ids(TRUE);
		$status = $this->Get_Status_Names();
		
		//mantis:7543 - added AND current.company_id IN ({$company_list}) to the query
		$query = "
			SELECT
				current.company_id,
				(account_balance.balance > 0) AS positive,
				current.application_status_id AS status,
				COUNT(account_balance.application_id) AS total_count,
				SUM(account_balance.balance) AS total_balance
			FROM
			  (
				SELECT
					ea.application_id,
					SUM(ea.amount) AS balance
				  FROM
					event_amount AS ea
					INNER JOIN event_amount_type AS eat USING (event_amount_type_id)
					JOIN transaction_register tr USING (transaction_register_id)
				  WHERE
					(eat.name_short = 'principal') AND
					(ea.company_id IN ({$company_list})) AND
					(tr.date_created <= '{$timestamp_end}') AND
					(tr.transaction_status <> 'failed' OR
					  NOT EXISTS (
						SELECT transaction_history_id
						  FROM transaction_history AS history
						  WHERE 
							history.transaction_register_id = tr.transaction_register_id
							AND history.date_created <= '{$timestamp_end}'
							AND history.status_after = 'failed'
						  LIMIT 1
					))
				  GROUP BY
					ea.application_id
				  HAVING
					(balance != 0)
				) AS account_balance
				INNER JOIN status_history AS current ON (
					current.application_id = account_balance.application_id
				)
			WHERE
				current.status_history_id = (
					SELECT
						MAX(status_history_id)
					FROM
						status_history
					WHERE
						(status_history.application_id = account_balance.application_id) AND
						(status_history.date_created <= '{$timestamp_end}')
				)
				AND current.company_id IN ({$company_list})
			GROUP BY
				company_id,
				positive,
				status
			ORDER BY
				company_id,
				status
		";

		// This report should ALWAYS hit the master.
		$db = ECash::getMasterDb();
		$result = $db->query($query);

	
		while ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			
			// Need data as array( Company => array( 'colname' => 'data' ) )
			//   Do all data formatting here
			$company_name = $company[$row['company_id']];
			//unset($row['company_id']);
			
			// translate status IDs => names
			$row['status'] = $status[$row['status']];
			
			// split into positive/negative buckets
			if ($row['positive'])
			{
				$row['positive_count'] = $row['total_count'];
				$row['positive_balance'] = $row['total_balance'];
				$row['negative_count'] = 0;
				$row['negative_balance'] = 0;
			}
			else
			{
				$row['negative_count'] = $row['total_count'];
				$row['negative_balance'] = $row['total_balance'];
				$row['positive_count'] = 0;
				$row['positive_balance'] = 0;
			}
			
			if (!isset($data[$company_name]))
			{
				$data[$company_name] = array();
			}
			
			// combine records
			foreach ($row as $name=>$value)
			{
				
				if (!isset($data[$company_name][$row['status']][$name]))
				{
					$data[$company_name][$row['status']][$name] = $value;
				}
				elseif (is_numeric($value))
				{
					$data[$company_name][$row['status']][$name] += $value;
				}
				
			}
			
		}
		
		foreach ($data as $k=>$v)
		{
			$data[$k] = array_values($v);
		}
		
		$this->timer->stopTimer( $timer );
		
		return $data;
		
	}

	public function Fetch_Past_Data($specific_date, $loan_type, $company_id, $timer)
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
				company_name_short   AS company_name,
				status               AS status,
				positive_count,
				positive_balance,
				negative_count,
				negative_balance,
				loan_type_id,
				total_count,
				total_balance
			FROM
				open_advances_report
			WHERE
				report_date          =  '{$specific_date}'
			AND	company_id           IN ({$company_list})
			ORDER BY company_name
		";

		$result = $this->db->query($past_query);

		$data = array();

		while ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$co = $row['company_name'];
			unset($row['company_name']);
			
			if (!isset($data[$co])) $data[$co] = array();
			$data[$co][$row['status']] = $row;
			
		}

		$this->timer->stopTimer( $timer );

		return $data;
	}
		
	
	 /* Saves the data for 1 day of the report and deletes old entries
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
			throw new Exception( "Open Advances Report [" . __METHOD__ . ":" . __LINE__ . "] invalid date parameter:  '{$date}'" );
		}

		// Quote the date for the query
		$date = "'{$date}'";

		// First make room
		// Disabled for now.
		//$this->Delete_Old_Data();
		
		$company_ids = $this->Get_Company_Ids();
		
		$save_query = "
			INSERT INTO open_advances_report
			(
				date_created,
				report_date,
				company_id,
				company_name_short,
				status,
				positive_count,
				positive_balance,
				negative_count,
				negative_balance,
				total_count,
				total_balance
			)
			VALUES
		";
		
		foreach ($data as $co=>$line)
		{
			
			// company information for the insert
			$company_id = $company_ids[strtolower($co)];
			$company_name = $this->db->quote(strtolower($co));
			
			foreach ($line as $key=>$row)
			{
				
				$status =  $this->db->quote(strtolower($row['status']));
				
				// build the query
				$query = $save_query;
				$query .= "
					(
						NOW(),
						{$date},
						{$company_id},
						{$company_name},
						{$status},
						{$row['positive_count']},
						{$row['positive_balance']},
						{$row['negative_count']},
						{$row['negative_balance']},
						{$row['total_count']},
						{$row['total_balance']}
					)
				";
				
				$save_result = $this->db->query($query);
				
			}
			
		}
		
	}

}
?>
