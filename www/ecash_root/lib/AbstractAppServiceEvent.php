<?php

require_once(LIB_DIR . 'Nightly_Event.class.php');

/**
 * Abstract base class for pulling application ID's from the application service by status.
 * 
 * @author Brian Feaver
 */
abstract class ECash_Nightly_AbstractAppServiceEvent extends ECash_Nightly_Event
{
	/**
	 * Returns a string with the name of the temp table to use.
	 * 
	 * @return string
	 */
	abstract protected function getTempTableName();
	
	/**
	 * Creates a temporary table containing the application ID's returned by the application service for the specified
	 * statuses.
	 * 
	 * @param array $statuses an array of statuses
	 */
	protected function createApplicationIdTempTable(array $statuses)
	{
		$db = ECash::getAppSvcDB();
		$app_ids = array();
		
		foreach ($statuses as $status)
		{
			$as_query = "EXECUTE sp_fetch_application_ids_by_application_status '{$status}'";
			$result = $db->query($as_query);
			$app_ids = array_merge($app_ids, $result->fetchAll());
		}
		
		ECash_DB_Util::generateTempTableFromArray($this->db, $this->getTempTableName(), $app_ids,
				$this->getTempTableSpec(), $this->getApplicationIdColumn());
	}
	
	/**
	 * Returns an array that contains the spec for the temp table with the key as the column name and the value as the
	 * MySQL specification.
	 * 
	 * @return array
	 */
	protected function getTempTableSpec()
	{
		return array($this->getApplicationIdColumn() => 'INT UNSIGNED');
	}
	
	/**
	 * Returns the name of the application ID column.
	 * 
	 * @return string
	 */
	protected function getApplicationIdColumn()
	{
		return 'application_id';
	}
}