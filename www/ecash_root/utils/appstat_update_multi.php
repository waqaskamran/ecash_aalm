<?php
/**
 * appstat_update_multi.php based on appstat_update.php
 * 
 * Updates several appliations (ID specified in first column of .csv)
 * to specified status ID
 * 
 * @author Justin Foell <justin.foell@sellingsource.com>
 * 
 */

require_once dirname(realpath(__FILE__)) . '/../www/config.php';

$skip_lines = 1; //number of header lines to skip from csv


if(!file_exists($argv[1]) || !is_numeric($argv[2]))
{
	echo "{$argv[0]}: invalid parameters passed.\n";
	echo "Usage: php {$argv[0]} file.csv status_id\n\n";
}
else 
{
	//double-check args
	$fp = fopen($argv[1], 'r');
	if(!$fp)
	{
		die("Unable to open file: $argv[1]\n");
	}

	$factory = ECash::getFactory();
	$asl = $factory->getReferenceList('ApplicationStatusFlat');
	$status_name = $asl->toName($argv[2]);
	if(!$status_name)
	{
		die("Status ID not found in application_status_flat table: {$argv[2]}\n");
	}
	echo "Updating applications to status: {$status_name}\n";
	
	try 
	{
		$model_list = new DB_Models_ModelList_1('ECash_Models_Application', $factory->getDB());
		$count = 0;
		while(($line = fgetcsv($fp)) !== FALSE)
		{
			$count++;
			if($count <= $skip_lines)
			{
				continue;
			}

			//assume the app_id is the first column in the CSV
			$app_id = current($line);
			$app = ECash::getApplicationByID($app_id);
			//ECash::setCompany(new ECash_Company($factory->getDB(),$app->company_id));
			$app->application_status_id = $argv[2];
			$model_list[] = $app->Model;
		}
		$model_list->save(); echo "Applications Updated\n";
	}
	catch(Exception $e)
	{
		echo $e->getMessage() . "\n";
		echo "Did not update any applications\n";
	}
}
?>