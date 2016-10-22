<?php
/**
 * Aggregate the total click, keypad, difference, and time of agent calls.
 *
 * @package Reporting
 * @subpackage PBX
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once("report_generic.class.php");

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
			$this->search_query = new Agent_Call_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
			  'end_date_MM'     => $this->request->end_date_month,
			  'end_date_DD'     => $this->request->end_date_day,
			  'end_date_YYYY'   => $this->request->end_date_year,
			  'agent_id'		=> $this->request->agent_id,
			  'company_id'      => $this->request->company_id,
			);

			$_SESSION['reports']['agent_call']['report_data'] = new stdClass();
			$_SESSION['reports']['agent_call']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['agent_call']['url_data'] = array('name' => 'Agent Call', 'link' => '/?module=reporting&mode=reporting&mode=agent_call');

			// Start date
			$start_date_YYYY = $this->request->start_date_year;
			$start_date_MM	 = $this->request->start_date_month;
			$start_date_DD	 = $this->request->start_date_day;
			if(!checkdate($start_date_MM, $start_date_DD, $start_date_YYYY))
			{
				//return with no data
				$data->search_message = "Start Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			// End date
			$end_date_YYYY	 = $this->request->end_date_year;
			$end_date_MM	 = $this->request->end_date_month;
			$end_date_DD	 = $this->request->end_date_day;
			if(!checkdate($end_date_MM, $end_date_DD, $end_date_YYYY))
			{
				//return with no data
				$data->search_message = "End Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			$start_date_YYYYMMDD = 10000 * $start_date_YYYY	+ 100 * $start_date_MM + $start_date_DD;
			$end_date_YYYYMMDD	 = 10000 * $end_date_YYYY	+ 100 * $end_date_MM   + $end_date_DD;

			if($end_date_YYYYMMDD < $start_date_YYYYMMDD)
			{
				//return with no data
				$data->search_message = "End Date must not precede Start Date.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			$data->search_results = $this->search_query->Fetch_Agent_Call_Data($start_date_YYYYMMDD,
												 $end_date_YYYYMMDD,
												 $this->request->agent_id,
												 $this->request->company_id);
		}
		catch (Exception $e)
		{
			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}
		// we need to prevent client from displaying too large of a result set, otherwise
		// the PHP memory limit could be exceeded;
		if(!empty($data->search_results) && count($data->search_results) > $this->max_display_rows)
		{
			$data->search_message = "Your report would have more than " . $this->max_display_rows . " lines to display. Please narrow the date range.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}


		// Sort if necessary
		$data = $this->Sort_Data($data);



		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);

		$_SESSION['reports']['agent_call']['report_data'] = $data;
	}
}

class Agent_Call_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Agent Call Report Query";

	private $calls = array();

	/**
	 * Fetches data for the Agent Call Report
	 *
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   string $agent_id   array of agent_ids
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Agent_Call_Data($date_start, $date_end, $agent_id, $company_id)
	{
		$this->timer->startTimer(self::$TIMER_NAME);

		$query = $this->getCompiledQuery($date_start, $date_end, $agent_id, $company_id);

		$dial_channels = array();

		$st = $this->db->query($query);
		while ($row = $st->fetch(PDO::ASSOC))
		{
			$this->calls[$row['agent']]['company_name'] = $row['company_name'];

			$raw_event = unserialize($row['result']);
			$agent = $row['agent'];

			// NOTE: The query above is returning the events ordered by pbx_event ASC.
			// The switch statements will actually trigger in order.
			switch ($row['pbx_event'])
			{
				case 'CDR Import':
					if (!ctype_digit((string)$raw_event['dst']) || strlen($raw_event['dst']) < 10)
					{
						break;
					}

					$dial_channels[$raw_event['channel']] = true;

					$this->calls[$agent]['time_spent'] += $raw_event['duration'];

					if ($raw_event['disposition'] == 'ANSWERED')
					{
						$this->calls[$agent]['keypad_dials_completed']++;
					}
					break;

				case 'Dial':
					if (empty($dial_channels[$raw_event['Source']]))
					{
						break;
					}

					$this->calls[$agent]['keypad_dials']++;
					break;

				case 'Originate':
					$this->calls[$agent]['click_dials']++;
					if ($raw_event['Response'] == 'Success')
					{
						$this->calls[$agent]['click_dials_completed']++;
					}
					break;
			}
		}

		foreach ($this->calls as $agent => $call)
		{
			$this->calls[$agent]['keypad_dials'] -= $call['click_dials_completed'];
			$this->calls[$agent]['keypad_dials_completed'] -= $call['click_dials_completed'];
		}

		$data = $this->getFormatedResults();

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}

	/**
	 * Compile our data query based on our incoming parameters
	 *
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   string $agent_id   array of agent_ids
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 */
	private function getCompiledQuery($date_start, $date_end, $agent_id, $company_id)
	{
		if(!is_array($agent_id) || 0 == count($agent_id) )
		{
			$agent_statement = '';
		}
		else
		{
			$agent_statement = "AND ph.agent_id IN (" . join(",",$agent_id) . ")";
		}

		$max_report_retrieval_rows = $this->max_display_rows + 1;

		if(isset($_SESSION) && is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		if( $company_id > 0 )
		{
			$company_list = "'{$company_id}'";
		}
		else
		{
			$company_list = "'" . implode("','", $auth_company_ids) . "'";
		}

		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';

		// Now build the report results array...
		return "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				co.name_short AS company_name,
				co.company_id AS company_id,
				CONCAT(
					ag.name_first,
					' ',
					ag.name_last
				) AS 'agent',
				ph.*
			FROM pbx_history AS ph
				JOIN company AS co ON (ph.company_id = co.company_id)
				JOIN agent AS ag ON (ph.agent_id = ag.agent_id)
			WHERE ph.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
				AND co.company_id IN ({$company_list})
				" . $agent_statement . "
				AND ph.pbx_event IN ('Originate', 'Dial', 'CDR Import')
			ORDER BY
				ph.agent_id,
				ph.pbx_event
--				ph.date_created,
--				ph.application_contact_id
		";
	}

	/**
	 * Format the data we've gotten into something the report can use.
	 *
	 * @return array column and field associative array for the company
	 */
	private function getFormatedResults()
	{
		$data = array();

		foreach ($this->calls as $agent => $call)
		{
			if (empty($call['click_dials']) && empty($call['keypad_dials']))
			{
				continue;
			}

			$call['agent'] = strtolower(preg_replace("/^ /", "", $agent)); // lowercase and remove space if no name_first for sorting
			$call['click_dials'] = ( is_numeric($call['click_dials']) ? $call['click_dials'] : 0);
			$call['click_dials_completed'] = ( is_numeric($call['click_dials_completed']) ? $call['click_dials_completed'] : 0);
			$call['keypad_dials'] = ( is_numeric($call['keypad_dials']) ? $call['keypad_dials'] : 0);
			$call['keypad_dials_completed'] = ( is_numeric($call['keypad_dials_completed']) ? $call['keypad_dials_completed'] : 0);
			$call['more_dials'] = $call['keypad_dials'] - $call['click_dials'];
			$call['more_dials_completed'] = $call['keypad_dials_completed'] - $call['click_dials_completed'];

			if ( is_numeric($call['time_spent']) && $call['time_spent'] > 0)
			{
				$hour_seconds = 60 * 60;
				$hours = floor($call['time_spent'] / $hour_seconds);
				$minutes = floor(($call['time_spent'] % $hour_seconds) / 60);
				$call['time_spent'] = $hours . 'hrs ' . $minutes . 'mins';
			}
			else
			{
				$call['time_spent'] = '0hrs 0mins';
			}

			$data[$call['company_name']][] = $call;
		}

		return $data;
	}
}

?>
