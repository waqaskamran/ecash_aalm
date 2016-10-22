<?php
/**
 * Abstract ACH Corrections Class
 *
 */
abstract class ECash_ACHReport_Corrections extends ECash_ACHReport_Process
{
	public function process($records)
	{
		$appServiceArray = array();
		foreach ($records as $record)
		{
			$record['ach_report_id'] = $this->report_id;
			$transaction_id = 0;
			$application_id = 0;
			$ach_id = $record['ach_id'];
			//fail ACH records (using return code)
			//Verify the transactionID
			list($application_id, $company_id) = $this->getApplicationID($record);
			if(! empty($application_id))
			{
				$appServiceArray[$application_id] = $record['app_updates'];
				$this->updateApplicationInfo($application_id, $record['app_updates']);
				//Update application information.
				$this->addComment($application_id, $record['comments']);
			}
			else 
			{
				$this->log->Write("Unable to locate information for ACH ID {$ach_id}");
			}
		}
		$this->SendChangesToAppService($appServiceArray);
	}
}

?>
