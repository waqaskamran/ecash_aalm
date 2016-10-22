<?php
/**
 * Description of ECash_LoanActionHistoryService_API
 *
 * @copyright Copyright &copy; 2014 aRKaic Equipment
 * @package ApplicationService
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class ECash_LoanActionHistoryService_API extends ECash_Service_LoanActionHistoryService_API
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
