<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once(SERVER_MODULE_DIR . "reporting/report_generic.class.php");
require_once( SQL_LIB_DIR . "fetch_status_map.func.php");

class Report extends Report_Generic
{
	private $search_query;

	public function Generate_Report()
	{
		// Generate_Report() expects the following from the request form:
		// company_id

		try
		{
			$this->search_query = new Open_Advances_Detail_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'specific_date_MM'   => $this->request->specific_date_month,
			  'specific_date_DD'   => $this->request->specific_date_day,
			  'specific_date_YYYY' => $this->request->specific_date_year,
			  'company_id' => $this->request->company_id,
			);

			$_SESSION['reports']['open_advances']['report_data'] = new stdClass();
			$_SESSION['reports']['open_advances']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['open_advances']['url_data'] = array('name' => 'Open Advances', 'link' => '/?module=reporting&mode=open_advances');

			if( ! checkdate($data->search_criteria['specific_date_MM'],
			                $data->search_criteria['specific_date_DD'],
			                $data->search_criteria['specific_date_YYYY']) )
			{
				$data->search_message = "Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			$specific_date_YYYYMMDD = 10000 * $data->search_criteria['specific_date_YYYY'] +
			                          100   * $data->search_criteria['specific_date_MM'] +
			                                  $data->search_criteria['specific_date_DD'];

			$data->search_results = $this->search_query->FetchOpenAdvancesData( $specific_date_YYYYMMDD,
			                                                                       $this->request->company_id );
		}
		catch (Exception $e)
		{
			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['open_advances_detail']['report_data'] = $data;
	}
}

class Open_Advances_Detail_Report_Query extends Base_Report_Query
{
	static protected $TIMER_NAME = "Open Advances Detailed Query";

	protected $status_map = array();

	protected $date;
	protected $results = array();

	protected static $empty = array(
		'positive_count' => 0,
		'positive_balance' => 0,
		'negative_count' => 0,
		'negative_balance' => 0,
		'total_count' => 0,
		'total_balance' => 0,
	);

	public function Results()
	{
		return $this->results;
	}

	public function Status_Map()
	{
		return $this->status_map;
	}

	public function Date()
	{
		return $this->date;
	}

	public function FetchOpenAdvancesData($date, $companies)
	{
		$this->log->Write("Starting Fetch");
		$this->timer->startTimer(self::$TIMER_NAME);

		// fetch all status names and IDs
		$this->Get_Status_Names();

		// normalize dates
		$today = strtotime(substr($date, 0, 4).'-'.substr($date, 4, 2).'-'.substr($date,6,2));
		$yesterday = strtotime('-1 day', $today);

		// save this
		$this->date = $today;

		// fetch the two days
		$result_t = $this->Query($today, $companies);
		$result_y = $this->Query($yesterday, $companies);

		// adjust to 6PM of the previous day for nightly stuff
		$today = strtotime(date('Y-m-d 18:00:00', $yesterday));

		// get the first record for yesterday
		$rec_y = $this->Next_Record($result_y);
		$rec_t = $this->Next_Record($result_t);

		while ($rec_y || $rec_t)
		{
			// records that exist today but didn't exist yesterday
			while ($rec_t && (!$rec_y || ($rec_t->application_id < $rec_y->application_id)))
			{
				$this->Add_Record('total_today', $rec_t);

				// these accounts are new!
				if ($rec_t->date_status_set < $today)
				{
					$this->Add_Record('unbalanced', $rec_t);
				}
				else
				{
					$this->Add_Record('new', $rec_t);
				}

				$rec_t = $this->Next_Record($result_t);
			}

			// records that existed yesterday but don't exist today
			while ($rec_y && (!$rec_t || ($rec_y->application_id < $rec_t->application_id)))
			{
				$this->Add_Record('total_yesterday', $rec_y);
				$this->Add_Record('balanced', $rec_y);

				$rec_y = $this->Next_Record($result_y);
			}

			// records that exist both days
			while ($rec_y && $rec_t && ($rec_t->application_id === $rec_y->application_id))
			{
				$this->Add_Record('total_today', $rec_t);
				$this->Add_Record('total_yesterday', $rec_y);

				if ($rec_t->status !== $rec_y->status)
				{
					// indicate the swap
					$rec_y->changed_to = $rec_t->status;
					$rec_t->changed_from = $rec_y->status;

					// changed statuses
					$this->Add_Record('changed_to', $rec_y);
					$this->Add_Record('changed_from', $rec_t);
				}
				elseif ($rec_t->balance !== $rec_y->balance)
				{
					// balance changed -- only show the difference
					$rec_t->balance_change = ($rec_t->balance - $rec_y->balance);

					$this->Add_Record('outstanding', $rec_y);
					$this->Add_Record('changed_balance', $rec_t);
				}
				else
				{
					// no changes
					$this->Add_Record('outstanding', $rec_t);
				}

				// move along...
				$rec_y = $this->Next_Record($result_y);
				$rec_t = $this->Next_Record($result_t);
			}
		}

		// zero these out, if necessary
		foreach ($this->results as $status=>&$results)
		{
			if (!isset($results['total_yesterday'])) $results['total_yesterday'] = self::$empty;
			if (!isset($results['total_today'])) $results['total_today'] = self::$empty;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $this->results;
	}

	protected function Get_Status_Names()
	{
		$this->status_map = array();

		$query = "
			SELECT
				application_status_id,
				name,
				name_short
			FROM application_status
		";
		$st = $this->db->Query($query);

		while ($rec = $st->fetch(PDO::FETCH_ASSOC))
		{
			$name = $rec['name'];

			if (strtolower(substr($rec['name_short'], -6)) === 'queued')
			{
				$name .= ' ('.ucfirst($rec['name_short']).')';
			}

			$this->status_map[$rec['application_status_id']] = $name;
		}
	}

	protected function Query($date, $company_id) 
	{
		$end_date = date('Ymd180000', $date);

		if(!empty($company_id))
		{
			$company_where = "(ea.company_id IN ({$company_id})) AND";
		}
		else
		{
			$company_where = "";
		}


		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				account_balance.application_id,
				current.application_status_id,
				current.date_created AS date_status_set,
				account_balance.balance,
				c.name_short as company
			FROM
				(
					SELECT
						ea.application_id,
						ea.company_id,
						SUM(ea.amount) AS balance
					FROM
					event_amount AS ea
					INNER JOIN event_amount_type AS eat USING (event_amount_type_id)
					JOIN transaction_register tr USING (transaction_register_id)
				  WHERE
					(eat.name_short = 'principal') AND
					{$company_where}
					(tr.date_created <= '{$end_date}') AND
					(tr.transaction_status <> 'failed' OR
					  NOT EXISTS (
						SELECT transaction_history_id
						  FROM transaction_history AS history
						  WHERE
							history.transaction_register_id = tr.transaction_register_id
							AND history.date_created <= '{$end_date}'
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
				LEFT JOIN company as c ON (
					c.company_id = account_balance.company_id
				)
			WHERE
				current.status_history_id = (
					SELECT
						status_history_id
					FROM
						status_history
					WHERE
						(status_history.application_id = account_balance.application_id) AND
						(status_history.date_created < '{$end_date}')
					ORDER BY
						status_history.date_created DESC
					LIMIT 1
				)
			ORDER BY
				account_balance.application_id
				";

		// This report should ALWAYS hit the master.
		$db = ECash::getMasterDb();
		return $db->query($query);
	}

	protected function Next_Record(PDOStatement $result)
	{
		$rec = $result->fetch(PDO::FETCH_OBJ);

		if ($rec)
		{
			$rec->application_id = (int)$rec->application_id;
			$rec->status = (int)$rec->application_status_id;
			$rec->balance = (float)$rec->balance;
			$rec->date_status_set = strtotime($rec->date_status_set);
		}

		return $rec;
	}

	protected function Add_Record($results, $record)
	{
//		$this->log->Write("Add_Record: ".print_r($results, true).print_r($record, true));

		$status = $record->status;

		// apportion space for this status
		if (!isset($this->results[$record->company])) {
			$this->results[$record->company] = array();
		}

		$results_arr =& $this->results[$record->company];
		if (!isset($results_arr[$record->status]))
		{
			$results_arr[$record->status] = array();
		}

		// and now, space for this type of result
		if (!isset($results_arr[$record->status][$results]))
		{
			$results_arr[$record->status][$results] = array();
		}

		$results = &$results_arr[$record->status][$results];

		if (isset($record->changed_to))
		{

			if (!isset($results[$record->changed_to]))
			{
				$results[$record->changed_to] = self::$empty;
			}

			$results = &$results[$record->changed_to];

		}
		elseif (isset($record->changed_from))
		{

			if (!isset($results[$record->changed_from]))
			{
				$results[$record->changed_from] = self::$empty;
			}

			$results = &$results[$record->changed_from];

		}

		if (!count($results))
		{
			$results = self::$empty;
		}

		if (isset($record->balance))
		{

			// for balance adjustments, put us in the
			// proper column, but display the difference
			$balance = isset($record->balance_change) ? $record->balance_change : $record->balance;

			if ($record->balance > 0)
			{
				$results['positive_count']++;
				$results['positive_balance'] += $balance;
			}
			else
			{
				$results['negative_count']++;
				$results['negative_balance'] += $balance;
			}

			$results['total_count']++;
			$results['total_balance'] += $balance;

		}

		return;

	}

}

?>
