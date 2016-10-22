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
			$this->search_query = new Post_2nd_Tier_Returns_Report_Query($this->server);
	
			$data = new stdClass();
	
			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
              'end_date_MM'   	=> $this->request->end_date_month,
              'end_date_DD'   	=> $this->request->end_date_day,
              'end_date_YYYY' 	=> $this->request->end_date_year,
			  'company_id'      => $this->request->company_id,
			  'loan_type'       => $this->request->loan_type
			);
	
			$_SESSION['reports']['post_2nd_tier_returns']['report_data'] = new stdClass();
			$_SESSION['reports']['post_2nd_tier_returns']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['post_2nd_tier_returns']['url_data'] = array('name' => 'Post 2nd Tier Returns', 'link' => '/?module=reporting&mode=post_2nd_tier_returns');
	
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

	
			$data->search_results = $this->search_query->Fetch_Post_2nd_Tier_Returns_Data( $start_date_YYYYMMDD,
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
		$_SESSION['reports']['post_2nd_tier_returns']['report_data'] = $data;
	}
}

class Post_2nd_Tier_Returns_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Post 2nd Tier Returns Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Loan Activity Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   string $loan_type  standard || card
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Post_2nd_Tier_Returns_Data($start_date, $end_date, $loan_type, $company_id)
	{
		$this->timer->startTimer(self::$TIMER_NAME);

		// Search from the beginning of start date to the end of end date
		$end_date   = "{$end_date}235959";
		$start_date = "{$start_date}000000";

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

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		// Return all returns for accounts in collections contact statuses
		$query = "
                SELECT
                    UPPER(c.name_short)                        AS company_name,
                    c.company_id                               AS company_id,
                    app.application_id                         AS application_id,
					app.application_status_id                  AS application_status_id,
                    lt.name                                    AS loan_type,
                    app.ssn                                    AS ssn,
					app.encryption_key_id                      AS encryption_key_id,
                    tr.amount                                  AS amount,
					(
						SELECT
							ish.date_created
						FROM
							status_history ish
						JOIN
							application_status iass ON (ish.application_status_id = iass.application_status_id)
						WHERE
							iass.name = 'Collections Contact'
						AND
							ish.application_id = app.application_id
						ORDER BY 
							ish.date_created ASC
						LIMIT 1
					)                                          AS collections_contact_date,
					(
						SELECT
							ith.date_created
						FROM
							transaction_history ith
						WHERE
							ith.status_after = 'failed'
						AND
							ith.date_created BETWEEN {$start_date} AND {$end_date}
						AND
							ith.transaction_register_id = tr.transaction_register_id
						ORDER BY
							ith.transaction_history_id DESC
						LIMIT 1
					)                                          AS return_date,
					(
                        SELECT
                            SUM(
                                IF
                                (
                                    itr.transaction_register_id IS NULL,
                                    ies.amount_principal + ies.amount_non_principal,
                                    itr.amount
                                )
                            )
                        FROM
                            event_schedule ies
                        LEFT JOIN 
							transaction_register itr USING (event_schedule_id)
                        WHERE
                            ies.application_id = app.application_id
                        AND
                            itr.transaction_status IN ('complete')
					)                                          AS balance
                FROM
                    transaction_register tr
                JOIN
                    application app ON (app.application_id = tr.application_id)
                JOIN
                    company c ON (c.company_id = app.company_id)
                JOIN
                    loan_type lt ON (lt.loan_type_id = app.loan_type_id)
                WHERE
					tr.transaction_status = 'failed'
				AND
					tr.date_modified > {$start_date}
				AND
					(
						SELECT
							COUNT(*)
						FROM
							transaction_history
						WHERE
							status_after = 'failed'
						AND
							date_created BETWEEN {$start_date} AND {$end_date}
						ORDER BY
							transaction_history_id DESC
					) > 0
 				AND
                    lt.name_short IN ({$loan_type_list})
				HAVING
					( return_date IS NOT NULL AND collections_contact_date IS NOT NULL AND return_date > collections_contact_date )
		";


		$data = array();

		$fetch_result = $this->db->query($query);

		$crypt  = new ECash_Models_Encryptor($this->db);

		while( $row = $fetch_result->fetch(PDO::FETCH_ASSOC))
		{
			$co = $row['company_name'];

			$row['ssn'] = $crypt->decrypt($row['ssn'],$row['encryption_key_id']);

			$this->Get_Module_Mode($row, $row['company_id']);

			$data[$co][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>
