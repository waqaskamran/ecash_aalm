<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision: 1.1.2.1 $
 */
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
			$this->search_query = new Leads_By_Campaign_Report_Query($this->server);
	
			$data = new stdClass();
	
			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
              'end_date_MM'   	=> $this->request->end_date_month,
              'end_date_DD'   	=> $this->request->end_date_day,
              'end_date_YYYY' 	=> $this->request->end_date_year,
			  'company_id'      => $this->request->company_id
			);
	
			$_SESSION['reports']['leads_by_campaign']['report_data'] = new stdClass();
			$_SESSION['reports']['leads_by_campaign']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['leads_by_campaign']['url_data'] = array('name' => 'Leads By Campaign Report', 'link' => '/?module=reporting&mode=leads_by_campaign');
	
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
	
			$data->search_results = $this->search_query->Fetch_Leads_By_Campaign_Data( $start_date_YYYYMMDD,
																				   $end_date_YYYYMMDD,
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

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['leads_by_campaign']['report_data'] = $data;
	}
}

class Leads_By_Campaign_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Leads by Campaign Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Leads By Campaign Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Leads_By_Campaign_Data($start_date, $end_date, $company_id)
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
			
		/**
		 * I'd much rather fetch the status_id separately rather than join against
		 * the application_status table and use the status name. [BR]
		 */
		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		$pending_status_id = $asf->toId('pending::prospect::*root');

		/**
		 * Added the 'AND ci.campaign IS NOT NULL' because it appears VendorAPI is only setting it
		 * on the very first record and none of the subsequent ones.  This caused the report query
		 * to return duplicate results since it was grouping NULL campaign names together as one. [BR]
		 * 
		 * Moved the campaign_info portion into a subquery so it can be grouped by the application_id.
		 * Previously, if there were multiple campaign_info records for an application and the campaign_name
		 * were populated, the count would increase by the number similar records. [BR]
		 * 
		 * Modified the way we pull from status_history.  We want this to be the first time the application
		 * has hit the Pending status.  Previously, if the account were modified and put back into Pending
		 * status, they would show up again in the report. [BR]
		 */
		$query = "
		-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
		SELECT  app.company_id                                  AS company_id,
		        UPPER(co.name_short)                            AS company,
		        (
		            SELECT
		              ( CASE
					    -- If the marketing site id is ecashapp.com
						-- and it's a react
						-- then we can consider it an 'ECash React' [#51659]
					    WHEN (app.is_react = 'yes' AND ci.site_id in (select site_id from site where name = 'ecashapp.com')) THEN 'eCash react'
		                -- If the marketing site id in campaign info is the same
		                -- as the enterprise site id in the application table,
		                -- then we can consider it an 'Organic' lead.
		                WHEN (app.is_react = 'no' AND ci.site_id = app.enterprise_site_id) THEN 'organic'
		                -- If it's a react and the marketing site id in campaign 
		                -- info is the same as the enterprise site id
		                -- then it's considered an organic react [#51659]
		                WHEN (app.is_react = 'yes' AND ci.site_id = app.enterprise_site_id) THEN 'organic react'
		                ELSE
		                	ci.campaign_name
		                END )
		            FROM	campaign_info AS ci
		            LEFT JOIN	site ON ci.site_id = site.site_id
		            WHERE		ci.application_id = app.application_id
		            AND			ci.campaign_name IS NOT NULL
		            GROUP BY	ci.application_id
		        ) AS rpt_campaign_name,
		        COUNT(distinct app.application_id)              AS num_bought,
		        SUM(IF(
		            EXISTS(
		                SELECT * 
		                FROM status_history sh2 
		                JOIN application_status_flat ass USING (application_status_id) 
		                WHERE ass.level0='agree' AND sh2.application_id = app.application_id
		                GROUP BY sh2.application_id
		        ), 1, 0))                                AS num_agree,
		        SUM(IF(
		            EXISTS(
		                SELECT * 
		                FROM status_history sh2 
		                JOIN application_status_flat ass USING (application_status_id)
		                WHERE ass.level0='approved' AND sh2.application_id = app.application_id
		                GROUP BY sh2.application_id
		        ), 1, 0))                               AS num_funded
		FROM (
		    SELECT sh.*
		    FROM status_history AS sh
		    LEFT OUTER JOIN (
		        SELECT sh2.application_id
		        FROM status_history AS sh2
		        WHERE sh2.date_created < '{$start_date}'
		        AND sh2.application_status_id = {$pending_status_id}
		    ) AS no_prev ON no_prev.application_id = sh.application_id
		    WHERE sh.date_created BETWEEN '{$start_date}' AND '{$end_date}'
		    AND	sh.company_id IN ({$company_list})
		    AND sh.application_status_id = {$pending_status_id}
		    AND no_prev.application_id IS NULL
		    GROUP BY sh.application_id
		) AS pending_apps
		JOIN		application app ON (app.application_id = pending_apps.application_id)
		JOIN		company co ON (co.company_id = app.company_id)
		GROUP BY	company, rpt_campaign_name
		ORDER BY	co.name_short, rpt_campaign_name
		";

		$data = array();
		$fetch_result = $this->db->query($query);
		while( $row = $fetch_result->fetch(PDO::FETCH_ASSOC))
		{
			$data[$row['company']][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>
