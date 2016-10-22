<?PHP
/**
	ECASH - LBS                          
	@version: 1.0.0 , 2006-01-11 , PHP5/MySQL5
	@author: Nick White
*/

// Make sure we don't quit too early -- MAC
set_time_limit(0);

require_once("./code/config_lbs.1.php");


$cfg = new Config_Lbs_1($sql,$log,'live');
$process = new Process_Lbs($sql,$log);

$_SESSION['SQL'] = $sql;
$_SESSION['LOG'] = $log;

$log->Write("Starting logger");

// If sdate is set the request is for a return file.
if(isset($_POST['report']))
{ 
	if ($_POST['report'] == 'COR') $type = 'correction';
	else $type = "return";
}
else
{	
	$type = 'upload';
}


// Determine what incoming request we have and what to do with it.
switch($type)
{
 case 'upload':
	 $result = Process_Lbs::upload_ach($_POST,$_FILES);
	 break;
	 
 case 'return':
	 $result = Process_Lbs::request_return($_POST);
	 break;
	 
 case 'correction': // Not implemented as of 01/23/2006
	 $_SESSION['LOG']->Write("Received unimplemented corrections request");
	 $result = "";
	 break;
 default:
	 $_SESSION['LOG']->Write("FATAL ERROR::Undetermined Incoming Request.");
	 $result = "";
	 break;
}
echo $result;

class Process_Lbs
{
	/**
	 * @desc Sets the MySQL and log pointers for process use
	 * @return bool
	 * @param sql
	 * @param log
	*/
	public function __construct()
	{
		return TRUE;
	}

	/**
	 * @desc Build the initial batch id to be referenced in all tables
	 * @return bool
	*/
	public function Create_Batch_Id()
	{
		$query = "INSERT INTO batch_reference VALUES('',NOW(),NOW(),0,'".$_POST['source']."')";
		$_SESSION['SQL']->Query($query,ecash_lbs);
		$_SESSION['BATCH_ID'] = $_SESSION['SQL']->Insert_Id();
		$_SESSION['LOG']->Write('Batch Id Set: '.$_SESSION['BATCH_ID']);

		return TRUE;
	}
	
	/**
	 * @desc Read,parse and store the uploaded file in its appropriate tables
	 * @return bool
	 * @param post
	 * @param file
	*/
	public function upload_ach($post,$file)
	{
		Process_Lbs::Create_Batch_Id();
		
		$login = $post['login'];
		$pwd = $post['pass'];		

		$_SESSION['LOG']->Write("Login found: {$login}, with password: {$pwd}\n");

		$contents = file_get_contents($file['filename']['tmp_name']);
		$header = new Header();
		try
		{
			$header->Parse_Batch($contents);
		}
		catch(Exception $e)
		{
			$_SESSION['LOG']->Write($e->getTraceAsString());
			Process_Lbs::upload_return('FAIL');
		}

		$rp = new Return_Parameters();
		$rp->Set_Values($header);

		if($rp->ec == 0) { $header->Write($loopback); }		
		
		$_SESSION['FINAL_DATA'] = $rp;

		return $_SESSION['FINAL_DATA'];
	}
	
	/**
	 * @desc 
	 * @return bool
	 * @param post
	*/
	public function upload_return($status='PASS')
	{
		switch($status)
		{
			case 'PASS':
			$_SESSION['FINAL_DATA'] = 'BC=5&DC=2&CC=19&CA=5242.82&DA=5242.82&AC=0&FS=3760&IC=0&REF=TSTA0613.03&ER=0&';
			break;
			
			case 'FAIL':
			default:
			$_SESSION['FINAL_DATA'] = 'BC=0&DC=0&CC=0&CA=0&DA=0&AC=0&FS=9117&IC=0&ER=-99&';
			break;	
		}
		
		return TRUE;
	}
	
	/**
	 * @desc Handles trafficing the requests to the correct functions
	 * @return bool
	 * @param post
	*/
	public function request_return($post)
	{
		$_SESSION['LOG']->Write("Received request return: ". print_r($post, true));
		$return = new Build_Return($post['sdate'], $post['compid']);
		$return->Get_Batch_List();
		if ($_SESSION['FULL_REC_COUNT'] == 0) {
			$_SESSION['LOG']->Write("No records found for date {$post['sdate']}");
			return "";
		}
		$return->Build_Rules();
		$return->Build_Data();
		$str = $return->Return_File();
		
		return $str;
	}
}

class Build_Return
{
	/**
	 * @desc Instantiate and set params
	 * @return bool
	 * @param sdate
	*/
	public function __construct($sdate, $cid)
	{
		$this->start_date = $sdate;
		$this->company_id = $cid ;
		$this->rollback = REC_ROLLBACK;
		
		$this->fatal_return_code = 
			array(
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

		$this->all_return_codes = array_merge($this->fatal_return_code,
						      $this->nonfatal_return_code);
		
		return TRUE;
	}
	
	
	/**
	 * @desc Determine if an override of the transaction status has been set in config_lbs.1.php, if so handle it
	 * @return response

	*/
	public function Transaction_Status($name)
	{
		global $fatal_iso_list;
		global $non_fatal_iso_list;

		$list = $this->all_return_codes;
		if (isset($fatal_iso_list[$name])) {
			$_SESSION['LOG']->Write("(F) ISOLATION: ". $name);
			$list = $this->fatal_return_code;
		} elseif (isset($non_fatal_iso_list[$name])) {
			$_SESSION['LOG']->Write("(NF) ISOLATION: ". $name);
			$list = $this->nonfatal_return_code;
		}
		
		$code = array_rand($list);
		$response = '"'.$list[$code].'","'.$code.'",';
		
		return $response;
	}
	
	/**
	 * @desc Determine if an override of the number of records to return has been set in config_lbs.1.php, if so handle it
	 * @return bool 
	*/
	public function Build_Rules()
	{
		global $positive_iso_list;
		global $negative_iso_list;

		// Iterate through once, looking to see if the record is in the iso lists.
		// I know it makes it slower than shit, but oh well. Making it faster requires
		// a rewrite, as usual.
		$_SESSION['FINAL_DATA'] = array();
		for ($j = 0; $j < $_SESSION['FULL_REC_COUNT']; $j++) {
			$name = trim($_SESSION['BATCH_DATA'][$j]->individual_name);
			if (isset($negative_iso_list[$name])) {
				// Remove this person - they're protected
				$_SESSION['LOG']->Write("(-)ISOLATION: {$name}"); 
				unset($_SESSION['BATCH_DATA'][$j]);
			} elseif (isset($positive_iso_list[$name])) {
				// Put them in the final data NOW
				$_SESSION['LOG']->Write("(+)ISOLATION: {$name}"); 
				$_SESSION['FINAL_DATA'][] = $_SESSION['BATCH_DATA'][$j];
				// Remove them b/c they're in.
				unset($_SESSION['BATCH_DATA'][$j]);
			} 
		}

		// Determine the number of records to return or limit
		if(REC_RETURNS > 0)
		{ 
			$_SESSION['RULES']['REC_RETURNS'] = REC_RETURNS; 
		}
		elseif(REC_PERCENTAGE < 100)
		{
			$_SESSION['RULES']['REC_RETURNS'] = round($_SESSION['FULL_REC_COUNT'] * REC_PERCENTAGE / 100);
		}
		else
		{
			$_SESSION['RULES']['REC_RETURNS'] = $_SESSION['FULL_REC_COUNT'];
		}		
				

		// Return the percentage, including the records that are
		// in the positive isolation list
		for($i=count($_SESSION['FINAL_DATA']);$i< $_SESSION['RULES']['REC_RETURNS'];$i++)
		{	
			if (isset($_SESSION['BATCH_DATA'][$i]))
			    $_SESSION['FINAL_DATA'][] = $_SESSION['BATCH_DATA'][$i];
		}
					
		return TRUE;	
	}
	
	/**
	 * @desc Build the object of data that will be returned based on the rules 
	 * @return bool
	*/
	public function Build_Data()
	{
		$return_file = '';
		foreach($_SESSION['FINAL_DATA'] AS $record=>$data)
		{
			// Get the return code for this row
			$status = $this->Transaction_Status(trim($data->individual_name));
			
			// Mark the record complete
			$this->Clean_Up($data->batch_id);	
					
			// Build the row
$return_file .= '"1","30631","2276554","Intercept Corporation","'.$data->company_name.'","'.$data->individual_id_num.'","006","877-675-5772","","'.$data->company_name.'","'.$data->company_name.'","'.$data->company_discretionary_data.'","F","'.$data->receiving_dfi_id.'","'.$data->dfi_account_number.'","","'.$data->standard_entry_class_code.'","'.$data->effective_entry_date.'","'.$data->individual_id_num.'","'.$data->individual_name.'","'.$data->debit_amount.'","'.$data->credit_amount.'","'.$status.'"Checking","'.$data->transaction_code.'","T","","'.$data->trace_number.'","'.$data->trace_number.'","F"'."\n";

		}
		
		$_SESSION['return_file'] = $return_file;
		
		return TRUE;
	}
	
	
	/**
	 * @desc Set the batch to complete so that we do not pull the same people more than once 
	 * @return bool
	 * @param batch_id
	*/
	public function Clean_Up($batch_id)
	{	
		$query = "Update batch_reference SET complete = 1 WHERE batch_id = ".$batch_id."";
		$_SESSION['SQL']->Query($query);
		
		$_SESSION['LOG']->Write('Record: '.$batch_id.' has been marked for completion');
		
		return TRUE;
	}
	
	/**
	 * @desc Create a full list of possible return records
	 * @return bool
	*/
	public function Get_Batch_List()
	{
		$query = "SELECT * FROM batch_control
		JOIN batch_reference USING(batch_id)
		JOIN company_batch_header USING(batch_id)
		JOIN file_control USING(batch_id)
		JOIN header USING(batch_id)
		JOIN ppd_entry USING(batch_id)
		WHERE batch_reference.created_date >= DATE_SUB('".$this->start_date."', INTERVAL ".$this->rollback." DAY)
                AND batch_reference.source = '".$_POST['source']."'
		AND batch_reference.created_date <= DATE_ADD('".$this->start_date."', INTERVAL 1 DAY)
		AND batch_reference.complete = 0
";

		$result = $_SESSION['SQL']->Query($query);
	
		while($row = $result->Fetch_Object_Row())
		{
			$_SESSION['BATCH_DATA'][] = $row;
		}
		
		$_SESSION['FULL_REC_COUNT'] = count($_SESSION['BATCH_DATA']);
		
		return TRUE;
	}	
	
	/**
	 * @desc This pushes the return file to the screen.
	 * @return bool
	*/
	public function Return_File()
	{
		return $_SESSION['return_file'];	       
	}
}
?>