#!/usr/bin/php
<?php

$db_list = array(	
	'local' => array (	'DB_HOST'=>'localhost',
						'DB_PORT' =>'3306',
						'DB_NAME' =>'ldb',
						'DB_USER' =>'ecash',
						'DB_PASS' =>'3c4shl1n3'),
'agean_dev' => array (	'DB_HOST'=>'monster.tss',
						'DB_PORT' =>'3309',
						'DB_NAME' =>'ldb_agean',
						'DB_USER' =>'ecash',
						'DB_PASS' =>'lacosanostra'),
'agean_rc'	=> array (	'DB_HOST'=> 'db101.ept.tss',
						'DB_PORT' =>'3308',
						'DB_NAME' =>'ldb_agean',
						'DB_USER' =>'ecash',
						'DB_PASS' =>'lacosanostra'),
'cfc_dev' => array (	'DB_HOST'=>'monster.tss',
						'DB_PORT' =>'3309',
						'DB_NAME' =>'ldb_cfc',
						'DB_USER' =>'ecash',
						'DB_PASS' =>'lacosanostra'),
'cfc_rc'	=> array (	'DB_HOST'=> 'db101.ept.tss',
						'DB_PORT' =>'3308',
						'DB_NAME' =>'ldb_cfc',
						'DB_USER' =>'ecash',
						'DB_PASS' =>'lacosanostra'),
'cfe_dev' => array (	'DB_HOST'=>'monster.tss',
						'DB_PORT' =>'3309',
						'DB_NAME' =>'ldb_cfe',
						'DB_USER' =>'ecash',
						'DB_PASS' =>'lacosanostra'),
'cfe_rc'	=> array (	'DB_HOST'=> 'db101.ept.tss',
						'DB_PORT' =>'3308',
						'DB_NAME' =>'ldb_cfe',
						'DB_USER' =>'ecash',
						'DB_PASS' =>'lacosanostra'),
'ccrt_dev' => array (	'DB_HOST'=>'monster.tss',
						'DB_PORT' =>'3309',
						'DB_NAME' =>'ldb_ccrt',
						'DB_USER' =>'ecash',
						'DB_PASS' =>'lacosanostra'),
'ccrt_rc'	=> array (	'DB_HOST'=> 'db101.ept.tss',
						'DB_PORT' =>'3308',
						'DB_NAME' =>'ldb_ccrt',
						'DB_USER' =>'ecash',
						'DB_PASS' =>'lacosanostra'),
'ccrt_local'=> array (	'DB_HOST'=>'localhost',
						'DB_PORT' =>'3306',
						'DB_NAME' =>'ldb_ccrt',
						'DB_USER' =>'root',
						'DB_PASS' =>''),
	'demo'	=> array (	'DB_HOST'=> 'db101.ept.tss',
						'DB_PORT' =>'3308',
						'DB_NAME' =>'ldb_demo',
						'DB_USER' =>'ecash',
						'DB_PASS' =>'lacosanostra'),
'clk_live'	=> array (	'DB_HOST'=>'writer.ecash3clk.ept.tss',
						'DB_PORT' =>'3306',
						'DB_NAME' =>'ldb',
						'DB_USER' =>'ecash',
						'DB_PASS' =>'ugd2vRjv'),
'impact_live'=> array (	'DB_HOST'=>'writer.ecashimpact.ept.tss',
						'DB_PORT' =>'3307',
						'DB_NAME' =>'ldb_impact',
						'DB_USER' =>'ecash',
						'DB_PASS' =>'showmethemoney'),
'impact_rc' => array (	'DB_HOST'=>'db101.clkonline.com',
						'DB_PORT' =>'3313',
						'DB_NAME' =>'ldb_impact',
						'DB_USER' =>'ecash',
						'DB_PASS' =>'1234five'),
'agean_live'=> array (	'DB_HOST'=>'reader.ecashagean.ept.tss',
						'DB_PORT' =>'3306',
						'DB_NAME' =>'ldb_agean',
						'DB_USER' =>'ecash',
						'DB_PASS' =>'Zeir5ahf'),
'mls_live'=> array (	'DB_HOST'=>'reader.ecashaalm.ept.tss',
						'DB_PORT' =>'3306',
						'DB_NAME' =>'ldb_mls_cc',
						'DB_USER' =>'ecash',
						'DB_PASS' =>'Hook6Zoh'),
);

if($argc < 3) {
	echo "Usage: {$argv[0]} {database} {table_name} ...\n";
	echo "Possible environment choices:\n";
	foreach($db_list as $env => $params) {
		echo "  $env\n";
	}
	echo "\n";
	exit;
}

$db = strtolower($argv[1]);
if(!isset($db_list[$db]))
{
	echo "Invalid DB, $db given.\n";
	echo "Possible environment choices:\n";
	foreach($db_list as $env => $params) {
		echo "  $env\n";
	}
	echo "\n";
	exit;
}

$db = $db_list[$db];

mysql_connect($db['DB_HOST'] . ':' . $db['DB_PORT'], $db['DB_USER'], $db['DB_PASS']) or die("Database connections failed.\n");
mysql_select_db($db['DB_NAME']) or die("Error selecting database.\n");

$tables = array_slice($argv, 2);

foreach($tables as $table_name)
{
	
	$query = "DESC `" . $table_name . "`";
	if($rs = mysql_query($query)) 
	{
		$name_arr = explode('_', $table_name);
		$camel_name = '';
		foreach($name_arr as $piece)
		{
			$camel_name .= ucfirst($piece);
		}
		$primary_key = array();
		$auto_inc = '';
		$columns = array();
		while($row = mysql_fetch_assoc($rs))
		{
			$columns[] = $row['Field'];
			if($row['Key'] == 'PRI')
			{
				$primary_key[] = $row['Field'];
			}
			if($row['Extra'] == 'auto_increment')
			{
				$auto_inc = $row['Field'];
			}
		}
		if(sizeof($primary_key) > 0)
		{
			$primary_key = "'" . implode("','",$primary_key) . "'";
		}
		else
		{
			$primary_key = '';
		}
		$column_list = "'" . implode("','", $columns) . "'";
		$model = file_get_contents('default_model_normal.tpl');
		if(!file_exists($camel_name . ".php"))
		{
			file_put_contents($camel_name . ".php", str_replace('%%%auto_inc%%%', $auto_inc, str_replace('%%%table_name%%%', $table_name, str_replace('%%%column_list%%%',$column_list, str_replace('%%%camel_name%%%',$camel_name, str_replace('%%%primary_key%%%', $primary_key, $model))))));
			echo "Successfully generated $camel_name.php.\n\n";
		}
		else
		{
			echo $camel_name . ".php already exists. Skipping table.\n\n";
		}
	}
	else
	{
		echo "\nTable " . $table_name . " doesn't exist; No model generated.\n\n";
	}
}

?>
