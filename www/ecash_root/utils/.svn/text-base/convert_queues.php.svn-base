<?php
	require_once('libolution/AutoLoad.1.php');

	@require_once('../www/config.php');
	@require_once(SERVER_CODE_DIR . 'queue_configuration.class.php');
	require_once(ECASH_COMMON_DIR . 'ECash/Models/Company.php');
	require_once(ECASH_COMMON_DIR . 'ECash/Models/Queue.php');
	require_once(ECASH_COMMON_DIR . 'ECash/Models/QueueGroup.php');
	require_once(ECASH_COMMON_DIR . 'ECash/Models/QueueEntry.php');

	$company = ECash::getFactory()->getModel('Company');
	$company->loadBy(array('name_short' => 'ccrt'));
	$enterprise_prefix = ECash::getConfig()->ENTERPRISE_PREFIX;
	$config_filename = BASE_DIR . "/config/{$enterprise_prefix}/company/{$company->name_short}.php";

	if(file_exists($config_filename))
	{
		@require_once($config_filename);
		$class_config = strtoupper($company->name_short) . "_CompanyConfig";
		ECash::setConfig(new $class_config(new Environment()));
	}

	$ecash_config = ECash::getConfig();
	$ecash_db = DB_DatabaseConfigPool_1::getConnection(ECash_Models_WritableModel::ALIAS_MASTER);
	$ecash_db->queryPrepared("delete from n_queue_group where company_id = ?", array($company->company_id));
	$ecash_db->queryPrepared("delete from n_queue where company_id = ?", array($company->company_id));

	$automated_queue_group = new ECash_Models_QueueGroup();
	$automated_queue_group->name_short = 'automated';
	$automated_queue_group->name = 'Automated Queues';
	$automated_queue_group->company_id = $company->company_id;
	$automated_queue_group->save();

	foreach ($ecash_config->QUEUE_CONFIG->getAllQueues() as $module_name => $modes)
	{
		foreach ($modes as $mode => $queues)
		{
			foreach ($queues as $queue)
			{
				echo "$module_name -> $mode -> " . $queue->short_name . "\n";

				$new_queue = new ECash_Models_Queue();
				$new_queue->company_id = $company->company_id;
				if ($queue->is_automated)
				{
					$new_queue->queue_group_id = $automated_queue_group->queue_group_id;
				}
				else
				{
					$new_queue->queue_group_id = NULL;
				}
				$new_queue->module = $queue->module;
				$new_queue->mode = $queue->mode;
				$new_queue->name_short = $queue->short_name;
				$new_queue->name = $queue->long_name;
				$new_queue->display_name = $queue->display_name;
				$new_queue->sort_order = "priority desc, date_available asc";
				$new_queue->save();

				$model_list = new DB_Models_ModelList_1('ECash_Models_QueueEntry', $ecash_db);

				// Now, pull every queue entry from the old queue table
				// and create a record for them here. Output in batches of 1000.
				$queue_rows = $ecash_db->queryPrepared("select * from queue where queue_name = ?", array($new_queue->name));
				while (($queue_row = $queue_rows->fetch(PDO::FETCH_OBJ)) !== FALSE)
				{
					$queue_entry = new ECash_Models_QueueEntry();
					$queue_entry->queue_id = $new_queue->queue_id;
					$queue_entry->application_id = $queue_row->key_value;
					$queue_entry->priority = NULL;
					$queue_entry->date_queued = $queue_row->date_created;
					$queue_entry->date_available = $queue_row->date_available;
					$queue_entry->dequeue_count = 1;

					$model_list->add($queue_entry);

					if ($model_list->count() > 1000)
					{
						$model_list->save();
						$model_list->clear();
					}
				}
				if ($model_list->count() > 0)
				{
					$model_list->save();
					$model_list->clear();
				}
			}
		}
	}
?>