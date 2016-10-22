<?php
/**
 * Script to populate the application_column table with Do Not Loan flags
 * as part of a transition to the Do Not Loan Table.
 * 
 * In other words, we pushed up code that now uses the do_not_loan_flag table
 * and needed to replicate those actions over to the existing application_column
 * table.
 * 
 */

try 
{
	require_once(dirname(__FILE__)."/../www/config.php");
	require_once("mysqli.1.php");
	require_once(LIB_DIR. "mysqli.1e.php");
	require_once(LIB_DIR. "common_functions.php");
	
	$mysqli = MySQLi_1e::Get_Instance();

	$flags = array();
	
	$sql = "
	SELECT dnl.ssn, a.application_id, a.company_id
	FROM do_not_loan_flag AS dnl, application AS a
	WHERE dnl.date_created > '2007-02-02 00:00:00'
	AND a.ssn = dnl.ssn
	AND a.company_id = dnl.company_id
	AND NOT EXISTS (
   	                SELECT 'x'
       	            FROM application_column AS ac
                    WHERE ac.do_not_loan = 'on'
                    AND ac.application_id = a.application_id
	               )
	GROUP BY application_id ";
	
	$result = $mysqli->Query($sql);
	while($row = $result->Fetch_Object_Row())
	{
		$flags[] = $row;
	}
	
	if(count($flags) > 0)
	{
		$sql = "
				INSERT INTO application_column 
					(date_modified, date_created, company_id, application_id, table_name, column_name, do_not_loan)
				VALUES ";

		foreach ($flags as $f)
		{
			$sql .= "
					(CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, {$f->company_id}, {$f->application_id}, 'application', 'ssn', 'on'), ";
		}

		$sql = rtrim($sql, ', ');
	
		$mysqli->Query($sql);
	}
	/*
	$query = "
		DELETE FROM
		  application_column
		WHERE
		  do_not_loan = 'on' AND
		  NOT EXISTS
		    (
		      SELECT 1
		      FROM
		        do_not_loan_flag dnlf
		        JOIN application a USING(company_id, ssn)
		      WHERE a.application_id = application_column.application_id
		    )
	";
	*/

	$query = 
	"
		DELETE FROM 
		  application_column 
		WHERE 
		  do_not_loan = 'on' AND 
		  NOT EXISTS 
		    (
			SELECT 1 
			FROM 
			do_not_loan_flag dnlf 
			JOIN application a USING(ssn) 
			WHERE a.application_id = application_column.application_id 
				AND dnlf.active_status = 'active'
		    )
	";
	
	$mysqli->Query($query);
}
catch (Exception $e)
{
	die($e->getMessage() . "\n" . $e->getTraceAsString());
}
