<?php
/**
 * @package Documents
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 * @created Sep 18, 2006
 *
 * @version $Revision$
 */

class eCash_Document_Type_Esig extends eCash_Document_Type {

	static public function Get_Document_List(Server $server, $addtl_where = NULL, $addtl_sort = 'send', $require_active = TRUE)
	{
		// Gets all, active & inactive
		$document_list = parent::Get_Document_List($server, $addtl_where .  " AND esig_capable = 'yes'", $addtl_sort, $require_active);
		$doc_list = array();
		foreach($document_list as $doc)
		{
			$document_name = strtolower($doc->description);
			if($doc->name != 'other') // other cannot be a sent document
			{
				$doc_list[$doc->document_list_id] = $doc;
			}
		}

		return $doc_list;

	}
}

?>
