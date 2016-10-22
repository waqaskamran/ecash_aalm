<?php

/**
 * Card class
 * 
 * This class wraps provides a factory for the appropriate Card processor 
 * batches, payments, and cancels.
 */
class Card
{
	/**
	 * Simple factory method used to return the appropriate class for the
	 * current company's Payment Card processor
	 *
	 * @param Server $server
	 * @param string $process_type
	 * @return Object
	 */
	public static function Get_Card_Process($server, $process_type = NULL)
	{
		if($process_type)
		{
			$type = ECash::getConfig()->CARD_PROCESS_FORMAT;
			
			$file_name = "card_" . strtolower($process_type) . "_" . strtolower($type) . ".class.php";

			require_once(LIB_DIR . 'Card/' . $file_name);

			$class = "Card_" . ucfirst($process_type) . "_" . ucwords($type);
			
			if (class_exists($class)) 
			{
				return new $class($server);
			} 
			else 
			{
				throw new Exception ("Cannot find class named '$class'");
			}
		}
		else 
		{
			throw new Exception ("Process name must be passed!");
		}
	}
}
?>
