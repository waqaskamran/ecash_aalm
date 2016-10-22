<?php
/**
 * Description of ECash_Loan_API
 *
 * @copyright Copyright &copy; 2009 The Selling Source, Inc.
 * @package ECash_Loan
 * @author Bryan Campbell <bryan.campbell@dataxltd.com>
 */
class ECash_DocumentService_API extends ECash_Service_DocumentService_API
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
