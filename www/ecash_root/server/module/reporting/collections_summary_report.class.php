<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once(SERVER_MODULE_DIR . "reporting/report_generic.class.php");

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
			$this->search_query = new Collections_Summary_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
			  'end_date_MM'     => $this->request->end_date_month,
			  'end_date_DD'     => $this->request->end_date_day,
			  'end_date_YYYY'   => $this->request->end_date_year,
			  'company_id'      => $this->request->company_id,
		
			);

			$_SESSION['reports']['collections_summary']['report_data'] = new stdClass();
			$_SESSION['reports']['collections_summary']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['collections_summary']['url_data'] = array('name' => 'Collections Summary', 'link' => '/?module=reporting&mode=collections_summary');

			// Start date
			$start_date_YYYY = $this->request->start_date_year;
			$start_date_MM   = $this->request->start_date_month;
			$start_date_DD   = $this->request->start_date_day;
			if(!checkdate($start_date_MM, $start_date_DD, $start_date_YYYY))
			{
				//return with no data
				$data->search_message = "Start Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			// End date
			$end_date_YYYY = $this->request->end_date_year;
			$end_date_MM   = $this->request->end_date_month;
			$end_date_DD   = $this->request->end_date_day;
			if(!checkdate($end_date_MM, $end_date_DD, $end_date_YYYY))
			{
				//return with no data
				$data->search_message = "End Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			$start_date_YYYYMMDD = 10000 * $start_date_YYYY + 100 * $start_date_MM + $start_date_DD;
			$end_date_YYYYMMDD   = 10000 * $end_date_YYYY   + 100 * $end_date_MM   + $end_date_DD;

			if($end_date_YYYYMMDD < $start_date_YYYYMMDD)
			{
				//return with no data
				$data->search_message = "End Date must not precede Start Date.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			$data->search_results = $this->search_query->Fetch_Data( $start_date_YYYYMMDD,
											     $end_date_YYYYMMDD,
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
		if( $data->search_results === false )
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
		$_SESSION['reports']['collections_summary']['report_data'] = $data;
	}
}

class Collections_Summary_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Collections Summary Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);

	}


	public function Fetch_Data($date_start, $date_end, $company_id)
	{
		$this->timer->startTimer(self::$TIMER_NAME);
		$max_report_retrieval_rows = $this->max_display_rows + 1;

		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';

	
		$company_list = $this->Format_Company_IDs($company_id);


		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
		SELECT  date_event, 
				count(event_schedule_id) as num_scheduled, 
				abs(sum(amount_principal + amount_non_principal)) as amount_scheduled, 
				ifnull(b.num_returned, 0) as num_returned, 
				abs(ifnull(b.amount_returned, 0)) as amount_returned,
				ifnull(esf.num_scheduled_future, 0) as num_scheduled_future,
				abs(ifnull(esf.amount_scheduled_future, 0)) as amount_scheduled_future
		FROM event_schedule as es
		LEFT JOIN
		( -- Returns received during date range
		    SELECT
		        DATE_FORMAT(ar.date_created, '%Y-%m-%d')             AS return_date,
		        SUM(IF(tr1.context = 'arrangement', 1, 0))          AS num_returned,
		        SUM(IF(tr1.context = 'arrangement',ach.amount, 0)) AS amount_returned
			    FROM ach_report AS ar
		    JOIN ach ON ach.ach_report_id = ar.ach_report_id
		    JOIN
		    (
		        SELECT tr.ach_id,
		               es.context
		        FROM transaction_register AS tr
		        JOIN event_schedule AS es on es.event_schedule_id = tr.event_schedule_id
		        GROUP BY tr.ach_id
		    ) AS tr1 ON tr1.ach_id = ach.ach_id
		    JOIN ach_return_code ac using (ach_return_code_id)
		    WHERE  ach.ach_type = 'debit'
		    AND ach.ach_status = 'returned'
		    AND ach.company_id in $company_list
		    GROUP BY return_date
		) AS b ON es.date_event = b.return_date
		LEFT JOIN 
		(
			select DATE_FORMAT(es2.date_created, '%Y-%m-%d') as date_created, 
				count(event_schedule_id) as num_scheduled_future, 
			sum(amount_principal + amount_non_principal) as amount_scheduled_future 
			from event_schedule as es2 
			where es2.context ='arrangement'  
			AND es2.date_created BETWEEN '$timestamp_start' AND '$timestamp_end'
			AND es2.company_id in $company_list
			group by es2.date_created
		)as esf on es.date_event = esf.date_created
		where es.context = 'arrangement'  
		AND es.date_event BETWEEN '$timestamp_start' AND '$timestamp_end'
		AND es.company_id in $company_list
		group by es.date_event
		LIMIT {$max_report_retrieval_rows}
		";
		$st = $this->db->query($query);

		if( $st->rowCount() == $max_report_retrieval_rows )
			return false;
		$data = array();
		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$data['company'][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>
