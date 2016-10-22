<?php

class ECash_NightlyEvent_MoveUnfundedToWithdrawn extends ECash_Nightly_Event
{
	// Parameters used by the Cron Scheduler
	protected $business_rule_name = 'move_unfunded_to_withdrawn';
	protected $timer_name = 'Move_Unfunded_To_Withdrawn';
	protected $process_log_name = 'move_unfunded_to_withdrawn';
	protected $use_transaction = FALSE;

	const DAYS_OLD = 14;

	public function __construct()
	{
		$this->classname = __CLASS__;

		parent::__construct();
	}
	
	public function run()
	{
		parent::run();

		$this->Move_Unfunded_To_Withdrawn($this->server, $this->start_date);
	}

	private function deleteTransaction($transaction_register_id)
	{
		//remove from transaction history
		$th = ECash::getFactory()->getModel('TransactionHistory');
		$th_delete = ECash::getFactory()->getModel('TransactionHistory');
		$th_array = $th->loadAllBy(array('transaction_register_id' => $transaction_register_id,));
		foreach ($th_array as $th_record)
		{
			$loaded = $th_delete->loadBy(array('transaction_history_id' => $th_record->transaction_history_id,));
			if ($loaded)
			{
				$th_delete->delete();
			}
		}

		//remove from transaction ledger
		$tl = ECash::getFactory()->getModel('TransactionLedger');
		$tl_delete = ECash::getFactory()->getModel('TransactionLedger');
		$tl_array = $tl->loadAllBy(array('transaction_register_id' => $transaction_register_id,));
		foreach ($tl_array as $tl_record)
		{
			$loaded = $tl_delete->loadBy(array('transaction_ledger_id' => $tl_record->transaction_ledger_id,));
			if ($loaded)
			{
				$tl_delete->delete();
			}
		}

		//remove from transaction register
		$tr = ECash::getFactory()->getModel('TransactionRegister');
		$loaded = $tr->loadBy(array('transaction_register_id' => $transaction_register_id,));
		if ($loaded)
		{
			$event_schedule_id = $tr->event_schedule_id;
			$transaction_type_id = $tr->transaction_type_id;
			$transaction_status = $tr->transaction_status;
			$amount = $tr->amount;
			$tr->delete();
		}

		//remove from event amount
		$ea = ECash::getFactory()->getModel('EventAmount');
		$ea_delete = ECash::getFactory()->getModel('EventAmount');
		$ea_array = $ea->loadAllBy(array('transaction_register_id' => $transaction_register_id,
						'event_schedule_id' => $event_schedule_id,
		));
		foreach ($ea_array as $ea_record)
		{
			$loaded = $ea_delete->loadBy(array('event_amount_id' => $ea_record->event_amount_id,));
			if ($loaded)
			{
				$ea_delete->delete();
			}
		}

		//remove from event schedule (only if no other transaction is associated with this event)
		$tr_check = ECash::getFactory()->getModel('TransactionRegister');
		$tr_check_array = $tr_check->loadAllBy(array('event_schedule_id' => $event_schedule_id));
		if (count($tr_check_array) == 0)
		{
			$es = ECash::getFactory()->getModel('EventSchedule');
			$loaded = $es->loadBy(array('event_schedule_id' => $event_schedule_id));
			if ($loaded)
			{
				$es->delete();
			}
		}
	}

	/**
	 * @param Server $server
	 * @param $run_date
	 */
	function Move_Unfunded_To_Withdrawn(Server $server, $run_date)
	{
		$db = ECash::getMasterDb();
		$log = $server->log;
		$qm = ECash::getFactory()->getQueueManager();
		
		$query = "
		SELECT transaction_type_id
		FROM transaction_type
		WHERE company_id=1
		AND name_short='loan_disbursement'
		";
		$result = $db->Query($query);
		$row = $result->fetch(PDO::FETCH_ASSOC);
		$disb_transaction_type_id = intval($row['transaction_type_id']);

		$status_list = array(
		'pending::prospect::*root',
		'confirmed::prospect::*root',
		'confirm_declined::prospect::*root',
		'disagree::prospect::*root',
		'funding_failed::servicing::customer::*root',
		);

		$status_list = "'".implode("','",$status_list)."'";

		$days_old = self::DAYS_OLD;
		$db_chasm = ECash::getAppSvcDB();
		$query_chasm = "
		SELECT
		ap.application_id AS application_id,
		st.application_status_name
		FROM application AS ap
		JOIN application_status AS st ON (st.application_status_id=ap.application_status_id)
		WHERE st.application_status_name IN ({$status_list})
		AND ap.date_application_status_set < DATE_ADD(NOW(),INTERVAL -{$days_old} DAY)
		ORDER BY st.application_status_name,ap.date_application_status_set
		";
		//echo $query_chasm, "\n";
		$mssql_results = array();
		$result_chasm = $db_chasm->query($query_chasm);
		while ($row_chasm = $result_chasm->fetch(DB_IStatement_1::FETCH_OBJ))
		{
			$mssql_results[] = $row_chasm;
		}
		
		$columns = array(
		'application_id' => 'int(10) unsigned',
		'application_status_name' => 'varchar (100)',
		);

		ECash_DB_Util::generateTempTableFromArray($db, 'temp_unfunded_apps', $mssql_results, $columns, 'application_id');

		$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
		SELECT DISTINCT
		t.application_id,
		t.application_status_name,
		a.company_id
		FROM
		temp_unfunded_apps AS t
		JOIN application AS a USING (application_id)
		LEFT JOIN follow_up AS fu ON (fu.application_id = a.application_id AND fu.status = 'pending')
		LEFT JOIN event_schedule es on (es.application_id = a.application_id)
		LEFT JOIN n_agent_queue_entry q on (q.related_id = a.application_id)
		WHERE fu.follow_up_id IS NULL
		AND es.application_id is NULL
		AND (q.related_id IS NULL or q.date_available < DATE_SUB('{$run_date}', INTERVAL {$days_old} DAY))
		";
		$result = $db->query($sql);
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			//echo $row->application_id, ", ", $row->application_status_name, "\n";

			$this->log->Write("[App: {$row->application_id}] Moving account from {$row->application_status_name} to Withdrawn");
			Update_Status(null, $row->application_id, array('withdrawn','applicant','*root'));

			$queue_item = new ECash_Queues_BasicQueueItem($row->application_id);
			$qm->removeFromAllQueues($queue_item);
			
			ECash_Documents_AutoEmail::Queue_For_Send($row->application_id, 'WITHDRAWN_LETTER');

			$comment = new Comment();
			$comment->Add_Comment($row->company_id, $row->application_id, 1,"Auto-withdrawn by system", "standard");
		}

		///////////////////////////////////////////////////////////////////////////REFI
		//echo "========================REFI==========================================\n";
		$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
		SELECT DISTINCT
		t.application_id AS application_id,
		t.application_status_name,
		a.application_id AS refi_application_id,
		a.company_id,

		(SELECT tr.transaction_register_id
		FROM transaction_register AS tr
		JOIN transaction_type AS tt ON (tt.transaction_type_id = tr.transaction_type_id)
		WHERE tr.application_id = t.application_id
		AND tt.name_short = 'converted_principal_bal'
		ORDER BY tr.transaction_register_id ASC
		LIMIT 1
		) AS pbf_child_transaction_register_id,

		(SELECT tr.transaction_register_id
		FROM transaction_register AS tr
		JOIN transaction_type AS tt ON (tt.transaction_type_id = tr.transaction_type_id)
		WHERE tr.application_id = a.application_id
		AND tt.name_short = 'refi_foward'
		ORDER BY tr.transaction_register_id DESC
		LIMIT 1
		) AS pbf_transaction_register_id,

		(SELECT tr.transaction_register_id
		FROM transaction_register AS tr
		JOIN transaction_type AS tt ON (tt.transaction_type_id = tr.transaction_type_id)
		JOIN event_schedule AS es ON (es.event_schedule_id = tr.event_schedule_id)
		WHERE tr.application_id = a.application_id
		AND tt.name_short = 'adjustment_internal_fees'
		-- AND es.configuration_trace_data='Drop Interest for Refi'
		ORDER BY tr.transaction_register_id DESC
		LIMIT 1
		) AS adj_transaction_register_id
		FROM
		temp_unfunded_apps AS t
		JOIN react_affiliation AS ra ON (ra.react_application_id = t.application_id)
		JOIN application AS a ON (a.application_id = ra.application_id)
		JOIN status_history AS st ON (st.application_id = a.application_id)
		JOIN application_status AS aps ON (aps.application_status_id = st.application_status_id)
		LEFT JOIN follow_up AS fu ON (fu.application_id = t.application_id AND fu.status = 'pending')
		LEFT JOIN n_agent_queue_entry q on (q.related_id = t.application_id)
		WHERE aps.name_short IN ('refi')
		AND fu.follow_up_id IS NULL
		AND (q.related_id IS NULL or q.date_available < DATE_SUB('{$run_date}', INTERVAL {$days_old} DAY))
		";
		$qm = ECash::getFactory()->getQueueManager();
		$result = $db->query($sql);
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			//echo $row->application_id,", ",$row->application_status_name,
			//", ",$row->refi_application_id,", ",$row->pbf_transaction_register_id,", ",$row->adj_transaction_register_id,
			//"\n";

			$this->log->Write("[App: {$row->application_id}] Moving account from {$row->application_status_name} to Withdrawn");
			Update_Status(null, $row->application_id, array('withdrawn','applicant','*root'));

			$queue_item = new ECash_Queues_BasicQueueItem($row->application_id);
			$qm->removeFromAllQueues($queue_item);

			ECash_Documents_AutoEmail::Queue_For_Send($row->application_id, 'WITHDRAWN_LETTER');

			if ($row->pbf_child_transaction_register_id > 0) $this->deleteTransaction($row->pbf_child_transaction_register_id);
			$comment = new Comment();
			$comment->Add_Comment($row->company_id, $row->application_id, 1,"Refi failed. Auto-withdrawn by system", "standard");

			//////////// PARENT:
			Update_Status(null, $row->refi_application_id, array('active::servicing::customer::*root'));
			if ($row->pbf_transaction_register_id > 0)
			{
				$this->deleteTransaction($row->pbf_transaction_register_id);
				
				if ($row->adj_transaction_register_id > 0) $this->deleteTransaction($row->adj_transaction_register_id);
			}

			Complete_Schedule($row->refi_application_id);
			$comment = new Comment();
			$comment->Add_Comment($row->company_id, $row->refi_application_id, 1,"Refi failed. Restored Active", "standard");
		}

		///////////////////////////////////////////////////////////////////////////FUNDING FAILED
		//echo "========================FUNDING FAILED==========================================\n";
		$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
		SELECT DISTINCT
		t.application_id,
		t.application_status_name,
		a.company_id
		FROM
		temp_unfunded_apps AS t
		JOIN application AS a USING (application_id)
		-- to not run twice apps with no schedule, filter them
		JOIN transaction_register AS tr1 ON (tr1.application_id = a.application_id)
		LEFT JOIN transaction_register AS tr ON (tr.application_id = a.application_id
							AND tr.transaction_type_id = {$disb_transaction_type_id}
							AND tr.transaction_status = 'pending')
		LEFT JOIN event_schedule AS es ON (es.application_id = a.application_id AND es.event_status = 'scheduled')
		LEFT JOIN follow_up AS fu ON (fu.application_id = a.application_id AND fu.status = 'pending')
		LEFT JOIN n_agent_queue_entry q on (q.related_id = a.application_id)
		WHERE
		tr.transaction_register_id IS NULL
		AND es.event_schedule_id IS NULL
		AND fu.follow_up_id IS NULL
		AND (q.related_id IS NULL or q.date_available < DATE_SUB('{$run_date}', INTERVAL {$days_old} DAY))
		";
		$qm = ECash::getFactory()->getQueueManager();
		$result = $db->query($sql);
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			//echo $row->application_id, ", ", $row->application_status_name, "\n";

			$this->log->Write("[App: {$row->application_id}] Moving account from {$row->application_status_name} to Withdrawn");
			Update_Status(null, $row->application_id, array('withdrawn','applicant','*root'));

			$queue_item = new ECash_Queues_BasicQueueItem($row->application_id);
			$qm->removeFromAllQueues($queue_item);

			ECash_Documents_AutoEmail::Queue_For_Send($row->application_id, 'WITHDRAWN_LETTER');

			$comment = new Comment();
			$comment->Add_Comment($row->company_id, $row->application_id, 1,"Auto-withdrawn by system", "standard");
		}
		/////////////////////////////////////////////////////
	}
}

?>
