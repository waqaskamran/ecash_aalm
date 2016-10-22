<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once( SERVER_CODE_DIR . "base_report_query.class.php" );
require_once(SERVER_MODULE_DIR . "reporting/report_generic.class.php");

ini_set("memory_limit",-1);



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
		// company_id
		//
		try
		{
			$this->search_query = new Cc_Payments_Due_Report_Query($this->server);
	
			$data = new stdClass();
	
			// Save the report criteria
			$data->search_criteria = array(
			  'specific_date_MM'   => $this->request->specific_date_month,
			  'specific_date_DD'   => $this->request->specific_date_day,
			  'specific_date_YYYY' => $this->request->specific_date_year,
			  'company_id'         => $this->request->company_id,
			   'agent_id'        => $this->request->agent_id,
			  'loan_type'          => $this->request->loan_type
			);
	
			$_SESSION['reports']['cc_payments_due']['report_data'] = new stdClass();
			$_SESSION['reports']['cc_payments_due']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['cc_payments_due']['url_data'] = array('name' => 'Cc Payments Due', 'link' => '/?module=reporting&mode=cc_payments_due');
	
			if( ! checkdate($data->search_criteria['specific_date_MM'],
			                $data->search_criteria['specific_date_DD'],
			                $data->search_criteria['specific_date_YYYY']) )
			{
				$data->search_message = "Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}
	
			$specific_date_YYYYMMDD = 10000 * $data->search_criteria['specific_date_YYYY'] +
			                          100   * $data->search_criteria['specific_date_MM'] +
			                                  $data->search_criteria['specific_date_DD'];
			                                  
			$data->search_results = $this->search_query->Fetch_Payments_Due_Data($specific_date_YYYYMMDD,
										     $this->request->company_id,
											 $this->request->agent_id,
											 $this->request->loan_type
											 );
		}
		catch (Exception $e)
		{
			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['cc_payments_due']['report_data'] = $data;

	}
}




class Cc_Payments_Due_Report_Query extends Base_Report_Query
{
	const TIMER_NAME    = "Payments Due Report Query - New";
	const ARCHIVE_TIMER = "Payments Due Report Query - Archive";
	const CLI_TIMER     = "CLI - ";

	// # days worth of reports
	const MAX_SAVE_DAYS = "30";

	public function __construct(Server $server)
	{
		parent::__construct($server);

		$this->Add_Status_Id('failed',           array('arrangements_failed', 'arrangements', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('current',          array('current',             'arrangements', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('hold',             array('hold',                'arrangements', 'collections', 'customer', '*root'));

		$this->Add_Status_Id('cashline',         array('queued',              'cashline',     '*root'));
		$this->Add_Status_Id('in_cashline',      array('dequeued',            'cashline',     '*root'));
		$this->Add_Status_Id('pending_transfer', array('pending_transfer',    'cashline',     '*root'));
		
		
	}

	public function Fetch_Payments_Due_Data($specific_date, $company_id, $agent_ids, $loan_type)
	{
		$year  = substr($specific_date, 0, 4);
		$month = substr($specific_date, 4, 2);
		$day   = substr($specific_date, 6, 2);
		$timestamp = mktime(0, 0, 0, $month, $day, $year);

		$data = array();

		
		if (is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		if($company_id == 0)
		{
			$company_list = "'" . implode("','", $auth_company_ids) . "'";
		}
		else 
		{
			$company_list = $company_id;
		}
		
		if ($loan_type == 'all')
            $loan_type_sql = "";
        else
            $loan_type_sql = "AND lt.name_short = '{$loan_type}'\n";
		
		// get ID lists for query
		$cashline_ids            = implode( ",", array($this->cashline, $this->in_cashline, $this->pending_transfer) );
		$alt_date 		 = date('Y-m-d', strtotime($specific_date));
		
		/**
		 * If the agent list is empty, the report is worthless.  Return
		 * empty results. [AALM RC GF: 4643]
		 */
		$agent_id_sql = '';
		if(empty($agent_ids))
		{
			return array();
		}
		else
		{
			$agent_id_sql = $this->Get_Agent_SQL($agent_ids, 'agent');
		}
		
		$agent_list = join(",",$agent_ids);

		// For each Application Id
		$query = "
			SELECT
				a.application_id AS application_id,
				a.application_status_id AS application_status_id,
				'Credit Card' AS `payment_type`,
				IF(ISNULL(agent.login),'Unassigned',(concat(lower(agent.name_first), ' ', lower(agent.name_last)))) AS `agent_login`,
				CONCAT(a.name_last, ', ', a.name_first) AS 'customer_name',
				es.date_effective AS `date_effective`,
				upper(c.name_short) AS company_name,
				a.company_id AS company_id,
				ABS(es.amount_principal) as princ_amt,
				ABS(es.amount_non_principal) as non_princ_amt,
				ABS(es.amount_non_principal + es.amount_principal) as total_due
			 FROM
				event_schedule es
			JOIN
				application a     ON (a.application_id = es.application_id)
			JOIN
				company           AS c ON (c.company_id = a.company_id)
			JOIN
				loan_type lt ON (lt.loan_type_id = a.loan_type_id)
			JOIN
				event_type        AS et ON (et.event_type_id = es.event_type_id)
			LEFT JOIN agent_affiliation_event_schedule AS aaes ON (
					aaes.event_schedule_id = es.event_schedule_id
					)
			LEFT JOIN agent_affiliation AS aa ON (
					aa.agent_affiliation_id = aaes.agent_affiliation_id
					)
			LEFT JOIN agent AS agent ON (
					agent.agent_id = aa.agent_id
					)
			 WHERE 
			 		a.company_id = c.company_id
				AND es.event_type_id = et.event_type_id
				AND et.name_short = 'credit_card'
				AND es.application_id = a.application_id
				AND a.application_status_id NOT IN ({$cashline_ids})
				AND es.company_id IN ({$company_list})
				AND es.date_effective = '{$specific_date}'
				AND es.amount_principal <= 0
				AND es.amount_non_principal <= 0
				{$agent_id_sql}
				{$loan_type_sql}
			 GROUP BY application_id , agent_login , payment_type , date_effective
	 		";
		//-- AND IFNULL(agent.agent_id,0) IN ({$agent_list})
		
		$st = $this->db->query($query);

		$data = array();
		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$this->Get_Module_Mode($row);
			
			$data[$row['company_name']][] = $row;
		}

		return $data;
	}
}


//class Report extends Report_Generic
//{
//	private $search_query;
//
//	public function Generate_Report()
//	{
//		// Generate_Report() expects the following from the request form:
//		//
//		// criteria start_date YYYYMMDD
//		// criteria end_date   YYYYMMDD
//		// company_id
//		//
//		try
//		{
//			$this->search_query = new Cc_Payments_Due_Report_Query($this->server);
//
//			$data = new stdClass();
//
//			// Save the report criteria
//			$data->search_criteria = array(
//			  'specific_date_MM'   => $this->request->specific_date_month,
//			  'specific_date_DD'   => $this->request->specific_date_day,
//			  'specific_date_YYYY' => $this->request->specific_date_year,
//			  'loan_type'          => $this->request->loan_type,
//			  'company_id'         => $this->request->company_id
//			);
//
//			$_SESSION['reports']['cc_payments_due']['report_data'] = new stdClass();
//			$_SESSION['reports']['cc_payments_due']['report_data']->search_criteria = $data->search_criteria;
//			$_SESSION['reports']['cc_payments_due']['url_data'] = array('name' => 'C.C. Payments Due', 'link' => '/?module=reporting&mode=cc_payments_due');
//
//			if( ! checkdate($data->search_criteria['specific_date_MM'],
//			                $data->search_criteria['specific_date_DD'],
//			                $data->search_criteria['specific_date_YYYY']) )
//			{
//				$data->search_message = "Date invalid or not specified.";
//				ECash::getTransport()->Set_Data($data);
//				ECash::getTransport()->Add_Levels("message");
//				return;
//			}
//
//			$specific_date_YYYYMMDD = 10000 * $data->search_criteria['specific_date_YYYY'] +
//			                          100   * $data->search_criteria['specific_date_MM'] +
//			                                  $data->search_criteria['specific_date_DD'];
//
//			$data->search_results = $this->search_query->Fetch_Payments_Due_Data($specific_date_YYYYMMDD,
//										     $this->request->company_id,
//											 $this->request->agent_id
//											 );
//		}
//		catch (Exception $e)
//		{
//			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
//			ECash::getTransport()->Set_Data($data);
//			ECash::getTransport()->Add_Levels("message");
//			return;
//		}
//
//		// Sort if necessary
//		$data = $this->Sort_Data($data);


?>
