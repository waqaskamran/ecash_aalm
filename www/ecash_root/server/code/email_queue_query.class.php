<?php

/**
 * Contains functions for accessing and updating records from the email queue.
 *
 * THIS CLASS IS INTENDED FOR THE INCOMING EMAIL QUEUE ONLY !!!
 *
 * None of these methods are intended to be called directly. Use Email_Queue
 * methods instead.
 * 
* server object tidbits used by this class
*company_id to limit query by company, and log appropriately
*agent_id for Get_Control_Info from acl object (doesn't acl already have this context?)
*acl
*transport->page_array (only for queue selection)
 */
class Email_Queue_Query
{

	protected $server;
	protected $request;
	protected $db;
	
	/**
	 * Number of minutes an email sits in timeout after being viewed
	 *
	 * @var int $timeout
	 */
	protected $timeout;

	/**
     * Constructor
     *
     * implemented params
     *
     * @param Server $server - the global $server object
     * @param Array $request - the global $request array
     *
     * alternative params- TODO?
     *
     * @param $queue - The name of the queue - one of 'servicing_email_queue', 'collections_email_queue', 'manager_email_queue'
     * @param $company_id - the id of the current company selected
     * @param $agent_id - the id of the current agent
     * @param $acl - an acl object for purposes of restrict
     */
	public function __construct(Server $server = NULL, $request = NULL)
	{
		$this->timeout = 30;
		if ($server != NULL) $this->SetServer($server);
		if ($request != NULL) $this->SetRequest($request);
		$this->db = ECash::getMasterDb();
	}

	/**
     * SetServer
     * 
     * @param $server - The server object for this object to use.
     */
	public function SetServer(Server $server) 
	{
		return $this->server = $server;
	}
	/**
     * SetRequest
     * 
     * @param $request - The request array for this object to use.
     */
	public function SetRequest($request) 
	{
		return $this->request = $request;
	}

	/**
	 * Returns record array for the next followup in the email queue.
	 *
	 * Attempts to pull the next followup. If a mature followup exists, then it 
	 * is pulled and the followup 'flag' is removed. Sets the date_available to 
	 * [$this->timeout] minutes in the future.
	 *
	 * date_followup supercedes date_available. [stated by tonyc documented by davidi]
	 *
	 * @return object|bool Next record in the email queue or FALSE if empty
	 */
	protected function Get_Next_Followup()
	{
		$current_time = date('Y-m-d H:i:s');
		$queue_name = $this->Get_Queue_Name();
		
		if(ECash::getConfig()->MULTI_COMPANY_ENABLED != true)
		{
			$company_restrict = "				AND
					incoming_email_queue.company_id = '{$this->server->company_id}'";
		}

		$agent_id = $this->server->agent_id;
		
		/**
		 * [#28132]
		 * 
		 * @see Get_Next_Non_Followup()
		 */
		$query = "
			SELECT
			incoming_email_queue.company_id as email_company_id,
			incoming_email_queue.*,
			app.*
			FROM
			 incoming_email_queue
            LEFT OUTER JOIN application AS app USING (application_id) 
			WHERE
             (incoming_email_queue.application_id = 0 OR app.application_id IS NOT NULL)
            AND
            	incoming_email_queue.company_id IN
            		(SELECT 
					 	aag.company_id 
					 FROM
					 	agent_access_group aag
					 WHERE
					 	active_status = 'active'
					 AND
					 	agent_id = {$agent_id}
					 )
            AND
			 date_follow_up != '0000-00-00 00:00:00'
			AND
			 date_follow_up <= '" . $current_time . "' 
			AND
			 queue_name = '" . $queue_name . "' 
			 {$company_restrict}
			 	ORDER BY
			 date_follow_up
			LIMIT 1
			FOR UPDATE
			";
		
		$select_result = $this->db->query($query);
		
		while( $record = $select_result->fetch(PDO::FETCH_OBJ) )
		{
			//if a record was found, update it then return it
			$query = "
					UPDATE
					 incoming_email_queue
					SET
					 date_follow_up = '0000-00-00 00:00:00',
					 date_available = ADDTIME(CURRENT_TIMESTAMP(), '0 0:" . $this->timeout . ":0'),
					 agent_id      = " . $this->server->agent_id . " 
					WHERE
					 archive_id = " . $record->archive_id;

			$update_result = $this->db->query($query);

			//[#28132]
			$record->company_id = $record->email_company_id;
			unset($record->email_company_id);

			return $record;
		}

		//no record was found, so...
		return FALSE;
	}

	/**
	 * Returns record array for the next non followup record in the email queue.
	 *
	 * Attempts to pull the next record based on date_available. Sets the
	 * date_available to [$this->timeout] minutes in the future.
	 *
	 * @return object|bool Next record in the email queue or FALSE if empty
	 */
	protected function Get_Next_Non_Followup()
	{
		$queue_name = $this->Get_Queue_Name();
		$this->timeout = 30;

		if(ECash::getConfig()->MULTI_COMPANY_ENABLED != true)
		{
			$company_restrict = "				AND
					incoming_email_queue.company_id = '{$this->server->company_id}'";
		}

		$agent_id = $this->server->agent_id;
		/**
		 * This should get both models at once and then simply inquire if application is null,
		 * avoiding company_id being overwritten in the object from selecting '*' [#28132]
		 * 
		 * @todo Replace with Application/Queue List extending ECash_Models_IterativeModel
		 * @see ECash_Models_TransactionList
		 */
		
		$query = "
			SELECT
			incoming_email_queue.company_id as email_company_id,
			incoming_email_queue.*,
			app.*
			FROM
			 incoming_email_queue
			LEFT OUTER JOIN application AS app USING (application_id)
			WHERE
             (incoming_email_queue.application_id = 0 OR app.application_id IS NOT NULL)
            AND
            	incoming_email_queue.company_id IN
            		(SELECT 
					 	aag.company_id 
					 FROM
					 	agent_access_group aag
					 WHERE
					 	active_status = 'active'
					 AND
					 	agent_id = {$agent_id}
					 )
            AND 
             date_follow_up = '0000-00-00 00:00:00'
			AND
			 date_available <= CURRENT_TIMESTAMP()
			AND
			 queue_name = '{$queue_name}' 
			 {$company_restrict}
			ORDER BY
			 incoming_email_queue.date_modified
			LIMIT 1
			FOR UPDATE
			";

		$select_result = $this->db->query($query);
		
		while( $record = $select_result->fetch(PDO::FETCH_OBJ) )
		{
			//if a record was found, update it then return it
			$query = "
					UPDATE
					 incoming_email_queue
					SET
					 date_available = DATE_ADD(CURRENT_TIMESTAMP(), INTERVAL " . $this->timeout . " MINUTE),
					 agent_id      = " . $this->server->agent_id . " 
					WHERE
					 archive_id = " . $record->archive_id;

			$update_result = $this->db->query($query);

			//[#28132]
			$record->company_id = $record->email_company_id;
			unset($record->email_company_id);
			
			return $record;
		}

		//no record was found, so...
		return FALSE;
	}

	/**
	 * Returns record object or FALSE if not found.
	 * 
	 * @param int $archive_id
	 * @return object|bool Next record in the email queue or FALSE if empty
	 */
	protected function Get_Record_By_Id($archive_id)
	{
		$query = "
			SELECT
			 *
			FROM
			 incoming_email_queue
			WHERE
			 archive_id = {$archive_id}
			";

		return $this->db->querySingleRow($query, NULL, PDO::FETCH_OBJ);
	}

	/**
	 * Returns the name of the queue being queried based on the location info
	 * from the server->transport object.
	 *
	 * @return string Queue name, or empty string for unknown location
	 */
	protected function Get_Queue_Name()
	{
		$queues = array(
		                'servicing_email_queue',
		                'collections_email_queue',
		                'manager_email_queue'
		               );

		switch (TRUE)
		{
			// if the queue is set to a valid queue name
			case ( isset($this->request->queue) && in_array($this->request->queue, $queues) ):
				return $this->request->queue;
			case ( in_array('loan_servicing', ECash::getTransport()->page_array) ):
				return 'servicing_email_queue';
			case ( in_array('collections', ECash::getTransport()->page_array) ):
				return 'collections_email_queue';
			default:
				//email_queue is not configured to be pulled from this location,
				//so no results will be displayed.
		}
	}

	/**
	 * Adds record to email queue. Not public. Email_Queue->Add_To_Email_Queue()
	 *
	 * @param int $archive_id The archive id
	 * @param int $company_id The company id
	 * @param int $queue_name Put in this queue
	 * @param bool|int|string $is_failed Bool, 1 or 0, 'yes' or 'no'
	 * @param int $application_id optional application_id association
	 */
	protected function Insert_Record($archive_id, $company_id, $queue_name, $is_failed, $application_id='0')
	{
		// is_failed can be bool value, 1 or 0, or 'yes' or 'no'
		$is_failed = strtolower($is_failed);
		if ($is_failed != 'yes' && $is_failed != 'no')
		{
			$is_failed = ($is_failed ? 'yes' : 'no');
		}

		// if Condor fails to match this up correctly - resulting in '', then just use the default
		if ( empty($queue_name) )
			$queue_name = ECash::getConfig()->DEFAULT_INCOMING_EMAIL_QUEUE;

		if ( empty($queue_name) ) 
			throw new Exception ("File " . __FILE__ . " method " . __METHOD__ . " Line " .  __LINE__ . "There is no queue name default or passed here!  This email will be inaccessible in ANY QUEUE!!");

		$query = "
				INSERT IGNORE INTO
				 incoming_email_queue
				 (
				  date_modified,
				  date_created,
				  date_available,
				  archive_id,
				  company_id,
				  application_id,
				  is_failed,
				  queue_name
				 )
				VALUES
				 (
				  SUBTIME(CURRENT_TIMESTAMP(), '00:31:00'),
				  CURRENT_TIMESTAMP(),
				  CURRENT_TIMESTAMP(),
				  {$archive_id},
				  {$company_id},
				  {$application_id},
				  '{$is_failed}',
				  '{$queue_name}'
				 )";

		$this->db->exec($query);
	}

	/**
	 * Sets a follow up for the specified archive_id and timestamp.
	 *
	 * @param int $archive_id The archive id
	 * @param string $timestamp should be a valid datetime format, 
	 * ie. 'Y-M-d H:i:s'
	 */
	protected function Set_Followup($archive_id, $timestamp)
	{
		$query = "
				UPDATE 
				 incoming_email_queue
				SET
				 date_follow_up = '{$timestamp}',
				 date_available = '{$timestamp}'
				WHERE
				 archive_id = {$archive_id}
				";

		$this->db->query($query);
	}

	/**
	 * Returns an array of account objects matching the specified email address.
	 *
	 * Each element contains an array of application_id, name_last, and name_first
	 *
	 * Intended use: if only one app is returned it should be associated with
	 * the archive id in the queue record. If multiple apps are returned, they
	 * should be displayed so that the user can choose the appropriate app.
	 *
	 * @param int $company_id
	 * @param string $email_address
	 * @return array Array of application_ids
	 */
	protected function Get_Accounts_By_Email($company_id, $email_address)
	{
		if ( empty($email_address) )
			return FALSE;

		$query = "
				SELECT 
				 app.application_id,
				 app.name_last,
				 app.name_first,
				 app_stat.name AS status
				FROM
				 application app
				JOIN
				 application_status app_stat
				ON
				 (app.application_status_id = app_stat.application_status_id)
				WHERE
				 app.email = '{$email_address}'
				AND
				 app.company_id = {$company_id}
				";
		
		return $this->db->query($query)->fetchAll(PDO::FETCH_OBJ);
	}

	/**
	 * Returns an array of account objects matching the specified email address.
	 *
	 * Each element contains an array of application_id, name_last, and name_first
	 *
	 * @param string $application_id
	 * @return array Array of application_ids
	 */
	protected function Get_Account_By_Application_Id($application_id)
	{
		if ( empty($application_id) )
			return FALSE;

		$query = "
				SELECT 
				 application_id,
				 name_last,
				 name_first
				FROM
				 application
				WHERE
				 application_id = '{$application_id}'
				";
		
		return $this->db->querySingleRow($query, NULL, PDO::FETCH_OBJ);
	}

	/**
	 * Sets the queue name for record [$archive_id] to $queue_name.
	 * Also sets the date_modified so that the record will appear in the
	 * new queue immediately.
	 *
	 * @param int $archive_id The archive id
	 * @param string $queue_name New queue_name value
	 */
	protected function Update_Queue_Name($archive_id, $queue_name)
	{
		$query = "
				-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
				UPDATE 
				 incoming_email_queue
				SET
				 queue_name = '{$queue_name}',
				 date_available = CURRENT_TIMESTAMP()
				WHERE
				 archive_id = '{$archive_id}'
				";

		$this->db->exec($query);
	}

    /**
     * Returns the number of emails in the queue. If no queue is specified,
     * attempts to determine queue based on the current module.
     *
     * @param string $queue_name optional queue name
     * @return int The number of emails in the queue
     */
	protected function Queue_Count($queue_name = NULL)
	{
		$current_time = date('Y-m-d H:i:s');
		$queue_name = ($queue_name !== NULL ? $queue_name : $this->Get_Queue_Name() );

		$company_restrict = '';
		if(ECash::getConfig()->MULTI_COMPANY_ENABLED != true)
		{
			$company_restrict = "				AND (
					a.company_id = '{$this->server->company_id}' OR a.company_id IS NULL )
					AND (
					ie.company_id = '{$this->server->company_id}' OR ie.company_id IS NULL )";
		}
		
		$agent_id = $this->server->agent_id;
		
		$query = "
			SELECT
				COUNT(ie.archive_id) as count
			FROM
				incoming_email_queue ie
			LEFT JOIN
				application a on (ie.application_id = a.application_id)
			WHERE
				ie.company_id IN
					(SELECT 
					 	aag.company_id 
					 FROM
					 	agent_access_group aag
					 WHERE
					 	active_status = 'active'
					 AND
					 	agent_id = {$agent_id}
					 )
			AND
				ie.date_available <= CURRENT_TIMESTAMP()
			AND
				ie.date_follow_up <= NOW()
			AND
				ie.queue_name = '{$queue_name}'
			{$company_restrict}
		";
		
		return $this->db->querySingleValue($query);
	}
	
	/**
	 * Set the application_id.
	 *
	 * @param int $archive_id The archive id
	 * @param int $application_id The company id
	 */
	public function Set_Application_Id($archive_id, $application_id)
	{
		$query = "
				UPDATE 
				 incoming_email_queue
				SET
				 application_id = '{$application_id}'
				WHERE
				 archive_id = '{$archive_id}'
				";

		$this->db->exec($query);
	}

	/**
	 * Move record to unassociated table. For emails that cannot be associated.
	 *
	 * @param int $archive_id The archive id
	 */
	protected function Move_To_Unassociated_Table($archive_id)
	{
		$query = "
				INSERT INTO 
				 unassociated_incoming_email
				(
				date_created,
				archive_id,
				company_id,
				agent_id,
				is_failed,
				queue_name
				)
				
				SELECT
				 NOW() as date_created,
				 archive_id,
				 company_id,
				 agent_id,
				 is_failed,
				 queue_name
				FROM
				 incoming_email_queue
				WHERE
				 archive_id = {$archive_id}
				
				";

		$this->db->exec($query);

		$query = "
				DELETE FROM
				 incoming_email_queue
				WHERE
				 archive_id = {$archive_id}
				";

		$this->db->exec($query);
	}

	/**
	 * Adds an entry to the email_queue_report table, logging the agent action.
	 *
	 * @param int $archive_id The archive id
	 * @param int $agent_id The agent's id
	 * @param string $action The action performed
	 */
	protected function Log_Email_Queue_Action($archive_id, $agent_id, $action)
	{
		$company_id = $this->server->company_id;
		$queue_name = $this->Get_Queue_Name();

		if (empty($agent_id)) 
			throw new Exception(" -- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . " - No agent id specified, unable to log email queue action!");

		if (empty($company_id)) 
			throw new Exception(" -- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . " - Unable to determine company_id, unable to log email queue action!");

		if (empty($archive_id)) 
			throw new Exception(" -- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . " - No archive id specified, unable to log email queue action!");

		$query = "
				INSERT INTO
				 email_queue_report
				(
				 date_created,
				 archive_id,
				 agent_id,
				 company_id,
				 queue_name,
				 action
				)
				VALUES
				(
				 NOW(),
				 {$archive_id},
				 {$agent_id},
				 {$company_id},
				 '{$queue_name}',
				 '{$action}'
				)
				";

		$this->db->exec($query);
	}

	/**
	 * Removes archive record from the incoming_email_queue table.
	 *
	 * @param int $archive_id The archive id
	 */
	protected function Remove_From_Email_Queue($archive_id)
	{
		$query = "
				DELETE FROM
				 incoming_email_queue
				WHERE
				 archive_id = {$archive_id}
				";

		$this->db->exec($query);
	}

	/**
	 * Returns the queue name associated with the specified $email_address
	 *
	 * This is determined by the email_response_footer table record, set in the
	 * Admin section under Document Manager > Email Manager
	 *
	 * @param string $company_id
	 * @param string $email_address incoming recipient email address
	 * @return string Queue associated with the specified incoming email address
	 * or empty string on failure
	 */
	protected function Fetch_Queue_By_Email_Address($company_id, $email_address)
	{
		$log = get_log();
		
		$query = "
				SELECT
				 queue_name
				FROM
				 email_response_footer
				WHERE
				 company_id = {$company_id}
				AND
				 email_incoming = '{$email_address}'
				";

		$log->Write("EMAIL SPECIFIED $email_address");
		//$log->Write("QUEUE FOUND {$row->queue_name}");
		return $this->db->querySingleValue($query);
	}

	/**
	 * Returns the company id associated with the specified $email_address
	 *
	 * This is determined by the email_response_footer table record, set in the
	 * Admin section under Document Manager > Email Manager
	 *
	 * @param string $email_address Returns queue associated with this address
	 * @return string Queue associated with the specified incoming email address
	 * or empty string on failure
	 */
	public function Fetch_Company_Id_By_Email_Address($email_address)
	{
		$query = "
			SELECT
			 company_id
			FROM
			 email_response_footer
			WHERE
			 email_incoming = '{$email_address}'
				";

		return $this->db->querySingleValue($query);
	}

	/**
	 * Removes any application_id from the specified incoming_email_queue record
	 *
	 * @param string $archive_id
	 */
	protected function Remove_Application_Id($archive_id)
	{
		$query = "
				UPDATE
				 incoming_email_queue
				SET
				 application_id = 0
				WHERE
				 archive_id = '{$archive_id}'
				";

		$this->db->exec($query);
	}
}

?>
