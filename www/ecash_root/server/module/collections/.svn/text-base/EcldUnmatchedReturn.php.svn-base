<?php

/**
 * Represents an unmatched return row. Unmatched returns are now generated 
 * daily by the quick check returns process. In the returns file we recieve a 
 * list of returns that have incomplete information due to check damage and 
 * possible other reasons unbeknownst to me. This incomplete data is loaded 
 * into this class. The class will then attempt to locate ecld (quick check) 
 * entries that match the information we have. This information is then 
 * aggregated and acted on by the client.
 */
class eCash_EcldUnmatchedReturn
{
	/**
	 * The possible status for ecld unmatched return items.
	 */
	const STATUS_NEW = 'new';
	const STATUS_MATCHED = 'matched';
	const STATUS_UNMATCHED = 'unmatched';
	const STATUS_RETRIEVED = 'retrieved';
	
	protected $date_modified;
	protected $date_created;
	protected $ecld_unmatched_return_id;
	protected $company_id;
	protected $ecld_unmatched_return_file_id;
	protected $date_return;
	
	protected $r_posting_date;
	protected $check_no;
	protected $rt;
	protected $account;
	protected $return_amount;
	protected $reason_code;
	
	protected $return_data = array();
	protected $status = self::STATUS_NEW;
	
	protected $score = 0;
	protected $match_returned = false;
	protected $match_r_posting_date = false;
	protected $match_check_no = false;
	protected $match_rt = false;
	protected $match_account = false;
	protected $match_amount = false;
	
	protected $application_id = 0;
	protected $ecld_id = 0;
	
	protected $dda_account;
	
	/**
	 * Update the status of the return (use self::STATUS_* constants)
	 *
	 * @param string $status
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}
	
	/**
	 * Sets the id of the unmatched return file that this return was included in.
	 *
	 * @param int $ecld_unmatched_return_file_id
	 */
	public function setEcldUnmatchedReturnFileId($ecld_unmatched_return_file_id)
	{
		$this->ecld_unmatched_return_file_id = $ecld_unmatched_return_file_id;
	}
	
	/**
	 * Returns the application id or 0 if a match has not been found.
	 *
	 * @return int
	 */
	public function getApplicationId()
	{
		return $this->application_id;
	}
	
	/**
	 * Retuns the DDA account number
	 *
	 * @return string
	 */
	public function getDdaAccount()
	{
		return $this->dda_account;
	}
	
	/**
	 * Returns the date an item was returned
	 *
	 * @return string
	 */
	public function getDateReturn()
	{
		return $this->date_return;
	}
	
	/**
	 * Returns the bank aba for the return item.
	 *
	 * @return string
	 */
	public function getRt()
	{
		return $this->rt;
	}
	
	/**
	 * Returns the account number for the return item.
	 *
	 * @return string
	 */
	public function getAccount()
	{
		return $this->account;
	}
	
	/**
	 * Returns the check number for the return item.
	 *
	 * @return string
	 */
	public function getCheckNo()
	{
		return $this->check_no;
	}
	
	/**
	 * Returns the amount of the return
	 *
	 * @return float
	 */
	public function getReturnAmount()
	{
		return $this->return_amount;
	}
	
	/**
	 * Returns the reason for the return
	 *
	 * @return string
	 */
	public function getReasonCode()
	{
		return $this->reason_code;
	}
	
	/**
	 * Returns the date the returned item attempted to post.
	 *
	 * @return string
	 */
	public function getRPostingDate()
	{
		return $this->r_posting_date;
	}
	
	/**
	 * Returns the ecld id of the return or 0 if there was no match found.
	 *
	 * @return int
	 */
	public function getEcldId()
	{
		return $this->ecld_id;
	}
	
	/**
	 * Returns the score of the current match. The higher this number the better the match.
	 *
	 * @return int
	 */
	public function getScore()
	{
		return $this->score;
	}
	
	/**
	 * Returns true if the return code matched. (currently not checked)
	 *
	 * @return bool
	 */
	public function getMatchReturned()
	{
		return $this->match_returned;
	}
	
	/**
	 * Returns true if the posting date matched.
	 *
	 * @return bool
	 */
	public function getMatchRPostingDate()
	{
		return $this->match_r_posting_date;
	}
	
	/**
	 * Returns true if the check number matched.
	 *
	 * @return bool
	 */
	public function getMatchCheckNo()
	{
		return $this->match_check_no;
	}
	
	/**
	 * Returns true if the check aba matched.
	 *
	 * @return bool
	 */
	public function getMatchRt()
	{
		return $this->match_rt;
	}
	
	/**
	 * Returns true if the check account number matched.
	 *
	 * @return bool
	 */
	public function getMatchAccount()
	{
		return $this->match_account;
	}
	
	/**
	 * Returns true if the check amount matched.
	 *
	 * @return bool
	 */
	public function getMatchAmount()
	{
		return $this->match_amount;
	}
	
	/**
	 * Creates a new unmatched return object based on a simple xml object 
	 * encapsulating an UnmatchedReturn element from the qc return file 
	 * recieved from us bank.
	 *
	 * @param SimpleXMLElement $unmatched_return
	 * @param int $company_id
	 * @param int $return_date
	 * @return eCash_EcldUnmatchedReturn
	 */
	static public function loadFromSimpleXML(SimpleXMLElement $unmatched_return, $company_id, $return_date = null)
	{
		if (empty($return_date))
		{
			$return_date = date('Y-m-d');
		}
		
		$return = new eCash_EcldUnmatchedReturn();
		$return->company_id = $company_id;
		$return->date_return = $return_date;
		$return->r_posting_date = trim($unmatched_return['RPostingDate']);
		$return->check_no = trim($unmatched_return['CheckNo']);
		$return->rt = trim(trim($unmatched_return['RT'], '0'));
		$return->account = trim($unmatched_return['Account']);
		$return->return_amount = trim($unmatched_return['ReturnAmount']);
		$return->reason_code = trim($unmatched_return['ReasonCode']);
		$return->dda_account = trim($unmatched_return['DDAAccount']);
		$return->return_data = serialize($unmatched_return->attributes());
		$return->status = self::STATUS_NEW;
		
		return $return;
	}
	
	/**
	 * Returns the number of unreceived matched for a given company. If there 
	 * was an error retrieving a count then -1 is returned
	 *
	 * @param int $company_id
	 * @return int
	 */
	static public function countAllUnrecievedMatches($company_id)
	{
		$db = ECash::getMasterDb();
		$query = "
			SELECT COUNT(*) cnt
			FROM ecld_unmatched_return
			WHERE
				status IN (".$db->quote(self::STATUS_MATCHED).", ".$db->quote(self::STATUS_UNMATCHED ).") AND
				company_id = ".$db->quote($company_id)."}
		";
		
		return $db->querySingleValue($query);
	}
	
	/**
	 * Returns an array of EcldUnmatchedReturn objects that have not yet been 
	 * received for a given company.
	 *
	 * @param int $company_id
	 * @return EcldUnmatchedReturn
	 */
	static public function loadAllUnrecievedMatches($company_id)
	{
		$db = ECash::getMasterDb();
		$query = "
			SELECT * 
			FROM ecld_unmatched_return
			WHERE
				status IN (".$db->quote(self::STATUS_MATCHED).", ".$db->quote(self::STATUS_UNMATCHED ).") AND
				company_id = ".$db->quote($company_id);
		
		$result = $db->query($query);
		
		$matches = array();
		
		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$unmatched_return = new eCash_EcldUnmatchedReturn();
			
			foreach ($row as $column => $value)
			{
				$unmatched_return->$column = $value;
			}
			
			$matches[] = $unmatched_return;
		}
		
		return $matches;
	}
	
	/**
	 * Attempts to find a matching ecld row based on the information in the 
	 * current object and returns true if a match was found.
	 *
	 * @return bool
	 */
	public function findMatch()
	{
		$potential_matches = $this->findMatchingECLDs();
		
		$best_match = false;
		foreach ($potential_matches as $potential_match)
		{
			$potential_match['bank_aba'] = trim($potential_match['bank_aba'], '0');
			
			$match_results = array();
			$score = $this->scoreMatch($potential_match, $match_results);
			
			if (!$best_match  || $score > $best_match['current_score']|| ($score == $best_match['current_score'] && 
				($best_match['application_id'] > $potential_match['application_id'] || 
				$best_match['ecld_id'] > $potential_match['ecld_id'])))
			{
				$best_match = $potential_match;
				$best_match['score'] = $score;
				$best_match['matches'] = $match_results;
			}
		}
		
		$this->application_id = empty($best_match['application_id']) ? 0 : $best_match['application_id'];
		$this->ecld_id = empty($best_match['ecld_id']) ? 0 : $best_match['ecld_id'];
		$this->match_returned = isset($best_match['matches']) && in_array('match_returned', $best_match['matches']) ? 'yes' : 'no';
		$this->match_amount = isset($best_match['matches']) && in_array('match_amount', $best_match['matches']) ? 'yes' : 'no';
		$this->match_rt = isset($best_match['matches']) && in_array('match_rt', $best_match['matches']) ? 'yes' : 'no';
		$this->match_account = isset($best_match['matches']) && in_array('match_account', $best_match['matches']) ? 'yes' : 'no';
		$this->match_r_posting_date = isset($best_match['matches']) && in_array('match_r_posting_date', $best_match['matches']) ? 'yes' : 'no';
		$this->match_check_no = isset($best_match['matches']) && in_array('match_check_no', $best_match['matches']) ? 'yes' : 'no';
		$this->score = isset($best_match['score']) ? $best_match['score'] : 0;
		$this->status = isset($best_match['score']) && $best_match['score'] > 0 ? self::STATUS_MATCHED : self::STATUS_UNMATCHED;
		
		return ($this->score > 0);
	}
	
	/**
	 * Creates a score for a given ecld row to the current object. This score 
	 * is returned and $results is set to an array containing the match_* 
	 * properties that were successfully matched.
	 *
	 * @param array $potential_match
	 * @param array $results
	 * @return int
	 */
	protected function scoreMatch(Array $potential_match, Array &$results)
	{
		$score = 0;
		$results = array();
		if ($potential_match['business_date'] == $this->r_posting_date)
		{
			$score++;
			$results[] = 'match_r_posting_date';
		}
		
		if ($potential_match['ecld_id'] == $this->check_no)
		{
			$score++;
			$results[] = 'match_check_no';
		}
		
		if ($potential_match['bank_aba'] == $this->rt)
		{
			$score++;
			$results[] = 'match_rt';
		}
		
		if ($potential_match['bank_account'] == $this->account)
		{
			$score++;
			$results[] = 'match_account';
		}
		
		if ($potential_match['amount'] == $this->return_amount)
		{
			$score++;
			$results['match_amount'];
		}
		
		return $score;
	}
	
	/**
	 * Returns an array of ecld rows that could potentially match this object.
	 *
	 * @return array
	 */
	protected function findMatchingECLDs()
	{
		$db = ECash::getMasterDb();
		if (trim($this->account, '0') == '')
		{
			$account_sql = '';
		}
		else 
		{
			$account_sql = "bank_account LIKE {$db->quote('%'.$this->account.'%')} OR";
		}
		
		$query = "
			SELECT *
			FROM ecld
			WHERE
				(
					{$account_sql}
					bank_aba LIKE {$db->quote('%'.$this->rt.'%')} OR
					ecld_id = {$db->quote($this->check_no)}
				) AND
				amount = {$db->quote($this->return_amount)} AND
				company_id = {$db->quote($this->company_id)}
		";
		
		$result = $db->query($query);
		return $result->fetchAll(PDO::FETCH_ASSOC);
	}
	
	/**
	 * Saves the record to the database.
	 *
	 */
	public function save()
	{
		if (!empty($this->ecld_unmatched_return_id) && $this->ecld_unmatched_return_id)
		{
			$this->update();
		}
		else 
		{
			$this->insert();
		}
	}
	
	/**
	 * Issues an update to the database to set all properties of the row with 
	 * a matching primary key.
	 */
	protected function update()
	{
		$query = "
			UPDATE ecld_unmatched_return
			SET
				company_id = ?,
				date_return = ?,
				r_posting_date = ?,
				check_no = ?,
				rt = ?,
				account = ?,
				return_amount = ?,
				reason_code = ?,
				return_data = ?,
				status = ?,
				score = ?,
				match_returned = ?,
				match_r_posting_date = ?,
				match_check_no = ?,
				match_rt = ?,
				match_account = ?,
				match_amount = ?,
				application_id = ?,
				ecld_id = ?,
				dda_account = ?,
				ecld_unmatched_return_file_id = ?
			WHERE
				ecld_unmatched_return_id = ?
		";
		
		$args = array(
			$this->company_id,
			$this->date_return,
			$this->r_posting_date,
			$this->check_no,
			$this->rt,
			$this->account,
			$this->return_amount,
			$this->reason_code,
			$this->return_data,
			$this->status,
			$this->score,
			$this->match_returned,
			$this->match_r_posting_date,
			$this->match_check_no,
			$this->match_rt,
			$this->match_account,
			$this->match_amount,
			$this->application_id,
			$this->ecld_id,
			$this->dda_count,
			$this->ecld_unmatched_return_file_id,
			$this->ecld_unmatched_return_id
		);
		
		ECash::getMasterDb()->queryPrepared($query, $args);
	}
	
	/**
	 * Inserts an array of return objects using the given connection using a 
	 * batch insert.
	 *
	 * @param Array $returns
	 */
	static public function insertBatch($db, $returns)
	{
		$db = ECash::getMasterDb();
		$query = "
			INSERT INTO ecld_unmatched_return
			(
				date_created,
				company_id,
				date_return,
				r_posting_date,
				check_no,
				rt,
				account,
				return_amount,
				reason_code,
				return_data,
				status,
				score,
				match_returned,
				match_r_posting_date,
				match_check_no,
				match_rt,
				match_account,
				match_amount,
				application_id,
				ecld_id,
				dda_account
			)
			VALUES
		";
		
		$values = array();
		foreach ($returns as $return)
		{
			if ($return instanceof eCash_EcldUnmatchedReturn)
			{
				$values[] = "(
					NOW(),
					{$db->quote($return->company_id)},
					{$db->quote($return->date_return)},
					{$db->quote($return->r_posting_date)},
					{$db->quote($return->check_no)},
					{$db->quote($return->rt)},
					{$db->quote($return->account)},
					{$db->quote($return->return_amount)},
					{$db->quote($return->reason_code)},
					{$db->quote($return->return_data)},
					{$db->quote($return->status)},
					{$db->quote($return->score)},
					{$db->quote($return->match_returned)},
					{$db->quote($return->match_r_posting_date)},
					{$db->quote($return->match_check_no)},
					{$db->quote($return->match_rt)},
					{$db->quote($return->match_account)},
					{$db->quote($return->match_amount)},
					{$db->quote($return->application_id)},
					{$db->quote($return->ecld_id)},
					{$db->quote($return->dda_account)}
				)";
			}
			else 
			{
				throw new InvalidArgumentException;
			}
		}
		
		$query .= implode(",", $values);
		
		$db->query($query);
	}
	
	/**
	 * Inserts the current object into the database.
	 */
	protected function insert()
	{
		$query = "
			INSERT INTO ecld_unmatched_return
			(
				date_created,
				company_id,
				date_return,
				r_posting_date,
				check_no,
				rt,
				account,
				return_amount,
				reason_code,
				return_data,
				status,
				score,
				match_returned,
				match_r_posting_date,
				match_check_no,
				match_rt,
				match_account,
				match_amount,
				application_id,
				ecld_id,
				dda_account
			)
			VALUES
			(
				NOW(),
				{$db->quote($this->company_id)},
				{$db->quote($this->date_return)},
				{$db->quote($this->r_posting_date)},
				{$db->quote($this->check_no)},
				{$db->quote($this->rt)},
				{$db->quote($this->account)},
				{$db->quote($this->return_amount)},
				{$db->quote($this->reason_code)},
				{$db->quote($this->return_data)},
				{$db->quote($this->status)},
				{$db->quote($this->score)},
				{$db->quote($this->match_returned)},
				{$db->quote($this->match_r_posting_date)},
				{$db->quote($this->match_check_no)},
				{$db->quote($this->match_rt)},
				{$db->quote($this->match_account)},
				{$db->quote($this->match_amount)},
				{$db->quote($this->application_id)},
				{$db->quote($this->ecld_id)},
				{$db->quote($this->dda_account)}
			)
		";
		$db = ECash::getMasterDb();
		$db->query($query);
		$this->ecld_unmatched_return_id = $db->lastInsertId();
	}
}

?>