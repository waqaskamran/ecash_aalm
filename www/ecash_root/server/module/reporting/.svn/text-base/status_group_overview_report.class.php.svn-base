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
			$this->search_query = new Status_Group_Overview_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'company_id'      => $this->request->company_id,
			  'status'      	=> $this->request->status,
			  'balance_type'	=> $this->request->balance_type
			);

			if(isset($this->request->date))
			{
				// Dates before the end of the requested date
				$date = $this->request->date;
			}
			else
			{
				// Dates before the end of today
				$date = date('Ymd') . "235959";
			}
	
			$_SESSION['reports']['status_group_overview']['report_data'] = new stdClass();
			$_SESSION['reports']['status_group_overview']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['status_group_overview']['url_data'] = array('name' => 'Status Group Overview', 'link' => '/?module=reporting&mode=status_group_overview');
	
	
			$data->search_results = $this->search_query->Fetch_Status_Group_Overview_Data( $this->request->status, $this->request->balance_type, $date, $this->request->company_id);
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
		$_SESSION['reports']['status_group_overview']['report_data'] = $data;
	}
}

class Status_Group_Overview_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Status Group Overview Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Status Group Overview Report
	 * @param   string $status_group 
	 * @param   string $balance type
	 * @param   string $date       YYYYmmdd
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Status_Group_Overview_Data($status_group, $balance_type, $date, $company_id)
	{
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

		switch($balance_type)
		{
			case "positive":
				$type = ">";
				break;
			case "negative":
			    $type = "<";
				break;
			case "zero":
				$type = "=";
				break;
		}



		$status_id_array = array();
		switch($status_group)
		{
			case "collections":
				$status_id_array = array(126,125,127,130,131,132,133,134,135,136,137,138);
				break;
			case "customers":
				$status_id_array = array(20,121,124,122,123);
				break;
			case "underwriting":
				$status_id_array = array(11,14,10);
				break;
			case "verification":
				$status_id_array = array(8,13,9);
				break;
			case "prospects":
				// Ignore all statuses except for Agree if the customer is not allowed the unsigned apps feature [#12032]
				if(isset(ECash::getConfig()->ALLOW_UNSIGNED_APPS) && ECash::getConfig()->ALLOW_UNSIGNED_APPS === TRUE)
				{
					$status_id_array = array(5,7,15,27,6,16);
				}
				else
				{
					$status_id_array = array(5);
				}
				break;
			case "inactive":
				$status_id_array = array(113,109);
				break;
		}
		$status_ids = implode(",",$status_id_array);

		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				ap.application_id,
				ap.name_first,
				ap.name_last,
				ap.street,
				ap.city,
				ap.county,
				ap.state,
				ass.name as status,
    			SUM(IF(account_balance.balance IS NULL, 0, account_balance.balance)) AS balance,
    			SUM(IF(account_balance.principal IS NULL, 0, account_balance.principal)) AS principal,
				ap.application_status_id AS application_status_id,
        		ap.company_id,
        		c.name_short as company_name,
				lt.name_short as loan_type
			FROM
				application AS ap
                LEFT JOIN (
					SELECT
						ea.application_id,
                        SUM(IF(eat.name_short = 'principal', ea.amount, 0)) AS principal,
                        SUM(IF(ea.amount IS NULL, 0, ea.amount)) AS balance
					FROM
						event_amount AS ea
						LEFT JOIN transaction_register AS tr ON (tr.transaction_register_id = ea.transaction_register_id)
						LEFT JOIN transaction_type AS tt ON (tt.transaction_type_id = tr.transaction_type_id)
                        JOIN event_amount_type AS eat ON (eat.event_amount_type_id = ea.event_amount_type_id)
					WHERE
						ea.company_id IN ({$company_list})
						AND tr.transaction_status IN ('pending', 'complete')
						AND tt.name_short NOT LIKE '%refund_3rd_party%'
					GROUP BY application_id
				) AS account_balance ON (account_balance.application_id = ap.application_id)
                JOIN company AS c ON (c.company_id = ap.company_id)
                JOIN application_status AS ass ON (ass.application_status_id = ap.application_status_id)
				JOIN loan_type AS lt ON lt.loan_type_id = ap.loan_type_id
			WHERE
                ap.application_status_id IN ({$status_ids})
            AND ap.company_id IN ({$company_list})
            GROUP BY
                application_id
			HAVING
				balance {$type} 0
			ORDER BY
				company_id,
				status
			";

		$data = array();

		$st = $this->db->query($query);

    while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$co = strtoupper($row['company_name']);

			$this->Get_Module_Mode($row);

			$data[$co][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>
