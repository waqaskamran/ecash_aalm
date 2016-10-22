<?php
/*
* Determines the processes and configs a company uses based on database values
*
*/
class ECash_ACHReport_ReportManager
{
	public static function getProcesses($company_id = null, $loan_type_id = null)
	{
		$db = ECash::getMasterDb();
		//Query external_batch_company
		$sql = "
		SELECT 
			loan_type_id,
			name_short,
			name,
			class_name
		FROM
			external_batch_company
		JOIN
			external_batch_report ebr ON (ebr.external_batch_report_id = external_batch_company.external_batch_report_id)
		WHERE
			company_id = {$company_id}
		";

		$st = $db->query($sql);

		$processes = array();
		while (($row = $st->fetch(PDO::FETCH_ASSOC)))
		{
			//Check to see if the process is locked?
			
			//Check to see if the process has already completed/failed?
			
			//Only add the process if it qualifies?
			$processes[] = $row;
		}
		
		return $processes;

	}

	public static function getConfig(ECash_Company $company)
	{
		//return new

		$customer = getenv('ECASH_CUSTOMER');
		$config = $customer . '_ACHReport_'.strtoupper($company->name_short).'config';
		
		return new $config($company);
		
		//return new HMS_ACHReport_TGCconfig;
	}

}


?>
