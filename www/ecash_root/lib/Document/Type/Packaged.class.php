<?php
/**
 * @package Documents
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 * @created Sep 13, 2006
 *
 * @version $Revision$
 */

require_once eCash_Document_DIR . "/Type.class.php";

class eCash_Document_Type_Packaged extends eCash_Document_Type {

	static public function Get_Document_List(Server $server, $addtl_where = NULL, $addtl_sort = 'send', $require_active = TRUE)
	{
		return parent::Get_Document_List($server, $addtl_where .  " AND document_package_active_status = 'active' AND document_package_id is not null", $addtl_sort, FALSE);
	}
	
	static public function Get_Display_List(Server $server, $addtl_where = NULL, $require_active = TRUE)
	{
		$db = ECash::getMasterDb();
		$company_id = ECash::getCompany()->company_id;
		$document_list = array();
		$query = "
				SELECT
						dp.document_package_id,
						dp.name as parent_desc,
						dlp.document_list_id as child_id,
						dl.name as child_name,
						dp.name_short as name_short
				  FROM
				  		document_package as dp,
						document_list_package as dlp,
						document_list as dl
				  WHERE
				  		dp.company_id = {$company_id}
						AND
						dp.document_package_id = dlp.document_package_id
						AND
						dlp.document_list_id = dl.document_list_id
						AND
						dp.active_status = 'active'
				  ORDER BY dp.name"; //"
		$q_obj = $db->query($query);
		while( $row = $q_obj->fetch(PDO::FETCH_OBJ))
		{
			$document_list[count($document_list)] = $row;
		}

		// get parent ids
		reset($document_list);
		$unique_parent_packages = array();
		foreach ($document_list as $key => $value)
		{
			if (!in_array($value->document_package_id, $unique_parent_packages))
			{
				$unique_parent_packages[$value->document_package_id] = array();
			}
		}

		$packaged_document_list = array();
		reset($unique_parent_packages);
		foreach ($unique_parent_packages as $parent_key => $parent_value)
		{
			$increment = 0;
//			$unique_parent_packages[$parent_key]['send_method'] = 'email,fax';
			$unique_parent_packages[$parent_key]['send_method'] = 'email';
			$unique_parent_packages[$parent_key]['sub_packets'] = array();
			reset($document_list);
			foreach($document_list as $doc_key => $doc_value)
			{
				if ($doc_value->document_package_id == $parent_key)
				{
					$increment++;
					$unique_parent_packages[$parent_key]['document_list_id'] = $parent_key;
					$unique_parent_packages[$parent_key]['description'] = $doc_value->parent_desc;
					$unique_parent_packages[$parent_key]['name'] = $doc_value->name_short;
					$unique_parent_packages[$parent_key]['sub_packets']['name' + $increment] = $doc_value->child_name;
				}
			}
		}

		return $unique_parent_packages;
	}

}

?>
