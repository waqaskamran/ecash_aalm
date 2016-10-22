<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once( SERVER_MODULE_DIR . "reporting/report_generic.class.php" );
require_once( SERVER_CODE_DIR . "base_report_query.class.php" );

class Report extends Report_Generic
{
    public function Generate_Report()
    {
		try
		{
	    	$search_query = new My_Apps_Report_Query($this->server);
	        $data = new stdClass();
	        $data->search_criteria = array( 
				'company_id' => $this->request->company_id, 
				'agent_id'   => $this->request->agent_id, 
				'loan_type'  => $this->request->loan_type
			);

	        $_SESSION['reports']['my_apps']['report_data'] = new stdClass();
			$_SESSION['reports']['my_apps']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['my_apps']['url_data'] = array('name' => 'My Queue', 'link' => '/?module=reporting&mode=my_apps');

        	$data->search_results = $search_query->Fetch_Report_Results( 
																			$data->search_criteria['company_id'] , 
																			$data->search_criteria['agent_id'],
																			$this->request->loan_type );

		}
		catch (Exception $e)
		{
			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}
        if( $data->search_results === false )
        {
            $data->search_message = "Your report would have more than " . $this->max_display_rows . " lines to display. Please narrow the date range.";
            ECash::getTransport()->Set_Data($data);
            ECash::getTransport()->Add_Levels("message");
            return;
        }
        $data = $this->Sort_Data($data);
        ECash::getTransport()->Add_Levels("report_results");
        ECash::getTransport()->Set_Data($data);
        $_SESSION['reports']['my_apps']['report_data'] = $data; //mantis:5064 - 'my_apps' instead of 'reminder_queue'
    }
}

class My_Apps_Report_Query extends Base_Report_Query
{
    private static $TIMER_NAME = "My Apps Report Query";

    public function Fetch_Report_Results( $company_id, $agent_id, $loan_type )
    {
        // Determine how long it take to do this
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
			$companies = array($company_id);
		}
		else
		{
			$company_list = "'" . implode("','", $auth_company_ids) . "'";
			$companies = $auth_company_ids;
		}
        // Use this in the LIMIT statement of your query
        $max_report_retrieval_rows = $this->max_display_rows + 1;

        // Now initialize the data array we will be returning
        $data = array();

        // If they want an affiliated agent
        if(!is_array($agent_id) || 0 == count($agent_id))
        {
            $agent_id = array();
        }

        // Build a SQL list
        $agent_id_list = join(",",$agent_id);

        //I ripped off the my queue availability code directly from 
        $open_time  = ECash::getConfig()->LOCAL_EARLIEST_CALL_TIME;
		$close_time = ECash::getConfig()->LOCAL_LATEST_CALL_TIME;
		
		$dst = idate('I');
        
		$qm = ECash::getFactory()->getQueueManager();
		$agent_queue = $qm->getQueue('Agent');
		$table = $agent_queue->getQueueEntryTableName();
		$reason_table = $agent_queue->getQueueReasonTableName();
		$time = localtime(time(), TRUE);

		if ($loan_type == 'all')
			$loan_type_sql = "";
		else
			$loan_type_sql = "AND lt.name_short = '{$loan_type}'\n";

		$query = "
			select
				a.application_id,
				IF(
				(CASE 
					WHEN 
						{$dst} AND
						(z.dst = 'Y' OR z.dst IS NULL)
					THEN  extract( HOUR_MINUTE from date_sub(utc_timestamp(), interval IFNULL(z.tz, 6) - 1 hour))
					
					ELSE  extract( HOUR_MINUTE from date_sub(utc_timestamp(), interval IFNULL(z.tz, 6) hour))
				END
				) BETWEEN qe.start_hour*100 AND qe.end_hour*100,'Available','Unavailable')
				AS availability,
				qe.date_expire date_expiration,
				qe.date_available date_next_contact,
				qr.name affiliation_area,
				a.name_last,
				a.name_first,
				CONCAT(agent.name_first, ' ', agent.name_last) agent_full_name,
				UPPER(c.name_short) company_name,
				c.company_id,
				a.application_status_id,
				qr.name AS follow_up_type
			from {$table} qe
				JOIN {$reason_table} qr USING (agent_queue_reason_id)
				join application a on related_id = application_id
			join loan_type lt on (lt.loan_type_id = a.loan_type_id)
				join agent on agent.agent_id = qe.agent_id
				join company c on c.company_id = a.company_id
				LEFT JOIN zip_tz as z ON (a.zip_code = z.zip_code)
			where
				qe.queue_id = ?
				and qe.date_available <= ?
				and (qe.date_expire IS NULL OR qe.date_expire >= ?)
				and qe.owning_agent_id in ({$agent_id_list})
			{$loan_type_sql}
			" . $agent_queue->getSortOrder() . "
			LIMIT {$max_report_retrieval_rows}
		";
		
		$st = DB_Util_1::queryPrepared(
			ECash::getMasterDb(),
			$query,
			array(
				$agent_queue->model->queue_id,
				date("Y-m-d H:i:s"),
				date("Y-m-d H:i:s")
			)
		);

	if( $st->rowCount() == $max_report_retrieval_rows )
		return false;

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			// Grab the company name out of the row
			$company_name = $row['company_name'];
			// Formatting
			if(NULL === $row["date_expiration"])
			{
				$row["date_expiration"] = "Never";
			}
			else
			{
				$row["date_expiration"] = "C.O.B. ".date('D M. jS, Y',strtotime($row["date_expiration"]));
			}
			if(NULL === $row["date_available"])
			{
				$row["date_available"] = "Not Set";
			}
			else
			{
				$row["date_available"] = date('g:i:s A D M. jS, Y',strtotime($row["date_available"]));
			}
			$row["name_first"] = ucfirst($row["name_first"]);
			$row["name_last"] = ucfirst($row["name_last"]);
			
			// If you want to be able to link the column, you need this
			$this->Get_Module_Mode($row);
			
			// Pass the data out by company
			if(in_array($row['company_id'], $companies))
			{
				$data[$company_name][] = $row;
			}
		}
	
        // Determine how long it take to do this
        $this->timer->stopTimer(self::$TIMER_NAME);

        // Return the data they want
        return($data);
    }
}

?>
