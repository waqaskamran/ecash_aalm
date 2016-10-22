<?php

	//$manager->Define_Task('Resolve_Open_Advances_Report', 'resolve_open_advances_report', null, null, array($server, $today), false); //no transaction

/**
 * @deprecated
 */
	class ECash_NightlyEvent_ReactProcess extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'resolve_open_advances_report';
		protected $timer_name = NULL;
		protected $process_log_name = NULL;
		protected $use_transaction = FALSE;
		
		public function __construct()
		{
			$this->classname = __CLASS__;
			
			parent::__construct();
		}
		
		/**
		 * Taken from the function Resolve_Open_Advances_Report()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();
			
$processed = 0;
		
// Determine the date range.
$date_start = strtotime('today - 10 days');
$date_end = strtotime('today - 0 days');

		
		$this->log->write(
			sprintf(
				'React process starting. Searching between %s and %s for properties: %s',
				date(self::DATE_FORMAT, $date_start),
				date(self::DATE_FORMAT, $date_end),
				ECash::getCompany()->name_short
			)
		);
		$processing_time = microtime(TRUE);
		
		// Run the query and process each row.
		$result = $this->getResult($date_start, $date_end);
		$applications_found = $result->rowCount();
		
		while ($row = $result->fetch(DB_IStatement_1::FETCH_ASSOC))
		{
			$this->log->write(
				sprintf(
					'[App:%d] Starting processing of this application.',
					$row['application_id']
				)
			);
			
			// Determine if we can react this application.
			if ($this->canReact($row))
			{
				// Process this application.
				$this->hitStat($row, 'react_start', $sms_promo_id);
				
				if ($wap_promo_id
					&& isset($row['phone_cell'], $row['send_wap'])
					&& $row['phone_cell']
					&& $row['send_wap'] == 'TRUE')
				{
					$this->hitStat($row, 'react_wap_send', $wap_promo_id);
				}
				
				$processed++;
			}
			
			$this->log->write(
				sprintf(
					'[App:%d] Ended processing of this application.',
					$row['application_id']
				)
			);
		}
		
		$processing_time = microtime(TRUE) - $processing_time;
		
		$this->log->write(
			sprintf(
				'React process ended. Processed %d applications out of %d found. Took %.3f seconds.',
				$processed,
				$applications_found,
				$processing_time
			)
		);
		
		return $processed;

		}
		/** Determines if an email address is in the master remove list.
		 *
		 * @param array $react_data
		 * @return bool
		 */
		public function canReact(array $react_data)
		{
			$result = TRUE;
			
			try
			{
				if (isset($react_data['email'])
					&& $react_data['email']
					&& $this->master_remove->queryUnsubEmail($react_data['email']))
				{
					$this->log->write(
						sprintf(
							'[App:%d] Email is in the master remove list: %s',
							$react_data['application_id'],
							$react_data['email']
						),
						Log_ILog_1::LOG_INFO
					);
					$result = FALSE;
				}
			}
			catch (Exception $e)
			{
				$this->log->write(
					sprintf(
						'[App:%d] An exception occurred while checking master remove list. Reason: %s',
						$react_data['application_id'],
						$e->getMessage()
					),
					Log_ILog_1::LOG_ERROR
				);
			}
			
			return $result;
		}
			/** Build and execute the query. Return the result.
		 *
		 * @param array $properties
		 * @param int $date_start
		 * @param int $date_end
		 * @return DB_IStatement_1
		 */
		protected function getResult($date_start, $date_end)
		{
			// I, Ryan Murphy, take no credit for this query. This is copied from
			// the old react process finder (OLP's cronjob/react/react_process.2.php)
			$query = "
				SELECT DISTINCT
					app.application_id,
					com.name_short AS property_short,
					email,
					phone_cell,
					track_id AS track_key,
					IF(app.date_created > '2007-08-09', 'TRUE', 'FALSE') AS send_wap,
					site.license_key,
					campaign_info.promo_id,
					campaign_info.promo_sub_code,
					site.site_id
				FROM
					application AS app
					INNER JOIN
						company AS com USING(company_id)
					INNER JOIN
						application_status AS app_stat
							ON app.application_status_id = app_stat.application_status_id
					INNER JOIN
						status_history AS sh
							ON app.application_id = sh.application_id
					INNER JOIN
						campaign_info
							ON campaign_info.campaign_info_id = (
								SELECT
									MAX(ci2.campaign_info_id)
								FROM
									campaign_info ci2
								WHERE
									ci2.application_id = app.application_id
							)
					INNER JOIN
						site
							ON campaign_info.site_id = site.site_id
				WHERE
					app_stat.name_short = 'paid'
					AND com.name_short IN (" . ECash::getCompany()->name_short . ")
					AND sh.date_created BETWEEN ? AND ?
					AND (
						SELECT
							COUNT(application_id)
						FROM
							application_column AS ac
							INNER JOIN
								application AS appcol USING(application_id)
						WHERE
							appcol.company_id = com.company_id
							AND appcol.ssn = app.ssn
							AND ac.table_name = 'application'
							AND ac.do_not_loan = 'on'
					) = 0
				";
			
			$query_data = 
				array(
					ECash::getCompany()->name_short,
					date(self::DATE_FORMAT, $date_start),
					date(self::DATE_FORMAT, $date_end),
				)
			;
			
			$this->log->write(
				sprintf(
					'Query String: %s',
					$query
				),
				Log_ILog_1::LOG_DEBUG
			);
			$this->log->write(
				sprintf(
					'Query Parameters: %s',
					implode(', ', $query_data)
				),
				Log_ILog_1::LOG_DEBUG
			);
			
			// Execute query (and record how long it took)
			$query_time = microtime(TRUE);
			$result = $this->dbi->prepare($query);
			$result->execute($query_data);
			$query_time = microtime(TRUE) - $query_time;
			
			$this->log->write(
				sprintf(
					'Query found %d applications in %.3f seconds.',
					$result->rowCount(),
					$query_time
				),
				Log_ILog_1::LOG_DEBUG
			);
			
			return $result;
		}
	}


?>