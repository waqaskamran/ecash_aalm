<?php
require_once(LIB_DIR . "request_log.class.php");

class Request_Timer {
	static private $start_time;
	static private $log;
	static private $company_id;
	static private $agent_id;
	static private $module;
	static private $mode;
	static private $action;
	static private $database;
	static private $levels;

	static private $sql_h;
	
	static private $last_error;
	
	static public function Start()
	{
		self::$start_time = round(microtime(true), 4);
	}
	
	static public function Set_Log($log) {
		self::$log = $log;
	}
	
	static public function Set_Database($database) {
		self::$database = $database;
	}
	
	static public function Set_Request_Information($company_id, $agent_id, $module, $mode, $action, $levels) {
		self::$company_id = $company_id;
		self::$agent_id = $agent_id;
		self::$mode = $mode;
		self::$module = $module;
		self::$action = $action;
		self::$levels = implode('|', $levels);
	}

	static public function Stop()
	{
		$rusage = getrusage();
		$user_time = $rusage['ru_utime.tv_sec'] + ($rusage['ru_utime.tv_usec'] / 1000000);
		$system_time = $rusage['ru_stime.tv_sec'] + ($rusage['ru_stime.tv_usec'] / 1000000);
		
		$values = array(
			'company_id' => self::$company_id,
			'agent_id' => self::$agent_id,
			'module' => self::$module,
			'mode' => self::$mode,
			'action' => self::$action,
			'levels' => self::$levels,
			'start_time' => self::$start_time,
			'stop_time' => round(microtime(true), 4),
			'elapsed_time' => round(microtime(true) - self::$start_time, 4),
			'memory_usage' => memory_get_usage(),
			'user_time' => $user_time,
			'system_time' => $system_time
		);
		
		$result = self::Log_Request($values);
		
		$log_text = "Elapsed time for [Request]  is " . $values['elapsed_time'] . " seconds.";
		if (self::$agent_id) 
		{
			$log_text .= " [agent_id:".self::$agent_id."]";
		}

		if ($result) 
		{
			$log_text .= " [request_log_id:".$result."]";
		} 
		else 
		{
			self::$log->Write("Could not save request: ".self::$last_error);
		}
		
		self::$log->Write($log_text);
	}
	
	/**
	 * Inserts row into the request_log table
	 */
	static protected function Log_Request($values) {
		try 
		{
			$new_database = new Request_Log();
			
			$result = $new_database->Insert_Request($values);
			
			if ($result === false) 
			{
				self::$last_error = $new_database->last_error;
			}
			return $result;
		} 
		catch (Exception $e) 
		{
			self::$last_error = "{$e->getFile()}:{$e->getLine()} - {$e->getMessage()}";
			return false;
		}
	}
}
