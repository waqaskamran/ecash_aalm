<?PHP

//*** START ACH UPLOAD CLASSES ***//
class Return_Parameters
{
	public function __construct()
	{
		$this->bc = 0;
		$this->dc = 0;
		$this->cc = 0;
		$this->ca = 0;
		$this->da = 0;
		$this->ac = 0;
		$this->fs = 0;
		$this->ic = 0;
		$this->ref = "";
		$this->er = 0;
	}

	public function __toString() {
		$str = "";

		$this->ref = "ECASH" . date("Ymd").".01";
		foreach ($this as $key => $value)
		{
			$str .= strtoupper($key);
			$str .= "={$value}&";
		}
		return $str;
	}

	public function Set_Values($header)
	{
		$this->bc = 1;
		if (trim($header->company_batch_header->company_entry_description) == "DEBIT")
		{
			$this->dc = count($header->ppd_entries);
		}
		else
		{
			$this->cc = count($header->ppd_entries);
		}

		$this->ac = count($header->ppd_addenda);
		$this->ca = $header->file_control->total_credit_dollar_amount;
		$this->da = $header->file_control->total_debit_dollar_amount;
	}
}

class DB_IO
{
	protected $exclusions = null;
	protected $type_map = array();

	public function Write()
	{
		$formal_list = "(";
		$values_list = "(";

		foreach($this as $key => $value)
		{
			if(preg_match('/exclusions|type_map/', $key) == 1) continue;
			if($this->exclusions != null)
			if(preg_match($this->exclusions, $key) == 1)
			continue;
			if ($value == null) continue;
			$formal_list .= "{$key},";

			switch($this->type_map[$key])
			{
				case "int":
				case "decimal":
				$values_list .= "{$value},";
				break;

				case "date":
				case "time":
				default:
				$value = $_SESSION['SQL']->Escape_String($value);
				$values_list .= "'{$value}',";
				break;
			}
		}

		$formal_list = rtrim($formal_list, ",");
		$values_list = rtrim($values_list, ",");
		$formal_list .= ")";
		$values_list .= ")";

		$query = "INSERT INTO ". strtolower(get_class($this)). " ${formal_list}";
		$query .= " VALUES {$values_list}";

		if($_SESSION['SQL'] != null) {
			$_SESSION['SQL']->Query($query);
		} else {
			$_SESSION['LOG']->Write("No SQL connection established!");
		}
		return($_SESSION['SQL']->Insert_Id());
	}
}

class Header extends DB_IO
{
	//DB ids
	public $batch_id;

	// Other elements
	public $company_batch_header = null;
	public $ppd_entries = array();
	public $ppd_addenda = array();
	public $batch_control = null;
	public $file_control = null;

	function __construct($str = null)
	{
		if ($str != null) $this->Parse_String($str);
		$this->exclusions = "/company_batch_header|ppd_entries|batch_control|file_control/";
		$this->type_map = array("file_creation_date" => "date",
		"file_creation_time" => "time");
		$this->batch_id = $_SESSION['BATCH_ID'];
	}

	private function Parse_String($str)
	{
		$this->record_type_code = substr($str, 0, 1);
		$this->priority_code = substr($str, 1, 2);
		$this->immediate_destination = substr($str, 3, 10);
		$this->immediate_origin = substr($str, 13, 10);
		$this->file_creation_date = "20".substr($str, 23, 2)."-".substr($str,25,2)."-".substr($str,27,2) ;
		$this->file_creation_time = substr($str, 29, 2).":".substr($str, 31, 2);
		$this->file_id_modifier = substr($str, 33, 1);
		$this->record_size = substr($str, 34, 3);
		$this->blocking_factor = substr($str, 37, 2);
		$this->format_code = substr($str, 39, 1);
		$this->immediate_destination_name = substr($str, 40, 23);
		$this->immediate_origin_name = substr($str, 63, 23);
		$this->reference_code = substr($str, 86, 8);
	}

	public function Parse_Batch($str)
	{
		$lines = split("\n", $str);
		$this->Parse_String(array_shift($lines));
		$this->company_batch_header = new Company_Batch_Header(array_shift($lines));
		while (substr($lines[0],0,1) == "6") $this->ppd_entries[] = new PPD_Entry(array_shift($lines));
		while (substr($lines[0],0,1) == "7") $this->ppd_addenda[] = new PPD_Addenda(array_shift($lines));
		$this->batch_control = new Batch_Control(array_shift($lines));
		$this->file_control = new File_Control(array_shift($lines));
	}

	public function Write()
	{
		$batch_id = parent::Write($_SESSION['SQL']);
		
		$this->company_batch_header->batch_id = $_SESSION['BATCH_ID'];
		$this->company_batch_header->Write($_SESSION['SQL']);
		foreach($this->ppd_entries as $ppd)
		{
			$ppd->batch_id;
			$ppd_id = $ppd->Write($_SESSION['SQL']);
		}

		foreach($this->ppd_addenda as $ppda)
		{
			$ppda->ppd_id = $ppd_id;
			$ppda->batch_id = $_SESSION['BATCH_ID'];
			$ppda->Write($_SESSION['SQL']);
		}

		$this->batch_control->batch_id = $_SESSION['BATCH_ID'];
		$this->batch_control->Write($_SESSION['SQL']);
		$this->file_control->batch_id = $_SESSION['BATCH_ID'];
		$this->file_control->Write($_SESSION['SQL']);
	}
}

class Company_Batch_Header extends DB_IO
{
	public $cbh_id;

	function __construct($str)
	{
		$this->cbh_id = null;
		$this->batch_id = $_SESSION['BATCH_ID'];
		$this->record_type_code = substr($str, 0 , 1);
		$this->service_class_code = substr($str, 1, 3);
		$this->company_name = substr($str, 4, 16);
		$this->company_discretionary_data = substr($str, 20, 20);
		$this->company_id = substr($str, 40, 10);
		$this->standard_entry_class_code = substr($str, 50, 3);
		$this->company_entry_description = substr($str, 53, 10);
		$_SESSION['AMOUNT_DIRECTION'] = trim(strtolower($this->company_entry_description));
		$_SESSION['LOG']->Write("Amount direction: {$_SESSION['AMOUNT_DIRECTION']}");
		$this->company_descriptive_date = substr($str, 63, 6);
		$this->effective_entry_date = "20".substr($str, 69, 2)."-".substr($str,71,2)."-".substr($str,73,2);
		$this->settlement_date = substr($str, 75, 3);
		$this->originator_status_code = substr($str, 78, 1);
		$this->originating_dfi_id = substr($str, 79, 8);
		$this->batch_number = substr($str, 87, 7);
		$this->type_map = array("company_descriptive_date" => "date",
		"effective_entry_date" => "date",
		"batch_id" => "int");
	}
}

class PPD_Entry extends DB_IO
{
	public $ppd_id;

	function __construct($str)
	{
		$this->ppd_id = null;
		$this->batch_id = $_SESSION['BATCH_ID'];
		$this->record_type_code = substr($str, 0, 1);
		$this->transaction_code = substr($str, 1, 2);
		$this->receiving_dfi_id = substr($str, 3, 8);
		$this->check_digit = substr($str, 11, 1);
		$this->dfi_account_number = substr($str, 12, 17);
		if ($_SESSION['AMOUNT_DIRECTION'] == 'credit') {
			$_SESSION['LOG']->Write("Credit parsing");
			$this->credit_amount = number_format(intval(substr($str, 29, 10))/100,2,'.','');
			$this->debit_amount = 0.00;
		} else {
			$_SESSION['LOG']->Write("debit parsing");
			$this->debit_amount = number_format(intval(substr($str, 29, 10))/100,2,'.','');
			$this->credit_amount = 0.00;
		}
		$this->individual_id_num = substr($str, 39, 15);
		$this->individual_name = substr($str, 54, 22);
		$this->discretionary_date = substr($str, 76, 2);
		$this->addenda_record_indicator = substr($str, 78, 1);
		$this->trace_number = substr($str, 79, 15);
		$this->type_map = array("debit_amount" => "decimal", "credit_amount" => "decimal",
					"batch_id" => "int");
	}

	function Verify_Check_Digit()
	{
		$weights = array(3,7,1,3,7,1,3,7);
		$digits = sscanf($this->receiving_dfi_id, "%1d%1d%1d%1d%1d%1d%1d%1d");
		$total = 0;
		foreach($digits as $idx => $digit)
		{
			$total += $digit * $weights[$idx];
		}
		$derived = 10 - ($total % 10);
		return ($derived == intval($dfi_record->check_digit));
	}
}

class PPD_Addenda extends DB_IO
{
	public $ppd_addenda_id;

	function __construct($str)
	{
		$this->ppd_addenda_id = null;
		$this->record_type_code = substr($str, 0, 1);
		$this->transaction_code = substr($str, 1, 2);
		$this->payment_related_info = substr($str, 3, 80);
		$this->addenda_seq_no = substr($str, 83, 4);
		$this->entry_detail_seq_no = substr($str, 87, 7);
		$this->type_map = array("batch_id" => "int",
		"ppd_id" => "int");
	}

}
class Batch_Control extends DB_IO
{
	public $bc_id;

	function __construct($str)
	{
		$this->bc_id = null;
		$this->record_type_code = substr($str, 0, 1);
		$this->service_class_code = substr($str, 1, 3);
		$this->entry_addenda_count = substr($str, 4, 6);
		$this->entry_hash = substr($str, 10, 10);
		$this->total_debit_dollar_amount = number_format(intval(substr($str, 20, 12))/100,2,'.','');
		$this->total_credit_dollar_amount = number_format(intval(substr($str, 32, 12))/100,2,'.','');
		$this->company_identification = substr($str, 44, 10);
		$this->message_authentication_code = substr($str, 54, 19);
		$this->reserves = substr($str, 73, 6);
		$this->originating_dft_identification = substr($str, 79, 8);
		$this->batch_number = substr($str, 87, 7);
		$this->type_map = array("batch_id" => "int",
		"total_debit_dollar_amount" => "decimal",
		"total_credit_dollar_amount" => "decimal");

	}

}

class File_Control extends DB_IO
{
	public $fc_id;

	function __construct($str)
	{
		$this->fc_id = null;
		$this->record_type_code = substr($str, 0, 1);
		$this->batch_count = substr($str, 1, 6);
		$this->block_count = substr($str, 7, 6);
		$this->entry_addenda_count = substr($str, 13, 8);
		$this->entry_hash = substr($str, 21, 10);
		$this->total_debit_dollar_amount = number_format(intval(substr($str, 31, 12))/100,2,'.','');
		$this->total_credit_dollar_amount = number_format(intval(substr($str, 43, 12))/100,2,'.','');
		$this->reserved = substr($str, 55, 39);
		$this->type_map = array("batch_id" => "int",
		"total_debit_dollar_amount" => "decimal",
		"total_credit_dollar_amount" => "decimal");
	}
}
//*** END ACH UPLOAD CLASSES ***//
?>
