<?
class ECash_BatchProgress
{
	/**
	 * @var ECash_Factory
	 */
	private $factory;
	
	/**
	 * @var int
	 */
	private $company_id;
	
	/**
	 * @var string
	 */
	private $batch;
	
	/**
	 * @var array
	 */
	private $message_args;

	/**
	 * Object Constructor
	 *
	 * @param ECash_Factory $factory
	 * @param int $company_id
	 * @param string $batch
	 */
	public function __construct(ECash_Factory $factory, $company_id, $batch)
	{
		$this->factory    = $factory;
		$this->company_id = $company_id;
		$this->batch      = $batch;
		
		$this->message_args['new'] = array("batch" => $this->batch, 
								  		   "company_id" => $this->company_id, 
								  		   "viewed" => FALSE);
		
		$this->message_args['old'] = array("batch" => $this->batch, 
								  		   "company_id" => $this->company_id, 
								  		   "viewed" => TRUE);
	}
	
	/**
	 * Adds a new message to the batch progress queue.
	 *
	 * @param string $message
	 * @param int $percent
	 */
	public function update($message, $percent = NULL)
	{
		$progress = $this->factory->getModel('BatchProgress');
		$progress->date_created = date('Y-m-d H:i:s');
		$progress->percent = $percent;
		$progress->message = $message;
		$progress->batch = $this->batch;
		$progress->company_id = $this->company_id;
		$progress->save();
	}
	
	/**
	 * Grabs the newest messages from the batch progress queue.
	 *
	 * @return array $percent, $message
	 */
	public function getProgress()
	{
		$message = '';
		$percent = 0;
		$counter = 0;

		//Check for new messages
		$results = $this->getMessages();
		
		//Iterate through the results
		foreach($results as $progress)
		{	
			$counter++;
			
			$new_message = $progress->message;
			$new_percent = $progress->percent;
			
			if(!empty($new_message))
				$message .= $new_message . "\n";
				
			if(!empty($new_percent) && ($new_percent > $percent))
				$percent = $new_percent;

			//Remove From Queue
			$progress->viewed = TRUE;
			$progress->save();
		}

		return array($percent, $message);
	}

	/**
	 * Will grab the proper messages
	 *
	 * @return array DB_Results
	 */
	private function getMessages()
	{
		//Check for new messages
		$Batch_Progress = $this->factory->getModel('BatchProgress');
		
		$results = $Batch_Progress->loadAllBy($this->message_args['new']);
		
		if(!count($results))
		{
			//If we don't have any new messages then load the old ones
			$results = $Batch_Progress->loadAllBy($this->message_args['old']);
		}
		else
		{
			//If there are new messages then delete the old ones
			foreach($Batch_Progress->loadAllBy($this->message_args['old']) as $old_message)
			{
				$old_message->delete();
			}
		}

		return $results;
	}

	/**
	 * Clears out the batch_progress table. Use before running the batch.
	 */
	public static function purgeMessageQueue($batch, $company_id)
	{
		$message_args = array("batch" => $batch, 
						      "company_id" => $company_id);
		
		foreach(ECash::getFactory()->getModel('BatchProgress')->loadAllBy($message_args) as $progress)
		{
			$progress->delete();
		}
	}
}
?>