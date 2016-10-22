<?php

if (!class_exists('Prpc_Server'))
{
	require_once 'prpc/server.php';
}

if (!class_exists('Security_8'))
{
	require_once 'security.8.php';
	define('PASSWORD_ENCRYPTION', 'ENCRYPT');
}

/**
 * Description of ECash_Nirvana_API
 *
 * @copyright Copyright &copy; 2009 The Selling Source, Inc.
 * @author Bryan Campbell <bryan.campbell@dataxltd.com>
 */
class ECash_Nirvana_API extends Prpc_Server
{
	/**
	 * @var Server_Web
	 */
	protected $server;
	
	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	/**
	 * @var ECash_Factory
	 */
	protected $factory;
	
	/**
	 * @var ECash_OLPAPI_API
	 */
	protected $olp_api;
	
	/**
	 * @var ECash_Security
	 */
	protected $security;
	
	protected $user_name;

	/**
	 * @param DB_IConnection_1 $db
	 * @param ECash_Factory $factory
	 */
	public function __construct(Server_Web $server, DB_IConnection_1 $db, ECash_Factory $factory, ECash_Security $security, $user_name)
	{
		$this->server = $server;
		$this->db = $db;
		$this->factory = $factory;
		$this->olp_api = new ECash_OLPAPI_API($this->db, $this->factory);
		$this->security = $security;
		$this->user_name = $user_name;
		
		// Run parent's constructor
		parent::__construct();
	}
	
	/**
	 * Single record fetch.
	 *
	 * @param track_key $track_key
	 * @return array
	 */
	public function Fetch($track_key)
	{
		return $this->Fetch_Multiple(array($track_key));
	}
	
	/**
	 * Takes in a bunch of track keys, returns a bunch of data about them.
	 *
	 * @param array $track_keys
	 * @return array
	 */
	public function Fetch_Multiple($track_keys)
	{
		if (!is_array($track_keys))
		{
			$track_keys = array($track_keys);
		}
		
		// To protect against bad data, only process strings
		$track_keys = array_filter($track_keys, 'is_string');
		
		$track_data = array();
		
		$accessible_companies = array();
		
		$application_company_id = null;
	
		$data = $this->olp_api->getDataByTrackKey($track_keys);
		
		if (is_array($data))
		{
			foreach ($data AS $key => $values)
			{
				$has_access = false;
				$application_id = $data[$key]['application_id'];
				if (is_numeric($application_id))
				{
					$app = ECash::getApplicationById($application_id);
					
					$new_application_company_id = $app->getCompanyId();
					
					if ($application_company_id != $new_application_company_id)
					{
						$application_company_id = $new_application_company_id;
						
						$company_new = new ECash_Company($this->db, $application_company_id);
					
						$this->server->Set_Company($application_company_id);
					}
					
					if (!in_array($application_company_id, $accessible_companies))
					{
						$agent_id  = $this->security->getAgent()->getModel()->agent_id;
	
						$system_id = 4;
			
						$acl = ECash::getACL($this->db);
						$acl->setSystemId($system_id);
						$acl->fetchUserACL($agent_id, $application_company_id);
		
						if ($acl->Acl_Access_Ok($this->user_name, $application_company_id))
						{
							$has_access = true;
							$accessible_companies[] = $application_company_id;
						}
					}
					else
					{
						$has_access = true;
					}
				}
				
				if ($has_access)		
				{
					$data[$key] = $this->mapData($values);
				}
				else
				{
					unset($data[$key]);
				}		
			}
		} 
		else
		{
			return array();
		}
				
		$data = $this->cleanData($data);

		$track_data = array_merge($track_data, $data);
		
		return $track_data;
	}
	
	protected function getCompanyData($current_data)
	{
		$app = ECash::getApplicationById($current_data['application_id']);
		
		$original_company_id = ECash::getCompany()->getCompanyId();
		$application_company_id = $app->getCompanyId();
		
		$company_new = new ECash_Company($this->db, $application_company_id);
		
		if ($original_company_id != $application_company_id)
		{
			$this->server->Set_Company($application_company_id);
		}
		$loan_type_model = Ecash::getFactory()->getModel('LoanType');
		if($loan_type_model->loadBy(array('loan_type_id' => $app->getLoanTypeId())))
		{
			$token_manager = ECash::getFactory()->getTokenManager();
			$db_tokens = $token_manager->getTokensByLoanTypeId($loan_type_model->company_id, $loan_type_id);
			
			$tokens = array();
			foreach($db_tokens as $token_name => $token)
			{
				$tokens[$token_name] = $token->getValue();

			}
			$doc_data = $tokens;
		}
		
		$tokens = $app->getTokenProvider();
		$doc_data = array_merge($doc_data, $tokens->getTokens());
		
		foreach($doc_data as $key=>$val)
		{
			$doc_data[$key] = trim($val);
		}

		$data = array();
		$data['company_name'] = $data['CompanyName'] = (!empty($doc_data['CompanyName'])) ? $doc_data['CompanyName'] : ECash::getConfig()->COMPANY_NAME;
		$data['company_street'] = (!empty($doc_data['CompanyStreet'])) ? $doc_data['CompanyStreet'] : ECash::getConfig()->COMPANY_ADDR_STREET;
		$data['company_city'] = (!empty($doc_data['CompanyCity'])) ? $doc_data['CompanyCity'] : ECash::getConfig()->COMPANY_ADDR_CITY;
		$data['company_state'] = (!empty($doc_data['CompanyState'])) ? $doc_data['CompanyState'] : ECash::getConfig()->COMPANY_ADDR_STATE;
		$data['company_zip'] = (!empty($doc_data['CompanyZip'])) ? $doc_data['CompanyZip'] : ECash::getConfig()->COMPANY_ADDR_ZIP;		
		$data['ent_short_url'] = "";
		$data['ent_site'] = (!empty($doc_data['SourceSiteName'])) ? $doc_data['SourceSiteName'] : ECash::getConfig()->COMPANY_DOMAIN;
		$data['teleweb_phone'] = "";
		$data['company_email'] = strtolower((!empty($doc_data['CompanyEmail'])) ? $doc_data['CompanyEmail'] : ECash::getConfig()->COMPANY_EMAIL);
		$data['company_client_email'] = $data['CompanyClientEmail'] = strtolower((!empty($doc_data['CompanyClientEmail'])) ? $doc_data['CompanyClientEmail'] : ECash::getConfig()->COMPANY_SUPPORT_EMAIL);
		$data['company_coll_email'] = $data['CompanyCollEmail'] = strtolower((!empty($doc_data['CompanyCollEmail'])) ? $doc_data['CompanyCollEmail'] : ECash::getConfig()->COMPANY_EMAIL);
		$data['CompanyCustEmail'] = strtolower($doc_data['CompanyCustEmail']);
		$data['company_phone'] = (!empty($doc_data['CompanyPhone'])) ? $doc_data['CompanyPhone'] : ECash::getConfig()->COMPANY_PHONE_NUMBER;
		$data['company_client_phone'] = $data['CompanyClientPhone'] = (!empty($doc_data['CompanyClientPhone'])) ? $doc_data['CompanyClientPhone'] : ECash::getConfig()->COMPANY_SUPPORT_PHONE;
		$data['company_coll_phone'] = $data['CompanyCollPhone'] = (!empty($doc_data['CompanyCollPhone'])) ? $doc_data['CompanyCollPhone'] : ECash::getConfig()->COMPANY_PHONE_NUMBER;
		$data['company_fax'] = (!empty($doc_data['CompanyFax'])) ? $doc_data['CompanyFax'] : ECash::getConfig()->COMPANY_FAX;
		$data['company_client_fax'] = $data['CompanyClientFax'] = (!empty($doc_data['CompanyClientFax'])) ? $doc_data['CompanyClientFax'] : ECash::getConfig()->COMPANY_SUPPORT_FAX;
		$data['company_coll_fax'] = $data['CompanyCollFax'] = (!empty($doc_data['CompanyCollFax'])) ? $doc_data['CompanyCollFax'] : ECash::getConfig()->COMPANY_FAX;
		
		$data['company_card_phone'] = "";
		$data['ent_url'] = $doc_data['CSLoginLink'];
		
		$data['start_url'] = (!empty($doc_data['SourceSiteName'])) ? $doc_data['SourceSiteName'] : ECash::getConfig()->COMPANY_DOMAIN;
		
		$application_model = Ecash::getFactory()->getModel('Application');
		$application_model->loadBy(array('application_id' => $current_data['application_id']));		
		$data['application_date'] = date("Y-m-d", $application_model->date_created);
		$data['application_datetime'] = date("Y-m-d H:i:s", $application_model->date_created);
		
		$data['today'] = date("Y-m-d");
		$data['usa_today'] = date("m/d/Y");
		
		$data['current_time'] = date("H:i");
		$data['usa_current_time'] = date("g:i a");
		
		$site_name = $doc_data['CompanyWebSite'];
		$encoded_app_id = urlencode(base64_encode($current_data['application_id']));
		
		$data['react_key'] = $encoded_app_id;
		$data['ReactKey'] = $encoded_app_id;
		
		$data['ReactLink'] = $data['react_url'] = sprintf("%s/?force_new_session&page=ent_cs_confirm_start&reckey=%s",
			$site_name,
			$encoded_app_id
		);
		
		$data['bb_option_url'] = sprintf("%s/?page=bb_option_email&bb_option=%s",
			$site_name,
			$encoded_app_id
		);
		
		if (empty($doc_data['TimeCSMFOpen'])) $data['TimeCSSatClose'] = "Closed";
		if (empty($doc_data['TimeCSMFClose'])) $data['TimeCSMFClose'] = "Closed";
		if (empty($doc_data['TimeCSSatOpen'])) $data['TimeCSSatClose'] = "Closed";
		if (empty($doc_data['TimeCSSatClose'])) $data['TimeCSSatClose'] = "Closed";
		if (empty($doc_data['TimeCSSunOpen'])) $data['TimeCSSunOpen'] = "Closed";
		if (empty($doc_data['TimeCSSunClose'])) $data['TimeCSSunClose'] = "Closed";	
		
		
		$data = array_merge($doc_data, $data);
		
		return $data; 
	}
	
	protected function cleanData(array $ecash_data)
	{
		$track_data = array();
		
		foreach ($ecash_data AS $track_key => $data)
		{
			$data['income_direct_deposit'] = strcasecmp($data['income_direct_deposit'], 'yes') ? FALSE : TRUE;
			$data['best_call_time'] = strtoupper($data['best_call_time']);
			$data['CustomerBestCallTime'] = $data['best_call_time'];
			$data['income_frequency'] = strtoupper($data['income_frequency']);
			$data['income_type'] = strtoupper($data['income_type']);
			$data['paydate_model_id'] = strtoupper($data['paydate_model_id']);
			$data['PaydateModelID'] = $data['paydate_model_id'];
			$data['pdm_day_of_week'] = $this->convertDayOfWeek($data['pdm_day_of_week']);
			$data['PDMDayOfWeek'] = $data['pdm_day_of_week'];
			$data['PDMDayOfMonth1'] = $data['pdm_day_of_month_1'];
			$data['PDMDayOfMonth2'] = $data['pdm_day_of_month_2'];
			$data['PDMDayOfWeek'] = $data['pdm_day_of_week'];
			$data['PDMWeek1'] = $data['pdm_week_1'];
			$data['PDMWeek2'] = $data['pdm_week_2'];
			$data['TrackKey'] = $data['track_key'];
			$data['TransactionDate'] = $data['transaction_date'];
			$data['military'] = 'no';
			
			$data['transaction_id_encoded'] = base64_encode($data['transaction_id']);
			
			// Nirvana always wants the latest promo_id/promo_sub_code
			if (!empty($data['promo_id_latest'])) $data['promo_id'] = $data['promo_id_latest'];
			if (!empty($data['promo_sub_code_latest'])) $data['promo_sub_code'] = $data['promo_sub_code_latest'];
			
			$data['password'] = $this->decryptECashPassword($data['password']);
			
			$data = array_merge($data, $this->getCompanyData($data));
			
			//Get rid of is_react
			unset($data['is_react']);
			
			// If reference data is not set, set it
			for ($x = 1; $x < 3; $x++)
			{
				if (!isset($data["ref_0{$x}_name_full"])) $data["ref_0{$x}_name_full"] = '';
				if (!isset($data["ref_0{$x}_phone_home"])) $data["ref_0{$x}_phone_home"] = '';
				if (!isset($data["ref_0{$x}_relationship"])) $data["ref_0{$x}_relationship"] = '';
			}
			
			$data['source'] = 'ECASH';
			$track_data[$track_key] = $data;
		}
		
		return $track_data;
	}
	
	/**
	 * Decrypts an eCash password.
	 *
	 * @param string $password
	 * @return string
	 */
	protected function decryptECashPassword($password)
	{
		$password = Security_8::Decrypt_Password($password);
		$password = is_string($password) ? $password : '';
		
		return $password;
	}
	
	/**
	 * Maps common data returned from eCash to common values for OLP.
	 *
	 * @param array $data
	 * @return array
	 */
	protected function mapData(array $data)
	{
		// Data that comes back from eCash isn't in the format we will use elsewhere.
		$data_map = array(
			'address_street' => 'home_street',
			'address_unit' => 'home_unit',
			'address_zipcode' => 'home_zip',
			'address_city' => 'home_city',
			'address_state' => 'home_state',
			'best_call_time' => 'best_call',
			'date_hire' => 'employer_length',
			'legal_id_number' => 'state_id_number',
			'legal_id_state' => 'legal_state',
			'income_monthly' => 'income_monthly_net',
			'income_type' => 'income_source',
			'work_name' => 'employer_name',
			'name' => 'ReturnReason',
			'company_name_short' => array('property_short', 'name_short'), // For CS, need as name_short
			'pdm_next_paydate' => 'last_paydate', // For CS, we need a valid paydate
			
			// Splits DOB into Year, Month, and Day, and copies to date_of_birth
			'dob' => array(
				'/^(?<date_dob_y>\d+)-(?<date_dob_m>\d+)-(?<date_dob_d>\d+)$/',
				'date_of_birth',
			),
			
			// Splits ssn into each of the different parts
			'ssn' => '/^(?<ssn_part_1>\d{3})(?<ssn_part_2>\d{2})(?<ssn_part_3>\d{4})$/',
			
			// Build the esignature from first and last name
			'@%%%name_first%%% %%%name_last%%%' => 'esignature',
		);
		
		$data = array_merge($data, self::dataMap($data, $data_map));
		
		return $data;
	}
	
	/**
	 * Allows fancy data mapping of arrays. Does NOT merge in result with source data.
	 *
	 * @param array $source_data
	 * @param array $data_map
	 * @param bool $process_recursively
	 * @param bool $throw_exceptions
	 * @return array
	 */
	protected static function dataMap(array $source_data, array $data_map, $process_recursively = FALSE, $throw_exceptions = TRUE)
	{
		$data = array();
		$data_previous = NULL;
		
		$steps = 0;
		$maximum_steps = 10;
		do
		{
			// Store our current data array to our previous value for recursion
			$data_previous = $data;
			
			foreach ($data_map AS $key => $actions)
			{
				// If key starts with a '@', assume this is a value built from others
				if ($key[0] == '@')
				{
					$key = substr($key, 1);
					
					$hits = preg_match_all('/(%%%([^%]+)%%%)/', $key, $matches);
					$replace_search = array();
					$replace_data = array();
					$valid = FALSE;
					for ($i = 0; $i < $hits; $i++)
					{
						$replace_search[] = $matches[1][$i];
						$replace_data[] = isset($source_data[$matches[2][$i]]) ? $source_data[$matches[2][$i]] : NULL;
						if (isset($source_data[$matches[2][$i]])) $valid = TRUE;
					}
					
					if ($valid)
					{
						$key = str_replace($replace_search, $replace_data, $key);
						
						$data = array_merge($data, self::dataMapProcessKey($key, $actions));
					}
				}
				// If key starts with a '!', assume it is an executable action
				elseif ($key[0] == '!')
				{
					if (preg_match('/^\!(\w+)\((.*)\)$/', $key, $matches))
					{
						switch ($matches[1])
						{
						}
					}
				}
				elseif (isset($source_data[$key]))
				{
					$data = array_merge($data, self::dataMapProcessKey($source_data[$key], $actions));
				}
			}
			
			// Tail recursion optimization
			$source_data = $data;
			
			// Increment our fail-safe
			$steps++;
			
			// As a protection against flip-flopping data maps, do not
			// process an array too many times.
			if ($steps == $maximum_steps)
			{
				if ($throw_exceptions)
				{
					throw new Exception("dataMap() processed a data mapping {$steps} times without reaching a stable result.");
				}
				break;
			}
		}
		while ($process_recursively && count(array_diff_assoc($data, $data_previous)));
		
		return $data;
	}
	
	/**
	 * Handles actions on one source value.
	 *
	 * @param string $source_data
	 * @param mixed $actions
	 * @return array
	 */
	protected static function dataMapProcessKey($source_data, $actions)
	{
		$data = array();
		
		// If an array, this key can map to multiple other actions
		if (is_array($actions))
		{
			foreach ($actions AS $action)
			{
				$data = array_merge($data, self::dataMapProcessKey($source_data, $action));
			}
		}
		// If an action begins with a '/' assume it is a regular expression with named subpatterns
		elseif ($actions[0] == '/')
		{
			if (preg_match($actions, $source_data, $matches))
			{
				foreach ($matches AS $match_name => $match_value)
				{
					if (!is_int($match_name))
					{
						$data[$match_name] = $match_value;
					}
				}
			}
		}
		else
		{
			$data[$actions] = $source_data;
		}
		
		return $data;
	}
	
/**
	 * Converts a day-of-week string to an integer.
	 *
	 * @param string $day_of_week
	 * @return int
	 */
	protected function convertDayOfWeek($day_of_week)
	{
		switch (strtoupper($day_of_week))
		{
			case 'MON':
				$dow = 1;
				break;
			
			case 'TUE':
				$dow = 2;
				break;
			
			case 'WED':
				$dow = 3;
				break;
			
			case 'THU':
				$dow = 4;
				break;
			
			case 'FRI':
				$dow = 5;
				break;
			
			default:
				$dow = NULL;
				break;
		}
		
		return $dow;
	}
}
