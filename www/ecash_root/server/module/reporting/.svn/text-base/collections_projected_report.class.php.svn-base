<?php
// * @package Reporting

require_once(SERVER_MODULE_DIR."/reporting/report_generic.class.php");
require_once( SERVER_CODE_DIR . "base_report_query.class.php" );

class Report extends Report_Generic
{
	private $search_query;

	public function Generate_Report()
	{
		// Generate_Report() expects the following from the request form:
		//
		// criteria start_date YYYYMMDD
		// criteria end_date   YYYYMMDD
		// company_id
		//
		try
		{
			$this->search_query = new Customer_Collections_Projected_Report_Query($this->server);
	
			$data = new stdClass();
	
			// Save the report criteria
			$data->search_criteria = array('date' => time()

			);
	
			$_SESSION['reports']['collections_projected']['report_data'] = new stdClass();
			$_SESSION['reports']['collections_projected']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['collections_projected']['url_data'] = array('name' => 'Collections Projected', 'link' => '/?module=reporting&mode=collections_projected');
	
	

	
			$data->search_results = $this->search_query->Fetch_Data();
		}
		catch (Exception $e)
		{
			$data->search_message = $e->getMessage();
//			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// we need to prevent client from displaying too large of a result set, otherwise
		// the PHP memory limit could be exceeded;
		if( $data->search_results === false )
		{
			$data->search_message = $this->max_display_rows_error;
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['collections_projected']['report_data'] = $data;
	}
}

class Customer_Collections_Projected_Report_Query extends Base_Report_Query
{
        private static $TIMER_NAME = "Collections Projected Report Query";
        private $system_id;

        public function __construct(Server $server)
        {
                parent::__construct($server);

                $this->system_id = $server->system_id;

        }

        public function Fetch_Data()
        {
			$this->timer->startTimer( self::$TIMER_NAME );

			$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
			  		
  			$prev_status_list_one = array($asf->toId('active::servicing::customer::*root'), $asf->toId('past_due::servicing::customer::*root') , $asf->toId('new::collections::customer::*root'));
  			$curr_status_list_one = array($asf->toId('past_due::servicing::customer::*root') , $asf->toId('new::collections::customer::*root'));
  			$status_list_one = implode(',', $prev_status_list_one);
  			$status_list_two = implode(',', $curr_status_list_one);
  			
  			$prev_status_list_two = array($asf->toId('current::arrangements::collections::customer::*root'), $asf->toId('arrangements_failed::arrangements::collections::customer::*root'));
			$curr_status_list_two = array($asf->toId('follow_up::contact::collections::customer::*root'), $asf->toId('dequeued::contact::collections::customer::*root'), $asf->toId('queued::contact::collections::customer::*root'),
  							 $asf->toId('arrangements::quickcheck::collections::customer::*root'), $asf->toId('amortization::bankruptcy::collections::customer::*root'));

  			$status_list_three = implode(',', $prev_status_list_two);
  			$status_list_four = implode(',', $curr_status_list_two);
			$status_list_five = $status_list_three . ', ' . $status_list_four;

			$query = "
					SELECT
				        abs(base.amount_delinquient_first_returns) as amount_delinquient_first_returns,
				        abs(base.amount_delinquent_all_else) as amount_delinquent_all_else,
				        abs(SUM(base.amount_delinquient_first_returns + base.amount_delinquent_all_else)) AS 
total_delinquent,
				        abs(base.delinquent_first_returns_attempted) as delinquent_first_returns_attempted,
				        abs(base.delinquent_all_other_arranged_today) as delinquent_all_other_arranged_today,
				        abs(base.delinquent_previously_arranged) as delinquent_previously_arranged,
				        abs(SUM(base.delinquent_first_returns_attempted + base.delinquent_all_other_arranged_today 
+ base.delinquent_previously_arranged)) AS total,
				        abs(SUM((base.delinquent_first_returns_attempted + 
base.delinquent_all_other_arranged_today + base.delinquent_previously_arranged)*.35)) AS projected_cleared
				FROM
				( 
				   SELECT
				        --- COLUMN A
				        SUM(
				            IF( (d.current_status IN (" . $asf->toId('current::arrangements::collections::customer::*root') . ") AND d.prev_status IN ({$status_list_one}))
				            OR d.current_status IN ({$status_list_two}), d.sc_fee_pending_balance, 0)
				           ) AS amount_delinquient_first_returns,
				        --- COLUMN B
				        SUM(
				            IF( (d.current_status IN ({$status_list_three}) AND d.prev_status IN ({$status_list_five}))
				            OR d.current_status IN ($status_list_four), d.total_pending_balance, 0)
				           ) AS amount_delinquent_all_else,
				        --- COLUMN D
				        ABS(SUM(
				            IF( (d.current_status = " . $asf->toId('current::arrangements::collections::customer::*root') . " AND d.prev_status IN ({$status_list_one}))
				                OR d.current_status IN ({$status_list_two}), d.arranged_today, 0)
				           )) AS delinquent_first_returns_attempted,
				
				        --- COLUMN E
				        ABS(SUM(
				            IF( (d.current_status IN ({$status_list_three}) AND d.prev_status IN ({$status_list_five}))
				            OR d.current_status IN ({$status_list_four}), d.arranged_today, 0)
				           )) AS delinquent_all_other_arranged_today,
				        --- COLUMN F
				        ABS(SUM(
				            IF( d.current_status IN ({$status_list_five}), d.arranged_prev, 0)
				           )) AS delinquent_previously_arranged
				    FROM
				    (
				        SELECT
				            ea.application_id,
				            a.application_status_id as current_status,
				            (
				                SELECT sh.application_status_id
				                FROM status_history AS sh
				                WHERE sh.application_id = ea.application_id
				                ORDER BY status_history_id DESC LIMIT 1,1
				            ) AS prev_status,
				            SUM( IF( (tr.transaction_status = 'complete' OR transaction_status = 'pending') AND eat.name_short IN ('service_charge','fee'),  ea.amount, 0)) sc_fee_pending_balance,
				            SUM( IF( tr.transaction_status = 'complete' OR transaction_status = 'pending', ea.amount, 0)) total_pending_balance,
				            SUM( IF( es.date_event = CURDATE() AND es.date_created > CURDATE(), ea.amount, 0)) arranged_today,
				            SUM( IF( es.date_event = CURDATE() AND es.date_created < CURDATE(), ea.amount, 0)) arranged_prev
				        FROM event_amount AS ea
				        JOIN event_amount_type AS eat ON eat.event_amount_type_id = ea.event_amount_type_id
				        JOIN event_schedule AS es ON es.event_schedule_id = ea.event_schedule_id
				        JOIN application AS a ON ea.application_id = a.application_id
				        JOIN application_status_flat AS asf ON asf.application_status_id = a.application_status_id
				        LEFT JOIN transaction_register AS tr ON ea.transaction_register_id = tr.transaction_register_id
				        WHERE (asf.level0 = 'past_due' OR asf.level1 = 'collections' OR asf.level2 = 'collections')
				        GROUP BY ea.application_id
				        HAVING arranged_today <> 0 OR arranged_prev <> 0
				        ORDER BY current_status
				    ) AS d
				) AS base
                       
                ";


                $st = $this->db->query($query);

                $data = array();

				while($row = $st->fetch(PDO::FETCH_ASSOC))
				{
					$data['company'][] = $row;
				}

                $this->timer->stopTimer( self::$TIMER_NAME );
                return $data;
        }

}

?>
