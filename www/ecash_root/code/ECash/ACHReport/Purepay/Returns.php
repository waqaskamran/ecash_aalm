<?php
/*
* Retrieves a standard data object from the config and performs the database updates based on the transactions given
* 
*
*/
class ECash_ACHReport_Purepay_Returns extends ECash_ACHReport_Returns
{
	public function __construct(ECash_ACHReport_Purepay_IReturnConfig $config)
	{
		parent::__construct($config);
	}

	public function run()
	{
		
		$config = $this->config;
		//Update Status.
		//Get File
		$filename = $config->getPurepayReturnsFilename();
		if($this->getProcessState() == 'completed')
		{
			return true;
		}
		$this->setProcessState('started');
		if($file = $this->retrieveFile($config->getPurepayReturnsTransport(),$filename))
		{
			//Store File
			$this->report_id = $this->storeFile($file,$filename,'returns');
			//Parse File
			$records = $this->parseFile($file, $config->getPurepayReturnsParser());
			//Update Records
			$this->process($records);
			//Mark report as processed!
			$this->updateACHReportStatus($this->report_id, 'processed');
			$this->setProcessState('completed');
			if($this->exceptions->hasExceptions())
			{
				$this->exceptions->storeExceptions();
			}
			return true;
		}
		else 
		{
			$this->setProcessState('failed');
			if($this->exceptions->hasExceptions())
			{
				$this->exceptions->storeExceptions();
			}
		}
	}
	
	public function process($records)
	{
		
		//update the records
		foreach ($records as $record)
		{
			$record['ach_report_id'] = $this->report_id;
			$transaction_id = 0;
			$application_id = 0;
			$ach_id = $record['ach_id'];
			//fail ACH records (using return code)
			//Verify the transactionID
			if ($transaction_id = $this->getTransactionID($record))
			{
				list($application_id, $company_id) = $this->getApplicationID($record);
				if($this->isReturn($record))
				{
					$this->returnACH($record);
					//fail transaction 
					$this->failTransaction($record);
					//Hit stat
				
					//Add rescheduling standby.
					$this->setStandBy($application_id, $company_id);
				}
				elseif($this->isCorrection($record))
				{
					//update application
					if($this->updateApplicationInfo($application_id, $record['app_updates']))
					{
						$this->addComment($application_id,$record['comments']);
					}
					
				}

			}
		}
	}
	
	public function isReturn($record)
	{
		if(!$this->isCorrection($record))
		{
			return true;
		}
		return false;
	}
	
	public function isCorrection($record)
	{
		if(substr($record['reason_code'],0,1) == 'C')
		{
			return true;
		}
		return false;
	}
	

}



?>
