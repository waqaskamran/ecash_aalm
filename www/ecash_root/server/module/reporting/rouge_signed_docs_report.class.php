<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once("report_generic.class.php");

class Report extends Report_Generic
{
	private $search_query;

	public function Generate_Report()
	{
		$this->search_query = new Rouge_Signed_Docs_Report_Query($this->server);

		// Generate_Report() expects the following from the request form:
		//
		// criteria start_date YYYYMMDD
		// criteria end_date   YYYYMMDD
		// company_id
		//

		try
		{
			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
			  'end_date_MM'     => $this->request->end_date_month,
			  'end_date_DD'     => $this->request->end_date_day,
			  'end_date_YYYY'   => $this->request->end_date_year,
			  'company_id'      => $this->request->company_id
			);

			$_SESSION['reports']['rouge_signed_docs']['report_data'] = new stdClass();
			$_SESSION['reports']['rouge_signed_docs']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['rouge_signed_docs']['url_data'] = array('name' => 'Tiff Funded Report', 'link' => '/?module=reporting&mode=rouge_signed_docs');

			// Start date
			$start_date_YYYY = $this->request->start_date_year;
			$start_date_MM	 = $this->request->start_date_month;
			$start_date_DD	 = $this->request->start_date_day;
			if(!checkdate($start_date_MM, $start_date_DD, $start_date_YYYY))
			{
				//return with no data
				$data->search_message = "Start Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			// End date
			$end_date_YYYY	 = $this->request->end_date_year;
			$end_date_MM	 = $this->request->end_date_month;
			$end_date_DD	 = $this->request->end_date_day;
			if(!checkdate($end_date_MM, $end_date_DD, $end_date_YYYY))
			{
				//return with no data
				$data->search_message = "End Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			$start_date_YYYYMMDD = 10000 * $start_date_YYYY	+ 100 * $start_date_MM + $start_date_DD;
			$end_date_YYYYMMDD	 = 10000 * $end_date_YYYY	+ 100 * $end_date_MM   + $end_date_DD;

			if($end_date_YYYYMMDD < $start_date_YYYYMMDD)
			{
				//return with no data
				$data->search_message = "End Date must not precede Start Date.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			$data->search_results = $this->search_query->Fetch_Rouge_Signed_Docs_Data( $start_date_YYYYMMDD,
				                                                                  $end_date_YYYYMMDD,
				                                                                  $this->request->company_id
				);
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
		$_SESSION['reports'][$this->report_name]['report_data'] = $data;
	}
}

class Rouge_Signed_Docs_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Tiff Funded Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Inactive Paid Status Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   int  $company_id company_id
	 * @returns array
	 */
	public function Fetch_Rouge_Signed_Docs_Data($start_date, $end_date, $company_id)
	{
		$this->timer->startTimer(self::$TIMER_NAME);

		$FILE = __FILE__;
		$METHOD = __METHOD__;
		$LINE = __LINE__;


		$query = <<<END_SQL
-- eCash 3.0, File: $FILE , Method: $METHOD, Line: $LINE
SELECT
UPPER(co.name_short) as name_short,
a.application_status_id,
a.company_id,
doc.application_id,
a.date_created as app_created,
appas.name as status_name
FROM document      AS doc
JOIN application   AS a     USING (application_id)
JOIN document_list AS dlist USING (document_list_id)
JOIN company       AS co    ON (co.company_id = a.company_id)
JOIN application_status AS appas USING (application_status_id)
WHERE co.company_id = $company_id
AND a.date_created BETWEEN {$start_date}000000 AND {$end_date}235900
AND (doc.document_id_ext != "" OR doc.signature_status = "signed")
AND dlist.company_id = a.company_id
AND dlist.document_list_id IN
				(
				    select
				        document_list_id
				    from
				       document_process as dp
				       join application_status as dpass using (application_status_id)
				   where
				       dpass.name = 'Approved'
				)
AND doc.application_id NOT IN
               (    SELECT sh.application_id
                   FROM   status_history AS sh
                   JOIN   application_status AS ass USING (application_status_id)
                   WHERE  sh.application_id = doc.application_id
                   AND    ass.name_short = 'active'
                   AND    sh.company_id = a.company_id
               )
GROUP BY doc.application_id
ORDER BY doc.application_id
END_SQL;

		$db = ECash::getSlaveDb();
		$st = $db->query($query);

		$order = 1;
		$data = array();

		while ($row = $db->fetch(PDO::FETCH_ASSOC));
		{
			if (!isset($data[$row['name_short']]))
			{
				$data[$row['name_short']] = array();
			}
			$this->Get_Module_Mode($row);
			$data[$row['name_short']][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);
		return $data;
	}
}

?>
