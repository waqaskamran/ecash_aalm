<?php

require_once( SERVER_CODE_DIR . "base_report_query.class.php" );

class Loan_Actions_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Loan Actions Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	public function Fetch_Loan_Actions_Report_Data($date_start, $date_end, $company_id,$denied = FALSE, $loan_type = 'all')
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

		$max_report_retrieval_rows = $this->max_display_rows + 1;

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end   = $date_end   . '235959';

		$query = "
			SELECT
				lh.application_id,
				concat(a.name_last, ', ', a.name_first) as full_name,
				a.ssn,
				IF(l.type LIKE \"PRESCRIPTION,%\",
				   (SELECT
					CONCAT('Comment: ',com.comment)
				     FROM 
					comment AS com
				     WHERE 
					com.related_key = lh.loan_action_history_id
				   ),
				   l.description
				)                    AS description,
				l.type               AS loan_action_type,
				aps.name_short            AS current_status,
				aps.application_status_id AS application_status_id,
				a.company_id              AS company_id,
				s.name               AS status,
				upper(co.name_short) AS company_name,
				ag.agent_id          AS agent_id,
				concat(lower(ag.name_first), ' ', lower(ag.name_last)) AS agent_name,
				date_format(lh.date_created,
				            '%m/%d/%y %H:%i') AS date_created,
				ci.campaign_name,
				site.name as site
			 FROM
				application             AS a
			 LEFT JOIN campaign_info ci ON (ci.application_id = a.application_id)
			 LEFT JOIN site on (site.site_id = ci.site_id),
				loan_actions            AS l,
				application_status      AS s,
				application_status      AS aps,
				company                 AS co,
				agent                   AS ag,
				loan_action_history     AS lh
			 WHERE
			    a.company_id IN ({$company_list})
				";

		// GF #16134: If in a live runlevel, do not include applications with 'tsstest' in the first or last name
		if (EXECUTION_MODE == 'LIVE')
		{
			$query .= "AND (a.name_last NOT LIKE '%tsstest%' AND a.name_first NOT LIKE '%tsstest%')\n";
		}

		if(!$denied)
		{
			$query .= "
			  AND	EXISTS (
					SELECT 
						lh2.application_id
					 FROM 
						loan_actions        AS l2,
						loan_action_history AS lh2
					 WHERE
						l2.type = 'PRESCRIPTION'
					  AND	l2.loan_action_id  = lh2.loan_action_id 
					  AND	lh2.application_id = a.application_id
				)
			  AND	EXISTS (
					SELECT 
						lh2.application_id
					 FROM 
						loan_actions        AS l2,
						loan_action_history AS lh2
					 WHERE
						l2.type             != 'PRESCRIPTION'
					  AND	l2.loan_action_id   =  lh2.loan_action_id 
					  AND	lh2.application_id  =  a.application_id
					  AND	lh2.date_created BETWEEN '{$timestamp_start}'
						                     AND '{$timestamp_end}'
				)
			";
		} 
		else if($denied)
		{ 
			$query .= "
			  AND	(
				  (s.name = 'Denied')
				  OR
				  (s.name = 'Withdrawn')
				)
			  AND
				lh.date_created  BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
			";
		}

		$query .= "
			  AND	s.application_status_id = lh.application_status_id
			  AND	a.application_status_id = aps.application_status_id
			  AND	co.company_id = a.company_id      
			  AND	a.application_id = lh.application_id
			  AND	l.loan_action_id = lh.loan_action_id
			  AND	lh.agent_id = ag.agent_id
			 ORDER BY lh.application_id DESC,lh.date_created ASC
			 LIMIT {$max_report_retrieval_rows}
			";

		$db = ECash::getMasterDb();
		$result = $db->query($query);

		if( $result->rowCount() == $max_report_retrieval_rows )
		{
			return false;
		}
		
		$app_temp = '';

		$items = array();

		while ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$row["description"] = is_null($row["description"]) ? "Other Disposition" : $row["description"];
			$row["agent_name"] = ($row["agent_id"] == 1) ? "" : $row["agent_name"];
			$company_name = $row['company_name'];

			//$appid = ($app_temp == $row["application_id"]) ? "" : $row["application_id"];
			$appid = $row["application_id"];
			$cstatus = $row["current_status"];
			
			$this->Get_Module_Mode($row);

			if(($row['loan_action_type'] == "PRESCRIPTION") && !$denied)
			{
				$items[$company_name][] = array( "application_id" => $appid,
												 "company_name"   => $company_name,
				                                 "current_status" => $cstatus,
				                                 "full_name"	  => ucwords($row["full_name"]),
				                                 "ssn"			  => $row["ssn"],
				                                 "verification"   => $row["description"],
								 "disposition"    => "",
				                                 "status"         => $row["status"],
				                                 "agent"          => $row["agent_name"],
				                                 "date"           => $row["date_created"],
												 'campaign_name'  => $row['campaign_name'],
												 'site'			  => $row['site'],
								 "module"         => isset($row["module"]) ? $row["module"] : null,
								 "mode"           => isset($row["mode"]) ? $row["mode"] : null);
			}
			else 
			{
				$items[$company_name][] = array( "application_id" => $appid,
												 "company_name"   => $company_name,
				                                 "current_status" => $cstatus,
				                                 "full_name"	  => ucwords($row["full_name"]),
				                                 "ssn"			  => $row["ssn"],
								 "verification"   => "",
				                                 "disposition"    => $row["description"],
				                                 "status"         => $row["status"],
				                                 "agent"          => $row["agent_name"],
				                                 "date"           => $row["date_created"],	
												 'campaign_name'  => $row['campaign_name'],
												 'site'			  => $row['site'],
							 "module"         => isset($row["module"]) ? $row["module"] : null,
								 "mode"           => isset($row["mode"]) ? $row["mode"] : null);
			}

			$app_temp = $row['application_id'];

		}

		$this->timer->stopTimer( self::$TIMER_NAME );

		return $items;
	}
}

?>
