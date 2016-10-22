<?php

// Pulls all the leaf node statuses and data that may be useful
function Fetch_Status_Map ($activeonly = true)
{
	static $statuses;
	
	if(is_array($statuses)) {
		return $statuses;
	}
	
	$statuses = array();
	$where_active = '';
	if ($activeonly) {
		$where_active = 'AND ass.active_status=\'active\'';
	}

	$db = ECash::getMasterDb();

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
                               $where_active 
                ORDER BY name";

	$result = $db->query($query);
	while($row = $result->fetch(PDO::FETCH_OBJ))
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

function Search_Status_Map($chain, $map) {
		foreach ($map as $id => $info) {
			if ($info['chain'] == $chain) {
				return $id;
			}
		}
}

?>
