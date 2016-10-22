<?php
/**
 * <CLASSNAME>
 * <DESCRIPTION>
 *
 * Created on Jan 16, 2007
 *
 * @package <PACKAGE>
 * @category <CATEGORY>
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once(LIB_DIR. "form.class.php");
require_once("admin_parent.abst.php");
require_once(COMMON_LIB_DIR . "dropdown_dates.1.php");
require_once(LIB_DIR . 'DisplayMessage.class.php');

//ecash module
class Display_View extends Admin_Parent
{

	public function Get_Module_HTML()
	{
		$data = ECash::getTransport()->Get_Data();

		$fields = new stdClass();
		
		$max = 99999999;
		foreach($data->document_list as $doc) 
		{
			$tsend_sort[$doc->document_list_id] = (isset($doc->doc_send_order)) ? (int) $doc->doc_send_order : $max++;
			$trecv_sort[$doc->document_list_id] = (isset($doc->doc_receive_order)) ? (int) $doc->doc_receive_order : $max++;
			$fields->document_list .= "<option value=\"{$doc->document_list_id}\">{$doc->name}</option>\n";
		}

		asort($tsend_sort);
		asort($trecv_sort);		

		$recv_sort = array_keys($trecv_sort);
		$send_sort = array_keys($tsend_sort);

		$name_shorts = array();
		foreach($data->package_list as $pkg) 
		{
			// Get the name short for each package so I have a list that I can check against in JS
			$name_shorts[] = $pkg->name_short;

			$fields->package_list .= "<option value=\"{$pkg->document_package_id}\">{$pkg->name}</option>\n";
		}

		$fields->packaged_docs_short_list = json_encode(array());
		if (is_array($name_shorts))
		{
			$fields->packaged_docs_short_list = json_encode($name_shorts);
		}
					
		$fields->existingDocs = json_encode(array());
		if (is_array($data->document_list))
		{
			foreach($data->document_list as $doc) 
			{
				$mydocs[] = array('name' => $doc->name, 'name_short' => $doc->name_short);
			}

			$fields->existingDocs = json_encode($mydocs);
		}

					
		switch ($data->view) 
		{
			case "documents":
				
				$form = new Form(CLIENT_MODULE_DIR . $this->module_name . "/view/docs_config_document_manager.html");
				
				return $form->As_String($fields) ;			
				
			case "packages":
				
				$form = new Form(CLIENT_MODULE_DIR . $this->module_name . "/view/docs_config_package_manager.html");

				return $form->As_String($fields) ;			
				
			case "sort_order":

				$fields->send_sorted_document_list = '';

				foreach ($send_sort as $doc_id) 
				{
					if($data->document_list[$doc_id]->active_status == 'active' && $data->document_list[$doc_id]->only_receivable == 'no' && $data->document_list[$doc_id]->name_short != 'other' && !preg_match("/^other_email/", $data->document_list[$doc_id]->name_short) ) 
					{
						$fields->send_sorted_document_list .= "<option value=\"{$data->document_list[$doc_id]->document_list_id}\">{$data->document_list[$doc_id]->name}</option>\n";					
					}
				}

				$fields->recv_sorted_document_list = '';

				foreach ($recv_sort as $doc_id) 
				{
					if($data->document_list[$doc_id]->active_status == 'active' && $data->document_list[$doc_id]->required == 'yes' && !preg_match("/^other_email/", $data->document_list[$doc_id]->name_short)) 
					{
						$fields->recv_sorted_document_list .= "<option value=\"{$data->document_list[$doc_id]->document_list_id}\">{$data->document_list[$doc_id]->name}</option>\n";					
					}
				}

				$form = new Form(CLIENT_MODULE_DIR . $this->module_name . "/view/docs_config_document_sort.html");

				return $form->As_String($fields) ;			
				
			case "process":
				$form = new Form(CLIENT_MODULE_DIR . $this->module_name . "/view/docs_config_process_manager.html");
				
				$statuses = Status_Utility::Fetch_Status_Map();
				$fields->application_status_list = "<option>&nbsp;</option>\n";
				foreach($statuses as $status_id => $status)
				{
					$fields->application_status_list .= '<option value="' . $status_id . '">' . $status['name'] . " (" .  $status['name_short'] . ")</option>\n";
				}
				
				$processes = eCash_Document::Get_All_Status_Triggers(ECash::getTransport()->company_id);
				$fields->json_processes =  json_encode($processes);
				
				return $form->As_String($fields);	
			
			case "printing_queue" :

				$form = new Form(CLIENT_MODULE_DIR . $this->module_name . "/view/docs_config_printing_manager.html");

				$data->printing_queue_fields['lines'] = json_encode($data->printing_queue_fields['lines']);
				$data->printing_queue_fields['queues'] = json_encode($data->printing_queue_fields['queues']);
				$data->printing_queue_fields['printers'] = json_encode($data->printing_queue_fields['printers']);

				foreach($data->printing_queue_fields as $field_name => $field_value)
				{
					$fields->$field_name = $field_value;
				}

				$date_day_selx   = (isset($data->printing_queue_fields->{'start_date_DD'}))   ? ($data->printing_queue_fields->{'start_date_DD'})   : date('d');
				$date_month_selx = (isset($data->printing_queue_fields->{'start_date_MM'}))   ? ($data->printing_queue_fields->{'start_date_MM'})   : date('m');
				$date_year_selx  = (isset($data->printing_queue_fields->{'start_date_YYYY'})) ? ($data->printing_queue_fields->{'start_date_YYYY'}) : date('Y');
				$date_code  = '<span>Start Date : (<a href="#" onClick="ReportCalendar(\'start_\', event.clientX, event.clientY)">select</a>)</span>';
				$date_code .= '<span> ';
				$date_code .= "<input type=text id='start_display' name='start_display' value='{$date_month_selx}/{$date_day_selx}/{$date_year_selx}' SIZE=7 READONLY>\n";
				$date_code .= "<input type=hidden id='start_month' name='start_month' value='{$date_month_selx}'>\n";
				$date_code .= "<input type=hidden id='start_day' name='start_day' value='{$date_day_selx}'>\n";
				$date_code .= "<input type=hidden id='start_year' name='start_year' value='{$date_year_selx}'>\n";
				$date_code .= '</span>';

				$fields->start_date = $date_code;

				$date_day_selx   = (isset($data->printing_queue_fields->{'end_date_DD'}))   ? ($data->printing_queue_fields->{'end_date_DD'})   : date('d');
				$date_month_selx = (isset($data->printing_queue_fields->{'end_date_MM'}))   ? ($data->printing_queue_fields->{'end_date_MM'})   : date('m');
				$date_year_selx  = (isset($data->printing_queue_fields->{'end_date_YYYY'})) ? ($data->printing_queue_fields->{'end_date_YYYY'}) : date('Y');
				$date_code  = '<spant;">End Date : (<a href="#" onClick="ReportCalendar(\'end_\', event.clientX, event.clientY)">select</a>)</span>';
				$date_code .= '<span> ';	
				$date_code .= "<input type=text id='end_display' name='end_display' value='{$date_month_selx}/{$date_day_selx}/{$date_year_selx}' SIZE=7 READONLY>\n";
				$date_code .= "<input type=hidden id='end_month' name='end_month' value='{$date_month_selx}'>\n";
				$date_code .= "<input type=hidden id='end_day' name='end_day' value='{$date_day_selx}'>\n";
				$date_code .= "<input type=hidden id='end_year' name='end_year' value='{$date_year_selx}'>\n";
				$date_code .= '</span>';

				$fields->end_date = $date_code;

				$dob_drop = new Dropdown_Dates();
				$dob_drop->Set_Prefix("start_");
				$dob_drop->Set_Day(date('d'));
				$dob_drop->Set_Month(date('m'));
				$dob_drop->Set_Year(date('Y'));
				$fields->from_date = $dob_drop->Fetch_Drop_All();

				$dob_drop->Set_Prefix("end_");
				$dob_drop->Set_Day(date('d'));
				$dob_drop->Set_Month(date('m'));
				$dob_drop->Set_Year(date('Y'));
				$fields->to_date = $dob_drop->Fetch_Drop_All();

				$fields->dd_start_time = '';
				$fields->dd_end_time = '';
				$year  = date('Y');
				$month = date('m');
				$day   = date('d');
				for ($x=0; $x<24; $x++)
				{
					$hour = sprintf('%02d', $x);
					$fields->dd_start_time .= "<option value=\"$hour:00\"> $hour:00 </option>\n";
					$fields->dd_start_time .= "<option value=\"$hour:30\"> $hour:30 </option>\n";

					$fields->dd_end_time   .= "<option value=\"$hour:00\"> $hour:00 </option>\n";
					$fields->dd_end_time   .= "<option value=\"$hour:30\"> $hour:30 </option>\n";
				}

				$fields->specify_arch_id_higher = DisplayMessage::get(array('javascript', 'info', 'to arch id higher then from'));
				$fields->specify_arch_id_reprint = DisplayMessage::get(array('javascript', 'info', 'specify arch id to reprint from'));
				return $form->As_String($fields);

			case 'email_responses' :

				$form = new Form(CLIENT_MODULE_DIR . $this->module_name . "/view/docs_config_email_manager.html");

				$data->email_responses_fields['responses'] = json_encode( $data->email_responses_fields['responses'] );
				$data->email_responses_fields['companies'] = json_encode( $data->email_responses_fields['companies'] );

				foreach($data->email_responses_fields as $field_name => $field_value)
				{
					$fields->$field_name = $field_value;
				}
				
				$fields->name_response_required = DisplayMessage::get(array('javascript', 'fields', 'name and response required'));
				$fields->select_response_remove = DisplayMessage::get(array('javascript', 'fields', 'select existing response to remove'));
				$fields->has_response_already = DisplayMessage::get(array('javascript', 'info', 'response exists with name'), '');
				return $form->As_String($fields);

			case 'email_footers' :

				$form = new Form(CLIENT_MODULE_DIR . $this->module_name . "/view/docs_config_email_footer.html");

				$fields->email_footers = json_encode( $data->email_footers );
				$fields->companies_list = $data->companies_list;

				return $form->As_String($fields);

			case "xml_package_data"	:
				
				header('Content-Type: text/xml');
				echo $this->Get_Package_XML($data->document_package_id)->saveXML();
				exit;
								
			case "xml_document_data":
				
				header('Content-Type: text/xml');
				echo $this->Get_Document_XML($data->document_list_id)->saveXML();
				exit;
				
		}
	}

	public function Get_Package_XML($document_package_id)
	{
		$data = ECash::getTransport()->Get_Data();
		
		$xml  = new DomDocument("1.0", "UTF-8");
		$xml->formatOutput = true;
		$dx = $xml->appendChild($xml->createElement("document-package"));
		
		if(isset($data->package_list[$document_package_id])) 
		{
			$doc = $data->package_list[$document_package_id];	

			foreach ($doc as $key => $value ) 
			{
				if(is_array($value)) 
				{
					$ax = $dx->appendChild($xml->createElement("attachments"));
					foreach ($value as $k => $attachment) 
					{
						$at = $ax->appendChild($xml->createElement("attachment"));
						$at->appendChild($xml->createElement("name", htmlspecialchars(utf8_encode($attachment->attachment_name))));
						$at->appendChild($xml->createElement("name_short", htmlspecialchars(utf8_encode($attachment->attachment_name_short))));
						$at->appendChild($xml->createElement("document_list_id", htmlspecialchars(utf8_encode($attachment->attachment_id))));
					}
				} 
				else 
				{
					$dx->appendChild($xml->createElement($key, htmlspecialchars(utf8_encode($value))));
				}
			}			
		}	
		
		return $xml;		
		
	}
	
	public function Get_Document_XML($document_list_id)
	{
		$data = ECash::getTransport()->Get_Data();
		
		$xml  = new DomDocument("1.0", "UTF-8");
		$xml->formatOutput = true;
		$dx = $xml->appendChild($xml->createElement("document"));
		
		if(isset($data->document_list[$document_list_id])) 
		{
			$doc = $data->document_list[$document_list_id];	

			foreach ($doc as $key => $value ) 
			{
				$dx->appendChild($xml->createElement($key, htmlspecialchars(utf8_encode($value))));
			}			
		}	
		
		return $xml;
		
	}
	public function Get_Header()
	{
		$js = new Form(ECASH_WWW_DIR.'js/document_management.js');
		$js2 = new Form(ECASH_WWW_DIR.'js/prototype-1.5.1.1.js');
		$js3 = new Form(ECASH_WWW_DIR.'js/json.js');
		return parent::Get_Header() . '<script type="text/javascript">' . $js2->As_String() . $js3->As_String() . '</script>' . $js->As_String();
	}	
	public function Get_Body_Tags()
	{
		$data = ECash::getTransport()->Get_Data();

		switch ($data->view)
		{
			case 'email_footers' :
				return "onLoad=\"javascript:get_last_settings();\"";
		}

		return "";
	}


}
