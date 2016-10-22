<?php

require_once(SERVER_CODE_DIR . "external_collections_query.class.php");

set_time_limit(0);

class External_Collections extends External_Collections_Query
{
	private $ld;

	function __construct($server)
	{
		parent::__construct($server);
	}

	// Not using models because joining in mysql is easier than PHP
	public function fetchBatchList($start_date = NULL, $end_date = NULL)
	{
		if ($start_date != NULL)
			$my_start = date('Y-m-d', strtotime($start_date));
		
		if ($end_date != NULL)
			$my_end   = date('Y-m-d', strtotime($end_date));

		$company_id = ECash::getCompany()->company_id;

		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT DISTINCT
					sr.sreport_id,
					DATE_FORMAT(sr.date_created,       '%m/%d/%Y %H:%i:%s') AS date_created,
					DATE_FORMAT(sr.sreport_date,       '%m/%d/%Y')          AS sreport_date,
					DATE_FORMAT(sr.sreport_start_date, '%m/%d/%Y %H:%i:%s') AS sreport_start_date,
					DATE_FORMAT(sr.sreport_end_date,   '%m/%d/%Y %H:%i:%s') AS sreport_end_date,
					(
						/* We want the last successful send date */
						SELECT
							DATE_FORMAT(sssh.date_created, '%m/%d/%Y %H:%i:%s')
						FROM
							sreport_send_status_history sssh
						JOIN
							sreport_send_status sss
						WHERE
							sssh.sreport_id = sr.sreport_id
						AND
							sss.name_short = 'sent'
						ORDER BY
							date_created DESC
						LIMIT 1
					)                                                       AS sreport_last_send_date,
					srss.name as send_status,
					srs.name as status,
					ebr.name   AS report_company,
					ecb.item_count AS num_apps
				FROM
					sreport sr
				JOIN
					sreport_send_status srss ON srss.sreport_send_status_id = sr.sreport_send_status_id
				JOIN
					sreport_status srs ON srs.sreport_status_id = sr.sreport_status_id
				JOIN
					sreport_data ON sreport_data.sreport_id = sr.sreport_id
				JOIN
					sreport_type ON sr.sreport_type_id = sreport_type.sreport_type_id
				JOIN
					sreport_data_type ON sreport_data.sreport_data_type_id = sreport_data_type.sreport_data_type_id
				JOIN
					ext_collections_batch ecb ON (ecb.sreport_id = sr.sreport_id)
				JOIN
					external_batch_report ebr ON (ebr.external_batch_report_id = ecb.external_batch_report_id)
				WHERE
					sreport_type.name_short IN ('second_tier_batch')
				AND
					sr.company_id = '{$company_id}'
		";

		if ($start_date != NULL && $end_date != NULL)
		{
			$query .= "
				AND
					sr.sreport_date BETWEEN '{$my_start}' AND '{$my_end}'
			";
		}
	
		$query .= "
				ORDER BY sr.sreport_date DESC, sr.date_created DESC
		";
	
		$result = $this->db->Query($query);
		$count = $result->rowCount();
		$results = array();
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$results[] = $row;	
		}
		return $results;
		
	}

	public function Get_Batch_Report_List()
	{
		$data = ECash::getTransport()->Get_Data();

		$ebc = ECash::getFactory()->getModel('ExternalBatchReportList');
		$ebt = ECash::getFactory()->getModel('ExternalBatchType')->getTypeId('second_tier');

		$ebc->loadBy(array('external_batch_type_id' => $ebt));

		$batch_companies = "";
		
		foreach ($ebc as $company)
		{
			$batch_companies .= "<option value='{$company->name_short}'>{$company->name}</option>\n";
		}

		$data->second_tier_batch_companies = $batch_companies;

		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}

	public function Get_Batch_List($start_date = NULL, $end_date = NULL)
	{
		$data = new StdClass();

		$sreports = $this->fetchBatchList($start_date, $end_date);

		$batches = array();

		foreach ($sreports as $report)
		{
			$batches[] = array(
				'batch_id'        => $report['sreport_id'],
				'batch_date'      => $report['date_created'],
				'batch_report'   => $report['batch_report'],
				'batch_num_apps'  => $report['num_apps'],
				'batch_status'    => $report['status'],
				'batch_tooltip'   => NULL,
			);
							  
		}

		// populate it in the transport's data
		// magic	

		$data->batches = $batches;

		ECash::getTransport()->Set_Data($data);
	}


	public function Get_Pending_Adjustment_Count()
	{
		$data = ECash::getTransport()->Get_Data();

		$data->second_tier_pending_adjustment_count = $this->Fetch_Adjustment_Count();
		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}


	public function Get_Pending_Count()
	{
		//[#42977] allow company overrides of default Second Tier Batch
		$batch_class = ECash::getFactory()->getClassString('ExternalBatches_SecondTierBatch');
		$batch = new $batch_class(ECash::getMasterDb());
		$batch->preprocess();
		
		$data = ECash::getTransport()->Get_Data();

		$data->second_tier_pending_count = $batch->getAppCount();

		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}

	private function Create_Adjustment_File_Contents($adjustment_data)
	{
		$file_lines = array();
		foreach ($adjustment_data as $row)
		{
			$file_lines[] =
				'"'.str_replace('"', '""', $row->application_id).'",'.
				'"'.str_replace('"', '""', $row->customer_name).'",'.
				'"'.str_replace('"', '""', $row->ext_col_company_name).'",'.
				'"'.str_replace('"', '""', $row->adjustment_amount).'",'.
				'"'.str_replace('"', '""', $row->adjustment_date).'",'.
				'"'.str_replace('"', '""', $row->new_balance).'"';
		}

		return implode("\n", $file_lines);
	}

	public function Process_Adjustments()
	{
		// Requiring the file here because the functions are only used here.
		require_once(dirname(__FILE__)."/../../sql/lib/comment.func.php");
		$strtotime = date('Y-m-d');
		/*-Start a process log*/
		$process_id = Set_Process_Status($this->db, $this->server->company_id, 'process_adjustment_records', 'started', $strtotime);

		$filename = $this->server->company . '-adjustments-'.date('Ymdhis',time()).".txt";

		$ids_to_delete = array();
		$records = $this->Fetch_External_Collections_Adjustments($ids_to_delete);
		$file_contents = $this->Create_Adjustment_File_Contents($records);
		$item_count = count($records);

		try
		{
			$this->db->beginTransaction();

			/*-Insert ext_coll_batch skeleton row to get ID*/
			$ec_batch_id = $this->Insert_Ext_Coll_Batch('other', true);
			$this->Update_Ext_Coll_Batch($ec_batch_id, $filename, $file_contents, $item_count);

			$this->Remove_External_Collections_Adjustments($ids_to_delete);
			$this->db->commit();
		}
		catch(PDOException $e)
		{
			/*-Update process_log */
			$this->db->rollBack();
			Set_Process_Status($this->db, $this->server->company_id, 'process_adjustment_records', 'failed', NULL, $process_id);
			return FALSE;
		}

		/*-Update process_log */
		Set_Process_Status($this->db, $this->server->company_id, 'process_adjustment_records', 'completed', NULL, $process_id);
		return TRUE;
	}

	public function Create_EC_Delta_From ( $application_id , $adjustment_amount )
	{
		$balance_info = Fetch_Balance_Information($application_id);
		$old_balance = ($balance_info->total_balance - $adjustment_amount);

		//This is to temporarily get around a silly unique index on ext_collections
		$filename = uniqid($application_id);
		$query = "
			-- eCash3.0 ".__FILE__.":".__LINE__.":".__METHOD__."()
			INSERT INTO ext_corrections
			(date_created, company_id, application_id, old_balance,
				adjustment_amount, new_balance, file_name, file_contents)
			VALUES (NOW(), ?, ?, ?, ?, ?, ?, '')
		";
		$args = array($this->server->company_id, $application_id, $old_balance,
			$adjustment_amount, $balance_info->total_balance, $filename);

		$this->db->queryPrepared($query, $args);
		return TRUE;
	}

	public function Show_Incoming_Batches($from_date, $to_date)
	{
		get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__.var_export($from_date, true).var_export($to_date, true), LOG_NOTICE);

		$full_contents = $this->Fetch_Ready_Inc_Coll_Batches($from_date, $to_date);

		foreach ($full_contents as &$file_meta)
		{
			$file_meta->record_count = substr_count(trim($file_meta->file_contents),"\n") + 1;
			// removing file contents unless needed for aggregate calculations later
			unset($file_meta->file_contents);
		}

		$data = new stdClass();
		$data->inc_coll_data = $full_contents;
		ECash::getTransport()->Set_Data( $data );
	}

	public function Process_Incoming_EC_File( $batch_id )
	{
		get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$batch_id})", LOG_NOTICE);

		// retrieve file contents from database
		list($batch_status, $file_name, $file_contents) = $this->Fetch_Inc_Coll_Batch($batch_id);

		get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$batch_id}): Batch File: {$file_name}. Status: {$batch_status}", LOG_NOTICE);

		switch ($batch_status)
		{
			case "failed":
			case "success":
			case "partial":
				return;

			case "in-progress":
				throw new OutOfBoundsException ("Attempting to process illegal Incoming External Collections file of status: {$batch_status}");

			case "received-partial":
			case "received-full":
			default:
				$this->Update_Inc_Coll_Batch_Status($batch_id, 'in-progress');
				$batch_status = 'partial';
		}

		// dump contents into items table
		try 
		{
			$fp = fopen("php://temp", "w+");

			fputs($fp, $file_contents);

			rewind($fp);

			$trimmer = create_function('&$a,$b', '$a = trim($a);');

			while ($row = fgetcsv($fp))
			{
//				var_dump($row);
//				if ($i++ == 10) throw new Exception("artificial limit of {$i} hit"); // force a partial
				array_walk($row,$trimmer);
				$this->Insert_Inc_Coll_Record($row, $batch_id);
			}
						
		} 
		catch (Exception $e) 
		{
			get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$batch_id}): Exception Caught: {$e->getMessage()}. Loading remaining records to database.", LOG_ERROR);

			$this->Update_Inc_Coll_Batch_Status($batch_id, 'partial');

			// write remainder of pointer to db as a new record
			// something needs to be done with the current "row", atm it dissapears into the aether
//			fputcsv($fp,$row);
			$file_contents = stream_get_contents($fp);
			fclose($fp);
			unset($fp);

			if (preg_match("/\.(\d+)$/",$file_name, $fnm))
			{
				$num = $fnm[1];
				$file_name = preg_replace("/\.\d+$/", "." . ($num + 1), $file_name);
			} 
			else 
			{
				$file_name = $file_name . ".1";
			}

			$query = "
				INSERT IGNORE INTO incoming_collections_batch
				(date_created, file_name, batch_status, file_contents)
				VALUES (NOW(), ?, 'received-partial', ?)
			";
			$this->db->queryPrepared($query, array($file_name, $file_contents));
		}
	}

	public function Process_Incoming_EC_Items( $batch_id, $skipchecks = NULL )
	{
		get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$batch_id}, {$skipchecks})", LOG_NOTICE);

		set_time_limit(0);

		$exceptions = array();

		if (!is_array($skipchecks) || !in_array("application",$skipchecks))
		{
			$exceptions = $this->Check_Valid_Inc_Coll_Record($batch_id);
			$this->Inc_Coll_Item_Set_Message($exceptions, "Application Not Found or invalid company_id or invalid SSN");

			// since we're handling this here.. we don't want to handle it in the item processor
			$skipchecks[] = "application";

		}
//var_dump($exceptions)		;
		// retrieve list of unworked items from table
		// loop thru list
		$successes = array();
		$fet = $this->Fetch_Inc_Coll_Records($batch_id, $exceptions);

		$this->ld = new Loan_Data($this->server);

		foreach($fet as $row)
		{ 
			try 
			{
				$ret = $this->Process_Incoming_EC_Item($row, $skipchecks);

				if ($ret !== true)
				{
					$exceptions[] = $row->incoming_collections_item_id;
				}
				else
				{
					$successes[] = $row->incoming_collections_item_id;
				}
			} 
			catch (Exception $e) 
			{
				get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$batch_id}, {$skipchecks}): Exception Caught: {$e->getMessage()}. Adding {$row->incoming_collections_item_id} to exceptions list.", LOG_ERROR);
				// instead of this, check to see if the status was set by the process, and if not, THEN set an exception
//				var_dump($e);
				$exceptions[] = $row->incoming_collections_item_id;
			}
		}

		$queryskel = "
		-- eCash3.0 ".__FILE__.":".__LINE__.":".__METHOD__."()
			UPDATE incoming_collections_item SET status = '%tkn%' WHERE incoming_collections_item_id in (%lst%)
		";


		try 
		{
			$this->db->beginTransaction();

			get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$batch_id}, {$skipchecks}): " . count($exceptions) . " Exceptions. " . count($successes) . " Completed.", LOG_NOTICE);

			if (count($exceptions))
			{
				$this->db->exec(str_replace(array('%tkn%', '%lst%'), array('flagged', implode(",", $exceptions)), $queryskel));
			}

			if (count($successes))
			{
				$this->db->exec(str_replace(array('%tkn%', '%lst%'), array('success', implode(",", $successes)), $queryskel));
			}

			// instead of just declaring, should check for validity
			$this->Update_Inc_Coll_Batch_Status($batch_id, 'success');

			$this->db->commit();

		} 
		catch (Exception $e) 
		{
			get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$batch_id}, {$skipchecks}): Exception Caught: {$e->getMessage()}. Setting batch {$batch_id} to failed.", LOG_ERROR);

			$this->db->rollBack();

			$this->Update_Inc_Coll_Batch_Status($batch_id, 'failed');

		}
	}

	public function Process_Incoming_EC_Item($record, $skipchecks = NULL)
	{
		get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$record}, {$skipchecks})", LOG_NOTICE);

//		var_dump($record);

		// check status
		if ( (!is_array($skipchecks) || !in_array("status",$skipchecks)) && $record->status != 'new')
		{
			get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$record}, {$skipchecks}): Not a 'new' record.", LOG_NOTICE);
			$this->Inc_Coll_Item_Set_Message($record->incoming_collections_item_id, "Invalid item status {$record->status}");
			return $record;
		}

		if (!is_array($skipchecks) || !in_array("value",$skipchecks))
		{
			// retrieve maximum allowed transaction value
			$max_value = 2000; // get from business rule

			if ( abs((float) $record->correction_amount) >= $max_value )
			{
				get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$record}, {$skipchecks}): Amount exceeds maximum value.", LOG_NOTICE);
				$this->Inc_Coll_Item_Set_Message($record->incoming_collections_item_id, "Transaction amount exceeds maximum value.");
				return $record;
			}
		}

		// check for valid app id
		if ( (!is_array($skipchecks) || !in_array("application",$skipchecks)) && count($this->Check_Valid_Inc_Coll_Record($record->application_id, TRUE)) )
		{
			get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$record}, {$skipchecks}): {$record->application_id} Not a valid application", LOG_NOTICE);
			$this->Inc_Coll_Item_Set_Message($record->incoming_collections_item_id, "Not a valid Application.");
			return $record;
		}

		// check code
		try 
		{
			
			get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$record}, {$skipchecks}): item record engaging action {$record->action}.", LOG_NOTICE);

			switch ($record->action)
			{
				case "recovery":
					return $this->Incoming_EC_Payment($record);

				case "recovery-reversal":
					return $this->Incoming_EC_Reverse($record);

				case "recovery-writeoff":
					$this->Incoming_EC_Payment($record);

				case "writeoff":
					return $this->Incoming_EC_Writeoff($record);

				case "bankruptcy-verified":
					return $this->Incoming_EC_Bankruptcy($record);

				case "other":
				default:
					$this->Inc_Coll_Item_Set_Message($record->incoming_collections_item_id, "Trust Code / Status Code Mismatch or Exception as defined by code.");
					return $record;
			}
		} 
		catch ( Exception $e ) 
		{
//				var_dump($e); exit;
			get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$batch_id}, {$skipchecks}): Exception Caught: {$e->getMessage()}.", LOG_ERROR);
			$this->Inc_Coll_Item_Set_Message($record->incoming_collections_item_id, "Exception Caught: {$e->getMessage()}");
			return $record;
		}
	}

	private function Incoming_EC_Payment( stdClass $record )
	{
		get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$record})", LOG_NOTICE);
		/*
		 * register_single_Event()
		 * request->amount
		 * request->action
		 * request->payment_description
		 */
		// loan_data->Save_Recovery(request,app_id);

		$request = new stdClass;
		$request->amount = $record->correction_amount;
		$request->action = 'recovery';
		$request->payment_description = "recovery payment";

		Register_Single_Event($record->application_id, $request, $this->db);

		return true;

	}

	private function Incoming_EC_Reverse( stdClass $record )
	{
		get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$record})", LOG_NOTICE);
		/*
		 * req'd: posted_fees
		 * 			posted_principal
		 * 			posted_total
		 * 			action = ext_recovery_reversal
		 * 			schedule_effect = shorten
		 * 			adjustment_target = fees
		 * 			action_type = save
		 */
		// loan_data->Set_RecoveryReversal(request,app_id);

		$request = new stdClass;
		$request->amount = $record->correction_amount;
		$request->action = 'ext_recovery_reversal';
		$request->payment_description = "recovery reversal";
		$this->ld->Save_RecoveryReversal($request,$record->application_id);

		return true;

	}

	private function Incoming_EC_Writeoff( stdClass $record )
	{
		get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$record})", LOG_NOTICE);
		/*
		 * req'd: posted_fees
		 * 			posted_principal
		 * 			posted_total
		 * 			action = writeoff
		 * 			schedule_effect = shorten
		 * 			adjustment_target = fees
		 * 			action_type = save
		 */
		// loan_data->Set_Writeoff(request);

		$request = new stdClass;
		$request->amount = $record->correction_amount;
		$request->action = 'writeoff';
		$request->payment_description = "writeoff";
		$request->schedule_effect = 'shorten';

		Register_Single_Event($record->application_id, $request, $this->db);
		$schedule = Fetch_Schedule($record->application_id);
		$data = Get_Transactional_Data($record->application_id, $this->db);
		
		// We've removed Repaint_Schedule, this code isn't even used, and even if it were used, 
		// the account shouldn't need any schedule adjustments, they're in 2nd Tier! [BR]
		//$schedule = Repaint_Schedule($schedule, $data->info, $data->rules, $request->schedule_effect, $this->db);
		//Update_Schedule($record->application_id, $schedule, $this->db);

		// If the schedule is "complete", i.e. they have no more balance,
		// set them to inactive
		Check_Inactive($record->application_id);

		return true;

	}

	private function Incoming_EC_Bankruptcy( stdClass $record )
	{
		get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$record})", LOG_NOTICE);

		// change status only
		Update_Status($this->server, $record->application_id, array('verified', 'bankruptcy', 'collections', 'customer', '*root' ));

		return true;

	}

}

?>
