<?php

/**
 * Class for handling Do Not Loan actions
 * 
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */
class Do_Not_Loan
{
	/**
	 * Database connection
	 * 
	 * @var DB_Database_1
	 */
	protected $db;

	/**
	 * Holds information retrieved by Get_DNL_Info
	 * 
	 * @var array
	 */
	protected $dnl_info;

	/**
	 * Constructor for the Do_Not_Loan object
	 * 
	 * @param DB_Database_1 $db
	 * @return void
	 */
	public function __construct(DB_Database_1 $db)
	{
		$this->db = $db;
	}

	/**
	 * Sets a do not loan flag for a company_id on SSN
	 * 
	 * @param int $company_id
	 * @param string $category
	 * @param string $explanation
	 * @param int $agent_id
	 * @param string $ssn
	 * @param string $other_reason
	 * @return bool
	 */
	public function Set_Do_Not_Loan($company_id, $category, $explanation, $agent_id, $ssn, $other_reason)
	{
		$mod_dnlf_cat = ECash::getFactory()->getReferenceModel('doNotLoanFlagCategory');
		$mod_dnlf_cat->loadBy(array('name' => $category));

		$mod_dnlf = ECash::getFactory()->getModel('doNotLoanFlag');
		$mod_dnlf->ssn = $ssn;
		$mod_dnlf->company_id = $company_id;
		$mod_dnlf->category_id = $mod_dnlf_cat->category_id;
		$mod_dnlf->other_reason = $other_reason;
		$mod_dnlf->explanation = $explanation;
		$mod_dnlf->active_status = 'active';
		$mod_dnlf->agent_id = $agent_id;
		$mod_dnlf->date_created = time();
		$mod_dnlf->date_modified = time();

		$result = $mod_dnlf->save();
		$this->clearDNLInfoCache();

		return $result;
	}

	/**
	 * Insert a do not loan flag override for company_id and SSN
	 * 
	 * @param int $company_id
	 * @param int $agent_id
	 * @param int $ssn
	 * @return bool
	 */
	public function Override_Do_Not_Loan($company_id, $agent_id, $ssn)
	{
		$mod_dnlf_over = ECash::getFactory()->getModel('doNotLoanFlagOverride');
		$mod_dnlf_over->ssn = $ssn;
		$mod_dnlf_over->company_id = $company_id;
		$mod_dnlf_over->agent_id = $agent_id;
		$mod_dnlf_over->date_created = time();
		$mod_dnlf_over->date_modified = time();

		$result = $mod_dnlf_over->save();
		$this->clearDNLInfoCache();

		return $result;
	}

	/**
	 * Checks if a DNL flag exists for an SSN for any company
	 * 
	 * @param int $ssn
	 * @return bool
	 */
	public function Does_SSN_In_Table($ssn)
	{
		$result = FALSE;
		$dnl_info = $this->Get_DNL_Info($ssn);
		$result = !empty($dnl_info);
		return $result;
	}

	/**
	 * Checks if a DNL flag exists for an SSN for a company id
	 * 
	 * @param int $ssn
	 * @param int $company_id
	 * @return bool
	 */
	public function Does_SSN_In_Table_For_Company($ssn, $company_id)
	{
		$result = FALSE;

		$dnl_info = $this->Get_DNL_Info($ssn);
		if (is_array($dnl_info))
		{
			foreach ($dnl_info as $row)
			{
				if ($row->company_id == $company_id)
				{
					$result = TRUE;
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Checks if a DNL flag exists for an SSN for company ids other than the specified
	 * 
	 * @param int $ssn
	 * @param int $company_id
	 * @return bool
	 */
	public function Does_SSN_In_Table_For_Other_Company($ssn, $company_id)
	{
		$result = FALSE;
		$dnl_info = $this->Get_DNL_Info($ssn);
		if (is_array($dnl_info))
		{
			foreach ($dnl_info as $row)
			{
				if ($row->company_id != $company_id)
				{
					$result = TRUE;
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Checks if a DNL flag override exists for an SSN for a company
	 * 
	 * @param int $ssn
	 * @param int $company_id
	 * @return bool
	 */
	public function Does_Override_Exists_For_Company($ssn, $company_id)
	{
		$result = FALSE;

		$overrides = ECash::getFactory()->getAppClient()->getDoNotLoanFlagOverrideAll($ssn);
		$overrides = isset($overrides->item) ? $overrides->item : array();
		if (!is_array($overrides)) $overrides = array($overrides);
		$result = FALSE;
		if (!empty($overrides))
		{
			foreach($overrides as $override)
			{
				if ($override->company_id == $company_id)
				{
					$result = TRUE;
					break;
				}
			}
		}
		return $result;
	}

	/**
	 * Returns the Do Not Loan Flag information for the ssn
	 * 
	 * @param string $ssn
	 * @return array
	 */
	public function Get_DNL_Info($ssn)
	{
		if (isset($this->dnl_info[$ssn]))
		{
			$values = $this->dnl_info[$ssn];
		}
		else
		{
			$dnlc_model = ECash::getFactory()->getReferenceModel("DoNotLoanFlagCategory");
			$comp_model = ECash::getFactory()->getModel("Company");
			$agent_model = ECash::getFactory()->getModel("Agent");
			$as_values = ECash::getFactory()->getAppClient()->getDoNotLoanFlagAll($ssn);
			$as_values = isset($as_values->item) ? $as_values->item : $as_values;
			//if (!is_array($as_values)) $as_values = array($as_values);
			$values = array();
			if (empty($as_values)) $as_values = array();
			foreach ($as_values as $value)
			{
				if (!empty($value->active_status))
				{
					$agt_loaded = $agent_model->loadBy(array('agent_id' => $value->modifying_agent_id));
					$value->agent_id = $value->modifying_agent_id;
					$value->name_last = ($agt_loaded) ? $agent_model->name_last : 'Unknown';
					$value->name_first = ($agt_loaded) ? $agent_model->name_first : 'Unknown';

					$com_loaded = $comp_model->loadBy(array('company_id' => $value->company_id));
					$value->company_name = ($com_loaded) ? $comp_model->name : NULL;
					$value->name_short = ($com_loaded) ? $comp_model->name_short : NULL;

					$value->name = $value->category;
					$value->active_status = ($value->active_status) ? 'active' : 'inactive';
					$value->date_created = date('Y-m-d H:i:s',strtotime($value->date_created));
					$value->date_modified = date('Y-m-d H:i:s',strtotime($value->date_modified));
					$values[] = $value;
				}
			}

			$this->dnl_info[$ssn] = $values;
		}


		return $values;
	}

	/**
	 * Clear (DELETE) all do not loan flags by ssn and company_id
	 * 
	 * @param int $ssn
	 * @return void
	 */
	public function clearDoNotLoan($ssn, $company_id)
	{
		$do_not_loan_model = ECash::getFactory()->getModel('DoNotLoanFlag');
		if($do_not_loan_model->loadBy(array('ssn' => $snn, 'company_id' => $company_id)))
		{
			$do_not_loan_model->delete();
		}
		ECash::getFactory()->getAppClient()->deleteDoNotLoanFlag($company_id, $ssn);
		$this->clearDNLInfoCache();
	}

	/**
	 * Set all do not loan flags to inactive
	 * 
	 * @param int $ssn
	 * @param int $company_id
	 * @param int $agent_id
	 * @return void
	 */
	public function Set_DNL_Inactive($ssn, $company_id, $agent_id)
	{

		$do_not_loan_model = ECash::getFactory()->getModel('DoNotLoanFlag');
		if($do_not_loan_model->loadBy(array('ssn' => $snn, 'company_id' => $company_id, 'active_status' => 'active')))
		{
			$do_not_loan_model->active_status = 'inactive';
			$do_not_loan_model->agent_id = $agent_id;
			$do_not_loan_model->save();
		}
		ECash::getFactory()->getAppClient()->deleteDoNotLoanFlag($company_id, $ssn);
		$this->clearDNLInfoCache();
	}

	/**
	 * Remove do not loan override flag by company id for an ssn
	 * 
	 * @param int $ssn
	 * @param int $company_id
	 * @return bool
	 */
	public function Remove_Override_DNL($ssn, $company_id)
	{
		$result = TRUE;
		$mod_dnl_over = ECash::getFactory()->getModel('DoNotLoanFlagOverride');
		if ($mod_dnl_over->loadBy(array('ssn' => $ssn, 'company_id' => $company_id)))
		{
			$result = $mod_dnl_over->delete();
		}
		else 
		{
			/**
			 * It is remotely possible that DNL will not be loadable from ldb but still exist
			 * inside of the app service. If that is the case loadBy will return false (the model
			 * will not be deletable) and this call should take care of any lingering flags in the
			 * app service. When this is exclusively app service this call will be all you need.
			 */
			ECash::getFactory()->getAppClient()->deleteDoNotLoanFlagOverride($company_id, $ssn);
		}
		
		$this->clearDNLInfoCache();

		return $result;
	}

	/**
	 * Clears any loaded DNL info so when it is read it must reload first
	 * This should be done after every action that modifies this data in the DB if possible
	 * 
	 * @return void
	 */
	protected function clearDNLInfoCache()
	{
		$this->dnl_info = NULL;
	}

	/**
	 * Get available DNL categories from the ECash DB
	 * 
	 * @return array
	 */
	public function Get_Category_Info()
	{
		$query = "
			SELECT name 
			FROM do_not_loan_flag_category 
			WHERE active_status='active' 
		";
		$st = $this->db->query($query);
		return $st->fetchAll(PDO::FETCH_OBJ);
	}

	public function Add_To_DNL_Audit($company_id, $ssn, $table_name, $column_name, $value_before, $value_after, $agent_id)
	{
		$do_not_loan_audit_model = ECash::getFactory()->getModel('DoNotLoanAudit');
		$do_not_loan_audit_model->date_created = time();
		$do_not_loan_audit_model->company_id = $company_id;
		$do_not_loan_audit_model->ssn = $ssn;
		$do_not_loan_audit_model->table_name = $table_name;
		$do_not_loan_audit_model->column_name = $column_name;
		$do_not_loan_audit_model->value_before = $value_before;
		$do_not_loan_audit_model->value_after = $value_after;
		$do_not_loan_audit_model->agent_id = $agent_id;
		return	$do_not_loan_audit_model->save();

	}

	public function Get_DNL_Audit_Log($ssn)
	{

		$agent_model = ECash::getFactory()->getModel("Agent");
		
		$log_entries = ECash::getFactory()->getAppClient()->getDoNotLoanAudit($ssn);

		if(empty($log_entries)) return array();

		foreach($log_entries as $entry)
		{
			if(empty($entry)) continue;
			
			$loaded = $agent_model->loadBy(array('agent_id' => $entry->modifying_agent_id));
			$entry->date_created = date('Y-m-d H:i:s', strtotime($entry->date_created));
			$entry->table_name = $entry->table_name;
			$entry->value_before = $entry->old_value;
			$entry->value_after = $entry->new_value;
			$entry->agent_id = $entry->modifying_agent_id;
			$entry->name_first = ($loaded) ? $agent_model->name_first : NULL;
			$entry->name_last = ($loaded) ? $agent_model->name_last : NULL;
		}

		return $log_entries;
	}

	/**
	 * Get DNL override flag for ssn if it exists
	 * 
	 * @param int $ssn
	 * @return array
	 */
	public function Get_DNL_Override_Info($ssn)
	{
		$mod_company = ECash::getFactory()->getModel('Company');
		
		$values = ECash::getFactory()->getAppClient()->getDoNotLoanFlagOverrideAll($ssn);
		$values = isset($values->item) ? $values->item : array();
		if (!is_array($values)) $values = array($values);
		$results = array();
		if (!empty($values))
		{
			foreach($values as $value)
			{
					$mod_company->loadBy(array('company_id'=>$value->company_id));
					$result->name_short = $mod_company->name_short;
					$result->name = $mod_company->name;
					$results[] = $result;
			}
		}

		return $results;
	}
}

?>
