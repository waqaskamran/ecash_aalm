<?php
//SECOND TIER INCOMING PROCESSING FOR GENEVA ROTH VENTURES!

class Second_Tier_Incoming_Grv extends Second_Tier_Incoming 
{
	protected $return_file_format = array(
						'application_id',
						'application_status', 
						'application_balance',
	);

	private static $RS		  = "\n";

	
	public function __construct(Server $server)
	{
		parent::__construct($server);
	}
	
	
	

	public function Process_Second_Tier_Incoming ($end_date, $report_type, $override_start_date = NULL)
	{
		$this->business_day = $end_date;
		$commented_corrections = array();
		
		$this->log->Write("Process_Second_Tier_Incoming ...\n");

		// Get the most recent business date of the last report execution of the same type
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT 
						max(business_day) as last_run_date
					FROM 
						process_log
					WHERE
							step	 = 'second_tier_{$report_type}'
						AND	state	 = 'completed'
						AND company_id	 = {$this->company_id}
						AND business_day <> '1970-01-01'
		";
		$result = $this->db->Query($query);
		$row = $result->fetch(PDO::FETCH_ASSOC);

		if(NULL !== $override_start_date)
		{
			$start_date = date("Y-m-d", strtotime($override_start_date));
		}
		elseif( !empty($row['last_run_date']) )
		{
			$last_run_date = $row['last_run_date'];
			$start_date = date("Y-m-d", strtotime("+1 day", strtotime($last_run_date)));
		}
		else
		{
			$start_date = date("Y-m-d", strtotime("now"));
		}

		$count = 0;

		$this->log->Write("Process_ACH_Report(): start date: {$start_date}, end date: {$end_date}");

		try
		{
			while($start_date <= $end_date)
			{
				$this->log->Write("Process_ACH_Report(): Running report {$report_type} for date {$start_date}");

				if($report = $this->Fetch_Report($start_date, $report_type))
				{
						//$this->log->Write("Received field does not contain ER= code");

						$second_tier_incoming_report_data = $this->Parse_Report_Batch($report->sreport_data);

						$this->log->Write("Found " . count($second_tier_incoming_report_data) . " items in Second_Tier_Incoming report.");
						
						foreach ($second_tier_incoming_report_data as $report_data)
						{
							
							if(!is_array($report_data))
								continue;
							

							$application_id = $report_data['application_id'];
							$status = $report_data['application_status'];
							$balance = $report_data['application_balance'];
										
							if (is_numeric($application_id))
							{

								$this->log->Write("Process_Incoming_Second_Tier: application_id: $application_id");
								$report_type = strtolower($report_type);
								if ($report_type == 'updates')
								{
									
									//Update application's status to the corresponding application status
									$this->Update_Application_Status($application_id, $status);
									//Update application's balance to corresponding 
									$this->Update_Application_Balance($application_id,$balance);
									//Insert comment denoting that this stuff was received from GR
									$application = ECash::getApplicationById($application_id);
									$comments = $application->getComments();
									$comments->add("Incoming 2nd Tier Update.  Status: {$status} Balance: {$balance}",ECash::getAgent()->AgentId);
								}
							}
							else
							{
								$this->log->Write("Unrecognized Report Entry: " . var_export($report_data,true));
							}
						}
						
						// Mark report as processed
						$this->Update_Report_Status($report->sreport_id, 'processed');
						$this->log->Write("Second_Tier_Incoming: " . ucfirst($report_type) . " Report {$report->sreport_id} has been received without errors ($start_date).", LOG_INFO);
						$count++;
				}	
					
				// Advance start date by one day
				$this->log->Write("Process_ACH_Report(): advance start date\n");
				$start_date = date("Y-m-d", strtotime("+1 day", strtotime($start_date)));
			}
		}
		catch(Exception $e)
		{
			$this->log->Write("Second_Tier_Incoming: Processing of $report_type failed and transaction will be rolled back.", LOG_ERR);
			$this->log->Write("Second_Tier_Incoming: No data recovery should be necessary after the cause of this problem has been determined.", LOG_INFO);
			throw $e;
		}
		
		$this->log->Write("Second_Tier_Incoming: $count " . ucfirst($report_type) . " Reports were successfully processed.", LOG_ERR);
		
		return $count;
	}
	
	protected  function Update_Application_Status($application_id,$grv_status)
	{
		$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		//We might want to have a better way to translate these statuses in the future.  Maybe not.
		switch (strtoupper($grv_status))
		{
			case 'BKRPT':
				$status_id = $status_list->toId('verified::bankruptcy::collections::customer::*root');
				break;
			case 'DECSD':
				$status_id = $status_list->toId('verified::deceased::collections::customer::*root');
				break;
			case 'FRAUD':
				$status_id = $status_list->toId('confirmed::fraud::applicant::*root');
				break;
			case 'PIF':
				$status_id = $status_list->toId('paid::customer::*root');
				break;
			case 'SIF':
				$status_id = $status_list->toId('settled::customer::*root');
				break;
				
			case 'ACTIVE':
			case 'LEGAL':
			case 'ATT':
			default:
				$status_id = $status_list->toId('dequeued::contact::collections::customer::*root');
				break;
		}
		
		return Update_Status(null,$application_id,$status_id);
	}
	
	protected function Update_Application_Balance($application_id, $balance)
	{
		$application_balance = Fetch_Balance_Information($application_id);
		$total_balance = $application_balance->total_balance;
		$adjustment_amount = $balance - $total_balance;
		
		
		$this->log->Write("$application_id : Current balance is - $total_balance | New balance am - $balance | Adding adjustment for $adjustment_amount");

	    //allocate the amounts

		if($application_balance->fee_pending + $adjustment_amount > 0)
		{
			$amounts[] = Event_Amount::MakeEventAmount('fee', $adjustment_amount);
			$adjustment_amount = 0;
		}
		else 
		{
			$amounts[] = Event_Amount::MakeEventAmount('fee', -$application_balance->fee_pending);
			$adjustment_amount = bcadd($adjustment_amount,$application_balance->fee_pending,2);
		}
		

		if($application_balance->service_charge_pending + $adjustment_amount > 0)
		{
			$amounts[] = Event_Amount::MakeEventAmount('service_charge', $adjustment_amount);
			$adjustment_amount = 0;
		}
		else 
		{
			$amounts[] = Event_Amount::MakeEventAmount('service_charge', -$application_balance->service_charge_pending);
			$adjustment_amount = bcadd($adjustment_amount,$application_balance->service_charge_pending,2);
		}

		//We're to the point of allocating principal.  We don't care what's left, so we're going to allocate
		//all of the remaining adjustment to principal
		$amounts[] = Event_Amount::MakeEventAmount('principal', $adjustment_amount);
		$adjustment_amount = 0;

		$date = date("Y-m-d");
		$e = Schedule_Event::MakeEvent($date, $date, $amounts, 'adjustment_internal',
				"Second Tier Update (New balance = $balance)");

		Post_Event($application_id, $e);
		
	}
	
	
	//Are you freaking kidding me?! This parses the file just fine without me changing anything?!  That's ridiculous!
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
					if($this->report_type == 'results')
					{
						$parsed_data_ary[$i][$this->results_file_format[$key]] = str_replace('"', '', $col_data);
					}else{
						$parsed_data_ary[$i][$this->return_file_format[$key]] = str_replace('"', '', $col_data);
					}
				}

				$i++;
			}
		}
		return $parsed_data_ary;
	}
}
?>
