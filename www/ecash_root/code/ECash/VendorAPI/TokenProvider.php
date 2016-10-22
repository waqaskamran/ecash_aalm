<?php
/**
 * Provides tokens to the vendor api based
 * on a ridiculous pile of data
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class ECash_VendorAPI_TokenProvider implements VendorAPI_ITokenProvider
{
	/**
	 * The ecash application object
	 *
	 * @var ECash_Application
	 */
	protected $ecash_application;

	/**
	 * Driver
	 *
	 * @var VendorAPI_IDriver
	 */
	protected $driver;

	/**
	 * Constructs...
	 *
	 * @param VendorAPI_IDriver $driver
	 */
	public function __construct(VendorAPI_IDriver $driver)
	{
		$this->driver = $driver;
	}

	/**
	 * Returns an array containing all
	 * of the tokens related to an application
	 *
	 * @param unknown_type $application_id
	 * @param VendorAPI_StateObject $state
 	 * @param bool $is_preview Indicates that documents are being previewed
	 * @return array
	 */
	public function getTokens(VendorAPI_IApplication $application, $is_preview, $loan_amount = NULL)
	{
		$app = new stdClass;

		$cols = $application->getModelColumns();
		foreach ($cols as $col)
		{
			$app->$col = $application->$col;
		}

		// recalculate finance info
		$qualify_info = $application->calculateQualifyInfo(!$is_preview, $loan_amount);
		$this->updateQualifyInfo($app, $qualify_info);

		$references = $application->getPersonalReferences();
		if (is_array($references))
		{
			if (!is_array($app->references))
			{
				$app->references = array();
			}
			foreach ($references as $ref)
			{
				$app->references[] = (object)$ref;
			}
		}

		$app->loan_type_short = $application->getLoanTypeNameShort($application->loan_type_id);

		$dir = getEnv('ECASH_COMMON_DIR');
		require_once($dir.DIRECTORY_SEPARATOR.'Condor'.DIRECTORY_SEPARATOR.'Condor_Commercial.php');
		require_once($dir.DIRECTORY_SEPARATOR.'ecash_api'.DIRECTORY_SEPARATOR.'interest_calculator.class.php');
		require_once($dir.DIRECTORY_SEPARATOR.'ecash_api'.DIRECTORY_SEPARATOR.'ecash_api.2.php');
		$dir = realpath(getEnv('ECASH_CODE_DIR').'/../');
		require_once($dir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'business_rules.class.php');
		require_once($dir.DIRECTORY_SEPARATOR.'sql/lib/scheduling.func.php');
		$app->client_ip_address = $app->ip_address;
		$app->zip = $app->zip_code;

		if (empty($app->fund_action_date) && !empty($app->date_fund_estimated))
		{
			$app->fund_action_date = $app->date_fund_estimated;
		}

		// some columns are returned as strings from the state object,
		// but unix timestamps from the models... normalize them!
		if (is_numeric($app->fund_action_date)) $app->fund_action_date = date('Y-m-d', $app->fund_action_date);
		if (is_numeric($app->date_fund_estimated)) $app->date_fund_estimated = date('Y-m-d', $app->date_fund_estimated);
		if (is_numeric($app->date_first_payment)) $app->date_first_payment = date('Y-m-d', $app->date_first_payment);
		if (is_numeric($app->last_paydate)) $app->last_paydate = date('Y-m-d', $app->last_paydate);
		if (is_numeric($app->dob)) $app->dob = date('Y-m-d', $app->dob);

		$app->date_fund_actual_ymd = $app->date_fund_estimated;
		$app->fund_due_date = $app->fund_action_date;
		$app->estimated_service_charge = $app->finance_charge;
		$app->customer_email = $app->email;
		$app->date_app_created = date('Y-m-d');
		$app->time_app_created = date('H:i:s');
		
		// tenancy type is an enum with 'unspecified' as the default;
		// if this is coming from an unsaved model, it won't have a value
		if (empty($app->tenancy_type))
		{
			$app->tenancy_type = 'unspecified';
		}

		$condor_tokens = new Condor_Commercial(
			$this->driver->getDatabase(),
			$this->getConfig6(),
			$this->driver->getCompanyID(),
			$app->space_key,
			$app
		);

		$condor_tokens->Set_Business_Rules();
		$app->business_rules = $condor_tokens->Get_Business_Rules()->Get_Rule_Set_Tree($app->rule_set_id);

		if (strcasecmp($app->business_rules['loan_type_model'], 'title') == 0)
		{
			$app->vehicle_vin = $application->vehicle_vin;
			$app->vehicle_year = $application->vehicle_year;
			$app->vehicle_model = $application->vehicle_model;
			$app->vehicle_make = $application->vehicle_make;
			$app->vehicle_mileage = $application->vehicle_mileage;
			$app->vehicle_series = $application->vehicle_series;
		}

		$condor_tokens->Set_Holiday_List();
		$condor_tokens->Set_Pay_Date_Calc();
		$condor_tokens->Calculate_Application_Pay_Dates($app);
		$condor_tokens->Set_Campaign_Info($application->getCampaignConfigInfo());
		$condor_tokens->Set_Site_Config();
		$condor_tokens->Get_Company_Data($app);
		$condor_tokens->feeTokensWithNoApplication($app);
		$tokens = $condor_tokens->Map_Condor_Data($app, FALSE);

		$tokens->CompanyNameLegal = $condor_tokens->Get_Site_Config()->legal_entity;
		$tokens->CompanyWebSite = $condor_tokens->Get_Site_Config()->site_name;

		// currently, these tokens are only populated when we're generating the actual
		// document -- this makes the document appear to be unsigned in the preview
		if (!$is_preview)
		{
			$tokens->CustomerESig = $tokens->CustomerNameFull;
			$tokens->Checkbox1 = 'X';
			$tokens->Checkbox2 = 'X';
			$tokens->Checkbox3 = 'X';
			$tokens->Checkbox4 = 'X';
		}
		if (strcasecmp($app->business_rules['loan_type_model'], 'title') == 0)
		{
			$info = $application->calculateQualifyInfo(FALSE, $tokens->LoanFundAmount);
			$apr = $info->getAPR();
			$tokens->LoanAPR = $tokens->LoanNextAPR = $tokens->LoanCurrAPR = number_format($apr, 2, '.', '') . '%';
		}

		return (array)$tokens;
	}

	protected function updateQualifyInfo($data, VendorAPI_QualifyInfo $info)
	{
		$data->fund_amount = $info->getLoanAmount();
		$data->apr = $info->getAPR();
		$data->finance_charge = $info->getFinanceCharge();
		$data->payment_total = $info->getTotalPayment();
		$data->date_fund_estimated = date('Y-m-d', $info->getFundDateEstimate());
		$data->date_first_payment = date('Y-m-d', $info->getFirstPaymentDate());

		$date = getdate($info->getFundDateEstimate());
		$data->date_fund_estimated_year = $date['year'];
		$data->date_fund_estimated_month = $date['mon'];
		$data->date_fund_estimated_day = $date['mday'];
	}

	/**
	 * Load an instance of config 6
	 *'
	 * @return unknown
	 */
	protected function getConfig6()
	{
		require_once 'config.6.php';
		require_once 'mysql.4.php';

		$stat_host = ECash::getConfig()->STAT_MYSQL_HOST;
		$stat_user = ECash::getConfig()->STAT_MYSQL_USER;
		$stat_pass = ECash::getConfig()->STAT_MYSQL_PASS;

		$scdb = new MySQL_4($stat_host, $stat_user, $stat_pass);
		$scdb->Connect();

		// The following is a quirk in how Config_6 is using MySQL_4
		$scdb->db_info['db'] = 'management';

		$scdb->Select('management');
		$config_6 = new Config_6($scdb);
		return $config_6;
	}

}
