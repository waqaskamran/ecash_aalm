<?php

class ECash_VendorAPI_Document implements VendorAPI_IDocument
{
	/**
	 *
	 * @var ECash_Documents_Condor
	 */
	protected $condor;

	/**
	 * Enter description here...
	 *
	 * @var ECash_Factory
	 */
	protected $factory;

	/**
	 * @var Integer
	 */
	protected $company_id;

	/**
	 *
	 * @param ECash_Documents_Condor $condor
	 * @return void
	 */
	public function __construct(
		ECash_Documents_Condor $condor,
		ECash_Factory $factory
	)
	{
		$this->condor = $condor;
		$this->factory = $factory;
	}

	/**
	 * Creates a document preview
	 *
	 * @param $template
	 * @param $tokens
	 * @return VendorAPI_DocumentData
	 */
	public function previewDocument($template, array $tokens, VendorAPI_CallContext $context)
	{
		$doc = $this->condor->Create($template, $tokens, FALSE, NULL, NULL, NULL, TRUE);

		if ($doc === FALSE)
		{
			throw new RuntimeException('Could not create preview');
		}

		$docdata = new VendorAPI_DocumentData();
		$docdata->populateFromObject($doc);
		$docdata->setDocumentListId($this->getDocumentListId($template, $context->getCompanyId()));

		return $docdata;
	}

	/**
	 * (non-PHPdoc)
	 * @see code/VendorAPI/VendorAPI_IDocument#getByArchiveId()
	 */
	public function getByArchiveId($archive_id)
	{
		$doc_data = $this->condor->Find_By_Archive_Id($archive_id);
		if (!$doc_data)
		{
			throw new VendorAPI_DocumentNotFoundException();
		}
		$doc = new VendorAPI_DocumentData();
		$doc_data->document_id = $archive_id;
		$doc->populateFromObject($doc_data);
		return $doc;
	}

	/**
	 * (non-PHPdoc)
	 * @see code/VendorAPI/VendorAPI_IDocument#create()
	 */
	public function create(
		$template,
		VendorAPI_IApplication $application,
		VendorAPI_ITokenProvider $token_provider,
		VendorAPI_CallContext $context
	)
	{
		// @todo Actually pass a "space" key so that we can
		// actually hti stats? Condor doens't actually do any
		// stats on create at the moment, so I'm not doing
		// this yet.
		$data = $this->condor->Create(
			$template,
			$token_provider->getTokens($application, FALSE),
			TRUE,
			$application->application_id,
			$application->track_id,
			''
		);
		if (!is_array($data) || !is_numeric($data['archive_id']))
		{
			throw new VendorAPI_DocumentCreateException("Unable to create $template document ($archive_id).");
		}
		$doc = new VendorAPI_DocumentData();
		$doc->populateFromObject($data['document']);
		$doc->setDocumentId($data['archive_id']);
		$doc->setDocumentListId($this->getDocumentListId($template, $context->getCompanyId()));
		return $doc;
	}

	/**
	 * (non-PHPdoc)
	 * @see code/VendorAPI/VendorAPI_IDocument#signDocument()
	 */
	public function signDocument(
		VendorAPI_IApplication $application,
		VendorAPI_DocumentData $document,
		VendorAPI_CallContext $context
	)
	{
		$archive_id = $document->getDocumentId();
		if (!is_numeric($archive_id))
		{
			throw new RuntimeException('No document id.');
		}
		$content = $document->getContents();

		return $this->condor->Sign($archive_id, $content, $application->ip_address);
	}

	/**
	 * (non-PHPdoc)
	 * @see code/VendorAPI/VendorAPI_IDocument#findDocument()
	 */
	public function findDocument($application_id, $template)
	{
		if (!is_numeric($application_id))
		{
			throw new InvalidArgumentException("Application id is not numeric.");
		}
		if (!is_string($template) || empty($template))
		{
			throw new InvalidArgumentException("$template is not a valid argument.");
		}

		$results = $this->condor->Find_By_Application_Id($application_id);
		if (!is_array($results))
		{
			throw new RuntimeException("No document list in condor.");
		}
		// Filter the results by template name
		$results = array_filter($results, create_function('$a', sprintf('return strcasecmp(\'%s\', $a[\'template_name\']) == 0;', $template)));
		// if we still have more than one left, sort them by date created
		if (is_array($results) && count($results) > 1)
		{
			uasort($results, create_function('$a, $b', 'return strtotime($a[\'date_created\']) > strtotime($b[\'date_created\']) ? -1 : 1;'));
		}

		$document = array_shift($results);
		return $document['document_id'];
	}

	public function getDocumentListId($template, $company_id)
	{
		$model = $this->factory->getModel('DocumentList');
		if ($model->loadBy(array('name' => $template,
				'company_id' => $company_id,
				'active_status' => 'active',
				'system_id' => ECash::getSystemId(),
			)))
		{
			return $model->document_list_id;
		}
		throw new RuntimeException("Can't find $template in document_list.");
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $template
	 * @param array $tokens
	 * @param VendorAPI_CallContext $context
	 */
	public function documentMatchesHash(
		VendorAPI_IApplication $application,
		$template,
		array $tokens,
		VendorAPI_CallContext $context)
	{
		$hash = $application->getDocumentHash($this->getDocumentListId($template, $context->getCompanyId()), $context->getCompanyId());
		if ($hash)
		{
			return $hash == $this->generateDocumentHash($template, $tokens, $context);
		}

		// if they have not previewed, we let them pass
		return TRUE;
	}



	/**
	 * Return a hash of the document requested
	 *
	 * @param string $template
	 * @param array $tokens
	 * @param VendorAPI_Context $context
	 * @return string
	 */
	protected function generateDocumentHash($template, $tokens, VendorAPI_CallContext $context)
	{
		$doc = $this->previewDocument($template, $tokens, $context);
		return $this->hashDocumentData($doc->getContents());
	}

	/**
	 * Hash a string (liek a document!) and return it back
	 *
	 * @param string $data
	 * @return string
	 */
	protected function hashDocumentData($data)
	{
		return sha1($data);
	}
}
