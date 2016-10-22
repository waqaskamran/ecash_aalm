<?php
/**
php data_fix_set_flag.php <application_id> <flag>, for example, data_fix_set_flag.php 987654321 has_fatal_ach_failure
*/
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');
require_once CUSTOMER_LIB . 'failure_dfa.php';

if ($_SERVER['argc'] != 3)
{
echo "Usage: php data_fix_set_flag.php <application_id> <flag> for example: data_fix_set_flag.php 987654321 has_fatal_ach_failure\n";
exit(1);
}

$application_id = $_SERVER['argv'][1];
$flag = $_SERVER['argv'][2];

echo $application_id, " ", $flag, "\n";

set_flag($application_id, $flag);

function set_flag($application_id, $flag)
{
        $app =  ECash::getApplicationByID($application_id);

        $flags = $app->getFlags();
        // only set it if its not set already
        $flags->set($flag);
}
?>

