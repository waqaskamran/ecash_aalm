<?php
require_once("bank_holidays.php");
require_once("../lib/mysqli.1.php");
require_once(COMMON_LIB_DIR . "pay_date_calc.3.php");

$db1 = new MySQLi_1('db1.clkonline.com', 'test_ecash', '3cash', 'ldb', 13306);
$bh = new Bank_Holidays($db1);
$holidays = $bh->Get_Holidays();
$pdc = new Pay_Date_Calc_3($holidays); 

$old_date = $pdc->Get_Business_Days_Backward("2006-04-15", 3);
echo "Old date is {$old_date}\n";

$info = new stdClass();

$info->direct_deposit = false;
$info->day_int_one = null;
$info->day_int_two = null;
$info->day_of_month_1 = null;
$info->day_of_month_2 = null;
$info->day_int_one = null;
$info->day_string_one = 'thu';
$info->week_one = null;
$info->week_two = null;
$info->paydate_model = 'dwpd';
$info->last_paydate = '2006-03-31';

$dates = $pdc->Calculate_Pay_Dates($info->paydate_model, $info,
				   $info->direct_deposit, 30, '2006-3-31');

print_r($dates);

?>