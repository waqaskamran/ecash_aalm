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
		try
		{
			$this->search_query = new Idvscore_Report_Query($this->server);

			// Generate_Report() expects the following from the request form:
			//
			// criteria start_date YYYYMMDD
			// criteria end_date   YYYYMMDD
			// company_id
			//

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

			$_SESSION['reports']['score']['report_data'] = new stdClass();
			$_SESSION['reports']['score']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['score']['url_data'] = array('name' => 'Score', 'link' => '/?module=reporting&mode=score');

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

			$data->search_results = $this->search_query->Fetch_Idvscore_Data( $start_date_YYYYMMDD,
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
		$_SESSION['reports'][$this->report_name]['report_data'] = $data;
	}
}

class Idvscore_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Score Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);

		$this->Add_Status_Id('cashline',      array('cashline',  'customer',             '*root'));
		$this->Add_Status_Id('current',       array('current',   'arrangements',         'collections', 'customer', '*root'));
		$this->Add_Status_Id('failed',        array('arrangements_failed','arrangements','collections', 'customer', '*root'));
		$this->Add_Status_Id('arr_hold',      array('hold',      'arrangements',         'collections', 'customer', '*root'));
		$this->Add_Status_Id('unverified',    array('unverified','bankruptcy',           'collections', 'customer', '*root'));
		$this->Add_Status_Id('verified',      array('verified',  'bankruptcy',           'collections', 'customer', '*root'));
		$this->Add_Status_Id('in_bankruptcy', array('dequeued',  'bankruptcy',           'collections', 'customer', '*root'));
		$this->Add_Status_Id('bankruptcy',    array('queued',    'bankruptcy',           'collections', 'customer', '*root'));
		$this->Add_Status_Id('in_contact',    array('dequeued',  'contact',              'collections', 'customer', '*root'));
		$this->Add_Status_Id('contact',       array('queued',    'contact',              'collections', 'customer', '*root'));
		$this->Add_Status_Id('cfollowup',     array('follow_up', 'contact',              'collections', 'customer', '*root'));
		$this->Add_Status_Id('qcready',       array('ready',     'quickcheck',           'collections', 'customer', '*root'));
		$this->Add_Status_Id('qcsent',        array('sent',      'quickcheck',           'collections', 'customer', '*root'));
	}

	private function Get_Customer_Status_Ids()
	{
		return implode( ",", array($this->cashline,
		                           $this->active,
		                           $this->funded,
		                           $this->fund_failed,
		                           $this->hold,
		                           $this->past_due,
		                           $this->current,
		                           $this->failed,
		                           $this->arr_hold,
		                           $this->unverified,
		                           $this->verified,
		                           $this->in_bankruptcy,
		                           $this->bankruptcy,
		                           $this->in_contact,
		                           $this->contact,
		                           $this->cfollowup,
		                           $this->qcready,
		                           $this->qcsent
		                          )
		              );
	}

	public function Fetch_Idvscore_Data($date_start, $date_end, $loan_type, $company_id)
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

		$score_data = array();

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';

		$status_ids = $this->Get_Customer_Status_Ids();

		$temp_table_query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			CREATE	TEMPORARY TABLE t
			SELECT	DISTINCT
				a.application_id          AS application_id,
				b.outcome                 AS outcome
			 FROM	application               AS a,
				status_history            AS sh,
				bureau_inquiry            AS b,
				loan_type                 AS lt
			 WHERE	a.application_id          =  sh.application_id
		-- All in customer branch
			  AND	sh.application_status_id IN ({$status_ids})
			  AND	a.application_id =  b.application_id
			  AND	a.loan_type_id   = lt.loan_type_id
			  AND	a.date_fund_actual BETWEEN '{$timestamp_start}'
				                    AND '{$timestamp_end}'
			  AND	b.bureau_inquiry_id = (SELECT MAX(bureau_inquiry_id)
				                        FROM  bureau_inquiry
				                        WHERE bureau_inquiry.application_id = a.application_id
				                         AND  bureau_inquiry.outcome IS NOT NULL
				                         AND  bureau_inquiry.outcome != '')
			  AND	a.company_id  IN ({$company_list})
			  AND	lt.name_short IN ({$loan_type_list})
			  AND   a.is_react = 'no'
			  ";

		//echo "<pre>temp query:\n" . str_replace("\t","        ",$temp_table_query) . "\n</pre>\n";
		//exit;

		$this->db->exec($temp_table_query);

		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT	DISTINCTROW
				upper(co.name_short)         AS company_name,
				a.application_id             AS application_id,
				lower(a.name_last)           AS name_last,
				lower(a.name_first)          AS name_first,
				a.date_fund_actual			 AS fund_date,
				a.fund_actual                AS fund_amount,
				t.outcome                    AS score,
				a.application_status_id      AS application_status_id,
				a.company_id                 AS company_id
			 FROM
				status_history               AS sh,
				company                      AS co,
				loan_type                    AS lt,
				application                  AS a
			 LEFT JOIN t ON a.application_id = t.application_id
			 WHERE
			 	a.application_id = sh.application_id
				-- All in customer branch
			  AND	a.application_status_id IN ({$status_ids})
			  AND	co.company_id  =  a.company_id
			  AND	a.loan_type_id =  lt.loan_type_id
			  AND	a.date_fund_actual BETWEEN '{$timestamp_start}'
				                    AND '{$timestamp_end}'
			  AND	a.company_id   IN ({$company_list})
			  AND	lt.name_short  IN ({$loan_type_list})
			  AND   a.is_react = 'no'
			 GROUP  BY application_id
			 ORDER  BY company_name, name_last, name_first
			";

		//echo "<pre>query:\n" . str_replace("\t", "        ", $query ) . "\n</pre>\n";
		//exit;

		$st = $this->db->query($query);

    while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			// Need data as array( Company => array( 'colname' => 'data' ) )
			//   Do all data formatting here
			$company_name = $row['company_name'];
			unset($row['company_name']);

			$row['name_last']  = ucwords($row['name_last']);
			$row['name_first'] = ucwords($row['name_first']);

			$this->Get_Module_Mode($row);

			$score_data[$company_name][] = $row;
		}

		$this->timer->stopTimer( self::$TIMER_NAME );

		return $score_data;
	}
}

?>
