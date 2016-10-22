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
			$this->search_query = new Internal_Recovery_Report_Query($this->server);

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

			$_SESSION['reports']['internal_recovery']['report_data'] = new stdClass();
			$_SESSION['reports']['internal_recovery']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['internal_recovery']['url_data'] = array('name' => 'Internal Recovery Report', 'link' => '/?module=reporting&mode=internal_recovery');
			$_SESSION['reports']['internal_recovery']['report_orginal'] = $_SESSION['reports']['internal_recovery']['report_data'];

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

			$data->search_results = $this->search_query->Fetch_Internal_Recovery_Data($start_date_YYYYMMDD,
												 $end_date_YYYYMMDD,
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
		$_SESSION['reports']['internal_recovery']['report_data'] = $data;
	}

	public function Download_Report()
	{
		if(@$this->request->detailed_view && @$this->request->detailed_date)
		{
			$export = "";
			$headerset = array(	"trans_date" => "Date",
								"application_id" => "Application ID",
								"tran_status" => "Transaction Status",
								"amount" => "Amount",
								"app_status_name" => "Application Status",
								"transaction_type" => "Transaction Type",
								);
			$data = $_SESSION['internal_recovery']['report_details'][$this->request->detailed_date][$this->request->detailed_view];
			for($i=0; $i<count($data); $i++)
			{
				$line = $data[$i];
				if($export == "")
				{
					$keys = array_keys($line);
					$export .= implode(",",$headerset);
					$export .= "\n";
				}
				$first = true;
				foreach($headerset as $key => $value)
				{
					if(!$first)
						$export .= ",";

					$export .= ($key == "amount") ? "$".number_format($line[$key],2) : $line[$key];
					$first = false;
				}
				$export .= "\n";
			}
			header("Accept-Ranges: bytes\n");
			header("Content-Disposition: attachment; filename=internal_recovery_details.csv\n");
			header("Content-Length: ".strlen($export)."\n");
			header("Content-Type: text/csv\n\n");
			print($export);
			die();
		}
		else
		{
			Report_Generic::Download_Report();
		}

	}
}

class Internal_Recovery_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Internal Recovery Report Query";

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
		                          )
		              );
	}

	/**
	 * Fetches data for the Internal Recovery Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Internal_Recovery_Data($date_start, $date_end, $company_id, $loan_type)
	{

		// If they want an affiliated agent
		$agents_selected = FALSE;
		$unassigned_selected = FALSE;


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



		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';

		if ($loan_type == 'all')
			$loan_type_sql = "";
		else
			$loan_type_sql = "AND lt.name_short = '{$loan_type}'\n";

		$status_ids = $this->Get_Customer_Status_Ids();
		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			select
			upper(co.name_short) AS company_name,
			co.company_id AS company_id,
			DATE_FORMAT(tr.date_effective,'%Y-%m-%d') as trans_date,
			tr.application_id,
			tr.transaction_status as tran_status,
			app.application_status_id,
			tr.amount,
			appstat.name as app_status_name,
            tt.name as transaction_type
			from 
				agent_affiliation_event_schedule as afes
			left join
				agent_affiliation as af using (agent_affiliation_id)
			join 
				transaction_register as tr using (event_schedule_id)
			join 
				transaction_type as tt on (tt.transaction_type_id = tr.transaction_type_id)
			join 
				company as co on (af.company_id = co.company_id)
			join 
				agent as ag using (agent_id)
			join 
				application as app on (tr.application_id = app.application_id)
			join
				loan_type lt on (lt.loan_type_id = app.loan_type_id)
			join 
				application_status as appstat on (appstat.application_status_id = app.application_status_id)
			where 
				tr.date_effective BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
			and 
				app.application_status_id IN ({$status_ids})
			AND 
				co.company_id IN ({$company_list})
			{$loan_type_sql}
			order by trans_date,tr.application_id

		";
		$st = $this->db->query($query);

		$data = array();
		
		while ($rowset = $st->fetch(PDO::FETCH_ASSOC))
		{
			$this->Get_Module_Mode($rowset, $rowset['company_id']);
			$keydate = $rowset['trans_date'];
			$company = $rowset['company_name'];
			if(!isset($data[$company][$keydate]))
			{
				$data[$company][$keydate] = array();
				$data[$company][$keydate]['company_name']			= $rowset['company_name'];
				$data[$company][$keydate]['company_id']			= $rowset['company_id'];
				$data[$company][$keydate]['trans_date'] 			= $keydate;
				$data[$company][$keydate]['Total_Arrangments'] 	= 0;
				$data[$company][$keydate]['Total_Amount'] 			= 0;
				$data[$company][$keydate]['Total_App_IDS'] 		= array();
				$data[$company][$keydate]['Failed_Arrangments'] 	= 0;
				$data[$company][$keydate]['Failed_Amount'] 		= 0;
				$data[$company][$keydate]['Failed_App_IDS'] 		= array();
				$data[$company][$keydate]['Completed_Arrangments']	= 0;
				$data[$company][$keydate]['Completed_Amount']		= 0;
				$data[$company][$keydate]['Completed_App_IDS']		= array();
			}


			if(in_array($rowset['tran_status'],array('complete','failed')))
			{

				$data[$company][$keydate]['Total_Arrangments']++;
				$data[$company][$keydate]['Total_Amount'] 		= $data[$company][$keydate]['Total_Amount'] + $rowset['amount'];
				$data[$company][$keydate]['Total_App_IDS'][] 	= $rowset;

				switch($rowset['tran_status'])
				{
					case 'complete':
						$data[$company][$keydate]['Completed_Arrangments']++;
						$data[$company][$keydate]['Completed_Amount'] 		= $data[$company][$keydate]['Completed_Amount'] + $rowset['amount'];
						$data[$company][$keydate]['Completed_App_IDS'][] = $rowset;

						break;
					case 'failed':
						$data[$company][$keydate]['Failed_Arrangments']++;
						$data[$company][$keydate]['Failed_Amount'] 		= $data[$company][$keydate]['Completed_Amount'] + $rowset['amount'];
						$data[$company][$keydate]['Failed_App_IDS'][] 	= $rowset;
						break;
				}
			}


		}
		unset($_SESSION['internal_recovery']['report_details']);
		if(!empty($data))
		{
			foreach($data as $company_name => $row)
			{
				foreach ($row as $rowkey => $rowitem)
				{
					if($rowitem['Total_Arrangments'] > 0)
					{
						$company_name = $rowitem['company_name'];
						$keydate = $rowitem['trans_date'];
						$rowitem['Paid_Perc'] = ($rowitem['Completed_Arrangments'] / $rowitem['Total_Arrangments']) * 100;
						$rowitem['Paid_Amount_Perc'] = ($rowitem['Completed_Amount'] / $rowitem['Total_Amount']) * 100;
						$rowitem['Failed_Perc'] = ($rowitem['Failed_Arrangments'] / $rowitem['Total_Arrangments']) * 100;
						$rowitem['Failed_Amount_Perc'] = ($rowitem['Failed_Amount'] / $rowitem['Total_Amount']) * 100;
						$data[$company_name][$keydate] = $rowitem;
						$_SESSION['internal_recovery']['report_details'][$rowkey] = $rowitem;
					}
	
				}
			}
		}
		$this->timer->stopTimer(self::$TIMER_NAME);
		return $data;
	}
}

?>
