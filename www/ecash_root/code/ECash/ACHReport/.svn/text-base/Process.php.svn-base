<?php

/**
 * Abstract ACH Return Class
 *
 */
abstract class ECash_ACHReport_Process
{
	protected $log;
	
	protected $report_id;
	
	protected $date;
	protected $parser;
	protected $config;
	protected $transport;
	protected $company_id;
	protected $process_name;
	protected $exceptions;
	
	public function __construct($config)
	{
		$this->log = $config->getLog();
		$this->db = $config->getDB();
		$this->company_id = $config->getCompanyId();
		$this->config = $config;
		$this->process_name = __CLASS__;
		$this->exceptions = new ECash_ACHReport_ACHExceptionsReport($this->db,$this->company_id);
	}

	protected function getProcessState()
	{
		$date = date('Y-m-d',$this->config->getDate());
		$query = "
			SELECT 	state
			FROM	process_log
			WHERE	company_id = {$this->company_id}
			AND     step = {$this->db->Quote($this->process_name)}
			AND		business_day = {$this->db->Quote($date)}	
			ORDER BY date_started desc
			LIMIT 1
		";

		return $this->db->querySingleValue($query);
	}
	
	protected function setProcessState($state)
	{
		
		$step  = $this->process_name;
		$state = strtolower(trim($state));
		$date = date('Y-m-d',$this->config->getDate());
		// If this step is started, it's going to be a new entry..
		if($state == 'started')
		{

			$query = "
				INSERT INTO process_log
					(
						business_day,
						company_id,
						step,
						state,
						date_started,
						date_modified
					)
				VALUES
					(
						{$this->db->Quote($date)},
						{$this->company_id},
						{$this->db->Quote($step)},
						{$this->db->Quote($state)},
						current_timestamp,
						current_timestamp
					)
				ON DUPLICATE KEY UPDATE
					state		= {$this->db->Quote($state)},
					date_modified	= current_timestamp
			";

			$st = $this->db->query($query);
			return $this->db->lastInsertId();
		}
		// Otherwise it'll be an update.  If we don't have the pid already, try to get it.
		elseif ($pid === null)
		{
			$pid = Get_Process_Log_Id($this->db, $this->company_id, $step, $date);
		}

		// If we have the pid do the update, if not return false.
		if(!empty($pid))
		{
			$query = "
				UPDATE process_log
					SET state		= {$this->db->Quote($state)},
					date_modified	= current_timestamp
				WHERE process_log_id = '{$pid}'
				AND   step = '{$step}'
			";
			$this->db->query($query);
			return $pid;
		}
		else
		{
			return false;
		}
		
	}
	
	
	public function storeFile($file, $filename, $report_type)
	{
		//Storing the file
		/**
		 * Converted this to a model since we're already planning on encrypting some of
		 * this data in the very near future. [BR]
		 */
		$ach_report = ECash::getFactory()->getModel('AchReport');
		$ach_report->date_created = Date('Y-m-d');
		$ach_report->date_request = date('Y-m-d',$this->config->getDate());
		$ach_report->company_id = $this->company_id;
		$ach_report->ach_report_request = $filename;
		$ach_report->remote_response = $file;
		$ach_report->remote_response_iv = md5($file);
		$ach_report->report_status = 'received';
		$ach_report->report_type = $report_type;
		$ach_report->content_hash = md5($file);
		$ach_report->save();

		return $ach_report->ach_report_id;
	}
	
	public function parseFile($file_contents, $parser)
	{
		$parser->setExceptionsReport(&$this->exceptions);
		//I'm in ur returns processor, parsing ur returnz.
		return $parser->parseFile($file_contents);
	}
	
	
	protected function addComment($application_id, $comment)
	{
		$application =  ECash::getApplicationByID($application_id);
		$comments = $application->getComments();
		$comments->add($comment,ECash::getAgent()->getAgentId());
	}
	
	/**
	 * Method used for corrections processing to update the application record
	 *
	 * @param int $application_id
	 * @param array $app_update_ary
	 * @return mixed FALSE on failure, rowcount on success
	 */
	protected function updateApplicationInfo($application_id, $app_update_ary)
	{
		$agent_id = Fetch_Current_Agent();

		if ( empty($application_id) || count($app_update_ary) < 1 )
		{
			return false;
		}

		$set_phrases	= "";
		$where_phrases	= "";

		foreach ($app_update_ary as $key => $value)
		{
			if (strlen($set_phrases) > 0)
			{
				$set_phrases .= ",
				";
			}
			$set_phrases .= " $key = " . $this->db->quote($value);

			if (strlen($where_phrases) > 0)
			{
				$where_phrases .= "
				OR ";
			}
			$where_phrases .= " $key <> " . $this->db->quote($value);
		}

		$query = "
    	    -- eCash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
			UPDATE application
				SET
					modifying_agent_id = '{$agent_id}',
					$set_phrases
				WHERE
						application_id	= $application_id
					AND	company_id		= {$this->company_id}
					AND (
							$where_phrases
						)
		";
		$result = $this->db->Query($query);
		return $result->rowCount();
	}
	
		/**
	 * Simple method for saving an ACH Exception
	 *
	 * This will take the associative array for the return record
	 * and map it to the data model.
	 *
	 * @param array $report_data
	 */

	public function setProcessName($process_name)
	{
		$this->process_name = $process_name;
	}
	
	protected function retrieveFile($transport, $filename)
	{
		for ($i = 0; $i < 5; $i++) 
		{ // make multiple request attempts
			try 
			{
				$file = $transport->getFile($filename);
				break;
			}
			catch (Exception $e)
			{
				$this->log->Write('(Try '.($i + 1).') '.$e->getMessage());
				//echo '(Try '.($i + 1).') '.$e->getMessage();
				$report_response = '';
				$file = false;
				sleep(5);
			}
		}

		return $file;
	}
	
	/**
	 * Attempts to get the application_id based on the ach record
	 *
	 * @param int $ach_id
	 * @return array('application_id', 'company_id') on success, NULL on failure
	 */
	protected function getApplicationID ($record)
	{
		$ach_id = $record['ach_id'];
		$reason_code = $record['reason_code'];
		$application_id = NULL;
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
						SELECT
							application_id,
							company_id
						FROM
							ach
						WHERE
							ach_id		= '$ach_id' ";

		$result = $this->db->Query($query);
		if (!$row = $result->fetch(PDO::FETCH_OBJ))
		{
			$exception = new ECash_ACHReport_ACHException();
			$this->log->Write("$ach_id does not appear to be associated with any application");
			$exception->ach_id = $ach_id;
			$exception->reason_code = $reason_code;
			$exception->details = "$ach_id does not appear to be associated with any application";
			$this->exceptions->addException($exception);
			return null;
		}

		return array($row->application_id, $row->company_id);
	}
	
	/**
	 * Update the status of an ach_report
	 *
	 * @param int $ach_report_id
	 * @param string $status
	 * @return BOOL always returns true
	 */
	protected function updateACHReportStatus ($ach_report_id, $status)
	{
		$ach_report = ECash::getFactory()->getModel('AchReport', $this->db);
		$ach_report->loadBy(array('ach_report_id' => $ach_report_id));

		$ach_report->report_status = $status;
		$ach_report->save();

		return true;
	}
	/*
		Builds and returns the application service client
		@return ECash_ApplicationService_Appclient
	*/
	public function getAppClient()
	{
		if(empty($this->app_client))
		{
			$this->app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
		}
		return $this->app_client;
	}
	/*
		Prepares and sends data to the appservice for BankInfo
	*/
	public function SendChangesToAppService($update_array)
	{
		$array_Chunks = $this->SplitIntoSizedArrays($update_array, 1000);
		foreach($array_Chunks as $send_array)
		{
			$this->bulkUpdateBankInfo($send_array);
		}
	
	}
	/*
		Prepares data to be sent to the appservice for BankInfo
	*/
	protected function SplitIntoSizedArrays($array, $step){
	    $new = array();
	 
	    $k = 0;
	    foreach ($array as $app_id => $values){
				
		$values['application_id'] = $app_id;
		if(count($new[$k]) >= $step)
		{
			$k++;
		}
			$new[$k][$app_id] = $values;
	    }
	 
	    return $new;
	}
	/*
		Bulk updates changes to application to the application service
		@param array array of application keyed column data that is to be changed
		array([appid] => array([column] => value,[column]=> value), [appid] => array([column] => value,[column]=> value))

	*/
	public function bulkUpdateBankInfo(array $updateArray)
	{
		$this->getAppClient()->bulkUpdateBankInfo($updateArray);

	}
}

?>
