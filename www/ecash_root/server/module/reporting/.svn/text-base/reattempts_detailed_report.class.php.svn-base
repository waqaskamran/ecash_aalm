<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once("report_generic.class.php");
require_once( SERVER_CODE_DIR . "reattempts_report_query.class.php" );

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
			$this->search_query = new Reattempts_Detailed_Report_Query($this->server);

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
			  'loan_type'       => $this->request->loan_type
			);

			$_SESSION['reports']['reattempts_detailed']['report_data'] = new stdClass();
			$_SESSION['reports']['reattempts_detailed']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['reattempts_detailed']['url_data'] = array('name' => 'Reattempts Detailed', 'link' => '/?module=reporting&mode=reattempts_detailed');

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
			$data->search_results = $this->search_query->Fetch_Reattempts_Data($start_date_YYYYMMDD,
												$end_date_YYYYMMDD,	$this->request->company_id, $this->request->loan_type);
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
		$_SESSION['reports']['reattempts_detailed']['report_data'] = $data;
	}
}

/* This shouldn't be here [benb] */
class Reattempts_Detailed_Report_Query extends Reattempts_Report_Query
{

	private static $TIMER_NAME = "Reattempts Detailed Report Query";

	protected $results;
	protected $status_map;

	protected function addRow($row)
	{

		if (!$this->status_map) $this->fetchStatusMap();

		$company = $this->company_map[$row->company_id];
		$status = $this->status_map[$row->application_status_id];

		$nrow = array(
			'company_id' => $row->company_id,
			'application_status_id' => $row->application_status_id,
            'application_id' => $row->application_id,
            'status' => $status,
            'event_schedule_id' => $row->event_schedule_id,
            'new_principal' => $row->new->p,
            'new_svr_charge' => $row->new->s,
            'new_fees' => $row->new->f,
            're_principal' => $row->reattempt->p,
            're_svr_charge' => $row->reattempt->s,
            're_fees' => $row->reattempt->f,
        );

		// GF 6978
		$this->Get_Module_Mode($nrow);

		$this->results[$company][] = $nrow;


		return;

	}

	protected function formatResults()
	{
		return $this->results;
	}

	protected function fetchStatusMap()
	{

		$query = "
			SELECT
				application_status_id,
				level0_name
			FROM
				application_status_flat
			WHERE
				active_status = 'active'
		";

		$st = $this->db->query($query);

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$this->status_map[$row['application_status_id']] = $row['level0_name'];
		}

		return;

	}

}

?>
