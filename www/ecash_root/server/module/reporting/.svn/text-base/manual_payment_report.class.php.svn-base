<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once("report_generic.class.php");
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
			$this->search_query = new Manual_Payment_Report_Query($this->server);

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
					'payment_type'    => $this->request->payment_type
					);

			$_SESSION['reports']['manual_payment']['report_data'] = new stdClass();
			$_SESSION['reports']['manual_payment']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['manual_payment']['url_data'] = array('name' => 'Manual Payments', 'link' => '/?module=reporting&mode=manual_payment');

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
			$data->search_results = $this->search_query->Fetch_Manual_Payment_Data($start_date_YYYYMMDD,
					$end_date_YYYYMMDD,
					$this->request->loan_type,
					$this->request->payment_type,
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
		if(!empty($data->search_results) && count($data->search_results) > $this->max_display_rows)
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
		$_SESSION['reports']['manual_payment']['report_data'] = $data;
	}
}

class Manual_Payment_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Manual Payment Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Manual Payment Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   string $loan_type  standard || card
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Manual_Payment_Data($start_date, $end_date, $loan_type, $payment_type, $company_id)
	{
		$this->timer->startTimer(self::$TIMER_NAME);

		$start_date = "{$start_date}000000";
		$end_date   = "{$end_date}235959";

		if(isset($_SESSION) && is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array($company_id);
		}

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		// Defaults
		$order = "";
		$exception = "0=1";
		$paytype=array();
		foreach ($auth_company_ids as $company) 
		{
			switch($payment_type)
			{
				case 'credit' :
					$paytype[] = $this->Get_Event_Type_Id('credit_card',$company);
					break;
				case 'moneygram' :
					$paytype[] = $this->Get_Event_Type_Id('moneygram',$company);
					break;
				case 'moneyorder' :
					$paytype[] = $this->Get_Event_Type_Id('money_order',$company);
					break;
				case 'westernunion' :
					$paytype[] = $this->Get_Event_Type_Id('western_union',$company);
					break;
				case 'tier2' :
					$paytype[] = $this->Get_Event_Type_Id('ext_recovery',$company);
					break;
				case 'debt_consolidation' :
					$paytype[] = $this->Get_Event_Type_Id('payment_debt',$company);
					$exception = "es.event_type_id = '" . $this->Get_Event_Type_Id('payment_debt',$company) . "'";
					$order = "ORDER BY application_id";
					break;
				case 'personal_check' :
					$paytype[] = $this->Get_Event_Type_Id('personal_check', $company);
					break;

				case 'all' :
				default:
					$paytype[] = $this->Get_Event_Type_Id('credit_card',$company);
					$paytype[] = $this->Get_Event_Type_Id('moneygram',$company);
					$paytype[] = $this->Get_Event_Type_Id('money_order',$company);
					$paytype[] = $this->Get_Event_Type_Id('western_union',$company);
					$paytype[] = $this->Get_Event_Type_Id('ext_recovery',$company);
					$paytype[] = $this->Get_Event_Type_Id('payment_debt',$company);
					$paytype[] = $this->Get_Event_Type_Id('personal_check', $company); //[#52601] added missing personal_check payment on 'all'

					$exception = "es.event_type_id = '" . $this->Get_Event_Type_Id('payment_debt',$company) . "'";
			}
		}
		$paytype = "'" . implode("','", $paytype) . "'";
		$fetch_query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT  es.application_id,
					concat(app.name_last, ', ', app.name_first) as customer_name,
					et.name as payment_type,
					es.date_effective as payment_date,
					-(es.amount_principal + es.amount_non_principal) AS 'amount',
					ag.agent_id,
					IFNULL(concat(ag.name_first, ' ', ag.name_last), 'None') as agent_name,
					IFNULL((SELECT CONCAT(name_first, ' ', name_last)
					 FROM agent, agent_affiliation
					 WHERE agent_affiliation.application_id = es.application_id
					 AND   agent_affiliation.agent_id       = agent.agent_id
					 AND   agent_affiliation.affiliation_area='collections'
					 AND	  agent_affiliation.affiliation_type='owner'
					 ORDER BY agent_affiliation.date_modified DESC LIMIT 1), 'None') AS controlling_agent,
					es.company_id,
					UPPER(c.name_short) as company_name,
					app.application_status_id
						FROM 	event_schedule es
						LEFT JOIN agent_affiliation_event_schedule aaes ON (aaes.event_schedule_id = es.event_schedule_id)
						JOIN event_type et ON (et.event_type_id = es.event_type_id)
						LEFT JOIN agent_affiliation aaf ON (aaf.agent_affiliation_id = aaes.agent_affiliation_id)
						LEFT JOIN agent ag ON (ag.agent_id = aaf.agent_id)
						JOIN company c ON (c.company_id = es.company_id)
						JOIN application app ON (app.application_id = es.application_id)

						WHERE   (es.context='manual' OR ((es.context='arrangement' OR es.context='partial') AND {$exception}))
						AND		es.event_type_id IN ({$paytype})
						AND	    es.company_id IN ({$company_list})
						AND     es.date_created BETWEEN {$start_date}
		AND     {$end_date}
		AND app.loan_type_id in (SELECT loan_type_id
				FROM loan_type
				WHERE name_short IN ({$loan_type_list}))
			$order ";

		//$this->log->Write($fetch_query);
		$data = array();

		$st = $this->db->query($fetch_query);

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{

			$row['customer_name'] = ucwords($row['customer_name']);

			$co = $row['company_name'];
			//unset($row['company_name']);

			$this->Get_Module_Mode($row);

			$data[$co][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);
		return $data;
	}
}

?>
