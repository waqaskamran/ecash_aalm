<?php

require_once( SERVER_CODE_DIR . "base_report_query.class.php" );

class Verification_Performance_Report_Query extends Base_Report_Query
{
        private static $TIMER_NAME = "Verification Performance Report Query";
        private $system_id;

        public function __construct(Server $server)
        {
                parent::__construct($server);

                $this->system_id = $server->system_id;
        }

        public function Fetch_Verification_Performance_Data($date_start, $date_end, $loan_type, $company_id)
        {
                $this->timer->startTimer( self::$TIMER_NAME );

                $company_list = $this->Format_Company_IDs($company_id);
                $loan_type_list = $this->Get_Loan_Type_List($loan_type);

                // Start and end dates must be passed as strings with format YYYYMMDD
                $timestamp_start = $date_start . '000000';
                $timestamp_end   = $date_end   . '235959';

				
                // This query gets all agents who have pulled from underwriting queues
                $query = "
                        -- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
                        SELECT
                                UPPER(c.name_short)                                         AS company_name,
                                CONCAT(LOWER(a.name_first),
                                           ' ',
                                           LOWER(a.name_last))                              AS agent_name,
                                SUM(IF(act.name_short = 'Underwriting', 1, 0))              AS num_pull_uw
                        FROM
                                agent_action aact
                        JOIN
                                agent a ON (a.agent_id = aact.agent_id)
                        JOIN
                                company c ON (c.company_id = aact.company_id)
                        JOIN
                                action act ON (act.action_id = aact.action_id)
                        JOIN
                                application app ON (app.application_id = aact.application_id)
                        WHERE
                                aact.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
                        AND
                                aact.company_id IN {$company_list}
                        GROUP BY
                                aact.company_id, aact.agent_id
                ";

                $agents = array();

                $st = $this->db->query($query);

                while ($row = $st->fetch(PDO::FETCH_ASSOC))
                {
                        $cname = $row['company_name'];
                        $aname = $row['agent_name'];

						if ($row['num_pull_uw'])
						{
	                        $agents[$cname][$aname]['num_pull_uw']               = $row['num_pull_uw'];
						}
                }

                // Now this query gets status changes
                $query = "
                        -- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
                        SELECT
                                UPPER(c.name_short)                                  AS company_name,
                                CONCAT(LOWER(a.name_first),
                                           ' ',
                                           LOWER(a.name_last))                       AS agent_name,
                                SUM(IF(ass.name_short='approved', 1, 0))             AS num_funded,
                                SUM(IF(ass.name='Approved'  AND
                                       ass.name_short='queued', 1, 0))               AS num_approved,
                                SUM(IF(ass.name_short='withdrawn', 1, 0))            AS num_withdrawn,

                                SUM(IF(ass.name_short='denied', 1, 0))               AS num_denied,
                                SUM(IF(ass.name='Confirmed' AND
                                       ass.name_short='queued', 1, 0))               AS num_reverified
                        FROM
                                status_history sh
                        JOIN
                                agent a ON (a.agent_id = sh.agent_id)
                        JOIN
                                company c ON (c.company_id = sh.company_id)
                        JOIN
                                application_status ass ON (ass.application_status_id = sh.application_status_id)
                        JOIN
                                application app ON (app.application_id = sh.application_id)
                        WHERE
                                sh.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
                        AND
                                sh.company_id IN {$company_list}
                        GROUP BY
                                sh.company_id, sh.agent_id
                ";

                $st = $this->db->query($query);

				while ($row = $st->fetch(PDO::FETCH_ASSOC))
				{
					$cname = $row['company_name'];
					$aname = $row['agent_name'];

					// This is a bit ugly, but just a simple check to exclude all agents with no stats
					if ($row['num_funded'] + $row['num_approved'] + $row['num_withdrawn'] + $row['num_denied'] + $row['num_reverified'])
					{
						$agents[$cname][$aname]['num_funded']     = $row['num_funded'];
						$agents[$cname][$aname]['num_approved']   = $row['num_approved'];
						$agents[$cname][$aname]['num_withdrawn']  = $row['num_withdrawn'];
						$agents[$cname][$aname]['num_denied']     = $row['num_denied'];
						$agents[$cname][$aname]['num_reverified'] = $row['num_reverified'];
					}
				}

                $data = array();

                // Now make it ecash format friendly
                foreach  ($agents as $company_name => $company_data)
                {
                        foreach ($company_data as $agent_name => $agent_data)
                        {
                                $agent_data['agent_name']   = $agent_name;
								$agent_data['company_name'] = $company_name;
                                $data[$company_name][] = $agent_data;
                        }
                }

                $this->timer->stopTimer( self::$TIMER_NAME );

                return $data;
        }

        // Why is this here? I see no reference to it [benb]
        public function Fetch_OLP_Agent_Id()
        {
                $query = "
                                SELECT agent_id
                                FROM agent
                                WHERE login = 'olp'
                                AND active_status = 'active' ";

                return $this->db->querySingleValue($query);
        }
}

?>
