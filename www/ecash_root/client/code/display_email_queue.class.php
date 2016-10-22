<?php

require_once(CLIENT_CODE_DIR . "display_parent.abst.php");
require_once(CLIENT_CODE_DIR . "display_utility.class.php");
require_once(COMMON_LIB_DIR . "dropdown_dates.1.php");
require_once(CUSTOMER_LIB.'list_available_criteria_types.php');
require_once("dropdown.1.generic.php");

/**
 * Displays email documents from the queue.
 *
 */
class Display_View extends Display_Parent
{
	private $display_layers;
	private $display_modes;
	private $layout_map;

	public function __construct(ECash_Transport $transport, $module_name, $mode)
	{
	   parent::__construct($transport, $module_name, $mode);

		$this->data->previous_module = "";
		$this->data->current_module = $module_name;
		$this->data->current_mode = $mode;

	}

	public function Get_Header()
	{
		include_once(WWW_DIR . "include_js.php");
		$header = include_js() . "
                   <link rel=\"stylesheet\" href=\"js/calendar/calendar-dp.css\">
		          ";

		return $header;
	}

	public function Get_Body_Tags()
	{
		return "";
	}

	public function Get_Module_HTML()
	{
		$this->data->mode = $this->mode;
// DEBUG FOR CONDOR BUG
//if (empty($this->data->email_document->latest_dispatch)) {
//print '<h2>Encountered missing email header from condor in ' . __METHOD__ . ' at ' . __FILE__ . ' line ' . __LINE__ . '</h2><pre>';
//print_r($this->data->email_document);
//print '</pre>';
//}
		$this->data->queue = $this->data->email_queue_record->queue_name;
		$this->data->email_subject = $this->data->email_document->subject;
		$this->data->response_subject = 'Re: ' . ( empty($this->data->email_document->subject) 
                                                 ? 'Your Inquiry' : $this->data->email_document->subject);
		$this->data->email_message = $this->data->email_document->data;
		$this->data->response_message = $this->Build_Response_Message(
																	  $this->data->account, 
																	  $this->data->email_document->data
																	 );
		if (isset($this->data->email_response_footer))
		{ 
			$this->data->email_response_footer = str_replace(array("\r", "\n"), array('', '\n'), $this->data->email_response_footer); // for javascript
		}
		
			
		$this->data->email_attachments = $this->Build_Attached_Documents($this->data->email_document->attached_data);
		
		
		
		$this->data->associated_account_link = $this->Build_Account_Link($this->data->account);
		$this->data->other_queue = ($this->data->current_module == 'collections' ? 'servicing_email_queue' : 'collections_email_queue');
		$this->data->display_other_queue = ($this->data->current_module == 'collections') ? 'Servicing' : 'Collections';
		$this->data->email_received = $this->data->email_document->latest_dispatch->dispatch_date;
		$this->data->email_from = $this->data->email_document->latest_dispatch->sender;
		$this->archive_id = (isset($this->data->archive_id) ? $this->data->archive_id : $this->data->email_queue_record->archive_id);
		$this->data->quick_search_results = $this->Build_Quick_Search_Results();
		if (isset($this->data->suggested_applications)) $this->data->suggested_applications = $this->Build_Suggestion_List($this->data->suggested_applications);
		$this->data->archive_id = $this->data->email_queue_record->archive_id;
		$this->data->application_id = $this->data->email_queue_record->application_id;
		$this->data->criteria_type_1_drop = $this->Build_Dropdown('criteria_type_1', list_available_criteria_types());
		$this->data->search_deliminator_1_drop = 
			'<select name="search_deliminator_1" id="search_deliminator_1">
			<option id="is_1" value="is">is</option>
			<option id="starts_with_1" value="starts_with">starts with</option>
			<option id="contains_1" value="contains">contains</option>
			</select>';
		$this->data->search_criteria_1 = (isset($this->data->search_criteria_1) ? $this->data->search_criteria_1 : '');
		$this->data->quick_search_message = $this->Get_Quick_Search_Message();
		if (isset($this->data->email_responses_fields['responses'])) 
			$this->data->response_js_array = $this->Build_Response_Array( $this->data->email_responses_fields['responses'], $this->data->tokens);
		$this->data->attach_documents = $this->Build_Attachment_Documents(eCash_Document::Get_Document_List(ECash::getServer(),"send"));
		$this->data->schedule_date = $this->Build_Date_Dropdown();
		$this->data->dd_schedule_time = $this->Build_Time_Dropdown_Options();
		$this->data->alert_message = $this->Get_Alert_Message();
		$this->data->msg_click_label = DisplayMessage::get(array('queue', 'associate account'));
		$this->data->msg_followup_schedule = DisplayMessage::get(array('javascript', 'info', 'schedule followups 30 min in future'));
		$html = $this->Get_HTML_Template();

		if ($this->data->queue == 'manager')
		{
			$this->data->queue_button_label = 'Servicing';
			$this->data->queue_button_action = 'servicing';
		}
		else
		{
			$this->data->queue_button_label = 'Manager';
			$this->data->queue_button_action = 'manager';
		}

		return Display_Utility::Token_Replace($html, (array)$this->data);
	}

	/**
	 * Returns HTML of the appropriate template
	 *
	 * @return string The template HTML
	 */
	private function Get_HTML_Template()
	{
		switch (TRUE)
		{
			case ($this->data->action == 'respond_to_email'):
				if( file_exists(CUSTOMER_LIB ."/view/email_respond.html"))
				{
					return file_get_contents(CUSTOMER_LIB . "/view/email_respond.html");
				}
				else
				{
					return file_get_contents(CLIENT_VIEW_DIR . "email_respond.html");
				}
			break;

			case ($this->data->email_queue_record->application_id > 0):
				if( file_exists(CUSTOMER_LIB ."/view/email_associated.html"))
				{
					return file_get_contents(CUSTOMER_LIB . "/view/email_associated.html");
				}
				else
				{
					return file_get_contents(CLIENT_VIEW_DIR . "email_associated.html");
				}
				break;

			default:
				if( file_exists(CUSTOMER_LIB ."/view/email_unassociated.html"))
				{
					return file_get_contents(CUSTOMER_LIB . "/view/email_unassociated.html");
				}
				else
				{
					return file_get_contents(CLIENT_VIEW_DIR . "email_unassociated.html");
				}
				break;
		}
	}

	/**
	 * Returns HTML account link
	 *
	 * @return string Account link
	 */
	private function Build_Account_Link($account)
	{
		if ( !is_object($account) )
			return '';

		$ret_val = '<a href="/?action=show_applicant'
				 . '&application_id=' . $account->application_id
				 . '&show_back_button=yes'
				 . '&show_email_archive_id=' . $this->data->email_queue_record->archive_id
				 . '&flux_capacitor=' . rand(1, 10000000) . '">' . $account->application_id . '</a> '
				 . ucwords($account->name_first) . ' '
				 . ucwords($account->name_last)
				 ;

		return $ret_val;
	}

	/**
	 * Returns HTML rows for the application suggestions
	 *
	 * @return string HTML rows
	 */
	private function Build_Suggestion_List($suggestions)
	{
		if ( !is_array($suggestions) || count($suggestions) == 0)
			return '<tr><td class="align_left_alt" style="color: #666666;"><i>none</i></td></tr>';

		$ret_val = '';

		for($x=0; $x<count($suggestions); $x++)
		{
			$label = $suggestions[$x]->application_id . ' '
				   . ucwords($suggestions[$x]->name_first) . ' '
				   . ucwords($suggestions[$x]->name_last)
				   ;

			$ret_val .= '<tr><td class="align_left_alt">'
					 . '<a href="/?mode=' . $this->data->current_mode
					 . '&action=associate_with_application'
					 . '&archive_id=' . $this->data->email_queue_record->archive_id
					 . '&application_id=' . $suggestions[$x]->application_id
					 . '&flux_capacitor=' . rand(1, 10000000) . '">' . $label . '</a>'
					 . ' (' . ucwords($suggestions[$x]->status) . ')' 
					 . '</td></tr>'
					 ;
		}

		return $ret_val;
	}

	/**
	 * Returns response message formatted as per the spec...
	 * 
	 * Header at the top, followed by the original message, followed by an
	 * empty response area (3 lines), then the footer.
	 *
	 * @param array $results array of result objects
	 * @return string HTML rows
	 */
	private function Build_Quick_Search_Results()
	{
		switch (TRUE)
		{
			case ( !empty($this->data->search_message) ):
				$this->data->search_results = array();

			case ( $this->data->action != 'email_queue_quick_search' ):
				return '<tr><td class="align_left" style="color: #666666;">&nbsp;</td></tr>';

			case ( is_array($data->application_list) && count($data->application_list) == 0):
				return '<tr><td class="align_left_alt" style="color: #666666;"><i>none</i></td></tr>';

			case ($this->data->action == 'email_queue_quick_search' && isset($this->data->application_id) ):
				$label = $this->data->application_id . ' '
					   . ucwords($this->data->name_first) . ' '
					   . ucwords($this->data->name_last)
					   ;
				$ret_val = '<tr><td class="align_left_alt">'
						 . '<a href="/?mode=' . $this->data->current_mode
						 . '&action=associate_with_application'
						 . '&archive_id=' . $this->data->archive_id
						 . '&application_id=' . $this->data->application_id
						 . '&flux_capacitor=' . rand(1, 10000000) . '">' . $label . '</a> '
						 . '('  . $this->data->status_long . ')'
						 . '</td></tr>'
						 ;
				return $ret_val;

			case ($this->data->action == 'email_queue_quick_search' && isset($this->data->search_results) ):
				$ret_val = '';
				foreach($this->data->search_results as $application)
				{
					$label = $application->application_id . ' '
						   . ucwords($application->name_first) . ' '
						   . ucwords($application->name_last)
						   ;

					$ret_val .= '<tr><td class="align_left_alt">'
							 . '<a href="/?mode=' . $this->data->current_mode
							 . '&action=associate_with_application'
							 . '&archive_id=' . $this->data->archive_id
							 . '&application_id=' . $application->application_id
							 . '&flux_capacitor=' . rand(1, 10000000) . '">' . $label . '</a> '
						     . '('  . $application->application_status . ')'
							 . '</td></tr>'
							 ;
				}
				return $ret_val;
		}
	}

	/**
	 * Returns response message formatted as per the spec...
	 * 
	 * Header at the top, followed by the original message, followed by an
	 * empty response area (3 lines), then the footer.
	 *
	 * @param object $account The account data
	 * @param string $message The email body|data
	 * @return string The template HTML
	 */
	private function Build_Response_Message($account, $message)
	{
		$ret_val = "";
		/*
		$ret_val = "Dear " . ucwords($account->name_first) 
				 . " " . ucwords($account->name_last) . ",\n\n"
				 . $message . "\n"
				 ;
		*/
		$ret_val =  "\n\n\n" . $message . "\n\n";
		return $ret_val;
	}

	/**
	 * Returns dropdown html with the specified name and keyvals
	 *
	 * @param string $name The name and id label for the select tag
	 * @param array $keyvals The options as Return_Value=>Display_Value
	 * @return string The HTML for the select tag
	 */
	private function Build_Dropdown($name, $keyvals)
	{
		$drop = new Dropdown(
							array("name" 		=> $name,
				  				  "unselected" 	=> FALSE,
				  				  "attrs" 		=> array("id" => $name),
				  				  "keyvals" 	=> $keyvals
								 )
							);
		if (isset($this->data->{$name})) $drop->setSelected($this->data->{$name});

		return $drop->display(TRUE);
	}

	/**
	 * Returns time dropdown options for every half hour of the day (options only)
	 *
	 * @return string The HTML for the time dropdown options
	 */
	private function Build_Time_Dropdown_Options()
	{
		$dd = '';
		for ($x=0; $x<24; $x++)
		{
			$hour = sprintf('%02d', $x);
			$dd .= "<option value=\"$hour:00\"> $hour:00 </option>\n";
			$dd .= "<option value=\"$hour:30\"> $hour:30 </option>\n";
		}

		return $dd;
	}

	/**
	 * Returns HTML rows for documents attached to the incoming email
	 *
	 * @param $attached_data The attached_data from the document object
	 * @return string The HTML rows for incoming email attachments
	 */
	private function Build_Attached_Documents($attached_data)
	{
		if(empty($attached_data)) 
		{
			return '<tr><td class="align_left_alt" style="color: #666666;"><i>none</i></td></tr>';
		}
		else
		{
			ob_start();
			foreach($attached_data as $key => $attachment) { 
				$attachment_name = 	empty($attachment->uri) ? "Attachment_$key" : $attachment->uri;
			?>
			
				<tr>
					<td class="align_left_alt">
					<a href="/?action=show_attachment&archive_id=<?=$this->data->email_queue_record->archive_id?>&part_id=<?=$attachment->part_id?>">
						<?= ($attachment_name == 'NULL') ? "Attachment_$key" : $attachment->uri?></a> 
				</td>
				</tr>
			
			<? } 
			return ob_get_clean();
		}
		
		return $ret_val;
	}

	/**
	 * Returns dropdown html for the day, month, and year, all set to today
	 *
	 * @return string The HTML for the select tags
	 */
	private function Build_Date_Dropdown()
	{
		$dob_drop = new Dropdown_Dates();
		$dob_drop->Set_Prefix("schedule_");
		$dob_drop->Set_Day(date('d'));
		$dob_drop->Set_Month(date('m'));
		$dob_drop->Set_Year(date('Y'));

		return $dob_drop->Fetch_Drop_All();
	}

	/**
	 * Returns the specified documents as HTML rows with checkboxes
	 *
	 * @param array $documents The documents to be returned
	 * @return string The specified documents in HTML rows
	 */
	private function Build_Attachment_Documents($documents)
	{
		$html = '';
		if (is_array($documents) )
		{
			foreach ($documents as $id => $document)
			{
				if ($document->only_receivable == 'no' 
					&& $document->document_api == 'condor'
					&& $document->active       == 'active')
				{
					$row_class = ($row_class == 'align_left_alt' ? 'align_left' : 'align_left_alt');
					$html .= '<tr><td class="' . $row_class . '"><a target="_blank" href="/document_preview.php?application_id=' 
						  . $this->data->email_queue_record->application_id . '&document_id=' . 
$id . '">' . $document->name
						  . '</a></td><td width=50" class="' . $row_class . '">'
					      . '<input type="checkbox" name="attachment[]" value="' . $document->name . '"></td></tr>';
				}
			}
		}
		if ('' == $html)
		{
			$html .= '<tr><td class="align_left_alt"><i>none</i></td></tr>';
		}
		return $html;    
	}

	/**
	 * Returns a string formatted as a json array with wrapped/indented text
	 *
	 * @param array $documents The documents to be returned
	 * @param object|array $tokens object returned by eCash_Document_DeliveryAPI_Condor::Map_Data()
	 * @return string The specified documents in HTML rows
	 */
	private function Build_Response_Array($responses, $tokens)
	{
		if ( !is_array($responses) || !is_array($tokens) ) return '';

		for ($x=0; $x<count($responses); $x++)
		{
			//$responses[$x]['text'] = "    " . str_replace("\n", "\n    ", wordwrap($responses[$x]['text'], 58) );
			//$responses[$x]['text'] = wordwrap($responses[$x]['text'], 58);
			$responses[$x]['text'] = str_replace("\r", "\\r", $responses[$x]['text']);
			$responses[$x]['text'] = str_replace("\n", "\\n", $responses[$x]['text']);
		}

		$str = json_encode($responses);

		return Display_Utility::Token_Replace($str, $tokens);
	}

	/**
	 * Returns the appropriate quick search message
	 *
	 * @return string The quick search message
	 */
	private function Get_Quick_Search_Message()
	{
		if(!empty($this->data->search_message))
		{
			return $this->data->search_message . '<br />';
		}
		elseif(isset($this->data->search_results))
		{
			return 'Your search returned ' . count($this->data->search_results) . ' results.<br />';
		}
		else
		{
			return '';
		}
	}

	/**
	 * Returns a javascript alert command if 'alert_message' is set.
	 *
	 * @return string Javascript alert command, or empty string if no alert is
	 * called for
	 */
	private function Get_Alert_Message()
	{
		if( !isset($this->data->alert_message) ) return '';

		return 'alert("' . $this->data->alert_message . '")';
	}

}
