#!/usr/bin/php
<?php

require_once 'libolution/DB/MySQLConfig.1.php';

class Bill_Day_Import
{
	const ARG_HOST = 'h';
	const ARG_PORT = 'P';
	const ARG_DB_NAME = 'd';
	const ARG_USER = 'u';
	const ARG_FILE = 'f';
	const ARG_PASS = 'p';
	const ARG_YEAR = 'y';
	const OPTION_TEST = 'x';

	const DEFAULT_HOST = 'localhost';
	const DEFAULT_PORT = 3306;
	const DEFAULT_DB_NAME = 'ldb';
	const DEFAULT_USER = 'root';
	const DEFAULT_PASS = '';

	const DEFAULT_DELIMITER = ',';
	const DEFAULT_ENCLOSURE = '"';

	const BILL_DAY_TABLE = 'billing_date';
	
	private $argv;
	private $options;
	private $matrix;
	private $inserts = array();

	private function __construct($argv)
	{
		$this->argv = $argv;
	}

	private function setOptions($options)
	{
		$this->options = $options;
		//echo print_r($this->options, TRUE), PHP_EOL;
	}
	
	public static function main($argv)
	{
		$import = new self($argv);
		$flags = array(self::ARG_FILE,
					   self::ARG_YEAR,
					   self::ARG_HOST,
					   self::ARG_PORT,
					   self::ARG_USER,
					   self::ARG_DB_NAME,
					   self::ARG_PASS
					   );
					   
		$import->setOptions(getopt(join(':', $flags) . '::' . self::OPTION_TEST));
		$import->run();
	}

	private function run()
	{
		if(empty($this->options[self::ARG_FILE]) || empty($this->options[self::ARG_YEAR]))
			$this->usage();
		
		//if(!isset($this->options[self::OPTION_TEST]))
		//	$this->connectDB();

		$this->readFile();
		$this->processMatrix();
	}

	/*
	private function connectDB()
	{
		$host = empty($this->options[self::ARG_HOST]) ? self::DEFAULT_HOST : $this->options[self::ARG_HOST];
		$port = empty($this->options[self::ARG_PORT]) ? self::DEFAULT_PORT : $this->options[self::ARG_PORT];
		$user = empty($this->options[self::ARG_USER]) ? self::DEFAULT_USER : $this->options[self::ARG_USER];
		$pass = empty($this->options[self::ARG_PASS]) ? self::DEFAULT_PASS : $this->options[self::ARG_PASS];
		$pass = empty($this->options[self::ARG_DB_NAME]) ? self::DEFAULT_DB_NAME : $this->options[self::ARG_DB_NAME];

		$config = new DB_MySQLConfig_1($host, $user, $pass, $db_name, $port);
		try
		{
  			$db = $config->getConnection();
		}
		catch(Exception $e)
		{
			echo $e->getMessage(), PHP_EOL;
			$this->usage();
		}
	}
	*/
	
	private function readFile()
	{
		$file = $this->options[self::ARG_FILE];
		if(!is_readable($file))
		{
			echo "File {$file} does not exist or is not readable", PHP_EOL;
			$this->usage();
		}

		$fp = fopen($file, 'r');

		$i = 0;
		while($line = fgetcsv($fp, NULL, self::DEFAULT_DELIMITER, self::DEFAULT_ENCLOSURE))
		{
			$this->addLine($line);
		}
		fclose($fp);
	}

	private function addLine(array $line)
	{
		$this->matrix[] = $line;
	}

	private function processMatrix()
	{
		$month_array = $this->matrix[0];
		$month_count = count($month_array);
		$depth = count($this->matrix);
		$year = $this->options[self::ARG_YEAR];

		for($j = 1; $j < $month_count; $j++)
		{
			for($i = 1; $i < $depth; $i++)
			{
				$dates = split(',', $this->matrix[$i][$j]);
				foreach($dates as $date)
				{
					$date = trim($date);
					if(!empty($date))
					{
						//echo $month_array[$j], ' ', $date, ' maps to: ', $this->matrix[$i][0], PHP_EOL;
						$timestamp = strtotime("{$date} {$month_array[$j]} {$year}");
						$insert = "insert into " . self::BILL_DAY_TABLE .
							" (approved_date, billing_date)" . PHP_EOL .
							"values ('" . date('Y-m-d', $timestamp) . "', {$this->matrix[$i][0]});";
						//if(isset($this->options[self::OPTION_TEST]))
						//{
							echo $insert, PHP_EOL;
						//}
						//else
						//{
							//$this->inserts = $insert;
						//}
					}
				}
			}
		}
	}
	
	private function usage()
	{
		echo 'Usage:', PHP_EOL;
		echo "{$this->argv[0]} -", self::ARG_FILE, ' <filename> -', self::ARG_YEAR, ' <year> ', PHP_EOL; // \\', PHP_EOL;
		//echo "\t -", self::ARG_HOST, ' [', self::DEFAULT_HOST, '] -', self::ARG_DB_NAME, ' [ldb] -', self::ARG_PORT, ' [',self::DEFAULT_PORT,'] -', self::ARG_USER , ' [', self::DEFAULT_USER ,'] -', self::ARG_PASS, 'password', PHP_EOL;
		exit(1);
	}
}

Bill_Day_Import::main($_SERVER['argv']);

?>