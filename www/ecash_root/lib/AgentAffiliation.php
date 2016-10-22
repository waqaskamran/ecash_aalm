<?php


class No_Such_Application_Affiliation_Exception extends Exception
{
    public function __construct($application_id) {
        parent::__construct('Application ID ' . $application_id . ' specified for affiliation operation does not exist');
    }
}

class No_Such_Agent_Affiliation_Exception extends Exception
{
    public function __construct($agent_id) {
        parent::__construct('Agent ID ' . $agent_id . ' specified for an affiliation operation does not exist');
    }
}

class eCash_AgentAffiliation_Legacy
{
	/**
	 * MSSQL has an insert limit of 1,000, so to
	 * be safe it's being limited to 999.
	 */
	const INSERT_LIMIT = 999;

	/**
	 * Batch the data retrieval to sets of this size
	 */
	const BATCH_SIZE_LIMIT = 1998;
	
	/**
	 * Maximum number of inserts that can be done into a SQL Server table type.
	 * 
	 * @var int
	 */
	const MAX_INSERTS = 1000;
	/**
	 * @var string
	 */
	protected $date_modified;
	
	/**
	 * @var string
	 */
	protected $date_created;
	
	/**
	 * @var int
	 */
	protected $agent_affiliation_id;
	
	/**
	 * @var int
	 */
	protected $company_id;
	
	/**
	 * @var int
	 */
	protected $agent_id;
	
	/**
	 * @var int
	 */
	protected $application_id;
	
	/**
	 * @var string
	 */
	protected $date_expiration;
	
	/**
	 * @var string
	 */
	protected $date_expiration_actual;
	
	/**
	 * @var string
	 */
	protected $affiliation_area;
	
	/**
	 * @var string
	 */
	protected $affiliation_type;
	
	/**
	 * @var string
	 */
	protected $affiliation_status;
	
	// Fields below are not part of the table but need to be passed to the my 
	// queue display. When we have a full data model section up and running 
	// then these fields would be represented using composite models.
	
	/**
	 * @var string
	 */
	protected $affiliation_reason;
	
	/**
	 * @var string
	 */
	protected $name_last;
	
	/**
	 * @var string
	 */
	protected $name_first;
	
	/**
	 * @var string
	 */
	protected $ssn;
	
	/**
	 * Returns the affiliation area of the affiliation. This should be one of 
	 * the values of the affiliation_area enum of the agent_affiliation table. 
	 *
	 * @return string
	 */
	public function getAffiliationArea()
	{
		return $this->affiliation_area;
	}
	
	/**
	 * Returns the application id of the affiliation.
	 *
	 * @return int
	 */
	public function getApplicationId()
	{
		return $this->application_id;
	}
	
	/**
	 * Returns the first name of the applicant.
	 *
	 * @return string
	 */
	public function getNameFirst()
	{
		return $this->name_first;
	}
	
	/**
	 * Returns the last name of the applicant.
	 *
	 * @return string
	 */
	public function getNameLast()
	{
		return $this->name_last;
	}
	
	/**
	 * Returns the ssn of the applicant.
	 *
	 * @return string
	 */
	public function getSsn()
	{
		return $this->ssn;
	}
	
	/**
	 * Returns the actual date that the affiliation expired or is going to 
	 * expire.
	 *
	 * @return string
	 */
	public function getDateExpirationActual()
	{
		return $this->date_expiration_actual;
	}
	
	/**
	 * Returns the date that the application will show up in an agent's queue.
	 *
	 * @return string
	 */
	public function getFollowUpDate()
	{
		return $this->followUpDate;
	}
	
	/**
	 * Returns the ID of the affiliation.
	 *
	 * @return int
	 */
	public function getAgentAffiliationId()
	{
		return $this->agent_affiliation_id;
	}
	
	/**
	 * Returns an array containing agent information for the current owner of 
	 * the given application in the given $area with the given $type. If an 
	 * owner does not exist then null is returned.
	 * 
	 * This method deprecates both Get_Agent_And_Account_Affiliation() and
	 * Fetch_Current_Agent_Affiliation()
	 *
	 * @param int $application_id
	 * @param string $area
	 * @param string $type
	 * @return array
	 */
	static public function getCurrentApplicationOwner($application_id, $area, $type)
	{
		self::check_for_application_id($application_id);
		return self::getMostRecentApplicationOwner($application_id, $area, $type, true);
	}
	
	/**
	 * Returns an array containing agent information for either the current 
	 * owner of the given application in the givent $area with the given 
	 * $type. If there has never been an owner for this application then null 
	 * is returned. If $active_only is true then only active affiliations will 
	 * be returned.
	 *
	 * This method deprecates Fetch_Most_Recent_Agent_Affiliation()
	 * 
	 * @param int $application_id
	 * @param string $area
	 * @param string $type
	 * @param bool $active_only
	 * @return array
	 */
	static public function getMostRecentApplicationOwner($application_id, $affiliation_area, $affiliation_type, $active_only = false)
	{
		self::check_for_application_id($application_id);

		$db = ECash::getMasterDb();
		
		if ($active_only)
		{
			$active_clause = " AND
				affiliation_status = 'active' AND
				(date_expiration_actual >= NOW() OR 
				date_expiration_actual IS NULL OR date_expiration_actual = '0000-00-00 00:00:00')
			";
		}
		else 
		{
			$active_clause = '';
		}
		
		$query = "
			SELECT
				ag.*,
				aa.date_created affiliation_created
			FROM
				agent_affiliation aa
				JOIN agent ag USING (agent_id)
			WHERE
				application_id = {$db->quote($application_id)} AND
				affiliation_area = {$db->quote($affiliation_area)} AND
				affiliation_type = {$db->quote($affiliation_type)}
				{$active_clause}
			ORDER BY
				date_expiration DESC
			LIMIT 1
		";
		
		$result = $db->query($query);
		
		return $result->fetch(PDO::FETCH_ASSOC);
	}
	


	/**
	 * A utility that will return a mysql time stamp equivelant to the end of 
	 * business $days number of days from $start_date.
	 * 
	 * If $start_date is null then the current date is used.
	 *
	 * @param int $days
	 * @param string $start_date
	 * @return string
	 */
	static public function getEndOfDayForward($days, $start_date = null)
	{
		require_once(LIB_DIR . 'business_time.class.php');
		return Company_Time::Singleton()->Get_End_Of_Day($days, $start_date);
	}
	
	/**
	 * Creates a new agent affiliation object with the given parameters. It 
	 * should be noted that this function will NOT insert into the database. 
	 * For that you should call save() on the results of this method.
	 *
	 * @param int $company_id
	 * @param int $agent_id
	 * @param int $application_id
	 * @param string $date_expiration
	 * @param string $affiliation_area
	 * @param string $affiliation_type
	 * @param string $affiliation_reason
	 * @return eCash_AgentAffiliation
	 */
	static public function createAgentAffiliation($company_id, $agent_id, $application_id, $date_expiration, $affiliation_area, $affiliation_type, $affiliation_reason = 'other')
	{
		self::check_for_application_id($application_id);
		self::check_for_agent_id($agent_id);
		$affiliation = new eCash_AgentAffiliation();
		$affiliation->date_modified = date('Y-m-d H:i:s');
		$affiliation->date_created = date('Y-m-d H:i:s');
		$affiliation->company_id = $company_id;
		$affiliation->agent_id = $agent_id;
		$affiliation->application_id = $application_id;
		$affiliation->date_expiration = $date_expiration;
		$affiliation->date_expiration_actual = $date_expiration;
		$affiliation->affiliation_area = $affiliation_area;
		$affiliation->affiliation_type = $affiliation_type;
		$affiliation->affiliation_status = 'active';
		$affiliation->affiliation_reason = $affiliation_reason;
		
		return $affiliation;
	}
	
	/**
	 * Expires all affiliations for a given application. If you specify an 
	 * $area and $type, then only those affiliations will be expired. You can 
	 * also filter it further (or in a different way) by passing an agent ID. 
	 *
	 * If you want to expire all affiliations on an application or all 
	 * affiliations for an $agent_id on a given $application then you should 
	 * call the expiredAllApplicationAffiliations() method insted.
	 *
	 * @param int $application_id
	 * @param string $area
	 * @param string $type
	 * @param int $agent_id
	 */
	static public function expireApplicationAffiliations($application_id, $area = null, $type = null, $agent_id = null)
	{
		self::check_for_application_id($application_id);
		if ($agent_id != null) self::check_for_agent_id($agent_id);
		$db = ECash::getMasterDb();

		if (isset($agent_id))
		{
			$agent_clause = " AND
				agent_id = {$db->quote($agent_id)}
			";
		}
		else 
		{
			$agent_clause = '';
		}
		
		if (isset($area))
		{
			$area_type_clause = " AND 
				affiliation_area = {$db->quote($area)} AND
				affiliation_type = {$db->quote($type)}
			";
		}
		
		$query = "
			UPDATE
				agent_affiliation
			SET
				affiliation_status = 'expired',
				date_expiration_actual = NOW()
			WHERE
				application_id = {$db->quote($application_id)} AND
				affiliation_status = 'active'
				{$area_type_clause}
				{$agent_clause}
		";
		$db->query($query);
	}
	
	/**
	 * Expires all affiliations for a given application. You can also filter 
	 * it by $agent_id to only expire that agent's affiliations.
	 *
	 * @param int $application_id
	 * @param int $agent_id
	 */
	static public function expireAllApplicationAffiliations($application_id, $agent_id = null)
	{
		self::check_for_application_id($application_id);
		if ($agent_id != null) self::check_for_agent_id($agent_id);
		self::expireApplicationAffiliations($application_id, null, null, $agent_id);
	}
	
	/**
	 * Copies all affiliations of $from_agent_id to $to_agent_id and expires 
	 * any active affiliations for $from_agent_id. The ids of the applications
	 * reassigned is returned.
	 *
	 * @param int $from_agent_id
	 * @param int $to_agent_id
	 * @return array of application ids
	 */
	static public function reassignApplications($from_agent_id, $to_agent_id)
	{
		self::check_for_agent_id($from_agent_id);
		self::check_for_agent_id($to_agent_id);
		$db = ECash::getMasterDb();

		
		$query = "
			INSERT INTO agent_affiliation
			(
				date_created,
				company_id,
				agent_id,
				application_id,
				date_expiration,
				date_expiration_actual,
				affiliation_area,
				affiliation_type,
				affiliation_status,
				agent_affiliation_reason_id
			)
			SELECT
				NOW(),
				company_id,
				{$db->quote($to_agent_id)},
				application_id,
				date_expiration,
				date_expiration_actual,
				affiliation_area,
				affiliation_type,
				affiliation_status,
				agent_affiliation_reason_id
			FROM
				agent_affiliation
			WHERE
				agent_id = {$db->quote($from_agent_id)} AND
				affiliation_status = 'active'
		";
		
		$rowcount = $db->query($query)->rowCount();
		
		self::expireAllAgentAffiliations($from_agent_id);

		return $rowcount;
	}

	/**
	 * Reports on all affiliations of the specified agent_id
	 *
	 * @param int $from_agent_id
	 * @param int $to_agent_id
	 * @return array of application ids
	 */
	static public function getAgentActiveAffiliations($from_agent_id)
	{
		$db = ECash::getMasterDb();

		$query = "
			SELECT
                application_id
            FROM
                agent_affiliation
            WHERE
                agent_id = {$db->quote($from_agent_id)} AND
                affiliation_status = 'active'
		";
		$result = $db->query($query);
		$assigned_application_ids = Array();
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$assigned_application_ids[] = $row->application_id;
		}
		return $assigned_application_ids;
	}

	/**
	 * Expires all affiliations for $agent_id.
	 *
	 * @param int $agent_id
	 */
	static public function expireAllAgentAffiliations($agent_id)
	{
		self::check_for_agent_id($agent_id);
		$db = ECash::getMasterDb();

		
		$query = "
			UPDATE
				agent_affiliation
			SET
				affiliation_status = 'expired',
				date_expiration_actual = NOW()
			WHERE
				agent_id = {$db->quote($agent_id)}
		";
		
		$db->query($query);
	}
	
	/**
	 * Sets the status of expired affiliations to expired for the given 
	 * $company_id.
	 *
	 * @param int $company_id
	 */
	static public function expireOldAgentAffiliations($company_id)
	{
		$db = ECash::getMasterDb();

		
		$query = "
			UPDATE
				agent_affiliation
			SET
				affiliation_status = 'expired'
			WHERE
				date_expiration_actual < NOW() AND
				affiliation_status = 'active' AND
				company_id = {$db->quote($company_id)}
		";
		
		$db->query($query);
	}
		
	/**
	 * Returns an array containing the process type and application id for 
	 * each application that has a standby and no active agent affiliations 
	 * for a given $company_id. 
	 *
	 * @param int $company_id
	 * @return array
	 */
	static public function getExpiredAffiliationsWithStandbys($company_id)
	{
		$db = ECash::getMasterDb();
		
		$query = "
			CREATE TEMPORARY TABLE temp_expired_affiliations_with_standbys (INDEX(application_id), INDEX (date_created))
			SELECT
				s.process_type,
				aa.application_id,
				aa.date_created,
				aa.date_expiration_actual
			FROM
				agent_affiliation AS aa
				JOIN standby AS s ON s.application_id = aa.application_id
			WHERE
				aa.affiliation_type = 'owner' AND
				aa.affiliation_area = 'collections' AND
				aa.company_id = {$company_id} AND
				s.process_type NOT IN ('qc_return', 'approval_terms')
		";
		
		$db->query($query);
		
		$result = $db->query('SELECT DISTINCT application_id FROM temp_expired_affiliations_with_standbys');
		
		$application_ids = array();
		while ($row = $result->fetch())
		{
			$application_ids[] = $row['application_id'];
		}
		
		unset($result);
		
		self::createMaxDateCreatedsFromApplicationServiceTempTable($application_ids);
		
		$query = "
			SELECT
				aa.process_type,
				aa.application_id,
				MAX(
					IFNULL(aa.date_expiration_actual, DATE_ADD(NOW(), INTERVAL 1 HOUR))
				) date_expiration_actual
			FROM
				temp_expired_affiliations_with_standbys aa
				INNER JOIN " . self::getExpiredAffiliationsWithStandbysAppSrvTempTableName() . " bb
					ON aa.application_id = bb.application_id
			WHERE
				aa.date_created >= bb.max_date_created
			GROUP BY
				aa.application_id,
				aa.process_type
			HAVING
				date_expiration_actual < NOW()
		";
		
		$result = $db->query($query);
		
		$applications = array();
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$applications[$row->application_id] = $row->process_type;
		}
		
		return $applications;
	}
	
	private static function createMaxDateCreatedsFromApplicationServiceTempTable(array $application_ids)
	{
		$mssql_db = ECash::getAppSvcDB();
		
		//$query = sprintf("EXECUTE sp_fetch_last_date_of_status_history '%s'", implode(',', $application_ids));
	
		$batched_list = array_chunk($application_ids, self::BATCH_SIZE_LIMIT);
		$data = array();
		foreach($batched_list as $application_batch_ids)
		{
			$query = 'CALL sp_fetch_last_date_of_status_history "'."'".implode("','", $application_batch_ids)."'".'";';

			$result = $mssql_db->query($query);
			while ($row = $result->fetch(DB_IStatement_1::FETCH_ASSOC))
			{
				$data[] = $row;
			}
			unset($result);
		}
		
		ECash_DB_Util::generateTempTableFromArray(
			ECash::getMasterDb(),
			self::getExpiredAffiliationsWithStandbysAppSrvTempTableName(),
			$data,
			array('application_id' => 'INT UNSIGNED', 'max_date_created' => 'DATETIME'),
			'application_id'
		);
	}
	
	private static function getExpiredAffiliationsWithStandbysAppSrvTempTableName()
	{
		return 'temp_maxDateCreated';
	}
	
	/**
	 * Saves the affiliation to the database.
	 *
	 */
	public function save()
	{
		if (!empty($this->agent_affiliation_id) && $this->agent_affiliation_id)
		{
			throw new Exception("Agent affiliations cannot be updated. They may only be expired");
		}
		else 
		{
			$this->insert();
		}
	}
	
	/**
	 * Inserts a new affiliation into the database. This function will 
	 * automatically expire other affiliation for the application in the same 
	 * area with the same type.
	 */
	protected function insert()
	{
		self::expireApplicationAffiliations($this->application_id, $this->affiliation_area, $this->affiliation_type);
		
		$db = ECash::getMasterDb();
		$query = "
			INSERT INTO agent_affiliation
			(
				date_created,
				company_id,
				agent_id,
				application_id,
				date_expiration,
				date_expiration_actual,
				affiliation_area,
				affiliation_type,
				affiliation_status,
				agent_affiliation_reason_id
			)
			VALUES
			(
				NOW(),
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				(SELECT agent_affiliation_reason_id FROM agent_affiliation_reason WHERE name_short=?)
			)
		";
		$db->queryPrepared($query,array(
				$this->company_id,
				$this->agent_id,
				$this->application_id,
				$this->date_expiration,
				$this->date_expiration_actual,
				$this->affiliation_area,
				$this->affiliation_type,
				$this->affiliation_status,
				$this->affiliation_reason,
		));
		$this->agent_affiliation_id = $db->lastInsertId();
	}
	
	static private function check_for_application_id ($application_id) {
		$app = ECash::getApplicationById($application_id);
		if (!$app->exists())
		{
			throw new No_Such_Application_Affiliation_Exception($application_id);
		}
	}

	static private function check_for_agent_id ($agent_id) {
		$db = ECash::getMasterDb();

		$query = 'SELECT COUNT(*) AS count FROM agent WHERE agent_id = ' . $db->quote($agent_id);
		$result = $db->query($query);
		$row = $result->fetch(PDO::FETCH_OBJ);
		if ($row->count == 0)
		{
			throw new No_Such_Agent_Affiliation_Exception($application_id);
		}
	}

}

?>
