<?php

require_once( SERVER_CODE_DIR . "base_report_query.class.php" );

class Follow_Up_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Follow Up Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);

		//$this->Add_Status_Id('follow_up_fraud',        array('follow_up', 'fraud',        'applicant',   '*root'));
		$this->Add_Status_Id('follow_up_contact',      array('follow_up', 'contact',      'collections', 'customer', '*root'));

		$this->Add_Application_Link_Destination( 'fraud_followup',        'watch',          'watch' );
		$this->Add_Application_Link_Destination( 'follow_up_contact',      'loan_servicing', 'account_mgmt' );
	}

	public function Fetch_Agent_Comment_Data($date_start, $date_end, $company_id, $loan_type, $follow_up_queue)
	{
		$this->timer->startTimer( self::$TIMER_NAME );

		$comment_data    = array();

		if( is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0 )
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";
			
		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		
		if ($date_end == date('Ymd')) 
		{
			$timestamp_end = date('YmdHis');
		} 
		else 
		{
			$timestamp_end	= $date_end   . '235959';
		}

		switch ($follow_up_queue)
		{
			case 'underwriting':
				$queue = "fu.follow_up_type_id = 3";
				break;
			case 'verification':
				$queue = "fu.follow_up_type_id  = 2";
				break;
			case 'fraud':
				$queue = "fu.follow_up_type_id  = 1";
				break;
			case 'collections':
				$queue = "fu.follow_up_type_id  = 4";
				break;
			case 'all':
				$queue = "fu.follow_up_type_id  in (1,2,3,4,5,6,7,8) ";
				break;
			default :
				break;
		}

		// Get last comment left for each application currently in follow-up
		$query = "
       SELECT
                upper(co.name_short)     AS company_name,
                fu.company_id             AS company_id,
                a.application_status_id     AS application_status_id,
                fu.application_id         AS application_id,
                fu.follow_up_time        AS follow_up,
                lt.name_short as loan_type,
		(SELECT
                    ft.name
                  FROM
                      follow_up_type ft
                  WHERE
                      ft.follow_up_type_id = fu.follow_up_type_id
                )                        AS queue,
     		       CONCAT(agent.name_first,
                       ' ',
                       agent.name_last) AS agentName,
		 fu.date_created as date_created,
		 (SELECT sc.comment
                  FROM  comment AS sc
                  WHERE sc.comment_id = fu.comment_id
                )                        AS comment

                from 
                 company                  AS co,
                 follow_up                 AS fu,
          	  	 agent                    AS agent,
		 		 application              AS a use index (idx_app_status_co_stsdate),
				 loan_type                AS lt
             WHERE
           		    agent.agent_id           =  fu.agent_id
              AND   fu.company_id             =  co.company_id
              AND   fu.application_id = a.application_id
	      	  AND   a.loan_type_id = lt.loan_type_id
			  AND	fu.company_id             IN ({$company_list})
			  AND	lt.name_short            IN ({$loan_type_list})
			 
			
			  AND	({$queue})
			  AND	fu.follow_up_time BETWEEN '{$timestamp_start}'
				                        AND '{$timestamp_end}'
			 ORDER BY application_id
		         ";

		//echo "<pre>" . str_replace("\t", "    ", $query) . "</pre><br>\n";
		//exit;

		// If query is successful, get the result data
		//   && number of rows

		$result = $this->db->query($query);

		while( $row = $result->fetch(PDO::FETCH_ASSOC) )
		{
			// Need data as array( Company => array( 'colname' => 'data' ) )
			//   Do all data formatting here
			$company_name = $row['company_name'];
			//unset($row['company_name']);

			$this->Get_Module_Mode($row);

			$row['agentName'] = ucwords($row['agentName']);
			$comment_data[$company_name][] = $row;
		}

		$this->timer->stopTimer( self::$TIMER_NAME );

		return $comment_data;
	}
}

?>
