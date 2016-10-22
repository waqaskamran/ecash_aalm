<?php


class ECash_ExternalBatches_SecondTierPurePay extends ECash_ExternalBatches_SecondTierBatch
{
	function __construct($db)
	{
		parent::__construct($db);

		$this->columns = array(
				'customernumber' => array(),
				'lastname' => array(),
				'firstname' => array(),
				'middlename' => array(),
				'address1' => array(),
				'address2' => array(),
				'city' => array(),
				'county' => array(),
				'state' => array(),
				'zip' => array(),
				'customerphone' => array(),
				'ssn' => array(),
				'idstate' => array(),
				'cellphone' => array(),
				'employer' => array(),
				'employerphone' => array(),
				'emailaddress' => array(),
				'bankname' => array(),
				'accounttype' => array(),
				'aba' => array(),
				'accountnumber' => array(),
				'employerphoneext' => array(),
				'dob' => array(),
				'lastadvamount' => array(),
				'lastadvance' => array(),
				'last_fail_date' => array(),
				'last_fail_type' => array(),
				'last_fail_reason' => array(),
				'Lastqc' => array(),
				'principalbalance' => array(),
				'chargebalance' => array(),
				'feebalance' => array(),
				'Accountbalance' => array(),
				'idnumber' => array(),
				'idtype' => array(),
				'companycode' => array(),
				'sitename' => array(),
				'companyname' => array(),
				
		);


		$this->headers = TRUE;
		$this->format  = 'csv';
	}


	public function process()
	{
		$this->updateProgress("Processing records...",5);
		if ($this->company_id == NULL)
			throw new Exception('Second Tier batch requires a company ID');

		$data_object = ECash::getFactory()->getData('SecondTier');
		$app_data = $data_object->getApplicationBatchData($this->application_ids);

		$application_list = implode(',', $this->application_ids);

		$query = "
			SELECT
				ls.application_id,
				ls.principalbalance                                           AS principal_balance,
				ls.balance_complete                                           AS balance,
				ls.feebalance                                                 AS fee_balance,
				ls.chargebalance                                              AS interest_balance,
				
				ls.balance_complete                                           AS accountbalance,
				IFNULL(date_format(qc.lastqc, '%m/%d/%Y'),'  /  /')           AS Lastqc,
				lf.fail_date_formatted                                        AS last_fail_date,
				lf.name                                                       AS last_fail_type,
				lf.reason                                                     AS last_fail_reason,
				ls.principalbalance                                           AS principalbalance,
				ls.chargebalance                                              AS chargebalance,
				ls.feebalance                                                 AS feebalance,
				ls.balance_complete                                           AS Accountbalance,

				upper(co.name_short)               							  AS companycode

			FROM
			(
				SELECT
					SUM(ea.amount) balance_complete,
					tr.application_id application_id,
					tr.company_id,
					SUM(IF(eat.name_short='principal', ea.amount, 0)) principalbalance,
					SUM(IF(eat.name_short='service_charge', ea.amount, 0)) chargebalance,
					SUM(IF(eat.name_short='fee', ea.amount, 0)) feebalance
				FROM
					transaction_register AS tr
				JOIN 
					event_amount AS ea USING (event_schedule_id, transaction_register_id)
				JOIN 
					event_amount_type AS eat USING (event_amount_type_id)
				WHERE
					tr.transaction_status = 'complete'
				AND
					tr.application_id in ({$application_list})
				GROUP BY 
					application_id
			) AS ls 
		 	LEFT JOIN 
			(
				SELECT
					tr.application_id AS application_id,
					MAX(tr.date_created) AS lastqc
				FROM transaction_register AS tr
				JOIN 
					transaction_type AS tt USING (transaction_type_id)
				WHERE 
					tt.name_short = 'quickcheck'
				GROUP 
					BY application_id
			) AS qc ON (qc.application_id = ls.application_id)
			LEFT JOIN
			(
				SELECT
					itr.application_id  AS application_id,
					DATE_FORMAT((
						SELECT MAX(date_created)
						FROM transaction_history AS th
						WHERE th.transaction_register_id = itr.transaction_register_id
						AND th.status_after = 'failed'
					), '%m/%d/%Y') AS fail_date_formatted,
					itr.date_modified   AS modify_date,
					IF(iach.ach_id IS NULL, iarcb.name, iarca.name) AS reason,
					itt.name            AS name
				FROM
					transaction_register itr
				JOIN
					transaction_type itt ON (itt.transaction_type_id = itr.transaction_type_id)
				LEFT JOIN
					ach iach ON (iach.ach_id = itr.ach_id)
				LEFT JOIN
					ecld iecld ON (iecld.ecld_id = itr.ecld_id)
				LEFT JOIN
					ach_return_code iarca ON (iarca.ach_return_code_id = iach.ach_return_code_id)
				LEFT JOIN
					ach_return_code iarcb ON (iarcb.name_short = iecld.return_reason_code)
				WHERE
					itr.transaction_status = 'failed'
				ORDER BY
					itr.date_modified 
				DESC
			) AS lf ON (lf.application_id = ls.application_id)
			JOIN company co on (co.company_id = ls.company_id)
			GROUP BY
				ls.application_id
		 	ORDER BY 
				ls.application_id
		";
		$st = $this->db->query($query);

		$crypt = new ECash_Models_Encryptor(ECash::getMasterDb());

		while (($row = $st->fetch(PDO::FETCH_ASSOC)))
		{
			$row['companyname'] = ECash::getConfig()->COMPANY_NAME_LEGAL;
			$data = $app_data[$row['application_id']];
			$row['customernumber'] = $data['application_id'];
			$row['lastname'] = $data['LastName'];
			$row['firstname'] = $data['FirstName'];
			$row['middlename'] =  $data['name_middle'];
			$row['address1'] = $data['address1'];
			$row['address2'] = $data['address2'];
			$row['city'] = $data['City'];
			$row['county'] = $data['county'];
			$row['state'] = $data['State'];
			$row['zip'] = $data['Zip'];
			$row['customerphone'] = $data['CustomerPhone'];
			$row['ssn'] = $data['SSN'];
			$row['idstate'] = $data['legal_id_state'];
			$row['cellphone'] = $data['CellPhone'];
			$row['employer'] = $data['Employer'];
			$row['employerphone'] = $data['EmployerPhone'];
			$row['emailaddress'] = $data['EmailAddress'];
			$row['bankname'] = $data['bank_name'];
			$row['accounttype'] = $data['AccountType'];
			$row['aba'] = $data['ABA'];
			$row['accountnumber'] = $data['AccountNumber'];
			$row['employerphoneext'] = $data['EmployerPhoneExt'];
			$row['dob'] = $data['DOB'];
			$row['lastadvamount'] = $data['LastAdvAmount'];
			$row['lastadvance'] = $data['LastAdvance'];
			$row['idnumber'] = $data['legal_id_number'];
			$row['idtype'] = $data['idtype'];
			$row['sitename'] = $data['site_name'];

			$this->data[$row['application_id']] = $row;
		}

		return TRUE;
	}

	protected function postprocess()
	{
		
		$this->updateProgress("Running PostProcessing",15);
		//Save it to the database, first and foremost!
		$this->saveToDb();
		$company_id       = ECash::getCompany()->company_id;
		$sreport_id 	  = $this->sreport_id;
		// Get Batch company type for this batch 

		// Create the ext collections batch
		$ext_batch = ECash::getFactory()->getModel('ExtCollectionsBatch');
		$ext_batch->date_created     = date('Y-m-d H:i:s');
		$ext_batch->date_modified    = date('Y-m-d H:i:s');
		$ext_batch->company_id       = $this->company_id;
		$ext_batch->sreport_id       = $sreport_id;

		// No longer using this
		$ext_batch->ec_file_outbound   = NULL;

		$ext_batch->item_count         = $this->getAppCount();
		$ext_batch->ec_filename        = $this->getFilename();
		$ext_batch->is_adjustment      = 0;

		$ext_batch->external_batch_report_id = $this->external_batch_report_id;

		$ext_batch->save();

		$app_data = $this->getAppData();

		$this->updateProgress("Adding Standby entries",5);
		foreach ($this->application_ids as $application_id)
		{
			Set_Standby($application_id, $this->company_id, 'external_batch');
		}
		$this->updateProgress("Writing Batch details entries",5);
		// Save individual details
		foreach ($app_data as $app)
		{
			$ext_col = ECash::getFactory()->getModel('ExtCollections');
			$ext_col->date_created  = date('Y-m-d H:i:s');
			$ext_col->date_modified = date('Y-m-d H:i:s');

			$ext_col->company_id               = ECash::getCompany()->company_id;
			$ext_col->application_id           = $app['customernumber'];
			$ext_col->ext_collections_batch_id = $ext_batch->ext_collections_batch_id;
			$ext_col->current_balance          = $app['Accountbalance'];
			$ext_col->save();
		}
		$i = 0;
		$this->updateProgress("Updating application statuses",5);
        foreach ($this->application_ids as $application_id)
        {
        	$i++;
        	if($i % 50 == 0)
        	{
        		$this->updateProgress("Updated $i applications", 1);
        	}
        	try 
        	{
	            if ($this->after_status != NULL)
	            {
	                Update_Status(NULL, $application_id, $this->after_status, null, null, true);
	            }
	
	            if ($this->dequeue === TRUE)
	            {
	                $qm = ECash::getFactory()->getQueueManager();
	                $qm->removeFromAllQueues(new ECash_Queues_BasicQueueItem($application_id));
	            }
	            Remove_Standby($application_id, 'external_batch');
        	}
			catch (Exception $e)
			{
				$this->log->Write("There was an error updating {$application_id} -  This will need to be updated!");
				$this->batch_exceptions[] = "There was an error updating {$application_id} -  This will need to be updated!";
				$this->updateProgress("There was an error updating application {$application_id} .. this application may not have had it's status updated",0);
				
			}
        }
		$this->updateProgress("Statuses updated, applications dequeued",10);
        return TRUE;
	}

}

?>
