<?php

class DataX_ECash_Autoload implements IAutoload_1
{
	public function load($class_name)
	{
		if(preg_match("/ECash_DataX/is", $class_name))
		{
			$datax_dir = ECASH_CODE_DIR . "DataX/";
			
			$file_name = str_replace("ECash_DataX_Responses_", "", $class_name);
			$file_name = str_replace("ECash_DataX_Requests_", "", $file_name);
			$interface_file_name = str_replace("Ecash_DataX_", "", $class_name);
			
			$response_path = $datax_dir . "Responses/" . $file_name . ".php";
			$request_path  = $datax_dir . "Requests/" . $file_name . ".php";
			$interface_path = $datax_dir . $interface_file_name . ".php";
			
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

AutoLoad_1::addLoader(new DataX_ECash_AutoLoad());