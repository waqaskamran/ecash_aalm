<?php

require_once( SERVER_CODE_DIR . "base_report_query.class.php" );

/**
 * Report Query Class for the Queue Summary Report
 * 
 * Description:
 * 
 * Will display a visual representation of all of the items currently
 * available in the queue.  The number of rows returned in the report for
 * a given queue should always match the queue count in the eCash interface
 * and the order of the items in the report should correspond with the order
 * in which they will be pulled from the queue.
 * 
 * Originally this report was used just as a way of seeing what items 
 * were in the queue but many customers have repurposed the report for 
 * marketing means.  I'm not sure if that's such a good idea because if you 
 * have one agent working the queues and another calling people out of a list
 * there's a good chance the customer will be contacted twice. [BR]
 *
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
class Queue_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Queue Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches report data for the Queue Summary report
	 *
	 * @param string $queue_name
	 * @param int $company_id
	 * @param int $min_hours
	 * @param int $max_hours
	 * @param array $attributes
	 * @return array
	 */
	public function Fetch_Queue_Data($queue_name, $company_id, $min_hours, $max_hours, $attributes)
	{
		$data = null;
		$this->timer->startTimer(self::$TIMER_NAME);
		$company_list = $this->Format_Company_IDs($company_id);
		$application_ids = array();
		$rows = array();
		$order = 1;
		$db = ECash::getMasterDb();

		$filter_sql = $this->Get_Field_Filter_SQL($attributes, 'app', 'af'); //[#35246][#42067]
		
		$qm         = ECash::getFactory()->getQueueManager();
		$vqueue     = $qm->getQueue($queue_name);
		$table_name = $vqueue->getQueueEntryTableName();
		$time = localtime(time(), TRUE);

		if($vqueue instanceof ECash_Queues_TimeSensitiveQueue)
		{
			$time_zone_sql = "(nqe.start_hour <= HOUR(NOW()) and nqe.end_hour > HOUR(NOW()))";
					
		}
		else
		{
			$time_zone_sql = " 1=1 ";
		}

		//[#34811] use the queues sort order, if it's provided
		$sort_sql = 'ORDER BY priority DESC, time_in_queue DESC';
		/* @todo this might be nicer if all queues implemented this method,
		 * or implemented IQueueSortable or something */
		if(method_exists($vqueue, 'getSortOrder'))
		{
			$sort_sql = $vqueue->getSortOrder();
		}

		$having_part = "";

		if (is_numeric($min_hours) || is_numeric($max_hours))
		{
			$having_part = "HAVING 1=1 ";

			if (is_numeric($min_hours))
				$having_part .= "AND time_in_queue > " . ($min_hours * 60 * 60) . " ";
			
			if (is_numeric($max_hours))
				$having_part .= "AND time_in_queue < " . ($max_hours * 60 * 60) . " ";
		}

		$query = " 
					SELECT	nqe.related_id						  				 AS application_id,
							(UNIX_TIMESTAMP() - UNIX_TIMESTAMP(nqe.date_queued)) AS time_in_queue,
							(UNIX_TIMESTAMP() - UNIX_TIMESTAMP(nqe.date_queued)) +
                            (
							 select ifnull(sum((UNIX_TIMESTAMP(nqh.date_removed) - UNIX_TIMESTAMP(nqh.date_queued))),0)
                             from n_queue_history nqh
                             where nqh.related_id = app.application_id
							) as total_time,
							app.name_first										 AS name_first,
							app.name_last										 AS name_last,
							app.phone_home                                       AS phone_home,
							app.phone_work                                       AS phone_work,
							app.phone_cell                                       AS phone_cell,
							app.street											 AS street,
							app.city											 AS city,
							app.county											 AS county,
							app.state											 AS state,
							app.zip_code										 AS zip_code,
							app.application_status_id							 AS application_status_id,
							app.date_created                                     AS submission_date,
							c.company_id										 AS company_id,
							upper(c.name_short)									 AS company_name,
							ass.name											 AS status,
							(SELECT 
							 	sh.date_created
							 FROM 
							 	status_history sh
							 WHERE 
							 	sh.application_id = app.application_id
							 ORDER BY 
							 	date_created DESC
							 LIMIT 1) 											 AS last_action_date,
							IFNULL((
								SELECT
									SUM(tr.amount)
								FROM
									transaction_register tr
								WHERE
									tr.application_id = app.application_id
								AND		
									tr.transaction_status = 'complete'
							),0)												 AS balance,
							nq.name												 AS queue_name,
							(CASE WHEN ".$time_zone_sql." THEN 'Available' ELSE 'Unavailable' END ) as availablity
					FROM    
							{$table_name} 						  				 AS nqe
					JOIN    
							n_queue nq ON (nq.queue_id = nqe.queue_id)
					JOIN
							application app ON (app.application_id = nqe.related_id)
					JOIN
							company c ON (c.company_id = app.company_id)
					JOIN
					application_status AS ass ON (ass.application_status_id = app.application_status_id)
					{$filter_sql['from']}
				    WHERE	
							nq.name_short = " . $db->quote($queue_name) . "
					{$filter_sql['where']}
					AND		
							nqe.date_available <= NOW()
					AND
							(nqe.date_expire IS NULL OR nqe.date_expire >= now())
					AND		
							(
								nq.company_id IS NULL OR
							 	nq.company_id IN $company_list
							)
					AND
							app.company_id IN $company_list
					{$having_part}
					{$sort_sql} ";


		$result = $db->query($query);

		$app_data = ECash::getFactory()->getData('Application');

		while ($row = $result->fetch(PDO::FETCH_ASSOC))
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
			
			/**
			 * The $respect_company argument is used to determine whether or not the 
			 * application link should show if the application belongs to a company
			 * different than what we're logged in as. [BR]
			 */
			$respect_company = (ECash::getConfig()->MULTI_COMPANY_ENABLED === TRUE) ? FALSE : TRUE;
			$this->Get_Module_Mode($row, $respect_company);

						$company_name = $row['company_name'];
						$row["order"] = $order;

						$data[$company_name][$order-1] = $row;

						$order++;
					}

					$this->timer->stopTimer(self::$TIMER_NAME);

					return $data;
	}

}

?>
