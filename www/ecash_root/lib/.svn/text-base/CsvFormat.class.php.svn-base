<?php
/**
 * Easy formatting of CSV rows.
 *
 * @package Library
 *
 * @author Russell Lee <russell.lee@sellingsource.com>
 * @copyright Copyright &copy; 2007 The Selling Source, Inc.
 * @created 2007-04-26
 *
 * @version $Revision$
 */

class CsvFormat
{
	/**
	 * Escape a value by doubling the quotes then quoting the entire thing.
	 *
	 * @param string $value Raw string
	 * @return string Formatted string
	 */
	public static function escapeValue($value)
	{
		$value = str_replace('"', '""', $value);
		$value = '"' . $value . '"';

		return $value;
	}

	/**
	 * Format values from an array into a CSV row with all delimiters and quotations.
	 *
	 * @param array $array Values to return
	 * @return string Formatted row from the array values
	 */
	public static function getFromArray($array)
	{
		$output = '';
		$first = true;

		foreach ($array as $key)
		{
			if (!$first)
			{
				$output .= ',';
			}
			else
			{
				$first = false;
			}

			$output .= self::escapeValue($key);
		}

		return $output . "\n";
	}
}

?>
