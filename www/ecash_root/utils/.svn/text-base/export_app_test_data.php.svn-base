<?php
/* This script does mutiple things.

	1: It will back up the demo Database
	2: It will backup Loanactions from IMPACT which are non CLK Specirfic
	3: It will create data for a skel instance of LDB
	4: Gather a random amount of apps in all the status for Test Data
	5: Steps 2-4 with one command
	
	TBD:
	Scramble Data
	
	I know this is messy but we can clean this up later 
	
	COMMANDS:
		export_app_test_data.php APPS
			: Gathers Apps and Dumps from Live Slave

		export_app_test_data.php LOAN_ACTIONS
			: Gathers LOAN_ACTIONS from impact rc database
						
		export_app_test_data.php SKEL
			: Gathers Skeleton from Live Slave
			
		export_app_test_data.php DEMO_BACK
			: I'm bringin Demo Back.. yea (save demo db to a file)
			
		export_app_test_data.php COMPLETE_SYSTEM
			: Runs SKEL,LOAN_ACTIONS,APPS, in that order
	Ray Lopez
	
	
*/
define ("COMMON_LIB_DIR",'/virtualhosts/lib/');
define ("COMMON_LIB_ALT_DIR", '/virtualhosts/lib5/');
define ("BASE_DIR", '/virtualhosts/ecash3.0/demo/');
define ("LIB_DIR", BASE_DIR . 'lib/');
define ("SQL_LIB_DIR", BASE_DIR . 'sql/lib/');
define ("SERVER_CODE_DIR", BASE_DIR . 'server/code/');
define ("CLIENT_CODE_DIR", BASE_DIR . 'client/code/');
define ("CUSTOMER_LIB",BASE_DIR."customer_lib/clk/");

require_once(COMMON_LIB_DIR."mysqli.1.php");
require_once(BASE_DIR . "server/code/loan_data.class.php");
require_once(COMMON_LIB_DIR . 'pay_date_calc.3.php');
require_once(COMMON_LIB_DIR . 'holiday.1.php');
require_once(SQL_LIB_DIR. 'util.func.php');
require_once(LIB_DIR.'common_functions.php');

$mysqlldump_bin = "/usr/bin/mysqldump";
$mysqllclien_bin = "/usr/bin/mysql";
$exec_php = "/usr/local/bin/php";
$sql_export_ldb = "/tmp/export_ldb_skel.sql";
$sql_export_demo = "/tmp/export_ldb_demo.sql";
$sql_backup_demo = "backup_ldb_demo.sql";

$DB_SETTINGS["LIVE_SLAVE"] = array("SERV" => "reader.ecash3clk.ept.tss", "PORT" => "3306", "USER" => "ecash", "PASS" => "ugd2vRjv", "DB" => "ldb");
$DB_SETTINGS["RC_IMPACT"] = array("SERV" => "db101.clkonline.com", "PORT" => "3313", "USER" => "ecash", "PASS" => "1234five", "DB" => "ldb_impact");
$DB_SETTINGS["RC_CLK"] = array("SERV" => "db101.clkonline.com", "PORT" => "3308", "USER" => "test_ecash", "PASS" => "3cash", "DB" => "ldb");
$DB_SETTINGS["DEMO"] = array("SERV" => "db1.clkonline.com", "PORT" => "3306", "USER" => "test_ecash","PASS" => "3cash",  "DB" => "ldb_demo");


$mysql_tables["ACCESS"] = array('access_group', 'acl', 'agent', 'agent_access_group', 'section', 'access_group_control_option');
$mysql_tables["RULES"] = array('rule_set', 'rule_component', 'rule_component_parm', 'rule_set_component', 'rule_set_component_parm_value');
$mysql_tables["MISC"] = array('ach_return_code', 'application_status', 'bureau bureau_inquiry_type', 'bureau_login', 'company','company_property', 'contact_outcome',
						'contact_type' ,'contact_type_outcome', 'control_option', 'ecash_module', 'event_transaction', 
						'event_type ', 'flag_type holiday', 'loan_type', 'site state', 'system', 'time_zone', 'transaction_type');
				
$mysql_tables["APPS"] =	 array("application","personal_reference","event_schedule",	"comment",	"transaction_register",	"transaction_ledger",
						"agent_affiliation","ach",	"application_audit","contact_history",	"loan_action_history",	"ecld",	"loan_snapshot",
						//"document"
							);		
										
$mysql_tables["RC_SPEC"] = array('document_list');	
// A very simplistic version of the Server class needed by Fund_Accounts.
// This was stolen from the ecash_engine and modified slightly.
class Server
{
        public  $log;
        public  $company_id;
        public  $company;
        public  $agent_id;
        private $mysqli;
        public  $auto_email;
        public  $timer;

        public function __construct($log, $mysqli, $cid)
        {
                $this->log = $log;
                $this->mysqli = $mysqli;
                $this->company_id = $cid;
                //$this->timer = new Timer($this->log);

                $query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
                        SELECT name_short as company from company where company_id = $cid";

                $q_obj = $this->mysqli->Query($query);
                $row = $q_obj->Fetch_Object_Row();
                $this->company = $row->company;
        }

        public function MySQLi()
        {
                return $this->mysqli;
        }

        public function Auto_Email()
        {
                if (!isset($this->auto_email))
                {
                        $this->auto_email = new Automated_Emailer($this->log, $this->MySQLi(),
                                                                  $this->ole_send, $this->company_id, $this->company);
                }
                return $this->auto_email;
        }
}

function Fetch_Active_Apps($mysqli)
{
        $query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
                                SELECT  application_id
                                FROM    application
                                WHERE   application_status_id = 20
                        ";
        $result = $mysqli->Query($query);
        while($row = $result->Fetch_Object_Row())
        {
                $apps[] = $row->application_id;
        }

        return $apps;
}

function Fetch_Prefund_Apps($mysqli)
{
/*
 8                         Confirmed            dequeued        verification  applicant
 9                         Confirmed            queued          verification  applicant
 10                        Approved             queued          underwriting  applicant
 11                        Approved             dequeued        underwriting  applicant
*/

        $query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
                                SELECT  application_id, date_first_payment, last_paydate
                                FROM    application
                                WHERE   application_status_id IN (8,9,10,11)
                        ";
        $result = $mysqli->Query($query);
        while($row = $result->Fetch_Object_Row())
        {
                $apps[] = $row;
        }

        return $apps;
}

function Get_Date_First_Payment($pdc, $app)
{
        $data = Get_Transactional_Data($app->application_id);
        $dates = $pdc->Calculate_Pay_Dates($data->info->paydate_model, $data->info->model,
                                                                  $data->info->direct_deposit, 48, "01/01/06");

        $ten_days = strtotime("+10 days", time());

        // Just grab the first date that is in the future.
        foreach($dates as $date)
        {
                if(strtotime($date) > $ten_days)
                {
                        return $date;
                        break;
                }
        }
}

function Get_Last_Paydate($pdc, $app)
{
        $data = Get_Transactional_Data($app->application_id);

        $dates = $pdc->Calculate_Pay_Dates($data->info->paydate_model, $data->info->model,
                                                                  $data->info->direct_deposit, 24, "01/01/06");

		$last_paydate = $dates[0]; // Just to be safe.

		// Just grab the first date that is in the future.
        foreach($dates as $date)
        {
                if(strtotime($date) > time())
                {
                        return $last_paydate;
                }
                $last_paydate = $date;
        }
}

function Update_Demo_App($mysqli, $application_id, $last_paydate, $date_first_payment)
{
        $query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
                                UPDATE  application
                                SET     date_first_payment = '{$date_first_payment}',
                                        last_paydate = '{$last_paydate}'
                                WHERE   application_id = $application_id
                        ";
        $result = $mysqli->Query($query);

}

if($argv[1] == "APPS")
{

	$outputfile 	= "/tmp/export_ldb_apps.sql";
	
	$app_limits 	= 25;
	$mysql_serv		= $DB_SETTINGS["LIVE_SLAVE"]["SERV"];
	$mysql_port		= $DB_SETTINGS["LIVE_SLAVE"]["PORT"];
	$mysql_user		= $DB_SETTINGS["LIVE_SLAVE"]["USER"];
	$mysql_pass		= $DB_SETTINGS["LIVE_SLAVE"]["PASS"];
	$mysql_db		= $DB_SETTINGS["LIVE_SLAVE"]["DB"];
							
	$mysql_options	= array("--extended-insert ",
							"--no-create-info ",
							"--insert-ignore ",
							"--skip-triggers ",
							"-u {$mysql_user} ",
							"-p{$mysql_pass} ",
							"--host={$mysql_serv} ",
							"--port={$mysql_port} ",
							"--databases {$mysql_db} ");
							
	print("Connecting to Database:\n");
	$id = mysql_connect("{$mysql_serv}:{$mysql_port}",$mysql_user,$mysql_pass);
	mysql_select_db($mysql_db,$id);
	
	
	// Gather all the status
	print("Gathering Application Statuses:\n");
	$query_status = "select application_status_id,name from application_status where active_status = 'active' order by name";
	$result = mysql_query($query_status);
	while($row = mysql_fetch_object($result))
	{
		$statuses[$row->application_status_id] = $row->name;
	}
	
	$i=0;
	print("Gathering Application IDs:\n");
	foreach($statuses as $key => $desc)
	{	
		$query_apps = "select 
								application_id 
						from application 
						join company as com using (company_id)
						where application_status_id = $key 
						and date_created > ".date("Ymd000000",strtotime("-30 Days"))."
						and com.name_short = 'ufc'
						order by date_created DESC limit {$app_limits}";
		$result = mysql_query($query_apps);
		if(mysql_num_rows($result) > 0)
		{	
			print("Status: [$key][$desc] Found: " . mysql_num_rows($result)."\n");
			while($row = mysql_fetch_object($result))
				$appids[] = $row->application_id;
		}
		
	
	}
	print("Total App Count: ".count($appids)."\n");
	print("Dump Database Data to File: {$outputfile}\n");
	$dump_query 	= "application_id in (".implode(",",$appids).")";
	$exec_str = "{$mysqlldump_bin}  ".implode(" ",$mysql_options)."   --tables ".implode(" ",$mysql_tables["APPS"])." --where=\"$dump_query\" >> {$sql_export_ldb}";
	exec($exec_str);
	print("Complete..\n");
} 
else if($argv[1] == "LOAN_ACTIONS")
{
	// We grab from the RC Impact DB becasue there are no CLK Refs
	$outputfile 	= "/tmp/export_ldb_skel.sql";
	
	$mysql_serv		= $DB_SETTINGS["RC_IMPACT"]["SERV"];
	$mysql_port		= $DB_SETTINGS["RC_IMPACT"]["PORT"];
	$mysql_user		= $DB_SETTINGS["RC_IMPACT"]["USER"];
	$mysql_pass		= $DB_SETTINGS["RC_IMPACT"]["PASS"];
	$mysql_db		= $DB_SETTINGS["RC_IMPACT"]["DB"];
	
	$mysql_tables 	= array("loan_actions");	
							
	$mysql_options	= array("--extended-insert",
							"--no-create-info",
							"--insert-ignore",
							"-u {$mysql_user}",
							"-p{$mysql_pass}",
							"--host={$mysql_serv}",
							"--port={$mysql_port}",
							"--databases {$mysql_db}");

    print("Dump Database Data to File: {$outputfile}\n");							
	$exec_str = "{$mysqlldump_bin}  ".implode(" ",$mysql_options)."   --tables ".implode(" ",$mysql_tables)."  >> {$sql_export_ldb}";
	exec($exec_str);
	print("Complete..\n");
		
}
else if($argv[1] == "SKEL")
{
	// This will give us a general 
	$outputfile 	= "/tmp/export_ldb_skel.sql";

	foreach ($mysql_tables as $key => $tables)
	{
		if($key != "APPS")
		{
			if(in_array($key,array("RC_SPEC","ACCESS")))
			{
				$mysql_serv		= $DB_SETTINGS["RC_CLK"]["SERV"];
				$mysql_port		= $DB_SETTINGS["RC_CLK"]["PORT"];
				$mysql_user		= $DB_SETTINGS["RC_CLK"]["USER"];
				$mysql_pass		= $DB_SETTINGS["RC_CLK"]["PASS"];
				$mysql_db		= $DB_SETTINGS["RC_CLK"]["DB"];				
			}
			else 
			{
				$mysql_serv		= $DB_SETTINGS["LIVE_SLAVE"]["SERV"];
				$mysql_port		= $DB_SETTINGS["LIVE_SLAVE"]["PORT"];
				$mysql_user		= $DB_SETTINGS["LIVE_SLAVE"]["USER"];
				$mysql_pass		= $DB_SETTINGS["LIVE_SLAVE"]["PASS"];
				$mysql_db		= $DB_SETTINGS["LIVE_SLAVE"]["DB"];				
			}
			$mysql_options	= array("--extended-insert",
									"--no-create-info",
									"--insert-ignore",
									"-u {$mysql_user}",
									"-p{$mysql_pass}",
									"--host={$mysql_serv}",
									"--port={$mysql_port}",
									"--databases {$mysql_db}");			
	    	print("Dump Database Data ($key) to File: {$outputfile}\n");							
			$exec_str = "{$mysqlldump_bin}  ".implode(" ",$mysql_options)."   --tables ".implode(" ",$tables)."  >> {$sql_export_ldb}";
			
			exec($exec_str);		
		}
	}
	print("Complete..\n");
		
}
else if($argv[1] == "DEMO_BACK")
{
	//backup DEMO DATA
	
	$mysql_serv		= $DB_SETTINGS["DEMO"]["SERV"];
	$mysql_port		= $DB_SETTINGS["DEMO"]["PORT"];
	$mysql_user		= $DB_SETTINGS["DEMO"]["USER"];
	$mysql_pass		= $DB_SETTINGS["DEMO"]["PASS"];
	$mysql_db		= $DB_SETTINGS["DEMO"]["DB"];
	

							
	$mysql_options	= array("--extended-insert",
							"--no-create-info",
							"-u {$mysql_user}",
							"-p{$mysql_pass}",
							"--host={$mysql_serv}",
							"--port={$mysql_port}",
							"--databases {$mysql_db}");
							

    print("Dump Database Data to File: {$outputfile}\n");							
	$exec_str = "{$mysqlldump_bin}  ".implode(" ",$mysql_options)."   > {$sql_export_demo}";
	exec($exec_str);
	print("Complete..\n");
			
}
else if($argv[1] == "COMPLETE")
{
	// This will rerun this script to process all the entries
	$mysql_serv		= $DB_SETTINGS["DEMO"]["SERV"];
	$mysql_port		= $DB_SETTINGS["DEMO"]["PORT"];
	$mysql_user		= $DB_SETTINGS["DEMO"]["USER"];
	$mysql_pass		= $DB_SETTINGS["DEMO"]["PASS"];
	$mysql_db		= $DB_SETTINGS["DEMO"]["DB"];
		
	if(is_file($exec_php) && is_file($mysqllclien_bin) && is_file($mysqlldump_bin))
	{

		$sub_cmd = array("RESET_DEMO","SKEL","LOAN_ACTIONS","APPS","RAND_DEMO","DEMOIZE_DEMO");
		foreach($sub_cmd as $cmd_action)
		{
			@unlink($sql_export_ldb);
			print("Running: $cmd_action\n");
			exec("$exec_php {$argv[0]} $cmd_action");
			if(is_file($sql_export_ldb))
			{
				$sqlscript = file_get_contents($sql_export_ldb);
				$sqlscript = str_replace("UFC ","",$sqlscript);
				$sqlscript = str_replace("UFC ","",$sqlscript);
				@unlink($sql_export_ldb);
				file_put_contents($sql_export_ldb,$sqlscript);
				print("Importing: $cmd_action\n");				
				$sqlcmd = "{$mysqllclien_bin} --user={$mysql_user} --password={$mysql_pass}  -h {$mysql_serv} --port={$mysql_port} {$mysql_db} < {$sql_export_ldb}";
				exec($sqlcmd);				
			}
		}
		$sqlcmd = "{$mysqllclien_bin} --user={$mysql_user} --password={$mysql_pass}  -h {$mysql_serv} --port={$mysql_port} {$mysql_db} < ../sql/updates/queues_migration.sql";
		exec($sqlcmd);
		
	}
	else 
	{
		print("Missing one or more MYSQL Client,Mysql Dump, PHP BIN.\n");
	}
}
else if($argv[1] == "RAND_DEMO")
{
	$mysql_serv		= $DB_SETTINGS["DEMO"]["SERV"];
	$mysql_port		= $DB_SETTINGS["DEMO"]["PORT"];
	$mysql_user		= $DB_SETTINGS["DEMO"]["USER"];
	$mysql_pass		= $DB_SETTINGS["DEMO"]["PASS"];
	$mysql_db		= $DB_SETTINGS["DEMO"]["DB"];

	$first_names 	= array("Aggie","George","Alf","Erin","Eppie","Brad","Portia","Pip","Prissy","Christy",
							"Zeph" ,"Nivek","Francene" ,"Franklin" ,"Jeannine","Joby","Kaley","Oneida","Baldric",
							"Ilean","Collyn","Shaun","Avery","Kaylee" ,"Jean","Rowanne" ,"Matt","Shelagh","Joselyn",
							"Normina","Sindy","Olive","Hall","Lou","Cornelius","Sal","Jonah","Xenia","Maggie",
							"Carlisle","Jade","Gethsemane","Haidee","Lauraine","Karaugh","Godfrey","Lindsey",
							"Daryl","Eldreda","Josslyn","Hugo","Agnes","Cheyanne","Terry","Christian","Leatrice",
							"Christabel","Janey","Dale","Suzan","Mckayla","Greta","Brock","Eugene","Jinny","Jemima",
							"Keeley","Deforest","Margaux","Louis","Kayleen","Tamsen","Aretha","Betty","Madonna",
							"Julius","Abner","Pollie","Drew","Xylia","Spring","Melville","Edie","Kaylin","Jayda",
							"Kameron", "Forest","Timmy","Abraham","Chyna","Dwayne","Melyssa","Daren" ,"Essie",
							"Buck","Lyndon" ,"Noelle","Rheanna","Alphonzo","Harmonie");  
	
	$last_names 	= array("Cypret","Hallauer","Mercer","Mingle","Schere","Focell","Earhart","Monroe","Baldwin",
							"Butterfill","Weinstein","Munshower","Sidower","Meyers","White","Jerome","Sutton","Dean",
							"Rader","Craig","Ammons","Dennis","Ashmore","Toyley","Ling","Dryfus","Foster","Burris",
							"Hardie","Sloan","Stough","Tennant","Wentzel","Bennett","Wheeler","Sauter","Otis",
							"Millhouse","Perkins","Law","Bratton","Tavoularis","Millard","Quinn","Johnston","Schmiel",
							"Jesse","Monahan","Harrold","Hays","Linton","Pierce","Gaskins","Teagarden","Raub","Pearson",
							"Shaw","Elsas","Wilson","Smith","Philips","Giesen","Kepplinger","Schuck","Echard","Bauerle",
							"Drennan","Paynter","Bastion","Fiscina","Weldy","Barrett","Wilkinson","Pearsall","Leonard",
							"Weidemann","Ream","Nabholz","Yonkie","Courtney","Moffat","Moberly","Hills","Haverrman",
							"Walker","Brinigh","Cowher","Sullivan","Downing","Zalack","Burkett","Bashline","Hatherly",
							"Mcmullen","Bryant","Richards","Albright","Langston","Buzzard","Allshouse");
							
	$relations = array('mother','daughter','father','friend','borther','sister','cowoker');
	$street_sign = array("CT.","DR.","RD."."BLVD.","ST.");
	
	print("Connecting to Database:\n");
	$id = mysql_connect("{$mysql_serv}:{$mysql_port}",$mysql_user,$mysql_pass);
	mysql_select_db($mysql_db,$id);
	
	//Gather Application IDS;
	$query = "select application_id from application order by application_id";
	$result = mysql_query($query);
	$app_ids = array();
	while($row = mysql_fetch_object($result))
	{
		$app_ids[] = $row->application_id;
	}	
	
	
	for($i=0; $i<count($app_ids); $i++)
	{
		$appid = $app_ids[$i];
		print("Processing: $appid ");
		$phone_home_num = rand(100,999).rand(100,999).rand(1000,9999);
		$phone_cell_num = rand(100,999).rand(100,999).rand(1000,9999);
		$phone_fax_num	= rand(100,999).rand(100,999).rand(1000,9999);
		$phone_work_num	= rand(100,999).rand(100,999).rand(1000,9999);
		$account_num	= rand(100000000,999999999);
		$aba_num		= rand(100000000,999999999);
		$ssn_num 		= rand(100,999).rand(10,99).rand(1000,9999);
		$fname 			= $first_names[array_rand($first_names)];
		$lname 			= $last_names[array_rand($last_names)];
		$zipcode 		= rand(10000,99999);
		$state_id 		= rand(1000000000,9999999999); 		
		$street_address =  rand(1000,9999)." ".$last_names[array_rand($last_names)]." ".$street_sign[array_rand($street_sign)];
		
		// Random Application Data
		$query = "update application set
					bank_aba = '{$aba_num}',
					bank_account = '{$account_num}',
					ssn = '{$ssn_num}',
					legal_id_number = '{$state_id}',
					email = 'ecash3drive@gmail.com',
					name_last = '{$lname}',
					name_first = '{$fname}',
					street = '{$street_address}',
					phone_home = '{$phone_home_num}',
					phone_cell = '{$phone_cell_num}',
					phone_fax = '{$phone_fax_num}',
					phone_work = '{$phone_work_num}'
					where application_id = 	$appid";
		$result = mysql_query($query);
		print(".");
		
		//Randomize Referenes
		$query = "select personal_reference_id from personal_reference where application_id =$appid";
		$result = mysql_query($query);
		$per_ids = array();
		while($row = mysql_fetch_object($result))
		{
			$per_ids[] = $row->personal_reference_id;
		}	

		for($x=0; $x<count($per_ids); $x++)
		{
			$perid = $per_ids[$i];
			$phone_num 		= rand(100,999).rand(100,999).rand(1000,9999);
			$fname 			= $first_names[array_rand($first_names)];
			$lname 			= $last_names[array_rand($last_names)];	
			$relation		= $relations[array_rand($relations)];
			$query = "update personal_reference_id set 
						name_full = '{$fname} {$lname}',
						phone_home ='{$phone_num}',
						relationship = '$relation'
						where personal_reference_id = {$perid}";
			$result = mysql_query($query);
			print(".");
		}
		print("\n");
	}
}
else if($argv[1] == "DEMOIZE_DEMO")
{
	include("/virtualhosts/lib/security.6.php");
	$mysql_serv		= $DB_SETTINGS["DEMO"]["SERV"];
	$mysql_port		= $DB_SETTINGS["DEMO"]["PORT"];
	$mysql_user		= $DB_SETTINGS["DEMO"]["USER"];
	$mysql_pass		= $DB_SETTINGS["DEMO"]["PASS"];
	$mysql_db		= $DB_SETTINGS["DEMO"]["DB"];	
	
	print("Connecting to Database:\n");
	$id = mysql_connect("{$mysql_serv}:{$mysql_port}",$mysql_user,$mysql_pass);
	mysql_select_db($mysql_db,$id);
	
	$query = "delete from company where company_id != 3";
	$result = mysql_query($query);
	
	//Changed the existing data
	$query = "update company set name='Demo Company',name_short='dem' where company_id = 3";
	$result = mysql_query($query);
	
	// get a lot of agents
	$query = "delete from agent where login not in ('ecash_support') order by rand() limit 1000";
	$result = mysql_query($query);	
	
	
}
else if($argv[1] == "RESET_DEMO")
{
	$mysql_serv		= $DB_SETTINGS["DEMO"]["SERV"];
	$mysql_port		= $DB_SETTINGS["DEMO"]["PORT"];
	$mysql_user		= $DB_SETTINGS["DEMO"]["USER"];
	$mysql_pass		= $DB_SETTINGS["DEMO"]["PASS"];
	$mysql_db		= $DB_SETTINGS["DEMO"]["DB"];	
	$mysql_tables["EXTRAS"] = array('queue','queue_history');
	
	print("Connecting to Database:\n");
	$id = mysql_connect("{$mysql_serv}:{$mysql_port}",$mysql_user,$mysql_pass);
	mysql_select_db($mysql_db,$id);
	foreach ($mysql_tables as $key => $tables)
	{
		foreach ($tables as $table)
		{
			$query = "delete from $table";
			$result = mysql_query($query);
		}
	}
}
else if($argv[1] == "BACKUP_DEMO")
{

	$mysql_serv		= $DB_SETTINGS["DEMO"]["SERV"];
	$mysql_port		= $DB_SETTINGS["DEMO"]["PORT"];
	$mysql_user		= $DB_SETTINGS["DEMO"]["USER"];
	$mysql_pass		= $DB_SETTINGS["DEMO"]["PASS"];
	$mysql_db		= $DB_SETTINGS["DEMO"]["DB"];	
	$mysql_options	= array("--extended-insert ",
							"--no-create-info ",
							"--insert-ignore ",
							"--skip-triggers ",
							"-u {$mysql_user} ",
							"-p{$mysql_pass} ",
							"--host={$mysql_serv} ",
							"--port={$mysql_port} ",
							"--databases {$mysql_db} ");	
	@unlink($sql_backup_demo);
	foreach ($mysql_tables as $key => $tables)
	{
	
    	print("Dump Database Data ($key) to File: {$sql_backup_demo}\n");							
		$exec_str = "{$mysqlldump_bin}  ".implode(" ",$mysql_options)."   --tables ".implode(" ",$tables)."  >> {$sql_backup_demo}";
		exec($exec_str);		

	}
}
else if($argv[1] == "RESTORE_DEMO")
{

	$mysql_serv		= $DB_SETTINGS["DEMO"]["SERV"];
	$mysql_port		= $DB_SETTINGS["DEMO"]["PORT"];
	$mysql_user		= $DB_SETTINGS["DEMO"]["USER"];
	$mysql_pass		= $DB_SETTINGS["DEMO"]["PASS"];
	$mysql_db		= $DB_SETTINGS["DEMO"]["DB"];	

	print("Resetting Demo.\n");
	exec("$exec_php {$argv[0]} RESET_DEMO");							
	print("Importing Demo.\n");
	$exec_str = "{$mysqllclien_bin} --user={$mysql_user} --password={$mysql_pass}  -h {$mysql_serv} --port={$mysql_port} {$mysql_db} < {$sql_backup_demo}";
	exec($exec_str);							
	$sqlcmd = "{$mysqllclien_bin} --user={$mysql_user} --password={$mysql_pass}  -h {$mysql_serv} --port={$mysql_port} {$mysql_db} < ../sql/updates/queues_migration.sql";
	exec($sqlcmd);			
}
else if($argv[1] == "FIX_PAYDATES")
{
	$mysql_serv		= $DB_SETTINGS["DEMO"]["SERV"];
	$mysql_port		= $DB_SETTINGS["DEMO"]["PORT"];
	$mysql_user		= $DB_SETTINGS["DEMO"]["USER"];
	$mysql_pass		= $DB_SETTINGS["DEMO"]["PASS"];
	$mysql_db		= $DB_SETTINGS["DEMO"]["DB"];

	$log = new Applog('ecash3.0', 5000000, 20);	
	$mysqli = new MySQLi_1($mysql_serv, $mysql_user, $mysql_pass, $mysql_db, $mysql_port);
	$GLOBALS['get_mysqli()']['DB_'] = $mysqli;	
	
	$server = new Server($log, $mysqli, 3);
	$ld = new Loan_Data($server);
	
	
	$holidays   = Fetch_Holiday_List();
	$pdc 		= new Pay_Date_Calc_3($holidays);
	
	$apps = Fetch_Active_Apps($mysqli);
	
	echo "Processing Active Apps\n";
	$apps = Fetch_Active_Apps($mysqli);
	foreach($apps as $application_id)
	{
	        echo "Processing Application ID: $application_id .. ";
	        echo "Updating schedule .. ";
	        
	        try
	        {
	                $mysqli->Start_Transaction();
	                $ld->Update_Schedule($application_id);
	                echo " Finished!\n";
	                $mysqli->Commit();
	        }
	        catch (Exception $e)
	        {
	                echo "Update Error: n";
	                print_r($e);
	                $mysqli->Rollback();
	        }
	        
	}
	
	unset($apps);	
	
	echo "Processing Pre-Fund Apps\n";
	
	$apps = Fetch_Prefund_Apps($mysqli);
	
	foreach($apps as $app)
	{
	        // Get what would be the most recent last paydate
	        
	        $last_paydate = Get_Last_Paydate($pdc, $app);
	        
	        $date_first_payment = Get_Date_First_Payment($pdc, $app);
	
	        echo "App ID: {$app->application_id}, Last Paydate: {$last_paydate}, Date First Payment: {$date_first_payment}\n";
	        Update_Demo_App($mysqli, $app->application_id, $last_paydate, $date_first_payment);
			
	}
	

}
?>