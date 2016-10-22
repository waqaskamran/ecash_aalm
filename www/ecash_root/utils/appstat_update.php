<?php
/**
 * appstat_update.php
 * 
 * Manually updates an application's status ID
 * 
 * @author Josef Norgan <josef.norgan@sellingsource.com>
 * 
 */
require_once('../www/config.php');

if(!is_numeric($argv[1]) || !is_numeric($argv[2]))
{
	echo "{$argv[0]}: invalid parameters passed.\n";
	echo "Usage: php {$argv[0]} [APPLICATION ID] [STATUS ID]\n\n";
}
else 
{
	try 
	{
		$app = ECash::getApplicationByID($argv[1]);
		$company = ECash::getFactory()->getModel('Company');
		$company->loadBy(array('company_id' => $app->company_id));
		ECash::setCompany($company);
		$app->application_status_id = $argv[2];
		$app->save();
	}
	catch(Exception $e)
	{
		echo $e->getMessage() . "\n";
	}
}
?>