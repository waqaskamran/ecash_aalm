<?php
define('DIR_LIB','/virtualhosts/lib');
define('DIR_LIB5','/virtualhosts/lib5');

ini_set('include_path',DIR_LIB5.':'.DIR_LIB.':'.ini_get('include_path'));
include_once(DIR_LIB . '/mysql.4.php');
include_once(DIR_LIB . '/pay_date_calc.2.php');
include_once('condor_client.php');


class Condor_Cron_Client extends Condor_Client{

	public function Preview_Docs($legal_document, $condor_content)
	{
		ob_start();
		include($legal_document);
		return ob_get_clean();
	}
	

}

class Condor_Legal_Doc  
{
	private $server;
	public $condor;
	public $legal_document;
	public $document;
	private $application_id;
	private $db_object;
	private $holiday_array;
	private $db_config;
	
	public function __construct($mode)
	{

		switch ($mode)
		{
			case "LIVE":
				DEFINE('CONDOR_SERVER' , 'prpc://internal.condor.3.edataserver.com/');
				$this->db_config["OLP"] = array("host" => "olpslave.internal.clkonline.com","port" => 3306, "user" => "sellingsource", "password" => "%selling\$_db", "db" => "olp");
				$this->db_config["CONDOR"]  = array("host" => "writer.condor.ept.tss","port" => 3306, "user" => "condor", "password" => "Nzt04g8a", "db" => "condor");
				$this->db_config["LDB"]  = array("host" => "reader.ecash3clk.ept.tss","port" => 3306, "user" => "ecash", "password" => "ugd2vRjv", "db" => "ldb");
				break;
			case "RC";
				DEFINE('CONDOR_SERVER' , 'prpc://condor.3.edataserver.com.gambit.tss:8080/');
				$this->db_config["OLP"] = array("host" => "rc: olpslave.clkonline.com","port" => 3306, "user" => "sellingsource", "password" => "%selling\$_db", "db" => "rc_olp");
				$this->db_config["CONDOR"] = array("host" => "db101.clkonline.com","port" => 3308, "user" => "condor", "password" => "Nzt04g8a", "db" => "condor");
				$this->db_config["LDB"] = array("host" => "db1.clkonline.com","port" => 3307, "user" => "ecash", "password" => "3cash", "db" => "ldb");
				break;
		}
		
		try 
		{
				
			if (isset($this->db_config["OLP"]['port'])) $this->db_config["OLP"]['host'] .= ':'.$this->db_config["OLP"]['port'];
			$this->db_object["OLP"] = new MySQL_4($this->db_config["OLP"]['host'], $this->db_config["OLP"]['user'], $this->db_config["OLP"]['password']);
			$this->db_object["OLP"]->Connect();
			
			
			if (isset($this->db_config["LDB"]['port'])) $this->db_config["LDB"]['host'] .= ':'.$this->db_config["LDB"]['port'];
			$this->db_object["LDB"] = new MySQL_4($this->db_config["LDB"]['host'], $this->db_config["LDB"]['user'], $this->db_config["LDB"]['password']);
			$this->db_object["LDB"]->Connect();
			
			
			if (isset($this->db_config["CONDOR"]['port'])) $this->db_config["CONDOR"]['host'] .= ':'.$this->db_config["CONDOR"]['port'];
			$this->db_object["CONDOR"] = new MySQL_4($this->db_config["CONDOR"]['host'], $this->db_config["CONDOR"]['user'], $this->db_config["CONDOR"]['password']);
			$this->db_object["CONDOR"]->Connect();
		}
		catch (MySQL_Exception $e)
		{
			print("DB Error:\n");
			print_r($e);
			die();
		}
		
		try 
		{	
			$this->condor = new Condor_Cron_Client(CONDOR_SERVER);
			$this->legal_document = $this->condor->condor->Get_Legal_Doc("paperless_application");
		
			$this->holiday_array = $this->Get_Holiday_Array();			
		}
		catch (Exception $e)
		{
			print("Condor Error:\n");
			print_r($e);
			die();
		}
	
	}
	
	public function Create_Doc($application_id)
	{
		$this->application_id = $application_id;
		$this->Check_Doc();
		$row = $this->Gather_Doc_Data();
		
		
		$data = array();
		$data['config'] = new stdClass();

		$data['application_id']                 = $this->application_id;
		$data['config']->property_short         = $row['com_name_short'];
		$data['config']->site_name              = $row['web_url'];

		$data['config']->promo_id				= $row['promo_id'];
		$data['config']->legal_entity			= $row['legal_entity'];
		$data['config']->support_fax			= $row['support_fax'];
		$data['data']['name_first']             = str_replace("\\", "",strtoupper($row['name_first']));
		$data['data']['name_last']              = str_replace("\\", "", strtoupper($row['name_last']));
		$data['data']['doc_date']               = str_replace("-","/",$row['date_created']);
		$data['data']['home_street']            = str_replace("\\", "",strtoupper($row['street']));
		$data['data']['home_unit']              = strtoupper($row['unit']);
		$data['data']['home_city']              = strtoupper($row['city']);
		$data['data']['home_state']             = strtoupper($row['state']);
		$data['data']['home_county']            = strtoupper($row['county']);
		$data['data']['home_zip']               = $row['zip'];
		$data['data']['dob']                    = $row['dob'];
		$data['data']['ssn_part_1']             = substr($row['ssn'],0,3);
		$data['data']['ssn_part_2']             = substr($row['ssn'],3,2);
		$data['data']['ssn_part_3']             = substr($row['ssn'],5,3);
		$data['data']['phone_home']             = $row['phone_home'];

		// residence lengths?
		$data['data']['residence_type']			= ($row['tenancy_type'] != "unspecified") ? strtoupper($row['tenancy_type']) :  NULL;
		$data['data']['residence_length']		= "**FIX1**";
		$data['data']['length_of_residence']	= "**FIX2**";

		$data['data']['phone_fax']				= $row['phone_fax'];
		$data['data']['email_primary']			= strtoupper($row['customer_email']);
		$data['data']['phone_cell']				= $row['phone_cell'];
		$data['data']['state_id_number']		= strtoupper($row['legal_id_number']);

		// remove backwhacks in data
		$data['data']['employer_name']			= str_replace("\\", "", strtoupper($row['employer_name']));
		$data['data']['employer_length']		= $row['employer_length'];
		$data['data']['income_type']			= strtoupper($row['income_type']);
		$data['data']['phone_work']				= $row['phone_work'];
		$data['data']['income_monthly_net']		= $row['income_monthly'];
		$data['data']['title']					= strtoupper($row['job_title']);

		$paydate_calc = new Pay_Date_Calc_2($this->holiday_array);
		$dd = ($row['income_direct_deposit'] == "yes") ? TRUE : FALSE;
		$data['data']['income_direct_deposit'] = ($row['income_direct_deposit'] == "yes") ? 'TRUE' : 'FALSE';

		$model_data["day_string_one"] = $row['day_of_week'];
		$model_data["next_pay_date"] = $row['next_paydate'];
		$model_data["day_int_one"] = $row['day_of_month_1'];
		$model_data["day_int_two"] = $row['day_of_month_2'];
		$model_data["week_one"] = $row['week_1'];
		$model_data["week_two"] = $row["week_2"];
		$model_data["last_paydate"] = $row["last_paydate"];

		$data['data']['paydates'] = $paydate_calc->Calculate_Pay_Dates($row['paydate_model'], $model_data, $dd,4,str_replace("-","/",$row['date_created']));

		// set these next three to the potentially new data from the confirm page
		// don't change this because it is used in application_content.php for condor docs
		$data['data']['paydate_model']['income_frequency']  = $row['income_frequency'];
		$data['data']['bank_aba']               = $row['bank_aba'];
		$data['data']['bank_account']           = $row['bank_account'];
		$data['data']['fund_qualified'] 		= "**FIX2**"; //$sink['fund_qualified'];
		$data['data']['bank_name']				= str_replace("\\", "", strtoupper($row['bank_name']));
//		$data['data']['check_number']			= "**FIX3**";
		$data['esignature']				= str_replace("\\", "", strtoupper($row['name_first'] . ' ' . $row['name_last']));

		$data['data']['ref_01_name_full']		= str_replace("\\", "", strtoupper($row['ref_01_name_full']));
		$data['data']['ref_01_phone_home']		= $row['ref_01_phone_home'];
		$data['data']['ref_01_relationship']	= strtoupper($row['ref_01_relationship']);
		$data['data']['ref_02_name_full']		= str_replace("\\", "", strtoupper($row['ref_02_name_full']));
		$data['data']['ref_02_phone_home']		= $row['ref_02_phone_home'];
		$data['data']['ref_02_relationship']	= strtoupper($row['ref_02_relationship']);



		// return qualify info
		$data["data"]["qualify_info"]["apr"] = $row['apr'];
		$data["data"]["qualify_info"]["finance_charge"] = $row['finance_charge'];
		$data["data"]["qualify_info"]["fund_amount"] = $row['fund_actual'];
		$data["data"]["qualify_info"]["payoff_date"] = $row['date_first_payment'];

		//$this->$document = $this->condor->Preview_Docs("paperless_application", $data);
		ob_start();
		$this->document = $this->condor->Preview_Docs($this->legal_document, $data);

		ob_end_flush();
		$this->data  = $data;		
		
	}
	
	public function Return_Doc()
	{
		return $this->document;
	}
	
	public function Write_Doc($destination)
	{
		file_put_contents("{$destination}{$this->application_id}.html",$this->document);
	}
	
	public function Insert_Doc()
	{
		
		print("Request\n");
		$request["type"] = "signature_request";
		$request["application_id"] = $this->application_id;
		$this->condor->Condor_Get_Docs('signature_request', "", $this->data);
		print_r($this->condor->response);
		print("Response\n");
		$_SESSION['condor']->audit_trail_id = $this->condor->response->audit_trail_id;
		$this->condor->Condor_Get_Docs('signature_response', 'TRUE', $this->data);
		
		//$response = $this->condor->Condor_Request($request, $this->document);
		
		//print_r($this->condor);
		//print("Response\n");
		//$request['signature_response'] = TRUE;
		//$request['type'] = "signature_response";
		//$response = $this->condor->condor->Condor_Request($request, null);
	
		//print_r($response);
		
	}
	
	private function Check_Doc()
	{
		$counter = 0;
		$query = "select count(*) as DOC_COUNT from signature where application_id = {$this->application_id}";
		try
        {
			$result = $this->db_object["CONDOR"]->Query($this->db_config["CONDOR"]['db'], $query);
        }
        catch (MySQL_Exception $e)
        {
        	throw $e;
        }
        
		while($row = mysql_fetch_assoc($result))
		{
			$counter = $row["DOC_COUNT"];
		}        
		
		if($counter > 0) die("Legal Docus found for this Application.Count : {$counter}"); 
	}
	
	private function Gather_Doc_Data()
	{
		$row = NULL;
		
		$query = "SELECT	ap.application_id,
									login.login,
									ap.track_id,
									ap.company_id,
									ap.archive_db2_id,
									ap.archive_mysql_id,
									(
										SELECT
											application_status.name
										FROM
											application_status
										WHERE
											ap.application_status_id = application_status.application_status_id
									) as status_long,
									(
										SELECT
											application_status.name_short
										FROM
											application_status
										WHERE
											ap.application_status_id = application_status.application_status_id
									) as status,
									(
										SELECT
											application_status.application_status_id
										FROM
											application_status
										WHERE
											ap.application_status_id = application_status.application_status_id
									) as status_id,
									date_format(ap.date_created, '%m-%d-%Y') as date_created,
									ap.fund_actual,
									date_format(ap.date_first_payment, '%m-%d-%Y') as date_first_payment,
									date_format(ap.date_first_payment, '%d') as date_first_payment_day,
									date_format(ap.date_first_payment, '%m') as date_first_payment_month,
									date_format(ap.date_first_payment, '%Y') as date_first_payment_year,
									ap.date_fund_actual as date_fund_stored,
									( CASE WHEN ap.date_fund_actual is null THEN DATE_FORMAT(current_date(),'%m-%d-%Y') ELSE DATE_FORMAT(date_fund_actual,'%m-%d-%Y') END ) as date_fund_actual,
									( CASE WHEN ap.date_fund_actual is null THEN DATE_FORMAT(current_date(),'%d') ELSE DATE_FORMAT(date_fund_actual,'%d') END ) as date_fund_actual_day,
									( CASE WHEN ap.date_fund_actual is null THEN DATE_FORMAT(current_date(),'%m') ELSE DATE_FORMAT(date_fund_actual,'%m') END ) as date_fund_actual_month,
									( CASE WHEN ap.date_fund_actual is null THEN DATE_FORMAT(current_date(),'%Y') ELSE DATE_FORMAT(date_fund_actual,'%Y') END ) as date_fund_actual_year,
									ap.finance_charge,
									ap.payment_total,
									ap.apr,
									ap.income_direct_deposit,
									ap.income_monthly,
									ap.income_frequency,
									ap.income_source,
									ap.paydate_model,
									ap.day_of_week,
									DATE_FORMAT(ap.last_paydate, '%Y-%m-%d') as last_paydate,
									ap.day_of_month_1,
									ap.day_of_month_2,
									ap.week_1,
									ap.week_2,
									ap.bank_name,
									ap.bank_aba,
									ap.bank_account,
									ap.bank_account_type,
									( IF(ap.fund_actual > 0, ap.fund_actual, ap.fund_qualified) ) as fund_amount,
									date_format(ap.date_fund_estimated, '%m-%d-%Y') as date_fund_estimated,
									date_format(ap.date_fund_estimated, '%d') as date_fund_estimated_day,
									date_format(ap.date_fund_estimated, '%m') as date_fund_estimated_month,
									date_format(ap.date_fund_estimated, '%Y') as date_fund_estimated_year,
									ap.street,
									ap.unit,
									ap.city,
									ap.county,
									ap.state,
									ap.zip_code as zip,
									ap.tenancy_type,
									ap.ip_address,
									ap.name_first,
									ap.name_middle,
									ap.name_last,
									date_format(ap.dob, '%m-%d-%Y') as dob,
									date_format(ap.dob, '%d') as dob_day,
									date_format(ap.dob, '%m') as dob_month,
									date_format(ap.dob, '%Y') as dob_year,
									ap.ssn,
									ap.employer_name,
									ap.job_title,
									ap.shift,
									date_format(ap.date_hire, '%m-%d-%Y') as date_hire,
									date_format(ap.date_hire, '%d') as date_hire_day,
									date_format(ap.date_hire, '%m') as date_hire_month,
									date_format(ap.date_hire, '%Y') as date_hire_year,
									ap.phone_work,
									ap.phone_work_ext,
									ap.phone_home,
									ap.phone_cell,
									ap.phone_fax,
									ap.call_time_pref,
									ap.legal_id_number,
									ap.legal_id_state,
									ap.email as customer_email,
									(
										SELECT
											count(distinct(ap2.ssn))
										FROM
											application ap2
										WHERE
											ap.bank_aba		= ap2.bank_aba
										AND ap.bank_account	= ap2.bank_account
										AND ap.company_id	= ap2.company_id
									) as aba_account_count,
									(
										select
											site.name
										from
											site
										where site_id = camp.site_id
									) as web_url,
									camp.promo_id,
									(
										SELECT
											date_format(sh.date_created, '%m-%d-%Y')
										FROM
											status_history sh,
											application_status_flat asf
										WHERE
											ap.application_id = sh.application_id
										AND sh.application_status_id = asf.application_status_id
										AND (asf.level0='confirmed' AND asf.level1='prospect' AND asf.level2='*root')
										ORDER BY sh.date_created
										LIMIT 1
									) as date_confirmed,
									com.name_short as com_name_short,
									com.name as com_name,
									(
										SELECT
											com.value
										FROM
											company_property com, application ap
										WHERE
											ap.company_id = com.company_id
										AND com.property = 'COMPANY_NAME'
										AND ap.application_id = {$this->application_id}
									) as legal_entity,
									(
										SELECT
											com.value
										FROM
											company_property com, application ap
										WHERE
											ap.company_id = com.company_id
										AND com.property = 'COMPANY_SUPPORT_FAX'
										AND ap.application_id = {$this->application_id}
									) as support_fax,
									(
										SELECT
											name_full
										FROM
											personal_reference
										WHERE
											application_id = {$this->application_id}
										LIMIT 1
									) as ref_01_name_full,
									(
										SELECT
											phone_home
										FROM
											personal_reference
										WHERE
											application_id = {$this->application_id}
										LIMIT 1
									) as ref_01_phone_home,
									(
										SELECT
											relationship
										FROM
											personal_reference
										WHERE
											application_id = {$this->application_id}
										LIMIT 1
									) as ref_01_relationship,
									(
										SELECT
											name_full
										FROM
											personal_reference
										WHERE
											application_id = {$this->application_id}
										LIMIT 1, 1
									) as ref_02_name_full,
									(
										SELECT
											phone_home
										FROM
											personal_reference
										WHERE
											application_id = {$this->application_id}
										LIMIT 1, 1
									) as ref_02_phone_home,
									(
										SELECT
											relationship
										FROM
											personal_reference
										WHERE
											application_id = {$this->application_id}
										LIMIT 1, 1
									) as ref_02_relationship
									FROM
									application ap
									LEFT JOIN site ON ap.enterprise_site_id = site.site_id
									LEFT JOIN company as com  ON ap.company_id = com.company_id
									LEFT JOIN login ON ap.login_id = login.login_id
									LEFT JOIN campaign_info camp ON camp.campaign_info_id =
									(
										SELECT
											min(campaign_info_id)
										FROM
											campaign_info cref
										WHERE
											cref.application_id = ap.application_id
									)
							WHERE
									ap.application_id = {$this->application_id}";
		try
        {
			$result = $this->db_object["LDB"]->Query($this->db_config["LDB"]['db'], $query);
        }
        catch (MySQL_Exception $e)
        {
        	throw $e;
        }
        
       	while($row = mysql_fetch_assoc($result))
		{
			return $row;
		} 
		
		die("Unable to Local Information for the Application");    			
	}

	private function Get_Holiday_Array()
	{
        $query = "
            SELECT
                *
            FROM
                holidays
            WHERE
                date >= DATE_SUB(CURDATE(),INTERVAL 90 DAY)
            ";

        try
        {
			$olp_result = $this->db_object["OLP"]->Query('d2_management', $query);

        }
        catch( MySQL_Exception $e )
        {
            throw $e;
        }

		while($row = mysql_fetch_assoc($olp_result))
		{
			$return[$row['date']] = TRUE;
		}
        return $return;
	}
}


if(in_array($argv[1],array("LIVE","RC")) && in_array($argv[2],array("INSERT","WRITE","CONSOLE")) && is_numeric($argv[3]))
{
	$cld = new Condor_Legal_Doc($argv[1]);
	$cld->Create_Doc($argv[3]);
	$pathset = (isset($argv[4])) ? "{$argv[4]}/" : ""; 
	switch ($argv[2])
	{
		case "WRITE":
			$cld->Write_Doc($pathset);
			break;
		case "CONSOLE":
			print($cld->Return_Doc());
			break;
		case "INSERT";
			$cld->Insert_Doc();
			break;
			
	}
}
else 
{
	print("condor_legal.php {LIVE/RC} {INSERT(tbd)/WRITE/CONSOLE} {application_id} {path}\n");
}
?>
