<?php
/**
 * Represents an application.
 *
 * @author stephan.soileau <stephan.soileau@sellingsource.com>
 */
class ECash_VendorAPI_Application implements VendorAPI_IApplication
{
	/**
	 *
	 * @var ECash_Models_Application
	 */
	protected $application;

	/**
	 * @var array
	 */
	protected $changed_data = array();

	/**
	 *
	 * @var ECash_IQualify
	 */
	protected $qualify;

	/**
	 *
	 * @var ECash_Factory
	 */
	protected $factory;

	/**
	 *
	 * @var VendorAPI_StatProClient
	 */
	protected $stat_client;

	/**
	 * @var VendorAPI_StateObject
	 */
	protected $state;

	/**
	 * Non-persistent data
	 * @var Array
	 */
	protected $post_data;

	/**
	 * The eCash Business rules for this application / loan_type
	 * @ varECash_BusinessRules
	 */
	protected $business_rules;

	/**
	 * @var WebServices_Client_AppClient
	 */
	protected $application_client;

	static protected $timestamp_columns = array(
		'date_modified', 'date_created', 'date_application_status_set',
		'date_next_contact', 'date_fund_estimated', 'date_fund_actual',
		'date_first_payment', 'income_date_soap_1', 'income_date_soap_2',
		'last_paydate', 'date_hire', 'residence_start_date', 'banking_start_date'
	);

	public function __construct(
		VendorAPI_IModelPersistor $persistor,
		ECash_Models_Application $application,
		VendorAPI_StateObject $state_object,
		VendorAPI_IQualify $qualify,
		ECash_Factory $factory,
		VendorAPI_StatProClient $stat_client,
		WebServices_Client_AppClient $app_client
	)
	{
		$this->persistor   = $persistor;
		$this->qualify     = $qualify;
		$this->application = $application;
		$this->state = $state_object;
		$this->factory = $factory;
		$this->stat_client = $stat_client;
		$this->post_data = array();
		$this->application_client = $app_client;
	}

	/**
	 * Magic get method.. First it looks for a getMethod for
	 * the key (fund_amount becomes getFundAmount, etc..). Otherwise,
	 * it'll check the app model, then load the versioned data from the
	 * state object and return what is more appropriate
	 *
	 * @param String $key
	 * @return mixed
	 */
	public function __get($key)
	{
		$method = 'get'.str_replace(' ', '', ucwords(str_replace('_',' ', $key)));
		if (method_exists($this, $method))
		{
			return $this->$method();
		}
		if (in_array($key, $this->application->getColumns()))
		{
			return $this->application->$key;
		}
	}

	/** IS ANYONE USING THESE? [JustinF] */
	protected function getVehicleYear()
	{
		return $this->getVehicleData('year');
	}

	protected function getVehicleModel()
	{
		return $this->getVehicleData('model');
	}

	protected function getVehicleMake()
	{
		return $this->getVehicleData('make');
	}

	protected function getVehicleMileage()
	{
		return $this->getVehicleData('mileage');
	}

	protected function getVehicleSeries()
	{
		return $this->getVehicleData('series');
	}

	protected function getVehicleVin()
	{
		return $this->getVehicleData('vin');
	}

	protected function getVehicleStyle()
	{
		return $this->getVehicleData('style');
	}

	protected function getVehicleData($col)
	{
		$vehicle = $this->persistor->loadBy(
			$this->factory->getModel('Vehicle'),
			array('application_id' => $this->application_id)
		);
		if ($vehicle !== FALSE)
		{
			return $vehicle->{$col};
		}
		if ($vehicle !== FALSE)
		{
			return $vehicle->{$col};
		}
 	}

	/**
	 * Checks for the most appropriate fund amount
	 * and returns that..
	 * @return Integer
	 */
	protected function getFundAmount()
	{
		$fields = array('fund_actual', 'fund_requested', 'fund_qualified');
		foreach ($fields as $field)
		{
			if (!empty($this->application->$field))
			{
				return $this->application->$field;
			}
		}
		return NULL;
	}

	/**
	 * Expected to return the template name of the loan document
	 * @return string
	 */
	public function getLoanDocumentTemplate()
	{
		return "Loan Document";
	}

	/**
	 * Returns the blackbox campaign the application was sold to
	 * @return string
	 */
	public function getCampaign()
	{
		$ci = reset($this->getCampaignInfo());
		if ($ci)
		{
			return $ci->campaign_name;
		}
	}

	public function getCampaignInfo($check_db = TRUE)
	{
		$ci_model = $this->factory->getModel('CampaignInfo');
		return $this->persistor->loadAllBy($ci_model, array('application_id' => $this->application_id), $check_db);
	}

	/**
	 * Get an array of assicative arrays of the status history from the current
	 * application. The arrays will contain kev/value pairs for name, the status
	 * chain, and created, the date/time the history item was created.
	 * If no items exist, an empty array will be retruned
	 * @return array Array of arrays with name and created indexes.  The name is
	 * the staus chain and the created is the created date/time
	 */
	public function getStatusHistory()
	{
		$history = array();
		$model = $this->factory->getModel('StatusHistory');
		$statuses = $this->persistor->loadAllBy($model, array('application_id' => $this->application_id));

		if ($statuses !== FALSE && count($statuses) > 0)
		{
			$ref_list = $this->factory->getReferenceList('ApplicationStatusFlat');
			foreach ($statuses as $status)
			{
				$status_name = $ref_list->toName($status->application_status_id);
				$history[] = array(
					"name" =>$status_name,
					"created" => $status->date_created);
			}
		}
		return $history;
	}

	/**
	 * Return the application id.
	 * @return Integer
	 */
	public function getApplicationId()
	{
		if (!is_numeric($this->application->application_id))
		{
			throw new RuntimeException("Could not load application id.");
		}
		return $this->application->application_id;
	}

	/**
	 * Grabs the id, then converts it to a string
	 * @return string
	 */
	public function getApplicationStatus()
	{
		$id = $this->application_status_id;
		$ref_list = $this->factory->getReferenceList('ApplicationStatusFlat');
		if (is_numeric($id))
		{
			return $ref_list->toName($id);
		}
		elseif (!empty($this->state->application->application_status))
		{
			$ref_id = $ref_list->toId($this->state->application->application_status);
			$this->application->application_status_id = $ref_id;
			$status = $this->state->application->application_status;
			return $status;
		}
		return FALSE;
	}

	/**
	 * Return the application status id
	 * @return Integer
	 */
	public function getApplicationStatusId()
	{
		if (!is_numeric($this->application->application_status_id))
		{
			$this->getApplicationStatus();
		}
		elseif (!empty($this->state->application->application_status))
		{
			unset($this->state->application->application_status);
		}
		return $this->application->application_status_id;
	}


	/**
	 * Returns an array of references
	 * @return array stdclass[]
	 */
	public function getPersonalReferences()
	{
		$references = $this->persistor->loadAllBy(
			$this->factory->getModel('PersonalReference'), array('application_id' => $this->application_id));
		$refs = array();
		foreach ($references as $ref)
		{
			$ref = $ref->getColumnData();
			$ref['phone'] = $ref['phone_home'];
			$ref['full_name'] = $ref['name_full'];
			$refs[] = $ref;
		}

		return $refs;
	}

	/**
	 * Set the state object to use for grabbing data
	 * and whatnots
	 *
	 * @param VendorAPI_StateObject $state
	 * @return void
	 */
	public function setStateObject(VendorAPI_StateObject $state)
	{
		$this->state = $state;
	}

	/**
	 * (non-PHPdoc)
	 * @see code/VendorAPI/VendorAPI_IApplication#getAmountIncrements()
	 */
	public function getAmountIncrements()
	{
		return $this->qualify->getAmountIncrements($this->fund_amount, $this->isReact());
	}

	/**
	 * (non-PHPdoc)
	 * @see code/VendorAPI/VendorAPI_IApplication#calculateQualifyInfo()
	 */
	public function calculateQualifyInfo($set = FALSE, $loan_amount = NULL, array $extra = array())
	{
		$data = $this->getQualifyArray($extra);
		$this->qualify->qualifyApplication($data, (is_numeric($loan_amount) ? $loan_amount : $this->fund_amount));
		try
		{
			// then try to requalify with the due date that's
			// potentially been modified by the agent -- if this date
			// is invalid, we keep the date calculated above
			if (!empty($data['date_first_payment']))
			{
				$this->qualify->calculateFinanceInfo(
					$this->qualify->getLoanAmount(),
					$this->qualify->getFundDateEstimate(),
					$data['date_first_payment']
				);
			}
		}
		catch (InvalidArgumentException $e)
		{
		}

		$return = $this->qualify->getQualifyInfo();

		if ($set)
		{
			$this->changeApplicationValue('date_fund_estimated', $return->getFundDateEstimate());
			$this->changeApplicationValue('date_first_payment', $return->getFirstPaymentDate());
			$this->changeApplicationValue('fund_qualified', $return->getLoanAmount());
			$this->changeApplicationValue('finance_charge', $return->getFinanceCharge());
			$this->changeApplicationValue('payment_total', $return->getTotalPayment());
			$this->changeApplicationValue('apr', $return->getAPR());
		}
		return $return;
	}

	public function saveApplicationInfoToAppService(Webservices_Client_AppClient $client)
	{
		$client->updateApplicationComplete($this->getApplicationId(), $this->changed_data);
	}



	public function getQualifyArray(array $data = array())
	{
		$dd = $this->income_direct_deposit;
		$dd = is_bool($dd) ? $dd : strcasecmp($dd, 'yes') == 0;
		$data['income_direct_deposit'] = $dd;
		$data['income_monthly'] = $this->income_monthly;
		$data['is_react'] = $this->isReact();
		$data['olp_process'] = $this->application->olp_process;
		$data['loan_type_name'] = $this->getLoanTypeName($this->loan_type_id);
		$data['income_frequency'] = $this->income_frequency;
		$data['paydate_model'] = $this->paydate_model;
		$data['last_paydate'] = $this->last_paydate === NULL ? NULL : date("Y-m-d", is_numeric($this->last_paydate) ? $this->last_paydate : strtotime($this->last_paydate));
		$data['day_of_week'] = $this->day_of_week;
		$data['day_of_month_1'] = $this->day_of_month_1;
		$data['day_of_month_2'] = $this->day_of_month_2;
		$data['week_1'] = $this->week_1;
		$data['week_2'] = $this->week_2;
		$data['date_first_payment'] = $this->application->date_first_payment;
		$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
		$raf = $app_client->getReactAffiliation($this->application_id);
		if ($data['is_react']
			&& $raf->application_id != NULL)
		{
			/**
			 * I've seen a both versions used before,
			 * setting both to be safe.
			 */
			$data['react_application_id'] = $raf->application_id;
			$data['react_app_id'] = $raf->application_id;

			/**
			 * Attempt to grab the number of paid applications
			 * which is required to get the right value from the loan_cap
			 * business rules.
			 */
			$application_data = $this->factory->getData('Application');
			$num_paid_apps = $application_data->getNumberPaidApplications($raf->application_id, $this->application->company_id);
            $prev_max_qualify = $application_data->getPrevMaxQualify($raf->application_id, $this->application->company_id);
			$data['num_paid_applications'] = $num_paid_apps;
			$data['prev_max_qualify'] = $prev_max_qualify;
		}
		else
		{
			$application_data = $this->factory->getData('Application');
			$num_paid_apps = $application_data->getNumberPaidApplicationsBySSN($this->application->ssn, $this->application->company_id);
			$data['num_paid_applications'] = $num_paid_apps;
			$data['prev_max_qualify'] = 0;
		}

		$rate_model = $this->factory->getModel('RateOverride');
		$rate_model->loadByKey($app->application_id);
		$data['rate_override'] = $rate_model->rate_override;

		$perf_response = $this->getDataXResponse();
		if($perf_response instanceof TSS_DataX_ILoanAmountResponse)
		{
			$data['idv_increase_eligible'] = $perf_response->getLoanAmountDecision();
		}

		$vehicle = $this->persistor->loadBy(
			$this->factory->getModel('Vehicle'),
			array('application_id' => $this->application_id)
		);
		if ($vehicle !== FALSE)
		{
			$data['vin'] = $vehicle->vin;
			$data['make'] = $vehicle->make;
			$data['year'] = $vehicle->year;
			$data['model'] = $vehicle->model;
			$data['style'] = $vehicle->style;
			$data['series'] = $vehicle->series;
			$data['mileage'] = $vehicle->mileage;
			$data['license_plate'] = $vehicle->license_plate;
			$data['color'] = $vehicle->color;
			$data['value'] = $vehicle->value;
			$data['title_state'] = $vehicle->title_state;
			//$data['type'] = $vehicle->type;
		}

		return $data;
	}

	/**
	 * Return the loan type name?
	 * @param Integer $loan_type_id
	 * @return string
	 */
	public function getLoanTypeName($loan_type_id = null)
	{
        if(empty($loan_type_id))
			$loan_type_id = $this->application->loan_type_id;

		$type = $this->findLoanType($loan_type_id);
		return $type->name;
	}

	public function getLoanTypeNameShort($loan_type_id = null)
	{
		if(empty($loan_type_id))
			$loan_type_id = $this->application->loan_type_id;

		$type = $this->findLoanType($loan_type_id);
		return $type->name_short;
	}

	/**
	 * Finds a loan type name
	 * @throws InvalidArgumentException
	 * @param $loan_type_id
	 * @return String
	 */
	public function findLoanType($loan_type_id)
	{
		if (is_numeric($loan_type_id))
		{

			$loan_type = $this->factory->getModel('LoanType');
			if ($loan_type->loadBy(array('loan_type_id' => $loan_type_id)))
			{
				return $loan_type;
			}
			else
			{
				throw new InvalidArgumentException('Failed to load a loan type with id ('.$loan_type_id.').');
			}
		}
		else
		{
			throw new InvalidArgumentException('No loan type id to find a loan type name ('.$loan_type_id.').');
		}
	}

	/**
	 * This grabs the campaign info that we need to use for the
	 * enterprise site config. Grabs it from the database, then checks
	 * the enterprise site id, and then checks the campaign info in the
	 * state object to override the promo/sub
	 * @return array
	 */
	public function getCampaignConfigInfo()
	{
		$data = $this->factory->getData('Application');
		$ci = $data->getCampaignInfo($this->application_id);

		if (isset($this->application->enterprise_site_id))
		{
			$site = $this->factory->getModel('Site');
			$site->loadByKey($this->application->enterprise_site_id);

			$ci['url'] = $site->name;
			$ci['license_key'] = $site->license_key;
		}

		if ($this->state->isPart('campaign_info'))
		{
			$campaign = array_pop($this->state->campaign_info->getData());
			$ci['promo_id'] = $campaign['promo_id'];
			$ci['promo_sub_code'] = $campaign['promo_sub_code'];
		}

		return $ci;
	}

	/**
	 * Returns an array of all the columns that exist
	 * on the application model
	 * @return array
	 */
	public function getModelColumns()
	{
		return $this->application->getColumns();
	}
	/**
	 * return columns that need to be timestamps
	 * @return array
	 */
	public function getTimestampColumns()
	{
		return self::$timestamp_columns;
	}
	public function addDocument(VendorAPI_DocumentData $document, VendorAPI_CallContext $context)
	{
		$doc = $this->factory->getDocumentClient(); 

		$dl = $this->factory->getReferenceModel('DocumentListRef');
		$dl->loadBy(array('document_list_id' => $document->getDocumentListId()));

		$data = array(
			'date_created' => date('Y-m-d H:i:s'),
			'application_id' => $this->application_id,
			'document_method' => 'olp',
			'transport_method' => 'web',
			'document_event_type' => 'sent',
			'archive_id' => $document->getDocumentId(),
			'document_list_id' => $document->getDocumentListId(),
			'agent_id' => $context->getApiAgentId(),
			'company_id' => $context->getCompanyId()
		);
		$results = $doc->saveDocument(
			$this->getApplicationId(), 
			$context->getCompanyId(), 
			$context->getApiAgentId(), 
			$document->getDocumentId(), 
			NULL, 
			NULL, 
			$dl->name_short, 
			'sent', 
			NULL, 
			'olp', 
			'web', 
			NULL, 
			''
		);

		$doc_model = $this->factory->getModel('Document');
		foreach ($data as $k => $v)
		{
			$doc_model->$k = $v;
		}
		$doc_model->document_id = $results->item->document_id;
		$this->persistor->save($doc_model);
	}

	/**
	 * Record the hash from a document preview so that it can be verified later
	 * @param string $content
	 * @param string $template_name
	 * @param VendorAPI_CallContext $context
	 * @return void
	 */
	public function recordDocumentPreview(VendorAPI_DocumentData $document, VendorAPI_CallContext $context)
	{
		$this->addDocumentHash($document, $context, TRUE);
		/* @todo apologize to the guy merging in model_persistor */
	}

	/**
	 * Expires a document hash so it can be ignored in later calls.
	 *
	 * @param VendorAPI_DocumentData $document
	 * @param VendorAPI_CallContext $context
	 */
	public function expireDocumentHash(VendorAPI_DocumentData $document, VendorAPI_CallContext $context)
	{
		$this->addDocumentHash($document, $context, FALSE);
	}

	/**
	 * Adds a document hash to the state object with a given status.
	 *
	 * @param VendorAPI_DocumentData $document
	 * @param VendorAPI_CallContext $context
	 * @param bool $is_active
	 */
	protected function addDocumentHash(VendorAPI_DocumentData $document, VendorAPI_CallContext $context, $is_active)
	{
		$data = array(
			'date_created' => time(),
			'application_id' => $this->application_id,
			'company_id' => $context->getCompanyId(),
			'document_list_id' => $document->getDocumentListId(),
			'hash' => $document->getHash(),
			'active_status' => $is_active ? 'active' : 'inactive',
		);
		$hash_model = $this->factory->getModel('DocumentHash');
		foreach ($data as $k => $v)
		{
			$hash_model->$k = $v;
		}
		$this->persistor->save($hash_model);

	}

	/**
	 * (non-PHPdoc)
	 * @see code/VendorAPI/VendorAPI_IApplication#addLoanAction()
	 */
	public function addLoanAction($la_client, $loan_action, VendorAPI_CallContext $context)
	{

        $id = $la_client->insert(array(
            'application_id' => $this->application_id,
            'loan_action' => $loan_action,
            'loan_action_section' => 'vendor_api',
            'application_status' => $this->getApplicationStatus(),
            'agent_id' => $context->getApiAgentId(),
        ));

        $la_model = $this->factory->getModel('LoanActions');
        $la_model->name_short = $loan_action;
        $la_model->description = $loan_action;
        $la_model->status = 'INACTIVE';
        $la_model->type = 'PRESCRIPTION';

        $lah_model = $this->factory->getModel('LoanActionHistory');
        $lah_model->loan_action_history_id = $id;
        $lah_model->loan_action_id = new VendorAPI_ReferenceColumn_Locator($la_model);
        $lah_model->loan_action_id->addLoadByMethod('loadBy', array('name_short' => $loan_action));
        $lah_model->application_id = $this->getApplicationId();
        $lah_model->application_status_id = $this->getApplicationStatusId();
        $lah_model->agent_id = $context->getApiAgentId();

        //$this->persistor->save($lah_model);
	}

	/**
	 * Return an id based on loan action
	 * @param string $name_short
	 * @return integer
	 */
	public function getLoanActionId($name_short)
	{
		$model = $this->factory->getModel('LoanActions');
		if ($model->loadBy(array('name_short' => $name_short)))
		{
			return $model->loan_action_id;
		}
		else
		{
			throw new RuntimeException("Failed to find loan action ($name_short)");
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see code/VendorAPI/VendorAPI_IApplication#getCfeContext()
	 */
	public function getCfeContext(VendorAPI_CallContext $api_context)
	{
		return new VendorAPI_CFE_ApplicationContext($this, $api_context);
	}

	/**
	 * Update the status
	 * @param $string
	 * @return unknown_type
	 */
	public function updateStatus($string, $agent_id)
	{
		$this->application->modifying_agent_id = $agent_id;
		if (!is_numeric($string))
		{
			$ref_list = $this->factory->getReferenceList('ApplicationStatusFlat');
			$id = $ref_list->toId($string);
		}
		$this->application->application_status_id = $id;

		// We tell the persistor to save, becasue we can potentially
		// update this more than once in a single run and all updates need
		// to sync
		$this->persistor->save($this->application);

		/* Update the application service */
		if ($this->getApplicationId())
		{
			$this->application_client->updateApplicationStatus($this->getApplicationId(), $agent_id, $string);
		}
	}

	/**
	 * Loop through loan actions in the state object and
	 * add them to the loan action history
	 * @return VOID
	 */
	public function handleLoanActions(array $loan_actions, VendorAPI_CallContext $context)
	{
		$la_client = ECash::getFactory()->getWebServiceFactory()->getWebService('loanaction');

		foreach ($loan_actions as $action)
		{
			$this->addLoanAction($la_client, $action, $context);
		}
	}

	/**
	 * handle hitting the triggers on this application
	 * @return boolean
	 */
	public function handleTriggers()
	{
		$has_hit_triggers = FALSE;
		if ($this->state->triggers instanceof VendorAPI_Blackbox_Triggers)
		{
			$stat = $this->state->triggers->getStatToHit();
			$has_hit_triggers = $this->state->triggers->hasHitTriggers();

			if (!empty($stat))
			{
				$this->stat_client->hitStat($stat, $this->state->track_key, $this->state->space_key);
			}
		}
		return $has_hit_triggers;
	}

	/**
	 * Returns true if this app has any loan actions, synched or
	 * unsynched.
	 *
	 * @return boolean
	 */
	public function hasLoanActions($agent_id)
	{
		if (isset($this->state->loan_actions)
			&& count($this->state->loan_actions))
		{
			return TRUE;
		}
		$actions = $this->persistor->loadAllBy($this->factory->getModel('LoanActionHistory'), array('application_id' => $this->application_id, 'agent_id' => $agent_id));
		return (count($actions) > 1);
	}

	/**
	 * Returns true if this app is a react
	 * @return boolean
	 */
	public function isReact()
	{
		$val = $this->application->is_react;
		return (is_bool($val) ? $val : strcasecmp($val, 'yes') == 0);
	}

	public function getPaydates()
	{
		return $this->qualify->getPaydates($this->getQualifyArray());
	}

	/**
	 * Returns true if this app is a title loan
	 * @return bool
	 */
	public function getIsTitleLoan()
	{
		return ($this->getVehicleVin() !== NULL);
	}

	/**
	 * Returns the ecash process type from the comapny
	 * @return unknown_type
	 */
	public function getECashProcessType()
	{
		return ECash::getCompany()->ecash_process_type;
	}

	/**
	 * Returns the hash for the given document type
	 * @param $doc_list_id
	 * @param $company_id
	 * @return unknown_type
	 */
	public function getDocumentHash($doc_list_id, $company_id)
	{
		$hash_list = $this->persistor->loadAllBy(
			$this->factory->getModel('DocumentHash'),
			array(
				'application_id' => $this->application_id,
				'document_list_id' => $doc_list_id,
				'company_id' => $company_id,
			)
		);

		$latest_model = NULL;
		foreach ($hash_list as $hash_model)
		{
			if ($latest_model === NULL || $hash_model->date_created > $latest_model->date_created)
			{
				$latest_model = $hash_model;
			}
		}

		if ($latest_model
			&& $latest_model->active_status == 'active')
		{
			return $latest_model->hash;
		}
		return NULL;
	}

	public function getData()
	{
		$app_data = array();
		foreach ($this->getModelColumns() as $col)
		{
			$app_data[$col] = $this->application->$col;
		}

		if (is_numeric($this->dob))
		{
			$app_data['dob'] = date('Y-m-d', $this->dob);
		}
		else
		{
			$app_data['dob'] = $this->dob;
		}
		
		$app_data['personal_reference'] = $this->getPersonalReferences();

		$ci = $this->getCampaignInfo();
		$first_campaign = reset($ci);
		$last_campaign = end($ci);
		$app_data['promo_id'] = $last_campaign->promo_id;
		$app_data['promo_sub_code'] = $last_campaign->promo_sub_code;
		$app_data['campaign_name'] = $last_campaign->campaign_name;
		$app_data['reservation_id'] = $first_campaign->reservation_id;

        if ($this->isTitleLoan())
        {
        	$app_data['vehicle_year'] = $this->getVehicleYear();
			$app_data['vehicle_make'] = $this->getVehicleMake();
			$app_data['vehicle_model'] = $this->getVehicleModel();
			$app_data['vehicle_series'] = $this->getVehicleSeries();
			$app_data['vehicle_style'] = $this->getVehicleStyle();
			$app_data['vehicle_mileage'] = $this->getVehicleMileage();
			$app_data['vehicle_vin'] = $this->getVehicleVin();
        }
        $app_data = array_merge($this->post_data, $app_data);
		return $app_data;
	}

	protected function isTitleLoan()
	{
		$make = $this->getVehicleMake();
		return !empty($make);
	}

	/**
	 * Marks the application as a react
	 *
	 * This inserts the react_affiliation row and sets the is_react flag.
	 *
	 * @param int $react_application_id
	 * @param VendorAPI_CallContext $context
	 * @param int $agent_id
	 * @return void
	 */
	public function setIsReact($react_application_id, VendorAPI_CallContext $context, $agent_id)
	{
		// Only ECash reacts pass a valid id in the post data
		// Default the value to the API agent ID for all other react calls
		if (empty($agent_id)) $agent_id = $context->getApiAgentId();

		$raf = $this->factory->getModel('ReactAffiliation');

		$exists = $this->persistor->loadBy(
			$raf,
			array(
				'company_id' => $context->getCompanyId(),
				'react_application_id' => $this->application_id,
			)
		);
		if (!$exists)
		{
			$raf->date_created = time();
			$raf->company_id = $context->getCompanyId();
			$raf->agent_id = $agent_id;
			$raf->application_id = $react_application_id;
			$raf->react_application_id = $this->application_id;
			$this->persistor->save($raf);
		}

		$this->changeApplicationValue('is_react', 'yes');
		$this->persistor->save($this->application);
	}

	/**
	 * Setting all the application data.
	 * @param array $data
	 * @return void
	 */
	public function setApplicationData(array $data)
	{
		foreach ($data as $col => $val)
		{
			$this->changeApplicationValue($col, $val);
		}
	}

	/**
	 * Sets the given column in the application model. This allows us to track changes
	 * through a request independently of the state object so we can begin to break that dependency.
	 *
	 * @param string $col
	 * @param mixed $val
	 */
	protected function changeApplicationValue($col, $val)
	{
		if ($col == 'last_paydate')
		{
			$val = strtotime($val);
		}

		if ($this->application->$col != $val)
		{
			if (in_array($col, array('date_first_payment')))
			{
				$this->changed_data[$col] = date('Y-m-d', $val);
			}
			else
			{
				$this->changed_data[$col] = $val;
			}
		}
		$this->application->$col = $val;
	}

	/**
	 * Adds a reference to this application
	 * @param VendorAPI_CallContext $context
	 * @param string $name
	 * @param string $phone
	 * @param string $relationship
	 * @return void
	 */
	public function addPersonalReference(VendorAPI_CallContext $context, $name, $phone, $relationship)
	{
		$model = $this->factory->getModel('PersonalReference');
		//[#41919] Prevent duplicate references on reacts (due to multiple submit react process)
		$loaded = $model->loadBy(array('name_full' => $name, 'phone_home' => $phone, 'relationship' => $relationship));
		if(!$loaded)
		{
			$model->company_id = $context->getCompanyId();
			$model->application_id = $this->application_id;
			$model->name_full = $name;
			$model->phone_home = $phone;
			$model->relationship = $relationship;
			$model->date_created = $this->getNow();
			$this->persistor->save($model);

			$this->application_client->updatePersonalReference(
				$this->getApplicationId(),
				NULL,
				$context->getCompanyId(),
				$name,
				$phone,
				$relationship,
				'do not contact',
				'unverified'
			);
		}
	}


	/**
	 * Adds a campaign info record to this application
	 *
	 * @param string $license_key
	 * @param string $site
	 * @param string $promo_id
	 * @param string $sub_code
	 * @param string $campaign
	 * @return void
	 */
	public function addCampaignInfo(VendorAPI_CallContext $context, $license_key, $site, $promo_id, $sub_code, $campaign, $reservation_id = NULL)
	{
		/* band-aid, not my fault */
		$temp = $site;

		$site = $this->factory->getModel('Site');
		$site->license_key = $license_key;
		$site->name = $temp;
		$site->active_status = 'active';

		$campaign_model = $this->factory->getModel('CampaignInfo');
		$campaign_model->application_id = $this->application_id;
		$campaign_model->company_id = $context->getCompanyId();
		$campaign_model->date_created = $this->getNow();
		$campaign_model->promo_id = $promo_id;
		$campaign_model->promo_sub_code = $sub_code;
		$campaign_model->site_id = new VendorAPI_ReferenceColumn_Locator($site);
		$campaign_model->site_id->addLoadByMethod('loadByLicenseKey', $license_key);
		$campaign_model->campaign_name = $campaign;
		$campaign_model->reservation_id = $reservation_id;
		$campaign_model->promo_sub_code = $sub_code;
		$this->persistor->save($campaign_model);

		$args = array(
			'application_id' => $this->getApplicationId(),
			'promo_id' => $promo_id,
			'promo_sub_code' => $sub_code,
			'campaign_name' => $campaign,
			'friendly_name' => $campaign,
			'site' => $temp,
			'license_key' => $license_key
		);

		$this->application_client->addCampaignInfo($args);
	}

	/**
	 * Return a timestmap of now
	 * @return Integer
	 */
	protected function getNow()
	{
		return time();
	}

	public function save(VendorAPI_IModelPersistor $persistor, $save_all)
	{
		if ($save_all)
		{
			if ($this->persistor instanceof VendorAPI_TemporaryPersistor)
			{
				$this->persistor->saveTo($persistor);
			}
		}
		else
		{
			$model_list = array(
				'Eventlog',
				'BureauInquiryFailed',
				'BureauInquiry'
			);
			foreach ($model_list as $model_type)
			{
				$model = $this->factory->getModel($model_type);
				$all = $this->persistor->loadAllBy($model, array(), FALSE);
				foreach ($all as $m)
				{
					$persistor->save($m);
				}
			}
		}
	}
		/**
	 * Updates all models with a new application_id
	 *
	 * @param Integer $application_id
	 * @return void
	 */
	public function updateApplicationId($application_id)
	{
		$this->application->application_id = $application_id;
		$this->persistor->updateApplicationId($application_id);
	}

	/**
	 * Creates new customer info for the application.
	 *
	 * @param int $customer_id
	 * @param string $login
	 * @param string $password
	 * @return void
	 */
	public function createCustomer($customer_id, $login, $password)
	{
		$customer = $this->factory->getModel('Customer');
		$customer->ssn = $this->application->ssn;
		$customer->company_id = $this->application->company_id;
		$customer->login = $login;
		$customer->password = $password;
		$customer->customer_id = $customer_id;
		$this->persistor->save($customer);
		$this->setCustomer($customer_id);
	}

	/**
	 * Assigns a customer id for the application
	 *
	 * @param int $customer_id
	 * @return void
	 */
	public function setCustomer($customer_id)
	{
		$this->changeApplicationValue('customer_id', $customer_id);
	}

	public function addPostData($key, $value)
	{
		$this->post_data[$key] = $value;
	}

	/**
	 * Gets business rules
	 *
	 * @return array
	 */
	public function getBusinessRules()
	{
		if (!$this->business_rules)
		{
			$br = new ECash_BusinessRules($this->factory->getDb());
			return $this->business_rules = $br->Get_Rule_Set_Tree($this->rule_set_id);
		}
		return $this->business_rules;
	}

	/**
	 * Hackish way to get the DataX Response
	 * Required in order to get the increase flag which is
	 * used to determine if we should increase the loan amount
	 * in the loan calc.
	 *
	 * @return TSS_DataX_IResponse
	 */
	protected function getDataXResponse()
	{
		$this->getBusinessRules();
		// If a response class name is defined in the business rules, use that class name
		if (isset($this->business_rules['datax_call_types'])
			&& is_array($this->business_rules['datax_call_types'])
			&& isset($this->business_rules['datax_call_types']['perf_response_class']))
		{
			$class = $this->business_rules['datax_call_types']['perf_response_class'];
		}
		// Otherwise default to the perf response class name
		else
		{
			$class = 'DataX_Responses_Perf';
		}
		$perf_obj =  $this->factory->getClass($class);

		$inquiry = $this->persistor->loadBy(
			$this->factory->getModel('BureauInquiry'),
			array('application_id' => $this->application_id)
		);

		if ($inquiry !== FALSE)
		{
			$perf_obj->parseXML($inquiry->received_package);
			return $perf_obj;
		}

		return FALSE;
	}

	/**
	 * Hits a stat on the active campaign and enterprise site.
	 */
	public function getSpaceKey(VendorAPI_IDriver $driver, VendorAPI_StatProClient $client)
	{
		// promo ID and sub code come from the most recent campaign
		$ci = $this->getCampaignInfo();
		$active = end($ci);

		// use the space key from the enterprise site
		$site = $this->factory->getModel('Site');
		$site->loadByKey($this->application->enterprise_site_id);

		$config = $driver->getSiteConfig($site->license_key, $ci->promo_id, $ci->promo_sub_code);
		return $client->setSpaceKeyFromCampaign(
			$config->page_id,
			$active->promo_id,
			$active->promo_sub_code
		);
	}
	
	/**
	 * Returns a track id from the state object
	 * or database.. whichever is near
	 * @return string
	 */
	public function getTrackId()
	{
		return $this->application->track_id;
	}
	
        public function addComment(VendorAPI_CallContext $context, $text, $type='standard', $related_table=NULL, $related_id=NULL, $visibility='public')
        {
                if (!in_array($visibility, array('public', 'private', 'hidden', NULL), TRUE))
                {
                        throw new Exception('Unknown comment visibility specified.');
                }

                if (!in_array($type, array('standard', 'withdraw', 'deny', 'followup', 'reverify', 'transaction', 'collection', 'notes', 'row', 'declined', 'ach_correction', 'dnl', 'high_risk', 'fraud', 'disposition', NULL), TRUE))
                {
                        throw new Exception('Unknown comment type specified.');
                }

                $comment = $this->factory->getModel('Comment');
                $comment->date_created = time(); // date('Y-m-d H:i:s');
                $comment->application_id = $this->getApplicationId();;
                $comment->company_id = $context->getCompanyId();
                $comment->agent_id = $context->getApiAgentId();
                $comment->source = 'loan agent';
                $comment->type = $type;
                $comment->related_key = $related_id;
                $comment->comment = $text;
                $comment->visibility = $visibility;
                $this->persistor->save($comment);

                return $comment;
        }


}
