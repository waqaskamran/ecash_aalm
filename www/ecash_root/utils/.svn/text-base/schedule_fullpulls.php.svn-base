<?php
define ('DEFAULT_SOURCE_TYPE', 'manual');
include "../www/config.php";
Set_Company_Constants('clk');

require_once(SQL_LIB_DIR.'util.func.php');
require_once(SQL_LIB_DIR.'get_mysqli.func.php');
require_once(SQL_LIB_DIR.'scheduling.func.php');
require_once(LIB_DIR.'common_functions.php');
require_once(COMMON_LIB_DIR.'pay_date_calc.3.php');

$fh = fopen('php://stdin', 'r');
$mysqli = get_mysqli();

$_SESSION['company_id'] = 3;
$_SESSION['company'] = 'ufc';

echo "To schedule a full pull for an application enter the application id on a blank line.\n";

while ($application_id = trim(fgets($fh))) {
	try {
		$mysqli->Start_Transaction();
		Remove_Unregistered_Events_From_Schedule($application_id);
		Schedule_Full_Pull($application_id);
		$mysqli->Commit();
		echo "{$application_id} Full Pulled\n";
	} catch (Exception $e) {
		echo "{$application_id} Error: {$e->getMessage()}\n";
		$mysqli->Rollback();
	}
}

fclose($fh);
