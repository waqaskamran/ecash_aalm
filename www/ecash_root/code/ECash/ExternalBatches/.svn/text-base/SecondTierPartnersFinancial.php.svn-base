<?php

class ECash_ExternalBatches_SecondTierPartnersFinancial extends ECash_ExternalBatches_SecondTierBatch
{
	function __construct($db)
	{
		parent::__construct($db);

		$this->filename_extension = 'csv';
		$this->format   = 'csv';

		$this->columns = array(
			'SSN'                    => array(),
			'First Name'              => array(),
			'Last Name'               => array(),
			'Address'                => array(),
			'City'                   => array(),
			'State'                  => array(),
			'Zip code'                    => array(),
			'Home phone'          => array(),
			'Other Phone'              => array(),
			'Employer name'            => array(),
			'Employer phone'          => array(),
			'Balance'         => array(),
			'Interest' => array(),
			'Monthly Payment' => array(),
			'Claimant' => array(),
			'Origination Date' => array(),
			'Date of birth'                    => array(),
			'Co-signer first name' => array(),
			'Co-signer last name' => array(),
			'Co-signer SSN' => array(),
			'Co-signer Address' => array(),
			'Co-signer city' => array(),
			'Co-signer state' => array(),
			'Co-signer zip code' => array(),
			'Co-signer phone' => array(),
			'Spouse name' => array(),
			'Your internal account identifier' => array(),
			'Reference name 1'         => array(),
			'Reference phone 1'        => array(),
			'Reference name 2'         => array(),
			'Reference phone 2'        => array(),
			'Reference name 3'         => array(),
			'Reference phone 3'        => array(),
			'Reference name 4'         => array(),
			'Reference phone 4'        => array(),
			'Reference name 5'         => array(),
			'Reference phone 5'        => array(),
			'Reference name 6'         => array(),
			'Reference phone 6'        => array(),
			'Patient' => array(),
			'Responsible Party' => array(),
			'Insurance Pending' => array(),
			'Insurance Paid' => array(),
			'Services' => array(),
			'Accident or Work Comp' => array(),
			'Comments' => array(),
			'Location Code' => array(),
		);
	}

	// We've got a list of application IDs we're working on 
	// get the extra data related to this to fill into the data
	// member
	public function process()
	{
		$this->updateProgress('Processing applications for batch',15);
		if ($this->company_id == NULL)
			throw new Exception('Second Tier batch requires a company ID');

		if(empty($this->application_ids))
			throw new Exception('No applications to process');

		$data_object = ECash::getFactory()->getData('SecondTier');
		$app_data = $data_object->getApplicationBatchData($this->application_ids);
		
		$application_list = implode(',', $this->application_ids);

		$query = "
		select
			ls.application_id,
			ls.Balance,
			ls.Balance as AccountBalance,			
			'' as Interest,
			'' as `Monthly Payment`,
			'' as Claimant,
			(	SELECT date_effective
				FROM
				transaction_register AS tr
				JOIN transaction_type tt on (tt.transaction_type_id = tr.transaction_type_id)
				WHERE tr.application_id = ls.application_id
				AND tr.transaction_status = 'complete'
				AND (tr.amount < 0 OR tt.name_short = 'loan_disbursement')
				ORDER BY date_effective desc limit 1) as `Origination Date`,
			'' as `Co-signer first name`,
			'' as `Co-signer last name`,
			'' as `Co-signer SSN`,
			'' as `Co-signer Address`,
			'' as `Co-signer city`,
			'' as `Co-signer state`,
			'' as `Co-signer zip code`,
			'' as `Co-signer phone`,
			'' as `Spouse name`,
			'' as Patient,
			'' as `Responsible Party`,
			'' as `Insurance Pending`,
			'' as `Insurance Paid`,
			'' as Services,
			'' as `Accident or Work Comp`,
			'' as Comments,
			'' as `Location code`
			from
					(
						SELECT
							SUM(ea.amount) as Balance,
							tr.application_id
						FROM
							transaction_register AS tr
						JOIN 
							event_amount AS ea USING (event_schedule_id, transaction_register_id)
						JOIN 
							event_amount_type AS eat USING (event_amount_type_id)
						WHERE
							tr.application_id IN ({$application_list})
						AND 
							tr.transaction_status = 'complete'
						GROUP BY 
							tr.application_id) as ls 

					GROUP BY
						ls.application_id
				 	ORDER BY 
						ls.application_id
				";

		$st = $this->db->query($query);

		while (($row = $st->fetch(PDO::FETCH_ASSOC)))
		{
			$data = $app_data[$row['application_id']];
			$row['SSN'] = $data['SSN'];
			$row['First Name'] = $data['FirstName'];
			$row['Last Name']               = $data['LastName'];
			$row['Address']                = $data['Address'];
			$row['City']                   = $data['City'];
			$row['State']                  = $data['State'];
			$row['Zip code']                    = $data['Zip'];   //fixed as part of
			$row['Home phone']          = $data['CustomerPhone']; //[#53232]
			$row['Other Phone']              = $data['CellPhone'];
			$row['Employer name']               = $data['Employer'];
			$row['Employer phone']          = $data['EmployerPhone'];
			$row['Date of birth']                    = $data['DOB'];
			$row['Your internal account identifier'] = $row['application_id'];
			$row['Reference name 1']         = $data['ReferenceName1'];
			$row['Reference phone 1']        = $data['ReferencePhone1'];
			$row['Reference name 2']         = $data['ReferenceName2'];
			$row['Reference phone 2']        = $data['ReferencePhone2'];
			$row['Reference name 3']         = $data['ReferenceName3'];
			$row['Reference phone 3']        =  $data['ReferencePhone3'];
			$row['Reference name 4']         = $data['ReferenceName4'];
			$row['Reference phone 4']        = $data['ReferencePhone4'];
			$row['Reference name 5']         = $data['ReferenceName4'];
			$row['Reference phone 5']        = $data['ReferencePhone4'];
			$row['Reference name 6']         = $data['ReferenceName4'];
			$row['Reference phone 6']        = $data['ReferencePhone4'];		

			$this->data[$row['application_id']] = $row;
		}

		return TRUE;
	}
}

?>
