<?php
ini_set('memory_limit','4096M');
/**
php compare_aalm-ldb_application_data.php
*/
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(COMMON_LIB_DIR."mysqli.1.php");

echo "Database Comparison Test:  application\n";

$time_window = 300;

$factory = ECash::getFactory();
$status_list = $factory->getReferenceList('ApplicationStatusFlat');

$application_field_list = array("date_modified"=>"date_modified",
 "date_created"=>"date_created",
 "company_id"=>"company_id",
 "customer_id"=>"customer_id",
 "version"=>"version",
 "is_react"=>"is_react",
 "loan_type_id"=>"loan_type_id",
 "rule_set_id"=>"rule_set_id",
 "enterprise_site_id"=>"enterprise_site_id",
 "application_status_id"=>"application_status_id",
 "date_application_status_set"=>"date_application_status_set",
 "date_next_contact"=>"date_next_contact",
 "ip_address"=>"ip_address",
 "application_type"=>"application_type",
 "bank_name"=>"bank_name",
 "bank_aba"=>"bank_aba",
 "bank_account"=>"bank_account",
 "bank_account_type"=>"bank_account_type",
 "date_fund_estimated"=>"date_fund_estimated",
 "date_fund_actual"=>"date_fund_actual",
 "date_first_payment"=>"date_first_payment",
 "fund_requested"=>"fund_requested",
 "fund_qualified"=>"fund_qualified",
 "fund_actual"=>"fund_actual",
 "finance_charge"=>"finance_charge",
 "payment_total"=>"payment_total",
 "apr"=>"apr",
 "rate_override"=>"rate_override",
 "income_monthly"=>"income_monthly",
 "income_source"=>"income_source",
 "income_direct_deposit"=>"income_direct_deposit",
 "income_frequency"=>"income_frequency",
 "income_date_soap_1"=>"income_date_soap_1",
 "income_date_soap_2"=>"income_date_soap_2",
 "paydate_model"=>"paydate_model",
 "day_of_week"=>"day_of_week",
 "last_paydate"=>"last_paydate",
 "day_of_month_1"=>"day_of_month_1",
 "day_of_month_2"=>"day_of_month_2",
 "week_1"=>"week_1",
 "week_2"=>"week_2",
 "track_id"=>"track_id",
 "agent_id"=>"agent_id",
 "agent_id_callcenter"=>"agent_id_callcenter",
 "dob"=>"dob",
 "ssn"=>"ssn",
 "legal_id_number"=>"legal_id_number",
 "legal_id_state"=>"legal_id_state",
 "legal_id_type"=>"legal_id_type",
 "identity_verified"=>"identity_verified",
 "email"=>"email",
 "email_verified"=>"email_verified",
 "name_last"=>"name_last",
 "name_first"=>"name_first",
 "name_middle"=>"name_middle",
 "name_suffix"=>"name_suffix",
 "street"=>"street",
 "unit"=>"unit",
 "city"=>"city",
 "state"=>"state",
 "county"=>"county",
 "zip_code"=>"zip_code",
 "tenancy_type"=>"tenancy_type",
 "phone_home"=>"phone_home",
 "phone_cell"=>"phone_cell",
 "phone_fax"=>"phone_fax",
 "call_time_pref"=>"call_time_pref",
 "contact_method_pref"=>"contact_method_pref",
 "marketing_contact_pref"=>"marketing_contact_pref",
 "employer_name"=>"employer_name",
 "job_title"=>"job_title",
 "supervisor"=>"supervisor",
 "shift"=>"shift",
 "date_hire"=>"date_hire",
 "job_tenure"=>"job_tenure",
 "phone_work"=>"phone_work",
 "phone_work_ext"=>"phone_work_ext",
 "work_address_1"=>"work_address_1",
 "work_address_2"=>"work_address_2",
 "work_city"=>"work_city",
 "work_state"=>"work_state",
 "work_zip_code"=>"work_zip_code",
 "employment_verified"=>"employment_verified",
 "pwadvid"=>"pwadvid",
 "olp_process"=>"olp_process",
 "is_watched"=>"is_watched",
 "schedule_model_id"=>"schedule_model_id",
 "modifying_agent_id"=>"modifying_agent_id",
 "banking_start_date"=>"banking_start_date",
 "residence_start_date"=>"residence_start_date",
 "cfe_rule_set_id"=>"cfe_rule_set_id",
 "price_point"=>"price_point",
 "bank_account_oldkey"=>"bank_account_oldkey",
 "dob_oldkey"=>"dob_oldkey",
 "age"=>"age",
 "ssn_oldkey"=>"ssn_oldkey",
 "legal_id_number_oldkey"=>"legal_id_number_oldkey",
 "encryption_key_id"=>"encryption_key_id",
 "ssn_last_four"=>"ssn_last_four");

$unset_ary = array("aalm_key_id", "legal_id_number_id", "ssn_id", "date_of_birth_id", "bank_info_id");

$key_ignore_list = array(
);

$db_ldb = ECash::getMasterDb();
$db_chasm = ECash::getAppSvcDB();
$counter = 0;

$query_chasm_ap = "
SELECT
    ap.company_id AS company_id,
    ap.cfe_ruleset_id AS cfe_rule_set_id,
    olp.olp_process_name AS olp_process,
    ap.application_type_id AS loan_type_id,
    ap.application_source_id AS application_source_id,
    ap.rule_set_id AS rule_set_id,
    ap.track_key AS track_id,
    ap.authoritative_id AS application_id,
    ap.application_id AS aalm_key_id,
    ap.date_created AS date_created,
    ap.date_modified AS date_modified,
    ast.application_status_name AS application_status,
    ap.date_application_status_set AS date_application_status_set,
    ap.date_first_payment AS date_first_payment,
    ap.date_fund_estimated AS date_fund_estimated,
    ap.date_fund_actual AS date_fund_actual,
    ap.enterprise_site_id AS enterprise_site_id,
    ap.external_id AS external_id,
    ap.fund_actual AS fund_actual,
    ap.fund_qualified AS fund_qualified,
    ap.finance_charge AS finance_charge,
    ap.apr AS apr,
    ap.ip_address AS ip_address,
    CASE 
        WHEN ap.is_react = 1 
            THEN 'yes' 
            ELSE 'no' 
    END AS is_react,
    CASE 
        WHEN ap.is_watched = 1 
            THEN 'yes' 
            ELSE 'no' 
    END AS is_watched,
    ap.loan_type_id AS loan_type_id,
    ap.modifying_agent_id AS modifying_agent_id,
    ap.payment_total AS payment_total,
    ap.price_point AS price_point,
    ap.user_id AS user_id,
    'no preference' AS call_time_pref,
    'no preference' AS contact_method_pref,
    'no preference' AS marketing_contact_pref,
    'dl' AS legal_id_type
FROM application AS ap
    JOIN olp_process olp ON (ap.olp_process_id = olp.olp_process_id)
    JOIN application_status ast ON (ap.application_status_id = ast.application_status_id)
    ORDER BY ap.authoritative_id
";

$query_chasm_appl = "
SELECT
    appl.application_id AS aalm_key_id,
    appl.applicant_account_id AS customer_id,
    appl.name_first AS name_first,
    appl.name_last AS name_last,
    appl.age AS age,
    appl.legal_id_state AS legal_id_state,
    appl.street AS street,
    appl.city AS city,
    appl.state AS state,
    appl.zip_code AS zip_code,
    tt.tenancy_type_name AS tenancy_type,
    appl.legal_id_number_id AS legal_id_number_id,
    appl.ssn_id AS ssn_id,
    appl.date_of_birth_id AS date_of_birth_id
FROM applicant AS appl
    JOIN tenancy_type tt ON (appl.tenancy_type_id = tt.tenancy_type_id)
";

$query_chasm_aa = "
SELECT
    aa.applicant_account_id AS customer_id,
    aa.login AS login,
    aa.password AS password
FROM applicant_account AS aa
";

$query_chasm_dob = "
SELECT
    dob.date_of_birth AS dob,
    dob.date_of_birth_id AS date_of_birth_id
FROM date_of_birth AS dob
";

$query_chasm_ssn = "
SELECT
    ssn.ssn AS ssn,
    ssn_last_four AS ssn_last_four,
    ssn.ssn_id AS ssn_id
FROM ssn AS ssn
";

$query_chasm_lg = "
SELECT
    lg.legal_id_number,
    lg.legal_id_number_id
FROM legal_id_number AS lg
";

$query_chasm_bi = "
SELECT
    bi.application_id AS aalm_key_id,
    bi.bank_info_id AS bank_info_id,
    bi.bank_aba AS bank_aba,
    CASE
        WHEN bi.is_direct_deposit = 1
        THEN 'yes'
        ELSE 'no'
    END AS income_direct_deposit,
    bi.banking_start_date AS banking_start_date,
    bi.bank_name AS bank_name,
    ba.bank_account AS bank_account
FROM bank_info AS bi
    JOIN bank_account AS ba ON (ba.bank_account_id = bi.bank_account_id)
";

$query_chasm_hp = "
SELECT
    ci1b.application_id AS aalm_key_id,
    ci1b.contact_info_value AS value
FROM (
            SELECT ci1a.application_id,ct1a.contact_type_name,ci1a.contact_info_value,ci1a.notes,ci1a.is_primary,ci1a.contact_type_id,ci1a.contact_info_id
            FROM contact_info ci1a
            JOIN contact_type ct1a ON (ct1a.contact_type_id = ci1a.contact_type_id)
            WHERE contact_info_id IN (
							SELECT CAST(RIGHT(ord_num,LEN(ord_num)-4) AS INTEGER) AS contact_info_id
							FROM (
								SELECT application_id, MAX(ord_num) AS ord_num
								FROM (
									SELECT application_id, (CAST(is_primary AS VARCHAR) + '-' + CAST((5 - contact_type_id) AS VARCHAR) + '-' + CAST(contact_info_id AS VARCHAR) ) AS ord_num 
									FROM contact_info 
									WHERE contact_type_id IN (3) AND LEN(contact_info_value)>1) ci1
								 GROUP BY application_id
							) ci2
            )

    ) as ci1b
";

$query_chasm_cp = "
SELECT
    ci1b.application_id AS aalm_key_id,
    ci1b.contact_info_value AS value
FROM (
            SELECT ci1a.application_id,ct1a.contact_type_name,ci1a.contact_info_value,ci1a.notes,ci1a.is_primary,ci1a.contact_type_id,ci1a.contact_info_id
            FROM contact_info ci1a
            JOIN contact_type ct1a ON (ct1a.contact_type_id = ci1a.contact_type_id)
            WHERE contact_info_id IN (
							SELECT CAST(RIGHT(ord_num,LEN(ord_num)-4) AS INTEGER) AS contact_info_id
							FROM (
								SELECT application_id, MAX(ord_num) AS ord_num
								FROM (
									SELECT application_id, (CAST(is_primary AS VARCHAR) + '-' + CAST((5 - contact_type_id) AS VARCHAR) + '-' + CAST(contact_info_id AS VARCHAR) ) AS ord_num 
									FROM contact_info 
									WHERE contact_type_id IN (2) AND LEN(contact_info_value)>1) ci1
								 GROUP BY application_id
							) ci2
            )

    ) as ci1b
";

$query_chasm_wp = "
SELECT
    ci1b.application_id AS aalm_key_id,
    ci1b.contact_info_value AS value
FROM (
            SELECT ci1a.application_id,ct1a.contact_type_name,ci1a.contact_info_value,ci1a.notes,ci1a.is_primary,ci1a.contact_type_id,ci1a.contact_info_id
            FROM contact_info ci1a
            JOIN contact_type ct1a ON (ct1a.contact_type_id = ci1a.contact_type_id)
            WHERE contact_info_id IN (
							SELECT CAST(RIGHT(ord_num,LEN(ord_num)-4) AS INTEGER) AS contact_info_id
							FROM (
								SELECT application_id, MAX(ord_num) AS ord_num
								FROM (
									SELECT application_id, (CAST(is_primary AS VARCHAR) + '-' + CAST((5 - contact_type_id) AS VARCHAR) + '-' + CAST(contact_info_id AS VARCHAR) ) AS ord_num 
									FROM contact_info 
									WHERE contact_type_id IN (5) AND LEN(contact_info_value)>1) ci1
								 GROUP BY application_id
							) ci2
            )

    ) as ci1b
";

$query_chasm_fx = "
SELECT
    ci1b.application_id AS aalm_key_id,
    ci1b.contact_info_value AS value
FROM (
            SELECT ci1a.application_id,ct1a.contact_type_name,ci1a.contact_info_value,ci1a.notes,ci1a.is_primary,ci1a.contact_type_id,ci1a.contact_info_id
            FROM contact_info ci1a
            JOIN contact_type ct1a ON (ct1a.contact_type_id = ci1a.contact_type_id)
            WHERE contact_info_id IN (
							SELECT CAST(RIGHT(ord_num,LEN(ord_num)-4) AS INTEGER) AS contact_info_id
							FROM (
								SELECT application_id, MAX(ord_num) AS ord_num
								FROM (
									SELECT application_id, (CAST(is_primary AS VARCHAR) + '-' + CAST((5 - contact_type_id) AS VARCHAR) + '-' + CAST(contact_info_id AS VARCHAR) ) AS ord_num 
									FROM contact_info 
									WHERE contact_type_id IN (1) AND LEN(contact_info_value)>1) ci1
								 GROUP BY application_id
							) ci2
            )

    ) as ci1b
";

$query_chasm_email = "
SELECT
    ci2b.application_id AS aalm_key_id,
    ci2b.contact_info_value AS value
FROM (
            SELECT ci1a.application_id,ct1a.contact_type_name,ci1a.contact_info_value,ci1a.notes,ci1a.is_primary,ci1a.contact_type_id,ci1a.contact_info_id
            FROM contact_info ci1a
            JOIN contact_type ct1a ON (ct1a.contact_type_id = ci1a.contact_type_id)
            WHERE contact_info_id IN (
							SELECT CAST(RIGHT(ord_num,LEN(ord_num)-4) AS INTEGER) AS contact_info_id
							FROM (
								SELECT application_id, MAX(ord_num) AS ord_num
								FROM (
									SELECT application_id, (CAST(is_primary AS VARCHAR) + '-' + CAST((5 - contact_type_id) AS VARCHAR) + '-' + CAST(contact_info_id AS VARCHAR) ) AS ord_num 
									FROM contact_info 
									WHERE contact_type_id IN (4) AND LEN(contact_info_value)>1) ci1
								 GROUP BY application_id
							) ci2
            )
    ) as ci2b
    ";

//echo $query_chasm_ap, "\n";
$result_chasm_ap = $db_chasm->query($query_chasm_ap);
$rows_chasm_ap = $result_chasm_ap->fetchAll(DB_IStatement_1::FETCH_OBJ);
    
//echo $query_chasm_appl, "\n";
$result_chasm_appl = $db_chasm->query($query_chasm_appl);
$rows_chasm_appl = $result_chasm_appl->fetchAll(DB_IStatement_1::FETCH_OBJ);
    
//echo $query_chasm_aa, "\n";
$result_chasm_aa = $db_chasm->query($query_chasm_aa);
$rows_chasm_aa = $result_chasm_aa->fetchAll(DB_IStatement_1::FETCH_OBJ);
    
//echo $query_chasm_dob, "\n";
$result_chasm_dob = $db_chasm->query($query_chasm_dob);
$rows_chasm_dob = $result_chasm_dob->fetchAll(DB_IStatement_1::FETCH_OBJ);
    
//echo $query_chasm_ssn, "\n";
$result_chasm_ssn = $db_chasm->query($query_chasm_ssn);
$rows_chasm_ssn = $result_chasm_ssn->fetchAll(DB_IStatement_1::FETCH_OBJ);
    
//echo $query_chasm_lg, "\n";
$result_chasm_lg = $db_chasm->query($query_chasm_lg);
$rows_chasm_lg = $result_chasm_lg->fetchAll(DB_IStatement_1::FETCH_OBJ);

//echo $query_chasm_bi, "\n";
$result_chasm_bi = $db_chasm->query($query_chasm_bi);
$rows_chasm_bi = $result_chasm_bi->fetchAll(DB_IStatement_1::FETCH_OBJ);
    
//echo $query_chasm_email, "\n";
$result_chasm_email = $db_chasm->query($query_chasm_email);
$rows_chasm_email = $result_chasm_email->fetchAll(DB_IStatement_1::FETCH_OBJ);
    
//echo $query_chasm_hp, "\n";
$result_chasm_hp = $db_chasm->query($query_chasm_hp);
$rows_chasm_hp = $result_chasm_hp->fetchAll(DB_IStatement_1::FETCH_OBJ);
    
//echo $query_chasm_cp, "\n";
$result_chasm_cp = $db_chasm->query($query_chasm_cp);
$rows_chasm_cp = $result_chasm_cp->fetchAll(DB_IStatement_1::FETCH_OBJ);
    
//echo $query_chasm_wp, "\n";
$result_chasm_wp = $db_chasm->query($query_chasm_wp);
$rows_chasm_wp = $result_chasm_wp->fetchAll(DB_IStatement_1::FETCH_OBJ);
    
//echo $query_chasm_fx, "\n";
$result_chasm_fx = $db_chasm->query($query_chasm_fx);
$rows_chasm_fx = $result_chasm_fx->fetchAll(DB_IStatement_1::FETCH_OBJ);

// construct complete application records
$finish = false;
$i = 0;

$lookup_ary_ap = array();
$lookup_ary_appl = array();
$lookup_ary_aa = array();
$lookup_ary_dob = array();
$lookup_ary_ssn = array();
$lookup_ary_lg = array();
$lookup_ary_bi = array();
$lookup_ary_hp = array();
$lookup_ary_cp = array();
$lookup_ary_wp = array();
$lookup_ary_fx = array();
$lookup_ary_email = array();

while (! $finish){
    $finish_test = true;

    if (count($rows_chasm_ap) > $i){
        $finish_test = false;
        $lookup_ary_ap[$rows_chasm_ap[$i]->aalm_key_id] = $i;
    }

    if (count($rows_chasm_appl) > $i){
        $finish_test = false;
        $lookup_ary_appl[$rows_chasm_appl[$i]->aalm_key_id] = $i;
    }

    if (count($rows_chasm_aa) > $i){
        $finish_test = false;
        $lookup_ary_aa[$rows_chasm_aa[$i]->customer_id] = $i;
    }

    if (count($rows_chasm_dob) > $i){
        $finish_test = false;
        $lookup_ary_dob[$rows_chasm_dob[$i]->date_of_birth_id] = $i;
    }

    if (count($rows_chasm_ssn) > $i){
        $finish_test = false;
        $lookup_ary_ssn[$rows_chasm_ssn[$i]->ssn_id] = $i;
    }

    if (count($rows_chasm_lg) > $i){
        $finish_test = false;
        $lookup_ary_lg[$rows_chasm_lg[$i]->legal_id_number_id] = $i;
    }

    if (count($rows_chasm_bi) > $i){
        $finish_test = false;
        $lookup_ary_bi[$rows_chasm_bi[$i]->aalm_key_id] = $i;
    }

    if (count($rows_chasm_hp) > $i){
        $finish_test = false;
        $lookup_ary_hp[$rows_chasm_hp[$i]->aalm_key_id] = $i;
    }

    if (count($rows_chasm_cp) > $i){
        $finish_test = false;
        $lookup_ary_cp[$rows_chasm_cp[$i]->aalm_key_id] = $i;
    }

    if (count($rows_chasm_wp) > $i){
        $finish_test = false;
        $lookup_ary_wp[$rows_chasm_wp[$i]->aalm_key_id] = $i;
    }

    if (count($rows_chasm_fx) > $i){
        $finish_test = false;
        $lookup_ary_fx[$rows_chasm_fx[$i]->aalm_key_id] = $i;
    }

    if (count($rows_chasm_email) > $i){
        $finish_test = false;
        $lookup_ary_email[$rows_chasm_email[$i]->aalm_key_id] = $i;
    }
    $i++;
    $finish = $finish_test;
}

$rows_chasm = array();
foreach ($rows_chasm_ap as $ap){
    $valid = true;
    $ap_id = $ap->aalm_key_id;
    $ap->application_status_id = $status_list->toId($ap->application_status);
    if (isset($lookup_ary_appl[$ap_id])) {
    	$appl = $rows_chasm_appl[$lookup_ary_appl[$ap_id]];

//        if (isset($lookup_ary_aa[$appl->customer_id])) $aa = $rows_chasm_aa[$lookup_ary_aa[$appl->customer_id]];
//        else {
//            $valid = false;
//	    echo " Chasm Application: ".$ap->application_id." is missing applicant account: ".$appl->customer_id."\n";
//        }
        if (isset($lookup_ary_dob[$appl->date_of_birth_id])) $dob = $rows_chasm_dob[$lookup_ary_dob[$appl->date_of_birth_id]];
        else {
            $valid = false;
            echo " Chasm Application: ".$ap->application_id." is missing date of birth: ".$appl->date_of_birth_id."\n";
        }
        if (isset($lookup_ary_ssn[$appl->ssn_id])) $ssn = $rows_chasm_ssn[$lookup_ary_ssn[$appl->ssn_id]];
        else {
            $valid = false;
            echo " Chasm Application: ".$ap->application_id." is missing ssn: ".$appl->ssn_id."\n";
        }
        if (isset($lookup_ary_lg[$appl->legal_id_number_id])) $lg = $rows_chasm_lg[$lookup_ary_lg[$appl->legal_id_number_id]];
        else {
    	    $lg = new stdClass();
	    $lg->legal_id_number = "";
	    $lg->legal_id_number_id =0;
        }
        if (isset($lookup_ary_bi[$ap_id])) $bi = $rows_chasm_bi[$lookup_ary_bi[$ap_id]];
        else {
            $valid = false;
            echo " Chasm Application: ".$ap->application_id." is missing bank info: ".$ap_id."\n";
        }

        $ct = new stdClass();
        if (isset($lookup_ary_hp[$ap_id])) $ct->phone_home = $rows_chasm_hp[$lookup_ary_hp[$ap_id]]->value;
        else $ct->phone_home = "";
        if (isset($lookup_ary_cp[$ap_id])) $ct->phone_cell = $rows_chasm_cp[$lookup_ary_cp[$ap_id]]->value;
        else $ct->phone_cell = "";
        if (isset($lookup_ary_wp[$ap_id])) $ct->phone_work = $rows_chasm_wp[$lookup_ary_wp[$ap_id]]->value;
        else $ct->phone_work = "";
        if (isset($lookup_ary_fx[$ap_id])) $ct->phone_fax = $rows_chasm_fx[$lookup_ary_fx[$ap_id]]->value;
        else $ct->phone_fax = "";
        if (isset($lookup_ary_email[$ap_id])) $ct->email = $rows_chasm_email[$lookup_ary_email[$ap_id]]->value;
        else $ct->email = "";
    
        $row = (object)array_merge((array)$ap, (array)$appl, (array)$dob, (array)$ssn, (array)$lg, (array)$bi, (array)$ct);
	foreach($unset_ary as $unset_var){
	    unset($row->$unset_var);
	}
        if ($valid) $rows_chasm[] = $row;
    } else {
        $valid = false;
        echo " Chasm Application: ".$ap->application_id." is missing applicant: ".$ap_id."\n";
    }
}


$query_ldb = "
SELECT *
FROM application
ORDER BY application_id
";
$result_ldb = $db_ldb->query($query_ldb);
$rows_ldb = $result_ldb->fetchAll(PDO::FETCH_OBJ);

$chasm_row = $rows_chasm[1];
$chasm_keys = get_object_vars($chasm_row);

$ldb_row = $rows_ldb[1];
$ldb_keys = get_object_vars($ldb_row);

echo "\n\nChasm keys found in LDB test:";
$chasm_lookup_array = array();
$ldb_lookup_array = array();
$initial_test_array = array();
$key_test_array = array();
$not_one_fail = true;
foreach (array_keys($chasm_keys) as $key){
    $initial_test_array[$key] = false;
    $key_test_array[$key] = true;
    if (!array_key_exists($key,$ldb_keys)) {
        echo "\n  missing chasm application field [".$key."] in ldb.";
        $key_test_array[$key] = false;
        $not_one_fail = false;
    }
}
if ($not_one_fail) echo "\n   none found.";

echo "\n\nChasm null/empty field test:";
$null_test_array = $initial_test_array;
$zero_test_array = $initial_test_array;
$empty_test_array = $initial_test_array;
$i = 0;
foreach ($rows_chasm as $chasm_row){
    $chasm_lookup_array[$chasm_row->application_id] = $i++;
    foreach (array_keys($chasm_keys) as $key){
        if (!is_null($chasm_row->$key)) $null_test_array[$key] = true;
        if (!empty($chasm_row->$key)) $empty_test_array[$key] = true;
        if (($chasm_row->$key != 0)) $zero_test_array[$key] = true;
    }
}
$not_one_fail = true;
foreach (array_keys($chasm_keys) as $key){
    if (!$null_test_array[$key]) {
        echo "\n  all of customer fields [".$key."] are null in chasm.";
        $not_one_fail = true;
    }
    if (!$empty_test_array[$key]) {
        echo "\n  all of customer fields [".$key."] are empty in chasm.";
        $not_one_fail = true;
    }
}
if ($not_one_fail) echo "\n   none found.";

echo "\n\nChasm fields match to LDB by application test:";
$i = 0;
$not_one_fail = true;
$chasm_missing_array = array();
foreach ($rows_ldb as $ldb_row){
    $ldb_lookup_array[$ldb_row->application_id] = $i++;
    $ldb_row->application_status_name = $status_list->toName($ldb_row->application_status_id);
//echo $ldb_row->application_id ."\n";
    if (isset($chasm_lookup_array[$ldb_row->application_id])) $chasm_row = $rows_chasm[$chasm_lookup_array[$ldb_row->application_id]];
    else $chasm_row = false;
    if ($chasm_row) {
        $this_test = true;
        foreach (array_keys($chasm_keys) as $key){
            // is the test valid
//echo $key.": ".$chasm_row->$key ." = ". $ldb_row->$key ."\n";
            if (!isset($key_ignore_list[$key]) && $key_test_array[$key] && $null_test_array[$key] && $empty_test_array[$key]) {
                if (strcasecmp(trim($chasm_row->$key),trim($ldb_row->$key)) != 0){ 
//                   !(
//                        (($chasm_row->$key == 0) || empty($chasm_row->$key) || is_null($chasm_row->$key))&&
//                        (($ldb_row->$key == 0) || empty($ldb_row->$key) || is_null($ldb_row->$key))
//                    ) &&
		    $fail = true;
                    if ((is_numeric($chasm_row->$key) || is_numeric($ldb_row->$key)) &&  ($chasm_row->$key == $ldb_row->$key)) $fail = false;
                    if (strtotime($chasm_row->$key)) {
		    	if (abs(strtotime($chasm_row->$key) - strtotime($ldb_row->$key)) < $time_window){
			    $fail = false;
			}
		    }
                    if ($fail && $this_test) {
                        echo "\n application id: ".$ldb_row->application_id;
                        $this_test = false;
                    }
                    if ($fail) {
                        echo "\n  field ".$key." is ".$chasm_row->$key." in chasm and ".$ldb_row->$key." in ldb";
                        $not_one_fail = false;
                    }
                }
            }
        }
    } else $chasm_missing_array[] = "\n  ap: ".$ldb_row->application_id;
}
if ($not_one_fail) echo "\n   none found.";

echo "\n\nMissing applications test:";
echo "\n ldb application ids missing from chasm";
foreach ($chasm_missing_array as $id_string){
    echo "\n  ".$id_string;
}
if (count($chasm_missing_array) == 0) echo "\n   none found.";

$i = 0;
echo "\n chasm application ids missing from ldb";
foreach ($rows_chasm as $chasm_row){
    if (!isset($ldb_lookup_array[$chasm_row->application_id])) {
        $i++;
        echo "\n  ".$chasm_row->application_id;
    }
}
if ($i == 0) echo "\n   none found.";
echo "\n\nTesting Complete\n";
?>
