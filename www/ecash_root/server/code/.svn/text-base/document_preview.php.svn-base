<?php

require_once (LIB_DIR . "Document/Document.class.php");

class Document_Preview
{
	protected $server;
	protected $application_id;
	protected $document_id;
	
	public function __construct($server, $application_id, $document_id)
	{
		$this->server = $server;
		$this->application_id = $application_id;
		$this->document_id = $document_id;
	}

	public function getDocument()
	{
		$deny_view_tokens = array("CustomerSSNPart1","CustomerSSNPart2");

		$app = ECash::getApplicationById($this->application_id);
		$docs = $app->getDocuments();
		$template = $docs->getTemplateById($this->document_id);

		if($template = $docs->getTemplateById($this->document_id))
		{
			$tokens = $template->getTemplateTokens();
			if($tokens  == 'unknown method (Get_Template_Tokens)' || !is_array($tokens)) 
			{
				throw new Exception('unknown method (Get_Template_Tokens)');
			}
			$acl = ECash::getACL()->Get_Control_Info(ECash::getAgent()->getAgentId(), $app->company_id);
	
			if(	count(@array_intersect(array('ssn_last_four_digits'),$acl)) && 
				count(array_intersect($tokens, $deny_view_tokens))) {
				return "Document Blocked";
			}
	
			if($document = $docs->create($template, true))
			{
				return $document;	
			}
			else
			{
				return 'Document Creation Failed';
			}

		}
		else
		{
			return 'Template Creation Failed';
		}
	}
}