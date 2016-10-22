<?php

require_once( SERVER_CODE_DIR . "base_report_query.class.php" );
require_once( LIB_DIR . "business_rules.class.php" );

class Aging_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Aging Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
		bcscale(2);
		set_time_limit(0);
	}

	/**
	 * Fetches data for the Aging Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   string $loan_type  standard || card
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Aging_Data($date_start, $date_end, $company_id, $loan_type)
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
			$asf->toId('settled::customer::*root'),
			$asf->toId('write_off::customer::*root'),
			$asf->toId('recovered::external_collections::*root'),
			$asf->toId('sent::external_collections::*root'),
			$asf->toId('verified::bankruptcy::collections::customer::*root'),
			$asf->toId('verified::deceased::collections::customer::*root'),
			);		
		$status_placeholders = substr(str_repeat('?,', count($ignored_statuses)), 0, -1);

		/**
		 * [#45279] For past due accounts (not active), the due date
		 * should be the first failed payment that was not resolved by
		 * a reattempt or cash payment.
		 */
		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
SELECT 
tr.application_id,
CONCAT(app.name_last , ', ', app.name_first) as name_full,
app.ssn as ssn,
app.date_fund_actual as fund_date,
(
CASE WHEN app.application_status_id = ?
THEN (
	SELECT 
	MIN(es0.date_effective) 
	FROM event_schedule es0
	WHERE es0.application_id = app.application_id
	AND event_status = 'scheduled'
	AND (amount_principal < 0 OR amount_non_principal < 0)
)
ELSE (
	SELECT 
	MIN(tr1.date_effective)
	FROM event_schedule es1
	JOIN transaction_register tr1 ON (tr1.event_schedule_id = es1.event_schedule_id)
	JOIN transaction_type tt1 ON (tt1.transaction_type_id = tr1.transaction_type_id)
	LEFT JOIN event_schedule es2 ON (es2.origin_id = tr1.transaction_register_id)
	LEFT JOIN transaction_register tr2 ON (tr2.event_schedule_id = es2.event_schedule_id)
	WHERE tr1.application_id = app.application_id
	AND tt1.clearing_type != 'adjustment'
	AND tr1.transaction_status = 'failed'
	AND (tr2.transaction_status IS NULL OR tr2.transaction_status = 'failed')
	)
END
) as due_date,
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
JOIN company as co ON (app.company_id = co.company_id)
JOIN loan_type as lt USING (loan_type_id)
WHERE app.application_status_id NOT IN ({$status_placeholders})
AND app.company_id IN ({$company_placeholders})
AND app.date_fund_actual IS NOT NULL
AND tr.date_created > ?
AND tr.date_created <= ?
{$loan_type_sql}
GROUP BY tr.application_id
ORDER BY name_full ASC
";			
		
		//$this->log->Write($query);
		$values = array_merge(
			array($asf->toId('active::servicing::customer::*root')),
			$ignored_statuses, $company_list,
			array($date_start),
			array($date_end),
			$loan_type_list);
			
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
		
		while( $row = $fetch_result->fetch(PDO::FETCH_ASSOC) )
		{
			$rules = $lt_rules[$row['loan_type_id']];
			$schedule = Fetch_Schedule($row['application_id'], FALSE);
			$balance_info = Fetch_Balance_Information($row['application_id'], $date_end);
			$row['balance'] = ($balance_info->principal_pending > 0) ? $balance_info->principal_pending : $balance_info->principal_balance;
			$row['interest'] = $balance_info->service_charge_balance;
			$row['interest'] = bcadd($row['interest'], Interest_Calculator::scheduleCalculateInterest($rules, $schedule, $date_end));
			$row['fee'] = $balance_info->fee_balance;
			
			$row['total'] = bcadd($row['balance'] , bcadd($row['interest'] , $row['fee']));
			$co = $row['company_name'] . " - " . $row['loan_type_name'];

			//$this->Get_Module_Mode($row, $row['company_id']);
			if($row['total'] > 0)
			{
				$data[$co][] = $row;
			}

		}

		$this->timer->stopTimer(self::$TIMER_NAME);
		ksort($data);
		return $data;
	}
}

?>
