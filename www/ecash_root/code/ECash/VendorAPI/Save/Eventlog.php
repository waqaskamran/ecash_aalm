<?php

class ECash_VendorAPI_Save_EventLog implements VendorAPI_Save_ITableHandler
{
	/**
	 * @var ECash_Factory
	 */
	protected $factory;

	/**
	 * @var ECash_VendorAPI_Driver
	 */
	protected $driver;

	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	public function __construct(ECash_Factory $factory, ECash_VendorAPI_Driver $driver, DB_IConnection_1 $db)
	{
		$this->factory = $factory;
		$this->driver = $driver;
		$this->db = $db;

		$this->events = $factory->getReferenceList('EventlogEvent', $this->db);
		$this->responses = $factory->getReferenceList('EventlogResponse', $this->db);
		$this->targets = $factory->getReferenceList('EventlogTarget', $this->db);
	}

	public function saveTo(array $data, DB_Models_Batch_1 $batch)
	{
		if (is_numeric($data['application_id'])) 
		{
			$model = $this->factory->getModel('Eventlog', $this->db);
		
			$model->application_id = $data['application_id'];
			$model->date_created = $data['date_created'];
			$model->eventlog_event_id = $this->findEventID($data['event']);
			$model->eventlog_response_id = $this->findResponseID($data['response']);
			$model->eventlog_target_id = $this->findTargetID($data['target']);

			$batch->save($model);
		}
	}

	protected function findEventID($name)
	{
		$id = $this->events->toID($name);

		if ($id === FALSE)
		{
			$model = $this->factory->getReferenceModel('EventlogEvent', $this->db);
			$model->name_short = $name;
			$model->insert();

			$this->events[] = $model;
			$id = $model->eventlog_event_id;
		}

		return $id;
	}

	protected function findResponseID($name)
	{
		$id = $this->responses->toID($name);

		if ($id === FALSE)
		{
			$model = $this->factory->getReferenceModel('EventlogResponse', $this->db);
			$model->name_short = $name;
			$model->insert();

			$this->responses[] = $model;
			$id = $model->eventlog_response_id;
		}

		return $id;
	}

	protected function findTargetID($name)
	{
		if (empty($name)) $name = "unknown";
		$id = $this->targets->toID($name);

		if ($id === FALSE)
		{
			$model = $this->factory->getReferenceModel('EventlogTarget', $this->db);
			$model->name_short = $name;
			$model->insert();

			$this->targets[] = $model;
			$id = $model->eventlog_target_id;
		}

		return $id;
	}
}

?>
