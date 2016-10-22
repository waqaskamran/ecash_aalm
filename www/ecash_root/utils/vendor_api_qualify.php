<?php

/**
 * VENDOR API STUFFS
 *
 * You must have a VENDORAPI_BASE_DIR . '../ecash_commercial'
 * directory or symlink to ecash_cfe
 *
 * You must have a VENDORAPI_BASE_DIR 
 */
define('LIBOLUTION_DIR', '/virtualhosts/libolution/');
define('VENDORAPI_BASE_DIR', '/virtualhosts/vendor_api/');

require_once(LIBOLUTION_DIR . 'AutoLoad.1.php');
AutoLoad_1::addSearchPath(
	VENDORAPI_BASE_DIR . 'code/',
	VENDORAPI_BASE_DIR . 'lib/'
);

function main()
{
	$company = $_SERVER['argv'][1];
	if(empty($company) || is_numeric($company))
		die("Invalid company supplied: {$company}\n");

	$application_id = $_SERVER['argv'][2];
	if(empty($application_id) || !is_numeric($application_id))
		die("Invalid application ID supplied: {$application_id}\n");

	$mode = getenv('ECASH_EXEC_MODE');
	
	$loader = new VendorAPI_Loader(getenv('ECASH_CUSTOMER'),
								   $company,
								   ($mode == 'Local' ? 'DEV' : $mode) //some hacky bullshit
								   );
	$loader->bootstrap();

	$driver = $loader->getDriver();
	
	// just need dummies of these
	$state_object = new VendorAPI_StateObject();
	$persistor = new VendorAPI_StateObjectPersistor($state_object);

	$af = $driver->getApplicationFactory();
	$app = $af->getApplication(
		$application_id,
		$persistor,
		$state_object
		);

	$data = $app->getData();
	
	$post = $driver->getAction('post');
	$context = new VendorAPI_CallContext();
	$context->setCompanyId($data['company_id']);
	$post->setCallContext($context);
	
	//massage the $data
	$data['page_id'] = 0;
	$data['promo_id'] = '99999';
	$data['campaign_info'] = array();
	$data['income_monthly'] = number_format($data['income_monthly'], 0, '', '');
	
	//vehicle data @TODO get from application
	$data['is_title_loan'] = 1;
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

	var_dump($data);
	
	$response = $post->execute($data);
	//var_dump($response);

	var_dump($response->getStateObject());

	//$result = $response->getResult();
	//var_dump($result);
	//var_dump($app->calculateQualifyInfo());
	
}

main();

?>