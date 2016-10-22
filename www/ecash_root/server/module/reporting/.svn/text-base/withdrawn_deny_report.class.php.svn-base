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

	public function __construct(Server $server, $request, $module_name, $report_name)
	{
		parent::__construct($server, $request, $module_name, $report_name);

	}

	public function Generate_Report()
	{
		// Generate_Report() expects the following from the request form:
		//
		// criteria start_date YYYYMMDD
		// criteria end_date   YYYYMMDD
		//

		try
		{
			$this->search_query = new Withdrawn_Deny_Report_Query($this->server);
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

			$_SESSION['reports']['withdrawn_deny']['report_data'] = new stdClass();
			$_SESSION['reports']['withdrawn_deny']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['withdrawn_deny']['url_data'] = array('name' => 'Verification Performance', 'link' => '/?module=reporting&mode=withdrawn_deny');

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
			$end_date_YYYYMMDD   = 10000 * $end_date_YYYY	+ 100 * $end_date_MM   + $end_date_DD;

			if($end_date_YYYYMMDD < $start_date_YYYYMMDD)
			{
				//return with no data
				$data->search_message = "End Date must not precede Start Date.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			$data->search_results = $this->search_query->Fetch_Company_Performance_Data( $start_date_YYYYMMDD,
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
		$_SESSION['reports']['withdrawn_deny']['report_data'] = $data;
	}
}

class Withdrawn_Deny_Report_Query extends Base_Report_Parent
{
	private static $TIMER_NAME = "Withdrawn / Deny Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);

		$this->Add_Status_Id('withdrawn', array('withdrawn', 'applicant', '*root'));
		$this->Add_Status_Id('denied',    array('denied',    'applicant', '*root'));

	}

	public function Fetch_Company_Performance_Data($date_start, $date_end, $loan_type, $company_id)
	{
		$this->timer->startTimer( self::$TIMER_NAME );

		if (is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		$performance_data = array();

		$max_report_retrieval_rows = $this->max_display_rows + 1;

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';

		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				upper(co.name_short)               AS company_name,
				tmp_app_hist.application_id        AS application_id,
				tmp_app_hist.s_name                AS name,
				(CASE WHEN c.comment IS NULL
				      THEN '-'
				      ELSE substr(c.comment,1,82)
				 END)                              AS comment
		         FROM
			   	(SELECT DISTINCT sh.application_id AS application_id,
				        sh.application_status_id   AS s_id,
				        asp.name_short             AS s_name
				  FROM  status_history             AS sh,
				  	application_status         AS asp
				  WHERE sh.date_created BETWEEN '{$timestamp_start}'
				                            AND '{$timestamp_end}'
				   AND  sh.application_status_id = asp.application_status_id
				   AND  sh.application_status_id IN ({$this->withdrawn},{$this->denied})
				)                                  AS tmp_app_hist,
				application                        AS a,
				company                            AS co,
				loan_type                          AS lt
			 LEFT OUTER JOIN comment c ON c.comment_id = (SELECT MAX(c2.comment_id)
			                                               FROM  comment AS c2
			                                               WHERE c2.application_id = tmp_app_hist.application_id
			                                                AND  c2.type IN ('withdraw','deny'))
			 WHERE
			 	tmp_app_hist.application_id = a.application_id
			  AND	a.application_status_id     = tmp_app_hist.s_id
			  AND	a.loan_type_id              = lt.loan_type_id
			  AND	co.company_id               = a.company_id
			  AND	a.company_id IN ({$company_list})
			  AND	lt.name_short IN ({$loan_type_list})
			 ORDER	BY tmp_app_hist.application_id
			 LIMIT	{$max_report_retrieval_rows}";

		//echo "<pre>query: " . str_replace( "\t", "", $query ) . "</pre>\n";
		//exit;

		$st = $this->db->query($query);

		if( $st->rowCount() == $max_report_retrieval_rows )
			return false;

    while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			// Need data as array( Company => array( 'colname' => 'data' ) )
			//   Do all data formatting here
			$company_name = $row['company_name'];
			unset($row['company_name']);

			$this->Get_Module_Mode($row);

			$withdrawn_data[$company_name][] = $row;
		}

		$this->timer->stopTimer( self::$TIMER_NAME );

		return isset($withdrawn_data) ? $withdrawn_data : null;
	}
}

?>
