<?php

require_once( SERVER_CODE_DIR . "base_report_query.class.php" );

class Status_Overview_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Status Overview Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Manual Payment Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   string $loan_type  standard || card
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Status_Overview_Data($status_type, $balance_type, $date, $company_id, $attributes)
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
		
		$status_type = explode(',',$status_type);

		// The status type actually is a comma separated list of status ids!
		$status = "'" . implode("','", $status_type) . "'";

		$filter_sql = $this->Get_Field_Filter_SQL($attributes, 'ap', 'af'); //[#41176]
		
		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT	
				ap.application_id,
				ap.name_first,
				ap.name_last,
				ap.phone_home,
				ap.phone_work,
				ap.phone_cell,
				ap.street,
				ap.city,
				ap.county,
				ap.state,
				ass.name as status,
				COALESCE((
 									 SELECT
                                               
                                                SUM(ea.amount) AS balance
                                        FROM
                                                event_amount AS ea
                                                JOIN event_amount_type eat USING (event_amount_type_id)
						JOIN transaction_register tr USING (transaction_register_id)
                                        WHERE
                                                (ea.company_id IN ({$company_list})) AND
                                                ea.application_id = ap.application_id and
                                                (eat.name_short in ('principal')) AND
                                                (tr.transaction_status in ('complete','pending'))
					
				
				), 0) as principal_balance,
				COALESCE((
 									 SELECT
                                               
                                                SUM(ea.amount) AS balance
                                        FROM
                                                event_amount AS ea
                                                JOIN event_amount_type eat USING (event_amount_type_id)
						JOIN transaction_register tr USING (transaction_register_id)
                                        WHERE
                                                (ea.company_id IN ({$company_list})) AND
                                                ea.application_id = ap.application_id and
                                                (eat.name_short in ('service_charge')) AND
                                                (tr.transaction_status in ('complete','pending'))
					
				
				), 0) as interest_balance,
				COALESCE((
 									 SELECT
                                               
                                                SUM(ea.amount) AS balance
                                        FROM
                                                event_amount AS ea
                                                JOIN event_amount_type eat USING (event_amount_type_id)
						JOIN transaction_register tr USING (transaction_register_id)
                                        WHERE
                                                (ea.company_id IN ({$company_list})) AND
                                                ea.application_id = ap.application_id and
                                                (eat.name_short in ('fee')) AND
                                                (tr.transaction_status in ('complete','pending'))
					
				
				), 0) as fee_balance,
				COALESCE((
 									 SELECT
                                               
                                                SUM(ea.amount) AS balance
                                        FROM
                                                event_amount AS ea
                                                JOIN event_amount_type eat USING (event_amount_type_id)
						JOIN transaction_register tr USING (transaction_register_id)
                                        WHERE
                                                (ea.company_id IN ({$company_list})) AND
                                                ea.application_id = ap.application_id and
                                                (eat.name_short in ('principal','fee','service_charge')) AND
                                                (tr.transaction_status in ('complete','pending'))
					
				
				), 0) as loan_balance,
				ap.application_status_id AS application_status_id,
        		ap.company_id,
        		c.name_short as company_name,
        		lt.name_short as loan_type
			FROM
				 application ap
			JOIN
				 loan_type AS lt ON lt.loan_type_id = ap.loan_type_id
			JOIN 
				application_status ass ON (ass.application_status_id = ap.application_status_id)
			JOIN 
				company c ON (c.company_id = ap.company_id)
			{$filter_sql['from']}
			WHERE
				ap.application_status_id IN ({$status})
			{$filter_sql['where']}
			";

			// GF #16134: Hide test applications if LIVE [benb]
			if (EXECUTION_MODE == 'LIVE')
			{
				$query .= "AND (ap.name_last NOT LIKE '%tsstest%' AND ap.name_first NOT LIKE '%tsstest%')\n";
			}

			$query .= "
            AND 
				ap.company_id IN ({$company_list})
            HAVING
				(loan_balance {$type} 0)
           	ORDER BY
				company_id,
				status
			";			

		$data = array();

		$app_data = ECash::getFactory()->getData('Application');

		$fetch_result = $this->db->query($query);
		while ($row = $fetch_result->fetch(PDO::FETCH_ASSOC))
		{
			$co = strtoupper($row['company_name']);

			if(!empty($attributes))
			{
				//Not displaying numbers that are flagged as do not contact, doing it here because it is faster than having three sub queries in the main query [#16638]
				$flags = $app_data->getContactFlags('application', $row['application_id']);
				foreach($flags as $flag_info)
				{
					if(in_array($flag_info->field_name, $attributes) && isset($row[$flag_info->column_name]))
						$row[$flag_info->column_name] = '';
				}
			}
				
			/**
			 * The $respect_company argument is used to determine whether or not the 
			 * application link should show if the application belongs to a company
			 * different than what we're logged in as. [BR]
			 */
			$respect_company = (ECash::getConfig()->MULTI_COMPANY_ENABLED === TRUE) ? FALSE : TRUE;
			$this->Get_Module_Mode($row, $respect_company);

			$data[$co][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>
