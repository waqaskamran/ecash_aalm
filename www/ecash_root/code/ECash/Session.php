<?php

/**
 * @TODO maybe make this into Session_CompressedDatabaseSession_1 in libolution
 *
 */
class ECash_Session extends Session_DatabaseSession_1
{
	const GZIP = 'gz';
	const BZIP = 'bz';

	protected $compression;

	public function __construct($session_name, $session_id, $compression = NULL)
	{
		$compression = strtolower($compression);
		$this->compression = in_array($compression, array(self::GZIP, self::BZIP)) ? $compression : NULL;
		parent::__construct(ECash_Models_WritableModel::ALIAS_MASTER, $session_name, $session_id, 'session_id', 'session_info');
	}

	public function read($id)
	{
		return $this->uncompress(parent::read($id));
	}

	public function write($id, $data)
	{
		$data = $this->compress($data);
		//can't call parent::write because we need to write to the compression column
		$db = DB_DatabaseConfigPool_1::getConnection($this->db_alias);

		$compression_value = is_null($this->compression) ? 'none' : $this->compression;

		$query = "
				INSERT INTO {$this->getTableByID($id)} ({$this->session_column_id}, {$this->session_column_data}, compression, session_open, date_created)
				VALUES (?, ?, ?, 1, NOW())
				ON DUPLICATE KEY UPDATE
					{$this->session_column_data} = VALUES({$this->session_column_data}),
					compression = VALUES(compression),
					session_open = 1,
					date_modified = NOW()
			";

		file_put_contents('/tmp/session_stuff', $query);

		$stmt = $db->queryPrepared($query, array($id, $data, $compression_value));
		return TRUE;
	}

	protected function compress($data)
	{
		switch($this->compression)
		{
			case self::GZIP:
				return gzcompress($data);
				break;

			case self::BZIP:
				return bzcompress($data);
				break;

			default:
				return $data;
				break;
		}
	}

	protected function uncompress($data)
	{
		//to avoid empty strings, etc.
		if(!$data)
			return $data;

		switch($this->compression)
		{
			case self::GZIP:
				return gzuncompress($data);
				break;

			case self::BZIP:
				return bzdecompress($data);
				break;

			default:
				return $data;
				break;
		}
	}

}

?>