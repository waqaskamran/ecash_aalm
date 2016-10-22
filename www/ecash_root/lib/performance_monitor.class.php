<?php

require_once( LIB_DIR . 'request_log.class.php');

class Performance_Monitor
{
	private $log_db;
	private $log_array;
	private $log_data_array;
	private $results;
	private $track_items;
	private $ticks;

	private $start_timestamp;
	private $end_timestamp;
	private $increment_amount;
	private $time_format;

	public function __construct($log_db)
	{
		$this->log_db = $log_db;
		$this->results = array();
		$this->time_group_stamps = array();
		$this->track_items = NULL;
		
		date_default_timezone_set(ECash::getConfig()->TIME_ZONE);
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
	public function Parse_Stats($start_timestamp, $end_timestamp, $increment_amount, $time_format = FALSE, $agent_id = null)
	{
		$company_map = array();
		$this->start_timestamp = $start_timestamp;
		$this->end_timestamp = $end_timestamp;
		$this->increment_amount = $increment_amount;
		$this->time_format = $time_format;
		$this->results = array();
		
		try 
		{
			$request_log = new Request_Log();
			if (!$requests = $request_log->Retrieve_Requests($this->start_timestamp, $this->end_timestamp, $GLOBALS['server']->company_id, $agent_id)) 
			{
				get_log()->Write("Request Log Error: {$request_log->last_error}");
				return false;
			}
		} 
		catch (Exception $e) 
		{
			get_log()->Write("Request Log Error: {$e->getMessage()}");
			return false;
		}
		
		foreach ($requests as $request) 
		{
			$stampy = $request['start_time'];
			$company = $request['company_id'];
			$company_map = Fetch_Company_Map();
			
			foreach ($this->track_items as $item => $label) 
			{
				if (!isset($agent_id)) 
				{
					$index = $company_map[$company];
				} else

				{
					$index = $request['agent_id'];
				}
				
				if (!isset($this->results[$label][$index])) 
				{
					$this->Initialize_Company($this->results[$label][$index]);
				}
				if($time_format !== FALSE)
				{
					$time_group = $this->Get_Time_Group($stampy);
	
					if( $time_group !== FALSE)
					{
						$this->results[$label][$index][$time_group][] = $request[$item];
					}
				}
				else
				{
					$this->results[$label][$index][] = $request[$item];
				}
			}
		}

		foreach($this->results as $item_tracked => $companies)
		{
			foreach($companies as $company => $time_group)
			{
				while( $key = key($time_group) )
				{
					if( !count($this->results[$item_tracked][$company][$key]) )
					{
						$this->results[$item_tracked][$company][$key][] = 0;
					}
	
					next($time_group);
				}
			}
		}

		ksort($this->results);

		return $this->results;
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
	
	private function Initialize_Company(&$arr) {
		$current = $this->start_timestamp;
		$current = strtotime("+".$this->increment_amount, $current);
		
		while ($current <= $this->end_timestamp) 
		{
			$arr[date( $this->time_format, $current)] = array();
			$current = strtotime("+".$this->increment_amount, $current);
		}
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

	private function Load_Log_List($start_from = NULL)
	{
		if(NULL === $start_from)
		{
			$this->log_array = array();

			foreach($this->log_location_list as $log_location)
			{
				$this->Load_Log_List($log_location);
			}

			sort($this->log_array);

			return(NULL);
		}

		if( is_dir($start_from) )
		{
			$handle = opendir($start_from);

			if(is_resource($handle))
			{
				while( FALSE !== ( $file = readdir($handle) ) )
				{
					if("." != $file && ".." != $file)
					{
						$this->Load_Log_List("$start_from/$file");
					}
				}

				closedir($handle);
			}

			return(NULL);
		}

		if( is_file($start_from) && ( "/current" == strrchr($start_from,"/") || preg_match("/log\.\d+\.gz/", $start_from) ) )
		{
			$this->log_array[] = $start_from;
			return(NULL);
		}
	}
}

?>
