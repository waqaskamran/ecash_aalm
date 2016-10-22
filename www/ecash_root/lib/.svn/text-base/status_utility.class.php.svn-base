<?php

/**
 * A utility to deal with statuses from the application_status table
 *
 */
Class Status_Utility
{
	/**
	 * Multi-dimensional array of statuses and their info
	 *
	 * @var array
	 */
	private static $status_map;
	
	/**
	 * Get a status chain by ID
	 *
	 * @param integer status_id (example: 20)
	 * @return string status chain (example: 'active::servicing::customer::*root')
	 */	
	static public function Get_Status_Chain_By_ID($id)
	{
		if(! is_array(self::$status_map))
		{
			self::$status_map = self::Fetch_Status_Map();
		}
		
		return self::$status_map[$id]['chain'];
	}
	
	/**
	 * Search the Status Map for a status_id by the status chain
	 *
	 * @param string status chain (example: 'active::servicing::customer::*root')
	 * @return integer status id
	 */	
	static public function Get_Status_ID_By_Chain($chain)
	{
		if(! is_array(self::$status_map))
		{
			self::$status_map = self::Fetch_Status_Map();
		}

		return self::Search_Status_Map($chain);
	}

	/**
	 * Retrieve the full status name by it's status ID
	 *
	 * @param integer status_id - 109
	 * @return string status name - 'Inactive (Paid)'
	 */	
	static public function Get_Status_Name_By_ID($id)
	{
		if(! is_array(self::$status_map))
		{
			self::$status_map = self::Fetch_Status_Map();
		}

		return self::$status_map[$id]['name'];
	}
	
	/**
	 * Retrieve the short status name by it's status ID
	 *
	 * @param integer status_id - 109
	 * @return string name_sort - 'paid'
	 */
	static public function Get_Status_Short_Name_By_ID($id)
	{
		if(! is_array(self::$status_map))
		{
			self::$status_map = self::Fetch_Status_Map();
		}
		return self::$status_map[$id]['name_short'];
	}
	
	/**
	 * Retrieve the full status chain as an array
	 *
	 * @param integer status_id - 109
	 * @return array - array('paid','customer','*root')
	 */	
	static public function Get_Status_Array_By_ID($id)
	{
		if(! is_array(self::$status_map))
		{
			self::$status_map = self::Fetch_Status_Map();
		}
		return explode('::', self::$status_map[$id]['chain']);
	}
	
	/**
	 * Fetches all of the active statuses and sets an
	 * associative array with statuses by id and named
	 * 'chains' such as 'active::servicing::customer::*root'
	 *
	 * @return array Associative array of statuses
	 */
	static public function Fetch_Status_Map()
	{
		$statuses = array();

		$query = "
        SELECT  ass.application_status_id,
                ass.name,
                ass.name_short,
                asf.level0, asf.level1, asf.level2, asf.level3, asf.level4
        FROM application_status ass
        LEFT JOIN application_status_flat AS asf ON (ass.application_status_id = asf.application_status_id)
        WHERE ass.application_status_id NOT IN
              (   SELECT application_status_parent_id
                  FROM application_status
                  WHERE active_status = 'active'
                  AND application_status_parent_id IS NOT NULL  )
                  AND ass.active_status='active'
                 ORDER BY name";

		$db = ECash::getMasterDb();
		$st = $db->query($query);		
		
		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$chain = $row->level0;
			if($row->level1 != null) { $chain .= "::" . $row->level1; }
			if($row->level2 != null) { $chain .= "::" . $row->level2; }
			if($row->level3 != null) { $chain .= "::" . $row->level3; }
			if($row->level4 != null) { $chain .= "::" . $row->level4; }

			$statuses[$row->application_status_id]['id'] = $row->application_status_id;
			$statuses[$row->application_status_id]['name_short'] = $row->name_short;
			$statuses[$row->application_status_id]['name'] = $row->name;
			$statuses[$row->application_status_id]['chain'] = $chain;
		}
		return $statuses;
	}

	/**
	 * Search the Status Map for a status_id by the status chain
	 *
	 * @param string Status chain (example: 'active::servicing::customer::*root')
	 * @return integer status id
	 */
	static public function Search_Status_Map($chain) 
	{
		if(! is_array(self::$status_map))
		{
			self::$status_map = self::Fetch_Status_Map();
		}

		foreach (self::$status_map as $id => $info) {
			if ($info['chain'] == $chain) {
				return $id;
			}
		}
	}
}
