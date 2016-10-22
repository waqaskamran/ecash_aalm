<?php
require_once("../www/config.php");
require_once("/virtualhosts/ecash_common/ECash/Models/Application.php");
require_once("/virtualhosts/ecash_common/ECash/API/FBOD.php");

/**
 * Quick and Dirty tool to test the FBOD API and insert applications.
 */
function main()
{
	
	$data = Array (
	    'Application' => Array
	        (
	            'company' => 'fbod',
	            'loan_type' => 'gold',
	            'application_status' => 'approved::servicing::customer::*root',
	            'ip_address' => '10.20.70.81',
	            'bank_aba' => '',
	            'bank_account' => '',
	            'bank_account_type' => '',
	            'dob' => '1952-01-01',
	            'ssn' => '897979718',
	            'email' => '998444161@TSSMASTERD.COM',
	            'name_last' => 'BAGGINSTSSTEST',
	            'name_first' => 'WILLIAMTSSTEST',
	            'name_suffix' => '',
	            'name_middle' => '',
	            'employer_name' => '',
	            'job_tenure' => '',
	            'street' => '123 Test Way',
	            'unit' => '',
	            'city' => 'Harrisburg',
	            'state' => 'AK',
	            'zip_code' => '06108',
	            'phone_home' => '9425553369',
	            'phone_work' => '',
	            'phone_work_ext' => '',
	            'phone_cell' => ''
	        ),
	
	    'BureauInquiry' => Array
	        (
	            'inquiry_type' => 'idv_l5',
	            'sent_package' => '',
	            'received_package' => '',
	        ),
	
	    'CampaignInfo' => Array
	        (
	            'promo_id' => '10000',
	            'promo_sub_code' => ''
	        ),
	
	    'Site' => Array
	        (
	            'name' => 'cfcapply.tss',
	            'license_key' => '4b7a5d97ef2631f6834fb8c5d78d49f3'
	        ),
	
	    'Demographics' => Array
	        (
	            'opt_in' => 'no'
	        ),
	
	    'LoanActionHistory' => Array
	        (
	        )
	
	);
	
	$api = new ECash_API_FBOD();
	$application_id = $api->insertApplication($data);
	
	echo "New Application ID: " . $application_id . "\n";
	
}

main();

