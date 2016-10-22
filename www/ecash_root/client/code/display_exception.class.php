<?php

class Display_Exception
{

	public function Do_Display(Exception $e)
	{
		//do some shit
		$details = To_String($e);
	
		echo "<pre>Message: {$e->getMessage()}\n";
		echo "Details: {$details}\n</pre>";
	}
 
}
?>