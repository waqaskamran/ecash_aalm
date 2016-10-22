<?php
class Batch_Processor
{

public $start_date;
public $output_dir;
public $company_id;
public $achstruct;

public $immediate_destination_name;
public $immediate_origin_name;
public $company_entry_description;
public $all_return_codes;


	function __construct($start_date, $output_dir)
	{
		$this->start_date = $start_date;
		$this->output_dir = $output_dir;
		
		$this->fatal_return_code =	array(
		'R02'=>'Account closed',
		'R05'=>'Unauthorized',
		'R07'=>'Authorization revoked',
		'R08'=>'Payment stopped',
		'R10'=>'Not Authorized',
		'R16'=>'Account frozen',
	    'R29'=>'Corporate customer not authorized',
    	'R38'=>'Stop payment on source document',
      	'R51'=>'Item is ineligible',
      	'R52'=>'Stop payment');

		$this->nonfatal_return_code = array(
		'R01'=>'Insufficient funds',
		'R03'=>'Unable to locate account',
		'R04'=>'Invalid account number',
		'R06'=>'Returned per ODFI request',
		'R09'=>'Uncollected funds',
		'R11'=>'Check truncation',
		'R12'=>'Branch sold to another DFI',
		'R13'=>'RDFI no qualified',
		'R14'=>'Representative payee deceased',
		'R15'=>'Account holder deceased',
		'R17'=>'File record edit',
		'R18'=>'Improper effective entry date',
		'R19'=>'Amount field error',
		'R20'=>'Non-transaction account',
		'R21'=>'Invalid company ID',
		'R22'=>'Invalid individual ID',
		'R23'=>'Credit entry refused',
		'R24'=>'Duplicate entry',
		'R25'=>'Addenda error',
		'R26'=>'Mandatory field error',
		'R27'=>'Trace number error',
		'R28'=>'Routing number check digit error',
		'R30'=>'RDFI not a participant',
		'R31'=>'Permissible return entry',
		'R32'=>'RDFI non-settlement',
		'R33'=>'Return of XCK entry',
		'R34'=>'Limited participation DFI',
		'R35'=>'Return of improper debit entry',
		'R36'=>'Return of improper credit entry',
		'R37'=>'Soure document presented for payment',
		'R40'=>'Return of ENR entry',
		'R41'=>'Invalid transaction code',
		'R42'=>'Routing number error',
		'R43'=>'Invalid DFI account number',
		'R44'=>'Invalid individual ID',
		'R45'=>'Invalid individual name',
		'R46'=>'Invalid representative payee',
		'R47'=>'Duplicate enrollment',
		'R50'=>'State law affecting RCK',
		'R53'=>'Item and ACH entry presneted for payment',
		'R61'=>'Misrouted return',
		'R62'=>'Incorrect trace number',
		'R63'=>'Incorrect dollar amount',
		'R64'=>'Incorrect individual id',
		'R65'=>'Incorrect tansaction code',
		'R66'=>'Incorrect company id',
		'R67'=>'Duplicate return',
		'R68'=>'Untimely return',
		'R69'=>'Multiple errors');
		
		$this->achstruct =  array(
					      1 =>
					      array(	'record_type_code'				=> array( 1,  1, 'A', '1'),
							'priority_code'					=> array( 2,  2, 'A', '01'),
							'immediate_destination'			=> array( 4, 10, 'A', ' 091214847'),
							'immediate_origin'				=> array(14, 10, 'A'),
							'file_creation_date'			=> array(24,  6, 'A'),
							'file_creation_time'			=> array(30,  4, 'A'),
							'file_id_modifier'				=> array(34,  1, 'A'),
							'record_size'					=> array(35,  3, 'A', '094'),
							'blocking_factor'				=> array(38,  2, 'A', '10'),
							'format_code'					=> array(40,  1, 'A', '1'),
							'immediate_destination_name'	=> array(41, 23, 'A', 'Processor Name'),
							'immediate_origin_name'			=> array(64, 23, 'A'),
							'reference_code'				=> array(87,  8, 'A')
							),
					      5 =>
					      array(	'record_type_code'				=> array( 1,  1, 'A', '5'),
							'service_class_code'			=> array( 2,  3, 'A', '200'),
							'company_name'					=> array( 5, 16, 'A'),
							'company_discretionary_data'	=> array(21, 20, 'A'),
							'company_identification'		=> array(41, 10, 'A'),
							'standard_entry_class_code'		=> array(51,  3, 'A', 'PPD'),
							'company_entry_description'		=> array(54, 10, 'A'),
							'company_descriptive_date'		=> array(64,  6, 'A'),
							'effective_entry_date'			=> array(70,  6, 'A'),
							'settlement_date'				=> array(76,  3, 'A', ' '),
							'originator_status_code'		=> array(79,  1, 'A', '1'),
							'originating_dfi_identification'=> array(80,  8, 'A', '09121484'),
							'batch_number'					=> array(88,  7, 'N', 1)
							),
					      6 =>
					      array(	'record_type_code'				=> array( 1,  1, 'A', '6'),
							'transaction_code'				=> array( 2,  2, 'A'),
							'receiving_dfi_identification'	=> array( 4,  8, 'A'),
							'check_digit'					=> array(12,  1, 'A'),
							'dfi_acct_number'				=> array(13, 17, 'A'),
							'amount'						=> array(30, 10, 'N'),
							'individual_identification_no'	=> array(40, 15, 'A'),
							'individual_name'				=> array(55, 22, 'A'),
							'discretionary_data'			=> array(77,  2, 'A', ' '),
							'addenda_record_indicator'		=> array(79,  1, 'A', '0'),
							'trace_number_prefix'			=> array(80,  8, 'A', '09121484'),
							'trace_number_suffix'			=> array(88,  7, 'N')
							),
					      8 =>
					      array(	'record_type_code'				=> array( 1,  1, 'A', '8'),
							'service_class_code'			=> array( 2,  3, 'A', '200'),
							'entry_addenda_count'			=> array( 5,  6, 'N'),
							'entry_hash'					=> array(11, 10, 'N'),
							'total_debit_entry_amount'		=> array(21, 12, 'N'),
							'total_credit_entry_amount'		=> array(33, 12, 'N'),
							'company_identification'		=> array(45, 10, 'A'),
							'message_authentication_code'	=> array(55, 19, 'A', ' '),
							'reserved'						=> array(74,  6, 'A', ' '),
							'originating_dfi_identification'=> array(80,  8, 'A', '09121484'),
							'batch_number'					=> array(88,  7, 'N', 1)
							),
					      9 =>
					      array(	'record_type_code'				=> array( 1,  1, 'A', '9'),
							'batch_count'					=> array( 2,  6, 'N', 1),
							'block_count'					=> array( 8,  6, 'N'),
							'entry_addenda_count'			=> array(14,  8, 'N'),
							'entry_hash'					=> array(22, 10, 'N'),
							'total_debit_entry_amount'		=> array(32, 12, 'N'),
							'total_credit_entry_amount'		=> array(44, 12, 'N'),
							'reserved'						=> array(56, 39, 'A', ' ')
							)
					      );
		$this->all_return_codes = array_merge($this->fatal_return_code, $this->nonfatal_return_code);
	}

	/**
	 * @desc Process the Batch Data and produce return files in specified output directory 
	 * @return bool
	*/
	public function Process_Data($batch_files)
	{
		$count = 0;
		foreach($batch_files as $company_name => $files)
		{
			$return_file = NULL;
			// Check for company_specific return file
			switch($company_name)
			{
				case "cbnk":
					$acro = "CBN";
					break;
				case "jiffy":
					$acro = "JY2";
					break;
				case "mydy":
					$acro = "MDP";
					break;
				case "micr":
					$acro = "OML";
					break;
				case "pcal":
					$acro = "PYA";
					break;
				
			}
			$returns_filename = $this->output_dir . '/' . $acro . date("md", strtotime($this->start_date)) . "A.CSV";
			
			foreach($files as $file)
			{
				$processed = FALSE;
				if($records = $this->Get_Return_Records($file))
				{
					foreach($records AS $data)
					{
						//TODO: fix this check
						if($data['record_type_code'] == 6 && $data['transaction_code'] == 27)
						{
							// The offset entry needs to be ignored, and it can be easily
							// found by looking for this field to be empty.
							if(empty($data['individual_identification_no']))
							{
								continue;
							}
								
							// Get the return code for this row
							if($add_return = round(rand(0,10)%2))
							{
								//Select random return code & reason
								$reason_codes = array_keys($this->all_return_codes);
								$sel_code = $reason_codes[floor(rand(0, count($reason_codes)))];
								$reason = $this->all_return_codes[$sel_code];
							}

							// Build the row
//							$vals = array(
//								1,			//Location Key
//								30631,		//Company Key
//								2276554, 	//Entry Key
//								'ACH COMMERCE', //Pull from eCash_Config
//								$this->immediate_destination_name,	//Processor
//								$data->individual_id_num,	//ACH ID
//								006,		//PIN
//								'877-675-5772',	//Phone #
//								'',			//Fax #
//								$this->company_name,	//Company Name
//								$this->company_entry_description,	//Entry Description
//								$this->company_discretionary_data, 		//App. Discretionary data (blank)
//								'F', 		// Correction flag
//								$data['receiving_dfi_identification'], //ABA
//								$data['dfi_acct_number'],	//Acct. #
//								'', 		//Corrected Info
//								$this->standard_entry_class_code,	//SEC
//								$this->effective_entry_date,	//Effective Entry Date
//								$data['individual_identification_no'],	//ID #
//								$data['individual_name'],	//Name
//								$data['debit_amount'],	//Debit Amount
//								$data['credit_amount'],	//Credit Amount
//								$reason, 
//								$sel_code,
//								'Checking',	//Acct. Type
//								$data['transaction_code'],	//Trans Code
//								'T', 		//Return Flag
//								'',			//Discretionary Data
//								$data['trace_number_prefix'] . $data['trace_number_suffix'],
//								$data['trace_number_prefix'] . $data['trace_number_suffix'],
//								'F'
//								);
								
								//ACHCOMMERCE FORMAT
								$amt = ($data['debit_amount']) ? $data['debit_amount'] : $data['credit_amount'];
								$vals = array(
								1201417854,		//Company Merchant ID
								$this->company_name,	//Company Name
								$this->effective_entry_date,	//Effective Entry Date
								$data['transaction_code'],	//Trans Code
								$data['receiving_dfi_identification'], //ABA
								$data['dfi_acct_number'],	//Acct. #
								$amt, //Amount
								$data['individual_identification_no'],	//ACH ID#
								$data['individual_name'],	//Customer Name
								$sel_code, //Return Code
								'', //Correction Info
								$data['trace_number_prefix'] . $data['trace_number_suffix']
								);
							
							$output_row = NULL;
							if($add_return)
							{
								$output_row = '"' . implode('","', $vals) . '"' . "\n";
								$return_file .= $output_row;
								$processed = TRUE;
								$count++;
							}
						}
					}
					if($processed)
					{
						echo "*** " . date("Ymd H:i:s") . ": Processed -> $company_name [{$this->company_entry_description}]\n";
					}
				}
			}

			if(strlen($return_file) > 0)
			{
				$fp = fopen($returns_filename, 'a');
				$result = fwrite($fp, $return_file);
				fclose($fp);
			}
		}

		return $count;
	}
	
	/**
	 * @desc Pull in related batch files, parse and produce returns
	 * @return Array
	*/
	public function Get_Return_Records($filename)
	{
		if(stristr($filename, "return")) return FALSE; 
		$handle = fopen($filename, 'r');
		$contents = fread($handle, filesize($filename));
		fclose($handle);
		$lines = explode("\n", $contents);
		foreach($lines as $line)
		{
			if(!empty($line[0]))
			{
				$records[] = $this->Parse_Line($line[0], $line);
			}
		}
		
		return $records;
	}
	
	public function Parse_Line($record_type, $line)
	{
		$ret_array = array();
		$global_vals = array(
			'immediate_destination_name',
			'company_entry_description',
			'company_name',
			'standard_entry_class_code',
			'effective_entry_date',
			'company_discretionary_data'
			);
				
		foreach($this->achstruct[$record_type] as $column => $parameters)
		{
			$ret_array[$column] = trim(substr($line, $parameters[0]-1, $parameters[1]));
			//Assign any return-wide values
			if(in_array($column, $global_vals))
			{
				$this->{$column} = trim($ret_array[$column]);
			}
			//Do any special formatting
			if($column == 'amount')
			{
				$ret_array[trim(strtolower($this->company_entry_description)).'_amount'] = money_format('%i', $ret_array[$column]/100);
			}
			if($column == 'effective_entry_date')
			{
				$this->effective_entry_date = date("m/d/Y", strtotime($ret_array[$column]));
			}
			
		}
		return $ret_array;
	}
}


// Execution

if($argv[1] == "--help" || $argv[1] == "-h" || empty($argv[1]) || empty($argv[2]))
{
	echo "Usage: ".__FILE__." <deposits_dir> <output_dir> <start_date> (start_date = YYYYMMDD; Optional)\n\n";
}
else
{
	$deposits_directory = $argv[1];
	$output_dir = $argv[2];
	$start = ($argv[3]) ? $argv[3] : date('Ymd', strtotime("-1 day"));

	if($dh = opendir($deposits_directory))
	{
		echo "\n*** " . date("Ymd H:i:s") . ": Batch Processing Initiated.\n";
		$batch_files = array();
		while($file = readdir($dh))
		{
			$name_pieces = explode("_", $file);
			if($name_pieces[2] == $start.".txt" && ($name_pieces[1] == 'credit' || $name_pieces[1] == 'debit'))
			{
				$batch_files[$name_pieces[0]][] = $deposits_directory . "/" . $file;
			}
		}
		closedir($dh);
	}
	else
	{ 
		echo "Failed To Open Directory.";
	}
	
	// Start Date in the Batch Processor should be Today as that is what the returns script will look for.
	$BP = new Batch_Processor(date('Ymd'), $output_dir);
	if($return_count = $BP->Process_Data($batch_files))
	{
		echo "*** " . date("Ymd H:i:s") . ": Batch Processing Complete. ({$return_count} returns)\n";
	}
	else
	{
		echo "*** " . date("Ymd H:i:s") . ": Batch Processing Incomplete or No Returns created.\n";
	}
}

?>