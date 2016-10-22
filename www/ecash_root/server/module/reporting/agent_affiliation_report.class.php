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
                        $this->search_query = new Agent_Affiliation_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   		=> $this->request->start_date_month,
			  'start_date_DD'   		=> $this->request->start_date_day,
			  'start_date_YYYY' 		=> $this->request->start_date_year,
			  'end_date_MM'     		=> $this->request->end_date_month,
			  'end_date_DD'     		=> $this->request->end_date_day,
			  'end_date_YYYY'   		=> $this->request->end_date_year,
			  'company_id'      		=> $this->request->company_id,
			  'collection_level'       	=> $this->request->collection_level,
			  'payment_arrange_type'	=> $this->request->payment_arrange_type,
			  'batch_type'       		=> $this->request->batch_type,
			  'ach_batch_company'       	=> $this->request->ach_batch_company,
			);

			$_SESSION['reports']['agent_affiliation']['report_data'] = new stdClass();
			$_SESSION['reports']['agent_affiliation']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['agent_affiliation']['url_data'] = array('name' => 'Agent Affiliation', 'link' => '/?module=reporting&mode=agent_affiliation');

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
			
			$data->search_results = $this->search_query->Fetch_Agent_Affiliation_Data($start_date_YYYYMMDD,
												$end_date_YYYYMMDD,
												$this->request->collection_level,
												$this->request->payment_arrange_type,
												$this->request->company_id,
												$this->request->batch_type,
												$this->request->ach_batch_company);
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
		$_SESSION['reports']['agent_affiliation']['report_data'] = $data;
	}
}


class Agent_Affiliation_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Agent Affiliation Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
               
                $this->Add_Status_Id('collections_followup', array('follow_up','contact','collections','customer','*root'));
                $this->Add_Status_Id('collections_rework', array('collections_rework','collections','customer','*root'));
                $this->Add_Status_Id('arrangement', array('current','arrangements','collections','customer','*root'));
                $this->Add_Status_Id('external_pending', array('pending','external_collections','*root'));
                $this->Add_Status_Id('cccs', array('cccs','collections','customer','*root'));
	}

	/**
	 * Fetches data for the Payment Arrangements Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   string $collection_level  standard || card
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @param   mixed  $mode       'cli' or null (null==default==web)
	 * @return  array
	 */
	public function Fetch_Agent_Affiliation_Data($start_date, $end_date, $collection_level, $payment_arrange_type, $company_id, $batch_type, $ach_batch_company)
	{
		$this->timer->startTimer(self::$TIMER_NAME);

		$start_date = $start_date. "000000";
		$end_date   = $end_date. "235959";

		if(isset($_SESSION) && is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

                $status_array = array();
		if ($collection_level == 'collection')
                {
                        //$status_array[] = $this->status_ids['past_due'];
                        //$status_array[] = $this->status_ids['indef_dequeue'];
                        //$status_array[] = $this->status_ids['collections_new'];
                        $status_array[] = $this->status_ids['dequeued'];
                        $status_array[] = $this->status_ids['queued'];
                        $status_array[] = $this->status_ids['collections_followup'];
                        $status_array[] = $this->status_ids['collections_rework'];
                        $status_array[] = $this->status_ids['arrangement'];
                        $status_array[] = $this->status_ids['external_pending'];
                }
                else if ($collection_level == 'cccs')
                {
                        $status_array[] = $this->status_ids['cccs'];
                }
                else //'all_coll'
                {
                        //$status_array[] = $this->status_ids['past_due'];
                        //$status_array[] = $this->status_ids['indef_dequeue'];
                        //$status_array[] = $this->status_ids['collections_new'];
                        $status_array[] = $this->status_ids['dequeued'];
                        $status_array[] = $this->status_ids['queued'];
                        $status_array[] = $this->status_ids['collections_followup'];
                        $status_array[] = $this->status_ids['collections_rework'];
                        $status_array[] = $this->status_ids['arrangement'];
                        $status_array[] = $this->status_ids['external_pending'];
                        $status_array[] = $this->status_ids['cccs'];
                }
                $status_id_string = implode(",", $status_array);

		if($payment_arrange_type == 'date_completed')
		{
			$payment_arrange_clause = "
				tr.date_effective BETWEEN {$start_date} AND {$end_date}
				AND tr.transaction_status = 'complete'
			";
		}
		else 
		{
			$payment_arrange_clause = "es.{$payment_arrange_type} BETWEEN {$start_date} AND {$end_date}";
		}
		
		if (empty($batch_type))
		{
			$batch_type_sql = "";
		}
		else
		{
			if ($batch_type == "ach")
			{
				$batch_type_sql = " AND tt.clearing_type = 'ach'\n";
			}
			elseif ($batch_type == "card")
			{
				$batch_type_sql = " AND tt.clearing_type = 'card'\n";
			}
			else
			{
				$batch_type_sql = "";
			}
		}

		if (empty($ach_batch_company))
			$ach_batch_company_sql = "";
		else
			$ach_batch_company_sql = " AND ab.ach_provider_id = '{$ach_batch_company}'\n";

		$fetch_query = "
				-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
                                -- arrangement, partial payments
				(SELECT
					app.company_id,
					UPPER(c.name_short) AS 'company_name',
                                        IF(ag.agent_id IS NUll OR ag.agent_id=1,CONCAT(ag2.name_last,' ,', ag2.name_first),CONCAT(ag.name_last,' ,', ag.name_first)) AS 'agent_name',
					CONCAT(ag3.name_last,' ,', ag3.name_first) AS created_by,
					app.application_id,
					app.application_status_id,
                                        aps1.name AS 'application_status_payment',
					aps.name AS 'application_status',
					CONCAT(app.name_last, ', ', app.name_first) AS 'customer_name',
					es.event_schedule_id,
					es.date_created AS 'created_date',
                                        es.context,
					et.name AS 'payment_type',
					-(es.amount_principal + es.amount_non_principal) AS 'amount',
					es.amount_principal AS 'principal',
					es.amount_non_principal AS 'amount_non_principal',
					es.date_effective,
					es.event_status,
					tr.transaction_status,
					tt.clearing_type,
					apr.name AS ach_provider
				FROM
					application AS app
				JOIN
					company c ON (c.company_id = app.company_id)
				JOIN
					application_status AS aps ON (aps.application_status_id = app.application_status_id)
				JOIN
					event_schedule AS es ON (es.company_id = app.company_id AND es.application_id = app.application_id)
				JOIN
					event_type AS et on (et.company_id = es.company_id AND et.event_type_id = es.event_type_id)
				JOIN
					event_transaction AS evtr ON (evtr.company_id = es.company_id AND evtr.event_type_id = es.event_type_id)
				JOIN
					transaction_type AS tt ON (tt.company_id = evtr.company_id AND tt.transaction_type_id = evtr.transaction_type_id)
                                JOIN
                                        status_history AS sh ON (sh.company_id = app.company_id
                                                                    AND sh.application_id = app.application_id
                                                                    AND sh.application_status_id IN ({$status_id_string})
                                                                    AND sh.date_created < es.date_created
                                        )
                                JOIN
					application_status AS aps1 ON (aps1.application_status_id = sh.application_status_id)
                                LEFT JOIN
                                        agent_affiliation_event_schedule AS aaes ON (aaes.event_schedule_id = es.event_schedule_id)
                                LEFT JOIN
                                        agent_affiliation AS aa ON (aa.agent_affiliation_id = aaes.agent_affiliation_id)
                                LEFT JOIN
                                        agent AS ag ON (ag.agent_id = aa.agent_id)
                                LEFT JOIN
                                        status_history AS sh1 ON (sh1.company_id = app.company_id
                                                                    AND sh1.application_id = app.application_id
                                                                    AND sh1.date_created < es.date_created
                                                                    AND sh1.date_created > sh.date_created
                                        )
                                LEFT JOIN
                                        status_history AS sh2 ON (sh2.company_id = app.company_id
                                                                    AND sh2.application_id = app.application_id
                                                                    AND sh2.application_status_id IN ({$this->status_ids['arrangement']})
                                                                    AND sh2.date_created BETWEEN DATE_SUB(es.date_created, INTERVAL 10 MINUTE)
                                                                                            AND DATE_ADD(es.date_created, INTERVAL 10 MINUTE)                                       )
                                LEFT JOIN
                                        agent AS ag2 ON (ag2.agent_id = sh2.agent_id)
				LEFT JOIN 
					transaction_register AS tr ON (tr.event_schedule_id = es.event_schedule_id)
				LEFT JOIN
					arrangement_history AS ah ON (ah.application_id = app.application_id
									AND ah.event_schedule_id = es.event_schedule_id)
				LEFT JOIN
					agent AS ag3 ON (ag3.agent_id = ah.agent_id)
				LEFT JOIN
					ach ON (ach.ach_id = tr.ach_id)
				LEFT JOIN
					ach_batch AS ab ON (ab.ach_batch_id = ach.ach_batch_id)
				LEFT JOIN
					ach_provider AS apr ON (apr.ach_provider_id = ab.ach_provider_id)
				WHERE
					app.company_id IN ({$company_list})
                                        AND es.context IN ('arrangement','partial','arrange_next')
                                        -- AND et.name_short IN ('payment_arranged','payment_manual','money_order','moneygram','western_union','credit_card','personal_check')
					AND {$payment_arrange_clause}
					{$batch_type_sql}
					{$ach_batch_company_sql}
                                        AND sh1.status_history_id IS NULL
				-- GROUP BY es.event_schedule_id
                                )

                                UNION
                                -- manual payments
                                (SELECT
					app.company_id,
					UPPER(c.name_short) AS 'company_name',
                                        CONCAT(ag.name_last,' ,', ag.name_first) AS 'agent_name',
					CONCAT(ag.name_last,' ,', ag.name_first) AS created_by,
					app.application_id,
					app.application_status_id,
                                        aps1.name AS 'application_status_payment',
					aps.name AS 'application_status',
					CONCAT(app.name_last, ', ', app.name_first) AS 'customer_name',
					es.event_schedule_id,
					es.date_created AS 'created_date',
                                        es.context,
					et.name AS 'payment_type',
					-(es.amount_principal + es.amount_non_principal) AS 'amount',
					es.amount_principal AS 'principal',
					es.amount_non_principal AS 'amount_non_principal',
					es.date_effective,
					es.event_status,
					tr.transaction_status,
					tt.clearing_type,
					apr.name AS ach_provider
				FROM
					application AS app
				JOIN
					company c ON (c.company_id = app.company_id)
				JOIN
					application_status AS aps ON (aps.application_status_id = app.application_status_id)
				JOIN
					event_schedule AS es ON (es.company_id = app.company_id AND es.application_id = app.application_id)
				JOIN
					event_type AS et on (et.company_id = es.company_id AND et.event_type_id = es.event_type_id)
                                JOIN
                                        status_history AS sh ON (sh.company_id = app.company_id
                                                                    AND sh.application_id = app.application_id
                                                                    AND sh.application_status_id IN ({$status_id_string})
                                                                    AND sh.date_created < es.date_created
                                        )
                                JOIN
					application_status AS aps1 ON (aps1.application_status_id = sh.application_status_id)
                                JOIN
					transaction_register AS tr ON (tr.event_schedule_id = es.event_schedule_id)
				JOIN
					transaction_type AS tt ON (tt.company_id = tr.company_id AND tt.transaction_type_id = tr.transaction_type_id)
                                JOIN
                                        agent AS ag ON (ag.agent_id = tr.modifying_agent_id)
                                LEFT JOIN
                                        status_history AS sh1 ON (sh1.company_id = app.company_id
                                                                    AND sh1.application_id = app.application_id
                                                                    AND sh1.date_created < es.date_created
                                                                    AND sh1.date_created > sh.date_created
                                        )
				LEFT JOIN
					ach ON (ach.ach_id = tr.ach_id)
				LEFT JOIN
					ach_batch AS ab ON (ab.ach_batch_id = ach.ach_batch_id)
				LEFT JOIN
					ach_provider AS apr ON (apr.ach_provider_id = ab.ach_provider_id)
				WHERE
					app.company_id IN ({$company_list})
                                        AND es.context IN ('manual')
                                        -- AND et.name_short IN ('payment_arranged','payment_manual','money_order','moneygram','western_union','credit_card','personal_check')
					AND et.name_short <> 'adjustment_internal'
					AND {$payment_arrange_clause}
					{$batch_type_sql}
					{$ach_batch_company_sql}
                                        AND sh1.status_history_id IS NULL
				-- GROUP BY es.event_schedule_id
                                )
		";
		
		$data = array();
		$st = $this->db->query($fetch_query);		
		
		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$co = $row['company_name'];
			unset($row['company_name']);
						
			$this->Get_Module_Mode($row);

			$data[$co][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>
