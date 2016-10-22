<?php
/**
 * Report
 * Controlling Agent Report
 *
 * @package Reporting
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 * @created Dec 7, 2006
 *
 * @version $Revision$
 */

require_once( SERVER_MODULE_DIR . "/reporting/report_generic.class.php" );
require_once( SERVER_CODE_DIR . "/base_report_query.class.php" );

class Report extends Report_Generic {

	public function Generate_Report()
	{
		try
		{
			$search_query = new Controlling_Agent_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
					'company_id'      => $this->request->company_id,
					'agent_id'        => $this->request->agent_id,
					'loan_type'       => $this->request->loan_type
					);

			// Copy the search criteria into the session, but don't use the $data
			// object because it will be used to store aggregate data
			$_SESSION['reports']['controlling_agent']['report_data'] = new stdClass();
			$_SESSION['reports']['controlling_agent']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['controlling_agent']['url_data'] = array('name' => 'Controlling Collection Agent', 'link' => '/?module=reporting&mode=controlling_agent');

			$data->search_results = $search_query->Fetch_Report_Results( $data->search_criteria['company_id'] , $data->search_criteria['agent_id'], $this->request->loan_type );
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
		$_SESSION['reports']['controlling_agent']['report_data'] = $data;

	}
}

class Controlling_Agent_Report_Query extends Base_Report_Query {

	private static $TIMER_NAME = "Collections Agent Report Query";

	public function Fetch_Report_Results( $company_id, $agent_id, $loan_type )
	{

		// How long does it take to do this?
		$this->timer->startTimer(self::$TIMER_NAME);

		// This is ESSENTIAL SECURITY.  Remember to include it in your query.
		if (is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}
		
		if( $company_id > 0 && in_array($company_id,$auth_company_ids))
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";
		

		// Use this in the LIMIT statement of your query
		$max_report_retrieval_rows = $this->max_display_rows + 1;

		// Now initialize the data array we will be returning
		$data = array();

		// If they want an affiliated agent
		$agents_selected = FALSE;
		$unassigned_selected = FALSE;
		if(!is_array($agent_id) || 0 == count($agent_id))
		{
			$agent_id = array(0);
		}
		foreach($agent_id as $id)
		{
			if(0 == $id)
			{
				$unassigned_selected = TRUE;
			}
			else
			{
				$agents_selected = TRUE;
			}
		}

		if ($loan_type == 'all')
			$loan_type_sql = "";
		else
			$loan_type_sql = "AND lt.name_short = '{$loan_type}'\n";

		// Build a SQL list
		$agent_id_list = join(",",$agent_id);

		// Build query
		$query_parts = array();
		if($agents_selected)
		{
			// Now build a query
			$query_parts[] = "
				SELECT
					UCASE(`c`.`name_short`) AS `company_name` ,
					`c`.`company_id` AS `company_id` ,
					`a`.`application_status_id` AS `application_status_id` ,
					`a`.`application_id` AS `app_id` ,
					`a`.`name_first` AS `first` ,
					`a`.`name_last` AS `last` ,
					`a`.`date_next_contact` AS `date` ,
					'No' AS `arranged` ,
					CONCAT(
						`ag`.`name_first` ,
						' ' ,
						`ag`.`name_last`
						) AS 'agent',
					lt.name_short as loan_type
				FROM
					`company`                 AS `c`   
				JOIN `application`             AS `a`   ON (c.company_id = a.company_id)
				JOIN `loan_type` as lt ON (a.loan_type_id = lt.loan_type_id)
				JOIN (SELECT * FROM `agent_affiliation` WHERE affiliation_type = 'owner'
															AND affiliation_area = 'collections'
															AND ( `date_expiration` > NOW()
																	OR `date_expiration` is NULL)
    				) AS `aa`  ON (aa.application_id = a.application_id)
				JOIN (SELECT * FROM `application_status_flat` WHERE
						`level2` = 'collections'
					AND	`level3` = 'customer'
					AND	`level4` = '*root'
					) AS `asf` ON (asf.application_status_id = a.application_status_id)
				JOIN `agent`                   AS `ag` ON (ag.agent_id = aa.agent_id)
				WHERE (`asf`.`level2` = 'collections' OR  `asf`.`level1` = 'contact' OR  ( `asf`.`level0` = 'follow_up' AND `a`.`date_next_contact` < NOW()))
					AND `c`.`company_id` IN ({$company_list})
					AND `aa`.`agent_id` IN ({$agent_id_list})
				{$loan_type_sql}
				";
			$query_parts[] = "
				SELECT
					UCASE(`c`.`name_short`) AS `company_name` ,
					`c`.`company_id` AS `company_id` ,
					`a`.`application_status_id` AS `application_status_id` ,
					`a`.`application_id` AS `app_id` ,
					`a`.`name_first` AS `first` ,
					`a`.`name_last` AS `last` ,
					`es`.`date_event` AS `date` ,
					'Yes' AS `arranged` ,
					CONCAT(
						`ag`.`name_first` ,
						' ' ,
						`ag`.`name_last`
						) AS 'agent',
					lt.name_short as loan_type
				FROM
					`company`                 AS `c`   
				JOIN `application`             AS `a`   ON (c.company_id = a.company_id)
				JOIN `loan_type` as lt ON (a.loan_type_id = lt.loan_type_id)
				JOIN (SELECT * FROM `agent_affiliation` WHERE affiliation_type = 'owner'
															AND affiliation_area = 'collections'
															AND ( `date_expiration` > NOW()
																	OR `date_expiration` is NULL)
    				) AS `aa`  ON (aa.application_id = a.application_id)
				JOIN	`event_schedule`          AS `es` ON ( `a`.`application_id` = `es`.`application_id`
															AND `es`.`date_event` BETWEEN
  									                      		DATE_FORMAT(CURRENT_DATE(),'%Y-%m-%d 00:00:00') AND
									                      		DATE_FORMAT(DATE_ADD(CURRENT_DATE(), INTERVAL 2 DAY),'%Y-%m-%d 23:59:59')
                    										AND `es`.`event_status` = 'scheduled' )
				JOIN	`application_status_flat` AS `asf` ON (`asf`.`application_status_id` = `a`.`application_status_id`
															AND	`asf`.`level0` = 'current'
															AND	`asf`.`level1` = 'arrangements'
															AND	`asf`.`level2` = 'collections'
															AND	`asf`.`level3` = 'customer'
															AND	`asf`.`level4` = '*root')
				JOIN	`agent`                   AS `ag` ON ( `ag`.`agent_id` = `aa`.`agent_id`)
				WHERE `c`.`company_id` IN ({$company_list})
					AND `aa`.`agent_id` IN ({$agent_id_list})
				{$loan_type_sql}
				";
		}
		if($unassigned_selected)
		{
			// Now build a query
			$query_parts[] = "
				SELECT
					UCASE(`c`.`name_short`) AS `company_name` ,
					`c`.`company_id` AS `company_id` ,
					`a`.`application_status_id` AS `application_status_id` ,
					`a`.`application_id` AS `app_id` ,
					`a`.`name_first` AS `first` ,
					`a`.`name_last` AS `last` ,
					'None' AS `date` ,
					'No' AS `arranged` ,
					'Unassigned' AS 'agent',
					lt.name_short as loan_type
				FROM
					`company`                 AS `c`   
				JOIN	`application`             AS `a`   ON ( `c`.`company_id` = `a`.`company_id` )
				JOIN `loan_type` as lt ON (a.loan_type_id = lt.loan_type_id)
				JOIN	`application_status_flat` AS `asf` ON ( `asf`.`application_status_id` = `a`.`application_status_id`
																AND	`asf`.`level1` = 'contact'
																AND	`asf`.`level2` = 'collections'
																AND	`asf`.`level3` = 'customer'
																AND	`asf`.`level4` = '*root')
				LEFT OUTER JOIN ( SELECT
							`aa`.`application_id`
						FROM
							`agent_affiliation` AS `aa`
						WHERE 1 = 1
							AND `aa`.`affiliation_type` = 'owner'
							AND `aa`.`affiliation_area` = 'collections'
							AND ( `aa`.`date_expiration` > NOW()
								OR `aa`.`date_expiration` is NULL
								)
						) no_agent_association ON (no_agent_association.application_id = a.application_id)
				WHERE no_agent_association.application_id IS NULL
					AND `c`.`company_id` IN ({$company_list})
					{$loan_type_sql}
					AND	( 0 = 1
						OR  `asf`.`level2` = 'collections'
						OR  ( 1 = 1
							AND `asf`.`level1` = 'collections'
							AND `a`.`date_application_status_set` < DATE_SUB(NOW(), INTERVAL 30 MINUTE)
							)
						OR  ( 1 = 1
							AND `asf`.`level0` = 'follow_up'
							AND `a`.`date_next_contact` < NOW()
							)
						)
				";
		}

		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			(".join(") UNION DISTINCT (", $query_parts).")
			ORDER BY
				`date` ASC, `app_id` ASC
			LIMIT
				{$max_report_retrieval_rows}
			";
		$query = preg_replace('/(^\s+--.*$)|(^\s+)/m','',$query);
		// Run query

		$query = "SELECT
			company_name,
			company_id,
			application_status_id,
			app_id,
			first,
			last,
			date,
			arranged,
			agent,
			loan_type
			FROM
			(" . $query . ") as results
		GROUP BY app_id";
		
		$st = $this->db->query($query);
		// Cap result size
		if( $st->rowCount() == $max_report_retrieval_rows )
		{
			return(FALSE);
		}

		// Process results
		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			// Grab the company name out of the row
			$company_name = $row['company_name'];

			// Clean up NULLs
			if(NULL === $row['date'])
			{
				$row['date'] = 'None';
			}

			// If you want to be able to link the column, you need this
			$this->Get_Module_Mode($row, FALSE);

			//Take care of name casing
			$row['first'] = ucfirst($row['first']);
			$row['last'] = ucfirst($row['last']);
			// Pass the data out by company
			$data[$company_name][] = $row;
		}

		// It takes THIS long to do this!
		$this->timer->stopTimer(self::$TIMER_NAME);

		// Return the juicy data they want
		return($data);
	}

}

?>
