<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision: 1.1.2.1 $
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
			$this->search_query = new Second_Tier_Report_Query($this->server);

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

			$_SESSION['reports']['second_tier']['report_data'] = new stdClass();
			$_SESSION['reports']['second_tier']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['second_tier']['url_data'] = array('name' => '2nd Tier', 'link' => '/?module=reporting&mode=second_tier');

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

			$start_date_YYYYMMDD = 10000 * $start_date_YYYY	+ 100 * $start_date_MM + $start_date_DD;

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

			$end_date_YYYYMMDD = 10000 * $end_date_YYYY + 100 * $end_date_MM + $end_date_DD;


			$data->search_results = $this->search_query->Fetch_2nd_Tier_Data( 
					$start_date_YYYYMMDD,
					$end_date_YYYYMMDD,
					$this->request->loan_type,
					$this->request->company_id
			);
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
		$_SESSION['reports']['second_tier']['report_data'] = $data;
	}
}

class Second_Tier_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "2nd Tier Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Charge Off Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   string $loan_type  standard || card
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_2nd_Tier_Data($start_date, $end_date, $loan_type, $company_id)
	{
		$this->timer->startTimer(self::$TIMER_NAME);

		// Search from the beginning of start date to the end of end date
		$end_date   = "{$end_date}235959";
		$start_date = "{$start_date}000000";

		// Returns with parenthesis, etc
		$company_list   = $this->Format_Company_IDs($company_id);

		// This one doesn't, go figure
		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		// pending::external_collections::*root
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				/* Report Columns */
				UPPER(co.name_short)             AS company_name,
				lt.name                          AS loan_type,
				(
					SELECT
						ish.date_created
					FROM
						status_history ish
					JOIN
						application_status_flat iasf ON (iasf.application_status_id = ish.application_status_id)
					WHERE
						ish.application_id = app.application_id
					AND
					(
							iasf.level0 = 'pending'
						AND
							iasf.level1 = 'external_collections'
						AND
							iasf.level2 = '*root'
					)
					ORDER BY ish.date_created DESC
					LIMIT 1
				)                                AS second_tier_date,
				app.application_id               AS application_id,
				asf.level0_name                  AS loan_status,
				app.name_first                   AS name_first,
				app.name_last                    AS name_last,
				app.street                       AS street,
				app.city                         AS city,
				app.state                        AS state,
				IFNULL(balance.principal,0)      AS princ_balance,
				IFNULL(balance.service_charge,0) AS svc_chg_balance,				
				IFNULL(balance.fee,0)            AS fee_balance,
				IFNULL(balance.balance,0)        AS total_balance,
				/* Get_Module_Mode BS */
				app.application_status_id        AS application_status_id,
				co.company_id                    AS company_id
			FROM
				application app
			JOIN
				loan_type lt ON (lt.loan_type_id = app.loan_type_id)
			JOIN
				company co ON (co.company_id = app.company_id)
			JOIN
				application_status_flat asf ON (asf.application_status_id = app.application_status_id)
			LEFT JOIN
				encryption_key ek ON (ek.encryption_key_id = app.encryption_key_id)
			LEFT JOIN
				(
                    SELECT
                        iea.application_id,
                        SUM(IF(ieat.name_short = 'principal', iea.amount, 0))      AS principal,
						SUM(IF(ieat.name_short = 'service_charge', iea.amount, 0)) AS service_charge,
						SUM(IF(ieat.name_short = 'fee', iea.amount, 0))            AS fee,
                        SUM(IF(iea.amount IS NULL, 0, iea.amount))                 AS balance
                    FROM
                        event_amount AS iea
                    LEFT JOIN 
						transaction_register AS itr ON (itr.transaction_register_id = iea.transaction_register_id)
                    LEFT JOIN 
						transaction_type AS itt ON (itt.transaction_type_id = itr.transaction_type_id)
                    JOIN 
						event_amount_type AS ieat ON (ieat.event_amount_type_id = iea.event_amount_type_id)
                    WHERE 
						itr.transaction_status IN ('pending', 'complete')
                    AND 
						itt.name_short NOT LIKE '%refund_3rd_party%'
                    GROUP BY iea.application_id
                ) AS balance ON (balance.application_id = app.application_id)
			WHERE
				app.company_id IN {$company_list}
			AND
				lt.name_short IN ({$loan_type_list})
			AND
				asf.level1 = 'external_collections'
			AND
				(
						asf.level0 = 'pending'
					OR
						asf.level0 = 'sent'
				)
			HAVING
				( second_tier_date BETWEEN {$start_date} AND {$end_date} AND second_tier_date IS NOT NULL ) 
		";

		$data = array();

		$fetch_result = $this->db->query($query);

		// Encrypted fields: ssn

		while( $row = $fetch_result->fetch(PDO::FETCH_ASSOC))
		{
			$co = $row['company_name'];

			$this->Get_Module_Mode($row, $row['company_id']);

			$data[$co][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>
