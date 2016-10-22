<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once("report_generic.class.php");
require_once( SERVER_CODE_DIR . "base_report_query.class.php" );

class Report extends Report_Generic
{
	private $search_query;

	public function Generate_Report()
	{
		// Generate_Report() expects the following from the request form:
		//
		// company_id
		//
		try
		{
			$this->search_query = new Fraud_Full_Queue_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'company_id'      => $this->request->company_id,
			  'queue_name'       => $this->request->queue_name,
			);
	
			$_SESSION['reports']['fraud_full_queue']['report_data'] = new stdClass();
			$_SESSION['reports']['fraud_full_queue']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['fraud_performance']['url_data'] = array('name' => 'High Risk/Fraud Full Queue', 'link' => '/?module=reporting&mode=fraud_full_queue');
			$data->search_results = $this->search_query->Fetch_Fraud_Report_Data($this->request->queue_name,
																		     $this->request->company_id);
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
		if(!empty($data->search_results) && count($data->search_results) > $this->max_display_rows)
		{
			$data->search_message = "Your report would have more than " . $this->max_display_rows . " lines to display. Please narrow the date range.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['fraud_full_queue']['report_data'] = $data;
	}
}

class Fraud_Full_Queue_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Fraud Full Queue Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	public function Fetch_Fraud_Report_Data($queue_name, $company_id)
	{
		$this->timer->startTimer(self::$TIMER_NAME);

		/* @var $qm ECash_Queues_QueueManager */
		$qm = ECash::getFactory()->getQueueManager();
		if($queue_name == 'both')
		{
			$queue_tables_name = $qm->getQueue('high_risk_queue')->getQueueEntryTableName();
			$queue_name = "'high_risk_queue','fraud_queue'";
		} else {
			$queue_tables_name = $qm->getQueue($queue_name)->getQueueEntryTableName();
			$queue_name = "'{$queue_name}'";
		}

		$company_list = $this->Format_Company_IDs($company_id);

		$fetch_query = "
			-- eCash 3.5, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				UPPER(co.name_short) as company_name,
				co.company_id as company_id,
				nq.name,
				a.application_id AS application_id,
				a.application_status_id AS application_status_id,
				a.name_last as `last_name`,
				a.name_first as `first_name`,
		        a.street AS `home_street`,
		        a.city AS `home_city`,
		        a.county AS `home_county`,
        		a.state AS `home_state`,
		        a.zip_code AS `home_zip`,
		        lt.name_short AS loan_type,
        		a.phone_home AS `home_phone`,
		        a.employer_name AS `employer`,
        		(CASE WHEN a.phone_work_ext IS NOT NULL
		              THEN concat(a.phone_work, ' ext. ', a.phone_work_ext)
		              ELSE a.phone_work
         		END) as `employer_phone`,
		        a.income_monthly AS `income`,
		        (CASE WHEN a.paydate_model = 'dwpd'
		              THEN 'Bi-Weekly'
		              WHEN a.paydate_model = 'dw'
		              THEN 'Weekly'
		              WHEN a.paydate_model = 'dmdm'
		              THEN 'Twice Monthly'
		              WHEN a.paydate_model = 'wwdw'
		              THEN 'Twice Monthly'
		              WHEN a.paydate_model = 'dm'
		              THEN 'Monthly'
		              WHEN a.paydate_model = 'wdw'
		              THEN 'Monthly'
		              WHEN a.paydate_model = 'dwdm'
		              THEN 'Monthly'
		              ELSE 'Other'
			         END) as `pay_period`,
			        a.bank_name AS `bank_name`,
			        a.bank_aba AS `bank_aba`,
        			a.fund_actual AS `principal_amount`,
			        a.date_first_payment AS `first_due`,
			        a.email AS `email_address`,
			        a.ip_address AS `ip_address`,
			        nqe.date_queued AS `timestamp`,
					nq.name AS queue_name
			FROM application a
			INNER JOIN {$queue_tables_name} nqe on (a.application_id = nqe.related_id)
			INNER JOIN n_queue nq ON (nqe.queue_id = nq.queue_id)
			INNER JOIN company AS co ON (co.company_id = a.company_id)
			INNER JOIN application_status_flat AS asf ON (asf.application_status_id = a.application_status_id)
			JOIN loan_type lt ON (a.loan_type_id = lt.loan_type_id)
			WHERE nq.name_short in ({$queue_name})
            AND nqe.date_available <= NOW()
            AND     (nq.company_id IS NULL OR
                     nq.company_id IN $company_list)
            AND a.company_id IN {$company_list}

			ORDER BY a.application_id
			";

		//$this->log->Write($fetch_query);
		//echo "<!-- {$fetch_query} -->";

		$data = array();

		$st = $this->db->query($fetch_query);

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$this->Get_Module_Mode($row,false);
			$company_name = $row['company_name'];
			$data[$company_name][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>
