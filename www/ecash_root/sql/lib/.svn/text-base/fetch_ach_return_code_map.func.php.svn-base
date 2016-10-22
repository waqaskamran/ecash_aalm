<?php

function Fetch_ACH_Return_Code_Map()
{
	static $return_codes;
	
	if (empty($return_codes))
	{
		$db = ECash::getMasterDb();
		
		$sql = "
			SELECT
				ach_return_code_id as id,
				name_short as return_code,
				name as return_description,
				is_fatal
			FROM	ach_return_code
		";
		$st = $db->query($sql);
		$st->setFetchMode(PDO::FETCH_OBJ);
		
		$return_codes = array();
		
		foreach ($st as $row)
		{
			$return_codes[$row->id] = array(
				'return_code' => $row->return_code,
				'is_fatal' => $row->is_fatal,
				'return_description' => $row->return_description
			);
		}
	}
	
	return $return_codes;
}

?>
