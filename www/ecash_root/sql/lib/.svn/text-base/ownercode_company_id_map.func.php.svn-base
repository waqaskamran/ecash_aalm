<?php

/**
 * Returns a mapping of all Owner Codes to Company IDs for use in the RDM
 * Return parsing / Recording
 *
 * @param DB_Database_1
 * @return array
 */
function ownercode_company_id_map(DB_Database_1 $db)
{
    $query = "
        SELECT company_id, value as owner_code FROM company_property
        	WHERE property='QC_OWNER_CODE'";

    $map = array();
    $result = $db->query($query);
    while ($row = $result->fetch(PDO::FETCH_OBJ))
	{
		$map[$row->owner_code] = $row->company_id;
	}

    return $map;
}

function reverse_ownercode_company_id_map(DB_Database_1 $db) {
    $query = "
        SELECT company_id, value as owner_code FROM company_property
        	WHERE property='QC_OWNER_CODE'";

    $map = array();
    $result = $db->query($query);
    while ($row = $result->fetch(PDO::FETCH_OBJ))
	{
		$map[$row->company_id] = $row->owner_code;
	}

    return $map;
}

?>
