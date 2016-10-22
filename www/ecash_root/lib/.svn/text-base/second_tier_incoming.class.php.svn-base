<?php

/**
 * Abstract Second_Tier_Incoming Return Class
 *
 */
require_once(LIB_DIR . "second_tier_incoming_interface.iface.php");
require_once(LIB_DIR . "Ach/ach_utils.class.php");

abstract class Second_Tier_Incoming implements Second_Tier_Incoming_Interface 
{	
	
	protected $log;
	protected $server;
	protected $mysqli;
	protected $report_type;
	protected $ach_utils;
	protected $holiday_ary;
	protected $paydate_obj;
	protected $paydate_handler;
	protected $biz_rules;
	protected $business_day;
	
	private static $RS		  = "\n";
	
	public function __construct(Server $server)
	{
		$this->server			= $server;
		$this->db = ECash::getMasterDb();
		$this->company_id		= $server->company_id;
		$this->company_abbrev	= strtolower($server->company);
		// Set up separate log object for collections purposes
		$this->log = new Applog(APPLOG_SUBDIRECTORY.'/collections', APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, strtoupper($this->company_abbrev));
		$this->ach_utils = new ACH_Utils($server);
	}
	
	public function Process_Second_Tier_Incoming_Update($end_date, $override_start_date = NULL)
	{
		return $this->Process_Second_Tier_Incoming($end_date, 'updates', $override_start_date);
	}
	
	protected function Validate_Name ($value, &$normalized_name_last, &$normalized_name_first)
	{
		$name_ary = explode(" ", $value);
		$name_first	= strtolower(trim($name_ary[0]));
		$name_last	= strtolower(trim($name_ary[1]));
		if ( strlen($name_last ) >  1	&&
		     strlen($name_last ) < 50	&&
		     strlen($name_first) >  0	&&
		     strlen($name_first) < 50		)
		{
			$normalized_name_last	= $name_last;
			$normalized_name_first	= $name_first;
			return true;
		}
		
		return false;
	}
	
	
	public function Fetch_Second_Tier_File($type, $start_date)
	{
		return $this->Send_Report_Request($start_date, $type);
	}
	
	public function Send_Report_Request($start_date, $report_type)
	{
		$return_val = array();
		$transport_type = ECash::getConfig()->SECOND_TIER_INCOMING_TRANSPORT_TYPE;
		$batch_server   = ECash::getConfig()->SECOND_TIER_INCOMING_SERVER;
		$batch_login    = ECash::getConfig()->SECOND_TIER_INCOMING_LOGIN;
		$batch_pass     = ECash::getConfig()->SECOND_TIER_INCOMING_PASS;
		$transport_port   = ECash::getConfig()->SECOND_TIER_INCOMING_SERVER_PORT;
		
		for ($i = 0; $i < 5; $i++) { // make multiple request attempts
			try {
				$transport = ACHTransport::CreateTransport($transport_type, $batch_server,  $batch_login, $batch_pass, $transport_port);
			
				if ($transport->hasMethod('setDate')) 
				{
					$transport->setDate($start_date);
				}
			
				if ($transport->hasMethod('setCompanyId')) 
				{
					$transport->setCompanyId($this->company_id);
				}
				
				switch($report_type)
				{
					case "updates":	
						$prefix = ECash::getConfig()->SECOND_TIER_INCOMING_URL_PREFIX;
						$suffix = ECash::getConfig()->SECOND_TIER_INCOMING_URL_SUFFIX;
						$url = ECash::getConfig()->SECOND_TIER_INCOMING_URL;
						
						if($prefix != NULL && $suffix != NULL)
						{
							$url = $prefix.date("mdY",strtotime($start_date)).$suffix;
						}
						
						break;
					//OK, fine, we don't have any other cases. This is just here because I ripped the ACH stuff off. You wanna fight about it?  
					
				}
			
				$report_response = '';
				$report_success = $transport->retrieveReport($url, $report_type, $report_response);
				
				if (!$report_success) {
					$this->log->write('(Try '.($i + 1).') Received an error code. Not trying again.');
					$this->log->write('Error: '.$report_response);
				}
				break;
			} catch (Exception $e) {
				$this->log->write('(Try '.($i + 1).') '.$e->getMessage());
				$report_response = '';
				$report_success = false;
				sleep(5);
			}
		}
		
		//if ($report_success && strlen($report_response) > 0) 
		if ($report_success) 
		{
			$this->log->Write("Successfully retrieved '".strlen($report_response)."' byte(s) $report_type report for $start_date.");
			$this->Insert_Second_Tier_Incoming_Response($report_type,$url,'csv', $report_response, $start_date);

			return true;

		} 
		else 
		{
			$this->log->Write("Second_Tier_Incoming '$report_type' report: was unable to retrieve report from $url", LOG_ERR);
			return false;
		}
	}
	
	public function Fetch_Report($start_date, $report_type)
	{
		$this->report_type = $report_type;
		$type = "second_tier_incoming_$report_type";
		
		$status      = new ECash_Status('Sreport',$this->agent_id);
		$send_status = new ECash_Status('sreport',$this->agent_id,'sreport_send_status');
		$report = ECash::getFactory()->getModel('Sreport');
		
		$sreport_status      = $status->getStatus('created');
		$sreport_send_status = $send_status->getStatus('received');
		
		$report->loadBy(array('sreport_date' => $start_date, 
							  'sreport_send_status_id' => $sreport_send_status,
							  'sreport_status_id' => $sreport_status,
							  'sreport_type_id' => ECash::getFactory()->getModel('SreportType')->getTypeId($type) ));
		
		
		$report_data = ECash::getFactory()->getModel('SreportData');
		$report_data->loadBy(array('sreport_id' => $report->sreport_id));
		return $report_data;
	}

	public function Parse_Report_Batch ($return_file)
	{
		
		// Split file into rows
		$return_data_ary = explode(self::$RS, $return_file);

		$parsed_data_ary = array();
		$i = 0;

		foreach ($return_data_ary as $line)
		{
			
			if ( strlen(trim($line)) > 0 )
			{
				
				$this->log->Write("Parse_Report_Batch():$line\n");
				//  Split each row into individual columns
				
				$matches = array();
				preg_match_all('#(?<=^"|,")(?:[^"]|"")*(?=",|"$)|(?<=^|,)[^",]*(?=,|$)#', $line, $matches);
				$col_data_ary = $matches[0];
				
				$parsed_data_ary[$i] = array();
				foreach ($col_data_ary as $key => $col_data)
				{
					// Apply column name map so we can return a friendly structure
					$parsed_data_ary[$i][$this->return_file_format[$key]] = str_replace('"', '', $col_data);
				}

				$i++;
			}
		}
		return $parsed_data_ary;
	}
	
	protected function Insert_Second_Tier_Incoming_Response($type, $filename = '', $extension = '', $contents, $start_date)
	{
		$type = strtolower("second_tier_incoming_$type");
		//Get status classes for easy status lookup, update, and history writing
		$status      = new ECash_Status('Sreport',$this->agent_id);
		$send_status = new ECash_Status('sreport',$this->agent_id,'sreport_send_status');

		//Obsolete current records
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			SELECT 
				sr.sreport_id
			FROM sreport sr
			JOIN sreport_data srd ON srd.sreport_id = sr.sreport_id
			WHERE sr.sreport_type_id = ".ECash::getFactory()->getModel('SreportType')->getTypeId($type)."
			AND srd.sreport_id = sr.sreport_id
			AND sr.sreport_date = {$this->db->quote($start_date)}
			AND sr.company_id = {$this->company_id}
			";
		$result = $this->db->Query($query);
		$count = $result->rowCount();
		$results = array();
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$status->setStatus('obsolete',$row['sreport_id']);
		}
		////////////////////////////////////////
		
		
		

		//Insert Report record
		$report = ECash::getFactory()->getModel('Sreport');

		$report->company_id         = $this->company_id;
		$report->sreport_start_date = $start_date;
		$report->sreport_end_date   = $start_date;
		$report->sreport_date       = $start_date;
		$report->sreport_type_id	= ECash::getFactory()->getModel('SreportType')->getTypeId($type);

		$report->sreport_status_id      = $status->getStatus('created');
		$report->sreport_send_status_id = $send_status->getStatus('received');
		$report->insert();

		//$this->log->Write(" {$report->sreport_id} successfully inserted");
		$report_id = $report->sreport_id;
		
				$this->log->Write("Inserting file:  report_id: {$report_id}, file: {$file}, type: {$type}, filename:{$filename}, extension:{$extension}");
		//Get new sreport_data model.
		$report_data = ECash::getFactory()->getModel('SreportData');
		
		//set sreport_id it belongs to
		$report_data->sreport_id = $report_id;

		//set the sreport_data for the file
		$report_data->sreport_data = $contents;
		//set the filename
		$report_data->filename = $filename;

		//set the extension
		$report_data->filename_extension = $extension;

		//get the type id and insert the type.
		$report_data->sreport_data_type_id = ECash::getFactory()->getModel('SreportDataType')->getTypeId($type);

		//insert the record;
		$report_data->insert();

		$this->log->Write("File successfully inserted, sreport_data_id: {$report_data->sreport_data_id}");
	}
	
	
	

	
	protected function Update_Report_Status ($report_id, $new_status)
	{
		$status      = new ECash_Status('Sreport',$this->agent_id);
		$status->setStatus($new_status,$report_id);
	}
	
}

?>