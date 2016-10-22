<?

require_once(CLIENT_CODE_DIR . "display.iface.php");

class Display_Unknown implements Display
{
	public function Do_Display(ECash_Transport $object)
	{
		//set variables
		$display_data = To_String($object);

		//include HTML page
		include_once(CLIENT_VIEW_DIR . "unknown.html");
	}
}

?>