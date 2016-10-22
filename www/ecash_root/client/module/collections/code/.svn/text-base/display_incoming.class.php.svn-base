<?php

require_once(LIB_DIR. "form.class.php");
require_once(CLIENT_CODE_DIR . "display_parent.abst.php");
require_once(COMMON_LIB_DIR . "dropdown_dates.1.php");
require_once(CUSTOMER_LIB."list_available_collection_companies.php");


class Display_View extends Display_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->module_name = $module_name;
		$this->transport = ECash::getTransport();
	}

	public function Get_Header()
	{
		include_once(WWW_DIR . "include_js.php");
		return include_js();
	}

	public function Get_Body_Tags()
	{
	}

	public function Get_Module_HTML()
	{
		// $fields = new stdClass();
		$fields = ECash::getTransport()->Get_Data();
		
		$fields->company_id = ECash::getTransport()->company_id;
		$fields->agent_id = ECash::getTransport()->agent_id;
		// $fields->ext_applications = 'There are ' . $temp['count'] . ' application(s) ready.';
		$fields->data_rows_concatenated = '';

		$fields->from_date = $this->Generate_Date_Dropdown( 'from_date_', $fields->from_date_year, $fields->from_date_month, $fields->from_date_day );
		$fields->to_date   = $this->Generate_Date_Dropdown( 'to_date_',
		                                                    $fields->to_date_year,
		                                                    $fields->to_date_month,
		                                                    $fields->to_date_day ); //mantis:5598

		$collection_companies = list_available_collection_companies();
		
		$fields->collection_companies = '';
		foreach ($collection_companies as $company_short => $company) {
			$fields->collection_companies .= '<option value="'.$company_short.'">'.$company."</option>\n";
		}
		
//				$fields->el_dumpo = "<pre>" . var_export($fields,true) . "</pre>";
		if (is_array($fields->inc_coll_data))
		{
			foreach( $fields->inc_coll_data as $row )
			{
				$fields->data_rows_concatenated .= $this->Get_Row_Html($row, ++$row_number);
			}
		}
		
		$form = new Form(CLIENT_MODULE_DIR.$this->module_name."/view/incoming_collections.html");
		
		return $form->As_String($fields);
	
	}

	public function Get_Row_Html( $row, $row_number )
	{
		$class = $row_number % 2 == 0 ? 'align_left' : 'align_left_alt';
	
		switch ($row->batch_status)
		{			
			case "received-partial":
			case "received-full":
				$piece = "<a href=\"?action=process_incoming_collections&incoming_collections_batch_id=$row->incoming_collections_batch_id\">Process</a>";
				break;
				
			case "failed":
			case "success":
			case "partial":
				$piece = " [ <a href=\"?action=success_report&batch_id=$row->incoming_collections_batch_id\">Success Report</a> ]  [ <a href=\"?action=exceptions_report&batch_id=$row->incoming_collections_batch_id\">Exceptions Report</a> ] ";
				break;
				
			case "in-progress":
			default:
				$piece = "";
		}
		
		$result = "
		<tr>
			<td class='$class'>$row->date_created</td>
			<td class='$class'>$row->file_name</td>
			<td class='$class'>$row->batch_status</td>
			<td class='$class'>$row->record_count</td>
			<td class='$class'>$row->success_count</td>
			<td class='$class'>$row->flagged_count</td>
			<td class='$class'>$piece</td>
		</tr>
		";

		return $result;
	
	}
	
	public function Generate_Date_Dropdown( $html_prefix, $year_selected=0, $month_selected=0, $day_selected=0 )
	{
		$date_drop = new Dropdown_Dates();

		$date_drop->Set_Prefix($html_prefix);
		
		$date_drop->Set_Day($day_selected > 0 ? $day_selected : date('d'));
		$date_drop->Set_Month($month_selected > 0 ? $month_selected : date('m'));
		$date_drop->Set_Year($year_selected > 0 ? $year_selected : date('Y'));

		return $date_drop->Fetch_Drop_All();
	}

	
}

?>
