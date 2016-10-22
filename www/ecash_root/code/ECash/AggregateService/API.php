<?php
/**
 * Description of ECash_AggregateService_API
 *
 * @copyright Copyright &copy; 2014 aRKaic Equipment
 * @package ApplicationService
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class ECash_AggregateService_API extends ECash_Service_AggregateService_API
{

	/**
	 * Inserts a message into the log.
	 *
	 * @param string $message The message to log.
	 * @return void
	 */
	protected function insertLogEntry($message) {
		$log = ECash::getLog();
		$log->write($message);
	}
	
}
