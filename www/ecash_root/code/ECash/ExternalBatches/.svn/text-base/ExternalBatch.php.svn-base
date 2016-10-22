<?php

class ECash_ExternalBatches_ExternalBatch
//External_Batch
{
	protected $format;        // Format, "csv", "xml", "tsv"
	protected $data;          // Array of columns, key => value
	protected $start_date;    // Start of report data
	protected $end_date;      // End of report data
	protected $columns;       // These are columns defined by report
	protected $column_limits; // This defines limits for a column's data for fixed width CSV, TSV, etc
	protected $before_status; // By default, when running an external batch,
							  // It will collect applications in this status
							  // and it will modify them afterwards to $after_status
	protected $after_status;
	protected $dequeue;       // Optionally dequeue application after processing

	protected $application_ids; // This contains all application IDs caught by $this->preprocess

	protected $quoted_headers; // Quote headers (this is stupid)

	protected $sreport_id;
	protected $sreport_data_id;

	protected $sreport_type;
	protected $sreport_data_type;

	protected $headers;
	protected $filename;

	protected $db;

	protected $background_columns;

	protected $company_id;
	protected $company_name_short;
	
	protected $external_batch_report_id;

	protected $log;
	
	protected $progress;
	protected $progress_type;
	protected $progress_percent;
	
	protected $batch_exceptions;
	
	function __construct($db)
	{
		$this->db                = $db;
		$this->format            = NULL;
		$this->data              = NULL;
		$this->start_date        = NULL;
		$this->end_date          = NULL;
		$this->columns           = NULL;
		$this->before_status     = NULL;
		$this->after_status      = NULL;
		$this->application_ids   = NULL;
		$this->column_limits     = NULL;
		$this->quoted_headers    = FALSE;
		$this->external_batch_report_id = NULL;
		$this->background_columns = array(
			'application_id',
			'balance',
			'principal_balance',
			'interest_balance',
			'fee_balance'
		);
		
		$this->log				 = get_log('external_batch_log');
		$this->sreport_id        = NULL;
		$this->sreport_data_id   = NULL;
		$this->sreport_data_ids = array();
		$this->sreport_type      = NULL;
		$this->sreport_data_type = NULL;
		$this->filename          = NULL;
		$this->batch_exceptions = array();
		$this->progress_percent  = 0;
		$this->progress_type     = 'external';
		$this->company_id        = ECash::getCompany()->company_id;
		$this->company_name_short = ECash::getCompany()->name_short;
	}

	
	public function setExternalBatchReportId($external_batch_report_id)
	{
		$this->external_batch_report_id = $external_batch_report_id;
	}
	
	public function output()
	{
		if (!is_array($this->columns) || empty($this->columns))
			throw new Exception('No defined columns for external batch');

		$column_names = array_keys($this->columns);
		// The Decider(tm)
		switch (strtolower($this->format))
		{
			case 'csv':
				return ECash_KeyValueFormatter::exportCSV($this->data, $column_names, $this->headers);
				break;
			case 'tsv':
				return ECash_KeyValueFormatter::exportTSV($this->data, $column_names, $this->headers);
				break;
			case 'xml':
				return ECash_KeyValueFormatter::exportXML($this->data, $column_names, $this->headers);	
				break;
			default:
				$message = "External_Batch->output() called, but no recognized format was specified.  Format specified: {$this->format}";
				$this->log->Write($message);
				throw new Exception($message);
		}
	}

	protected function updateStatus($application_id)
	{
		if ($this->after_status != NULL)
			{
				Update_Status(NULL, $application_id, $this->after_status, null, null, true);
				return true;
			}
		return false;
			
	}
	
	protected function updateProgress($message, $percent)
	{
		if(!$this->progress)
		{
			return null;
		}
		//If the percent is lower than the current progress percent, let's assume that we just want to 
		//increment by that number.
		$this->log->Write("$message");
		$percent = ($percent > $this->progress_percent) ? $percent : $this->progress_percent+$percent;
		if($percent > 100)
		{
			$percent = $this->progress_percent;
		}
		
		$this->progress->update($message,$percent);
		$this->progress_percent = $percent;
	}
	
	public function formatData()
	{
		$this->updateProgress("Formatting records into batch format",5);
		$new_data = array();
		foreach ($this->data as $application) 
		{
			$new_record = array();
			//We're going to treat certain keys as special, these are items that do not belong on a report necessarily,
			//but we should have them associated with the data records for reporting and post-processing.  Regardless
			//of their involvement with the data we're formatting
			foreach ($this->background_columns as $column_name) 
			{
				if(array_key_exists($column_name,$application))
				{
					$new_record[$column_name] = $application[$column_name];
				}
			}
			
			//Populate the empty fields with their default values
			foreach ($this->columns as $column_name => $column_data) 
			{
				$data = $application[$column_name];
				if(!array_key_exists($column_name,$application))
				{
					//Yeah, that's right, I'm making the dangerous assumption that the default value is going to meet
					//all of the other formatting and sanitization criteria. Don't make me regret trusting you!
					$data = array_key_exists('default',$column_data)? $column_data['default']: '';
				}
				else 
				{
					//Truncate the fields to within their allowable limits
					if(array_key_exists('max',$column_data))
					{
						$data = substr($data,0,$column_data['max']);
					}
					//Do other stuff that I'm not sure about.
				}
				
				$new_record[$column_name] = $data;
			}
			
			$new_data[$new_record['application_id']] = $new_record;
		}
		
		$this->data = $new_data;
	}
	
	public function getFilename()
	{
		return $this->filename . '.' . $this->format;
	}

	public function getAppData()
	{
		return $this->data;
	}

	public function getAppIds()
	{
		return $this->application_ids;
	}

	public function getAppCount()
	{
		if (!is_array($this->application_ids) || empty($this->application_ids))
			return 0;
	
		return count($this->application_ids);
	}

	//Queries up the balance based on just the app_ids.  This way we don't have to run processing just to get a balance
	public function getBalance()
	{
		if (!is_array($this->application_ids) || empty($this->application_ids))
		{
			return 0;
		}
		$application_list = implode(',', $this->application_ids);
		$query = "SELECT
							SUM(ea.amount) balance,
							tr.application_id application_id,
							SUM(IF(eat.name_short='principal', ea.amount, 0)) principal_balance,
							SUM(IF(eat.name_short='service_charge', ea.amount, 0)) interest_balance,
							SUM(IF(eat.name_short='fee', ea.amount, 0)) fee_balance
						FROM
							transaction_register AS tr
						JOIN 
							event_amount AS ea USING (event_schedule_id, transaction_register_id)
						JOIN 
							event_amount_type AS eat USING (event_amount_type_id)
						WHERE
						    tr.transaction_status IN ('complete','pending')
						AND tr.application_id IN ({$application_list})
						GROUP BY 
							application_id";
		
		$st = $this->db->query($query);

		while (($row = $st->fetch(PDO::FETCH_ASSOC)))
		{
			$balance = bcadd($balance,$row['balance']);
		}

		
		return $balance;
	}
	
	// This is all that's needed for most things
	public function run($num_apps = null)
	{
		$this->progress 	= new ECash_BatchProgress(ECash::getFactory(), $this->company_id, $this->progress_type);
		$this->updateProgress("Running batch with $num_apps apps", 0);
		try{
			// This gathers information, usually application IDs
			$this->preprocess($num_apps);
			
			// This performs operations on the preprocessed data
			$this->process();
			
			// We've processed the data, let's format it correctly
			$this->formatData();
			
			// This performs actions after a successful batch run
			// by default, this will set to $after_status and dequeue
			// based on the value of $this->dequeue;
			$this->postprocess();
			$this->updateProgress("Batch was successful!", 100);
			}
			catch (Exception $e)
			{
				$this->updateProgress("There was an error with the batch!\n",0);
				$this->updateProgress(var_export($e,true),0);
				$this->log->Write("There was an error with the batch");
				$this->log->Write(var_export($e,true));
			}
			if (count($this->batch_exceptions)) 
			{
				$this->emailException();
			}
	}

	protected function emailException()
	{
		if(count($this->batch_exceptions) > 0)
		{
			$this->log->Write(" " . count($this->batch_exceptions) . " Exceptions found.", LOG_ERR);
			$report_body = "";

			$csv = "Batch Exceptions!\n";

			foreach ($this->batch_exceptions as $e)
			{
				$csv .= "$e.\n";
			}

			$attachments = array(
				array(
					'method' => 'ATTACH',
					'filename' => 'externalbatch-exceptions.csv',
					'mime_type' => 'text/plain',
					'file_data' => gzcompress($csv),
					'file_data_length' => strlen($csv)));

			if(ECash::getConfig()->NOTIFICATION_ERROR_RECIPIENTS != NULL) {
				$recipients = ECash::getConfig()->NOTIFICATION_ERROR_RECIPIENTS;
			}

			if (!empty($recipients))
			{
				$subject = 'Ecash Alert '. strtoupper($this->company_name_short);
				$body = $this->company_name_short . ' - External Batch Exceptions';
				require_once(LIB_DIR . '/Mail.class.php');
				try
				{
					eCash_Mail::sendExceptionMessage($recipients, $body, $subject, array(), $attachments);
				}
				catch (Exception $e)
				{
					$this->log->Write("The External Batch Exception Report Failed to send but failures have been logged.");
				}
			}
		}
	}
	
	
	// IF this function is not overridden. It merely grabs all applications in a specified status
	// and populates their application id in $this->application_ids
	// 04-22-2009 update!   Query now excludes applications that have a scheduled or pending transaction. [W!-04-22-2009][#32068]
	// 06-03-2009 update!  Query now excludes applications that have an existing external batch record, this prevents them from getting picked up twice [W!-06-03-2009][#34991]
	public function preprocess($limit = NULL)
	{
		$this->updateProgress("Gathering {$limit} applications for the batch", 20);
		if ($this->before_status == NULL)
			throw new Exception('$this->preprocess: This function needs to be defined by a child class if no before_status is specified');

		// Determine what before_status's application status id is.
		if (is_array($this->before_status))
		{
            $chain = $this->before_status[0];
            if($this->before_status[1] != null) { $chain .= "::" . $this->before_status[1]; }
            if($this->before_status[2] != null) { $chain .= "::" . $this->before_status[2]; }
            if($this->before_status[3] != null) { $chain .= "::" . $this->before_status[3]; }
            if($this->before_status[4] != null) { $chain .= "::" . $this->before_status[4]; }
		}
		else
			$chain = $this->before_status;

		if (($status_id = Status_Utility::Get_Status_ID_By_Chain($chain)) == NULL)
			throw new Exception('before_status is invalid');

		$data_obj = ECash::getFactory()->getData('Application');
		$app_data = $data_obj->getAppIdsByStatus($chain);
		$application_array = array();
		foreach($app_data as $row)
		{
			$application_array[] = $row['application_id'];	
		}
		$application_list = implode(',', $application_array);
		if(!empty($application_list))
		{
			$query = "
			SELECT DISTINCT
				ap.application_id
			FROM
				application AS ap
			JOIN
				transaction_register AS tr_debit ON (tr_debit.application_id = ap.application_id
				AND tr_debit.amount < 0)
			LEFT JOIN
				event_schedule es ON (es.application_id = ap.application_id
				AND es.event_status = 'scheduled')
			LEFT JOIN
				transaction_register AS tr ON (tr.application_id = ap.application_id
				AND tr.transaction_status = 'pending')
			LEFT JOIN
				ext_collections AS ec ON (ec.application_id = ap.application_id)
			WHERE
				ap.application_id IN ({$application_list})
				AND es.event_schedule_id IS NULL
				AND tr.transaction_register_id IS NULL
				AND ec.ext_collections_id IS NULL
			";

			if ($limit != NULL)
			{
				$query .= "
					LIMIT {$limit}
				";
			}
			$result = $this->db->query($query);
			while (($row = $result->fetch(PDO::FETCH_OBJ)))
			{
				$this->application_ids[] = $row->application_id;
			}
		}
		return TRUE;
	}

	// self documenting code requires no comments
	protected function saveToFile($filename)
	{
		$this->updateProgress("Saving Batch records to file",5);
		return file_put_contents($filename, $this->output());
	}

	// This saves the report as a SReport in the DB
	protected function saveToDb()
	{
		$this->updateProgress("Saving Batch record to the database",10);
		if ($this->sreport_type == NULL || $this->sreport_data_type == NULL)
			throw new Exception('You need to have sreport_type and sreport_data_type defined by a child class');

		$sreport = ECash::getFactory()->getModel('Sreport');		

		if ($this->sreport_id != NULL)
			$sreport->loadBy(array('sreport_id' => $this->sreport_id));
		else
			$sreport->date_created       = date('Y-m-d H:i:s');

		$sreport->company_id         = ECash::getCompany()->company_id;
		$sreport->sreport_start_date = date('Y-m-d H:i:s');
		$sreport->sreport_end_date   = date('Y-m-d H:i:s');

		$sreport_ss = ECash::getFactory()->getModel('SreportSendStatus');
		$sreport_s  = ECash::getFactory()->getModel('SreportStatus');
	
		// Created or current?
		$sreport->sreport_status_id      = $sreport_s->getStatusId('current');
		$sreport->sreport_send_status_id = $sreport_ss->getStatusId('unsent');
	
		$srt = ECash::getFactory()->getModel('SreportType')->getTypeId($this->sreport_type);
		$sdt = ECash::getFactory()->getModel('SreportDataType')->getTypeId($this->sreport_data_type);

		$sreport->sreport_date = date('Y-m-d');

		$sreport->sreport_type_id = $srt;

		$sreport_data = ECash::getFactory()->getModel('SreportData');
	
		if ($this->sreport_data_id != NULL)
			$sreport_data->loadBy(array('sreport_data_id' => $this->sreport_data_id));
		else
			$sreport_data->date_created = date('Y-m-d H:i:s');
	
		$sreport_data->filename           = $this->filename;
		$sreport_data->filename_extension = (isset($this->filename_extension)) ? $this->filename_extension : $this->format;
		$sreport_data->sreport_data       = $this->output();
		$sreport_data->sreport_data_iv	  = md5($this->output());
		
		// Make sure the type for this report exists
		$type = ECash::getFactory()->getModel('SreportType');
		
		$sreport_data->sreport_data_type_id = $sdt;

		$sreport->save();
		$this->sreport_id = $sreport->sreport_id;
		
		$sreport_data->sreport_id = $sreport->sreport_id;
		$sreport_data->save();		
		$this->sreport_data_ids[] = $sreport_data->sreport_data_id;
		$this->updateProgress("Saved Batch as report ID {$sreport->sreport_id}",5);
		return $sreport->sreport_id;
	}

	public function process()
	{
		throw new Exception('Child class (which you should be using, but not enforced by interface) needs to implement process() method');
	}

	// This sets the status to $this->after_status, and deques if $this->dequeue === TRUE
	protected function postprocess()
	{
		$this->updateProgress("Running post-processing on applications",25);
		foreach ($this->application_ids as $application_id)
		{
			if ($this->after_status != NULL)
			{
				Update_Status(NULL, $application_id, $this->after_status, null, null, true);
			}

			if ($this->dequeue === TRUE)
			{
				$qm = ECash::getFactory()->getQueueManager();
				$qm->removeFromAllQueues(new ECash_Queues_BasicQueueItem($application_id));
			}
		}

		return TRUE;
	}

	// Sends out the file via different transport methods
	public function send()
	{
		throw new Exception("This hasn't been written yet");
	}

}


?>
