<?php
/*
* Retrieves a standard data object from the config and performs the database updates based on the transactions given
* 
*
*/
class ECash_ACHReport_IMPACT_Returns extends ECash_ACHReport_Returns
{
	public function __construct(ECash_ACHReport_IMPACT_IReturnConfig $config)
	{
		parent::__construct($config);
	}

	public function run()
	{
		$config = $this->config;
		//Update Status.
		//Get File
		if($this->getProcessState() == 'completed')
		{
			return true;
		}
		$this->setProcessState('started');
		if ($file = $this->retrieveFile($config->getImpactReturnsTransport(), $config->getImpactReturnsFileName())) 
		{
			//Store File
			$this->report_id = $this->storeFile($file,$config->getImpactReturnsFilename(),'returns');
			//Parse File
			$records = $this->parseFile($file, $config->getImpactReturnsParser());

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
			$this->log->Write("FAIL!");
			if($this->exceptions->hasExceptions())
			{
				$this->exceptions->storeExceptions();
			}
		}
	}	

}



?>
