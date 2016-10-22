<?php
/**
 * @package Documents
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 * @created Jan 31, 2007
 *
 * @version $Revision$
 */

if (!defined('eCash_Document_DIR')) require_once LIB_DIR . "Document/Document.class.php";

class eCash_Document_XMLFormat {

	private $obj;
	
	private $last_value;
	private $last_called;
	private $last_args;
	private $last_static;
	
	public function __construct($obj = NULL, $static_call = false)
	{
//		var_dump(__METHOD__ . " Called, Line " . __LINE__);
		if ($static_call !== true) {
			$this->__subConstruct($obj);
		}
	}

	private function __subConstruct(eCash_Document $obj)
	{
		$this->obj = $obj;		
	}
	
	public function __get($name)
	{
		switch ($name) {
			case "value":
				return $this->last_value;
			case "called":
				return $this->last_called;
			case "args":
				return $this->last_args;
			case "rerun":
				return ($this->last_static === true) ? self::staticCall($this->last_called, $this->last_args): $this->__call($this->last_called, $this->last_args);
			default:
				throw new OutOfBoundsException("Invalid index {$name}");
		}
	}
	
	public function __call($name, $args)
	{
		if(is_callable(array($this->obj,$name))) {
			$value = call_user_func_array(array($this->obj,$name),$args);
			$this->__forceLastParams($name, $args, &$value);
			return $this->last_value;
		}
	}
	
	static public function staticCall($name, $args)
	{
//var_dump(__METHOD__ . " Called, Line " . __LINE__.":\n" . var_export($args,true));
		if(is_callable(array("eCash_Document",$name))) {
//var_dump("eCash_Document::{$name} is Callable");		
			$obj = new eCash_Document_XMLFormat(null,TRUE);
			$value = call_user_func_array(array("eCash_Document",$name),$args);
			$obj->__forceLastParams($name, $args, &$value, TRUE);
			return $obj;
		}
		
	}
	
	public function __forceLastParams($name, $args, &$value, $static = false)
	{
//var_dump(__METHOD__ . " Called, Line " . __LINE__.":\n" . var_export(array($name,$args,&$value,$static),true));
		$this->last_called = $name;
		$this->last_args = $args;
		$this->last_value =& $value;
		$this->last_static = $static;
	}
	
	public function __toString()
	{
//		var_dump(__METHOD__ . " Called, Line " . __LINE__);
		switch ($this->last_called) {
			case "Get_Documents_By_Name":
			case "Get_Documents":
			case "Get_Document_List":
				return $this->formatDocumentList();
				
			case "Get_Document_Log":
			case "Log_Document":
			case "Send_Document":
			case "Get_Document_Id":
			case "Get_Application_Data":
			case "Get_Application_History":
			default:
				return $this->obj->__toString();
		}
	}
	
	protected function formatDocumentList()
	{
//var_dump(__METHOD__ . " Called, Line " . __LINE__);
		$xml = new DomDocument("1.0","UTF-8");
		$xml->formatOutput = true;
		
		$list = $xml->appendChild($xml->createElement("documents"));
		$list->setAttribute("count",count($this->last_value));
		$list->setAttribute("which-list",htmlentities(utf8_encode($this->last_args[1])));
		
		foreach ($this->last_value as $doc_id => $doc) {
			$dlm = $list->appendChild($xml->createElement("document"));
			$dlm->setAttribute("id",$doc_id);
			$dlm->setAttribute("api", $doc->document_api);
			switch ($this->last_args[1]) {
				case "package-display":
				case "packaged":
					$dlm->setAttribute("status",'active');
					$dlm->setAttribute("type","package");
					$dlm->appendChild($xml->createElement("name",htmlentities(utf8_encode($doc->document_package_name))));
					break;
				default:
					$dlm->setAttribute("status",$doc->active);
					$dlm->setAttribute("type","document");
					$dlm->appendChild($xml->createElement("name",htmlentities(utf8_encode($doc->description))));
			}
				
			if($doc->only_receivable == "yes" || $doc->required == "yes") { 
				$capr = $dlm->appendChild($xml->createElement("capabilities"));
				$capr->setAttribute("type","receive");
			}
			if ($doc->only_receivable != "yes") {
				$caps = $dlm->appendChild($xml->createElement("capabilities"));
				$caps->setAttribute("type","send");
			}

			$methods = explode(",",$doc->send_method);
			if($doc->esig_capable == "yes") $methods[] = "esig";
					
			foreach($methods as $a) {
				if($doc->only_receivable == "yes" || $doc->required == "yes") { 
					$capr->appendChild($xml->createElement("capability", $a));
				}
				if($doc->only_receivable != "yes") {
					$caps->appendChild($xml->createElement("capability", $a));
					
					$a = strtolower($a);
					$em = $dlm->appendChild($xml->createElement("{$a}_body",htmlentities(utf8_encode($doc->{"{$a}_body_name"}))));
					if ($doc->{"{$a}_body_id"}) $em->setAttribute("id",$doc->{"{$a}_body_id"});
					
				}
			}					
					
			if(count($doc->bodyparts) < 1) $doc->bodyparts[$doc_id] = $doc->description;
					
			$ats = $dlm->appendChild($xml->createElement("attachments"));
			foreach ($doc->bodyparts as $att_id => $att) {
				$at = $ats->appendChild($xml->createElement("attachment",htmlentities(utf8_encode($att))));
				$at->setAttribute("id",$att_id);					
			}
		}

//var_dump($xml->saveXML());
				
//var_dump($this->last_value);
		
		return $xml->saveXML();
		
	}
	
	static public function Get_Document_List() 
	{
//var_dump(__METHOD__ . " Called, Line " . __LINE__ );
		$args = func_get_args();
		$obj = self::staticCall("Get_Document_List",$args);
//var_dump($obj);
		return (string) $obj->__toString();
	}
}

?>
