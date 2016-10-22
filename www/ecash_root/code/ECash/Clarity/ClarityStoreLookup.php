<?php

/**
 * determines which UW and inquiry tyoe to use based off of the source campaign and the lookup map in the database 
 *
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class ClarityStore
{
	protected $config;

	protected $db;
    
    public $merchant;
    
    public $username;
    
    public $password;

	public function __construct($store_id)
	{
        $this->config = ECash::getConfig();
		$this->db = $this->getDatabase();

        $store_id = strtoupper($store_id);
        // build the basic UW and inquiry type query
            $sql_str_prem = "SELECT merchant, username, password ".
                "FROM uw_store AS us ".
                "WHERE store_id = '".$store_id."'";
        $result = $this->db->query($sql_str);
        $row = $result->fetchOneRow();
        // if no rows found get the default row
        if (!($row) || ($result->rowCount()<1)){
            $this->merchant = '';
            $this->username = '';
            $this->password = '';
        } else {
            $this->merchant = $row['merchant'];
            $this->username = $row['username'];
            $this->password = $row['password'];
        }
    }
	/**
	 * Gets a database connection
	 *
	 * This will attempt to connect to each defined database in the failover order
	 *
	 * @return DB_IConnection_1
	 */
	public function getDatabase()
	{

		if (!$this->db)
		{
			$db = new DB_FailoverConfig_1();
			if (!$this->use_master)
			{
				$db->addConfig($this->config->DB_API_CONFIG);
				$db->addConfig($this->config->DB_SLAVE_CONFIG);
			}
			$db->addConfig($this->config->DB_MASTER_CONFIG);
			$this->db = $db->getConnection();
		}
		return $this->db;
	}
}

?>
