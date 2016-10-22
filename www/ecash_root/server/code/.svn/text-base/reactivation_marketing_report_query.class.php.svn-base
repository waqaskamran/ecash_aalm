<?php

require_once( SERVER_CODE_DIR . "base_report_query.class.php" );
require_once( LIB_DIR . "business_rules.class.php" );

/**
 * Report Query Class for the Reactivation Marketing Report / 
 * Customer Marketing Report (It has two names.  The name 
 * depends on the customer who's viewing it.
 * 
 * Description:
 * 
 * This is a marketing report for a few different groups of statuses.
 * There are a few versions of this out there using the same name.
 * For example Impact and HMS both have custom versions of the report
 * which may have totally different purposes.  It's really nasty
 * and in much need of a rewrite.
 * 
 * History:
 * 
 * [#35246] - Added attribute filters to the global versions of this report so
 * a user can select Do Not Loan, Do Not Market, Do Not Contact, and Bad Info.
 * If Do Not Loan or Do Not Market are selected and the flags exist then
 * the application is removed from the report results.  If  Do Not Contact
 * or Bad Info is selected and the flags exist on any contact item, they will
 * be made blank in the report results.
 * 
 */
class Reactivation_Marketing_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Reactivation Marketing Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches Report Data for the Customer Marketing Report / Reactivation Marketing Report
	 *
	 * @param string $start_date
	 * @param string $end_date
	 * @param int $company_id
	 * @param string $loan_type
	 * @param string $status
	 * @param array $attributes
	 * @return array
	 */
	public function Fetch_Reactivation_Marketing_Data($start_date, $end_date, $company_id, $loan_type, $status, $attributes)
	{
		$this->timer->startTimer(self::$TIMER_NAME);

		$FILE = __FILE__;
		$METHOD = __METHOD__;
		$LINE = __LINE__;

		$disallowed_statuses_array = array(
				"recovered::external_collections::*root",
				"sent::external_collections::*root",
				"indef_dequeue::collections::customer::*root",
				"arrangements_failed::arrangements::collections::customer::*root",
				"hold::arrangements::collections::customer::*root",
				"unverified::bankruptcy::collections::customer::*root",
				"verified::bankruptcy::collections::customer::*root",
				"ready::quickcheck::collections::customer::*root",
				);

		$status_map = Fetch_Status_Map();
		$inactive_paid_status = Search_Status_Map('paid::customer::*root', $status_map);
		$withdrawn_status = Search_Status_Map('withdrawn::applicant::*root', $status_map);
		$denied_status = Search_Status_Map('denied::applicant::*root', $status_map);
		$active_status = Search_Status_Map('active::servicing::customer::*root', $status_map);

		$open_statuses_array = array(
				"pending::external_collections::*root",
				"new::collections::customer::*root",
				"active::servicing::customer::*root",
				"approved::servicing::customer::*root",
				"past_due::servicing::customer::*root",
				"current::arrangements::collections::customer::*root",
				"dequeued::contact::collections::customer::*root",
				"follow_up::contact::collections::customer::*root",
				"queued::contact::collections::customer::*root",
				"sent::quickcheck::collections::customer::*root",
				"collections_rework::collections::customer::*root",
		);

		$open_status_ids_array = array($active_status);
	
		foreach ($open_statuses_array as $open_status)
		{
			$status_id = Search_Status_Map($open_status, $status_map);
			if($status_id !== NULL)
				$open_status_ids_array[] = $status_id;
		}
		
		switch($status)
		{
			case 'inactive':
				$status_list = "$inactive_paid_status";
			break;
			case 'denied':
				$status_list = "$denied_status";
			break;
			case 'withdrawn':
				$status_list = " $withdrawn_status";
			break;
			case 'active':
				$status_list = "$active_status";
			break;
			case 'open':
				$status_list = implode(', ', $open_status_ids_array);
			break;
			case 'all':
			default:
				$status_list = implode(', ', array_merge(array($inactive_paid_status, $withdrawn_status, $denied_status), $open_status_ids_array));
			break;
		}
		
		if( $loan_type == 'all' )
			$loan_type_list = $this->Get_Loan_Type_List($loan_type);
		else
			$loan_type_list = "'{$loan_type}'";
			
		foreach ($disallowed_statuses_array as $status) {
			$disallowed_status_ids_array[] = Search_Status_Map($status, $status_map);
		}

		$disallowed_status_ids = implode (',', $disallowed_status_ids_array);

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

		$filter_sql = $this->Get_Field_Filter_SQL($attributes, 'a', 'af'); //[#35246][#42067]
		
		$query = "
			-- eCash 3.0, File: {$FILE} , Method: {$METHOD}, Line: {$LINE}
		SELECT
			UPPER(co.name_short) as name_short,
			a.application_status_id, 
			a.ip_address,
			a.company_id,
			co.name_short AS company_name,
			a.application_id, 
			a.name_last, 
			a.name_first, 
			a.ssn_last_four as ssn,
			a.phone_home,
			a.phone_cell,
			a.phone_work,
			a.phone_work_ext AS work_ext,
			a.email AS customer_email,
			a.street AS address,
			a.city  AS city,
			a.state AS state,
			a.zip_code AS zip_code,
			a.date_application_status_set, 
			a.fund_actual,
			a.date_fund_actual,
			IF(a.application_status_id = {$inactive_paid_status}, a.date_application_status_set, null) as date_payoff,
			UPPER(stat.name) as status,
			ash.date_created as esign_date,
			site.name as source_site
		FROM application AS a
		JOIN application_status AS stat USING (application_status_id)
		JOIN loan_type AS lt USING (loan_type_id)
		LEFT JOIN application AS a2 USING (ssn)
		LEFT JOIN company co ON a.company_id = co.company_id
		LEFT JOIN (SELECT status_history.date_created, application_id FROM status_history JOIN application_status USING (application_status_id) WHERE name = 'Pending') AS ash ON a.application_id = ash.application_id 
		LEFT JOIN campaign_info ci on (a.application_id = ci.application_id)
		LEFT JOIN site on (ci.site_id = site.site_id)
		{$filter_sql['from']}
		WHERE a.application_status_id in ({$status_list})
		{$filter_sql['where']}
		AND a.company_id IN ({$company_list})
		AND a.date_application_status_set BETWEEN {$start_date}000000 AND {$end_date}235959
		AND lt.name_short IN ({$loan_type_list})
		AND NOT EXISTS (
			SELECT application_id
			FROM application a2
			WHERE a2.customer_id = a.customer_id 
			AND  application_id <> a.application_id 
			AND a2.date_created > a.date_created
		)
		GROUP BY a.ssn
		ORDER BY a.date_application_status_set
		LIMIT {$this->max_display_rows}";

		$data = array();
		$fetch_result = $this->db->query($query);

		$app_data = ECash::getFactory()->getData('Application');

		while ($row = $fetch_result->fetch(PDO::FETCH_ASSOC))
		{
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

			$co = strtoupper($row['name_short']);

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
