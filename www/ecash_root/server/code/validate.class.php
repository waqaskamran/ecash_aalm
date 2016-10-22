<?php

require_once("data_validation.1.php");
require_once(SQL_LIB_DIR . "util.func.php");
require_once(LIB_DIR . "business_rules.class.php");

class Validate
{
	protected $validation_rules;
	protected $validation_errors;
	protected $dv_obj;
	protected $last_normalized;

	/**
	 * This needs to be set the the number of validation rules
	 * currently in place for each personal reference in the file
	 * validation_rules.php.  Look at $rules->validation->ref_name_1
	 * for an example.
	 *
	 * @var integer
	 */
	public static $NUM_PERSONAL_REFERENCES = 6;

	function __construct($server)
	{
		//changed to include all of the time
		include('validation_rules.php');
		if(file_exists(CUSTOMER_LIB . 'validation_rules.php'))
		{
			include(CUSTOMER_LIB . 'validation_rules.php');
		}

		$holidays = Fetch_Holiday_List();

		$this->validation_rules = $rules;
		$this->validation_errors = array();
		$this->last_normalized = array();
		$this->dv_obj = new Data_Validation($holidays);

	}

	public function Get_Last_Normalized()
	{
		return (object) $this->last_normalized;
	}

	public function Clear_Normalized()
	{
		$this->last_normalized = array();

		return TRUE;
	}

	protected function Validate_Data($val_ruleset, $data)
	{
		(object) $data;

		$this->Clear_Normalized();

		foreach($val_ruleset as $field => $required)
		{
			if( isset($data->{$field}) && strlen(trim($data->{$field})) )
			{
				if( isset($this->validation_rules->normalize->{$field}) )
				{
					$normalized_data = $this->dv_obj->Normalize(trim($data->$field), $this->validation_rules->normalize->{$field});
				}
				else
				{
					$normalized_data = $data->{$field};
				}

				if( is_string($normalized_data) )
					$normalized_data = strtolower($normalized_data);

				$this->last_normalized[$field] = $normalized_data;

				$val_result = $this->dv_obj->Validate($normalized_data, $this->validation_rules->validation->{$field});

				if( !$val_result['status'] )
				{
					$this->validation_errors[$field] = "Invalid";
				}

			}
			elseif( $required )
			{
				$this->validation_errors[$field] = "Required";
			}
		}

		return TRUE;
	}

	protected function Consolidate_Name_Errors()
	{
		if( isset($this->validation_errors['name_last']) )
		{
			$this->validation_errors['name'] = "Last name is: " . $this->validation_errors['name_last'];
		}
		elseif( isset($this->validation_errors['name_first']) )
		{
			$this->validation_errors['name'] = "First name is: " . $this->validation_errors['name_first'];
		}
	}

	public function Validate_Card($data)
	{
		$val_ruleset = (object) array();

		$val_ruleset->card_number = TRUE;
		$val_ruleset->card_exp1   = TRUE;
		$val_ruleset->card_exp2   = TRUE;

		$val_ruleset->cardholder_name = TRUE;
		$val_ruleset->card_street = TRUE;
		$val_ruleset->card_zip = TRUE;

		$this->Validate_Data($val_ruleset, $data);
		$this->Consolidate_Name_Errors();

		return $this->validation_errors;
	}
	

	public function Validate_Personal($data)
	{
		$val_ruleset = (object) array();
		$val_ruleset->name_first = TRUE;
		$val_ruleset->name_last = TRUE;
		$val_ruleset->ssn = TRUE;
		$val_ruleset->legal_id_number = TRUE;
		$val_ruleset->street = TRUE;
		$val_ruleset->unit = FALSE;
		$val_ruleset->city = TRUE;
		$val_ruleset->zip = TRUE;
		$val_ruleset->customer_email = TRUE;
		$val_ruleset->state = TRUE;

		if( !checkdate($data->EditAppPersonalInfoCustDobmonth, $data-EditAppPersonalInfoCustDobday, $data->EditAppPersonalInfoCustDobyear) )
		{
			$this->validation_errors['dob'] = "Invalid";
		}

		$this->Validate_Data($val_ruleset, $data);
		$this->Consolidate_Name_Errors();

		return $this->validation_errors;
	}

	public function Validate_Personal_References($data)
	{
		$val_ruleset = (object) array();

		/**
		 * To support a variable number of references, I'm resorting to
		 * using a regular expression against the request to determine if I
		 * need to validate against the additional field.
		 */
		foreach($data as $field_name => $value)
		{
			if(preg_match('/^ref_\w{1,16}_(\d{1,2})/', $field_name))
			{
				if(!empty($value))	$val_ruleset->$field_name = TRUE;
			}
		}

		$this->Validate_Data($val_ruleset, $data);

		return $this->validation_errors;
	}

	public function Validate_Dispositions($data)
	{
		$val_ruleset = (object) array();

		$rules = $this->Load_Business_Rules($rule_set_id);

		$this->Validate_Data($val_ruleset, $data);

		$this->Consolidate_Name_Errors();

		return $this->validation_errors;
	}


	public function Validate_General_Info($data, $rule_set_id)
	{
		$val_ruleset = (object) array();
		$val_ruleset->name_first = TRUE;
		$val_ruleset->name_last = TRUE;
		$val_ruleset->phone_home = TRUE;
		$val_ruleset->phone_cell = FALSE;
		$val_ruleset->phone_work = TRUE;
		$val_ruleset->phone_work_ext = FALSE;
		$val_ruleset->customer_email = TRUE;
		$val_ruleset->income_monthly = TRUE;

		$rules = $this->Load_Business_Rules($rule_set_id);
		if (empty($rules['require_bank_account']) || $rules['require_bank_account'] == 'On' || $data->income_direct_deposit == 'yes') {
			$val_ruleset->bank_aba = TRUE;
			$val_ruleset->bank_name = TRUE;
			$val_ruleset->bank_account = TRUE;

			if( !isset($data->bank_account_type) || !in_array($data->bank_account_type, array('checking','savings')) )
			$this->validation_errors['bank_account_type'] = "Invalid";
		}

		$this->Validate_Data($val_ruleset, $data);

		$this->Consolidate_Name_Errors();

		return $this->validation_errors;
	}


	/**
	 *
	 */
	public function Validate_Application($data)
	{
		if( !isset($data->fund_amount) || !is_numeric($data->fund_amount) )
		$this->validation_errors['fund_amount'] = "Invalid";

		if( !isset($data->income_direct_deposit) || !in_array($data->income_direct_deposit, array('yes','no')) )
		$this->validation_errors['income_direct_deposit'] = "Invalid";

		// DLH, 2005.11.16, fund_date and due_date are no longer editable so they don't need to be validated.
		// It appears that Fraud, Loan Servicing, Collections, Funding, Conversions all use the same Application -> Application Info
		// screen (aka layer) so I think it should be safe to comment this out.  I don't think commenting this out
		// can break anything though I'm not quite sure where these values get set initially (probably from some enrollment
		// screen that doesn't use this method).
		// I NEED TO CHECK THIS ASSUMPTION WITH MARC CARTRIGHT
		// ----------------------------------------------------------------------------------------------------------------------
		// if( !checkdate($data->date_fund_actual_month, $data->date_fund_actual_day, $data->date_fund_actual_year) )
		// {
		// 	$this->validation_errors['date_fund_actual'] = "Invalid";
		// }
		//

		if (isset($data->new_first_due_date) && $data->new_first_due_date == 'yes')
		{
			if(!checkdate($data->date_first_payment_month,
				      $data->date_first_payment_day,
				      $data->date_first_payment_year))
			{
				$this->validation_errors['date_first_payment'] = "Invalid";
			}
			else
			{
				$date_string = $data->date_first_payment_month . '-'
					. $data->date_first_payment_day . '-' . $data->date_first_payment_year;
				if (in_array($date_string,unserialize(str_replace('\\',"",$data->paydate_list))))
				{
					$date1_parts = explode('-', $data->date_fund_actual_hidden);
					$date2_parts = explode('-', $date_string);

					if(function_exists("gregoriantojd"))
					{
						$start_date = gregoriantojd($date1_parts[0], $date1_parts[1], $date1_parts[2]);
						$stop_date = gregoriantojd($date2_parts[0], $date2_parts[1], $date2_parts[2]);
						$date_diff = $stop_date - $start_date;
					}
					else
					{
						$date_diff = abs(strtotime($date_string) - strtotime($data->date_fund_actual_hidden)) / 86400;
					}

					$business_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());
					$rule_set_id = $business_rules->Get_Rule_Set_Id_For_Application($_SESSION['current_app']->application_id);
					$rule_sets = $business_rules->Get_Rule_Set_Tree($rule_set_id);

                    $due_date_offset = $rule_sets['grace_period'];
                    // Include the reaction due date for the grace period for react apps
                    //$react_due_time = strtotime($rule_sets['react_grace_date']);
                    //$react_due_offset = $react_due_time - time();
                    //$react_due_offset = ceil($react_due_offset / (24 * 60 * 60));
                    //if ($react_due_offset > $due_date_offset) $due_date_offset = $react_due_offset;

					if ($date_diff < $due_date_offset)
					{
						$this->validation_errors['date_first_payment'] = "The Date First Payment must be at least<br>10 days from the fund date";
					}
				}
				else
				{
					$this->validation_errors['date_first_payment'] = "First Due Date must fall on a paydate.";
				}
			}
		}

		return $this->validation_errors;
	}




	public function Validate_Employment($data)
	{
		$val_ruleset = (object) array();

		$val_ruleset->employer_name = TRUE;
		$val_ruleset->phone_work = TRUE;
		$val_ruleset->phone_work_ext = FALSE;
		$val_ruleset->job_title = FALSE;
		$val_ruleset->income_monthly = TRUE;

		$this->Validate_Data($val_ruleset, $data);

		return $this->validation_errors;
	}

	public function Validate_Campaign_Info($data)
	{
		$val_ruleset = (object) array();


		$val_ruleset->promo_sub_code = FALSE;
		$val_ruleset->url = FALSE;


		$this->Validate_Data($val_ruleset, $data);

		return $this->validation_errors;
	}

	public function Validate_Comment($data)
	{
		$val_ruleset = (object) array();

		$val_ruleset->comment = FALSE;

		$this->Validate_Data($val_ruleset, $data);

		return $this->validation_errors;
	}

	public function Validate_Document($data)
	{
		$val_ruleset = (object) array();

		$val_ruleset->alt_xfer_date = FALSE;
		//$val_ruleset->verified_date = FALSE;

		$this->Validate_Data($val_ruleset, $data);

		return $this->validation_errors;
	}

	public function Validate_Reference($data)
	{
		$val_ruleset = (object) array();

		$val_ruleset->full_name = FALSE;
		$val_ruleset->phone = FALSE;
		$val_ruleset->relationship = FALSE;

		$this->Validate_Data($val_ruleset, $data);

		return $this->validation_errors;
	}

	public function Validate_Cashline($data)
	{
		$val_ruleset = (object) array();

		$val_ruleset->employer_name = TRUE;
		$val_ruleset->job_title = FALSE;
		$val_ruleset->shift = FALSE;
		$val_ruleset->bank_name = TRUE;
		$val_ruleset->name_first = TRUE;
		$val_ruleset->name_middle = FALSE;
		$val_ruleset->name_last = TRUE;
		$val_ruleset->customer_email = TRUE;
		$val_ruleset->ssn = TRUE;
		$val_ruleset->legal_id_number = TRUE;
		$val_ruleset->street = TRUE;
		$val_ruleset->unit = FALSE;
		$val_ruleset->city = TRUE;

		$val_ruleset->phone_work = FALSE;
		$val_ruleset->phone_work_ext = FALSE;
		$val_ruleset->phone_home = TRUE;
		$val_ruleset->phone_cell = FALSE;
		$val_ruleset->phone_fax = FALSE;

		$val_ruleset->date_fund_estimated = FALSE;

		$this->Validate_Data($val_ruleset, $data);

		return $this->validation_errors;
	}

    public function Load_Business_Rules($rule_set_id)
    {
        static $ruletree;

        if(! empty($ruletree)) {
            return $ruletree;
        }

        $biz_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());
        $rules = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

        $ruletree = $biz_rules->Get_Rule_Set_Tree($rule_set_id);
        return ($ruletree);
    }



}

?>
