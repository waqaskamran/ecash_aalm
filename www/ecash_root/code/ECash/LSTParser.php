<?php

class ECash_LSTParser extends ECash_CSVParser
{
	/**
	 * Basically a copy of ECash_CSVParser::parse() with some LST weirdness thrown in
	 *
	 * @param string $csv_data
	 * @param array $key_list
	 * @param int $offset (Used to skip the first n records)
	 * @return mixed boolean on failure, array on success
	 */
	static public function parse($csv_data, array $key_list, $offset = 0)
	{
		if(empty($csv_data))
		{
			return false;
		}

		if(empty($key_list))
		{
			return false;
		}

		//[#50910] if the encoding is weird, fix it before it goes into mysql
		if(mb_check_encoding ( $csv_data, 'byte2le' ))
		{
			$csv_data = mb_convert_encoding($csv_data, 'UTF-8', 'byte2le');
		}			

		// Determine the record separator
		$rs = self::detectLineEndings($csv_data);

		if($rs == '')
		{
			throw new Exception ("Unable to parse a CSV file with no record separators!");
		}

		// Split file into rows
		$return_data_ary = explode($rs, $csv_data);
		$parsed_data_ary = array();
		$i = 0;

		foreach ($return_data_ary as $line)
		{
			if($offset > 0)
			{
					$offset--;
					continue;
			}

			//[#50910] Remove quotes at the beginning and end of line			
			if(strpos($line, '"') === 0 && strrpos($line, '"') == strlen($line) - 1)
			{
				//strip the first and last quotes
				$line = substr($line, 1, strlen($line) - 2);
			}
			//[#50910] Reduce double double-quotes to just one double-quote
			$line = str_replace('""', '"', $line);

			if ( strlen(trim($line)) > 0 )
			{
				$matches = array();
				preg_match_all('#(?<=^"|,")(?:[^"]|"")*(?=",|"$)|(?<=^|,)[^",]*(?=,|$)#', $line, $matches);
				$col_data_ary = $matches[0];

				$parsed_data_ary[$i] = array();

				/**
				 * Validation check to ensure the # of columns in the csv_data matches
				 * the # of columns in the supplied key_list.  If there are more data columns,
				 * we throw an Exception.
				 */
				if(count($col_data_ary) > count($key_list))
				{
					throw new Exception("Column Data: " . count($col_data_ary) . " > Key List: " . count($key_list));
				}

				foreach ($col_data_ary as $key => $col_data)
				{
					//$col_data = trim($col_data);
					// Apply column name map so we can return a friendly structure
					$parsed_data_ary[$i][$key_list[$key]] = str_replace('"', '', $col_data);
				}

				$i++;
			}
		}

		return $parsed_data_ary;
	}
}

?>