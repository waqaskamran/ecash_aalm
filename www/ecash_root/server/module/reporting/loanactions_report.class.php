<?php
/**
 * Loan Actions Report
 * 
 * @package Reporting
 *
 * @copyright Copyright &copy; 2014 aRKaic Equipment.
 *
 * @version $Revision$
 */

require_once(SERVER_MODULE_DIR."/reporting/report_generic.class.php");
require_once( SERVER_CODE_DIR . "base_report_query.class.php" );

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
			$this->search_query = new Customer_Loan_Actions_Report_Query($this->server);
	
			$data = new stdClass();
	
			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
			  'end_date_MM'     => $this->request->end_date_month,
			  'end_date_DD'     => $this->request->end_date_day,
			  'end_date_YYYY'   => $this->request->end_date_year,
			  'company_id'      => $this->request->company_id
			);
	
			$_SESSION['reports']['loanactions']['report_data'] = new stdClass();
			$_SESSION['reports']['loanactions']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['loanactions']['url_data'] = array('name' => 'Loan Action', 'link' => '/?module=reporting&mode=loanactions');
	
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
	
			$data->search_results = $this->search_query->Fetch_Loan_Action_Data($start_date_YYYYMMDD,
											    $end_date_YYYYMMDD,
											    $this->request->company_id);
		}
		catch (Exception $e)
		{
			$data->search_message = $e->getMessage();
//			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// we need to prevent client from displaying too large of a result set, otherwise
		// the PHP memory limit could be exceeded;
		if( $data->search_results === false )
		{
			$data->search_message = $this->max_display_rows_error;
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['loanactions']['report_data'] = $data;
	}
}

class Customer_Loan_Actions_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Loan Action Report Query";
	private $system_id;

	public function __construct(Server $server)
	{
		parent::__construct($server);

		$this->system_id = $server->system_id;

	}

	public function Fetch_Loan_Action_Data($date_start, $date_end, $company_id)
	{
		$this->timer->startTimer( self::$TIMER_NAME );

		$company_list = $this->Format_Company_IDs($company_id);
		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		if ($loan_type == 'all')
			$loan_type_sql = "";
		else
			$loan_type_sql = "AND lt.name_short = '{$loan_type}'\n";

		// Start and end dates must be passed AS strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end   = $date_end   . '235959';

		$query = "
			SELECT
				application.application_id                AS application_id,
				application.is_react                      AS is_react,
				application.name_first                    AS name_first,
				application.name_last                     AS name_last,
				IF(application.phone_home IS NULL or application.phone_home = '', 'none', application.phone_home) AS phone_home,
				IF(application.phone_cell IS NULL or application.phone_cell = '', 'none', application.phone_cell) AS phone_cell,
				IF(application.phone_work IS NULL or application.phone_work = '', 'none', application.phone_work) AS phone_work,
				application.email                         AS email,
				application_status.name                   AS application_status_name,
				application.date_created                  AS date_created,
				IFNULL(application.date_fund_actual,'none') AS date_fund_actual,
				loan_action_history.date_created          AS loan_action_date,
				loan_actions.description                  AS loan_action_description,
				IFNULL(comment.comment,'none')            AS comment,
				concat(agent.name_first, agent.name_last) AS agent_name,
				application.income_monthly                AS income_monthly,
				application.income_source                 AS income_source,
				application.income_direct_deposit         AS income_direct_deposit,
				application.employer_name                 AS employer,
				application.income_frequency              AS income_frequency,
				IFNULL(application.week_1,'none')         AS week_1,
				IFNULL(application.week_2,'none')         AS week_2,
				IFNULL(application.day_of_week,'none')    AS day_of_week,
				IFNULL(application.day_of_month_1,'none') AS day_of_month_1,
				IFNULL(application.day_of_month_2,'none') AS day_of_month_2,
				campaign_info.campaign_name               AS campaign_name,
				campaign_info.promo_id                    AS promo_id
			FROM application
				JOIN application_status ON (application.application_status_id = application_status.application_status_id)
				JOIN loan_action_history ON (application.application_id = loan_action_history.application_id)
				JOIN loan_actions ON (loan_action_history.loan_action_id = loan_actions.loan_action_id)
				JOIN agent ON (loan_action_history.agent_id = agent.agent_id)
				JOIN campaign_info ON (application.application_id = campaign_info.application_id)
				LEFT JOIN comment ON (loan_action_history.agent_id = comment.agent_id 
					AND loan_action_history.application_id = comment.application_id
					AND DATE(loan_action_history.date_created) = DATE(comment.date_created))
			WHERE loan_action_history.date_created BETWEEN '$timestamp_start' AND '$timestamp_end'
				AND application.company_id IN $company_list
			";

		$st = $this->db->query($query);

		$data = array();
		while($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$data['company'][] = $row;
		}

		$this->timer->stopTimer( self::$TIMER_NAME );

		return $data;
	}

}

?>
