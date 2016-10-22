<?php

	/**
	 * This class is used to describe and implement the necessary calls for
	 * a nightly event.  This is the base class that handles the basic
	 * necessities required for the majority of the tasks to run.
	 *
	 * The objects created from these classes will be passed to a
	 * CronScheduler object which will then run them.
	 *
 	 * @author Brian Ronald <brian.ronald@sellingsource.com>
	 */
	abstract class ECash_Nightly_Event
	{
		// Arguments
		protected $server;
		protected $log;
		protected $today;
		protected $start_date;
		protected $end_date;

		protected $company;
		protected $company_id;

		/**
		 * @var DB_Database_1
		 */
		protected $db;
		
		/**
		 * @var DB_IConnection_1
		 */
		protected $app_svc_db;

		// Parameters used by the Cron Scheduler
		protected $business_rule_name = '';
		protected $timer_name = '';
		protected $process_log_name = '';
		protected $use_transaction = FALSE;

		public function __construct()
		{
			// For internal use and the Timer logging
			$this->today = date('Y-m-d');

			// Set a default start/run date to today.  This can be
			// overridden using the setStartDate() method.
			$this->start_date = $this->today;

			// Some functions require an end_date.  This is set to today
			// but can be overridden using the setEndDate() method.
			$this->end_date   = $this->today;
		}

		/**
		 * The contents of the function that needs to be run.  This may simply
		 * be a wrapper or the entire function.
		 *
		 * @return boolean TRUE on Success, FALSE on Failure
		 */
		public function run()
		{

			$this->log    = $this->server->log;
			$this->db = ECash::getMasterDb();
			$this->app_svc_db = ECash::getAppSvcDB();

			return TRUE;
		}

		/**
		 * Set the Server Object
		 *
		 * @param Server $server
		 */
		public function setServer(Server $server)
		{
			$this->server = $server;
		}

		/**
		 * Get the Server Object
		 *
		 * @return Server $server
		 */
		public function getServer()
		{
			return $this->server;
		}

		/**
		 * Set the Log Object to something other
		 * than the default
		 *
		 * @param Applog $log
		 */
		public function setLog(Applog $log)
		{
			$this->log = $log;
		}

		/**
		 * Set the company short name
		 *
		 * @param string
		 */
		public function setCompanyShort($company)
		{
			$this->company = $company;
		}

		/**
		 * Set the company short name
		 *
		 * @param string
		 */
		public function setCompanyId($company_id)
		{
			$this->company_id = $company_id;
		}

		/**
		 * Set the start date
		 *
		 * @param string $date (Y-m-d)
		 */
		public function setStartDate($date)
		{
			$this->start_date = $date;
		}

		/**
		 * Set the end date
		 *
		 * @param string $date (Y-m-d)
		 */
		public function setEndDate($date)
		{
			$this->end_date = $date;
		}

		/**
		 * Returns the class name
		 *
		 * @return string
		 */
		public function Get_Function_Name()
		{
			return $this->classname;
		}

		/**
		 * Getter method for the event's
		 * business rule name.  Used by the Cron Scheduler.
		 *
		 * @return string
		 */
		public function getBusinessRuleName()
		{
			return $this->business_rule_name;
		}

		/**
		 * Getter for the event's
		 * Timer name.  Used by the Cron Scheduler.
		 *
		 * @return string
		 */
		public function getTimerName()
		{
			return "({$this->today}) " . $this->timer_name;
		}

		/**
		 * Getter for the event's
		 * Process Log entry name.  Used by the Cron Scheduler.
		 *
		 * @return unknown
		 */
		public function getProcessLogName()
		{
			return $this->process_log_name;
		}

		/**
		 * Tell the class whether or not to run within
		 * a MySQL transaction
		 *
		 * @param bool $value
		 */
		public function setUseTransactionFlag($value)
		{
			if(is_bool($value))
			{
				$this->use_transaction = $value;
			}
		}

		/**
		 * Get the setting for whether the function
		 * should run within a MySQL transaction
		 *
		 * @return bool
		 */
		public function getUseTransactionFlag()
		{
			return $this->use_transaction;
		}

	}

?>