<?php

require_once SERVER_MODULE_DIR.'collections/EcldUnmatchedReturn.php';
require_once LIB_DIR.'common_functions.php';

/**
 * Represents an unmatched return file and provides an interface to create, 
 * list and view files.
 */
class eCash_EcldUnmatchedReturnFile
{
	const MYSQL_LOCK_PREFIX = '__EUR_LOCK_';
	const FILENAME_PREFIX = 'unmatched_returns-';
	
	protected $date_modified;
	protected $date_created;
	protected $ecld_unmatched_return_file_id;
	protected $company_id;
	protected $file_content;
	protected $file_name;
	
	/**
	 * Returns the name of the file.
	 *
	 * @return string
	 */
	public function getFileName()
	{
		return $this->file_name;
	}
	
	/**
	 * Returns the contents of the file
	 *
	 * @return string
	 */
	public function getFileContent()
	{
		return $this->file_content;
	}
	
	/**
	 * Creates a new umatched return file using all unreceived unmatched 
	 * returns for the given company.
	 *
	 * @param int $company_id
	 */
	static public function createNewFile($company_id)
	{
		$return_file = new eCash_EcldUnmatchedReturnFile();
		$return_file->company_id = $company_id;
		
		$return_file->lock();
		
		try 
		{
			$matches = eCash_EcldUnmatchedReturn::loadAllUnrecievedMatches($return_file->company_id);
			
			$return_file->buildFile($matches);
			
			$return_file->save();
			
			$return_file->markMatchesRetrieved($matches);
			
			$return_file->unlock();
		}
		catch (Exception $e)
		{
			$return_file->unlock();
			throw $e;
		}
	}
	
	/**
	 * Returns an array containing the id, name, date created, and count of 
	 * return items for unmatched return files created between the $start_date 
	 * and $end_date (inclusive) for a given company.
	 *
	 * @param string $start_date
	 * @param string $end_date
	 * @param int $company_id
	 * @return Array
	 */
	static public function listMatchingFiles($start_date, $end_date, $company_id)
	{
		$db = ECash::getMasterDb();
		$query = "
			SELECT
				eurf.ecld_unmatched_return_file_id,
				eurf.file_name,
				eurf.date_created,
				COUNT(DISTINCT eur.ecld_unmatched_return_id) cnt
			FROM
				ecld_unmatched_return_file eurf
				JOIN ecld_unmatched_return eur USING (ecld_unmatched_return_file_id)
			WHERE
				eurf.company_id = {$db->quote($company_id)} AND
				eurf.date_created BETWEEN {$db->quote($start_date.'000000')} AND 
					{$db->quote($end_date.'235959')}
			GROUP BY
				eurf.ecld_unmatched_return_file_id
		";
		
		return $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
	}
	
	/**
	 * Download a specific file for a specific company
	 *
	 * @param int $ecld_unmatched_return_file_id
	 * @param int $company_id
	 * @return eCash_EcldUnmatchedReturnFile
	 */
	static public function loadFile($ecld_unmatched_return_file_id, $company_id)
	{
		$db = ECash::getMasterDb();
		$query = "
			SELECT
				*
			FROM
				ecld_unmatched_return_file
			WHERE
				company_id = {$db->quote($company_id)} AND
				ecld_unmatched_return_file_id = {$db->quote($ecld_unmatched_return_file_id)}
		";
		
		$result = $db->query($query);
		
		if ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$file = new eCash_EcldUnmatchedReturnFile();
			$file->company_id = $row['company_id'];
			$file->date_created = $row['date_created'];
			$file->date_modified = $row['date_modified'];
			$file->ecld_unmatched_return_file_id = $row['ecld_unmatched_return_file_id'];
			$file->file_content = $row['file_content'];
			$file->file_name = $row['file_name'];
			return $file;
		}
		return NULL;
	}
	
	/**
	 * Builds the file and populates the content and name properties of the 
	 * current object.
	 *
	 * @param array $matches
	 */
	protected function buildFile(Array $matches)
	{
		$company_map = Fetch_Company_Map();
		$this->file_name = self::FILENAME_PREFIX . $company_map[$this->company_id] . '-' .date('YmdHis') . '.csv';
		$this->file_content = $this->getFileHeader()."\n";
		
		foreach ($matches as $match)
		{
			/* @var $match eCash_EcldUnmatchedReturn */
			$this->file_content .= $this->buildRecordFromReturn($match)."\n";
		}
	}
	
	/**
	 * Marks the return items contained in this file as retreived.
	 *
	 * @param array $matches
	 */
	protected function markMatchesRetrieved(Array $matches)
	{
		foreach ($matches as $match)
		{
			/* @var $match eCash_EcldUnmatchedReturn */
			$match->setEcldUnmatchedReturnFileId($this->ecld_unmatched_return_file_id);
			$match->setStatus(eCash_EcldUnmatchedReturn::STATUS_RETRIEVED);
			$match->save();
		}
	}
	
	/**
	 * Returns the header of the unmatched returns file.
	 *
	 * @return string
	 */
	protected function getFileHeader()
	{
		return 'clk_acct_number,gen_date,aba,acct_number,check_number,amount,'.
			'return_code,batch_date,application_id,ecld_id,returned,amount_match,'.
			'aba_match,account_match,batch_date_match,check_number_match,score';
	}

	/**
	 * Creates an unmatched returns file line from a given unmatched return 
	 * object.
	 *
	 * @param eCash_EcldUnmatchedReturn $return
	 * @return string
	 */
	protected function buildRecordFromReturn(eCash_EcldUnmatchedReturn $return)
	{
		$values = array(
			$return->getDdaAccount(),
			$return->getDateReturn(),
			$return->getRt(),
			$return->getAccount(),
			$return->getCheckNo(),
			$return->getReturnAmount(),
			$return->getReasonCode(),
			$return->getRPostingDate(),
			$return->getApplicationId(),
			$return->getEcldId(),
			$return->getMatchReturned(),
			$return->getMatchAmount(),
			$return->getMatchRt(),
			$return->getMatchAccount(),
			$return->getMatchRPostingDate(),
			$return->getMatchCheckNo(),
			$return->getScore()
		);
		
		foreach ($values as $i => $value)
		{
			if (strpos($value, '"') !== FALSE)
			{
				$value = '"'. str_replace('"', '""', $value) . '"';
			}
		}
		
		return implode(',', $values);
	}
	
	/**
	 * Achieves a database lock to build the file. This might just be a feeble 
	 * attempt to prevent queries from ever really stacking up excessively if 
	 * multiple people try to build a file for a given company at the same time.
	 */
	protected function lock()
	{
		$lock_name = self::MYSQL_LOCK_PREFIX . $this->company_id;
		$db = ECash::getMasterDb();
		$row = $db->querySingleValue("SELECT GET_LOCK('{$lock_name}', 60) lock_result");
		
		if ($row == 0 || is_null($row))
		{
			throw new Exception("Unable to obtain lock to build unmatched return file.");
		}
	}
	
	/**
	 * Releases the database lock.
	 */
	protected function unlock()
	{
		$lock_name = self::MYSQL_LOCK_PREFIX . $this->company_id;
		
		ECash::getMasterDb()->exec("DO RELEASE_LOCK('{$lock_name}')");
	}
	
	/**
	 * Saves the return file to the database.
	 */
	public function save()
	{
		$db = ECash::getMasterDb();
		$query = "
			INSERT INTO ecld_unmatched_return_file
			(
				date_created,
				company_id,
				file_content,
				file_name
			)
			VALUES
			(
				NOW(),
				{$db->quote($this->company_id)},
				{$db->quote($this->file_content)},
				{$db->quote($this->file_name)}
			)
		";
		
		$db->exec($query);
		$this->ecld_unmatched_return_file_id = $db->lastInsertId();
	}
}

?>