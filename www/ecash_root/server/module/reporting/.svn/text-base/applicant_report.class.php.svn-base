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
			$this->search_query = new Applicant_Report_Query($this->server);

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

			$_SESSION['reports']['applicant']['report_data'] = new stdClass();
			$_SESSION['reports']['applicant']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['applicant']['url_data'] = array('name' => 'Applicant', 'link' => '/?module=reporting&mode=applicant');

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

			$data->search_results = $this->search_query->Fetch_Company_Applicant_Data( $start_date_YYYYMMDD,
											   $end_date_YYYYMMDD,
											   $this->request->loan_type,
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
		$_SESSION['reports']['applicant']['report_data'] = $data;
	}
}

class Applicant_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Applicant Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);

		// These status ids are not in the default list
		$this->Add_Status_Id('withdrawn', array('withdrawn','applicant','*root'));
		$this->Add_Status_Id('denied',    array('denied',   'applicant','*root'));
		$this->Add_Status_Id('addl_verify',    array('addl', 'verification',  'applicant','*root'));
	}

	public function Fetch_Company_Applicant_Data($date_start, $date_end, $loan_type, $company_id)
	{
		$this->timer->startTimer( self::$TIMER_NAME );

		//echo "\n<br><pre>" . print_r($_SESSION,true) . "</pre><br>\n";
		if (is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		$data = array();

		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

		/**
		 * In order to show up in this report, the applications must be in one of these
		 * statuses still.  This means anyone that goes back to an Unsigned status will
		 * not show.
		 */
		$status_array = array();
		$status_array[] = $this->status_ids['in_verify'];
		$status_array[] = $this->status_ids['reverified'];
		$status_array[] = $this->status_ids['in_underwriting'];
		$status_array[] = $this->status_ids['approved'];
		$status_array[] = $this->status_ids['funded'];
		$status_array[] = $this->status_ids['withdrawn'];
		$status_array[] = $this->status_ids['denied'];
		$status_array[] = $this->status_ids['addl_verify'];
		$status_id_string =  implode(",", $status_array);
		
		//OLP needs to be ignored when looking at the num reverified.[jeffd][#14374]
		$olp_agent = ECash::getFactory()->getModel('Agent');
		$olp_agent->loadBy(array('login' => 'olp'));
		$olp_agent_id = $olp_agent->agent_id;

		$query = "
			SELECT
				upper(c.name_short) AS company_name,
				sh.company_id       AS company_id,
				sh.application_id,
				sh.application_status_id,
				lt.name_short as loan_type,
				(select
					count(act.name_short) as num_addl
				from
					agent_action as aa
				join agent as ag on (ag.agent_id = aa.agent_id)
				join action as act on (act.action_id = aa.action_id)
				join company as co on (aa.company_id = co.company_id)
				where
					aa.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
				AND
					aa.company_id IN ({$company_list})
				and  act.name_short = 'verification'
				AND aa.application_id = app.application_id
					
				) as in_verify,
				(
					select
					count(act.name_short) as num_addl
				from
					agent_action as aa
				join agent as ag on (ag.agent_id = aa.agent_id)
				join action as act on (act.action_id = aa.action_id)
				join company as co on (aa.company_id = co.company_id)
				where
					aa.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
				AND
					aa.company_id IN ({$company_list})
				and  act.name_short = 'underwriting'
				AND aa.application_id = app.application_id
				) as in_underwriting,
				sum(
					CASE
						WHEN
							sh.application_status_id = {$this->status_ids['funded']}
						THEN
							1
						ELSE
							0
					END
				) as funded,
				sum(
					CASE
						WHEN
							sh.application_status_id = {$this->status_ids['approved']}
						THEN
							1
						ELSE
							0
					END
				) as approved,
				sum(
					CASE
						WHEN
							sh.application_status_id = {$this->status_ids['withdrawn']}
						THEN
							1
						ELSE
							0
					END
				) as withdrawn,
				sum(
					CASE
						WHEN
							sh.application_status_id = {$this->status_ids['denied']}
						THEN
							1
						ELSE
							0
					END
				) as denied,
				sum(
					CASE
						WHEN
							sh.application_status_id = {$this->status_ids['reverified']}
							AND sh.agent_id <> {$olp_agent_id}
						THEN
							1
						ELSE
							0
					END
				) as reverified
			 FROM
				company                 c,
				status_history          sh,
				loan_type               lt,
				application             app
			 WHERE
				sh.date_created BETWEEN '{$timestamp_start}'
				                    AND '{$timestamp_end}'
			  AND	app.application_id       =  sh.application_id
			  AND	app.loan_type_id         =  lt.loan_type_id
			  AND	sh.company_id            =  c.company_id
			  AND	sh.application_status_id IN ({$status_id_string})
			  AND	sh.company_id            IN ({$company_list})
			  AND	lt.name_short            IN ({$loan_type_list})
			 GROUP BY
			 	sh.company_id,
			 	application_id
		";
		//echo $query;
		$st = $this->db->query($query);

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			// Need data as array( Company => array( 'colname' => 'data' ) )
			//   Do all data formatting here
			$company_name = $row['company_name'];
		//	unset($row['company_name']);

			$this->Get_Module_Mode($row, FALSE);

			$data[$company_name][] = $row;
		}

		$this->timer->stopTimer( self::$TIMER_NAME );

		return $data;
	}
}

?>
