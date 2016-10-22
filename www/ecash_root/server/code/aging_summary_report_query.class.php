<?php

require_once( SERVER_CODE_DIR . "base_report_query.class.php" );
require_once( LIB_DIR . "business_rules.class.php" );

class Aging_Summary_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Aging Summary Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
		bcscale(2);
	}

	/**
	 * Fetches data for the Aging Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   string $loan_type  standard || card
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Aging_Summary_Data($company_id, $loan_type, $run_date)
	{
		$this->timer->startTimer(self::$TIMER_NAME);
		
		$loan_type_list = $this->Get_Loan_Type_List($loan_type);
		$loan_type_list = is_array($loan_type_list) ? $loan_type_list :  array();
		$loan_type_sql = '';
		if(count($loan_type_list))
		{
			$loan_type_placeholders = substr(str_repeat('?,', count($loan_type_list)), 0, -1);
			$loan_type_sql = "AND lt.name_short IN ({$loan_type_placeholders})";
		}
		
		if(isset($_SESSION) && is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		if( $company_id > 0 )
			$company_list = array($company_id);
		else
			$company_list = $auth_company_ids;

		$company_placeholders = substr(str_repeat('?,', count($company_list)), 0, -1);		

		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		$ignored_statuses = array(
			$asf->toId('denied::applicant::*root'),
			$asf->toId('withdrawn::applicant::*root'),
			$asf->toId('paid::customer::*root'),
			$asf->toId('funding_failed::servicing::customer::*root'),
			);
		
		$status_placeholders = substr(str_repeat('?,', count($ignored_statuses)), 0, -1);
		
		
		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
SELECT 
tr.application_id,
CONCAT(app.name_last , ', ', app.name_first) as name_full,
app.ssn as ssn,
app.date_fund_actual as fund_date,
DATEDIFF('{$run_date}', min(sh.date_created)) num_days_delinquent,
0 as balance,
0 as interest,
0 as fee,
0 as total,
app.application_status_id,
stat.name as status,
app.loan_type_id,
co.name as company_name,
lt.name as loan_type_name
FROM status_history sh
JOIN application as app on (app.application_id = sh.application_id)
JOIN transaction_register as tr on (tr.application_id = app.application_id)
JOIN application_status as stat on (stat.application_status_id = app.application_status_id)
JOIN loan_type as lt USING (loan_type_id)
JOIN company as co ON (app.company_id = co.company_id)
WHERE app.application_status_id NOT IN ({$status_placeholders})
AND tr.date_created <= ?
AND app.company_id IN ({$company_placeholders})
AND app.date_fund_actual IS NOT NULL
{$loan_type_sql}
GROUP BY tr.application_id
";			
		//echo '<pre>'.$query.'</pre>';
		//$this->log->Write($query);
		$values = array_merge($ignored_statuses, array($run_date), $company_list, $loan_type_list);
		//print_r($values);
		$fetch_result = $this->db->queryPrepared($query, $values);
		
		$data = array();
		require_once(SQL_LIB_DIR . "scheduling.func.php");
		require_once(ECASH_COMMON_DIR . "ecash_api/interest_calculator.class.php");

		$biz_rules = new ECash_Business_Rules(ECash::getMasterDb());
	    $holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);
		
		//Preload necessary rulesets
		$loan_type_id_list = $this->Get_Loan_Type_List($loan_type, TRUE);
		$lt_ids = explode(',', str_replace("'", "", $loan_type_id_list));

		foreach($lt_ids as $loan_type_id)
		{
			$rule_set_id = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
			$lt_rules[intval($loan_type_id)] = $biz_rules->Get_Rule_Set_Tree($rule_set_id);
		}
				
		$groups = array(
				'Current',
				"1-15",
				'16-30',
				'31-45',
				'46-60',
				'61-90',
				'91+');
		$totals = array();

		while( $row = $fetch_result->fetch(PDO::FETCH_ASSOC) )
		{
			$rules = $lt_rules[$row['loan_type_id']];
			$schedule = Fetch_Schedule($row['application_id'], FALSE);
			$balance_info = Fetch_Balance_Information($row['application_id'], $run_date);
			$row['balance'] = ($balance_info->principal_pending > 0) ? $balance_info->principal_pending : $balance_info->principal_balance;
			$row['interest'] = $balance_info->service_charge_balance;
			$interest = bcadd($row['interest'], Interest_Calculator::scheduleCalculateInterest($rules, $schedule, $run_date));
			// I definitely don't think this is needed here
			// Don't accrue past default date
			if ($rules['loan_type_model'] == 'CSO')
			{
				$renewal_class =  ECash::getFactory()->getRenewalClassByApplicationID($row['application_id']);
				if ($renewal_class->hasDefaulted($row['application_id']))
					$interest = 0.00;
			}

			$row['interest'] = $interest;
			$row['fee'] = $balance_info->fee_balance;
			$row['total'] = bcadd($row['balance'] , bcadd($row['interest'] , $row['fee']));
			
			if($row['total'] > 0)
			{
			
				$grouping = ceil($row['num_days_delinquent']/15);
				
				if ($grouping < 0)
				{ 
					$group_num = 0;
				}
				else if ($grouping > 4 && $grouping <= 6)
				{ 
					$group_num = 5;
				}
				else if ($grouping > 6)
				{ 
					$group_num = 6;
				}
				else
				{
					$group_num = $grouping;
				}
				//$grouping = $groups[$group_num];
	
				//$this->Get_Module_Mode($row, $row['company_id']);
				$company_loantype = $row['company_name'] . " - " . $row['loan_type_name'];
				
				// Initialize the $data[$company_loantype] array so it displays properly
				if(count($data[$company_loantype]) == 0) 
				{ 
					$data[$company_loantype] = array();
					$data[$company_loantype] = array_pad($data[$company_loantype], count($groups), array());
					foreach($data[$company_loantype] as $num => $group_info)
					{
						$data[$company_loantype][$num]['num_days'] = $groups[$num];
						$data[$company_loantype][$num]['num_loans'] = 0;
						$totals[$company_loantype]['loans'] = 0;
						$totals[$company_loantype]['principal'] = 0;
					}
				}
					
				$data[$company_loantype][$group_num]['num_loans']++;
				$totals[$company_loantype]['loans']++;
				$data[$company_loantype][$group_num]['balance'] = bcadd($data[$company_loantype][$group_num]['balance'], $row['balance']);
				$totals[$company_loantype]['principal'] = bcadd($totals[$company_loantype]['principal'], $row['balance']);
				$data[$company_loantype][$group_num]['interest'] = bcadd($data[$company_loantype][$group_num]['interest'], $row['interest']);
				$data[$company_loantype][$group_num]['fee'] = bcadd($data[$company_loantype][$group_num]['fee'], $row['fee']);
				$data[$company_loantype][$group_num]['total'] = bcadd($data[$company_loantype][$group_num]['total'], $row['total']);
			}
		}
		
		//Calculate bucket percentages
		foreach($data as $company_loantype => $group)
		{
			foreach($group as $bucket_num => $bucket)
			{
				$data[$company_loantype][$bucket_num]['pct_loans'] = ($bucket['num_loans'] / $totals[$company_loantype]['loans']) * 100;
				$data[$company_loantype][$bucket_num]['pct_balance'] = ($bucket['balance'] / $totals[$company_loantype]['principal']) * 100;
			}
		}

		
		foreach($data as $company_loantype_group)
		{
			ksort($company_loantype_group);			
		}
		$this->timer->stopTimer(self::$TIMER_NAME);
		ksort($data);
		
		return $data;
	}
}

?>
