#!/usr/bin/php
<?php
	
	//for strict use of strtotime()
	date_default_timezone_set('America/Los_Angeles');
	require_once('mysqli.1.php');
	require_once('mysql.4.php');
	require_once('statProClient.php');
	require_once('enterpriseProClient.php');
	require_once('config.6.php');
	require_once('setstat.3.php');
	require_once('../www/config.php');
	
	// change the working directory to our own
	chdir(dirname(__FILE__));
	
	$options = getopt("hxps:e:dc:");
	$mode = isset($options['x']) ? 'live' : 'test';
	$pretend = !isset($options['p']);
	
	$start_date = isset($options['s']) ? strtotime($options['s']) : strtotime('yesterday 00:00:00');
	$end_date = isset($options['e']) ? strtotime($options['e'], $start_date) : strtotime('+1 day', $start_date);
	
	/**
	 * Added a new requirement for the 'c' parameter for the company.  This is used
	 * to determine which company to run this under to grab the appropriate statpro
	 * user and password.
	 */
	if(! isset($options['c']) || isset($options['h']))
	{
		echo "usage: " . $argv[0] . "[OPTION]... [-c COMPANY]\n\n";
		echo "  -c Company Name (Example: usfc) (Required)\n";
		echo "  -x Use Live\n";
		echo "  -p Disable Pretend (Use this flag to actually execute)\n";
		echo "  -s Start Date\n";
		echo "  -e End Date\n";
		echo "  -d Debug\n";
		echo "  -h Help (this page)\n\n";
		die();
	}
	else
	{
		$company = $options['c'];
		
		$enterprise_prefix = ECash::getConfig()->ENTERPRISE_PREFIX;
		$base_config = CUSTOMER . '_Config_' . EXECUTION_MODE;

		echo "CUST_DIR = " . CUST_DIR . "\n";

		$config_filename = CUST_DIR . "/code/" . $enterprise_prefix . '/Config/' . $company . '.php';
		
		$enterprise = strtolower($enterprise_prefix);		//public function __construct($ecash_db, $mgmt_db, $statpro_cust, $statpro_pass, $statpro_mode, $pretend)

		
		try
		{
			require_once($config_filename);
			$class_config = $company . '_CompanyConfig';
			ECash::setConfig(new $class_config(new $base_config()));
		}
		catch(Exception $e)
		{
			throw new Exception("Invalid company configuration class or company config file does not exist: $config_filename");
		}
	}
	
	echo "Enterprise: $enterprise, Company: $company\n";
	echo 'Stats Mode: ', $mode, " (add the -x flag for live, remove for test)\n";
	echo 'Exec Mode: ', ($pretend ? 'pretend' : 'EXECUTE'), " (add the -p flag to actually execute, remove to pretend)\n";
	
	// be intelligent about arguments like '2006-05-01' '2006-05-01'
	if (($start_date === $end_date) && ((int)date('His', $start_date) === 0))
	{
		$end_date = strtotime('+1 day', $end_date);
	}
	
	/**
	 * eCash Database
	 */
	$db_host = ECash::getConfig()->DB_HOST;
	$db_user = ECash::getConfig()->DB_USER;
	$db_pass = ECash::getConfig()->DB_PASS;
	$db_name = ECash::getConfig()->DB_NAME;
	$db_port = ECash::getConfig()->DB_PORT;
	$ecash_db = new MySQLi_1($db_host, $db_user, $db_pass, $db_name, $db_port);
	
	/**
	 * Site Management Database
	 */
	$mgmt_db_host = ECash::getConfig()->STAT_MYSQL_HOST;
	$mgmt_db_user = ECash::getConfig()->STAT_MYSQL_USER;
	$mgmt_db_pass = ECash::getConfig()->STAT_MYSQL_PASS;
	
	$mgmt_db = new MySQL_4($mgmt_db_host, $mgmt_db_user, $mgmt_db_pass);
	$mgmt_db->Connect();
	$mgmt_db->Select('management');
	
	// 3rd and 4th arguments can come from lib/setstat.3.php
	// 3rd argument 'clk' = Customer Name
	// 4th argument is statpro pass
	$report = new StatAuditReport($ecash_db, $mgmt_db, $enterprise, $mode, $pretend);
	$report->setDebug(isset($options['d']));
	
	echo 'Preparing report for ', date('n/j/Y H:i:s', $start_date), ' - ', date('n/j/Y H:i:s', $end_date), "...\n";
	$start = microtime(TRUE);
	
	$missing = $report->Prepare($start_date, $end_date);
	
	echo 'Finished in ', round(microtime(TRUE) - $start, 4), " seconds\n";
	
	if (count($missing))
	{
		echo "\n\nThe following events appear to be missing:\n\n";
		
		foreach ($missing as $company=>$events)
		{
			
			// company header
			echo str_repeat('=', 72), "\n", strtoupper($company), "\n", str_repeat('=', 72), "\n\n";
			
			// header
			echo '+', str_repeat('-', 32), '+', str_repeat('-', 10), "+\n";
			echo '| ', str_pad('Event', 30), ' | ', str_pad('Count', 8), " |\n";
			echo '+', str_repeat('-', 32), '+', str_repeat('-', 10), "+\n";
			
			foreach ($events as $event=>$count)
			{
				echo '| ', str_pad($event, 30), ' | ', str_pad($count, 8, ' ', STR_PAD_LEFT), ' |', "\n";
			}
			
			// footer
			echo '+', str_repeat('-', 32), '+', str_repeat('-', 10), "+\n\n";
			
		}
		
	}
	else
	{
		echo "\n\nNo events were found missing.\n";
	}
	
	
	class StatAuditReport
	{
		
		// how many records to process at once
		const BATCH_SIZE = 100;
		
		// due to some time drift on the servers, I was forced to increase this...
		// perhaps all inserts should use the system time for consistency!
		const EVENT_THRESHOLD = 1800;
		//const EVENT_THRESHOLD = 60;
		
		protected $missing;
		
		// maps eCash statii to their IDs and back
		protected $status_map;
		protected $status_id_map;
		
		// maps eCash statii to their IDs and back
		protected $company_map;
		protected $company_id_map;
		
		// maps statpro event keys to their event IDs and back
		protected $event_map;
		protected $event_id_map;
		
		//statpro
		private $sp;
		private $ep;
		private $customer_name;
		private $customer_pass;
		private $customer_key;
		private $config;
		
		//dbs
		private $ecash_db;
		private $mgmt_db;

		private $pretend = FALSE;
		private $debug = FALSE;
		
		// maps eCash statii to statpro event keys
		protected $status_event_map = array(
			'active::servicing::customer::*root' => 'funded',
			'withdrawn::applicant::*root' => 'withdrawn',
			'denied::applicant::*root' => 'deny',
		);
		
		// maps eCash statii to statpro event keys
		protected $status_event_map_3x = array(
			// the following removed for sanity's sake: OLP does NOT hit the stats for these statii
			//'queued::underwriting::applicant::*root' => 'underwriting_queued',
			//'queued::verification::applicant::*root' => 'verification_queued',
			'denied::applicant::*root' => 'deny',
			'withdrawn::applicant::*root' => 'withdraw',
			'paid::customer::*root' => 'inactive_paid',
			'pending::external_collections::*root' => 'second_tier_pending',
			'sent::external_collections::*root' => 'second_tier_sent',
			'dequeued::underwriting::applicant::*root' => 'underwriting_dequeued',
			'follow_up::underwriting::applicant::*root' => 'underwriting_followup',
			'dequeued::verification::applicant::*root' => 'verification_dequeued',
			'follow_up::verification::applicant::*root' => 'verification_followup',
			'new::collections::customer::*root' => 'collections_new',
			'approved::servicing::customer::*root' => 'funded',
			'funding_failed::servicing::customer::*root' => 'funding_failed',
			'past_due::servicing::customer::*root' => 'past_due',
			'arrangements_failed::arrangements::collections::customer::*root' => 'arrangements_failed',
			'current::arrangements::collections::customer::*root' => 'made_arrangements',
			'unverified::bankruptcy::collections::customer::*root' => 'bankruptcy_notified',
			'verified::bankruptcy::collections::customer::*root' => 'bankruptcy_verified',
			'dequeued::contact::collections::customer::*root' => 'collections_contact_dequeued',
			'queued::contact::collections::customer::*root' => 	'collections_contact_queued',
			'follow_up::contact::collections::customer::*root' => 'collections_contact_followup',
			'ready::quickcheck::collections::customer::*root' => 'qc_ready',
			'cccs::collections::customer::*root' => 'cccs',
			'sent::quickcheck::collections::customer::*root' => 'qc_sent',
			'refi::servicing::customer::*root' => 'refi'
		);
		
		// special case for the pulled event
		
		protected $pull_event = 'pulled_prospect';
		
		protected $pull_status = array(
			'dequeued::underwriting::applicant::*root',
			'dequeued::verification::applicant::*root'
		);
		
		public function __construct($ecash_db, $mgmt_db, $enterprise, $statpro_mode, $pretend)
		{
			
			$this->ecash_db = $ecash_db;
			$this->mgmt_db = $mgmt_db;
			
			$this->config = new Config_6($this->mgmt_db);

			list($statpro_cust, $statpro_pass) = $this->getStatProCredentials();
			
			$this->customer_name = $statpro_cust;
			$this->customer_pass = $statpro_pass;
			
			$this->pretend = $pretend;
			
			$this->event_map    = array();
			$this->event_id_map = array();
			
			// statpro database
			$db = strtolower($statpro_cust).'_sp2_data';
			
			switch (strtolower($statpro_mode))
			{
				case 'live':
					/** You'll need access to the StatPro 2 DB **/
					$this->statpro_db = new MySQLi_1('writers.statpro2.ept.tss', '', '', $db, 3306);
					$this->customer_key = 'spc_'.$this->customer_name.'_live';
					$this->sp = new statProClient($this->customer_key);
					$this->ep = new enterpriseProClient ($this->customer_key);
					break;
				
				case 'test':
					/** Unknown RC database credentials -- I tested with the Live DB [BR] **/
					$this->statpro_db = new MySQLi_1('db101.ept.tss', 'epointps', 'pwsb1tch', $db, 3309);
					$this->customer_key = 'spc_'.$this->customer_name.'_test';
					$this->sp = new statProClient($this->customer_key);
					$this->ep = new enterpriseProClient ($this->customer_key);
					break;
			}
			
			return;
			
		}
		
		public function setDebug($debug)
		{
			$this->debug = (bool)$debug;
		}
		
		public function setPretend($pretend)
		{
			$this->pretend = (bool)$pretend;
		}
		
		public function Prepare($start_date, $end_date, $companies = NULL)
		{
			// populate our mappings
			if (!$this->status_map) $this->getStatusMap();
			if (!$this->company_map) $this->getCompanyMap();
			
			if (!is_numeric($start_date)) $start_date = strtotime($start_date);
			if (!is_numeric($end_date)) $end_date = strtotime($end_date);
			
			$result = $this->queryApplications($start_date, $end_date, $companies);
			$num_apps = $result->Row_Count();
			
			echo "Found $num_apps to processes.\n";
			
			$this->missing = array();
			
			$c = 0;
			$total = 0;
			
			while ($rec = $result->Fetch_Array_Row(MYSQLI_ASSOC))
			{
				
				// reset batch variables
				if ($c === 0)
				{
					$batch = array();
				}
				
				// convert to a timestamp
				$rec['date_created'] = strtotime($rec['date_created']);
				
				// save in our batch
				$batch[] = $rec;
				
				if (++$c > self::BATCH_SIZE)
				{
					
					// process the batch of records
					$this->processBatch($batch);
					
					// reset batch
					$c = 0;
					
				}
				
				++$total;
				
			}
			
			echo "Processed ", number_format($total), " records\n";
			
			return $this->missing;
			
		}
		
		protected function queryApplications($start_date, $end_date, $companies = NULL)
		{
			
			if (($companies !== NULL) && !is_array($companies))
			{
				$companies = array($companies);
			}
			
			$query = "
				SELECT
					status_history.company_id,
					status_history.date_created,
					status_history.application_id,
					status_history.application_status_id,
					track_id AS track_key,
					site.name AS url,
					site.license_key,
					promo_id,
					promo_sub_code
				FROM
					status_history JOIN
					application ON (application.application_id = status_history.application_id) JOIN
					campaign_info ON (
						campaign_info.application_id = status_history.application_id AND
						campaign_info.campaign_info_id = (
							SELECT
								MAX(campaign_info_id)
							FROM
								campaign_info AS ci
							WHERE
								ci.application_id = status_history.application_id AND
								ci.date_created <= status_history.date_created
						)
					) JOIN
					site ON (site.site_id = application.enterprise_site_id)
				WHERE
					status_history.date_created >= '".date('Y-m-d H:i:s', $start_date)."' AND
					status_history.date_created < '".date('Y-m-d H:i:s', $end_date)."' AND
					-- filter out duplicates
					NOT EXISTS (
						SELECT
							status_history_id
						FROM
							status_history AS dupe
						WHERE
							dupe.application_id = status_history.application_id AND
							dupe.application_status_id = status_history.application_status_id AND
							dupe.date_created < status_history.date_created
						LIMIT 1
					)
					ORDER BY application_id
			";
			if ($companies !== NULL)
			{
				$query .= "
					AND status_history.company_id IN (".implode(', ', $companies).")
				";
			}
			
			$result = $this->ecash_db->Query($query);
			return $result;
			
		}
		
		protected function processBatch(Array $batch)
		{
			foreach ($batch as $rec)
			{
				echo "Processing {$rec['application_id']} ... \n";
				if (($rec['track_id'] = $this->getTrackID($rec['track_key'], $rec['date_created'])) === FALSE || empty($rec['track_id']))
				{
					echo "*** Invalid or missing track key: {$rec['track_key']} or track_id: {$rec['track_id']}\n";
					continue;
				}
					
				try
				{
					
					// get the space key for this info
					if(empty($rec['license_key']))
					{
						var_dump($rec);
						continue;
					}
					else
					{
						$space = $this->generateSpaceKey($rec['license_key'], $rec['promo_id'], $rec['promo_sub_code']);
					}
					
					// map the status ID to a status string
					$rec['status'] = $this->doMap($this->status_id_map, $rec['application_status_id']);

					// get the events we're looking for
					$events = $this->getEventsToCheck($rec);
					
					// convert to IDs
					$event_map = $this->getEventMap($rec['date_created']);
					$event_ids = $this->doMap($event_map, $events);
					if (count($event_ids))
					{
						// see which events we've hit
						$missing = $this->checkTrack($rec['track_id'], $event_ids, $rec['date_created'], TRUE, self::EVENT_THRESHOLD, $space->space_key);
						
						// add to our results
						$this->addMissing($space, $rec, $missing);
					}
				}
				catch (Exception $e)
				{
					// gracefully continue
					echo "*** ", $e->getMessage(), "\n";
				}
			}
			
			return;
			
		}
		
		protected function getEventsToCheck($rec)
		{
			
			// get the events we should hit for this status
			if (in_array($rec['status'], $this->pull_status))
			{
				// if they've been pulled before, we don't look for anything -- otherwise, we want a pull event
				$events = (!$this->hasPreviousPull($rec['application_id'], $rec['date_created'])) ? array($this->pull_event) : array();
			}
			else
			{
				// use the eCash 3.x mapping
				$events = $this->doMap($this->status_event_map_3x, $rec['status']);
			}
			
			return $events;
			
		}
		
		protected function addMissing($space, $rec, $event_ids)
		{
			
			// get our company name
			$company = $this->doMap($this->company_id_map, $rec['company_id']);
			
			// translate the missing event IDs to event names
			$event_id_map = $this->getEventIdMap($rec['date_created']);
			$events = $this->doMap($event_id_map, $event_ids);
			
			foreach ($events as $event)
			{
				
				// create space for this company
				if (!isset($this->missing[$company])) $this->missing[$company] = array();
				
				// add the missing events
				if (!isset($this->missing[$company][$event])) $this->missing[$company][$event] = 1;
				else $this->missing[$company][$event]++;
				
				// preliminary stat hitting
				if($this->pretend && $this->debug)
				{
					echo $rec['application_id'], ': ', $rec['track_key'], ', ', $space->space_key, ', ', $space->page_id, ' (', $rec['url'], '), ', $space->promo_id,
						', ', $space->promo_sub_code, ', ', $event, ', ', date('Y-m-d H:i:s', $rec['date_created']), "\n";
				}
				elseif (!$this->pretend)
				{
					$this->sp->recordEvent($this->customer_name, $this->customer_pass, $rec['track_key'],
						$space->space_key, $event, $rec['date_created']);
				}
			}
			
			return;
			
		}
		
		protected function generateSpaceKey($license_key, $promo_id, $sub_code)
		{
			$config = @$this->config->Get_Site_Config($license_key, $promo_id, $sub_code);
			
			if (!isset($config->page_id))
			{
				throw new Exception('Invalid config for: '.$license_key.' '.$promo_id.'/'.$sub_code);
			}
			
			$space_def = array(
				'page_id' => $config->page_id,
				'promo_id' => $promo_id,
				'promo_sub_code' => $sub_code,
			);
			$space_key = $this->ep->getSpaceKey($this->customer_key, $this->customer_pass, $space_def);
			
			// send back lots of info
			$space = new stdClass();
			$space->space_key = $space_key;
			$space->license_key = $license_key;
			$space->page_id = $config->page_id;
			$space->promo_id = $config->promo_id;
			$space->promo_sub_code = $config->promo_sub_code;
			
			return $space;
			
		}
	
		protected function getTrackID($track_key, $timestamp = NULL)
		{
			$date_suffix = $this->getDateSuffix($timestamp);
			
			$query = "
				SELECT
					track_id
				FROM
					track{$date_suffix}
				WHERE
					track_key = '".$this->statpro_db->Escape_String($track_key)."'
			";
			$result = $this->statpro_db->Query($query);
			
			if ($rec = $result->Fetch_Array_Row())
			{
				return $rec['track_id'];
			}
			
			return FALSE;
			
		}
		
		protected function hasPreviousPull($app_id, $date)
		{
			// get the status IDs for the pulled statuses
			$status_ids = implode(', ', $this->doMap($this->status_map, $this->pull_status));
			
			$query = "
				SELECT
					status_history_id
				FROM
					status_history
				WHERE
					application_id = {$app_id}
					AND date_created < '".date('Y-m-d H:i:s', $date)."'
					AND application_status_id IN ({$status_ids})
				LIMIT 1
			";
			$result = $this->ecash_db->Query($query);
			
			return ($result->Row_Count() > 0);
			
		}
		
		protected function checkTrack($track_id, $event_ids, $timestamp = NULL, $inverse = FALSE, $fudge = 300, $space_key = NULL)
		{
			
			if ($timestamp !== NULL && !is_numeric($timestamp))
			{
				$timestamp = strtotime($timestamp);
			}
			
			$date_suffix = $this->getDateSuffix($timestamp);
			
			if (!is_array($event_ids)) $event_ids = array($event_ids);
			
			$query = "
				SELECT
					event_log_id,
					event_type_id,
					space_key,
					date_occurred
				FROM
					event_log{$date_suffix}
					JOIN space{$date_suffix} USING (space_id)
				WHERE
					track_id = {$track_id}
					AND event_type_id IN (".implode(', ', $event_ids).")
			";
			if (($timestamp !== NULL) && ($fudge !== FALSE))
			{
				$query .= "
					AND date_occurred > ".($timestamp - $fudge)."
					AND date_occurred < ".($timestamp + $fudge)."
				";
			}
			
			$result = $this->statpro_db->Query($query);
			
			// holds our results
			$events = $inverse ? $event_ids : array();
			
			while ($rec = $result->Fetch_Array_Row())
			{
				if (($key = array_search($rec['event_type_id'], $event_ids)) !== FALSE)
				{
					// useful if you accidentally hit on the wrong space key... but I'd never do that.
					//if ($this->getSpaceDefinition($rec['space_key']) === FALSE)
					//{
					//	echo "UPDATE event_log SET space_id = (SELECT space_id FROM space WHERE space_key='{$space_key}') WHERE event_log_id = {$rec['event_log_id']}\n";
					//}
					
					if (!$inverse) $events[] = $rec['event_type_id'];
					else unset($events[$key]);
					
					// useful if you think this is reporting false positives
					//echo "Event '", $this->doMap($this->event_id_map, $rec['event_type_id']) ,"' was ", ($rec['date_occurred'] - $timestamp),
					//	" seconds off; ", date('Y-m-d H:i:s', $timestamp), " ", date('Y-m-d H:i:s', $rec['date_occurred']), "\n";
				}
			}
			
			return $events;
			
		}
		
		protected function getStatusMap()
		{
			$query = "
				SELECT
					application_status_id,
					level0, level1, level2, level3, level4, level5
				FROM
					application_status_flat
				WHERE
					active_status = 'active'
			";
			$result = $this->ecash_db->Query($query);
			
			$this->status_map = array();
			$this->status_id_map = array();
			
			while ($rec = $result->Fetch_Array_Row(MYSQLI_ASSOC))
			{
				// get a leaf::branch::*root style status
				$status_id = array_shift($rec);
				$status = implode('::', array_filter($rec));
				
				$this->status_map[$status] = $status_id;
				$this->status_id_map[$status_id] = $status;
			}
			
			return TRUE;
			
		}
		
		protected function getCompanyMap()
		{
			$query = "
				SELECT
					company_id,
					name_short
				FROM
					company
				WHERE
					active_status = 'active'
			";
			$result = $this->ecash_db->Query($query);
			
			$this->company_map = array();
			$this->company_id_map = array();
			
			while ($rec = $result->Fetch_Array_Row(MYSQLI_ASSOC))
			{
				$this->company_map[$rec['name_short']] = $rec['company_id'];
				$this->company_id_map[$rec['company_id']] = $rec['name_short'];
			}
			
			return TRUE;
			
		}
		
		protected function getCampaignInfo($application_id)
		{
			$query = "
				SELECT
					page_id,
					promo_id,
					promo_sub_code
				FROM
					campaign_info
					JOIN site ON (site.site_id = campaign_info.site_id)
				WHERE
					application_id = {$application_id}
				ORDER BY
					date_created DESC
				LIMIT 1
			";
			$result = $this->ecash_db->Query($query);
			
			if ($rec = $result->Fetch_Array_Row())
			{
				return $rec;
			}
			
			return FALSE;
			
		}
		
		protected function getEventMap($timestamp)
		{
			$year  = date('Y', $timestamp);
			$month = date('m', $timestamp);
			$key = $year . "_" . $month;
			
			if(! isset($this->event_map[$key]))
			{
				$this->event_map[$key] = array();
				
				$query = "
					SELECT
						event_type_id,
						event_type_key
					FROM
						event_type_{$year}_{$month} ";

				$result = $this->statpro_db->Query($query);

				while ($rec = $result->Fetch_Array_Row())
				{
					$this->event_map[$key][$rec['event_type_key']] = $rec['event_type_id'];
				}
			}
			
			return $this->event_map[$key];
			
		}

		protected function getEventIdMap($timestamp)
		{
			$year  = date('Y', $timestamp);
			$month = date('m', $timestamp);
			$key = $year . "_" . $month;
			
			if(! isset($this->event_id_map[$key]))
			{
				$this->event_id_map[$key] = array();
				
				$query = "
					SELECT
						event_type_id,
						event_type_key
					FROM
						event_type_{$year}_{$month} ";

				$result = $this->statpro_db->Query($query);

				while ($rec = $result->Fetch_Array_Row())
				{
					$this->event_id_map[$key][$rec['event_type_id']] = $rec['event_type_key'];
				}
			}
			
			return $this->event_id_map[$key];
		}
		
		protected function doMap(Array $map, $keys, $nulls = FALSE)
		{
			if (is_array($keys))
			{
				$mapped = array();
				
				foreach ($keys as $index=>$key)
				{
					if (isset($map[$key]))
					{
						$mapped[$index] = $map[$key];
					}
					elseif ($nulls)
					{
						$mapped[$index] = NULL;
					}
				}
				
				return $mapped;
				
			}
			else
			{
				return isset($map[$keys]) ? $map[$keys] : NULL;
			}
			
			return FALSE;
			
		}
		
		private function getStatProCredentials()
		{
			// Get the enterprise site name from the company config.
			$site_name = ECash::getConfig()->COMPANY_DOMAIN;
			$site_license = $this->getSiteLicenseKey($site_name);
			
			if(empty($site_license))
			{
				die("Unable to locate site '$site_name' in site table.\n");
			}

			$site_config = $this->config->Get_Site_Config($site_license);
			$set_stat = new Set_Stat_3();
			
			$data = $set_stat->getStatProAuthentication($site_config->property_id);
			
			return array($data['key'], $data['pass']);
		}
		
		private function getSiteLicenseKey($site_name)
		{
			$sql = "SELECT license_key FROM site WHERE name = '" . $this->ecash_db->Escape_String($site_name) . "';";
			
			$result = $this->ecash_db->Query($sql);
			$key = $result->Fetch_Object_Row()->license_key;
			
			echo "License Key: $key\n";
			
			return $key;
		}
		
		private function getDateSuffix($timestamp = NULL)
		{
			if(empty($timestamp))
			{
				throw new Exception (__METHOD__ . " :: Missing Timestamp!");
			}
			
			$year  = date('Y', $timestamp);
			$month = date('m', $timestamp);
			
			return "_" . $year . "_" . $month;
			
		}

	}

	class Set_Stat_Hack extends Set_Stat_3
	{
		public function getStatProAuthentication($property_id)
		{
			return parent::getStatProAuthentication($property_id);
		}
	}


	
?>
