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
			$this->search_query = new Full_Balance_Due_Report_Query($this->server);
	
			$data = new stdClass();
	
			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
			  'end_date_MM'     => $this->request->end_date_month,
			  'end_date_DD'     => $this->request->end_date_day,
			  'end_date_YYYY'   => $this->request->end_date_year,
			  'company_id'         => $this->request->company_id,
			  'loan_type'          => $this->request->loan_type
			);
	
			$_SESSION['reports']['full_balance_due']['report_data'] = new stdClass();
			$_SESSION['reports']['full_balance_due']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['full_balance_due']['url_data'] = array('name' => 'Full Balance Due', 'link' => '/?module=reporting&mode=full_balance_due');
	
			// Start date
			$start_date_YYYY = $this->request->start_date_year;
			$start_date_MM   = $this->request->start_date_month;
			$start_date_DD   = $this->request->start_date_day;
			if(!checkdate($start_date_MM, $start_date_DD, $start_date_YYYY))
			{
				//return with no data
				$data->search_message = "Start Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			// End date
			$end_date_YYYY = $this->request->end_date_year;
			$end_date_MM   = $this->request->end_date_month;
			$end_date_DD   = $this->request->end_date_day;
			if(!checkdate($end_date_MM, $end_date_DD, $end_date_YYYY))
			{
				//return with no data
				$data->search_message = "End Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}
			                                  
			$start_date_YYYYMMDD = 10000 * $start_date_YYYY + 100 * $start_date_MM + $start_date_DD;
			$end_date_YYYYMMDD   = 10000 * $end_date_YYYY   + 100 * $end_date_MM   + $end_date_DD;

			if($end_date_YYYYMMDD < $start_date_YYYYMMDD)
			{
				//return with no data
				$data->search_message = "End Date must not precede Start Date.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			$data->search_results = $this->search_query->Fetch_Full_Balance_Due_Data($start_date_YYYYMMDD,
										     $end_date_YYYYMMDD,
										     $this->request->company_id,
											 $this->request->loan_type
											 );
		}
		catch (Exception $e)
		{
			var_dump($e);
			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['full_balance_due']['report_data'] = $data;

	}
}

/**
 * I made this report 'weird' with a date range selection because
 * HMS commissioned it, and according to their credit card payments
 * due report, they seem to like it that way [JustinF]
 */
class Full_Balance_Due_Report_Query extends Base_Report_Query
{
	public function Fetch_Full_Balance_Due_Data($date_start, $date_end, $company_id, $loan_type)
	{
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';

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
				
		// For each Application Id
		$query = "
			SELECT
				upper(c.name_short) AS company_name,
				c.company_id,
				ABS(sum(eap.amount)) as princ_amt,
				ABS(sum(eai.amount)) as int_amt,
				ABS(sum(eaf.amount)) as fee_amt,				
				ABS(sum(es.amount_non_principal + es.amount_principal)) as total_due,
				es.date_event,
				es.date_effective,
				a.application_id,
				a.application_status_id,
				a.name_first,
				a.name_last,
				a.phone_home,
				a.phone_cell,
				a.phone_work,
				a.street,
				a.city,
				a.state,
				a.zip_code				
			 FROM event_schedule es
			JOIN application a ON (a.application_id = es.application_id)
			JOIN company AS c ON (c.company_id = a.company_id)
			JOIN loan_type lt ON (lt.loan_type_id = a.loan_type_id)
			JOIN event_type AS et ON (et.event_type_id = es.event_type_id)
			left join event_amount eap on (eap.event_schedule_id = es.event_schedule_id)
			left join event_amount eai on (eai.event_schedule_id = es.event_schedule_id)
			left join event_amount eaf on (eaf.event_schedule_id = es.event_schedule_id)
			WHERE 
				et.name_short = 'full_balance'
			AND es.company_id IN ({$company_list})
			AND es.date_effective BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
			and eap.event_amount_type_id = 1
			and eai.event_amount_type_id = 2
			and eaf.event_amount_type_id = 3
			{$loan_type_sql}
			GROUP BY application_id, date_effective
	 		";

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

?>
