
<?php

/**
 * Date manipulation
 *
 * @copyright Copyright 2009 The Selling Source, Inc.
 * @package ECash
 * @author Bill Szerdy <bill.szerdy@sellingsource.com>
 * @created May 7, 2009
 */
class ECash_Dates
{
	/**
	 * Takes a date and returns the age in years
	 *
	 * @todo after php 5.3.x is released this can be depricated in favor of the
	 *	DateTime::diff function.
	 *
	 * @param string $date - use format Y-m-d
	 * @return integer
	 */
	public static function calculateAge($date)
	{
		list($year, $month, $day) = explode("-", $date);

		$dob = getdate(mktime(0, 0, 0, $month, $day, $year));
		$now = getdate();

		$pre_age = $now["year"] - $dob["year"];

		/**
		 * compare the months, if the current month is less than the dob month,
		 *	then the birthday hasn't passed
		 */
		if (strcmp($now["month"], $dob["month"]) < 0)
		{
			return $pre_age-1;
		}

		/**
		 * if the current month and the dob month are equal, test the day of
		 *	the month
		 */
		if ((strcmp($now["month"], $dob["month"]) == 0)
			&& (strcmp($now["mday"], $dob["mday"]) < 0))
		{
			return $pre_age-1;
		}

		return $pre_age;
	}
}

?>
