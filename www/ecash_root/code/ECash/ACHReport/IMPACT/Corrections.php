<?php
class ECash_ACHReport_IMPACT_Corrections extends ECash_ACHReport_Corrections
{
	
	public function __construct(ECash_ACHReport_IMPACT_ICorrectionConfig $config)
	{
		parent::__construct($config);
	}
	
	public function run()
	{
		$config = $this->config;
		
		if($this->getProcessState() == 'completed')
		{
			return true;
		}
		$this->setProcessState('started');
		//retrieve file
		if ($file = $this->retrieveFile($config->getImpactCorrectionsTransport(), $config->getImpactCorrectionsFileName()))
		{
			//store file
			$this->report_id = $this->storeFile($file,$config->getImpactCorrectionsFileName(),'corrections');
			//parse file
			$records = $this->parseFile($file, $config->getImpactCorrectionsParser());
			//update applications
			$this->process($records);	
			//Mark report as processed!
			$this->updateACHReportStatus($this->report_id, 'processed');
			$this->setProcessState('completed');
			if($this->exceptions->hasExceptions())
			{
				$this->exceptions->storeExceptions();
			}
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
	
}
?>