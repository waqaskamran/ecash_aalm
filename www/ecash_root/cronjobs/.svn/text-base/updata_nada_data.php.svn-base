<?php

require_once( SQL_LIB_DIR."util.func.php");
require_once( ECASH_COMMON_DIR."/nada/NADA_Import.5.php");

function Main($argv)
{

	/**
	 * To be run at the wee hours o' the mornin'.
	 * gets the current flat files from tmp directory, backs up the existing tables, replaces current data with the new
	 * deletes the flat files from the tmp directory
	 * 
	 * I'm aware of the file name, but it rhymes!!!!!!!
	 */
	
	

	$temp_dir = ECash::getConfig()->NADA_TEMP_DIR;
	$backup_dir = ECash::getConfig()->NADA_BACKUP_DIR;
	$db = ECash::getMasterDb();
	$nada_import = new NADA_Import_V5($db);
	
	//list of the tables/files to update
	$tables = array(
	'VehicleDescription' =>	array('name'=>'VicDescriptions.DAT','length'=>218,'table'=>'nada_vehicle_description'),
	'VehicleValues' =>			array('name'=>'VicValues.DAT','length'=>29,'table'=>'nada_vehicle_value'),
	'VehicleSegments' =>	array('name'=>'VehicleSegments.DAT','length'=>48,'table'=>'nada_vehicle_segment'),
	'TruckDuties' =>		array('name'=>'TruckDuties.DAT','length'=>46,'table'=>'nada_truck_duty'),
	'VehicleAttributes' =>		array('name'=>'VicAttributes.DAT','length'=>277,'table'=>'nada_vehicle_attribute'),
	'AttributeType' =>		array('name'=>'AttributeTypes.DAT','length'=>48,'table'=>'nada_attribute_type'),
	'AccessoryDescription' =>	array('name'=>'VacDescriptions.DAT','length'=>51,'table'=>'nada_accessory_description'),
	'AccessoryValue' =>			array('name'=>'VacValues.DAT','length'=>30,'table'=>'nada_accessory_value'),
	'AccessoryCategory' =>		array('name'=>'VacCategories.DAT','length'=>48,'table'=>'nada_accessory_category'),
	'AccessoryExclude' =>		array('name'=>'VacExcludes.DAT','length'=>23,'table'=>'nada_accessory_exclude'),
	'AccessoryInclude' =>		array('name'=>'VacIncludes.DAT','length'=>23,'table'=>'nada_accessory_include'),
	'AccessoryBodyInclude' =>	array('name'=>'VacBodyIncludes.DAT','length'=>22,'table'=>'nada_accessory_body_include'),
	'AccessoryBodyNotAvailable'=>array('name'=>'VacBodyNotAvailables.DAT','length'=>22,'table'=>'nada_accessory_body_unavailable'),
	'Mileage' =>			array('name'=>'Mileage.DAT','length'=>57,'table'=>'nada_mileage'),
	'VIN' =>			array('name'=>'VinPrefix.DAT','length'=>40,'table'=>'nada_vehicle_vin'),
	'Region' =>			array('name'=>'Regions.DAT','length'=>61,'table'=>'nada_region'),
	'State' =>				array('name'=>'States.DAT','length'=>63,'table'=>'nada_state'),
	'ValueType' =>			array('name'=>'ValueTypes.DAT','length'=>48,'table'=>'nada_value_type'),
	'BookFlag' =>			array('name'=>'BookFlags.DAT','length'=>58,'table'=>'nada_book_flag'),
	'AccessoryVIN' =>			array('name'=>'VinVacs.DAT','length'=>29,'table'=>'nada_accessory_vin'),
	'VINAlternateVehicle' =>	array('name'=>'VinAlternateVics.DAT','length'=>29,'table'=>'nada_vehicle_alternate'),
	'GVWRating' =>			array('name'=>'GvwRatings.DAT','length'=>22,'table'=>'nada_gvw_rating'),
	'TonCode' =>			array('name'=>'TonRatings.DAT','length'=>15,'table'=>'nada_ton_code')
	);

	//database info for table dumps.
	$host   = ECash::getConfig()->DB_HOST;
	$user   = ECash::getConfig()->DB_USER;
	$pass   = ECash::getConfig()->DB_PASS;
	$name   = ECash::getConfig()->DB_NAME;
	$port   = ECash::getConfig()->DB_PORT;
	$socket = ECash::getConfig()->DB_SOCKET;
	$cache  = ECash::getConfig()->DB_CACHE;
	//options to use for table dump
	$mysql_options	= array(
	"--no-create-info ",
	"--insert-ignore ",
	"--skip-triggers ",
	'--lock-tables=false ',
	"-u {$user} ",
	"--password={$pass} ",
	"--host={$host} ",
	"--port={$port} ",
	"--databases {$name} ");

	//create directory if directory doesn't exist
	if (!is_dir($backup_dir))
	{
		mkdir($backup_dir);
	}


	//loop through each of our files and do dis!
	foreach ($tables as $function => $details)
	{
		$file = $temp_dir."/".$details['name'];
		//get flat file for update
		if(is_file($file))
		{

			//backup current table (dump)
			$backup_file = $backup_dir."/".$function."_".date("YmdHi")."backup.sql";
			exec('mysqldump '.implode(" ",$mysql_options).' --tables \''.$details['table'].'\' > '.$backup_file);
			//start transaction
			$db->beginTransaction();
			//remove current data in table
			//$nada_import->clearTable($details['table']);
			$query = "DELETE FROM {$details['table']}";
			$db->Query($query);
			
			
			//insert new data \validate data
			
			
			$function_name = "populate{$function}";
			//if data is inserted correctly commit transaction
			if($nada_import->$function_name($file))
			{
				$db->commit();
				//delete flat file
				unlink($file);
				echo "\n{$function} update was successful!";
			}
			else
			{
				echo "\n{$function} update failed";
				$db->rollBack();
			}
						
			//NEXT!

		}
		else
		{
			echo "\n{$details['name']} is not available";
		}

	}



}


?>
