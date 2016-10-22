<?php

// These functions for the most part accept the same types of arguments
// $data is always a 2d array of key values
// Example:
//   array(
//     array('foo'  => 'bar'),
//     array('test' => 'fail'),
//   )

// $columns is always a predefined set of columns that will display on the exported/saved
// file.

class ECash_KeyValueFormatter
{
	// This exports in a fixed form, fixed specifiers is
	//
	// array(
	//   <column name> => array(<min>, <max>, TBD .... ),
	//   ...
	// );
	static function exportFixed($data, $columns, $fixed_specifiers)
	{
	}

	static function exportCSV($data, $columns, $headers = TRUE)
	{
		if (!is_array($data) || empty($data))
			throw new Exception('Key_Value_Formatter::exportCSV() called with non-array data.');

		$csv = "";

		// If headers is true, print a headers row at the very top
		if ($headers === TRUE)
		{
			foreach ($columns as $column)
			{
				// strip out commas inside keys
				// Only display keys which are in $columns
					$csv .= '"'.str_replace(",", "", $column).'"' . ",";
			}

			$csv = substr($csv, 0, -1) . "\n";
		}

		// For each node in the inputted data
		foreach ($data as $row)
		{
			// For each column in the row
			foreach ($columns as $column)
			{
				// strip out commas inside data
				// Only display values which are in $columns
					$data_elem = '"'.str_replace(",", "", $row[$column]).'"';
					$csv .= $data_elem . ",";
			}

			// Remove the trailing tab, add a newline
			$csv = substr($csv, 0, -1) . "\n";
		}

		return $csv;

	}

	static function exportTSV($data, $columns, $headers = TRUE)
	{
		if (!is_array($data) || empty($data))
			throw new Exception('Key_Value_Formatter::exportTSV() called with non-array data.');

		$tsv = "";

		// If headers is true, print a headers row at the very top
		if ($headers === TRUE)
		{
			foreach ($columns as $column)
			{
				// Replace tabs in data with spaces
				// Only display columns listed in $columns
				$tsv .= str_replace("\t", " ", $column) . "\t";
			}

			$tsv = substr($tsv, 0, -1) . "\n";
		}

		// For each node in the inputted data
		foreach ($data as $row)
		{
			// For each column in the row
			foreach ($columns as $column)
			{
				// Replace tabs in data with spaces
				// Only display if the column is listed in $columns
				$tsv .= str_replace("\t", " ", $row[$column]) . "\t";
			}

			// Remove the trailing tab, add a newline
			$tsv = substr($tsv, 0, -1) . "\n";
		}

		return $tsv;
	}

	static function exportXML($data, $columns, $headers = FALSE)
	{
		if (!is_array($data) || empty($data))
			throw new Exception('Key_Value_Formatter::exportXML() called with non-array data.');

		// We're going to create the XML file as a DOM tree
		$dom  = new DOMDocument('1.0', 'iso-8859-1');
		$dom->formatOutput = true;

		$document = $dom->createElement('document');
		
		// Heh
		$node = $dom->createElement('editable', 'false');
		$document->appendChild($node);

		// PCI Compliance
		$node = $dom->createElement('secure', 'true');
		$document->appendChild($node);
	
		$records = $dom->createElement('records');

		// For each node in the inputted data
		foreach ($data as $row)
		{
			$record = $dom->createElement('record');

			// For each column in the row
			foreach ($columns as $column)
			{
				if (!in_array($column, $row))
					continue;

				$field = $dom->createElement($column, $row[$column]);
				$record->appendChild($field);
			}

			$records->appendChild($record);
		}

		$document->appendChild($records);
		$dom->appendChild($document);

		return $dom->saveXML();
	}

}

?>
