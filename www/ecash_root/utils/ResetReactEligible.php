#!/usr/bin/php
<?php

/*
 * I don't feel like remember what I did each time or manually running this kind of query.
 * It's a cheap cheat.
 */

$o = getopt('c:a:');

if (empty($o['c']) || (empty($o['a']) && empty($o['r'])))
{
	echo <<<USAGE
{$argv[0]} -c <company short> (-a <application_id[,...]>)

This utility will reset an application to inactive paid with fund information.
This utility will refuse to work on anything but test applications.
This is best used on withdrawn/denied applications.

USAGE;
	exit;
}

require_once(dirname(__FILE__) . '/../www/config.php');
require_once(COMMON_LIB_DIR ."applog.1.php");

$db = ECash::getMasterDb();

$query = "
	UPDATE application
	SET application_status_id = 109,
		date_fund_actual = date_fund_estimated,
		fund_actual = fund_qualified
	WHERE application_id = ?
		AND (
			name_first LIKE '%tsstest%'
			OR name_last LIKE '%tsstest%'
			OR email LIKE '%tssmasterd.com'
		)
";

$updated_apps = array();
foreach (array_filter(array_map('trim', explode(',', $o['a']))) as $application_id)
{
	echo 'Setting [', $application_id, '] to Inactive (Paid)... ';

	$updated_apps[] = $application_id;
	DB_Util_1::execPrepared(
		$db,
		$query,
		array(
			$application_id,
		)
	);

	echo 'Done.', "\n";
}

$app_client = ECash::getFactory()->getAppClient();

foreach($updated_apps as $application_id)
{
	echo "Updating [{$application_id}] in the App Service... ";
	$info = $app_client->getApplicationInfo($application_id);
	$date_fund_estimated = strtotime($info->dateFundEstimated);

	if($date_fund_estimated > time())
		$date_fund_estimated = time();

	$fund_requested = $info->fundRequested;

	$update_args = array('date_fund_actual' => date('Y/m/d', $date_fund_estimated),
						 'fund_actual'	    => $fund_requested);
	if($app_client->updateApplication($application_id, $update_args))
	{
		$app_client->updateApplicationStatus($application_id, 603, 'paid::customer::*root');
		echo "Done.\n";
	}
	else
	{
		echo "Error updating application!\n";
	}
}

?>
