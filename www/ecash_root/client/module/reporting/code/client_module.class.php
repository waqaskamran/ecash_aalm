<?php

/**
 * ecash module factory -- Reporting
 *
 * Handles choosing and construction of all reports
 *
 * @package Reporting
 */
class Client_Module
{
	public static function Get_Display_Module(ECash_Transport $transport, $module_name)
	{
		$mode = ECash::getTransport()->Get_Current_Level();
		//$view = ECash::getTransport()->Get_Next_Level();

		if ($mode == "report_initialization")
		{
			$mode = "report_parent";
		}
		else
		{
			
			require_once("display_report_parent.class.php");
		}
		
		if(file_exists(CUSTOMER_LIB . "/reporting/display_{$mode}_report.class.php"))
		{
			require_once(CUSTOMER_LIB . "/reporting/display_{$mode}_report.class.php");
		}
		else 
		{	
			require_once("display_{$mode}_report.class.php");
		}


		$class = ucwords(str_replace("_", " ", $mode));
		$class = str_replace(" ", "_", $class);
		$class .= "_Report";
		$application_object = new $class($transport, $module_name);
		$application_object->Set_Mode($mode);
		//$application_object->Set_View($view);

		// We need to check to see if it's a download here or not.
		// If so, we call Download_Data, which sends download headers
		// to the client, so we're done, and the module tells the Display_App
		// class to stop processing.
		$data = ECash::getTransport()->Get_Data();
		if (isset($data->download) && $data->download)
		{
			$application_object->Download_Data();
			$application_object->send_display_data = false;
		}
		if (isset($data->download_xml_report) && $data->download_xml_report)
		{
			$application_object->Download_XML_Data();
			$application_object->send_display_data = false;
		}
		return ($application_object);
	}
}

?>
