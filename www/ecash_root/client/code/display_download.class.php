<?

require_once(CLIENT_CODE_DIR . "display.iface.php");

class Display_Download implements Display
{
	public function Do_Display(ECash_Transport $object)
	{
		$report = $object->Get_Next_Level();

		if($report != NULL)
		{
			require_once CLIENT_MODULE_DIR ."reporting/code/download_{$report}.class.php";
			$download_obj = new Client_Download($object);
			
			$download_obj->Download_Data();
		}
	
	}
}

?>