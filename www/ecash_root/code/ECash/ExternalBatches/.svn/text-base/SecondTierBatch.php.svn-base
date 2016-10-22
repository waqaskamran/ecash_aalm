<?php

// This is the default 2nd tier class
class ECash_ExternalBatches_SecondTierBatch extends ECash_ExternalBatches_ExternalBatch
{
	function __construct($db)
	{
		parent::__construct($db);

		$this->before_status = array('pending', 'external_collections', '*root');
		$this->after_status  = array('sent',    'external_collections', '*root');

		$this->filename = 'second_tier' . '_' . date('Ymd') . '_' . ECash::getCompany()->name_short;
		$this->filename_extension = 'csv';
		$this->format   = 'csv';

		$this->headers        = TRUE;
		$this->quoted_headers = TRUE;

		// dequeue afterwards
		$this->dequeue  = TRUE;

		$this->sreport_type      = 'second_tier_batch';
		$this->sreport_data_type = 'second_tier_batch';
		
		$this->columns = array(
			"Lender"               	=> array(),
			"LoanID"               	=> array(),
			"SSN"           	=> array(),
			"DOB"               	=> array(),
			"FirstName"            	=> array(),
			"LastName"             	=> array(),
			"MI"               	=> array(),
			"Address1"              => array(),
			"Address2"              => array(),
			"City"               	=> array(),
			"State"               	=> array(),
			"Zip"               	=> array(),
			"HomeStatus"            => array(),
			"MonthsAtAddress"	=> array(),
			"HomePhone"             => array(),
			"MobilePhone"           => array(),
			"WorkPhone"             => array(),
			"WorkExtension"         => array(),
			"Email"               	=> array(),
			"Employer"            	=> array(),
			"JobTitle"            	=> array(),
			"MonthsEmployed"      	=> array(),
			"PayMethod"           	=> array(),
			"PayFrequency"        	=> array(),
			"MonthlyIncome"         => array(),
			"BankAccountType"       => array(),
			"BankName"              => array(),
			"Routing"               => array(),
			"Checking"              => array(),
			"FundDate"              => array(),
			"FundAmount"            => array(),
			"PrincipalBalance"      => array(),
			"InterestBalance"       => array(),
			"TotalBalance"          => array(),
			"WriteOffDate"          => array(),
			"WriteOffReason"        => array(),
			"LastPaymentDate"       => array(),
			"LastPaymentAmount"     => array(),
			"LastPaymentStatus"     => array(),
			"Ref1Name"              => array(),
			"Ref1Phone"             => array(),
			"Ref1Relationship"      => array(),
			"Ref2Name"              => array(),
			"Ref2Phone"             => array(),
			"Ref2Relationship"      => array(),
			"Ref3Name"              => array(),
			"Ref3Phone"             => array(),
			"Ref3Relationship"      => array(),
		);
	}

	// We've got a list of application IDs we're working on 
	// get the extra data related to this to fill into the data
	// member
	public function process()
	{
		$this->updateProgress("Processing applications for batch",15);
		if ($this->company_id == NULL)
			throw new Exception('Second Tier batch requires a company ID');

		$application_list = implode(',', $this->application_ids);

		$query = "
		SELECT
			ap.application_id,
			-- ap.encryption_key_id AS encryption_key_id,
			'Multiloansource.net' AS Lender,
			ap.application_id AS LoanID,
			ap.ssn AS SSN,
			-- ap.dob AS dob,
			DATE_FORMAT(ap.dob, '%m/%d/%Y') AS DOB,
			ap.name_first AS FirstName,
			ap.name_last AS LastName,
			LEFT(ap.name_middle,1) AS MI,
			ap.street AS Address1,
			ap.unit AS Address2,
			ap.city AS City,
			ap.state AS State,
			ap.zip_code AS Zip,
			(CASE 
			WHEN ap.tenancy_type = 'own' THEN 'W'
			WHEN ap.tenancy_type = 'rent' THEN 'R'
			ELSE NULL
			END) AS HomeStatus,
			NULL AS MonthsAtAddress,
			ap.phone_home AS HomePhone,
			ap.phone_cell AS MobilePhone,
			ap.phone_work AS WorkPhone,
			ap.phone_work_ext AS WorkExtension,
			ap.email AS Email,
			ap.employer_name AS Employer,
			ap.job_title AS JobTitle,
			NULL AS MonthsEmployed,
			IF (ap.income_direct_deposit = 'yes', 'D', 'P') AS PayMethod,
			(CASE 
			WHEN ap.income_frequency = 'weekly' THEN 'W'
			WHEN ap.income_frequency = 'bi_weekly' THEN 'B'
			WHEN ap.income_frequency = 'twice_monthly' THEN 'S'
			WHEN ap.income_frequency = 'monthly' THEN 'M'
			ELSE 'O'
			END) AS PayFrequency,
			ap.income_monthly AS MonthlyIncome,
			IF (ap.bank_account_type = 'checking', 'C', 'S') AS BankAccountType,
			ap.bank_name AS BankName,
			ap.bank_aba AS Routing,
			ap.bank_account AS Checking,
			DATE_FORMAT(ap.date_fund_actual, '%m/%d/%Y') AS FundDate,
			ap.fund_actual AS FundAmount,
			b.principalbalance AS PrincipalBalance,
			b.interestbalance AS InterestBalance,
			b.totalbalance AS TotalBalance,
			DATE_FORMAT(ap.date_application_status_set, '%m/%d/%Y') AS WriteOffDate,
			IFNULl(lf.reason, 'External payment failed') AS WriteOffReason,
			lp.last_payment_date AS LastPaymentDate,

			(SELECT SUM(ABS(tr1.amount))
			FROM transaction_register AS tr1
			JOIN transaction_type tt1 ON (tt1.company_id = {$this->company_id} AND tt1.transaction_type_id = tr1.transaction_type_id)
			WHERE tr1.application_id = ap.application_id
			AND tt1.clearing_type IN ('ach','card','external')
			AND tr1.amount < 0
			AND NOT EXISTS
			(SELECT tr2.transaction_register_id
			FROM transaction_register AS tr2
			JOIN transaction_type tt2 ON (tt2.company_id = {$this->company_id} AND tt2.transaction_type_id = tr2.transaction_type_id)
			WHERE tr2.application_id = ap.application_id
			AND tt2.clearing_type IN ('ach','card','external')
			AND tr2.amount < 0
			AND tr2.date_effective > tr1.date_effective
			)
			GROUP BY tr1.date_effective
			) AS LastPaymentAmount,

			IF(lp.transaction_status = 'complete', 'C', 'R') AS LastPaymentStatus,

			pr1.name_full AS Ref1Name,
			pr1.phone_home AS Ref1Phone,
			pr1.relationship AS Ref1Relationship,
			pr2.name_full AS Ref2Name,
			pr2.phone_home AS Ref2Phone,
			pr2.relationship AS Ref2Relationship,
			pr3.name_full AS Ref3Name,
			pr3.phone_home AS Ref3Phone,
			pr3.relationship AS Ref3Relationship


			FROM
				application AS ap
			
			LEFT JOIN 
			(
			SELECT
				ap1.application_id,
				SUM(IF(eat.name_short = 'principal', ea.amount, 0)) AS principalbalance,
				SUM(IF(eat.name_short IN ('service_charge','fee'), ea.amount, 0)) AS interestbalance,
				SUM(ea.amount) AS totalbalance
			FROM
				application AS ap1
			JOIN
				event_schedule AS es ON (es.application_id = ap1.application_id)
			JOIN
				transaction_register AS tr ON (tr.application_id = ap1.application_id
								AND tr.event_schedule_id = es.event_schedule_id
								AND tr.transaction_status = 'complete')
			JOIN
				event_amount AS ea ON (ea.application_id = ap1.application_id
							AND ea.event_schedule_id = es.event_schedule_id
							AND ea.transaction_register_id = tr.transaction_register_id)
			JOIN
				event_amount_type AS eat ON (eat.event_amount_type_id = ea.event_amount_type_id
								AND eat.name_short <> 'irrecoverable')
			GROUP BY ap1.application_id
			) AS b ON (b.application_id = ap.application_id)

			LEFT JOIN
			(
			SELECT
				itr.application_id AS application_id,
				IF(iach.ach_id IS NULL, cpr.response_text, iarca.name) AS reason
				FROM
					transaction_register AS itr
				JOIN
					transaction_type itt ON (itt.company_id = {$this->company_id}
								AND itt.transaction_type_id = itr.transaction_type_id)
				LEFT JOIN
					ach iach ON (iach.ach_id = itr.ach_id)
				LEFT JOIN
					card_process AS cp ON (cp.card_process_id = itr.card_process_id)
				LEFT JOIN
					ach_return_code iarca ON (iarca.ach_return_code_id = iach.ach_return_code_id)
				LEFT JOIN
					card_process_response AS cpr ON (cpr.reason_code = cp.reason_code)
				WHERE
					itr.transaction_status = 'failed'
					AND itt.clearing_type IN ('ach','card')
				ORDER BY itr.date_modified DESC
			) AS lf ON (lf.application_id = ap.application_id)

			LEFT JOIN
			(
			SELECT
				itr1.application_id AS application_id,
				DATE_FORMAT(itr1.date_effective, '%m/%d/%Y') AS last_payment_date,
				itr1.transaction_status
			FROM
				transaction_register AS itr1
			JOIN
				transaction_type itt1 ON (itt1.company_id = {$this->company_id}
								AND itt1.transaction_type_id = itr1.transaction_type_id)
			WHERE
				itr1.amount < 0
				AND itt1.clearing_type IN ('ach','card','external')
			ORDER BY itr1.transaction_register_id DESC
			) AS lp ON (lp.application_id = ap.application_id)

			LEFT JOIN
			(
			SELECT
				application_id,
				name_full,
				phone_home,
				relationship
			FROM
				personal_reference
			ORDER BY personal_reference_id DESC LIMIT 1 OFFSET 0
			) AS pr1 ON (pr1.application_id = ap.application_id)
			
			LEFT JOIN
			(
			SELECT
				application_id,
				name_full,
				phone_home,
				relationship
			FROM
				personal_reference
				ORDER BY personal_reference_id DESC LIMIT 1 OFFSET 1
			) AS pr2 ON (pr2.application_id = ap.application_id)
			
			LEFT JOIN
			(
			SELECT
				application_id,
				name_full,
				phone_home,
				relationship
			FROM
				personal_reference
			ORDER BY personal_reference_id DESC LIMIT 1 OFFSET 2
			) AS pr3 ON (pr3.application_id = ap.application_id)

			WHERE
				ap.company_id = {$this->company_id}
			AND
				ap.application_id IN ({$application_list})
			GROUP BY
				ap.application_id
			ORDER BY 
				ap.application_id
		";
		$st = $this->db->query($query);

		//$crypt = new ECash_Models_Encryptor(ECash::getMasterDb());

		while (($row = $st->fetch(PDO::FETCH_ASSOC)))
		{
			//$row['SSN'] = $crypt->decrypt($row['SSN'], $row['encryption_key_id']);
			//$row['Checking'] = $crypt->decrypt($row['Checking'], $row['encryption_key_id']);
			//$dob = $crypt->decrypt($row['dob'], $row['encryption_key_id']);
			//unset($row['dob']);
			//$row['DOB'] = ($dob == NULL) ? ' / / ' : $dob;
			
			$this->data[$row['application_id']] = $row;
		}

		return TRUE;
	}

	public function run($num_apps = null)
	{
		if($this->external_batch_report_id == null)
		{
			throw new Exception('External Batch Report ID was not set! External Batch Report ID needs to be set using setExternalBatchReportId($id)!');
		}
		parent::run($num_apps);
	}
	protected function postprocess()
	{
		$this->updateProgress("Running post processing",10);
		//Save it to the database, first and foremost!
		$this->saveToDb();
		$company_id       = $this->company_id;

		// Create the ext collections batch
		$ext_batch = ECash::getFactory()->getModel('ExtCollectionsBatch');
		$ext_batch->date_created     = date('Y-m-d H:i:s');
		$ext_batch->date_modified    = date('Y-m-d H:i:s');
		$ext_batch->company_id       = ECash::getCompany()->company_id;
		$ext_batch->sreport_id       = $this->sreport_id;

		// No longer using this
		$ext_batch->ec_file_outbound   = NULL;

		$ext_batch->item_count         = $this->getAppCount();
		$ext_batch->ec_filename        = $this->getFilename();
		$ext_batch->is_adjustment      = 0;

		$ext_batch->external_batch_report_id = $this->external_batch_report_id;

		$ext_batch->save();

		$app_data = $this->getAppData();

		// Save individual details
		foreach ($app_data as $app)
		{
			$ext_col = ECash::getFactory()->getModel('ExtCollections');
			$ext_col->date_created  = date('Y-m-d H:i:s');
			$ext_col->date_modified = date('Y-m-d H:i:s');

			$ext_col->company_id               = ECash::getCompany()->company_id;
			$ext_col->application_id           = $app['application_id'];
			$ext_col->ext_collections_batch_id = $ext_batch->ext_collections_batch_id;
			$ext_col->current_balance          = $app['TotalBalance'];
			$ext_col->save();
		}

		$this->updateProgress("Updating applications status",5);
		
		foreach ($this->application_ids as $application_id)
		{
			if ($this->after_status != NULL)
			{
				$this->updateProgress("Updating {$application_id}'s status",.001);
				Update_Status(NULL, $application_id, $this->after_status, null, null, true);
			}

			if ($this->dequeue === TRUE)
			{
                                $this->updateProgress("Removing {$application_id} from all queues",.001);
				$qm = ECash::getFactory()->getQueueManager();
				$qm->removeFromAllQueues(new ECash_Queues_BasicQueueItem($application_id));
			}
			
		}
		$this->updateProgress("Updated application status and dequeued applications",5);
		return TRUE;

	}

}

?>
