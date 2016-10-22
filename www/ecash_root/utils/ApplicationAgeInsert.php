<?php

/**
 * Converts the date of birth (dob) field to the actual age at the time the
 *	application was created.
 *
 * These scripts will take a long time to complete and should be run in chunks
 *	for each company.
 *
 * @copyright Copyright 2009 The Selling Source, Inc.
 * @package Utils
 * @author Bill Szerdy <bill.szerdy@sellingsource.com>
 * @created May 8, 2009
 */

/**
 * MIN(date_created) for databases as of 05.08.2009
 *
 * ca	- 2001-09-15 00:09:00
 * ucl	- 2003-01-28 02:41:01
 * d1	- 2001-09-15 00:09:00
 * pcl	- 2001-09-15 00:09:00
 * ufc	- 2001-09-15 00:09:00
 */


class ApplicationAgeInsert
{
	private $companies;

	public function __construct()
	{
	}

	/**
	 * Updates the application table with the age
	 *
	 * @param string $company_short
	 * @param string $start_date
	 * @param string $end_date
	 * @return integer
	 */
	public function execute($start_date = "1990-01-01", $end_date = Date("Y-m-d"))
	{
		$company_id = ECash::getCompany()->company_id;
		$records = array();
		$db = ECash_Config::getMasterDb();

		$get_date_query = "
			SELECT
				application_id,
				dob,
				date_created
			FROM
				application
			WHERE
				date_created BETWEEN ? AND ? AND
				company_id = ?
			ORDER BY
				date_created ASC;
		";

		$args = array(
			$start_date." 00:00:00",
			$end_date. " 23:59:59",
			$company_id,
		);

		/* retrieve the records between the dates */
		$st = $db->prepare($get_date_query);
		$st->execute($args);

		/* calculate the age and add the value to the record */
		while ($row = $st->fetch(DB_IStatement_1::FETCH_OBJ)) {
			$records[$row->application_id] = $row;
			$records[$row->application_id]->age = $this->calculateAgeFromDateCreated($row->dob, $row->date_created);
		}

		/* update the database with the age */
		$update_sql = "
			UPDATE
				application
			SET
				age = ?
			WHERE
				application_id = ? AND
				company_id = ?;
		";

		$ust = $db->prepare($update_sql);
		$affected_rows = 0;

		foreach ($records as $record) {
			$u_args = array(
				$record->age,
				$record->application_id,
				$company['company_id'],
			);
			$ust->execute($u_args);
			if ($ust->rowCount())
			{
				$affected_rows++;
			}
		}
		return $affected_rows;
	}

	/**
	 * Calculates the age of the client base on the application date_created
	 *
	 * @param string $dob
	 * @param string $date_created
	 * @return integer
	 */
	private function calculateAgeFromDateCreated($dob, $date_created)
	{
		list($year, $month, $day) = explode("-", trim($dob));
		$dob = getdate(mktime(0, 0, 0, (int)$month, (int)$day, (int)$year));

		list($year, $month, $day) = explode("-", trim($date_created));
		$date_created = getdate(mktime(0, 0, 0, (int)$month, (int)$day, (int)$year));

		$pre_age = $date_created["year"] - $dob["year"];

		/**
		 * compare the months, if the current month is less than the dob month,
		 *	then the birthday hasn't passed
		 */
		if (strcmp($date_created["month"], $dob["month"]) < 0)
		{
			return $pre_age-1;
		}

		/**
		 * if the current month and the dob month are equal, test the day of
		 *	the month
		 */
		if ((strcmp($date_created["month"], $dob["month"]) == 0)
			&& (strcmp($date_created["mday"], $dob["mday"]) < 0))
		{
			return $pre_age-1;
		}

		return $pre_age;
	}
}


$a = new ApplicationAgeInsert();
$count = $a->execute();

/*
foreach ($args as $company => $dates)
{
	foreach ($dates as $start_date => $end_date)
	{
		$count = $a->execute($company, $start_date, $end_date);
		print "{$start_date} => {$end_date} {$count} records updated for {$company}.\n";
	}
}
*/

?>
