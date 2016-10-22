<?php

// Usage:
// php driver_tokens.php <application_id> <token>

putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');
require_once(SQL_LIB_DIR.'scheduling.func.php');
require_once CUSTOMER_LIB . 'failure_dfa.php';
require_once(COMMON_LIB_DIR . 'pay_date_calc.3.php');

if ($_SERVER['argc'] != 3)
{
echo "Usage: php driver_tokens.php <application_id> <token>, for example, php driver_tokens.php 987654321 LoanFinCharge\n";
exit(1);
}

$application_id = $_SERVER['argv'][1];
$token = $_SERVER['argv'][2];

$company_id = 1;

$db = ECash::getMasterDb();

$tokens = ECash::getApplicationById($application_id)->getTokenProvider()->getTokens();

echo $token, ": ", $tokens[$token], "\n";

?>
