<?php

/**
 * determines which UW from the inquiry type 
 *
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class VendorAPI_Inquiry2UW_Lookup {
	protected $config;

	protected $db;

	public function __construct() {
        $this->config = ECash::getConfig();
		$this->db = $this->getDatabase();
	}

	public function lookupUW($inquiry) {
        $campaign = strtoupper($inquiry);
        // build the basic UW and inquiry type query
        $sql_str = "SELECT up.uw_name_short ".
            "FROM uw_inquiries AS ui ".
            "JOIN uw_providers AS up USING (uw_provider_id) ".
        "WHERE ui.uw_inquiry_name = '".$inquiry."'";
        // get the rows associated with the campaign
	$result = $this->db->query($sql_str);
        $rows = $result->fetchAll();
        return $rows[0]['uw_name_short'];
	}
	/**
	 * Gets a database connection
	 *
	 * This will attempt to connect to each defined database in the failover order
	 *
	 * @return DB_IConnection_1
	 */
	public function getDatabase() {

		if (!$this->db)
		{
			$db = new DB_FailoverConfig_1();
			$db->addConfig($this->config->DB_MASTER_CONFIG);
			$this->db = $db->getConnection();
		}
		return $this->db;
	}
}

?>
