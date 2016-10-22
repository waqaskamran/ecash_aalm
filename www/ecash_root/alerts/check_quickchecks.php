<?php

try 
{
	if($argc > 2) $notify_list = $argv[2];
	else $notify_list = "rebel75cell@gmail.com, brian.gillingham@gmail.com, randy.klepetko@sbcglobal.net";
	require_once(dirname(__FILE__)."/../www/config.php");
	Set_Company_Constants($_SERVER['argv'][1]);
	require_once(SERVER_MODULE_DIR."collections/quick_checks.class.php");

	$server = new Server();
	$server->Set_Company($_SERVER['argv'][1]);
	$yesterday = date('Y-m-d', strtotime("-1 day", time()));
	$query = "
		SELECT ecld_return_id
		FROM ecld_return
		WHERE date_created BETWEEN '$yesterday 00:00:00' AND '$yesterday 23:59:59';
	";
	$st = ECash::getMasterDb()->query($query);

	$results = array();
	while ($row = $st->fetch(PDO::FETCH_OBJ))
	{
		$qc = new Quick_Checks($server);
		$results = array_merge($results, $qc->Retroactive_Pull_Unmatched_Returns($row->ecld_return_id));
	}
	if (count($results)) 
	{
		Email_Report($notify_list, "QC Return Exception Report", $results);
	}
} 

catch(Exception $e) 
{
	echo "State 11 error: Unknown error occurred.\n";
	exit(3);
}

function Email_Report($recipients, $body, $results)
{
	require_once(LIB_DIR . '/CsvFormat.class.php');

	$csv = CsvFormat::getFromArray(array(
		'Application ID',
		'ABA',
		'Account',
		'ECLD ID',
		'Code'));

	foreach ($results as $result)
	{
		$csv .= CsvFormat::getFromArray(array(
			$result['app_id'],
			$result['aba'],
			$result['account'],
			$result['ecld_id'],
			$result['return_code']));
	}

	$attachments = array(
		array(
			'method' => 'ATTACH',
			'filename' => 'alert_errors.csv',
			'mime_type' => 'text/plain',
			'file_data' => gzcompress($csv),
			'file_data_length' => strlen($csv)));

	require_once(LIB_DIR . '/Mail.class.php');
	return eCash_Mail::sendExceptionMessage($recipients, $body, null, array(), $attachments);
}

class Server
{
	public  $timer;
	public	$log;
	public  $company_id;
	public  $system_id;
	public  $company;
	public  $agent_id;
	private	$db;

	public function __construct()
	{
		$this->db = ECash::getMasterDb();
		$this->agent_id   = Fetch_Agent_ID_by_Login($this->db, 'ecash');
	}

	public function Set_Company($company_name)
	{
		$this->company = $company_name;
		$this->company_id = Fetch_Company_ID_by_Name($this->db, $company_name);
		$_SESSION['company'] = $this->company;
		$_SESSION['company_id'] = $this->company_id;

		$this->Set_Agent($this->agent_id);

		$sys_name = ECash::getConfig()->SYSTEM_NAME;
		$this->system_id = Get_System_ID_By_Name($this->db, $sys_name);
		Set_Company_Constants($this->company);
	}

	public function Set_Log ($log)
	{
		$this->log = $log;
		$this->timer = new Timer($this->log);
	}

	public function Fetch_Company_IDs()
	{
		$sql = "
			SELECT company_id, name_short
			FROM company
			WHERE active_status = 'active'
		";
		$st = $this->db->query($sql);

		$companies = array();

		while($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$companies[$row->company_id] = $row->name_short;
		}

		return $companies;
	}

	public function Set_Agent($agent_id)
	{
		$_SESSION["agent_id"] = $agent_id;
	}
}

?>
