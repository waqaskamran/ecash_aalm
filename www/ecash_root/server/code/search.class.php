<?php
/**
 * @package Search
 */
require_once LIB_DIR . "/company_rules.class.php";

class Search
{
	private $server;
	private $request;
	private $search_limit;
	protected $module;
	protected $application_service_fields = array('ssn_last4', 'application_id', 'name_first', 'name_last', 'social_security_number', 'street', 'email', 'phone', 'zip_code', 'bank_account', 'customer_id', 'ip_address');
	protected $application_service_results = array();
	protected $is_application_service_disabled = FALSE;

	public function __construct(Server $server, $request)
	{
		$this->server = $server;
		$this->request = $request;
		$this->module = ECash::getModule()->Get_Active_Module();
		$this->timer = ECash::getMonitoring()->getTimer();

		$search_limit = ECash::getConfig()->search_limit;
 		if(empty($search_limit))
 		{
 			$search_limit = 500; // Set a limit of some sort if it's not configured.
 		}
 		$this->search_limit = $search_limit;
	}

	/**
	 * Performs a search and returns either the list of applications found or if only one then it
	 * just displays it.
	 *
	 * Revision History:
	 *     rlee - 	    2007-08-22 - Set application pulled location as 'search'
	 *     alexanderl - 2008-04-25 - Filtered search result by one expired [#11710]
	 *
	 * @param boolean $no_actions Flag to indicate if an agent action should be triggered.
	 * @return integer|NULL Number of results found.
	 */
	public function Search($no_actions = false)
	{
		$this->timer->startTimer('searching_for_application');

		if (empty($this->request->criteria_type_1))
		{
			$this->request->criteria_type_1 = 'application_id';
		}
		
		/* search expects the following from the request form:
		//
		// criteria_type_1 (application_id/name_last/name_first/social_security_number)
		// search_deliminator_1 (is/contains/starts_with) -- in honor of brilliance
		// search_criteria_1
		//
		// optionally:
		// criteria_type_2
		// search_deliminator_2
		// search_criteria_2
		//
		*/

		$this->search_options = array(
			'balance_accounts' => !empty($this->request->search_option),
			'criteria' => array(),
		);

		$i = 1;
		$min = 2;
		do
		{
			if (!empty($this->request->{'criteria_type_' . $i}))
			{
				$this->search_options['criteria'][] = $criteria = array(
					'field' => $this->request->{'criteria_type_' . $i},
					'operator' => $this->request->{'search_deliminator_' . $i},
					'value' => $this->request->{'search_criteria_' . $i},
				);
			}

			$i++;
		} while (!empty($this->request->{'criteria_type_' . $i}) || $i <= $min);

		$data = new stdClass;
		$data->search_results = array();

		$app_service_did_run = $this->applicationServiceSearch($data, $this->search_limit);

		$db = ECash::getMasterDb();

		// if the app service search hasn't run yet, or it has run and found results. don't search if the app service didnt find anything
		if (!$app_service_did_run || ($app_service_did_run && count($this->application_service_results) > 0 && count($this->application_service_results) <= $this->search_limit))
		{
		//	print_r($this->application_service_results);
			if (ECash::getACL()->Acl_Check_For_Access(Array('loan_servicing', 'restriced_access')))
			{
				$this->application_service_results = $this->stripRestictedAps($this->application_service_results);
			}

			try
			{
				ECash_DB_Util::generateTempTableFromArray(
					$db,
					'application_id_list',
					$this->application_service_results,
					array(
						'application_id' => 'int(10) unsigned NOT NULL',
						'company_id' => 'int(10) unsigned NOT NULL',
						'loan_type_id' => 'int(10) unsigned NOT NULL',
						'customer_id' => 'int(10) unsigned NOT NULL',
						'ssn' => 'varchar(9) NOT NULL',
						'date_created' => 'timestamp NOT NULL'
					),
					'application_id'
				);
				$search = ECash::getFactory()->getClass('Application_Search');
				
				$search->getQueryBuilder()->setLimit($this->search_limit + 1);

				if ($app_service_did_run && count($this->application_service_results) > 0)
				{
					$app_ids = array();
					foreach ($this->application_service_results as $application)
					{
						$app_ids[] = $application->application_id;
					}
					$search->addCriteria('application_id', 'in', $app_ids);
				}
				foreach ($this->search_options['criteria'] as $criteria)
				{
					if ($this->is_application_service_disabled || !in_array($criteria['field'], $this->application_service_fields))
					{
						$search->addCriteria(
							$criteria['field'],
							$criteria['operator'],
							$criteria['value']
						);
					}
				}
	
				$base_search = clone $search;
				$base_search->getQueryBuilder()->setLimit($this->search_limit + 1);

				if ($this->search_options['balance_accounts'])
				{
					$search->addCriteria('balance_accounts', 'is', '1');
				}
	
				$search->setDb($db);
				$results = $search->getResults();
				if (count($results) == 0 && $this->search_options['balance_accounts'])
				{
					$base_search->setDb($db);
					$results = $base_search->getResults();
				}
				
				$data->search_results = array_merge($data->search_results, $results);
			}
			catch (UnexpectedValueException $e)
			{
				$data->search_message = $e->getMessage();
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels('search');
				$this->timer->stopTimer('searching_for_application');
				return;
			}
		}
		else
		{
			$data->search_results = $this->application_service_results;
		}
		if (!$app_service_did_run && !$this->is_application_service_disabled && count($data->search_results) > 0)
		{
			$this->applicationServicePostSearch($data, $this->search_limit);
		}
		if (count($this->application_service_results) > 0)
		{
			$this->intersectResults($this->application_service_results, $data);
		}


		$count = count($data->search_results);
		
		if ($count > $this->search_limit)
		{
			$data->search_message = DisplayMessage::get(array('report', 'max search results'), $this->search_limit);
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels('search');
		}
		// if there's exactly one result, show the data for that application
		elseif($count == 1)
		{
			$this->request->application_id = $data->search_results[0]->application_id;
			if($this->Show_Applicant($data->search_results[0]->application_id, $no_actions) === FALSE)
			{
				$count = 0;
				$data->search_results = array();
			}
		}
		else
		{
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels('search');
		}

		 $this->Save_Search_Data($data, $this->request->module, $this->request->mode);
		$this->timer->stopTimer('searching_for_application');

		return $count;
	}

	/**
	 * Saves search options not to lose them after the result is displayed
	 *
	 * Revision History:
	 *     alexanderl - 2008-04-08 - saved search_pending_option [#7997]
	 *     alexanderl - 2008-12-04 - saved search_criteria_3 [#21713]
	 *
	 * @param stdobject $data
	 * @param string $module
	 * @param string $mode
	 */
	private function Save_Search_Data($data, $module, $mode)
	{
		//save the criteria for display
		$data->search_criteria = array(
			'criteria_type_1'       => $this->request->criteria_type_1,
			'search_deliminator_1'	=> $this->request->search_deliminator_1,
			'search_criteria_1'     => $this->request->search_criteria_1,
			'criteria_type_2'       => $this->request->criteria_type_2,
			'search_deliminator_2'	=> $this->request->search_deliminator_2,
			'search_criteria_2'	=> $this->request->search_criteria_2,
			'criteria_type_3'       => $this->request->criteria_type_3,
			'search_deliminator_3'	=> $this->request->search_deliminator_3,
			'search_criteria_3'	=> $this->request->search_criteria_3,
			'search_option' 	=> !empty($this->request->search_option),
			'search_option_checked'	=> !empty($this->request->search_option) ? 'CHECKED' : '',
			'search_pending_option' => !empty($this->request->search_pending_option),			//#7997
			//We do not want to save 'all apps' state if we are in a quick search. It should always go back to checked.
			'search_pending_option_checked'	=> (!empty($this->request->search_pending_option) || !empty($this->request->quick_search)) ? 'CHECKED' : '', //#7997
		);

		if (strlen($this->request->criteria_type_2) == 0)
		{
			$data->search_criteria['search_criteria_2'] = '';
		}

		if (strlen($this->request->search_criteria_2) == 0)
		{
			$data->search_criteria['criteria_type_2'] = '';
		}

		if (strlen($this->request->criteria_type_3) == 0)
		{
			$data->search_criteria['search_criteria_3'] = '';
		}

		if (strlen($this->request->search_criteria_3) == 0)
		{
			$data->search_criteria['criteria_type_3'] = '';
		}

		$_SESSION['search_data'] = $data;

		$is_start_with_ssn_disabled = false;
		if (in_array("disable_document_links", $this->server->transport->Get_Data()->read_only_fields))
			$is_start_with_ssn_disabled = true;
		
		if ($this->request->criteria_type_1 == 'email' || $this->request->criteria_type_1 == 'phone'  
			|| $is_start_with_ssn_disabled && $this->request->criteria_type_1 == 'social_security_number')
		{
			$data->search_criteria['criteria_type_1'] = 'application_id';
			$data->search_criteria['search_criteria_1'] = '';
		}
		if ($this->request->criteria_type_2 == 'email' || $this->request->criteria_type_2 == 'phone'  
			|| $is_start_with_ssn_disabled && $this->request->criteria_type_2 == 'social_security_number')
		{
			$data->search_criteria['criteria_type_2'] = '';
			$data->search_criteria['search_criteria_2'] = '';
		}
	}

	public function Show_Applicant($application_id=null, $no_actions = false)	
	{
		if (is_null($application_id)) $application_id = $this->request->application_id;
		$app = ECash::getApplicationById($application_id);
		if($app->exists())
		{
			/**
			 * Multi-Company Search capability.  Enable company switching ONLY
			 * if Multi-Company is enabled.
			 */
			$multi_company = ECash::getConfig()->MULTI_COMPANY_ENABLED;
			if($multi_company === FALSE && $this->module != 'fraud')
			{
				if($app->company_id != ECash::getCompany()->company_id)
				{
					ECash::getLog()->Write("Can't pull application from a different company! [{$app->application_id}] {$app->company_id} != ".ECash::getCompany()->company_id);
					$this->Get_Last_Search();
					return FALSE;				
				}
			}
			else if($app->company_id != ECash::getCompany()->company_id)
			{
				$company = ECash::getFactory()->getCompanyById($app->company_id);
				ECash::setCompany($company);
			}

			// We need to track how this application was found
			if (!$no_actions && isset($_SESSION[$this->module.'_mode']))
			{
				//[#27459]
				$search_area = $_SESSION[$this->module.'_mode'];
				$search_action = "search_{$this->module}_{$search_area}";

				$agent = ECash::getAgent();
				$agent->getTracking()->add($search_action, $application_id);
			}
			
			$follow_up = Follow_Up::Get_Recent_Follow_Up_For_Application($application_id);
			if (isset($follow_up) && $follow_up->status == "pending")
			{
				$data = new stdClass();
				$data->javascript_on_load = "if(confirm('The follow-up time for application {$application_id} has been exceeded. Would you like to clear the follow-up now? FYI: You can clear the follow-up anytime from the follow-up info window that can be opened by clicking on the FC or FI icon.')) window.open('/?action=get_followup_info&application_id={$application_id}', 'followup_info', 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,copyhistory=no,width=450,height=200,left=150,top=150,screenX=150,screenY=150');";
				ECash::getTransport()->Set_Data($data);
			}
			
			$loan_data = new Loan_Data(ECash::getServer());
			$data = $loan_data->Fetch_Loan_All($application_id);

			ECash::getTransport()->Set_Data($data);
			return TRUE;
		}
		else
		{
			$this->Get_Last_Search();
			return FALSE;
		}
	}

	public function Get_Last_Search()
	{
		if (!empty($_SESSION['search_data']))
		{
			if (isset($this->request->sort))
			{
				include_once("advanced_sort.1.php");

				switch ($this->request->sort)
				{
					case "social":
						$sort_data_col = "ssn";
						break;

					case "status":
						$sort_data_col = "application_status";
						break;

					case "amount":
						$sort_data_col = "application_balance";
						break;

					default:
						if (empty($this->request->sort))
						{
							$this->request->sort = "name_last";
						}
						$sort_data_col = $this->request->sort;
				}

				$direction = SORT_ASC;
				$sort_string = "asc";

				if (isset($_SESSION['search']['last_sort']))
				{
					if ($_SESSION['search']['last_sort']['col'] == $sort_data_col)
					{
						if (isset($_SESSION['search']['last_sort']['direction']) && $_SESSION['search']['last_sort']['direction'] == "asc")
						{
							$direction = SORT_DESC;
							$sort_string = "desc";
						}
					}
				}
				$_SESSION['search']['last_sort']['col'] = $sort_data_col;
				$_SESSION['search']['last_sort']['direction'] = $sort_string;
				$_SESSION['search_data']->search_results = Advanced_Sort::Sort_Data($_SESSION['search_data']->search_results, $sort_data_col, $direction);
			}

			ECash::getTransport()->Set_Data($_SESSION['search_data']);
		}

		$this->server->transport->Add_Levels('search');
	}
	
	/**
	 * Performs a search against the application service and merges
	 * data into search results
	 *
	 * @param stdClass $data
	 * @param int $limit
	 * @return bool
	 */
	protected function applicationServiceSearch($data, $limit)
	{
		$criteria = array();
		foreach ($this->search_options['criteria'] as $c)
		{
			if (in_array($c['field'], $this->application_service_fields))
			{
				if($c['field'] == 'phone')
					$c['value'] = ereg_replace( '[^0-9]+', '', $c['value'] );
				$criteria[] = array('field' => $c['field'], 'strategy' => $c['operator'], 'searchCriteria' => trim($c['value']));
			}
		}
		
		// if we can find everything in the app service, run it first
		if (count($criteria) == count($this->search_options['criteria']))
		{
			$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
			$results = $app_client->applicationSearch($criteria, $limit + 1);
			
			if ($results === FALSE)
			{
				$this->is_application_service_disabled = TRUE;
				return FALSE;
			}
			else
			{
				$this->application_service_results = $results;
				return TRUE;
			}
		}
		else 
		{
			return FALSE;
		}
		
	}
	
	/**
	 * Performs a search against the application service and merges
	 * data into search results
	 *
	 * @param stdClass $data
	 * @param int $limit
	 * @return bool
	 */
	protected function applicationServicePostSearch($data, $limit)
	{
		$criteria = array();
		foreach ($this->search_options['criteria'] as $c)
		{
			if (in_array($c['field'], $this->application_service_fields))
			{
                                if($c['field'] == 'phone')
                                        $c['value'] = ereg_replace( '[^0-9]+', '', $c['value'] );

				$criteria[] = array('field' => $c['field'], 'strategy' => $c['operator'], 'searchCriteria' => trim($c['value']));
			}
		}
		
		if (count($data->search_results) > 0)
		{
			$app_ids = array();
			foreach ($data->search_results as $result)
			{
				$app_ids[] = $result->application_id;
			}
			$criteria[] = array('field' => 'application_id', 'strategy' => 'in', 'searchCriteria' => implode(',', $app_ids));
		}
		
		if (!empty($criteria))
		{
			$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
			$results = $app_client->applicationSearch($criteria, $limit);
			
			if ($results === FALSE)
			{
				$this->is_application_service_disabled = TRUE;
				return FALSE;
			}
			else
			{
				$this->application_service_results = $results;
				return TRUE;
			}
		}
		else 
		{
			return FALSE;
		}
		
	}
	
	/**
	 * Performs a search against the application service and merges
	 * data into search results
	 *
	 * @param stdClass $data
	 * @param int $limit
	 * @return bool
	 */
	protected function stripRestictedAps($ap_list)
	{
		$new_list = array();
		
		$restrict_dates = Company_Rules::Get_Config("restrict_application_access");
		$new_date = strtotime($restrict_dates["last_date_new_ap_access"]);
		$react_date = strtotime($restrict_dates["last_date_react_ap_access"]);
		
		foreach ($this->application_service_results as $app)
		{
			if (!($app->is_react) || ($app->is_react == 'no')) {
				if ($new_date > strtotime($app->date_fund_actual)){
					$new_list[] = $app;
				}
			} else {
				if ($react_date > strtotime($app->date_fund_actual)){
					$new_list[] = $app;
				}
			}
		}
		return $new_list;
	}
	
	/**
	 * Performs a search against the application service and merges
	 * data into search results
	 *
	 * @return void
	 */
	protected function intersectResults(array $results, $existing_data)
	{
		$existing_apps = array();
		foreach ($existing_data->search_results as $existing_result)
		{
			$existing_apps[$existing_result->application_id] = $existing_result;
		}
		
		$new_apps = array();
		foreach ($results as $new_result)
		{
			$new_apps[$new_result->application_id] = $new_result;
		}
		$shared_app_ids = array_intersect(array_keys($new_apps), array_keys($existing_apps));
		$merged_data = array();
		foreach ($shared_app_ids as $application_id)
		{
			$temp = $existing_apps[$application_id];
			foreach ($new_apps[$application_id] as $key => $value)
			{
				$temp->$key = $value;
			}
			
			if ($this->search_options['balance_accounts'] && isset($temp->application_balance) && $temp->application_balance == 0)
			{
				continue;
			}
			
			$merged_data[] = $temp;
		}
		
		$existing_data->search_results = $merged_data;
	}

}

?>
