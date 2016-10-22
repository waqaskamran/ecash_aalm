<?php

/**
 * added a new field to fetch_loan_all, the origin_url, that contains the site of origin instead of the enterprise site.[jeffd][IMPACT LIVE #11065]
 */

require_once("qualify.2.php");
require_once(LIB_DIR . "Application/FieldAttribute.class.php");
require_once(LIB_DIR . "Application/Contact.class.php");
require_once(LIB_DIR . "status_utility.class.php");
require_once(SERVER_CODE_DIR . "bureau_query.class.php");
require_once(SERVER_CODE_DIR . "paydate_handler.class.php");
require_once(SERVER_CODE_DIR . "vehicle_data.class.php");
require_once(SQL_LIB_DIR . "app_flags.class.php"); //mantis:6966
require_once(SQL_LIB_DIR . "application.func.php");
require_once(LIB_DIR.'AgentAffiliation.php');
require_once(LIB_DIR.'common_functions.php');
require_once(SQL_LIB_DIR . "app_mod_checks.func.php");
require_once(SQL_LIB_DIR . "comment.func.php");
require_once(SQL_LIB_DIR . "debt_company.func.php");
require_once(SQL_LIB_DIR . "do_not_loan.class.php"); //mantis:4360
require_once(SQL_LIB_DIR . "loan_actions.func.php");
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(SQL_LIB_DIR . "refi.func.php");
require_once(SQL_LIB_DIR . "util.func.php");
require_once(LIB_DIR . "PaymentArrangementException.php");
require_once(LIB_DIR . "Document/Document.class.php");
require_once(SQL_LIB_DIR . 'agent_affiliation.func.php');
require_once(ECASH_COMMON_DIR . 'ecash_api/loan_amount_calculator.class.php');

class Loan_Data
{
	private $server;
	private $company_id;
	private $log;
	private $idv_query;
	private $business_rules;

	/**
	 * Customer Object
	 *
	 * @var Customer
	 */
	private $customer;

	/**
	 * Do Not Loan Object - Mantis:4360
	 *
	 * @var Do_Not_Loan
	 */
	private $do_not_loan;

	/**
	 * Bureau Query Object
	 *
	 * @var Bureau_Query
	 */
	public $bureau_query;

	/**
	 * Pay Date Calc Object
	 *
	 * @var Pay_Date_Calc_3
	 */
	private $pdc;

	/**
	 * Paydate Handler Object
	 *
	 * @var Paydate_Handler
	 */
	private $paydate_handler;

	private $application_field_attributes;

	private $application_contact;

	public function __construct(Server $server)
	{
		$this->server = $server;
		$this->company_id = $server->company_id;
		$this->log = $server->log;
		$this->db = ECash::getMasterDb();
		$this->business_rules = new ECash_BusinessRulesCache($this->db);
		$this->application_field_attributes = new eCash_Application_FieldAttribute($this->db);
		$this->do_not_loan = new Do_Not_Loan($this->db);
		$this->bureau_query = new Bureau_Query($this->db, $this->log);
		$this->pdc = new Pay_Date_Calc_3(Fetch_Holiday_List());
		$this->paydate_handler = new Paydate_Handler();
	}

	public function Fetch_Loan_All($application_id, $set_td_lock = TRUE)
	{
		// Require this here since the functions are only used within this method
		require_once(SQL_LIB_DIR . "comment.func.php");
		require_once(SQL_LIB_DIR . "loan_actions.func.php");

		$application = ECash::getApplicationById($application_id, NULL, TRUE);
		if(! $application->exists())
		{
			throw new ECash_Application_NotFoundException("Application '{$application_id}' not found!");
		}

		$trans_obj = new stdClass();
		$application->getModel()->loadLegacyAll($application_id, $trans_obj, $this->server);
		if($trans_obj->application_id === NULL)
		{
			throw new ECash_Application_NotFoundException("Error reading Application Data for '{$application_id}'");
		}

		//Select the class being used for renewal/rollover/refinancing behavior 
		$renewal_class =  ECash::getFactory()->getRenewalClassByApplicationID($application_id);
		//Set the application as readonly for particular statuses
		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');

		// lib/common_functions.php
		$readonly_statuses = Get_Readonly_Statuses();

		// PLEASE CHECK TO SEE ITS NOT BROKEN [benb]
		//GForge [#20937] do not force override, sane defaults set in Get_Readonly_Statuses()
		//$readonly_statuses[] = $asf->toId('pending::prospect::*root');
		//$readonly_statuses[] = $asf->toId('confirmed::prospect::*root');
		//$readonly_statuses[] = $asf->toId('confirm_declined::prospect::*root');
		//end GForge [#20937]
		$readonly_statuses[] = $asf->toId('declined::prospect::*root');

		if(in_array($application->application_status_id, $readonly_statuses))
		{
			$application->setReadOnly(TRUE);
			$trans_obj->isReadOnly = TRUE;
		}

		// They must have a keyfile for using cards
		if (isset(ECash::getConfig()->PAYMENT_CARD_KEY))
		{
			// Fetch Payment Card Info.
			$card_info = ECash::getFactory()->getModel('CardInfoList');

			// This is needed for decrypting each card number
			$card_info->loadBy(array('application_id' => $trans_obj->application_id));
			$trans_obj->card_info = $card_info;

			$CardTypes = ECash::getFactory()->getModel('CardTypeList');
			$CardTypes->loadBy(array());

			$trans_obj->card_types = array();

			foreach($CardTypes as $CardType)
			{
				$trans_obj->card_types[$CardType->card_type_id] = $CardType;
			}

			// Fetch Payment Card Info.
			$card_info = ECash::getFactory()->getModel('CardInfoList');
			$card_info->loadBy(array('application_id' => $application->application_id));

			$trans_obj->card_info = $card_info;

			$trans_obj->active_card_saved = FALSE;
			$ci_model = ECash::getFactory()->getModel('CardInfo');
			$ci_array = $ci_model->loadAllBy(array(
								'application_id' => $application->application_id,
								'active_status' => 'active',
			));
			if (count($ci_array) > 0)
			{
				$trans_obj->active_card_saved = TRUE;
			}

			// Card Authorization doc
			$trans_obj->card_authorization_received = FALSE;
			$doc_list_model = ECash::getFactory()->getModel('DocumentList');
			$doc_list_model->loadBy(array(
							'company_id' => $this->company_id,
							'active_status' => 'active',
							'name_short' => 'Card Authorization',
			));

			$doc_model = ECash::getFactory()->getModel('Document');
			$doc_array = $doc_model->loadAllBy(array(
								'application_id' => $application->application_id,
								'company_id' => $this->company_id,
								'document_event_type' => 'received',
								'document_list_id' => $doc_list_model->document_list_id,
			));
			if (count($doc_array) > 0)
			{
				$trans_obj->card_authorization_received = TRUE;
			}
			
			//$trans_obj->may_use_card_schedule = mayUseCardSchedule($application->application_id, $this->company_id);
			$trans_obj->may_use_card_schedule = $trans_obj->active_card_saved && $trans_obj->card_authorization_received;
		}

		$trans_obj->price_point = $application->price_point;

		$trans_obj->business_rules = $application->getBusinessRules();

		$trans_obj->status			= $application->getStatus()->level0;
		$trans_obj->level0			= $application->getStatus()->level0;
		$trans_obj->level1			= $application->getStatus()->level1;
		$trans_obj->level2			= $application->getStatus()->level2;
		$trans_obj->level3			= $application->getStatus()->level3;
		$trans_obj->level4			= $application->getStatus()->level4;
		$trans_obj->level5			= $application->getStatus()->level5;

		// Check if application is watched
		$trans_obj->is_watched		= ($application->getAffiliations()->getCurrentAffiliation('watch', 'owner')) ? 'yes' : 'no';

		$flags = $application->getFlags();

		$trans_obj->is_card_schedule = FALSE;
		if($trans_obj->active_card_saved
		   && $trans_obj->card_authorization_received
		   && $flags->get('card_schedule')
		)
		{
			$trans_obj->is_card_schedule = TRUE;
		}
		
		$trans_obj->is_ach_schedule = FALSE;
		if(!$trans_obj->is_card_schedule)
		{
			//$trans_obj->is_ach_schedule = TRUE; //Uncomment
		}

		$trans_obj->available_flags = ECash::getFactory()->getReferenceList('FlagType');
		$trans_obj->app_flags = $flags->getAll();

 		// Get application flags object for this application
 		$flags = new Application_Flags($this->server, $application_id);
 		$trans_obj->flags = $flags->Get_Active_Flag_Array();
 	//	$trans_obj->available_flags =$flags->Get_Flag_Types();
 	//	$trans_obj->app_flags = $flags->Get_Application_Flags();

		/**
		 * Check for the rule loan type model to see if the loan type is 'Title'.  The default is 'Payday'.
		 */
		if(isset($trans_obj->business_rules['loan_type_model']) && $trans_obj->business_rules['loan_type_model'] == 'Title')
		{
			$vehicle_data = Vehicle_Data::fetchVehicleData($application_id);
			$trans_obj = (object) array_merge((array) $trans_obj, (array) $vehicle_data);
			$trans_obj->loan_type_model = 'Title';
		}
		else
		{
			$trans_obj->loan_type_model = $trans_obj->business_rules['loan_type_model'];
		}

		$trans_obj->application_id = $application_id;
		$trans_obj->company_id = $application->company_id;
		$trans_obj->ssn = $application->ssn;

 		if(is_null($this->customer))
		{
			//use the company_id from the application (incase the app is being loaded by fraud)
			// If there's no customer_id (Archive companies) then look up the apps by SSN & Company
			if($trans_obj->customer_id != NULL && $trans_obj->customer_id != 0)
			{
				$this->customer = ECash_Customer::getByCustomerId(ECash::getMasterDB(), $trans_obj->customer_id, $trans_obj->company_id);
 			}
 			else
 			{
				$this->customer = ECash_Customer::getBySSN(ECash::getMasterDB(), $trans_obj->ssn, $this->server->company_id);
 			}
 		}

		$trans_obj->application_list = $this->customer->getApplications();
		$this->Add_App_to_View_History($application->getModel());

		if($trans_obj->application_status_id === $asf->toId(("sent::external_collections::*root")))
		{
			$trans_obj->ext_collections_co = $application->getCollectionsCompany();
		}

		if ($trans_obj->banking_start_date)
		{
			$trans_obj->banking_duration = $this->getDurationTag($trans_obj->banking_start_date);
		}
		else
		{
			$trans_obj->banking_start_date = "";
			$trans_obj->banking_duration = "n/a";
		}

		if ($trans_obj->residence_start_date)
		{
			$trans_obj->residence_duration = $this->getDurationTag($trans_obj->residence_start_date);
		}
		else
		{
			$trans_obj->residence_start_date = "";
			$trans_obj->residence_duration = "n/a";
		}

		// mantis:1472
		if (isset($application->model->bank_aba) && $application->model->bank_aba != '')
		{
			$bank_obj = Get_Bank_Phone($application->bank_aba);

			if($bank_obj[0] != null)
			{
				$trans_obj->actual_bank = substr($bank_obj[0]->Institution_Name_Full, 0, 30); //mantis:5293
				$trans_obj->bank_phone = "(" . $bank_obj[0]->ACH_Contact_Area_Code . ") " .
					substr($bank_obj[0]->ACH_Contact_Phone_Number, 0, 3) . "-" .
					substr($bank_obj[0]->ACH_Contact_Phone_Number, 3, 4);

				if($bank_obj[0]->ACH_Contact_Extension != '')
				{
					$trans_obj->bank_phone .= " ext. " . $bank_obj[0]->ACH_Contact_Extension;
				}
			}
			else
			{
				$trans_obj->actual_bank = "Cannot retrieve with this ABA #";
				$trans_obj->bank_phone = "Cannot retrieve with this ABA #";
			}
		}
		else
		{
			$trans_obj->actual_bank = "ABA # is not set";
			$trans_obj->bank_phone = "ABA # is not set";
		}
		// end mantis:1472

		// get any associated agents
		$affiliation = $application->getAffiliations()->getCurrentAffiliation('collections', 'owner');
		$trans_obj->assoc_agent = !empty($affiliation) ? $affiliation->getAgent()->getModel() : NULL;

		// Add their references separately
		$trans_obj->references = $application->getPersonalReferences();

		// I don't think we need this anymore.
		if((is_array($trans_obj->references)) && (count($trans_obj->references) > 0))
		{
			// format the references for Edit::isChanged()
			for ($i = 0; $i < 2; $i++)
			{
				$name = "ref_name_" . ($i + 1);
				$phone = "ref_phone_" . ($i + 1);
				$relationship = "ref_relationship_" . ($i + 1);
				$trans_obj->$name = $trans_obj->references[$i]->name_full;
				$trans_obj->$phone = $trans_obj->references[$i]->phone_home;
				$trans_obj->$relationship = $trans_obj->references[$i]->relationship;
			}
		}

		$trans_obj->comments = ECash::getFactory()->getData('Comments')->getCommentDetails($application_id);

		/**
		 * Check against the Fraud Rules if the feature is enabled
		 */
		if(isset(ECash::getConfig()->USE_FRAUD_RULES) && ECash::getConfig()->USE_FRAUD_RULES === TRUE)
		{
			$fraud_stuff = ECash::getFactory()->getData('Fraud')->getFraudRulesAndFields($application_id);
			if(! empty($fraud_stuff))
			{
				foreach($fraud_stuff as $fraud_column => $fraud_value)
				{
					$trans_obj->{$fraud_column} = $fraud_value;
				}
			}
		}

		$trans_obj->delinquency_date = ECash::getFactory()->getData('Application')->getDelinquencyDate($application_id);

		// Get the paydates array from the current application in the application list
		$trans_obj->payperiod = $trans_obj->income_frequency;
		$pdh = new Paydate_Handler();
		
		$app_row = new stdclass();
		$columns = $application->getColumns();
		foreach($columns as $column)
		{
			$app_row->$column = $application->$column;
		}
		$pdh->Get_Model($app_row);
		$paydates = $pdh->Get_Paydates($app_row->model, 'Y-m-d');
		$trans_obj->income_frequency = $pdh->Get_Paydate_String($app_row->model);
		$trans_obj->model = $app_row;

		// For Debt Collects gather all the Debt Companies
		if(in_array(ECash::getModule()->Get_Active_Module(), array('loan_servicing', 'collections')))
		{
			if(ECash::getConfig()->USE_DEBT_CONSOLIDATION_PAYMENTS !== FALSE)
			{
				$trans_obj->debt_companies = Get_Debt_Companies();
			}
		}

		// DLH, 2005.11.16
		// If the date_fund_actual contains only zeros and separators (- or / or spaces) then it is considered not set.
		// If the date_fund_actual is not set then display the current date in its place because the current date
		// is the earliest possible date it could be funded.  If date_fund_actual is not set then display a reasonable
		// due date in place of the date_first_payment.  In both of these cases we will display "estimated" next
		// to the fields.  (I'm creating new fields with "_display" suffix because I can't be positive the original
		// fields are not used for other purposes.  Also, I'm translating - and 0 to spaces and trimming and then
		// comparing to '' rather than simply comparing to "00-00-0000" because this should work for a null date_fund_actual
		// and should continue to work even if the date format is changed to yyyy/mm/dd or something else.)

		/**
		 * The above comment is now handled in the base application model [JustinF]
		 *
		 * @TODO date_now_strtotime is just a workaround until we can
		 * remove all date format required code from business logic
		 * *and* javascript [JustinF]
		 */

		$date_normalizer = ECash::getFactory()->getDateNormalizer();
		// If the batch has closed, we want to show the next business day
		$date_now = time();
		if(Has_Batch_Closed(ECash::getCompany()->company_id))
		{
			$date_now = $date_normalizer->advanceBusinessDays(time(), 1);
		}

		if ( $trans_obj->date_fund_actual === NULL )
		{
			$trans_obj->date_fund_actual_display = date('m-d-Y', $date_now);
			$trans_obj->date_fund_actual_hidden = $date_now;  // for database update

			$appstatus_not_requiring_due_date = $this->Get_IDs_Not_Requiring_Due_Dates();

			if ( in_array( $trans_obj->application_status_id, $appstatus_not_requiring_due_date ) )
			{
				// put blank or 'n/a' into due date field
				$trans_obj->date_first_payment_display = 'n/a';
				$trans_obj->date_first_payment_hidden = $paydates['paydates'][0];//$paydates['paydates'][0];       // for database update
			}
			else
			{
				$qualify = $application->getQualify();
				$due_date = $qualify->getLoanDueDate(time(), $application->getPayDateCalculator());
				$due_date_formatted = date('m-d-Y', $due_date);
				$trans_obj->date_first_payment_display = $due_date_formatted;
				$trans_obj->date_first_payment_hidden = $due_date_formatted;   // for database update
			}
		}
		else if ( isset($trans_obj) && isset($trans_obj->date_fund_actual) )
		{
			$trans_obj->date_fund_actual_display = $trans_obj->date_fund_actual;
			$trans_obj->date_fund_actual_hidden  = $trans_obj->date_fund_actual_display;   // for database update

			$trans_obj->date_first_payment_display = $trans_obj->date_first_payment;
			$trans_obj->date_first_payment_hidden  = $trans_obj->date_first_payment_display;   // for database update

			if ( trim($trans_obj->date_first_payment_hidden) == '' )
			{
				// we must have a valid value in this field in order to update the database
				// in application_query.class.php::Update_Application() without getting php warnings
				// on the strtotime() function.

				$trans_obj->date_first_payment_hidden = $paydates['paydates'][0];
			}
		}
		else
		{
			$trans_obj->date_fund_actual_display = '';
			$trans_obj->date_fund_actual_hidden  = $date_now; // for database update

			$trans_obj->date_first_payment_display = '';
			$trans_obj->date_first_payment_hidden  = $paydates['paydates'][0];   // for database update
		}

		// WTF is this? it's above as well
		for($i = 0; $i < count($paydates['paydates']); $i++)
		{
			$pd_name = "paydate_{$i}";
			$trans_obj->{$pd_name} = $paydates['paydates'][$i];
		}

		$trans_obj->income_date_one_day = $paydates['income_date_one_day'];
		$trans_obj->income_date_one_month = $paydates['income_date_one_month'];
		$trans_obj->income_date_one_year = $paydates['income_date_one_year'];
		$trans_obj->income_date_two_day = $paydates['income_date_two_day'];
		$trans_obj->income_date_two_month = $paydates['income_date_two_month'];
		$trans_obj->income_date_two_year = $paydates['income_date_two_year'];
		
		$trans_obj->docs = $application->getDocuments()->getSentandRecieved();
		//Add IDV stuff, ignore for reacts
		if ($app_row->is_react == 'no')
		{
			//Add IDV stuff
			$trans_obj->inquiry_packages = $this->bureau_query->getData($application_id, $trans_obj->company_id);
			/*
			if(count($trans_obj->inquiry_packages))
			{
				foreach($trans_obj->inquiry_packages as $package)
				{
					$call_type = strtolower($package->inquiry_type);
					switch ($package->bureau_id) {
						case 4:  // FactorTrust
							$uwResponse = $application->getFactorTrustPerf();
							break;
						case 5:  // Clarity
							$uwResponse = $application->getClarityPerf();
							break;
						case 1:  // CL Verify
						case 2:  // TSS DataX
						case 3:  // Satori
						default:
							$uwResponse = $application->getDataXPerf();
							break;
					}
					$uwResponse->parseXML($package->received_package);
					$trans_obj->idv_increase_eligible = $uwResponse->getLoanAmountDecision();
					if ($trans_obj->idv_increase_eligible) break;  // Break foreach loop the first case it is true
				}
			}
			*/

			$trans_obj->idv_increase_eligible = false;
		}
		else
		{
			$trans_obj->inquiry_packages = NULL;
			$trans_obj->idv_increase_eligible = false;	
		}
		/*
		if(count($trans_obj->inquiry_packages))
		{
			// We retrieve packages Newest to Oldest, so stop on the first match
			foreach($trans_obj->inquiry_packages as $package)
			{
				switch ($package->name_short) {
					case "datax":
						$dataxResponse = $application->getDataXPerf();
						break;
					case "factortrust":
						$dataxResponse = $application->getFactorTrustPerf();
						break;
					case "clarity":
						$dataxResponse = $application->getClarityPerf();
						break;
				}
				//if ($dataxResponse->isValid()) {
					$dataxResponse->parseXML($package->received_package);
					$trans_obj->idv_increase_eligible = $dataxResponse->getLoanAmountDecision();
					break;
				//}
			}
		}
		*/

		// Needs to be below bureau inquiry pull because
		// we now use datax info in the calculator...
		$trans_obj->fund_amount_array = LoanAmountCalculator::Get_Instance($this->db, $this->server->company)->calculateLoanAmountsArray($trans_obj);
		$trans_obj->loan_amount_allowed = LoanAmountCalculator::Get_Instance($this->db, $this->server->company)->calculateMaxLoanAmount($trans_obj);


		$contact_flags = $application->getContactFlags();
		$trans_obj->notifications = $contact_flags->getAll();

		$this->application_contact = new eCash_Application_Contact($application_id);

		//don't include company_id (for fraud to pull an app from another company)
		$trans_obj->contact_flags = $this->application_contact->getFlags();
		$trans_obj->contacts = $this->application_contact->getContactInformation();
		$trans_obj->contact_categories = $this->application_contact->getCategories();
		$trans_obj->loan_action_list = Get_Loan_Actions($application_id); //mantis:6106 - moved behind IDV

		//mantis:4648
		$trans_obj->dnl_info = $this->do_not_loan->Get_DNL_Info($trans_obj->ssn);
		$trans_obj->is_dnl_set = $this->do_not_loan->Does_SSN_In_Table($trans_obj->ssn);
		$trans_obj->is_dnl_set_for_company = $this->do_not_loan->Does_SSN_In_Table_For_Company($trans_obj->ssn, $this->company_id);
		$trans_obj->is_dnl_set_for_other = $this->do_not_loan->Does_SSN_In_Table_For_Other_Company($trans_obj->ssn, $this->company_id);
		$trans_obj->is_override_dnl_set = $this->do_not_loan->Does_Override_Exists_For_Company($trans_obj->ssn, $this->company_id);
		$trans_obj->do_not_loan = $trans_obj->is_dnl_set_for_company || $trans_obj->is_dnl_set_for_other && !($trans_obj->is_override_dnl_set);

		$trans_obj->dnl_override_info = $this->do_not_loan->Get_DNL_Override_Info($trans_obj->ssn);
		//end mantis:4648

		// We need to make sure that we do not react accounts that are set to not be able to reactivate,
		if($trans_obj->do_not_loan)
		{
			$trans_obj->can_react = false;
		}
		else
		{
			$trans_obj->can_react = $this->Is_React_Eligible($trans_obj->application_list);
		}

		//Mantis:10206 - Alert has wrong number of business days in it.  here because it relates to the paydates
		$due_date_offset = $trans_obj->business_rules['grace_period'];
	
		// Include the reaction due date for the grace period for react apps that aren't funded yet
		if ($trans_obj->can_react && ( $trans_obj->date_fund_actual === NULL )) {
			$react_due_time = strtotime($trans_obj->business_rules['react_grace_date']);
			$react_due_offset = $react_due_time - time();
			$react_due_offset = ceil($react_due_offset / (24 * 60 * 60));
			
			if ($react_due_offset > $due_date_offset) $due_date_offset = $react_due_offset;
		}
        
		$trans_obj->due_date_offset = $due_date_offset;

		$trans_obj->esig_doc_list = $application->getDocuments()->getEsigable();
		$trans_obj->packaged_doc_list = $application->getDocuments()->getPackages();
		$trans_obj->send_doc_list = $application->getDocuments()->getSendable();
		$trans_obj->receive_doc_list = $application->getDocuments()->getRecievable();
		// Add all collections agents that can be associated with payment arrangements
		// (for Collections ONLY -- need to delete after conversion from Cashline) -- MAC
		if(ECash::getModule()->Get_Active_Module() === 'conversion')
		{
			$trans_obj->collections_agents = ECash::getFactory()->getData('Agent')->getCollectionsAgents($application->getCompanyId());
		}

		// Add their schedule information
		list($schedule_status, $schedule) = $this->Fetch_Schedule_Data($application_id, true, $set_td_lock);
		$trans_obj->schedule_status = $schedule_status;

		$trans_obj->interest_accrued_now_posted = isset($trans_obj->schedule_status->posted_service_charge_total) ? abs($trans_obj->schedule_status->posted_service_charge_total) : 0;
		/**
		 * If this account uses daily interest instead of a fixed interest charge, then calculate current interest owed
		 * for the next business day.
		 */
		$trans_obj->svc_charge_type = 'fixed';
		if(isset($trans_obj->business_rules['service_charge']['svc_charge_type']) && $trans_obj->business_rules['service_charge']['svc_charge_type'] === 'Daily')
		{
			$trans_obj->svc_charge_type = 'daily';

			require_once(ECASH_COMMON_DIR . 'ecash_api/interest_calculator.class.php');

			$next_business_day = $this->pdc->Get_Next_Business_Day(date('Y-m-d'));
			$trans_obj->next_business_day = $next_business_day;

			// If the batch has been closed for the day, make it two business days ahead.
			if(Has_Batch_Closed())
			{
				$next_business_day = $this->pdc->Get_Business_Days_Forward(date('Y-m-d'), 2);
			}

			$interest = Interest_Calculator::scheduleCalculateInterest($trans_obj->business_rules, $schedule, $next_business_day);
			// Check if a loan is CSO before determining whether to calculate interest or not based on default status
			if ($trans_obj->business_rules['loan_type_model'] == 'CSO')
			{
				// Zero out the interest if they've defaulted
				if ($renewal_class->hasDefaulted($trans_obj->application_id))
					$interest = 0.00;
			}
				
			$trans_obj->interest_accrued = $interest;

			$interest = Interest_Calculator::scheduleCalculateInterest($trans_obj->business_rules, $schedule);

			// Check if a loan is CSO before determining whether to calculate interest or not based on default status
			if ($trans_obj->business_rules['loan_type_model'] == 'CSO')
			{
				// Zero out the interest if they've defaulted
				if ($renewal_class->hasDefaulted($trans_obj->application_id))
					$interest = 0.00;
			}

			$trans_obj->interest_accrued_now_posted = $interest;
			$trans_obj->current_daily_interest = $interest;
			$interest = Interest_Calculator::scheduleCalculateInterest($trans_obj->business_rules, $schedule, null, true);

			// Check if a loan is CSO before determining whether to calculate interest or not based on default status
			if ($trans_obj->business_rules['loan_type_model'] == 'CSO')
			{
				// Zero out the interest if they've defaulted
				if ($renewal_class->hasDefaulted($trans_obj->application_id))
					$interest = 0.00;
			}

			$trans_obj->interest_accrued_now_pending = $interest;
			if (ereg ("([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})", $next_business_day, $regs))
			{
				$trans_obj->interest_accrued_to_date = "$regs[2]/$regs[3]/$regs[1]";
			}

			// also just save when interest was paid up until - back one business day for convenience on front end calculations.

			// LOOK AT ME, I'm an interest calculator! <- go to hell
			$paid_to = Interest_Calculator::getInterestPaidPrincipalAndDate($schedule, false, null, false);
			//daily_interest_amount of interest accrued in one day

			$rate_calc = $application->getRateCalculator();
			$daily_interest_amount = ($rate_calc->getPercent() / 7) / 100 * $paid_to['principal'];
			if ($trans_obj->business_rules['loan_type_model'] == 'CSO')
			{

				// Zero out the interest if they've defaulted
				if ($renewal_class->hasDefaulted($trans_obj->application_id))
					$daily_interest_amount = 0;
			}

			$trans_obj->daily_interest_amount = number_format($daily_interest_amount, 2);
			$trans_obj->interest_paid_to_date = preg_replace('%^(\d{4})-(\d{2})-(\d{2})$%', '$2/$3/$1', $paid_to['date']);
		}

		$trans_obj->has_payment_arrangements = (($trans_obj->schedule_status->has_arrangements) ? 1 : 0);
		$trans_obj->has_failed_payment_arrangements = (($trans_obj->schedule_status->has_failed_arrangements) ? 1 : 0);

		//$follow_ups = Follow_Up::Get_Follow_Ups_For_Application($application_id);
		//foreach ($follow_ups as $follow_up)
		//{
			/**
			 * Originally we would only complete servicing and collection Follow Ups 
			 * when the agent who created the follow up pulled the app.  This doesn't really
			 * flow well with every customer's follow up process, so I removed that restriction.
			 * 
			 *  @todo Follow Ups really suck and need to be rewritten.  [BR]
			 */
			//Follow_Up::Update_Follow_Up_Status($application_id,$follow_up->follow_up_id);
		//}

		/*
		$trans_obj->has_followup = FALSE;
		$follow_ups = Follow_Up::Get_Follow_Ups_For_Application($application_id);
		if (count($follow_ups) > 0)
		{
		    $trans_obj->has_followup = TRUE;    
		}
		*/
		
		$follow_up = Follow_Up::Get_Follow_Up_Info_For_Application($application_id);
		if ($follow_up === NULL)
		{
			$trans_obj->followup = FALSE;
		}
		else
		{
			$trans_obj->followup = $follow_up;
		}
		
		// Will be used for Preacts [rlopez]
		$trans_obj->scheduled_payments = Fetch_Scheduled_Payments_Left($application_id);

		//this is legacy cashline conversion default data values
		$trans_obj->conversion_info_button = '';
		$trans_obj->conversion_status = '';
		$trans_obj->converted_transaction_count = 0;
		$trans_obj->converted_notes_count = 0;
		$trans_obj->conversion_queue_reasons	 = array();
		$trans_obj->conversion_status = '';
		$trans_obj->cashline_status = '';

		//some defaults for tiffing
		$trans_obj->dnis = NULL;
		$trans_obj->tiff = NULL;
		$trans_obj->tiff_message = NULL;

/*
		WTF are you talking about??  Any updates to the DB should have taken place before calling this
		method.  This data is already being fetched and set somewhere, so why are you overriding it here
		rather than fixing it previously?  This reeks of poor troubleshooting.  - BR

        //mantis: 10803 Paydates weren't refreshing because direct_deposit wasn't updating in db till now, this
        //is a vastly bad form to have to rehit db to get updated direct_deposit status
*/
		$pdc =  new Pay_Date_Calc_3(Fetch_Holiday_List());
		$tr_data = Get_Transactional_Data($application_id, $this->db);
		$tr_data->info->direct_deposit = ($tr_data->info->direct_deposit == 1) ? true : false;

		/**
		  * If for some reason the pay date info has missing or bad data the PayDate Calculator
		  * will throw errors.  This is an attempt to catch the errors and display a semi-friendly
		  * error message.
		  */
		try
		{
				$dates = $pdc->Calculate_Pay_Dates($tr_data->info->paydate_model,
								$tr_data->info->model, $tr_data->info->direct_deposit,
								26, date("Y-m-d"));
				$trans_obj->paydate_0 = date("m-d-Y", strtotime($dates[0]));
				$trans_obj->paydate_1 = date("m-d-Y", strtotime($dates[1]));
				$trans_obj->paydate_2 = date("m-d-Y", strtotime($dates[2]));
				$trans_obj->paydate_3 = date("m-d-Y", strtotime($dates[3]));
				$trans_obj->payday_0 = date("D", strtotime($dates[0]));
				$trans_obj->payday_1 = date("D", strtotime($dates[1]));
				$trans_obj->payday_2 = date("D", strtotime($dates[2]));
				$trans_obj->payday_3 = date("D", strtotime($dates[3]));
				$paydate_list = array();
				foreach ($dates as $pay_date){
					$paydate_list [] = date("m-d-Y", strtotime($pay_date));
				}
				$trans_obj->paydate_list = serialize($paydate_list);
		}
		catch (Exception $e)
		{
			$_SESSION['error_message'] .= "This account has pay schedule errors!  Reason: " .$e->getMessage() . "\n";
		}

		$_SESSION['current_app'] = $trans_obj;
		return $trans_obj;
	}
	/**
	 * creates the formated string for the display of a duration
	 * 
	 * @param date date in the past to calculate duration from
	 * 
	 * @return string formated string in the form {$yrs}yrs {$mos}mos
	 */
	public function getDurationTag($date)
	{
		$secs = time() - strtotime($date); // Get the difference in seconds
		$yrs = date("Y", $secs) - 1970; // Subtract the epoch date
		$mos = date("m", $secs) - 1;

		return "{$yrs}yrs {$mos}mos";
	}
	public function Add_App_to_View_History(ECash_Models_Application $application)
	{
		if((! isset($_SESSION['view_history'])) || (! is_array($_SESSION['view_history'])))
		{
			$_SESSION['view_history'] = array();
		}

		// If the app already exists, we'll pull it out and put it back on top.
		$tmp = array();
		foreach($_SESSION['view_history'] as $app)
		{
			if($app['application_id'] != $application->application_id)
			{
				$tmp[] = $app;
			}
		}
		unset($_SESSION['view_history']);
		$_SESSION['view_history'] = $tmp;


		if(count($_SESSION['view_history']) > 3)
		{
			array_pop($_SESSION['view_history']);
		}

		array_unshift($_SESSION['view_history'], array('application_id' => $application->application_id,
														'name_last' => $application->name_last,
														'name_first' => $application->name_first,
														'company_id' => $application->company_id));
	}

	public function Load_Business_Rules($rule_set_id)
	{
		static $ruletree;

		if(! empty($ruletree))
		{
			return $ruletree;
		}

		$ruletree = $this->business_rules->Get_Rule_Set_Tree($rule_set_id);
		return ($ruletree);
	}

	public function Save_Payments($structure)
    {
		/* sample input (expressed in JSON syntax)
{
   payment_type:'payment_arrangement',
   manual_payment:{
      num:3,
      total_balance:333,
   },
   payment_arrangement:{
	  num: 1,
      arr_incl_pend:'true',
      rows:[
         {
            actual_amount:333,
            interest_amount:33,
            interest_range_begin:3333/33/33,
            interest_range_end:3333/33/33,
            desc:'arbitrary text',
            payment_type:credit_card,
            date:3333/33/33,

         }
      ],
      discount_amount:33,
      discount_desc:'arbitrary text',

   },
   collections_agent:4,
   agent_id:4,
   application_id:4,

}
		*/
		// !! REQUEST FIELDS USED WITHIN THIS FUNCTION (Research done in ecash3.0 ecash_commercial agean codebase) !!
		// payment_type
		// manual_payment_total_balance
		// payment_arrangement_arr_incl_pend
		// collections_agent
		// agent_id
		//// in Set_Completed_Payments
		////// in Set_ScheduledDebt_Payments
		////// payment_type
		////// debt_consolidation_amount
		////// debt_consolidation_final_interest_charge
		////// debt_payments
		////// debt_consolidation_arr_incl_pend
		////// debt_consolidation_date
		////// debt_consolidation_company
		//// in Set_Scheduled_Payments
		//// payment_type         					// Tentative Payments Structure!
		//// {base}_num								// payments_structure = { #base#:{ rows:[
		//// application_id							//   { actual_amount: #..#,
		//// {base}_actual_amount_{#}				//     interest_amount: #..#,
		//// {base}_interest_amount_{#}				//     interest_range_begin: #..#,
		//// {base}_interest_range_begin_{#}		//     interest_range_end: #..#,
		//// {base}_interest_range_end_{#}			//     desc: #..#,
		//// {base}_desc_{#}						//     date: #..#,
		//// {base}_payment_type_{#}				//   }, { ... } ],
		//// {base}_date_{#}						//   discount_amount: #..#,
		//// {base}_discount_amount					//   discount_desc: #..#
		//// {base}_discount_desc					//   } };

		$remove_from_queue = true;
		$module = ECash::getModule()->Get_Active_Module();
		$application_id = $structure->application_id;
		$company_id = $structure->company_id;

		$agent_id = ECash::getAgent()->getAgentId();

		/** @todo: Get rid of this crap! **/
		$as = $_SESSION['current_app']; // App Status can be grabbed from here

		if(Check_For_Transaction_Modifications($this->db, $this->log, $application_id))
		{
			$_SESSION['error_message'] = "Unable to save payments.  This applicant's schedule has been modified.";
			return;
		}

		$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Saving Payment (type: {$structure->payment_type})");

		$schedule = Fetch_Schedule($application_id);
		$status = Analyze_Schedule($schedule);

		if($structure->payment_type == 'partial_payment')
		{
			return 	$this->Set_Partial_Payment($application_id, $schedule, $structure, $module, $status);
		}

		if ($structure->payment_type == 'manual_payment')
		{
			$evids = null;
			$evids = Set_Completed_Payments($application_id, $schedule, $structure, $module, $status);
			$schedule = Fetch_Schedule($application_id);
			$status = Analyze_Schedule($schedule);
			//mantis:6806
			$balance = Fetch_Balance_Information($application_id);
			if (0 >= ($balance->total_pending - $structure->manual_payment->total_balance) )
			{
				Remove_Unregistered_Events_From_Schedule($application_id);
			}
			//mantis:7560
			else if ( Event_Type_Is_Scheduled($schedule, 'full_balance')
				 || Event_Type_Is_Scheduled($schedule, 'card_full_balance')
			)
			{
				foreach ($schedule as $scheduled_event)
				{
					if (
						in_array($scheduled_event->event_name_short, array('full_balance','card_full_balance'))
						&& 'scheduled' == $scheduled_event->status
					)
					{
						$date_event     = $scheduled_event->date_event;
						$date_effective = $scheduled_event->date_effective;
						break;
					}
				}

				// This will clear unregistered events then create a new Full_Pull
				//Schedule_Full_Pull($application_id, NULL, NULL, $date_event, $date_effective);
			}

			// If this is an Amortization payment, set the 30-day follow-up timer
			if($as->status == 'amortization')
			{
				$biz_rules = $this->business_rules;
				$loan_type_id = $biz_rules->Get_Loan_Type_For_Company(ECash::getCompany()->name_short, 'offline_processing');
				$rule_set_id = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
				$rules = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

				$interval = Follow_Up::Add_Time(strtotime(date('Y-m-d')), $rules['amortization_payment_period'], 'day');

				$comment = "Amortization Payment made, setting {$rules['amortization_payment_period']} day follow up timer";
				$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:$application_id] $comment");

				Follow_Up::Expire_Follow_Ups($application_id);
				Follow_Up::Create_Follow_Up($application_id, 'amortization_payment', $interval, $agent_id, $company_id, $comment);
			}
			elseif ($as->level1 != 'collections' && $as->level1 != 'contact')
			{
				Complete_Schedule($application_id);
			}

			/**
			 * Special Handling of payments for Collections Customers
			 */
			if($as->level1 == 'collections' || $as->level1 == 'contact')
			{
				/**
				 * This is a lame hack to override functionality for Impact/HMS
				 */
				if(file_exists(CUSTOMER_LIB . 'actions_after_manual_collections.php'))
				{
					require_once(CUSTOMER_LIB . 'actions_after_manual_collections.php');
					$customer_collections = new Customer_Actions_After_Manual_Collections($this->server);
					$remove_from_queue = $customer_collections->run($status, $application_id);
				}
				else
				{
					/**
					 * If they don't have a past due balance
					 * - If they have a fatal ACH, they still need to make arrangements to pay off their account
					 * - Otherwise they're current and we'll automatically move them back to an Active status
					 * @todo: This logic is probably undocumented and should be moved outside of the generic code.
					 */
					if($status->past_due_balance <= 0)
					{
						$flags = new Application_Flags($this->server, $application_id);
						if($flags->Get_Flag_State('has_fatal_ach_failure'))
						{
							//has fatal do popup to change bank info set to collections general collections contact
							$data = new stdclass();
							$data->has_js ="<script>alert('Bank info Must be updated in order for further ACH transactions'); </script>";
							ECash::getTransport()->Set_Data($data);
							Update_Status($this->server, $application_id, array("queued", "contact", "collections", "customer", "*root"));
							$remove_from_queue = false;
						}
						elseif($flags->Get_Flag_State('has_fatal_card_failure'))
						{
							//has fatal do popup to change card info set to collections general collections contact
							$data = new stdclass();
							$data->has_js ="<script>alert('Payment Card info Must be updated in order for further ACH transactions'); </script>";
							ECash::getTransport()->Set_Data($data);
							Update_Status($this->server, $application_id, array("queued", "contact", "collections", "customer", "*root"));
							$remove_from_queue = false;
						}
						else
						{
							//regen schedule move to active and remove queue status
							Update_Status($this->server, $application_id, array("active", "servicing", "customer", "*root"));
							Complete_Schedule($application_id);
						}
					}
					else
					{
						/**
						 * If we don't have any other scheduled events, this is a poor attempt at re-inserting the application
						 * into a queue so it's dealt with.  Otherwise, attempt to regenerate the schedule to account for the balance
						 * change.
						 */
						if($status->num_scheduled_events == 0)
						{
							//not current move to collections general and collections contact
							Update_Status($this->server, $application_id, array("queued", "contact", "collections", "customer", "*root"));
							$remove_from_queue = false;
						}
						else
						{
							Complete_Schedule($application_id);
						}
					}
				}
			}

			if(Check_Inactive($application_id))
			{
				/**
				 * @todo Make this a CFE event to send the document when the balance goes to zero
				 */
				ECash_Documents_AutoEmail::Queue_For_Send($application_id, 'ZERO_BALANCE_LETTER');
				return false;
			}

			// Create an Affiliation to track who entered payments
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Creating affiliation for {$application_id}");
			$aaid = null;

			$application = ECash::getApplicationById($application_id);
			$affiliations = $application->getAffiliations();
			$currentAffiliation = $affiliations->getCurrentAffiliation('manual', 'creator');
			if(!empty($currentAffiliation))
				$agent = $currentAffiliation->getAgent();
			else
				$agent = null;

			if (!empty($agent))
			{
				$agent_id = (is_a($agent,'ECash_Agent')) ? $agent->getAgentId() : $agent['agent_id']; //
				$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Found existing affiliation w/ agent {$agent_id}");
			}
			else
			{
				$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Creating new affiliation w/ agent ". ECash::getAgent()->getAgentId());
				$agent_id = ECash::getAgent()->getAgentId();
				$agent = ECash::getAgent();
			}
			$currentAffiliation = $affiliations->add($agent, 'manual', 'creator', null);
			$currentAffiliation->associateWithScheduledEvents($evids);
		}

		else if ($structure->payment_type == 'debt_consolidation')
		{
			$new_schedule = Set_ScheduledDebt_Payments($application_id, $structure, $status, $this->db);
		}

		else
		{
			try
			{
				$new_schedule = Set_Scheduled_Payments($schedule, $structure, $module, $status, false);
			}
			catch (eCash_PaymentArrangementException $e)
			{
				$_SESSION['error_message'] = "The arrangement could not be saved: {$e->getMessage()}";
				return;
			}
		}

		// Manual Payments use Complete_Schedule, not Update_schedule
		if($structure->payment_type != 'manual_payment')
		{
			$evids = null;
			$evids = Update_Schedule($application_id, $new_schedule, $this->db);
		}

		if (($structure->payment_type) == 'payment_arrangement')
		{
			//check for include pending flag

			if (isset($structure->payment_arrangement->arr_incl_pend))
			{
				Application_Add_Flag($application_id, 'arr_incl_pend');
			}
			else
			{
				Application_Remove_Flag($application_id, 'arr_incl_pend');
			}

			/*	If the account is QC but not Ready, set them to QC Arrangements
			 *  If the account is NOT QC, or is QC Ready then set them to Made Arrangements
			 */
			if ($as->level1 == 'quickcheck' && $as->status != 'ready')
			{
				Update_Status($this->server, $application_id, array('arrangements','quickcheck','collections','customer','*root'));
			}
			else if ($as->level1 != 'quickcheck' || ($as->level1 == 'quickcheck' && $as->status == 'ready'))
			{
				Update_Status($this->server, $application_id, array('current','arrangements','collections','customer','*root'));
			}

			$aaid = null;
			if (isset($structure->collections_agent) && ($module === 'collections'))
			{
				$agent_id = $structure->agent_id;
			}
			else
			{
				$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$_SESSION['current_app']->application_id}] Creating affiliation for {$application_id}");

				$application = ECash::getApplicationById($application_id);
				$affiliations = $application->getAffiliations();
				$currentAffiliation = $affiliations->getCurrentAffiliation('collections', 'owner');
				if(!empty($currentAffiliation))
					$agent = $currentAffiliation->getAgent();
				else
					$agent = null;

				if (!empty($agent))
				{
					$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$_SESSION['current_app']->application_id}] Found existing affiliation w/ agent " . $agent->getAgentId());
					$agent_id = $agent->getAgentId();
				}
				else
				{
					$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$_SESSION['current_app']->application_id}] Creating new affiliation w/ agent " . ECash::getAgent()->getAgentId());
					$agent = ECash::getAgent();
					$agent_id = $agent->getAgentId();
				}
			}
			//THIS IS STUPID! There's no good reason to be creating a followup on/myqueue entry on an application when they make
			//arrangements!  It should pop into the agent's myqueue when they fail the arrangements. [W!-03-20-2009][HMS COLLECTIONS]
			//$follow_up = new Follow_Up();
			//$follow_up->createCollectionsFollowUp($application_id, Follow_Up::Add_Time(time(),1,'minute'), $agent_id, ECash::getCompany()->company_id, null, null, null);
			$currentAffiliation = $affiliations->add($agent, 'collections', 'owner', null);
			$currentAffiliation->associateWithScheduledEvents($evids);
			$remove_from_queue = TRUE; //[#52881] Remove from queues until arrangement is due (handled elsewhere)
			$agent = ECash::getAgent();
			$agent->getTracking()->add('made_arrangements', $application_id);

			//ECash_Documents_AutoEmail::Queue_For_Send( $application_id, 'ARRANGEMENTS_MADE');
			Remove_Standby($application_id, 'arrangements_failed');
		}
		else if (($structure->payment_type) == 'debt_consolidation')
		{

			/*	Per Mantis:1992 - The app should go to arrangements status and not
			 *      get excluded.
			 */
			if (!(($as->status == 'sent') && ($as->level1 == 'quickcheck')))
				Update_Status($this->server, $application_id, array("current", "arrangements", "collections", "customer", "*root"));

			$aaid = null;
			if (isset($structure->collections_agent))
			{
				$agent_id = $structure->collections_agent;
			}
			else
			{
				$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Creating affiliation for {$application_id}");
				$application = ECash::getApplicationById($application_id);
				$affiliations = $application->getAffiliations();
				$currentAffiliation = $affiliations->getCurrentAffiliation('collections', 'owner');
				if(!empty($currentAffiliation))
					$agent = $currentAffiliation->getAgent();
				else
					$agent = null;

				if (!empty($agent))
				{
					$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$_SESSION['current_app']->application_id}] Found existing affiliation w/ agent " . $agent->getAgentId());
					$agent_id = $agent->getAgentId();
				}
				else
				{
					$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$_SESSION['current_app']->application_id}] Creating new affiliation w/ agent " . ECash::getAgent()->getAgentId());
					$agent = ECash::getAgent();
					$agent_id = $agent->getAgentId();
				}			
			}
			$currentAffiliation = $affiliations->add($agent, 'collections', 'owner', null);
			$currentAffiliation->associateWithScheduledEvents($evids);

			/**
			 * @todo This may need to be rewritten for new MyQueue functionality
			 */
			$follow_up = new Follow_Up();
			$follow_up->createCollectionsFollowUp($application_id, Follow_Up::Add_Time(time(),1,'minute'), $agent_id, ECash::getCompany()->company_id, 'Made Debt Consolidation', null, null);
			$remove_from_queue = false;

		}
		if($remove_from_queue)
		{
		//	remove_from_automated_queues($application_id);
			$qm = ECash::getFactory()->getQueueManager();
			$qm->removeFromAllQueues(new ECash_Queues_BasicQueueItem($application_id));
		}

		alignActionDateForCard($application_id);

		return true;
	}

	/**protected function Set_Partial_Payment
	 * Creates Single Payment in Schedule and sets up actions to be followed after the action date
	 * Returns new events to be added to schedule
	 * @param long $application_id
	 * @param  array $schedule
	 * @param  class $structure
	 * @param  string $module
	 * @param  class $status
	 * @return array
	 */
	protected function Set_Partial_Payment($application_id, $schedule, $structure, $module, $status)
	{

		$new_event = Set_Scheduled_Payments($schedule, $structure, $module, $status);

		Remove_Unregistered_Events_From_Schedule($application_id);
		if(!empty($new_event))
		{
			$events = Append_Schedule($application_id, $new_event);
		}

		$payment_date = $structure->partial_payment->rows[0]->date;
		$application = ECash::getApplicationById($application_id);
		$affiliations = $application->getAffiliations();
		$agent = ECash::getAgent();
		$affiliations->expire('collections','owner');
		$currentAffiliation = $affiliations->add($agent, 'collections', 'owner', null);
		$currentAffiliation->associateWithScheduledEvents($events);
		$agent->getTracking()->add('partial_payments', $application_id);
				
		//Adds the delayed queue entry based on what the business rules say
		$this->Handle_Actions_After_Partial($application_id, $payment_date, ECash::getAgent()->getAgentId(), $structure->company_id);

		//Look, I'm not going to lie to you, because lying is a sin, and sinners burn in Hell.
		//The CFE rules are executed after the Handle_Actions_After_Partial is called so that it can potentially undo any actions 
		//performed.  We really should pick one way or the other to set this up, (internal code or CFE event), but that's way outside
		//the scope of this current implementation.  This current implementation shouldn't break anything.
		
		//Execute the desired CFE events		
        $engine = ECash::getEngine();	
        $engine->executeEvent('PARTIAL_PAYMENT', array('date_available' => strtotime($payment_date)));
	
		alignActionDateForCard($application_id);

		return $new_event;
	}

	/**protected function Handle_Actions_After_Partial
	 * Handles Actions after a Partial Payment has Been Made
	 * @param long $application_id
	 * @param  date $date
	 * @param  int $agent_id
	 * @param  int $company_id
	 */
	protected function Handle_Actions_After_Partial($application_id, $date, $agent_id, $company_id)
	{
		//Handle Actions after Partial
		$application = ECash::getApplicationById($application_id);
		$rules = $application->getBusinessRules();

		if(isset($rules['partial_payment']['notification_after_partial']))
		{
			$days_forward = $rules['partial_payment']['notification_after_partial'];
		}
		else
		{
			$days_forward = 1 ;
		}

		if(isset($rules['partial_payment']['action_after_partial']))
		{
			$action = $rules['partial_payment']['action_after_partial'];
		}
		else
		{
			$action = 'Collections General';
		}

		$date_available = $this->pdc->Get_Calendar_Days_Forward($date, $days_forward);
		switch($action)
		{
			case 'My Queue':
				//set followup and agent affiliation with date_available/follow_up_time as $structure->partial_payment->rows[0]->date plus $rules['notification_after_partial']
				$agent = ECash::getAgentById($agent_id);
				$agent->getQueue()->insertApplication($application, 'collections', null, strtotime($date_available));

			break;
			case 'Collections General':
				//put in collections general queue with date_available as $structure->partial_payment->rows[0]->date plus $rules['notification_after_partial']
				//change to queue manager
			//	move_to_automated_queue('Collections General', $application_id, "0 - {$date_available}" , strtotime($date_available), null);

				$qm = ECash::getFactory()->getQueueManager();
				$queue_item = $qm->getQueue('collections_general')->getNewQueueItem($application_id);
				$queue_item->DateAvailable = strtotime($date_available);
				$qm->moveToQueue($queue_item, 'collections_general');
			break;
			//Queue operations are being handled outside of here, most likely with a nightly process that requeues applications not in any queueus.
			case 'External Process':
				$qm = ECash::getFactory()->getQueueManager();
				$qm->removeFromAllQueues(new ECash_Queues_BasicQueueItem($application_id));
				break;
			default:
				break;
		}
	}


	public function Permanently_Dequeue($application_id)
	{
		if(Check_For_Application_Modifications($application_id))
		{
			$_SESSION['error_message'] = "Unable to Permanently Dequeue, this applicant has been modified.";
		}
		else
		{
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$_SESSION['current_app']->application_id}] Moving Application ID {$application_id} to Permamently Dequeued (Collections)");
			Update_Status($this->server, $application_id, array('indef_dequeue','collections',
									       'customer','*root'));
			$application = ECash::getApplicationById($application_id);
			$affiliations = $application->getAffiliations();
			$affiliations->expire('collections', 'owner');
		}
	}

	public function Schedule_Payment_Card_Payoff($request)
	{
		$application_id = $request->application_id;
		$comment        = $request->freeform;
	
		if(Check_For_Transaction_Modifications($this->db, $this->log, $application_id))
		{
			$_SESSION['error_message'] = "Unable to schedule payment card payoff.  This applicant's schedule has been modified.";
		}
		else
		{
			$agent = ECash::getAgent();
			$agent->getTracking()->add('payment_card_payoff', $application_id);
			
			// Track the principal balance (assume it's a positive balance)
			// and include anything pending
			$balance_info = Fetch_Balance_Information($application_id);
			$balance = array(
					'principal' => -$balance_info->principal_pending,
					'service_charge' => -$balance_info->service_charge_pending,
					'fee' => -$balance_info->fee_pending
					);
		    $amounts = AmountAllocationCalculator::generateGivenAmounts($balance);

	    	if (count($amounts))
    		{
        		$date_event     = date('Y-m-d');
		        $date_effective = date('Y-m-d');

		        $e = Schedule_Event::MakeEvent($date_event, $date_effective, $amounts,
		        'payment_card_payoff', $comment, NULL, 'manual');
        		Post_Event($application_id, $e);

				Check_Inactive($application_id);
    		}
		}
	}


	public function Schedule_Payout($request)
	{
		$application_id = $request->application_id;

		switch ($request->edate)
		{
			case 'select':
				$date = $request->scheduled_date?date("Y-m-d", strtotime($request->scheduled_date)):NULL;
			break;

			default:
				$date = $request->edate;
			break;
		}

		switch ($request->edate)
		{
			case 'select':
				$date = $request->scheduled_date?date("Y-m-d", strtotime($request->scheduled_date)):NULL;
			break;

			default:
				$date = $request->edate;
			break;
		}

		if(Check_For_Transaction_Modifications($this->db, $this->log, $application_id))
		{
			$_SESSION['error_message'] = "Unable to schedule payout.  This applicant's schedule has been modified.";
		}
		else
		{
			if (Set_Payout($application_id,$date) && isset($_SESSION['api_payment']))
			{
				$queue_manager = ECash::getFactory()->getQueueManager();
				$queue = $queue_manager->getQueue("account_summary");
				$queue->remove(new ECash_Queues_BasicQueueItem($application_id));
				$agent = ECash::getAgent();
				$agent->getTracking()->add('api_payment_payout', $application_id);

				Remove_API_Payment($_SESSION['api_payment']->api_payment_id);

				unset($_SESSION['api_payment']);
				ECash_Documents_AutoEmail::Send($application_id, 'ONLINE_REQUEST_CONFIRMATION');
			}

		}
	}

	public function Cancel_Loan($application_id)
	{
		if(Check_For_Transaction_Modifications($this->db, $this->log, $application_id))
		{
			$_SESSION['error_message'] = "Unable to cancel loan.  This applicant's schedule has been modified.";
		}
		else
		{
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Cancelling Loan");

			// First, remove all the unregistered events
			Remove_Unregistered_Events_From_Schedule($application_id);

			$holidays = Fetch_Holiday_List();
			$pd_calc = new Pay_Date_Calc_3($holidays);
			$today = $pd_calc->Get_Closest_Business_Day_Forward(date('Y-m-d'));
			$next_day = $pd_calc->Get_Business_Days_Forward($today, 1);

			$balance_info = Fetch_Balance_Information($application_id);
			$principal_amount = $balance_info->principal_pending;
			$service_charge_amount = $balance_info->service_charge_pending;
			$fee_amount = $balance_info->fee_pending;
			// GF 6334
			// Should be 0 if no lien fees exist
            $lien_fee_amount       = Fetch_Balance_Total_By_Event_Names($application_id, array('payment_fee_lien','assess_fee_lien','writeoff_fee_lien'));
            $delivery_fee_amount   = Fetch_Balance_Total_By_Event_Names($application_id, array('payment_fee_delivery','assess_fee_delivery','writeoff_fee_delivery'));
            $transfer_fee_amount   = Fetch_Balance_Total_By_Event_Names($application_id, array('payment_fee_transfer','assess_fee_transfer','writeoff_fee_transfer'));

			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Principal: {$principal_amount}   Interest: {$service_charge_amount}   Fee Amount: {$fee_amount}");

			$new_schedule = array();

			//Forgive the remaining fees (if there are any)
			if(($service_charge_amount + $fee_amount) != 0)
			{
				$amounts = array();
				if ($service_charge_amount > 0)
				{
					$amounts[] = Event_Amount::MakeEventAmount('service_charge', -$service_charge_amount);
				}

				if ($delivery_fee_amount > 0)
				{
					$delivery_amount[] = Event_Amount::MakeEventAmount('fee',-$delivery_fee_amount);

					$new_schedule[] = Schedule_Event::MakeEvent($today, $today, $delivery_amount, 'writeoff_fee_delivery', 'Cancel Request, Forgive Delivery Fees','scheduled','cancel');

					$fee_amount -= $delivery_fee_amount;
				}

				if ($transfer_fee_amount > 0)
				{
					$transfer_amount[] = Event_Amount::MakeEventAmount('fee',-$transfer_fee_amount);

					$new_schedule[] = Schedule_Event::MakeEvent($today, $today, $transfer_amount, 'writeoff_fee_transfer', 'Cancel Request, Forgive Wire Transfer Fees','scheduled','cancel');

					$fee_amount -= $transfer_fee_amount;
				}

				// in order to make an event of a writeoff_lien_fee, I need to do that before the amount
				// is calculated for the fee forgiveness.
				if ($lien_fee_amount > 0)
				{
					$lien_amount[] = Event_Amount::MakeEventAmount('fee',-$lien_fee_amount);

					// Make its own event, so totalling writeoff_fee_lien, assess_fee_lien, and payment_fee_lien totalled up
					// should equal 0 if application is cancelled.
					$new_schedule[] = Schedule_Event::MakeEvent($today, $today, $lien_amount, 'writeoff_fee_lien', 'Cancel Request, Forgive Lien Fees','scheduled','cancel');

					// We don't want to cancel the next step, only adjust it to be minus the lien fee
					// This is a bit of a hack.
					$fee_amount -= $lien_fee_amount;
				}

				if ($fee_amount > 0)
				{
					$amounts[] = Event_Amount::MakeEventAmount('fee', -$fee_amount);
				}
				$new_schedule[] = Schedule_Event::MakeEvent($today, $today, $amounts, 'adjustment_internal', 'Cancel Request, Forgive Fees','scheduled','cancel');
			}
			// Deduct the remaining principal (if there is one)
			if($principal_amount > 0)
			{
				$payment = isCardSchedule($application_id) ? 'card_cancel' : 'cancel';
				$amounts = array();
				$amounts[] = Event_Amount::MakeEventAmount('principal', -$principal_amount);
				$new_schedule[] = Schedule_Event::MakeEvent($today, $next_day, $amounts, $payment, 'Cancel Request, Deduct principal','scheduled','cancel');
			}

			if ($new_schedule != null)
			{
				Append_Schedule($application_id, $new_schedule);
			}
			else
			{
				$this->log->Write("No new schedule to create for cancelled loan for $application_id");
			}

			// GF 10729: If loan is cancelled from a pre-fund status, set the new status as
			// withdrawn
			$app = ECash::getApplicationById($application_id);
			$app_status = $app->getStatus();

                        //$canceled_status = ($app_status->level0 == "approved") ? 'withdrawn::applicant::*root' : 'canceled::servicing::customer::*root';
                        $canceled_status = ($app_status->level0 == "approved") ? 'withdrawn::applicant::*root' : 'canceled::applicant::*root';

                        Update_Status(NULL, $application_id, $canceled_status);
		}
	}

	public function Save_Recovery($request, $application_id = NULL)
	{
		if(NULL === $application_id)
		{
			$application_id = $_SESSION['current_app']->application_id;
		}

		if(Check_For_Transaction_Modifications($this->db, $this->log, $application_id))
		{
			$_SESSION['error_message'] = "Unable to Save Recovery.  This applicant's schedule has been modified.";
		}
		else
		{
			Register_Single_Event($application_id, $request, $this->db);
		}
	}

	/**
	 * Add an ACH payment on date specified, without taking over next scheduled payment [#27768]
	 */
	public function Add_Manual_ACH($request)
	{
		$application_id = $request->application_id;

		if(Check_For_Transaction_Modifications($this->db, $this->log, $application_id))
		{
			$_SESSION['error_message'] = "Unable to Add Manual ACH.  This applicant's schedule has been modified.";
		}
		else
		{
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Adding Manual ACH");

			if (Add_Manual_ACH($application_id, $request))
			{
				Complete_Schedule($application_id);
			}
		}
	}
	
	public function Add_Paydown($request)
	{
		$application_id = $request->application_id;

		if(Check_For_Transaction_Modifications($this->db, $this->log, $application_id))
		{
			$_SESSION['error_message'] = "Unable to Add Paydown.  This applicant's schedule has been modified.";
		}
		else
		{
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Adding Paydown");

			if (Add_Paydown($application_id, $request))
			{	// Replacing Repaint Schedule with this per Mantis: 4551
				Complete_Schedule($application_id);

                if (isset($_SESSION['api_payment']))
                {
					$queue_manager = ECash::getFactory()->getQueueManager();
					$queue = $queue_manager->getQueue("account_summary");
					$queue->remove(new ECash_Queues_BasicQueueItem($application_id));
					$agent = ECash::getAgent();
					$agent->getTracking()->add('api_payment_paydown', $application_id);

                    Remove_API_Payment($_SESSION['api_payment']->api_payment_id);
                    unset($_SESSION['api_payment']);
          			ECash_Documents_AutoEmail::Send($application_id, 'ONLINE_REQUEST_CONFIRMATION');
                }
			}
		}
	}

	public function Save_Refund($request)
	{
		$application_id = $_SESSION['current_app']->application_id;

		if(Check_For_Transaction_Modifications($this->db, $this->log, $application_id))
		{
			$_SESSION['error_message'] = "Unable to Save Refund.  This applicant's schedule has been modified.";
		}
		else
		{
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$_SESSION['current_app']->application_id}] Saving Refund for Application ID {$application_id}");

			$schedule = Fetch_Schedule($application_id);
			Set_Refund($application_id, $request, $schedule, $this->db);
			Complete_Schedule($application_id);
		}
	}

	public function Save_Chargeback($request, $app_id = NULL)
	{
		if(NULL === $app_id)
		{
			$app_id = $_SESSION['current_app']->application_id;
		}

		if(Check_For_Transaction_Modifications($this->db, $this->log, $app_id))
		{
			$_SESSION['error_message'] = "Unable to Save Chargeback.  This applicant's schedule has been modified.";
			return;
		}

		$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$_SESSION['current_app']->application_id}] Saving Chargeback for Application ID {$app_id}: \${$request->amount}");

		$data = Get_Transactional_Data($app_id);
		$schedule = Fetch_Schedule($app_id);
		Set_Chargeback($app_id, $request, $schedule, $this->db);
	}

	public function Save_RecoveryReversal($request, $app_id = NULL)
	{

		if(NULL === $app_id)
		{
			$app_id = $_SESSION['current_app']->application_id;
		}

		$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$_SESSION['current_app']->application_id}] Saving Recovery Reversal for Application ID {$app_id}");

		$data = Get_Transactional_Data($app_id);
		Set_RecoveryReversal($app_id, $request);

	}

	public function Save_Writeoff($request, $application_id = NULL)
	{
		if(NULL === $application_id)
		{
			$application_id = $_SESSION['current_app']->application_id;
		}

		if(Check_For_Transaction_Modifications($this->db, $this->log, $application_id))
		{
			$_SESSION['error_message'] = "Unable to Save Writeoff.  This applicant's schedule has been modified.";
			return false;
		}
		else
		{
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Saving Writeoff");
		}

		$amt = round(floatval($request->amount), 2);
		$comment = $request->payment_description;
		switch ($request->action)
		{
			case 'recovery': $payment_type = "ext_recovery"; break;
			case 'writeoff': $payment_type = "debt_writeoff"; break;
		}

		$date = date("Y-m-d", strtotime("now"));

		$balance_info = Fetch_Balance_Information($application_id);
		
		$balance = array(
			'principal' =>  $balance_info->principal_pending,
			'service_charge' => $balance_info->service_charge_pending,
			'fee' => $balance_info->fee_pending,
		);

		$amounts = AmountAllocationCalculator::generateAmountsFromBalance(-$amt, $balance);
		$event = Schedule_Event::MakeEvent($date, $date, $amounts, $payment_type, $comment);
			
		try {
			$this->db->beginTransaction();
			$evid = Post_Event($application_id, $event);
			$this->db->commit();
		}
		catch (Exception $e)
		{
			$this->db->rollBack();
			$this->log->Write("Error recording event, rolling back changes.");
			$_SESSION['error_message'] = "Unable to Save Writeoff.  There was a problem saving the transaction.";
			return false;
		}

		// [#17817][#17683]
		// If the total pending balance is zero'd out, then remove all pending transactions [VT]
		if(Fetch_Balance_Information($application_id)->total_pending <= 0)
		{
			Remove_Unregistered_Events_From_Schedule($application_id);
		}

		// Get the CFE engine
		ECash::getApplicationById($application_id);
		$engine = ECash::getEngine();
		$engine->executeEvent('BAD_DEBT_WRITE_OFF', array());

		Complete_Schedule($application_id);
		
        // If the schedule is "complete", i.e. they have no more balance,
		// set them to inactive
		if(Check_Inactive($application_id))
		{
			ECash_Documents_AutoEmail::Queue_For_Send($application_id, 'ZERO_BALANCE_LETTER');
		}
		
		
	}

	public function Save_Adjustment($request)
	{
		$application_id = $_SESSION['current_app']->application_id;

		if(Check_For_Transaction_Modifications($this->db, $this->log, $application_id))
		{
			$_SESSION['error_message'] = "Unable to Save Adjustment.  This applicant's schedule has been modified.";
		}
		else
		{
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$_SESSION['current_app']->application_id}] Saving Adjustment for Application ID {$application_id}");
			$data = Get_Transactional_Data($application_id);
			$schedule = Set_Adjustment($application_id, $request, ECash::getAgent()->getAgentId());
			$schedule = Fetch_Schedule($application_id);
			Complete_Schedule($application_id);

			$ns = Fetch_Schedule($application_id);
			$status = Analyze_Schedule($ns);

			// If the schedule is "complete", i.e. they have no more balance,
			// set them to inactive
			if(Check_Inactive($application_id))
			{
				ECash_Documents_AutoEmail::Queue_For_Send($application_id, 'ZERO_BALANCE_LETTER');

				$queue_log = get_log("queues");
   				$queue_log->Write(__FILE__.":".'$Revision$'.":".__LINE__.":".__METHOD__."()",LOG_NOTICE);
				$queue_manager = ECash::getFactory()->getQueueManager();
				$queue_manager->getQueueGroup('automated')->remove(new ECash_Queues_BasicQueueItem($application_id));
			}
		}
	}

	public function Fetch_Schedule_Data($application_id, $verify=TRUE, $set_td_lock=TRUE)
	{
		$schedule = Fetch_Schedule($application_id);
		if($set_td_lock === TRUE)
		{
			// This should set the Transactional Lock Info, hopefully for most reads.
			Set_Transaction_Lock_Info($application_id, $schedule);
		}
		$data = Get_Transactional_Data($application_id);
		$status = Analyze_Schedule($schedule, $verify, $data->rules);
		return array($status, $schedule);
	}

	public function Fund($application_id, $method, $funding_method = 'ach', $payment_method = 'ach')
	{
		if(Check_For_Transaction_Modifications($this->db, $this->log, $application_id))
		{
			$_SESSION['error_message'] = "Unable to Fund.  This applicant's schedule has been modified.";
			$this->log->Write("[Agent:{$_SESSION['agent_id']}] App {$application_id} not funded. The schedule has been previously modified.");
			return false;
		}
		else
		{
			$this->log->Write("[Agent:{$_SESSION['agent_id']}] Funding Application ID {$application_id}");
			
			$application = ECash::getApplicationByID($application_id);
			
			// Update the app status to have accurate info for the schedule.
			$status = Update_Status($this->server, $application_id, array('approved','servicing', 'customer','*root'));
			if ($status)
			{
				require_once(CUSTOMER_LIB . "create_schedule_dfa.php");
				require_once(COMMON_LIB_DIR . "pay_date_calc.3.php");

				if (!Set_Schedule_Model($application_id, $method))
				{
					$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Could not set the funding method: {$method}");
				}

				// This has to come before the batch
				// check, because it replaces $data
				$data = Get_Transactional_Data($application_id);

				// Creck for refi loans and set balances and statuses accordingly
				$refi_amt = Handle_Refi_Applications($application_id);
                $data->amt = $data->amt - $refi_amt;

				// Used to include any pre-existing fee$data->amts for Title Loans
				$data->schedule = Fetch_Schedule($application_id);

				$holidays = $holidays = Fetch_Holiday_List();
				$pdc = new Pay_Date_Calc_3($holidays);

				$log = get_log("scheduling");
				$data->log = $log;
				$data->pdc = $pdc;

				$data->application_id = $application_id;
				$data->fund_amount = $data->amt;

				$data->balance_info = Fetch_Balance_Information($application_id);
				$rules = Prepare_Rules($data->rules, $data->info);
				$data->rules = $rules;
				$data->fund_method = $method;

				$data->may_use_card_schedule = mayUseCardSchedule($application_id, $this->company_id);

				if ($funding_method == 'card')
				{
					$flag_type = ECash::getFactory()->getModel('FlagType');
					$loaded = $flag_type->loadBy(array('name_short'=>'card_disbursement','active_status'=>'active',));

					if ($loaded)
					{
						$flags = $application->getFlags();
						if(!$flags->get('card_disbursement'))
						{
							$flags->set('card_disbursement');
						}
					}
				}
				
				if ($payment_method == 'card')
				{
					$flag_type = ECash::getFactory()->getModel('FlagType');
					$loaded = $flag_type->loadBy(array('name_short'=>'card_schedule','active_status'=>'active',));
					
					if ($loaded)
					{
						$flags = $application->getFlags();
						if(!$flags->get('card_schedule'))
						{
							$flags->set('card_schedule');
						}
					}
				}

				if(isset($_REQUEST['comment'])) $data->comment = $_REQUEST['comment'];

				if (!isset($dfas['create_schedule']))
				{
					$dfa = new CreateScheduleDFA();
					$dfa->SetLog($data->log);
					$dfas['create_schedule'] = $dfa;
				}
				else
				{
					$dfa = $dfas['create_schedule'];
				}
				$new_events = $dfa->run($data);
			    foreach ($new_events as $e)
				{
					$evid = Record_Event($application_id, $e);
					if(($method == 'Fund_Check' && $e->type == 'check_disbursement') || ($method == 'Fund_Moneygram' && $e->type == 'moneygram_disbursement'))
					{
						$trids = Record_Current_Scheduled_Events_To_Register($e->date_event,
						$application_id, $evid);

						foreach ($trids as $trid)
						{
							Post_Transaction($application_id, $trid);
						}

					}

				}

			}
			
			if ($payment_method == 'card') alignActionDateForCard($application_id);

			$loan_type = $application->getLoanType();
			$loan_type_name_short = $loan_type->name_short;

			switch ($loan_type_name_short)
			{
				case 'california_payday':
			 		if ($method == "Fund_Moneygram")
			 		{
			 			$document_name = "APPROVAL_FUND_MG_CA";
			 		}
			 		else
			 		{
			 			$document_name = "APPROVAL_FUND_CA";
			 		}
				break;

			 	case 'delaware_title':
			 	case 'delaware_payday':
			 	default:
			 		if ($method == 'Fund_Moneygram')
			 		{
			 			$document_name = "APPROVAL_FUND_MG_DE";
			 		}
			 		else if($method == 'Fund_Check')
			 		{
			 			$document_name = "APPROVAL_FUND_CK_DE";
			 		}
			 		else
			 		{
			 			$document_name = "APPROVAL_FUND";
			 		}
			 	break;
			 }

 			try
			{
			 	ECash_Documents_AutoEmail::Send($application_id, $document_name);
 			}
 			catch (Exception $e)
 			{
 				// This is meant as a way to stack error messages.
 				$_SESSION['error_message'] .= $e->getMessage() . '\n';
 				throw $e;
 			}
 			$app_data = $this->Fetch_Loan_All($application_id);

			 ECash::getTransport()->Set_Data($app_data);

			// Now return their update status
			return $status;
		}
	}

	public function Quickcheck($application_id)
	{
		if(Check_For_Transaction_Modifications($this->db, $this->log, $application_id))
		{
			$_SESSION['error_message'] = "Unable to move this applicant to QuickCheck Status, the applicant has been modified. Please verify and try again.";
		}
		else
		{
			return (Update_Status($this->server, $application_id, array('ready','quickheck','collections','customer','*root')));
		}
	}

	public function Not_Bankruptcy($application_id)
	{
		if(Check_For_Transaction_Modifications($this->db, $this->log, $application_id))
		{
			$_SESSION['error_message'] = "Unable to move this applicant to the Underwriting Queue, the applicant has been modified. Please verify and try again.";
		}
		else
		{
			return (Update_Status($this->server, $application_id,
					      array('queued','contact','collections','customer','*root')));
		}
	}

	public function Bankruptcy($application_id, $verified)
	{
		if(Check_For_Transaction_Modifications($this->db, $this->log, $application_id))
		{
			$_SESSION['error_message'] = "Unable to move this applicant to Bankruptcy, the applicant has been modified. Please verify and try again.";
		}
		else
		{
			if ($verified)
			{
				$result = Update_Status($this->server, $application_id, array('verified','bankruptcy','collections','customer','*root'));
			}
			else
			{
				$result = Update_Status($this->server, $application_id, array('unverified','bankruptcy','collections','customer','*root'));
			}

			//Remove_Unregistered_Events_From_Schedule($application_id);
			Remove_And_Suspend_Events_From_Schedule($application_id); //mantis:4454

			return $result;
		}
	}

	public function Deny($application_id)
	{
		if(Check_For_Transaction_Modifications($this->db, $this->log, $application_id))
		{
			$_SESSION['error_message'] = "Unable to Deny, the applicant has been modified.  Please verify and try again.";
		}
		else
		{
			$result = Update_Status($this->server, $application_id, array('denied','applicant','*root'));
			Remove_Unregistered_Events_From_Schedule($application_id);

			$queue_manager = ECash::getFactory()->getQueueManager();
			$queue_manager->getQueueGroup('automated')->remove(new ECash_Queues_BasicQueueItem($application_id));

			/**
			 * This wouldn't be typical of most loans, but in some cases there might be some sort of fee applied.
			 */
			$this->Adjust_Fee_And_Service_Charge_Balance($application_id);

			return $result;
		}
	}

	public function Withdraw($application_id)
	{
		if(Check_For_Transaction_Modifications($this->db, $this->log, $application_id))
		{
			$_SESSION['error_message'] = "Unable to Withdraw, the applicant has been modified.  Please verify and try again.";
		}
		else
		{
			$result = Update_Status($this->server, $application_id, array('withdrawn','applicant','*root'));
			Remove_Unregistered_Events_From_Schedule($application_id);

			$queue_manager = ECash::getFactory()->getQueueManager();
			$queue_manager->getQueueGroup('automated')->remove(new ECash_Queues_BasicQueueItem($application_id));

			/**
			 * This wouldn't be typical of most loans, but in some cases there might be some sort of fee applied.
			 */
			$this->Adjust_Fee_And_Service_Charge_Balance($application_id);

			return $result;
		}
	}

	public function Adjust_Fee_And_Service_Charge_Balance($application_id)
	{
		$balance_info = Fetch_Balance_Information($application_id);
		$service_charge_amount = $balance_info->service_charge_pending;
		$fee_amount = $balance_info->fee_pending;
		$principal = $balance_info->principal_balance;

		//Forgive the remaining fees (if there are any)
		if(($service_charge_amount + $fee_amount + $principal) != 0)
		{
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Adjusting Fees - Interest: {$service_charge_amount}   Fee Amount: {$fee_amount}");

			$holidays = Fetch_Holiday_List();
			$pd_calc = new Pay_Date_Calc_3($holidays);
			$today = $pd_calc->Get_Closest_Business_Day_Forward(date('Y-m-d'));
			$next_day = $pd_calc->Get_Business_Days_Forward($today, 1);

			$new_schedule = array();

			$amounts = array();
			if ($service_charge_amount > 0)
			{
				$amounts[] = Event_Amount::MakeEventAmount('service_charge', -$service_charge_amount);
			}
			if ($fee_amount > 0)
			{
				$amounts[] = Event_Amount::MakeEventAmount('fee', -$fee_amount);
			}
			if ($principal > 0)
			{
				$amounts[] = Event_Amount::MakeEventAmount('principal', -$principal);
			}
			$new_schedule[] = Schedule_Event::MakeEvent($today, $today, $amounts, 'adjustment_internal', 'Cancel Request, Forgive Fees','scheduled','cancel');

			if (! empty($new_schedule))
			{
				Append_Schedule($application_id, $new_schedule);
			}
			else
			{
				$this->log->Write("No new schedule to create for $application_id");
			}
		}
	}

	public function To_Verify_Queue($application_id)
	{
		if(Check_For_Application_Modifications($application_id))
		{
			$_SESSION['error_message'] = "Unable to move this applicant to the Verification Queue, this application has been modified.";
		}
		else
		{
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Moving Application to Verification Queue");
			return Update_Status($this->server, $application_id, array('queued','verification','applicant','*root'));
		}

	}

	public function To_In_Process_Queue($application_id)
	{
		if(Check_For_Application_Modifications($application_id))
		{
			$_SESSION['error_message'] = "Unable to move this applicant to the In Process Queue, this application has been modified.";
		}
		else
		{
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Moving Application to In Process Queue");
			Remove_Unregistered_Events_From_Schedule($application_id);
			return Update_Status($this->server, $application_id, array('in_process','prospect','*root'));
		}

	}

	public function decline($application_id)
	{
		if(Check_For_Application_Modifications($application_id))
		{
			$_SESSION['error_message'] = "Unable to decline this applicant, this application has been modified.";
		}
		else
		{
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Moving Application to Denied Status");
			return Update_Status($this->server, $application_id, 'denied::applicant::*root');
		}
	}
	public function To_REFI($application_id)
	{
		if(Check_For_Application_Modifications($application_id))
		{
			$_SESSION['error_message'] = "Unable to move this application to REFI, this application has been modified.";
		}
		else
		{
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Moving Application to REFI status");
			Remove_Unregistered_Events_From_Schedule($application_id);
			return Update_Status($this->server, $application_id, array('refi', 'servicing', 'customer', '*root'));
		}
	}

	public function To_Addl_Verify_Queue($application_id)
	{
		if(Check_For_Application_Modifications($application_id))
		{
			$_SESSION['error_message'] = "Unable to move this applicant to the Additional Verification Queue, this application has been modified.";
		}
		else
		{
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Moving Application to Additional Verification Queue");
			return Update_Status($this->server, $application_id, array('addl','verification','applicant','*root'));
		}

	}

	public function To_Hotfile_Queue($application_id)
	{
		if(Check_For_Application_Modifications($application_id))
		{
			$_SESSION['error_message'] = "Unable to move this applicant to the Hotfile Queue, this application has been modified.";
		}
		else
		{
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Moving Application to Hotfile Queue");
			return Update_Status($this->server, $application_id, array('hotfile','verification','applicant','*root'));
		}

	}

	public function To_Underwriting_Queue($application_id)
	{
		if(Check_For_Application_Modifications($application_id))
		{
			$_SESSION['error_message'] = "Unable to move this applicant to the Underwriting Queue, this applicant has been modified.";
		}
		else
		{
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Moving Application to Underwriting Queue");
			return Update_Status($this->server, $application_id, array('queued','underwriting','applicant','*root'));
		}
	}

	public function To_Second_Tier($application_id)
	{
		if(Check_For_Application_Modifications($application_id))
		{
			$_SESSION['error_message'] = "Unable to move this application to the Second Tier, this application has been modified.";
		}
		else
		{
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Moving Application to Second Tier Pending status");
			Remove_Unregistered_Events_From_Schedule($application_id);
			return Update_Status($this->server, $application_id, array('pending', 'external_collections', '*root'));
		}
	}

	public function To_CCCS($application_id)
	{
		if(Check_For_Application_Modifications($application_id))
		{
			$_SESSION['error_message'] = "Unable to move this application to CCCS, this application has been modified.";
		}
		else
		{
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Moving Application to CCCS status");
			Remove_Unregistered_Events_From_Schedule($application_id);
			return Update_Status($this->server, $application_id, array('cccs', 'collections', 'customer', '*root'));
		}
	}

	public function To_Skip_Trace($application_id)
	{
		if(Check_For_Application_Modifications($application_id))
		{
			$_SESSION['error_message'] = "Unable to move this application to the Skip Trace Queue, this application has been modified.";
		}
		else
		{
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Moving Application to Skip Trace status");
			return Update_Status($this->server, $application_id, array('skip_trace','collections','customer','*root'));
		}
	}

	public function From_Skip_Trace($application_id)
	{
		$app = ECash::getApplicationById($application_id);
		$prev_app_status_id = $app->getPreviousStatus();
		if(Update_Status(null, $application_id, $prev_app_status_id))
		{
			$queue_manager = ECash::getFactory()->getQueueManager();
			$queue_manager->getQueueGroup('automated')->remove(new ECash_Queues_BasicQueueItem($application_id));

			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Removing Skip Trace status and setting to previous status ({$prev_app_status_id})");
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	public function Update_Schedule($application_id, $start_date=null, $made_arrangement)
	{
		$as = $_SESSION['current_app']; // App Status can be grabbed from here

		// Check to see if the shift is forward or backward. If forward, it is an arranged due date delay.
		$date_vals = explode('-', $as->schedule_status->next_action_date);

		//if there's a payout, just change the payout date.
		if($as->schedule_status->has_payout)
		{
			$date = $this->pdc->Get_Next_Business_Day($start_date);
			Set_Payout($application_id,$date);
			return;
		}

		$old_action_date = $date_vals[2] .'-'. $date_vals[0] .'-'. $date_vals[1];
		if($old_action_date > $start_date)
		{
			$made_arrangement = FALSE;
		}

		$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Attempting to recalculate dates, starting at ".
				  (($start_date == null) ? date("Y-m-d") : $start_date));
		if(Check_For_Transaction_Modifications($this->db, $this->log, $application_id))
		{
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Schedule shift failed. Modifications found.");
			$_SESSION['error_message'] = "Unable to Update Schedule, this applicant has been modified.";
		}
		else
		{
			$schedule = Fetch_Schedule($application_id);
			$data = Get_Transactional_Data($application_id);

			if (($as->status == 'past_due') || ($start_date != null))
			{
				$data->rules['grace_period'] = 0;
			}
			$schedule = Recalculate_Schedule_Dates($schedule, $data->info, $data->rules, $start_date);
			$evids = Update_Schedule($application_id, $schedule, $made_arrangement);
			//GForge [#26702] fix affiliations missing after shifting schedule
			if($made_arrangement)
			{
				$application = ECash::getApplicationById($application_id);
				$affiliations = $application->getAffiliations();
				$currentAffiliation = $affiliations->getCurrentAffiliation('manual', 'creator');
				$agent = NULL;
				if(!empty($currentAffiliation))
					$agent = $currentAffiliation->getAgent();
				else
					$agent = ECash::getAgent();

				$currentAffiliation = $affiliations->add($agent, 'manual', 'creator', NULL);
				$currentAffiliation->associateWithScheduledEvents($evids);
				$agent->getTracking()->add('made_arrangements', $application_id);
			}
		}
	}

	public function Complete_Pending_Items($request)
	{
		$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$_SESSION['current_app']->application_id}] In Complete_Pending_Items in Loan Data");
		$application_id = $_SESSION['current_app']->application_id;
		Resolve_Pending_Items($application_id, $request);
	}

	public function Hold_Status ($hold_type)
	{
		$application_id = $_SESSION['current_app']->application_id;
		$app = ECash::getApplicationById($application_id);
		switch($hold_type)
		{
			case 'servicing':
				Update_Status($this->server, $application_id, array("hold","servicing","customer", "*root"));
				break;
			case 'collections':
				Update_Status($this->server, $application_id, array("hold","arrangements","collections", "customer", "*root"));
				break;
			case 'previous_status':
				if($previous_status = $app->getPreviousStatus())
					Update_Status($this->server, $application_id, intval($previous_status->application_status_id));
				break;
		}
	}

	public function Is_React_Eligible($app_list)
	{
		if(empty($app_list))
			return false;

		/*
		$inactive_ids = $this->Get_Inactive_Ids();
		foreach($app_list as $app)
		{
			if (!in_array($app->application_status_id,
				      $inactive_ids))
				return false;
		}
		return true;
		*/
		// Check if any apps are in collections if so we can not react
		$collection_ids =  $this->Get_Collection_Ids();
		foreach($app_list as $app)
		{
			if (in_array($app->application_status_id, $collection_ids))
			{
				return false;

			}
		}
		// Check if In Servicing
		$serv_ids =  $this->Get_Servicing_Ids();
		foreach($app_list as $app)
		{
			if (in_array($app->application_status_id, $serv_ids))
			{
				return false;
			}
		}
		/*
		// Check if app was denied
		// We check the very last Denied app and the Very last Inactive and
		// make sure that the Denied app created date is less then the
		// Inactive last paydate
		$denied_ids = $this->Get_Denied_Ids();
		$inactive_ids = $this->Get_Inactive_Ids();
		$denied_app = null;
		$inactive_app = null;
		foreach($app_list as $app)
		{
			if (in_array($app->application_status_id, $denied_ids))
			{
				if(is_null($denied_app) ||
				strtotime($denied_app->date_application_status_set) < strtotime($app->date_application_status_set))
					$denied_app = $app;
			}
			elseif (in_array($app->application_status_id, $inactive_ids))
			{
				if(is_null($inactive_app) ||
				strtotime($inactive_app->date_application_status_set) < strtotime($app->date_application_status_set))
					$inactive_app = $app;
			}
		}
		if(!is_null($denied_app) &&  !is_null($inactive_app))
		{
			if(strtotime($denied_app->date_application_status_set) > strtotime($inactive_app->date_application_status_set))
				return false;
		}

		*/
		return true;
	}

	//TODO: Move all these status grabbing functions to company-specific class

   /**
	* Grab Status IDs that do not require Due Dates
	*
	* @return array returns array of integers (status ids)
	*/
	public function Get_IDs_Not_Requiring_Due_Dates()
	{
		static $ids;

		if(is_array($ids))
		{
			return $ids;
		}

		$ids = array();

		$ids[] = Status_Utility::Search_Status_Map('paid::customer::*root');
		$ids[] = Status_Utility::Search_Status_Map('sent::external_collections::*root');
		$ids[] = Status_Utility::Search_Status_Map('recovered::external_collections::*root');
		$ids[] = Status_Utility::Search_Status_Map('hold::servicing::customer::*root');
		$ids[] = Status_Utility::Search_Status_Map('funding_failed::servicing::customer::*root');
		$ids[] = Status_Utility::Search_Status_Map('hold::arrangements::collections::customer::*root');
		$ids[] = Status_Utility::Search_Status_Map('dequeued::bankruptcy::collections::customer::*root');
		$ids[] = Status_Utility::Search_Status_Map('queued::bankruptcy::collections::customer::*root');
		$ids[] = Status_Utility::Search_Status_Map('verified::bankruptcy::collections::customer::*root');

		return($ids);
	}

	/**
	* Grab Status IDs For hold statuses
	*
	* @return array returns array of integers (status ids)
	*/
	public function Get_Hold_IDs()
	{
		static $ids;


		if(is_array($ids))
		{
			return $ids;
		}
		$ids = array();

		$ids[] = Status_Utility::Search_Status_Map('hold::arrangements::collections::customer::*root');
		$ids[] = Status_Utility::Search_Status_Map('amortization::bankruptcy::collections::customer::*root');
		$ids[] = Status_Utility::Search_Status_Map('unverified::bankruptcy::collections::customer::*root');
		$ids[] = Status_Utility::Search_Status_Map('verified::bankruptcy::collections::customer::*root');

		return($ids);
	}

	public function Get_Inactive_Ids()
	{
		static $ids;

		if(! empty($ids))
		{
			return $ids;
		}
		$query = "
			SELECT application_status_id
			FROM application_status_flat
			WHERE 	(level0='paid' and level1='customer' and level2='*root')
			OR 		(level0='recovered' and level1='external_collections' and level2='*root')
		";
		$st =  $this->db->query($query);
		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$ids[] = $row->application_status_id;
		}
		return $ids;
	}

	public function Get_Collection_Ids()
	{
		static $ids;

		if(! empty($ids))
		{
			return $ids;
		}
		$query = "
			SELECT application_status_id
		    FROM application_status_flat
		    WHERE (level1='external_collections' and level0 != 'recovered')
		    OR (level2='collections') OR (level1='collections')
		 ";
		$st =  $this->db->query($query);
		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$ids[] = $row->application_status_id;
		}
		return $ids;
	}

	public function Get_Servicing_Ids()
	{
		static $ids;

		if(! empty($ids))
		{
			return $ids;
		}
		$query = "
			SELECT application_status_id
		    FROM application_status_flat
		    WHERE (level1='servicing')
		 ";
		$st =  $this->db->query($query);
		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$ids[] = $row->application_status_id;
		}
		return $ids;
	}

	public function Get_Denied_Ids()
	{
		static $ids;


		if(! empty($ids))
		{
			return $ids;
		}
		$query = "
			SELECT application_status_id
		    FROM application_status_flat
		    WHERE (level0='denied')
		 ";
		$st =  $this->db->query($query);
		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$ids[] = $row->application_status_id;
		}
		return $ids;
	}

	// Active the Pedning account assoicated with Inactive Loans
	public function Activate_Pending_Preact(Server $server,$application_id)
	{
		$log = $server->log;
		try
		{
			$reacts = Get_Reacts_From_App($application_id, ECash::getCompany()->company_id);
			for($i=0; $i<count($reacts); $i++)
			{
				if($reacts[$i]->olp_process == "ecashapp_preact")
				{
					// $reacts[$i]->application_id
					// Set React app to a status that will be
					$appstatid = $reacts[$i]->application_status_id;
					$appid = $reacts[$i]->application_id;
					if($appstatid === Status_Utility::Search_Status_Map("queued::underwriting::applicant::*root"))
					{
						$log->Write("Setting Preact ID: $appid [queued,underwriting,applicant,*root]");
						Update_Status($server, $appid,array("queued","underwriting","applicant","*root"));
					}
					else if($appstatid === Status_Utility::Search_Status_Map("preact_pending::prospect::*root"))
					{
						$log->Write("Setting Preact ID: $appid [pending,prospect,*root]");
						Update_Status($server, $appid,array("pending","prospect","*root"));
					}
					else if($appstatid === Status_Utility::Search_Status_Map("preact_confirmed::prospect::*root"))
					{
						$log->Write("Setting Preact ID: $appid [confirmed,prospect,*root]");
						Update_Status($server, $appid,array("confirmed","prospect","*root"));
					}
					else if($appstatid === Status_Utility::Search_Status_Map("preact_agree::prospect::*root"))
					{
						$log->Write("Setting Preact ID: $appid [confirmed,prospect,*root]");
						Update_Status($server, $appid,array("agree","prospect","*root"));
					}
					else
					{
						$log->Write("Preact for AppID:{$application} Preact ID:{$appid} had status of: ".$appstatid);
					}
				}
			}
		}
		catch (Exception $e)
		{
			$log->Write("Preact Automation for: {$application} failed.");
			throw $e;
		}
	}

	/**
	 * Add a fee to an account
	 *
	 * Currently fee types can be 'assess_fee_lien', 'assess_fee_delivery', and 'assess_fee_transfer'
	 *
	 * @param int $application_id
	 * @param string $type
	 * @return bool
	 */
	public function Add_Fee($application_id, $type)
	{
		if(! in_array($type, array('assess_fee_lien','assess_fee_delivery','assess_fee_transfer')))
		{
			throw new Exception("Invalid fee type '$type'");
		}

		$agent_id = ECash::getAgent()->AgentId;
		ECash::getLog()->Write("[Agent:{$agent_id}] Adding Fee of type: $type to $application_id");

		$application = ECash::getApplicationById($application_id);
		if(! $application->exists())
		{
			throw new Exception("Unable to locate application_id '{$application_id}'");
		}

		$rules = $application->getBusinessRules();

		switch ($type)
		{
			case 'assess_fee_lien':
				$title_data = ECash::getFactory()->getData('TitleLoan');
				$fee_amount = $title_data->getLienFee($application->state);
				break;
			case 'assess_fee_delivery':
				$fee_amount = $rules['ups_label_fee'];
				break;
			case 'assess_fee_transfer':
				$fee_amount = $rules['moneygram_fee'];
				break;
			default:
				return FALSE;
		}

		// Now inserting the fee as a principal amount per Agean Live #10603
		if($fee_amount > 0.0)
		{
			$date = date('Y-m-d');
			//$amounts = AmountAllocationCalculator::generateGivenAmounts(array('fee' => $fee_amount));
			$amounts = AmountAllocationCalculator::generateGivenAmounts(array('principal' => $fee_amount));
			$event   = Schedule_Event::MakeEvent($date, $date, $amounts, $type,
					     "Agent {$agent_id} initiated Fee",'scheduled','manual');

			Post_Event($application_id, $event);
			Complete_Schedule($application_id);
			return TRUE;
		}
		else
		{
			ECash::getLog()->Write(__FUNCTION__ . " No fee amount!");
		}

		return FALSE;
	}

	/**
	 * Method used to determine the Principal Payment Amount for the application
	 *
	 * - This may not be the most appropriate place, but I needed a quick fix. [BR]
	 *
	 * @param array $rules - Business Rules
	 * @param integer $fund_amount - the fund amount
	 * @return integer
	 */
	static public function Get_Payment_Amount($rules, $fund_amount)
	{
		if(! is_array($rules) || ctype_digit((string) $fund_amount))
			return 0;

		// Try new rules, else fall back.
		if(isset($rules['principal_payment']))
		{
			if($rules['principal_payment']['principal_payment_type'] === 'Percentage')
			{
				$p_amount = (($fund_amount / 100) * $rules['principal_payment']['principal_payment_percentage']);
			}
			else
			{
				$p_amount = $rules['principal_payment']['principal_payment_amount'];
			}

			return $p_amount;

		}
		else
		{
			return $rules['principal_payment_amount'];
		}

	}



}

?>
