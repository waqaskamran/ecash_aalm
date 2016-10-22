<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once("report_generic.class.php");

class Report extends Report_Generic
{
	protected $report_query_class = array(
	                                      'Email_Queue_Contents_Query',
	                                      'Fetch_Email_Queue_Contents_Data'
	                                     );

	protected $report_full_name =  'Email Queue Contents';

	protected $report_search_criteria = array(
	                                          'queue_names',
	                                          'company_id'
	                                         );
}

class Email_Queue_Contents_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Email Queue Contents Query";

	/**
	 * Fetches data for the Email Queue Contents Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   string $loan_type  standard || card
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Email_Queue_Contents_Data($queue_names, $company_id)
	{
		$this->timer->startTimer(self::$TIMER_NAME);

		$company_list = $this->Format_Company_IDs($company_id);

		if (!is_array($queue_names)) $queue_names = array($queue_names);
		$queue_names = "'" . implode("','", $queue_names) . "'";

		$mysqli = get_mysqli("SLAVE_DB_");
		
		$query = "
			-- eCash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
			SELECT
			    ieq.archive_id archive_id,
			    ieq.application_id application_id,
			    ieq.company_id company_id,
				ieq.queue_name queue_name,
			    ieq.date_created date_created,
			    IF(ieq.date_follow_up != '0000-00-00 00:00:00', ieq.date_follow_up, ieq.date_available) date_available,
			    ieq.date_follow_up date_follow_up,			    
			    c.name_short company_name,
			    IF(ag.name_last = NULL, 'none', CONCAT(ag.name_last, ', ', ag.name_first)) pulling_agent,
				IF(a.application_status_id = NULL, 0, a.application_status_id) application_status_id
			  FROM
			    incoming_email_queue ieq
			    JOIN company c ON (ieq.company_id = c.company_id)
			    LEFT JOIN agent ag ON (ieq.agent_id = ag.agent_id)
				LEFT JOIN application a ON (ieq.application_id = a.application_id)
			  WHERE
			    ieq.queue_name IN ({$queue_names}) AND
			    ieq.company_id IN ({$company_list})
			";

		$data = array();
		$result = $mysqli->Query($query);
		while($row = $result->Fetch_Array_Row(MYSQLI_ASSOC))
		{
			if ('collections' === $row['queue_name'])
			{
				$row['alt_module'] = 'collections';
				$row['alt_mode'] = 'internal';
			}
			else
			{
				$row['alt_module'] = 'loan_servicing';
				$row['alt_mode'] = 'customer_service';
			}

			// Report_Parent only makes the link if the 'mode' isset, so...
			if ($row['application_id'] !== '0')
			{
				$this->Get_Module_Mode($row, $row["company_id"]);
			}
			else
			{
				$row['module'] = $row['alt_module'];
				$row['mode'] = $row['alt_mode'];
				$row['link_override'] = "&action=show_email&archive_id=" . $row['archive_id'];
			}

			$data[$row["company_name"]][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);
		return $data;
	}
}

?>
