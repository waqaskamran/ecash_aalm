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
			$search_query = new Reminder_Queue_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
					'company_id'      => $this->request->company_id,
					'agent_id'        => $this->request->agent_id,
					);

			// Copy the search criteria into the session, but don't use the $data
			// object because it will be used to store aggregate data
			$_SESSION['reports']['reminder_queue']['report_data'] = new stdClass();
			$_SESSION['reports']['reminder_queue']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['reminder_queue']['url_data'] = array('name' => 'Reminder Queue', 'link' => '/?module=reporting&mode=reminder_queue');
			$data->search_results = $search_query->Fetch_Report_Results( $data->search_criteria['company_id'] , $data->search_criteria['agent_id'] );
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
			$data->search_message = "Your report would have more than " . $this->max_display_rows . " lines to display. Please choose more selective criteria.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['reminder_queue']['report_data'] = $data;
	}
}

class Reminder_Queue_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "TITLE Report Query";

	public function Fetch_Report_Results( $company_id, $agent_id )
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

		if($company_id == 0)
		{
			$company_list = "'" . implode("','", $auth_company_ids) . "'";
		}
		else
		{
			$company_list = $company_id;
		}

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

		// Build a SQL list
		$agent_id_list = join(",",$agent_id);

		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ .
			
		"	
			SELECT
				upper(company.name_short) as company_name,
				company.company_id,
				application.application_id,
				application.name_last,
				application.name_first,
				application.application_status_id,
				event_schedule.event_schedule_id,
				agent_affiliation.affiliation_status,
				agent_affiliation.date_expiration,
				event_schedule.date_event,
				loan_type.name_short as loan_type,
				CONCAT(agent.name_first,' ',agent.name_last) as agent_name
			from 
				agent_affiliation
			LEFT JOIN 
				agent_affiliation_event_schedule on (agent_affiliation.agent_affiliation_id = agent_affiliation_event_schedule.agent_affiliation_id)


			JOIN
				agent on (agent_affiliation.agent_id = agent.agent_id)

			LEFT JOIN 
				event_schedule ON (agent_affiliation_event_schedule.event_schedule_id = event_schedule.event_schedule_id)
			JOIN
				application ON (agent_affiliation.application_id = application.application_id)
			JOIN 	company on (application.company_id = company.company_id)
			JOIN
				loan_type ON (loan_type.loan_type_id = application.loan_type_id)
			WHERE 
				agent_affiliation.date_expiration BETWEEN
						DATE_FORMAT(CURRENT_DATE(),'%Y-%m-%d 00:00:00') AND
						DATE_FORMAT(DATE_ADD(CURRENT_DATE(), INTERVAL 2 DAY),'%Y-%m-%d 23:59:59')	
			AND
				agent_affiliation.affiliation_status != 'expired'
			AND 
				agent_affiliation.agent_id IN ({$agent_id_list})	
			AND
				company.company_id IN ({$company_list})

			LIMIT
				{$max_report_retrieval_rows}
			";
		$query = preg_replace('/(^\s+--.*$)|(^\s+)/m','',$query);

		// Run query

		$st = $this->db->query($query);

		if( $st->rowCount() == $max_report_retrieval_rows )
			return false;

    while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			
			// Grab the company name out of the row
			$company_name = $row['company_name'];
			//unset($row['company_name']);
			
			// Clean up NULLs
			if(NULL === $row['date_event'])
			{
				$row['date_event'] = 'None';
			}

			// If you want to be able to link the column, you need this
			$this->Get_Module_Mode($row,false);
			//Take care of name casing
			if (isset($row['first'])) $row['first'] = ucfirst($row['first']);
			if (isset($row['last'])) $row['last'] = ucfirst($row['last']);
			
			$row['arranged'] = $row['event_schedule_id']?'Yes':'No';
			// Pass the data out by company
			$data[$company_name][] = $row;
		}

		// Determine how long it take to do this
		$this->timer->stopTimer(self::$TIMER_NAME);
		
		// Return the data they want
		return($data);
	}
}

?>
