<?php

require_once (LIB_DIR . "Document/Document.class.php");
require_once (LIB_DIR . "Document/DeliveryAPI/Condor.class.php");

class Show_PDF
{
	protected $server;
	protected $archive_id;
	protected $attachment_key;
	
	public function __construct($server, $archive_id, $attachment_key)
	{
		$this->server = $server;
		$this->archive_id = $archive_id;
		$this->attachment_key = $attachment_key;
	}

	public function getDocument()
	{	
		if ( isset($this->attachment_key) )
		{
			eCash_Document_DeliveryAPI_Condor::Get_Attachment_PDF($this->server, $this->archive_id, $this->attachment_key);
		}
		else
		{
			$document_handler = new ECash_Documents_Handler();
	
			if($document = $document_handler->getByIDFromCondor($this->archive_id))
			{
				if($template = $document_handler->getTemplateByName($document->getName()))
				{
					try{
						$tokens = $template->getTemplateTokens();
						if($tokens  == 'unknown method (Get_Template_Tokens)' || !is_array($tokens)) 
						{
							throw new Exception('unknown method (Get_Template_Tokens)');
						}
					}
					catch(Exception $e)
					{
					}

					$acl = ECash::getACL()->Get_Control_Info(ECash::getAgent()->getAgentId(), ECash::getCompany()->company_id);
		
					if(	count(@array_intersect(array('ssn_last_four_digits'),$acl)) && 
						count(array_intersect($tokens, $deny_view_tokens))) {
						return "Document Blocked";
					}
				}
				return $document;	
			}
			else
			{
				return 'Archive ID does not Exist in Condor';
			}
		}
	}
}