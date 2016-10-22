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
			$this->search_query = new Agent_Internal_Recovery_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
			  'end_date_MM'     => $this->request->end_date_month,
			  'end_date_DD'     => $this->request->end_date_day,
			  'end_date_YYYY'   => $this->request->end_date_year,
			  'agent_id'		=> $this->request->agent_id,
			  'company_id'      => $this->request->company_id,
			  'loan_type'       => $this->request->loan_type
			);

			$_SESSION['reports']['agent_internal_recovery']['report_data'] = new stdClass();
			$_SESSION['reports']['agent_internal_recovery']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['agent_internal_recovery']['url_data'] = array('name' => 'Agent Internal Recovery', 'link' => '/?module=reporting&mode=reporting&mode=agent_internal_recovery');

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

			$data->search_results = $this->search_query->Fetch_Agent_Internal_Recovery_Data(
					$start_date_YYYYMMDD,
					$end_date_YYYYMMDD,
					isset($this->request->agent_id) ? $this->request->agent_id : FALSE, //for no agent list [#23563]
					$this->request->company_id,
					$this->request->loan_type);
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
        $num_results = 0;
        foreach ($data->search_results as $company => $results)
        {
            $num_results += count($results);

            if ($num_results > $this->max_display_rows)
			{
				$data->search_message = "Your report would have more than " . $this->max_display_rows . " lines to display. Please narrow the date range.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}
		}

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['agent_internal_recovery']['report_data'] = $data;
	}
}

class Agent_Internal_Recovery_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Payment Type Success Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
		// For Collections

		$this->Add_Status_Id('coll_new',			array('new',			'collections',  'customer',  '*root'));
		$this->Add_Status_Id('coll_indef_deq',		array('indef_dequeue',	'collections',  'customer',  '*root'));
		$this->Add_Status_Id('arrangements',    	array('current',  		'arrangements', 'collections',	'customer',  '*root'));
		$this->Add_Status_Id('arrangements_hold',   array('hold',  			'arrangements', 'collections',	'customer',  '*root'));
		$this->Add_Status_Id('uvbankruptcy',    	array('unverified', 	'bankruptcy',   'collections',	'customer',  '*root'));
		$this->Add_Status_Id('vbankruptcy',     	array('verified', 		'bankruptcy',   'collections',	'customer',  '*root'));
		$this->Add_Status_Id('dqcontact',      		array('dequeued',		'contact',		'collections',	'customer',  '*root'));
		$this->Add_Status_Id('followup',       		array('follow_up',		'contact',		'collections',	'customer',  '*root'));
		$this->Add_Status_Id('qcontact',      	 	array('queued',			'contact',		'collections',	'customer',  '*root'));
		$this->Add_Status_Id('qc_ready',      	  	array('ready',			'quickcheck',   'collections',    'customer',  '*root'));
		$this->Add_Status_Id('qc_sent',       	 	array('sent',			'quickcheck',   'collections',    'customer',  '*root'));
		$this->Add_Status_Id('qc_return',      	 	array('return',			'quickcheck',   'collections',    'customer',  '*root'));
		$this->Add_Status_Id('qc_arrange',        	array('arrangements',	'quickcheck',   'collections',    'customer',  '*root'));
		$this->Add_Status_Id('past_due',        	array('past_due',	'servicing',   'customer',  '*root'));
		$this->Add_Status_Id('collections_rework',        	array('collections_rework',	'collections',   'customer',  '*root'));
	}

	private function Get_Customer_Status_Ids()
	{
		return implode( ",", array($this->coll_new,
		                           $this->coll_indef_deq,
		                           $this->arrangements,
		                           $this->fund_failed,
		                           $this->arrangements_hold,
		                           $this->uvbankruptcy,
		                           $this->vbankruptcy,
		                           $this->dqcontact,
		                           $this->followup,
		                           $this->qcontact,
		                           $this->qc_ready,
		                           $this->qc_sent,
		                           $this->qc_return,
		                           $this->qc_arrange,
					   $this->past_due,
					   $this->collections_rework,
		                          )
		              );
	}

	public function Fetch_Agent_Internal_Recovery_Data($date_start, $date_end, $agent_id, $company_id, $loan_type)
	{
		$agent_id_sql = '';
		//for no agent list [#23563]
		if($agent_id !== FALSE)
		{
			// If they want an affiliated agent
			$agents_selected = FALSE;
			$agent_ids = array();
		
			if(!is_array($agent_id) || count($agent_id) == 0)
			{
				$agent_id = array(0);
			}
		
			foreach($agent_id as $id)
			{
				if($id == 0)
				{
					$unassigned_selected = TRUE;
				}
				else
				{
					$agent_ids[] = $id;
				}
			}

			$agent_id_list = NULL;
			if(!empty($agent_ids))
			{
				// Build a SQL list
				$agent_id_list = 'ag.agent_id IN (' . join(',', $agent_ids) . ')';
			}

			$agent_not_list = NULL;
			if($unassigned_selected)
			{
				$agent_not_list = 'ag.agent_id NOT IN (' . join(',', array_keys(Get_All_Agents($this->server->company_id))) . ')';
			}

			$agent_id_sql = ' AND (' . $agent_id_list . (($agent_id_list && $agent_not_list) ? ' OR ' : '') . $agent_not_list . ')';
		}
		
		$max_report_retrieval_rows = $this->max_display_rows + 1;

		$this->timer->startTimer(self::$TIMER_NAME);

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

        if ($loan_type == 'all')
            $loan_type_sql = "";
        else
            $loan_type_sql = "AND lt.name_short = '{$loan_type}'\n";


		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';
		$data = array();

		$status_ids = $this->Get_Customer_Status_Ids();
		$query = "
			SELECT	
			UPPER(co.name_short) AS company_name,
			co.company_id AS company_id,
			CONCAT(
			    ag.name_first ,
			    ' ' ,
			    ag.name_last
			    ) AS 'agent',
			SUM(IF(tr.transaction_status IN ('complete','failed'), 1,0)) AS Total_Arrangments,
			SUM(IF(tr.transaction_status IN ('complete','failed'), tr.amount,0)) AS Total_Amount,
			SUM(IF(tr.transaction_status = 'failed', 1,0)) AS Failed_Arrangments,
			SUM(IF(tr.transaction_status = 'failed', tr.amount,0)) AS Failed_Amount,
			SUM(IF(tr.transaction_status = 'complete', 1,0)) AS Completed_Arrangments,
			SUM(IF(tr.transaction_status = 'complete', tr.amount,0)) AS Completed_Amount
			FROM 
				agent_affiliation_event_schedule as afes
			LEFT JOIN
				agent_affiliation as af using (agent_affiliation_id)
			JOIN 
				transaction_register as tr using (event_schedule_id)
			JOIN 
				company as co on (af.company_id = co.company_id)
			JOIN
				agent as ag using (agent_id)
			JOIN
				application app ON (app.application_id = tr.application_id)
			JOIN
				loan_type lt ON (lt.loan_type_id = app.loan_type_id)
			WHERE 
				tr.date_effective BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
			and 
				app.application_status_id IN ({$status_ids})				
			AND 
				co.company_id IN ({$company_list})
			{$agent_id_sql}
			{$loan_type_sql}
			GROUP BY 
				company_name,agent
		";

		$st = $this->db->query($query);

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			// Seriously We can count this row if it has no Arrangemtns at all
			if($row['Total_Arrangments'] > 0)
			{
				$company_name = $row['company_name'];
			//	unset($row['company_name']);
				$row['Paid_Perc'] = ($row['Completed_Arrangments'] / $row['Total_Arrangments']) * 100;
				$row['Paid_Amount_Perc'] = ($row['Completed_Amount'] / $row['Total_Amount']) * 100;
				$row['Failed_Perc'] = ($row['Failed_Arrangments'] / $row['Total_Arrangments']) * 100;
				$row['Failed_Amount_Perc'] = ($row['Failed_Amount'] / $row['Total_Amount']) * 100;
				$data[$company_name][] = $row;
			}
		}

		$this->timer->stopTimer(self::$TIMER_NAME);
		return $data;
	}
}

?>
