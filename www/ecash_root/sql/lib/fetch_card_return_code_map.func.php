<?php

function Fetch_Card_Return_Code_Map()
{
	static $return_codes;
	
	if (empty($return_codes))
	{
		$db = ECash::getMasterDb();
		
		$sql = "
			SELECT
				reason_code as id,
				reason_text as return_code,
				fatal_fail as is_fatal
			FROM	card_process_response
		";
		$st = $db->query($sql);
		$st->setFetchMode(PDO::FETCH_OBJ);
		
		$return_codes = array();
		
		foreach ($st as $row)
		{
			$return_codes[$row->id] = array(
				'return_code' => $row->return_code,
				'is_fatal' => $row->is_fatal
			);
		}
	}
	
	return $return_codes;
}

?>
