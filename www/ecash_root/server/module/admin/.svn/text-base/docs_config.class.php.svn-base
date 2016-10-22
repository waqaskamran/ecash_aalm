<?php
/**
 * <CLASSNAME>
 * <DESCRIPTION>
 *
 * Created on Jan 16, 2007
 *
 * @package <PACKAGE>
 * @category <CATEGORY>
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once LIB_DIR . "Document/Document.class.php";
require_once eCash_Document_DIR . "Type.class.php";
require_once COMMON_LIB_ALT_DIR . "prpc/client.php";

class Docs_Config {

	private $server;
	private $request;
	private $documents;
	private $acl;

	public function __construct(Server $server, $request)
	{
	        $this->server = $server;
	        $this->request = $request;
	        $this->documents = eCash_Document::Singleton($server, $request);
			$this->acl = ECash::getACL();

	        $obj = new stdClass;
	        $obj->document_list = eCash_Document_Type::Get_Raw_Document_List($this->server);
	        $obj->package_list = eCash_Document_Type::Get_Raw_Package_List($this->server);

			$request_obj = new stdclass;
			foreach($request as $key => $value)
        	{
				$request_obj->$key = $value;
			}
			ECash::getTransport()->Set_Data($request_obj);
			ECash::getTransport()->Set_Data($obj);

		if (isset($request->view)) {
			// Load screen data if no updated are being made
			switch (TRUE)
			{
				case ('printing_queue' == $request->view && $request->action != 'update_printing_queue'):
					$this->DisplayPrintingQueue();
					break;

				case ('email_responses' == $request->view && empty($request->action)):
					$this->DisplayEmailResponses();
					break;

				case ('email_footers' == $request->view && empty($request->action)):
					$this->DisplayEmailFooters();
					break;
			}
		}

	}

	public function Update_Sort()
	{
//		var_dump($this->request);
		$data = new StdClass;
		$data->order = $this->request->option;
		$data->view = 'sort_order';

		$this->documents->Update_List_Sort($this->request->document_sort_order, str_replace("_documents","",$this->request->option));
		$data->document_list = eCash_Document_Type::Get_Raw_Document_List($this->server);
		ECash::getTransport()->Set_Data($data);

	}

	public function Update_Package()
	{
//		var_dump($this->request);
		$data = new StdClass;
		$data->mode = 'docs_config';
		$data->view = 'packages';

		$request_obj = new stdclass;
		foreach($this->request as $key => $value)
		{
			$request_obj->$key = $value;
		}
		$this->documents->Update_Document_Package($request_obj);
		$data->package_list = eCash_Document_Type::Get_Raw_Package_List($this->server);
		ECash::getTransport()->Set_Data($data);


	}

	public function Update_Document()
	{
//		var_dump($this->request);
		$data = new StdClass;
		$data->mode = 'docs_config';
		$data->view = 'documents';

		$this->documents->Update_List_Document($this->request);
		$data->document_list = eCash_Document_Type::Get_Raw_Document_List($this->server);
		ECash::getTransport()->Set_Data($data);

	}

	public function Delete_Package()
	{
//		var_dump($this->request);
		$data = new StdClass;
		$data->mode = 'docs_config';
		$data->view = 'packages';

		$this->documents->Delete_Document_Package($this->request->document_package_id);

	    $data->package_list = eCash_Document_Type::Get_Raw_Package_List($this->server);
	    ECash::getTransport()->Set_Data($data);


	}

	/**
	 * Loads data for the Printing Manager screen into [transport]
	 *
	 * @return bool TRUE on completion
	 */
	public function DisplayPrintingQueue()
	{
		$query = "
			 SELECT
			   printer_id,
			   printer_name,
			   printer_host,
			   queue_name,
			   document_highwater_id
			 FROM
			   printer
			 WHERE
			  company_id = {$this->server->company_id} AND
			  active_status = 'active'
			";

		$fields = array();
		$db = ECash::getMasterDb();
		$res = $db->query($query);

		$fields['reprint_lines'] = '';

		while($row = $res->fetch(PDO::FETCH_OBJ))
		{
			$select = (($row->printer_id == $this->request->reprint_line) ? ' selected' : '');
			$fields['dd_reprint_lines'] .= "<option value=\"{$row->printer_name}\"{$select}>{$row->printer_name}</option>\n";
			$line = array(
			              'line'    => $row->printer_id,
			              'name'    => $row->printer_name,
			              'queue'   => $row->queue_name,
			              'printer' => $row->printer_host
			             );
			$fields['lines'][] = $line;
		}

		$queue_name_array = $this->Fetch_Condor_Queue_List();
		$printer_host_array = $this->Fetch_Printer_List();

		// build queue name array and dropdown for date range reprint
		$fields['queue_list'] = '';
		for ($x=0; $x<count($queue_name_array); $x++)
		{
			$fields['queues'][] = $queue_name_array[$x];
			$fields['queue_list'] .= '<option value="' . $queue_name_array[$x]
								  . '">' . $this->Beautify_Text($queue_name_array[$x], true) . '</option>';
		}

		// build printer host array and dropdown for id range reprint
		$fields['printer_list'] = '';
		for ($x=0; $x<count($printer_host_array); $x++)
		{
			$fields['printers'][] = $printer_host_array[$x];
			$fields['printer_list'] .= '<option value="' . $printer_host_array[$x]
								    . '">' . $this->Beautify_Text($printer_host_array[$x]) . '</option>';
		}

		$data = new StdClass;
		$data->printing_queue_fields = $fields;
		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}

	/**
	 * Simple function to strip the underscores and upper-case the words
	 *
	 * @param string $val - String to beautify
	 * @param bool $all_upper - Use all uppercase words
	 * @return string
	 */
	public function Beautify_Text($val, $all_upper = false)
	{
		$val = preg_replace('/_/', ' ', $val);

		if($all_upper)
			return strtoupper($val);
		else
			return ucwords($val);
	}

	/**
	 * Updates printing queue data (displayed on Printing Manager screen)
	 *
	 * @return bool TRUE on completion
	 */
	public function UpdatePrintingQueue()
	{
		$fields = get_object_vars($this->request);
		$db = ECash::getMasterDb();

		foreach ($fields['line_printer_name'] as $printer_id => $ignore)
		{
			$printer_name = $fields['line_printer_name'][$printer_id];
			$queue_name = $fields['line_queue_name'][$printer_id];
			$printer_host = $fields['line_printer_host'][$printer_id];

			if(!empty($printer_name))
			{
				$query = "
					 UPDATE
					   printer
					 SET
					   printer_name = '{$printer_name}',
					   printer_host = '{$printer_host}',
					   queue_name = '{$queue_name}'
					 WHERE
					   printer_id = {$printer_id}
					";
			}
			else
			{
				$query = "
					 DELETE FROM printer WHERE printer_id = {$printer_id}
					";
			}
			$res = $db->query($query);
		}

		if ( !empty($fields['add_line_printer_name']) )
		{
			$printer_name = $fields['add_line_printer_name'];
			$queue_name = $fields['add_line_queue_name'];
			$printer_host = $fields['add_line_printer_host'];

			$query = "
					 INSERT INTO printer
					   (
					    company_id,
					    printer_id,
					    printer_name,
					    printer_host,
						queue_name,
					    date_created
					   )
					 VALUES
					   (
					    {$this->server->company_id},
        	            {$printer_id},
					    '{$printer_name}',
					    '{$printer_host}',
					    '{$queue_name}',
					    '" . date('Y-m-d H:i:s') . "'
					   )
					";
			$res = $db->query($query);
		}

		$data = new StdClass;
		$data->mode = 'docs_config';
		$data->view = 'printing_queue';
		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}

	/**
	 * Reprints documents based on specified ID span
	 *
	 * @return bool TRUE on completion
	 */
	public function ReprintDocumentsById()
	{

		$data = new StdClass;
		$data->mode = 'docs_config';
		$data->view = 'printing_queue';
		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}

	/**
	 * Reprints documents based on specified Date/Time span
	 *
	 * @return bool TRUE on completion
	 */
	public function ReprintDocumentsByDate()
	{

		$data = new StdClass;
		$data->mode = 'docs_config';
		$data->view = 'printing_queue';
		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}

	/**
	 * Loads data for the Email Responses screen into [transport]
	 *
	 * @return bool TRUE on completion
	 */
	public function DisplayEmailResponses($company_id = NULL)
	{
		$query = "
			 SELECT
			   email_response_id,
			   response_name,
			   response_text,
			   company_id,
			   active_status
			 FROM
			   email_response";

		if (NULL !== $company_id && is_numeric($company_id) )
		{
			$query .= "
			 WHERE
			   company_id = {$company_id}
			";
		}
		$db = ECash::getMasterDb();

		$res = $db->query($query);

		$fields = array();
		$fields['last_action'] = isset($this->request->action) ? $this->request->action : null;
		$fields['responses_list']  = '';
		$fields['responses'] = array();
		$x=0;
		while ($row = $res->fetch(PDO::FETCH_OBJ))
		{
			$fields['responses'][] = array(
											'id' => $row->email_response_id,
											'name' => $row->response_name,
											'text' => $row->response_text,
											'status' => $row->active_status,
											'company_id' => $row->company_id
										  );
			$fields['responses_list'] .= "<option value=\"{$x}\">".substr($row->response_name, 0, 24)."</option>\n";
			$x++;
		}

		$company_map = Fetch_Company_map();
		$active_company_short = $company_map[$this->server->company_id];
		$default_name_short = (empty($this->request->response_name_prefix) ? $active_company_short : strtolower($this->request->response_name_prefix) );
		$fields['companies_list'] = '';
		$fields['companies'] = array();
		$companies = $this->Get_Permitted_Companies();
		foreach ( $companies as $comp )
		{
			$fields['companies'][] = array(
											'id' => $comp->company_id,
											'name' => $comp->name,
											'prefix' => strtoupper($comp->name_short)
										  );
			$fields['companies_list'] .= "<option value=\"" . $comp->company_id . "\"" . ($comp->name_short == $default_name_short ? ' selected' : '') . ">" . $comp->name . "</option>\n";
		}

		$data = new StdClass;
		$data->email_responses_fields = $fields;
		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}

	/**
	 * Adds email response data
	 *
	 * @return bool TRUE on completion
	 */
	public function AddEmailResponse()
	{
		$db = ECash::getMasterDb();
	 	$response_name = $db->quote($this->request->response_name_prefix . ' ' . $this->request->response_name);
		$response_text = $db->quote($this->request->response_text);
		$company_id    = $this->request->response_company;
		$active_status = ($this->request->response_status == 'active' ? 'active' : 'inactive');

		$date_created  = date('Y-m-d H:i:s');

		$query = "
			 INSERT INTO email_response
			   (
			    response_name,
			    response_text,
			    company_id,
			    active_status,
			    date_created
			   )
			 VALUES
			   (
			     {$response_name},
			     {$response_text},
			     {$company_id},
			    '{$active_status}',
			    '{$date_created}'
			   )
			";

		$db->exec($query);

		$data = new StdClass;
		$data->mode = 'docs_config';
		$data->view = 'email_responses';
		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}

	/**
	 * Modifies email response data
	 *
	 * @return bool TRUE on completion
	 */
	public function ModifyEmailResponse()
	{
		$db = ECash::getMasterDb();
		$email_response_id = $this->request->email_response_id;
	 	$response_name     = $db->quote($this->request->response_name_prefix . ' ' . $this->request->response_name);
		$response_text     = $db->quote($this->request->response_text);
		$company_id        = $this->request->response_company;
		$active_status     = ($this->request->response_status == 'active' ? 'active' : 'inactive');
		$date_created      = date('Y-m-d H:i:s');

		$query = "
			 UPDATE
			   email_response
			 SET
			   response_name = {$response_name},
			   response_text = {$response_text},
			   company_id = {$company_id},
			   active_status = '{$active_status}'
			 WHERE
			   email_response_id = {$email_response_id}
			";

		$res = $db->query($query);

		$data = new StdClass;
		$data->mode = 'docs_config';
		$data->view = 'email_responses';
		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}

	/**
	 * Remove email response data
	 *
	 * @return bool TRUE on completion
	 */
	public function RemoveEmailResponse()
	{
		$email_response_id = $this->request->email_response_id;

		$query = "
			 DELETE FROM
			   email_response
			 WHERE
			   email_response_id = {$email_response_id}
			";

		$res = ECash::getMasterDb()->exec($query);

		$data = new StdClass;
		$data->mode = 'docs_config';
		$data->view = 'email_responses';
		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}

	public function Update_Document_Process()
	{
		$db = ECash::getMasterDb();
		$company_id = $this->server->company_id;
		$system_id = $this->server->system_id;
		$current_application_status_id = $this->request->current_application_status_id;
		$application_status_id = $this->request->application_status_id;
		$new_documents = $this->request->document_list;
		$query = "
			DELETE FROM document_process
			WHERE
			document_list_id IN (
				SELECT document_list_id
				FROM document_list
				WHERE company_id={$company_id}
				AND system_id={$system_id}
				)
			AND application_status_id={$application_status_id}
			AND current_application_status_id={$current_application_status_id}
		";

		$db->Query($query);

		if(is_array($new_documents))
		{
			foreach($new_documents as $document_list_id)
			{
				$query = "
					INSERT INTO document_process (date_modified, date_created, document_list_id, application_status_id, current_application_status_id)
					VALUES (now(), now(), {$document_list_id}, {$application_status_id}, {$current_application_status_id})
				";
				$db->Query($query);
			}
		}
		return true;
	}

	/**
	 * Makes a PRPC call to the Print Manager and returns a list of printers
	 *
	 * @return array $printer_list
	 */
	public function Fetch_Printer_List()
	{
		$client = new Prpc_Client(ECash::getConfig()->PRINT_MANAGER_URL);
		$client->_prpc_use_pack = PRPC_PACK_NO;

		$client->Set_Company($this->server->company);
		return $client->Fetch_Printer_List();
	}

	/**
	 * Makes a PRPC call to the Print Manager and sends a request
	 * to print a document on a specified printer.
	 *
	 * @param string $printer_name
	 * @param integer $document_id
	 */
	public function Print_Document($printer_name, $document_id)
	{
		if(! is_numeric((string) $document_id))
			throw new Exception("Invalid document ID passed!");

		$printer_list = $this->Fetch_Printer_List();
		if(! in_array($printer_name, $printer_list))
			throw new Exception("Invalid Printer name '$printer_name' specified!");

		$client = new Prpc_Client(ECash::getConfig()->PRINT_MANAGER_URL);
		$client->_prpc_use_pack = PRPC_PACK_NO;

		$client->Set_Company($this->server->company);
		return $client->Print_Document($printer_name, $document_id);

	}

	/**
	 * Reprint documents based on a date/time range and a queue name
	 *
	 * @return boolean
	 */
	public function Reprint_Date_Range()
	{

		$queue_name = $this->request->reprint_queue;

		$start_date = $this->request->start_year . "/" . $this->request->start_month . "/" . $this->request->start_day;
		$start = $start_date . " " . $this->request->start_time;
		$start = date('Ymdhis', strtotime($start));

		$end_date = $this->request->end_year . "/" . $this->request->end_month . "/" . $this->request->end_day;
		$end = $end_date . " " . $end_time = $this->request->end_time;
		$end = date('YmdHis', strtotime($end));

		$client = new Prpc_Client(ECash::getConfig()->PRINT_MANAGER_URL);
		$client->_prpc_use_pack = PRPC_PACK_NO;

		// Set the displays
		$data = new StdClass;
		$data->mode = 'docs_config';
		$data->view = 'printing_queue';
		ECash::getTransport()->Set_Data($data);

		// Do the call
		$client->Set_Company($this->server->company);
		return $client->Reprint_Date_Range($this->server->company, $start, $end, $queue_name);
	}

	/**
	 * Reprint a Numbered Range of Document ID's
	 *
	 * If a document is not successfully retrieved or there is a non-fatal
	 * error while printing, the process will continue to try to print each
	 * consecutive document_id
	 *
	 * @return boolean
	 */
	public function Reprint_Numbered_Range()
	{
		$start_number = $this->request->reprint_from;
		$end_number   = $this->request->reprint_to;
		$printer_name = $this->request->reprint_printer;

		$client = new Prpc_Client(ECash::getConfig()->PRINT_MANAGER_URL);
		$client->_prpc_use_pack = PRPC_PACK_NO;

		// Set the displays
		$data = new StdClass;
		$data->mode = 'docs_config';
		$data->view = 'printing_queue';
		ECash::getTransport()->Set_Data($data);

		// Do the call
		$client->Set_Company($this->server->company);
		return $client->Reprint_Numbered_Range($this->server->company, $start_number, $end_number, $printer_name);
	}

	/**
	 * Makes a PRPC call to Condor and returns an array of queue names
	 *
	 * @return array
	 */
	public function Fetch_Condor_Queue_List()
	{
		$client = new Prpc_Client(ECash::getConfig()->CONDOR_SERVER);
		return $client->Get_Queue_Names();
	}

		/**
	 * Retrieves the list of documents from Condor
	 *
	 * If $start_date or $end_date are not null, the list will return all documents
	 * within a date range
	 *
	 * @param string $start_date
	 * @param string $end_date
	 * @param string $queue_name
	 * @param boolean $unprinted_only
	 * @return array of objects containing document data
	 */
	public function Retrieve_Document_List($start_date = null, $end_date = null, $queue_name = null, $unprinted_only = true)
	{
		$client = new Prpc_Client(ECash::getConfig()->CONDOR_SERVER);
		$list = $client->Get_Incoming_Documents($start_date, $end_date, $queue_name, $unprinted_only);

		return $list;

	}

	/**
	 * Loads email response footers data into the transport object
	 *
	 */
	public function DisplayEmailFooters()
	{
		$data = new stdClass();

		// build company dropdown options
		$default_name_short = (empty($this->request->footer_company) ? '3' : strtolower($this->request->footer_company) );
		$data->companies_list = '';
		$company_ids = array();
		$companies = $this->Get_Permitted_Companies(TRUE);
		foreach ( $companies as $comp )
		{
			$data->companies_list .= "<option value=\"" . $comp->company_id . "\">" . $comp->name . "</option>\n";
		}

		$data->email_footers = $this->FetchEmailFooters();

		ECash::getTransport()->Set_Data($data);
	}

	/**
	 * Returns array of footer objects
	 *
	 * TODO:
	 * Currently this table has a single record for each email_incoming.
	 * The other columns are duplicated. Yes this is aweful, the result of
	 * various changes made under a deadline. There needs to be a table
	 * dedicated to email_incoming, as multiple values exist per footer record.
	 *
	 * @return array of footer objects
	 */
	public function FetchEmailFooters()
	{
		$companies = $this->Get_Permitted_Companies();
		foreach ( $companies as $comp )
		{
			$company_ids[] = $comp->company_id;
		}

		$query = "
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
			   company_id IN (" . implode(",", $company_ids) . ")
			";

		$result = ECash::getMasterDb()->query($query);

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

	/**
	 * Updates an email response footer
	 *
	 */
	public function UpdateEmailFooter()
	{
		$db = ECash::getMasterDb();
		$footer_id = $this->request->footer_id;
		$email_replyto = $db->quote($this->request->response_sender_label);
		$footer_text = $db->quote($this->request->footer_text);
		$company_id = $this->request->footer_company;
		$queue_name = $this->request->footer_queue;

		if ( is_numeric($footer_id) )
		{
			$query = "
					 UPDATE
					  email_response_footer
					 SET
					  email_replyto = {$email_replyto},
					  footer_text = {$footer_text}
					 WHERE
					  company_id = '{$company_id}'
					 AND
					  queue_name = '{$queue_name}'
					";
		}
		else
		{
			$query = "
					 INSERT INTO email_response_footer
					   (
					    email_replyto,
					    footer_text,
					    company_id,
						queue_name,
					    date_created
					   )
					 VALUES
					   (
					    {$email_replyto},
					    {$footer_text},
					    '{$company_id}',
					    '{$queue_name}',
					    '" . date('Y-m-d H:i:s') . "'
					   )
					";
		}

		$result = $db->query($query);


		$data = new StdClass;
		$data->mode = 'docs_config';
		$data->view = 'email_footers';
		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}

	/**
	 * Adds incoming email address to footer record.
	 *
	 */
	public function AddIncomingEmailAddress()
	{
		$db = ECash::getMasterDb();
		$email_incoming = $db->quote($this->request->add_email_address);
		$email_replyto = $db->quote($this->request->response_sender_label);
		$footer_text = $db->quote($this->request->footer_text);
		$company_id = $this->request->footer_company;
		$queue_name = $this->request->footer_queue;

		$query = "
					 INSERT INTO email_response_footer
					   (
				        email_incoming,
					    email_replyto,
					    footer_text,
					    company_id,
						queue_name,
					    date_created
					   )
					 VALUES
					   (
				        {$email_incoming},
					    {$email_replyto},
					    {$footer_text},
					    '{$company_id}',
					    '{$queue_name}',
					    '" . date('Y-m-d H:i:s') . "'
					   )
					";


		$result = $db->query($query);


		$data = new StdClass;
		$data->mode = 'docs_config';
		$data->view = 'email_footers';
		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}

	/**
	 * Removes incoming email address from footer record.
	 *
	 */
	public function RemoveIncomingEmailAddress()
	{
		$db = ECash::getMasterDb();
		$email_incoming = $db->quote($this->request->incoming_email_addresses);
		$company_id = $this->request->footer_company;
		$queue_name = $this->request->footer_queue;

		$query = "
					 DELETE FROM
					  email_response_footer
					 WHERE
					  email_incoming = {$email_incoming}
					 AND
					  company_id = '{$company_id}'
					 AND
					  queue_name = '{$queue_name}'
					";


		$db->exec($query);


		$data = new StdClass;
		$data->mode = 'docs_config';
		$data->view = 'email_footers';
		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}

	/**
	 * Returns an array of permitted company objects
	 *
	 * @return array of company objects
	 */
	protected function Get_Permitted_Companies($ecash3_only=FALSE)
	{
		$companies = array();
		$acl_companies = ECash::getFactory()->getReferenceList('Company');
		foreach($acl_companies as $company)
		{
			if( $company->active_status == 'active' &&
				$this->acl->Acl_Access_Ok("admin", $company->company_id) &&
				$this->acl->Acl_Access_Ok("privs", $company->company_id) )
			{
				$companies[] = $company;
			}
		}

		return $companies;
	}

}

?>
