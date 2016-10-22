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
	private $fraud_query;

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
			$this->search_query = new Fraud_Balance_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'company_id'      => $this->request->company_id,
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
			  'end_date_MM'     => $this->request->end_date_month,
			  'end_date_DD'     => $this->request->end_date_day,
			  'end_date_YYYY'   => $this->request->end_date_year,
			  'loan_type'       => $this->request->loan_type,
			);

			$_SESSION['reports']['fraud_balance']['report_data'] = new stdClass();
			$_SESSION['reports']['fraud_balance']['report_data']->search_criteria = $data->search_criteria;
	
			$results = $this->Get_Dates(&$data);

			if(!$results)
				return;
			else
				list($start_date_YYYYMMDD, $end_date_YYYYMMDD) = $results;

			$data->search_results = $this->search_query->Fetch_Fraud_Balance_Data($start_date_YYYYMMDD, $end_date_YYYYMMDD, $this->request->company_id, $this->request->loan_type);
			//echo "<!-- ", print_r($data->search_results, TRUE), " -->";
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
		$_SESSION['reports']['fraud_balance']['report_data'] = $data;
	}

}

// TODO: I don't like the fact that this class is here
class Fraud_Balance_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Fraud Balance Report Query";

	private $system_id;

	public function __construct(Server $server)
	{
		parent::__construct($server);

		$this->system_id = $server->system_id;
	}

	public function Fetch_Fraud_Balance_Data($date_start, $date_end, $company_id, $loan_type)
	{
		$this->timer->startTimer( self::$TIMER_NAME );
		$max_report_retrieval_rows = $this->max_display_rows + 1;

 		$company_list = $this->Format_Company_IDs($company_id);

		$balance_data = array();

		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';

		if ($loan_type == 'all')
			$loan_type_sql = "";
		else
			$loan_type_sql = "AND lt.name_short = '{$loan_type}'\n";

		$query = "
				SELECT
					'To Fraud/High Risk' as destination,
					upper(c.name_short) company_name,
					SUM(ps.application_status_id in ({$this->approved}, {$this->in_underwriting}, {$this->ufollowup})) AS underwriting,
					SUM(ps.application_status_id in ({$this->reverified}, {$this->in_verify}, {$this->ufollowup})) AS verification,
					SUM(ps.application_status_id = {$this->withdrawn}) AS withdrawn,
					0 as denied,
					(
						SUM(ps.application_status_id in ({$this->approved}, {$this->in_underwriting}, {$this->ufollowup})) +
						SUM(ps.application_status_id in ({$this->reverified}, {$this->in_verify}, {$this->ufollowup})) +
						SUM(ps.application_status_id = {$this->withdrawn})
					) as total
				FROM
					status_history sh
				join application app ON (app.application_id = sh.application_id)
				join loan_type lt ON (lt.loan_type_id = app.loan_type_id)
					inner join company c on (c.company_id = sh.company_id)
					inner join status_history ps on (ps.status_history_id =
						(
						SELECT
							status_history_id
						FROM
							status_history AS prev_status
						WHERE
							prev_status.application_id = sh.application_id
						AND prev_status.date_created < sh.date_created
						ORDER BY prev_status.date_created DESC
						limit 1
					))
				WHERE
					sh.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
				AND c.company_id IN {$company_list}
				AND ps.application_status_id IN ({$this->reverified}, {$this->in_verify}, {$this->vfollowup},
											  {$this->approved}, {$this->in_underwriting}, {$this->ufollowup},{$this->withdrawn})
				AND sh.application_status_id in ({$this->in_fraud}, {$this->fraud}, {$this->fraud_followup}, {$this->fraud_confirmed},
					  {$this->in_high_risk}, {$this->high_risk}, {$this->high_risk_followup})
				{$loan_type_sql}
				group by company_name

				UNION

				SELECT
					'From Fraud/High Risk' as destination,
					upper(c.name_short) company_name,
					SUM(sh.application_status_id = {$this->approved}) AS underwriting,
					SUM(sh.application_status_id = {$this->reverified}) AS verification,
					SUM(sh.application_status_id = {$this->withdrawn}) AS withdrawn,
					SUM(sh.application_status_id = {$this->denied}) AS denied,
					(
						SUM(sh.application_status_id = {$this->approved}) +
						SUM(sh.application_status_id = {$this->reverified}) +
						SUM(sh.application_status_id = {$this->withdrawn}) +
						SUM(sh.application_status_id = {$this->denied})
					) as total
				FROM
					status_history sh
					inner join company c on (c.company_id = sh.company_id)
				join application app ON (app.application_id = sh.application_id)
				join loan_type lt ON (lt.loan_type_id = app.loan_type_id)
				WHERE
					sh.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
				AND c.company_id IN {$company_list}
				AND sh.application_status_id IN ({$this->reverified},
											  {$this->approved},
											  {$this->denied},
											  {$this->withdrawn})
				{$loan_type_sql}
				AND
				(
					SELECT
						application_status_id
					FROM
						status_history AS prev_status
					WHERE
						prev_status.application_id = sh.application_id
					AND prev_status.date_created < sh.date_created
					ORDER BY prev_status.date_created DESC
					limit 1
				) in ({$this->in_fraud}, {$this->fraud}, {$this->fraud_followup}, {$this->fraud_confirmed},
					  {$this->in_high_risk}, {$this->high_risk}, {$this->high_risk_followup})
				group by company_name

		        ORDER BY company_name asc, destination desc
		";
	
		$st = $this->db->query($query);

		if( $st->rowCount() == $max_report_retrieval_rows )
			return false;

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			// Need data as array( Company => array( 'colname' => 'data' ) )
			//   Do all data formatting here
			$company_name = $row['company_name'];
			$balance_data[$company_name][] = $row;
		}

		$this->timer->stopTimer( self::$TIMER_NAME );

		return $balance_data;
	}
}

?>
