<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once("advanced_sort.1.php");
require_once(SQL_LIB_DIR."util.func.php");

class Report_Generic
{
	protected $server;
	protected $request;
	protected $report_name;
	protected $module_name;
	protected $max_display_rows;
	protected $max_display_rows_error;
    protected $db;
   
	private $temp_company_list;

	public function __construct(Server $server, $request, $module_name, $report_name)
	{
		$this->server = $server;
		$this->request = $request;
		$this->module_name = $module_name;
		$this->report_name = $report_name;

		$read_only_fields = ECash::getACL()->Get_Control_Info($server->agent_id, $server->company_id); //mantis:4416
		ECash::getTransport()->Set_Data((object) array('read_only_fields' => $read_only_fields)); //mantis:4416

		$this->max_display_rows = ECash::getConfig()->MAX_REPORT_DISPLAY_ROWS;
		$this->max_display_rows_error = "Your report would have more than {$this->max_display_rows} lines to display. Please narrow the date range.";

		$this->db = ECash::getSlaveDb();
	}

	public function Get_Prompt_Reference_Data()
	{
		$data = new stdClass();

		$data->prompt_reference_data = $this->Fetch_Allowed_Companies("prompt");
		$data->prompt_reference_agents = $this->Fetch_All_Agents();
		$data->auth_company_name = $this->Fetch_Allowed_Companies("name_short");
		$data->auth_company_id = $this->Fetch_Allowed_Companies("id");
		$data->prompt_statuses = $this->Get_Status_Leaves_Simple();
		$data->ach_providers = $this->getACHProviders(); //asm 80

		$data->loan_type_list = Base_Report_Query::Get_Loan_Type_Map($data->auth_company_id);

		if ($this->report_name == "status_overview")
		{
			$_SESSION['statuses'] = $this->Get_Status_Leaves();
		}

		// oh yay, specific report information in the 'Report_Generic' file...
		if( $this->report_name == "applicant_status" )
		{
			// Remember any changes to the status_ids must also be made in server/module/reporting/applicant_status_report.class.php
			//  && server/code/applicant_status_report_query.class.php
			$app_query = new Applicant_Status_Report_Query($this->server);
			$data->search_criteria = array(
				'status_ids'           => array( $app_query->withdrawn   => "Withdrawn",
				                                 $app_query->denied      => "Denied",
				                                 $app_query->transfer    => "Pending Transfer",
				                                 $app_query->paid        => "Inactive (Paid)",
				                                 $app_query->ecpending   => "Second Tier Ready",
				                                 $app_query->recovered   => "Inactive (Recovered)",
				                                 $app_query->sent        => "Second Tier Sent",
				                                 $app_query->active      => "Active",
				                                 $app_query->fund_failed => "Funding Failed",
				                                 $app_query->current     => "Made Arrangements",
				                                 $app_query->verified    => "Bankruptcy Verified",
				                                 $app_query->qcready     => "QC Ready",
				                                 $app_query->qcsent      => "QC Sent",
				                                 $app_query->approved    => "Approved",
				                                 $app_query->funded      => "Pre-Fund",
				                                 $app_query->past_due    => "Past Due",
				                                 $app_query->agree       => "Agree",
				                                 $app_query->disagree    => "Disagree",
				                                 $app_query->confirmed   => "Confirmed",
				                                 $app_query->cdeclined   => "Confirmed Decline",
				                                 $app_query->duplicate   => "Duplicate",
				                                 $app_query->pending     => "Pending",
				                                 $app_query->cdequeued   => "In Cashline",
				                                 $app_query->cpending    => "Pending Transfer",
				                                 $app_query->cqueued     => "Cashline",
				                                 $app_query->declined    => "Declined",
				                                 $app_query->afailed     => "Arrangements Failed",
				                                 $app_query->ahold       => "Arrangements Hold",
				                                 $app_query->unverified  => "Bankruptcy Notification",
				                                 $app_query->cfollowup   => "Contact Followup",
				                                 $app_query->cnew        => "Collections New",
				                                 $app_query->in_contact  => "Collections Dequeued",
				                                 $app_query->contact     => "Collections Queued"
			                                       )
			                              );

		}

		$_SESSION['auth_company']['name'] = $data->auth_company_name;
		$_SESSION['auth_company']['id'] = $data->auth_company_id;

		ECash::getTransport()->Set_Data($data);
	}

	/**
	 * Only used to populate $_SESSION['statuses'] which in turn is only used
	 * for the Transaction Summary report... 
	 * @todo: KILL THIS!
	 * @return unknown_type
	 */
	public function Get_Status_Leaves ()
	{
		$statuses = array();
		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
            SELECT application_status_id, name, name_short
            FROM application_status
            WHERE application_status_id NOT IN
                (   SELECT application_status_parent_id
                    FROM application_status
                    WHERE active_status = 'active'
                    AND application_status_parent_id IS NOT NULL  )
            AND active_status = 'active'
            ORDER BY name";

		$st = $this->db->query($query);

		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$statuses[$row->application_status_id]['id'] = $row->application_status_id;
			$statuses[$row->application_status_id]['name_short'] = $row->name_short;
			$statuses[$row->application_status_id]['name'] = $row->name;
		}

		return $statuses;
	}
	
	public function Get_Status_Leaves_Simple ()
	{
		$statuses = array();
		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
            SELECT application_status_id, name, name_short
            FROM application_status
            WHERE application_status_id NOT IN
                (   SELECT application_status_parent_id
                    FROM application_status
                    WHERE active_status = 'active'
                    AND application_status_parent_id IS NOT NULL  )
            AND active_status = 'active'
	    AND name NOT LIKE '%preact%' 
	    AND name NOT LIKE '%fraud%' 
	    AND name NOT LIKE '%watch%' 
	    AND name NOT LIKE '%duplicate%'
            ORDER BY name";

		$st = $this->db->query($query);

		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$statuses[$row->application_status_id] = $row->name . " (" . $row->name_short . ")";
		}

		return $statuses;
	}

	//asm 80
	public function getACHProviders()
	{
		$providers = array();
		$query = "
			SELECT ach_provider_id,name
			FROM ach_provider
		";
		$st = $this->db->query($query);
		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$providers[$row->ach_provider_id] = $row->name;
		}

		return $providers;
	}


	public function Get_Last_Report()
	{
		$data = (object) array();

		if (!empty($_SESSION['reports'][$this->report_name]['report_data']->search_criteria))
		{
			$data->search_criteria = $_SESSION['reports'][$this->report_name]['report_data']->search_criteria;
		}

		if (!empty($_SESSION['reports'][$this->report_name]['report_data']->search_results))
		{
			$data->search_results = $_SESSION['reports'][$this->report_name]['report_data']->search_results;

			ECash::getTransport()->Add_Levels("report_results");
		}

		$data = $this->Sort_Data($data);

		ECash::getTransport()->Set_Data($data);
		if(!empty($_REQUEST['clear_session']))
		{
			$_SESSION['reports'][$this->report_name] = null;
		}
	}

	protected function Sort_Data($data)
	{
		// This report has been sorted before
		if( isset($_SESSION['reports'][$this->report_name]['last_sort']['direction']) )
		{
			// Same sort, swap direction
			if( isset($this->request->sort) && $this->request->sort == $_SESSION['reports'][$this->report_name]['last_sort']['col'])
			{
				$sort_data_col = $_SESSION['reports'][$this->report_name]['last_sort']['col'];
				$direction = ($_SESSION['reports'][$this->report_name]['last_sort']['direction'] == SORT_ASC ? SORT_DESC : SORT_ASC);
			}
			// New sort column on this report
			else if( isset($this->request->sort) )
			{
				$sort_data_col = $this->request->sort;
				$direction = SORT_ASC;
			}
			// Came back to this report or downloading, use current sort options
			else
			{
				$sort_data_col = $_SESSION['reports'][$this->report_name]['last_sort']['col'];
				$direction     = $_SESSION['reports'][$this->report_name]['last_sort']['direction'];
			}

			if(isset($this->request->sortDir))
			{
				$direction = ($this->request->sortDir == "DESC") ? SORT_DESC : SORT_ASC;
			}
		}
		// First sort for this report
		else if( isset($this->request->sort) )
		{
			$sort_data_col = $this->request->sort;
			$direction = ($this->request->sortDir == "DESC") ? SORT_DESC : SORT_ASC;
		}
		// No sorting necessary
		else
		{
			return $data;
		}

		$_SESSION['reports'][$this->report_name]['last_sort']['col']       = $sort_data_col;
		$_SESSION['reports'][$this->report_name]['last_sort']['direction'] = $direction;

		if (!isset($data->search_results))
			return $data;

		// First sort the data by company
		if(!empty($data->search_results))
		{
			ksort( $data->search_results, SORT_STRING );
		}
		
		// Now sort each company's data by the column and direction requested
		foreach( $data->search_results as $company_name => $company_data )
		{
			// this handles special case for Reporting/Applicant Reports/Score Report
			// where sorting by Date Funded
			if ($sort_data_col == 'fund_date' || $sort_data_col == 'next_due') 
			{
				$company_data = $this->mySQL_TS_To_Unix_TS($company_data, $sort_data_col);
			}
			$data->search_results[$company_name] = Advanced_Sort::Sort_Data($company_data, $sort_data_col, $direction);
			if ($sort_data_col == 'fund_date' || $sort_data_col == 'next_due') 
			{
				$data->search_results[$company_name] = $this->Unix_TS_To_mySQL_TS($data->search_results[$company_name],$sort_data_col);			
			}		
		}	
				
		return $data;
	}

	public function Download_Report()
	{
		$data = new stdClass();

		// Mantis:4324 - Use a business rule value to decide whether or not to force all  upper or
		// lowercase string values.
		$data->is_upper_case = $this->getUpperLowerPreference() ? TRUE : FALSE;

		if( !empty($_SESSION['reports'][$this->report_name]['report_data']->search_results) &&
		    !empty($_SESSION['reports'][$this->report_name]['report_data']->search_criteria))
		{
			$data->search_results  = $_SESSION['reports'][$this->report_name]['report_data']->search_results;
			$data->search_criteria = $_SESSION['reports'][$this->report_name]['report_data']->search_criteria;

			$data->prompt_reference_data = $this->Fetch_Allowed_Companies("prompt");
			$data->prompt_reference_agents = $this->Fetch_Allowed_Agents();

			$data = $this->Sort_Data($data);

			$data->download = TRUE;

			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels('download', $this->report_name);
		}
	}

	public function Download_XML_Report()
	{
		$data = new stdClass();

		// Mantis:4324 - Use a business rule value to decide whether or not to force all  upper or
		// lowercase string values.
		$data->is_upper_case = $this->getUpperLowerPreference() ? TRUE : FALSE;

		if( !empty($_SESSION['reports'][$this->report_name]['report_data']->search_results) &&
		    !empty($_SESSION['reports'][$this->report_name]['report_data']->search_criteria))
		{
			$data->search_results  = $_SESSION['reports'][$this->report_name]['report_data']->search_results;
			$data->search_criteria = $_SESSION['reports'][$this->report_name]['report_data']->search_criteria;

			$data->prompt_reference_data = $this->Fetch_Allowed_Companies("prompt");
			$data->prompt_reference_agents = $this->Fetch_Allowed_Agents();

			$data = $this->Sort_Data($data);

			$data->download_xml_report = TRUE;

			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels('download', $this->report_name);
		}
	}
	
	/**
	 * Checks the company level rule set for the report export
	 * setting to determine if we should force upper or lower case
	 * values in the report export output. [Mantis 4324]
	 * 
	 * @return bool
	 */
	protected function getUpperLowerPreference()
	{
		$company_id = $this->server->company_id;
		$db = ECash::getMasterDb();
		$ebrc = new ECash_BusinessRulesCache($db);
		
		if($loan_type_id = $ebrc->Get_Loan_Type_For_Company(ECash::getCompany()->name_short, 'company_level'))
		{
			$rule_set_id = $ebrc->Get_Current_Rule_Set_Id($loan_type_id);
			$rules = $ebrc->Get_Rule_Set_Tree($rule_set_id);
			
			if(isset($rules['report_export']) 
				&& strtolower($rules['report_export']) === 'upper case')
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	protected function Fetch_Allowed_Companies($type = "id", $report_acl = "agent_reports")
	{
		// Valid lists that can be requested
		$valid_types = array("name","name_short","id", "prompt");

		// Default unknown types to id
		if( !in_array($type, $valid_types) )
		{
			$type = "id";
		}

		// Have we fetched this type in this object copy before?
		return isset($temp_company_list[$report_acl][$type]) ? $temp_company_list[$report_acl][$type]: $this->Generate_Allowed_List($type, $report_acl);
	}

	protected function Get_Dates(&$data)
	{
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
			return FALSE;
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
			return FALSE;
		}

		$start_date_YYYYMMDD = 10000 * $start_date_YYYY	+ 100 * $start_date_MM + $start_date_DD;
		$end_date_YYYYMMDD	 = 10000 * $end_date_YYYY	+ 100 * $end_date_MM   + $end_date_DD;

		if($end_date_YYYYMMDD < $start_date_YYYYMMDD)
		{
			//return with no data
			$data->search_message = "End Date must not precede Start Date.";
			ECash::getTransport()->Set_Data($data);			ECash::getTransport()->Add_Levels("message");
			return FALSE;
		}

		return array($start_date_YYYYMMDD, $end_date_YYYYMMDD);
	}

	private function Generate_Allowed_List($type, $report_acl)
	{
		$list = array();

		foreach($this->server->company_list as $company_id => $company)
		{
			// Allow access?
			if( ECash::getACL()->Acl_Access_Ok($report_acl, $company_id) )
			{
				switch($type)
				{
					case "id":
					$list[] = $company_id;
					break;

					case "prompt":
					$obj = (object) array();
					$obj->company_id = $company_id;
					$obj->company_name = $company['name_short'];
					$list[] = $obj;
					break;

					default:
					$list[] = $company[$type];
					break;
				}
			}
		}

		if( $type == "prompt" && count($list) > 1 )
		{
			$obj = (object) array();
			$obj->company_id = 0;
			$obj->company_name = "All";
			array_unshift($list, $obj);
		}

		// Save the list in memory in case we require it again
		$this->temp_company_list[$type] = $list;

		return $list;
	}

	protected function Fetch_Allowed_Agents()
	{
		$return = Get_Collections_Agents($this->server->company_id);
		return($return);
	}

	private function Fetch_All_Agents()
	{
		$return = Get_All_Agents($this->server->company_id);
		return($return);
	}

	private function mySQL_TS_To_Unix_TS($company, $sort_col)
	{
		$mycompany = $company;
		foreach ($mycompany as $key => $value) 
		{
			if (isset ($value[$sort_col])) 
			{
				if (strlen($value[$sort_col]) < 1) 
				{
					$mycompany[$key][$sort_col] = 0; 
				}
				else 
				{
					$mycompany[$key][$sort_col] = strval(strtotime($value[$sort_col]));
				}
			}
		}

		return $mycompany;
	}

	private function Unix_TS_To_mySQL_TS($company, $sort_col)
	{
		$mycompany = $company;
		foreach ($mycompany as $key => $value) 
		{
			if (isset ($value[$sort_col])) 
			{
				// In some casess the date may never be
				// non-specified because the entire report is
				// selected by date boundaries, but I still want to cover
				// this possibility in case there is sorting by date
				// in another screen where the initial selection is not
				// by date - in that case the date could
				// be non-specified. (example: Project Payments Due)
				if ($value[$sort_col] == 0) 
				{
					$mycompany[$key][$sort_col] = "";
				}
				else 
				{
					$mycompany[$key][$sort_col] = date("n/j/Y",($value[$sort_col]));
				}
			}

		}

		return $mycompany;

	}
}

?>
