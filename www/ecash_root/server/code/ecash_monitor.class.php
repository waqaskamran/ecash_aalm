<?php

class Ecash_Monitor
{
	private $log_location_list;
	private $log_array;
	private $log_data_array;
	private $results;
	private $track_items;
	private $ticks;

	private $start_timestamp;
	private $end_timestamp;
	private $increment_amount;
	private $time_format;

	public function __construct($log_location_list)
	{
		$this->log_array = array();
		$this->log_data_array = array();
		$this->log_location_list = $log_location_list;
		$this->results = array();
		$this->time_group_stamps = array();

		$this->Load_Log_List();
	}

	// An array of items we want results for
	public function Set_Track_Items($track_items)
	{
		if( is_array($track_items) )
		{
			$this->track_items = $track_items;
		}
	}

	// Parse desired data and store in the results array
	public function Parse_Stats($start_timestamp, $end_timestamp, $increment_amount, $time_format = FALSE)
	{
		$this->start_timestamp = $start_timestamp;
		$this->end_timestamp = $end_timestamp;
		$this->increment_amount = $increment_amount;
		$this->time_format = $time_format;

		$this->Merge_Required_Logs($start_timestamp, $end_timestamp);

		$log_data_count = count($this->log_data_array);

		for($i = 0; $i < $log_data_count; $i++)
		{
			if( preg_match("/time for \[(.*?)\]/", $this->log_data_array[$i], $item_found_matches) )
			{
				$item_found = strtolower($item_found_matches[1]);

				$item_tracked = "";

				foreach( $this->track_items as $item => $name)
				{
					if( strpos($item_found, $item) !== FALSE)
					{
						$item_tracked = $name;
						break;
					}
				}

				if( strlen($item_tracked) )
				{
					if( preg_match("/is (.*?) seconds/", $this->log_data_array[$i], $time_matches) )
					{
						if( preg_match("/\] \[(.*?)\] Elapsed/", $this->log_data_array[$i], $company_matches) )
						{
							$company = $company_matches[1];
						}

						$stampy = $this->Extract_Timestamp($this->log_data_array[$i]);

						if( $stampy >= $this->start_timestamp && $stampy < $this->end_timestamp)
						{
							if($time_format !== FALSE)
							{
								$time_group = $this->Get_Time_Group($stampy);

								// Preset the X ticks if this is a new item/company
								if( !isset($this->results[$item_tracked][$company]) )
								{
									$this->Get_Ticks();

									foreach($this->ticks as $tick)
									{
										$this->results[$item_tracked][$company][$tick] = array();
									}
								}

								if( $time_group !== FALSE)
								{
									$this->results[$item_tracked][$company][$time_group][] = $time_matches[1];
								}
							}
							else
							{
								$this->results[$item_tracked][$company][] = $time_matches[1];
							}
						}
					}
				}
			}
		}

		unset($this->log_data_array);

		foreach($this->results as $item_tracked => $companies)
		{
			foreach($companies as $company => $time_group)
			{
				while( $key = key($time_group) )
				{
					if( !count($this->results[$item_tracked][$company][$key]) )
					{
						unset($this->results[$item_tracked][$company][$key]);
					}

					next($time_group);
				}
			}
		}

		return $this->results;
	}

	// For the date range supplied check which log files will need to be parsed thru to get a valid answer.
	private function Merge_Required_Logs($start_timestamp, $end_timestamp)
	{
		foreach($this->log_array as $log_location => $log_location_array)
		{
			foreach($log_location_array as $log_name)
			{
				// If its compressed run it thru gzuncompress, then explode to get the array.
				if( strpos($log_name, ".gz") )
				{
					$file_array = gzfile($log_location . $log_name);
				}
				else // No compression for the current log, get the array in one call
				{
					$file_array = file($log_location . $log_name);
				}

				$first_timestamp = $this->Applog_Date($file_array, "forward");  // First timestamp found in the file (oldest)
				$last_timestamp = $this->Applog_Date($file_array, "backward"); // Last timestamp found in the file (newest)

				// At least one timestamp must be found in the log
				if($first_timestamp != -1)
				{
					// If this log file has required data for the range add it the big data array
					if( $start_timestamp <= $last_timestamp)
					{
						$this->log_data_array = array_merge($this->log_data_array, $file_array);

						if( $start_timestamp >= $first_timestamp)
						{
							break;
						}
					}
					else
					{
						break;
					}
				}
			}
		}
	}

	// Get the timestamp from an applog line
	private function Extract_Timestamp($applog_line)
	{
		return strtotime( str_replace(".", "-", substr($applog_line, 0, 19) ) );
	}

	// Return the time group the timestamp falls in
	public function Get_Ticks()
	{
		if( !count($this->ticks) )
		{
			$ticks = array();

			$x = strtotime("+".$this->increment_amount, $this->start_timestamp);

			while($x <= $this->end_timestamp)
			{
				$ticks[] = date($this->time_format, $x);
				$x = strtotime("+".$this->increment_amount, $x);
			}

			$this->ticks = $ticks;
		}
		return $this->ticks;
	}

	// Return the time group the timestamp falls in
	private function Get_Time_Group($timestamp)
	{
		$current = $this->start_timestamp;

		while($current <= $this->end_timestamp)
		{
			if( $current > $timestamp )
			{
				return date( $this->time_format, $current);
			}

			$current = strtotime("+".$this->increment_amount, $current);
		}

		return FALSE;
	}

	// Find the first line with a date in the log, can start from the end of the file or the beginning.
	private function Applog_Date($data, $direction = "forward")
	{
		$timestamp = -1;

		if($direction == "forward")
		{
			for($i = 0; $i < count($data); $i++)
			{
				if( strlen($data[$i]) >= 19 )
				{
					$timestamp = $this->Extract_Timestamp($data[$i]);

					if($timestamp != -1)
					break;
				}
			}
		}
		elseif ($direction == "backward")
		{
			for($i = count($data)-1; $i > 0; $i--)
			{
				if( strlen($data[$i]) >= 19 )
				{
					$timestamp = $this->Extract_Timestamp($data[$i]);

					if($timestamp != -1)
					break;
				}
			}
		}
		return $timestamp;
	}

	// Load a list of the current log and any archive logs if they exist.
	private function Load_Log_List()
	{
		foreach($this->log_location_list as $log_location)
		{
			if( is_dir($log_location) )
			{
				if( $dh = opendir($log_location) )
				{
					while( ($file = readdir($dh)) !== false)
					{
						if( filetype($log_location . $file) == "file" )
						{
							if( $file == "current" || preg_match("/log\.\d+\.gz/", $file) )
							{
								$this->log_array[$log_location][] = $file;
							}
						}
					}
					closedir($dh);
				}
			}
		}

		// Sort the arrays.  Due to the naming scheme a normal ksort should give us current first and the arcives in order.
		while( $key = key($this->log_array) )
		{
			sort($this->log_array[$key]);
			next($this->log_array);
		}
	}
}

?>