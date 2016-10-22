<?php
/**
 * When given a start and end date, will get all applications modified in that range then get
 * information regarding their payment arrangement modification history of all time.
 *
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 * @author Russell Lee <russell.lee@sellingsource.com>
 */

/**
 * Get the generic reporting class
 */
require_once('report_generic.class.php');

class Report extends Report_Generic
{
	private $search_query;

	public function Generate_Report()
	{
		// Generate_Report() expects the following from the request form:
		//
		// criteria start_date YYYYMMDD
		// criteria end_date   YYYYMMDD
		// company_id
		//
		try
		{
			$this->search_query = new Payment_Arrangements_Modified_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   		=> $this->request->start_date_month,
			  'start_date_DD'   		=> $this->request->start_date_day,
			  'start_date_YYYY' 		=> $this->request->start_date_year,
			  'end_date_MM'     		=> $this->request->end_date_month,
			  'end_date_DD'     		=> $this->request->end_date_day,
			  'end_date_YYYY'   		=> $this->request->end_date_year,
			  'company_id'      		=> $this->request->company_id,
			  'loan_type'       		=> $this->request->loan_type
			);

			$_SESSION['reports']['payment_arrangements_modified']['report_data'] = new stdClass();
			$_SESSION['reports']['payment_arrangements_modified']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['payment_arrangements_modified']['url_data'] = array(
				'name' => 'Payment Arrangements Modified',
				'link' => '/?module=reporting&mode=payment_arrangements_modified');

			// Start date
			$start_date_YYYY = $this->request->start_date_year;
			$start_date_MM	 = $this->request->start_date_month;
			$start_date_DD	 = $this->request->start_date_day;
			if(!checkdate($start_date_MM, $start_date_DD, $start_date_YYYY))
			{
				//return with no data
				$data->search_message = 'Start Date invalid or not specified.';
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels('message');
				return;
			}

			// End date
			$end_date_YYYY	 = $this->request->end_date_year;
			$end_date_MM	 = $this->request->end_date_month;
			$end_date_DD	 = $this->request->end_date_day;
			if(!checkdate($end_date_MM, $end_date_DD, $end_date_YYYY))
			{
				//return with no data
				$data->search_message = 'End Date invalid or not specified.';
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels('message');
				return;
			}

			$start_date_YYYYMMDD = 10000 * $start_date_YYYY	+ 100 * $start_date_MM + $start_date_DD;
			$end_date_YYYYMMDD	 = 10000 * $end_date_YYYY	+ 100 * $end_date_MM   + $end_date_DD;

			if($end_date_YYYYMMDD < $start_date_YYYYMMDD)
			{
				//return with no data
				$data->search_message = 'End Date must not precede Start Date.';
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels('message');
				return;
			}

			$data->search_results = $this->search_query->Fetch_Payment_Arrangements_Data(
				$start_date_YYYYMMDD,
				$end_date_YYYYMMDD,
				$this->request->company_id);
		}
		catch (Exception $e)
		{
			$data->search_message = 'Unable to execute report. Reporting server may be unavailable.';
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels('message');
			return;
		}

		// we need to prevent client from displaying too large of a result set, otherwise
		// the PHP memory limit could be exceeded;
		if(!empty($data->search_results) && count($data->search_results) > $this->max_display_rows)
		{
			$data->search_message = 'Your report would have more than ' . $this->max_display_rows . ' lines to display. Please narrow the date range.';
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels('message');
			return;
		}

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels('report_results');
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['payment_arrangements_modified']['report_data'] = $data;
	}
}

class Payment_Arrangements_Modified_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = 'Payment Arrangements Modified Report Query';

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Payment Arrangements Modified Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Payment_Arrangements_Data($start_date, $end_date, $company_id)
	{
		$this->timer->startTimer(self::$TIMER_NAME);

		$start_date = $start_date . '000000';
		$end_date   = $end_date . '235959';

		if(isset($_SESSION) && is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		if ($company_id > 0)
		{
			$company_list = "'" . $company_id . "'";
		}
		else
		{
			$company_list = "'" . implode("','", $auth_company_ids) . "'";
		}

		// This defines the level of detail that the report groups payment schedules.
		// We should never group by second, and may even want only by hour or even day.
		$date_filter = '%Y-%m-%d %H:%i';

		// This query would allow us to get all arranges for arranges of applications that occurred
		// within a specific date. A subquery translates the date range into application ids.
		$query = '-- eCash3.5 ' . __FILE__ . ':' . __LINE__ . ':' . __METHOD__ . "()
			SELECT
				company.name_short AS 'company_short',
				DATE_FORMAT(ah.date_created, '{$date_filter}') AS 'created_date',
				CONCAT(agent.name_last, ', ', agent.name_first) AS 'agent_name',
				ah.application_id,
				CONCAT(application.name_last, ', ', application.name_first) AS 'customer_name',
				COUNT(ah.arrangement_history_id) AS 'number_of_payments',
				SUM(ah.amount_payment_principal + ah.amount_payment_non_principal) AS 'amount',
				(CASE
					WHEN tr.transaction_status IS NOT NULL
					THEN tr.transaction_status
					WHEN es.event_status IS NOT NULL
					THEN es.event_status
					ELSE 'reset'
				END) AS 'status'
			FROM arrangement_history ah
			LEFT JOIN application USING (application_id)
			LEFT JOIN agent ON (agent.agent_id = ah.agent_id)
			LEFT JOIN company ON (company.company_id = ah.company_id)
			LEFT JOIN event_schedule es ON (es.event_schedule_id = ah.event_schedule_id)
			LEFT JOIN transaction_register tr ON (tr.event_schedule_id = ah.event_schedule_id)
			WHERE ah.company_id IN ({$company_list})
			AND ah.application_id IN (
				SELECT ah2.application_id
				FROM arrangement_history ah2
				WHERE ah2.company_id = ah.company_id
				AND ah2.date_created BETWEEN {$start_date} AND {$end_date})
			GROUP BY ah.application_id, DATE_FORMAT(ah.date_created, '{$date_filter}')
			ORDER BY ah.application_id ASC, ah.date_created ASC
		";
		$st = $this->db->query($query);

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$company_short = $row['company_short'];
			if (empty($reset_counts[$row['application_id']]))
			{
				$reset_counts[$row['application_id']] = 0;
			}
			$reset_counts[$row['application_id']]++;
			$row['occurance'] = $reset_counts[$row['application_id']];

			foreach (array('status', 'agent_name', 'occurance') as $item)
			{
				if (empty($data['summary'][$company_short][$item][$row[$item]]))
				{
					$data['summary'][$company_short][$item][$row[$item]] = 0;
				}
				$data['summary'][$company_short][$item][$row[$item]]++;
			}

			unset($row['company_short']);
			$data[$company_short][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}