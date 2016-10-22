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

	public function __construct(Server $server, $request, $module_name, $report_name)
	{
		parent::__construct($server, $request, $module_name, $report_name);
		$this->search_query = new Datax_Underwriting_Report_Query($this->server);		
	}
	
	public function Generate_Report()
	{
		try
		{
			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'specific_date_MM'   => $this->request->specific_date_month,
			  'specific_date_DD'   => $this->request->specific_date_day,
			  'specific_date_YYYY' => $this->request->specific_date_year,
			);

			$_SESSION['reports']['datax_underwriting']['report_data'] = new stdClass();
			$_SESSION['reports']['datax_underwriting']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['datax_underwriting']['url_data'] = array('name' => 'Datax_Underwriting Report', 'link' => '/?module=reporting&mode=datax_underwriting');

			if( ! checkdate($data->search_criteria['specific_date_MM'],
			                $data->search_criteria['specific_date_DD'],
			                $data->search_criteria['specific_date_YYYY']) )
			{
				$data->search_message = "Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}
			
			$_SESSION['reports']['datax_underwriting']['report_data']->search_criteria['specific_date'] = $specific_date =
							$data->search_criteria['specific_date_YYYY'] . '-' .
			                $data->search_criteria['specific_date_MM'] . '-' .
			                $data->search_criteria['specific_date_DD'];

			if($this->search_query->Check_Datax_Underwriting_Data($specific_date) == 'TRUE')
			{
				$data->search_message = "Report available for download.";
				$data->dl_ready = TRUE;
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}
		}
		catch (Exception $e)
		{
			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		//do not save in session -- download only
		//$_SESSION['reports']['datax_underwriting']['report_data'] = $data;
	}

	public function Download_Report()
	{
		$data = new stdClass();

		$data->is_upper_case = $this->getUpperLowerPreference() ? TRUE : FALSE;

		if(!empty($_SESSION['reports'][$this->report_name]['report_data']->search_criteria['specific_date']))
		{		
			try
			{
				$data->search_results = $this->search_query->Fetch_Datax_Underwriting_Data($_SESSION['reports'][$this->report_name]['report_data']->search_criteria['specific_date']);
			}
			catch (Exception $e)
			{
				$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			$data->search_criteria = $_SESSION['reports'][$this->report_name]['report_data']->search_criteria;

			$data->prompt_reference_data = $this->Fetch_Allowed_Companies("prompt");
			$data->prompt_reference_agents = $this->Fetch_Allowed_Agents();

			$data->download = TRUE;

			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels('download', $this->report_name);
		}
	}	
}

class Datax_Underwriting_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Datax Underwriting Report Query";

	public function Fetch_Datax_Underwriting_Data($date)
	{
		$this->timer->startTimer(self::$TIMER_NAME);

		$query = "
		select
		uncompress(result) as csv
		from resolve_datax_underwriting_report
		where report_date = ?
		";

		$data = array();

		$db = ECash::getMasterDb();
		$st = $db->queryPrepared($query, array($date));

		//there should only be one row
		if($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$data['dl_data'] = $row['csv'];
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}

	public function Check_Datax_Underwriting_Data($date)
	{
		$query = "
		select
			'TRUE'
		from resolve_datax_underwriting_report
		where report_date = ?
		";
		
		$db = ECash::getMasterDb();
		return $db->querySingleValue($query, array($date));
	}	
}

?>
