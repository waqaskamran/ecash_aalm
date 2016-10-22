#!/usr/bin/php
<?php
/*
 * Quick and dirty utility to compare database schemas between environments.
 *
 * @author Brian Ronald <brian.ronald@sellingsource.com>
 * @todo Plenty!
 */

require_once("/virtualhosts/lib/mysqli.1.php");
define('COMPARE_EXEC', 'kdiff3');

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
	'local'=> array (	'DB_HOST'=>'localhost',
						'DB_PORT' =>'3306',
						'DB_NAME' =>'ldb_agean',
						'DB_USER' =>'root',
						'DB_PASS' =>'')
);

if($argc < 3) {
	echo "Usage: {$argv[0]} {environment1} {environment2}\n";
	echo "Possible environment choices:\n";
	foreach($db_list as $env => $params) {
		echo "  $env\n";
	}
	echo "\n";
	exit;
}

$db1 = strtolower($argv[1]);
$db2 = strtolower($argv[2]);

if((! isset($db_list[$db1])) || (! isset($db_list[$db2])))
{
	echo "Invalid database choice!  Use one of the following environments:\n";
	foreach($db_list as $env => $params) {
		echo "  $env\n";
	}
	echo "\n";
	exit;
}

if($db1 == $db2)
	die("Don't try to compare the schemas of the same database!\n\n");

$db_one = new MySQLi_1 (	$db_list[$db1]['DB_HOST'],
							$db_list[$db1]['DB_USER'],
							$db_list[$db1]['DB_PASS'],
							$db_list[$db1]['DB_NAME'],
							$db_list[$db1]['DB_PORT']);

$db_two = new MySQLi_1 (	$db_list[$db2]['DB_HOST'],
							$db_list[$db2]['DB_USER'],
							$db_list[$db2]['DB_PASS'],
							$db_list[$db2]['DB_NAME'],
							$db_list[$db2]['DB_PORT']);

/**/
echo "Grabbing table list from $db1\n";
$db_one_tables = get_table_list($db_one, $db_list[$db1]['DB_NAME']);
echo "Grabbing views from $db1\n";
$db_one_views = get_view_list($db_one, $db_list[$db1]['DB_NAME']);
echo "Grabbing triggers from $db1\n";
$db_one_triggers = get_trigger_list($db_one, $db_list[$db1]['DB_NAME']);

/**/
echo "Grabbing table list from $db2\n";
$db_two_tables = get_table_list($db_two, $db_list[$db2]['DB_NAME']);
echo "Grabbing views from $db2\n";
$db_two_views = get_view_list($db_two, $db_list[$db2]['DB_NAME']);
echo "Grabbing triggers from $db2\n";
$db_two_triggers = get_trigger_list($db_two, $db_list[$db2]['DB_NAME']);


echo "Combining tables\n";
$table_list = array_unique( array_merge($db_one_tables, $db_two_tables));
$view_list = array_unique( array_merge($db_one_views, $db_two_views));
$trigger_list = array_unique( array_merge($db_one_triggers, $db_two_triggers));

$fp1 = fopen("/tmp/db_".$db1."_schema.sql", "w");
$fp2 = fopen("/tmp/db_".$db2."_schema.sql", "w");

echo "Fetching Table Schemas\n";
foreach($table_list as $table)
{
	if(in_array($table, $db_one_tables)) {
		$data = get_table_schema($db_one, 'TABLE', $table)."\n\n";
		fputs($fp1, $data);
	} else {
		fputs($fp1, "\n\n");
	}

	if(in_array($table, $db_two_tables)) {
		$data = get_table_schema($db_two, 'TABLE', $table)."\n\n";
		fputs($fp2, $data);
	} else {
		fputs($fp2, "\n\n");
	}
}

echo "Fetching View Definitions\n";
foreach($view_list as $view)
{
	if(in_array($view, $db_one_views)) {
		$data = get_table_schema($db_one, 'VIEW', $view)."\n\n";
		fputs($fp1, $data);
	} else {
		fputs($fp1, "\n\n");
	}

	if(in_array($view, $db_two_views)) {
		$data = get_table_schema($db_two, 'VIEW', $view)."\n\n";
		fputs($fp2, $data);
	} else {
		fputs($fp2, "\n\n");
	}
}

echo "Fetching Trigger Definitions\n";
foreach($trigger_list as $trigger)
{
	if(in_array($trigger, $db_one_triggers)) {
		$data = get_trigger_definition($db_one, $trigger, $db_list[$db1]['DB_NAME'])."\n\n";
		fputs($fp1, $data);
	} else {
		fputs($fp1, "\n\n");
	}

	if(in_array($trigger, $db_two_triggers)) {
		$data = get_trigger_definition($db_two, $trigger, $db_list[$db2]['DB_NAME'])."\n\n";
		fputs($fp2, $data);
	} else {
		fputs($fp2, "\n\n");
	}
}

fclose($fp1);
fclose($fp2);

exec(COMPARE_EXEC . " /tmp/db_".$db1."_schema.sql /tmp/db_".$db2."_schema.sql >/dev/null & ");

function get_table_list ($mysqli, $database_name)
{
	$tables = array();
	$sql = "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$database_name}' AND TABLE_TYPE = 'BASE TABLE'";
	$result = $mysqli->Query($sql);
	while($row = $result->Fetch_Array_Row(MYSQL_NUM))
	{
		$tables[] = $row[0];
	}
	return $tables;
}

function get_view_list ($mysqli, $database_name)
{
	$views = array();
	$sql = "SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = '{$database_name}'";
	$result = $mysqli->Query($sql);
	while($row = $result->Fetch_Array_Row(MYSQL_NUM))
	{
		$views[] = $row[0];
	}
	return $views;
}

function get_trigger_list ($mysqli, $database_name)
{
	$triggers = array();
	$sql = "SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = '$database_name'";
	$result = $mysqli->Query($sql);
	while($row = $result->Fetch_Array_Row(MYSQL_NUM))
	{
		$triggers[] = $row[0];
	}
	return $triggers;
}

function get_function_list ($mysqli, $database_name)
{
	$functions = array();
	$sql = " SELECT ROUTINE_NAME FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = '$database_name'";
	$result = $mysqli->Query($sql);
	while($row = $result->Fetch_Array_Row(MYSQL_NUM))
	{
		$functions[] = $row[0];
	}
	return $functions;
}


function get_table_schema ($mysqli, $type, $name)
{
	$type = strtoupper($type);
	
	$sql = "SHOW CREATE $type $name";
	$result = $mysqli->Query($sql); 
	if($result->Row_Count() > 0) {
		$row = $result->Fetch_Array_Row(MYSQL_NUM);
		return $row[1];
	}
	else
	{
		return "";
	}
}

function get_trigger_definition ($mysqli, $trigger_name, $database_name)
{
	$sql = "
	SELECT 	TRIGGER_NAME, EVENT_MANIPULATION, 
			EVENT_OBJECT_TABLE, TRIGGER_SCHEMA, 
			ACTION_TIMING, ACTION_ORIENTATION,
			ACTION_STATEMENT 
	FROM information_schema.TRIGGERS 
	WHERE TRIGGER_NAME = '$trigger_name' 
	AND TRIGGER_SCHEMA = '$database_name'";
	$result = $mysqli->Query($sql);
	if($result->Row_Count() > 0) {
		$row = $result->Fetch_Array_Row();
		$create_sql = "
CREATE TRIGGER {$trigger_name} {$row['ACTION_TIMING']} {$row['EVENT_MANIPULATION']} ON {$row['EVENT_OBJECT_TABLE']}
FOR EACH {$row['ACTION_ORIENTATION']}
{$row['ACTION_STATEMENT']} ";
		
		return $create_sql;
	}
	else
	{
		return "";
	}
}


/*

SELECT 
  CONCAT( 
    'CREATE DEFINER = \'', 'ecashtrigger', '\'@\'', '%', '\'\n', 
    'TRIGGER ', TRIGGER_NAME, ' ', ACTION_TIMING, ' ', EVENT_MANIPULATION, '\n', 
    'ON ', EVENT_OBJECT_TABLE, '\n', 
    'FOR EACH ', ACTION_ORIENTATION, '\n', 
    ACTION_STATEMENT, '\n|' 
  ) 
FROM information_schema.TRIGGERS 
WHERE TRIGGER_SCHEMA = 'ldb'


*/
