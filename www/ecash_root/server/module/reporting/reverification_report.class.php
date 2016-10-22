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
			$this->search_query = new Reverification_Report_Query($this->server);

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

			$_SESSION['reports']['reverification']['report_data'] = new stdClass();
			$_SESSION['reports']['reverification']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['reverification']['url_data'] = array('name' => 'Reverification', 'link' => '/?module=reporting&mode=reverification');

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

			$data->search_results = $this->search_query->Fetch_Reverification_Report_Data($start_date_YYYYMMDD,
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
		$_SESSION['reports']['reverification']['report_data'] = $data;

	}
}
class Reverification_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Reverification Report Query";

	private $system_id;

	public function __construct(Server $server)
	{
		parent::__construct($server);


		$this->system_id = $server->system_id;

		$this->Add_Status_Id('withdrawn', array('withdrawn', 'applicant', '*root'));
		$this->Add_Status_Id('denied',    array('denied',    'applicant', '*root'));
		$this->Add_Status_Id('confirmed', array('confirmed', 'prospect',  '*root'));
	}

	public function Fetch_Reverification_Report_Data($date_start, $date_end, $loan_type, $company_id)
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
			SELECT distinct
				upper(co.name_short) AS company_name,
				app.company_id       AS company_id,
				app.application_status_id AS application_status_id,
				concat(lower(a.name_first), ' ', lower(a.name_last)) AS agent_name,
				app.application_id,
				c.description as comment
			 FROM
				loan_type AS lt,
				application             AS app,
				(SELECT	shv.company_id,
								(SELECT
						agent_id
					  FROM
						loan_action_history sc
					  WHERE
						
					   	sc.application_id = shv.application_id
					   AND	sc.date_created   < IFNULL((SELECT min(shsref.date_created)
						                             FROM  status_history     shsref
						                             WHERE shsref.application_id = shv.application_id
						                              AND  shsref.company_id     = shv.company_id
						                              AND  shsref.application_status_id IN ({$this->approved},
						                                                                    {$this->in_underwriting})
						                              AND  shsref.date_created   > shv.date_created
						                           ), '2099-12-31 23:59:59'
						                          )
					   AND  sc.date_created   > shv.date_created
					   AND  sc.application_status_id          = {$this->reverified}
					   limit 1
					) as agent_id,
					shv.application_id,
					(SELECT
						DISTINCT 1
					  FROM
						status_history AS shs
					  WHERE
						shs.company_id            = shv.company_id
					   AND	shs.application_id        = shv.application_id
					   AND	shs.application_status_id = {$this->reverified}
					   AND	shs.date_created < IFNULL((SELECT min(shsref.date_created)
						                            FROM  status_history          shsref
						                            WHERE shsref.application_id = shv.application_id
						                             AND  shsref.company_id     = shv.company_id
						                             AND  shsref.application_status_id IN ({$this->approved},
						                                                                   {$this->in_underwriting})
						                             AND  shsref.date_created > shv.date_created
						                         ), '2099-12-31 23:59:59'
						                         )
					   AND  shs.date_created > shv.date_created
					   AND  shs.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
					) AS reverified_flag,
					(SELECT
						loan_action_id
					  FROM
						loan_action_history sc
					  WHERE
						
					   	sc.application_id = shv.application_id
					   AND	sc.date_created   < IFNULL((SELECT min(shsref.date_created)
						                             FROM  status_history     shsref
						                             WHERE shsref.application_id = shv.application_id
						                              AND  shsref.company_id     = shv.company_id
						                              AND  shsref.application_status_id IN ({$this->approved},
						                                                                    {$this->in_underwriting})
						                              AND  shsref.date_created   > shv.date_created
						                           ), '2099-12-31 23:59:59'
						                          )
					   AND  sc.date_created   > shv.date_created
					   AND  sc.application_status_id          = {$this->reverified}
					   limit 1
					) as loan_action_id
				  FROM  status_history          AS shv,
					company                 AS cv
				  WHERE shv.date_created BETWEEN DATE_SUB('{$timestamp_start}', INTERVAL 10 DAY) AND '{$timestamp_end}'
				   AND	shv.company_id = cv.company_id
				   AND	shv.agent_id > 0

				   -- Process type 2 (directly to underwriting queue)
				   AND	(CASE WHEN cv.ecash_process_type = 2
				              THEN shv.application_status_id IN ({$this->approved}, {$this->confirmed}, {$this->in_underwriting})

				   -- Process type 1 (directly to verification queue)
					      WHEN cv.ecash_process_type = 1
					      THEN shv.application_status_id IN ({$this->approved}, {$this->in_underwriting})

				   -- Default, dont know this process type, assume similar to type 1
					      ELSE shv.application_status_id IN ({$this->approved}, {$this->in_underwriting})
					 END)
				   AND	shv.company_id  IN ({$company_list})
				) temp
			 LEFT OUTER JOIN agent     a  ON temp.agent_id   =  a.agent_id
			 LEFT OUTER JOIN company   co ON temp.company_id = co.company_id
			 LEFT OUTER JOIN loan_actions   c  ON temp.loan_action_id =  c.loan_action_id
			 WHERE
				temp.reverified_flag      =  1
			  and   temp.loan_action_id is not null
			  AND	lt.company_id             =  co.company_id
			  AND	app.application_id        =  temp.application_id
			  AND	lt.name_short             IN ({$loan_type_list})
			  AND	a.system_id               =  {$this->system_id}
			 ORDER BY
				co.name_short,
				lower(a.name_first),
				lower(a.name_last),
				temp.agent_id,
				app.application_id
			 LIMIT {$max_report_retrieval_rows}";

		//echo "<pre>query:\n" . str_replace("\t","        ",$query) . "</pre>\n";
		//exit;

		$st = $this->db->query($query);

		if( $st->rowCount() == $max_report_retrieval_rows )
			return false;

    while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			// Need data as array( Company => array( 'colname' => 'data' ) )
			//   Do all data formatting here
			$company_name = $row['company_name'];
		//	unset($row['company_name']);

			$this->Get_Module_Mode($row);

			$row['agent_name'] = ucwords($row['agent_name']);
			$performance_data[$company_name][] = $row;
		}

		$this->timer->stopTimer( self::$TIMER_NAME );

		return $performance_data;
	}
}

?>
