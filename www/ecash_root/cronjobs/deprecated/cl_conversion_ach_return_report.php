#!/usr/bin/php
<?php

// CSV Escape a value
function csv_escape($value)
{
	$value = str_replace('"','""',$value);
	$value = '"'.$value.'"';

	return($value);
}

// CSV Escape an entire array's values
function csv_escape_row($row)
{
	foreach($row as $key => $value)
	{
		$row[$key] = csv_escape($value);
	}

	return($row);
}

// Setup
{
	// Get user options
	$self = array_shift($argv);
	$low_date = array_shift($argv);
	$high_date = array_shift($argv);
	$save_file = array_shift($argv);

	// Initialize check stage
	$show_usage = FALSE;

	// If they're not already timestamps
	if(!preg_match('/^[0-9]+$/',$low_date) || !preg_match('/^[0-9]+$/',$high_date))
	{
		$low_date = strtotime($low_date);
		$high_date = strtotime($high_date);

		if(FALSE === $low_date || FALSE === $high_date)
		{
			print("Invalid date passed in\n");
			$show_usage = 1;
		}

		// If both are the beginning of the day, assume they meant to search to the end of the day
		if("00:00:00" == date('H:i:s',$low_date) && "00:00:00" == date('H:i:s',$high_date))
		{
			$high_date = strtotime(date('Y-m-d 23:59:59',$high_date));
		}
	}

	// If they didn't provide a save file
	if(!is_string($save_file) || !realpath(dirname($save_file)) || !is_dir(realpath(dirname($save_file))) || file_exists($save_file))
	{
		print("Invalid save path, or file already exists\n");
		$show_usage = 1;
	}

	// Show usage and die
	if($show_usage)
	{
		print("Usage: $self <low_date> <high_date> <save_file.csv>\n");
		die(1);
	}
}

// Connect to database
$handle = mysql_connect('db1.clkonline.com:3307','ecash','3cash',TRUE);
mysql_select_db('ldb',$handle);

// Build and run query
$query = "
	SELECT
		`cl_conversion_ach_return`.`application_id` AS `Return: Application Identifier`,
		`cl_conversion_ach_return`.`company_id`     AS `Return: Company Identifier`,
		`cl_conversion_ach_return`.`return_date`    AS `Return: Date`,
		`cl_conversion_ach_return`.`return_code`    AS `Return: Code`,
		`cl_conversion_ach_return`.`return_amount`  AS `Return: Amount`,
		`application`.`archive_cashline_id`         AS `Application: Archived Cashline Id`,
		`application`.`name_last`                   AS `Application: Name Last`,
		`application`.`name_first`                  AS `Application: Name First`,
		`application`.`name_middle`                 AS `Application: Name Middle`,
		`application`.`name_suffix`                 AS `Application: Name Suffix`
	FROM
		`cl_conversion_ach_return`
	LEFT JOIN `application` ON ( 1 = 1
		AND `cl_conversion_ach_return`.`application_id` = `application`.`application_id`
		AND `cl_conversion_ach_return`.`company_id`     = `application`.`company_id`
		)
	WHERE 1 = 1
		AND `cl_conversion_ach_return`.`return_date` BETWEEN FROM_UNIXTIME($low_date) AND FROM_UNIXTIME($high_date)
	";
$result_handle = mysql_query($query, $handle);

// If not a resource, error running query
if(!is_resource($result_handle))
{
	print("Failed running query:\nMySQL Errno: ".mysql_errno()."\nMySQL Error: ".mysql_error()."\nQuery:\n$query\n");
	die(1);
}

// Open file to dump contents to
$file_handle = fopen($save_file,"w");
if(!is_resource($file_handle))
{
	print("Failed opening $save_file for writing\n");
	die(1);
}

// Get header row
$headers = array();
$header_count = mysql_num_fields($result_handle);
for($x = 0; $x < $header_count; $x++)
{
	$headers[] = csv_escape(mysql_field_name($result_handle, $x));
}
fwrite($file_handle, join(",",$headers)."\n");

// Get data rows
while(is_array($row = mysql_fetch_row($result_handle)))
{
	$row = csv_escape_row($row);
	fwrite($file_handle, join(",",$row)."\n");
}

// Cleanup
mysql_close($handle);
fclose($file_handle);
print("Done.\n");

?>
