<?php

require_once( SERVER_CODE_DIR . "base_report_query.class.php" );
require_once( COMMON_LIB_DIR . "pay_date_calc.3.php" );

class Reattempts_Report_Query extends Base_Report_Query
{
	
	private static $TIMER_NAME = "Reattempts Report Query";
	private $system_id;
	
	protected $new;
	protected $reattempt;
	protected $holidays;
	protected $pdc;
	
	protected $company_map;
	
	protected $ach_event_types;

	public function __construct(Server $server)
	{
		parent::__construct($server);
		
		$this->holidays = Fetch_Holiday_List();
		$this->pdc = new Pay_Date_Calc_3($this->holidays);
		$this->ach_event_types = $this->fetchAchEventTypes();
	}
	
	private function fetchAchEventTypes()
	{
		$query = "
			SELECT DISTINCT et.event_type_id
			FROM
				event_transaction et
				JOIN transaction_type tt USING (transaction_type_id)
			WHERE tt.clearing_type = 'ach'
		";
		
		$result = $this->db->query($query);
		
		$achEventTypes = array();
		while ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$achEventTypes[] = $row['event_type_id'];
		}
		
		return $achEventTypes;
	}
	
	public function nextBusinessDay($date, $move = 1)
	{
		if ($move > 0) 
		{
			$date = $this->pdc->Get_Business_Days_Forward(date('Y-m-d', $date), $move);
		} 
		else 
		{
			$date = $this->pdc->Get_Business_Days_Backward(date('Y-m-d', $date), abs($move));
		}
		
		return strtotime($date);
	}
		
	public function Fetch_Reattempts_Data($date_start, $date_end, $company_id, $loan_type = 'all')
	{
		set_time_limit(0);
		// reset these
		$this->new = array();
		$this->reattempt = array();
		
		// get company info
		$this->company_map = $this->Get_Company_Ids(TRUE);
		
		$this->timer->startTimer( self::$TIMER_NAME );

		if (is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}
		
		$max_report_retrieval_rows = $this->max_display_rows + 1;

		if( $company_id > 0 )
			$company_list = "'$company_id'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";
					
		if ($loan_type == 'all')
			$loan_type_sql = "";
		else
			$loan_type_sql = "AND lt.name_short = '{$loan_type}'\n";

		// Start and end dates must be passed as strings with format YYYYMMDD
		
		/**
		 * Removing the block and using the dates given to allow the specification of the 
		 * date effective in the reattempts report. This will more closely mimic the 
		 * behaviour of the payments due report.
		// for a start date on a weekend, we actually have to move two days forward,
		// due to the date shifting between the bank statement and eCash's ACH dates
		$date_start = strtotime($date_start);
		$weekday = date('w', $date_start);
		
		$forward = ($weekday == 0 || $weekday == 6 || in_array (date ("Y-m-d", $date_start), $this->holidays)) ? 2 : 1;
		
		$timestamp_start = date("Ymd000000", $this->nextBusinessDay($date_start, $forward));
		$timestamp_end = date("Ymd235959", $this->nextBusinessDay(strtotime($date_end )));
		 */
		$timestamp_start = date("Ymd000000", strtotime($date_start));
		$timestamp_end = date("Ymd000000", strtotime($date_end));
		

		$c = 0;
		// grab full-pull events for today
		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			(
		    SELECT
				schedule.*,
				ach.ach_date,
				type.name_short AS event_type,
				ach.company_id,
				(
					SELECT
						application_status_id
					FROM
						status_history
					WHERE
						status_history.application_id = schedule.application_id AND
						status_history.date_created <= schedule.date_effective
					ORDER BY
						date_created DESC
					LIMIT 1
				) AS application_status_id,
				SUM(IF(eat.name_short = 'principal', ea.amount, 0)) p,
				SUM(IF(eat.name_short = 'fee', ea.amount, 0)) f,
				SUM(IF(eat.name_short = 'service_charge', ea.amount, 0)) s
			FROM
				event_schedule AS schedule
			JOIN
				application app ON (app.application_id = schedule.application_id)
			JOIN
				loan_type lt ON (lt.loan_type_id = app.loan_type_id)
			JOIN
				event_type AS type ON (type.event_type_id = schedule.event_type_id) 
			JOIN
				transaction_register AS register ON (register.event_schedule_id = schedule.event_schedule_id) 
			JOIN
				event_amount AS ea ON (ea.event_schedule_id = register.event_schedule_id AND ea.transaction_register_id = register.transaction_register_id)
			JOIN
				event_amount_type AS eat ON (ea.event_amount_type_id = eat.event_amount_type_id) JOIN
				ach ON (register.ach_id = ach.ach_id)
			WHERE
				schedule.event_status = 'registered' 
			AND
				schedule.date_effective BETWEEN {$timestamp_start} AND {$timestamp_end} 
			AND
				schedule.company_id IN ($company_list)
			{$loan_type_sql}
			GROUP BY
				schedule.event_schedule_id
			)
			UNION
			(
			SELECT
				schedule.*,
				schedule.date_effective ach_date,
				type.name_short AS event_type,
				schedule.company_id,
				(
					SELECT
						application_status_id
					FROM
						status_history
					WHERE
						status_history.application_id = schedule.application_id AND
						status_history.date_created <= schedule.date_effective
					ORDER BY
						date_created DESC
					LIMIT 1
				) AS application_status_id,
				SUM(IF(eat.name_short = 'principal', ea.amount, 0)) p,
				SUM(IF(eat.name_short = 'fee', ea.amount, 0)) f,
				SUM(IF(eat.name_short = 'service_charge', ea.amount, 0)) s
			FROM
				event_schedule AS schedule
			JOIN
				application app ON (app.application_id = schedule.application_id)
			JOIN
				loan_type lt ON (lt.loan_type_id = app.loan_type_id)
			JOIN
				event_type AS type ON (type.event_type_id = schedule.event_type_id) 
			JOIN
				event_amount AS ea ON (ea.event_schedule_id = schedule.event_schedule_id) 
			JOIN
				event_amount_type AS eat ON (ea.event_amount_type_id = eat.event_amount_type_id)
			WHERE
				schedule.event_status = 'scheduled' 
			AND
				type.event_type_id IN(".implode(',', $this->ach_event_types).") 
			AND
				schedule.date_effective BETWEEN {$timestamp_start} AND {$timestamp_end} 
			AND
				schedule.company_id IN ($company_list)
			{$loan_type_sql}
			GROUP BY
				schedule.event_schedule_id
			)
		";
		$result = $this->db->query($query);
		
		if( $result->rowCount() == $max_report_retrieval_rows )
			return false;
		
		$items = array();
		$new = array();
		
		while ($pull = $result->fetch(PDO::FETCH_OBJ))
		{
			
			// skip loan disbursements, refunds, etc...
			if ($pull->amount_principal > 0 || $pull->amount_non_principal > 0) continue;
			
			// set some stuff up
			$this->preparePull($pull);
			
			// we trust generated payments to tell the truth
			if (($pull->context === 'generated' || $pull->context === 'reattempt')
				&& in_array($pull->event_type, array('payment_service_chg', 'repayment_principal', 'payment_fee_ach_fail', 'paydown', 'payout')))
			{
				
				if ($pull->origin_id === NULL)
				{
					$pull->new = $pull->amount->copy();
				}
				else
				{
					$pull->reattempt = $pull->amount->copy();
				}
				
			}
			else
			{
				// pull the entire schedule and calculate all kinds of stuff...
				$this->processPull($pull);
			}
			
			// add to our results
			$this->addRow($pull);
			
			$c++;
			
		}
		
		return $this->formatResults();
		
	}
	
	protected function preparePull($pull)
	{
		
		$pull->amount = new Amount();
		$pull->amount->p = (float)$pull->p;
		$pull->amount->s = (float)$pull->s;
		$pull->amount->f = (float)$pull->f;
		$pull->balance = new Amount();
		$pull->new = new Amount();
		$pull->paid = new Amount();
		$pull->reattempt = new Amount();
		$pull->ach_date = strtotime($pull->ach_date);
		$pull->date = date('Y-m-d', $pull->ach_date);
		
		return;
		
	}
	
	protected function processPull($pull)
	{
		
		// pull the entire event_schedule for this application -- we order by
		// date_effective and resolve any "ties" by putting retries first
		
		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				event_schedule.*,
				transaction_register.*,
				event_type.name_short AS event_type,
				(
					SELECT
						status_after
					FROM
						transaction_history
					WHERE
						transaction_history.transaction_register_id = transaction_register.transaction_register_id AND
						transaction_history.date_created <= '{$pull->date_event}'
					ORDER BY
						date_created DESC
					LIMIT 1
				) AS status,
				SUM(IF(eat.name_short = 'principal', ea.amount, 0)) p,
				SUM(IF(eat.name_short = 'fee', ea.amount, 0)) f,
				SUM(IF(eat.name_short = 'service_charge', ea.amount, 0)) s
 			FROM
				event_schedule 
			JOIN
				event_type ON (event_type.event_type_id = event_schedule.event_type_id) 
			JOIN
				transaction_register ON (transaction_register.event_schedule_id = event_schedule.event_schedule_id) 
			JOIN
				event_amount ea ON ea.event_schedule_id = transaction_register.event_schedule_id AND
					ea.transaction_register_id = transaction_register.transaction_register_id 
			JOIN
				event_amount_type eat ON ea.event_amount_type_id = eat.event_amount_type_id
			WHERE
				event_schedule.application_id = {$pull->application_id} AND
				event_schedule.date_event <= '{$pull->date_event}'
			GROUP BY
				event_schedule.event_schedule_id
			ORDER BY
				event_schedule.date_effective,
				(origin_id IS NULL)
		";
		$result = $this->db->query($query);
		
		// get the first row
		$row = $result->fetch(PDO::FETCH_OBJ);
		
		while ($row && ($row->event_schedule_id != $pull->event_schedule_id))
		{
			
			// event amounts
			$event = new stdClass();
			$event->id = $row->event_schedule_id;
			$event->type = ($row->amount < 0) ? 'debit' : 'credit';
			$event->context = trim(strtolower($row->context));
			$event->event_type = $row->event_type;
			$event->amount_principal = (float)$row->amount_principal;
			$event->amount_non_principal = (float)$row->amount_non_principal;
			$event->status = $row->status;
			$event->origin_id = $row->origin_id;
			$event->amount = new Amount();
			
			$event->amount->p = $row->p;
			$event->amount->s = $row->s;
			$event->amount->f = $row->f;
			
			// process this event in the context of the pull
			$this->processEvent($pull, $event);
			
			$row = $result->fetch(PDO::FETCH_OBJ);
			
		}
		
		// perform some post-processing: allocate total fees, etc.
		$this->finalizePull($pull);
		
		return;
		
	}
	
	protected function processEvent($pull, $event)
	{
		
		if ($event->status === 'complete')
		{
			
			// mainly used for sanity checks
			$pull->balance->add($event->amount);
			
			if ($event->type === 'debit')
			{
				
				$pull->paid->add($event->amount);
				
				// reattempt amount can never go positive
				if ($pull->reattempt->p < 0) $pull->reattempt->p = ($pull->reattempt->p < $event->amount->p) ? ($pull->reattempt->p - $event->amount->p) : 0;
				if ($pull->reattempt->f < 0) $pull->reattempt->f = ($pull->reattempt->f < $event->amount->f) ? ($pull->reattempt->f - $event->amount->f) : 0;
				if ($pull->reattempt->s < 0) $pull->reattempt->s = ($pull->reattempt->s < $event->amount->s) ? ($pull->reattempt->s - $event->amount->s) : 0;
				
			}
			
		}
		elseif (($event->type === 'debit') && ($event->status === 'failed') && ($event->origin_id === NULL))
		{
			
			if ($event->context === 'generated')
			{
				$pull->reattempt->add($event->amount);
			}
			else
			{
				if ($pull->reattempt->p > $event->amount->p) $pull->reattempt->p += ($event->amount->p - $pull->reattempt->p);
				if ($pull->reattempt->s > $event->amount->s) $pull->reattempt->s += ($event->amount->s - $pull->reattempt->s);
				if ($pull->reattempt->f > $event->amount->f) $pull->reattempt->f += ($event->amount->f - $pull->reattempt->f);
			}
			
		}
		
		return;
		
	}
	
	protected function finalizePull($pull)
	{
		
		// we can never go below the full-pull amount
		$pull->reattempt->max($pull->amount);
		$pull->new = $pull->amount->copy()->subtract($pull->reattempt);
		
		return;
		
	}
	
	protected function addRow($pull)
	{
		
		// add to our totals
		if (!isset($this->new[$pull->company_id])) $this->new[$pull->company_id] = array();
		if (!isset($this->new[$pull->company_id][$pull->date])) $this->new[$pull->company_id][$pull->date] = new Amount();
		$this->new[$pull->company_id][$pull->date]->add($pull->new);
		
		if (!isset($this->reattempt[$pull->company_id])) $this->reattempt[$pull->company_id] = array();
		if (!isset($this->reattempt[$pull->company_id][$pull->date])) $this->reattempt[$pull->company_id][$pull->date] = new Amount();
		$this->reattempt[$pull->company_id][$pull->date]->add($pull->reattempt);
		
		return;
		
	}
	
	protected function formatResults()
	{
		
		$c = array_keys($this->new);
		$row_item = array();
		
		foreach ($c as $company_id)
		{
			
			// get our property short
			$company = $this->company_map[$company_id];
			
			// get the dates that are on the report
			$dates = array_keys($this->new[$company_id]);
			sort($dates);
			
			// hold the rows for this company
			$rows = array();
			
			foreach ($dates as $date)
			{
				
				$rows[] = array(
					'reattempt_date' => $date,
					'new_principal' => $this->new[$company_id][$date]->p,
					'new_svr_charge' => $this->new[$company_id][$date]->s,
					'new_fees' => $this->new[$company_id][$date]->f,
					're_principal' => $this->reattempt[$company_id][$date]->p,
					're_svr_charge' => $this->reattempt[$company_id][$date]->s,
					're_fees' => $this->reattempt[$company_id][$date]->f
				);
				
			}
			
			// save into our results
			$row_item[$company] = $rows;
			
		}
		
		return $row_item;
		
	}
	
}

class Amount
{
	
	public $p = 0.0; // principal
	public $f = 0.0; // fees
	public $s = 0.0; // service-charges
	
	public function __toString()
	{
		return "Principal: {$this->p}, Service-Charges: {$this->s}, Fees: {$this->f}";
	}
	
	public function &add(Amount $a)
	{
		$this->p += $a->p;
		$this->f += $a->f;
		$this->s += $a->s;
		return $this;
	}
	
	public function &subtract(Amount $a)
	{
		$this->p -= $a->p;
		$this->f -= $a->f;
		$this->s -= $a->s;
		return $this;
	}
	
	public function &invert()
	{
		$this->p = -$this->p;
		$this->f = -$this->f;
		$this->s = -$this->s;
		return $this;
	}
	
	public function equals($a)
	{
		return ($this->p == $a->p && $this->f == $a->f && $this->s = $a->s);
	}
	
	public function &min(Amount $a)
	{
		$this->p = min($this->p, $a->p);
		$this->f = min($this->f, $a->f);
		$this->s = min($this->s, $a->s);
		return $this;
	}
	
	public function &max(Amount $a)
	{
		$this->p = max($this->p, $a->p);
		$this->f = max($this->f, $a->f);
		$this->s = max($this->s, $a->s);
		return $this;
	}
	
	public function &copy()
	{
		$new = clone $this;
		return $new;
	}
	
}
?>
