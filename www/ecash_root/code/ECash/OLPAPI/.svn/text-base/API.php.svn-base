<?php

/**
 * Description of ECash_Crypto_OLP_API
 *
 * Revision History:
 *	04.20.2009 - bszerdy - getDataByTrackKey() now takes an array of keys.
 *
 * @copyright Copyright &copy; 2009 The Selling Source, Inc.
 * @package Crypto
 * @author Bill Szerdy <bill.szerdy@sellingsource.com>
 * @created Mar 23, 2009
 * @version $Revision$
 */
class ECash_OLPAPI_API
{
	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	/**
	 * @var ECash_Factory
	 */
	protected $factory;

	/**
	 * @param DB_IConnection_1 $db
	 * @param ECash_Factory $factory
	 */
	public function __construct(DB_IConnection_1 $db, ECash_Factory $factory)
	{
		$this->db = $db;
		$this->factory = $factory;
	}

	/**
	 * Return an array of decrypted customer information
	 *
	 * @param integer $application_id
	 * @return array or FALSE
	 */
	public function getDataByApplicationId($application_id)
	{
		try
		{
			$app_model = $this->factory->getModel('Application');
			$app_model->loadBy(array('application_id' => $application_id));
			return empty($app_model->application_id) ? FALSE : $this->loadReturnArray($app_model);
		}
		catch (Exception $e)
		{
			return array
			(
				'html'		=> "<h3>Error!</h3><p>{$e->getMessage()}</p><p>".nl2br($e->getTraceAsString())."</p>",
				'function'	=> __FILE__."->".__METHOD__,
				'success'	=> FALSE,
				'error'		=> $e->getMessage(),
			);
		}
	}

	/**
	 * Returns an array of decrypted customer information. If no application information
	 *	is returned from the call, it is not included in the return array.
	 *
	 * @param array $track_keys
	 * @return array
	 */
	public function getDataByTrackKey(array $track_keys)
	{
		$retval = array();
		try
		{
			foreach ($track_keys as $key)
			{
				$app_models = $this->factory->getModel('ApplicationList');
				$app_models->loadBy(array('track_id' => $key));

				$cur_app_model		= FALSE;
				$last_date_created	= 0;

				foreach ($app_models as $app_model)
				{
					if ($app_model->date_created >= $last_date_created)
					{
						$cur_app_model		= $app_model;
						$last_date_created	= $app_model->date_created;
					}
				}

				/**
				 *  return false if none exist
				 */
				if ($cur_app_model != FALSE)
				{
					$retval[$key] = $this->loadReturnArray($cur_app_model);
				}
			}
			return $retval;
		}
		catch (Exception $e)
		{
			return array
			(
				'html'		=> "<h3>Error!</h3><p>{$e->getMessage()}</p><p>".nl2br($e->getTraceAsString())."</p>",
				'function'	=> __FILE__."->".__METHOD__,
				'success'	=> FALSE,
				'error'		=> $e->getMessage(),
			);
		}
	}

	/**
	 *
	 * @param string $ssn
	 * @param string $dob
	 * @param integer $react_app_id
	 * @return array or FALSE
	 */
	public function getDataByReactInfo($ssn, $dob, $react_app_id)
	{
		try
		{
			$load_by = array
			(
				'ssn'				=> $ssn,
				'dob'				=> $dob,
				'application_id'	=> $react_app_id
			);

			$app_model = $this->factory->getModel('Application');
			$app_model->loadBy($load_by);

			return empty($app_model->application_id) ? FALSE : $this->loadReturnArray($app_model);
		}
		catch (Exception $e)
		{
			return array
			(
				'html'		=> "<h3>Error!</h3><p>{$e->getMessage()}</p><p>".nl2br($e->getTraceAsString())."</p>",
				'function'	=> __FILE__."->".__METHOD__,
				'success'	=> FALSE,
				'error'		=> $e->getMessage(),
			);
		}
	}

	/**
	 * @param integer $application_id
	 * @param string $phone_work
	 * @param string $dob
	 * @return string pass|fail|fail_one_remaining|locked
	 */
	public function validate($application_id, $phone_work, $dob)
	{
		$application_id = (int)$application_id;
		$application = ECash::getApplicationByID($application_id);

		$company_model = $this->factory->getModel('Company');
		$company_model->loadBy(array('company_id' => $application->getCompanyId()));
		
		$business_rules = new ECash_BusinessRules($this->db);
		$settings = $business_rules->Get_Rule_Set_Component_Parm_Values($company_model->name_short, 'login_lock');
		$rate = $settings['max_attempt'];

		$lock = $this->factory->getModel('ApplicationLoginLock');
		$loaded = $lock->loadBy(array('application_id' => $application_id));
		if (!$loaded)
		{
			$lock->date_created = date('Y-m-d H:i:s');
			$lock->application_id = $application_id;
			$lock->counter = 0;
		}

		if ($lock->counter >= $rate)
		{
			return 'locked';
		}

		if (!$application->exists())
		{
			$lock->counter++;
			$lock->save();

			if ($lock->counter >= $rate)
			{
				return 'locked';
			}
			else
			{
				return ($lock->counter == $rate - 1) ? 'fail_one_remaining' : 'fail';
			}
		}

		if ($application->getModel()->phone_work != preg_replace('#\D#', '', $phone_work)
			|| $application->getModel()->dob != date('Y-m-d', strtotime($dob)))
		{
			$lock->counter++;
			$lock->save();

			if ($lock->counter >= $rate)
			{
				$olp_agent = $this->factory->getModel('Agent');
				$olp_agent->loadBy(array('login' => 'olp'));
				$olp_agent_id = $olp_agent->agent_id;
				$application->getContactFlags()->set($olp_agent_id, 'login_lock', 'application_id');
			}

			if ($lock->counter >= $rate)
			{
				return 'locked';
			}
			else
			{
				return ($lock->counter == $rate - 1) ? 'fail_one_remaining' : 'fail';
			}
		}
		else
		{
			$lock->counter = 0;
			$lock->save();

			return 'pass';
		}
	}
	
	/**
	 * @param integer $application_id
	 * @return boolean
	 */
	public function isLocked($application_id)
	{
		$application = ECash::getApplicationById($application_id);

		$company_model = $this->factory->getModel('Company');
		$company_model->loadBy(array('company_id' => $application->getCompanyId()));
		
		$business_rules = new ECash_BusinessRules($this->db);
		$settings = $business_rules->Get_Rule_Set_Component_Parm_Values($company_model->name_short, 'login_lock');
		$rate = $settings['max_attempt'];

		$lock = $this->factory->getModel('ApplicationLoginLock');
		$loaded = $lock->loadBy(array('application_id' => $application_id));

		return ($loaded && $lock->counter >= $rate);
	}

	/**
	 * Returns the populated array
	 * 
	 * @param ECash_Models_application $app_model
	 * @return array
	 */
	private function loadReturnArray(ECash_Models_Application $app_model)
	{
		$company_model		= $this->factory->getModel('Company');
		$customer_model		= $this->factory->getModel('Customer');
		$loan_model			= $this->factory->getModel('LoanType');
		$campaign_model		= $this->factory->getModel('CampaignInfo');
		$site_model			= $this->factory->getModel('Site');
		$ach_model			= $this->factory->getModel('Ach');
		$ach_return_model	= $this->factory->getModel('AchReturnCode');

		$last_loan_payoff_date = FALSE;
		$inactive_statuses = array(
			'paid::customer::*root',
			'recovered::external_collections::*root',
		);
		$apps = $this->factory->getModel('ApplicationList');

		foreach ($inactive_statuses as $status)
		{
			$apps->loadBy(array(
				'customer_id' => $app_model->customer_id,
				'application_status' => $status,
			));
			foreach ($apps as $app)
			{
				if ($last_loan_payoff_date === FALSE
					|| $app->date_application_status_set > $last_loan_payoff_date)
				{
					$last_loan_payoff_date = $app->date_application_status_set;
				}
			}
		}

		$company_model->loadBy(array('company_id' => $app_model->company_id));
		$customer_model->loadBy(array('customer_id' => $app_model->customer_id));
		$loan_model->loadBy(array('loan_type_id' => $app_model->loan_type_id));
		$campaign_model->loadBy(array('application_id' => $app_model->application_id));
		$site_model->loadBy(array('site_id' => $campaign_model->site_id));
		$ach_model->loadBy(array('application_id' => $app_model->application_id));
		$ach_return_model->loadBy(array('ach_return_code_id' => $ach_model->ach_return_code_id));

		$references = $this->factory->getModel('PersonalReferenceList');
		$references->loadBy(array('application_id' => $app_model->application_id));

		$lreferences = array();
		
		$i = 0;
		foreach ($references as $reference)
		{
			$i++;
			$lreferences['ref_' . sprintf("%02d",$i) . '_name_full']    = $reference->name_full;
			$lreferences['ref_' . sprintf("%02d",$i) . '_phone_home']   = $reference->phone_home;
			$lreferences['ref_' . sprintf("%02d",$i) . '_relationship'] = $reference->relationship;
		}

		// CAMPAIGN INFO TURKEY
		$cinfolist = $this->factory->getModel('CampaignInfoList');
		$cinfolist->loadBy(array('application_id' => $app_model->application_id));

		$first_promo_sub_code = FALSE;
		$first_promo_id       = FALSE;
		$last_promo_sub_code  = FALSE;
		$last_promo_id        = FALSE;
		$last_ts              = 0;

		foreach ($cinfolist as $cinfo)
		{
			if ($first_promo_sub_code == FALSE)
			{
				$first_promo_sub_code = $cinfo->promo_sub_code;
				$first_promo_id       = $cinfo->promo_id;
			}

			if ($cinfo->date_created >= $last_ts)
			{
				$last_promo_sub_code = $cinfo->promo_sub_code;
				$last_promo_id       = $cinfo->promo_id;
			}
		}

		// [#40315] - added new field
		$military = $app_model->income_source == 'military' ? 'yes' : 'no';

		$retval = array
		(
			'address_city'				=> $app_model->city,
			'address_state'				=> $app_model->state,
			'address_street'			=> $app_model->street,
			'address_unit'				=> $app_model->unit,
			'address_zipcode'			=> $app_model->zip_code,
			'application_id'			=> $app_model->application_id,
			'bank_name'					=> $app_model->bank_name,
			'bank_aba'					=> $app_model->bank_aba,
			'bank_routing'				=> $app_model->bank_aba,
			'bank_account'				=> $app_model->bank_account,
			'bank_account_type'			=> $app_model->bank_account_type,
			'best_call_time'			=> $app_model->call_time_pref,
			'company_name_short'		=> strtoupper($company_model->name_short),
			'customer_id'               => $app_model->customer_id,
			'date_created'				=> $app_model->date_created,
			'date_fund_estimated'		=> ($app_model->date_fund_estimated) ? date('Y-m-d', $app_model->date_fund_estimated) : NULL,
			'date_hire'					=> ($app_model->date_hire)           ? date('Y-m-d', $app_model->date_hire)           : NULL,
			'day_of_month_1'			=> $app_model->day_of_month_1,
			'day_of_month_2'			=> $app_model->day_of_month_2,
			'day_of_week'				=> strtoupper($app_model->day_of_week),
			'dob'						=> ($app_model->dob) ? $app_model->dob : NULL,
			'ecash_process_type'		=> $company_model->ecash_process_type,
			'email'						=> $app_model->email,
			'email_primary'				=> $app_model->email,
			'fund_qualified'			=> ($app_model->fund_actual == NULL) ? $app_model->fund_qualified : $app_model->fund_actual,
			'income_amount'				=> $app_model->income_monthly,
			'income_direct_deposit'		=> strtoupper($app_model->income_direct_deposit),
			'income_frequency'			=> strtoupper($app_model->income_frequency),
			'income_monthly'			=> $app_model->income_monthly,
			'income_type'				=> $app_model->income_source,
			'ip_address'				=> $app_model->ip_address,
			'is_react'					=> $app_model->is_react,
			'last_loan_payoff_date'		=> $last_loan_payoff_date ? date('Y-m-d', $last_loan_payoff_date) : NULL,
			'legal_id_number'			=> $app_model->legal_id_number,
			'legal_id_state'			=> $app_model->legal_id_state,
			'loan_type'					=> $loan_model->name_short,
			'loan_type_id'				=> $app_model->loan_type_id,
			'login'						=> $customer_model->login,
			'name'						=> $ach_return_model->name,
			'name_first'				=> $app_model->name_first,
			'name_last'					=> $app_model->name_last,
			'name_middle'				=> $app_model->name_middle,
			'next_paydate'				=> ($app_model->last_paydate) ? date("Y-m-d", is_numeric($app_model->last_paydate) ? $app_model->last_paydate : strtotime($app_model->last_paydate))  : NULL,
			'olp_process'				=> $app_model->olp_process,
			'originating_address'		=> $site_model->name,
			'originating_source'		=> $site_model->name,
			'password'					=> $customer_model->password,
			'paydate_model'				=> strtoupper($app_model->paydate_model),
			'paydate_model_id'			=> $app_model->paydate_model,
			'pdm_day_of_month_1'		=> $app_model->day_of_month_1,
			'pdm_day_of_month_2'		=> $app_model->day_of_month_2,
			'pdm_day_of_week'			=> $app_model->day_of_week,
			'pdm_next_paydate'			=> ($app_model->last_paydate) ? date("Y-m-d", is_numeric($app_model->last_paydate) ? $app_model->last_paydate : strtotime($app_model->last_paydate))  : NULL,
			'pdm_week_1'				=> $app_model->week_1,
			'pdm_week_2'				=> $app_model->week_2,
			'phone_cell'				=> $app_model->phone_cell,
			'phone_fax'					=> $app_model->phone_fax,
			'phone_home'				=> $app_model->phone_home,
			'phone_work'				=> $app_model->phone_work,
			'phone_work_ext'			=> $app_model->phone_work_ext,
			'promo_id'					=> $first_promo_id,
			'promo_sub_code'			=> $first_promo_sub_code,
			'promo_id_latest'           => $last_promo_id,
			'promo_sub_code_latest'     => $last_promo_sub_code,
			'social_security_number'	=> $app_model->ssn,
			'ssn'						=> $app_model->ssn,
			'status'					=> $app_model->application_status_id,
			'source'					=> 'LDB',
			'transaction_id'			=> $app_model->application_id,
			'transaction_date'			=> $app_model->date_created,
			'track_id'					=> $app_model->track_id,
			'track_key'					=> $app_model->track_id,
			'week_1'					=> $app_model->week_1,
			'week_2'					=> $app_model->week_2,
			'work_date_of_hire'			=> ($app_model->date_hire) ? date('Y-m-d', $app_model->date_hire) : NULL,
			'work_name'					=> $app_model->employer_name,
			'work_shift'				=> $app_model->shift,
			'work_title'				=> $app_model->job_title,
			'military'					=> $military,
		);
		
		return array_merge($retval, $lreferences);
	}
	
	public function isApplicationReactable($application_id) 
	{
		if (!(($company = ECash::getCompany()) instanceof ECash_Company))
		{
			$company = $this->setCompanyByApplicationId($application_id);
		}
		if ($company instanceof ECash_Company)
		{
			$provider = new ECash_HistoryProvider($this->db, array($company->name_short));
			return $provider->getHistoryBy(array('application_id' => $application_id))->getIsReact($company->name_short);
		}
		
	}
	
	private function setCompanyByApplicationId($application_id)
	{
		$app_model = $this->factory->getModel('Application');
		if ($app_model->loadBy(array('application_id' => $application_id)))
		{
			$company = $this->factory->getCompanyById($app_model->company_id);
			ECash::setCompany($company);
			return ECash::getCompany();
		}
		return FALSE;
	}
}

?>
