<?php

require_once dirname(realpath(__FILE__)) . '/../www/config.php';

class restore_nada_data
{
	protected $tables = array(
	'VehicleDescription' =>		'nada_vehicle_description',
	'VehicleValues' => 			'nada_vehicle_value',
	'VehicleSegments' =>		'nada_vehicle_segment',
	'TruckDuties' =>			'nada_truck_duty',
	'VehicleAttributes' =>		'nada_vehicle_attribute',
	'AttributeType' =>			'nada_attribute_type',
	'AccessoryDescription' =>	'nada_accessory_description',
	'AccessoryValue' =>			'nada_accessory_value',
	'AccessoryCategory' =>		'nada_accessory_category',
	'AccessoryExclude' =>		'nada_accessory_exclude',
	'AccessoryInclude' =>		'nada_accessory_include',
	'AccessoryBodyInclude' =>	'nada_accessory_body_include',
	'AccessoryBodyNotAvailable'=>'nada_accessory_body_unavailable',
	'Mileage' =>				'nada_mileage',
	'VIN' =>					'nada_vehicle_vin',
	'Region' =>					'nada_region',
	'State' =>					'nada_state',
	'ValueType' =>				'nada_value_type',
	'BookFlag' =>				'nada_book_flag',
	'AccessoryVIN' =>			'nada_accessory_vin',
	'VINAlternateVehicle' =>	'nada_vehicle_alternate',
	'GVWRating' =>				'nada_gvw_rating',
	'TonCode' =>				'nada_ton_code'
	);
	
	public function main()
	{
		$options = getopt('d:r');

		if(!isset($options['d']))
		{
			$this->usage();
		}

		if(isset($options['r']))
		{
			$this->backup_tables();
		}

		$this->load_backup($options['d']);
	}

	protected function usage()
	{
		echo "Usage: {$_SERVER['argv'][0]} -d <YYYYYMMDD> [-r]", PHP_EOL,
			"	-d <YYYYMMDD> date file string to use ex: 20090731", PHP_EOL,
			"	-r backup current nada table contents before running", PHP_EOL;
		exit(1);
		
	}

	protected function backup_tables()
	{
		$mysql_options = array(
			"--no-create-info",
			"--insert-ignore",
			"--skip-triggers",
			'--lock-tables=false',
			"-u " . ECash::getConfig()->DB_USER,
			"--password=" . ECash::getConfig()->DB_PASS,
			"--host=" . ECash::getConfig()->DB_HOST,
			"--port=" . ECash::getConfig()->DB_PORT,
			"--databases " . ECash::getConfig()->DB_NAME);

		foreach ($this->tables as $filename => $table)
		{
			$backup_file = ECash::getConfig()->NADA_BACKUP_DIR.'/'.$filename.'_'.date('YmdHi').'backup.sql';
			exec('mysqldump '.implode(" ",$mysql_options).' --tables \''.$table.'\' > '.$backup_file);
		}
		
	}

	protected function load_backup($glob_date)
	{
		$db = ECash::getMasterDb();

		$mysql_options = array(
			"-u " . ECash::getConfig()->DB_USER,
			"--password=" . ECash::getConfig()->DB_PASS,
			"--host=" . ECash::getConfig()->DB_HOST,
			"--port=" . ECash::getConfig()->DB_PORT,
			"--database=" . ECash::getConfig()->DB_NAME);
		
		foreach(glob(ECash::getConfig()->NADA_BACKUP_DIR.'/*_'.$glob_date.'*backup.sql') as $backup_file)
		{
			$basefile = basename($backup_file);
			$firstpart = substr($basefile, 0, strpos($basefile, '_'));
			if(!isset($this->tables[$firstpart]))
			{
				echo "Could not find table/file map for '{$firstpart}', skipping\n";
			}
			
			//remove current data in table
			$query = "DELETE FROM {$this->tables[$firstpart]}";
			$db->Query($query);

			//echo 'mysql '.implode(" ",$mysql_options).' < '.$backup_file, PHP_EOL;
			exec('mysql '.implode(" ",$mysql_options).' < '.$backup_file);
		}
	}
}

$nada = new restore_nada_data();
$nada->main();

?>