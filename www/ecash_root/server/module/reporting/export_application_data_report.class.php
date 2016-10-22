<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision: 19111 $
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
			$this->search_query = new Export_Application_Data_Query($this->server);

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

			$_SESSION['reports']['export_application_data']['report_data'] = new stdClass();
			$_SESSION['reports']['export_application_data']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['export_application_data']['url_data'] = array('name' => 'Export Application Data', 'link' => '/?module=reporting&mode=export_application_data');

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

			$data->search_results = $this->search_query->Fetch_Export_Application_Data_Data( $start_date_YYYYMMDD,
											   $end_date_YYYYMMDD,
											   $this->request->loan_type,
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
		$_SESSION['reports']['export_application_data']['report_data'] = $data;
	}
}

class Export_Application_Data_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Export Application Data Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	public function Fetch_Export_Application_Data_Data($date_start, $date_end, $loan_type, $company_id)
	{
		$this->timer->startTimer( self::$TIMER_NAME );

		if (is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		$data = array();
		$company_name = null;

		if( $company_id > 0 )
		{
			$company_list = "'{$company_id}'";
		}
		else
		{
			$company_list = "'" . implode("','", $auth_company_ids) . "'";
		}


//don't have carrier data
//don't have opt-in txt data
//don't have time at current employer
//don't have checking/saving data, only bank_account_type column
//don't have credit/debit data
//don't have consent
//don't have accept T&C
//don't have decision set identifier
//don't have approved/decline reason
//don't have IDV rules pass/fail
//don't have credit bureau rules pass/fail


	//grab all that crap
	$query = "
		-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
		SELECT
			application.name_first,
			application.name_last,
			application.name_middle,
			application.name_suffix,
			application.street,
			application.unit,
			application.city,
			application.county,
			application.state,
			application.zip_code,
			application.dob,
			application.ssn,
			application.email,
			application.phone_home,
			application.phone_work,
			application.phone_cell,
			application.bank_aba,
			application.bank_account,
			application.ip_address,
			application.application_id,
			campaign_info.promo_id
 	FROM
			application
	LEFT JOIN
			campaign_info ON (application.application_id = campaign_info.application_id
		AND
			(campaign_info.campaign_info_id =
				(
					SELECT
						MAX(campaign_info_id)
					FROM
						campaign_info cref
					WHERE
						cref.application_id = campaign_info.application_id
				)

			))
		WHERE
				application.date_created BETWEEN '{$date_start}'
				                    AND '{$date_end}'
			  AND	application.company_id            IN ({$company_list})

		";
		$st = $this->db->query($query);

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{

			// Need data as array( Company => array( 'colname' => 'data' ) )
			//   Do all data formatting here
			$company_name = $row['company_name'];
			unset($row['company_name']);
			$data[$company_name][] = $row;

		}

		return $data;
	}
}

?>
