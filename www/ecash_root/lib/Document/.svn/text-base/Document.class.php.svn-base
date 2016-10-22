<?php
/**
 * Document
 * Document management module for ecash
 *
 * @package Documents
 * @category Document_Management
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 * @created Sep 13, 2006
 *
 * @version $Revision$
 */

require_once SQL_LIB_DIR ."/application.func.php";

define("eCash_Document_DIR", dirname(realpath(__FILE__)).'/');

// require_once eCash_Document_DIR . "/XMLFormat.class.php";

class eCash_Document {

	private $server;
	private $request;

	static private $log_context;
	
	static private $instance = array();

	static public $package_list_fields = array('name','name_short','document_list_id','active_status');
	static public $document_list_fields = array('name', 'name_short', 'active_status', 'required', 'esig_capable','send_method','document_api','only_receivable');
	
	static public $message;
	
	static public function Factory(Server $server, $request)
	{
		return new eCash_Document($server, $request);
	}

	/**
	 * returns a singleton
	 *
	 * @param Server $server
	 * @param unknown_type $request
	 * @return eCash_Document
	 */
	static public function Singleton(Server $server, $request)
	{
		$key = md5(serialize(array($server,$request)));

		if(!isset(self::$instance[$key]) || !(self::$instance[$key] instanceof eCash_Document)) 
		{
			self::$instance[$key] = self::Factory($server, $request);
		}

		return self::$instance[$key];

	}

	public function __construct(Server $server, $request)
	{

		$this->server = $server;
		$this->transport = ECash::getTransport();
		$this->request = $request;

	}

	static public function Get_Document_List(Server $server, $type = NULL,  $addtl_where = NULL, $require_active = TRUE)
	{
		switch(strtolower($type)) 
		{
			case "package-display":
				require_once eCash_Document_DIR . "/Type/Packaged.class.php";
				return eCash_Document_Type_Packaged::Get_Display_List($server,  $addtl_where, $require_active);
				break;

			case "packaged":
				require_once eCash_Document_DIR . "/Type/Packaged.class.php";
				return eCash_Document_Type_Packaged::Get_Document_List($server,  $addtl_where, NULL, $require_active);
				break;

			case "receive":
				require_once eCash_Document_DIR . "/Type/Receive.class.php";
				return eCash_Document_Type_Receive::Get_Document_List($server,  $addtl_where, 'receive', $require_active);
				break;

			case "send":
				require_once eCash_Document_DIR . "/Type/Send.class.php";
				return eCash_Document_Type_Send::Get_Document_List($server,  $addtl_where, 'send', $require_active);
				break;

			case "esig":
				require_once eCash_Document_DIR . "/Type/Esig.class.php";
				return eCash_Document_Type_Esig::Get_Document_List($server,  $addtl_where, NULL, $require_active);
				break;

			default:
				require_once eCash_Document_DIR . "/Type/Send.class.php";
				return eCash_Document_Type::Get_Document_List($server, $addtl_where, NULL, $require_active);
				break;

		}

	}

	/**
	 * replaces document_query::Fetch_Application_Docs
	 */
	static public function Get_Application_History(Server $server, $application_id, $event = NULL)
	{
		require_once eCash_Document_DIR . "/ApplicationData.class.php";

		return eCash_Document_ApplicationData::Get_History($server, $application_id, $event);

	}


	public function Get_Document_Id($doc_name, $require_active = FALSE)
	{
		$record  = $this->Get_Documents_By_Name($doc_name, $require_active);

		return ($record && !is_array($record)) ? $record->document_list_id : FALSE ;

	}

	public function Get_Documents_By_Name ($doc_name, $require_active = FALSE)
	{
		
		if(is_array($doc_name)) 
		{
			foreach($doc_name as $t) 
			{
				if (is_numeric(($t))) 
				{
					return $this->Get_Documents($doc_name, $require_active);
				}
			}
		} 
		elseif(is_numeric($doc_name)) 
		{
			return $this->Get_Documents($doc_name, $require_active);
		}

		
		$sql_piece = is_array($doc_name) ? " AND l.name IN ('" . implode("','",$doc_name) . "')" : " AND l.name = '{$doc_name}'" ; //"

		$document_list = self::Get_Document_List($this->server, NULL, $sql_piece, $require_active);
		
		if(!is_array($doc_name) && isset($document_list) && is_array($document_list)) 
		{
			return array_shift($document_list);
		} 
		elseif (!isset($document_list)) 
		{
			return false;
		} 
		else 
		{
			return $document_list;
		}

	}

	public function Get_Documents($id, $require_active = FALSE)
	{

		if(is_array($id)) 
		{
			foreach($id as $t) 
			{
				if (!is_numeric(($t))) 
				{
					return $this->Get_Documents_By_Name($id, $require_active);
				}
			}
		} 
		elseif(!is_numeric($id)) 
		{
			return $this->Get_Documents_By_Name($id, $require_active);
		}

		$sql_piece = is_array($id) ? " AND l.document_list_id IN (" . implode(",",$id) . ")" : " AND l.document_list_id = {$id}" ; //"

		$document_list = self::Get_Document_List($this->server, NULL, $sql_piece, $require_active);

		if(!is_array($id) && is_array($document_list)) 
		{
			return array_shift($document_list);
		} 
		elseif (!$document_list) 
		{
			return false;
		} 
		else 
		{
			return $document_list;
		}

	}


	public function Receive_Document($request)
	{

		$docs = array_keys($request->document_list);
		$document_list = $this->Get_Documents($docs);

		if(!is_array($document_list)) $document_list = array($document_list);

		$tcondor = array();
		$tcopia = array();
		foreach($document_list as $doc) 
		{
			switch (strtolower($doc->document_api)) 
			{
				case "condor":
					$tcondor[] = $doc;
					break;
					
				case "copia":
					$tcopia[] = $doc;
			}
		}

		if(count($tcondor)) 
		{
			require_once eCash_Document_DIR . "/DeliveryAPI/Condor.class.php";
			$success = eCash_Document_DeliveryAPI_Condor::Receive($this->server, $tcondor, $request);
		}

		if(count($tcopia)) 
		{
			require_once eCash_Document_DIR . "/DeliveryAPI/Copia.class.php";
			$success = eCash_Document_DeliveryAPI_Copia::Receive($this->server, $tcopia, $request);
		}
		

		if ($success) 
		{
			foreach($document_list as $doc ) 
			{
				// Calls the CFE event, don't know why I have to get the application at this point, but I do.
				$app    = ECash::getApplicationById($_SESSION['current_app']->application_id);
				$engine = ECash::getEngine();
				$engine->executeEvent('DOCUMENT_RECEIVED', array($doc->document_list_id));
				
				if(($process_status_id = self::Get_Status_Trigger($this->server, $_SESSION['current_app']->application_id, $doc->document_list_id)) !== FALSE) 
				{
					Update_Status($this->server, $request->application_id, intval($process_status_id), null, null, TRUE, "Verification (react)");
				}
			}
		}
		
		$_SESSION['current_app']->docs = self::Get_Application_History($this->server, $_SESSION['current_app']->application_id);
		ECash::getTransport()->Set_Data($_SESSION['current_app']);
		ECash::getTransport()->Add_Levels('overview','receive_documents','edit','documents','view');

	}


	public function Update_List_Sort($doc_array, $which = 'send')
	{
		if (!is_array($doc_array)) 
		{
			$doc_array = array($doc_array);
		}
		
		$field = ($which == 'receive') ? 'doc_receive_order' : 'doc_send_order';
		
		if (!ctype_digit((string) implode("",array_keys($doc_array))) || !ctype_digit((string) implode("",$doc_array)) ) 
		{
			throw new InvalidArgumentException(__METHOD__ . " Error: list must be a numerically indexed array of document_list_ids");
		}
		try 
		{
		
			foreach ($doc_array as $key => $did) 
			{
				$doc_query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					UPDATE
						document_list
					SET
						{$field} = {$key}
					WHERE
						document_list_id = {$did}
						";
				$db = ECash::getMasterDb();
				$db->exec($doc_query);
			}
		} 
		catch (Exception $e) 
		{
			get_log('main')->Write("There was an error sorting document list.");
			throw $e;
		}
	}
	
	public function Update_Document_Package($doc)
	{
		if(!is_object($doc)) $doc = (object) $doc;
		
		if(!isset($doc->document_package_id) || !$doc->document_package_id) 
		{
			return $this->New_Document_Package($doc);
		}
		
		$values = array_intersect(array_keys((array) $doc), self::$package_list_fields);

		$db = ECash::getMasterDb();
		
		$update = array();
		foreach($values as $field ) {
			$update[] = "{$field} = " . $db->quote($doc->$field);
		}

		try 
		{
			if (is_array($doc->attachments) && count($doc->attachments)) 
			{
							
				$atch_chk_query = "
					SELECT
						document_list_id
					FROM
						document_list_package
					WHERE
						document_package_id = {$doc->document_package_id}
					";
				
				$doc_id_ary = $db->querySingleColumn($atch_chk_query);
			
				$removes = array_diff($doc_id_ary, $doc->attachments);
				$additions = array_diff($doc->attachments, $doc_id_ary);
			
				if (count($removes)) 
				{
					$del_query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
						DELETE FROM document_list_package
						WHERE
							document_package_id = {$doc->document_package_id}
						AND
							document_list_id IN (" . implode(",", $removes) . ")
					";
					
					$db->exec($del_query);
						
				}
					
				if (count($additions)) 
				{
					$lines = array();
					foreach($additions as $doc_id) 
					{
						$lines[] = "(now(),now(), {$this->server->company_id}, {$doc->document_package_id}, {$doc_id})";
					}
					
					$doc_query = "
						INSERT INTO document_list_package
							(date_modified, date_created, company_id, document_package_id, document_list_id)
						VALUES
							" . implode(",\n",$lines);
					$db->exec($doc_query);
					
				}
			} 
			else 
			{
				$del_query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					DELETE FROM document_list_package
					WHERE
						document_package_id = {$doc->document_package_id}
				";
				$db->exec($del_query);
				
			}
			
			if (count($update)) 
			{
				
				$doc_query = "
					UPDATE
						document_package
					SET
						" . implode(", ", $update) . "
					WHERE
						document_package_id = {$doc->document_package_id}
				";
				$db->exec($doc_query);
			
			}				
		} 
		catch (Exception $e) 
		{
			throw $e;
		}	
		
	}
	
	public function New_Document_Package($doc)
	{
		if(!is_object($doc)) $doc = (object) $doc;
		
		if(isset($doc->document_package_id) && $doc->document_package_id) 
		{
			return $this->Update_Document_Package($doc);
		}
		
		$missing = array_diff(self::$package_list_fields, array_keys((array) $doc));
		
		if (count($missing)) 
		{
			throw new InvalidArgumentException(__METHOD__ . " Error: the required values are missing: " . implode(", ", $missing));
		}
		
		$db = ECash::getMasterDb();
		
		try 
		{
			$insert = array();
			foreach(self::$package_list_fields as $field) 
			{
				if (!$doc->$field) 
				{
					throw new OutOfRangeException(__METHOD__ . " Error: Required value is missing for field {$field}");
				}
				
				$insert[] = $db->quote($doc->$field);
				
			}
				
			$doc_query = "
				INSERT INTO	document_package
					(date_modified, date_created, company_id, " . implode (",", self::$package_list_fields) . ")
				VALUES
					(now(), now(), {$this->server->company_id}, " . implode(", ", $insert) . ")
			";

			$db->exec($doc_query);		
			$doc->document_package_id = $db->lastInsertId();
			
			if(!$doc->document_package_id) 
			{
				throw new RuntimeException(__METHOD__ . " Error: Unknown MySQL error. No value returned for document_package insert.");
			}
			
			if (is_array($doc->attachments) && count($doc->attachments)) 
			{
							
				$lines = array();

				foreach($doc->attachments as $doc_id) 
				{
					$lines[] = "(now(),now(), {$this->server->company_id}, {$doc->document_package_id}, {$doc_id})";
				}
					
				$doc_query = "
					INSERT INTO document_list_package
						(date_modified, date_created, company_id, document_package_id, document_list_id)
					VALUES
						" . implode(",\n",$lines);
				$db->exec($doc_query);
					
			}
		} 
		catch (Exception $e) 
		{
			throw $e;
		}	
		
		
	}
	
	public function Delete_Document_Package($doc)
	{
		if(!is_numeric($doc)) 
		{
			if(is_object($doc))  
			{
				$doc = $doc->document_package_id;
			} 
			elseif (is_array($doc)) 
			{
				$doc = $doc['document_package_id'];
			} 
			else 
			{
				throw new InvalidArgumentException(__METHOD__ . " Error: {$doc} is an invalid package or package id");
			}
		} 
		$db = ECash::getMasterDb();
		
		try 
		{
			
			$doc_query = "
				DELETE FROM
					document_package
				WHERE
					document_package_id = {$doc}
			";
			
			$db->exec($doc_query);

			$doc_query = "
				DELETE FROM
					document_list_package
				WHERE
					document_package_id = {$doc}
			";
			$db->exec($doc_query);
		} 
		catch (Exception $e) 
		{
			throw $e;
		}
		
		
	}
	
	public function Update_List_Document($doc)
	{
		if(!is_object($doc)) $doc = (object) $doc;
		
		if(!isset($doc->document_list_id) || !$doc->document_list_id) 
		{
			return $this->New_List_Document($doc);
		}
				$doc_obj = new stdclass();
		foreach($doc as $key => $value)
		{
			$doc_obj->$key = $value;
		}
		$values = array_intersect(array_keys((array) $doc_obj), self::$document_list_fields);
		$db = ECash::getMasterDb();

		$update = array();
		foreach($values as $field ) 
		{
			if($field == "send_method") 
			{
				$doc->$field = implode(",",$doc->$field);
			}
			$update[] = "{$field} = " . $db->quote($doc->$field);
		}

		try 
		{
			
			
			$doc_query = "
				SELECT
					document_list_body_id,
					send_method
				FROM
					document_list_body
				WHERE
					document_list_id = {$doc->document_list_id}
			";
						
			$current_bodies = array();
			$res = $db->query($doc_query);
			while($row = $res->fetch(PDO::FETCH_OBJ))
			{
				$current_bodies[$row->send_method] = $row->document_list_body_id;
			}
			
			$new_bodies['esig'] = (is_numeric($doc->esig_body)) ? $doc->esig_body : NULL;
			$new_bodies['email'] = (is_numeric($doc->email_body)) ? $doc->email_body : NULL;
			$new_bodies['fax'] = (is_numeric($doc->fax_body)) ? $doc->fax_body : NULL;

			$company_id = ECash::getCompany()->company_id;
			foreach(array_keys($new_bodies) as $mode) 
			{
				$bod_query = NULL;
				
				switch (TRUE) 
				{
					case (isset($new_bodies[$mode]) && isset($current_bodies[$mode]) && $new_bodies[$mode] != $current_bodies[$mode]) :
						$bod_query = "
							UPDATE
								document_list_body
							SET
								document_list_body_id  = {$new_bodies[$mode]}
							WHERE
								document_list_id = {$doc->document_list_id} AND
								send_method = '{$mode}'
						";
						break;
						
					case (isset($new_bodies[$mode]) && !isset($current_bodies[$mode])) :
						$bod_query = "
							INSERT INTO 
								document_list_body
								(date_modified, date_created, company_id, document_list_id, document_list_body_id, send_method)
							VALUES
								(now(), now(), {$company_id}, {$doc->document_list_id}, {$new_bodies[$mode]}, '{$mode}')
						";
						break;						
						
					case (!isset($new_bodies[$mode]) && isset($current_bodies[$mode])) :
						$bod_query = "
							DELETE FROM
								document_list_body
							WHERE
								document_list_id = {$doc->document_list_id} AND
								send_method = '{$mode}'
						";
						break;
						
				}

				if ($bod_query) 
				{
					$db->exec($bod_query);
				}
				
			}
			
			if (count($update)) 
			{
				
				$doc_query = "
					UPDATE
						document_list
					SET
						" . implode(", ", $update) . "
					WHERE
						document_list_id = {$doc->document_list_id}
				";
			
				$db->exec($doc_query);
			
			}			
		} 
		catch (Exception $e) 
		{
			throw $e;
		}		
			
	}
	
	public function New_List_Document($doc)
	{
		if(!is_object($doc)) $doc = (object) $doc;
		
		if(isset($doc->document_list_id) && $doc->document_list_id) 
		{
			return $this->Update_List_Document($doc);
		}
		$doc_obj = new stdclass();
		foreach($doc as $key => $value)
		{
			$doc_obj->$key = $value;
		}
		$missing = array_diff(self::$document_list_fields, array_keys((array) $doc_obj));
		
		if (count($missing)) 
		{
			throw new InvalidArgumentException(__METHOD__ . " Error: the required values are missing: " . implode(", ", $missing));
		}
		
		$db = ECash::getMasterDb();
		$company_id = ECash::getCompany()->company_id;
		try 
		{
			$insert = array();
			foreach(self::$document_list_fields as $field) 
			{
				if (!$doc->$field) 
				{
					throw new OutOfRangeException(__METHOD__ . " Error: Required value is missing for field {$field}");
				}
				
				if($field == "send_method") 
				{
					$doc->$field = implode(",",$doc->$field);
				}
				
				$insert[] = $db->quote($doc->$field);			
			}
				
			$doc_query = "
				INSERT INTO
					document_list
					(date_modified, date_created, company_id, system_id, " . implode (",", self::$document_list_fields) . ")
				VALUES
					(now(), now(), {$company_id}, 3, " . implode(", ", $insert) . ")
			";
			
			$db->exec($doc_query);		
			$doc->document_list_id = $db->lastInsertId();
			
			if(!$doc->document_list_id) 
			{
				throw new RuntimeException(__METHOD__ . " Error: Unknown MySQL error. No value returned for document_list insert.");
			}
						
			$new_bodies['esig'] = (is_numeric($doc->esig_body)) ? $doc->esig_body : NULL;
			$new_bodies['email'] = (is_numeric($doc->email_body)) ? $doc->email_body : NULL;
			$new_bodies['fax'] = (is_numeric($doc->fax_body)) ? $doc->fax_body : NULL;
			
			$values = array();
			foreach(array_keys($new_bodies) as $mode) 
			{
				if (isset($new_bodies[$mode])) 
				{
					$values[] = "(now(), now(), {$company_id}, {$doc->document_list_id}, {$new_bodies[$mode]}, '{$mode}')";
				}				
			}
			
			if(count($values)) 
			{
				$bod_query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					INSERT INTO 
						document_list_body
						(date_modified, date_created, company_id, document_list_id, document_list_body_id, send_method)
					VALUES
						" . implode(",\n",$values);
				$db->exec($bod_query);
			}
			
		} 
		catch (Exception $e) 
		{
			throw $e;
		}		
	}
	
	static public function Log_Document(Server $server, $document, $result)
	{
//		$map[''] = () ? : ;
		$map['document_method'] 	= isset($result['method']) 				? "" . $result['method'] . "" 				: NULL ;
		$map['sent_to'] 			= isset($result['destination']['destination'])	? "" . $result['destination']['destination'] . "" : NULL ;
		$map['name_other'] 			= isset($result['destination']['name']) 	? "" . $result['destination']['name'] . "" 	: NULL ;
		$map['signature_status'] 	= isset($result['signature_status']) 	? "" . $result['signature_status'] . "" 	: NULL ;
		$map['document_id_ext'] 	= isset($result['document_id_ext']) 		? "" . $result['document_id_ext'] . "" 		: NULL ;
		$map['document_event_type'] = isset($result['document_event_type']) 	? "" . $result['document_event_type'] . ""  : "'sent'" ;
		$map['archive_id'] 			= isset($result['archive_id']) 			? $result['archive_id'] : NULL ;

		$document_model = self::getModel();
		$document_model->date_created = time();
		$document_model->date_modified = time();
		$document_model->application_id = $result['application_id'];
		$document_model->company_id = ECash::getCompany()->company_id;
		$document_model->document_list_id = $document->document_list_id;
		$document_model->transport_method = strtolower($document->document_api);
		$document_model->agent_id = ECash::getAgent()->getAgentId();
		
		foreach($map as $field => $rawval) 
		{
			if($rawval != NULL) 
			{
				$document_model->{$field} = $rawval;
			}
		}

		$document_model->save();
		return $document_model->document_id;
	}

	
	static public function Delete_Archive_Document(Server $server, $document_id)
	{
		if(!isset($document_id) || !is_numeric($document_id)) 
		{
			throw new Exception ("Document ID must be valid.");
		}
		
		$document = self::getModel();
		$found = $document->loadBy(array("document_id" => $document_id));
		if (!$found)
		{
			throw new Exception ("Document ID could not be loaded.");
		}
		$document->delete();
		
	}
	
	static public function Change_Document_Archive_ID(Server $server, $document_id, $archive_id)
	{
		$document = self::getModel();
		$document->loadBy(array("document_id"  => $document_id));
		$document->archive_id = $archive_id;
		$document->save();
	}

	static public function Get_Status_Trigger(Server $server, $application_id, $document_list_id)
	{
		require_once(SQL_LIB_DIR . "loan_actions.func.php");
		require_once(SQL_LIB_DIR."/application.func.php");
		$app = ECash::getApplicationById($application_id);
		$document_process_model = ECash::getFactory()->getModel('DocumentProcess');
		if($document_process_model->loadBy(array('current_application_status_id' => $app->application_status_id, 'document_list_id' => $document_list_id)))
		{
			//This is crap, but we need to do some special processing for react apps
			$status = $app->getStatus()->toName();
			if($status['status_chain'] == 'pending::prospect::*root' && $app->is_react == 'yes')
			{
				if(count(Get_Loan_Actions($application_id)) > 0)
				{
					return Status_Utility::Get_Status_ID_By_Chain('queued::verification::applicant::*root');
				}
				else
				{
					return 	$document_process_model->application_status_id;
				}
			}
			return 	$document_process_model->application_status_id;		
		}
		else
		{
			return FALSE;
		}
	
		
	}
	
	/**
	 * will return an array of arrays of status triggers
	 *
	 * @return array[current_application_status_id][application_status_id][document_list_id] = true
	 */
	static public function Get_All_Status_Triggers($company_id)
	{
		$db = ECash::getMasterDb();
		
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT dp.*
					FROM
						document_process AS dp
					INNER JOIN document_list AS dl ON dp.document_list_id=dl.document_list_id
					WHERE dl.company_id={$company_id}
					";
		
		$q_obj = $db->Query($query);

		$retval = array();
		while(  $row = $q_obj->fetch(PDO::FETCH_OBJ) ) 
		{
			if(!is_array($retval[$row->current_application_status_id]))
			{
				$retval[$row->current_application_status_id] = array();
				$retval[$row->current_application_status_id][$row->application_status_id] = array();
			} elseif(!is_array($retval[$row->current_application_status_id][$row->application_status_id])) {
				$retval[$row->current_application_status_id][$row->application_status_id] = array();
			}
			$retval[$row->current_application_status_id][$row->application_status_id][$row->document_list_id] = true;
		}
		return $retval;
	}
	
	static public function Log()
	{
		return ECash::getLog('documents');	
	}
	
	static protected function getModel()
	{
		return ECash::getFactory()->getModel("Document");
	}
}

?>
