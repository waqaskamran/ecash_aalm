<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once("report_generic.class.php");
require_once(LIB_DIR . "Ach/ach.class.php");

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
			$this->search_query = new Batch_Review_Report_Query($this->server);
	
			$data = new stdClass();
	
			// Save the report criteria
			$data->search_criteria = array(
			  'company_id'      => $this->request->company_id,
			);
	
			$_SESSION['reports']['batch_review']['report_data'] = new stdClass();
			$_SESSION['reports']['batch_review']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['batch_review']['url_data'] = array('name' => 'Batch Review', 'link' => '/?module=reporting&mode=batch_review');

			$data->search_results = $this->search_query->Fetch_Batch_Review_Data($this->request->company_id);
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
		$_SESSION['reports']['batch_review']['report_data'] = $data;
	}
}

class Batch_Review_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Batch Review Report Query";
	private $ach;
	private $pdc;

	public function __construct(Server $server)
	{
		parent::__construct($server);

		$this->ach = ACH::Get_ACH_Handler($server, 'batch');
		$holidays = Fetch_Holiday_List();
		$this->pdc = new Pay_Date_Calc_3($holidays);

		// These status ids are not in the default list
		$this->Add_Status_Id('withdrawn', array('withdrawn','applicant','*root'));
		$this->Add_Status_Id('denied',    array('denied',   'applicant','*root'));
	}

	public function Fetch_Batch_Review_Data($company_id)
	{
		$this->timer->startTimer( self::$TIMER_NAME );

		$data = array();

		if( $company_id > 0 )
		{
			$company_list = array($company_id);
		}
		elseif( ! empty($_SESSION['auth_company']['id']) &&
		   is_array($_SESSION['auth_company']['id']) &&
		   count($_SESSION['auth_company']['id']) > 0 )
		{
			$company_list = $_SESSION['auth_company']['id'];
		}
		else
			$company_list = array();

		$today = date("Y-m-d",strtotime("now"));
		$tomorrow = $this->pdc->Get_Next_Business_Day($today);

		foreach( $company_list as $company )
		{
			$this->ach->Set_Company($company);
			$company_short = strtoupper($this->ach->Get_Company_Abbrev());
			$close_time = $this->ach->Get_Closing_Timestamp($today);

			if ($close_time)
			{
				// generate our results, and sort them if required
				$batchlist = $this->ach->Preview_ACH_Batches($tomorrow);

				if ($batchlist !== false)
				{
					$data[$company_short] = $batchlist;
					
					for($i=0;$i<count($data[$company_short]);$i++)
					{
						$data[$company_short][$i]['company_name'] = $company_short;
						// Can't do Get_Module_Mode, how about a hack instead?
						$data[$company_short][$i]['module'] = 'loan_servicing';
						$data[$company_short][$i]['mode']   = 'customer_service';

					}
				}	
				else
					$data[$company_short]['message'] = "There are no entries to send at this time.";
			}
			else
				$data[$company_short]['message'] = "You need to close out the business day in order to review the ACH batch for this company.";
		}

		//echo "<pre>data:\n";
		//print_r($data);
		//exit;

		$this->timer->stopTimer( self::$TIMER_NAME );

		return $data;
	}
}

?>
