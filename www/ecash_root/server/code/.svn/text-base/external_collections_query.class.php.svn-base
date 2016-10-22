<?php

require_once( SQL_LIB_DIR . 'fetch_status_map.func.php');

class External_Collections_Query
{
	protected $server;
	protected $status_map;

	/**
	 * @var DB_Database_1
	 */
	protected $db;

	public function __construct(Server $server)
	{
		$this->server = $server;
		$this->db = ECash::getMasterDb();
	}

	protected function Fetch_External_Collections_Adjustments(Array &$ids_to_delete)
	{
		$query = "
			SELECT
				ec.ext_corrections_id ext_corrections_id,
				ec.application_id application_id,
				ecb.ext_collections_co ext_col_company_name,
				ec.adjustment_amount adjustment_amount,
				ec.date_created adjustment_date,
				ec.new_balance new_balance
			 FROM
			 	ext_corrections ec
			 	JOIN ext_collections USING(application_id)
			 	JOIN ext_collections_batch AS ecb USING(ext_collections_batch_id)
			 FOR UPDATE
		";
		$st = $this->db->query($query);

		$adjustment_data = array();
		$app_ids = array();
		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$app_ids[] = $row->application_id;
			$adjustment_data[] = $row;
			$ids_to_delete[] = $row->ext_corrections_id;
		}
		if(!empty($app_ids))
		{
			$data_object = ECash::getFactory()->getData('Application');
			$app_data = $data_object->getApplicationData($app_ids);
			
			foreach($adjustment_data as $row)
			{
				$row->customer_name = $app_data[$row->application_id]['name_first'] . ' ' . $app_data[$row->application_id]['name_last'];
			}
		}
		
	
		return $adjustment_data;  // row was not found
	}

	protected function Remove_External_Collections_Adjustments($ids_to_delete)
	{
		$id_batches = array_chunk($ids_to_delete, 50);

		foreach ($id_batches as $id_batch)
		{
			if (!count($id_batch)) break;
			$id_list = implode(', ', $id_batch);

			$query = "
				DELETE FROM ext_corrections
				WHERE ext_corrections_id IN ({$id_list})
			";
			$result = $this->db->exec($query);
		}
	}

	/* Gets a count of all applications marked as "pending->external_collections->*root" */
	public function Fetch_Adjustment_Count()
	{
		$ext_status = Search_Status_Map('pending::external_collections::*root', $this->getStatusMap());

		$query = "
			SELECT COUNT(*) count
			FROM ext_corrections ec
			 	JOIN ext_collections USING(application_id)
			 	JOIN ext_collections_batch AS ecb USING(ext_collections_batch_id)
		";
		$count = $this->db->querySingleValue($query);
		return (int)$count;
	}

	public function Insert_Inc_Coll_Record($row_array, $batch_id)
	{
		list($t_agency,
			$t_company,
			$application_id,
			$ssn,
			$t_name,
			$edi_transaction_code,
			$correction_amount,
			$date_posted,
			$ext_collections_status,
			$reported_balance,
			$ext_collections_transaction_id) = $row_array;

		// this hard-coding map should probably be shoved somewhere else
		$company_map = array(
			"USFAC" => 3 ,
			"USFACB" => 3
		);
		$agency_map = array(
			"L100" => "pinion mgmt",
			"B100" => "pinion north"
		);
		$mapped_company_id = $company_map[$t_company];
		$ext_collections_co = $agency_map[$t_agency];

		$ssn = substr("0000000000" . $ssn, -9);

		if (is_numeric($edi_transaction_code))
		{
			$edi_transaction_code = substr("000" . $edi_transaction_code, -3);
		}

		$date_posted = date('Y-m-d', strtotime($date_posted));

		$query = "
			INSERT INTO incoming_collections_item (
				date_created,
				company_id,
				incoming_collections_batch_id,
				application_id,
				ssn,
				date_posted,
				edi_transaction_code,
				correction_amount,
				ext_collections_co,
				reported_balance,
				ext_collections_status,
				ext_collections_transaction_id,
				raw_record
			) VALUES (
				now(),
				{$mapped_company_id},
				{$batch_id},
				{$application_id},
				'{$ssn}',
				'{$date_posted}',
				'{$edi_transaction_code}',
				{$correction_amount},
				'{$ext_collections_co}',
				{$reported_balance},
				'{$ext_collections_status}',
				'{$ext_collections_transaction_id}',
				'" . serialize($row_array) . "'
			)
		";
		$this->db->exec($query);
	}

	public function Update_Inc_Coll_Batch_Status($batch_id, $status)
	{
		$query = "
			UPDATE incoming_collections_batch
			SET batch_status = ?
			WHERE incoming_collections_batch_id = ?
		";
		$this->db->queryPrepared($query, array($status, $batch_id));
	}

	public function Fetch_Ready_Inc_Coll_Batches($from_date, $to_date)
	{
		get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__.var_export($from_date, true).var_export($to_date, true), LOG_NOTICE);

		$from = $from_date->from_date_year . '-' . $from_date->from_date_month . '-' . $from_date->from_date_day;
		$to = $to_date->to_date_year . '-' . $to_date->to_date_month . '-' . $to_date->to_date_day;

		$query = "
			select
				b.*,
				success_count,
				success_aggregate,
				flagged_count,
				flagged_aggregate
			from
				incoming_collections_batch b
			LEFT JOIN (
				select
					i.incoming_collections_batch_id,
					count(*) as success_count,
					sum(i.correction_amount) as success_aggregate
				from incoming_collections_item i
				where i.status in ('completed','success')
				group by incoming_collections_batch_id
			) as s ON (s.incoming_collections_batch_id = b.incoming_collections_batch_id)
			LEFT JOIN (
				select
					i.incoming_collections_batch_id,
					count(*) as flagged_count,
					sum(i.correction_amount) as flagged_aggregate
				from incoming_collections_item i
				where i.status in ('flagged','failed')
				group by incoming_collections_batch_id
			) as f ON (f.incoming_collections_batch_id = b.incoming_collections_batch_id)
			WHERE date(b.date_created) BETWEEN ? AND ?
		";
		$st = $this->db->queryPrepared($query, array($from, $to));
		return $st->fetchAll(PDO::FETCH_OBJ);
	}

	public function Fetch_Inc_Coll_Batch($batch_id = NULL)
	{
		$query = "
			SELECT
				batch_status,
				file_name,
				file_contents
			FROM incoming_collections_batch
			WHERE incoming_collections_batch_id = ?
		";
		$row = $this->db->querySingleRow($query, array($batch_id), PDO::FETCH_ASSOC);
		return $row;
	}

	public function Check_Valid_Inc_Coll_Record($id, $item = FALSE)
	{
		$query = "
			SELECT i.incoming_collections_item_id, i.application_id, i.ssn, i.company_id
			FROM incoming_collections_item i
			WHERE
				i.incoming_collections_" . (($item === TRUE) ? "item" : "batch" ) . "_id = ?
				
		";

		$ldb_result = $this->db->queryPrepared($query, array($id));

		$app_ids = array();
		$return_array = array();
		while ($row = $ldb_result->fetch(DB_IStatement_1::FETCH_OBJ))
		{
			$return_array[] = $row;
			$app_ids[] = $row->application_id;
		}

		$data_object = ECash::getFactory()->getData('Bureau');
		$data = $data_object->getIDVInformation($app_ids);		

		$return_ids = array();
		
		foreach($return_array as $item)
		{
			if(empty($data[$item->application_id]) || $data[$item->application_id]['ssn'] != $item->ssn ||
				 $data[$item->application_id]['company_id'] != $item->company_id)
			{
				$return_ids[] = $row->incoming_collections_item_id;
			}
		}		

		return $return_ids;
	}

	public function Fetch_Inc_Coll_Records($batch_id, $exceptions = NULL)
	{
		if (is_array($exceptions) && count($exceptions))
		{
			$equery = " AND i.incoming_collections_item_id NOT IN (" . implode(",",$exceptions) . ") ";
		}

		$query = "
			SELECT
				i.*,
				if (t.action IS NOT NULL AND s.action IS NOT NULL AND s.action = t.action, t.action, 'other') as action
			FROM incoming_collections_item i
				LEFT JOIN incoming_collections_code_map t ON (i.edi_transaction_code = t.code_id AND
					i.ext_collections_co = t.ext_collections_co)
				LEFT JOIN incoming_collections_code_map s ON (i.ext_collections_status = s.code_id AND
					i.ext_collections_co = t.ext_collections_co)
			WHERE
				i.status = 'new' AND
				i.incoming_collections_batch_id = ?
				{$equery}
			GROUP BY i.incoming_collections_item_id
		";
		$st = $this->db->query($query, array($batch_id));
		return $st->fetchAll(PDO::FETCH_OBJ);
	}

	public function Inc_Coll_Item_Set_Message($item_id, $message)
	{
		if (is_array($item_id) && count($item_id))
		{
			$equery = " IN (" . implode(",",$item_id) . ") ";

		} else if (is_numeric($item_id))
		{
			$equery = " = {$item_id} ";
		} else
		{
			return;
		}

		$query = "
			UPDATE
				incoming_collections_item i
			SET
				i.result_msg = ?
			WHERE
				i.incoming_collections_item_id
				{$equery}
		";
		$this->db->queryPrepared($query, array($message));
	}

	protected function getStatusMap()
	{
		if (!$this->status_map)
		{
			$this->status_map = Fetch_Status_Map();
		}
		return $this->status_map;
	}
}

?>
