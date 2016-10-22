<?php


require_once(SQL_LIB_DIR . "debt_company.func.php");
require_once(SQL_LIB_DIR . "application.func.php");
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(SQL_LIB_DIR . "app_flags.class.php");
require_once(CUSTOMER_LIB . "/failure_dfa.php");
require_once(ECASH_COMMON_DIR . 'ECashApplication.php');
require_once(LIB_DIR.'AgentAffiliation.php');
require_once(LIB_DIR."Payment_Card.class.php");

class Edit
{
	private $server;
	private $log;
	private $transport;
	private $request;
	private $loan_data;
	private $val_obj;
	private $agent_id;
	private $company_id;
	private $customer;

	protected $ach_card_payment_type_map = array(
	"loan_disbursement" => "card_disbursement",
	"repayment_principal" => "card_repayment_principal",
	"payment_service_chg" => "card_payment_service_chg",
	"assess_fee_ach_fail" => "assess_fee_card_fail",
	"payment_fee_ach_fail" => "payment_fee_card_fail",
	"full_balance" => "card_full_balance",
	"paydown" => "card_paydown",
	"payout" => "card_payout",
	"payment_arranged" => "card_payment_arranged",
	"payment_manual" => "card_payment_manual",
	"refund" => "card_refund",
	"cancel" => "card_cancel",
	"writeoff_fee_ach_fail" => "writeoff_fee_card_fail",
	"chargeback" => "card_chargeback",
	"chargeback_reversal" => "card_chargeback_reversal"
	);

	public function __construct(Server $server, $request)
	{
		$this->server = $server;
		$this->log = ECash::getLog();
		$this->request = $request;
		$this->loan_data = new Loan_Data($server);
		$this->timer = ECash::getMonitoring()->getTimer();
		//I hate this implementation [JustinF]
		if(file_exists(CUSTOMER_LIB . 'company_validate.class.php'))
		{
			require_once(CUSTOMER_LIB . 'company_validate.class.php');
			$this->val_obj = new Company_Validate($server);
		}
		else
		{
			require_once(SERVER_CODE_DIR . 'validate.class.php');
			$this->val_obj = new Validate($server);
		}
		$this->agent_id = ECash::getAgent()->AgentId;
		$this->company_id = ECash::getCompany()->company_id;
	}

	public function Save_Personal()
	{
		//mantis:4416
		if (in_array("ssn_last_four_digits", ECash::getTransport()->Get_Data()->read_only_fields))
			$this->request->ssn = $_SESSION['current_app']->ssn;
		$validation_errors = $this->val_obj->Validate_Personal($this->request);
		
		if( !count($validation_errors) )
		{
			$normalized = (array)$this->val_obj->Get_Last_Normalized();
			foreach($normalized as $key => $value)
			{
			   $this->request->$key = $value;
			}

                        $set_to_agree = false;
                        $fields_to_check = array("name_first","name_last","legal_id_number","legal_id_state",
                                                "street","unit","city","state","county","zip","customer_email");

			//load the application and response
			$return_obj = $this->loan_data->Fetch_Loan_All($this->request->application_id);
                        $old_residence_start_date = strtotime($return_obj->residence_start_date);

			$application = ECash::getApplication();
                        $old_dob = $application->Model->dob;
			//$old_name_first = strtolower($application->Model->name_first);
			//$old_name_last = strtolower($application->Model->name_last);

			//set the changed vars
			try
			{
					ECash::getFactory()->getDisplay('LegacySavePersonal')->toModel($this->request, $application->model);
                                        $new_dob = $this->request->EditAppPersonalInfoCustDobyear .'-'. $this->request->EditAppPersonalInfoCustDobmonth .'-'. $this->request->EditAppPersonalInfoCustDobday;
					//$new_name_first = strtolower($this->request->name_first);
					//$new_name_last = strtolower($this->request->name_last);
                                        $new_residence_start_date = strtotime($this->request->residence_start_date);

                                        foreach ($fields_to_check as $field)
                                        {
                                                if (
                                                        trim(strtolower($return_obj->{$field})) != trim(strtolower($this->request->{$field}))
                                                )
                                                {
                                                        $set_to_agree = true;
                                                        brake;
                                                }
                                        }

                                        if($application->isAltered()
                                                &&
                                                ($set_to_agree
                                                        || $new_dob != $old_dob
                                                        || $new_residence_start_date != $old_residence_start_date
							//|| $new_name_first != $old_name_first
							//|| $new_name_last != $old_name_last
                                                )
                                        )
                                        {
                                                $this->Set_In_Process($this->request->application_id);
                                        }

					//save
					$application->model->save();
					$this->Check_Fraud($this->request->application_id);
					$fields = array('name_first','name_last','dob_year','dob_month',
									'dob_day','street','unit','city','county','state','zip','residence_start_date','customer_email','legal_id_number','legal_id_state');

					//set changed vars on the display obj
					ECash::getFactory()->getDisplay('LegacySavePersonal')->toResponse($return_obj, $application->model);
			}
			catch(DB_Models_ReadOnlyException $e)
			{
				$js = "<script>alert('This application is in a read-only status\\nNo changes will be saved'); </script>";
			}
            // Tracing through this code, I've found that the Fetch_Loan_All does not know the changes in
            // banking_start_date, therefore cannot get a properly updated duration. The current ecash_commercial
            // has the Fetch_Loan_All after saving the application, and it does fix the issue. A more elegant
            // solution could probably be made, but I'm not going to rewrite this system right now. [benb] [5747]

			//set the crap we've changed in leiu of calling fetch_loan_all again
			$return_obj = $this->loan_data->Fetch_Loan_All($this->request->application_id);
			if ($js) 
			{
				$return_obj->has_js = $js;
			}
			$_SESSION['current_app'] = $return_obj;

			ECash::getTransport()->Add_Levels('overview','personal','view','general_info','view');
		}
		else
		{
			$return_obj = $_SESSION['current_app'];
			$return_obj->validation_errors = $validation_errors;
			$return_obj->saved_error_data = $this->request;

			ECash::getTransport()->Add_Levels('overview','personal','edit', 'general_info', 'view');
		}

		ECash::getTransport()->Set_Data($return_obj);
	}

	// This is used by the outgoing call dispositions screen, I didn't name it Outgoing_Dispositions
	// because I don't see the need for two functions for something that invariably does the same thing.
	// This expects the following to be in the request:
	//   application_id
	//   curmodule
	//   curmode
	//   agent_id
	public function Save_Dispositions()
	{
		$validation_errors = $this->val_obj->Validate_Dispositions($this->request);

		if(! count($validation_errors))
		{
            $normalized = (array)$this->val_obj->Get_Last_Normalized();
            foreach($normalized as $key => $value)
            {
               $this->request->$key = $value;
            }

			$return_obj = $this->loan_data->Fetch_Loan_All($this->request->application_id);
			//set the references to nil
			$return_obj->references = array();

			foreach($this->request as $field_name => $value)
			{
				switch ($field_name)
				{
					case 'loan_action':
						// centralize somewhere
						$contact_dispositions = array('talked_to_customer', 'promise_to_pay_cc', 'promise_to_pay_moneygram', 'customer_bankruptcy', 'customer_cccs', 'manager_callback', 'setup_callback', 'talked_no_promise', 'manual_notes' );

						$contact_made = FALSE;

						if (is_array($value))
						{
							foreach($value as $key => $data)
							{
								$las = ECash::getFactory()->getModel('LoanActionSection');
								$la  = ECash::getFactory()->getModel('LoanActions');

								if ($la->loadBy(array('loan_action_id' => $data)))
								{
									if (in_array($la->name_short, $contact_dispositions))
										$contact_made = TRUE;


									// We're not logging "No Call"
									if ($la->name_short == 'no_call')
										continue;
								}
							
								// I'm using CLK's schema, so I have to do some stupid things
								$name = $this->request->curmodule;
								$name = strtoupper($name) . "_" . $key;
							
							 	// Only if we have an associated loan action section
								if ($las->loadBy(array('name_short' => $name)))
								{
									$is_resolved = isset($this->request->{$key . "_flag"}) ? 1 : 0;
									// Log the loan action
									$lah = ECash::getFactory()->getModel('LoanActionHistory');

									$lah->application_id         = $this->request->application_id;
									$lah->date_created           = date('Y-m-d H:i:s');
									$lah->agent_id               = $this->request->agent_id;
									$lah->loan_action_id         = $data;
									$lah->loan_action_section_id = $las->loan_action_section_id;
									$lah->application_status_id  = $return_obj->application_status_id;
									$lah->is_resolved	= $is_resolved;
									$lah->save();	

								}
							}
						}

						if ($contact_made)
						{
							// If agent group has control feature 'lock_collections_queue'
							// unlock the collections queues here
						}

						break;
					case 'canned_comment':
						// Placeholder for future functionality
						break;
					case 'free_comment':
						if (!empty($value))
						{
							$resolved = (isset($this->request->free_comment_flag)) ? TRUE : FALSE;
							// If a comment exists add it
							$comments = ECash::getApplicationById($this->request->application_id)->getComments();
							$comments->add($this->request->free_comment, $this->request->agent_id, NULL, NULL, NULL, $resolved);
						}
						break;
					default:
						break;
				}
			}

			$return_obj = $this->loan_data->Fetch_Loan_All($this->request->application_id);
			$_SESSION['current_app'] = $return_obj;
	  	  	ECash::getApplicationById($this->request->application_id);
		        $engine = ECash::getEngine();		
	   	        $engine->executeEvent('CALL_DISPOSITION', array());
		}
		else
  		{
			$return_obj = $_SESSION['current_app'];
			$return_obj->validation_errors = $validation_errors;
			$return_obj->saved_error_data = $this->request;

		}

		ECash::getTransport()->Set_Data($this->loan_data->Fetch_Loan_All($this->request->application_id));
		ECash::getTransport()->Set_Levels('close_pop_up');
	}

	public function Save_Personal_References()
	{
		$validation_errors = $this->val_obj->Validate_Personal_References($this->request);

		if(! count($validation_errors))
		{
            $normalized = (array)$this->val_obj->Get_Last_Normalized();
            foreach($normalized as $key => $value)
            {
               $this->request->$key = $value;
            }

			$return_obj = $this->loan_data->Fetch_Loan_All($this->request->application_id);
			//set the references to nil
			$return_obj->references = array();

			$personal_reference_model = ECash::getFactory()->getModel('PersonalReference');
			$models = $personal_reference_model->loadAllBy(array("application_id" => $this->request->application_id));

			foreach($this->request as $field_name => $value)
			{
				// Try to match the ref_name
				if(preg_match('/^ref_name_(\d{1,2})/', $field_name, $matches) && !empty($value))
				{
					$ref_num = $matches[1];
					// Get the key
					$key = $this->request->{'personal_ref_id_' . $ref_num};
					
					// No key means a new record
					if (empty($key))
					{
						$personal_reference = ECash::getFactory()->getModel('PersonalReference');
					}
					// Otherwise find the key on the models for the app
					else
					{
						// Loop through the models to get the model with the matching primary key
						foreach ($models as $model)
						{
							if ($model->personal_reference_id == $this->request->{'personal_ref_id_' . $ref_num})
							{
								$personal_reference = $model;
								break;
							}
						}
						reset($models);
					}

					ECash::getFactory()->getDisplay('LegacySavePersonalReference')->toModel($this->request, $personal_reference, $ref_num);
					$personal_reference->save();
					ECash::getFactory()->getDisplay('LegacySavePersonalReference')->toResponse($return_obj, $personal_reference, $ref_num);
				}
			}
			
			$_SESSION['current_app'] = $return_obj;
			ECash::getTransport()->Add_Levels('overview', 'personal_reference', 'view', 'general_info', 'view');
		}
		else
  		{
			$return_obj = $_SESSION['current_app'];
			$return_obj->validation_errors = $validation_errors;
			$return_obj->saved_error_data = $this->request;
			ECash::getTransport()->Add_Levels('overview','personal_reference','edit', 'general_info', 'view');
		}
		ECash::getTransport()->Set_Data($return_obj);

	}

	public function Save_Employment()
	{
		$validation_errors = $this->val_obj->Validate_Employment($this->request);

		if( !count($validation_errors) )
		{
//			$this->request = (object) array_merge((array)$this->request, (array)$this->val_obj->Get_Last_Normalized());
            		$normalized = (array)$this->val_obj->Get_Last_Normalized();
            		foreach($normalized as $key => $value)
            		{
               			$this->request->$key = $value;
            		}

			//load the application and response
			$return_obj = $this->loan_data->Fetch_Loan_All($this->request->application_id);
			$old_employer_name = $return_obj->employer_name;
			$old_phone_work = $return_obj->phone_work;
			$old_phone_work_ext = $return_obj->phone_work_ext;
			$old_income_monthly = $return_obj->income_monthly;
			$old_date_hire = strtotime($return_obj->date_hire);

			//get the loaded application
			$application = ECash::getApplicationById($this->request->application_id);

			try
			{
				//set the changed vars
				ECash::getFactory()->getDisplay('LegacySaveEmployment')->toModel($this->request, $application->model);
				$new_employer_name = $this->request->employer_name;
				$new_date_hire = strtotime($this->request->date_hire);

				if(
					//$application->isAltered()
					//&&
					(
						trim(strtolower($old_employer_name)) != trim(strtolower($new_employer_name))
						|| $old_phone_work != $this->request->phone_work
						|| $old_phone_work_ext != $this->request->phone_work_ext
						|| intval($old_income_monthly) != intval($this->request->income_monthly)
						|| $old_date_hire != $new_date_hire
					)
				)
				{
				        $this->Set_In_Process($this->request->application_id);
				}

				$application->save();
				$this->Check_Fraud($this->request->application_id);
				ECash::getFactory()->getDisplay('LegacySaveEmployment')->toResponse($return_obj, $application->model);
			}
			catch(DB_Models_ReadOnlyException $e)
			{
				$js = "<script>alert('This application is in a read-only status\\nNo changes will be saved'); </script>";
			}
			// GF #15110: Got rid of crappy calculation that doesn't take into account NULL values
			// also doesn't take into account that the mysql datatype for this field can be before
			// the epoch, and shouldn't use UNIX timestamps. [benb]
			if ($this->request->date_hire != NULL)
			{
				// This is going to be hackish, this is NOT a unix timestamp, therefore
				// I cannot do strtotime(), etc, and the date is in two different forms
				// at this point in the code, both m/d/Y and Y-m-d, so I need to account
				// for both. [benb]
				// Since a person can theoretically have worked at the same job
				// since before the epoch (maybe that have excellent fringe benefits)
				// I need to treat this like it's not a UNIX timestamp. [benb]
				if (strchr($this->request->date_hire, '/'))
				{
					list($month, $day, $year) = explode('/', $this->request->date_hire);
				}
				else
				{
					list($year, $month, $day) = explode('-', $this->request->date_hire);
				}

				$yrs     = date('Y') - $year;

				$tmonths = date('m');

				if ($tmonths < $month)
				{
					$tmonths += 12;
					$yrs--;
				}

				$mos = $tmonths - $month;

				$return_obj->date_hire           = "$year-$month-$day";
				$return_obj->employment_duration = "{$yrs}yrs {$mos}mos";
			}
			else
			{
				$return_obj->date_hire           = NULL;
				$return_obj->employment_duration = 'n/a';
			}
			
			$return_obj = $this->loan_data->Fetch_Loan_All($this->request->application_id);

			//set the crap we've changed in leiu of calling fetch_loan_all again
			$_SESSION['current_app'] = $return_obj;

			ECash::getTransport()->Add_Levels('overview','employment','view','general_info','view');
		}
		else
		{
			$return_obj = $_SESSION['current_app'];
			$return_obj->validation_errors = $validation_errors;
			$return_obj->saved_error_data = $this->request;

			ECash::getTransport()->Add_Levels('overview','employment','edit', 'general_info', 'view');
		}
		if ($js) 
		{
			$return_obj->has_js = $js;
		}
		ECash::getTransport()->Set_Data($return_obj);

	}

	/**
	 * @TODO the application status setting at the end of this method
	 * should probably be done by business rules as this status may be
	 * CLK specific [JustinF]
	 *
	 * This method is also currently untested
	 */
	public function Save_Application($use_session_data = FALSE, $strip_first_due_date = FALSE)
	{
		if( $use_session_data !== FALSE)
		{
			$validation_errors = $this->val_obj->Validate_Application($_SESSION['current_app']);
			$data_obj = $_SESSION['current_app'];
		}
		else
		{
			$validation_errors = $this->val_obj->Validate_Application($this->request);
			$data_obj = $this->request;
		}

		if ($strip_first_due_date)
		{
			$data_obj->date_first_payment_year = "";
			$data_obj->date_first_payment_month = "";
			$data_obj->date_first_payment_day = "";
			$data_obj->new_first_due_date = 'no';
		}

		if( !count($validation_errors) )
		{
			$business_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());

		//	$data_obj = (object) array_merge((array)$data_obj, (array)$this->val_obj->Get_Last_Normalized());
			$normalized = (array)$this->val_obj->Get_Last_Normalized();
			foreach($normalized as $key => $value)
			{
				$data_obj->$key = $value;
			}

			$finance_charge = $business_rules->Calc_Original_Service_Charge_On_Loan($data_obj->application_id, $data_obj->fund_amount);
			$data_obj->finance_charge = $finance_charge;
			$data_obj->payment_total = $data_obj->fund_amount + $finance_charge;


			$application = ECash::getApplicationById($this->request->application_id);			
			$old_fund_amount = $application->Model->fund_actual;
			$old_rate_override = $application->getRate();
			$old_income_direct_deposit = $application->Model->income_direct_deposit;
			ECash::getFactory()->getDisplay('LegacySaveApplication')->toModel($this->request, $application->model);
			try 
			{
				if($use_session_data === TRUE)
				{
					if (isset($this->request->new_first_due_date) && ($this->request->new_first_due_date == 'yes')
						|| $old_fund_amount != $data_obj->fund_amount
						|| $old_rate_override != $application->getRate()
					)
					{
						$this->Set_In_Process($this->request->application_id);
					}
					$application->save();
				}
                                else if($application->isAltered())
				{
					if (isset($this->request->new_first_due_date) && ($this->request->new_first_due_date == 'yes')
						|| $old_fund_amount != $data_obj->fund_amount
						|| $old_rate_override != $application->getRate()
						|| strtolower($old_income_direct_deposit) != strtolower($this->request->income_direct_deposit)
					)
					{
						$this->Set_In_Process($this->request->application_id);
					}
					$application->save();
				}
			}			
			catch(DB_Models_ReadOnlyException $e)
			{
				$js = "<script>alert('This application is in a read-only status\\nNo changes will be saved'); </script>";
			}
			
			//after all that initial saving, we HAVE TO fetch everything again, because fetch_loan_all does processing on 
			//the data we updated.			
			$return_obj = $this->loan_data->Fetch_Loan_All($this->request->application_id);
			ECash::getFactory()->getDisplay('LegacySaveApplication')->toResponse($return_obj, $application->model);
			$_SESSION['current_app'] = $return_obj;
			ECash::getTransport()->Add_Levels('overview','application_info','view','general_info','view');
		}
		else
		{
			$return_obj = $_SESSION['current_app'];
			$return_obj->validation_errors = $validation_errors;
			$return_obj->saved_error_data = $data_obj;

			ECash::getTransport()->Add_Levels('overview','application_info','edit', 'general_info', 'view');
		}
		if ($js) 
		{
			$return_obj->has_js = $js;
		}
		ECash::getTransport()->Set_Data($return_obj);
	}

	public function Save_Card_Info()
	{
		$validation_errors = $this->val_obj->Validate_Card($this->request, $application->rule_set_id);
		$js = NULL;
		
		if ( !count($validation_errors) )
		{
			try
			{
				$normalized = (array)$this->val_obj->Get_Last_Normalized();
				foreach($normalized as $key => $value)
				{
					// hax
					if ($key != 'cardholder_name')
						$this->request->$key = $value;
				}
				$return_obj = $this->loan_data->Fetch_Loan_All($this->request->application_id);
				$application = ECash::getApplication();

				// Load the card info from the DB
				$card_info = ECash::getFactory()->getModel('CardInfo');
				$edited = $card_info->loadBy(array('card_info_id' => $this->request->payment_card_id));

				if (!$edited) {
					$action = 'add';
					$card_info->date_created = date('Y-m-d H:i:s');
					//$card_info->date_created = time();
				} else {
					$action = 'edit';
				}

				// Get the card types
				$CardTypes = ECash::getFactory()->getModel('CardTypeList');
				$CardTypes->loadBy(array()); 

				$card_types = array();
				foreach($CardTypes as $CardType)
				{
					$card_types[$CardType->name_short] = $CardType;
				}

				// Detect the card type
				$ct = $card_types[Payment_Card::Get_Card_Type_By_Card_Number($this->request->card_number)];

				// Determine altered columns.
				$altered = array();
				$before = array();
				$after = array();

				if ($card_info->card_type_id != $ct->card_type_id)
				{
					$altered[] = 'card_type_id';
					$before['card_type_id'] = $card_info->card_type_id;
					$after['card_type_id'] = $ct->card_type_id;
				}

				if ($card_info->card_number != Payment_Card::encrypt($this->request->card_number))
				{
					$altered[] = 'card_number';
					$before['card_number'] = $card_info->card_number;
					$after['card_number'] = Payment_Card::encrypt($this->request->card_number);
				}

				if ($card_info->cardholder_name != Payment_Card::encrypt($this->request->cardholder_name))
				{
					$altered[] = 'cardholder_name';
					$before['cardholder_name'] = $card_info->cardholder_name;
					$after['cardholder_name'] = Payment_Card::encrypt($this->request->cardholder_name);
				}

				if ($card_info->card_street != $this->request->card_street)
				{
					$altered[] = 'card_street';
					$before['card_street'] = $card_info->card_street;
					$after['card_street'] = $this->request->card_street;
				}

				if ($card_info->card_zip != $this->request->card_zip)
				{
					$altered[] = 'card_zip';
					$before['card_zip'] = $card_info->card_zip;
					$after['card_zip'] = $this->request->card_zip;
				}

				$card_info->card_type_id    = $ct->card_type_id;
				$card_info->card_number     = Payment_Card::encrypt($this->request->card_number);
				$card_info->cardholder_name = Payment_Card::encrypt($this->request->cardholder_name);
				$card_info->application_id  = $this->request->application_id;
				$card_info->card_street     = $this->request->card_street;
				$card_info->card_zip        = $this->request->card_zip;

				if (strtotime($card_info->expiration_date) != strtotime(($this->request->card_exp1 . '/01/' . $this->request->card_exp2)))
				{
					$before['expiration_date'] = $card_info->expiration_date;
					//$after['expiration_date'] = $this->request->card_exp1 . '/01/' . $this->request->card_exp2;
					$after['expiration_date'] = date("Y-m-d", strtotime($this->request->card_exp1 . '/01/' . $this->request->card_exp2));

					$card_info->expiration_date = ($this->request->card_exp1 . '/01/' . $this->request->card_exp2);
					$altered[] = 'expiration_date';
				}

				if ($card_info->isAltered())
				{
					$card_info->save();

					foreach ($altered as $altered_column)
					{
						$card_audit = NULL;
						$card_audit = ECash::getFactory()->getModel('CardAudit');

						$card_audit->date_created = date("Y-m-d H:i:s", time());
						$card_audit->company_id = $this->company_id;
						$card_audit->application_id = $this->request->application_id;
						$card_audit->table_name = 'card_info';
						$card_audit->column_name = $altered_column;
						$card_audit->value_before = $before[$altered_column];
						$card_audit->value_after = $after[$altered_column];
						$card_audit->agent_id = ECash::getAgent()->getAgentId();

						$card_audit->setInsertMode(DB_Models_WritableModel_1::INSERT_STANDARD);
						$card_audit->insert();
					}

					$card_action = ECash::getFactory()->getModel('CardAction');
					$card_action->loadBy(array('name_short' => $action));

					$card_action_history = ECash::getFactory()->getModel('CardActionHistory');
					$card_action_history->date_created   = time();
					$card_action_history->card_action_id = $card_action->card_action_id;
					$card_action_history->card_info_id   = $card_info->card_info_id;
					$card_action_history->application_id = $this->request->application_id;
					$card_action_history->agent_id       = ECash::getAgent()->getModel()->agent_id;

					if (count($altered) > 0)
					{
						$altered_fields = implode(',', $altered);
						$card_action_history->changed_fields = $altered_fields;
					}

					// The credit card information is modified, remove the fatal_card flag!
					$app_flags = new Application_Flags($this->server, $this->request->application_id);
					if ($app_flags->Get_Flag_State('has_fatal_card_failure'))
					{ 
						$app_flags->Clear_Flag('has_fatal_card_failure', Array($this->server->Get_Active_Module()));
					}

					$card_action_history->save();
				}

				// Inactivate the card, log a separate request
				if ($this->request->inactivate_card == 'on')
				{
					$card_info->active_status = 'inactive';

					$card_info->save();
					$card_action = ECash::getFactory()->getModel('CardAction');
					$card_action->loadBy(array('name_short' => 'inactivate'));

					$card_action_history = ECash::getFactory()->getModel('CardActionHistory');
					$card_action_history->date_created   = time();
					$card_action_history->card_action_id = $card_action->card_action_id;
					$card_action_history->card_info_id   = $card_info->card_info_id;
					$card_action_history->application_id = $this->request->application_id;
					$card_action_history->agent_id       = ECash::getAgent()->getModel()->agent_id;

					$card_action_history->save();
				}

			}
			catch (Exception $e)
			{
				$js = "<script>alert('The encryption server is down. Your changes were not saved.'); </script>";
			}

			$return_obj = $this->loan_data->Fetch_Loan_All($this->request->application_id);
			$_SESSION['current_app'] = $return_obj;
			ECash::getTransport()->Add_Levels('view');
		}
		else
		{
			$return_obj = $_SESSION['current_app'];
			$return_obj->validation_errors = $validation_errors;
			$return_obj->saved_error_data = $this->request;

			ECash::getTransport()->Add_Levels('edit');
		}

		if ($js != NULL)
			$return_obj->has_js = $js;

		ECash::getTransport()->Set_Data($return_obj);
	}

	/**
	 * For [#29877]
	 */
	public function Remove_Fatal_ACH()
	{
		$app_flags = new Application_Flags($this->server, $this->request->application_id);
		if ($app_flags->Get_Flag_State('has_fatal_ach_failure'))
		{ 
			$app_flags->Clear_Flag('has_fatal_ach_failure', array($this->server->Get_Active_Module()));
			$this->log->Write("[Agent:{$this->agent_id}][AppID:{$this->request->application_id}] Removed Fatal ACH");
			$comments = ECash::getApplicationById($this->request->application_id)->getComments();
			$comments->add('Removed Fatal ACH', $this->agent_id);
			$agent = ECash::getAgent();
			$agent->getTracking()->add('removed_fatal_ach', $this->request->application_id);
		}
	}

	public function Remove_Fatal_Card()
	{
		$app_flags = new Application_Flags($this->server, $this->request->application_id);
		if ($app_flags->Get_Flag_State('has_fatal_card_failure'))
		{ 
			$app_flags->Clear_Flag('has_fatal_card_failure', array($this->server->Get_Active_Module()));
			$this->log->Write("[Agent:{$this->agent_id}][AppID:{$this->request->application_id}] Removed Fatal Card");
			$comments = ECash::getApplicationById($this->request->application_id)->getComments();
			$comments->add('Removed Fatal Card', $this->agent_id);
			$agent = ECash::getAgent();
			$agent->getTracking()->add('removed_fatal_card', $this->request->application_id);
		}
	}
	
	public function Save_General_Info()
	{
		$validation_errors = $this->val_obj->Validate_General_info($this->request, $application->rule_set_id);

		if( !count($validation_errors) )
		{
			$normalized = (array)$this->val_obj->Get_Last_Normalized();
			foreach($normalized as $key => $value)
			{
			   $this->request->$key = $value;
			}

			$set_to_agree = false;
			$fields_to_check = array("name_first","name_last","phone_home","phone_cell",
						"phone_work","phone_work_ext","customer_email",
						"income_monthly");

			$return_obj = $this->loan_data->Fetch_Loan_All($this->request->application_id);
			$old_banking_start_date = strtotime($return_obj->banking_start_date);

			$application = ECash::getApplication();
			$old_income_monthly = $application->Model->income_monthly;

			$rebuild_statuses = array(  'active::servicing::customer::*root',
				'hold::servicing::customer::*root',
				'past_due::servicing::customer::*root',
				'indef_dequeue::collections::customer::*root',
				'new::collections::customer::*root',
				'skip_trace::collections::customer::*root',
				'arrangements_failed::arrangements::collections::customer::*root',
				'current::arrangements::collections::customer::*root',
				'arrangements_failed::arrangements::collections::customer::*root',
				'amortization::bankruptcy::collections::customer::*root',
				'unverified::bankruptcy::collections::customer::*root',
				'dequeued::contact::collections::customer::*root',
				'follow_up::contact::collections::customer::*root',
				'queued::contact::collections::customer::*root'
			 );
			try
			{	
	
				ECash::getFactory()->getDisplay('LegacySaveGeneralInfo')->toModel($this->request, $application->model);	

				foreach ($fields_to_check as $field)
				{
					if (
						trim(strtolower($return_obj->{$field})) != trim(strtolower($this->request->{$field}))
					)
					{
						$set_to_agree = true;
						break;
					}
				}

				if($application->isAltered() && $set_to_agree)
				{
					$this->Set_In_Process($this->request->application_id);
				}

				$application->save();
				$this->Check_Fraud($this->request->application_id);
				ECash::getFactory()->getDisplay('LegacySaveGeneralInfo')->toResponse($return_obj, $application->model);
			}
			catch(DB_Models_ReadOnlyException $e)
			{
				$js = "<script>alert('This application is in a read-only status\\nNo changes will be saved'); </script>";
			}

                        if (in_array($status_chain, $rebuild_statuses))
                        {
                                // Rebuild the schedule
                                Complete_Schedule($this->request->application_id);
                        }
                        else if (Status_Chain_Needs_Resigned($status_chain))
                        {
                                // Remove any unregistered events
                                Remove_Unregistered_Events_From_Schedule($this->request->application_id);
                        }

			// Tracing through this code, I've found that the Fetch_Loan_All does not know the changes in
			// banking_start_date, therefore cannot get a properly updated duration. The current ecash_commercial
			// has the Fetch_Loan_All after saving the application, and it does fix the issue. A more elegant
			// solution could probably be made, but I'm not going to rewrite this system right now. [benb] [5667]

			$return_obj = $this->loan_data->Fetch_Loan_All($this->request->application_id);

			//Recalculate fund_qualified
			if (trim($this->request->income_monthly) != trim($old_income_monthly))
			{
				//$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
				$loan_amount_allowed = $return_obj->loan_amount_allowed;
				$application->fund_qualified = $loan_amount_allowed;
				
				$status = $application->getStatus();
				if(
					(
						in_array($status->level1, array('applicant','prospect'))
						|| in_array($status->level2, array('applicant'))
					)
					&& isset($application->fund_actual)
					&& ($application->fund_actual > 0)
				)
				{
					$application->fund_actual = $loan_amount_allowed;
				}
				
				$application->save();
				$return_obj = $this->loan_data->Fetch_Loan_All($this->request->application_id);
			}

			$_SESSION['current_app'] = $return_obj;

			ECash::getTransport()->Add_Levels('view'); // mantis:3561
		}
		else
		{
			$return_obj = $_SESSION['current_app'];
			$return_obj->validation_errors = $validation_errors;
			$return_obj->saved_error_data = $this->request;

			ECash::getTransport()->Add_Levels('edit');
		}
		if ($js) 
		{
			$return_obj->has_js = $js;
		}
		ECash::getTransport()->Set_Data($return_obj);
	}
	
	public function Save_Bank_Info()
	{
		$validation_errors = $this->val_obj->Validate_Bank_info($this->request, $application->rule_set_id);

		if( !count($validation_errors) )
		{
			$normalized = (array)$this->val_obj->Get_Last_Normalized();
			foreach($normalized as $key => $value)
			{
			   $this->request->$key = $value;
			}

			$set_to_agree = false;
			$fields_to_check = array("bank_aba","bank_account","bank_account_type",
						"income_direct_deposit","bank_name");

			$return_obj = $this->loan_data->Fetch_Loan_All($this->request->application_id);
			$old_banking_start_date = strtotime($return_obj->banking_start_date);

			$application = ECash::getApplication();

			$rebuild_statuses = array(  'active::servicing::customer::*root',
				'hold::servicing::customer::*root',
				'past_due::servicing::customer::*root',
				'indef_dequeue::collections::customer::*root',
				'new::collections::customer::*root',
				'skip_trace::collections::customer::*root',
				'arrangements_failed::arrangements::collections::customer::*root',
				'current::arrangements::collections::customer::*root',
				'arrangements_failed::arrangements::collections::customer::*root',
				'amortization::bankruptcy::collections::customer::*root',
				'unverified::bankruptcy::collections::customer::*root',
				'dequeued::contact::collections::customer::*root',
				'follow_up::contact::collections::customer::*root',
				'queued::contact::collections::customer::*root'
			 );
			try
			{	
				// OK changing how things are working again, I'll be working off a list given to me by will
				// so if anything is incorrect, feel free to direct blame to the appropriate person, will.
				// If the direct deposit information is changed
				if ($return_obj->income_direct_deposit != $this->request->income_direct_deposit)
				{
	
					$status_chain = $return_obj->status . (($return_obj->level1) ? "::{$return_obj->level1}" : "") . 
														  (($return_obj->level2) ? "::{$return_obj->level2}" : "") .
														  (($return_obj->level3) ? "::{$return_obj->level3}" : "") .
														  (($return_obj->level4) ? "::{$return_obj->level4}" : "") .
														  (($return_obj->level5) ? "::{$return_obj->level5}" : "");
	

					$set_to_agree = true;
				}

				// If the ABA, Bank Account, or Bank account type is modified, remove the fatal_ach flag!
				if(
					$return_obj->bank_aba != $this->request->bank_aba ||
					$return_obj->bank_account != $this->request->bank_account ||
					$return_obj->bank_account_type != $this->request->bank_account_type
					)
				{
					$app_flags = new Application_Flags($this->server, $this->request->application_id);
					if ($app_flags->Get_Flag_State('has_fatal_ach_failure'))
					{ 
						$app_flags->Clear_Flag('has_fatal_ach_failure', Array($this->server->Get_Active_Module()));
					}
				}
	
				ECash::getFactory()->getDisplay('LegacySaveBankInfo')->toModel($this->request, $application->model);	
				$new_banking_start_date = strtotime($this->request->banking_start_date);

				foreach ($fields_to_check as $field)
				{
					if (
						trim(strtolower($return_obj->{$field})) != trim(strtolower($this->request->{$field}))
					)
					{
						$set_to_agree = true;
						brake;
					}
				}

				if($application->isAltered()
					&&
					($set_to_agree
						|| $new_banking_start_date != $old_banking_start_date
					)
				)
				{
					$this->Set_In_Process($this->request->application_id);
				}

				$application->save();
				$this->Check_Fraud($this->request->application_id);
				ECash::getFactory()->getDisplay('LegacySaveBankInfo')->toResponse($return_obj, $application->model);
			}
			catch(DB_Models_ReadOnlyException $e)
			{
				$js = "<script>alert('This application is in a read-only status\\nNo changes will be saved'); </script>";
			}

                        if (in_array($status_chain, $rebuild_statuses))
                        {
                                // Rebuild the schedule
                                Complete_Schedule($this->request->application_id);
                        }
                        else if (Status_Chain_Needs_Resigned($status_chain))
                        {
                                // Remove any unregistered events
                                Remove_Unregistered_Events_From_Schedule($this->request->application_id);
                        }

			// Tracing through this code, I've found that the Fetch_Loan_All does not know the changes in
			// banking_start_date, therefore cannot get a properly updated duration. The current ecash_commercial
			// has the Fetch_Loan_All after saving the application, and it does fix the issue. A more elegant
			// solution could probably be made, but I'm not going to rewrite this system right now. [benb] [5667]

			$return_obj = $this->loan_data->Fetch_Loan_All($this->request->application_id);
			$_SESSION['current_app'] = $return_obj;

			ECash::getTransport()->Add_Levels('view'); // mantis:3561
		}
		else
		{
			$return_obj = $_SESSION['current_app'];
			$return_obj->validation_errors = $validation_errors;
			$return_obj->saved_error_data = $this->request;

			ECash::getTransport()->Add_Levels('edit');
		}
		if ($js) 
		{
			$return_obj->has_js = $js;
		}
		ECash::getTransport()->Set_Data($return_obj);
	}

	public function Save_Wizard()
	{
		$db = ECash::getMasterDb();
		
		$fields_to_check = array("model_name","paydate_model","frequency_name","day_string_one","day_of_week",
						"day_int_one","day_of_month_1","day_int_two","day_of_month_2","week_one","week_1",
						"week_two","week_2","date_first_payment","day_int_2","week2"
		);

		$old_info = Get_Transactional_Data($this->request->application_id, $db)->info;

		$this->request->paydate['frequency'] = strtolower($this->request->paydate['frequency']);
		try
		{
			//$data = ECash::getFactory()->getModel("Application");
			//$data->loadBy(array('application_id' => $this->request->application_id));
			$data = ECash::getApplicationById($this->request->application_id);
			/* @var $display CFE_Display_LegacySaveWizard */
			$display = ECash::getFactory()->getDisplay('LegacySaveWizard');
			$display->toModel($this->request, $data->getModel());
		}
		catch(Exception $e)
		{
			ECash::getTransport()->Set_Levels('popup', 'wizard_error');
			ECash::getTransport()->Set_Data((object)(array($e->getMessage())));
			return;
		}

		try {
			// Grab the schedule, then check to see if they have any sort of arrangements.
			$schedule = Fetch_Schedule($this->request->application_id);
			$status   = Analyze_Schedule($schedule);

			$app = ECash::getApplicationById($this->request->application_id);
			$app_status = $app->getStatus();
			$data->modifying_agent_id = Fetch_Current_Agent($this->server);
			$affected_rows = $data->save();

			$new_info = Get_Transactional_Data($this->request->application_id, $db)->info;

			foreach ($fields_to_check as $field)
			{
				if (empty($old_info->{$field}) && empty($new_info->{$field})) continue;

				if ($old_info->{$field} != $new_info->{$field})
				{
					$this->Set_In_Process($this->request->application_id);
					brake;
				}
			}

			// If they either don't have a schedule or have arrangements,
			// we don't need to reschedule anything
			if(($status->has_arrangements != true) && (count($schedule) > 0 || $app_status->level0 == 'approved'))
			{
				$this->log->Write("Propogating model change to schedule for {$this->request->application_id}");

				//asm 66
				//before
				$is_active = ($app_status->level0 == 'active');
				if ($is_active)
				{
					$status_before = Analyze_Schedule($schedule, TRUE);
					$next_due_date_before = $status_before->next_due_date;
					$next_due_date_before = str_replace("-", "/", $next_due_date_before);
					$next_due_date_before_ut = strtotime($next_due_date_before);
				}
				////////

				Complete_Schedule($this->request->application_id);

				//asm 66
				//after
				if ($is_active)
				{
					$schedule_after = Fetch_Schedule($this->request->application_id);
					$status_after = Analyze_Schedule($schedule_after, TRUE);
					$next_due_date_after = $status_after->next_due_date;
					$next_due_date_after = str_replace("-", "/", $next_due_date_after);
					$next_due_date_after_ut = strtotime($next_due_date_after);

					if ($next_due_date_after_ut > $next_due_date_before_ut)
					{
						$next_due_date_before = date("Y-m-d", $next_due_date_before_ut);
						$next_due_date_after = date("Y-m-d", $next_due_date_after_ut);

						$app_ach_provider_model = ECash::getFactory()->getModel('ApplicationDateEffective');				
						$app_ach_provider_model->date_modified = date("Y-m-d H:i:s", time());
						$app_ach_provider_model->date_created = date("Y-m-d H:i:s", time());
						$app_ach_provider_model->application_id = $this->request->application_id;
						$app_ach_provider_model->date_effective_before = $next_due_date_before;
						$app_ach_provider_model->date_effective_after = $next_due_date_after;
						$app_ach_provider_model->setInsertMode(DB_Models_WritableModel_1::INSERT_STANDARD);
						$app_ach_provider_model->insert();
					}
				}
				////////
			}

			
			// We only want to Send Loan Note if Paydate Wizard Changed and we are in UW/Verification
			if($affected_rows > 0 && ($_SESSION['current_app']->level2 == "applicant" ||
									  $_SESSION['current_app']->level0 == 'in_process') ) 
			{
				/**
				 * something tells me affected_rows is insufficient, but it appears
				 * that this function doesn't get called if there is no change to FPD
				 */
				$holidays = Fetch_Holiday_List();
				$pdc 	= new Pay_Date_Calc_3($holidays);
				$trans_data = Get_Transactional_Data($this->request->application_id);
				$rules = Prepare_Rules($trans_data->rules, $trans_data->info);
				if (!isset($rules['grace_period'])) $rules['grace_period'] = 10;

                $grace_days = $rules['grace_period'];
                
                // Include the reaction due date for the grace period for react apps
                if ($app->column_data['is_react']){
                    $react_due_time = strtotime($rules['react_grace_date']);
                    $react_due_offset = $react_due_time - time();
                    $react_due_offset = ceil($react_due_offset / (24 * 60 * 60));
                    
                    if ($react_due_offset > $grace_days) $grace_days = $react_due_offset;
                }
                
				$gp = $pdc->Get_Calendar_Days_Forward(date('m/d/Y'), $grace_days);
				$next_paydate = Get_Next_Payday($gp, $trans_data->info , $rules);
				$data->date_first_payment = strtotime($next_paydate['effective']);
				$data->save();

				//$this->Set_In_Process($this->request->application_id);
			}
		}
		catch(Exception $e) 
		{
			$this->log->Write("Exception: Error Saving Paydates!  " . $e->getMessage());
			$this->log->write($e->getTraceAsString());
		}

		ECash::getTransport()->Set_Levels('close_pop_up');
	}

	/**
	 * The user has requested an SSN change so this function
	 * will try to determine if the SSN can simply just change
	 * or if there is need for either a merge or split between
	 * customers.
	 *
	 * @TODO refactor with models -- not yet used by CFE
	 */
	public function Customer_SSN_Change()
	{
		//I think the company should be obtained from the application, not who the user is logged in with [JustinF]
		$company_id = $_SESSION['current_app']->company_id;
		$new_ssn = preg_replace('/-/','',$this->request->new_ssn);

		$customer = ECash::getFactory()->getModel('Customer');
		$customer->loadBy(array('ssn' => $new_ssn, 'company_id' => $company_id));


		if(!empty($customer->customer_id))
		{
			if($customer->customer_id != $this->request->customer_id)
			{
				// Situation: There's matching applications with the new SSN.
				// Action: Show Prompt to merge with the new applications
				$this->log->Write("Found customer id: {$customer->customer_id} while searching with new SSN $new_ssn");
				$new_customer = ECash_Customer::getByCustomerId(ECash::getMasterDB(), $customer->customer_id, $this->company_id);
				$old_customer = ECash_Customer::getByCustomerId(ECash::getMasterDB(), $this->request->customer_id, $this->company_id);

				$data = new stdClass();
				$data->old_customer_id = $this->request->customer_id;
				$data->new_customer_id = $customer->customer_id;

				$data->old_applications = $old_customer->getApplications();
				$data->new_applications = $new_customer->getApplications();

				$data->application_id = $this->request->application_id;
				$data->customer_name = ucwords($data->old_applications[$data->application_id]->name_last) . ', ' . ucwords($data->old_applications[$data->application_id]->name_first);
				$data->employer_name = $data->old_applications[$data->application_id]->employer_name;

				$data->old_formatted_ssn = $old_customer->Format_SSN($old_customer->Get_SSN());
				$data->new_formatted_ssn = $old_customer->Format_SSN($new_ssn);
				ECash::getTransport()->Set_Levels('popup', 'merge_customers');
				ECash::getTransport()->Set_Data($data);
				return;
			}
		}
		else
		{
			$customer = ECash::getFactory()->getModel('Customer')->loadBy(array('customer_id' => $this->request->customer_id));

			if($new_ssn == $customer->ssn)
			{
				// Situation: The New SSN and the Old SSN are the same.
				// Action: Do Nothing
				$this->log->Write("No change in SSN");
				ECash::getTransport()->Set_Levels('close_pop_up');
				return;
			}
		}


		$old_customer_id = $this->request->customer_id;
		$old_customer = ECash_Customer::getByCustomerId(ECash::getMasterDB(), $old_customer_id, $this->company_id);

		$old_ssn = $old_customer->Get_SSN();


		$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$this->request->application_id}] Requested SSN Change from {$old_ssn} to {$new_ssn}");


		// Situation: There's no matching apps with the new SSN and there's only one
		//            current application.
		// Action: Change the SSN for the one existing application
		if (count($old_customer->getApplications()) == 1)
		{
			$this->log->Write("Modifying SSN to $new_ssn");
			$old_customer->Update_Customer_SSN($new_ssn);
			$old_customer->Update_Application_SSN(array($this->request->application_id), $new_ssn);

			if ($old_ssn != $new_ssn)
			{
				$this->Set_In_Process($this->request->application_id);
			}

			$this->Check_Fraud($this->request->application_id);
			$_SESSION["popup_display_list"] = 'personal_info_edit';
			ECash::getTransport()->Set_Levels('close_pop_up');
			return;
		}

		// Situation: There's no matching apps with the new SSN but there's
		//            multiple applications associated with this customer.
		// Action: Prompt the user to Split one or more of the applications
		//         into a new customer
		else
		{
			$data = new stdClass();
			$data->old_customer_id = $old_customer_id;
			$data->old_applications = $old_customer->getApplications();

			$data->application_id = $this->request->application_id;
			$data->customer_name = ucwords($data->old_applications[$data->application_id]->name_last) . ', ' . ucwords($data->old_applications[$data->application_id]->name_first);
			$data->employer_name = $data->old_applications[$data->application_id]->employer_name;

			$data->old_formatted_ssn = $old_customer->Format_SSN($old_ssn);
			$data->new_formatted_ssn = $old_customer->Format_SSN($new_ssn);
			$data->old_ssn = $old_ssn;
			$data->new_ssn = $new_ssn;
			ECash::getTransport()->Set_Levels('popup', 'split_customers');
			ECash::getTransport()->Set_Data($data);
		}
	}

	/**
	 * @TODO refactor with models -- not yet used by CFE
	 */
	public function Customer_SSN_Commit_Change()
	{
		$old_customer_id = $this->request->old_customer_id;
		$old_customer = ECash_Customer::getByCustomerId(ECash::getMasterDB(), $old_customer_id, $this->company_id);

		$new_ssn = $this->request->new_ssn;

		$applications = array();

		// Filter out the oddball keys
		foreach($this->request->app as $key => $application_id) 
		{
			$applications[] = $application_id;
		}

		// If there's a new customer ID in the request, we're going to be merging
		if(isset($this->request->new_customer_id))
		{
			$new_customer_id = $this->request->new_customer_id;
			$new_customer = ECash_Customer::getByCustomerId(ECash::getMasterDB(), $new_customer_id, $this->company_id);

			$new_customer->Merge_Applications($applications, $new_customer->Get_SSN());

			foreach($applications as $app_id)
			{
				$this->Check_Fraud($app_id);
			}
			$this->log->Write("[Agent:{$_SESSION['agent_id']}] Merged " . implode(', ',$applications) . " with customer id {$new_customer_id}");

		}

		// We're creating a new customer
		else
		{
			$new_customer  = ECash_Customer::getByCustomerId(ECash::getMasterDB(), $this->request->old_customer_id, $this->company_id);
			$new_customer_id = $new_customer->Create_Customer($applications, $new_ssn);
			foreach($applications as $app_id)
			{
				$this->Check_Fraud($app_id);
			}
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$this->request->application_id}] Split " . implode(', ',$applications) . " into new customer {$new_customer_id}");
		}

		// If we've moved all of the applications from the old to the new
		if(count($applications) == count($old_customer->getApplications()))
		{
		//	$new_customer->Remove_Customer($old_customer_id);
			$old_customer->getModel()->delete();		
			$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$this->request->application_id}] removing old customer {$old_customer_id}");
		}


		$_SESSION["popup_display_list"] = 'personal_info_edit';
		ECash::getTransport()->Set_Levels('close_pop_up');

	}

	/**
	 * The application being pulled is a React but did not apply for
	 * the new loan through the customer service site or an email
	 * so the identity needs to be verified before we email the customer
	 * their login credentials.
	 *
	 * @TODO refactor with models -- not yet used by CFE
	 */
	public function Verify_React_Application()
	{
		$customer = ECash_Customer::getByCustomerId(ECash::getMasterDB(), $this->request->customer_id, $this->company_id);

		$data = new stdClass();
		$data->application_id = $this->request->application_id;
		$data->customer_id = $this->request->customer_id;

		// Grab the list of all applications, then filter out the current one in question
		$all_applications = $customer->getApplications();

		// If for some strange reason we don't pull any applications
		// then just close the pop-up.  So far this hasn't happened
		// but it's just a safeguard.
		if(count($all_applications) < 1) //mantis:6616   < 1 instead of < 2
		{
			ECash::getTransport()->Set_Levels('close_pop_up');
			return;
		}

		$old_applications = array();
		foreach($all_applications as $a)
		{
			if($a->application_id == $data->application_id)
			{
				$current_application = $a;
			}
			else
			{
				$a->customer_name = ucwords($a->name_last) . ', ' . ucwords($a->name_first);
				$a->street_address = $a->unit === '' ? $a->street : $a->street . " " . $a->unit;

				//mantis:7029
				$a->customer_name = str_replace("'", "", $a->customer_name);
				$a->street_address = str_replace("'", "", $a->street_address);
				$a->city = str_replace("'", "", $a->city);
				$a->county = str_replace("'", "", $a->county);
				$a->employer_name = str_replace("'", "", $a->employer_name);
				//end mantis:7029

				$old_applications[] = $a;
			}
		}

		// JSON Encoded list of other applications
		$data->encoded_applications = json_encode($old_applications);

		// Set the replacement variables for the form
		$data->formatted_ssn = $current_application->formatted_ssn;
		$data->customer_name = ucwords($current_application->name_last) . ', ' . ucwords($current_application->name_first);
		$data->street_address = $current_application->street . " ".$current_application->unit;
		$data->city = $current_application->city;
		$data->county = $current_application->county;
		$data->state = $current_application->state;
		$data->zip_code = $current_application->zip_code;
		$data->phone_home = $current_application->phone_home;
		$data->phone_cell = $current_application->phone_cell;
		$data->employer_phone = $current_application->employer_phone;
		$data->employer_name = $current_application->employer_name;
		$data->current_status = $current_application->status_long;

		ECash::getTransport()->Set_Levels('popup', 'compare_react');
		ECash::getTransport()->Set_Data($data);
	}

	/**
	 * The agent has finished reviewing the React and we will now take
	 * some sort of action on the account.
	 */
	public function React_Verification_Action()
	{

		require_once (LIB_DIR . "Document/Document.class.php");

		$request = $this->request;

		// debug
		$this->log->Write(var_export($request,true));

		$application_id = $request->application_id;

		switch($request->react_type)
		{
			// React is the same.  Email the customer their existing login info
			case 'same':
				$this->log->Write("React {$application_id} is the same");
				move_to_automated_queue
						(	"Underwriting"
							,	$application_id
							,	"" // Sort String
							,	NULL // Time available
							,	NULL // Time unavailable
							)	; // 6186 [rlopez]
				ECash_Documents_AutoEmail::Send($application_id, 'LOGIN_AND_PASSWORD');
				break;
			// React is Different.  Create a new account with the specified SSN and send an email
			// with the new login info.
			case 'different':
				$this->log->Write("React {$application_id} is different.  New SSN: {$request->new_ssn}");
				$new_ssn = preg_replace('/-/','',$this->request->new_ssn);
				$comments = ECash::getApplicationById($application_id)->getComments();
				$comments->add($this->request->comment, $this->agent_id);
				$new_customer  = ECash_Customer::getByCustomerId(ECash::getMasterDB(), ECash::getApplicationById($application_id)->customer_id, $this->company_id);

				if($new_customer_id = $new_customer->Create_Customer(array($request->application_id), $new_ssn))
				{
					ECash_Documents_AutoEmail::Send($application_id, 'NEW_USERNAME_PASSWORD');
					$this->log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$this->request->application_id}] Split into new customer {$new_customer_id}");
				}
				break;

			// Not Implemented yet.
			case 'fraud':
				$this->log->Write("React {$request->application_id} is Fraud");
				break;
		}

		ECash::getTransport()->Set_Levels('close_pop_up');
	}

	public function Shift_Schedule()
	{
		$application_id = $this->request->application_id;

		$this->log->Write("Shifting schedule for {$application_id}");

		$this->loan_data->Update_Schedule($application_id,  $this->request->schedule_shift_date, TRUE);
		ECash::getTransport()->Set_Data($this->loan_data->Fetch_Loan_All($application_id));
		ECash::getTransport()->Add_Levels('overview', 'schedule', 'view');
	}

	public function Post_DebtConsolidation_Payment($request) 
	{
		if ($request->action_type == 'fetch') 
		{
			$data = new stdClass;
			$data->application_id = $request->application_id;
			$data->events = Fetch_Scheduled_DebtConsolidation_Payments($request->application_id);

			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Set_Levels('popup', 'post_debt_consolidation');
		} 
		else 
		{
			Post_DebtConsolidation_Payment($request->application_id, $request->event_schedule_id, $request->original_amount - $request->actual_amount);
			$_SESSION["popup_display_list"] = 'transaction_overview';
			ECash::getTransport()->Set_Levels('close_pop_up');
		}
	}

	public function Save_Payments($structure = null)
	{
		if (empty($structure)) $structure = $this->Request_To_Data_Structure($this->request);
		$retval = $this->loan_data->Save_Payments($structure);
		ECash::getTransport()->Set_Data($this->loan_data->Fetch_Loan_All($_SESSION['current_app']->application_id));
		ECash::getTransport()->Add_Levels('overview', 'schedule', 'view');
		return $retval;
	}

    /* Request_To_Data_Structure
      sample output (expressed in JSON syntax)
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
    public function Request_To_Data_Structure ($request)
    {
        $structure = new StdClass;
        // Add to the bases array if there is a new sub-structure that should be divided out into the structure for payment save
        $bases = array('debt_consolidation', 'payment_arrangement', 'manual_payment', 'ad_hoc', 'next_payment_adjustment', 'partial_payment');
        $baserowexpression = '/^(' . join('|', $bases) . ')_(.+)_(\d+)$/';
        $baseexpression = '/^(' . join('|', $bases) . ')_(.+)$/';
        foreach ($request as $key => $value)
        {
            preg_match($baserowexpression, $key, $matches);
            if (isset($matches[1]) && isset($matches[2]) && isset($matches[3]))
            {
                $structure->$matches[1]->rows[$matches[3]]->$matches[2] = $value;
                continue;
            }

            preg_match($baseexpression, $key, $matches);
            if (isset($matches[1]) && isset($matches[2]))
            {
                $structure->$matches[1]->$matches[2] = $value;
                continue;
            }

            $structure->$key = $value;
        }
		return $structure;
    }

	public function Complete_Pending_Items()
	{
		$data = new stdClass();
		switch($this->request->action_type)
		{
		case 'fetch':
			$data->pending_items =
				Fetch_Pending_Items();
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Set_Levels('popup', 'complete');
			break;
		case 'save':
			$this->loan_data->Complete_Pending_Items($this->request);
			$_SESSION['popup_display_list'] = array('overview', 'schedule', 'view');
			ECash::getTransport()->Set_Levels('close_pop_up');
			break;
		}
	}

	public function Add_Follow_Up($application_id, $comment, $interval, $followup_date = null, $agents_followup, $category_followup)
	{
		$this->log->Write("[AppID:$application_id][Agent:{$this->server->agent_id}] Sent to: {$agents_followup} Adding Follow Up");
		$app = ECash::getApplicationById($application_id);
		$status = $app->getStatus();

		if ($status->level1 == 'underwriting' && $status->level2 == 'applicant')
		{
			$follow_up_type = 'underwriting';
		}
		elseif ($status->level1 == 'verification' && $status->level2 == 'applicant')
		{
			$follow_up_type = 'verification';
		}
		elseif ($status->level0 == 'amortization')
		{
			$follow_up_type = 'amortization';
		}
		elseif (($status->level1 == 'collections' || $status->level2 == 'collections') && !in_array($status->level0, array('unverified','verified')))
		{
			$follow_up_type = 'collections';
		}
		elseif ($status->level1 == 'servicing')
		{
			$follow_up_type = 'servicing';
		}
		else
		{
			$follow_up_type = 'other';
		}

		if ($interval == "DATE")
		{
			//mantis:4462  convert to timestamp, add 20 hrs not to have a contact time 00-00-00 of a selected date
			/* GF # 21687
			 * I cannot find a single logical reason why 20 hours had to be added to this. Doing that
             * essentially caused the follow up day to be the day after whatever the agent set, since 
			 * +20hrs is 9pm. 
			 */
			
			$interval = strtotime($followup_date);

			//mantis:6358 part 2 : $follow_up_time needs to always be set or it will default to zero
			$follow_up_time = date("Y-m-d H:i:s", $interval);
		}
		else
		{
			$interval = split(" ",$interval);
			if(2 != count($interval))
			{
				throw(new Exception("Unexpected number of components"));
			}

			$follow_up_time = Follow_Up::Add_Time(time(),$interval[0],$interval[1]);
		}

		$agent_id = ECash::getAgent()->getAgentId();

		$normalizer= new Date_Normalizer_1(new Date_BankHolidays_1());
		$date_expiration = $normalizer->advanceBusinessDays(strtotime($follow_up_time), 2);
		
		$pending = Follow_Up::Force_Update_Follow_Up_Status($application_id);
                if ($pending > 0)
                {
			$query = "
					UPDATE comment
					SET is_resolved = 1
					WHERE application_id = {$application_id}
					AND type = 'followup'
			";
			ECash::getMasterDb()->exec($query);

                        $comments = ECash::getApplicationById($application_id)->getComments();
                        $comments->add("F: pending follow-up overwritten", $agent_id, ECash_Application_Comments::TYPE_FOLLOWUP, ECash_Application_Comments::SOURCE_LOAN_AGENT);
                }
			
		if ($follow_up_type == 'collections')
		{
			Follow_Up::createCollectionsFollowUp($application_id, $category_followup, $follow_up_type, $follow_up_time, $agent_id, ECash::getCompany()->company_id, $comment, $date_expiration, 'followup');
		}
		else 
		{
			Follow_Up::Create_Follow_Up($application_id, $category_followup, $follow_up_type, $follow_up_time, $agent_id, ECash::getCompany()->company_id, $comment, $date_expiration);
			
			/*
			//LET CFE DO ALL THIS
			//called from Create_Follow_Up()
			$application = ECash::getApplicationById($application_id);
			$affiliations = $application->getAffiliations();
			$agent = ECash::getAgent();
			$affiliations->add($agent, 'manual', 'owner', $date_expiration);
			$agent->getQueue()->insertApplication($application, 'Follow Up', $date_expiration, strtotime($follow_up_time));
			*/
		}
		
		//insert into queue requested
		$qm = ECash::getFactory()->getQueueManager();
                $queue_item = new ECash_Queues_BasicQueueItem($application_id);
                $qm->removeFromAllQueues($queue_item);

		if ($agents_followup == "follow_up")
		{
			$qm = ECash::getFactory()->getQueueManager();
			$queue_item = $qm->getQueue('follow_up')->getNewQueueItem($application_id);
			$queue_item->DateAvailable = strtotime($follow_up_time);
			$qm->moveToQueue($queue_item, 'follow_up');
		}
		else
		{
			$agent = ($agents_followup == "my_queue") ? ECash::getAgentById($agent_id) : ECash::getAgentById($agents_followup);
			$agent->getQueue()->insertApplication($app, 'follow up', null, strtotime($follow_up_time));
		}

		if ($this->request->mode == 'collections')
		{
			$_SESSION['popup_display_list'] = array('overview', 'personal', 'view');
		}
		else
		{
			$_SESSION['popup_display_list'] = array('overview', 'schedule', 'view');
		}
		ECash::getTransport()->Set_Levels('close_pop_up');
	}

	public function Writeoff()
	{
		$data = new stdClass();
		switch($this->request->action_type)
		{
		case 'fetch':
			list($status, $schedule) = $this->loan_data->Fetch_Schedule_Data($_SESSION['current_app']->application_id);
			$data->schedule_status = $status;
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Set_Levels('popup', 'writeoff');
			break;
		case 'save':
			$this->loan_data->Save_Writeoff($this->request);
			$_SESSION['popup_display_list'] = array('overview', 'schedule', 'view');
			ECash::getTransport()->Set_Levels('close_pop_up');
			break;
		}
	}

	public function Show_Transaction_Details()
	{
		$app_id = $_SESSION['current_app']->application_id;
		$data = new stdClass();
		$data->application_id = $app_id;
		$esid = (isset($this->request->esid)) ? $this->request->esid : null;
		$trid = (isset($this->request->trid)) ? $this->request->trid : null;

		$return_code_array = $this->Get_ACH_Return_Codes();
		$data->ach_return_codes = array ();
		foreach ($return_code_array as $code)
		{
			if (strpos($code->name_short, 'R') !== 0) continue;
			$data->ach_return_codes[$code->ach_return_code_id] = $code->name_short . " " . $code->is_fatal . " " . substr($code->name,0,25);
		}

		$return_code_array = $this->Get_Card_Return_Codes();
		$data->card_return_codes = array ();
		foreach ($return_code_array as $code)
		{
			$data->card_return_codes[$code->card_return_code_id] = $code->name_short . " " . $code->is_fatal . " " . substr($code->name,0,25);
		}

		// Dig up transactional info here
		$event = $this->Get_Transaction($app_id, $esid, $trid);
		$data->event = $event;
		
		//Pass in the module for ACL
		$data->module = $this->server->Get_Active_Module();
		//Pass in the mode for ACL
		$data->mode = $this->request->mode;

		$data->may_use_card_schedule = mayUseCardSchedule($app_id);
		
		$data->active_card_saved = FALSE;
		$ci_model = ECash::getFactory()->getModel('CardInfo');
		$ci_array = $ci_model->loadAllBy(array(
							'application_id' => $app_id,
							'active_status' => 'active',
		));
		if (count($ci_array) > 0)
		{
			$data->active_card_saved = TRUE;
		}

		ECash::getTransport()->Set_Data($data);
		ECash::getTransport()->Set_Levels('popup', 'transaction_details');
	}

	// Honestly, all failures should go to the Failure DFA.
	/**
	 * modifies the transaction
	 *
	 * @TODO take out the queue movement and put it into the rule editor
	 */
	public function Modify_Transaction()
	{
		$_SESSION["popup_display_list"] = 'transaction_overview';

		$log = get_log();
		$db = ECash::getMasterDb();

		switch ($this->request->specific_action)
		{
			case 'switch_ach_card_payment_type':
				if (array_key_exists($this->request->name_short, $this->ach_card_payment_type_map))
				{
					$target_name_short = $this->ach_card_payment_type_map[$this->request->name_short];
				}
				else if (in_array($this->request->name_short, $this->ach_card_payment_type_map))
				{
					$target_name_short = array_search($this->request->name_short, $this->ach_card_payment_type_map);
				}
				else
				{
					$target_name_short = $this->request->name_short;
				}

				$et = ECash::getFactory()->getModel('EventType');
				$loaded = $et->loadBy(array('name_short' => $target_name_short,
								'active_status' => 'active',
								'company_id' => $this->company_id,
				));
				if ($loaded)
				{
					$target_event_type_id = $et->event_type_id;

					$es = ECash::getFactory()->getModel('EventSchedule');
					$es->loadBy(array('event_schedule_id' => $this->request->event_schedule_id,));
					$es->event_type_id = $target_event_type_id;
					$es->save();
				}
				
				$es = ECash::getFactory()->getModel('EventSchedule');
				$es->loadBy(array('event_schedule_id' => $this->request->event_schedule_id,));
				$application_id = $es->application_id;
				
				alignActionDateForCard($application_id);

				break;

			//asm 80
			case 'designate_ach_provider_to_event':
				if (isset($this->request->event_schedule_id))
				{
					//Retrieve date_event
					$event_model = ECash::getFactory()->getModel('EventSchedule');
					$event_model->loadBy(array('event_schedule_id' => $this->request->event_schedule_id,));
					$date_event = $event_model->date_event;
					
					//Inactivate all event-provider records for the app and date_event
					$event_provider_model = ECash::getFactory()->getModel('EventScheduleAchProvider');
					$event_provider_array = $event_provider_model->loadAllBy(array('application_id' => $this->request->application_id,
											'date_event' => $date_event,
											'active_status' => 'active',
					));
					foreach ($event_provider_array as $ep)
					{
						$event_schedule_ach_provider_id = $ep->event_schedule_ach_provider_id;
						
						$ep_inactivate = ECash::getFactory()->getModel('EventScheduleAchProvider');
						$loaded = $ep_inactivate->loadBy(array('event_schedule_ach_provider_id' => $event_schedule_ach_provider_id,
						));
						if ($loaded)
						{
							$ep_inactivate->active_status = 'inactive';
							$ep_inactivate->agent_id = $this->agent_id;
							$ep_inactivate->save();
						}
					}

					//Designate ach provider to all ach events for the action date
					$ach_event_id_ary = $this->fetchAchEventTypes();
					$db = ECash::getMasterDb();
					$query = "
						SELECT DISTINCT
							event_schedule_id
						FROM
							event_schedule
						WHERE
							application_id = {$this->request->application_id}
						AND
							date_event = '{$date_event}'
						AND
							event_type_id IN (" . implode(',', $ach_event_id_ary) . ")
					";
					$result = $db->query($query);
					while ($row = $result->fetch(PDO::FETCH_OBJ))
					{
						$event_schedule_id = intval($row->event_schedule_id);

						if (!empty($this->request->ach_provider_event))
						{
							$event_provider_model = ECash::getFactory()->getModel('EventScheduleAchProvider');

							$event_provider_model->date_modified = date("Y-m-d H:i:s", time());
							$event_provider_model->date_created = date("Y-m-d H:i:s", time());
							$event_provider_model->event_schedule_id = $event_schedule_id;
							$event_provider_model->application_id = $this->request->application_id;
							$event_provider_model->date_event = $date_event;
							$event_provider_model->ach_provider_id = $this->request->ach_provider_event;
							$event_provider_model->active_status = 'active';
							$event_provider_model->agent_id = $this->agent_id;
							$event_provider_model->setInsertMode(DB_Models_WritableModel_1::INSERT_STANDARD);
							$event_provider_model->insert();
						}
					}
				}
				break;

		case 'remove':
			try 
			{
				Remove_One_Unregistered_Event_From_Schedule($this->request->application_id, $this->request->event_schedule_id);
				Complete_Schedule($this->request->application_id);
			}
			catch(Exception $e) 
			{
				$log->Write("Exception: Error removing transaction!  " . $e->getMessage());
			}
			break;

                        //asm 31
                        case 'remove non ach':
                        	//remove from transaction history
				$th = ECash::getFactory()->getModel('TransactionHistory');
				$th_delete = ECash::getFactory()->getModel('TransactionHistory');
				$th_array = $th->loadAllBy(array('transaction_register_id' => $this->request->transaction_register_id,));
				foreach ($th_array as $th_record)
				{
					$loaded = $th_delete->loadBy(array('transaction_history_id' => $th_record->transaction_history_id,));
					if ($loaded)
					{
				        	$th_delete->delete();
					}
				}

				//remove from transaction ledger
				$tl = ECash::getFactory()->getModel('TransactionLedger');
				$tl_delete = ECash::getFactory()->getModel('TransactionLedger');
				$tl_array = $tl->loadAllBy(array('transaction_register_id' => $this->request->transaction_register_id,));
				foreach ($tl_array as $tl_record)
				{
					$loaded = $tl_delete->loadBy(array('transaction_ledger_id' => $tl_record->transaction_ledger_id,));
					if ($loaded)
					{
						$tl_delete->delete();
					}
				}

				//remove from transaction register
				$tr = ECash::getFactory()->getModel('TransactionRegister');
				$loaded = $tr->loadBy(array('transaction_register_id' => $this->request->transaction_register_id,));
				if ($loaded)
				{
					$event_schedule_id = $tr->event_schedule_id;
					$transaction_type_id = $tr->transaction_type_id;
					$transaction_status = $tr->transaction_status;
					$amount = $tr->amount;
					$tr->delete();
				}

				//remove from event amount
				$ea = ECash::getFactory()->getModel('EventAmount');
				$ea_delete = ECash::getFactory()->getModel('EventAmount');
				$ea_array = $ea->loadAllBy(array('transaction_register_id' => $this->request->transaction_register_id,
				                                'event_schedule_id' => $event_schedule_id,
				));
				foreach ($ea_array as $ea_record)
				{
					$loaded = $ea_delete->loadBy(array('event_amount_id' => $ea_record->event_amount_id,));
					if ($loaded)
					{
						$ea_delete->delete();
					}
				}

				//remove from event schedule (only if no other transaction is associated with this event)
				$tr_check = ECash::getFactory()->getModel('TransactionRegister');
				$tr_check_array = $tr_check->loadAllBy(array('event_schedule_id' => $event_schedule_id));
				if (count($tr_check_array) == 0)
				{
					$es = ECash::getFactory()->getModel('EventSchedule');
					$loaded = $es->loadBy(array('event_schedule_id' => $event_schedule_id));
					if ($loaded)
					{
						$es->delete();
					}
				}

				$log = ECash::getLog('transaction');
				$log->Write("[Agent:{$this->agent_id}][AppID:{$this->request->application_id}] Removed transaction type: {$transaction_type_id}, transaction status: {$transaction_status}, amount: {$amount}.");
			break;

		case 'complete':
			try 
			{
				if(empty($this->request->transaction_register_id))
				{
					$current_schedule   = Fetch_Schedule($this->request->application_id);
					$status = Analyze_Schedule($current_schedule, TRUE);
					$today_is_a_big_day = "2037-01-01";
					if($status->next_service_charge_date != 'N/A' && strtotime($status->next_service_charge_date) <= time())
					{
						//Complete the current service charge
						$trids = Record_Current_Scheduled_Events_To_Register($today_is_a_big_day,$this->request->application_id,$status->next_service_charge_id);
						foreach ($trids as $trid) 
						{
							Post_Transaction($this->request->application_id,$trid);
						}
					
					}
					$tr_ids  = Record_Current_Scheduled_Events_To_Register($today_is_a_big_day, $this->request->application_id, $this->request->event_schedule_id, 'all', 'immediate posting');
					
					foreach ($tr_ids as $trid)
					{
						Post_Transaction($this->request->application_id, $trid);
					}
				}
				else
				{
					Post_Recorded_Events($this->request->application_id, $this->request->event_schedule_id, true);
				}
				Adjust_Negative_Balances($this->request->application_id);
				Check_Inactive($this->request->application_id);
			}
			catch(Exception $e) 
			{
				$log->Write("Exception: Error completing transaction!  " . $e->getMessage());
			}

			//I removed a huge chunk of code which added an application to an agent's myqueue or the collections queue
			//If a transaction was completed on an application in made arrangements status, which didn't pay off the balance
			//I strongly believe this behavior should be handled outside of ModifyTransaction.  If you disagree, change this
			//and please tell me why [W!-03-23-2009][HMS Collections]

			break;
		case 'fail':
			try 
			{	
				if(empty($this->request->transaction_register_id))
				{
					$today_is_a_big_day = "2037-01-01";
					$trid = Record_Current_Scheduled_Events_To_Register($today_is_a_big_day, $this->request->application_id, $this->request->event_schedule_id, 'all', 'immediate posting');
					
				}

				//assembla 25
				if(!empty($this->request->transaction_register_id) && !empty($this->request->ach_return_code_id))
				{
					$query = "
					UPDATE ach
					JOIN transaction_register AS tr USING (ach_id)
					SET ach.ach_return_code_id = {$this->request->ach_return_code_id},
					ach.ach_status = 'returned'
					WHERE tr.transaction_register_id = {$this->request->transaction_register_id}"
					;
	
					ECash::getMasterDb()->exec($query);
				}

				if(!empty($this->request->transaction_register_id) && !empty($this->request->card_return_code_id))
				{
					$query = "
					UPDATE card_process cp
					JOIN transaction_register AS tr USING (card_process_id)
					SET cp.reason_code = {$this->request->card_return_code_id},
					cp.result_code = 'declined'
					WHERE tr.transaction_register_id = {$this->request->transaction_register_id}"
					;
	
					ECash::getMasterDb()->exec($query);
				}

				// Necessary to record a failure ALL transactions associated with an event.
				Record_Event_Failure($this->request->application_id, $this->request->event_schedule_id);

			}
			catch(Exception $e) 
			{
				$log->Write("Exception: Error failing transaction!  " . $e->getMessage());
				$log->Write($e->getTraceAsString());
				ECash::getTransport()->Set_Levels('exception');
				return;
			}

			try 
			{
				if (isset($this->request->end_status))
				{
					$statlist = split(",", $this->request->end_status);
					Update_Status($this->server, $this->request->application_id, $statlist);
				}
				else // Let the failure DFA handle it
				{
					$fdfap = new stdClass();
					$fdfap->application_id = $this->request->application_id;
					$fdfap->server = $this->server;
					$fdfa = new FailureDFA($this->request->application_id);
					$fdfa->run($fdfap);
					
				}
				Check_Inactive($this->request->application_id);
			}
			catch(Exception $e) 
			{
				print("Exception: Error processing failed transaction:  " . $e->getMessage() . "<br>\n");
				print("Application {$this->request->application} put into rescheduling standby." . "<br>\n");
				$log->Write("Exception: Error processing failed transaction: " . $e->getMessage());
				$log->Write("Trace: " . $e->getTraceAsString());
				$log->Write("Application {$this->request->application} put into rescheduling standby.");
				Set_Standby($this->request->application_id, $this->company_id, 'reschedule');
				ECash::getTransport()->Set_Levels('exception');
				return;
			}

			break;
		}
		ECash::getTransport()->Set_Levels('close_pop_up');
	}

	public function To_Quickcheck_Ready()
	{
		$app_id = $this->request->application_id;
		Update_Status($this->server, $app_id, array('ready','quickcheck', 'collections','customer','*root'));
		Remove_Unregistered_Events_From_Schedule($app_id);
		ECash::getTransport()->Add_Levels('overview','personal','view', 'general_info','view');

		$data = $this->loan_data->Fetch_Loan_All($this->request->application_id);
		ECash::getTransport()->Set_Data($data);

	}

	public function Recovery()
	{
		$data = new stdClass();
		switch($this->request->action_type)
		{
		case 'fetch':
			list($status, $schedule) = $this->loan_data->Fetch_Schedule_Data($_SESSION['current_app']->application_id);
			$data->schedule_status = $status;
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Set_Levels('popup', 'recovery');
			break;
		case 'save':
			$this->loan_data->Save_Recovery($this->request);
			$_SESSION['popup_display_list'] = array('overview', 'schedule', 'view');
			ECash::getTransport()->Set_Levels('close_pop_up');
			break;
		}
	}

	public function Internal_Adjustment($mode=null)
	{
		$data = new stdClass();

		switch ($this->request->action_type)
		{
		case 'fetch':
			if ($mode == 'conversion') $data->preset_date = date("m/d/Y", strtotime("now"));
			list($status, $schedule) = $this->loan_data->Fetch_Schedule_Data($_SESSION['current_app']->application_id, true, false);
			$data->schedule_status = $status;
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Set_Levels('popup', 'adjustment');
			break;
		case 'save':
			$this->loan_data->Save_Adjustment($this->request);
			$_SESSION['popup_display_list'] = array('overview', 'schedule', 'view');
			ECash::getTransport()->Set_Levels('close_pop_up');
			break;
		}
	}

	public function Id_Recheck($application_id, $recheck_type, $agent_id)
	{
		$business_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());
		$rule_set_id    = $business_rules->Get_Rule_Set_Id_For_Application($application_id);
		$rule_sets      = $business_rules->Get_Rule_Set_Tree($rule_set_id);
		$call_type      = $rule_sets['IDV_CALL'];
		$recheck_all    = $business_rules->Get_Rule_Set_Component_Parm_Values(ECash::getCompany()->name_short, 'datax_recheck_all');
		if(!empty($recheck_all['datax_recheck_waterfall']) && $recheck_all['datax_recheck_waterfall'] == 'No'
		   && isset($rule_sets['IDV_CALL_NW']))
		{			
			$call_type = $rule_sets['IDV_CALL_NW'];
		}
		$app_data 		= $this->loan_data->Fetch_Loan_All($application_id);
		//formating income monthly to not break datax [#55160]
		$app_data->income_monthly = number_format($app_data->income_monthly, 0 , '', '');
		$timer_name     = "ID_Verification Application ID " . $application_id;
		
		$datax_password    = ECash::getConfig()->DATAX_PASSWORD;
		$datax_license_key = ECash::getConfig()->DATAX_LICENSE_KEY;
		
		$datax 			 = new ECash_DataX($datax_license_key, $datax_password, $call_type);
		$response_parser = new ECash_DataX_Responses_Generic(); 
		
		if(!$packages = $this->loan_data->bureau_query->getData($app_data->application_id, $app_data->company_id, "idv"))
		{
			$packages = $this->loan_data->bureau_query->getData($app_data->application_id, $app_data->company_id, $call_type);
		}
		
		if(count($packages))
		{
			$newest_package = @current($packages);
			
			$response_parser->parseXML($newest_package->received_package);
		}
		
		//If we are rechecking all services, then remove the track hash
		if($recheck_type == 'all') $app_data->track_hash = NULL;
		else                       $app_data->track_hash = $response_parser->getTrackHash();
		
		ECash::getMonitoring()->getTimer()->startTimer($timer_name);
		ECash::getLog()->Write("[App_ID: {$application_id}] ID Verification Rechecked.");
		
		try
		{
			$datax->setRequest('Perf');
			$datax->setResponse('Perf');
			$datax->execute((array)$app_data);
			$datax->saveResult($agent_id);
		}
		catch(Exception $e)
		{
			ECash::getLog()->Write("[App_id: {$application_id}] An Error Occured During ID Verification: " . $e->getMessage()); 	
		}

		$app_data->inquiry_packages = $this->loan_data->bureau_query->getData($app_data->application_id, $app_data->company_id);

		ECash::getMonitoring()->getTimer()->stopTimer($timer_name);
		
		ECash::getTransport()->Set_Data($app_data);
		ECash::getTransport()->Add_Levels('overview','idv','view','general_info','view');
	}

	public function Debt_Company($company_id)
	{
		$data = new stdClass();
		switch ($this->request->action_type)
		{
			case 'fetch':
				$data->debt_company_id = $company_id;
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Set_Levels('popup', $this->request->action);
				break;

			case 'edit':
				Edit_Debt_Company(	$this->request->debt_company_id,
									$this->request->debt_company_name,
									$this->request->debt_company_address1,
									$this->request->debt_company_address2,
									$this->request->debt_company_city,
									$this->request->debt_company_state,
									$this->request->debt_company_zipcode,
									$this->request->debt_company_phone);
				$_SESSION['popup_display_list'] = "debt_consolidation";
				ECash::getTransport()->Set_Levels('close_pop_up');
				break;
			case 'add':
				Add_Debt_Company(	$this->request->debt_company_name,
									$this->request->debt_company_address1,
									$this->request->debt_company_address2,
									$this->request->debt_company_city,
									$this->request->debt_company_state,
									$this->request->debt_company_zipcode,
									$this->request->debt_company_phone);
				$_SESSION['popup_display_list'] = "debt_consolidation";
				ECash::getTransport()->Set_Levels('close_pop_up');
				break;

		}
	}

	private function Add_Comment()
	{
		// Requiring the file here because the functions are only used here.
		require_once(SERVER_CODE_DIR . 'comment.class.php');

		if(trim($this->request->comment) != '')
		{
			// Set the type of comment
			//   REQUIRED
			switch( $this->request->action )
			{
			case 'add_follow_up':
				$this->request->comment_type = 'followup';
				break;
			case 'change_status':
				switch( $this->request->submit_button )
				{
				case 'Deny':
					$this->request->comment_type = 'deny';
					break;
				case 'Withdraw':
					$this->request->comment_type = 'withdraw';
					break;
				case 'Reverify':
					$this->request->comment_type = 'reverify';
					break;
				case 'Watch':
					$this->request->comment_type = 'watch';
					break;
				default:
					$this->request->comment_type = 'standard';
					break;
				}
				break;
			case 'add_comment':
			default:
				$this->request->comment_type = 'standard';
				break;
			}

			$comment = new Comment();
			$comment->Add_Comment($this->server->company_id, $this->request->application_id, $this->server->agent_id,
				    $this->request->comment, $this->request->comment_type);
		}
	}

	private function Format_Wizard_Date($date)
	{
		$date_array = split("/", $date);
		return $date_array[2] . "-" . $date_array[0] . "-" . $date_array[1];
	}

	private function Is_Changed($array)
	{
		if(!is_array($array))
		{
			$array = array($array);
		}
/*
		foreach($array as $value)
		{
			$db_value = $value;
			if(isset($_SESSION['current_app']->{$db_value}))
			{
				if($_SESSION['current_app']->{$db_value} !== $this->request->{$value}
					|| 	strcasecmp($_SESSION['current_app']->{$db_value}, $this->request->{$value}) !== 0)
				{
					return TRUE;
				}
			}
		}
 */
		foreach($array as $value)
		{
			$db_value = $value;

			if ($_SESSION['current_app']->{$db_value} !== $this->request->{$value} ||
				strcasecmp($_SESSION['current_app']->{$db_value}, $this->request->{$value}) !== 0
				) {
					return TRUE;
			}
		}

		return FALSE;
	}

	private function Get_Transaction($app_id, $esid=null, $trid=null)
	{

		$db = ECash::getMasterDb();

		if (($esid == null) && ($trid == null))
			throw new Exception("Need either transaction_register_id or event_schedule_id");

		if ($esid == null)
		{
			$query = "
				SELECT (CASE WHEN tt.clearing_type = 'quickcheck'
				             THEN 'quickcheck'
             				WHEN tt.clearing_type = 'ach'
             				THEN 'ach'
					WHEN tt.clearing_type = 'card'
             				THEN 'card'
             				WHEN tt.clearing_type = 'landmark'
             				THEN 'landmark'
             				ELSE 'other'
        				END) as transaction_type
				FROM  	transaction_register tr,
      					transaction_type tt
				WHERE tt.transaction_type_id = tr.transaction_type_id
				AND   tr.transaction_register_id = {$trid}
			";
			$tt = $db->querySingleRow($query, NULL, PDO::FETCH_OBJ);

			if ($tt->transaction_type === 'landmark')
			{	// This is for Landmark ACH Transactions
				$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT
						a.*,
						ea.principal 		AS 'principal',
						ea.service_charge 	AS 'service_charge',
						ea.fee 				AS 'fee',
						ea.irrecoverable 	AS 'irrecoverable',
						ach.lm_ach_id		AS 'ach_id',
						ach.return_code 	AS 'return_code',
						ach.return_reason 	AS 'return_description',
						ach.confirmation_number,
						date_format(ar.date_request, '%m/%d/%Y %H:%i:%S') AS 'return_date', 
						a2.name_last,
						a2.name_first
					FROM
						(	SELECT  easub.event_schedule_id,
								SUM(IF(eat.name_short = 'principal', easub.amount, 0)) principal,
								SUM(IF(eat.name_short = 'service_charge', easub.amount, 0)) service_charge,
								SUM(IF(eat.name_short = 'fee', easub.amount, 0)) fee,
								SUM(IF(eat.name_short = 'irrecoverable', easub.amount, 0)) irrecoverable 
							FROM	event_amount easub 
							LEFT JOIN event_amount_type eat USING (event_amount_type_id) 
							WHERE		easub.application_id = {$app_id} 
							GROUP BY    easub.event_schedule_id
						)	ea,
						(	SELECT
								tr.application_id,
								tr.transaction_register_id,
                                tr.lm_ach_id,
								tr.lm_ach_id as ach_id,
                                tr.transaction_status,
                                tr.event_schedule_id,
								es.origin_id,
								es.origin_group_id,
								es.amount_principal,
								es.amount_non_principal,
								es.event_status,
								es.context,
								es.configuration_trace_data,
								es.is_shifted,
								date_format(es.date_created, '%m/%d/%Y %H:%i:%S') as event_date_created,
								date_format(es.date_modified, '%m/%d/%Y %H:%i:%S') as event_date_modified,
								date_format(tr.date_created, '%m/%d/%Y %H:%i:%S') as transaction_date_created,
								date_format(tr.date_modified, '%m/%d/%Y %H:%i:%S') as transaction_date_modified,
								DATE_FORMAT(tr.date_effective, '%m/%d/%Y') as date_effective_formatted,
								DATE_FORMAT(es.date_event, '%m/%d/%Y') as date_event,
								tt.NAME,
								tt.name_short 
							FROM
								transaction_register tr
							JOIN event_schedule AS es ON (es.event_schedule_id = tr.event_schedule_id)
							JOIN transaction_type AS tt ON (tt.transaction_type_id = tr.transaction_type_id)
							WHERE   tr.transaction_register_id = {$trid} 
							AND tr.application_id = {$app_id} 
							AND tr.company_id = {$this->server->company_id}
						) a
					LEFT JOIN landmark_ach AS ach ON (ach.lm_ach_id = a.lm_ach_id)
					LEFT JOIN landmark_report ar ON (ar.return_report_id = ach.return_report_id) 
					LEFT JOIN agent_affiliation_event_schedule aaes ON (aaes.event_schedule_id = a.event_schedule_id)
					LEFT JOIN agent_affiliation aa ON (aa.agent_affiliation_id = aaes.agent_affiliation_id)
					LEFT JOIN agent a2 ON (aa.agent_id = a2.agent_id)
					WHERE ea.event_schedule_id = a.event_schedule_id ";
			}
			else if ($tt->transaction_type === 'card')
			{	// This is for Payment Card Transactions
				$query = "
					SELECT
						a.*,
						ea.principal AS 'principal',
						ea.service_charge AS 'service_charge',
						ea.fee AS 'fee',
						ea.irrecoverable AS 'irrecoverable',
						cp.authorization_code,
						cpr.reason_code AS 'reason_code',
						cpr.reason_text AS 'reason_text',
						cpr.response_text AS 'response_text',
                                                date_format(a.transaction_date_modified, '%m/%d/%Y %H:%i:%S') AS 'return_date',
						a2.name_last,
						a2.name_first
					FROM
						(	SELECT  easub.event_schedule_id,
								SUM(IF(eat.name_short = 'principal', easub.amount, 0)) principal,
								SUM(IF(eat.name_short = 'service_charge', easub.amount, 0)) service_charge,
								SUM(IF(eat.name_short = 'fee', easub.amount, 0)) fee,
								SUM(IF(eat.name_short = 'irrecoverable', easub.amount, 0)) irrecoverable
							FROM	event_amount easub
							LEFT JOIN event_amount_type eat USING (event_amount_type_id)
							WHERE		easub.application_id = {$app_id}
							GROUP BY    easub.event_schedule_id
						)	ea,
						(	SELECT
								tr.*,
								es.origin_id,
								es.origin_group_id,
								es.amount_principal,
								es.amount_non_principal,
								es.event_status,
								es.context,
								es.configuration_trace_data,
								es.is_shifted,
								date_format(es.date_created, '%m/%d/%Y %H:%i:%S') as event_date_created,
								date_format(es.date_modified, '%m/%d/%Y %H:%i:%S') as event_date_modified,
								date_format(tr.date_created, '%m/%d/%Y %H:%i:%S') as transaction_date_created,
								date_format(tr.date_modified, '%m/%d/%Y %H:%i:%S') as transaction_date_modified,
								DATE_FORMAT(tr.date_effective, '%m/%d/%Y') as date_effective_formatted,
								DATE_FORMAT(es.date_event, '%m/%d/%Y') as date_event,
								tt.NAME,
								tt.name_short
							FROM
								transaction_register tr
					            JOIN event_schedule AS es USING (event_schedule_id)
								JOIN transaction_type AS tt USING (transaction_type_id)
							WHERE   tr.transaction_register_id = {$trid}
								AND tr.application_id = {$app_id}
								AND tr.company_id = {$this->server->company_id}
						) a
					LEFT JOIN card_process cp ON (a.card_process_id = cp.card_process_id)
					LEFT JOIN card_process_response cpr ON (cp.reason_code = cpr.reason_code)
					LEFT JOIN agent_affiliation_event_schedule aaes ON (aaes.event_schedule_id = a.event_schedule_id)
					LEFT JOIN agent_affiliation aa ON (aa.agent_affiliation_id = aaes.agent_affiliation_id)
					LEFT JOIN agent a2 ON (aa.agent_id = a2.agent_id)
					WHERE ea.event_schedule_id = a.event_schedule_id ";
			}
			else
			{	// This is for Non-QC Transactions
				$query = "
					SELECT
						a.*,
						ea.principal      AS 'principal',
						ea.service_charge AS 'service_charge',
						ea.fee            AS 'fee',
						ea.irrecoverable  AS 'irrecoverable',
						arc.name_short  AS 'return_code',
						arc.NAME        AS 'return_description',
						date_format(ar.date_request, '%m/%d/%Y %H:%i:%S') AS 'return_date',
						a2.name_last,
						a2.name_first,
						apr.name AS ach_provider_name
					FROM
						(	SELECT  easub.event_schedule_id,
								SUM(IF(eat.name_short = 'principal', easub.amount, 0)) principal,
								SUM(IF(eat.name_short = 'service_charge', easub.amount, 0)) service_charge,
								SUM(IF(eat.name_short = 'fee', easub.amount, 0)) fee,
								SUM(IF(eat.name_short = 'irrecoverable', easub.amount, 0)) irrecoverable
							FROM	event_amount easub
							LEFT JOIN event_amount_type eat USING (event_amount_type_id)
							WHERE		easub.application_id = {$app_id}
							GROUP BY    easub.event_schedule_id
						)	ea,
						(	SELECT
								tr.*,
								es.origin_id,
								es.origin_group_id,
								es.amount_principal,
								es.amount_non_principal,
								es.event_status,
								es.context,
								es.configuration_trace_data,
								es.is_shifted,
								date_format(es.date_created, '%m/%d/%Y %H:%i:%S') as event_date_created,
								date_format(es.date_modified, '%m/%d/%Y %H:%i:%S') as event_date_modified,
								date_format(tr.date_created, '%m/%d/%Y %H:%i:%S') as transaction_date_created,
								date_format(tr.date_modified, '%m/%d/%Y %H:%i:%S') as transaction_date_modified,
								DATE_FORMAT(tr.date_effective, '%m/%d/%Y') as date_effective_formatted,
								DATE_FORMAT(es.date_event, '%m/%d/%Y') as date_event,
								tt.NAME,
								tt.name_short
							FROM
								transaction_register tr
					            JOIN event_schedule AS es USING (event_schedule_id)
								JOIN transaction_type AS tt USING (transaction_type_id)
							WHERE   tr.transaction_register_id = {$trid}
								AND tr.application_id = {$app_id}
								AND tr.company_id = {$this->server->company_id}
						) a
					LEFT JOIN ach USING (ach_id)
					LEFT JOIN ach_batch AS ab ON (ab.ach_batch_id = ach.ach_batch_id)
					LEFT JOIN ach_provider AS apr ON (apr.ach_provider_id = ab.ach_provider_id)
					LEFT JOIN ach_report ar USING(ach_report_id)
					LEFT JOIN ach_return_code arc USING(ach_return_code_id)
					LEFT JOIN agent_affiliation_event_schedule aaes ON (aaes.event_schedule_id = a.event_schedule_id)
					LEFT JOIN agent_affiliation aa ON (aa.agent_affiliation_id = aaes.agent_affiliation_id)
					LEFT JOIN agent a2 ON (aa.agent_id = a2.agent_id)
					WHERE ea.event_schedule_id = a.event_schedule_id ";
			}
		}
		else
		{
			// This is for events without transactions
			//[#28418] add clearing type for more granular ACL
			$query = "
				SELECT
					es.*,
					date_format(es.date_created, '%m/%d/%Y %H:%i:%S') as event_date_created,
					date_format(es.date_modified, '%m/%d/%Y %H:%i:%S') as event_date_modified,
					DATE_FORMAT(es.date_effective, '%m/%d/%Y %H:%i:%S') as date_effective,
					DATE_FORMAT(es.date_event, '%m/%d/%Y') as date_event,
					et.NAME,
					et.name_short,
					ea.principal      AS 'principal',
					ea.service_charge AS 'service_charge',
					ea.fee            AS 'fee',
					ea.irrecoverable  AS 'irrecoverable',
					dc.company_name   AS debt_company_name,
					tt.clearing_type  AS transaction_type,
					a2.name_last,
					a2.name_first
				FROM	event_schedule es
				JOIN event_transaction etr on (etr.event_type_id = es.event_type_id)
				JOIN transaction_type tt on (tt.transaction_type_id = etr.transaction_type_id),
				event_type et,
					(	SELECT
							easub.event_schedule_id,
							SUM(IF(eat.name_short = 'principal', easub.amount, 0)) principal,
							SUM(IF(eat.name_short = 'service_charge', easub.amount, 0)) service_charge,
							SUM(IF(eat.name_short = 'fee', easub.amount, 0)) fee,
							SUM(IF(eat.name_short = 'irrecoverable', easub.amount, 0)) irrecoverable
						FROM	event_amount easub
						LEFT JOIN event_amount_type eat USING (event_amount_type_id)
						WHERE		easub.application_id = {$app_id}
						GROUP BY    easub.event_schedule_id
					)	ea
			    LEFT JOIN debt_company_event_schedule AS dces USING (event_schedule_id)
				LEFT JOIN debt_company AS dc ON (dces.company_id = dc.company_id)
				LEFT JOIN agent_affiliation_event_schedule aaes ON (aaes.event_schedule_id = ea.event_schedule_id)
				LEFT JOIN agent_affiliation aa ON (aa.agent_affiliation_id = aaes.agent_affiliation_id)
				LEFT JOIN agent a2 ON (aa.agent_id = a2.agent_id)
				WHERE	es.event_schedule_id = {$esid}
				AND es.application_id = {$app_id}
				AND es.company_id = {$this->server->company_id}
				AND et.event_type_id = es.event_type_id
				AND ea.event_schedule_id = es.event_schedule_id
				GROUP BY et.event_type_id
				";
		}

		$result = $db->query($query);
		$row = $result->fetch(PDO::FETCH_OBJ);

		if(!isset($row->transaction_type) && !empty($tt))
		{
			$row->transaction_type = $tt->transaction_type;
		}
		$fields = array("transaction_status", "ach_id");
		foreach ($fields as $f)
		{
			if (!isset($row->$f)) $row->$f = "N/A";
		}
		$row->agent_name = isset($row->name_last) ? $row->name_last . ', ' . $row->name_first : 'N/A';
		$row->is_shifted = $row->is_shifted == '1' ? 'Yes' : 'No';
		return $row;
	}

	public function Get_ACH_Return_Codes()
	{
		static $codes;
		if(! empty($codes)) 
		{
			return $codes;
		}

		$query = "
					SELECT
						ach_return_code_id,
						name_short,
						name, 
						IF(is_fatal='yes','F','NF') AS is_fatal
					FROM ach_return_code
					WHERE active_status='active'
					ORDER BY name_short
				";

		$codes = ECash::getMasterDb()->query($query)->fetchAll(PDO::FETCH_OBJ);
		return $codes;
	}

	public function Get_Card_Return_Codes()
	{
		static $codes;
		if(! empty($codes)) 
		{
			return $codes;
		}

		$query = "
					SELECT
						reason_code as card_return_code_id,
						response_text as name_short,
						reason_text as name, 
						IF(fatal_fail,'F','NF') AS is_fatal
					FROM card_process_response
					ORDER BY name_short
				";

		$codes = ECash::getMasterDb()->query($query)->fetchAll(PDO::FETCH_OBJ);
		return $codes;
	}

	public function ModifyDocument()
	{
		$document_model = ECash::getFactory()->getModel('Document');
		if(!$document_model->loadBy(array('document_event_type' => 'received', 'archive_id' => $this->request->archive_id)))
		{
			$_SESSION['current_app']->tiff_message = "<font color=\"red\"><b>Archive ID already used</b></font>";
		}
		else
		{
			eCash_Document::Change_Document_Archive_ID($this->server, $this->request->document_id, $this->request->archive_id);
		}
		ECash::getTransport()->Set_Levels('close_pop_up');
	}
	
	public function resendDocument($method='email')
	{
		$rcpt = array((strtolower($method) == 'fax') ? 'fax_number': 'email_primary' => $this->request->destination);
		$prpc = new ECash_Documents_Condor();
		$response = $prpc->getPrpc()->Send($this->request->archive_id, $rcpt, (strtolower($method) == 'fax') ? 'FAX': 'EMAIL');
		ECash::getTransport()->Set_Levels('close_pop_up');
	}

	public function DeleteDocument()
	{
		eCash_Document::Delete_Archive_Document($this->server, $this->request->document_id);
		ECash::getTransport()->Set_Levels('close_pop_up');

	}

	/**
	 * Function to move an application to the Action Queue for Review
	 *
	 * @param string Reason the account was sent to the queue
	 */
	public function Send_To_Action_Queue()
	{
		require_once(SQL_LIB_DIR . "comment.func.php");

		if(empty($this->request->reason_comment)) 
		{
			$reason = "Review SSN";
		} 
		else 
		{
			$reason = $this->request->reason_comment;
		}

		$application_id = $this->request->application_id;
		Add_Comment($this->company_id, $application_id, $this->agent_id, $reason, 'standard', $this->server->system_id);
		//move_to_automated_queue('Action_Queue',$application_id,'',time(), null);
		$qm = ECash::getFactory()->getQueueManager();
		if($qm->hasQueue('action_queue'))
		{
			$qi = $qm->getQueue('action_queue')->getNewQueueItem($application_id);
			$qm->moveToQueue($qi, 'action_queue') ;
		}

		$return_obj = $this->loan_data->Fetch_Loan_All($application_id);
		ECash::getTransport()->Add_Levels('overview','personal','view','general_info','view');
		ECash::getTransport()->Set_Data($return_obj);
	}

	public function Dump_Request()
	{
		var_dump($this->request);
		die();
	}

	private function Check_Fraud($application_id)
	{
		require_once(SERVER_CODE_DIR . 'dummy_module.class.php');
		require_once(SERVER_MODULE_DIR  . 'fraud/fraud.class.php');
		require_once(ECASH_COMMON_DIR . 'Fraud/FraudCheck.php');

		$fraud_query = new Application_Fraud_Query($this->server);

		//I don't like re-loading the application after every save
		//(adds yet another query to the request), but currently there
		//is not choice because there could be a fraud rule where this
		//app matches by last name and SSN, but not first name -- by
		//changing first name to fit the rule, it is now fraud, so we
		//need to make sure all field are loaded for the fraud
		//comparison.
		$application = $fraud_query->Get_Fraud_App($application_id);
		//to remedy the situation, we could use an ECashApplication
		//object universally and when Fetch_Loan_All returns that we
		//could do the fraud check against it and then only re-load if
		//it matched fraud rules

		$checker = new FraudCheck(ECash::getMasterDb());
		// Required for the Fraud Modules to work outside of a typical request
   		$dummy_module = new Dummy_Module($this->server, $this->request, 'fraud');

   		$fraud = new Fraud($this->server, $this->request, NULL, $dummy_module);
		$fraud->Check_Fraud($checker, $application);
	}

	public function Save_Vehicle_Data()
	{
		if(isset($this->request->co_borrower))
			$this->Save_Co_Borrower();
		
		require_once(SERVER_CODE_DIR . "vehicle_data.class.php");
		$result = Vehicle_Data::Update_Vehicle_Data($this->request);
		$return_obj = $this->loan_data->Fetch_Loan_All($this->request->application_id);
		ECash::getTransport()->Add_Levels('overview','vehicle','view','general_info','view');
		ECash::getTransport()->Set_Data($return_obj);
	}

	/**
	 * GForge [#10188]
	 */
	private function Save_Co_Borrower()
	{
		require_once(LIB_DIR . "Application/Contact.class.php");
		$application_contact = new eCash_Application_Contact($this->request->application_id);
		
		if($this->request->co_borrower_id && trim($this->request->co_borrower) == '') //delete the co-borrower
		{			
			$application_contact->deleteContact($this->request->co_borrower_id);
			/**
			 * Per the spec:
			 * 6.1.3.1 If no information resides in the Co-Borrower field,
			 *         the documents do not need updating.
			 */ 
		}
		else if($this->request->co_borrower != $this->request->old_co_borrower) //co-borrower has changed
		{
			
			if($this->request->co_borrower_id) //there's already a co-borrower, replace it
			{
				$application_contact->updateContact($this->request->co_borrower_id, array('value' => $this->request->co_borrower));
			}
			else
			{
				$application_contact->addContact($this->request->application_id, $this->request->co_borrower, 'Co-Borrower', 'co_borrower');
			}			
			$this->Set_In_Process($this->request->application_id);
		}
	}

	/**
	 * GForge [#29112]
	 */
	private function Set_In_Process($application_id)
	{		
		//[#35613] Don't allow a 'customer' to be set to 'In Process'
		$application = ECash::getApplicationById($application_id);
		$status = $application->getStatus();

                if($status->level0 == 'approved')
		{
                        Remove_Unregistered_Events_From_Schedule($application_id);
		}
		
		if(in_array($status->level0, array('underwriting', 'verification','approved'))
		   || in_array($status->level1, array('underwriting', 'verification')))
		{
			$application->application_status_id = ECash::getFactory()->getReferenceList('ApplicationStatusFlat')->toId('in_process::prospect::*root');
			$application->save();
		}
	}

	/**
	 * GForge [#29112]
	 */
	public function Re_ESig()
	{
		$application = ECash::getApplicationById($this->request->application_id);

		//send loan documents
		ECash_Documents_AutoEmail::Send($this->request->application_id,'LOAN_NOTE_AND_DISCLOSURE');

		$application->application_status_id = ECash::getFactory()->getReferenceList('ApplicationStatusFlat')->toId('pending::prospect::*root');
		$application->save();
	}
	
	public function switchSchedule()
	{
		$application_id = $this->request->application_id;
		$this->log->Write("Switching schedule for {$application_id}");
		
		$flag_type = ECash::getFactory()->getModel('FlagType');
		$loaded = $flag_type->loadBy(array('name_short'=>'card_schedule','active_status'=>'active',));
		
		if ($loaded)
		{
			$app = 	ECash::getApplicationByID($application_id);
			$flags = $app->getFlags();
			if($flags->get('card_schedule'))
			{
				$flags->clear('card_schedule');
				$switched_to_card = FALSE;
			}
			else
			{
				$flags->set('card_schedule');
				$switched_to_card = TRUE;
			}
		}
		
		Complete_Schedule($application_id);
		
		if ($switched_to_card) alignActionDateForCard($application_id);

		ECash::getTransport()->Set_Data($this->loan_data->Fetch_Loan_All($application_id));
		ECash::getTransport()->Add_Levels('overview', 'schedule', 'view');
	}

	public function fetchAchEventTypes()
	{
		$db = ECash::getMasterDb();
		$ach_event_id_ary = array();		
		$query = "
			SELECT DISTINCT 
				et.event_type_id 
			FROM 
				event_transaction et, 
				transaction_type tt
			WHERE
				et.transaction_type_id	= tt.transaction_type_id 
				AND tt.clearing_type = 'ach'
				AND et.active_status = 'active'
				AND tt.company_id = {$this->company_id}
		";
		$result = $db->Query($query);
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$ach_event_id_ary[] = $row['event_type_id'];
		}
		
		return $ach_event_id_ary;
	}
}
?>
