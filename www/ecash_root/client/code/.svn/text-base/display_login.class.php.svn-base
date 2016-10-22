 
<?php

require_once(CLIENT_CODE_DIR . "display.iface.php");

class Display_Login implements Display
{
	public function Do_Display(ECash_Transport $transport)
	{
//		return $this->Do_XML($transport);
		
		//set other variables
		$data = ECash::getTransport()->Get_Data();
		$errors = ECash::getTransport()->Get_Errors();
		$html = file_get_contents(CLIENT_VIEW_DIR . "login.html");

		$html = str_replace("%%%title%%%", TITLE, $html);
		$error = count($errors) ? "<tr><th colspan=\"2\" style=\"background: red;\">{$errors[0]}</th><tr>": "";
		$html = str_replace("%%%login_errors%%%", $error, $html);
		$html = str_replace("%%%current_server%%%", EXECUTION_MODE, $html);
		//set the default login if needed
		$company_options = "";
		$pbx_companies = array();
		foreach ($data->companies as $c)
		{			
			if($c['ecash3_company'] === true) 
			{

				if (empty($_REQUEST['default_company']))
				{
					$_REQUEST['default_company'] = $c['name_short'];
				}
				
				$company_options .= "<option value=\"{$c['name_short']}\"";
// commented out in order to force trigger the onChange javascript
//				if ((isset($_REQUEST['default_company'])) &&
//				    ($_REQUEST['default_company'] == $c['name_short']))
//					$company_options .= " selected";
				$company_options .= ">{$c['name']}</option>\n";

				if ($c['pbx_enabled'] === true) 
				{
					$pbx_companies[] = $c['name_short'];
				}	
			}
		}

		$html = str_replace("%%%login_default_company%%%", $_REQUEST['default_company'], $html);
		$html = str_replace("%%%companies%%%", $company_options, $html);

		if (ECash::getConfig()->PBX_ENABLED === TRUE) 
		{
			$html = str_replace("%%%additional_fields%%%", file_get_contents(CLIENT_VIEW_DIR . "login_additional_fields.html"), $html);
			$html = str_replace("%%%agent_phone_extension%%%", $_COOKIE['previous_phone_extension'], $html);
			$html = str_replace("%%%pbx_company_active_list%%%","'" . implode("','", $pbx_companies) . "'",$html);
			$html = str_replace("%%%login_onchange_js%%%", "var active = checkPhoneAllowed(this.options[this.selectedIndex].value); togglePhoneExtRow(active);" ,$html);
//			$html = str_replace("%%%login_submit_javascript%%%", "return (validatePhoneField() && Set_Company_Host_Location() )", $html);
			$html = str_replace("%%%login_submit_javascript%%%", "return ( Set_Company_Host_Location() )", $html);
			
		} else {
			$html = str_replace("%%%additional_fields%%%", "", $html);
			$html = str_replace("%%%login_onchange_js%%%", "" ,$html);
			$html = str_replace("%%%login_submit_javascript%%%", "return Set_Company_Host_Location()", $html);
		}
		
		switch (EXECUTION_MODE)
		{
		case 'LIVE': $html = str_replace("%%%current_bg%%%", 'bg_live', $html); break;
		case 'RC':   $html = str_replace("%%%current_bg%%%", 'bg_rc', $html); break;
		case 'QA_MANUAL':
		case 'QA_SEMI_AUTOMATED':
		case 'QA_AUTOMATED':
		case 'QA':	 $html = str_replace("%%%current_bg%%%",'bg_qa',$html); break;
		default: $html = str_replace("%%%current_bg%%%", 'bg_local', $html);
		}

		//if not on https/443
		if((empty($_SERVER["HTTPS"]) || ($_SERVER["HTTPS"] != "on" && $_SERVER["HTTPS"] != 1))
		   && ECash::getConfig()->FORCE_SSL_LOGIN == "ON")
		{
			// If the TLD is not .tss
			if (strtolower(substr($_SERVER['SERVER_NAME'], -3, 3)) !== 'tss')
				$html .= "<script language=javascript>window.location ='".str_replace("http","https",$_SERVER["SCRIPT_URI"])."';</script>\n";
		}

		echo $html;
	}
	
	
	public function Do_XML(ECash_Transport $transport)
	{
		$data = ECash::getTransport()->Get_Data();
		$errors = ECash::getTransport()->Get_Errors();
		$xml = new DomDocument("1.0", "UTF-8");
		$xml->formatOutput = TRUE;
		$xml->appendChild($xml->createProcessingInstruction('xml-stylesheet','href="/xsl/login.xsl" type="text/xsl"'));
		
		$root = $xml->appendChild($xml->createElement("ecash"));
		$root->setAttribute("execution_mode", EXECUTION_MODE);
		$root->setAttribute("major_version", MAJOR_VERSION);		
		$root->setAttribute("minor_version", MINOR_VERSION);		
		$root->setAttribute("build_version", BUILD_NUM);
		$root->setAttribute("database", DB_NAME . ":" . DB_PORT);

		$agt = $root->appendChild($xml->createElement("agent"));
		$agt->appendChild($xml->createElement("login", $_COOKIE['previous_login']));
		$agt->appendChild($xml->createElement("phone_extension", $_COOKIE['previous_phone_extension']));	
		
		if (count($errors)) 
		{
			$root->appendChild($xml->createElement("error", $errors[0]));
		}

		if (ECash::getConfig()->PBX_ENABLED === TRUE) 
		{
			$root->appendChild($xml->createElement("pbx"))->setAttribute("enabled","true");
		}
		
		$cmp = $root->appendChild($xml->createElement("companies"));
		foreach ($data->companies as $c) 
		{
			if($c['ecash3_company'] !== true) continue;
			$d = $cmp->appendChild($xml->createElement("company", $c['name']));
			$d->setAttribute("name_short", $c['name_short']);
			$d->setAttribute("id", $c['company_id']);
			
			if ( isset($_REQUEST['default_company']) && $_REQUEST['default_company'] == $c['name_short']) 
			{
				$cmp->setAttribute("default",$c['name_short']);
			}
		}
		
		header("Content-type: text/xml");
		echo $xml->saveXML();
	}
	
}

?>
