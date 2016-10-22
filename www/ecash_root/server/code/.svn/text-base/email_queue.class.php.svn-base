<?php

require_once(SERVER_CODE_DIR . "email_queue_query.class.php");
require_once(SQL_LIB_DIR ."application.func.php");
require_once(SERVER_MODULE_DIR  . "/admin/docs_config.class.php");
require_once(LIB_DIR . "/Document/DeliveryAPI/Condor.class.php");

/**
 * Contains functions for accessing and updating records from the email queue.
 *
 * THIS CLASS IS INTENDED FOR THE INCOMING EMAIL QUEUE ONLY !!!
 *
 */
class Incoming_Email_Queue extends Email_Queue_Query
{

	/**
	 * Associative array of email address / queue name values.
	 * This var is used for caching these values to avoid unnecessary
	 * database calls during queue population.
	 *
	 * @var array $queue_by_email_address
	 */
	protected $queue_by_email_address;

	/**
	 * Associative array of email address / company id values.
	 * This var is used for caching these values to avoid unnecessary
	 * database calls during queue population.
	 *
	 * @var array $company_by_email_address
	 */
	protected $company_by_email_address;

	/**
	 * Construct
	 *
	 */
    public function __construct(Server $server = NULL, $request = NULL)
	{
    	parent::__construct($server, $request);

		// initialize caching arrays
		$this->queue_by_email_address = array();
		$this->company_by_email_address = array();
	}

	/**
	 * Fetches the next archive in the queue then calls Get_Email_Document()
	 *
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function Get_Next_Email()
	{
		// get the next archive_id in the queue
		$record = $this->Fetch_Next_Record();

		if (FALSE === $record) return FALSE; // queue is empty

		// load document data into the transaport object
		$success = $this->Get_Email_Document($record->archive_id);

		// if the document was successfully loaded...
		if (TRUE === $success)
		{
			$this->Log_Agent_Action($this->request->email_queue_record->archive_id, $this->server->agent_id, 'receive');
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Loads data for the specified email document into the transport object.
	 *
	 * Returns FALSE if the document fails to load (queue empty or Condor error)
	 *
	 * @param int $archive_id required, use request->archive_id if you don't have it already.
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function Get_Email_Document($archive_id=NULL)
	{

		// get the incoming_email_queue record data
		$this->request->email_queue_record = $this->Fetch_Email_By_Id($archive_id);

		// if the record is valid, get the data for the application link ie. customer name
		if ($this->request->email_queue_record->application_id > 0)
		{
			$this->request->account = $this->Fetch_Account_By_Application_Id($this->request->email_queue_record->application_id);
		}

		// this is passed to a hidden input value token for the quick search form
		$this->request->module = ($this->request->email_queue_record->queue_name == 'collections_email_queue' ? 'collections' : 'loan_servicing');

		try
		{
			// get the actual document data from Condor
			$this->request->email_document = eCash_Document_DeliveryAPI_Condor::Prpc()->Find_Email_By_Archive_Id($archive_id, TRUE);
			if(strlen($this->request->email_document->data) == 0)
			{
				if($this->request->email_document->attached_data[0]->content_type = 'text/html' && isset($this->request->email_document->attached_data[0]->data))
				{
					$this->request->email_document->data = strip_tags($this->request->email_document->attached_data[0]->data);
				}
			}

			// if we didn't get the data from Condor...
			if (FALSE === $this->request->email_document)
			{
				$message = "condor.4::Find_Email_By_Archive_Id did not return a valid result for archive id {$archive_id}.";
				throw new Exception($message);
			}

		}
		catch (Exception $e)
		{
			ECash::getLog()->Write( 'Error in File:' . __FILE__ . ' on Line:' . __LINE__ . ', ' . $e->getMessage() );
			/*GF 30878 
			  Since there is an error, we might as well return false so Log_Agent_Action doesn't explode later...
			  */
			return FALSE;
		}

		// GF 10985: Make sure this has a default value [ben]b
		$this->request->suggested_applications = "";

		// if Condor sent an originating email address and the email is not already associated...
		if ( isset($this->request->email_document->latest_dispatch->sender) 
			&& '0' === $this->request->email_queue_record->application_id )
		{
			// get a list of (application_ids, names, and statuses) that match the originating email address
			$this->request->suggested_applications = $this->Fetch_Matching_Accounts_By_Email($this->request->email_queue_record->company_id, $this->request->email_document->latest_dispatch->sender);
		}
		$request_obj = new stdclass();
		foreach($this->request as $key => $value)
		{
			$request_obj->$key = $value;
		}
		ECash::getTransport()->Set_Data($request_obj);

		if ('loan_servicing' === $this->request->module)
		{
			ECash::getTransport()->Set_Levels('application', 'loan_servicing', 'customer_service', 'email_queue');
		}
		else
		{
			ECash::getTransport()->Set_Levels('application', 'collections', 'internal', 'email_queue');
		}

		return TRUE;
	}

	/**
	 * Calls Docs_Config::Display_Email_Responses which loads the response data
	 * into the transport object, then calls Get_Email_Document() to load the
	 * email document data.
	 *
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function Get_Email_Response_Data()
	{
		// load canned responses into the transport object
		$docs_config = new Docs_Config($this->server, $this->request);
		$docs_config->DisplayEmailResponses($this->company_id);

		$data = new stdClass();

		// load token values into the data object
		$application = ECash::getApplicationById($this->request->application_id);
		$data->tokens = $application->getTokenProvider()->getTokens();


		// manually load the response footer into the data object
		$data->email_response_footer = $this->Get_Email_Response_Footer();
		ECash::getTransport()->Set_Data($data);

		// return the bool result of loading the email document data into the transport object
		return $this->Get_Email_Document($this->request->archive_id);
	}

	/**
	 * Calls Docs_Config::Display_Email_Responses which loads the response data
	 * into the transport object, then calls Get_Email_Document() to load the
	 * email document data.
	 *
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function Get_Email_Response_Footer()
	{
		$docs_config = new Docs_Config($this->server, $this->request);
		$footers = $this->FetchEmailFooters($this->server->company_id);
		$queue_name = parent::Get_Queue_Name();

		if ( isset($footers[$this->server->company_id][$queue_name] ))
		{
			return  $footers[$this->server->company_id][$queue_name]['text'];
		}

		return '';
	}

	/**
	 * Sends the email response, then displays the next email in the queue.
	 *
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function Send_Email_Response()
	{
		//assemble document with attachments
		$document_name = ECash::getConfig()->EMAIL_RESPONSE_DOCUMENT;
		$docs_to_send = array();
		$request_obj = new stdclass();
		foreach($this->request as $key => $value)
		{
			$request_obj->$key = $value;
		}
		$request_obj->email_queue_record = $this->Fetch_Email_By_Id($request_obj->archive_id);
		$app_obj = ECash::getApplicationById($request_obj->email_queue_record->application_id);
		$app_obj->getDocuments()->Set_Generic_Email($this->Get_Sender_Name_By_Queue($request_obj->email_queue_record->queue_name), $request_obj->response_subject, str_replace("\n", "<br />", stripslashes($request_obj->response_message) ));
		$message_template = $app_obj->getDocuments()->getTemplateByNameShort($document_name);

		try
		{
			ECash::getTransport()->Set_Data((object) array('alert_message' => 'The email response was not sent due to an error.'));

			if (FALSE === $message_template)
			{
				$message = " eCash_Document::Get_Documents_By_Name did not return a valid result for '{$document_name}'.";
				throw new Exception($message);
			}
			$docs_to_send[] = $message_template;
//			$key = array_keys($doc);
			
			if (is_array($request_obj->attachment) )
			{
				for($x=0; $x<count($request_obj->attachment); $x++)
				{
					if($template = 	$app_obj->getDocuments()->getTemplateByNameShort($request_obj->attachment[$x]))
						$docs_to_send[] = $template;
				//	$doc[$key[0]]->bodyparts[] = eCash_Document::Singleton($this->server, $request_obj)->Get_Documents_By_Name($request_obj->attachment[$x]);
				}
			}
			
			foreach($docs_to_send as $template)
			{
				if($doc = $app_obj->getDocuments()->create($template))
				{
					$transports = $doc->getTransportTypes();
					$transports['email']->setEmail($app_obj->email);
				
					if(!$doc->send($transports['email'], ECash::getAgent()->getAgentId()))
					{
						throw new Exception('email send failure');
					
					}

				}			
		
			}
			
			
			//ECash::getTransport()->Set_Data($request_obj);
			//eCash_Document::Singleton($this->server, $request_obj)->Send_Document($request_obj->email_queue_record->application_id, $doc, 'email');

			ECash::getTransport()->Set_Data((object) array('alert_message' => 'An error occurred after the email was sent.'));

			if ( isset($request_obj->canned_response_count) && $request_obj->canned_response_count > 0)
			{
				$this->Log_Canned_Response_Usage($request_obj->archive_id, $request_obj->canned_response_count);
			}

			$this->File_Email_Document($request_obj->archive_id);

			$this->Log_Agent_Action($request_obj->archive_id, $this->server->agent_id, 'respond');

			ECash::getTransport()->Set_Data((object) array('alert_message' => 'The email response has been sent.'));
		}
		catch (Exception $e)
		{

			ECash::getLog()->Write( 'Error in File:' . __FILE__ . ' on Line:' . __LINE__ . ', ' . $e->getMessage() );

		}

		return $this->Get_Next_Email();		
	}

	/**
	 * Receives the email document and attachments.
	 *
	 * @param int $archive_id The archive id
	 */
	public function File_Email_Document($archive_id)
	{
		// Get the email queue record data
		$request = $this->Fetch_Email_By_Id($archive_id);
		$request->archive_id = $archive_id;
		$request->method = 'email';

		// Get the document (template) name
		$document_name = ECash::getConfig()->EMAIL_RECEIVE_DOCUMENT;

		try
		{
			// Get the actual email document (and attachment data) from Condor
			$email = eCash_Document_DeliveryAPI_Condor::Prpc()->Find_Email_By_Archive_Id($archive_id, TRUE);

			// Make sure it the email data is valid...
			if (FALSE === $email)
			{
				$message = " eCash_Document::Find_Email_By_Archive_Id did not return a valid result for '{$archive_id}'.";
				throw new Exception($message);
			}

			// Get the document list data for $document_name
			$doc = eCash_Document::singleton($this->server, $this->request)->Get_Documents_By_Name($document_name);

			// Make sure it the list data is valid...
			if (FALSE === $doc)
			{
				$message = " eCash_Document::Get_Documents_By_Name did not return a valid result for '{$document_name}'.";
				throw new Exception($message);
			}

			$request->document_list[$doc->document_list_id] = $doc;
			$request->{docname_ . strtolower($doc->name)} = ( empty($email->subject) || $email->subject == 'NULL' ? 'Email' : $email->subject);

			// eCash_Document::Receive_Document needs $_SESSION['current_app']->application_id
			$_SESSION['current_app']->application_id = $request->application_id;

			// There must be a valid application id to receive documents, so...
			if ( !is_numeric($_SESSION['current_app']->application_id) )
			{
				$message = 'Email_Queue::File_Email_Document could not obtain a valid application_id, '
				         . "so archive ({$request->archive_id}) was not received.";
				throw new Exception($message);
			}

			// Receive the document
			eCash_Document::singleton($this->server, $this->request)->Receive_Document($request);

			// Get the actual email document (and attachment data) from Condor
			$email = eCash_Document_DeliveryAPI_Condor::Prpc()->Find_Email_By_Archive_Id($archive_id, TRUE);

			// Now for each attachment...
			foreach ($email->attached_data as $attachment)
			{
				if ($attachment->uri != 'NULL') // only valid attachments
				{
					// Get an archive_id for this attachment, so we can treat it as a document
					$request->archive_id = eCash_Document_DeliveryAPI_Condor::Prpc()->Create_Document_From_Part($archive_id, $attachment->part_id);

					if ( !is_numeric($request->archive_id) )
					{
						throw new Exception("Prpc()->Create_Document_From_Part did not return a valid archive_id.");
					}
					else
					{
						// Set the attached document's name
						$request->{docname_ . strtolower($doc->name)} = ($attachment->uri == 'NULL' ? 'Attached Document' : $attachment->uri);

						// Receive the attachment
						eCash_Document::singleton($this->server, $this->request)->Receive_Document($request);
					}
				}
			}

			// no errors to this point, so remove the email from the queue
			parent::Remove_From_Email_Queue($archive_id);

		}
		catch (Exception $e)
		{

			ECash::getLog()->Write( 'Error in File:' . __FILE__ . ' on Line:' . __LINE__ . ', ' . $e->getMessage() );

		}

		// Receive_Document tries to show the documents screen, so...
		$module = ($request->queue_name == 'servicing_email_queue' ? 'loan_servicing' : 'collections');
		ECash::getTransport()->Set_Levels('application', $module, $this->request->mode);
	}

	/**
	 * Returns record object for the next archive in the email queue, or FALSE.
	 *
	 * Object properties:
	 *  date_modified
	 *  date_created
	 *  date_follow_up
	 *  archive_id
	 *  company_id
	 *  application_id
	 *  agent_id
	 *  is_failed
	 *  queue_name
	 * 
	 * First attempts to pull the next followup. If there is no mature followup,
	 * then the next record is pulled based on the date_modified. When a
	 * record is pulled, the date_modified is updated. If it's a followup, then
	 * the followup is removed.
	 *
	 * WHY IS THIS NOT HANDLED WITH ONE ELEGANT QUERY?
	 * This is a very active table with many records. Though this multi-query
	 * approach is less elegant, the queries will run much quicker.
	 *
	 * @return object|bool Next record in the email queue or FALSE if empty
	 */
	protected function Fetch_Next_Record()
	{
		$record = parent::Get_Next_Followup();

		if (FALSE === $record)
			$record = parent::Get_Next_Non_Followup();

		if($record && ! empty($record->company_id) && $record->company_id != $this->server->company_id)
		{
			$this->server->Set_Company($record->company_id);
			$this->server->Load_Company_Config($record->company_id);
		}
		return $record;
	}

	/**
	 * Attempts to associate archive with application then adds it to the queue.
	 *
	 * Fetch_Matching_Accounts_By_Email() returns an array of all apps matching
	 * the specified $email_address. If only one match is returned, then it is
	 * associated with the archive in the incoming_email_queue table.
	 *
	 * @param int $company_id The company id
	 * @param int $archive_id The archive id
	 * @param bool|int|string $is_failed Bool, 1 or 0, 'yes' or 'no'
	 * @param string $rec_email_address The email recipient address (address email was sent to)
	 * @param string $orig_email_address The originating email address
	 */
	public function Add_To_Email_Queue($company_id, $archive_id, $is_failed, $rec_email_address, $orig_email_address)
	{
		// if company id is not set or is not set correctly, then attempt to
		// determine the correct company id based on the rec_email_address
		if ( !is_numeric($company_id) )
		{
			$company_id = $this->Get_Company_Id_By_Email_Address($rec_email_address);
		}

		// get a list of (application_ids, names, and statuses) that match the originating email address
		$apps = $this->Fetch_Matching_Accounts_By_Email($company_id, $orig_email_address);

		// if only one application is returned, set $application_id so it will be added to the record
		$application_id = (1 === count($apps) ? $apps[0]->application_id : '0');

		//We haven't been appropriately handling situations where there are multiple recipients for an email.
		//When there are multiple recipients, we've been searching for the list of recipients as a string, and returning no queue
		//In cases where there's no default email queue defined, the email is not getting imported into eCash.  
		//We're going to sanitize the addresses and explode them into an array.  As soon as we find an address that matches a queue,
		//We'll import it into that one. [W!-02-09-2009][#25547]
		$rec_email_address = preg_replace('/[^a-zA-Z0-9_.@,\+]*/','',$rec_email_address);
		$recipients = explode(',',$rec_email_address);
		foreach ($recipients AS $recipient)
		{
			$queue_name = $this->Get_Queue_By_Email_Address($company_id, $recipient);
			if($queue_name) 
			{
				break;	
			}
		}

		parent::Insert_Record($archive_id, $company_id, $queue_name, $is_failed, $application_id);
	}

	/**
	 * Manually associate an application with the specified archive.
	 *
	 * @param int $archive_id The archive id
	 * @param int $application_id The company id
	 */
	public function Associate_With_Application_Id($archive_id, $application_id)
	{
		parent::Set_Application_Id($archive_id, $application_id);

		$this->Log_Agent_Action($archive_id, $this->server->agent_id, 'associate');
	}

	/**
	 * Returns array of account objects matching the specified $email_address.
	 *
	 * Each object contains an array of application_id, name_last, and name_first
	 * 
	 * @param int $company_id
	 * @param string $email_address The archive's originating email address
	 * @return array Application Ids matching $email_address
	 */
	protected function Fetch_Matching_Accounts_By_Email($company_id, $email_address)
	{
		return parent::Get_Accounts_By_Email($company_id, $email_address);
	}

	/**
	 * Returns array of account objects matching the specified $application_id.
	 *
	 * Each object contains an array of application_id, name_last, and name_first
	 * 
	 * @param int $application_id of account
	 * @return array Application Ids matching $email_address
	 */
	protected function Fetch_Account_By_Application_Id($application_id)
	{
		return parent::Get_Account_By_Application_Id($application_id);
	}

	/**
	 * Schedules a follow up for the specified archive_id and timestamp.
	 *
	 * @param int $archive_id The archive id
	 * @param string $timestamp should be a valid datetime format, 
	 * ie. 'Y-M-d H:i:s'
	 */
	public function Schedule_Followup($archive_id, $timestamp)
	{
		parent::Set_Followup($archive_id, $timestamp);

		$this->Log_Agent_Action($archive_id, $this->server->agent_id, 'followup');
	}

	/**
	 * Returns record object or FALSE if not found.
	 *
	 * @param int $archive_id The archive id
	 * @return object|bool Next record in the email queue or FALSE if empty
	 */
	protected function Fetch_Email_By_Id($archive_id)
	{
		if ( empty($archive_id) )
			return FALSE;

		return parent::Get_Record_By_Id($archive_id);
	}

	/**
	 * Sets the queue name for record [$archive_id] to $queue_name.
	 *
	 * @param int $archive_id The archive id
	 * @param string $queue_name New queue_name value
	 */
	public function Send_To_Other_Queue($archive_id, $queue_name)
	{
		parent::Update_Queue_Name($archive_id, $queue_name);

		// GF #11973: Since it's not conditional on anything in PHP, I just went ahead
		// and moved the alert_message crap to the associated_email HTML file. [benb]

		// sending an email to the Manager Queue is a unique action type
		$action_type = ($queue_name == 'manager' ? 'manager' : 'queue');

		$this->Log_Agent_Action($archive_id, $this->server->agent_id, $action_type);
	}

	/**
	 * attempts to determine queue based on the current module.
	 *
	 * @param string $queue_name optional queue name
	 * @return int The number of emails in the queue
	 */
	public function Fetch_Queue_Count($queue_name = NULL)
	{
		return parent::Queue_Count($queue_name);
	}

	/**
	 * Move record to unassociated table. For emails that cannot be associated.
	 *
	 * @param int $archive_id The archive id
	 */
	public function Move_To_Unassociated($archive_id)
	{
		parent::Move_To_Unassociated_Table($archive_id);

		$this->Log_Agent_Action($archive_id, $this->server->agent_id, 'remove');
	}

	/**
	 * Logs canned response usage by simply adding [$count] canned response entries.
	 *
	 * @param int $archive_id The archive id
	 * @param int $count Number of canned responses used
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function Log_Canned_Response_Usage($archive_id, $count)
	{
		// some minimal input validation to be safe
		if ( !is_numeric($count) )
			return FALSE;

		// $count should be around 2 or 3, 
		// if there's more than 10 then the agent was doing something screwy,
		// it serves our purpose to simply log the first 10
		$count = ($count > 10 ? 10 : $count);

		for ($x=0; $x<$count; $x++)
		{
			$this->Log_Agent_Action($archive_id, $this->server->agent_id, 'canned');
		}

		return TRUE;
	}

	/**
	 * Adds an entry to the email_queue_report table, logging the agent action.
	 *
	 * actions:
	 *   receive   - agent pulls an email (get_next_email)
	 *   associate - agent associates email with an application
	 *   respond   - agent responds to an email (clicks send)
	 *   followup  - agent schedules a followup
	 *   file      - agent clicks 'file without responding'
	 *   queue     - agent sends email to other queue
	 *   canned    - agent loads a canned response into message
	 *   remove    - agent moves email to unassociated table
	 *
	 * @param int $archive_id The archive id
	 * @param int $agent_id The agent's id
	 * @param string $action The action performed
	 */
	public function Log_Agent_Action($archive_id, $agent_id, $action)
	{
		parent::Log_Email_Queue_Action($archive_id, $agent_id, $action);
	}

	/**
	 * Returns the queue name associated with the specified $email_address
	 *
	 * This is determined by the email_response_footer table record, set in the
	 * Admin section under Document Manager > Email Manager
	 *
	 * @param string $company_id
	 * @param string $email_address Returns queue associated with this address
	 * @return string Queue associated with the specified incoming email address
	 * or empty string on failure
	 */
	public function Get_Queue_By_Email_Address($company_id, $email_address)
	{
		// returned values are cached to prevent unnecessary database calls
		if ( isset($this->queue_by_email_address[$email_address]) )
			return $this->queue_by_email_address[$email_address];

		$queue_name = parent::Fetch_Queue_By_Email_Address($company_id, $email_address);

		if ( !empty($queue_name) )
			$this->queue_by_email_address[$email_address] = $queue_name;

		return $queue_name;
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
	public function Get_Company_Id_By_Email_Address($email_address)
	{
		// returned values are cached to prevent unnecessary database calls
		if ( isset($this->company_by_email_address[$email_address]) )
			return $this->company_by_email_address[$email_address];

		$company_id = parent::Fetch_Company_Id_By_Email_Address($email_address);

		if ( FALSE !== $company_id )
			$this->company_by_email_address[$email_address] = $company_id;

		return $company_id;
	}

	/**
	 * Returns the SenderName associated with the specified $queue_name
	 *
	 * This is determined by the email_response_footer table record, set in the
	 * Admin section under Document Manager > Email Manager
	 *
	 * @param string $queue_name
	 * @return string SenderName associated with the specified queue name
	 * or empty string on failure
	 */
	public function Get_Sender_Name_By_Queue($queue_name)
	{
		$docs_config = new Docs_Config($this->server, $this->request);
		$footers = $this->FetchEmailFooters($this->company_id);

		if ( isset($footers[$this->company_id][$queue_name]) )
		{
			return $footers[$this->company_id][$queue_name]['sender_label'];
		}

		return '';
	}

	/**
	 * Removes any application_id from the specified incoming_email_queue record
	 *
	 * @param string $archive_id
	 * @return bool TRUE on success
	 */
	public function Disassociate_Email($archive_id=FALSE)
	{
		$archive_id = ($archive_id === FALSE ? $this->request->archive_id : $archive_id);

		parent::Remove_Application_Id($archive_id);

		$this->Log_Agent_Action($archive_id, $this->server->agent_id, 'disassociate');

		return TRUE;
	}

	/**
	 * Moves specified application to a queue and adds a comment to the app.
	 *
	 * This is used by agents to request actions that they lack the privs to
	 * do themselves.
	 *
	 * @param int $application_id
	 * @param string $transfer_type The type of transfer requested.
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function Transfer_Application($application_id, $transfer_type)
	{
		switch ($transfer_type)
		{
			case 'react':
				// Shouldn't emails about Reacts go to the Customer Service Queue?
				// They would match against an Inactive Paid App so that wouldn't
				// do you any good in the Verification / Underwriting queues. - BR
				//$queue = 'Verification (react)';
				$queue = 'customer_service';
				$comment = 'Email React Request';
				break;

			case 'new_loan':
				$queue = 'verification';
				$comment = 'Email New Loan Request';
				break;

			case 'pay_down':
				$queue = 'account_summary';
				$comment = 'Email Pay Down Request';
				break;

			case 'pay_out':
				$queue = 'account_summary';
				$comment = 'Email Pay Out Request';
				break;

			default:
				// if this happens then the display code must be wrong...
				ECash::getTransport()->Set_Data((object) array('alert_message' => 'The Transfer Action failed.'));
				return FALSE;
		}

		// put the application in the specified queue and add a comment		
		//move_to_automated_queue($queue, $this->request->application_id, '', NULL, NULL);\
		$qm = ECash::getFactory()->getQueueManager();
		$q = $qm->getQueue($queue);
		$qm->moveToQueue($q->getNewQueueItem($this->request->application_id),$queue);
				
		$agent_id   = ECash::getAgent()->getAgentId();
		$company_id = ECash::getCompany()->company_id;
		                        
		Add_Comment($company_id, $this->request->application_id, $agent_id, $comment);
		
		// track agent action
		$agent = ECash::getAgent();
		$agent->getTracking()->add('request_' . $transfer_type . '_from_email_queue', $application_id);
		// so far, so good, tell the browser to display the success alert.
		ECash::getTransport()->Set_Data((object) array('alert_message' => 'The Transfer Action was successful.'));

		return TRUE;
	}


	public function __toString()
	{
		return 'This is an Incoming Email Queue Object.  It may be double violating encapsulation concepts, but it works.';
	}

	public function handle_actions($action) 
	{
        // archive and application id are used frequently.
        @$archive_id = $this->request->archive_id;
        @$application_id = $this->request->application_id;

		$success = true;
		switch ($action) 
		{
            // Email Queue
            case "show_email":
                $success = $this->Get_Email_Document($archive_id);
                break;

            case "associate_with_application":
                $this->Associate_With_Application_Id($archive_id, $application_id);
                $success = $this->Get_Email_Document($archive_id);
                break;

            case "get_next_email":
                $success = $this->Get_Next_Email();
                break;

            case "respond_to_email":
                $success = $this->Get_Email_Response_Data();
                break;

            case "file_without_responding":
                $this->File_Email_Document($archive_id);
                $this->Log_Agent_Action($archive_id, $this->server->agent_id, 'file');
                $success = $this->Get_Next_Email();
                break;

            case "send_to_other_queue":
                $this->Send_To_Other_Queue($archive_id, $this->request->other_queue);
                $success = $this->Get_Next_Email();
                break;

            case "schedule_followup":
                $datetime = date('Y-m-d H:i:s', strtotime($this->request->follow_up_time) );
                $this->Schedule_Followup($archive_id, $datetime);
                Add_Comment($this->server->company_id, $this->request->application_id,
                            $this->server->agent_id, $this->request->follow_up_comment,
                            "followup", $this->server->system_id);
                $success = $this->Get_Next_Email();
                break;

            case "send_email_response":
                if (isset($this->request->cancel) )
                {
                    // user clicked the cancel button, so...
                    $this->Get_Email_Document($archive_id);
                }
                else
                {
                    // send the email response
                    $success = $this->Send_Email_Response();
                }
                break;

            case "remove_from_email_queue":
                $this->Move_To_Unassociated($archive_id);
                $success = $this->Get_Next_Email();
                break;

            case "email_queue_quick_search":
                ECash::getTransport()->Set_Data($this->request);
                $this->Get_Email_Document($archive_id);
				$search = new Search($this->server, $this->request);
                $search->Search();
                ECash::getTransport()->Set_Levels('application', $this->request->module, $this->request->mode, 'email_queue');
                break;

            case "disassociate_email":
                $this->Disassociate_Email();
                $success = $this->Get_Email_Document($archive_id);
                break;
            case "transfer_action":
                $this->Transfer_Application($this->request->application_id, $this->request->transfer_action);
                $success = $this->Get_Email_Document($archive_id);
                break;

            case "add_fee":
                $loan_data = new Loan_Data($this->server);
                $loan_data->Add_Fee($this->request->application_id, $this->request->type);
                $app_data = $loan_data->Fetch_Loan_All($this->request->application_id);
                ECash::getTransport()->Set_Data($app_data);
                ECash::getTransport()->Add_Levels('overview', 'schedule','view');
                break;

			default:
				return false;
		}
        if (!$success) 
        {
        	
        	//GF 5645: Empty customer service email queue was redirecting to collections module, causing problems. fixed!
        	//ECash::getTransport()->Set_Levels('application', 'collections', 'internal');
        	/**
        	 * TODO: FIGURE OUT WHY THIS STUPID FUCKING CODE NEEDS THIS.
        	 * - Seems to be a hack when there is no next email to pull the code needs a view to show instead of email queue view
        	 */
        	
        	ECash::getTransport()->Set_Levels('application', $this->request->module, $this->request->mode, 'search');
        }
		return true;

	}

    public function FetchEmailFooters($company_id)
    {
        $query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
             SELECT
               email_response_footer_id,
               email_incoming,
               email_replyto,
               footer_text,
               company_id,
               queue_name
             FROM
               email_response_footer
             WHERE
               company_id = '{$company_id}'
            ";
		$db = ECash::getMasterDb();
        $result = $db->query($query);

        $footers = array();

        while($row = $result->fetch(PDO::FETCH_OBJ))
        {
            // build footers array -- why is this structured so weird you ask?
            // because this structure will make the javascript much simpler
            $footers[$row->company_id][$row->queue_name]['incoming_emails'][] = $row->email_incoming;
            $footers[$row->company_id][$row->queue_name]['sender_label'] = $row->email_replyto;
            $footers[$row->company_id][$row->queue_name]['text'] = $row->footer_text;
            $footers[$row->company_id][$row->queue_name]['id'] = $row->email_response_footer_id;
        }

        return $footers;
    }


}


?>
