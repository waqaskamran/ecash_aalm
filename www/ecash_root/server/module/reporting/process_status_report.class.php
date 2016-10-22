<?php
/**
 * Report
 * Process Status Report
 *
 * @package Reporting
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 * @created Dec 11, 2006
 *
 * @version $Revision$
 */

require_once( SERVER_MODULE_DIR . "/reporting/report_generic.class.php" );
require_once( SERVER_CODE_DIR . "/base_report_query.class.php" );
require_once LIB_DIR . 'business_rules.class.php';

class Report extends Report_Generic {

	public function Generate_Report()
	{
		try
		{
			$search_query = new Process_Status_Report_Query($this->server);

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
					);

			// Copy the search criteria into the session, but don't use the $data
			// object because it will be used to store aggregate data
			$_SESSION['reports']['process_status']['report_data'] = new stdClass();
			$_SESSION['reports']['process_status']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['process_status']['url_data'] = array('name' => 'Process Status Report', 'link' => '/?module=reporting&mode=process_status');

	        // Start date

	        $start_date_YYYY = $this->request->start_date_year;
	        $start_date_MM   = $this->request->start_date_month;
	        $start_date_DD   = $this->request->start_date_day;
	        if(!checkdate($start_date_MM, $start_date_DD, $start_date_YYYY))
	        {
	            //return with no data
	            $data->search_message = "Start Date invalid or not specified.";
	            ECash::getTransport()->Set_Data($data);
	            ECash::getTransport()->Add_Levels("message");
	            return;
	        }


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


	        $start_date_YYYYMMDD = 10000 * $start_date_YYYY + 100 * $start_date_MM + $start_date_DD;
	        $end_date_YYYYMMDD   = 10000 * $end_date_YYYY   + 100 * $end_date_MM   + $end_date_DD;


	        if($end_date_YYYYMMDD < $start_date_YYYYMMDD)
	        {
	            //return with no data
	            $data->search_message = "End Date must not precede Start Date.";
	            ECash::getTransport()->Set_Data($data);
	            ECash::getTransport()->Add_Levels("message");
	            return;
	        }


        	$data->search_results = $search_query->Fetch_Report_Results( $data->search_criteria['company_id'] , $start_date_YYYYMMDD, $end_date_YYYYMMDD );
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

		if(empty($_SESSION[$this->report_name]['last_sort'])) 
		{
			$_SESSION[$this->report_name]['last_sort']['col']       = 'business_day';
			$_SESSION[$this->report_name]['last_sort']['direction'] = SORT_DESC;
		}

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['process_status']['report_data'] = $data;

	}
}

class Process_Status_Report_Query extends Base_Report_Query {

	private static $TIMER_NAME = "Process Status Report Query";

	private static $business_rules = array();

	static $skip_rules = array ('resolve_payments_due_report',
								'resolve_open_advances_report'
								);

	static $rule_step_map = array (	'resolve_flash_report' 				=> 'resolve_flash',
									'resolve_dda_history_report' 		=> 'resolve_dda_history',
//									'resolve_payments_due_report' 		=> '',
//									'resolve_open_advances_report' 		=> '',
									'nightly_transactions_update' 		=> 'nightly_trans',
									'resolve_past_due_to_active' 		=> 'resolve_past_due',
									'resolve_collections_new_to_act' 	=> 'resolve_collections_new',
									'move_bankruptcy_to_collections' 	=> 'bankruptcy_move',
									'completed_accounts_to_inactive' 	=> 'set_inactive',
									'set_qc_to_2nd_tier' 				=> 'qc_2nd_tier',
									'expire_watched_accounts' 			=> 'expire_watched',
//									'reschedule_held_apps' 				=> '',
									'deq_coll_to_qc_ready' 				=> 'collections_qc',
//									'cmp_aff_exp_actions' 				=> '',
									);

	private static function Map_Rule_to_Step($rule)
	{
		if (in_array($rule, array_keys(self::$rule_step_map))) 
		{
			return self::$rule_step_map[$rule];
		}

		return $rule;
	}

	private static function Map_Step_to_Rule($step)
	{
		if (in_array($step, self::$rule_step_map)) 
		{
			return array_search($step, self::$rule_step_map);
		}

		return $step;
	}

	private function Get_Days_Tasks($company_id,$date)
	{
		$rules = array_keys($this->Get_Business_Rules($company_id));
		$trules = array();

		foreach($rules as $rule) 
		{
			if($this->Should_Run_Task($company_id,$rule,$date)) 
			{
				$trules[] = $rule;
			}
		}

		return $trules;

	}

	private function Should_Run_Task($company_id,$rule_name, $day) {
		$rules = $this->Get_Business_Rules($company_id);

		$named_day = date('l', $day);
		$days = $rules[$rule_name];

		if ($days[$named_day] == 'Yes') 
		{
			if ($days['Holidays'] == 'No') 
			{
				$holidays = Fetch_Holiday_List();
				$check_day = strtotime("+1 day", $day);
				if (in_array($check_day, $holidays)) 
				{
					return false;
				} 
				else 
				{
					return true;
				}
			} 
			else 
			{
				return true;
			}
		} 
		else 
		{
			return false;
		}
	}


	private function Get_Business_Rules($company_id)
	{
		if (empty(self::$business_rules[$company_id]))
		{
			self::$business_rules[$company_id] = array();

			$business_rules = new ECash_Business_Rules(ECash::getMasterDb());
			$loan_type_id = $this->Get_Company_Loan_Type($company_id,$business_rules);
			$rule_sets = $business_rules->Get_Rule_Sets();
			$rule_set_id = 0;
			foreach ($rule_sets as $rule_set) 
			{
				if ($rule_set->loan_type_id == $loan_type_id && $rule_set->name == 'Nightly Task Schedule') 
				{
					$rule_set_id = $rule_set->rule_set_id;
				}
			}
			if ($rule_set_id) 
			{
				self::$business_rules[$company_id] = array_diff_key($business_rules->Get_Rule_Set_Tree($rule_set_id), array_flip(self::$skip_rules));
			}
		}

		return self::$business_rules[$company_id];
	}

	private function Get_Company_Loan_Type($company_id, Business_Rules $business_rules) {
		$loan_types = $business_rules->Get_Loan_Types($company_id);

		foreach ($loan_types as $type) 
		{
			if ($type->name == 'Offline Processing Rules') 
			{
				return $type->loan_type_id;
			}
		}

		return 0;
	}

	public function Fetch_Report_Results( $company_id, $date_start, $date_end )
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
        if( $company_id > 0 )
        {
            $company_list = "'{$company_id}'";
        }
        else
        {
            $company_list = "'" . implode("','", $auth_company_ids) . "'";
        }

        $ustart = strtotime($date_start);
        $uend   = strtotime($date_end);

        $company_array = explode(",",str_replace("'","",$company_list));
        foreach($company_array as $co) 
		{
        	for($i = $ustart ; $i < ($uend + 1) ; $i = $i + 86400) 
			{
        		$dt = $this->Get_Days_Tasks($co,$i);
        		if(!empty($dt)) 
				{
        			$tasks[$co][date('Y-m-d',$i)] = $dt;
        		}
        	}
        }

        // Use this in the LIMIT statement of your query
        $max_report_retrieval_rows = $this->max_display_rows + 1;

        // Now initialize the data array we will be returning
        $data = array();

        // Now build a query
        $query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
select
     c.name as company_name,
     c.company_id,
     p.process_log_id,
     p.step as process_step,
     p.state as process_state,
     p.date_started as start_date,
     p.date_modified as end_date,
     sec_to_time( ( p.date_modified - p.date_started)) as duration,
     p.business_day
 from
     process_log p
 join
     company c
 on p.company_id = c.company_id
 where 1 = 1
	AND c.company_id IN ({$company_list})
	AND p.business_day between {$date_start} and {$date_end}
 order by
     company_id, business_day desc, date_started desc, process_step asc
 limit
        {$max_report_retrieval_rows}
";

				$st = $this->db->query($query);

				if( $st->rowCount() == $max_report_retrieval_rows )
					return false;

        $tcompanies = array();
        $tbd = $date_end;

        while ($row = $st->fetch(PDO::FETCH_ASSOC))
        {
            // quick and dirty way to get a company name from id at this exact moment
            $tcompanies[$row['company_id']] = $row['company_name'];

//        	// Grab the company name out of the row
//            $company_name = $row['company_name'];
//            unset($row['company_name']);

            if( is_array($tasks[$row['company_id']]) &&
            	is_array($tasks[$row['company_id']][$row['business_day']]) &&
            	in_array(self::Map_Step_to_Rule($row['process_step']),$tasks[$row['company_id']][$row['business_day']])) {
            		$tasks[$row['company_id']][$row['business_day']] = array_diff( 	$tasks[$row['company_id']][$row['business_day']],
            																		array(self::Map_Step_to_Rule($row['process_step']))
            																		);

            	}

            // Pass the data out by company
//            $tdata[] = $row;

            $data[$row['company_name']][] = $row;

        }

        foreach ($tasks as $company_id => $task_day) 
		{
        	foreach ($task_day as $day => $task_list) 
			{
        		foreach ($task_list as $task) 
				{
//        			$data[$tcompanies[$company_id]][$row['business_day']][] = array('company_id' => $company_id,
        			$data[$tcompanies[$company_id]][] = array('company_id' => $company_id,
//        			$tdata[] = array('company_name' => $tcompanies[$company_id],
        							'company_id' => $company_id,
        							'business_day' => $day,
        							'process_step' => self::Map_Rule_to_Step($task),
        							'process_state' => 'not started',
//        							'start_date' => '0000-00-00 00:00:00',
//        							'end_date' => '0000-00-00 00:00:00',
//        							'duration' => '00:00:00'
        							);
        		}
        	}
        }

//        foreach($tdata as $row) {
//        	$ddata[$row['business_day']][] = $row;
//        }
//
//        krsort($ddata);
//
//        foreach($ddata as $day => $set) {
//        	foreach ($set as $row) {
//        		$data[$row['company_name']][] = $row;
//        	}
//        }

//        var_dump($tasks);

//        var_dump($data);

        // It takes THIS long to do this!
        $this->timer->stopTimer(self::$TIMER_NAME);

        // Return the juicy data they want
        return($data);
    }
}

?>
