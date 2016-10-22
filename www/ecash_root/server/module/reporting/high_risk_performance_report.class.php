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
			$this->search_query = new High_Risk_Performance_Report_Query($this->server);

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
			  'loan_type'       => $this->request->loan_type,
			  'react_type'		=> $this->request->react_type
			);

			$_SESSION['reports']['high_risk_performance']['report_data'] = new stdClass();
			$_SESSION['reports']['high_risk_performance']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['high_risk_performance']['url_data'] = array('name' => 'High Risk Agent Actions Report', 'link' => '/?module=reporting&mode=high_risk_performance');

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

			$data->search_results = $this->search_query->Fetch_High_Risk_Performance_Data($start_date_YYYYMMDD,
											    $end_date_YYYYMMDD,
											    $this->request->loan_type,
											    $this->request->company_id,
											    $this->request->react_type);
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
		$_SESSION['reports']['high_risk_performance']['report_data'] = $data;
	}
}

class High_Risk_Performance_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Fraud Performance Report Query";
	private $system_id;

	public function __construct(Server $server)
	{
		parent::__construct($server);

		$this->Add_Status_Id('withdrawn', array('withdrawn', 'applicant',    '*root'));
		$this->Add_Status_Id('denied',    array('denied',    'applicant',    '*root'));

		$this->system_id = $server->system_id;

	}

	public function Fetch_High_Risk_Performance_Data($date_start, $date_end, $loan_type, $company_id, $react_type = 'both')
	{
		$this->timer->startTimer( self::$TIMER_NAME );

		//echo "\n<br><pre>" . print_r($_SESSION,true) . "</pre><br>\n"; //**DEBUG
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
			$company_list = "'$company_id'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';

		switch($react_type)
		{
			case 'yes':
				$query_add = "AND 	a.is_react = 'yes'";
				break;
			case 'no':
				$query_add = "AND 	a.is_react = 'no'";
				break;
			case 'both':
			default:
				$query_add = "";
				break;
		}

		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				upper(co.name_short)        AS company_name,
				concat(lower(a.name_last),
				       ', ',
				       lower(a.name_first)) 	AS agent_name,
				sum(num_in_verify_new)          	AS num_in_verify_new,
				sum(num_in_verify_react)          	AS num_in_verify_react,
				sum(num_in_underwriting_new)    	AS num_in_underwriting_new,
				sum(num_in_underwriting_react)    	AS num_in_underwriting_react,
				sum(num_funded_new = 1 AND dupe = 0)             	AS num_funded_new,
				sum(num_funded_new = 1 AND dupe = 1) AS num_funded_dupe,
				sum(num_funded_react)             	AS num_funded_react,
				sum(num_verified_new)           	AS num_approved_new,
				sum(num_verified_react)           	AS num_approved_react,
				sum(num_withdrawn_new)          	AS num_withdrawn_new,
				sum(num_withdrawn_react)          	AS num_withdrawn_react,
				sum(num_denied_new)             	AS num_denied_new,
				sum(num_denied_react)             	AS num_denied_react,
				sum(num_sendback_new)           	AS num_reverified_new,
				sum(num_sendback_react)           	AS num_reverified_react,
				sum(num_follow_up_new)         		AS num_follow_up_new,
				sum(num_follow_up_react)       		AS num_follow_up_react,
				sum(num_put_in_verify_new)      	AS num_put_in_verify_new,
				sum(num_put_in_verify_react)      	AS num_put_in_verify_react,
				sum(num_put_in_underwriting_new)	AS num_put_in_underwriting_new,
				sum(num_put_in_underwriting_react)	AS num_put_in_underwriting_react
			 FROM
				(
				SELECT
					sh.company_id,
					sh.agent_id,
					(CASE WHEN sh.application_status_id = {$this->in_verify} AND sh.agent_id != 87 AND a.is_react = 'no'
					      THEN 1
					      ELSE 0
					 END)             AS num_in_verify_new,
					(CASE WHEN sh.application_status_id = {$this->in_verify} AND sh.agent_id != 87 AND a.is_react = 'yes'
					      THEN 1
					      ELSE 0
					 END)             AS num_in_verify_react,
					(CASE WHEN sh.application_status_id = {$this->in_underwriting} AND sh.agent_id != 87 AND a.is_react = 'no'
					      THEN 1
					      ELSE 0
					 END)             AS num_in_underwriting_new,
					(CASE WHEN sh.application_status_id = {$this->in_underwriting} AND sh.agent_id != 87 AND a.is_react = 'yes'
					      THEN 1
					      ELSE 0
					 END)             AS num_in_underwriting_react,
					(CASE WHEN sh.application_status_id = {$this->funded} AND sh.agent_id != 87 AND a.is_react = 'no'
					      THEN 1
					      ELSE 0
					 END)             AS num_funded_new,
					(CASE WHEN sh.application_status_id = {$this->funded} AND sh.agent_id != 87 AND a.is_react = 'yes'
					      THEN 1
					      ELSE 0
					 END)             AS num_funded_react,
					(CASE WHEN sh.application_status_id = {$this->approved} AND sh.agent_id != 87 AND a.is_react = 'no'
					      THEN 1
					      ELSE 0
					 END)             AS num_verified_new,
					(CASE WHEN sh.application_status_id = {$this->approved} AND sh.agent_id != 87 AND a.is_react = 'yes'
					      THEN 1
					      ELSE 0
					 END)             AS num_verified_react,
					(CASE WHEN sh.application_status_id = {$this->withdrawn} AND sh.agent_id != 87 AND a.is_react = 'no'
					      THEN 1
					      ELSE 0
					 END)             AS num_withdrawn_new,
					(CASE WHEN sh.application_status_id = {$this->withdrawn} AND sh.agent_id != 87 AND a.is_react = 'yes'
					      THEN 1
					      ELSE 0
					 END)             AS num_withdrawn_react,
					(CASE WHEN sh.application_status_id = {$this->denied} AND sh.agent_id != 87 AND a.is_react = 'no'
					      THEN 1
					      ELSE 0
					 END)             AS num_denied_new,
					(CASE WHEN sh.application_status_id = {$this->denied} AND sh.agent_id != 87 AND a.is_react = 'yes'
					      THEN 1
					      ELSE 0
					 END)             AS num_denied_react,
					(CASE WHEN sh.application_status_id = {$this->reverified} AND sh.agent_id != 87 AND a.is_react = 'no'
					      THEN 1
					      ELSE 0
					 END)             AS num_sendback_new,
					(CASE WHEN sh.application_status_id = {$this->reverified} AND sh.agent_id != 87 AND a.is_react = 'yes'
					      THEN 1
					      ELSE 0
					 END)             AS num_sendback_react,

					(CASE WHEN (sh.application_status_id = {$this->ufollowup}
					        OR sh.application_status_id = {$this->vfollowup}) AND sh.agent_id != 87 AND a.is_react = 'no'
					      THEN 1
					      ELSE 0
					 END)             AS num_follow_up_new,
					(CASE WHEN (sh.application_status_id = {$this->ufollowup}
					        OR sh.application_status_id = {$this->vfollowup}) AND sh.agent_id != 87 AND a.is_react = 'yes'
					      THEN 1
					      ELSE 0
					 END)             AS num_follow_up_react,
					(CASE WHEN sh.application_status_id = {$this->reverified} AND sh.agent_id = 87 AND a.is_react = 'no'
					      THEN 1
					      ELSE 0
					 END)             AS num_put_in_verify_new,
					(CASE WHEN sh.application_status_id = {$this->reverified} AND sh.agent_id = 87 AND a.is_react = 'yes'
					      THEN 1
					      ELSE 0
					 END)             AS num_put_in_verify_react,
					(CASE WHEN sh.application_status_id = {$this->approved} AND sh.agent_id = 87 AND a.is_react = 'no'
					      THEN 1
					      ELSE 0
					 END)             AS num_put_in_underwriting_new,
					(CASE WHEN sh.application_status_id = {$this->approved} AND sh.agent_id = 87 AND a.is_react = 'yes'
					      THEN 1
					      ELSE 0
					 END)             AS num_put_in_underwriting_react,
					 EXISTS (
						 	SELECT
								application_status_id
							FROM
								status_history AS dupe
							WHERE
								dupe.application_id = sh.application_id AND
								dupe.application_status_id = sh.application_status_id AND
								dupe.date_created < sh.date_created
							LIMIT 1
						) AS dupe
				 FROM
					status_history          AS sh,
					application_status_flat AS asf,
					loan_type               AS lt,
					application				AS a
				 WHERE
					sh.date_created BETWEEN '{$timestamp_start}'
					                    AND '{$timestamp_end}'
				  AND	sh.agent_id > 0
				  AND	sh.application_status_id = asf.application_status_id
				  AND	sh.application_status_id IN ({$this->in_verify},
				                                     {$this->in_underwriting},
								     {$this->funded},
								     {$this->approved},
								     {$this->withdrawn},
								     {$this->denied},
								     {$this->reverified},
								     {$this->ufollowup},
								     {$this->vfollowup})
				  AND	sh.company_id IN ({$company_list})
				  AND	sh.company_id =  lt.company_id
				  AND	lt.name_short IN ({$loan_type_list})
				  AND	a.application_id = sh.application_id
				  {$query_add}
				)                           AS temp
			 LEFT OUTER JOIN agent   AS a  ON temp.agent_id   = a.agent_id
			 LEFT OUTER JOIN company AS co ON temp.company_id = co.company_id
			 WHERE
				(a.system_id = {$this->system_id}) OR (a.agent_id = 87)
			 GROUP BY co.name_short, lower(a.name_last), lower(a.name_first), temp.agent_id
			 ORDER BY co.name_short, lower(a.name_last), lower(a.name_first), temp.agent_id
			 LIMIT {$max_report_retrieval_rows}";

		//echo "\n<br><pre>" . print_r($query,true) . "</pre><br>\n";

		$st = $this->db->query($query);

		if( $st->rowCount() == $max_report_retrieval_rows )
			return false;

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			// Need data as array( Company => array( 'colname' => 'data' ) )
			//   Do all data formatting here
			$company_name = $row['company_name'];
			unset($row['company_name']);

			$row['agent_name'] = ucwords($row['agent_name']);
			$performance_data[$company_name][] = $row;
		}

		$this->timer->stopTimer( self::$TIMER_NAME );

		return $performance_data;
	}
}

?>
