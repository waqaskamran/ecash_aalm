<?php

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once SQL_LIB_DIR . 'util.func.php';
require_once SQL_LIB_DIR . 'scheduling.func.php';
require_once ECASH_COMMON_DIR . 'ecash_api/ecash_api.2.php';
require_once ECASH_COMMON_DIR . 'ecash_api/loan_amount_calculator.class.php';
/**
 * VENDOR API STUFFS
 *
 * This script requires you to additionally remove the ECASH_DIR &
 * ECASH_CODE_DIR entries from ecash_cfe/www/config.php otherwise the
 * ecash_cfe/code/ECash path entry will locate VendorAPI/Qualify.php
 * rather than vendor_api/code/VendorAPI/Qualify.php
 */
define('VENDORAPI_BASE_DIR', '/virtualhosts/vendor_api/');
AutoLoad_1::addSearchPath(
	VENDORAPI_BASE_DIR . '/code/'
);


function main()
{
	
	$application_id = $_SERVER['argv'][1];
	if(empty($application_id)) die("No application ID supplied!\n");	

	$application = ECash::getApplicationById($application_id);
	$db = ECash::getMasterDb();
	$company = new ECash_Company($db, $application->company_id);
	
	$business_rules = $application->getBusinessRules();
	$holidays = Fetch_Holiday_List();
	$pay_date_calc = new Pay_Date_Calc_3($holidays);
	$qualify = new ECash_Qualify($business_rules, $pay_date_calc);

	$property_short = $company->name_short;
	$ecash_api = eCash_API_2::Get_eCash_API($property_short, $db, $application_id, $application->company_id);
	$vendor_api = new ECash_VendorAPI_Qualify(
		$qualify,
		$pay_date_calc,
		$ecash_api,
		LoanAmountCalculator::Get_Instance($db, $property_short),
		$property_short,
		$application->getLoanType()->name,
		$application->getBusinessRules(),
		ECash::getFactory());

	$tr_data = Get_Transactional_Data($application_id);
	//var_dump($tr_data); die();
	
	/**
	 * Set up the incoming application based on the one we retrieved
	 */
	$data = array();
	$data['loan_type_id'] = $application->getLoanType()->loan_type_id;
	$data['income_monthly'] = $tr_data->info->income_monthly;

	//react data
	$data['is_react'] = $tr_data->info->is_react;
	$data['num_paid_applications'] = 0;
	//$data['react_app_id'] = $data['react_application_id'];
	//$data['application_list']
	//$data['idv_increase_eligible']

	//desired amount data
	//$data['olp_process'] = 'ecashapp_react';
	//$data['loan_amount_desired'];

	//paydate data
	$data['paydate_model'] = $tr_data->info->paydate_model;
	$data['income_frequency'] = $tr_data->info->income_frequency;
	$data['income_direct_deposit'] = $tr_data->info->income_direct_deposit == 'yes' ? TRUE : FALSE;
	$data['last_paydate'] = $tr_data->info->last_paydate;
	$data['day_of_week'] = $tr_data->info->day_of_week;
	$data['day_of_month_1'] = $tr_data->info->day_of_month_1;
	$data['day_of_month_2'] = $tr_data->info->day_of_month_2;
	$data['week_1'] = $tr_data->info->week_1;
	$data['week_2'] = $tr_data->info->week_2;

	//vehicle data @TODO get from application
	$data['vin'] = '1FTZX1727XNB74497';	
	$data['title_state'] = 'CO';
	$data['make'] = 'FORD TRUCK';
	$data['model'] = 'F150';
	$data['series'] = 'F150 Pickup-V8';
	$data['style'] = 'Styleside Supercab XL';
	$data['year'] = '1999';
	$data['mileage'] = '109000';
	$data['license_plate'] = 'XXXYYY';
	//$data['type'];
	//$data['color'];
	//$data['value'];

	$ret = $vendor_api->qualifyApplication($data);
	var_dump($ret);
}

main();

?>