<?php
/**
 * system_check.php
 * 
 * A program to do an overall check of system install properties. 
 * 
 * @author Josef Norgan <josef.norgan@sellingsource.com>
 * 
 **/

require_once('../www/config.php');

$loaded_extensions = get_loaded_extensions();

// Define properties to check for here
$tests = array(
	'ECash System Check' => array('********************',
		'Mode' => ECash::getConfig()->mode
		),
	'Database Information' => array(
		'Host' => ECash::getConfig()->DB_HOST,
		'Schema' => ECash::getConfig()->DB_NAME,
		'User' => ECash::getConfig()->DB_USER,
		'Port' => ECash::getConfig()->DB_PORT
		),
	'ACH Information' => array(
		'Batch Server' => ECash::getConfig()->ACH_BATCH_SERVER,
		'Server Port' => ECash::getConfig()->ACH_BATCH_SERVER_PORT
		),
	'PHP Install Check' => array('********************'),
	'Installed Modules' => array(
		'json' => in_array('json', $loaded_extensions),
		'ssh2' => in_array('ssh2', $loaded_extensions),
		'pcntl' => in_array('pcntl', $loaded_extensions),
		'PDO' => in_array('PDO', $loaded_extensions),
		'pdo_mysql' => in_array('pdo_mysql', $loaded_extensions),
		'pdo_sqlite' => in_array('pdo_sqlite', $loaded_extensions),
		'xdebug' => in_array('xdebug', $loaded_extensions)
		),
	'Directories' => array(
		'ecash/Data' => is_writable('../data'),
		'Statpro' => file_exists('/opt/statpro/var'),
		'EnterprisePro' => file_exists('/opt/enterprisepro/var')
		),
	'php.ini Settings' => array(
		'allow_url_fopen' => (bool)ini_get('allow_url_fopen'),
		'include paths' => explode(":",ini_get('include_path'))
		)
	);

	
// Display tests and values here
foreach($tests as $test_group_name => $test_group_children)
{
	echo "\n".color($test_group_name.":", '1')."\n";
	foreach($test_group_children as $test_name => $test_value)
	{
		if($test_name)
		{
			echo "{$test_name}: \t";
			if(is_bool($test_value))		//make nice the display of booleans
			{
				echo ($test_value) ? color('OK', '32') : color('FAILED', '1;5;31');
			}
			else if(is_array($test_value))
			{
				foreach($test_value as $value)
				{
					echo "\t" . color($value, '1;33') . "\n";
				}
			}else{
				echo color($test_value, '1;33');			
			}
		}else{
			echo $test_value;
		}
		echo "\n";
	}
}

function color($text, $num)
{
	return "\x1B[{$num}m{$text}\x1B[0m";
}
echo "\n";

?>
