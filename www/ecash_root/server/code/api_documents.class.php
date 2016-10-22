<?php
require_once(SERVER_CODE_DIR . 'module_interface.iface.php');


/**
 * Defines the ajax function for documents admin screens
 */
class API_Documents implements Module_Interface
{
	public function __construct(Server $server, $request, $module_name) 
	{
		$this->request = $request;
		$this->server = $server;
		$this->name = $module_name;
		$this->permissions = array(array('admin'));
	
	}
	public function get_permissions()
	{
		return $this->permissions; 
	}
	public function Main() 
	{
		$input = $this->request->params[0];
		switch ($input->action)
		{
			case 'documents':
				switch($input->function)
				{
					case 'getDocuments':
							$data = $this->getDocuments($input->company_id, $input->loan_type_id);
					break;
					case 'getSorted':
							$data = $this->getSorted($input->company_id, $input->loan_type_id);
					break;
					case 'updateDocument':
						$data = $this->updateDocument($input->document_list_id, $input->params);
					break;
					case 'addDocument':
						$data = $this->addDocument($input->params);
					break;
					case 'deleteDocument':
						$data = $this->deleteDocument($input->document_id);
					break;	
					case 'deletePackage':
						$data = $this->deletePackage($input->package_id);
					break;			
					case 'getCondorList':
						$data = $this->getCondorList();
					break;
					case 'getPackages':
						$data = $this->getPackages($input->company_id, $input->loan_type_id);
					break;
					case 'updatePackage':
						$data = $this->updatePackage($input->document_package_id, $input->params);
					break;
					case 'updateSendSort':
						$data = $this->updateSendSort($input->params);
					break;
					case 'updateReceiveSort':
						$data = $this->updateReceiveSort($input->params);
					break;
					default:
						throw new Exception("Unknown reference function {$input->function}");	
				}
			break;		
			
			default:
				throw new Exception("Unknown action {$input->action}");	
		}
		return $data;
	}
	/*
	*retrieves document list based on company and or loan type
	*
	* @param int $company_id
	* @param int $loan_type_id
	*
	* @return array $documents	
	*/
	protected function getDocuments($company_id, $loan_type_id)
	{
		$document_list = ECash::getFactory()->getModel('DocumentListList');
		
		$document_list->getdocs($company_id, $loan_type_id, array(), 'send', false, false);
		$documents = array();	
		//print_r($document_list);
		//exit();
		foreach($document_list as $document)
		{
			if($document->system_id == ECash::getSystemId())
			{
				$document_body = ECash::getFactory()->getModel('DocumentListBody');
			//	$documentlist = ECash::getFactory()->getReferenceModel('DocumentList');
			//	$documentlist->getBy(array('document_list_id' => $document->document_list_id));
				$can_delete = true;
			//	foreach($documentlist as $associated_doc)
			//	{
			//		$can_delete = false;
			//		break;
			//	}
				$document_body->loadBy(array('document_list_id' => $document->document_list_id));
				$documents[$document->document_list_id] = array('name' => $document->name, 'name_short' => $document->name_short, 
										'required' => $document->required, 'active_status' => $document->active_status,
										'esig_capable' => $document->esig_capable, 'send_method' => $document->send_method, 
										'only_receivable' => $document->only_receivable, 'company_id' => $document->company_id,
										'loan_type_id' => $document->loan_type_id, 'document_body_id' => $document_body->document_list_body_id, 'can_delete' => $can_delete);
			}		
		}	
		return $documents;
	}
	/*
	*retrieves document list based on company and or loan type
	*
	* @param int $company_id
	* @param int $loan_type_id
	*
	* @return array $documents	
	*/
	protected function getSorted($company_id, $loan_type_id)
	{
	 	$receivedocs = ECash::getFactory()->getModel('DocumentListList');
	 	$receivedocs->getRecievable($company_id, $loan_type_id);

	 	$senddocs = ECash::getFactory()->getModel('DocumentListList');
	 	$senddocs->getSendable($company_id, $loan_type_id);

		$documents = array();	
		$documents['receive'] = array();
		foreach($receivedocs as $document)
		{
			$documents['receive'][$document->document_list_id] = array('document_list_id' => $document->document_list_id, 'name_short' => $document->name_short);
		}	
		$documents['send'] = array();
		foreach($senddocs as $document)
		{
			$documents['send'][$document->document_list_id] = array('document_list_id' => $document->document_list_id, 'name_short' => $document->name_short);
		}
		return $documents;
	}
	/*
	*update send sort order
	*
	* @param array $params
	* 
	* @return boolean success	
	*/
	protected function updateSendSort($params)
	{
		
		foreach($params->docs as $order => $doc_id)
		{
			$doc = ECash::getFactory()->getModel('DocumentList');
			$doc->loadBy(array('document_list_id' => $doc_id));
			$doc->doc_send_order = $order;
			$doc->save();
	
		}
		return array('result' => 1);
	}
	/*
	*update received sort order
	*
	* @param array $params
	* 
	* @return boolean success	
	*/
	protected function updateReceiveSort($params)
	{
		foreach($params->docs as $order => $doc_id)
		{
			$doc = ECash::getFactory()->getModel('DocumentList');
			$doc->loadBy(array('document_list_id' => $doc_id));
			$doc->doc_receive_order = $order;
			$doc->save();
	
		}
		return array('result' => 1);
	}
	/*
	*retrieves package list based on company and or loan type
	*
	* @param int $company_id
	* @param int $loan_type_id
	*
	* @return array $Packages	
	*/
	protected function getPackages($company_id, $loan_type_id)
	{
	 	$document_data = ECash::getFactory()->getData('Document');
	 	$package_list = $document_data->get_package_list($company_id, $loan_type_id, false, false);
		$packages = array();
		foreach($package_list as $package_name => $package)
	 	{
	 		$templates = array();
	 		foreach($package as $doc)
		 	{
	 			$templates[$doc->child_id] = array('document_list_id' => $doc->child_id, 'name' => $doc->child_name, 'name_short' => $doc->child_name_short);
		 		$package_id = $doc->document_package_id;
		 		$body_id = $doc->package_body_id;
				$name_short = $doc->name_short;
				$active = $doc->active_status;
				$company_id = $doc->company_id;
				$loan_type_id = $doc->loan_type_id;
		 	}

		 	$packages[$package_id] = array('docs' => $templates, 'name' => $package_name, 'name_short' => $name_short, 'company_id' => $company_id, 'loan_type_id' => $loan_type_id,
							 'document_package_id' => $package_id, 'package_body_id' => $body_id, 'active_status' => $active);
	 		
	 	}
		return $packages;

	}	
	protected function updatePackage($id, $params)
	{
		$package = ECash::getFactory()->getModel('DocumentPackage');
		$package->loadBy(array('document_package_id' => $params->document_package_id));
	
		if($package->document_package_id == null)
		{
			$package->date_created = date('Y-m-d');
			$package->company_id = $params->company_id;
			$package->loan_type_id = $params->loan_type_id;
		}		
		$package->active_status = $params->active_status;
		$package->name_short = urldecode($params->name);
		$package->name = urldecode($params->name);
		$package->document_list_id = $params->package_body_id;
			
		if(!$package->isAltered() || $package->save())
		{

			$listpackagelist = ECash::getFactory()->getModel('DocumentListPackageList');
			$listpackagelist->loadBy(array('document_package_id' => $package->document_package_id));
			foreach($listpackagelist as $listpackagerow)
			{
				$listpackagerow->delete();
			}
			if(empty($params->docs))
				return false;
			foreach($params->docs as $doc)
			{
				$listpackage = ECash::getFactory()->getModel('DocumentListPackage');
				$listpackage->date_created = date('Y-m-d');
				$listpackage->company_id = $params->company_id;
				$listpackage->document_package_id = $package->document_package_id;
				$listpackage->document_list_id = $doc->document_list_id;
				
				$listpackage->save();
			}
			return array('id' => $package->document_package_id, 'name_short' => $package->name_short, 'date_created' => $package->date_created, 'date_modified' => $package->date_modified);
		}
		else
		{
			return false;
		}

	}
	protected function updateDocument($id, $params)
	{
		$document = ECash::getFactory()->getModel('DocumentList');
		$document->loadBy(array('document_list_id' => $id));
		
		if($document->document_list_id == null)
		{
			$document->date_created = date('Y-m-d');
			$document->system_id = 3;
			$document->document_api = 'condor';
			$document->company_id = $params->company_id;
			$document->loan_type_id = $params->loan_type_id;
		}		
		$document->active_status = $params->active_status;
		$document->name_short = urldecode($params->name_short);
		$document->name = empty($params->name) ? $params->name_short : $params->name;
		$document->required = $params->required;
		$document->esig_capable = $params->esig_capable;
		$document->send_method = $params->send_method;
		$document->only_receivable = $params->only_receivable; 
			
		if(!$document->isAltered() || $document->save())
		{
			
			if(!empty($params->document_body_id))
			{
				$document_body = ECash::getFactory()->getModel('DocumentListBody');
				$document_body->loadBy(array('document_list_id' => $id));
				$document_body->delete();
				unset($document_body);
				//deleted and recreating because the primary key is changing and libolution models suck! [RB]
				$document_body = ECash::getFactory()->getModel('DocumentListBody');
				$document_body->document_list_id = $id;
				$document_body->date_created = date('Y-m-d');
				$document_body->company_id = $params->company_id;
				$document_body->document_list_body_id = $params->document_body_id;
				$document_body->send_method = 'email';
				$document_body->save();
			}
			else
			{
				$document_body = ECash::getFactory()->getModel('DocumentListBody');
				$document_body->loadBy(array('document_list_id' => $id));
				$document_body->delete();
			}			
			return array('id' => $document->document_list_id, 'name_short' => $document->name_short, 'date_created' => $document->date_created, 'date_modified' => $document->date_modified);
		}
		else
		{
			return false;
		}

	}
	protected function addDocument($params)
	{
		$document = ECash::getFactory()->getModel('DocumentList');
		
		$document->active_status = $params->active_status;
		$document->name = $params->name;
		$document->name_short = $params->name_short;
		$document->loan_type_id = $params->loan_type_id;
		$document->company_id = $params->company_id;
		$document->required = $params->required;
		$document->esign_capable = $params->esig_capable;
		$document->send_method = $params->send_method;
		$document->only_receivable = $params->only_receivable; 
		
		if($document->save())
		{
			return array('id' => $document->document_list_id);
		}
		else
		{
			return false;
		}
	}
	protected function deleteDocument($id)
	{
		$document_list_ref = ECash::getFactory()->getReferenceList('DocumentListRef');
		$documents = ECash::getFactory()->getDocumentClient()->findNormalized(array('document_list_name' => $document_list_ref[$id]), 1, array());

		if(count($documents))
		{
			$document = ECash::getFactory()->getModel('DocumentList');
			$document->document_list_id = $id;
			return $document->delete();
		}
		else
		{
			return false;
		}	
	}
	protected function deletePackage($id)
	{
		$package = ECash::getFactory()->getModel('DocumentPackage');
		$package->document_package_id = $id;
		$return_value = $package->delete();
		if($return_value)
		{
			$listpackagelist = ECash::getFactory()->getModel('DocumentListPackageList');
			$listpackagelist->loadBy(array('document_package_id' => $id));
			foreach($listpackagelist as $listpackagerow)
			{
				$listpackagerow->delete();
			}			
		}	
		return $return_value;
	}
	protected function getCondorList()
	{
		$doc_manager = new ECash_Documents_Handler();
		$list = $doc_manager->getCondorList();
		sort($list);
		return $list;
	}
}
?>
