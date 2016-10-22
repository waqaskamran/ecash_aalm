<?php

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once ECASH_COMMON_DIR . 'ecash_api/loan_amount_calculator.class.php';
require_once SQL_LIB_DIR . 'scheduling.func.php';

function main()
{
	$db = ECash::getMasterDb();

	$application_id = $_SERVER['argv'][1];
	if(empty($application_id)) die("No application ID supplied!\n");	

	//$ebr = new ECash_BusinessRules($db);
	//$rule_set_id = 322;
	//$rules = $ebr->Get_Rule_Set_Tree($rule_set_id);

	$application = ECash::getApplicationById($application_id);
	$rule_set_id = $application->rule_set_id;

	$data = new stdClass();
	$data->business_rules = $application->getBusinessRules();
	$data->loan_type_name = $application->getLoanType()->name;
	//$data->num_paid_applications = 0;
	$data->income_monthly = $application->income_monthly;
	$data->is_react = $application->is_react;
	
	$data->vehicle_vin = '1FTZX1727XNB74497';	
	$data->vehicle_title_state = 'CO';
	$data->vehicle_make = 'FORD TRUCK';
	$data->vehicle_model = 'F150';
	$data->vehicle_series = 'F150 Pickup-V8';
	$data->vehicle_style = 'Styleside Supercab XL';
	$data->vehicle_year = '1999';
	
	$calc = LoanAmountCalculator::Get_Instance($db, ECash::getCompany()->name_short);
	
	$data->application_list = Fetch_Application_List($application->company_id, $application->ssn, 'ssn');
	
	$data->num_paid_applications = $calc->countNumberPaidApplications($data);
	
	$loan_amounts = $calc->calculateLoanAmountsArray($data);
	$max_loan_amount = $calc->calculateMaxLoanAmount($data);
	
	echo "Max loan amount for loan number {$data->num_paid_applications} is $max_loan_amount\n";
	echo "Loan amounts available: \n" . var_export($loan_amounts, true) . "\n";
	
	echo "Rules for Loan Cap: \n" . var_export($rules['loan_cap'], true) . "\n";
	
}

main();

?>