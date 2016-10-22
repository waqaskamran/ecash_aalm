<?php

/**
 * Removes old documents from the document hash.
 *
 * @package CronJob
 * @author Mike Lively <mike.lively@sellingsource.com> 
 */
class ECash_CronJob_RemoveOldDocumentHashes
{
	/**
	 * Remove hashes older than this
	 */
	const THRESHOLD = '-60 days';

	/**
	 * @var ECash_Factory
	 */
	protected $factory;

	/**
	 * @param ECash_Factory
	 */
	public function __construct(ECash_Factory $factory)
	{
		$this->factory = $factory;
	}

	/**
	 * Initialize the cron with a factory.
	 *
	 * @param object $server Connection to the rest of the system; provided by the cron job handler.
	 * @return ECash_CronJob_RemoveOldDocumentHashes
	 */
	public static function initializeCronJob($server)
	{

		return new self(ECash::getFactory());
	}

	/**
	 * Process the cron job.
	 *
	 * @param integer $company_id
	 * @return NULL
	 */
	public function processCronJob($company_id)
	{
		$model = $this->factory->getModel('DocumentHash');

		$model->removeEntriesBefore(self::THRESHOLD, $company_id);
	}
}

function Main()
{
	global $server;

	$cron = ECash_CronJob_RemoveOldDocumentHashes::initializeCronjob($server);
	$cron->processCronJob($server->company_id);
}

?>
