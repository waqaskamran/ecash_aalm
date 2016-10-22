<?php

require_once( SERVER_CODE_DIR . "base_report_query.class.php" );

class Fraud_Deny_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Fraud Deny Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 */
	public function Fetch_Fraud_Denied_Data($date_start, $date_end, $company_id, $loan_type)
	{

		$company_list = $this->Format_Company_IDs($company_id);
		$this->timer->startTimer(self::$TIMER_NAME);

		$date_start .= '000000';
		$date_end .= '235959';

		$rows = array();
		$order = 1;

		$db = ECash::getMasterDb();

		if ($loan_type == 'all')
			$loan_type_sql = "";
		else
			$loan_type_sql = "AND lt.name_short = '{$loan_type}'\n";

		$query = "
			select
				UPPER(c.name_short) as company,
				fr.fraud_rule_id,
				fr.name,
				fr.comments,
				count(*) as count
			from 
				fraud_rule fr
			inner join fraud_application fa on (fa.fraud_rule_id = fr.fraud_rule_id)
			inner join status_history sh on (sh.application_id = fa.application_id)
			join application app ON (app.application_id = fa.application_id)
			join loan_type lt ON (lt.loan_type_id = app.loan_type_id)
			inner join company c on (sh.company_id = c.company_id)
			where
				fr.rule_type = 'FRAUD'
			and sh.date_created between '{$date_start}' and '{$date_end}'
			{$loan_type_sql}

			AND c.company_id IN {$company_list}
			and sh.application_status_id = {$this->denied}
			group by company, fraud_rule_id, name, comments

		";
		
		$result = $db->query($query);
		while ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			if($row['company'] == 'OLP')
			{
				$row['count'] = $olp_results[$row['fraud_rule_id']];
			}
			
			$company = $row['company'];
			
			//fake a company name here for display
			$rows[$company][] = $row;
		}
				
		$this->timer->stopTimer(self::$TIMER_NAME);

		return $rows;
	}

	public function Fetch_Fraud_Proposition_Data($company_id = null, $loan_type = 'all')
	{
		$this->timer->startTimer(self::$TIMER_NAME);
		$company_list = $this->Format_Company_IDs($company_id);
		$application_ids = array();
		$rows = array();
		$order = 1;

		$db = ECash::getMasterDb();

		if ($loan_type == 'all')
			$loan_type_sql = "";
		else
			$loan_type_sql = "AND lt.name_short = '{$loan_type}'\n";

		$query = "
			select				
				c.name_short company_name,
				c.company_id,
				a.application_status_id,
				lt.name_short as loan_type,
				fr.name,
				fp.fraud_proposition_id,
				fa.application_id,
				(NOW() - nqe.date_queued) time_in_queue,
				if(fr.active = 1, 'Fraud Confirmed', 'Not Fraud') outcome
			from 
				fraud_rule fr	
			inner join fraud_proposition fp on (fp.fraud_rule_id = fr.fraud_rule_id)
			left join fraud_application fa on (fa.fraud_rule_id = fr.fraud_rule_id)
			left join application a on (a.application_id = fa.application_id)
			left join company c on (c.company_id = a.company_id)
			inner join loan_type lt on (lt.loan_type_id = a.loan_type_id)
			LEFT JOIN n_queue_entry nqe on (nqe.related_id = fa.application_id)
			LEFT JOIN n_queue nq on (nq.queue_id = nqe.queue_id)
			where
				fr.rule_type = 'FRAUD'
			{$loan_type_sql}
			AND nq.name = 'Fraud'
			AND nqe.date_available <= NOW()
			AND c.company_id IN {$company_list}
			and fr.confirmed = 1
		";
		//echo "<hr><pre>{$query}</pre><hr>";
		$result = $db->query($query);
		while ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			//echo "<!-- ", print_r($row, TRUE), " -->";
			//fake a company name here for display
			if($row['company_name'])
			{
				$this->Get_Module_Mode($row, false);
				$company_name = $row['company_name'];
				$rows[$company_name][] = $row;
			}
			else
			{
				$rows['Unmatched'][] = $row;
			}
		}

		//echo "<!-- ", print_r($rows, TRUE), " -->";
		$this->timer->stopTimer(self::$TIMER_NAME);

		return $rows;
	}	
	
}

?>
