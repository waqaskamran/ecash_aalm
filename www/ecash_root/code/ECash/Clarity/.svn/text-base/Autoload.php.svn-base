<?php

class Clarity_ECash_Autoload implements IAutoload_1
{
	public function load($class_name)
	{
		if(preg_match("/ECash_Clarity/is", $class_name))
		{
			$clarity_dir = ECASH_CODE_DIR . "Clarity/";
			
			$file_name = str_replace("ECash_Clarirty_Responses_", "", $class_name);
			$file_name = str_replace("ECash_Clarity_Requests_", "", $file_name);
			$interface_file_name = str_replace("Ecash_Clarity_", "", $class_name);
			
			$response_path = $clarity_dir . "Responses/" . $file_name . ".php";
			$request_path  = $clarity_dir . "Requests/" . $file_name . ".php";
			$interface_path = $clarity_dir . $interface_file_name . ".php";
			
			$success = FALSE;
			
			if(file_exists($response_path))
			{
				include_once($response_path);
				$success = TRUE;
			}
			
			if(file_exists($request_path))
			{
				include_once($request_path);
				$success = TRUE;
			}
			
			if(file_exists($interface_path))
			{
				include_once($interface_path);
				$success = TRUE;
			}
			
			return $success;
		}
		
		return FALSE;
	}
}

AutoLoad_1::addLoader(new Clarity_ECash_AutoLoad());