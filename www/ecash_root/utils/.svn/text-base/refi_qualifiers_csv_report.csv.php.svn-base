<?php
set_time_limit(0);
//all unix timestamps
//'2011-11-16' //day AALM went live outside of TSS environment
//'2012-03-08' //2nd time a run was requested
$ts_start = strtotime('20122-01-01');
$ts_end = strtotime('2013-04-01'); //time();
$ts_today = time();
set_time_limit(0);
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_COMMON_DIR=/virtualhosts/ecash_common.cfe/");
$y = date('Y-m-d');
putenv("YESTERDAY==$y");  // `date --date="yesterday" +%Y-%m-%d`

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once dirname(realpath(__FILE__)) . '/../server/code/bureau_query.class.php';

require_once(SQL_LIB_DIR . 'scheduling.func.php');

$server = ECash::getServer();
ini_set('memory_limit','2048M');

/*
        REQUIRED FIELDS
                ApplicationID       (see: "Unique Identifier")
                FirstName           (see: "NameFirst")
                LastName            (see: "NameLast")
                email
                cell phone
*/


$columns = array('Application ID', 'Created', 'NameFirst', 'MI', 'NameLast', 'PhoneHome', 'PhoneCell', 'Email');

$date_start = date('Ymd', $ts_start);
$date_end = date('Ymd', $ts_end);
$date_today = date('Ymd', $ts_today);
$fp = fopen("/tmp/refi_qualifiers_{$date_start}-{$date_end}_run_{$date_today}.csv", 'w');
fputcsv($fp, $columns);

$query = "SELECT a.application_id, a.date_created, a.name_first, a.name_middle, a.name_last, a.phone_home, a.phone_cell, a.email, a.loan_type_id
FROM application a
WHERE 
 NOT a.ssn in (SELECT ssn FROM do_not_loan_flag WHERE active_status = 'active') AND a.application_status_id = 20 
 ORDER BY a.date_created";

// add a limit if the parameter is passed
if  (isset($argv[1])) {
	if (is_numeric($argv[1])) {
		$query .= " LIMIT 0, ".$argv[1];
	}
}

$factory = ECash::getFactory();
$db = $factory->getDB();
$statement =  $db->prepare($query);
// $values = array(date('Y-m-d', $ts_start), date('Y-m-d', $ts_end));
$values = array();

echo "start = ".date('Y-m-d', $ts_start)." \n";
echo "end = ".date('Y-m-d', $ts_end)." \n";
echo "Querying...\nSQL =\n".$query;

$statement->execute($values);
echo "Fetching results...\n";
$rows = $statement->fetchAll(PDO::FETCH_ASSOC);

echo "Writing results\n";
$refi_qual_ach_pmt_types = array(28, 29, 296, 24, 25, 22, 23, 35, 12, 13, 6, 14, 15, 3, 38, 39, 30, 31, 2, 26, 27);
$x = 0;
$app_ids = $app_ids_no_achs = $app_ids_too_high_principal = array();
$app = ECash::getFactory()->getModel('Application');
foreach($rows as $row) {
        set_time_limit(0);
        $x++;
        //pull off the appID
        $app_id = $row['application_id'];
        $app->loadBy(array('application_id' => $app_id));
        $decider = new ECash_PaymentTypesRestrictions(ECash::getMasterDb(),
          $app_id, 'underwriting', 'verification', 'Title',
          $row['loan_type_id'], false);

        $new_array = (array)$decider;
        $hacked = print_r($new_array, true);
        $hack = explode("\n", $hacked);

        $posted_principal = 9999;
        $get_debits = false;
        $debits = '';
        foreach ($hack as $k=>$v) {
                if ($get_debits) {
                        if (strstr($v, '[posted_schedule]') <> '') {
                                $get_debits = false;
                        } else {
                                $debits.= trim($v);
                        }
                }
                if (strstr($v, 'posted_principal') <> '') {
                        @list($junk, $posted_principal) = explode('=>', $v);
                } elseif (strstr($v, '[debits]') <> '') {
                        $get_debits = true;
                }
        }

        if ($posted_principal < 200) {
                $debits_arr = explode("stdClass Object", $debits);
                $keep_on_list = false;
                foreach ($debits_arr as $debit_arr_line) {
                        if ($debit_arr_line) {
                                $keys = $values = array();
                                $key = $value = '';
                                for ($i = 0; $i < strlen($debit_arr_line); $i++) {
                                        $ch = $debit_arr_line[$i];
                                        if ($ch == ']') {
                                                $get_key = false;
                                                if ($key) {
                                                        $keys[] = $key;
                                                        $key = '';
                                                }
                                        } elseif ($ch == '[') {
                                                $get_key = true;
                                                $get_val = false;
                                                if (trim($value) && $value <> '(') {
                                                        $values[] = str_replace("=> ", "", $value);
                                                        $value = '';
                                                } elseif ($value == '(') {
                                                        $value = '';
                                                }
                                        } elseif (!$get_key && $ch == '=') {
                                                $get_val = true;
                                        }
                                        if ($get_val) {
                                                $value .= $ch;
                                        } elseif ($get_key && $ch <> '[') {
                                                $key .= $ch;
                                        }
                                }
                                $ach_id_idx = array_search('ach_id', $keys);
                                $debit_type_id_idx = array_search('type_id', $keys);
                                $ach_id = $debit_type_id = 0;
                                if ($ach_id_idx && $debit_type_id_idx) {
                                        $debit_type_id = $values[$debit_type_id_idx];
                                        $ach_id = $values[$ach_id_idx];
                                }

                                $keep_on_list |= ($ach_id && !(array_search($debit_type_id, $refi_qual_ach_pmt_types) === false));
                        }
                }

                if ($keep_on_list) {
                        unset($row['loan_type_id']);
                        $app_ids[] = $app_id;
                        fputcsv($fp, $row);
                        echo "[rec# ".$x."] app ID = ".$app_id.", Posted Principal = ".number_format($posted_principal)."\n";

                } else {
                        $app_ids_no_achs[] = $app_id;
			echo "[rec# ".$x."] app ID = ".$app_id.", No ach payments\n";
                }
        } else {
                $app_ids_too_high_principal[] = $app_id;
		echo "[rec# ".$x."] app ID = ".$app_id.", Principal too high (".number_format($posted_principal).")\n";
        }
}

echo "\nDone.\n\n ".number_format($x)." records processed.\n\n";

if (count($app_ids_no_achs) > 0) {
        echo "Application IDs with no ach payments: (".implode(", ", $app_ids_no_achs).")\n";
}
if (count($app_ids_too_high_principal) > 0) {
        echo "Application IDs with principal too high: (".implode(", ", $app_ids_too_high_principal).")\n";
}
echo "\nFound Application IDs = (".implode(", ", $app_ids).")";

fclose($fp);

echo "\nOutput file: /tmp/refi_qualifiers_{$date_start}-{$date_end}_run_{$date_today}.csv\n";
