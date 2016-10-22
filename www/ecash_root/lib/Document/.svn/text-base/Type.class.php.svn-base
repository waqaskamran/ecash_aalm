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

class eCash_Document_Type {

	protected static $template_names;
	
	static public function Get_Document_List(Server $server, $addtl_where = NULL, $addtl_sort = "send", $require_active = TRUE)
	{
		$db = ECash::getMasterDb();
		$document_list = array();

		$addtl_sql = ($require_active === true) ? " AND l.active_status = 'active'" : "";
		$addtl_sql .= ($addtl_where) ? $addtl_where : "";

		switch ($addtl_sort) 
		{
			case "send":
				$addtl_sort = " l.doc_send_order, ";
				break;

			case "receive":
				$addtl_sort = " l.doc_receive_order, ";
				break;

			default:
				$addtl_sort .= ($addtl_sort) ? ", " : "" ;
		}

		$query = "
 			SELECT
				l.document_list_id,
				l.name_short as name,
				l.name as description,
				l.active_status as active,
				l.required,
				l.send_method,
				l.document_api,
				l.esig_capable,
				l.only_receivable,
				l.doc_send_order,
				l.doc_receive_order,
				IFNULL(b.document_list_body_id, l.document_list_id) as body_id,
				IFNULL(b.description, l.name) as body_name,
				IFNULL(b.send_method, 'any') as body_send_method,
				CASE 
					WHEN b.document_list_body_id is not null THEN
						l.document_list_id 
					WHEN p.document_attachment_id is not null THEN
						p.document_attachment_id 
				END as part_id,
				CASE 
					WHEN b.name is not null THEN
						l.name 
					WHEN p.document_attachment_name is not null THEN
						p.document_attachment_name 
				END as part_name,
				p.document_package_name,
				p.document_package_id
			FROM
				document_list l 
				LEFT JOIN (
					SELECT 
						p.document_package_id,
						p.name as document_package_name,
						p.name_short as document_package_name_short,
						p.document_list_id ,
						p.active_status as document_package_active_status,
						pl.document_list_id as document_attachment_id,
						l.name as document_attachment_name,
						l.name_short as document_attachment_name_short,
						l.active_status as document_attachment_active_status,
						l.required as document_attachment_required,
						l.esig_capable as document_attachment_esig_capable,
						l.document_api as document_attachment_document_api,
						l.send_method as document_attachment_send_method,
						l.system_id as document_attachment_system_id,
						l.company_id as document_attachment_company_id
					FROM
						document_list l, 
						document_list_package pl, 
						document_package p 
					WHERE
						pl.document_list_id = l.document_list_id and 
						pl.document_package_id = p.document_package_id
				) as p ON ( l.document_list_id = p.document_list_id )
				LEFT JOIN (
					SELECT
						l2.name as description,
						l2.name_short as name,
						l2.company_id,
						b2.document_list_body_id,
						b2.document_list_id,
					b2.send_method
					FROM
						document_list_body b2,
						document_list l2
					WHERE
						document_list_body_id = l2.document_list_id
				) as b ON (l.document_list_id = b.document_list_id AND
					l.company_id = b.company_id ) 
			WHERE
				l.company_id = {$server->company_id} AND
				l.system_id = {$server->system_id} 
				{$addtl_sql}
			ORDER BY
				{$addtl_sort}
				l.name_short
		";
		$st = $db->query($query);
		$trow = array();
		$use_condor = FALSE;
		
		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$trow[] = $row;
			if(strtolower($row->document_api) == 'condor') $use_condor = true;
		}

		$condor_list = array();
		
		if(isset($use_condor) && $use_condor === true) 
		{
			require_once eCash_Document_DIR . "/DeliveryAPI/Condor.class.php";
			try 
			{
				if (is_null(self::$template_names))
				{
					$condor_list = eCash_Document_DeliveryAPI_Condor::Prpc()->Get_Template_Names();
					self::$template_names = $condor_list;
				}
				else
				{
					$condor_list = self::$template_names;
				}
			}
			catch (Exception $e)
			{
				eCash_Document::Log()->Write("Exception: Unable to fetch Condor List: " . $e->getMessage());
			}
		}

		$esig_body = ECash::getConfig()->DOCUMENT_DEFAULT_ESIG_BODY;
		$fax_cover = ECash::getConfig()->DOCUMENT_DEFAULT_FAX_COVERSHEET;
		
		foreach ($trow as $row) 
		{
			if(isset($row->document_package_id) && $row->document_package_id) 
			{
				$lkey = $row->document_package_id;
			} 
			else 
			{
				$lkey = $row->document_list_id;
			}
				
			if($row->name != 'other'
				&& is_array($condor_list)
				&& count($condor_list) > 0
				&& strtolower($row->document_api) == "condor"
				&& !in_array($row->description,$condor_list)
				&& isset($row->part_name)
				&& !in_array($row->part_name,$condor_list))
			{

				//eCash_Document::Log()->Write("Skipping: " . var_export($row,true));
				continue;
			}

			if(!isset($document_list[$lkey])) 
			{
				$document_list[$lkey] = $row;
				$document_list[$lkey]->bodyparts = array();
				$document_list[$lkey]->esig_body_name = $esig_body;
				$document_list[$lkey]->fax_body_name = $fax_cover;
			}

			switch ($row->body_send_method) 
			{
				case "esig":
					$document_list[$lkey]->esig_body_id = $row->body_id;
					$document_list[$lkey]->esig_body_name = $row->body_name;
					break;
						
				case "fax":
					$document_list[$lkey]->fax_body_id = $row->body_id;
					$document_list[$lkey]->fax_body_name = $row->body_name;
					break;
						
				case "email":
				case "any":
				default:
					$document_list[$lkey]->email_body_id = $row->body_id;
					$document_list[$lkey]->email_body_name = $row->body_name;
					break;
			}
				
			// remove the body_send_method for confusion's sake
			unset($document_list[$lkey]->body_send_method);
			
			if($document_list[$lkey] && $row->part_name) 
			{
				$document_list[$lkey]->bodyparts[$row->part_id] = $row->part_name;
			}
		}
		return $document_list;
	}

	static public function Get_Raw_Document_List(Server $server, $addtl_where = NULL)
	{
		$db = ECash::getMasterDb();
		
		$query = "
			select
				l.*,
				b.document_list_body_id,
				b.body_name,
				b.body_name_short,
				b.send_method as body_method
			from 
			  document_list l 
			left join
			  (
			  	select 
			  		b2.*,
			  		l2.name as body_name,
			  		l2.name_short as body_name_short
			  	from document_list_body b2
						join document_list l2 on (b2.document_list_body_id = l2.document_list_id)
				) as b on (l.document_list_id = b.document_list_id)
			where 
				l.company_id = ? AND
				l.system_id = ? 				
		";
		$st = $db->queryPrepared($query, array($server->company_id, $server->system_id));

		$trow = array();
		
		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$method = $row->body_method;
			
			if (isset($trow[$row->document_list_id]))
			{
				$row = $trow[$row->document_list_id];
			}
				
			switch ($method)
			{
				case "fax":
					$row->fax_body_id = $row->document_list_body_id;
					$row->fax_body_name = $row->body_name;
					$row->fax_body_name_short = $row->body_name_short;
					break;
						
				case "esig":
					$row->esig_body_id = $row->document_list_body_id;
					$row->esig_body_name = $row->body_name;
					$row->esig_body_name_short = $row->body_name_short;
					break;
						
				case "email":
					$row->alt_body_id = $row->document_list_body_id;
					$row->alt_body_name = $row->body_name;
					$row->alt_body_name_short = $row->body_name_short;
					break;
			}
			
			unset($row->document_list_body_id);
			unset($row->body_method);
			unset($row->body_name);
			unset($row->body_name_short);
			
			$trow[$row->document_list_id] = $row;
		}
		
		return $trow;
	}

	static public function Get_Raw_Package_List(Server $server, $addtl_where = NULL)
	{
		$db = ECash::getMasterDb();
		
		$query = "
			select
		    pk.*,
		    pl.document_list_id as attachment_id,
		    l2.name as attachment_name,
		    l2.name_short as attachment_name_short
			from
		    (
		    	select
		      	p.*,
		        l.system_id,
		        l.name as body_name,
		        l.name_short as body_name_short
	        from document_list l
	        join document_package p on (p.document_list_id = l.document_list_id)
	      ) as pk 
		    left join document_list_package pl on (pl.document_package_id = pk.document_package_id)
				left join document_list l2 on (pl.document_list_id = l2.document_list_id)
			where
				pk.company_id = ? AND
				pk.system_id = ?
		";
		$st = $db->queryPrepared($query, array(ECash::getCompany()->company_id, ECash::getSystemId()));

		$trow = array();
	
		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			if (!isset($trow[$row->document_package_id]))
			{
				$trow[$row->document_package_id] = $row;
			}
			

			if(empty($trow[$row->document_package_id]->attachments) || !is_array($trow[$row->document_package_id]->attachments)) 
			{
				$trow[$row->document_package_id]->attachments = array();
			}
			

			if($row->attachment_id) 
			{
				$trow[$row->document_package_id]->attachments[$row->attachment_id] = new StdClass();
				$trow[$row->document_package_id]->attachments[$row->attachment_id]->attachment_name = $row->attachment_name;
				$trow[$row->document_package_id]->attachments[$row->attachment_id]->attachment_id = $row->attachment_id;
				$trow[$row->document_package_id]->attachments[$row->attachment_id]->attachment_name_short = $row->attachment_name_short;
			}
			
			unset($trow[$row->document_package_id]->attachment_name);
			unset($trow[$row->document_package_id]->attachment_id);
			unset($trow[$row->document_package_id]->attachment_name_short);
		}
		
		return $trow;
	}

}

?>
