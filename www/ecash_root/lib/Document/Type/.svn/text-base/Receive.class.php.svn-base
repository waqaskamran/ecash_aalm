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

class eCash_Document_Type_Receive extends eCash_Document_Type {

	static public function Get_Document_List(Server $server, $addtl_where = NULL, $addtl_sort = 'receive', $require_active = TRUE)
	{
		$doc_list = array();
		unset($_SESSION['reqd_document_list']);
		if(empty($_SESSION['reqd_document_list']))
		{
			$_SESSION['reqd_document_list'] = parent::Get_Document_List($server," and required = 'yes' " . $addtl_where, $addtl_sort, $require_active);
		}


		$reqd_docs = $_SESSION['reqd_document_list'];
		foreach($reqd_docs as $doc)
		{
			if(empty($doc_list[$doc->document_list_id]))
			{
				$doc_list[$doc->document_list_id] = $doc;
			}
		}

		if ($server->company_id < 100)
		{
			$other = current($t = parent::Get_Document_List($server," and name_short = 'other' AND active_status = 'active' " . $addtl_where, $addtl_sort, TRUE ));

			if (isset($other->name) && isset($other->description) && isset($other->required)
					&& isset($other->document_list_id))
			{
				$doc_list[$other->document_list_id] = $other;
			}
		}

		return $doc_list;

	}
}

?>
