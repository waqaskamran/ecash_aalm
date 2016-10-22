<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once(SERVER_MODULE_DIR . "reporting/report_generic.class.php");

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
			$this->search_query = new Return_Item_Summary_Report_Query($this->server);

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
			  'loan_type'       => $this->request->loan_type,
			  'ach_batch_company'       	=> $this->request->ach_batch_company,
			);

			$_SESSION['reports']['return_item_summary']['report_data'] = new stdClass();
			$_SESSION['reports']['return_item_summary']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['return_item_summary']['url_data'] = array('name' => 'Return Item Summary', 'link' => '/?module=reporting&mode=return_item_summary');

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

			$data->search_results = $this->search_query->Fetch_Return_Item_Summary_Data( $start_date_YYYYMMDD,
											     $end_date_YYYYMMDD,
											     $this->request->loan_type,
											     $this->request->company_id,
											     $this->request->ach_batch_company);
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

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['return_item_summary']['report_data'] = $data;
	}
}

class Return_Item_Summary_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Return Item Summary Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);

		$this->Add_Status_Id('second_tier',   array('pending',    			'external_collections', '*root'));
		$this->Add_Status_Id('recovered',     array('recovered',  			'external_collections', '*root'));
		$this->Add_Status_Id('sent',          array('sent',       			'external_collections', '*root'));
		$this->Add_Status_Id('collnew',       array('new',        			'collections', 	'customer', '*root'));
		$this->Add_Status_Id('colldeq',       array('indef_dequeue',		'collections', 	'customer', '*root'));
		$this->Add_Status_Id('arrng_failed',  array('arrangements_failed', 	'arrangements', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('current',       array('current',    			'arrangements', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('hold',          array('hold',       			'arrangements', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('in_bankruptcy', array('dequeued',   			'bankruptcy',   'collections', 'customer', '*root'));
		$this->Add_Status_Id('bankruptcy',    array('queued',     			'bankruptcy',   'collections', 'customer', '*root'));
		$this->Add_Status_Id('unverified',    array('unverified', 			'bankruptcy',   'collections', 'customer', '*root'));
		$this->Add_Status_Id('verified',      array('verified',   			'bankruptcy',   'collections', 'customer', '*root'));
		$this->Add_Status_Id('in_contact',    array('dequeued',   			'contact',      'collections', 'customer', '*root'));
		$this->Add_Status_Id('contact',       array('queued',     			'contact',      'collections', 'customer', '*root'));
		$this->Add_Status_Id('cfollowup',     array('follow_up',  			'contact',      'collections', 'customer', '*root'));
		$this->Add_Status_Id('qcready',       array('ready',      			'quickcheck',   'collections', 'customer', '*root'));
		$this->Add_Status_Id('qcsent',        array('sent',       			'quickcheck',   'collections', 'customer', '*root'));
		$this->Add_Status_Id('qcreturn',      array('return',      			'quickcheck',   'collections', 'customer', '*root'));
		$this->Add_Status_Id('qcarrange',     array('arrangements',       	'quickcheck',   'collections', 'customer', '*root'));
	}

	private function Get_Collections_Status_Ids()
	{
		return implode( ",", array($this->second_tier,
		                           $this->recovered,
		                           $this->sent,
		                           $this->arrng_failed,
		                           $this->current,
		                           $this->hold,
		                           $this->in_bankruptcy,
		                           $this->bankruptcy,
		                           $this->unverified,
		                           $this->verified,
		                           $this->in_contact,
		                           $this->contact,
		                           $this->cfollowup,
		                           $this->qcready,
		                           $this->qcsent,
		                           $this->collnew,
		                           $this->colldeq,
		                           $this->qcreturn,
		                           $this->qcarrange
		                          )
		              );
	}

	public function Fetch_Return_Item_Summary_Data($date_start, $date_end, $loan_type, $company_id, $ach_batch_company)
	{
		$this->timer->startTimer(self::$TIMER_NAME);
	

		// This is the format of the summary arrays.
		$empty_summary_row = array(
			'count' => 0,
			'credit' => 0,
			'debit' => 0);

		$data = array();
		$empty_company_summary = array(
			'principal' => $empty_summary_row,
			'fee' => $empty_summary_row,
			'service_charge' => $empty_summary_row,
			'other' => $empty_summary_row,
			'total' => $empty_summary_row,
			'code' => array(),
			'notes' => array(),
			'reattempts' => array(),
			'returns' => array());

		$max_report_retrieval_rows = $this->max_display_rows + 1;

		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		$company_list = $this->Format_Company_IDs($company_id);

		$collections_status_ids = $this->Get_Collections_Status_Ids();
		
		if (empty($ach_batch_company))
			$ach_batch_company_sql = "";
		else
			$ach_batch_company_sql = " AND ab.ach_provider_id = '{$ach_batch_company}'\n";

		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				upper(c.name_short) company_name,
				a.application_id application_id,
				a.application_status_id application_status_id,
				a.company_id company_id,
				a.name_last name_last,
				a.name_first name_first,
				ach.ach_date date_sent,
				arc.name_short code,
				lt.name_short as loan_type,
				CONCAT(arc.name_short, ' ', arc.name) reason,
				IF(ach.ach_type = 'debit', ach.amount, 0) debit,
				IF(ach.ach_type = 'credit', ach.amount, 0) credit,
				CASE
					WHEN
						ach.ach_type = 'credit' AND
						tt.name_short = 'loan_disbursement'
					THEN 'Adv'

					WHEN
						ach.ach_type = 'debit' AND
						ach.ach_id = (
							SELECT ach3.ach_id
							FROM ach AS ach3
							JOIN transaction_register AS tr3 ON tr3.ach_id = ach3.ach_id
							JOIN transaction_type AS tt3 ON tt3.transaction_type_id = tr3.transaction_type_id
							WHERE ach3.application_id = ach.application_id
							AND	ach3.company_id = ar.company_id
							AND ach3.ach_type = 'debit'
							AND tt3.name_short = 'payment_service_chg'
							ORDER BY ach3.date_created ASC
							LIMIT 1
					)
					THEN '1stSC'

					WHEN
						ach.ach_type = 'debit' AND
						a.application_id = (
							SELECT application_id
							FROM application a2
							WHERE
								a2.ssn = a.ssn AND
								a2.date_fund_actual IS NOT NULL AND
								a2.fund_actual IS NOT NULL AND
								a2.company_id = ar.company_id
							ORDER BY date_created ASC
							LIMIT 1
						) AND
						ach.ach_id = (
							SELECT ach_id
							FROM ach ach2
							WHERE ach2.application_id = ach.application_id
							AND	ach2.company_id = ar.company_id
							AND ach2.ach_type = 'debit'
							ORDER BY ach2.date_created ASC
							LIMIT 1
						)
					THEN '1stAP'

					WHEN
						ach.ach_type = 'debit' AND
						ar.date_request = (
							SELECT ar.date_request
							FROM
								ach ach2
								JOIN ach_report ar USING (ach_report_id)
							WHERE
								ach2.application_id = ach.application_id AND
								ach.ach_status =  'returned' AND
								ach2.company_id = ar.company_id
							ORDER BY ar.date_request ASC
							LIMIT 1
						)
					THEN '1stR'

					WHEN
						ach.ach_type = 'debit' AND
						a.application_status_id IN ({$collections_status_ids})
					THEN 'Coll'

					ELSE 'Other'
				END notes,
				IF(es.origin_id IS NULL, 'No', 'Yes') reattempt,
				IF(arc.is_fatal = 'yes', 'Y', 'N') fatal,
				(
					SELECT es.date_event
					FROM event_schedule AS es
					JOIN event_type AS et ON es.event_type_id = et.event_type_id
					WHERE es.application_id = ach.application_id
					AND et.name_short = 'loan_disbursement'
					ORDER BY es.date_created DESC
					LIMIT 1
				) as date_funded,
				(
					SELECT DATE_FORMAT(date_created, '%m/%d/%Y')
					FROM status_history AS sh
					JOIN application_status_flat AS asf ON asf.application_status_id = sh.application_status_id
					WHERE sh.application_id = ach.application_id
					AND asf.level0_name = 'Pending'
					ORDER BY date_created ASC
					LIMIT 1
				) as date_bought,
				apr.name AS ach_provider
			FROM
				ach_report ar
				JOIN company c USING (company_id)
				JOIN ach USING (ach_report_id)
				JOIN ach_return_code arc USING (ach_return_code_id)
				JOIN application a USING (application_id)
				JOIN loan_type lt USING (loan_type_id)
				JOIN transaction_register tr USING (ach_id)
				JOIN event_amount ea on (ea.transaction_register_id = tr.transaction_register_id)
				JOIN transaction_type tt USING (transaction_type_id)
				JOIN event_schedule es on (es.event_schedule_id = tr.event_schedule_id)
				JOIN
					ach_batch AS ab ON (ab.ach_batch_id = ach.ach_batch_id)
				JOIN
					ach_provider AS apr ON (apr.ach_provider_id = ab.ach_provider_id)
			WHERE
				ar.date_request BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
				{$ach_batch_company_sql}
				AND	lt.name_short IN ({$loan_type_list})
				AND	ar.company_id IN {$company_list}
			GROUP BY company_name, application_id, ach.ach_id
			LIMIT {$max_report_retrieval_rows}
		";

		//die($query);
		$st = $this->db->query($query);

		if( $st->rowCount() == $max_report_retrieval_rows )
			return false;

		
    	while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			// Need data as array( Company => array( 'colname' => 'data' ) )
			//   Do all data formatting here
			$company_name = strtoupper($row['company_name']);

			$this->Get_Module_Mode($row);

			if (empty($data['summary'][$company_name]))
			{
				$data['summary'][$company_name] = $empty_company_summary;
			}

			// We're making a variable reference here since we always reference the same part
			$company_summary = &$data['summary'][$company_name];

			// I think we're making negative debits positive here. Spec please?
			if ($row['debit'] < 0)
			{
				$row['debit'] = -(floatval($row['debit']));
			}

			// Count & sum of codes & notes
			foreach (array('code', 'notes') as $summary_item)
			{
				if (!isset($company_summary[$summary_item][$row[$summary_item]]))
				{
					$company_summary[$summary_item][$row[$summary_item]] = $empty_summary_row;
				}

				$company_summary[$summary_item][$row[$summary_item]]['count']++;
				$company_summary[$summary_item][$row[$summary_item]]['debit'] += $row['debit'];
				$company_summary[$summary_item][$row[$summary_item]]['credit'] += $row['credit'];
			}
			unset($row['code']);

			// Count reattempts
			if (!isset($company_summary['reattempts']))
			{
				$company_summary['reattempts'] = $empty_summary_row;
			}

			if ($row['reattempt'] == 'Yes')
			{
				$company_summary['reattempts']['count']++;
				$company_summary['reattempts']['debit'] += $row['debit'];
				$company_summary['reattempts']['credit'] += $row['credit'];
			}

			// Count fatal vs non fatal
			if (!isset($company_summary['returns'][$row['notes']]))
			{
				$company_summary['returns'][$row['notes']] = array(
					'fatal' => 0,
					'non fatal' => 0,
					'total' => 0);
			}

			$row_sum = $row['debit'] + $row['credit'];
			$fatal_column = ($row['fatal'] == 'Y') ? 'fatal' : 'non fatal';
			$company_summary['returns'][$row['notes']]['total'] += $row_sum;
			$company_summary['returns'][$row['notes']][$fatal_column] += $row_sum;

			$data[$company_name][] = $row;

			// Unset our variable reference to the original array.
			unset($company_summary);
		}

		//[#40273] get the event amount type summary total breakdowns
		//from a second query
		$query2 = "
		SELECT
				upper(c.name_short) company_name,
				a.application_id application_id,
				a.company_id company_id,
				sum(IF(ea.amount < 0, abs(ea.amount), 0)) debit,
				sum(IF(ea.amount > 0, ea.amount, 0)) credit,
				eat.name_short amount_type
			FROM
				ach_report ar
				JOIN ach USING (ach_report_id)
				JOIN company c on (c.company_id = ar.company_id)
				JOIN application a USING (application_id)
				JOIN loan_type lt USING (loan_type_id)
				JOIN transaction_register tr USING (ach_id)
				JOIN event_amount ea on (ea.transaction_register_id = tr.transaction_register_id)
				JOIN event_amount_type eat on (ea.event_amount_type_id = eat.event_amount_type_id)
				JOIN transaction_type tt USING (transaction_type_id)
				JOIN event_schedule es on (es.event_schedule_id = tr.event_schedule_id)
			WHERE
				ar.date_request BETWEEN '{$timestamp_start}' AND '{$timestamp_end}' 
				AND	lt.name_short IN ({$loan_type_list})
				AND	ar.company_id IN {$company_list}
			GROUP BY company_name, application_id, amount_type
			";

		//die($query);
		$st2 = $this->db->query($query2);
		
    	while ($row = $st2->fetch(PDO::FETCH_ASSOC))
		{
			$company_name = strtoupper($row['company_name']);
			$company_summary = &$data['summary'][$company_name];

			// Count & sum of principal, fees, service charges, and 'other' types
			$company_summary[$row['amount_type']]['count']++;
			$company_summary[$row['amount_type']]['debit'] += $row['debit'];
			$company_summary[$row['amount_type']]['credit'] += $row['credit'];
			$company_summary['total']['count']++;
			$company_summary['total']['debit'] += $row['debit'];
			$company_summary['total']['credit'] += $row['credit'];
			unset($company_summary);
		}
		
		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>
