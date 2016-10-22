<?php

/**
 * ACH class
 * 
 * This class wraps provides a factory for the appropriate ACH 
 * batches, returns, and transports.
 */
class ACH
{
	/**
	 * Simple factory method used to return the appropriate Batch or Returns class for the
	 * current company's ACH processor
	 *
	 * @param Server $server
	 * @param string $process_type
	 * @return Object
	 */
	public static function Get_ACH_Handler($server, $process_type = NULL, $submitted_format = NULL)
	{
		if($process_type)
		{
			if (
				($submitted_format === NULL)
				//asm 114
				|| ($submitted_format == 'transact24')
				|| ($submitted_format == 'empire_business_solutions')
				|| ($submitted_format == 'empire_ofs' && $process_type == 'batch')
			)
			{
				$type = (strtolower($process_type) == 'return') ? ECash::getConfig()->ACH_RETURN_FORMAT : ECash::getConfig()->ACH_BATCH_FORMAT;
			}
			else
			{
				$type = strtolower($submitted_format);
			}

			$file_name = "ach_" . strtolower($process_type) . "_" . strtolower($type) . ".class.php";

			require_once(LIB_DIR . 'Ach/' . $file_name);

			$class = "ACH_" . ucfirst($process_type) . "_" . ucwords($type);

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
