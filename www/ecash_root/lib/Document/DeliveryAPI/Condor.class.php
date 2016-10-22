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

//require_once('config.php');
//require_once("qualify.1.php");
require_once("prpc/client.php");
require_once("config.4.php");
require_once(SERVER_CODE_DIR . "loan_data.class.php");
require_once(SQL_LIB_DIR . "/fetch_campaign_info.func.php");
require_once(SQL_LIB_DIR . "/application.func.php");
require_once(SQL_LIB_DIR . "/react.func.php");
require_once(ECASH_COMMON_DIR."/ecash_api/qualify.2.ecash.php");
require_once(SERVER_CODE_DIR . "vehicle_data.class.php");
require_once(COMMON_LIB_DIR.'pay_date_calc.3.php');

class eCash_Document_DeliveryAPI_Condor_Receive_Exception extends Exception {
}

class eCash_Document_DeliveryAPI_Condor {

	static private $prpc;

	static public $deny_view_tokens = array("CustomerSSNPart1","CustomerSSNPart2");

	static private $template_names = array();

	static public function Prpc()
	{
		try
		{
			if (!(self::$prpc instanceof Prpc_Client))
			{
				$condor_server = ECash::getConfig()->CONDOR_SERVER;
				self::$prpc = new Prpc_Client($condor_server);
			}

			return self::$prpc;

		}
		catch (Exception $e)
		{
			if (preg_match("//",$e->getMessage()))
			{
				throw new InvalidArgumentException(__METHOD__ . " Error: " . $condor_server . " is not a valid PRPC resource.");
			}

			throw $e;

		}

	}

	static public function Receive(Server $server, $document_list, $request)
	{
		try
		{
			$orequest = $request;

			if(!is_numeric($request->archive_id))
			{
				throw new eCash_Document_DeliveryAPI_Condor_Receive_Exception("Archive ID must be numeric");
			}

			if(!isset($request->document_list))
			{
				$_SESSION['current_app']->archive_id = '';
				throw new eCash_Document_DeliveryAPI_Condor_Receive_Exception("No document is selected");
			}

//			if (isset($request->document_list) && count($request->document_list) > 1) {
//				$_SESSION['current_app']->archive_id = '';
//				throw new eCash_Document_DeliveryAPI_Condor_Receive_Exception("Select 1 document at a time");
//			}

			$_SESSION['current_app']->archive_id = $request->archive_id;

			if(!self::Validate_Tiff($request->archive_id))
			{
				throw new eCash_Document_DeliveryAPI_Condor_Receive_Exception("Document not found");
			}

//			if(self::Check_ArchiveIDs($server, $request->archive_id)) {
//				throw new eCash_Document_DeliveryAPI_Condor_Receive_Exception("Archive ID already used");
//			}

			eCash_Document::$message = "<font color=\"green\"><b>Document found and updated</b></font>";

			foreach($document_list as $document)
			{

				$otherdoc = "docname_" . strtolower($document->name);
				if (preg_match("/^other/", strtolower($document->name)) && strlen($request->$otherdoc) < 1)
				{
					throw new eCash_Document_DeliveryAPI_Condor_Receive_Exception("Enter another name");
				}
				elseif (preg_match("/^other/", strtolower($document->name)) )
				{
					$request->destination['name'] = $request->$otherdoc;
				}

				$request->signature_status = ($request->signature_status) ? $request->signature_status : 'unsigned';

				$request->method = ( isset($request->method) && !empty($request->method) ? $request->method : "fax");
				$request->document_event_type = "received";
				$result = array();
				foreach($request as $key => $value)
				{
					$result[$key] = $value;
				}
				eCash_Document::Log_Document($server, $document,  $result);

				self::Set_Application_ID($request->archive_id, $request->application_id);


			} // end foreach

			$_SESSION['current_app']->archive_id = '';
		}

		catch (eCash_Document_DeliveryAPI_Condor_Receive_Exception $e)
		{
				eCash_Document::Log()->write($e->getMessage(), LOG_ERROR);

				eCash_Document::$message = "<font color=\"red\"><b>" . $e->getMessage() . "</b></font>";
				return false;
		}

		return true;
	}



	static function format_time($dec)
	{
		if(empty($dec))
		{
			return 'Closed';
		}
		if($dec > 1200)
		{
			return intval((substr(($dec),0,2) - 12)) . ':' . substr(($dec),2)  . 'pm';
		}
		elseif($dec < 1200)
		{
			return intval((substr(($dec),0,2))) . ':' . substr(($dec),2)  . 'am';
		}
		else
		{
			return intval((substr(($dec),0,2))) . ':' . substr(($dec),2)  . 'pm';
		}
	}

	static public function Fetch_Doc_List( $server = NULL )
	{

		try
		{
			if(!count(self::$template_names))
			{
				self::$template_names = self::Prpc()->Get_Template_Names();
			}

			foreach(self::$template_names as $key => $value)
			{

				$obj = (object) array();
				$obj->name = $value;
				$obj->description = $obj->name;
				$obj->file = $value;
				$obj->required = 0;
				$doc_return[$obj->name] = $obj;
			}

		}
		catch(Exception $e)
		{
			if($server instanceof Server) eCash_Document::Log()->Write($e->getMessage());
			else throw $e;
		}

		return $doc_return;

	}


	static public function Validate_Tiff ($archive_id)
	{

		return (bool) self::Prpc()->Find_By_Archive_Id($archive_id);

	}



	static public function Get_PDF (Server $server, $archive_id)
	{
		$document = self::Prpc()->Find_By_Archive_Id($archive_id);

		if($document === FALSE)
		{
			echo "There was an error retrieving this document.  Please contact support and reference Archive ID: {$archive_id}";
			die();
		}

		if(isset($document->template_name))
		{
			try
			{
				if(!count(self::$template_names))
				{
					self::$template_names = self::Prpc()->Get_Template_Names();
				}

				if(in_array($document->template_name,self::$template_names))
				{

					$tokens = self::Prpc()->Get_Template_Tokens($document->template_name);
					if($tokens  == 'unknown method (Get_Template_Tokens)' || !is_array($tokens))
					{
						throw new Exception();
					}


					$acl = ECash::getACL()->Get_Control_Info($server->agent_id, $server->company_id);
					if(	is_array($acl) && count(array_intersect(array('ssn_last_four_digits'),$acl)) &&
						count(array_intersect($tokens,self::$deny_view_tokens))) {
						echo "Document Blocked";
						die;
					}

				}

			}
			catch (Exception $e)
			{
				if ($e->getMessage())
				{
					throw $e;
				}
			}

		}

		//header("Content-type: " . $document->content_type);

		self::Set_Display_Headers($document);

		echo (isset($document->data)) ? $document->data : "No Document Data" ;

		die;

//		return ($document->data) ? $document : false;

	}

	/**
	 * Same as Get_PDF(), but for document attachments.
	 *
	 * @param object $server
	 * @param object $archive_id
	 * @param object $attachment_key The key from the document's attached_data array
	 */
	static public function Get_Attachment_PDF (Server $server, $archive_id, $attachment_key)
	{
		$document = self::Prpc()->Find_By_Archive_Id($archive_id);

		if($document === FALSE)
		{
			echo "There was an error retrieving this document.  Please contact support and reference Archive ID: {$archive_id}";
			die();
		}

		// TODO: Modify this in some way to protect attachments with no templates
		if(isset($document->template_name))
		{
			try
			{
				if(!count(self::$template_names))
				{
					self::$template_names = self::Prpc()->Get_Template_Names();
				}

				if(in_array($document->template_name,self::$template_names))
				{

					$tokens = self::Prpc()->Get_Template_Tokens($document->template_name);
					if($tokens  == 'unknown method (Get_Template_Tokens)' || !is_array($tokens))
					{
						throw new Exception();
					}

					$acl = ECash::getACL()->Get_Control_Info($server->agent_id, $server->company_id);
					if(	count(array_intersect(array('ssn_last_four_digits'),$acl)) &&
						count(array_intersect($tokens,self::$deny_view_tokens))) {
						echo "Document Blocked";
						die;
					}

				}

			}
			catch (Exception $e)
			{
				if ($e->getMessage())
				{
					throw $e;
				}
			}
		}

		if ( !is_array($document->attached_data) || !isset($document->attached_data[$attachment_key]) )
			die;

		$attachment = $document->attached_data[$attachment_key];

		self::Set_Display_Headers($attachment);

		echo (isset($attachment->data)) ? $attachment->data : NULL ;
		die;
	}



	static public function Set_Application_Id ($archive_id, $application_id)
	{

		return (bool) self::Prpc()->Set_Application_Id($archive_id, $application_id);

	}

	static public function Format_Money($value, $default = NULL)
	{
		return eCash_Document_ApplicationData::Format_Money($value, $default);
	}

	static public function Format_Phone($value)
	{
		preg_match("/1?(\d{3})(\d{3})(\d{4})/", preg_replace("/\D/","",$value), $matches);
		array_shift($matches);

		if ( strlen(implode("",$matches)) != 10 )
		{
			return $value;
		}

		return ( ($incl_iac) ? "1 " : "" ) . "({$matches[0]}) {$matches[1]}-{$matches[2]}";
	}

	/**
	 * Returns a file name based on the document's Condor data object
	 *
	 * @param object $document The Condor data object
	 * return string The file extension
	 */
	static protected function Get_File_Name($document)
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
		                   'image/tif'       => '.tif'
		                   );

		$filename = ($document->template_name != 'NULL' ? $document->template_name : 'Document');

		if ( isset($extensions[$document->content_type]) )
		{
			return $filename . $extensions[$document->content_type];
		}

		return $filename;
	}

	/**
	 * Sets header data based on the document's Condor data object
	 *
	 * @param object $document The Condor data object
	 */
	static protected function Set_Display_Headers($document)
	{
		header("Content-type: " . $document->content_type);

		// content types that will display in the browser
		$display_types = array(
		                   'text/html',
		                   'text/plain'
		                   );

		// if this document's content type will not display in the browser
		if ( !in_array($document->content_type, $display_types) )
		{
			header('Content-Disposition: attachment; filename="' . self::Get_File_Name($document) . '"');
		}
	}

}

?>
