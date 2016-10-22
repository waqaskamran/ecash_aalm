<?php 

Class ECash_Status
{

	protected $table;
	protected $status_table;
	protected $status_model_name;
	protected $history_model_name;
	protected $parent_model_name;
	protected $agent_id;
	
	
	public function __construct($table,$agent_id,$status_table = null)
	{
		$this->table = strtolower($table);
		if(!$status_table)
		{
			$status_table = $table.'_status';
		}
		$this->status_table = strtolower($status_table);
		$this->agent_id = $agent_id;
		//Turn the table names into Model names
		$table = str_replace(' ','',(ucwords(str_replace('_',' ',$table))));
		$status_table = str_replace(' ','',(ucwords(str_replace('_',' ',$status_table))));
		
		$this->parent_model_name = $table;
		$this->status_model_name = $status_table;
		$this->history_model_name = $status_table.'History';
	}
	
  	public function getStatus($status_name)
	{

		//Look up the status
     	$status_id = $this->lookupStatus($status_name);

		//If the lookup fails, generate a new one
		if(!$status_id)
		{
			$status_id = $this->createStatus($status_name);
		}

		return $status_id;

	}

  	public function setStatus($status_name,$record_id)
	{

		//Get the proper status id
		$status_id = $this->getStatus($status_name);

		//Update the status
		$this->updateStatus($status_id,$record_id);

		//Trigger the history update
		$this->setHistory($status_id,$record_id);

	}

  	private function updateStatus($status_id,$record_id)
	{
		//update the parent table status ID ie: sreport.some_status_id
		$record = ECash::getFactory()->getModel($this->parent_model_name);
		$record->loadBy(array($this->table.'_id' => $record_id)); 
		$record->{$this->status_table.'_id'} = $status_id;
		$record->update();
	}

  	private function setHistory($status_id,$record_id)
	{
		//Insert Status History table row
		$obj = $this->history_model_name;
		$history_record = ECash::getFactory()->getModel($obj);
		$history_record->agent_id = $this->agent_id;
		$history_record->{$this->status_table.'_id'} = $status_id;
		$history_record->{$this->table.'_id'} = $record_id;
		$history_record->insert();
	}

  	private function lookupStatus($status_name)
	{
		$status_model = ECash::getFactory()->getModel($this->status_model_name);
		$status_model->loadBy(array('name_short' => $status_name));
		$status_id = $status_model->{$this->status_table.'_id'};
		return $status_id;
	}

  	private function createStatus($status_name)
	{
		//Simple query to create the status
		$status_model = ECash::getFactory()->getModel($this->status_model_name);
		$status_model->name_short = $status_name;
		$status_model->name = ucwords(str_replace('_',' ',$status_name));
		$status_model->insert();
		$status_id = $status_model->{$this->status_table.'_id'};

		return $status_id;
	}

}

        ?>
