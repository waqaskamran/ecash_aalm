<?php
/**
 * The purpose of this script is to display condor documents or their attachments if a part_id is given.
 */

require_once (LIB_DIR . "Document/Document.class.php");
require_once (LIB_DIR . "Document/DeliveryAPI/Condor.class.php");

class Show_Attachment
{
	protected $server;
	protected $archive_id;
	protected $part_id;
	
	public function __construct($server, $archive_id, $part_id)
	{
		$this->server = $server;
		$this->archive_id = $archive_id;
		$this->part_id = $part_id;
	}

	public function getDocument()
	{
		// Get the condor document
		$condor_document = eCash_Document_DeliveryAPI_Condor::Prpc()->Find_Email_By_Archive_Id($this->archive_id, TRUE);

		//Check to see that we got something
		if(empty($condor_document))	
		{
			throw new Exception("No Document Found");
		}
		else
		{
			//If the part id is passed in, then display the respective attachment, else display the main document.
			if($document_to_show = empty($this->part_id) ? $condor_document : $this->getAttachment($condor_document,$this->part_id))
			{
				//die(print_r($document_to_show, TRUE));
				$this->out($document_to_show);
			}
		}
	}

	function out($document)
	{
		header("Content-type: ".$document->content_type);
		header('Content-Disposition: attachment; filename=attachment_'.$document->part_id.'_'.$this->Get_Extension($document));
		echo $document->data;
		die();
	}

	function getAttachment($condor_document,$part_id)
	{
		foreach($condor_document->attached_data as $attachment)
		{
			if($attachment->part_id == $part_id)
			{
				return $attachment;
			}
		}
	
		return null;
	}

	/**
	 * Returns a file name based on the document's Condor data object
	 *
	 * @param object $document The Condor data object
	 * return string The file extension
	 */
	function Get_Extension($document)
	{
		if ( !empty($document->uri) && $document->uri != 'NULL' )
		{
			return $document->uri;
		}

		$extensions = array(
			'text/html'       => '.html',
			'text/plain'      => '.txt',
			'text/rtf'        => '.rtf',
			'text/rtx'        => '.rtf',
			'application/pdf' => '.pdf',
			'image/tif'       => '.tif',
			'image/jpeg' 	 =>  '.jpg',
			'image/gif'		 => '.gif'
			);

		$filename = ($document->template_name != 'NULL' ? $document->template_name : 'Document');

		if ( isset($extensions[$document->content_type]) )
		{
			return $extensions[$document->content_type];
		}
	}

}

?>