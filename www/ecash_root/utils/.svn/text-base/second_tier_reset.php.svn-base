<?php

/**
 * Searches 2nd tier sent applications for the given date, resets them
 * to 2nd tier pending and deletes the 2nd tier file.
 * 
 * @author Justin Foell <justin.foell@sellingsource.com>
 */

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once LIB_DIR . 'common_functions.php';
require_once SQL_LIB_DIR . 'util.func.php';

$factory = ECash::getFactory();
$db = $factory->getDB();

/**
 * there could potentially be multiple 2nd tier sents on one date
 * feel free to change this to accomodate
 */
if(!isset($argv[1]) || strtotime($argv[1]) === FALSE)
{
	echo "You must choose a date which you'd like to reset the 2nd Tier batch for.\n";
	echo "The last 10 are:\n";
	$query = "select date(date_created) as date
	from ext_collections_batch
	order by date_created desc limit 10";
	$dates = $db->querySingleColumn($query);
	foreach($dates as $date)
	{
		echo "{$date}\n";
	}
	exit(1);
}

$query = "select application_id from ext_collections where date(date_created) = ?";
$app_ids = $db->querySingleColumn($query, array($argv[1]));

foreach($app_ids as $app_id)
{
	echo "Updating $app_id to second tier (pending)\n";
	try
	{
		Update_Status(NULL, $app_id, array('pending', 'external_collections', '*root'), null, null, true);
	}
	catch(ECash_Application_NotFoundException $e)
	{
		echo $e->getMessage() . PHP_EOL;
	}
}


$query = "delete ec, ecb
from ext_collections_batch ecb
left join ext_collections ec on (ecb.ext_collections_batch_id = ec.ext_collections_batch_id)
where date(ecb.date_created) = ?";
$db->execPrepared($query, array($argv[1]));

?>